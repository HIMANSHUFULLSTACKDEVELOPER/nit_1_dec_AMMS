<?php
require_once '../db.php';
checkRole(['teacher']);




$user = getCurrentUser();

// Get current academic year
$current_year = date('Y');
$default_academic_year = $current_year . '-' . ($current_year + 1);

// Get selected academic year from URL parameter or use default
$selected_academic_year = isset($_GET['academic_year']) ? sanitize($_GET['academic_year']) : $default_academic_year;

// Get all available academic years from classes table
$years_query = "SELECT DISTINCT academic_year FROM classes WHERE teacher_id = ? ORDER BY academic_year DESC";
$stmt = $conn->prepare($years_query);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$available_years_result = $stmt->get_result();
$available_years = [];
while ($year_row = $available_years_result->fetch_assoc()) {
    $available_years[] = $year_row['academic_year'];
}

// If no years found, add current year as default
if (empty($available_years)) {
    $available_years = [$default_academic_year];
}

// FIXED QUERY: Get all classes assigned to this teacher for selected academic year with CORRECT student count
$classes_query = "SELECT 
    c.id,
    c.class_name,
    c.section,
    c.year,
    c.semester,
    c.academic_year,
    d.dept_name,
    d.id as dept_id,
    COALESCE(
        (SELECT COUNT(DISTINCT s.id)
         FROM students s
         INNER JOIN classes c2 ON s.class_id = c2.id
         WHERE c2.section = c.section
         AND c2.year = c.year
         AND c2.semester = c.semester
         AND c2.academic_year = c.academic_year
         AND s.is_active = 1
        ), 0
    ) as student_count
FROM classes c
JOIN departments d ON c.department_id = d.id
WHERE c.teacher_id = ? AND c.academic_year = ?
GROUP BY c.id, c.class_name, c.section, c.year, c.semester, c.academic_year, d.dept_name, d.id
ORDER BY c.section, c.year, c.semester";

$stmt = $conn->prepare($classes_query);
$stmt->bind_param("is", $user['id'], $selected_academic_year);
$stmt->execute();
$classes = $stmt->get_result();

// Get today's attendance stats for selected academic year
$today = date('Y-m-d');
$stats_query = "SELECT 
    COUNT(DISTINCT sa.student_id) as marked_today,
    SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) as present_today,
    SUM(CASE WHEN sa.status = 'absent' THEN 1 ELSE 0 END) as absent_today
FROM student_attendance sa
JOIN students s ON sa.student_id = s.id
JOIN classes c ON s.class_id = c.id
WHERE sa.marked_by = ? AND sa.attendance_date = ? AND c.academic_year = ?";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("iss", $user['id'], $today, $selected_academic_year);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Store classes data for JavaScript
$classes_data = [];
$classes->data_seek(0);
while ($class = $classes->fetch_assoc()) {
    $classes_data[] = $class;
}
$classes->data_seek(0);

// Get detailed attendance for AI
$detailed_stats_query = "SELECT 
    c.section,
    c.class_name,
    c.academic_year,
    COALESCE(
        (SELECT COUNT(DISTINCT s.id)
         FROM students s
         INNER JOIN classes c2 ON s.class_id = c2.id
         WHERE c2.section = c.section
         AND c2.year = c.year
         AND c2.semester = c.semester
         AND c2.academic_year = c.academic_year
         AND s.is_active = 1
        ), 0
    ) as total_students,
    COUNT(DISTINCT CASE WHEN sa.status = 'present' AND sa.attendance_date = ? THEN sa.student_id END) as present_count,
    COUNT(DISTINCT CASE WHEN sa.status = 'absent' AND sa.attendance_date = ? THEN sa.student_id END) as absent_count
FROM classes c
LEFT JOIN students s ON s.class_id = c.id AND s.is_active = 1
LEFT JOIN student_attendance sa ON sa.student_id = s.id AND sa.marked_by = ?
WHERE c.teacher_id = ? AND c.academic_year = ?
GROUP BY c.id, c.section, c.class_name, c.academic_year, c.year, c.semester
ORDER BY c.section";
$stmt = $conn->prepare($detailed_stats_query);
$stmt->bind_param("ssiis", $today, $today, $user['id'], $user['id'], $selected_academic_year);
$stmt->execute();
$detailed_stats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get total classes count for selected year
$total_classes = $classes_data ? count($classes_data) : 0;

// Calculate total students across all classes
$total_students = 0;
foreach ($classes_data as $class) {
    $total_students += $class['student_count'];
}







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
    <title>Teacher Dashboard - NIT College</title>
    <link rel="icon" href="../Nit_logo.png" type="image/svg+xml" />
    <link rel="stylesheet" href="teacher_dashboard_styles.css">

    <style>
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
    0%, 100% { 
        transform: translateY(0) rotate(0deg); 
        opacity: 0; 
    } 
    10% { 
        opacity: 1; 
    } 
    90% { 
        opacity: 1; 
    } 
    100% { 
        transform: translateY(-100vh) rotate(360deg); 
        opacity: 0; 
    } 
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
    animation: fadeInDown 0.8s ease-out; 
}

@keyframes fadeInDown { 
    from { 
        opacity: 0; 
        transform: translateY(-30px); 
    } 
    to { 
        opacity: 1; 
        transform: translateY(0); 
    } 
}

.hero-text p { 
    font-size: 18px; 
    color: #666; 
    margin-bottom: 10px; 
    animation: fadeIn 1s ease-out 0.3s both; 
}

@keyframes fadeIn { 
    from { opacity: 0; } 
    to { opacity: 1; } 
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
    animation: slideInRight 0.8s ease-out; 
}

@keyframes slideInRight { 
    from { 
        opacity: 0; 
        transform: translateX(50px); 
    } 
    to { 
        opacity: 1; 
        transform: translateX(0); 
    } 
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

/* Alert Messages */
.alert { 
    padding: 20px; 
    border-radius: 15px; 
    margin-bottom: 30px; 
    animation: slideDown 0.5s ease-out; 
    backdrop-filter: blur(10px); 
    border: 2px solid; 
}

@keyframes slideDown { 
    from { 
        opacity: 0; 
        transform: translateY(-20px); 
    } 
    to { 
        opacity: 1; 
        transform: translateY(0); 
    } 
}

.alert-success { 
    background: rgba(212, 237, 218, 0.95); 
    border-color: #28a745; 
    color: #155724; 
}

.alert-success h3 { 
    margin: 0 0 10px 0; 
    color: #155724; 
}

.alert-warning {
    background: rgba(255, 243, 205, 0.95);
    border-color: #ffc107;
    color: #856404;
}

.alert-warning h3 {
    margin: 0 0 10px 0;
    color: #856404;
}

/* Year Filter Container */
.year-filter-container {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    padding: 25px 30px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    margin-bottom: 30px;
}

.year-filter-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.year-filter-header h3 {
    font-size: 22px;
    color: #333;
    display: flex;
    align-items: center;
    gap: 10px;
}

.year-buttons {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.year-btn {
    padding: 12px 28px;
    border-radius: 12px;
    border: 2px solid #e0e0e0;
    background: white;
    color: #333;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-block;
}

.year-btn:hover {
    border-color: #667eea;
    background: rgba(102, 126, 234, 0.1);
    transform: translateY(-2px);
}

.year-btn.active {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-color: transparent;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.stats-card {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 15px 25px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    gap: 12px;
    font-weight: 600;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.stats-card span {
    font-size: 24px;
}

/* Stats Grid */
.stats-grid { 
    display: grid; 
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
    gap: 25px; 
    margin: 40px 0; 
}

.stat-card { 
    background: rgba(255, 255, 255, 0.95); 
    backdrop-filter: blur(20px); 
    padding: 35px; 
    border-radius: 20px; 
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15); 
    position: relative; 
    overflow: hidden; 
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); 
    border: 2px solid rgba(255, 255, 255, 0.5); 
}

.stat-card::before { 
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

.stat-card:hover { 
    transform: translateY(-10px) scale(1.02); 
    box-shadow: 0 20px 60px rgba(102, 126, 234, 0.4); 
}

.stat-card h3 { 
    color: #666; 
    font-size: 13px; 
    font-weight: 600; 
    text-transform: uppercase; 
    letter-spacing: 1px; 
    margin-bottom: 15px; 
}

.stat-value { 
    font-size: 48px; 
    font-weight: 800; 
    background: linear-gradient(135deg, #667eea, #764ba2); 
    -webkit-background-clip: text; 
    -webkit-text-fill-color: transparent; 
}

.stat-subtitle {
    font-size: 12px;
    color: #999;
    margin-top: 10px;
}

/* Table Container */
.table-container { 
    background: rgba(255, 255, 255, 0.95); 
    backdrop-filter: blur(20px); 
    border-radius: 25px; 
    padding: 40px; 
    box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2); 
    margin: 30px 0; 
    border: 2px solid rgba(255, 255, 255, 0.5); 
}

.table-container h3 { 
    font-size: 24px; 
    font-weight: 700; 
    color: #2c3e50; 
    margin-bottom: 30px; 
}

/* Class Selection Grid */
.class-selection-grid { 
    display: grid; 
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); 
    gap: 25px; 
}

.class-card { 
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.7)); 
    backdrop-filter: blur(10px); 
    padding: 30px; 
    border-radius: 20px; 
    border: 2px solid rgba(102, 126, 234, 0.3); 
    transition: all 0.4s ease; 
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1); 
}

.class-card:hover { 
    transform: translateY(-10px); 
    box-shadow: 0 15px 40px rgba(102, 126, 234, 0.3); 
    border-color: #667eea; 
}

