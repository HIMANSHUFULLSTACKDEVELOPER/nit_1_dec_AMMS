<?php
require_once '../db.php';
checkRole(['teacher']);

$user = getCurrentUser();
$teacher_id = $user['id'];

// Get teacher's full information including photo - FORCE FRESH DATA
$teacher_query = "SELECT u.*, d.dept_name,
                  (SELECT COUNT(*) FROM classes WHERE teacher_id = u.id) as class_count
                  FROM users u
                  LEFT JOIN departments d ON u.department_id = d.id
                  WHERE u.id = ?";
$stmt = $conn->prepare($teacher_query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Debug: Log the photo path
error_log("Current photo path: " . ($teacher['photo'] ?? 'NULL'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Teacher</title>
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
            gap: 25px;
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
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .profile-container {
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .profile-header {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.9) 100%);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            padding: 50px;
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
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
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb);
            background-size: 200% 100%;
            animation: gradientShift 3s ease infinite;
        }

        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        .profile-photo-container {
            position: relative;
            width: 180px;
            height: 180px;
            margin: 0 auto 25px;
            animation: zoomIn 0.6s ease-out;
        }

        @keyframes zoomIn {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        
        .profile-photo {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            object-fit: cover;
            border: 6px solid white;
            box-shadow: 0 10px 40px rgba(102, 126, 234, 0.5);
            transition: transform 0.3s ease;
        }

        .profile-photo:hover {
            transform: scale(1.05);
        }
        
        .profile-photo-placeholder {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 90px;
            border: 6px solid white;
            box-shadow: 0 10px 40px rgba(102, 126, 234, 0.5);
        }
        
        .upload-btn-wrapper {
            position: relative;
            display: inline-block;
            margin-top: 20px;
        }
        
        .upload-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 14px 35px;
            border-radius: 30px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
            font-size: 15px;
        }
        
        .upload-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.6);
        }
        
        .profile-info-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            padding: 40px;
            margin-bottom: 25px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.5);
            transition: transform 0.3s ease;
        }

        .profile-info-card:hover {
            transform: translateY(-5px);
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 25px;
        }
        
        .info-item {
            padding: 20px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.08), rgba(118, 75, 162, 0.08));
            border-radius: 15px;
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
        }

        .info-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.2);
        }
        
        .info-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .info-value {
            font-size: 17px;
            font-weight: 700;
            color: #2c3e50;
        }
        
        .success-message, .error-message {
            padding: 20px 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            border-left: 5px solid;
            animation: slideDown 0.5s ease;
            backdrop-filter: blur(10px);
            font-weight: 500;
        }
        
        .success-message {
            background: rgba(212, 237, 218, 0.95);
            color: #155724;
            border-color: #28a745;
            box-shadow: 0 5px 20px rgba(40, 167, 69, 0.2);
        }
        
        .error-message {
            background: rgba(248, 215, 218, 0.95);
            color: #721c24;
            border-color: #dc3545;
            box-shadow: 0 5px 20px rgba(220, 53, 69, 0.2);
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

        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
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

        /* Responsive Design */
        @media (max-width: 768px) {
            .navbar { padding: 15px 20px; flex-direction: column; gap: 15px; }
            .navbar h1 { font-size: 18px; }
            .main-content { padding: 20px; }
            .profile-header { padding: 30px 20px; }
            .info-grid { grid-template-columns: 1fr; }
            .user-info { flex-direction: column; gap: 10px; }
        }
    </style>
</head>
<body>
    <!-- Animated Background Particles -->
    <div class="particles">
        <div class="particle" style="width: 10px; height: 10px; left: 10%; animation-delay: 0s;"></div>
        <div class="particle" style="width: 8px; height: 8px; left: 20%; animation-delay: 2s;"></div>
        <div class="particle" style="width: 12px; height: 12px; left: 30%; animation-delay: 4s;"></div>
        <div class="particle" style="width: 6px; height: 6px; left: 40%; animation-delay: 6s;"></div>
        <div class="particle" style="width: 14px; height: 14px; left: 50%; animation-delay: 8s;"></div>
        <div class="particle" style="width: 10px; height: 10px; left: 60%; animation-delay: 10s;"></div>
        <div class="particle" style="width: 8px; height: 8px; left: 70%; animation-delay: 12s;"></div>
        <div class="particle" style="width: 12px; height: 12px; left: 80%; animation-delay: 14s;"></div>
        <div class="particle" style="width: 10px; height: 10px; left: 90%; animation-delay: 16s;"></div>
    </div>

    <nav class="navbar">
        <div>
            <h1>🎓 NIT AMMS - My Profile</h1>
        </div>
        <div class="user-info">
            <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
            <span>👨‍🏫 <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <div class="profile-container">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="success-message">
                    ✅ <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error-message">
                    ❌ <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <div class="profile-header">
                <div class="profile-photo-container">
                    <?php if (!empty($teacher['photo']) && file_exists('../' . $teacher['photo'])): ?>
                        <img src="../<?php echo htmlspecialchars($teacher['photo']); ?>?v=<?php echo time(); ?>" 
                             alt="Profile Photo" 
                             class="profile-photo"
                             id="profilePhotoImg"
                             onerror="this.style.display='none'; document.getElementById('profilePhotoPlaceholder').style.display='flex';">
                    <?php else: ?>
                        <div class="profile-photo-placeholder" id="profilePhotoPlaceholder">
                            👨‍🏫
                        </div>
                    <?php endif; ?>
                </div>
                
                <h2 style="margin: 0 0 15px 0; font-size: 38px; font-weight: 800; background: linear-gradient(135deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                    <?php echo htmlspecialchars($teacher['full_name']); ?>
                </h2>
                <p style="font-size: 18px; color: #666; margin-bottom: 8px;">
                    📧 <?php echo htmlspecialchars($teacher['username']); ?>
                </p>
                <p style="font-size: 16px; color: #888;">
                    🎓 Role: Teacher
                </p>
                
                <form action="../upload_phototeacher.php" 
                      method="POST" 
                      enctype="multipart/form-data" 
                      id="uploadForm">
                    <div class="upload-btn-wrapper">
                        <label for="photoInput" class="upload-btn">
                            <?php echo !empty($teacher['photo']) ? '📸 Change Photo' : '📸 Upload Photo'; ?>
                        </label>
                        <input type="file" 
                               id="photoInput" 
                               name="photo" 
                               accept="image/jpeg,image/jpg,image/png,image/gif"
                               style="display: none;"
                               onchange="this.form.submit();">
                    </div>
                </form>
                
                <p style="font-size: 13px; margin-top: 15px; color: #888; font-weight: 500;">
                    📌 Accepted: JPG, PNG, GIF (Max 5MB)
                </p>
            </div>
            
            <div class="profile-info-card">
                <h3 style="color: #2c3e50; margin-bottom: 25px; font-size: 26px; font-weight: 800; display: flex; align-items: center; gap: 12px;">
                    <span>📋</span> Personal Information
                </h3>
                
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Full Name</div>
                        <div class="info-value"><?php echo htmlspecialchars($teacher['full_name']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Username</div>
                        <div class="info-value"><?php echo htmlspecialchars($teacher['username']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?php echo htmlspecialchars($teacher['email']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Phone</div>
                        <div class="info-value"><?php echo htmlspecialchars($teacher['phone']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Department</div>
                        <div class="info-value"><?php echo htmlspecialchars($teacher['dept_name'] ?? 'Not Assigned'); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Teaching Classes</div>
                        <div class="info-value"><?php echo $teacher['class_count']; ?> Class(es)</div>
                    </div>
                </div>
            </div>
            
            <div class="profile-info-card">
                <h3 style="color: #2c3e50; margin-bottom: 25px; font-size: 26px; font-weight: 800; display: flex; align-items: center; gap: 12px;">
                    <span>📊</span> Account Status
                </h3>
                
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Account Status</div>
                        <div class="info-value">
                            <?php if ($teacher['is_active']): ?>
                                <span style="color: #28a745;">✅ Active</span>
                            <?php else: ?>
                                <span style="color: #dc3545;">❌ Inactive</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Account Created</div>
                        <div class="info-value"><?php echo date('d M Y', strtotime($teacher['created_at'])); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Current Photo</div>
                        <div class="info-value" style="font-size: 12px; word-break: break-all;">
                            <?php echo !empty($teacher['photo']) ? htmlspecialchars($teacher['photo']) : 'No photo'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer (keeping the original footer design) -->
    <div style="background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 50%, #2a3254 100%); position: relative; overflow: hidden;">
        <div style="height: 2px; background: linear-gradient(90deg, #4a9eff, #00d4ff, #4a9eff, #00d4ff); background-size: 200% 100%;"></div>
        <div style="max-width: 1000px; margin: 0 auto; padding: 30px 20px 20px;">
            <div style="background: rgba(255, 255, 255, 0.03); padding: 20px 20px; border-radius: 15px; border: 1px solid rgba(74, 158, 255, 0.15); text-align: center; box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);">
                <p style="color: #ffffff; font-size: 14px; margin: 0 0 12px; font-weight: 500; letter-spacing: 0.5px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">✨ Designed & Developed by</p>
                <a href="https://himanshufullstackdeveloper.github.io/techyugsoftware/" style="display: inline-block; color: #ffffff; font-size: 16px; font-weight: 700; text-decoration: none; padding: 8px 24px; border: 2px solid #4a9eff; border-radius: 30px; background: linear-gradient(135deg, rgba(74, 158, 255, 0.2), rgba(0, 212, 255, 0.2)); box-shadow: 0 3px 12px rgba(74, 158, 255, 0.3); margin-bottom: 15px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">🚀 Techyug Software Pvt. Ltd.</a>
                <div style="width: 50%; height: 1px; background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent); margin: 15px auto;"></div>
                <p style="color: #888; font-size: 10px; margin: 0 0 12px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">💼 Development Team</p>
                <div style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; margin-top: 12px;">
                    <a href="https://himanshufullstackdeveloper.github.io/portfoilohimanshu/" style="color: #ffffff; font-size: 13px; text-decoration: none; padding: 8px 16px; background: linear-gradient(135deg, rgba(74, 158, 255, 0.25), rgba(0, 212, 255, 0.25)); border-radius: 20px; border: 1px solid rgba(74, 158, 255, 0.4); display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 3px 10px rgba(74, 158, 255, 0.2); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;"><span style="font-size: 16px;">👨‍💻</span><span style="font-weight: 600;">Himanshu Patil</span></a>
                    <a href="https://devpranaypanore.github.io/Pranaypanore-live-.html/" style="color: #ffffff; font-size: 13px; text-decoration: none; padding: 8px 16px; background: linear-gradient(135deg, rgba(74, 158, 255, 0.25), rgba(0, 212, 255, 0.25)); border-radius: 20px; border: 1px solid rgba(74, 158, 255, 0.4); display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 3px 10px rgba(74, 158, 255, 0.2); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;"><span style="font-size: 16px;">👨‍💻</span><span style="font-weight: 600;">Pranay Panore</span></a>
                </div>
                <div style="margin-top: 15px; display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;">
                    <span style="color: #4a9eff; font-size: 10px; padding: 4px 12px; background: rgba(74, 158, 255, 0.1); border-radius: 12px; border: 1px solid rgba(74, 158, 255, 0.3); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">Full Stack</span>
                    <span style="color: #00d4ff; font-size: 10px; padding: 4px 12px; background: rgba(0, 212, 255, 0.1); border-radius: 12px; border: 1px solid rgba(0, 212, 255, 0.3); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">UI/UX</span>
                    <span style="color: #4a9eff; font-size: 10px; padding: 4px 12px; background: rgba(74, 158, 255, 0.1); border-radius: 12px; border: 1px solid rgba(74, 158, 255, 0.3); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">Database</span>
                </div>
            </div>
            <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.1); text-align: center;">
                <p style="color: #888; font-size: 12px; margin: 0 0 10px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">© 2025 NIT AMMS. All rights reserved.</p>
                <p style="color: #666; font-size: 11px; margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">Made with <span style="color: #ff4757; font-size: 14px;">❤️</span> by Techyug Software</p>
            </div>
        </div>
    </div>
</body>
</html>