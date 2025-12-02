<?php
require_once '../db.php';
checkRole(['parent']);

$parent_id = $_SESSION['user_id'];
$student_id = $_SESSION['student_id'];

// Get parent info
$parent = $conn->query("SELECT * FROM parents WHERE id = $parent_id")->fetch_assoc();

// Get student info
$student_query = "SELECT s.*, d.dept_name, c.class_name 
                  FROM students s
                  LEFT JOIN departments d ON s.department_id = d.id
                  LEFT JOIN classes c ON s.class_id = c.id
                  WHERE s.id = $student_id";
$student = $conn->query($student_query)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Parent</title>
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
        }

        .main-content {
            padding: 40px;
            max-width: 1600px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        /* Profile Header */
        .profile-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 50px;
            border-radius: 25px;
            text-align: center;
            margin-bottom: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.5);
            position: relative;
            overflow: hidden;
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #f093fb, #f5576c, #4facfe);
            background-size: 200% 100%;
            animation: gradientShift 3s ease infinite;
        }

        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        .profile-photo-container {
            position: relative;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .profile-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #f093fb;
            box-shadow: 0 10px 30px rgba(240, 147, 251, 0.4);
            transition: all 0.3s;
        }

        .profile-photo:hover {
            transform: scale(1.05);
            box-shadow: 0 15px 40px rgba(240, 147, 251, 0.6);
        }
        
        .profile-photo-placeholder {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f093fb, #f5576c);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 80px;
            border: 5px solid white;
            box-shadow: 0 10px 30px rgba(240, 147, 251, 0.4);
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
        }
        
        .upload-photo-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.6);
        }

        .profile-header h2 {
            margin: 15px 0 5px 0;
            font-size: 32px;
            background: linear-gradient(135deg, #f093fb, #f5576c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 800;
        }

        .profile-header p {
            color: #666;
            font-size: 18px;
            margin: 5px 0;
        }
        
        .info-card {
            background: linear-gradient(135deg, rgba(240, 147, 251, 0.05), rgba(245, 87, 108, 0.05));
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #f093fb;
            margin-bottom: 15px;
            transition: all 0.3s;
        }

        .info-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(240, 147, 251, 0.2);
        }
        
        .info-card label {
            font-size: 12px;
            color: #f093fb;
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

        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .profile-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 40px;
            border-radius: 25px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.5);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .profile-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(240, 147, 251, 0.4);
        }

        .profile-card h3 {
            margin-bottom: 25px;
            font-size: 24px;
            background: linear-gradient(135deg, #f093fb, #f5576c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 800;
        }

        .action-buttons {
            text-align: center;
            margin-top: 40px;
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
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

        /* Buttons */
        .btn {
            padding: 14px 28px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-block;
            border: none;
            cursor: pointer;
            font-size: 15px;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #f093fb, #f5576c);
            color: white;
            box-shadow: 0 4px 15px rgba(240, 147, 251, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(240, 147, 251, 0.6);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary:hover {
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

        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b, #ee5a5a);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4);
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.6);
        }

        /* Media Queries */
        @media screen and (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
                padding: 15px 20px;
            }

            .user-info {
                flex-direction: column;
                gap: 10px;
                width: 100%;
            }

            .user-info .btn {
                width: 100%;
            }

            .main-content {
                padding: 20px;
            }

            .profile-header {
                padding: 30px 20px;
            }

            .profile-photo,
            .profile-photo-placeholder {
                width: 120px;
                height: 120px;
            }

            .profile-photo-placeholder {
                font-size: 60px;
            }

            .upload-photo-btn {
                width: 40px;
                height: 40px;
                font-size: 18px;
            }

            .profile-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .profile-card {
                padding: 25px;
            }

            .info-card {
                padding: 15px;
            }

            .info-card value {
                font-size: 16px;
            }

            .action-buttons {
                flex-direction: column;
                align-items: stretch;
            }

            .action-buttons .btn {
                width: 100%;
            }
        }

        @media screen and (max-width: 480px) {
            .profile-header {
                padding: 25px 15px;
            }

            .profile-header h2 {
                font-size: 24px;
            }

            .profile-header p {
                font-size: 14px;
            }

            .profile-photo,
            .profile-photo-placeholder {
                width: 100px;
                height: 100px;
            }

            .profile-photo-placeholder {
                font-size: 50px;
            }

            .upload-photo-btn {
                width: 35px;
                height: 35px;
                font-size: 16px;
            }

            .profile-card {
                padding: 20px;
            }

            .profile-card h3 {
                font-size: 20px;
            }

            .info-card {
                padding: 12px;
            }

            .info-card label {
                font-size: 11px;
            }

            .info-card value {
                font-size: 14px;
            }

            .btn {
                padding: 12px 20px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background Particles -->
    <div class="particles">
        <script>
            for(let i = 0; i < 20; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.width = Math.random() * 5 + 2 + 'px';
                particle.style.height = particle.style.width;
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 15 + 's';
                particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
                document.querySelector('.particles').appendChild(particle);
            }
        </script>
    </div>

    <nav class="navbar">
        <div>
            <h1>🎓 NIT AMMS - My Profile</h1>
        </div>
        <div class="user-info">
            <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
            <span>👨‍👩‍👦 <?php echo htmlspecialchars($parent['parent_name']); ?></span>
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
                <?php if (!empty($parent['photo']) && file_exists("../uploads/parents/" . $parent['photo'])): ?>
                    <img src="../uploads/parents/<?php echo htmlspecialchars($parent['photo']); ?>" 
                         alt="Profile Photo" 
                         class="profile-photo">
                <?php else: ?>
                    <div class="profile-photo-placeholder">👨‍👩‍👦</div>
                <?php endif; ?>
                
                <form id="photoForm" method="POST" action="../upload_photo.php" enctype="multipart/form-data" style="display: inline;">
                    <input type="hidden" name="user_type" value="parent">
                    <input type="hidden" name="user_id" value="<?php echo $parent_id; ?>">
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
                          📷
                    </button>
                </form>
            </div>
            
            <h2><?php echo htmlspecialchars($parent['parent_name']); ?></h2>
            <p style="font-size: 18px; font-weight: 600;">Parent of: <?php echo htmlspecialchars($student['full_name']); ?></p>
            <p style="font-size: 16px;">Relationship: <?php echo ucfirst($parent['relationship']); ?></p>
        </div>

        <div class="profile-grid">
            <div class="profile-card">
                <h3>📋 My Information</h3>
                
                <div class="info-card">
                    <label>Full Name</label>
                    <value><?php echo htmlspecialchars($parent['parent_name']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Email Address</label>
                    <value><?php echo htmlspecialchars($parent['email']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Phone Number</label>
                    <value><?php echo htmlspecialchars($parent['phone']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Relationship</label>
                    <value><?php echo ucfirst($parent['relationship']); ?></value>
                </div>
            </div>

            <div class="profile-card">
                <h3>👨‍🎓 Child's Information</h3>
                
                <div class="info-card">
                    <label>Student Name</label>
                    <value><?php echo htmlspecialchars($student['full_name']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Roll Number</label>
                    <value><?php echo htmlspecialchars($student['roll_number']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Email</label>
                    <value><?php echo htmlspecialchars($student['email']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Department</label>
                    <value><?php echo htmlspecialchars($student['dept_name']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Class</label>
                    <value><?php echo htmlspecialchars($student['class_name']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Year & Semester</label>
                    <value>Year <?php echo $student['year']; ?> - Semester <?php echo $student['semester']; ?></value>
                </div>
            </div>
        </div>

        <div class="action-buttons">
            <a href="index.php" class="btn btn-primary">🏠 Back to Dashboard</a>
            <a href="attendance_report.php" class="btn btn-success">📊 View Child's Attendance</a>
        </div>
    </div>

    <!-- Compact Footer -->
    <div style="background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 50%, #2a3254 100%); position: relative; overflow: hidden;">
        <div style="height: 2px; background: linear-gradient(90deg, #4a9eff, #00d4ff, #4a9eff, #00d4ff); background-size: 200% 100%;"></div>
        
        <div style="max-width: 1000px; margin: 0 auto; padding: 30px 20px 20px;">
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
                <p style="color: #666; font-size: 11px; margin: 0;">Made with <span style="color: #ff4757; font-size: 14px;">❤️</span> by Techyug Software</p>
            </div>
        </div>
    </div>
</body>
</html>