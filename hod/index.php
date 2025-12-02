<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../db.php';
checkRole(['hod']);

$user = getCurrentUser();
$department_id = $_SESSION['department_id'];

// Get current academic year from URL or default
$current_year = isset($_GET['year']) ? $_GET['year'] : "2025-2026";

// Get department info
$dept_query = "SELECT * FROM departments WHERE id = $department_id";
$dept_result = $conn->query($dept_query);
$department = $dept_result->fetch_assoc();

// Get statistics
$stats = [];

// Total teachers in department
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'teacher' AND department_id = $department_id AND is_active = 1");
$stats['teachers'] = $result->fetch_assoc()['count'];

// Total students in department (all years if academic_year column doesn't exist)
$student_count_query = "SELECT COUNT(*) as count FROM students WHERE department_id = $department_id AND is_active = 1";
// Check if academic_year column exists
$check_column = $conn->query("SHOW COLUMNS FROM students LIKE 'academic_year'");
if ($check_column && $check_column->num_rows > 0) {
    $student_count_query = "SELECT COUNT(*) as count FROM students WHERE department_id = $department_id AND is_active = 1 AND academic_year = '$current_year'";
}
$result = $conn->query($student_count_query);
$stats['students'] = $result->fetch_assoc()['count'];

// Total classes in department (all years if academic_year column doesn't exist)
$class_count_query = "SELECT COUNT(*) as count FROM classes WHERE department_id = $department_id";
$check_column = $conn->query("SHOW COLUMNS FROM classes LIKE 'academic_year'");
if ($check_column && $check_column->num_rows > 0) {
    $class_count_query = "SELECT COUNT(*) as count FROM classes WHERE department_id = $department_id AND academic_year = '$current_year'";
}
$result = $conn->query($class_count_query);
$stats['classes'] = $result->fetch_assoc()['count'];

// Today's attendance in department
$today = date('Y-m-d');
$today_query = "SELECT COUNT(*) as total,
                SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN sa.status = 'absent' THEN 1 ELSE 0 END) as absent
                FROM student_attendance sa
                JOIN classes c ON sa.class_id = c.id
                WHERE c.department_id = $department_id AND sa.attendance_date = '$today'";
$today_result = $conn->query($today_query);
$today_stats = $today_result->fetch_assoc();

// Get all available academic years (only if column exists)
$available_years = array("2025-2026", "2024-2025", "2023-2024"); // Default years
$check_column = $conn->query("SHOW COLUMNS FROM classes LIKE 'academic_year'");
if ($check_column && $check_column->num_rows > 0) {
    $years_query = "SELECT DISTINCT academic_year FROM classes WHERE department_id = $department_id AND academic_year IS NOT NULL ORDER BY academic_year DESC";
    $years_result = $conn->query($years_query);
    if ($years_result && $years_result->num_rows > 0) {
        $available_years = [];
        while ($year_row = $years_result->fetch_assoc()) {
            $available_years[] = $year_row['academic_year'];
        }
    }
}

// Get department teachers
$teachers_query = "SELECT * FROM users WHERE role = 'teacher' AND department_id = $department_id AND is_active = 1 ORDER BY full_name";
$teachers = $conn->query($teachers_query);

// Get department classes with attendance
$classes_query = "SELECT c.*, u.full_name as teacher_name,
                  (SELECT COUNT(*) FROM students WHERE class_id = c.id AND is_active = 1) as student_count,
                  (SELECT COUNT(*) FROM student_attendance WHERE class_id = c.id AND attendance_date = '$today') as today_marked
                  FROM classes c
                  LEFT JOIN users u ON c.teacher_id = u.id
                  WHERE c.department_id = $department_id
                  ORDER BY c.class_name";
$classes = $conn->query($classes_query);

// Get students
$students_query = "SELECT s.*, c.class_name 
                   FROM students s
                   LEFT JOIN classes c ON s.class_id = c.id
                   WHERE s.department_id = $department_id AND s.is_active = 1
                   ORDER BY s.full_name";
