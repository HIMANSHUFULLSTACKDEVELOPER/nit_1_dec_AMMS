<?php
require_once '../db.php';
checkRole(['teacher']);

$user = getCurrentUser();
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
$section = isset($_GET['section']) ? $_GET['section'] : '';

// Verify teacher has access to this class
$verify_query = "SELECT c.*, d.dept_name FROM classes c 
                 JOIN departments d ON c.department_id = d.id
                 WHERE c.id = ? AND c.teacher_id = ?";
$stmt = $conn->prepare($verify_query);
$stmt->bind_param("ii", $class_id, $user['id']);
$stmt->execute();
$class = $stmt->get_result()->fetch_assoc();

if (!$class) {
    header("Location: index.php");
    exit();
}

// Get the section from the class
$section = $class['section'];

// Get filter parameters
$filter_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$filter_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

// FIXED: Get attendance records for ALL STUDENTS in this SECTION
$attendance_query = "SELECT sa.*, s.roll_number, s.full_name as student_name
                     FROM student_attendance sa
                     JOIN students s ON sa.student_id = s.id
                     JOIN classes c ON s.class_id = c.id
                     WHERE c.section = ? 
                     AND sa.attendance_date BETWEEN ? AND ?
                     AND sa.marked_by = ?
                     ORDER BY sa.attendance_date DESC, s.roll_number";

$stmt = $conn->prepare($attendance_query);
$stmt->bind_param("sssi", $section, $filter_date_from, $filter_date_to, $user['id']);
$stmt->execute();
$attendance_records = $stmt->get_result();

// FIXED: Get statistics for the SECTION
$stats_query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN sa.status = 'absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN sa.status = 'late' THEN 1 ELSE 0 END) as late
                FROM student_attendance sa
                JOIN students s ON sa.student_id = s.id
                JOIN classes c ON s.class_id = c.id
                WHERE c.section = ? 
                AND sa.attendance_date BETWEEN ? AND ?
                AND sa.marked_by = ?";

