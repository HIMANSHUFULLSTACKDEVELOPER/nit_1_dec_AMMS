<?php
require_once '../db.php';
checkRole(['student']);

$student_id = $_SESSION['user_id'];

// Get student info
$student_query = "SELECT s.*, d.dept_name, c.class_name 
                  FROM students s
                  LEFT JOIN departments d ON s.department_id = d.id
                  LEFT JOIN classes c ON s.class_id = c.id
                  WHERE s.id = $student_id";
$student = $conn->query($student_query)->fetch_assoc();

// Get today's attendance
$today = date('Y-m-d');
$today_query = "SELECT * FROM student_attendance 
                WHERE student_id = $student_id AND attendance_date = '$today'";
$today_attendance = $conn->query($today_query)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Today's Attendance - Student</title>
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

        .user-info span {
            background: rgba(255, 255, 255, 0.1);
            padding: 10px 20px;
            border-radius: 50px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-weight: 600;
        }

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

        .btn-secondary {
            background: rgba(108, 117, 125, 0.9);
            color: white;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.4);
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.6);
            background: rgba(108, 117, 125, 1);
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

        .main-content {
            padding: 40px;
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .attendance-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            padding: 50px;
            border-radius: 30px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.5);
            position: relative;
            overflow: hidden;
        }

        .attendance-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb);
            background-size: 200% 100%;
            animation: gradientShift 3s ease infinite;
        }

        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .date-header {
            font-size: 28px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 40px;
            font-weight: 800;
        }

        .attendance-status {
            margin: 50px 0;
            animation: scaleIn 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes scaleIn {
            0% {
                opacity: 0;
                transform: scale(0.5);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        .status-icon {
            font-size: 120px;
            margin-bottom: 30px;
            animation: bounce 2s ease-in-out infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        .status-message {
            font-size: 36px;
            font-weight: 800;
            margin: 30px 0;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .status-details {
            margin-top: 40px;
            padding: 30px;
            background: linear-gradient(135deg, rgba(248, 249, 250, 0.9), rgba(233, 236, 239, 0.9));
            border-radius: 20px;
            box-shadow: inset 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .detail-item {
            font-size: 20px;
            margin: 15px 0;
            padding: 15px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
        }

        .detail-item:hover {
            transform: translateX(10px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .detail-item strong {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
        }

        .not-marked {
            margin: 50px 0;
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .not-marked .status-icon {
            font-size: 100px;
        }

        .not-marked h1 {
            color: #ffc107;
            font-size: 32px;
            margin: 20px 0;
        }

        .not-marked p {
            font-size: 18px;
            color: #666;
            margin-top: 15px;
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

        .developer-title {
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
            border-radius: 12px;
            border: 1px solid;
        }

        .footer-bottom {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .copyright {
            color: #888;
            font-size: 12px;
            margin: 0 0 10px;
        }

        .made-with {
            color: #666;
            font-size: 11px;
            margin: 0;
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
            background: rgba(74, 158, 255, 0.2);
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
                padding: 15px 20px;
            }

            .navbar h1 {
                font-size: 18px;
            }

            .user-info {
                flex-direction: column;
                gap: 10px;
                width: 100%;
            }

            .main-content {
                padding: 20px;
            }

            .attendance-card {
                padding: 30px 20px;
            }

            .date-header {
                font-size: 20px;
            }

            .status-icon {
                font-size: 80px !important;
            }

            .status-message {
                font-size: 24px;
            }

            .detail-item {
                font-size: 16px;
            }

            .developer-badges {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background Particles -->
    <div class="particles">
        <div class="particle" style="width: 80px; height: 80px; left: 10%; animation-delay: 0s;"></div>
        <div class="particle" style="width: 60px; height: 60px; left: 30%; animation-delay: 2s;"></div>
        <div class="particle" style="width: 100px; height: 100px; left: 50%; animation-delay: 4s;"></div>
        <div class="particle" style="width: 70px; height: 70px; left: 70%; animation-delay: 1s;"></div>
        <div class="particle" style="width: 90px; height: 90px; left: 85%; animation-delay: 3s;"></div>
    </div>

    <nav class="navbar">
        <div>
            <h1>🎓 NIT AMMS - Today's Attendance</h1>
        </div>
        <div class="user-info">
            <a href="index.php" class="btn btn-secondary">← Back</a>
            <span>👨‍🎓 <?php echo htmlspecialchars($student['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <div class="attendance-card">
            <h2 class="date-header">📅 Today's Date: <?php echo date('l, d F Y'); ?></h2>
            
            <?php if ($today_attendance): ?>
                <div class="attendance-status">
                    <?php
                    $icon = '';
                    $color = '';
                    $message = '';
                    
                    if ($today_attendance['status'] === 'present') {
                        $icon = '✅';
                        $color = '#28a745';
                        $message = 'You were marked PRESENT today!';
                    } elseif ($today_attendance['status'] === 'absent') {
                        $icon = '❌';
                        $color = '#dc3545';
                        $message = 'You were marked ABSENT today!';
                    } else {
                        $icon = '⏰';
                        $color = '#ffc107';
                        $message = 'You were marked LATE today!';
                    }
                    ?>
                    
                    <div class="status-icon"><?php echo $icon; ?></div>
                    <h1 class="status-message" style="color: <?php echo $color; ?>;">
                        <?php echo $message; ?>
                    </h1>
                    
                    <div class="status-details">
                        <div class="detail-item">
                            <strong>Status:</strong> 
                            <span style="color: <?php echo $color; ?>; font-size: 24px; font-weight: 700;">
                                <?php echo strtoupper($today_attendance['status']); ?>
                            </span>
                        </div>
                        <div class="detail-item">
                            <strong>Marked At:</strong> <?php echo date('h:i A', strtotime($today_attendance['marked_at'])); ?>
                        </div>
                        <?php if ($today_attendance['remarks']): ?>
                        <div class="detail-item">
                            <strong>Remarks:</strong> <?php echo htmlspecialchars($today_attendance['remarks']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="not-marked">
                    <div class="status-icon">⏳</div>
                    <h1>Attendance Not Marked Yet</h1>
                    <p>Your teacher hasn't marked attendance for today. Please check back later.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Enhanced Footer -->
    <div class="footer">
        <div class="footer-border"></div>
        
        <div class="footer-content">
            <div class="developer-section">
                <p class="developer-title">✨ Designed & Developed by</p>
                
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
                    <span class="role-tag" style="color: #4a9eff; background: rgba(74, 158, 255, 0.1); border-color: rgba(74, 158, 255, 0.3);">Full Stack</span>
                    <span class="role-tag" style="color: #00d4ff; background: rgba(0, 212, 255, 0.1); border-color: rgba(0, 212, 255, 0.3);">UI/UX</span>
                    <span class="role-tag" style="color: #4a9eff; background: rgba(74, 158, 255, 0.1); border-color: rgba(74, 158, 255, 0.3);">Database</span>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p class="copyright">© 2025 NIT AMMS. All rights reserved.</p>
                <p class="made-with">Made with <span style="color: #ff4757; font-size: 14px;">❤️</span> by Techyug Software</p>
                
                <div class="social-links">
                    <a href="#" class="social-link">📧</a>
                    <a href="#" class="social-link">🌐</a>
                    <a href="#" class="social-link">💼</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>