$students = $conn->query($students_query);

// Include notices component
$notices_path = __DIR__ . '/../admin/notices_component.php';
if (!file_exists($notices_path)) {
    $notices_path = __DIR__ . '/notices_component.php';
}
if (file_exists($notices_path)) {
    require_once $notices_path;
}




// Get count of student resumes
$resume_count_query = "SELECT COUNT(*) as count FROM student_resumes";
$resume_count_result = $conn->query($resume_count_query);
$resume_count = $resume_count_result->fetch_assoc()['count'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../Nit_logo.png" type="image/svg+xml" />
    <title>HOD Dashboard - NIT AMMS</title>
    <link rel="stylesheet" href="hod_style_new.css">
    <style>
        /* HOD Dashboard - Enhanced Modern Styles */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
    min-height: 100vh;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    position: relative;
    overflow-x: hidden;
}

/* Animated Background Particles */
.particles {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 0;
    pointer-events: none;
}

.particle {
    position: absolute;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 50%;
    animation: float 15s infinite ease-in-out;
}

@keyframes float {
    0%, 100% { transform: translateY(0) rotate(0deg); opacity: 0; }
    10% { opacity: 1; }
    90% { opacity: 1; }
    100% { transform: translateY(-100vh) rotate(360deg); opacity: 0; }
}

/* Enhanced Navbar */
.navbar {
    background: rgba(26, 31, 58, 0.95);
    backdrop-filter: blur(20px);
    padding: 20px 40px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    border-bottom: 2px solid rgba(255, 255, 255, 0.1);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.navbar-brand {
    display: flex;
    align-items: center;
    gap: 15px;
}

.navbar-logo {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    animation: rotateLogo 10s linear infinite;
}

@keyframes rotateLogo {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.navbar h1 {
    color: white;
    font-size: 24px;
    font-weight: 700;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}

.user-info {
    display: flex;
    align-items: center;
    gap: 25px;
    color: white;
}

.user-profile {
    display: flex;
    align-items: center;
    gap: 12px;
    background: rgba(255, 255, 255, 0.1);
    padding: 10px 20px;
    border-radius: 50px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #f093fb, #f5576c);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: bold;
}

.main-content {
    padding: 40px;
    max-width: 1600px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
}

/* Hero Welcome Section */
.hero-welcome {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    padding: 50px;
    border-radius: 30px;
    margin-bottom: 40px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    position: relative;
    overflow: hidden;
    border: 2px solid rgba(255, 255, 255, 0.5);
}

.hero-background {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
    opacity: 0.1;
    z-index: 0;
}

.animated-wave {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 100px;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120"><path d="M985.66,92.83C906.67,72,823.78,31,743.84,14.19c-82.26-17.34-168.06-16.33-250.45.39-57.84,11.73-114,31.07-172,41.86A600.21,600.21,0,0,1,0,27.35V120H1200V95.8C1132.19,118.92,1055.71,111.31,985.66,92.83Z" fill="%23667eea" opacity="0.2"/></svg>');
    background-size: cover;
    animation: wave 8s linear infinite;
    z-index: 0;
}

@keyframes wave {
    0% { background-position: 0 0; }
    100% { background-position: 1200px 0; }
}

.hero-content {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 40px;
    align-items: center;
}

.hero-text h2 {
    font-size: 42px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 15px;
    font-weight: 800;
}

.hero-text p {
    font-size: 18px;
    color: #666;
    margin-bottom: 10px;
}

/* Year Selector */
.year-selector {
    display: flex;
    align-items: center;
    background: rgba(255, 255, 255, 0.5);
    padding: 12px 20px;
    border-radius: 15px;
    border: 2px solid rgba(102, 126, 234, 0.3);
}

.year-select {
    padding: 8px 16px;
    border: 2px solid rgba(102, 126, 234, 0.5);
    border-radius: 10px;
    background: white;
    font-size: 16px;
    font-weight: 600;
    color: #2c3e50;
    cursor: pointer;
    transition: all 0.3s;
}

.year-select:hover {
    border-color: #667eea;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.year-select:focus {
    outline: none;
    border-color: #764ba2;
    box-shadow: 0 0 0 3px rgba(118, 75, 162, 0.2);
}

.hero-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 25px;
    margin-top: 30px;
}

.hero-stat-item {
    text-align: center;
    background: rgba(255, 255, 255, 0.5);
    padding: 20px;
    border-radius: 15px;
    border: 2px solid rgba(102, 126, 234, 0.2);
    transition: all 0.3s ease;
}

.hero-stat-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
    border-color: rgba(102, 126, 234, 0.5);
}