$stmt = $conn->prepare($stats_query);
$stmt->bind_param("sssi", $section, $filter_date_from, $filter_date_to, $user['id']);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// FIXED: Get student-wise attendance summary for ALL students in SECTION
$summary_query = "SELECT s.roll_number, s.full_name,
                  COUNT(sa.id) as total_days,
                  SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) as present_days,
                  SUM(CASE WHEN sa.status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                  SUM(CASE WHEN sa.status = 'late' THEN 1 ELSE 0 END) as late_days
                  FROM students s
                  JOIN classes c ON s.class_id = c.id
                  LEFT JOIN student_attendance sa ON s.id = sa.student_id
                  AND sa.attendance_date BETWEEN ? AND ?
                  AND sa.marked_by = ?
                  WHERE c.section = ?
                  AND s.is_active = 1
                  GROUP BY s.id, s.roll_number, s.full_name
                  ORDER BY s.roll_number";

$stmt = $conn->prepare($summary_query);
$stmt->bind_param("ssis", $filter_date_from, $filter_date_to, $user['id'], $section);
$stmt->execute();
$student_summary = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Reports - <?php echo htmlspecialchars($class['section']); ?></title>
    <link rel="icon" href="../Nit_logo.png" type="image/svg+xml" />
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

        /* Summary Card / Hero Section */
        .summary-card {
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

        .summary-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            opacity: 0.1;
            z-index: 0;
        }

        .summary-card h2 {
            font-size: 36px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 25px;
            font-weight: 800;
            position: relative;
            z-index: 1;
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

        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            position: relative;
            z-index: 1;
        }

        .summary-stat {
            text-align: center;
            padding: 20px;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 15px;
            border: 1px solid rgba(102, 126, 234, 0.2);
            transition: all 0.3s ease;
        }

        .summary-stat:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
        }

        .summary-stat .label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .summary-stat .number {
            font-size: 24px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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
            text-align: center;
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
            font-size: 14px;
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
            margin-bottom: 25px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
        }

        .form-group input[type="date"],
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid rgba(102, 126, 234, 0.3);
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: white;
        }

        .form-group input[type="date"]:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        /* Enhanced Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table thead {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        table thead th {
            padding: 18px 15px;
            text-align: left;
            color: white;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        table thead th:first-child {
            border-radius: 12px 0 0 0;
        }

        table thead th:last-child {
            border-radius: 0 12px 0 0;
        }

        table tbody tr {
            transition: all 0.3s ease;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        table tbody tr:hover {
            background: rgba(102, 126, 234, 0.08);
            transform: scale(1.01);
        }

        table tbody td {
            padding: 16px 15px;
            color: #2c3e50;
            font-size: 14px;
        }

        /* Badges */
        .badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .badge-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border: 1px solid #28a745;
        }

        .badge-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border: 1px solid #dc3545;
        }

        .badge-warning {
            background: linear-gradient(135deg, #fff3cd, #ffeeba);
            color: #856404;
            border: 1px solid #ffc107;
        }

        .badge-info {
            background: linear-gradient(135deg, #d1ecf1, #bee5eb);
            color: #0c5460;
            border: 1px solid #17a2b8;
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

        /* Download Section */
        .download-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 35px;
            border-radius: 25px;
            margin: 30px 0;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.5);
            text-align: center;
        }

        .download-section h3 {
            font-size: 22px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .download-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 20px;
        }

        .download-btn {
            padding: 14px 35px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 15px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            text-decoration: none;
            color: white;
        }

        .download-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
        }

        .btn-excel {
            background: linear-gradient(135deg, #1e7e34, #28a745);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
        }

        .btn-pdf {
            background: linear-gradient(135deg, #c82333, #dc3545);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4);
        }

        .btn-print {
            background: linear-gradient(135deg, #5a6268, #6c757d);
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.4);
        }

        /* Alert */
        .alert {
            padding: 20px 25px;
            border-radius: 15px;
            margin: 20px 0;
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

        .alert-info {
            background: rgba(209, 236, 241, 0.95);
            border-color: #17a2b8;
            color: #0c5460;
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
            font-size: 10px;
            padding: 4px 12px;
            background: rgba(74, 158, 255, 0.1);
            border-radius: 12px;
            border: 1px solid rgba(74, 158, 255, 0.3);
        }

        .role-tag:nth-child(1) { color: #4a9eff; }
        .role-tag:nth-child(2) { color: #00d4ff; border-color: rgba(0, 212, 255, 0.3); background: rgba(0, 212, 255, 0.1); }
        .role-tag:nth-child(3) { color: #4a9eff; }

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

        .social-links {
            margin-top: 15px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .social-link {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(74, 158, 255, 0.1);
            border: 1px solid rgba(74, 158, 255, 0.3);
            border-radius: 50%;
            color: #4a9eff;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }

        .social-link:hover {
            background: rgba(74, 158, 255, 0.3);
            transform: translateY(-3px);
        }

        /* Print Styles */
        @media print {
            .navbar, .download-section, .btn, .footer { display: none !important; }
            body { background: white; }
            .main-content { padding: 20px; }
            .summary-card, .table-container, .stat-card { 
                box-shadow: none; 
                border: 1px solid #ddd;
            }
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 768px) {
            .navbar { 
                padding: 15px 20px; 
                flex-direction: column; 
                gap: 15px; 
            }
            .navbar h1 { font-size: 18px; }
            .main-content { padding: 20px; }
            .summary-card { padding: 30px 20px; }
            .summary-card h2 { font-size: 24px; }
            .stats-grid { grid-template-columns: 1fr; gap: 15px; }
            .table-container { padding: 20px; overflow-x: auto; }
            .user-info { flex-direction: column; gap: 10px; }
            .download-buttons { flex-direction: column; }
            .download-btn { width: 100%; justify-content: center; }
        }

        @media (max-width: 480px) {
            .summary-stats { grid-template-columns: repeat(2, 1fr); }
            .stat-value { font-size: 36px; }
            .developer-badges { flex-direction: column; }
            table { font-size: 12px; }
            table thead th, table tbody td { padding: 10px 8px; }
        }
    </style>
</head>
<body>
    <!-- Animated Particles Background -->
    <div class="particles">
        <div class="particle" style="width: 20px; height: 20px; left: 10%; animation-delay: 0s;"></div>
        <div class="particle" style="width: 15px; height: 15px; left: 20%; animation-delay: 2s;"></div>
        <div class="particle" style="width: 25px; height: 25px; left: 35%; animation-delay: 4s;"></div>
        <div class="particle" style="width: 18px; height: 18px; left: 50%; animation-delay: 6s;"></div>
        <div class="particle" style="width: 22px; height: 22px; left: 65%; animation-delay: 8s;"></div>
        <div class="particle" style="width: 16px; height: 16px; left: 80%; animation-delay: 10s;"></div>
        <div class="particle" style="width: 20px; height: 20px; left: 90%; animation-delay: 12s;"></div>
    </div>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-brand">
            <div class="navbar-logo">🎓</div>
            <h1>NIT AMMS - <?php echo htmlspecialchars($class['section']); ?></h1>
        </div>
        <div class="user-info">
            <a href="index.php" class="btn btn-secondary">← Back</a>
            <div class="user-profile">
                <span>👨‍🏫 <?php echo htmlspecialchars($user['full_name']); ?></span>
            </div>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Summary Card -->
        <div class="summary-card">
            <h2>📚 <?php echo htmlspecialchars($class['class_name']); ?></h2>
            <div class="summary-stats">
                <div class="summary-stat">
                    <div class="label">Section</div>
                    <div class="number"><?php echo htmlspecialchars($class['section']); ?></div>
                </div>
                <div class="summary-stat">
                    <div class="label">Department</div>
                    <div class="number"><?php echo htmlspecialchars($class['dept_name']); ?></div>
                </div>
                <div class="summary-stat">
                    <div class="label">Year</div>
                    <div class="number"><?php echo $class['year']; ?></div>
                </div>
                <div class="summary-stat">
                    <div class="label">Semester</div>
                    <div class="number"><?php echo $class['semester']; ?></div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="table-container">
            <h3>🔍 Filter Attendance</h3>
            <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">
                <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                <input type="hidden" name="section" value="<?php echo htmlspecialchars($section); ?>">
                
                <div class="form-group">
                    <label>From Date:</label>
                    <input type="date" name="date_from" value="<?php echo $filter_date_from; ?>">
                </div>
                
                <div class="form-group">
                    <label>To Date:</label>
                    <input type="date" name="date_to" value="<?php echo $filter_date_to; ?>">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">🔍 Filter</button>
                </div>
            </form>
        </div>

        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>📊 Total Records</h3>
                <div class="stat-value"><?php echo $stats['total']; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>✅ Present</h3>
                <div class="stat-value" style="background: linear-gradient(135deg, #28a745, #20c997); -webkit-background-clip: text; background-clip: text;"><?php echo $stats['present']; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>❌ Absent</h3>
                <div class="stat-value" style="background: linear-gradient(135deg, #dc3545, #c82333); -webkit-background-clip: text; background-clip: text;"><?php echo $stats['absent']; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>⏰ Late</h3>
                <div class="stat-value" style="background: linear-gradient(135deg, #ffc107, #e0a800); -webkit-background-clip: text; background-clip: text;"><?php echo $stats['late']; ?></div>
            </div>
        </div>

        <!-- Student-wise Summary Table -->
        <div class="table-container">
            <h3>👥 Student-wise Attendance Summary</h3>
            <p style="margin-bottom: 20px; color: #666;">
                Showing attendance for students in section: <strong style="color: #667eea;"><?php echo htmlspecialchars($class['section']); ?></strong>
            </p>
            
            <?php if ($student_summary->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Roll Number</th>
                            <th>Student Name</th>
                            <th>Total Days</th>
                            <th>Present</th>
                            <th>Absent</th>
                            <th>Late</th>
                            <th>Attendance %</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $student_summary->data_seek(0);
                        while ($student = $student_summary->fetch_assoc()): 
                            $percentage = $student['total_days'] > 0 
                                ? round(($student['present_days'] / $student['total_days']) * 100, 2) 
                                : 0;
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($student['roll_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                            <td><?php echo $student['total_days']; ?></td>
                            <td><span class="badge badge-success"><?php echo $student['present_days']; ?></span></td>
                            <td><span class="badge badge-danger"><?php echo $student['absent_days']; ?></span></td>
                            <td><span class="badge badge-warning"><?php echo $student['late_days']; ?></span></td>
                            <td><strong style="color: #667eea;"><?php echo $percentage; ?>%</strong></td>
                            <td>
                                <?php if ($percentage >= 75): ?>
                                    <span class="badge badge-success">Good</span>
                                <?php elseif ($percentage >= 60): ?>
                                    <span class="badge badge-warning">Average</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Low ⚠️</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">
                    ℹ️ No students found in this section. Please contact the administrator.
                </div>
            <?php endif; ?>
        </div>

        <!-- Download Section -->
        <?php if ($attendance_records->num_rows > 0): ?>
        <div class="download-section">
            <h3>📥 Download Detailed Attendance Records</h3>
            <p style="color: #666; margin-bottom: 15px;">
                Export attendance data from <strong style="color: #667eea;"><?php echo date('d M Y', strtotime($filter_date_from)); ?></strong> 
                to <strong style="color: #667eea;"><?php echo date('d M Y', strtotime($filter_date_to)); ?></strong>
            </p>
            <div class="download-buttons">
                <a href="download_attendance.php?class_id=<?php echo $class_id; ?>&section=<?php echo urlencode($section); ?>&date_from=<?php echo urlencode($filter_date_from); ?>&date_to=<?php echo urlencode($filter_date_to); ?>&format=excel" 
                   class="download-btn btn-excel" download>
                    📊 Download Excel (CSV)
                </a>
                
                <a href="download_attendance.php?class_id=<?php echo $class_id; ?>&section=<?php echo urlencode($section); ?>&date_from=<?php echo urlencode($filter_date_from); ?>&date_to=<?php echo urlencode($filter_date_to); ?>&format=pdf" 
                   class="download-btn btn-pdf" target="_blank">
                    📄 Download PDF
                </a>
                
                <button onclick="window.print()" class="download-btn btn-print">
                    🖨️ Print Report
                </button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Detailed Attendance Records -->
        <div class="table-container">
            <h3>📋 Detailed Attendance Records</h3>
            
            <?php if ($attendance_records->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Roll Number</th>
                            <th>Student Name</th>
                            <th>Status</th>
                            <th>Remarks</th>
                            <th>Marked At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $attendance_records->data_seek(0);
                        while ($record = $attendance_records->fetch_assoc()): 
                        ?>
                        <tr>
                            <td><?php echo date('d M Y', strtotime($record['attendance_date'])); ?></td>
                            <td><strong><?php echo htmlspecialchars($record['roll_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($record['student_name']); ?></td>
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
                            <td><?php echo date('H:i', strtotime($record['marked_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">ℹ️ No attendance records found for the selected date range.</div>
            <?php endif; ?>

            <div style="margin-top: 20px;">
                <a href="update_attendance_summary.php" class="btn btn-primary">📊 Attendance Summary All</a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
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
                        <span>Himanshu Patil</span>
                    </a>
                    <a href="https://devpranaypanore.github.io/Pranaypanore-live-.html/" class="developer-badge">
                        <span>👨‍💻</span>
                        <span>Pranay Panore</span>
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
                <p>Made with <span style="color: #ff4757;">❤️</span> by Techyug Software</p>
                <div class="social-links">
                    <a href="#" class="social-link">📧</a>
                    <a href="#" class="social-link">🌐</a>
                    <a href="#" class="social-link">💼</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>