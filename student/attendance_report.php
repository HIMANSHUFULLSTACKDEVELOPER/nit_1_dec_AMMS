<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../db.php';

// Check if user is logged in
if (!isset($_SESSION['role']) || !isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Check role
if ($_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

$student_id = $_SESSION['user_id'];

// Get student info with class section
$student_query = "SELECT s.*, d.dept_name, c.class_name, c.section
                  FROM students s
                  LEFT JOIN departments d ON s.department_id = d.id
                  LEFT JOIN classes c ON s.class_id = c.id
                  WHERE s.id = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    die("Student record not found.");
}

// Get filter parameters
$filter_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$filter_year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Get attendance for selected month with teacher name
$attendance_query = "SELECT sa.*, u.full_name as teacher_name
                     FROM student_attendance sa
                     LEFT JOIN users u ON sa.marked_by = u.id
                     WHERE sa.student_id = ? 
                     AND DATE_FORMAT(sa.attendance_date, '%Y-%m') = ?
                     ORDER BY sa.attendance_date DESC";
$stmt = $conn->prepare($attendance_query);
$stmt->bind_param("is", $student_id, $filter_month);
$stmt->execute();
$attendance_records = $stmt->get_result();

// Get monthly statistics
$stats_query = "SELECT 
                COUNT(*) as total_days,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late
                FROM student_attendance
                WHERE student_id = ? 
                AND DATE_FORMAT(attendance_date, '%Y-%m') = ?";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("is", $student_id, $filter_month);
$stmt->execute();
$stats_result = $stmt->get_result();
$stats = $stats_result->fetch_assoc();

$total_days = $stats['total_days'] ?? 0;
$present = $stats['present'] ?? 0;
$absent = $stats['absent'] ?? 0;
$late = $stats['late'] ?? 0;
$percentage = $total_days > 0 ? round(($present / $total_days) * 100, 2) : 0;

// Get yearly comparison
$yearly_query = "SELECT 
                 DATE_FORMAT(attendance_date, '%Y-%m') as month,
                 COUNT(*) as total,
                 SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present
                 FROM student_attendance
                 WHERE student_id = ? 
                 AND YEAR(attendance_date) = ?
                 GROUP BY DATE_FORMAT(attendance_date, '%Y-%m')
                 ORDER BY month";
$stmt = $conn->prepare($yearly_query);
$stmt->bind_param("ii", $student_id, $filter_year);
$stmt->execute();
$yearly_data = $stmt->get_result();

// Display class section
$section_names = [
    'Civil' => '🏗️ Civil Engineering',
    'Mechanical' => '⚙️ Mechanical Engineering',
    'CSE-A' => '💻 Computer Science - A',
    'CSE-B' => '💻 Computer Science - B',
    'Electrical' => '⚡ Electrical Engineering'
];

$section = $student['section'] ?? '';
$display_section = isset($section_names[$section]) ? 
                   $section_names[$section] : 
                   htmlspecialchars($section ?: ($student['class_name'] ?? 'N/A'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Report - Student</title>
    <link rel="icon" href="../Nit_logo.png" type="image/svg+xml" />
    <link rel="stylesheet" href="../css/attendance_report.css">
    <style>
        /* ==================== RESET & BASE STYLES ==================== */
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

/* ==================== ANIMATED BACKGROUND PARTICLES ==================== */
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

/* ==================== NAVBAR ==================== */
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
    flex-wrap: wrap;
    gap: 15px;
}

.navbar h1 {
    color: white;
    font-size: 24px;
    font-weight: 700;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    margin: 0;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 15px;
    color: white;
    flex-wrap: wrap;
}

.user-info span {
    font-size: 14px;
    font-weight: 500;
}

/* ==================== MAIN CONTENT ==================== */
.main-content {
    padding: 40px;
    max-width: 1600px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
}

/* ==================== PROFILE CARD ==================== */
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
    background-clip: text;
    margin-bottom: 25px;
    font-weight: 800;
}

.profile-card p {
    font-size: 15px;
    color: #2c3e50;
    margin-bottom: 12px;
    line-height: 1.6;
}

.profile-card strong {
    color: #667eea;
    font-weight: 600;
}

/* ==================== STATS GRID ==================== */
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
    0%, 100% { 
        background-position: 0% 50%; 
    }
    50% { 
        background-position: 100% 50%; 
    }
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
    background-clip: text;
}