.hero-stat-value {
    font-size: 36px;
    font-weight: 700;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 5px;
}

.hero-stat-label {
    font-size: 13px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
}

/* Glass Clock */
.glass-clock {
    background: rgba(255, 255, 255, 0.3);
    backdrop-filter: blur(20px);
    padding: 30px;
    border-radius: 25px;
    text-align: center;
    border: 2px solid rgba(255, 255, 255, 0.5);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    min-width: 280px;
}

.clock-icon {
    font-size: 48px;
    margin-bottom: 15px;
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.glass-clock .time {
    font-size: 48px;
    font-weight: 800;
    font-family: 'Courier New', monospace;
    color: #2c3e50;
    letter-spacing: 2px;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.glass-clock .date {
    font-size: 14px;
    color: #666;
    margin-top: 10px;
    font-weight: 500;
}

/* Premium Stats Cards */
.premium-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 25px;
    margin: 40px 0;
}

.premium-stat-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    padding: 30px;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    position: relative;
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border: 2px solid rgba(255, 255, 255, 0.5);
}

.premium-stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, #667eea, #764ba2, #f093fb);
    background-size: 200% 100%;
    animation: gradientShift 3s ease infinite;
}

@keyframes gradientShift {
    0%, 100% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
}

.premium-stat-card:hover {
    transform: translateY(-10px) scale(1.02);
    box-shadow: 0 20px 60px rgba(102, 126, 234, 0.4);
}

.stat-icon-wrapper {
    width: 70px;
    height: 70px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    margin-bottom: 20px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    animation: iconFloat 3s ease-in-out infinite;
}

@keyframes iconFloat {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.stat-details h4 {
    color: #666;
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 10px;
}

.stat-value-large {
    font-size: 42px;
    font-weight: 800;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 10px;
}

/* Tables */
.table-container {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 25px;
    padding: 40px;
    box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
    margin: 30px 0;
    border: 2px solid rgba(255, 255, 255, 0.5);
    scroll-margin-top: 100px; /* For smooth scroll to anchor */
}

.table-container h3 {
    font-size: 24px;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    gap: 10px;
}

table {
    width: 100%;
    border-collapse: collapse;
}

thead {
    background: linear-gradient(135deg, #667eea, #764ba2);
}

thead th {
    padding: 15px;
    color: white;
    font-weight: 600;
    text-align: left;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

tbody tr {
    border-bottom: 1px solid #f0f0f0;
    transition: all 0.3s;
}

tbody tr:hover {
    background: rgba(102, 126, 234, 0.05);
    transform: translateX(5px);
}

tbody td {
    padding: 18px 15px;
    color: #2c3e50;
    font-size: 14px;
}

/* Badges */
.badge {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-block;
}

.badge-success {
    background: #d4edda;
    color: #155724;
}

.badge-warning {
    background: #fff3cd;
    color: #856404;
}

.badge-info {
    background: #d1ecf1;
    color: #0c5460;
}

/* Buttons */
.btn {
    padding: 12px 24px;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s;
    display: inline-block;
    border: none;
    cursor: pointer;
    font-size: 14px;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
}

.btn-success {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
}

.btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.6);
}

.btn-secondary {
    background: linear-gradient(135deg, #6c757d, #5a6268);
    color: white;
    box-shadow: 0 4px 15px rgba(108, 117, 125, 0.4);
}

.btn-danger {
    background: linear-gradient(135deg, #ff6b6b, #ee5a5a);
    color: white;
    box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4);
}

.btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 107, 107, 0.6);
}

