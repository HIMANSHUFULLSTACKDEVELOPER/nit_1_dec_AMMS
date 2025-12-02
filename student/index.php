<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../db.php';
checkRole(['student']);

$student_id = $_SESSION['user_id'];

// Get student info with class section details
$student_query = "SELECT s.*, d.dept_name, c.class_name, c.section, c.year as class_year, c.semester as class_semester
                  FROM students s
                  LEFT JOIN departments d ON s.department_id = d.id
                  LEFT JOIN classes c ON s.class_id = c.id
                  WHERE s.id = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// Get unread notifications count
$unread_count_query = "SELECT COUNT(*) as unread FROM student_notifications 
                       WHERE student_id = ? AND is_read = 0";
$stmt = $conn->prepare($unread_count_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$unread_result = $stmt->get_result();
$unread_count = $unread_result->fetch_assoc()['unread'];

// Get recent notifications (last 10)
$notifications_query = "SELECT sn.*, u.full_name as teacher_name, c.section as class_section
                        FROM student_notifications sn
                        LEFT JOIN users u ON sn.teacher_id = u.id
                        LEFT JOIN classes c ON sn.class_id = c.id
                        WHERE sn.student_id = ?
                        ORDER BY sn.created_at DESC
                        LIMIT 10";
$stmt = $conn->prepare($notifications_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$notifications = $stmt->get_result();

// Get today's attendance
$today = date('Y-m-d');
$today_query = "SELECT sa.*
                FROM student_attendance sa
                WHERE sa.student_id = ? AND sa.attendance_date = ?";
$stmt = $conn->prepare($today_query);
$stmt->bind_param("is", $student_id, $today);
$stmt->execute();
$today_attendance = $stmt->get_result();

// Get current month statistics
$current_month = date('Y-m');
$month_stats_query = "SELECT 
                      COUNT(*) as total_days,
                      SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                      SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                      SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late
                      FROM student_attendance
                      WHERE student_id = ? 
                      AND DATE_FORMAT(attendance_date, '%Y-%m') = ?";
$stmt = $conn->prepare($month_stats_query);
$stmt->bind_param("is", $student_id, $current_month);
$stmt->execute();
$month_stats_result = $stmt->get_result();
$month_stats = $month_stats_result->fetch_assoc();

$total_days = $month_stats['total_days'];
$attendance_percentage = $total_days > 0 ? round(($month_stats['present'] / $total_days) * 100, 2) : 0;

// Get overall statistics
$overall_stats_query = "SELECT 
                        COUNT(*) as total_days,
                        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late
                        FROM student_attendance
                        WHERE student_id = ?";
$stmt = $conn->prepare($overall_stats_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$overall_stats_result = $stmt->get_result();
$overall_stats = $overall_stats_result->fetch_assoc();

$overall_total = $overall_stats['total_days'];
$overall_percentage = $overall_total > 0 ? round(($overall_stats['present'] / $overall_total) * 100, 2) : 0;

// Get recent attendance with teacher name, class and department
$recent_query = "SELECT sa.*, u.full_name as teacher_name, 
                 c.class_name, c.section, d.dept_name
                 FROM student_attendance sa
                 LEFT JOIN users u ON sa.marked_by = u.id
                 LEFT JOIN classes c ON sa.class_id = c.id
                 LEFT JOIN departments d ON c.department_id = d.id
                 WHERE sa.student_id = ?
                 ORDER BY sa.attendance_date DESC LIMIT 10";
$stmt = $conn->prepare($recent_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$recent_attendance = $stmt->get_result();

// Display class section with proper formatting
$section_names = [
    'Civil' => '🗿️ Civil Engineering',
    'Mechanical' => '⚙️ Mechanical Engineering',
    'CSE-A' => '💻 Computer Science - A',
    'CSE-B' => '💻 Computer Science - B',
    'Electrical' => '⚡ Electrical Engineering'
];

$display_section = isset($section_names[$student['section']]) ? 
                   $section_names[$student['section']] : 
                   htmlspecialchars($student['section'] ?? $student['class_name']);

// Inspirational quotes array
$inspirational_quotes = [
    ["quote" => "Success is not final, failure is not fatal: it is the courage to continue that counts.", "author" => "Winston Churchill"],
    ["quote" => "Education is the most powerful weapon which you can use to change the world.", "author" => "Nelson Mandela"],
    ["quote" => "The future belongs to those who believe in the beauty of their dreams.", "author" => "Eleanor Roosevelt"],
    ["quote" => "Your time is limited, don't waste it living someone else's life.", "author" => "Steve Jobs"],
    ["quote" => "The only way to do great work is to love what you do.", "author" => "Steve Jobs"],
    ["quote" => "Don't watch the clock; do what it does. Keep going.", "author" => "Sam Levenson"],
    ["quote" => "Believe you can and you're halfway there.", "author" => "Theodore Roosevelt"],
    ["quote" => "The expert in anything was once a beginner.", "author" => "Helen Hayes"],
    ["quote" => "Learning never exhausts the mind.", "author" => "Leonardo da Vinci"],
    ["quote" => "Strive for progress, not perfection.", "author" => "Unknown"]
];

// Select a random quote
$daily_quote = $inspirational_quotes[array_rand($inspirational_quotes)];

// Include notices component
$notices_path = __DIR__ . '/../admin/notices_component.php';
if (!file_exists($notices_path)) {
    $notices_path = __DIR__ . '/notices_component.php';
}
if (file_exists($notices_path)) {
    require_once $notices_path;
}





// Check if student has a resume
$student_id = $_SESSION['user_id'];
$resume_check = "SELECT id FROM student_resumes WHERE student_id = ?";
$stmt = $conn->prepare($resume_check);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$has_resume = $stmt->get_result()->num_rows > 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - NIT College</title>
    <link rel="icon" href="../Nit_logo.png" type="image/png" />
    <link rel="stylesheet" href="css/dashboard.css">

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
    gap: 15px;
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

/* Inspirational Quote Card */
.inspiration-container {
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

.quote-background {
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

.quote-content {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 40px;
    align-items: center;
}

.quote-text-area h3 {
    font-size: 28px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 20px;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: 10px;
}

.quote-text {
    font-size: 20px;
    font-style: italic;
    color: #2c3e50;
    line-height: 1.8;
    margin-bottom: 15px;
    font-weight: 500;
}

.quote-text::before {
    content: """;
    font-size: 40px;
    color: rgba(102, 126, 234, 0.3);
    margin-right: 5px;
}

.quote-text::after {
    content: """;
    font-size: 40px;
    color: rgba(102, 126, 234, 0.3);
    margin-left: 5px;
}

.quote-author {
    font-size: 16px;
    color: #666;
    text-align: right;
    font-weight: 600;
}

.quote-author::before {
    content: "— ";
}

/* Profile Card */
.profile-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 25px;
    padding: 40px;
    box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
    margin: 30px 0;
    border: 2px solid rgba(255, 255, 255, 0.5);
}

.profile-card h2 {
    font-size: 28px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 25px;
    font-weight: 800;
}

.profile-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.profile-item {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
    padding: 15px 20px;
    border-radius: 12px;
    border-left: 4px solid #667eea;
}

.profile-item strong {
    color: #667eea;
    display: block;
    margin-bottom: 5px;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Action Buttons Section */
.action-buttons {
    text-align: center;
    margin: 40px 0;
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 15px;
}

/* Notification Badge */
.notification-badge {
    background: linear-gradient(135deg, #ff6b6b, #ee5a5a);
    color: white;
    border-radius: 50%;
    padding: 4px 10px;
    font-size: 12px;
    font-weight: bold;
    margin-left: 10px;
    animation: bounce 2s ease-in-out infinite;
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-5px); }
}

/* Notification Cards */
.notifications-container {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    padding: 40px;
    border-radius: 25px;
    margin: 30px 0;
    box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
    border: 2px solid rgba(255, 255, 255, 0.5);
}

.notifications-container h2 {
    font-size: 28px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 30px;
    font-weight: 800;
    display: flex;
    align-items: center;
}

.notification-card {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.7));
    border-left: 4px solid #007bff;
    padding: 25px;
    margin-bottom: 20px;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    transition: all 0.3s;
}

.notification-card:hover {
    transform: translateX(10px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

.notification-card.unread {
    background: linear-gradient(135deg, rgba(255, 243, 205, 0.9), rgba(255, 236, 179, 0.9));
    border-left-color: #ffc107;
}

.notification-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.notification-from {
    font-weight: 700;
    color: #2c3e50;
    font-size: 16px;
}

.notification-date {
    font-size: 12px;
    color: #666;
    background: rgba(102, 126, 234, 0.1);
    padding: 5px 12px;
    border-radius: 20px;
}

.notification-message {
    color: #555;
    line-height: 1.8;
    margin: 15px 0;
    padding: 15px;
    background: rgba(255, 255, 255, 0.7);
    border-radius: 10px;
    white-space: pre-wrap;
}

.notification-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 15px;
    font-size: 12px;
    color: #666;
}

.email-sent-badge {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    padding: 5px 12px;
    border-radius: 15px;
    font-size: 11px;
    font-weight: 600;
}

.new-message-badge {
    background: linear-gradient(135deg, #ffc107, #ff9800);
    color: #000;
    padding: 5px 12px;
    border-radius: 15px;
    font-size: 11px;
    font-weight: 700;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 25px;
    margin: 40px 0;
}

.stat-card {
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

/* Alerts */
.alert {
    padding: 20px 30px;
    border-radius: 15px;
    margin: 30px 0;
    animation: slideDown 0.5s ease-out;
    backdrop-filter: blur(10px);
    border: 2px solid;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
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

.alert-warning {
    background: rgba(255, 243, 205, 0.95);
    border-color: #ffc107;
    color: #856404;
}

/* Enhanced Table Container */
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
    font-size: 28px;
    font-weight: 800;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.table-wrapper {
    overflow-x: auto;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
}

table {
    width: 100%;
    border-collapse: collapse;
    background: white;
}

thead {
    background: linear-gradient(135deg, #667eea, #764ba2);
    position: sticky;
    top: 0;
    z-index: 10;
}

thead th {
    padding: 18px 15px;
    color: white;
    font-weight: 700;
    text-align: left;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 1px;
    white-space: nowrap;
    border-bottom: 3px solid rgba(255, 255, 255, 0.3);
}

tbody tr {
    border-bottom: 1px solid #f0f0f0;
    transition: all 0.3s ease;
}

tbody tr:hover {
    background: linear-gradient(90deg, rgba(102, 126, 234, 0.08), rgba(118, 75, 162, 0.08));
    transform: translateX(8px);
    box-shadow: -5px 0 15px rgba(102, 126, 234, 0.2);
}

tbody tr:last-child {
    border-bottom: none;
}

tbody td {
    padding: 20px 15px;
    color: #2c3e50;
    font-size: 14px;
    font-weight: 500;
}

tbody td:first-child {
    font-weight: 700;
    color: #667eea;
}

.teacher-name {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    color: #764ba2;
    background: linear-gradient(135deg, rgba(118, 75, 162, 0.1), rgba(102, 126, 234, 0.1));
    padding: 6px 12px;
    border-radius: 20px;
    border: 1px solid rgba(118, 75, 162, 0.2);
}

.teacher-name::before {
    content: "👨‍🏫";
    font-size: 16px;
}

.class-info {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 600;
    color: #17a2b8;
    background: linear-gradient(135deg, rgba(23, 162, 184, 0.1), rgba(32, 201, 151, 0.1));
    padding: 6px 12px;
    border-radius: 20px;
    border: 1px solid rgba(23, 162, 184, 0.2);
    font-size: 13px;
}

.class-info::before {
    content: "🏛️";
    font-size: 14px;
}

.dept-info {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 600;
    color: #28a745;
    background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(32, 201, 151, 0.1));
    padding: 6px 12px;
    border-radius: 20px;
    border: 1px solid rgba(40, 167, 69, 0.2);
    font-size: 13px;
}

.dept-info::before {
    content: "🎓";
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
    background: linear-gradient(135deg, #d4edda, #a8d5ba);
    color: #155724;
    border: 1px solid #28a745;
}

.badge-danger {
    background: linear-gradient(135deg, #f8d7da, #f5c6cb);
    color: #721c24;
    border: 1px solid #dc3545;
}

.badge-warning {
    background: linear-gradient(135deg, #fff3cd, #ffeaa7);
    color: #856404;
    border: 1px solid #ffc107;
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
    margin: 5px;
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

.btn-warning {
    background: linear-gradient(135deg, #ffc107, #ff9800);
    color: #000;
    box-shadow: 0 4px 15px rgba(255, 193, 7, 0.4);
}

.btn-warning:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 193, 7, 0.6);
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
    color: #4a9eff;
    text-decoration: none;
    font-size: 16px;
    font-weight: 700;
    padding: 12px 30px;
    background: linear-gradient(135deg, rgba(74, 158, 255, 0.2), rgba(0, 212, 255, 0.2));
    border-radius: 25px;
    border: 2px solid rgba(74, 158, 255, 0.5);
    display: inline-block;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(74, 158, 255, 0.3);
}

.company-link:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(74, 158, 255, 0.5);
    border-color: #00d4ff;
}

.no-notifications {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.no-notifications p:first-child {
    font-size: 64px;
    margin-bottom: 20px;
}



/* =====================================================
   ENHANCED MOBILE RESPONSIVE STYLES
   Modern, Animated, and Smooth Transitions
   ===================================================== */

/* =====================================================
   TABLETS & SMALL LAPTOPS (768px - 1024px)
   ===================================================== */
@media (max-width: 1024px) {
    .main-content {
        padding: 30px 20px;
        animation: fadeIn 0.6s ease-out;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
    
    .profile-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }
    
    .inspiration-container {
        padding: 40px 30px;
    }
    
    .quote-text {
        font-size: 18px;
    }
}

/* =====================================================
   MOBILE DEVICES (max-width: 768px)
   ===================================================== */
@media (max-width: 768px) {
    /* Smooth fade-in animation for body */
    body {
        overflow-x: hidden;
        animation: fadeIn 0.8s ease-out;
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }
    
    /* ===== NAVBAR MOBILE STYLES ===== */
    .navbar {
        flex-direction: column;
        gap: 15px;
        padding: 15px 20px;
        position: sticky;
        top: 0;
        animation: slideDown 0.5s ease-out;
        z-index: 1000;
    }
    
    @keyframes slideDown {
        from {
            transform: translateY(-100%);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    .navbar-brand {
        width: 100%;
        justify-content: center;
        gap: 10px;
        animation: fadeInScale 0.6s ease-out 0.2s backwards;
    }
    
    @keyframes fadeInScale {
        from {
            opacity: 0;
            transform: scale(0.8);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }
    
    .navbar-logo {
        width: 40px;
        height: 40px;
        font-size: 20px;
        transition: transform 0.3s ease;
    }
    
    .navbar-logo:active {
        transform: scale(0.9) rotate(15deg);
    }
    
    .navbar h1 {
        font-size: 18px;
        text-align: center;
    }
    
    .user-info {
        flex-direction: column;
        width: 100%;
        gap: 10px;
        animation: fadeInUp 0.6s ease-out 0.3s backwards;
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .user-profile {
        width: 100%;
        justify-content: center;
        padding: 10px 15px;
        font-size: 14px;
        transition: all 0.3s ease;
    }
    
    .user-profile:active {
        transform: scale(0.98);
    }
    
    /* ===== MAIN CONTENT ===== */
    .main-content {
        padding: 20px 15px;
        animation: fadeInUp 0.8s ease-out 0.2s backwards;
    }
    
    /* ===== INSPIRATION SECTION ===== */
    .inspiration-container {
        padding: 30px 20px;
        margin-bottom: 25px;
        animation: slideInLeft 0.8s ease-out;
    }
    
    @keyframes slideInLeft {
        from {
            opacity: 0;
            transform: translateX(-30px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    .quote-content {
        grid-template-columns: 1fr;
        gap: 25px;
    }
    
    .quote-text-area {
        animation: fadeIn 1s ease-out 0.3s backwards;
    }
    
    .quote-text-area h3 {
        font-size: 22px;
        text-align: center;
        animation: bounceIn 0.8s ease-out 0.5s backwards;
    }
    
    @keyframes bounceIn {
        0% {
            opacity: 0;
            transform: scale(0.3);
        }
        50% {
            transform: scale(1.05);
        }
        100% {
            opacity: 1;
            transform: scale(1);
        }
    }
    
    .quote-text {
        font-size: 16px;
        text-align: center;
        line-height: 1.6;
        animation: fadeIn 1s ease-out 0.6s backwards;
    }
    
    .quote-author {
        font-size: 14px;
        text-align: center;
        animation: fadeIn 1s ease-out 0.7s backwards;
    }
    
    /* ===== GLASS CLOCK ===== */
    .glass-clock {
        padding: 20px;
        min-width: 100%;
        animation: zoomIn 0.8s ease-out 0.4s backwards;
    }
    
    @keyframes zoomIn {
        from {
            opacity: 0;
            transform: scale(0.5);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }
    
    .clock-icon {
        font-size: 36px;
        animation: swing 2s ease-in-out infinite;
    }
    
    @keyframes swing {
        0%, 100% {
            transform: rotate(-5deg);
        }
        50% {
            transform: rotate(5deg);
        }
    }
    
    .glass-clock .time {
        font-size: 36px;
        animation: pulse 2s ease-in-out infinite;
    }
    
    .glass-clock .date {
        font-size: 12px;
        animation: fadeIn 1s ease-out 0.8s backwards;
    }
    
    /* ===== PROFILE CARD ===== */
    .profile-card {
        padding: 25px 20px;
        margin: 20px 0;
        animation: slideInUp 0.8s ease-out;
    }
    
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .profile-card h2 {
        font-size: 22px;
        text-align: center;
        margin-bottom: 20px;
        animation: fadeInScale 0.6s ease-out 0.3s backwards;
    }
    
    .profile-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .profile-item {
        padding: 12px 15px;
        animation: fadeInLeft 0.5s ease-out backwards;
        transition: all 0.3s ease;
    }
    
    @keyframes fadeInLeft {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    .profile-item:nth-child(1) { animation-delay: 0.1s; }
    .profile-item:nth-child(2) { animation-delay: 0.15s; }
    .profile-item:nth-child(3) { animation-delay: 0.2s; }
    .profile-item:nth-child(4) { animation-delay: 0.25s; }
    .profile-item:nth-child(5) { animation-delay: 0.3s; }
    .profile-item:nth-child(6) { animation-delay: 0.35s; }
    .profile-item:nth-child(7) { animation-delay: 0.4s; }
    .profile-item:nth-child(8) { animation-delay: 0.45s; }
    .profile-item:nth-child(9) { animation-delay: 0.5s; }
    
    .profile-item:active {
        transform: scale(0.98);
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.15), rgba(118, 75, 162, 0.15));
    }
    
    .profile-item strong {
        font-size: 12px;
    }
    
    /* ===== ACTION BUTTONS ===== */
    .action-buttons {
        flex-direction: column;
        gap: 12px;
        margin: 30px 0;
        animation: fadeInUp 0.8s ease-out 0.4s backwards;
    }
    
    .btn {
        width: 100%;
        padding: 14px 20px;
        font-size: 15px;
        margin: 0;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        animation: slideInRight 0.5s ease-out backwards;
        position: relative;
        overflow: hidden;
    }
    
    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(30px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    .btn:nth-child(1) { animation-delay: 0.1s; }
    .btn:nth-child(2) { animation-delay: 0.15s; }
    .btn:nth-child(3) { animation-delay: 0.2s; }
    .btn:nth-child(4) { animation-delay: 0.25s; }
    .btn:nth-child(5) { animation-delay: 0.3s; }
    .btn:nth-child(6) { animation-delay: 0.35s; }
    .btn:nth-child(7) { animation-delay: 0.4s; }
    
    .btn::before {
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
    
    .btn:active::before {
        width: 300px;
        height: 300px;
    }
    
    .btn:active {
        transform: scale(0.95);
    }
    
    /* ===== NOTIFICATIONS ===== */
    .notifications-container {
        padding: 25px 20px;
        margin: 20px 0;
        animation: fadeIn 0.8s ease-out 0.5s backwards;
    }
    
    .notifications-container h2 {
        font-size: 22px;
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 20px;
        animation: fadeInScale 0.6s ease-out 0.6s backwards;
    }
    
    .notification-badge {
        margin-left: 0;
        margin-top: 5px;
        animation: bounceIn 0.8s ease-out 0.8s backwards;
    }
    
    .notification-card {
        padding: 20px 15px;
        margin-bottom: 15px;
        animation: slideInLeft 0.6s ease-out backwards;
        transition: all 0.3s ease;
    }
    
    .notification-card:nth-child(1) { animation-delay: 0.1s; }
    .notification-card:nth-child(2) { animation-delay: 0.2s; }
    .notification-card:nth-child(3) { animation-delay: 0.3s; }
    .notification-card:nth-child(4) { animation-delay: 0.4s; }
    .notification-card:nth-child(5) { animation-delay: 0.5s; }
    
    .notification-card:active {
        transform: scale(0.98);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
    }
    
    .notification-header {
        flex-direction: column;
        gap: 10px;
        align-items: flex-start;
    }
    
    .notification-from {
        font-size: 15px;
    }
    
    .notification-date {
        font-size: 11px;
        padding: 4px 10px;
    }
    
    .notification-message {
        font-size: 14px;
        padding: 12px;
        margin: 12px 0;
        animation: fadeIn 0.6s ease-out 0.3s backwards;
    }
    
    .notification-footer {
        flex-direction: column;
        gap: 8px;
        align-items: flex-start;
        animation: fadeIn 0.6s ease-out 0.4s backwards;
    }
    
    /* ===== ALERTS ===== */
    .alert {
        padding: 15px 20px;
        margin: 20px 0;
        font-size: 14px;
        animation: slideDown 0.6s ease-out;
    }
    
    /* ===== STATS GRID ===== */
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 15px;
        margin: 30px 0;
    }
    
    .stat-card {
        padding: 25px 20px;
        animation: zoomIn 0.6s ease-out backwards;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .stat-card:nth-child(1) { animation-delay: 0.1s; }
    .stat-card:nth-child(2) { animation-delay: 0.2s; }
    .stat-card:nth-child(3) { animation-delay: 0.3s; }
    .stat-card:nth-child(4) { animation-delay: 0.4s; }
    .stat-card:nth-child(5) { animation-delay: 0.5s; }
    
    .stat-card:active {
        transform: scale(0.95) translateY(2px);
    }
    
    .stat-card h3 {
        font-size: 12px;
        margin-bottom: 12px;
    }
    
    .stat-value {
        font-size: 36px;
        animation: countUp 1s ease-out 0.3s backwards;
    }
    
    @keyframes countUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* ===== TABLE CONTAINER ===== */
    .table-container {
        padding: 25px 15px;
        margin: 20px 0;
        animation: fadeIn 0.8s ease-out 0.6s backwards;
    }
    
    .table-container h3 {
        font-size: 22px;
        margin-bottom: 20px;
        text-align: center;
        animation: fadeInScale 0.6s ease-out 0.7s backwards;
    }
    
    .table-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scroll-behavior: smooth;
        animation: slideInUp 0.8s ease-out 0.8s backwards;
    }
    
    /* Scrollbar styling for mobile */
    .table-wrapper::-webkit-scrollbar {
        height: 6px;
    }
    
    .table-wrapper::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.1);
        border-radius: 10px;
    }
    
    .table-wrapper::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, #667eea, #764ba2);
        border-radius: 10px;
    }
    
    table {
        min-width: 600px;
        font-size: 13px;
    }
    
    thead th {
        padding: 12px 10px;
        font-size: 11px;
        position: sticky;
        top: 0;
    }
    
    tbody tr {
        transition: all 0.3s ease;
    }
    
    tbody tr:active {
        background: linear-gradient(90deg, rgba(102, 126, 234, 0.15), rgba(118, 75, 162, 0.15));
        transform: scale(0.98);
    }
    
    tbody td {
        padding: 15px 10px;
        font-size: 13px;
    }
    
    .teacher-name,
    .class-info,
    .dept-info {
        font-size: 12px;
        padding: 5px 10px;
        transition: all 0.3s ease;
    }
    
    .badge {
        padding: 5px 12px;
        font-size: 10px;
        animation: pulse 2s ease-in-out infinite;
    }
    
    /* ===== FOOTER ===== */
    .footer-content {
        padding: 25px 15px 15px;
        animation: fadeIn 1s ease-out 1s backwards;
    }
    
    .developer-section {
        padding: 20px 15px;
        animation: slideInUp 0.8s ease-out 1.1s backwards;
    }
    
    .developer-section p {
        font-size: 13px;
    }
    
    .company-link {
        font-size: 14px;
        padding: 10px 25px;
        transition: all 0.3s ease;
    }
    
    .company-link:active {
        transform: scale(0.95);
    }
    
    .developer-section > div:last-child {
        flex-direction: column;
        gap: 10px;
    }
    
    .developer-section a[href*="himanshufullstackdeveloper"],
    .developer-section a[href*="devpranaypanore"] {
        width: 100%;
        justify-content: center;
        transition: all 0.3s ease;
    }
    
    .developer-section a[href*="himanshufullstackdeveloper"]:active,
    .developer-section a[href*="devpranaypanore"]:active {
        transform: scale(0.95);
    }
    
    /* ===== NO NOTIFICATIONS ===== */
    .no-notifications {
        padding: 40px 15px;
        animation: fadeIn 1s ease-out;
    }
    
    .no-notifications p:first-child {
        font-size: 48px;
        animation: bounce 2s ease-in-out infinite;
    }
    
    @keyframes bounce {
        0%, 100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-10px);
        }
    }
    
    .no-notifications p {
        font-size: 16px;
    }
}

/* =====================================================
   SMALL MOBILE DEVICES (max-width: 480px)
   ===================================================== */
@media (max-width: 480px) {
    .navbar h1 {
        font-size: 16px;
    }
    
    .profile-card h2,
    .notifications-container h2,
    .table-container h3 {
        font-size: 20px;
    }
    
    .quote-text-area h3 {
        font-size: 20px;
    }
    
    .quote-text {
        font-size: 15px;
    }
    
    .glass-clock {
        padding: 15px;
    }
    
    .clock-icon {
        font-size: 32px;
    }
    
    .glass-clock .time {
        font-size: 32px;
    }
    
    .btn {
        padding: 12px 18px;
        font-size: 14px;
    }
    
    .inspiration-container {
        padding: 25px 15px;
    }
    
    .quote-text::before,
    .quote-text::after {
        font-size: 30px;
    }
    
    .profile-card,
    .notifications-container,
    .table-container {
        padding: 20px 15px;
        border-radius: 20px;
    }
    
    .notification-card {
        padding: 15px 12px;
    }
    
    .notification-message {
        font-size: 13px;
        padding: 10px;
    }
    
    .stat-card {
        padding: 20px 15px;
    }
    
    .stat-value {
        font-size: 32px;
    }
    
    table {
        min-width: 550px;
        font-size: 12px;
    }
    
    thead th {
        padding: 10px 8px;
        font-size: 10px;
    }
    
    tbody td {
        padding: 12px 8px;
        font-size: 12px;
    }
    
    .developer-section p {
        font-size: 12px;
    }
    
    .company-link {
        font-size: 13px;
        padding: 8px 20px;
    }
    
    .developer-section a[href*="himanshufullstackdeveloper"] span,
    .developer-section a[href*="devpranaypanore"] span {
        font-size: 12px;
    }
}

/* =====================================================
   VERY SMALL DEVICES (max-width: 360px)
   ===================================================== */
@media (max-width: 360px) {
    .main-content {
        padding: 15px 10px;
    }
    
    .navbar {
        padding: 12px 15px;
    }
    
    .navbar h1 {
        font-size: 14px;
    }
    
    .inspiration-container,
    .profile-card,
    .notifications-container,
    .table-container {
        padding: 15px 12px;
    }
    
    .quote-text {
        font-size: 14px;
    }
    
    .glass-clock .time {
        font-size: 28px;
    }
    
    .stat-value {
        font-size: 28px;
    }
    
    .btn {
        padding: 10px 15px;
        font-size: 13px;
    }
}

/* =====================================================
   LANDSCAPE ORIENTATION FOR PHONES
   ===================================================== */
@media (max-width: 768px) and (orientation: landscape) {
    .navbar {
        padding: 10px 20px;
    }
    
    .navbar h1 {
        font-size: 16px;
    }
    
    .quote-content {
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .glass-clock {
        min-width: auto;
    }
    
    .inspiration-container {
        padding: 20px;
    }
}

/* =====================================================
   TOUCH DEVICE OPTIMIZATIONS
   ===================================================== */
@media (hover: none) {
    /* Remove hover effects on touch devices */
    .btn:hover,
    .stat-card:hover,
    .notification-card:hover,
    tbody tr:hover {
        transform: none;
    }
    
    /* Enhanced active states for better touch feedback */
    .btn:active {
        transform: scale(0.95);
        transition: transform 0.1s ease;
    }
    
    .stat-card:active {
        transform: scale(0.98);
        transition: transform 0.1s ease;
    }
    
    .notification-card:active {
        transform: translateX(5px);
        transition: transform 0.2s ease;
    }
}

/* =====================================================
   IOS SAFARI FIXES
   ===================================================== */
@supports (-webkit-touch-callout: none) {
    .navbar {
        position: -webkit-sticky;
        position: sticky;
    }
    
    .table-wrapper {
        -webkit-overflow-scrolling: touch;
    }
    
    /* Fix for iOS input zoom */
    input, select, textarea {
        font-size: 16px !important;
    }
}

/* =====================================================
   PRINT STYLES
   ===================================================== */
@media print {
    .navbar,
    .action-buttons,
    .footer,
    .particles {
        display: none;
    }
    
    .main-content {
        padding: 0;
    }
    
    .profile-card,
    .table-container,
    .notifications-container {
        box-shadow: none;
        border: 1px solid #ddd;
        break-inside: avoid;
    }
    
    body {
        background: white;
    }
}

/* =====================================================
   REDUCED MOTION ACCESSIBILITY
   ===================================================== */
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}

/* =====================================================
   HIGH CONTRAST MODE
   ===================================================== */
@media (prefers-contrast: high) {
    .btn {
        border: 2px solid currentColor;
    }
    
    .stat-card,
    .profile-card,
    .notification-card {
        border: 2px solid rgba(0, 0, 0, 0.3);
    }
}

/* =====================================================
   DARK MODE PREFERENCES
   ===================================================== */
@media (prefers-color-scheme: dark) {
    /* Add dark mode adjustments if needed */
    .table-wrapper::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
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
    <!-- Animated Particles Background -->
    <div class="particles">
        <div class="particle" style="width: 10px; height: 10px; left: 10%; animation-delay: 0s;"></div>
        <div class="particle" style="width: 15px; height: 15px; left: 20%; animation-delay: 2s;"></div>
        <div class="particle" style="width: 8px; height: 8px; left: 30%; animation-delay: 4s;"></div>
        <div class="particle" style="width: 12px; height: 12px; left: 40%; animation-delay: 6s;"></div>
        <div class="particle" style="width: 10px; height: 10px; left: 50%; animation-delay: 8s;"></div>
        <div class="particle" style="width: 14px; height: 14px; left: 60%; animation-delay: 10s;"></div>
        <div class="particle" style="width: 9px; height: 9px; left: 70%; animation-delay: 12s;"></div>
        <div class="particle" style="width: 11px; height: 11px; left: 80%; animation-delay: 14s;"></div>
        <div class="particle" style="width: 13px; height: 13px; left: 90%; animation-delay: 16s;"></div>
    </div>

    <!-- Enhanced Navbar -->
    <nav class="navbar">
        <div class="navbar-brand">
            <div class="navbar-logo">🎓</div>
            <h1>NIT AMMS - Student Portal</h1>
        </div>
        <div class="user-info">
            <a href="profile.php" class="btn btn-info">👤 My Profile</a>
            <div class="user-profile">
                <span>👨‍🎓</span>
                <span><?php echo htmlspecialchars($student['full_name']); ?></span>
            </div>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <!-- Inspirational Quote Section -->
        <div class="inspiration-container">
            <div class="quote-background"></div>
            <div class="animated-wave"></div>
            <div class="quote-content">
                <div class="quote-text-area">
                    <h3>💡 Daily Inspiration</h3>
                    <p class="quote-text"><?php echo htmlspecialchars($daily_quote['quote']); ?></p>
                    <p class="quote-author"><?php echo htmlspecialchars($daily_quote['author']); ?></p>
                </div>
                <div class="glass-clock">
                    <div class="clock-icon">⏰</div>
                    <div class="time" id="clock">--:--:--</div>
                    <div class="date" id="date">Loading...</div>
                </div>
            </div>
        </div>

        <!-- Profile Card -->
        <div class="profile-card">
            <h2>👤 Student Profile</h2>
            <div class="profile-grid">
                <div class="profile-item">
                    <strong>Roll Number</strong>
                    <div><?php echo htmlspecialchars($student['roll_number']); ?></div>
                </div>
                <div class="profile-item">
                    <strong>Email</strong>
                    <div><?php echo htmlspecialchars($student['email']); ?></div>
                </div>
                <div class="profile-item">
                    <strong>Phone</strong>
                    <div><?php echo htmlspecialchars($student['phone']); ?></div>
                </div>
                <div class="profile-item">
                    <strong>Department</strong>
                    <div><?php echo htmlspecialchars($student['dept_name']); ?></div>
                </div>
                <div class="profile-item">
                    <strong>Class/Section</strong>
                    <div><?php echo $display_section; ?></div>
                </div>
                <div class="profile-item">
                    <strong>Year</strong>
                    <div><?php echo $student['year']; ?></div>
                </div>
                <div class="profile-item">
                    <strong>Semester</strong>
                    <div><?php echo $student['semester']; ?></div>
                </div>
                <div class="profile-item">
                    <strong>Admission Year</strong>
                    <div><?php echo htmlspecialchars($student['admission_year']); ?></div>
                </div>
                <div class="profile-item">
                    <strong>Status</strong>
                    <div>
                        <?php if ($student['is_active']): ?>
                            <span class="badge badge-success">Active</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Inactive</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>


<!-- Add this button in your quick actions/menu section -->
<div class="quick-action-card">
    <a href="<?php echo $has_resume ? 'view_resume.php' : 'create_resume.php'; ?>" class="action-link">
        <div class="action-icon" style="background: linear-gradient(135deg, #667eea, #764ba2);">
            📄
        </div>
        <div class="action-content">
            <h3><?php echo $has_resume ? 'My Resume' : 'Create Resume'; ?></h3>
            <p><?php echo $has_resume ? 'View and edit your resume' : 'Build your professional resume'; ?></p>
        </div>
        <div class="action-arrow">→</div>
    </a>
</div>



        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="new.php" class="btn btn-primary">📰 Latest News</a>
            <a href="syallbus.php" class="btn btn-success">📚 Syllabus</a>
            <a href="time_tablee.php" class="btn btn-info">📅 Time Table</a>
            <a href="./assignment.php" class="btn btn-warning">📝 Assignment</a>
            <a href="teachercall.html" class="btn btn-primary">📞 Call Teacher</a>
            <a href="chat.php" class="btn btn-info">💬 Messages</a>

            <a href="./view_child_paper_marks.php" class="btn btn-primary">📊 My Marks</a>

            <!-- Add this in the action buttons area -->
<a href="apply_leave.php" class="btn btn-warning">
    📝 Apply for Leave
</a>

<a href="student_view_examtime.php" class="btn btn-primary">📅 My Timetable</a>
        </div>
 <?php 
        if (function_exists('displayNotices')) {
            displayNotices('student'); 
        }
        ?>




        <!-- Notifications Section -->
        <div class="notifications-container">
            <h2>
                📬 Messages from Teachers 
                <?php if ($unread_count > 0): ?>
                    <span class="notification-badge"><?php echo $unread_count; ?> New</span>
                <?php endif; ?>
            </h2>
            
            <?php if ($notifications && $notifications->num_rows > 0): ?>
                <?php while ($notification = $notifications->fetch_assoc()): ?>
                    <div class="notification-card <?php echo $notification['is_read'] == 0 ? 'unread' : ''; ?>">
                        <div class="notification-header">
                            <div>
                                <span class="notification-from">
                                    👨‍🏫 <?php echo htmlspecialchars($notification['teacher_name']); ?>
                                </span>
                                <?php if ($notification['class_section']): ?>
                                    <span style="color: #666; font-size: 14px; margin-left: 10px;">
                                        (<?php echo htmlspecialchars($notification['class_section']); ?>)
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="notification-date">
                                <?php 
                                $date = strtotime($notification['created_at']);
                                $today_start = strtotime('today');
                                $yesterday_start = strtotime('yesterday');
                                
                                if ($date >= $today_start) {
                                    echo 'Today, ' . date('g:i A', $date);
                                } elseif ($date >= $yesterday_start) {
                                    echo 'Yesterday, ' . date('g:i A', $date);
                                } else {
                                    echo date('d M Y, g:i A', $date);
                                }
                                ?>
                            </div>
                        </div>
                        
                        <div class="notification-message">
                            <?php echo nl2br(htmlspecialchars($notification['message'])); ?>
                        </div>
                        
                        <div class="notification-footer">
                            <span>
                                📅 Date: <?php echo date('d M Y', strtotime($notification['notification_date'])); ?>
                            </span>
                            <?php if ($notification['email_sent'] == 1): ?>
                                <span class="email-sent-badge">✉️ Email Sent</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($notification['is_read'] == 0): ?>
                            <div style="margin-top: 10px;">
                                <span class="new-message-badge">🆕 New Message</span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
                
                <div style="text-align: center; margin-top: 20px;">
                    <a href="all_messages.php" class="btn btn-primary">📬 View All Messages</a>
                </div>
            <?php else: ?>
                <div class="no-notifications">
                    <p>📭</p>
                    <p style="font-size: 18px; color: #666; margin-bottom: 10px;">No messages yet</p>
                    <p style="font-size: 14px; color: #999;">Your teachers will send you attendance-related messages here</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Today's Attendance Alert -->
        <?php if ($today_attendance && $today_attendance->num_rows > 0): ?>
            <?php $today_record = $today_attendance->fetch_assoc(); ?>
            <div class="alert alert-success">
                <strong>✅ Today's Attendance: <?php echo strtoupper($today_record['status']); ?></strong>
                <?php if ($today_record['remarks']): ?>
                    <br>Remarks: <?php echo htmlspecialchars($today_record['remarks']); ?>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                <strong>⚠️ Attendance not marked yet for today</strong>
            </div>
        <?php endif; ?>

        <!-- Monthly Statistics -->
        <h3 style="font-size: 28px; background: linear-gradient(135deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 800; margin: 40px 0 20px;">📊 This Month's Attendance Statistics</h3>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>📅 Total Classes</h3>
                <div class="stat-value"><?php echo $total_days; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>✅ Present</h3>
                <div class="stat-value" style="color: #28a745;"><?php echo $month_stats['present']; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>❌ Absent</h3>
                <div class="stat-value" style="color: #dc3545;"><?php echo $month_stats['absent']; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>⏰ Late</h3>
                <div class="stat-value" style="color: #ffc107;"><?php echo $month_stats['late']; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>📈 Attendance %</h3>
                <div class="stat-value" style="color: <?php echo $attendance_percentage >= 75 ? '#28a745' : '#dc3545'; ?>">
                    <?php echo $attendance_percentage; ?>%
                </div>
            </div>
        </div>

        <!-- Overall Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>📊 Overall Statistics</h3>
                <p style="margin: 10px 0;"><strong>Total Days:</strong> <?php echo $overall_total; ?></p>
                <p style="margin: 10px 0;"><strong>Present:</strong> <span style="color: #28a745; font-weight: 700;"><?php echo $overall_stats['present']; ?></span></p>
                <p style="margin: 10px 0;"><strong>Absent:</strong> <span style="color: #dc3545; font-weight: 700;"><?php echo $overall_stats['absent']; ?></span></p>
                <p style="margin: 10px 0;"><strong>Late:</strong> <span style="color: #ffc107; font-weight: 700;"><?php echo $overall_stats['late']; ?></span></p>
                <p style="margin: 10px 0;"><strong>Overall %:</strong> 
                    <span style="color: <?php echo $overall_percentage >= 75 ? '#28a745' : '#dc3545'; ?>; font-size: 24px; font-weight: 800;">
                        <?php echo $overall_percentage; ?>%
                    </span>
                </p>
            </div>
        </div>

        <!-- Recent Attendance Table -->
        <div class="table-container">
            <h3>📝 Recent Attendance Records</h3>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>📅 Date</th>
                            <th>📆 Day</th>
                            <th>👨‍🏫 Marked By</th>
                            <th>🏛️ Class</th>
                            <th>🎓 Department</th>
                            <th>✓ Status</th>
                            <th>📝 Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recent_attendance->num_rows > 0): ?>
                            <?php while ($record = $recent_attendance->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('d M Y', strtotime($record['attendance_date'])); ?></td>
                                <td><?php echo date('l', strtotime($record['attendance_date'])); ?></td>
                                <td>
                                    <?php if ($record['teacher_name']): ?>
                                        <span class="teacher-name"><?php echo htmlspecialchars($record['teacher_name']); ?></span>
                                    <?php else: ?>
                                        <span style="color: #999; font-style: italic;">Not Available</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($record['class_name'] || $record['section']): ?>
                                        <span class="class-info">
                                            <?php 
                                            $class_display = '';
                                            if ($record['section']) {
                                                $class_display = htmlspecialchars($record['section']);
                                            } elseif ($record['class_name']) {
                                                $class_display = htmlspecialchars($record['class_name']);
                                            }
                                            echo $class_display;
                                            ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #999; font-style: italic;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($record['dept_name']): ?>
                                        <span class="dept-info"><?php echo htmlspecialchars($record['dept_name']); ?></span>
                                    <?php else: ?>
                                        <span style="color: #999; font-style: italic;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    if ($record['status'] === 'present') $status_class = 'badge-success';
                                    elseif ($record['status'] === 'absent') $status_class = 'badge-danger';
                                    else $status_class = 'badge-warning';
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>">
                                        <?php echo strtoupper($record['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($record['remarks'] ?? '-'); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 40px; color: #999;">
                                    <div style="font-size: 48px; margin-bottom: 10px;">📋</div>
                                    <div>No attendance records found</div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Action Buttons -->
        <div style="text-align: center; margin: 40px 0;">
            <a href="attendance_report.php" class="btn btn-primary">📊 View Detailed Report</a>
            <a href="today_attendance.php" class="btn btn-success">📅 Today's Attendance</a>
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
                
                <div style="width: 50%; height: 1px; background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent); margin: 15px auto;"></div>
                
                <p style="color: #888; font-size: 10px; margin: 0 0 12px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600;">💼 Development Team</p>
                
                <div style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; margin-top: 12px;">
                    <a href="https://himanshufullstackdeveloper.github.io/portfoilohimanshu/" style="color: #ffffff; font-size: 13px; text-decoration: none; padding: 8px 16px; background: linear-gradient(135deg, rgba(74, 158, 255, 0.25), rgba(0, 212, 255, 0.25)); border-radius: 20px; border: 1px solid rgba(74, 158, 255, 0.4); display: inline-flex; align-items: center; gap: 6px;">
                        <span style="font-size: 16px;">👨‍💻</span>
                        <span style="font-weight: 600;">Himanshu Patil</span>
                    </a>
                    
                    <a href="https://devpranaypanore.github.io/Pranaypanore-live-.html/" style="color: #ffffff; font-size: 13px; text-decoration: none; padding: 8px 16px; background: linear-gradient(135deg, rgba(74, 158, 255, 0.25), rgba(0, 212, 255, 0.25)); border-radius: 20px; border: 1px solid rgba(74, 158, 255, 0.4); display: inline-flex; align-items: center; gap: 6px;">
                        <span style="font-size: 16px;">👨‍💻</span>
                        <span style="font-weight: 600;">Pranay Panore</span>
                    </a>
                </div>
            </div>
            
            <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.1); text-align: center;">
                <p style="color: #888; font-size: 12px; margin: 0 0 10px;">© 2025 NIT AMMS. All rights reserved.</p>
                <p style="color: #666; font-size: 11px; margin: 0;">
                    Made with <span style="color: #ff4757; font-size: 14px;">❤️</span> by Techyug Software
                </p>
            </div>
        </div>
    </div>

<script>
    // Clock function
function updateClock() {
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    
    const clockElement = document.getElementById('clock');
    if (clockElement) {
        clockElement.textContent = `${hours}:${minutes}:${seconds}`;
    }
    
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const dateElement = document.getElementById('date');
    if (dateElement) {
        dateElement.textContent = now.toLocaleDateString('en-US', options);
    }
}

// Initialize clock
updateClock();
setInterval(updateClock, 1000);

// Check for unread messages
function checkUnreadMessages() {
    fetch('../chat_handler.php?action=get_unread_count')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.count > 0) {
                // Add badge to messages button or navbar
                const badge = `<span style="background: #ff6b6b; color: white; border-radius: 50%; padding: 2px 8px; font-size: 11px; margin-left: 5px;">${data.count}</span>`;
                // Update your messages button with the badge
                const messagesBtn = document.querySelector('a[href="chat.php"]');
                if (messagesBtn && !messagesBtn.querySelector('span')) {
                    messagesBtn.innerHTML += badge;
                }
            }
        })
        .catch(error => console.error('Error checking unread messages:', error));
}

// Check every 30 seconds
setInterval(checkUnreadMessages, 30000);
checkUnreadMessages();

// Smooth scroll for internal links
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

// Add loading animation for buttons
document.querySelectorAll('.btn').forEach(button => {
    button.addEventListener('click', function(e) {
        if (!this.classList.contains('loading')) {
            this.classList.add('loading');
            setTimeout(() => {
                this.classList.remove('loading');
            }, 1000);
        }
    });
});
</script>
</body>
</html>