.class-card h3 { 
    font-size: 28px; 
    background: linear-gradient(135deg, #667eea, #764ba2); 
    -webkit-background-clip: text; 
    -webkit-text-fill-color: transparent; 
    margin-bottom: 20px; 
    font-weight: 800; 
}

.class-info { 
    margin: 20px 0; 
}

.info-item { 
    display: flex; 
    justify-content: space-between; 
    padding: 12px 0; 
    border-bottom: 1px solid rgba(0, 0, 0, 0.1); 
    font-size: 14px; 
}

.info-item span { 
    color: #666; 
}

.info-item strong { 
    color: #2c3e50; 
    font-weight: 600; 
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
    text-align: center; 
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

.btn-info { 
    background: linear-gradient(135deg, #17a2b8, #138496); 
    color: white; 
    box-shadow: 0 4px 15px rgba(23, 162, 184, 0.4); 
}

.btn-info:hover { 
    transform: translateY(-2px); 
    box-shadow: 0 6px 20px rgba(23, 162, 184, 0.6); 
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

/* Instructions Box */
.instructions-box { 
    background: linear-gradient(135deg, rgba(227, 242, 253, 0.9), rgba(187, 222, 251, 0.9)); 
    padding: 30px; 
    border-radius: 20px; 
    border: 2px solid rgba(102, 126, 234, 0.3); 
}

.instructions-box ul { 
    list-style-position: inside; 
    line-height: 2.2; 
    color: #2c3e50; 
}

.instructions-box li { 
    padding-left: 10px; 
    font-size: 15px; 
}

/* AI Assistant Styles */
.ai-assistant-btn { 
    position: fixed; 
    bottom: 30px; 
    right: 30px; 
    width: 70px; 
    height: 70px; 
    border-radius: 50%; 
    background: linear-gradient(135deg, #4285F4, #34A853, #FBBC05, #EA4335); 
    border: none; 
    cursor: pointer; 
    box-shadow: 0 10px 40px rgba(66, 133, 244, 0.5); 
    z-index: 9998; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    transition: all 0.3s; 
    animation: pulseBtn 2s infinite; 
}

@keyframes pulseBtn { 
    0%, 100% { box-shadow: 0 10px 40px rgba(66, 133, 244, 0.5); } 
    50% { box-shadow: 0 10px 60px rgba(66, 133, 244, 0.8), 0 0 0 10px rgba(66, 133, 244, 0.2); } 
}

.ai-assistant-btn:hover { 
    transform: scale(1.1) rotate(5deg); 
}

.ai-assistant-btn span { 
    font-size: 32px; 
    animation: sparkle 2s infinite; 
}

@keyframes sparkle { 
    0%, 100% { filter: brightness(1); } 
    50% { filter: brightness(1.5); } 
}

.ai-chat-container { 
    position: fixed; 
    bottom: 120px; 
    right: 30px; 
    width: 450px; 
    max-height: 650px; 
    background: white; 
    border-radius: 25px; 
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); 
    z-index: 9999; 
    display: none; 
    flex-direction: column; 
    overflow: hidden; 
    animation: slideUp 0.3s ease; 
    border: 3px solid transparent; 
    background-image: linear-gradient(white, white), linear-gradient(135deg, #4285F4, #34A853, #FBBC05, #EA4335); 
    background-origin: border-box; 
    background-clip: padding-box, border-box; 
}

@keyframes slideUp { 
    from { 
        opacity: 0; 
        transform: translateY(20px); 
    } 
    to { 
        opacity: 1; 
        transform: translateY(0); 
    } 
}

.ai-chat-header { 
    background: linear-gradient(135deg, #4285F4, #34A853); 
    padding: 25px; 
    display: flex; 
    align-items: center; 
    justify-content: space-between; 
    position: relative; 
    overflow: hidden; 
}

.ai-chat-header::before { 
    content: ''; 
    position: absolute; 
    top: -50%; 
    left: -50%; 
    width: 200%; 
    height: 200%; 
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); 
    animation: rotate 10s linear infinite; 
}

@keyframes rotate { 
    0% { transform: rotate(0deg); } 
    100% { transform: rotate(360deg); } 
}

.ai-chat-header-info { 
    display: flex; 
    align-items: center; 
    gap: 15px; 
    position: relative; 
    z-index: 1; 
}

.ai-avatar { 
    width: 50px; 
    height: 50px; 
    border-radius: 50%; 
    background: white; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    font-size: 28px; 
    box-shadow: 0 4px 15px rgba(0,0,0,0.2); 
}

.ai-chat-header h4 { 
    color: white; 
    font-size: 20px; 
    margin: 0; 
    font-weight: 700; 
    text-shadow: 0 2px 10px rgba(0,0,0,0.2); 
}

.ai-chat-header p { 
    color: rgba(255,255,255,0.9); 
    font-size: 13px; 
    margin: 3px 0 0; 
}

.ai-header-controls { 
    display: flex; 
    gap: 10px; 
    position: relative; 
    z-index: 1; 
}

.ai-control-btn { 
    background: rgba(255,255,255,0.2); 
    border: none; 
    width: 38px; 
    height: 38px; 
    border-radius: 50%; 
    color: white; 
    cursor: pointer; 
    font-size: 18px; 
    transition: all 0.3s; 
    backdrop-filter: blur(10px); 
}

.ai-control-btn:hover { 
    background: rgba(255,255,255,0.3); 
    transform: scale(1.1); 
}

.ai-control-btn.active { 
    background: rgba(255,255,255,0.4); 
}

.ai-chat-messages { 
    flex: 1; 
    overflow-y: auto; 
    padding: 25px; 
    max-height: 420px; 
    background: linear-gradient(to bottom, #f8f9fa 0%, #ffffff 100%); 
}

.ai-message { 
    margin-bottom: 20px; 
    display: flex; 
    gap: 12px;
    animation: messageSlide 0.3s ease;
}

@keyframes messageSlide {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.ai-message.user .ai-avatar { 
    background: #667eea; 
    color: white; 
}

/* ============================================
   TEACHER DASHBOARD - EXTENDED STYLES
   ============================================ */

/* ============ FOOTER STYLES ============ */
.footer {
    background: linear-gradient(135deg, #1a1f3a 0%, #16213e 50%, #0f3460 100%);
    color: white;
    padding: 50px 40px 30px;
    margin-top: 60px;
    position: relative;
    overflow: hidden;
}

.footer-border {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background: linear-gradient(90deg, #667eea, #764ba2, #f093fb, #667eea);
    background-size: 200% 100%;
    animation: gradientShift 3s ease infinite;
}

.footer-content {
    max-width: 1200px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
}

.developer-section {
    text-align: center;
    margin-bottom: 40px;
    padding: 30px;
    background: rgba(102, 126, 234, 0.05);
    border-radius: 20px;
    border: 1px solid rgba(102, 126, 234, 0.2);
}

.developer-section p {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.8);
    margin: 10px 0;
    font-weight: 500;
}

.company-link {
    display: inline-block;
    color: #667eea;
    text-decoration: none;
    font-weight: 700;
    font-size: 18px;
    margin: 15px 0;
    transition: all 0.3s;
    padding: 10px 20px;
    border-radius: 10px;
}

.company-link:hover {
    color: #f093fb;
    background: rgba(102, 126, 234, 0.1);
    transform: translateY(-2px);
}

.divider {
    width: 80px;
    height: 2px;
    background: linear-gradient(90deg, transparent, #667eea, transparent);
    margin: 20px auto;
}

.team-label {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: #667eea;
    margin: 20px 0 15px;
}

.developer-badges {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin: 20px 0;
    flex-wrap: wrap;
}

.developer-badge {
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(102, 126, 234, 0.2);
    padding: 12px 25px;
    border-radius: 50px;
    text-decoration: none;
    color: white;
    font-size: 14px;
    transition: all 0.3s;
    border: 1px solid rgba(102, 126, 234, 0.4);
}

.developer-badge:hover {
    background: rgba(102, 126, 234, 0.4);
    border-color: #667eea;
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
}

.developer-badge span {
    font-size: 18px;
}

.role-tags {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 20px;
    flex-wrap: wrap;
}

.role-tag {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.footer-bottom {
    text-align: center;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding-top: 25px;
}

.footer-bottom p {
    font-size: 13px;
    color: rgba(255, 255, 255, 0.7);
    margin: 8px 0;
}

/* ============ AI CHAT ADDITIONAL STYLES ============ */

.ai-message-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
    box-shadow: 0 2px 10px rgba(102, 126, 234, 0.2);
}

.ai-message.user .ai-message-avatar {
    background: linear-gradient(135deg, #28a745, #20c997);
}

.ai-message-content {
    background: rgba(0, 0, 0, 0.05);
    padding: 12px 18px;
    border-radius: 15px;
    font-size: 14px;
    line-height: 1.6;
    max-width: 280px;
    word-wrap: break-word;
    color: #333;
}

.ai-message.user .ai-message-content {
    background: linear-gradient(135deg, rgba(40, 167, 69, 0.2), rgba(32, 201, 151, 0.2));
    color: #155724;
}

/* Typing indicator animation */
.typing-indicator {
    display: flex;
    gap: 4px;
    padding: 10px;
}

.typing-indicator span {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #667eea;
    animation: typing 1.4s infinite;
}

.typing-indicator span:nth-child(2) {
    animation-delay: 0.2s;
}

.typing-indicator span:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes typing {
    0%, 60%, 100% {
        opacity: 0.5;
        transform: translateY(0);
    }
    30% {
        opacity: 1;
        transform: translateY(-10px);
    }
}

.ai-quick-actions {
    padding: 15px;
    background: rgba(102, 126, 234, 0.05);
    border-top: 1px solid rgba(102, 126, 234, 0.2);
    border-bottom: 1px solid rgba(102, 126, 234, 0.2);
}

.ai-quick-actions p {
    font-size: 12px;
    color: #666;
    margin: 0 0 10px 0;
    font-weight: 600;
    text-transform: uppercase;
}

.ai-quick-btn {
    background: white;
    border: 1px solid #e0e0e0;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 12px;
    margin-right: 8px;
    margin-bottom: 8px;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-block;
}

.ai-quick-btn:hover {
    background: #667eea;
    color: white;
    border-color: #667eea;
    transform: scale(1.05);
}

.ai-chat-input {
    display: flex;
    gap: 10px;
    padding: 15px;
    background: white;
    border-top: 1px solid #e0e0e0;
}

.ai-chat-input input {
    flex: 1;
    border: 1px solid #e0e0e0;
    border-radius: 10px;
    padding: 10px 15px;
    font-size: 14px;
    outline: none;
    transition: all 0.3s;
}

.ai-chat-input input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

#sendBtn {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    border-radius: 10px;
    width: 40px;
    height: 40px;
    cursor: pointer;
    font-size: 18px;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
}

#sendBtn:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

/* ============ RESPONSIVE DESIGN ============ */

/* Tablet devices */
@media (max-width: 1024px) {
    .navbar {
        padding: 15px 25px;
    }

    .navbar h1 {
        font-size: 20px;
    }

    .main-content {
        padding: 25px;
    }

    .hero-welcome {
        padding: 35px;
    }

    .hero-content {
        grid-template-columns: 1fr;
    }

    .hero-text h2 {
        font-size: 32px;
    }

    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    }

    .class-selection-grid {
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    }

    .ai-chat-container {
        width: 400px;
    }
}

/* Mobile devices */
@media (max-width: 768px) {
    * {
        scroll-behavior: auto;
    }

    body {
        background-attachment: fixed;
    }

    .navbar {
        padding: 12px 15px;
        flex-direction: column;
        gap: 15px;
    }

    .navbar-brand h1 {
        font-size: 18px;
    }

    .user-info {
        gap: 10px;
        width: 100%;
    }

    .main-content {
        padding: 15px;
    }

    .hero-welcome {
        padding: 20px;
        margin-bottom: 25px;
    }

    .hero-text h2 {
        font-size: 24px;
    }

    .hero-text p {
        font-size: 14px;
    }

    .glass-clock {
        min-width: auto;
        padding: 20px;
    }

    .glass-clock .time {
        font-size: 32px;
    }

    .year-filter-container {
        padding: 15px;
    }

    .year-filter-header {
        flex-direction: column;
        gap: 15px;
    }

    .year-filter-header h3 {
        font-size: 18px;
    }

    .year-buttons {
        gap: 10px;
    }

    .year-btn {
        padding: 10px 18px;
        font-size: 12px;
    }

    .stats-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }

    .stat-card {
        padding: 20px;
    }

    .stat-value {
        font-size: 36px;
    }

    .table-container {
        padding: 20px;
        border-radius: 15px;
    }

    .table-container h3 {
        font-size: 18px;
        margin-bottom: 15px;
    }

    .class-selection-grid {
        grid-template-columns: 1fr;
    }

    .class-card {
        padding: 20px;
    }

    .class-card h3 {
        font-size: 22px;
    }

    .btn {
        padding: 10px 16px;
        font-size: 13px;
    }

    .ai-chat-container {
        width: calc(100vw - 30px);
        max-width: 350px;
        bottom: 110px;
        right: 15px;
        max-height: 500px;
    }

    .ai-chat-messages {
        max-height: 300px;
    }

    .ai-message-content {
        max-width: 200px;
        font-size: 13px;
    }

    .footer {
        padding: 30px 20px 20px;
    }

    .developer-section {
        padding: 20px;
    }

    .developer-badges {
        flex-direction: column;
    }

    .developer-badge {
        width: 100%;
        justify-content: center;
    }

    .instructions-box ul {
        padding-left: 20px;
    }

    .instructions-box li {
        font-size: 13px;
        line-height: 1.8;
    }
}

/* Small mobile devices */
@media (max-width: 480px) {
    .navbar {
        padding: 10px;
    }

    .navbar h1 {
        font-size: 16px;
    }

    .navbar-logo {
        width: 40px;
        height: 40px;
        font-size: 20px;
    }

    .hero-welcome {
        padding: 15px;
    }

    .hero-text h2 {
        font-size: 20px;
    }

    .hero-text p {
        font-size: 12px;
    }

    .glass-clock .time {
        font-size: 24px;
    }

    .glass-clock .date {
        font-size: 12px;
    }

    .stat-card {
        padding: 15px;
    }

    .stat-value {
        font-size: 28px;
    }

    .stat-card h3 {
        font-size: 11px;
    }

    .class-card {
        padding: 15px;
    }

    .info-item {
        font-size: 12px;
        padding: 8px 0;
    }

    .ai-chat-container {
        bottom: 100px;
        max-width: calc(100vw - 20px);
    }

    .ai-chat-header {
        padding: 15px;
    }

    .ai-chat-header h4 {
        font-size: 16px;
    }

    .ai-assistant-btn {
        width: 60px;
        height: 60px;
        bottom: 20px;
        right: 15px;
    }

    .ai-assistant-btn span {
        font-size: 24px;
    }
}

/* ============ UTILITY CLASSES ============ */

.hidden {
    display: none !important;
}

.visible {
    display: block !important;
}

.text-center {
    text-align: center;
}

.text-right {
    text-align: right;
}

.text-left {
    text-align: left;
}

.mt-10 {
    margin-top: 10px;
}

.mt-20 {
    margin-top: 20px;
}

.mb-10 {
    margin-bottom: 10px;
}

.mb-20 {
    margin-bottom: 20px;
}

.p-10 {
    padding: 10px;
}

.p-20 {
    padding: 20px;
}

/* ============ PRINT STYLES ============ */

@media print {
    .navbar,
    .ai-assistant-btn,
    .ai-chat-container,
    .footer {
        display: none;
    }

    body {
        background: white;
    }

    .main-content {
        max-width: 100%;
        padding: 0;
    }

    .hero-welcome,
    .stat-card,
    .table-container,
    .class-card {
        box-shadow: none;
        border: 1px solid #ccc;
        page-break-inside: avoid;
    }
}

/* ============ SCROLLBAR STYLES ============ */

::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}

::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.1);
    border-radius: 10px;
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 10px;
    transition: all 0.3s;
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #764ba2, #f093fb);
}

/* ============ ANIMATION KEYFRAMES ============ */

@keyframes shimmer {
    0% {
        background-position: -1000px 0;
    }
    100% {
        background-position: 1000px 0;
    }
}

@keyframes glow {
    0% {
        text-shadow: 0 0 10px rgba(102, 126, 234, 0.5);
    }
    50% {
        text-shadow: 0 0 20px rgba(102, 126, 234, 1);
    }
    100% {
        text-shadow: 0 0 10px rgba(102, 126, 234, 0.5);
    }
}

@keyframes bounce {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-10px);
    }
}

@keyframes fadeOut {
    from {
        opacity: 1;
    }
    to {
        opacity: 0;
    }
}

/* ============ ACCESSIBILITY ============ */

@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}

/* ============ DARK MODE SUPPORT ============ */

@media (prefers-color-scheme: dark) {
    .ai-message-content {
        background: rgba(255, 255, 255, 0.1);
        color: #e0e0e0;
    }

    .ai-chat-input input {
        background: #333;
        color: white;
        border-color: #444;
    }

    .ai-quick-btn {
        background: #333;
        color: white;
        border-color: #444;
    }
}




/* nit time table */

/* ============================================
   TIMETABLE SECTION - COMPLETE STYLES
   ============================================ */

/* Container Styles */
.container_timetable_timetable {
    max-width: 1400px;
    margin: 30px auto;
    padding: 25px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 25px;
    box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
    border: 2px solid rgba(255, 255, 255, 0.5);
}

/* View Toggle Buttons Container */
.flex.justify-center {
    display: flex;
    justify-content: center;
    align-items: center;
}

.mb-8 {
    margin-bottom: 2rem;
}

.space-x-4 {
    gap: 1rem;
}

/* View Toggle Buttons */
.view-toggle-btn {
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    border-radius: 9999px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: none;
    cursor: pointer;
    font-size: 15px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.view-toggle-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
}

.view-toggle-btn:active {
    transform: translateY(0);
}

/* Indigo/Active State */
.bg-indigo-600 {
    background: linear-gradient(135deg, #667eea, #764ba2);
}

.text-white {
    color: white;
}

.hover\:bg-indigo-700:hover {
    background: linear-gradient(135deg, #5568d3, #6a4193);
}

/* Gray/Inactive State */
.bg-gray-200 {
    background: #e5e7eb;
}

.text-gray-700 {
    color: #374151;
}

.hover\:bg-gray-300:hover {
    background: #d1d5db;
}

/* Content Views */
#student-view,
#teacher-view {
    background: white;
    padding: 30px;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    animation: fadeIn 0.4s ease-in-out;
}

.bg-white {
    background-color: white;
}

.p-6 {
    padding: 1.5rem;
}

.rounded-xl {
    border-radius: 20px;
}

.shadow-xl {
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
}

.hidden {
    display: none !important;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Section Headers */
.text-2xl {
    font-size: 1.5rem;
    line-height: 2rem;
}

.font-bold {
    font-weight: 700;
}

.text-gray-800 {
    color: #1f2937;
}

.mb-4 {
    margin-bottom: 1rem;
}

.mb-6 {
    margin-bottom: 1.5rem;
}

/* Department Buttons Container */
.flex.flex-wrap {
    display: flex;
    flex-wrap: wrap;
}

.gap-3 {
    gap: 0.75rem;
}

/* Department Buttons */
.dept-btn {
    padding: 0.625rem 1.25rem;
    font-weight: 500;
    border-radius: 12px;
    transition: all 0.2s ease;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    border: none;
    cursor: pointer;
    font-size: 14px;
    position: relative;
    overflow: hidden;
}

.dept-btn::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
}

.dept-btn:hover::before {
    width: 300px;
    height: 300px;
}

.dept-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
}

.dept-btn:active {
    transform: translateY(-1px);
}

/* Active State for Department Buttons */
.dept-btn.ring-4 {
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.3),
                0 8px 25px rgba(0, 0, 0, 0.2);
}