.btn-sm {
    padding: 8px 16px;
    font-size: 13px;
}

/* Footer */
.footer {
    background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 50%, #2a3254 100%);
    position: relative;
    overflow: hidden;
    margin-top: 60px;
}

.footer-border {
    height: 2px;
    background: linear-gradient(90deg, #4a9eff, #00d4ff, #4a9eff, #00d4ff);
    background-size: 200% 100%;
    animation: borderMove 3s linear infinite;
}

@keyframes borderMove {
    0% { background-position: 0% 50%; }
    100% { background-position: 200% 50%; }
}

.footer-content {
    max-width: 1000px;
    margin: 0 auto;
    padding: 30px 20px 20px;
}

.developer-section {
    background: rgba(255, 255, 255, 0.03);
    padding: 20px;
    border-radius: 15px;
    border: 1px solid rgba(74, 158, 255, 0.15);
    text-align: center;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
}

.developer-section p {
    color: #ffffff;
    font-size: 14px;
    margin: 0 0 12px;
    font-weight: 500;
    letter-spacing: 0.5px;
}

.company-link {
    display: inline-block;
    color: #ffffff;
    font-size: 16px;
    font-weight: 700;
    text-decoration: none;
    padding: 8px 24px;
    border: 2px solid #4a9eff;
    border-radius: 30px;
    background: linear-gradient(135deg, rgba(74, 158, 255, 0.2), rgba(0, 212, 255, 0.2));
    box-shadow: 0 3px 12px rgba(74, 158, 255, 0.3);
    margin-bottom: 15px;
    transition: all 0.3s;
}

.company-link:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(74, 158, 255, 0.5);
}

.divider {
    width: 50%;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    margin: 15px auto;
}

.team-label {
    color: #888;
    font-size: 10px;
    margin: 0 0 12px;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    font-weight: 600;
}

.developer-badges {
    display: flex;
    justify-content: center;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 12px;
}

.developer-badge {
    color: #ffffff;
    font-size: 13px;
    text-decoration: none;
    padding: 8px 16px;
    background: linear-gradient(135deg, rgba(74, 158, 255, 0.25), rgba(0, 212, 255, 0.25));
    border-radius: 20px;
    border: 1px solid rgba(74, 158, 255, 0.4);
    display: inline-flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 3px 10px rgba(74, 158, 255, 0.2);
    transition: all 0.3s;
}

.developer-badge:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(74, 158, 255, 0.4);
}

.role-tags {
    margin-top: 15px;
    display: flex;
    justify-content: center;
    gap: 10px;
    flex-wrap: wrap;
}

.role-tag {
    color: #4a9eff;
    font-size: 10px;
    padding: 4px 12px;
    background: rgba(74, 158, 255, 0.1);
    border-radius: 12px;
    border: 1px solid rgba(74, 158, 255, 0.3);
}

.footer-bottom {
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    text-align: center;
}

.footer-bottom p {
    color: #888;
    font-size: 12px;
    margin: 0 0 10px;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .premium-stats { grid-template-columns: repeat(2, 1fr); }
    .hero-stats { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 768px) {
    .navbar { padding: 15px 20px; flex-direction: column; gap: 15px; }
    .navbar h1 { font-size: 18px; }
    .main-content { padding: 20px; }
    .hero-welcome { padding: 30px 20px; }
    .hero-content { grid-template-columns: 1fr; text-align: center; }
    .hero-text h2 { font-size: 28px; }
    .hero-stats { grid-template-columns: repeat(2, 1fr); gap: 15px; }
    .glass-clock { min-width: 100%; margin-top: 20px; }
    .premium-stats { grid-template-columns: 1fr; gap: 15px; }
    .table-container { padding: 20px; overflow-x: auto; }
    table { font-size: 12px; }
    .user-info { flex-direction: column; gap: 10px; }
    .year-selector { flex-direction: column; gap: 10px; }
}

@media (max-width: 480px) {
    .stat-value-large { font-size: 32px; }
    .developer-badges { flex-direction: column; }
    .hero-text h2 { font-size: 24px; }
    .hero-stats { grid-template-columns: 1fr; }
    .hero-stat-value { font-size: 28px; }
}







.quick-action-card {
    background: white;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    transition: all 0.3s;
    margin-bottom: 20px;
}

.quick-action-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
}

