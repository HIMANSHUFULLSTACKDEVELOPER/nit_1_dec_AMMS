<?php
require_once '../db.php';
checkRole(['hod']);

$user = getCurrentUser();
$hod_id = $user['id'];
$department_id = $_SESSION['department_id'];

// Get HOD's full information including photo
$hod_query = "SELECT u.*, d.dept_name,
              (SELECT COUNT(*) FROM users WHERE role = 'teacher' AND department_id = u.department_id AND is_active = 1) as teacher_count,
              (SELECT COUNT(*) FROM students WHERE department_id = u.department_id AND is_active = 1) as student_count,
              (SELECT COUNT(*) FROM classes WHERE department_id = u.department_id) as class_count
              FROM users u
              LEFT JOIN departments d ON u.department_id = d.id
              WHERE u.id = $hod_id";
$hod = $conn->query($hod_query)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - HOD</title>
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

        .main-content {
            padding: 40px;
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.15);
            border: 2px solid #28a745;
            color: #155724;
        }

        .alert-danger {
            background: rgba(220, 53, 69, 0.15);
            border: 2px solid #dc3545;
            color: #721c24;
        }

        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            padding: 40px;
            border-radius: 20px;
            color: white;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 8s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.1) rotate(180deg); }
        }
        
        .profile-photo-container {
            position: relative;
            display: inline-block;
            margin-bottom: 20px;
            z-index: 1;
        }
        
        .profile-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
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
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .upload-photo-btn {
            position: absolute;
            bottom: 0;
            right: 0;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            font-size: 20px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.5);
            transition: all 0.3s;
        }
        
        .upload-photo-btn:hover {
            transform: scale(1.1);
            background: #218838;
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.7);
        }
        
        .stat-mini {
            background: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            border: 2px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s;
        }

        .stat-mini:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .stat-mini-value {
            font-size: 36px;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
        }
        
        .stat-mini-label {
            font-size: 14px;
            color: #666;
            font-weight: 600;
        }

        .info-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #667eea;
            margin-bottom: 15px;
            transition: all 0.3s;
        }

        .info-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }
        
        .info-card label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
            display: block;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        
        .info-card value {
            font-size: 18px;
            color: #333;
            font-weight: 500;
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

        .content-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
            margin-bottom: 20px;
            border: 2px solid rgba(255, 255, 255, 0.5);
        }

        .content-card h3 {
            margin-bottom: 25px;
            color: #2c3e50;
            font-weight: 700;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .department-stat {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            transition: all 0.3s;
        }

        .department-stat:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
        }

        .department-stat:nth-child(2) {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }

        .department-stat:nth-child(3) {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
        }

        .department-stat-value {
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 10px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .department-stat-label {
            font-size: 16px;
            font-weight: 500;
            opacity: 0.9;
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

        .btn-info {
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
            box-shadow: 0 4px 15px rgba(23, 162, 184, 0.4);
        }

        .btn-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(23, 162, 184, 0.6);
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

        /* Footer Styles */
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
       /* Enhanced Responsive Design - Media Queries */

/* Large Screens (1200px and above) */
@media (min-width: 1200px) {
    .main-content {
        padding: 50px;
        max-width: 1400px;
    }
    
    .content-card {
        padding: 40px;
    }
    
    .profile-header {
        padding: 50px;
    }
    
    .navbar {
        padding: 25px 50px;
    }
}

/* Medium Screens (992px to 1199px) */
@media (max-width: 1199px) {
    .main-content {
        padding: 35px;
    }
    
    .content-card {
        padding: 30px;
    }
    
    .profile-header {
        padding: 35px;
    }
    
    .user-info {
        gap: 15px;
    }
    
    .navbar h1 {
        font-size: 22px;
    }
}

/* Tablets (768px to 991px) */
@media (max-width: 991px) {
    .navbar {
        padding: 15px 25px;
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .navbar h1 {
        font-size: 20px;
        margin-bottom: 10px;
    }
    
    .user-info {
        flex-direction: column;
        gap: 12px;
        width: 100%;
        align-items: center;
    }
    
    .main-content {
        padding: 25px;
    }
    
    .profile-header {
        padding: 30px 20px;
        text-align: center;
    }
    
    .profile-header h2 {
        font-size: 28px !important;
    }
    
    .profile-header p {
        font-size: 15px !important;
    }
    
    .stat-mini {
        padding: 15px;
    }
    
    .stat-mini-value {
        font-size: 32px;
    }
    
    .stat-mini-label {
        font-size: 13px;
    }
    
    .content-card {
        padding: 25px;
    }
    
    .content-card h3 {
        font-size: 22px;
        margin-bottom: 20px;
    }
    
    /* Grid adjustments for tablets */
    .info-card-grid {
        grid-template-columns: 1fr !important;
    }
    
    /* Department stats in 2 columns on tablets */
    .department-stat {
        padding: 25px;
    }
    
    .department-stat-value {
        font-size: 40px;
    }
    
    .btn {
        padding: 10px 20px;
        font-size: 13px;
    }
    
    .footer-content {
        padding: 25px 15px 15px;
    }
    
    .developer-badge {
        font-size: 12px;
        padding: 6px 12px;
    }
}

/* Small Tablets & Large Phones (576px to 767px) */
@media (max-width: 767px) {
    .navbar {
        padding: 12px 15px;
    }
    
    .navbar h1 {
        font-size: 18px;
        margin-bottom: 8px;
    }
    
    .user-info {
        flex-direction: column;
        gap: 10px;
        width: 100%;
        font-size: 14px;
    }
    
    .main-content {
        padding: 15px;
    }
    
    .profile-header {
        padding: 25px 15px;
        margin-bottom: 20px;
        border-radius: 15px;
    }
    
    .profile-photo {
        width: 120px;
        height: 120px;
    }
    
    .profile-photo-placeholder {
        width: 120px;
        height: 120px;
        font-size: 60px;
    }
    
    .upload-photo-btn {
        width: 40px;
        height: 40px;
        font-size: 18px;
    }
    
    .profile-header h2 {
        font-size: 24px !important;
        margin: 12px 0 3px !important;
    }
    
    .profile-header p {
        font-size: 14px !important;
    }
    
    .stat-mini {
        padding: 12px;
    }
    
    .stat-mini-value {
        font-size: 28px;
    }
    
    .stat-mini-label {
        font-size: 12px;
    }
    
    /* 2 columns on small tablets */
    .profile-header > div[style*="grid"] {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 10px !important;
        margin-top: 15px !important;
    }
    
    .content-card {
        padding: 20px;
        border-radius: 15px;
        margin-bottom: 15px;
    }
    
    .content-card h3 {
        font-size: 20px;
        margin-bottom: 15px;
    }
    
    .info-card {
        padding: 15px;
        margin-bottom: 12px;
    }
    
    .info-card label {
        font-size: 11px;
        margin-bottom: 6px;
    }
    
    .info-card value {
        font-size: 16px;
    }
    
    .department-stat {
        padding: 20px;
        border-radius: 12px;
    }
    
    .department-stat-value {
        font-size: 36px;
    }
    
    .department-stat-label {
        font-size: 15px;
    }
    
    .btn {
        padding: 10px 18px;
        font-size: 12px;
        margin: 5px;
        display: block;
        width: 100%;
        text-align: center;
    }
    
    .alert {
        padding: 12px 15px;
        font-size: 13px;
        border-radius: 10px;
    }
    
    .developer-section {
        padding: 15px;
        border-radius: 12px;
    }
    
    .developer-section p {
        font-size: 13px;
        margin: 0 0 10px;
    }
    
    .company-link {
        font-size: 14px;
        padding: 6px 20px;
        margin-bottom: 12px;
    }
    
    .developer-badges {
        gap: 8px;
        margin-top: 10px;
    }
    
    .developer-badge {
        font-size: 11px;
        padding: 6px 12px;
    }
    
    .role-tags {
        gap: 8px;
        margin-top: 12px;
    }
    
    .role-tag {
        font-size: 9px;
        padding: 3px 10px;
    }
    
    .footer-bottom {
        margin-top: 15px;
        padding-top: 15px;
    }
    
    .footer-bottom p {
        font-size: 11px;
        margin: 0 0 8px;
    }
}

/* Small Phones (480px to 575px) */
@media (max-width: 575px) {
    * {
        font-size: 14px;
    }
    
    .navbar {
        padding: 10px 12px;
        gap: 10px;
    }
    
    .navbar h1 {
        font-size: 16px;
    }
    
    .user-info {
        font-size: 12px;
        gap: 8px;
    }
    
    .main-content {
        padding: 12px;
    }
    
    .profile-header {
        padding: 20px 12px;
        margin-bottom: 15px;
    }
    
    .profile-photo {
        width: 100px;
        height: 100px;
        border: 4px solid white;
    }
    
    .profile-photo-placeholder {
        width: 100px;
        height: 100px;
        font-size: 50px;
        border: 4px solid white;
    }
    
    .upload-photo-btn {
        width: 35px;
        height: 35px;
        font-size: 16px;
    }
    
    .profile-header h2 {
        font-size: 20px !important;
        margin: 10px 0 2px !important;
    }
    
    .profile-header p {
        font-size: 12px !important;
    }
    
    /* 1 column on small phones */
    .profile-header > div[style*="grid"] {
        grid-template-columns: 1fr !important;
        gap: 8px !important;
        margin-top: 12px !important;
    }
    
    .stat-mini {
        padding: 10px;
    }
    
    .stat-mini-value {
        font-size: 24px;
    }
    
    .stat-mini-label {
        font-size: 11px;
    }
    
    .content-card {
        padding: 15px;
        margin-bottom: 12px;
    }
    
    .content-card h3 {
        font-size: 18px;
        margin-bottom: 12px;
    }
    
    .info-card {
        padding: 12px;
        margin-bottom: 10px;
    }
    
    .info-card label {
        font-size: 10px;
        margin-bottom: 5px;
    }
    
    .info-card value {
        font-size: 14px;
    }
    
    .department-stat {
        padding: 15px;
    }
    
    .department-stat-value {
        font-size: 32px;
    }
    
    .department-stat-label {
        font-size: 13px;
    }
    
    .btn {
        padding: 8px 15px;
        font-size: 11px;
        margin: 4px;
    }
    
    .badge {
        font-size: 9px;
        padding: 4px 10px;
    }
    
    .alert {
        padding: 10px 12px;
        font-size: 12px;
    }
    
    .developer-section {
        padding: 12px;
    }
    
    .developer-section p {
        font-size: 12px;
    }
    
    .company-link {
        font-size: 13px;
        padding: 5px 15px;
    }
    
    .developer-badge {
        font-size: 10px;
        padding: 5px 10px;
    }
    
    .role-tag {
        font-size: 8px;
        padding: 2px 8px;
    }
    
    .footer-border {
        height: 1px;
    }
    
    .footer-bottom p {
        font-size: 10px;
    }
    
    .social-links {
        gap: 8px;
    }
    
    .social-link {
        width: 28px;
        height: 28px;
        font-size: 12px;
    }
}

/* Extra Small Phones (under 480px) */
@media (max-width: 479px) {
    .navbar {
        padding: 8px 10px;
    }
    
    .navbar h1 {
        font-size: 14px;
    }
    
    .user-info {
        font-size: 11px;
    }
    
    .main-content {
        padding: 10px;
    }
    
    .profile-photo {
        width: 80px;
        height: 80px;
    }
    
    .profile-photo-placeholder {
        width: 80px;
        height: 80px;
        font-size: 40px;
    }
    
    .profile-header h2 {
        font-size: 18px !important;
    }
    
    .profile-header p {
        font-size: 11px !important;
    }
    
    .stat-mini-value {
        font-size: 20px;
    }
    
    .content-card h3 {
        font-size: 16px;
    }
    
    .btn {
        padding: 7px 12px;
        font-size: 10px;
    }
}
    </style>
</head>
<body>
    <!-- Animated Background Particles -->
    <div class="particles"></div>

    <nav class="navbar">
        <div>
            <h1>🎓 NIT AMMS - My Profile</h1>
        </div>
        <div class="user-info">
            <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
            <span>👔 <?php echo htmlspecialchars($hod['full_name']); ?></span>
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
                <?php 
                $photo_exists = false;
                $photo_src = '';
                
                if (!empty($hod['photo'])) {
                    if (file_exists("../uploads/hods/" . $hod['photo'])) {
                        $photo_exists = true;
                        $photo_src = "../uploads/hods/" . htmlspecialchars($hod['photo']);
                    } elseif (file_exists("../" . $hod['photo'])) {
                        $photo_exists = true;
                        $photo_src = "../" . htmlspecialchars($hod['photo']);
                    }
                }
                
                if ($photo_exists): 
                ?>
                    <img src="<?php echo $photo_src; ?>" 
                         alt="Profile Photo" 
                         class="profile-photo"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="profile-photo-placeholder" style="display: none;">👔</div>
                <?php else: ?>
                    <div class="profile-photo-placeholder">👔</div>
                <?php endif; ?>
                
                <form id="photoForm" method="POST" action="../upload_photo.php" enctype="multipart/form-data" style="display: inline;">
                    <input type="hidden" name="user_type" value="hod">
                    <input type="hidden" name="user_id" value="<?php echo $hod_id; ?>">
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
            
            <h2 style="margin: 15px 0 5px 0; font-size: 36px; font-weight: 800; position: relative; z-index: 1;"><?php echo htmlspecialchars($hod['full_name']); ?></h2>
            <p style="font-size: 18px; opacity: 0.9; position: relative; z-index: 1;">Head of Department</p>
            <p style="font-size: 16px; opacity: 0.8; position: relative; z-index: 1;"><?php echo htmlspecialchars($hod['dept_name']); ?></p>
            
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 20px; position: relative; z-index: 1;">
                <div class="stat-mini">
                    <div class="stat-mini-value"><?php echo $hod['teacher_count']; ?></div>
                    <div class="stat-mini-label">Teachers</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-value"><?php echo $hod['student_count']; ?></div>
                    <div class="stat-mini-label">Students</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-value"><?php echo $hod['class_count']; ?></div>
                    <div class="stat-mini-label">Classes</div>
                </div>
            </div>
        </div>

        <div class="content-card">
            <h3>📋 Personal Information</h3>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="info-card">
                    <label>Full Name</label>
                    <value><?php echo htmlspecialchars($hod['full_name']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Username</label>
                    <value><?php echo htmlspecialchars($hod['username']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Email Address</label>
                    <value><?php echo htmlspecialchars($hod['email']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Phone Number</label>
                    <value><?php echo htmlspecialchars($hod['phone']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Role</label>
                    <value>Head of Department (HOD)</value>
                </div>
                
                <div class="info-card">
                    <label>Department</label>
                    <value><?php echo htmlspecialchars($hod['dept_name']); ?></value>
                </div>
                
                <div class="info-card">
                    <label>Account Status</label>
                    <value>
                        <?php if ($hod['is_active']): ?>
                            <span class="badge badge-success">✅ Active</span>
                        <?php else: ?>
                            <span class="badge badge-danger">❌ Inactive</span>
                        <?php endif; ?>
                    </value>
                </div>
                
                <div class="info-card">
                    <label>Member Since</label>
                    <value><?php echo date('F Y', strtotime($hod['created_at'])); ?></value>
                </div>
            </div>
        </div>

        <div class="content-card">
            <h3>📊 Department Overview</h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div class="department-stat">
                    <div class="department-stat-value"><?php echo $hod['teacher_count']; ?></div>
                    <div class="department-stat-label">Active Teachers</div>
                </div>
                
                <div class="department-stat">
                    <div class="department-stat-value"><?php echo $hod['student_count']; ?></div>
                    <div class="department-stat-label">Enrolled Students</div>
                </div>
                
                <div class="department-stat">
                    <div class="department-stat-value"><?php echo $hod['class_count']; ?></div>
                    <div class="department-stat-label">Total Classes</div>
                </div>
            </div>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="index.php" class="btn btn-primary">🏠 Back to Dashboard</a>
            <a href="view_teachers.php" class="btn btn-success">👨‍🏫 View Teachers</a>
            <a href="view_students.php" class="btn btn-info">👨‍🎓 View Students</a>
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
                <p style="color: #666; font-size: 11px; margin: 0;">
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
        // Create animated background particles
        function createParticles() {
            const particlesContainer = document.querySelector('.particles');
            const particleCount = 50;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                // Random size between 2px and 8px
                const size = Math.random() * 6 + 2;
                particle.style.width = size + 'px';
                particle.style.height = size + 'px';
                
                // Random starting position
                particle.style.left = Math.random() * 100 + '%';
                particle.style.top = Math.random() * 100 + '%';
                
                // Random animation delay
                particle.style.animationDelay = Math.random() * 15 + 's';
                
                // Random animation duration
                particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
                
                particlesContainer.appendChild(particle);
            }
        }
        
        // Initialize particles on page load
        document.addEventListener('DOMContentLoaded', createParticles);
        
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
        
        // Add entrance animations to cards
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '0';
                    entry.target.style.transform = 'translateY(20px)';
                    setTimeout(() => {
                        entry.target.style.transition = 'all 0.6s ease-out';
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }, 100);
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);
        
        // Observe all content cards
        document.querySelectorAll('.content-card, .info-card').forEach(card => {
            observer.observe(card);
        });
    </script>