.ring-offset-2 {
    outline-offset: 2px;
}

.ring-indigo-400 {
    outline-color: #818cf8;
}

/* Color variants for dept buttons */
.bg-blue-500 {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
}

.hover\:bg-blue-600:hover {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
}

.bg-green-500 {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.hover\:bg-green-600:hover {
    background: linear-gradient(135deg, #059669, #047857);
}

.bg-yellow-500 {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
}

.hover\:bg-yellow-600:hover {
    background: linear-gradient(135deg, #d97706, #b45309);
}

.bg-red-500 {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.hover\:bg-red-600:hover {
    background: linear-gradient(135deg, #dc2626, #b91c1c);
}

.bg-purple-500 {
    background: linear-gradient(135deg, #a855f7, #9333ea);
    color: white;
}

.hover\:bg-purple-600:hover {
    background: linear-gradient(135deg, #9333ea, #7e22ce);
}

.bg-pink-500 {
    background: linear-gradient(135deg, #ec4899, #db2777);
    color: white;
}

.hover\:bg-pink-600:hover {
    background: linear-gradient(135deg, #db2777, #be185d);
}

/* Faculty Dropdown */
.block {
    display: block;
}

.text-sm {
    font-size: 0.875rem;
    line-height: 1.25rem;
}

.font-medium {
    font-weight: 500;
}

.text-gray-700 {
    color: #374151;
}

.mb-2 {
    margin-bottom: 0.5rem;
}

#faculty-select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
    font-size: 14px;
    background: white;
    cursor: pointer;
}

#faculty-select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1),
                0 2px 8px rgba(0, 0, 0, 0.1);
}

#faculty-select:hover {
    border-color: #9ca3af;
}

/* Timetable Output Containers */
#student-timetable-output,
#teacher-timetable-output {
    overflow-x: auto;
    border-radius: 15px;
    margin-top: 20px;
}

.overflow-x-auto {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.overflow-x-auto::-webkit-scrollbar {
    height: 10px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 10px;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 10px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #764ba2, #f093fb);
}

/* Placeholder Text */
.text-center {
    text-align: center;
}

.text-gray-500 {
    color: #6b7280;
}

.p-8 {
    padding: 2rem;
}

.border-2 {
    border-width: 2px;
}

.border-dashed {
    border-style: dashed;
}

.rounded-lg {
    border-radius: 12px;
}

/* Timetable Table Styles */
.timetable-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
}

.timetable-table th,
.timetable-table td {
    border: 1px solid #e5e7eb;
    padding: 12px;
    text-align: center;
    font-size: 0.9rem;
    white-space: nowrap;
    transition: all 0.2s ease;
}

.timetable-table th {
    background: linear-gradient(135deg, #0b2241, #1a3a5c);
    color: white;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 0.85rem;
    position: sticky;
    top: 0;
    z-index: 10;
}

.timetable-table tr:nth-child(even) {
    background-color: #f9fafb;
}

.timetable-table tr:hover {
    background-color: #f3f4f6;
}

/* Cell Hover Effect */
.timetable-table td:hover:not(.day-cell):not(.break-cell):not(.room-cell) {
    transform: scale(1.02);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    z-index: 5;
    background-color: #e0e7ff !important;
}

/* Sticky Columns */
.sticky-col {
    position: sticky;
    left: 0;
    z-index: 5;
    background: white;
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.05);
}

.timetable-table th.sticky-col {
    z-index: 15;
    background: linear-gradient(135deg, #0b2241, #1a3a5c);
}

/* Cell Type Styles */
.day-cell {
    font-weight: 700;
    background: linear-gradient(135deg, #e0f2f1, #b2dfdb) !important;
    color: #0d9488;
    font-size: 0.95rem;
}

.room-cell {
    background: linear-gradient(135deg, #fff7ed, #ffedd5) !important;
    color: #ea580c;
    font-weight: 600;
}

.class-cell {
    background: linear-gradient(135deg, #e3f2fd, #bbdefb);
    color: #1565c0;
    font-weight: 600;
    cursor: pointer;
}

.practical {
    background: linear-gradient(135deg, #fce4ec, #f8bbd0);
    color: #c2185b;
    font-weight: 600;
    cursor: pointer;
}

.break-cell,
.lunch-cell {
    background: linear-gradient(135deg, #ffe0b2, #ffcc80);
    color: #e65100;
    font-weight: 700;
    font-size: 0.8rem;
}

.library {
    background: linear-gradient(135deg, #e1f5fe, #b3e5fc);
    color: #0277bd;
    font-weight: 500;
    cursor: pointer;
}

.data-missing {
    background: linear-gradient(135deg, #fee2e2, #fecaca);
    color: #b91c1c;
    font-style: italic;
}

.bg-yellow-100 {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    color: #92400e;
}

.bg-red-100 {
    background: linear-gradient(135deg, #fee2e2, #fecaca);
    color: #991b1b;
}

.bg-gray-200 {
    background: #e5e7eb;
    color: #6b7280;
}

/* ============================================
   RESPONSIVE DESIGN - MEDIA QUERIES
   ============================================ */

/* Large Tablets and Small Desktops (max-width: 1024px) */
@media (max-width: 1024px) {
    .container_timetable_timetable {
        padding: 20px;
        margin: 20px auto;
    }

    #student-view,
    #teacher-view {
        padding: 20px;
    }

    .text-2xl {
        font-size: 1.25rem;
    }

    .dept-btn {
        padding: 0.5rem 1rem;
        font-size: 13px;
    }

    .view-toggle-btn {
        padding: 0.625rem 1.25rem;
        font-size: 14px;
    }

    .timetable-table th,
    .timetable-table td {
        padding: 10px;
        font-size: 0.85rem;
    }
}

/* Tablets (max-width: 768px) */
@media (max-width: 768px) {
    .container_timetable_timetable {
        padding: 15px;
        margin: 15px auto;
        border-radius: 15px;
    }

    /* View Toggle Buttons - Stack Vertically */
    .flex.justify-center {
        flex-direction: column;
        gap: 10px;
    }

    .view-toggle-btn {
        width: 100%;
        padding: 0.75rem;
        font-size: 14px;
        border-radius: 12px;
    }

    #student-view,
    #teacher-view {
        padding: 15px;
    }

    .text-2xl {
        font-size: 1.125rem;
    }

    /* Department Buttons - Full Width */
    .flex.flex-wrap {
        flex-direction: column;
    }

    .dept-btn {
        width: 100%;
        padding: 0.75rem;
        font-size: 14px;
        text-align: center;
    }

    /* Faculty Dropdown */
    #faculty-select {
        width: 100%;
        font-size: 14px;
    }

    .md\:w-1\/2 {
        width: 100% !important;
    }

    /* Timetable Table */
    .timetable-table th,
    .timetable-table td {
        padding: 8px;
        font-size: 0.75rem;
        white-space: normal;
        min-width: 80px;
    }

    .timetable-table th {
        font-size: 0.7rem;
    }

    /* Reduce padding for placeholder text */
    .p-8 {
        padding: 1.5rem;
    }

    /* Sticky columns on mobile */
    .sticky-col {
        position: sticky;
        left: 0;
        min-width: 60px;
    }
}

/* Mobile Phones (max-width: 480px) */
@media (max-width: 480px) {
    .container_timetable_timetable {
        padding: 10px;
        margin: 10px auto;
        border-radius: 12px;
    }

    .mb-8 {
        margin-bottom: 1rem;
    }

    .mb-6 {
        margin-bottom: 1rem;
    }

    .view-toggle-btn {
        padding: 0.625rem;
        font-size: 12px;
    }

    #student-view,
    #teacher-view {
        padding: 12px;
    }

    .text-2xl {
        font-size: 1rem;
    }

    .mb-4 {
        margin-bottom: 0.75rem;
    }

    .dept-btn {
        padding: 0.625rem;
        font-size: 13px;
    }

    #faculty-select {
        padding: 0.625rem;
        font-size: 13px;
    }

    .text-sm {
        font-size: 0.8rem;
    }

    /* Timetable Table - Extra Compact */
    .timetable-table th,
    .timetable-table td {
        padding: 6px;
        font-size: 0.7rem;
        min-width: 70px;
    }

    .timetable-table th {
        font-size: 0.65rem;
        padding: 8px 6px;
    }

    .day-cell,
    .room-cell {
        font-size: 0.75rem;
    }

    .break-cell,
    .lunch-cell {
        font-size: 0.65rem;
    }

    .p-8 {
        padding: 1rem;
    }

    /* Disable hover effects on mobile for performance */
    .timetable-table td:hover {
        transform: none;
        box-shadow: none;
    }

    .dept-btn:hover {
        transform: none;
    }

    .sticky-col {
        min-width: 50px;
    }

    /* Make scrollbar thinner on mobile */
    .overflow-x-auto::-webkit-scrollbar {
        height: 6px;
    }
}

/* Extra Small Devices (max-width: 360px) */
@media (max-width: 360px) {
    .container_timetable_timetable {
        padding: 8px;
        margin: 8px;
    }

    .view-toggle-btn {
        font-size: 11px;
        padding: 0.5rem;
    }

    #student-view,
    #teacher-view {
        padding: 10px;
    }

    .text-2xl {
        font-size: 0.9rem;
    }

    .dept-btn {
        font-size: 12px;
        padding: 0.5rem;
    }

    .timetable-table th,
    .timetable-table td {
        padding: 4px;
        font-size: 0.65rem;
        min-width: 60px;
    }

    .timetable-table th {
        font-size: 0.6rem;
    }

    .p-8 {
        padding: 0.75rem;
    }
}

/* Landscape Orientation Adjustments */
@media (max-width: 768px) and (orientation: landscape) {
    .container_timetable_timetable {
        max-height: 85vh;
        overflow-y: auto;
    }

    #student-view,
    #teacher-view {
        max-height: 70vh;
    }

    .overflow-x-auto {
        max-height: 50vh;
    }
}

/* Print Styles */
@media print {
    .container_timetable_timetable {
        box-shadow: none;
        border: 1px solid #ccc;
    }

    .view-toggle-btn,
    .dept-btn {
        display: none;
    }

    #student-view,
    #teacher-view {
        display: block !important;
        box-shadow: none;
    }

    .timetable-table {
        box-shadow: none;
        border: 1px solid #ccc;
    }

    .overflow-x-auto {
        overflow: visible;
    }
}

/* Accessibility - Reduced Motion */
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        transition-duration: 0.01ms !important;
    }

    .dept-btn::before,
    .dept-btn:hover,
    .view-toggle-btn:hover,
    .timetable-table td:hover {
        transform: none;
    }
}

/* Dark Mode Support (Optional) */
@media (prefers-color-scheme: dark) {
    .container_timetable_timetable {
        background: rgba(30, 30, 30, 0.95);
        border-color: rgba(255, 255, 255, 0.1);
    }

    #student-view,
    #teacher-view {
        background: #1f2937;
        color: #e5e7eb;
    }

    .text-gray-800 {
        color: #e5e7eb;
    }

    .text-gray-700 {
        color: #d1d5db;
    }

    .text-gray-500 {
        color: #9ca3af;
    }

    #faculty-select {
        background: #374151;
        color: #e5e7eb;
        border-color: #4b5563;
    }

    .timetable-table {
        background: #1f2937;
    }

    .timetable-table tr:nth-child(even) {
        background-color: #374151;
    }

    .timetable-table tr:hover {
        background-color: #4b5563;
    }
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
            <h1>NIT AMMS - Teacher Portal</h1>
        </div>
        <div class="user-info">
           <div style="position:relative; display:inline-block;">

    <!-- BUTTON -->
    <a href="profile.php"
       style="
            background: linear-gradient(135deg, #1aa3b8, #157a96);
            padding: 14px 35px;
            border-radius: 30px;
            color: #ffffff;
            text-decoration: none;
            font-size: 18px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0px 0px 20px rgba(0, 255, 255, 0.25);
            transition: 0.3s ease;
        "
       id="profileBtn">
        👤 My Profile
    </a>

    <!-- TOOLTIP (Now Below) -->
    <div id="tooltip"
         style="
            display:none;
            position:absolute;
            bottom:-55px;
            left:50%;
            transform:translateX(-50%);
            background:#1e1e1e;
            color:#fff;
            padding:10px 14px;
            border-radius:8px;
            font-size:14px;
            white-space:nowrap;
            box-shadow:0px 4px 15px rgba(0,0,0,0.5);
            opacity:0;
            transition:opacity 0.25s ease;
            z-index:9999;
         ">
        👉 Click to open your profile,
        You can change your Photos,
        You can see your details...
    </div>

</div>

<script>
const btn = document.getElementById("profileBtn");
const tip = document.getElementById("tooltip");

// Show tooltip on hover
btn.addEventListener("mouseenter", () => {
    tip.style.display = "block";
    setTimeout(() => { tip.style.opacity = "1"; }, 10);
});

// Hide tooltip
btn.addEventListener("mouseleave", () => {
    tip.style.opacity = "0";
    setTimeout(() => { tip.style.display = 'none'; }, 200);
});
</script>


            <div class="user-profile">
                <span>👨‍🏫 <?php echo htmlspecialchars($user['full_name']); ?></span>
            </div>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
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
                    <h2>📊 Today's Summary</h2>
                    <p>Welcome back, <strong><?php echo htmlspecialchars($user['full_name']); ?>!</strong> 👋</p>
                    <p>Kindly update the attendance for <strong><?php echo date('d M Y'); ?></strong></p>
                </div>
                <div class="glass-clock">
                    <div class="clock-icon">⏰</div>
                    <div class="time" id="liveClock">--:--:--</div>
                    <div class="date" id="liveDate">Loading...</div>
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



        <!-- Quick Links -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin: 30px 0;">
            <a href="./time_table.php" class="btn btn-primary" style="width: 100%; padding: 15px;">
                📅 Time-table Overview
            </a>
            <a href="./syallbuss.php" class="btn btn-info" style="width: 100%; padding: 15px;">
                📚 Syllabus
            </a>
            <a href="./news.php" class="btn btn-primary" style="width: 100%; padding: 15px;">
                📰 Latest News
            </a>
         <a href="./assignments.php" class="btn btn-primary" style="width: 100%; padding: 15px;">
    📝 Assignments
</a>

            <a href="./chat.php" class="btn btn-info" style="width: 100%; padding: 15px;">
    💬 Messages
</a>


<a href="manage_marks.php" class="btn btn-primary">📝 Manage Paper Marks</a>

<a href="view_leave_applications.php" class="btn btn-info" style="width: 100%; padding: 15px;">
                📋 Student Leave Applications
            </a>


            <a href="teacher_view.php" class="btn btn-primary">📅 Exam Schedule</a>
        </div>


<div class="container_timetable_timetable">


   
   

    <!-- View Toggles -->
    <div class="flex justify-center mb-8 space-x-4">
        <button id="student-view-btn" class="view-toggle-btn px-6 py-3 font-semibold rounded-full shadow-lg transition duration-300 bg-indigo-600 text-white hover:bg-indigo-700">
            Student Timetable (By Dept.)
        </button>
        <button id="teacher-view-btn" class="view-toggle-btn px-6 py-3 font-semibold rounded-full shadow-lg transition duration-300 bg-gray-200 text-gray-700 hover:bg-gray-300">
            Teacher Timetable (By Faculty)
        </button>
    </div>

    <!-- Main Content Area -->
    <div id="student-view" class="bg-white p-6 rounded-xl shadow-xl hidden">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Student Timetable View</h2>
        <div class="mb-6 flex flex-wrap gap-3">
            <!-- Department Buttons -->
            <button onclick="renderStudentTimetable('ACSE', this)" class="dept-btn px-4 py-2 bg-blue-500 text-white font-medium rounded-lg hover:bg-blue-600 transition duration-150 shadow-md">ACSE</button>
            <button onclick="renderStudentTimetable('BCSE', this)" class="dept-btn px-4 py-2 bg-green-500 text-white font-medium rounded-lg hover:bg-green-600 transition duration-150 shadow-md">BCSE</button>
            <button onclick="renderStudentTimetable('IT', this)" class="dept-btn px-4 py-2 bg-yellow-500 text-white font-medium rounded-lg hover:bg-yellow-600 transition duration-150 shadow-md">IT</button>
            <button onclick="renderStudentTimetable('EE', this)" class="dept-btn px-4 py-2 bg-red-500 text-white font-medium rounded-lg hover:bg-red-600 transition duration-150 shadow-md">EE</button>
            <button onclick="renderStudentTimetable('ME', this)" class="dept-btn px-4 py-2 bg-purple-500 text-white font-medium rounded-lg hover:bg-purple-600 transition duration-150 shadow-md">ME</button>
            <button onclick="renderStudentTimetable('CE', this)" class="dept-btn px-4 py-2 bg-pink-500 text-white font-medium rounded-lg hover:bg-pink-600 transition duration-150 shadow-md">CE</button>
        </div>
        <div id="student-timetable-output" class="overflow-x-auto">
            <p class="text-center text-gray-500 p-8 border-2 border-dashed rounded-lg">
                Please select a Department above to view its day-wise timetable.
            </p>
        </div>
    </div>

    <div id="teacher-view" class="bg-white p-6 rounded-xl shadow-xl hidden">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Teacher Timetable View</h2>
        <div class="mb-6">
            <label for="faculty-select" class="block text-sm font-medium text-gray-700 mb-2">Select Faculty Member:</label>
            <select id="faculty-select" onchange="renderTeacherTimetable(this.value)" class="w-full md:w-1/2 p-3 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition duration-150">
                <option value="">-- Select a Teacher --</option>
                <!-- Options populated by JS -->
            </select>
        </div>
        <div id="teacher-timetable-output" class="overflow-x-auto">
            <p class="text-center text-gray-500 p-8 border-2 border-dashed rounded-lg">
                Please select a Faculty Member from the dropdown to see their weekly schedule.
            </p>
        </div>
    </div>
</div>

<script>
    // --- TIMETABLE DATA STRUCTURE ---
    // The raw data from the provided table, structured for easy processing
    const TIMETABLE_DATA = [
        // MON
        { day: "MON", sec: "ACSE", room: "B-104", p1: "E.CHEM(B1)/ BEE(B2) PRACTICAL (Prac)", p2: "E.CHEM(B1)/ BEE(B2) PRACTICAL (Prac)", p3: "A. MATH-I (MD)", p4: "PSC(AS)", p5: "T&P SESSION (Other)", p6: "PSC(B1,B2) PRACTICAL (Prac)", faculty: { 'A. MATH-I (MD)': 'MRS. MONA DANGE', 'PSC(AS)': 'MR. AYAZ SHAIKH', 'T&P SESSION (Other)': 'N/A', 'PSC(B1,B2) PRACTICAL (Prac)': 'MR. AYAZ SHAIKH', 'E.CHEM(B1)/ BEE(B2) PRACTICAL (Prac)': 'DR. SONIKA KOCHHAR' } },
        { day: "MON", sec: "BCSE", room: "B-105", p1: "PSC(AS)", p2: "A. MATH-I (MD)", p3: "E.CHEM(SK)", p4: "BEE (RK)", p5: "CS (B1)/CC (B2) PRACTICAL (Prac)", p6: "CS (B1)/CC (B2) PRACTICAL (Prac)", faculty: { 'PSC(AS)': 'MR. AYAZ SHAIKH', 'A. MATH-I (MD)': 'MRS. MONA DANGE', 'E.CHEM(SK)': 'DR. SONIKA KOCHHAR', 'BEE (RK)': 'MR. RAHUL KADAM', 'CS (B1)/CC (B2) PRACTICAL (Prac)': 'MRS. HITASHI CHAUHAN' } },
        { day: "MON", sec: "IT", room: "B-106", p1: "E.CHEM(MJ)", p2: "A. MATH-I (VR)", p3: "PSC(AS)", p4: "CS(HC)", p5: "CS (B1)/CC (B2) PRACTICAL (Prac)", p6: "CS (B1)/CC (B2) PRACTICAL (Prac)", faculty: { 'E.CHEM(MJ)': 'DR. MEGHNA JUMBHLE', 'A. MATH-I (VR)': 'MRS.VIDYA RAUT', 'PSC(AS)': 'MR. AYAZ SHAIKH', 'CS(HC)': 'MRS. HITASHI CHAUHAN', 'CS (B1)/CC (B2) PRACTICAL (Prac)': 'MRS. HITASHI CHAUHAN' } },
        { day: "MON", sec: "EE", room: "B-107", p1: "A.MATH-I(PD)", p2: "A.PHY(JB)", p3: "CS(MS)", p4: "BEE (RD)", p5: "A.PHY(B1)/EG (B2) PRACTICAL (Prac)", p6: "A.PHY(B1)/EG (B2) PRACTICAL (Prac)", faculty: { 'A.MATH-I(PD)': 'MR. PRASHANT DANGE', 'A.PHY(JB)': 'DR. JITENDRA BHAISWAR', 'CS(MS)': 'DR. MOHAMMAD SABIR', 'BEE (RD)': 'MRS. RACHANA DAGA', 'A.PHY(B1)/EG (B2) PRACTICAL (Prac)': 'DR. JITENDRA BHAISWAR' } },
        { day: "MON", sec: "ME", room: "B-206", p1: "CP (AS)", p2: "A.PHY(DM)", p3: "A.MATH-I(PD)", p4: "EG(SK)", p5: "EG (B1,B2) PRACTICAL (Prac)", p6: "EG (B1,B2) PRACTICAL (Prac)", faculty: { 'CP (AS)': 'MISS. AYUSHI SHARMA', 'A.PHY(DM)': 'MR. DHIRAJ MEGHE', 'A.MATH-I(PD)': 'MR. PRASHANT DANGE', 'EG(SK)': 'MR. SAMRAT KAVISHWAR', 'EG (B1,B2) PRACTICAL (Prac)': 'MR. SAMRAT KAVISHWAR' } },
        { day: "MON", sec: "CE", room: "B-207", p1: "CS (B1)/CC (B2) PRACTICAL (Prac)", p2: "CS (B1)/CC (B2) PRACTICAL (Prac)", p3: "A. MATH-I (VR)", p4: "EG(GK)", p5: "FOV(AK)", p6: "TGM.LIBRARY (Library)", faculty: { 'CS (B1)/CC (B2) PRACTICAL (Prac)': 'DR. MOHAMMAD SABIR', 'A. MATH-I (VR)': 'MRS.VIDYA RAUT', 'EG(GK)': 'MR. GIRISAN KHAN', 'FOV(AK)': 'MR. AMIT KHARWADE', 'TGM.LIBRARY (Library)': 'N/A' } },

        // TUE
        { day: "TUE", sec: "ACSE", room: "B-104", p1: "E.CHEM(B1)/ BEE(B1) PRACTICAL (Prac)", p2: "E.CHEM(B1)/ BEE(B1) PRACTICAL (Prac)", p3: "PSC(AS)", p4: "CS(HC)", p5: "A. MATH-I (MD)", p6: "TGM.LIBRARY (Library)", faculty: { 'E.CHEM(B1)/ BEE(B1) PRACTICAL (Prac)': 'DR. SONIKA KOCHHAR', 'PSC(AS)': 'MR. AYAZ SHAIKH', 'CS(HC)': 'MRS. HITASHI CHAUHAN', 'A. MATH-I (MD)': 'MRS. MONA DANGE', 'TGM.LIBRARY (Library)': 'N/A' } },
        { day: "TUE", sec: "BCSE", room: "B-105", p1: "PSC(AS)", p2: "A. MATH-I (MD)", p3: "BEE (RK)", p4: "E.CHEM(SK)", p5: "CS (B1)/CC (B2) PRACTICAL (Prac)", p6: "CS (B1)/CC (B2) PRACTICAL (Prac)", faculty: { 'PSC(AS)': 'MR. AYAZ SHAIKH', 'A. MATH-I (MD)': 'MRS. MONA DANGE', 'BEE (RK)': 'MR. RAHUL KADAM', 'E.CHEM(SK)': 'DR. SONIKA KOCHHAR', 'CS (B1)/CC (B2) PRACTICAL (Prac)': 'MR. RAHUL KADAM' } },
        { day: "TUE", sec: "IT", room: "B-106", p1: "E.CHEM(MJ)", p2: "A. MATH-I (VR)", p3: "BEE (TS)", p4: "PSC(AS)", p5: "PSC(B1,B2) PRACTICAL (Prac)", p6: "PSC(B1,B2) PRACTICAL (Prac)", faculty: { 'E.CHEM(MJ)': 'DR. MEGHNA JUMBHLE', 'A. MATH-I (VR)': 'MRS.VIDYA RAUT', 'BEE (TS)': 'MR. TUSHAR SHELKE', 'PSC(AS)': 'MR. AYAZ SHAIKH', 'PSC(B1,B2) PRACTICAL (Prac)': 'MISS. AYUSHI SHARMA' } },
        { day: "TUE", sec: "EE", room: "B-107", p1: "A.MATH-I(PD)", p2: "A.PHY(JB)", p3: "BEE (RD)", p4: "EG(RD)", p5: "A.PHY(B2)/EG (B1) PRACTICAL (Prac)", p6: "A.PHY(B2)/EG (B1) PRACTICAL (Prac)", faculty: { 'A.MATH-I(PD)': 'MR. PRASHANT DANGE', 'A.PHY(JB)': 'DR. JITENDRA BHAISWAR', 'BEE (RD)': 'MRS. RACHANA DAGA', 'EG(RD)': 'MR. ROHAN DESHMUKH', 'A.PHY(B2)/EG (B1) PRACTICAL (Prac)': 'DR. JITENDRA BHAISWAR' } },
        { day: "TUE", sec: "ME", room: "B-206", p1: "CP (AS)", p2: "A.PHY(DM)", p3: "A.MATH-I(PD)", p4: "EG(SK)", p5: "WS(B1,B2) PRACTICAL (Prac)", p6: "WS(B1,B2) PRACTICAL (Prac)", faculty: { 'CP (AS)': 'MISS. AYUSHI SHARMA', 'A.PHY(DM)': 'MR. DHIRAJ MEGHE', 'A.MATH-I(PD)': 'MR. PRASHANT DANGE', 'EG(SK)': 'MR. SAMRAT KAVISHWAR', 'WS(B1,B2) PRACTICAL (Prac)': 'MR. SAMRAT KAVISHWAR' } },
        { day: "TUE", sec: "CE", room: "B-207", p1: "CS (B1)/CC (B2) PRACTICAL (Prac)", p2: "CS (B1)/CC (B2) PRACTICAL (Prac)", p3: "A.PHY(DM)", p4: "CS(MS)", p5: "T&P SESSION (Other)", p6: "T&P SESSION (Other)", faculty: { 'CS (B1)/CC (B2) PRACTICAL (Prac)': 'DR. MOHAMMAD SABIR', 'A.PHY(DM)': 'MR. DHIRAJ MEGHE', 'CS(MS)': 'DR. MOHAMMAD SABIR', 'T&P SESSION (Other)': 'N/A' } },

        // WED
        { day: "WED", sec: "ACSE", room: "B-104", p1: "PSC(AS)", p2: "A. MATH-I (MD)", p3: "E.CHEM(SK)", p4: "BEE (RD)", p5: "PSC(B1,B2) PRACTICAL (Prac)", p6: "PSC(B1,B2) PRACTICAL (Prac)", faculty: { 'PSC(AS)': 'MR. AYAZ SHAIKH', 'A. MATH-I (MD)': 'MRS. MONA DANGE', 'E.CHEM(SK)': 'DR. SONIKA KOCHHAR', 'BEE (RD)': 'MRS. RACHANA DAGA', 'PSC(B1,B2) PRACTICAL (Prac)': 'MR. AYAZ SHAIKH' } },
        { day: "WED", sec: "BCSE", room: "B-105", p1: "CS(HC)", p2: "E.CHEM(SK)", p3: "A. MATH-I (MD)", p4: "PSC(AS)", p5: "CS (B2)/CC (B1) PRACTICAL (Prac)", p6: "CS (B2)/CC (B1) PRACTICAL (Prac)", faculty: { 'CS(HC)': 'MRS. HITASHI CHAUHAN', 'E.CHEM(SK)': 'DR. SONIKA KOCHHAR', 'A. MATH-I (MD)': 'MRS. MONA DANGE', 'PSC(AS)': 'MR. AYAZ SHAIKH', 'CS (B2)/CC (B1) PRACTICAL (Prac)': 'MR. RAHUL KADAM' } },
        { day: "WED", sec: "IT", room: "B-106", p1: "E.CHEM(B1)/ BEE(B2) PRACTICAL (Prac)", p2: "E.CHEM(B1)/ BEE(B2) PRACTICAL (Prac)", p3: "A. MATH-I (VR)", p4: "BEE(TS)", p5: "T&P SESSION (Other)", p6: "T&P SESSION (Other)", faculty: { 'E.CHEM(B1)/ BEE(B2) PRACTICAL (Prac)': 'DR. MEGHNA JUMBHLE', 'A. MATH-I (VR)': 'MRS.VIDYA RAUT', 'BEE(TS)': 'MR. TUSHAR SHELKE', 'T&P SESSION (Other)': 'N/A' } },
        { day: "WED", sec: "EE", room: "B-107", p1: "CS (B1)/CC (B2) PRACTICAL (Prac)", p2: "CS (B1)/CC (B2) PRACTICAL (Prac)", p3: "A.PHY(JB)", p4: "EG(RD)", p5: "A. MATH-I (PD)", p6: "TGM.LIBRARY (Library)", faculty: { 'CS (B1)/CC (B2) PRACTICAL (Prac)': 'DR. MOHAMMAD SABIR', 'A.PHY(JB)': 'DR. JITENDRA BHAISWAR', 'EG(RD)': 'MR. ROHAN DESHMUKH', 'A. MATH-I (PD)': 'MR. PRASHANT DANGE', 'TGM.LIBRARY (Library)': 'N/A' } },
        { day: "WED", sec: "ME", room: "B-206", p1: "A.MATH-I(PD)", p2: "A.PHY(DM)", p3: "CP(AS)", p4: "CS(MS)", p5: "A.PHY(B1)/CC (B2) PRACTICAL (Prac)", p6: "A.PHY(B1)/CC (B2) PRACTICAL (Prac)", faculty: { 'A.MATH-I(PD)': 'MR. PRASHANT DANGE', 'A.PHY(DM)': 'MR. DHIRAJ MEGHE', 'CP(AS)': 'MISS. AYUSHI SHARMA', 'CS(MS)': 'DR. MOHAMMAD SABIR', 'A.PHY(B1)/CC (B2) PRACTICAL (Prac)': 'MR. DHIRAJ MEGHE' } },
        { day: "WED", sec: "CE", room: "B-207", p1: "EG(GK)", p2: "A. MATH-I (VR)", p3: "A.PHY(DM)", p4: "FOV(AK)", p5: "WS(B1)/ FOV(B2) PRACTICAL (Prac)", p6: "WS(B1)/ FOV(B2) PRACTICAL (Prac)", faculty: { 'EG(GK)': 'MR. GIRISAN KHAN', 'A. MATH-I (VR)': 'MRS.VIDYA RAUT', 'A.PHY(DM)': 'MR. DHIRAJ MEGHE', 'FOV(AK)': 'MR. AMIT KHARWADE', 'WS(B1)/ FOV(B2) PRACTICAL (Prac)': 'MR. AMIT KHARWADE' } },

        // THU
        { day: "THU", sec: "ACSE", room: "B-104", p1: "A. MATH-I (MD)", p2: "PSC(AS)", p3: "BEE (RD)", p4: "E.CHEM(SK)", p5: "WT(B1,B2) PRACTICAL (Prac)", p6: "WT(B1,B2) PRACTICAL (Prac)", faculty: { 'A. MATH-I (MD)': 'MRS. MONA DANGE', 'PSC(AS)': 'MR. AYAZ SHAIKH', 'BEE (RD)': 'MRS. RACHANA DAGA', 'E.CHEM(SK)': 'DR. SONIKA KOCHHAR', 'WT(B1,B2) PRACTICAL (Prac)': 'PURNIMA BHUYAR' } },
        { day: "THU", sec: "BCSE", room: "B-105", p1: "E.CHEM(B1)/ BEE(B2) PRACTICAL (Prac)", p2: "E.CHEM(B1)/ BEE(B2) PRACTICAL (Prac)", p3: "PSC(AS)", p4: "BEE (RK)", p5: "A. MATH-I (MD)", p6: "TGM.LIBRARY (Library)", faculty: { 'E.CHEM(B1)/ BEE(B2) PRACTICAL (Prac)': 'DR. SONIKA KOCHHAR', 'PSC(AS)': 'MR. AYAZ SHAIKH', 'BEE (RK)': 'MR. RAHUL KADAM', 'A. MATH-I (MD)': 'MRS. MONA DANGE', 'TGM.LIBRARY (Library)': 'N/A' } },
        { day: "THU", sec: "IT", room: "B-106", p1: "E.CHEM(MJ)", p2: "PSC(AS)", p3: "A. MATH-I (VR)", p4: "BEE(TS)", p5: "CS (B2)/CC (B1) PRACTICAL (Prac)", p6: "CS (B2)/CC (B1) PRACTICAL (Prac)", faculty: { 'E.CHEM(MJ)': 'DR. MEGHNA JUMBHLE', 'PSC(AS)': 'MR. AYAZ SHAIKH', 'A. MATH-I (VR)': 'MRS.VIDYA RAUT', 'BEE(TS)': 'MR. TUSHAR SHELKE', 'CS (B2)/CC (B1) PRACTICAL (Prac)': 'MRS. HITASHI CHAUHAN' } },
        { day: "THU", sec: "EE", room: "B-107", p1: "CS (B2)/CC (B1) PRACTICAL (Prac)", p2: "CS (B2)/CC (B1) PRACTICAL (Prac)", p3: "A.MATH-I(PD)", p4: "CS(MS)", p5: "T&P SESSION (Other)", p6: "T&P SESSION (Other)", faculty: { 'CS (B2)/CC (B1) PRACTICAL (Prac)': 'DR. MOHAMMAD SABIR', 'A.MATH-I(PD)': 'MR. PRASHANT DANGE', 'CS(MS)': 'DR. MOHAMMAD SABIR', 'T&P SESSION (Other)': 'N/A' } },
        { day: "THU", sec: "ME", room: "B-206", p1: "A.MATH-I(PD)", p2: "A.PHY(DM)", p3: "EG(SK)", p4: "CP (AS)", p5: "A.PHY(B2)/CC (B1) PRACTICAL (Prac)", p6: "A.PHY(B2)/CC (B1) PRACTICAL (Prac)", faculty: { 'A.MATH-I(PD)': 'MR. PRASHANT DANGE', 'A.PHY(DM)': 'MR. DHIRAJ MEGHE', 'EG(SK)': 'MR. SAMRAT KAVISHWAR', 'CP (AS)': 'MISS. AYUSHI SHARMA', 'A.PHY(B2)/CC (B1) PRACTICAL (Prac)': 'MR. DHIRAJ MEGHE' } },
        { day: "THU", sec: "CE", room: "B-207", p1: "FOV(AK)", p2: "A. MATH-I (VR)", p3: "A.PHY(DM)", p4: "EG(GK)", p5: "WS(B2)/ FOV(B1) PRACTICAL (Prac)", p6: "WS(B2)/ FOV(B1) PRACTICAL (Prac)", faculty: { 'FOV(AK)': 'MR. AMIT KHARWADE', 'A. MATH-I (VR)': 'MRS.VIDYA RAUT', 'A.PHY(DM)': 'MR. DHIRAJ MEGHE', 'EG(GK)': 'MR. GIRISAN KHAN', 'WS(B2)/ FOV(B1) PRACTICAL (Prac)': 'MR. AMIT KHARWADE' } },

        // FRI
        { day: "FRI", sec: "ACSE", room: "B-104", p1: "PSC(AS)", p2: "E.CHEM(SK)", p3: "A. MATH-I (MD)", p4: "BEE (RD)", p5: "CS (B1)/CC (B2) PRACTICAL (Prac)", p6: "CS (B1)/CC (B2) PRACTICAL (Prac)", faculty: { 'PSC(AS)': 'MR. AYAZ SHAIKH', 'E.CHEM(SK)': 'DR. SONIKA KOCHHAR', 'A. MATH-I (MD)': 'MRS. MONA DANGE', 'BEE (RD)': 'MRS. RACHANA DAGA', 'CS (B1)/CC (B2) PRACTICAL (Prac)': 'MRS. HITASHI CHAUHAN' } },
        { day: "FRI", sec: "BCSE", room: "B-105", p1: "A. MATH-I (MD)", p2: "CS(HC)", p3: "BEE (RK)", p4: "E.CHEM(SK)", p5: "WT(B1,B2) PRACTICAL (Prac)", p6: "WT(B1,B2) PRACTICAL (Prac)", faculty: { 'A. MATH-I (MD)': 'MRS. MONA DANGE', 'CS(HC)': 'MRS. HITASHI CHAUHAN', 'BEE (RK)': 'MR. RAHUL KADAM', 'E.CHEM(SK)': 'DR. SONIKA KOCHHAR', 'WT(B1,B2) PRACTICAL (Prac)': 'PURNIMA BHUYAR' } },
        { day: "FRI", sec: "IT", room: "B-106", p1: "E.CHEM(B1)/ BEE(B1) PRACTICAL (Prac)", p2: "E.CHEM(B1)/ BEE(B1) PRACTICAL (Prac)", p3: "E.CHEM(MJ)", p4: "BEE (TS)", p5: "A. MATH-I (VR)", p6: "TGM.LIBRARY (Library)", faculty: { 'E.CHEM(B1)/ BEE(B1) PRACTICAL (Prac)': 'DR. MEGHNA JUMBHLE', 'E.CHEM(MJ)': 'DR. MEGHNA JUMBHLE', 'BEE (TS)': 'MR. TUSHAR SHELKE', 'A. MATH-I (VR)': 'MRS.VIDYA RAUT', 'TGM.LIBRARY (Library)': 'N/A' } },
        { day: "FRI", sec: "EE", room: "B-107", p1: "A.MATH-I(PD)", p2: "BEE (RD)", p3: "A.PHY(JB)", p4: "EG(RD)", p5: "BEE(B1)/ SPI(B2) PRACTICAL (Prac)", p6: "BEE(B1)/ SPI(B2) PRACTICAL (Prac)", faculty: { 'A.MATH-I(PD)': 'MR. PRASHANT DANGE', 'BEE (RD)': 'MRS. RACHANA DAGA', 'A.PHY(JB)': 'DR. JITENDRA BHAISWAR', 'EG(RD)': 'MR. ROHAN DESHMUKH', 'BEE(B1)/ SPI(B2) PRACTICAL (Prac)': 'MRS. RACHANA DAGA' } },
        { day: "FRI", sec: "ME", room: "B-206", p1: "CP (B1,B2) PRACTICAL (Prac)", p2: "CP (B1,B2) PRACTICAL (Prac)", p3: "A.PHY(DM)", p4: "EG(SK)", p5: "T&P SESSION (Other)", p6: "T&P SESSION (Other)", faculty: { 'CP (B1,B2) PRACTICAL (Prac)': 'MISS. AYUSHI SHARMA', 'A.PHY(DM)': 'MR. DHIRAJ MEGHE', 'EG(SK)': 'MR. SAMRAT KAVISHWAR', 'T&P SESSION (Other)': 'N/A' } },
        { day: "FRI", sec: "CE", room: "B-207", p1: "EG(GK)", p2: "A.PHY(DM)", p3: "A. MATH-I (VR)", p4: "FOV(AK)", p5: "A.PHY(B1)/EG (B2) PRACTICAL (Prac)", p6: "A.PHY(B1)/EG (B2) PRACTICAL (Prac)", faculty: { 'EG(GK)': 'MR. GIRISAN KHAN', 'A.PHY(DM)': 'MR. DHIRAJ MEGHE', 'A. MATH-I (VR)': 'MRS.VIDYA RAUT', 'FOV(AK)': 'MR. AMIT KHARWADE', 'A.PHY(B1)/EG (B2) PRACTICAL (Prac)': 'MR. GIRISAN KHAN' } },

        // SAT
        { day: "SAT", sec: "ACSE", room: "B-104", p1: "E.CHEM(SK)", p2: "A. MATH-I (MD)", p3: "BEE (RD)", p4: "CS(HC)", p5: "CS (B2)/CC (B1) PRACTICAL (Prac)", p6: "CS (B2)/CC (B1) PRACTICAL (Prac)", faculty: { 'E.CHEM(SK)': 'DR. SONIKA KOCHHAR', 'A. MATH-I (MD)': 'MRS. MONA DANGE', 'BEE (RD)': 'MRS. RACHANA DAGA', 'CS(HC)': 'MRS. HITASHI CHAUHAN', 'CS (B2)/CC (B1) PRACTICAL (Prac)': 'MRS. HITASHI CHAUHAN' } },
        { day: "SAT", sec: "BCSE", room: "B-105", p1: "E.CHEM(B2)/ BEE(B1) PRACTICAL (Prac)", p2: "E.CHEM(B2)/ BEE(B1) PRACTICAL (Prac)", p3: "A. MATH-I (MD)", p4: "E.CHEM(SK)", p5: "T&P SESSION (Other)", p6: "T&P SESSION (Other)", faculty: { 'E.CHEM(B2)/ BEE(B1) PRACTICAL (Prac)': 'DR. SONIKA KOCHHAR', 'A. MATH-I (MD)': 'MRS. MONA DANGE', 'E.CHEM(SK)': 'DR. SONIKA KOCHHAR', 'T&P SESSION (Other)': 'N/A' } },
        { day: "SAT", sec: "IT", room: "B-106", p1: "CS(HC)", p2: "A. MATH-I (VR)", p3: "E.CHEM(MJ)", p4: "PSC(AS)", p5: "WT(B1,B2) PRACTICAL (Prac)", p6: "WT(B1,B2) PRACTICAL (Prac)", faculty: { 'CS(HC)': 'MRS. HITASHI CHAUHAN', 'A. MATH-I (VR)': 'MRS.VIDYA RAUT', 'E.CHEM(MJ)': 'DR. MEGHNA JUMBHLE', 'PSC(AS)': 'MR. AYAZ SHAIKH', 'WT(B1,B2) PRACTICAL (Prac)': 'PURNIMA BHUYAR' } },
        { day: "SAT", sec: "EE", room: "B-107", p1: "BEE (RD)", p2: "A.MATH-I(PD)", p3: "A.PHY(JB)", p4: "EG(RD)", p5: "BEE(B2)/ SPI(B1) PRACTICAL (Prac)", p6: "BEE(B2)/ SPI(B1) PRACTICAL (Prac)", faculty: { 'BEE (RD)': 'MRS. RACHANA DAGA', 'A.MATH-I(PD)': 'MR. PRASHANT DANGE', 'A.PHY(JB)': 'DR. JITENDRA BHAISWAR', 'EG(RD)': 'MR. ROHAN DESHMUKH', 'BEE(B2)/ SPI(B1) PRACTICAL (Prac)': 'MRS. RACHANA DAGA' } },
        { day: "SAT", sec: "ME", room: "B-206", p1: "CS (B1,B2) PRACTICAL (Prac)", p2: "CS (B1,B2) PRACTICAL (Prac)", p3: "A.MATH-I(PD)", p4: "EG(SK)", p5: "CS(MS)", p6: "TGM.LIBRARY (Library)", faculty: { 'CS (B1,B2) PRACTICAL (Prac)': 'DR. MOHAMMAD SABIR', 'A.MATH-I(PD)': 'MR. PRASHANT DANGE', 'EG(SK)': 'MR. SAMRAT KAVISHWAR', 'CS(MS)': 'DR. MOHAMMAD SABIR', 'TGM.LIBRARY (Library)': 'N/A' } },
        { day: "SAT", sec: "CE", room: "B-207", p1: "A. MATH-I (VR)", p2: "A.PHY(DM)", p3: "CS(MS)", p4: "EG(GK)", p5: "A.PHY(B2)/EG (B1) PRACTICAL (Prac)", p6: "A.PHY(B2)/EG (B1) PRACTICAL (Prac)", faculty: { 'A. MATH-I (VR)': 'MRS.VIDYA RAUT', 'A.PHY(DM)': 'MR. DHIRAJ MEGHE', 'CS(MS)': 'DR. MOHAMMAD SABIR', 'EG(GK)': 'MR. GIRISAN KHAN', 'A.PHY(B2)/EG (B1) PRACTICAL (Prac)': 'MR. GIRISAN KHAN' } },
    ];

    // Helper function to determine the CSS class based on content type
    function getCellClass(content) {
        if (!content || content.includes('LUNCH') || content.includes('BREAK') || content.includes('RECESS')) {
            return 'break-cell';
        }
        if (content.includes('PRACTICAL') || content.includes('Prac')) {
            return 'practical';
        }
        if (content.includes('LIBRARY')) {
            return 'library';
        }
        if (content.includes('T&P SESSION')) {
             return 'bg-yellow-100 text-yellow-800 font-medium';
        }
        // Special case for when content is missing/undefined in student view
        if (content === 'N/A' || content === 'DATA MISSING') {
            return 'data-missing';
        }
        // Default for theory classes
        return 'class-cell';
    }

    // Time slots for the headers
    const TIME_SLOTS = [
        "9:30-10:30", "10:30-11:30", "11:30-12:00 (Break)", "12:00-1:00", "1:00-2:00", "2:00-2:30 (Lunch)", "2:30-3:30", "3:30-4:15"
    ];

    // --- STUDENT TIMETABLE LOGIC ---

    function renderStudentTimetable(department, button) {
        const outputDiv = document.getElementById('student-timetable-output');

        // Clear previous active state and set new active button
        document.querySelectorAll('.dept-btn').forEach(btn => {
            btn.classList.remove('ring-4', 'ring-offset-2', 'ring-indigo-400');
        });
        if (button) {
            button.classList.add('ring-4', 'ring-offset-2', 'ring-indigo-400');
        }

        const filteredData = TIMETABLE_DATA.filter(item => item.sec === department);

        if (filteredData.length === 0) {
            outputDiv.innerHTML = `<p class="text-center text-red-500 p-8">No timetable data found for ${department}.</p>`;
            return;
        }

        let tableHTML = `
            <h3 class="text-xl font-semibold text-center py-4 bg-gray-50 rounded-t-lg">Timetable for Department: ${department}</h3>
            <table class="timetable-table">
                <thead>
                    <tr>
                        <th class="sticky-col">DAY</th>
                        <th class="sticky-col">ROOM NO.</th>
                        ${TIME_SLOTS.map(slot => `<th>${slot}</th>`).join('')}
                    </tr>
                </thead>
                <tbody>
        `;

        const days = ["MON", "TUE", "WED", "THU", "FRI", "SAT"];

        days.forEach(day => {
            const dayData = filteredData.find(item => item.day === day);
            
            // Safety check: if no data for the day, show error placeholder
            if (!dayData) {
                tableHTML += `<tr><td class="day-cell sticky-col">${day}</td><td class="data-missing sticky-col" colspan="9">No schedule data available.</td></tr>`;
                return;
            }

            // Function to safely get content and strip indicators
            const getContent = (periodKey) => {
                const content = dayData[periodKey];
                // CRITICAL FIX: Ensure content exists before calling string methods
                if (content && typeof content === 'string') {
                    // Remove the type indicators for a cleaner student view
                    return content.replace('(Prac)', '').replace('(Library)', '').replace('(Other)', '').trim();
                }
                return 'N/A'; // Fallback content
            };

            tableHTML += `
                <tr>
                    <td class="day-cell sticky-col">${day}</td>
                    <td class="room-cell sticky-col">${dayData.room}</td>
                    
                    <td class="${getCellClass(dayData.p1)}">${getContent('p1')}</td>
                    <td class="${getCellClass(dayData.p2)}">${getContent('p2')}</td>
                    <td class="break-cell">BREAK</td>
                    <td class="${getCellClass(dayData.p3)}">${getContent('p3')}</td>
                    <td class="${getCellClass(dayData.p4)}">${getContent('p4')}</td>
                    <td class="break-cell">LUNCH</td>
                    <td class="${getCellClass(dayData.p5)}">${getContent('p5')}</td>
                    <td class="${getCellClass(dayData.p6)}">${getContent('p6')}</td>
                </tr>
            `;
        });

        tableHTML += `</tbody></table>`;
        outputDiv.innerHTML = tableHTML;
    }


    // --- TEACHER TIMETABLE LOGIC (FROM PREVIOUS FIX) ---

    // Extract unique faculty names for the dropdown
    function getUniqueFaculty() {
        const facultySet = new Set();
        TIMETABLE_DATA.forEach(dayData => {
            Object.values(dayData.faculty).forEach(name => {
                if (name !== 'N/A') {
                    facultySet.add(name);
                }
            });
        });
        return Array.from(facultySet).sort();
    }

    function populateFacultyDropdown() {
        const select = document.getElementById('faculty-select');
        const uniqueFaculty = getUniqueFaculty();

        uniqueFaculty.forEach(name => {
            const option = document.createElement('option');
            option.value = name;
            option.textContent = name;
            select.appendChild(option);
        });
    }

    function renderTeacherTimetable(facultyName) {
        const outputDiv = document.getElementById('teacher-timetable-output');
        if (!facultyName) {
            outputDiv.innerHTML = `<p class="text-center text-gray-500 p-8 border-2 border-dashed rounded-lg">Please select a Faculty Member from the dropdown to see their weekly schedule.</p>`;
            return;
        }

        let tableHTML = `
            <h3 class="text-xl font-semibold text-center py-4 bg-gray-50 rounded-t-lg">Schedule for ${facultyName}</h3>
            <table class="timetable-table">
                <thead>
                    <tr>
                        <th class="sticky-col">DAY</th>
                        ${TIME_SLOTS.map(slot => `<th>${slot}</th>`).join('')}
                    </tr>
                </thead>
                <tbody>
        `;

        const days = ["MON", "TUE", "WED", "THU", "FRI", "SAT"];

        days.forEach(day => {
            let rowContent = `<td class="day-cell sticky-col">${day}</td>`;
            const dayEntries = TIMETABLE_DATA.filter(item => item.day === day);

            // Mapping of period keys to their index in TIME_SLOTS (excluding breaks/lunch)
            const periodKeys = ["p1", "p2", "p3", "p4", "p5", "p6"];
            let periodIndex = 0;

            for (let i = 0; i < TIME_SLOTS.length; i++) {
                const slot = TIME_SLOTS[i];

                if (slot.includes('Break') || slot.includes('Lunch')) {
                    rowContent += `<td class="break-cell">${slot.replace(/\s*\(.*\)/, '')}</td>`;
                    continue;
                }

                const periodKey = periodKeys[periodIndex];
                let content = 'FREE';
                let cellClass = 'bg-gray-200 text-gray-500';

                // Check all entries for this day
                for (const entry of dayEntries) {
                    const classContent = entry[periodKey];
                    
                    // The core fix: Check if the faculty name is associated with the current class content
                    if (entry.faculty && Object.entries(entry.faculty).some(([clsKey, facName]) => 
                        facName === facultyName && clsKey === classContent
                    )) {
                        content = `${classContent.replace('(Prac)', '').replace('(Library)', '').replace('(Other)', '').trim()} (${entry.sec}, ${entry.room})`;
                        cellClass = getCellClass(classContent);
                        break;
                    }
                }

                rowContent += `<td class="${cellClass}">${content}</td>`;
                periodIndex++;
            }

            tableHTML += `<tr>${rowContent}</tr>`;
        });

        tableHTML += `</tbody></table>`;
        outputDiv.innerHTML = tableHTML;
    }


    // --- VIEW TOGGLE & INITIALIZATION ---

    const studentView = document.getElementById('student-view');
    const teacherView = document.getElementById('teacher-view');
    const studentBtn = document.getElementById('student-view-btn');
    const teacherBtn = document.getElementById('teacher-view-btn');

    function toggleView(view) {
        if (view === 'student') {
            studentView.classList.remove('hidden');
            teacherView.classList.add('hidden');
            studentBtn.classList.add('bg-indigo-600', 'text-white', 'hover:bg-indigo-700');
            studentBtn.classList.remove('bg-gray-200', 'text-gray-700', 'hover:bg-gray-300');
            teacherBtn.classList.remove('bg-indigo-600', 'text-white', 'hover:bg-indigo-700');
            teacherBtn.classList.add('bg-gray-200', 'text-gray-700', 'hover:bg-gray-300');
        } else {
            studentView.classList.add('hidden');
            teacherView.classList.remove('hidden');
            teacherBtn.classList.add('bg-indigo-600', 'text-white', 'hover:bg-indigo-700');
            teacherBtn.classList.remove('bg-gray-200', 'text-gray-700', 'hover:bg-gray-300');
            studentBtn.classList.remove('bg-indigo-600', 'text-white', 'hover:bg-indigo-700');
            studentBtn.classList.add('bg-gray-200', 'text-gray-700', 'hover:bg-gray-300');
        }
    }

    // Set up listeners and initial view
    document.addEventListener('DOMContentLoaded', () => {
        studentBtn.addEventListener('click', () => toggleView('student'));
        teacherBtn.addEventListener('click', () => toggleView('teacher'));

        populateFacultyDropdown();
        
        // Default to showing the Student View
        toggleView('student');
    });

</script>

         <?php 
        if (function_exists('displayNotices')) {
            displayNotices('teacher'); 
        }
        ?>

        <!-- Success Alert -->
        <?php if (isset($_GET['success']) && $_GET['success'] === 'attendance_saved'): ?>
            <div class="alert alert-success">
                <h3>✅ Attendance Saved Successfully!</h3>
                <p>
                    <strong><?php echo isset($_GET['count']) ? intval($_GET['count']) : 0; ?></strong> students marked for 
                    <strong><?php echo isset($_GET['date']) ? date('d M Y', strtotime($_GET['date'])) : 'today'; ?></strong>
                </p>
            </div>
        <?php endif; ?>






        <!-- Academic Year Filter -->
        <div class="year-filter-container">
            <div class="year-filter-header">
                <h3>
                    <span>📅</span>
                    <span>Academic Year: <?php echo htmlspecialchars($selected_academic_year); ?></span>
                </h3>
                <div style="display: flex; gap: 15px;">
                    <div class="stats-card">
                        <span>📚</span>
                        <div>
                          <a href="#select_mark" style="text-decoration: none;">
    <div style="font-size: 12px; opacity: 0.9; color: inherit;">
        Total Classes
    </div>
</a>

                            <div style="font-size: 20px;"><?php echo $total_classes; ?></div> 

                        </div>
                    </div>
                    <div class="stats-card" style="background: linear-gradient(135deg, #28a745, #20c997);">
                        <span>👥</span>
                        <div>
                            <div style="font-size: 12px; opacity: 0.9;">Total Students</div>
                            <div style="font-size: 20px;"><?php echo $total_students; ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="year-buttons">
                <?php foreach ($available_years as $year): ?>
                    <a href="?academic_year=<?php echo urlencode($year); ?>" 
                       class="year-btn <?php echo ($year === $selected_academic_year) ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($year); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        

      

           <!-- Class Selection -->
        <div id = "select_mark"    class="table-container"  >
            <h3>📚 Select Class to Mark Attendance - <?php echo htmlspecialchars($selected_academic_year); ?></h3>
            
            <?php if ($classes->num_rows > 0): ?>
                <div class="class-selection-grid">
                    <?php while ($class = $classes->fetch_assoc()): ?>
                        <div class="class-card">
                            <h3><?php echo htmlspecialchars($class['section']); ?></h3>
                            <div class="class-info">
                             
                                <div class="info-item">
                                    <span>🏢 Department:</span>
                                    <strong><?php echo htmlspecialchars($class['dept_name']); ?></strong>
                                </div>
                                <div class="info-item">
                                    <span>📅 Year:</span>
                                    <strong><?php echo $class['year']; ?></strong>
                                </div>
                                <div class="info-item">
                                    <span>📆 Semester:</span>
                                    <strong><?php echo $class['semester']; ?></strong>
                                </div>
                                <div class="info-item">
                                    <span>📚 Academic Year:</span>
                                    <strong><?php echo htmlspecialchars($class['academic_year']); ?></strong>
                                </div>
                                <div class="info-item" style="background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)); padding: 12px; border-radius: 10px; margin-top: 10px;">
                                    <span style="font-size: 18px;">👥 Students:</span>
                                    <strong style="font-size: 24px; color: #667eea;"><?php echo $class['student_count']; ?></strong>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 10px; margin-top: 15px;">
                                <a href="mark_attendance.php?class_id=<?php echo $class['id']; ?>&section=<?php echo urlencode($class['section']); ?>" 
                                   class="btn btn-primary" style="flex: 1;">
                                    📝 Mark Attendance
                                </a>
                                <a href="view_attendance.php?class_id=<?php echo $class['id']; ?>&section=<?php echo urlencode($class['section']); ?>" 
                                   class="btn btn-info" style="flex: 1;">
                                    📊 View Reports
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <h3>⚠️ No Classes Found</h3>
                    <p>You don't have any classes assigned for the academic year <strong><?php echo htmlspecialchars($selected_academic_year); ?></strong>.</p>
                    <p>Please select a different year or contact the administrator.</p>
                </div>
            <?php endif; ?>
        </div>

  <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>📝 ATTENDANCE MARKED TODAY</h3>
                <div class="stat-value"><?php echo $stats['marked_today'] ?? 0; ?></div>
                <p class="stat-subtitle">Academic Year: <?php echo htmlspecialchars($selected_academic_year); ?></p>
            </div>
            
            <div class="stat-card">
                <h3>✅ PRESENT</h3>
                <div class="stat-value" style="color: #28a745;"><?php echo $stats['present_today'] ?? 0; ?></div>
                <p class="stat-subtitle">Students present today</p>
            </div>
            
            <div class="stat-card">
                <h3>❌ ABSENT</h3>
                <div class="stat-value" style="color: #dc3545;"><?php echo $stats['absent_today'] ?? 0; ?></div>
                <p class="stat-subtitle">Students absent today</p>
            </div>
        </div>


        

     
       
    </div>

    <!-- AI Assistant Button -->
    <button class="ai-assistant-btn" onclick="toggleAIChat()">
        <span>🤖</span>
    </button>

    <!-- AI Chat Container -->
    <div class="ai-chat-container" id="aiChatContainer">
        <div class="ai-chat-header">
            <div class="ai-chat-header-info">
                <div class="ai-avatar">🤖</div>
                <div>
                    <h4>AI Teaching Assistant</h4>
                    <p>Here to help you manage classes</p>
                </div>
            </div>
            <div class="ai-header-controls">
                <button class="ai-control-btn" onclick="clearChat()" title="Clear Chat">🗑️</button>
                <button class="ai-control-btn" onclick="toggleAIChat()" title="Close">✕</button>
            </div>
        </div>
        
        <div class="ai-chat-messages" id="aiChatMessages">
            <div class="ai-message bot">
                <div class="ai-message-avatar">🤖</div>
                <div class="ai-message-content">
                    Hello! I'm your AI Teaching Assistant. I can help you with:
                    <br>• 📚 View classes for <?php echo htmlspecialchars($selected_academic_year); ?>
                    <br>• 📊 Check today's attendance
                    <br>• 📈 View statistics
                    <br>• ❓ Answer questions
                </div>
            </div>
        </div>
        
        <div class="ai-quick-actions">
            <p>Quick Actions:</p>
            <button class="ai-quick-btn" onclick="sendQuickMessage('How many students?')">👥 Total Students</button>
            <button class="ai-quick-btn" onclick="sendQuickMessage('Show attendance')">📊 Today's Stats</button>
            <button class="ai-quick-btn" onclick="sendQuickMessage('Help')">❓ Help</button>
        </div>
        
        <div class="ai-chat-input">
            <input type="text" id="aiChatInput" placeholder="Ask me anything..." onkeypress="handleKeyPress(event)">
            <button onclick="sendMessage()" id="sendBtn">➤</button>
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

    <script>
        const classesData = <?php echo json_encode($classes_data); ?>;
        const detailedStats = <?php echo json_encode($detailed_stats); ?>;
        const todayStats = {
            marked: <?php echo $stats['marked_today'] ?? 0; ?>,
            present: <?php echo $stats['present_today'] ?? 0; ?>,
            absent: <?php echo $stats['absent_today'] ?? 0; ?>
        };
        const selectedYear = "<?php echo $selected_academic_year; ?>";
        const totalStudents = <?php echo $total_students; ?>;
        
        function updateClock() {
            const now = new Date();
            let hours = now.getHours();
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12 || 12;
            hours = String(hours).padStart(2, '0');
            document.getElementById('liveClock').textContent = `${hours}:${minutes}:${seconds} ${ampm}`;
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('liveDate').textContent = now.toLocaleDateString('en-US', options);
        }
        
        updateClock();
        setInterval(updateClock, 1000);
        
        function toggleAIChat() {
            const container = document.getElementById('aiChatContainer');
            container.style.display = container.style.display === 'none' || container.style.display === '' ? 'flex' : 'none';
        }

        function sendMessage() {
            const input = document.getElementById('aiChatInput');
            const message = input.value.trim();
            if (message === '') return;
            addUserMessage(message);
            input.value = '';
            showTypingIndicator();
            setTimeout(() => {
                hideTypingIndicator();
                const response = generateAIResponse(message);
                addBotMessage(response);
            }, 1500);
        }

        function addUserMessage(message) {
            const messagesContainer = document.getElementById('aiChatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'ai-message user';
            messageDiv.innerHTML = `
                <div class="ai-message-avatar">👤</div>
                <div class="ai-message-content">${escapeHtml(message)}</div>
            `;
            messagesContainer.appendChild(messageDiv);
            scrollToBottom();
        }

        function addBotMessage(message) {
            const messagesContainer = document.getElementById('aiChatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'ai-message bot';
            messageDiv.innerHTML = `
                <div class="ai-message-avatar">🤖</div>
                <div class="ai-message-content">${message}</div>
            `;
            messagesContainer.appendChild(messageDiv);
            scrollToBottom();
        }

        function showTypingIndicator() {
            const messagesContainer = document.getElementById('aiChatMessages');
            const typingDiv = document.createElement('div');
            typingDiv.className = 'ai-message bot';
            typingDiv.id = 'typingIndicator';
            typingDiv.innerHTML = `
                <div class="ai-message-avatar">🤖</div>
                <div class="ai-message-content">
                    <div class="typing-indicator">
                        <span></span><span></span><span></span>
                    </div>
                </div>
            `;
            messagesContainer.appendChild(typingDiv);
            scrollToBottom();
        }

        function hideTypingIndicator() {
            const indicator = document.getElementById('typingIndicator');
            if (indicator) indicator.remove();
        }

        function generateAIResponse(message) {
            const lowerMessage = message.toLowerCase();
            
            if (lowerMessage.includes('student') || lowerMessage.includes('how many')) {
                let response = `👥 <strong>Student Overview for ${selectedYear}:</strong><br><br>`;
                response += `Total Students: <strong style="color: #28a745; font-size: 20px;">${totalStudents}</strong><br><br>`;
                response += `<strong>Breakdown by Class:</strong><br>`;
                classesData.forEach((cls, index) => {
                    response += `${index + 1}. <strong>${cls.section}</strong> - ${cls.class_name}<br>`;
                    response += `   • Students: <strong style="color: #667eea;">${cls.student_count}</strong><br><br>`;
                });
                return response;
            }
            else if (lowerMessage.includes('class')) {
                let response = `📚 <strong>Your Classes for ${selectedYear}:</strong><br><br>`;
                response += `You are teaching <strong>${classesData.length}</strong> classes with <strong>${totalStudents}</strong> total students:<br><br>`;
                classesData.forEach((cls, index) => {
                    response += `${index + 1}. <strong>${cls.section}</strong><br>`;
                    response += `   • Students: <strong>${cls.student_count}</strong><br><br>`;
                });
                return response;
            }
            else if (lowerMessage.includes('attendance') || lowerMessage.includes('present') || lowerMessage.includes('absent')) {
                return `📊 <strong>Today's Attendance (${selectedYear}):</strong><br><br>
                    • Present: <span style="color: #28a745; font-weight: 600;">${todayStats.present}</span><br>
                    • Absent: <span style="color: #dc3545; font-weight: 600;">${todayStats.absent}</span><br>
                    • Total Marked: <strong>${todayStats.marked}</strong>`;
            }
            else {
                return `I can help you with:<br>
                    👥 "How many students?" - View total students<br>
                    📚 "Show my classes" - View your classes<br>
                    📊 "Show attendance" - Today's stats<br>
                    📅 Information for academic year: ${selectedYear}`;
            }
        }

        function sendQuickMessage(message) {
            document.getElementById('aiChatInput').value = message;
            sendMessage();
        }

        function clearChat() {
            const messagesContainer = document.getElementById('aiChatMessages');
            messagesContainer.innerHTML = `
                <div class="ai-message bot">
                    <div class="ai-message-avatar">🤖</div>
                    <div class="ai-message-content">Chat cleared! How can I help you?</div>
                </div>
            `;
        }

        function handleKeyPress(event) {
            if (event.key === 'Enter') sendMessage();
        }

        function scrollToBottom() {
            const messagesContainer = document.getElementById('aiChatMessages');
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        function escapeHtml(text) {
            const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
            return text.replace(/[&<>"']/g, m => map[m]);
        }





        // Check for unread messages
function checkUnreadMessages() {
    fetch('../chat_handler.php?action=get_unread_count')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.count > 0) {
                // Add badge to messages button or navbar
                const badge = `<span style="background: #ff6b6b; color: white; border-radius: 50%; padding: 2px 8px; font-size: 11px; margin-left: 5px;">${data.count}</span>`;
                // Update your messages button with the badge
            }
        });
}

// Check every 30 seconds
setInterval(checkUnreadMessages, 30000);
checkUnreadMessages();
    </script>
</body>
</html>