.action-link {
    display: flex;
    align-items: center;
    gap: 20px;
    text-decoration: none;
    color: inherit;
}

.action-icon {
    width: 60px;
    height: 60px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    flex-shrink: 0;
}

.action-content {
    flex: 1;
}

.action-content h3 {
    font-size: 18px;
    color: #333;
    margin-bottom: 5px;
}

.action-content p {
    font-size: 14px;
    color: #666;
}

.action-arrow {
    font-size: 24px;
    color: #667eea;
    font-weight: bold;
}
    </style>

    <script>
        /* HOD Dashboard JavaScript */

// Live Clock Function
function updateClock() {
    const now = new Date();
    
    // Time in 12-hour format
    let hours = now.getHours();
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    
    // Convert to 12-hour format
    hours = hours % 12;
    hours = hours ? hours : 12; // If hour is 0, make it 12
    hours = String(hours).padStart(2, '0');
    
    const clockElement = document.getElementById('liveClock');
    if (clockElement) {
        clockElement.textContent = `${hours}:${minutes}:${seconds} ${ampm}`;
    }
    
    // Date
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const dateString = now.toLocaleDateString('en-US', options);
    const dateElement = document.getElementById('liveDate');
    if (dateElement) {
        dateElement.textContent = dateString;
    }
}

// Filter by Year Function
function filterByYear(year) {
    if (year) {
        showToast('Loading data for ' + year + '...', 'info');
        
        // Reload page with year parameter
        const url = new URL(window.location.href);
        url.searchParams.set('year', year);
        window.location.href = url.toString();
    }
}

// Update clock immediately and then every second
updateClock();
setInterval(updateClock, 1000);

// Create more animated particles dynamically
function createParticles() {
    const particlesContainer = document.querySelector('.particles');
    if (!particlesContainer) return;
    
    for (let i = 0; i < 15; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.width = Math.random() * 15 + 5 + 'px';
        particle.style.height = particle.style.width;
        particle.style.left = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 15 + 's';
        particle.style.animationDuration = Math.random() * 10 + 10 + 's';
        particlesContainer.appendChild(particle);
    }
}

// Initialize particles on page load
document.addEventListener('DOMContentLoaded', function() {
    createParticles();
    
    // Add smooth scroll behavior
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Add fade-in animation for stat cards
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    // Observe all stat cards
    document.querySelectorAll('.premium-stat-card, .table-container').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        observer.observe(card);
    });
    
    // Add click animation to buttons
    document.querySelectorAll('.btn').forEach(button => {
        button.addEventListener('click', function(e) {
            // Create ripple effect
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.classList.add('ripple');
            
            this.appendChild(ripple);
            
            setTimeout(() => ripple.remove(), 600);
        });
    });
    
    // Table row highlight
    document.querySelectorAll('tbody tr').forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = 'rgba(102, 126, 234, 0.08)';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
        });
    });
    
    // Year selector change animation
    const yearSelect = document.getElementById('academicYear');
    if (yearSelect) {
        yearSelect.addEventListener('change', function() {
            this.style.transform = 'scale(1.05)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 200);
        });
    }
});