/* ==================== ALERTS ==================== */
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

.alert-info {
    background: rgba(217, 237, 247, 0.95);
    border-color: #17a2b8;
    color: #0c5460;
}

/* ==================== TABLE CONTAINER ==================== */
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
    background-clip: text;
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* ==================== FORM ELEMENTS ==================== */
.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #2c3e50;
    font-weight: 600;
    font-size: 14px;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid rgba(102, 126, 234, 0.2);
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.3s;
    background: white;
    font-family: inherit;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* ==================== TABLE STYLES ==================== */
.table-wrapper {
    overflow-x: auto;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    -webkit-overflow-scrolling: touch;
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

/* ==================== BADGES ==================== */
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

.badge-info {
    background: linear-gradient(135deg, #d1ecf1, #bee5eb);
    color: #0c5460;
    border: 1px solid #17a2b8;
}

/* ==================== BUTTONS ==================== */
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
    white-space: nowrap;
    font-family: inherit;
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

.btn-secondary {
    background: linear-gradient(135deg, #6c757d, #5a6268);
    color: white;
    box-shadow: 0 4px 15px rgba(108, 117, 125, 0.4);
}

.btn-secondary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(108, 117, 125, 0.6);
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

/* ==================== FOOTER ==================== */
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
    0% { 
        background-position: 0% 50%; 
    }
    100% { 
        background-position: 200% 50%; 
    }
}

/* ==================== PRINT STYLES ==================== */
@media print {
    body {
        background: white;
    }

    .navbar,
    .btn,
    .form-group,
    .particles {
        display: none !important;
    }

    .main-content,
    .table-container,
    .profile-card,
    .stat-card {
        box-shadow: none;
        background: white;
        page-break-inside: avoid;
    }

    table {
        page-break-inside: avoid;
    }

    thead {
        display: table-header-group;
        background: #667eea !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}

/* ==================== RESPONSIVE: LARGE TABLETS (768px - 1024px) ==================== */
@media (max-width: 1024px) {
    .main-content {
        padding: 30px 25px;
    }

    .table-container {
        padding: 30px 25px;
    }

    .profile-card {
        padding: 30px 25px;
    }

    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
    }

    .stat-card {
        padding: 25px;
    }

    .stat-value {
        font-size: 40px;
    }

    .navbar {
        padding: 15px 25px;
    }

    .navbar h1 {
        font-size: 20px;
        flex-basis: 100%;
        text-align: center;
    }

    .user-info {
        flex-basis: 100%;
        justify-content: center;
    }

    thead th {
        font-size: 12px;
        padding: 15px 12px;
    }

    tbody td {
        padding: 15px 12px;
        font-size: 13px;
    }

    .badge {
        padding: 5px 10px;
        font-size: 10px;
    }

    .btn {
        padding: 10px 16px;
        font-size: 13px;
        margin: 4px;
    }
}

/* ==================== RESPONSIVE: TABLETS (480px - 768px) ==================== */
@media (max-width: 768px) {
    body {
        font-size: 14px;
    }

    .navbar {
        flex-direction: column;
        gap: 12px;
        padding: 15px;
    }

    .navbar h1 {
        font-size: 18px;
        width: 100%;
        text-align: center;
    }

    .user-info {
        flex-direction: column;
        gap: 8px;
        width: 100%;
        text-align: center;
    }

    .main-content {
        padding: 20px 15px;
    }

    .table-container {
        padding: 20px 15px;
        border-radius: 15px;
    }

    .table-container h3 {
        font-size: 20px;
        margin-bottom: 20px;
    }

    .profile-card {
        padding: 20px 15px;
        margin: 20px 0;
    }

    .profile-card h2 {
        font-size: 22px;
        margin-bottom: 15px;
    }

    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
        margin: 25px 0;
    }

    .stat-card {
        padding: 20px;
        border-radius: 15px;
    }

    .stat-card h3 {
        font-size: 11px;
        margin-bottom: 10px;
    }

    .stat-value {
        font-size: 32px;
    }

    .form-group {
        margin-bottom: 12px;
    }

    .form-group label {
        font-size: 13px;
        margin-bottom: 6px;
    }

    .form-group input,
    .form-group select {
        padding: 10px 12px;
        font-size: 13px;
    }

    .table-wrapper {
        border-radius: 10px;
        box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1);
    }

    table {
        font-size: 12px;
    }

    thead th {
        padding: 12px 8px;
        font-size: 11px;
    }

    tbody td {
        padding: 12px 8px;
        font-size: 12px;
    }

    .badge {
        padding: 4px 8px;
        font-size: 9px;
    }

    .btn {
        padding: 8px 12px;
        font-size: 12px;
        margin: 3px;
    }

    .alert {
        padding: 15px 20px;
        margin: 20px 0;
        font-size: 12px;
    }

    .profile-card p {
        font-size: 13px;
        margin-bottom: 8px;
    }

    /* Form Grid Override for Mobile */
    form[style*="grid-template-columns"] {
        display: flex !important;
        flex-direction: column !important;
        gap: 12px !important;
    }
}

/* ==================== RESPONSIVE: MOBILE (320px - 480px) ==================== */
@media (max-width: 480px) {
    .navbar {
        padding: 12px 10px;
        gap: 10px;
    }

    .navbar h1 {
        font-size: 16px;
    }

    .user-info {
        gap: 6px;
        font-size: 12px;
    }

    .main-content {
        padding: 12px 10px;
    }

    .table-container {
        padding: 15px 10px;
        margin: 15px 0;
    }

    .table-container h3 {
        font-size: 16px;
        margin-bottom: 15px;
        flex-direction: column;
        gap: 5px;
    }

    .profile-card {
        padding: 15px 10px;
        margin: 15px 0;
    }

    .profile-card h2 {
        font-size: 18px;
        margin-bottom: 12px;
    }

    .profile-card p {
        font-size: 12px;
        margin-bottom: 6px;
    }

    .stats-grid {
        grid-template-columns: 1fr;
        gap: 12px;
        margin: 20px 0;
    }

    .stat-card {
        padding: 15px;
        margin-bottom: 5px;
    }

    .stat-card h3 {
        font-size: 10px;
        margin-bottom: 8px;
    }

    .stat-value {
        font-size: 28px;
    }

    .form-group {
        margin-bottom: 10px;
    }

    .btn {
        width: 100%;
        padding: 10px 8px;
        font-size: 12px;
        margin: 2px 0;
    }

    table {
        font-size: 11px;
    }

    thead th {
        padding: 10px 6px;
        font-size: 10px;
    }

    tbody td {
        padding: 10px 6px;
        font-size: 11px;
    }

    tbody tr:hover {
        transform: none;
    }

    .badge {
        padding: 3px 6px;
        font-size: 8px;
    }

    .alert {
        padding: 12px 10px;
        margin: 15px 0;
        font-size: 11px;
    }
}

/* ==================== RESPONSIVE: EXTRA SMALL MOBILE (Below 320px) ==================== */
@media (max-width: 320px) {
    .navbar h1 {
        font-size: 14px;
    }

    .user-info {
        font-size: 11px;
    }

    .main-content {
        padding: 8px 5px;
    }

    .table-container {
        padding: 10px 5px;
    }

    .stats-grid {
        grid-template-columns: 1fr;
        gap: 8px;
    }

    .stat-value {
        font-size: 24px;
    }

    .btn {
        padding: 8px 6px;
        font-size: 11px;
    }

    table {
        font-size: 10px;
    }

    thead th {
        padding: 8px 4px;
        font-size: 9px;
    }

    tbody td {
        padding: 8px 4px;
        font-size: 10px;
    }
}

/* ==================== LANDSCAPE ORIENTATION ==================== */
@media (max-width: 768px) and (orientation: landscape) {
    .navbar {
        padding: 10px 15px;
    }

    .navbar h1 {
        font-size: 16px;
    }

    .main-content {
        padding: 15px;
    }

    .stats-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
    }

    .stat-card {
        padding: 15px;
    }

    .stat-value {
        font-size: 24px;
    }
}

