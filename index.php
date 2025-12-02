<?php
require_once 'db.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = $_SESSION['role'];
    header("Location: $role/index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title>NIT AMMS - Attendance System</title>
    <link rel="icon" href="Nit_logo.png" type="image/svg+xml" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            width: 100%;
            overflow-x: hidden;
        }

        body {
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            background: #0a0a1a;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        /* Background Elements */
        .mesh-gradient {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            background: 
                radial-gradient(ellipse at 10% 20%, rgba(120, 0, 255, 0.4) 0%, transparent 50%),
                radial-gradient(ellipse at 90% 80%, rgba(255, 0, 128, 0.4) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 50%, rgba(0, 212, 255, 0.3) 0%, transparent 60%),
                radial-gradient(ellipse at 80% 20%, rgba(255, 165, 0, 0.3) 0%, transparent 40%);
            animation: meshMove 15s ease-in-out infinite;
        }

        @keyframes meshMove {
            0%, 100% { filter: hue-rotate(0deg); transform: scale(1); }
            50% { filter: hue-rotate(30deg); transform: scale(1.1); }
        }

        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            animation: orbFloat 20s ease-in-out infinite;
            z-index: 0;
        }

        .orb-1 {
            width: 600px;
            height: 600px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            top: -200px;
            left: -200px;
        }

        .orb-2 {
            width: 500px;
            height: 500px;
            background: linear-gradient(135deg, #f093fb, #f5576c);
            bottom: -150px;
            right: -150px;
            animation-delay: -5s;
        }

        .orb-3 {
            width: 400px;
            height: 400px;
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation-delay: -10s;
        }

        @keyframes orbFloat {
            0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.6; }
            25% { transform: translate(50px, -50px) scale(1.1); opacity: 0.8; }
            50% { transform: translate(-30px, 30px) scale(0.9); opacity: 0.5; }
            75% { transform: translate(40px, 40px) scale(1.05); opacity: 0.7; }
        }

        .stars {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            pointer-events: none;
        }

        .star {
            position: absolute;
            background: white;
            border-radius: 50%;
            animation: twinkle 3s ease-in-out infinite;
        }

        @keyframes twinkle {
            0%, 100% { opacity: 0.3; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.2); }
        }

        .grid-lines {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: 0;
            animation: gridMove 20s linear infinite;
        }

        @keyframes gridMove {
            0% { transform: perspective(500px) rotateX(60deg) translateY(0); }
            100% { transform: perspective(500px) rotateX(60deg) translateY(50px); }
        }

        /* Main Container - LEFT: Brand, RIGHT: Login */
        .main-wrapper {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: center;
            gap: 60px;
            padding: 40px 20px;
            z-index: 10;
            position: relative;
            max-width: 1400px;
            width: 100%;
            min-height: 100vh;
        }

        /* Login Container - RIGHT SIDE */
        .login-container {
            flex: 1;
            max-width: 500px;
            order: 2;
            animation: slideInRight 1s ease-out;
            display: flex;
            justify-content: center;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(40px);
            border-radius: 32px;
            padding: 50px 45px;
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.5);
            position: relative;
            overflow: hidden;
            width: 100%;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb, #4facfe);
            background-size: 300% 100%;
            animation: borderGradient 4s linear infinite;
        }

        @keyframes borderGradient {
            0% { background-position: 0% 50%; }
            100% { background-position: 300% 50%; }
        }

        .login-header {
            text-align: center;
            margin-bottom: 35px;
            position: relative;
            z-index: 1;
        }

        .login-header h2 {
            font-size: 32px;
            font-weight: 700;
            color: white;
            margin-bottom: 8px;
        }

        .login-header p {
            color: rgba(255,255,255,0.5);
            font-size: 14px;
        }

        .live-clock {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .clock-segment {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 12px 18px;
            text-align: center;
            min-width: 70px;
        }

        .clock-segment .value {
            font-size: 28px;
            font-weight: 700;
            color: white;
            font-family: 'Courier New', monospace;
            background: linear-gradient(135deg, #667eea, #f093fb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .clock-segment .label {
            font-size: 10px;
            color: rgba(255,255,255,0.4);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 4px;
        }

        .alert {
            padding: 16px 20px;
            border-radius: 16px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            font-size: 14px;
            font-weight: 500;
            animation: alertSlide 0.5s ease-out;
            position: relative;
            z-index: 1;
        }

        @keyframes alertSlide {
            from { opacity: 0; transform: translateY(-20px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #86efac;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 22px;
            position: relative;
            z-index: 1;
        }

        .form-group {
            position: relative;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: rgba(255,255,255,0.7);
            margin-bottom: 10px;
            letter-spacing: 0.5px;
            text-align: left;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 20px;
            z-index: 2;
            pointer-events: none;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 18px 20px 18px 55px;
            background: rgba(255,255,255,0.05);
            border: 2px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            font-size: 15px;
            color: white;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
            outline: none;
        }

        .form-group input::placeholder {
            color: rgba(255,255,255,0.3);
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.1);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15);
        }

        .form-group select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23667eea' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 20px center;
            padding-right: 45px;
        }

        .form-group select option {
            background: #1a1a2e;
            color: white;
            padding: 15px;
        }

        .password-toggle {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(255,255,255,0.4);
            cursor: pointer;
            font-size: 18px;
            transition: all 0.3s;
            padding: 5px;
            z-index: 3;
        }

        .password-toggle:hover {
            color: #667eea;
        }

        .btn-submit {
            width: 100%;
            padding: 20px;
            border: none;
            border-radius: 16px;
            font-size: 16px;
            font-weight: 700;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            margin-top: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 10px 40px rgba(102, 126, 234, 0.4);
            letter-spacing: 0.5px;
        }

        .btn-submit::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.6s;
        }

        .btn-submit:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 60px rgba(102, 126, 234, 0.5);
        }

        .btn-submit:hover::before {
            left: 100%;
        }

        .btn-submit .btn-text {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-submit.loading {
            pointer-events: none;
        }

        .btn-submit.loading .btn-text {
            opacity: 0;
        }

        .btn-submit.loading::after {
            content: '';
            position: absolute;
            width: 24px;
            height: 24px;
            border: 3px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .login-footer {
            margin-top: 30px;
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .forgot-password {
            color: rgba(255,255,255,0.5);
            font-size: 13px;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            justify-content: center;
        }

        .forgot-password:hover {
            color: #667eea;
        }

        /* Mobile Developer Section - Hidden on Desktop */
        .mobile-dev-section {
            display: none;
        }

        /* Brand Section - LEFT SIDE */
        .brand-section {
            flex: 1;
            max-width: 600px;
            order: 1;
            color: white;
            animation: slideInLeft 1s ease-out;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-100px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(100px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .brand-logo {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #667eea, #764ba2, #f093fb);
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            margin: 0 auto 30px;
            box-shadow: 0 20px 60px rgba(102, 126, 234, 0.5);
            animation: logoFloat 6s ease-in-out infinite;
            position: relative;
            overflow: hidden;
        }

        .brand-logo::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: logoShine 3s ease-in-out infinite;
        }

        @keyframes logoShine {
            0% { transform: translateX(-100%) rotate(45deg); }
            100% { transform: translateX(100%) rotate(45deg); }
        }

        @keyframes logoFloat {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-10px) rotate(2deg); }
        }

        .brand-title {
            font-size: 56px;
            font-weight: 900;
            line-height: 1.1;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #fff, #a8edea, #fed6e3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-align: center;
        }

        .brand-subtitle {
            font-size: 18px;
            color: rgba(255,255,255,0.7);
            margin-bottom: 40px;
            line-height: 1.6;
            text-align: center;
        }

        .feature-cards {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            width: 100%;
            max-width: 600px;
        }

        .feature-card {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            padding: 18px 22px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            backdrop-filter: blur(10px);
            transition: all 0.4s ease;
        }

        .feature-card:hover {
            background: rgba(255,255,255,0.1);
            transform: translateY(-5px);
            border-color: rgba(102, 126, 234, 0.5);
        }

        .feature-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }

        .feature-icon.purple { background: linear-gradient(135deg, #667eea, #764ba2); }
        .feature-icon.pink { background: linear-gradient(135deg, #f093fb, #f5576c); }
        .feature-icon.blue { background: linear-gradient(135deg, #4facfe, #00f2fe); }
        .feature-icon.orange { background: linear-gradient(135deg, #fa709a, #fee140); }

        .feature-text {
            text-align: left;
            flex: 1;
        }

        .feature-text h4 {
            color: white;
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 3px;
        }

        .feature-text p {
            color: rgba(255,255,255,0.5);
            font-size: 12px;
        }

        .feature-text a {
            color: rgba(255,255,255,0.5);
            text-decoration: none;
            transition: color 0.3s;
        }

        .feature-text a:hover {
            color: #667eea;
        }

        .divider {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin: 30px 0 25px;
            color: rgba(255,255,255,0.3);
            font-size: 12px;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        }

        .dev-section {
            padding: 25px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.08), rgba(118, 75, 162, 0.08));
            border-radius: 24px;
            border: 1px solid rgba(255,255,255,0.1);
            position: relative;
            overflow: hidden;
            width: 100%;
        }

        .dev-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb, #4facfe);
            background-size: 200% 100%;
            animation: borderGradient 3s linear infinite;
        }

        .dev-title {
            font-size: 11px;
            color: rgba(255,255,255,0.5);
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 18px;
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .dev-title::before,
        .dev-title::after {
            content: '';
            width: 30px;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3));
        }

        .dev-title::after {
            background: linear-gradient(90deg, rgba(255,255,255,0.3), transparent);
        }

        .company-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 16px 32px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.2), rgba(118, 75, 162, 0.2));
            border-radius: 50px;
            color: white;
            text-decoration: none;
            font-size: 15px;
            font-weight: 700;
            margin: 0 auto 20px;
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.2);
            width: fit-content;
        }

        .company-badge:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
        }

        .company-badge .company-icon {
            font-size: 22px;
        }

        .company-badge .company-name {
            background: linear-gradient(135deg, #fff, #a8edea, #fed6e3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .dev-links {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            position: relative;
            z-index: 1;
        }

        .dev-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 22px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .dev-link:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.2), rgba(118, 75, 162, 0.2));
            border-color: rgba(102, 126, 234, 0.4);
            color: white;
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.25);
        }

        .dev-link .dev-icon {
            font-size: 18px;
        }

        .dev-info {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .dev-link .dev-role {
            font-size: 9px;
            color: rgba(255,255,255,0.4);
            text-transform: uppercase;
            letter-spacing: 1px;
            display: block;
            margin-top: 2px;
        }

        .dev-link:hover .dev-role {
            color: rgba(255,255,255,0.6);
        }

        /* Audio Control */
        .audio-indicator {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 20;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 10px 40px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
            border: 2px solid rgba(255,255,255,0.2);
        }

        .audio-indicator:hover {
            transform: scale(1.1);
            box-shadow: 0 15px 50px rgba(102, 126, 234, 0.6);
        }

        .audio-indicator.playing {
            animation: audioPulse 0.8s ease-in-out infinite;
        }

        @keyframes audioPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .audio-icon {
            font-size: 28px;
        }

        /* MOBILE RESPONSIVE - Brand TOP, Login MIDDLE, Dev Info BOTTOM */
        @media (max-width: 768px) {
            .main-wrapper {
                flex-direction: column;
                gap: 25px;
                padding: 20px;
            }

            .brand-section {
                order: 1;
                max-width: 100%;
                padding: 0;
            }

            .login-container {
                order: 2;
                max-width: 100%;
            }

            /* Show only logo and title on top */
            .brand-logo {
                width: 70px;
                height: 70px;
                font-size: 35px;
                margin-bottom: 12px;
            }

            .brand-title {
                font-size: 28px;
                margin-bottom: 8px;
            }

            .brand-subtitle {
                font-size: 13px;
                margin-bottom: 0;
            }

            /* Hide features and desktop dev section on mobile */
            .feature-cards,
            .brand-section .divider,
            .brand-section .dev-section {
                display: none;
            }

            /* Show mobile dev section inside login card */
            .mobile-dev-section {
                display: block;
                margin-top: 25px;
            }

            .mobile-dev-section .divider {
                margin: 20px 0 15px;
            }

            .mobile-dev-section .dev-section {
                padding: 20px;
                margin-top: 0;
            }

            .mobile-dev-section .dev-title {
                font-size: 10px;
                margin-bottom: 12px;
            }

            .mobile-dev-section .company-badge {
                padding: 12px 24px;
                font-size: 13px;
                margin-bottom: 15px;
            }

            .mobile-dev-section .company-badge .company-icon {
                font-size: 18px;
            }

            .mobile-dev-section .dev-links {
                flex-direction: column;
                gap: 10px;
            }

            .mobile-dev-section .dev-link {
                width: 100%;
                justify-content: center;
                padding: 12px 18px;
                font-size: 12px;
            }

            .mobile-dev-section .dev-link .dev-icon {
                font-size: 16px;
            }

            .login-card {
                padding: 35px 28px;
            }

            .clock-segment {
                padding: 10px 14px;
                min-width: 60px;
            }

            .clock-segment .value {
                font-size: 20px;
            }

            .form-group input,
            .form-group select {
                padding: 15px 16px 15px 48px;
                font-size: 14px;
            }

            .btn-submit {
                padding: 17px;
                font-size: 15px;
            }

            .audio-indicator {
                bottom: 20px;
                right: 20px;
                width: 50px;
                height: 50px;
            }

            .audio-icon {
                font-size: 24px;
            }
        }

        @media (max-width: 480px) {
            .main-wrapper {
                gap: 20px;
                padding: 15px 12px;
            }

            /* Show only logo and title on top */
            .brand-logo {
                width: 60px;
                height: 60px;
                font-size: 30px;
                margin-bottom: 10px;
            }

            .brand-title {
                font-size: 24px;
                margin-bottom: 6px;
            }

            .brand-subtitle {
                font-size: 11px;
                margin-bottom: 0;
            }

            /* Hide features but show developer section */
            .feature-cards {
                display: none;
            }

            .divider {
                margin: 18px 0 12px;
                font-size: 11px;
            }

            .dev-section {
                padding: 16px;
                border-radius: 18px;
            }

            .dev-title {
                font-size: 9px;
                letter-spacing: 2px;
                margin-bottom: 10px;
            }

            .company-badge {
                padding: 10px 20px;
                font-size: 12px;
                margin-bottom: 12px;
            }

            .company-badge .company-icon {
                font-size: 16px;
            }

            .dev-links {
                flex-direction: column;
                gap: 8px;
            }

            .dev-link {
                width: 100%;
                justify-content: center;
                padding: 10px 16px;
                font-size: 11px;
            }

            .dev-link .dev-icon {
                font-size: 14px;
            }

            .dev-link .dev-role {
                font-size: 8px;
            }

            .login-card {
                padding: 28px 18px;
            }

            .login-header h2 {
                font-size: 22px;
            }

            .clock-segment {
                padding: 8px 12px;
                min-width: 55px;
            }

            .clock-segment .value {
                font-size: 18px;
            }

            .form-group input,
            .form-group select {
                padding: 14px 14px 14px 45px;
                font-size: 13px;
            }

            .btn-submit {
                padding: 16px;
                font-size: 14px;
            }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.05);
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <!-- Background Elements -->
    <div class="mesh-gradient"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <div class="grid-lines"></div>
    <div class="stars" id="stars"></div>

    <div class="audio-indicator" id="audioIndicator" title="Click to replay welcome message">
        <span class="audio-icon">🔊</span>
    </div>

    <div class="main-wrapper">
        <!-- Brand Section - LEFT SIDE (Desktop) / TOP (Mobile - Logo Only) -->
        <div class="brand-section">
            <div class="brand-logo">🎓</div>
            <h1 class="brand-title">NIT AMMS</h1>
            <p class="brand-subtitle">
                Nagpur Institute Of Technology<br>
                Asset and Maintenance Management System
            </p>
            
            <div class="feature-cards">
                <div class="feature-card">
                    <div class="feature-icon purple">📊</div>
                    <div class="feature-text">
                        <h4>Real-time Analytics</h4>
                        <p>Track attendance instantly</p>
                    </div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon pink">🔔</div>
                    <div class="feature-text">
                        <h4>Smart Notifications</h4>
                        <p>Automated alerts & reminders</p>
                    </div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon blue">📱</div>
                    <div class="feature-text">
                        <h4>Mobile Friendly</h4>
                        <p>Access from any device</p>
                    </div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon orange">🔒</div>
                    <div class="feature-text">
                        <h4>Secure Access</h4>
                        <a href="superadmin/index.php"><p>Super Admin</p></a>
                    </div>
                </div>
            </div>

            <div class="divider">Powered By</div>

            <div class="dev-section">
                <p class="dev-title">💻 Design & Development</p>
                <a href="https://himanshufullstackdeveloper.github.io/techyugsoftware/" target="_blank" class="company-badge">
                    <span class="company-icon">🏢</span>
                    <span class="company-name">Techyug Software Pvt. Ltd.</span>
                </a>
                <div class="dev-links">
                    <a href="https://himanshufullstackdeveloper.github.io/portfoilohimanshu/" target="_blank" class="dev-link">
                        <span class="dev-icon">✨</span>
                        <span class="dev-info">
                            <span class="dev-name">Himanshu Patil</span>
                            <span class="dev-role">Full Stack Developer</span>
                        </span>
                    </a>
                    <a href="https://devpranaypanore.github.io/Pranaypanore-live-.html/" target="_blank" class="dev-link">
                        <span class="dev-icon">🚀</span>
                        <span class="dev-info">
                            <span class="dev-name">Pranay Panore</span>
                            <span class="dev-role">Software Engineer</span>
                        </span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Login Section - RIGHT SIDE (Desktop) / MIDDLE (Mobile) -->
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <h2>Welcome Back</h2>
                    <p>Sign in to continue to your dashboard</p>
                </div>

                <!-- Live Clock -->
                <div class="live-clock">
                    <div class="clock-segment">
                        <div class="value" id="hours">00</div>
                        <div class="label">Hours</div>
                    </div>
                    <div class="clock-segment">
                        <div class="value" id="minutes">00</div>
                        <div class="label">Minutes</div>
                    </div>
                    <div class="clock-segment">
                        <div class="value" id="seconds">00</div>
                        <div class="label">Seconds</div>
                    </div>
                </div>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-error">
                        <span>❌</span>
                        <?php 
                            if ($_GET['error'] === 'invalid') {
                                echo "Invalid username or password!";
                            } elseif ($_GET['error'] === 'unauthorized') {
                                echo "Unauthorized access!";
                            } elseif ($_GET['error'] === 'inactive') {
                                echo "Your account is inactive. Contact admin.";
                            }
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['success']) && $_GET['success'] === 'logout'): ?>
                    <div class="alert alert-success">
                        <span>✅</span>
                        Logged out successfully!
                    </div>
                <?php endif; ?>

                <form action="login_process.php" method="POST" class="login-form" id="loginForm">
                    <div class="form-group">
                        <label>Login As</label>
                        <div class="input-wrapper">
                            <select name="role" id="role" required>
                                <option value="">-- Select Role --</option>
                                <option value="admin">👨‍💼 Admin</option>
                                <option value="hod">👔 HOD</option>
                                <option value="teacher">👨‍🏫 Teacher</option>
                                <option value="student">👨‍🎓 Student</option>
                                <option value="parent">👨‍👩‍👦 Parent</option>
                            </select>
                            <span class="input-icon">👤</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Username / Roll Number / Email</label>
                        <div class="input-wrapper">
                            <input type="text" name="username" id="username" placeholder="Enter your username" required>
                            <span class="input-icon">📧</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <div class="input-wrapper">
                            <input type="password" name="password" id="password" placeholder="Enter your password" required>
                            <span class="input-icon">🔑</span>
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <span id="eyeIcon">👁️</span>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit" id="submitBtn">
                        <span class="btn-text">🔐 Sign In</span>
                    </button>
                </form>

                <div class="login-footer">
                    <a href="#" class="forgot-password">
                        <span>📧</span> Forgot password? Contact administrator
                    </a>
                </div>

                <!-- Mobile Developer Section - Shows AFTER login form on mobile only -->
                <div class="mobile-dev-section">
                    <div class="divider">Powered By</div>
                    <div class="dev-section">
                        <p class="dev-title">💻 Design & Development</p>
                        <a href="https://himanshufullstackdeveloper.github.io/techyugsoftware/" target="_blank" class="company-badge">
                            <span class="company-icon">🏢</span>
                            <span class="company-name">Techyug Software Pvt. Ltd.</span>
                        </a>
                        <div class="dev-links">
                            <a href="https://himanshufullstackdeveloper.github.io/portfoilohimanshu/" target="_blank" class="dev-link">
                                <span class="dev-icon">✨</span>
                                <span class="dev-info">
                                    <span class="dev-name">Himanshu Patil</span>
                                    <span class="dev-role">Full Stack Developer</span>
                                </span>
                            </a>
                            <a href="https://devpranaypanore.github.io/Pranaypanore-live-.html/" target="_blank" class="dev-link">
                                <span class="dev-icon">🚀</span>
                                <span class="dev-info">
                                    <span class="dev-name">Pranay Panore</span>
                                    <span class="dev-role">Software Engineer</span>
                                </span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // AI Voice Welcome Message
        function playWelcomeMessage() {
            const messages = [
                "Welcome to NIT AMMS - Nagpur Institute Of Technology Asset and Maintenance Management System. Please sign in to continue.",
                "Hello! Welcome to our secure attendance portal. Please enter your credentials to login.",
                "Greetings! This is the Nagpur Institute of Technology Attendance Management System. Sign in now.",
                "NIT AMMS - Design and Development by Techyug Software - Himanshu Patil and Pranay Panore",
                "Welcome! Access your attendance records and management dashboard by signing in."
            ];
            
            const randomMessage = messages[Math.floor(Math.random() * messages.length)];
            
            if ('speechSynthesis' in window) {
                speechSynthesis.cancel();
                
                const utterance = new SpeechSynthesisUtterance(randomMessage);
                utterance.rate = 1;
                utterance.pitch = 1;
                utterance.volume = 1;
                
                const voices = window.speechSynthesis.getVoices();
                if (voices.length > 0) {
                    const englishVoice = voices.find(voice => voice.lang.includes('en'));
                    utterance.voice = englishVoice || voices[0];
                }
                
                const audioIndicator = document.getElementById('audioIndicator');
                if (audioIndicator) {
                    audioIndicator.classList.add('playing');
                }
                
                utterance.onend = function() {
                    if (audioIndicator) {
                        audioIndicator.classList.remove('playing');
                    }
                };
                
                speechSynthesis.speak(utterance);
            }
        }

        // Create Stars
        function createStars() {
            const starsContainer = document.getElementById('stars');
            const starCount = 80;

            for (let i = 0; i < starCount; i++) {
                const star = document.createElement('div');
                star.className = 'star';
                
                const size = Math.random() * 2 + 0.5;
                star.style.width = size + 'px';
                star.style.height = size + 'px';
                star.style.left = Math.random() * 100 + '%';
                star.style.top = Math.random() * 100 + '%';
                star.style.animationDelay = Math.random() * 3 + 's';
                
                starsContainer.appendChild(star);
            }
        }

        // Live Clock
        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');

            document.getElementById('hours').textContent = hours;
            document.getElementById('minutes').textContent = minutes;
            document.getElementById('seconds').textContent = seconds;
        }

        // Password Toggle
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                eyeIcon.textContent = '👁️';
            }
        }

        // Form Submit Loading
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.classList.add('loading');
        });

        // Initialize
        createStars();
        updateClock();
        setInterval(updateClock, 1000);
        
        // Play welcome message when page loads
        window.addEventListener('load', function() {
            window.speechSynthesis.onvoiceschanged = function() {
                setTimeout(playWelcomeMessage, 500);
            };
            setTimeout(playWelcomeMessage, 1000);
        });

        // Audio indicator click handler
        document.getElementById('audioIndicator').addEventListener('click', function(e) {
            e.preventDefault();
            playWelcomeMessage();
        });

        // Parallax Effect on Mouse Move (Desktop only)
        if (window.innerWidth > 768) {
            document.addEventListener('mousemove', function(e) {
                const orbs = document.querySelectorAll('.orb');
                const moveX = (e.clientX - window.innerWidth / 2) * 0.02;
                const moveY = (e.clientY - window.innerHeight / 2) * 0.02;

                orbs.forEach((orb, index) => {
                    const factor = (index + 1) * 0.5;
                    orb.style.transform = `translate(${moveX * factor}px, ${moveY * factor}px)`;
                });
            });
        }
    </script>
</body>
</html>