// Add CSS for ripple effect
const style = document.createElement('style');
style.textContent = `
    .btn {
        position: relative;
        overflow: hidden;
    }
    
    .ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.5);
        transform: scale(0);
        animation: ripple-animation 0.6s ease-out;
        pointer-events: none;
    }
    
    @keyframes ripple-animation {
        to {
            transform: scale(2);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Toast notification function
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    
    toast.style.cssText = `
        position: fixed;
        bottom: 30px;
        right: 30px;
        padding: 15px 25px;
        background: ${type === 'success' ? 'linear-gradient(135deg, #28a745, #20c997)' : 
                     type === 'error' ? 'linear-gradient(135deg, #ff6b6b, #ee5a5a)' : 
                     'linear-gradient(135deg, #667eea, #764ba2)'};
        color: white;
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        z-index: 9999;
        font-weight: 600;
        animation: slideInRight 0.3s ease-out;
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease-out';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Add toast animations
const toastStyle = document.createElement('style');
toastStyle.textContent = `
    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(100px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes slideOutRight {
        from {
            opacity: 1;
            transform: translateX(0);
        }
        to {
            opacity: 0;
            transform: translateX(100px);
        }
    }
`;
document.head.appendChild(toastStyle);

// Expose functions globally
window.showToast = showToast;
window.updateClock = updateClock;
window.filterByYear = filterByYear;

// Console log for developers
console.log('%c🎓 NIT AMMS - HOD Dashboard', 'color: #667eea; font-size: 24px; font-weight: bold;');
console.log('%c✨ Developed by Techyug Software Pvt. Ltd.', 'color: #764ba2; font-size: 14px;');
console.log('%c👨‍💻 Developers: Himanshu Patil & Pranay Panore', 'color: #f093fb; font-size: 12px;');

// Performance monitoring
if ('performance' in window) {
    window.addEventListener('load', function() {
        const perfData = performance.getEntriesByType('navigation')[0];
        console.log(`⚡ Page loaded in ${Math.round(perfData.loadEventEnd - perfData.fetchStart)}ms`);
    });
}
    </script>