/* ==================== HIGH DPI DISPLAYS ==================== */
@media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
    .navbar,
    .table-container,
    .profile-card,
    .stat-card {
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }
}

/* ==================== REDUCED MOTION ==================== */
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}

/* ==================== DARK MODE SUPPORT (Optional) ==================== */
@media (prefers-color-scheme: dark) {
    /* Add dark mode styles if needed in future */
}
    </style>
</head>
<body>
    <nav class="navbar">
        <div>
            <h1>🎓 NIT AMMS - My Attendance Report</h1>
        </div>
        <div class="user-info">
            <a href="index.php" class="btn btn-secondary">← Back</a>
            <span>👨‍🎓 <?php echo htmlspecialchars($student['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <div class="profile-card">
            <h2>📊 Attendance Report</h2>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($student['full_name']); ?></p>
            <p><strong>Roll Number:</strong> <?php echo htmlspecialchars($student['roll_number']); ?></p>
            <p><strong>Class/Section:</strong> <?php echo $display_section; ?></p>
            <p><strong>Department:</strong> <?php echo htmlspecialchars($student['dept_name'] ?? 'N/A'); ?></p>
        </div>

        <div class="table-container" style="margin-bottom: 30px;">
            <h3>🔍 Filter Report</h3>
            <form method="GET" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 15px;">
                <div class="form-group">
                    <label>Select Month:</label>
                    <input type="month" name="month" value="<?php echo htmlspecialchars($filter_month); ?>">
                </div>
                
                <div class="form-group">
                    <label>Select Year:</label>
                    <select name="year">
                        <?php for($y = date('Y'); $y >= 2020; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php echo $filter_year == $y ? 'selected' : ''; ?>>
                                <?php echo $y; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="form-group" style="display: flex; align-items: flex-end;">
                    <button type="submit" class="btn btn-primary">View Report</button>
                </div>
            </form>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>📅 Total Days</h3>
                <div class="stat-value"><?php echo $total_days; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>✅ Present</h3>
                <div class="stat-value" style="color: #28a745;"><?php echo $present; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>❌ Absent</h3>
                <div class="stat-value" style="color: #dc3545;"><?php echo $absent; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>⏰ Late</h3>
                <div class="stat-value" style="color: #ffc107;"><?php echo $late; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>📈 Attendance %</h3>
                <div class="stat-value" style="color: <?php echo $percentage >= 75 ? '#28a745' : '#dc3545'; ?>">
                    <?php echo $percentage; ?>%
                </div>
            </div>
        </div>

        <div class="table-container" style="margin-top: 30px;">
            <h3>📝 Detailed Attendance for <?php echo date('F Y', strtotime($filter_month.'-01')); ?></h3>
            
            <?php if ($attendance_records->num_rows > 0): ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Day</th>
                                <th>Class</th>
                                <th>Status</th>
                                <th>Remarks</th>
                                <th>👨‍🏫 Marked By</th>
                                <th>Marked At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($record = $attendance_records->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('d M Y', strtotime($record['attendance_date'])); ?></td>
                                <td>
                                    <span style="font-weight: 600; color: #667eea;">
                                        <?php echo date('l', strtotime($record['attendance_date'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    // Check if your table has subject_id, class_id, or period columns
                                    if (isset($record['class_id'])):
                                        echo htmlspecialchars($record['class_id'] ?? '-');
                                    else:
                                        echo '-';
                                    endif;
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $status_class = 'badge-info';
                                    if ($record['status'] === 'present') $status_class = 'badge-success';
                                    elseif ($record['status'] === 'absent') $status_class = 'badge-danger';
                                    elseif ($record['status'] === 'late') $status_class = 'badge-warning';
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>">
                                        <?php echo strtoupper($record['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($record['remarks'] ?? '-'); ?></td>
                                <td>
                                    <?php if (!empty($record['teacher_name'])): ?>
                                        <span style="color: #667eea; font-weight: 600;">
                                            👨‍🏫 <?php echo htmlspecialchars($record['teacher_name']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #999;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    if (isset($record['marked_at']) && $record['marked_at']): 
                                        echo date('h:i A', strtotime($record['marked_at']));
                                    else: 
                                        echo '-';
                                    endif; 
                                    ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    No attendance records found for <?php echo date('F Y', strtotime($filter_month.'-01')); ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="table-container" style="margin-top: 30px;">
            <h3>📊 Yearly Comparison - <?php echo $filter_year; ?></h3>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Total Days</th>
                            <th>Present</th>
                            <th>Attendance %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($yearly_data->num_rows > 0):
                            while ($year_data = $yearly_data->fetch_assoc()): 
                                $year_total = $year_data['total'] ?? 0;
                                $year_present = $year_data['present'] ?? 0;
                                $year_percentage = $year_total > 0 ? round(($year_present / $year_total) * 100, 2) : 0;
                        ?>
                        <tr>
                            <td><?php echo date('F Y', strtotime($year_data['month'].'-01')); ?></td>
                            <td><?php echo $year_total; ?></td>
                            <td><span class="badge badge-success"><?php echo $year_present; ?></span></td>
                            <td>
                                <strong style="color: <?php echo $year_percentage >= 75 ? '#28a745' : '#dc3545'; ?>">
                                    <?php echo $year_percentage; ?>%
                                </strong>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">No data available for <?php echo $filter_year; ?></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div style="background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 50%, #2a3254 100%); position: relative; overflow: hidden;">
        
        <!-- Animated Top Border -->
        <div style="height: 2px; background: linear-gradient(90deg, #4a9eff, #00d4ff, #4a9eff, #00d4ff); background-size: 200% 100%;"></div>
        
        <!-- Main Footer Container -->
        <div style="max-width: 1000px; margin: 0 auto; padding: 30px 20px 20px;">
            
            <!-- Developer Section -->
            <div style="background: rgba(255, 255, 255, 0.03); padding: 20px; border-radius: 15px; border: 1px solid rgba(74, 158, 255, 0.15); text-align: center; box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);">
                
                <p style="color: #ffffff; font-size: 14px; margin: 0 0 12px; font-weight: 500; letter-spacing: 0.5px;">✨ Designed & Developed by</p>
                
                <a href="https://himanshufullstackdeveloper.github.io/techyugsoftware/" style="display: inline-block; color: #ffffff; font-size: 16px; font-weight: 700; text-decoration: none; padding: 8px 24px; border: 2px solid #4a9eff; border-radius: 30px; background: linear-gradient(135deg, rgba(74, 158, 255, 0.2), rgba(0, 212, 255, 0.2)); box-shadow: 0 3px 12px rgba(74, 158, 255, 0.3); margin-bottom: 15px;">
                    🚀 Techyug Software Pvt. Ltd.
                </a>
                
                <div style="width: 50%; height: 1px; background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent); margin: 15px auto;"></div>
                
                <p style="color: #888; font-size: 10px; margin: 0 0 12px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600;">💼 Development Team</p>
                
                <div style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; margin-top: 12px;">
                    <a href="https://himanshufullstackdeveloper.github.io/portfoilohimanshu/" style="color: #ffffff; font-size: 13px; text-decoration: none; padding: 8px 16px; background: linear-gradient(135deg, rgba(74, 158, 255, 0.25), rgba(0, 212, 255, 0.25)); border-radius: 20px; border: 1px solid rgba(74, 158, 255, 0.4); display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 3px 10px rgba(74, 158, 255, 0.2);">
                        <span style="font-size: 16px;">👨‍💻</span>
                        <span style="font-weight: 600;">Himanshu Patil</span>
                    </a>
                    
                    <a href="https://devpranaypanore.github.io/Pranaypanore-live-.html/" style="color: #ffffff; font-size: 13px; text-decoration: none; padding: 8px 16px; background: linear-gradient(135deg, rgba(74, 158, 255, 0.25), rgba(0, 212, 255, 0.25)); border-radius: 20px; border: 1px solid rgba(74, 158, 255, 0.4); display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 3px 10px rgba(74, 158, 255, 0.2);">
                        <span style="font-size: 16px;">👨‍💻</span>
                        <span style="font-weight: 600;">Pranay Panore</span>
                    </a>
                </div>
                
                <div style="margin-top: 15px; display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;">
                    <span style="color: #4a9eff; font-size: 10px; padding: 4px 12px; background: rgba(74, 158, 255, 0.1); border-radius: 12px; border: 1px solid rgba(74, 158, 255, 0.3);">Full Stack</span>
                    <span style="color: #00d4ff; font-size: 10px; padding: 4px 12px; background: rgba(0, 212, 255, 0.1); border-radius: 12px; border: 1px solid rgba(0, 212, 255, 0.3);">UI/UX</span>
                    <span style="color: #4a9eff; font-size: 10px; padding: 4px 12px; background: rgba(74, 158, 255, 0.1); border-radius: 12px; border: 1px solid rgba(74, 158, 255, 0.3);">Database</span>
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
</body>
</html>