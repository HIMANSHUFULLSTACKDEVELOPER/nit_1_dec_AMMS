<?php
require_once '../db.php';
checkRole(['student']);

$student_id = $_SESSION['user_id'];

// Get student info with class section details
$student_query = "SELECT s.*, d.dept_name, c.class_name, c.section, c.year as class_year, c.semester as class_semester
                  FROM students s
                  LEFT JOIN departments d ON s.department_id = d.id
                  LEFT JOIN classes c ON s.class_id = c.id
                  WHERE s.id = $student_id";
$student = $conn->query($student_query)->fetch_assoc();

// Get attendance statistics
$stats_query = "SELECT 
                COUNT(*) as total_days,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late
                FROM student_attendance
                WHERE student_id = $student_id";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

$total_days = $stats['total_days'];
$attendance_percentage = $total_days > 0 ? round(($stats['present'] / $total_days) * 100, 2) : 0;

// Display class section
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Student</title>
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

        .main-content {
            padding: 40px;
            max-width: 1600px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
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

        .alert-danger {
            background: rgba(248, 215, 218, 0.95);
            border-color: #dc3545;
            color: #721c24;
        }

        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px;
            border-radius: 25px;
            color: white;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
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

        .profile-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
        }

        .profile-header::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: shimmer 3s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        .profile-photo-container {
            position: relative;
            display: inline-block;
            margin-bottom: 20px;
            z-index: 1;
            animation: zoomIn 0.6s ease-out 0.3s both;
        }

        @keyframes zoomIn {
            from {
                opacity: 0;
                transform: scale(0.3);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        .profile-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: 0 8px 30px rgba(0,0,0,0.3);
            transition: transform 0.3s;
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .profile-photo:hover {
            transform: scale(1.1);
            animation-play-state: paused;
        }
        
        .profile-photo-placeholder {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 80px;
            border: 5px solid white;
            box-shadow: 0 8px 30px rgba(0,0,0,0.3);
            animation: bounce 2s ease-in-out infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .upload-photo-btn {
            position: absolute;
            bottom: 0;
            right: 0;
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            font-size: 20px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
            transition: all 0.3s;
            animation: pulse-btn 2s ease-in-out infinite;
        }

        @keyframes pulse-btn {
            0%, 100% { 
                transform: scale(1);
                box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
            }
            50% { 
                transform: scale(1.05);
                box-shadow: 0 6px 20px rgba(40, 167, 69, 0.6);
            }
        }
        
        .upload-photo-btn:hover {
            transform: scale(1.15) rotate(15deg);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.6);
            animation-play-state: paused;
        }
        
        .profile-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .info-card {
            background: rgba(248, 249, 250, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 15px;
            border-left: 4px solid #667eea;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            animation: fadeInUp 0.6s ease-out backwards;
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

        .info-card:nth-child(1) { animation-delay: 0.1s; }
        .info-card:nth-child(2) { animation-delay: 0.2s; }
        .info-card:nth-child(3) { animation-delay: 0.3s; }
        .info-card:nth-child(4) { animation-delay: 0.4s; }
        .info-card:nth-child(5) { animation-delay: 0.5s; }
        .info-card:nth-child(6) { animation-delay: 0.6s; }
        .info-card:nth-child(7) { animation-delay: 0.7s; }
        .info-card:nth-child(8) { animation-delay: 0.8s; }
        .info-card:nth-child(9) { animation-delay: 0.9s; }
        .info-card:nth-child(10) { animation-delay: 1s; }

        .info-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            border-left-color: #764ba2;
        }
        
        .info-card label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
            display: block;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }
        
        .info-card value {
            font-size: 18px;
            color: #333;
            font-weight: 500;
        }
        
        .stats-mini {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 20px;
            z-index: 1;
            position: relative;
        }
        
        .stat-mini {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 15px;
            border-radius: 15px;
            text-align: center;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            animation: scaleIn 0.5s ease-out backwards;
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.5);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .stat-mini:nth-child(1) { animation-delay: 0.6s; }
        .stat-mini:nth-child(2) { animation-delay: 0.7s; }
        .stat-mini:nth-child(3) { animation-delay: 0.8s; }

        .stat-mini:hover {
            transform: translateY(-8px) scale(1.05);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
        }
        
        .stat-mini-value {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
            animation: countUp 1s ease-out;
        }

        @keyframes countUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .stat-mini-label {
            font-size: 12px;
            color: #666;
            font-weight: 600;
            text-transform: uppercase;
        }

        .content-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 30px;
            border-radius: 25px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
            margin-top: 20px;
            border: 2px solid rgba(255, 255, 255, 0.5);
            animation: slideInLeft 0.8s ease-out;
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .content-card h3 {
            margin-bottom: 25px;
            color: #667eea;
            font-size: 24px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: gradientText 3s ease infinite;
            background-size: 200% auto;
        }

        @keyframes gradientText {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .stat-card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .stat-card-item {
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
            transition: all 0.3s;
            animation: flipIn 0.6s ease-out backwards;
        }

        @keyframes flipIn {
            from {
                opacity: 0;
                transform: rotateY(90deg);
            }
            to {
                opacity: 1;
                transform: rotateY(0);
            }
        }

        .stat-card-item:nth-child(1) { animation-delay: 0.2s; }
        .stat-card-item:nth-child(2) { animation-delay: 0.4s; }
        .stat-card-item:nth-child(3) { animation-delay: 0.6s; }
        .stat-card-item:nth-child(4) { animation-delay: 0.8s; }

        .stat-card-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(100%);
            transition: transform 0.3s;
        }

        .stat-card-item:hover::before {
            transform: translateY(0);
        }

        .stat-card-item:hover {
            transform: translateY(-8px) scale(1.03);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        }

        .stat-card-value {
            font-size: 36px;
            font-weight: bold;
            position: relative;
            z-index: 1;
            animation: numberPop 0.8s ease-out;
        }

        @keyframes numberPop {
            0% {
                transform: scale(0);
                opacity: 0;
            }
            50% {
                transform: scale(1.2);
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .stat-card-label {
            font-size: 14px;
            margin-top: 5px;
            position: relative;
            z-index: 1;
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

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
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
            position: relative;
            overflow: hidden;
        }

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

        .btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            animation: btnFloat 3s ease-in-out infinite;
        }

        @keyframes btnFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-3px); }
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
            animation-play-state: paused;
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

        .button-group {
            text-align: center;
            margin-top: 30px;
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
            border-color: #00d4ff;
        }

        .dev-badge {
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
            margin: 0 6px;
        }

        .dev-badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(74, 158, 255, 0.4);
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
                padding: 15px 20px;
            }

            .user-info {
                flex-direction: column;
                gap: 10px;
            }

            .main-content {
                padding: 20px;
            }

            .stats-mini {
                grid-template-columns: 1fr;
            }

            .profile-info-grid {
                grid-template-columns: 1fr;
            }

            .stat-card-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Background Particles -->
    <div class="particles">
        <div class="particle" style="width: 10px; height: 10px; left: 10%; animation-delay: 0s;"></div>
        <div class="particle" style="width: 15px; height: 15px; left: 20%; animation-delay: 2s;"></div>
        <div class="particle" style="width: 8px; height: 8px; left: 30%; animation-delay: 4s;"></div>
        <div class="particle" style="width: 12px; height: 12px; left: 40%; animation-delay: 1s;"></div>
        <div class="particle" style="width: 10px; height: 10px; left: 50%; animation-delay: 3s;"></div>
        <div class="particle" style="width: 15px; height: 15px; left: 60%; animation-delay: 5s;"></div>
        <div class="particle" style="width: 8px; height: 8px; left: 70%; animation-delay: 2.5s;"></div>
        <div class="particle" style="width: 12px; height: 12px; left: 80%; animation-delay: 4.5s;"></div>
        <div class="particle" style="width: 10px; height: 10px; left: 90%; animation-delay: 1.5s;"></div>
    </div>

    <nav class="navbar">
        <div>
            <h1>🎓 NIT AMMS - My Profile</h1>
        </div>
        <div class="user-info">
            <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
            <span>👨‍🎓 <?php echo htmlspecialchars($student['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">✅ Profile photo updated successfully!</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">❌ Error: <?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <div class="profile-header">
            <div class="profile-photo-container">
                <?php if (!empty($student['photo']) && file_exists("../uploads/students/" . $student['photo'])): ?>
                    <img src="../uploads/students/<?php echo htmlspecialchars($student['photo']); ?>" 
                         alt="Profile Photo" 
                         class="profile-photo">
                <?php else: ?>
                    <div class="profile-photo-placeholder">👨‍🎓</div>
                <?php endif; ?>
                
                <form id="photoForm" method="POST" action="../upload_photo.php" enctype="multipart/form-data" style="display: inline;">
                    <input type="hidden" name="user_type" value="student">
                    <input type="hidden" name="user_id" value="<?php echo $student_id; ?>">
                    <input type="file" 
                           name="photo" 
                           id="photoInput" 
                           accept="image/*" 
                           style="display: none;"
                           onchange="document.getElementById('photoForm').submit();">
                    <button type="button" 
                            class="upload-photo-btn" 
                            onclick="document.getElementById('photoInput').click();"
                            title="Upload Photo">
                        .
                    </button>
                </form>
            </div>
            
            <h2 style="margin: 15px 0 5px 0; position: relative; z-index: 1;"><?php echo htmlspecialchars($student['full_name']); ?></h2>
            <p style="font-size: 18px; opacity: 0.9; position: relative; z-index: 1;">Roll No: <?php echo htmlspecialchars($student['roll_number']); ?></p>
            <p style="font-size: 16px; opacity: 0.8; position: relative; z-index: 1;"><?php echo $display_section; ?></p>
            
            <div class="stats-mini">
                <div class="stat-mini">
                    <div class="stat-mini-value" style="color: #28a745;"><?php echo $stats['present']; ?></div>
                    <div class="stat-mini-label">Present</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-value" style="color: #dc3545;"><?php echo $stats['absent']; ?></div>
                    <div class="stat-mini-label">Absent</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-value" style="color: <?php echo $attendance_percentage >= 75 ? '#28a745' : '#dc3545'; ?>">
                        <?php echo $attendance_percentage; ?>%
                    </div>
                    <div class="stat-mini-label">Attendance</div>
                </div>
            </div>
        </div>

        <div class="content-card">
            <h3>📋 Personal Information</h3>
            
            <div class="profile-info-grid">
                <div class="info-card">
                    <label>Full Name</label>
                    <value><?php echo htmlspecialchars($student['full_name']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Roll Number</label>
                    <value><?php echo htmlspecialchars($student['roll_number']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Email Address</label>
                    <value><?php echo htmlspecialchars($student['email']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Phone Number</label>
                    <value><?php echo htmlspecialchars($student['phone']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Department</label>
                    <value><?php echo htmlspecialchars($student['dept_name']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Class/Section</label>
                    <value><?php echo $display_section; ?></value>
                </div>
                
                <div class="info-card">
                    <label>Academic Year</label>
                    <value><?php echo htmlspecialchars($student['year']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Semester</label>
                    <value><?php echo htmlspecialchars($student['semester']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Admission Year</label>
                    <value><?php echo htmlspecialchars($student['admission_year']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Account Status</label>
                    <value>
                        <?php if ($student['is_active']): ?>
                            <span class="badge badge-success">✅ Active</span>
                        <?php else: ?>
                            <span class="badge badge-danger">❌ Inactive</span>
                        <?php endif; ?>
                    </value>
                </div>
            </div>
        </div>

        <div class="content-card">
            <h3>📊 Quick Statistics</h3>
            
            <div class="stat-card-grid">
                <div class="stat-card-item" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <div class="stat-card-value"><?php echo $total_days; ?></div>
                    <div class="stat-card-label">Total Classes</div>
                </div>
                
                <div class="stat-card-item" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                    <div class="stat-card-value"><?php echo $stats['present']; ?></div>
                    <div class="stat-card-label">Days Present</div>
                </div>
                
                <div class="stat-card-item" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);">
                    <div class="stat-card-value"><?php echo $stats['absent']; ?></div>
                    <div class="stat-card-label">Days Absent</div>
                </div>
                
                <div class="stat-card-item" style="background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);">
                    <div class="stat-card-value"><?php echo $stats['late']; ?></div>
                    <div class="stat-card-label">Days Late</div>
                </div>
            </div>
        </div>

        <div class="button-group">
            <a href="index.php" class="btn btn-primary">🏠 Back to Dashboard</a>
            <a href="attendance_report.php" class="btn btn-success">📊 View Detailed Report</a>
        </div>
    </div>

    <!-- Compact Footer -->
    <div class="footer">
        <div class="footer-border"></div>
        
        <div class="footer-content">
            <div class="developer-section">
                <p style="color: #ffffff; font-size: 14px; margin: 0 0 12px; font-weight: 500; letter-spacing: 0.5px;">✨ Designed & Developed by</p>
                
                <a href="https://himanshufullstackdeveloper.github.io/techyugsoftware/" class="company-link">
                    🚀 Techyug Software Pvt. Ltd.
                </a>
                
                <div style="width: 50%; height: 1px; background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent); margin: 15px auto;"></div>
                
                <p style="color: #888; font-size: 10px; margin: 0 0 12px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600;">💼 Development Team</p>
                
                <div style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; margin-top: 12px;">
                    <a href="https://himanshufullstackdeveloper.github.io/portfoilohimanshu/" class="dev-badge">
                        <span style="font-size: 16px;">👨‍💻</span>
                        <span style="font-weight: 600;">Himanshu Patil</span>
                    </a>
                    
                    <a href="https://devpranaypanore.github.io/Pranaypanore-live-.html/" class="dev-badge">
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
                
                <div style="margin-top: 15px; display: flex; justify-content: center; gap: 10px;">
                    <a href="#" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; background: rgba(74, 158, 255, 0.1); border: 1px solid rgba(74, 158, 255, 0.3); border-radius: 50%; color: #4a9eff; text-decoration: none; font-size: 14px; transition: all 0.3s;">📧</a>
                    <a href="#" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; background: rgba(74, 158, 255, 0.1); border: 1px solid rgba(74, 158, 255, 0.3); border-radius: 50%; color: #4a9eff; text-decoration: none; font-size: 14px; transition: all 0.3s;">🌐</a>
                    <a href="#" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; background: rgba(74, 158, 255, 0.1); border: 1px solid rgba(74, 158, 255, 0.3); border-radius: 50%; color: #4a9eff; text-decoration: none; font-size: 14px; transition: all 0.3s;">💼</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>