</head>
<body>
    <!-- Animated Background Particles -->
    <div class="particles">
        <div class="particle" style="width: 10px; height: 10px; left: 10%; animation-delay: 0s;"></div>
        <div class="particle" style="width: 15px; height: 15px; left: 20%; animation-delay: 2s;"></div>
        <div class="particle" style="width: 8px; height: 8px; left: 30%; animation-delay: 4s;"></div>
        <div class="particle" style="width: 12px; height: 12px; left: 50%; animation-delay: 1s;"></div>
        <div class="particle" style="width: 10px; height: 10px; left: 70%; animation-delay: 3s;"></div>
        <div class="particle" style="width: 14px; height: 14px; left: 85%; animation-delay: 5s;"></div>
    </div>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-brand">
            <div class="navbar-logo">🎓</div>
            <h1>NIT AMMS - HOD Panel</h1>
        </div>
        <div class="user-info">
            <a href="profile.php" class="btn btn-secondary btn-sm">👤 My Profile</a>
            <div class="user-profile">
                <div class="user-avatar">👔</div>
                <span><?php echo htmlspecialchars($user['full_name']); ?></span>
            </div>
            <a href="../logout.php" class="btn btn-danger btn-sm">🚪 Logout</a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Hero Welcome Section -->
        <div class="hero-welcome">
            <div class="hero-background"></div>
            <div class="animated-wave"></div>
            <div class="hero-content">
                <div class="hero-text">
                    <h2>🏢 <?php echo htmlspecialchars($department['dept_name']); ?> Department</h2>
                    <p>Welcome back, <strong><?php echo htmlspecialchars($user['full_name']); ?>!</strong> 👋 Here's your comprehensive department overview.</p>
                    <div style="margin-top: 15px; display: flex; gap: 20px; align-items: center; flex-wrap: wrap;">
                        <p><strong>Department Code:</strong> <?php echo htmlspecialchars($department['dept_code']); ?></p>
                        <div class="year-selector">
                            <label for="academicYear" style="font-weight: 600; margin-right: 10px;">📅 Academic Year:</label>
                            <select id="academicYear" class="year-select" onchange="filterByYear(this.value)">
                                <?php foreach ($available_years as $year): ?>
                                    <option value="<?php echo htmlspecialchars($year); ?>" <?php echo $year === $current_year ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($year); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="hero-stats">
                        <div class="hero-stat-item">
                            <div class="hero-stat-value"><?php echo $stats['teachers']; ?></div>
                            <div class="hero-stat-label">Total Teachers</div>
                        </div>
                        <div class="hero-stat-item">
                            <div class="hero-stat-value"><?php echo $stats['students']; ?></div>
                            <div class="hero-stat-label">Total Students</div>
                        </div>
                    
                    </div>
                </div>
                <div class="glass-clock">
                    <div class="clock-icon">⏰</div>
                    <div class="time" id="liveClock">--:--:--</div>
                    <div class="date" id="liveDate">Loading...</div>
                </div>
            </div>
        </div>



        <!-- Premium Stats Cards -->
        <div class="premium-stats">
            <div class="premium-stat-card">
                <div class="stat-icon-wrapper">📊</div>
                <div class="stat-details">
                    <h4>Academic Year</h4>
                    <div class="stat-value-large"><?php echo htmlspecialchars($current_year); ?></div>
                </div>
            </div>

            <div class="premium-stat-card">
                <div class="stat-icon-wrapper">👨‍🏫</div>
                <div class="stat-details">
                    <h4>Total Teachers</h4>
                    <div class="stat-value-large"><?php echo $stats['teachers']; ?></div>
                    <a href="view_teachers.php" class="btn btn-primary btn-sm">View Teachers</a>
                </div>
            </div>

            <div class="premium-stat-card">
                <div class="stat-icon-wrapper">👨‍🎓</div>
                <div class="stat-details">
                    <h4>Total Students</h4>
                    <div class="stat-value-large"><?php echo $stats['students']; ?></div>
                    <a href="view_students.php" class="btn btn-primary btn-sm">View Students</a>
                </div>
            </div>

            <div class="premium-stat-card">
                <div class="stat-icon-wrapper">📚</div>
                <div class="stat-details">
                    <h4>Total Classes</h4>
                    <div class="stat-value-large"><?php echo $stats['classes']; ?></div>
                    <a href="#classesTable" class="btn btn-success btn-sm">📊 View Classes</a>
                </div>
            </div>

            <div class="premium-stat-card">
                <div class="stat-icon-wrapper">📋</div>
                <div class="stat-details">
                    <h4>Today's Attendance</h4>
                    <div class="stat-value-large">
                        ✅ <?php echo $today_stats['present'] ?? 0; ?> | 
                        ❌ <?php echo $today_stats['absent'] ?? 0; ?>
                    </div>
                    <a href="view_department_attendance.php" class="btn btn-success btn-sm">📊 View Details</a>
                </div>
            </div>
            
        </div>

<!-- Add this button in your quick actions/menu section -->
<div class="quick-action-card">
    <a href="student_resumes.php" class="action-link">
        <div class="action-icon" style="background: linear-gradient(135deg, #667eea, #764ba2);">
            📄
        </div>
        <div class="action-content">
            <h3>Student Resumes</h3>
            <p>View all student resumes (<?php echo $resume_count; ?> available)</p>
        </div>
        <div class="action-arrow">→</div>
    </a>
</div>




<a href="view_all_marks.php" class="btn btn-primary">📊 View All Marks</a>
<a href="manage_notices.php" class="btn btn-primary"> Message Notices</a>
<a href="call.php" class="btn btn-primary"> Teacher CALL</a>
<a href="timetable.php" class="btn btn-primary"> Time Table</a>
<a href="new.php" class="btn btn-primary"> Daily News</a>
<a href="dailyreport.php" class="btn btn-primary"> Daily Report</a>

<a href="hod_view_timetable.php" class="btn btn-primary">📅 Department Timetables</a>


