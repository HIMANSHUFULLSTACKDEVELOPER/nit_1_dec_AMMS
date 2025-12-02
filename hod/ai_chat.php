<?php
require_once '../db.php';
checkRole(['hod']);

$user = getCurrentUser();
$department_id = $_SESSION['department_id'];

// Get department info
$dept_query = "SELECT * FROM departments WHERE id = $department_id";
$dept_result = $conn->query($dept_query);
$department = $dept_result->fetch_assoc();

// Get statistics
$stats = [];

// Total teachers in department
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'teacher' AND department_id = $department_id AND is_active = 1");
$stats['teachers'] = $result->fetch_assoc()['count'];

// Total students in department
$result = $conn->query("SELECT COUNT(*) as count FROM students WHERE department_id = $department_id AND is_active = 1");
$stats['students'] = $result->fetch_assoc()['count'];

// Total classes in department
$result = $conn->query("SELECT COUNT(*) as count FROM classes WHERE department_id = $department_id");
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../Nit_logo.png" type="image/svg+xml" />
    <title>HOD Dashboard - NIT AMMS</title>
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

        /* Enhanced Navbar with Glass Effect */
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

        .hero-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            margin-top: 30px;
            animation: fadeInUp 1s ease-out 0.5s both;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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

        /* Animated Clock with Glass Effect */
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

        /* Modern Tables */
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

        /* AI Chat Button - Floating */
        .ai-chat-float {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 999;
            animation: floatBounce 3s ease-in-out infinite;
        }

        @keyframes floatBounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }

        .ai-chat-btn {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            font-size: 32px;
            cursor: pointer;
            box-shadow: 0 10px 40px rgba(102, 126, 234, 0.6);
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .ai-chat-btn:hover {
            transform: scale(1.15) rotate(10deg);
            box-shadow: 0 15px 50px rgba(102, 126, 234, 0.8);
        }

        .ai-chat-btn::before {
            content: '';
            position: absolute;
            top: -5px;
            right: -5px;
            width: 20px;
            height: 20px;
            background: #28a745;
            border-radius: 50%;
            border: 3px solid white;
            animation: pulse-ring 2s infinite;
        }

        @keyframes pulse-ring {
            0% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.2);
                opacity: 0.7;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
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
            .ai-chat-float { bottom: 20px; right: 20px; }
            .ai-chat-btn { width: 60px; height: 60px; font-size: 28px; }
        }

        @media (max-width: 480px) {
            .stat-value-large { font-size: 32px; }
            .developer-badges { flex-direction: column; }
            .hero-text h2 { font-size: 24px; }
            .hero-stats { grid-template-columns: 1fr; }
            .hero-stat-value { font-size: 28px; }
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
                    <p style="margin-top: 10px;"><strong>Department Code:</strong> <?php echo htmlspecialchars($department['dept_code']); ?></p>
                    <p style="margin-top: 10px; font-size: 14px;">💡 <em>Tip: Click "👤 My Profile" to view and upload your profile photo!</em></p>
                    <div class="hero-stats">
                        <div class="hero-stat-item">
                            <div class="hero-stat-value"><?php echo $stats['teachers']; ?></div>
                            <div class="hero-stat-label">Total Teachers</div>
                        </div>
                        <div class="hero-stat-item">
                            <div class="hero-stat-value"><?php echo $stats['students']; ?></div>
                            <div class="hero-stat-label">Total Students</div>
                        </div>
                        <div class="hero-stat-item">
                            <div class="hero-stat-value"><?php echo $stats['classes']; ?></div>
                            <div class="hero-stat-label">Total Classes</div>
                        </div>
                        <div class="hero-stat-item">
                            <div class="hero-stat-value"><?php echo ($today_stats['present'] ?? 0); ?></div>
                            <div class="hero-stat-label">Present Today</div>
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
                    <h4>Department Code</h4>
                    <div class="stat-value-large"><?php echo htmlspecialchars($department['dept_code']); ?></div>
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
                    <a href="attendance_reports.php" class="btn btn-success btn-sm">📊 View Report</a>
                </div>
            </div>

            <div class="premium-stat-card">
                <div class="stat-icon-wrapper">📝</div>
                <div class="stat-details">
                    <h4>Today's Attendance</h4>
                    <div class="stat-value-large">
                        ✅ <?php echo $today_stats['present'] ?? 0; ?> | 
                        ❌ <?php echo $today_stats['absent'] ?? 0; ?>
                    </div>
                    <a href="view_department_attendance.php" class="btn btn-success btn-sm">📊 View Details</a> 
                </div>
            </div>

            <!-- AI Chat Card -->
            <div class="premium-stat-card">
                <div class="stat-icon-wrapper">🤖</div>
                <div class="stat-details">
                    <h4>AI Assistant</h4>
                    <div class="stat-value-large">Chat</div>
                    <a href="ai_chat.php" class="btn btn-primary btn-sm">🚀 Open AI Chat</a>
                </div>
            </div>
        </div>

        <!-- Department Classes Table -->
        <div class="table-container">
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
                    $classes->data_seek(0);
                    while ($class = $classes->fetch_assoc()): 
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($class['class_name']); ?></td>
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
                    <?php endwhile; ?>
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

    <!-- Floating AI Chat Button -->
    <div class="ai-chat-float">
        <a href="ai_chat.php">
            <button class="ai-chat-btn" title="Open AI Assistant">
                🤖
            </button>
        </a>
    </div>

    <!-- Compact Footer -->
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
                
                <div class="social-links">
                    <a href="#" class="social-link">📧</a>
                    <a href="#" class="social-link">🌐</a>
                    <a href="#" class="social-link">💼</a>
                </div>
            </div>
        </div>
    </div>

    <script>
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
            hours = hours ? hours : 12;
            hours = String(hours).padStart(2, '0');
            
            document.getElementById('liveClock').textContent = `${hours}:${minutes}:${seconds} ${ampm}`;
            
            // Date
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const dateString = now.toLocaleDateString('en-US', options);
            document.getElementById('liveDate').textContent = dateString;
        }
        
        // Update clock immediately and then every second
        updateClock();
        setInterval(updateClock, 1000);
        
        // Create more animated particles dynamically
        const particlesContainer = document.querySelector('.particles');
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
    </script>
</body>
</html>