<a href="hod_create_examtime.php" class="btn btn-primary">exam</a>



                <!-- DISPLAY NOTICES HERE FOR HODs -->
        <?php 
        if (function_exists('displayNotices')) {
            displayNotices('hods');
        }
        ?>



        <!-- Department Classes Table -->
        <div class="table-container" id="classesTable">
            <h3>📚 Department Classes - Today's Attendance Status</h3>
            <table>
                <thead>
                    <tr>
                        <th>Class Name</th>
                        <th>Year</th>
                        <th>Section</th>
                        <th>Teacher</th>
                        <th>Total Students</th>
                        <th>Today's Marked</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($classes && $classes->num_rows > 0) {
                        $classes->data_seek(0);
                        while ($class = $classes->fetch_assoc()): 
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($class['class_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($class['year']); ?></td>
                        <td><?php echo htmlspecialchars($class['section']); ?></td>
                        <td><?php echo htmlspecialchars($class['teacher_name'] ?? 'Not Assigned'); ?></td>
                        <td><?php echo $class['student_count']; ?></td>
                        <td><?php echo $class['today_marked']; ?></td>
                        <td>
                            <?php if ($class['today_marked'] > 0): ?>
                                <span class="badge badge-success">✅ Marked</span>
                            <?php else: ?>
                                <span class="badge badge-warning">⏳ Pending</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                    } else {
                        echo '<tr><td colspan="7" style="text-align: center; padding: 30px;">No classes found in this department</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Department Students Table -->
        <div class="table-container" id="studentsTable">
            <h3>👨‍🎓 Department Students</h3>
            <table>
                <thead>
                    <tr>
                        <th>Roll No</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Class</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($students && $students->num_rows > 0) {
                        $students->data_seek(0);
                        while ($student = $students->fetch_assoc()): 
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($student['roll_number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                        <td><?php echo htmlspecialchars($student['phone']); ?></td>
                        <td><?php echo htmlspecialchars($student['class_name'] ?? 'Not Assigned'); ?></td>
                        <td>
                            <span class="badge badge-success">Active</span>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                    } else {
                        echo '<tr><td colspan="6" style="text-align: center; padding: 30px;">No students found in this department</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Department Teachers Table -->
        <div class="table-container">
            <h3>👨‍🏫 Department Teachers</h3>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Username</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $teachers->data_seek(0);
                    while ($teacher = $teachers->fetch_assoc()): 
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($teacher['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                        <td><?php echo htmlspecialchars($teacher['phone']); ?></td>
                        <td><?php echo htmlspecialchars($teacher['username']); ?></td>
                        <td>
                            <span class="badge badge-success">Active</span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

  <!-- Footer -->
    <div class="footer">
        <div class="footer-border"></div>
        <div class="footer-content">
            <div class="developer-section">
                <p>✨ Designed & Developed by</p>
                <a href="https://himanshufullstackdeveloper.github.io/techyugsoftware/" class="company-link">
                    🚀 Techyug Software Pvt. Ltd.
                </a>
                <div class="divider"></div>
                <p class="team-label">💼 Development Team</p>
                <div class="developer-badges">
                    <a href="https://himanshufullstackdeveloper.github.io/portfoilohimanshu/" class="developer-badge">
                        <span>👨‍💻</span>
                        <span style="font-weight: 600;">Himanshu Patil</span>
                    </a>
                    <a href="https://devpranaypanore.github.io/Pranaypanore-live-.html/" class="developer-badge">
                        <span>👨‍💻</span>
                        <span style="font-weight: 600;">Pranay Panore</span>
                    </a>
                </div>
                <div class="role-tags">
                    <span class="role-tag">Full Stack</span>
                    <span class="role-tag">UI/UX</span>
                    <span class="role-tag">Database</span>
                </div>
            </div>
            <div class="footer-bottom">
                <p>© 2025 NIT AMMS. All rights reserved.</p>
                <p style="color: #666; font-size: 11px;">
                    Made with <span style="color: #ff4757; font-size: 14px;">❤️</span> by Techyug Software
                </p>
            </div>
        </div>
    </div>

    <script src="hod_script_new.js"></script>
</body>
</html>