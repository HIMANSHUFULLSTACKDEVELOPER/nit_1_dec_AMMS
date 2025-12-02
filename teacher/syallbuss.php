<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RTMNU B.Tech IT - Interactive Syllabus</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
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

        /* Header */
        .header {
            background: rgba(26, 31, 58, 0.95);
            backdrop-filter: blur(20px);
            padding: 30px 50px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            border-bottom: 3px solid rgba(255, 255, 255, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            animation: slideDown 0.6s ease;
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

        .header-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 25px;
            flex-wrap: wrap;
        }

        .university-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
            animation: rotateLogo 10s linear infinite;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.5);
        }

        @keyframes rotateLogo {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

        .header-text h1 {
            color: white;
            font-size: 32px;
            font-weight: 800;
            text-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            margin-bottom: 8px;
        }

        .header-text p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 16px;
            font-weight: 500;
        }

        /* Main Container */
        .container {
            max-width: 1400px;
            margin: 50px auto;
            padding: 0 30px;
            position: relative;
            z-index: 1;
        }

        /* Welcome Card */
        .welcome-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            padding: 50px;
            margin-bottom: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.5);
            animation: fadeInUp 0.8s ease;
            text-align: center;
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

        .welcome-card h2 {
            font-size: 36px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 15px;
            font-weight: 800;
        }

        .welcome-card p {
            color: #666;
            font-size: 18px;
            line-height: 1.6;
        }

        /* Branch Selection */
        .branch-selection {
            display: none;
            animation: fadeInUp 0.6s ease;
        }

        .branch-selection.active {
            display: block;
        }

        .branch-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .branch-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 40px 30px;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            border: 2px solid rgba(255, 255, 255, 0.5);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .branch-card::before {
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

        .branch-card:hover {
            transform: translateY(-15px) scale(1.03);
            box-shadow: 0 25px 60px rgba(102, 126, 234, 0.5);
        }

        .branch-icon {
            width: 90px;
            height: 90px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 45px;
            color: white;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        .branch-card h3 {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .branch-card p {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .branch-badge {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        /* Navigation Buttons */
        .navigation {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }

        .nav-btn {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: none;
            padding: 40px 30px;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            border: 2px solid rgba(255, 255, 255, 0.5);
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.8s ease;
            animation-fill-mode: both;
        }

        .nav-btn:nth-child(1) {
            animation-delay: 0.1s;
        }

        .nav-btn:nth-child(2) {
            animation-delay: 0.2s;
        }

        .nav-btn:nth-child(3) {
            animation-delay: 0.3s;
        }

        .nav-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.5s;
        }

        .nav-btn:hover::before {
            left: 100%;
        }

        .nav-btn:hover {
            transform: translateY(-15px) scale(1.03);
            box-shadow: 0 25px 60px rgba(102, 126, 234, 0.5);
        }

        .nav-btn-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
        }

        .nav-btn h3 {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .nav-btn p {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }

        /* Semester Selection */
        .semester-selection {
            display: none;
            animation: fadeInUp 0.6s ease;
        }

        .semester-selection.active {
            display: block;
        }

        .back-btn {
            background: rgba(255, 255, 255, 0.95);
            border: none;
            padding: 15px 30px;
            border-radius: 15px;
            cursor: pointer;
            margin-bottom: 30px;
            font-size: 16px;
            font-weight: 600;
            color: #667eea;
            transition: all 0.3s;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .back-btn:hover {
            transform: translateX(-5px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        /* Dashboard Button */
        .dashboard-btn {
            position: fixed;
            top: 120px;
            right: 30px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            padding: 15px 30px;
            border-radius: 50px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 700;
            color: white;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            display: none;
            align-items: center;
            gap: 10px;
            z-index: 999;
            animation: slideInRight 0.6s ease;
            text-decoration: none;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .dashboard-btn.active {
            display: inline-flex;
        }

        .dashboard-btn:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.6);
        }

        .dashboard-btn .arrow-icon {
            font-size: 20px;
            animation: pulse 2s ease-in-out infinite;
            display: inline-block;
            transition: transform 0.3s;
        }

        .dashboard-btn:hover .arrow-icon {
            transform: translateX(-5px);
        }

        .semester-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
        }

        .semester-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 35px 25px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            border: 2px solid rgba(255, 255, 255, 0.5);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .semester-card::before {
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

        .semester-card:hover {
            transform: translateY(-10px) scale(1.05);
            box-shadow: 0 20px 60px rgba(102, 126, 234, 0.4);
        }

        .semester-icon {
            font-size: 48px;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .semester-card h4 {
            font-size: 22px;
            color: #2c3e50;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .semester-card p {
            color: #666;
            font-size: 14px;
        }

        .credit-badge {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 15px;
            display: inline-block;
        }

        /* Subject Details */
        .subject-details {
            display: none;
            animation: fadeInUp 0.6s ease;
        }

        .subject-details.active {
            display: block;
        }

        .subjects-grid {
            display: grid;
            gap: 25px;
        }

        .subject-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            border: 2px solid rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .subject-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(180deg, #667eea, #764ba2, #f093fb);
            transition: width 0.3s;
        }

        .subject-card:hover::before {
            width: 100%;
            opacity: 0.1;
        }

        .subject-card:hover {
            transform: translateX(10px);
            box-shadow: 0 15px 50px rgba(102, 126, 234, 0.3);
        }

        .subject-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            gap: 15px;
        }

        .subject-info h3 {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .subject-code {
            color: #667eea;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .subject-meta {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .meta-badge {
            padding: 6px 14px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }

        .meta-badge.credits {
            background: #e3f2fd;
            color: #1976d2;
        }

        .meta-badge.exam {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .meta-badge.duration {
            background: #fff3e0;
            color: #e65100;
        }

        .expand-icon {
            font-size: 24px;
            color: #667eea;
            transition: transform 0.3s;
        }

        .subject-card.expanded .expand-icon {
            transform: rotate(180deg);
        }

        .subject-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s ease;
        }

        .subject-card.expanded .subject-content {
            max-height: 2000px;
        }

        .unit {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 15px;
            margin-top: 15px;
            border-left: 4px solid #667eea;
        }

        .unit h4 {
            color: #2c3e50;
            font-size: 16px;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .unit ul {
            padding-left: 20px;
            color: #666;
            font-size: 14px;
            line-height: 1.8;
        }

        .unit li {
            margin-bottom: 5px;
        }

        /* Stats Section */
        .stats-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            padding: 40px;
            margin-top: 40px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.5);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 25px;
            margin-top: 25px;
        }

        .stat-card {
            text-align: center;
            padding: 25px;
            background: #f8f9fa;
            border-radius: 15px;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
        }

        .stat-value {
            font-size: 42px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header {
                padding: 20px 20px;
            }

            .header-text h1 {
                font-size: 22px;
            }

            .university-logo {
                width: 60px;
                height: 60px;
                font-size: 30px;
            }

            .container {
                padding: 0 15px;
                margin: 30px auto;
            }

            .welcome-card {
                padding: 30px 20px;
            }

            .welcome-card h2 {
                font-size: 26px;
            }

            .navigation {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .nav-btn {
                padding: 30px 20px;
            }

            .semester-grid {
                grid-template-columns: 1fr;
            }

            .subject-card {
                padding: 20px;
            }

            .subject-header {
                flex-direction: column;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-btn {
                top: 100px;
                right: 15px;
                padding: 12px 20px;
                font-size: 14px;
            }

            .dashboard-btn i {
                font-size: 16px;
            }
        }

        @media (max-width: 480px) {
            .header-text h1 {
                font-size: 18px;
            }

            .header-text p {
                font-size: 13px;
            }

            .welcome-card h2 {
                font-size: 22px;
            }

            .welcome-card p {
                font-size: 14px;
            }

            .nav-btn-icon {
                width: 60px;
                height: 60px;
                font-size: 30px;
            }

            .nav-btn h3 {
                font-size: 20px;
            }

            .subject-info h3 {
                font-size: 16px;
            }

            .dashboard-btn {
                top: 90px;
                right: 10px;
                padding: 10px 16px;
                font-size: 13px;
            }

            .dashboard-btn i {
                font-size: 14px;
            }

            .dashboard-btn .arrow-icon {
                font-size: 14px;
            }

            .branch-card h3 {
                font-size: 20px;
            }

            .branch-card p {
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Particles -->
    <div class="particles" id="particles"></div>

   

    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="university-logo">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="header-text">
                <h1>RTMNU - B.Tech Information Technology</h1>
                <p>First Year Complete Syllabus (2024-25)</p>
            </div>
        </div>
    </header>
 <!-- Floating Dashboard Button (Always Visible) -->
  
    <!-- Main Container -->
    <div class="container">
        <!-- Welcome Card -->
        <div class="welcome-card">
            <h2>Welcome to Your Academic Journey</h2>
            <p>Explore the comprehensive first-year syllabus for Bachelor of Technology. Select your branch, year and semester to view detailed course information.</p>
        </div>

        <!-- Main Navigation -->
        <div class="navigation" id="mainNav">
            <button class="nav-btn" onclick="showBranches()">
                <div class="nav-btn-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h3>Select Branch</h3>
                <p>Choose Your Stream</p>
                <p>All Engineering Branches</p>
            </button>

            <button class="nav-btn" onclick="showStats()">
                <div class="nav-btn-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <h3>Overview</h3>
                <p>View Statistics</p>
                <p>& Course Summary</p>
            </button>


           <a href="index.php">  <button class="nav-btn". >
                <div class="nav-btn-icon">
                    <i class="fas fa-back"></i>
                    <-
                </div>
               <h3>Back Dashboard</h3>
               
            </button>
            </a>

           
        </div>
 
        <!-- Branch Selection -->
        <div class="branch-selection" id="branchSelection">
            <button class="back-btn" onclick="backToMain()">
                <i class="fas fa-arrow-left"></i> Back to Main
            </button>
            
            <h2 style="text-align: center; color: white; margin-bottom: 30px; font-size: 32px; text-shadow: 0 4px 15px rgba(0,0,0,0.3);">
                <i class="fas fa-university"></i> Select Your Branch
            </h2>
            
            <div class="branch-grid">
                <div class="branch-card" onclick="selectBranch('IT')">
                    <div class="branch-icon">
                        <i class="fas fa-laptop-code"></i>
                    </div>
                    <h3>Information Technology</h3>
                    <p>Modern IT concepts, web development, databases, and software engineering</p>
                    <span class="branch-badge">B.Tech IT</span>
                </div>

                <div class="branch-card" onclick="selectBranch('CSE')">
                    <div class="branch-icon">
                        <i class="fas fa-code"></i>
                    </div>
                    <h3>Computer Science</h3>
                    <p>Algorithms, data structures, AI/ML, and computer systems</p>
                    <span class="branch-badge">B.Tech CSE</span>
                </div>

                <div class="branch-card" onclick="selectBranch('Civil')">
                    <div class="branch-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <h3>Civil Engineering</h3>
                    <p>Structural design, construction, surveying, and infrastructure</p>
                    <span class="branch-badge">B.Tech Civil</span>
                </div>

                <div class="branch-card" onclick="selectBranch('Mechanical')">
                    <div class="branch-icon">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <h3>Mechanical Engineering</h3>
                    <p>Thermodynamics, mechanics, manufacturing, and design</p>
                    <span class="branch-badge">B.Tech Mech</span>
                </div>

                <div class="branch-card" onclick="selectBranch('Electrical')">
                    <div class="branch-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3>Electrical Engineering</h3>
                    <p>Power systems, circuits, control systems, and electronics</p>
                    <span class="branch-badge">B.Tech EE</span>
                </div>

                <div class="branch-card" onclick="selectBranch('Electronics')">
                    <div class="branch-icon">
                        <i class="fas fa-microchip"></i>
                    </div>
                    <h3>Electronics & Comm.</h3>
                    <p>Communication systems, signal processing, and embedded systems</p>
                    <span class="branch-badge">B.Tech ECE</span>
                </div>




                
            </div>
        </div>

        <!-- Semester Selection -->
        <div class="semester-selection" id="semesterSelection">
            <button class="back-btn" onclick="backToBranches()">
                <i class="fas fa-arrow-left"></i> Back to Branches
            </button>
            
            <h2 style="text-align: center; color: white; margin-bottom: 30px; font-size: 32px; text-shadow: 0 4px 15px rgba(0,0,0,0.3);">
                <span id="selectedBranchTitle"></span> - Select Semester
            </h2>
            
            <div class="semester-grid">
                <div class="semester-card" onclick="showSubjects(1)">
                    <div class="semester-icon">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <h4>Semester 1</h4>
                    <p>Foundation Courses</p>
                    <span class="credit-badge">20 Credits</span>
                </div>

                <div class="semester-card" onclick="showSubjects(2)">
                    <div class="semester-icon">
                        <i class="fas fa-code"></i>
                    </div>
                    <h4>Semester 2</h4>
                    <p>Advanced Fundamentals</p>
                    <span class="credit-badge">20 Credits</span>
                </div>
            </div>
        </div>

        <!-- Subject Details -->
        <div class="subject-details" id="subjectDetails">
            <button class="back-btn" onclick="backToSemesters()">
                <i class="fas fa-arrow-left"></i> Back to Semesters
            </button>
            
            <div class="subjects-grid" id="subjectsContainer"></div>
        </div>

        <!-- Stats Section -->
        <div class="stats-section" id="statsSection" style="display: none;">
            <button class="back-btn" onclick="backToMain()">
                <i class="fas fa-arrow-left"></i> Back to Main
            </button>
            
            <h2 style="text-align: center; color: #2c3e50; margin-bottom: 10px; font-size: 28px;">First Year Overview</h2>
            <p style="text-align: center; color: #666; margin-bottom: 30px;">Academic Year 2024-25</p>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value">40</div>
                    <div class="stat-label">Total Credits</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">19</div>
                    <div class="stat-label">Total Subjects</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">25</div>
                    <div class="stat-label">Theory Credits</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">15</div>
                    <div class="stat-label">Practical Credits</div>
                </div>
            </div>
        </div>
    </div>

       <!-- Compact Footer -->
    <div style="background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 50%, #2a3254 100%); position: relative; overflow: hidden;">
        
        <!-- Animated Top Border -->
        <div style="height: 2px; background: linear-gradient(90deg, #4a9eff, #00d4ff, #4a9eff, #00d4ff); background-size: 200% 100%;"></div>
        
        <!-- Main Footer Container -->
        <div style="max-width: 1000px; margin: 0 auto; padding: 30px 20px 20px;">
            
            <!-- Developer Section -->
            <div style="background: rgba(255, 255, 255, 0.03); padding: 20px 20px; border-radius: 15px; border: 1px solid rgba(74, 158, 255, 0.15); text-align: center; box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);">
                
                <!-- Title -->
                <p style="color: #ffffff; font-size: 14px; margin: 0 0 12px; font-weight: 500; letter-spacing: 0.5px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">✨ Designed & Developed by</p>
                
                <!-- Company Link -->
                <a href="https://himanshufullstackdeveloper.github.io/techyugsoftware/" style="display: inline-block; color: #ffffff; font-size: 16px; font-weight: 700; text-decoration: none; padding: 8px 24px; border: 2px solid #4a9eff; border-radius: 30px; background: linear-gradient(135deg, rgba(74, 158, 255, 0.2), rgba(0, 212, 255, 0.2)); box-shadow: 0 3px 12px rgba(74, 158, 255, 0.3); margin-bottom: 15px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
                    🚀 Techyug Software Pvt. Ltd.
                </a>
                
                <!-- Divider -->
                <div style="width: 50%; height: 1px; background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent); margin: 15px auto;"></div>
                
                <!-- Team Label -->
                <p style="color: #888; font-size: 10px; margin: 0 0 12px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">💼 Development Team</p>
                
                <!-- Developer Badges -->
                <div style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; margin-top: 12px;">
                    
                    <!-- Developer 1 -->
                    <a href="https://himanshufullstackdeveloper.github.io/portfoilohimanshu/" style="color: #ffffff; font-size: 13px; text-decoration: none; padding: 8px 16px; background: linear-gradient(135deg, rgba(74, 158, 255, 0.25), rgba(0, 212, 255, 0.25)); border-radius: 20px; border: 1px solid rgba(74, 158, 255, 0.4); display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 3px 10px rgba(74, 158, 255, 0.2); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
                        <span style="font-size: 16px;">👨‍💻</span>
                        <span style="font-weight: 600;">Himanshu Patil</span>
                    </a>
                    
                    <!-- Developer 2 -->
                    <a href="https://devpranaypanore.github.io/Pranaypanore-live-.html/" style="color: #ffffff; font-size: 13px; text-decoration: none; padding: 8px 16px; background: linear-gradient(135deg, rgba(74, 158, 255, 0.25), rgba(0, 212, 255, 0.25)); border-radius: 20px; border: 1px solid rgba(74, 158, 255, 0.4); display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 3px 10px rgba(74, 158, 255, 0.2); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
                        <span style="font-size: 16px;">👨‍💻</span>
                        <span style="font-weight: 600;">Pranay Panore</span>
                    </a>
                </div>
                
                <!-- Role Tags -->
                <div style="margin-top: 15px; display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;">
                    <span style="color: #4a9eff; font-size: 10px; padding: 4px 12px; background: rgba(74, 158, 255, 0.1); border-radius: 12px; border: 1px solid rgba(74, 158, 255, 0.3); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">Full Stack</span>
                    <span style="color: #00d4ff; font-size: 10px; padding: 4px 12px; background: rgba(0, 212, 255, 0.1); border-radius: 12px; border: 1px solid rgba(0, 212, 255, 0.3); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">UI/UX</span>
                    <span style="color: #4a9eff; font-size: 10px; padding: 4px 12px; background: rgba(74, 158, 255, 0.1); border-radius: 12px; border: 1px solid rgba(74, 158, 255, 0.3); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">Database</span>
                </div>
            </div>
            
            <!-- Bottom Section -->
            <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.1); text-align: center;">
                
                <!-- Copyright -->
                <p style="color: #888; font-size: 12px; margin: 0 0 10px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">© 2025 NIT AMMS. All rights reserved.</p>
                
                <!-- Made With Love -->
                <p style="color: #666; font-size: 11px; margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
                    Made with <span style="color: #ff4757; font-size: 14px;">❤️</span> by Techyug Software
                </p>
                
                <!-- Social Links -->
                <div style="margin-top: 15px; display: flex; justify-content: center; gap: 10px;">
                    <a href="#" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; background: rgba(74, 158, 255, 0.1); border: 1px solid rgba(74, 158, 255, 0.3); border-radius: 50%; color: #4a9eff; text-decoration: none; font-size: 14px;">📧</a>
                    <a href="#" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; background: rgba(74, 158, 255, 0.1); border: 1px solid rgba(74, 158, 255, 0.3); border-radius: 50%; color: #4a9eff; text-decoration: none; font-size: 14px;">🌐</a>
                    <a href="#" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; background: rgba(74, 158, 255, 0.1); border: 1px solid rgba(74, 158, 255, 0.3); border-radius: 50%; color: #4a9eff; text-decoration: none; font-size: 14px;">💼</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Create animated particles
        function createParticles() {
            const container = document.getElementById('particles');
            for (let i = 0; i < 30; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.width = Math.random() * 50 + 10 + 'px';
                particle.style.height = particle.style.width;
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 15 + 's';
                particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
                container.appendChild(particle);
            }
        }

        createParticles();

        // Current selected branch
        let currentBranch = 'IT';

        // Branch data mapping
        const branchNames = {
            'IT': 'Information Technology',
            'CSE': 'Computer Science Engineering',
            'Civil': 'Civil Engineering',
            'Mechanical': 'Mechanical Engineering',
            'Electrical': 'Electrical Engineering',
            'Electronics': 'Electronics & Communication'
        };

        // Syllabus Data for IT Branch
        const syllabusData = {
            1: [
                {
                    code: 'BIT1T01',
                    name: 'Essentials of Chemistry',
                    credits: 2,
                    type: 'Theory',
                    exam: 'SEE: 70 | CIE: 30',
                    duration: '3 Hours',
                    units: [
                        {
                            title: 'Unit 1: Battery Technology (6 Hours)',
                            topics: ['Electrochemical & Galvanic Series', 'Electrochemical & Electrolytic cells', 'Battery types - primary, secondary, reserve', 'Lithium-cobalt oxide and metal air batteries', 'Super capacitors: EDLC, pseudo, asymmetric', 'Fuel cells and solar cells']
                        },
                        {
                            title: 'Unit 2: Rare Earth Elements & E-wastes (6 Hours)',
                            topics: ['Rare earth elements properties & applications', 'Lanthanide contraction', 'E-wastes types and risks', 'Recycling methods', 'Twelve principles of Green Chemistry', 'Green Computing']
                        },
                        {
                            title: 'Unit 3: Nanomaterials (6 Hours)',
                            topics: ['Introduction and classification', 'Size dependent properties', 'Synthesis methods - Top down & Bottom-up', 'Carbon nanomaterials: CNT and graphene', 'Applications of nanomaterials']
                        },
                        {
                            title: 'Unit 4: Material Characterization (6 Hours)',
                            topics: ['Electronic Spectroscopy & Beer-Lambert\'s law', 'Infra-Red & NMR spectroscopy', 'Thermal analysis (TGA, DTA, DSC)', 'SEM, TEM, AFM', 'XRD, BET surface area analysis', 'HPLC and Gas Chromatography']
                        }
                    ]
                },
                {
                    code: 'BIT1P01',
                    name: 'Essentials of Chemistry Lab',
                    credits: 1,
                    type: 'Practical',
                    exam: 'CIE: 25 | SEE: 25',
                    duration: 'Lab Sessions',
                    units: [
                        {
                            title: 'Practical Experiments (Any 6 + 1 Virtual)',
                            topics: ['Estimation of Copper (iodometrically)', 'Estimation of Ni by complexometry/gravimetry', 'Fe(II)/(III) estimation by redox titration', 'Beer\'s Law verification', 'Paper chromatography separation', 'Potentiometry titrations', 'Conductometry titrations', 'Virtual Labs on Chromatography & Spectroscopy']
                        }
                    ]
                },
                {
                    code: 'BIT1T02',
                    name: 'Applied Algebra',
                    credits: 3,
                    type: 'Theory',
                    exam: 'SEE: 70 | CIE: 30',
                    duration: '3 Hours',
                    units: [
                        {
                            title: 'Unit 1: Linear Algebra I (7 Hours)',
                            topics: ['Linear dependence of vectors', 'Eigen values and Eigen vectors', 'Reduction to diagonal form', 'Largest Eigen value by iteration method', 'Gaussian elimination', 'LU Decomposition (Crout\'s method)']
                        },
                        {
                            title: 'Unit 2: Linear Algebra II (7 Hours)',
                            topics: ['Vector Space, Subspaces, Basis, Dimension', 'Linear transformation', 'Range Space and Rank, Null Space', 'Rank nullity theorem', 'Inner Product Spaces', 'Positive definite matrices', 'Singular Value Decomposition', 'Gram-Schmidt process']
                        },
                        {
                            title: 'Unit 3: Differential Calculus (7 Hours)',
                            topics: ['Successive differentiation: Leibnitz\'s Rule', 'Taylor\'s and Maclaurin\'s series', 'Indeterminate forms & L\'Hospital\'s Rule', 'Maxima and Minima', 'Continuity and differentiability', 'Rolle\'s theorem, Mean value theorem']
                        },
                        {
                            title: 'Unit 4: Integral Calculus (8 Hours)',
                            topics: ['Beta and Gamma functions', 'Curve Tracing (Cartesian)', 'Applications of definite integrals', 'Length of the curve', 'Area, volume & surface area of revolution']
                        },
                        {
                            title: 'Unit 5: Sequence and Series (7 Hours)',
                            topics: ['Sequence and types', 'Test of convergence of sequences', 'Cauchy sequence', 'Infinite series, power series', 'Alternating series', 'Tests of convergence and absolute convergence']
                        }
                    ]
                },
                {
                    code: 'BIT1T03',
                    name: 'Problem Solving using C',
                    credits: 3,
                    type: 'Theory',
                    exam: 'SEE: 70 | CIE: 30',
                    duration: '3 Hours',
                    units: [
                        {
                            title: 'Unit 1: Introduction to Programming (7 Hours)',
                            topics: ['Importance of C, Basic Structure', 'Constants, Variables, Data Types', 'Character Set, C Tokens, Keywords', 'Input/Output Operations', 'Operators and Expressions', 'Type Conversions, Operator Precedence']
                        },
                        {
                            title: 'Unit 2: Decision Making & Branching (7 Hours)',
                            topics: ['IF Statement variations', 'Nesting of IF...ELSE', 'The ELSE IF Ladder', 'The Switch statement', 'WHILE, DO, FOR loops', 'Jumps in LOOPS']
                        },
                        {
                            title: 'Unit 3: Arrays (7 Hours)',
                            topics: ['One-dimensional Arrays', 'Linear search, Binary search', 'Bubble sort, Selection sort', 'Two-dimensional Arrays', 'Matrix operations']
                        },
                        {
                            title: 'Unit 4: Character Arrays & Pointers (8 Hours)',
                            topics: ['String handling', 'String functions (strlen, strcpy, strcmp, strcat)', 'Two-dimensional character arrays', 'Introduction to Pointers', 'Pointer Expressions', 'Pointers and Arrays']
                        },
                        {
                            title: 'Unit 5: User-defined Functions (7 Hours)',
                            topics: ['Function Definition and Declaration', 'Return Values and Types', 'Function Categories', 'Passing Arrays to Functions', 'Recursion - Factorial, Power, Fibonacci']
                        }
                    ]
                },
                {
                    code: 'BIT1P03',
                    name: 'Problem Solving using C Lab',
                    credits: 1,
                    type: 'Practical',
                    exam: 'CIE: 25 | SEE: 25',
                    duration: 'Lab Sessions',
                    units: [
                        {
                            title: 'Programming Exercises (Any 10)',
                            topics: ['Quadratic equation roots', 'Binary search', 'Calculator using switch', 'Bubble sort', 'Prime number generation', 'Recursive functions', 'GCD & LCM', 'Pointers with arrays', 'Matrix multiplication', 'String operations']
                        }
                    ]
                },
                {
                    code: 'BIT1T04',
                    name: 'Basics of Electronics Engineering',
                    credits: 3,
                    type: 'Theory',
                    exam: 'SEE: 70 | CIE: 30',
                    duration: '3 Hours',
                    units: [
                        {
                            title: 'Unit 1: Electronic Components (7 Hours)',
                            topics: ['Voltage, Current, Resistance', 'Passive Components (R, L, C)', 'Active Components (Diodes, Transistors)', 'Operational Amplifiers', 'Amplifiers and Oscillators']
                        },
                        {
                            title: 'Unit 2: Digital Logic & Circuits (7 Hours)',
                            topics: ['Binary Number System', 'Logic Gates (AND, OR, NOT, XOR)', 'Combinational Circuits', 'Sequential Circuits', 'Flip-Flops and Registers', 'Adders, Multiplexer, Decoder']
                        },
                        {
                            title: 'Unit 3: Microcontrollers (7 Hours)',
                            topics: ['Introduction to Microcontrollers', 'Arduino Platform', 'Interfacing Electronics', 'ADC and DAC Conversion', 'Types of Sensors']
                        },
                        {
                            title: 'Unit 4: Embedded Systems & IoT (8 Hours)',
                            topics: ['Embedded system types', 'Sensor Interfacing', 'Actuators (Motors, LEDs, Relays)', 'Building microcontroller systems', 'IoT system architecture', 'Simple IoT design']
                        },
                        {
                            title: 'Unit 5: Communication Systems (7 Hours)',
                            topics: ['Analog and Digital Communication', 'Serial and Parallel Communication', 'Wireless Communication', 'Cellular Networks, 4G & 5G', 'CDMA Technology', 'Wireless LAN, Bluetooth']
                        }
                    ]
                },
                {
                    code: 'BIT1P04',
                    name: 'Basics of Electronics Lab',
                    credits: 1,
                    type: 'Practical',
                    exam: 'CIE: 50',
                    duration: 'Lab Sessions',
                    units: [
                        {
                            title: 'Electronics Practicals',
                            topics: ['Circuit design and analysis', 'Digital logic implementation', 'Microcontroller programming', 'Sensor interfacing experiments']
                        }
                    ]
                },
                {
                    code: 'BVS1P01',
                    name: 'Web Design Technology',
                    credits: 2,
                    type: 'Practical',
                    exam: 'CIE: 50 | SEE: 50',
                    duration: 'Lab Sessions',
                    units: [
                        {
                            title: 'Module 1-3: HTML & CSS Fundamentals',
                            topics: ['HTML Structure and Elements', 'CSS Selectors and Properties', 'Responsive Web Design', 'Flexbox and Grid Layout']
                        },
                        {
                            title: 'Module 4-6: Advanced Topics',
                            topics: ['Web Typography', 'Images and Multimedia', 'Web Accessibility', 'ARIA Roles and Attributes']
                        },
                        {
                            title: 'Module 7-8: Tools & Development',
                            topics: ['Text Editors and IDEs', 'Version Control with Git', 'Browser Developer Tools', 'CSS Preprocessors, JavaScript Basics', 'Web Hosting and Deployment']
                        }
                    ]
                },
                {
                    code: 'BAE1T01',
                    name: 'Communication Skills',
                    credits: 1,
                    type: 'Theory',
                    exam: 'SEE: 35 | CIE: 15',
                    duration: '3 Hours',
                    units: [
                        {
                            title: 'Unit 1: Grammar (4 Hours)',
                            topics: ['Tenses and types', 'Sentence Types', 'Transformation of Sentences', 'Reported speech']
                        },
                        {
                            title: 'Unit 2: Introduction to Communication (3 Hours)',
                            topics: ['Importance of communication', 'Verbal and non-verbal types', 'Kinesics, Vocalics, Chronemics, Haptics', 'Barriers to communication']
                        },
                        {
                            title: 'Unit 3: Listening & Speaking (4 Hours)',
                            topics: ['Listening Skills: Types and Barriers', 'Public speaking components', 'Overcoming stage fear', 'Do\'s and Don\'ts of Public speaking']
                        },
                        {
                            title: 'Unit 4: Reading & Writing (3 Hours)',
                            topics: ['Reading Skills and Types', 'Comprehending passages', 'Effective writing', 'Paragraph writing, Email etiquettes']
                        }
                    ]
                },
                {
                    code: 'BAE1P01',
                    name: 'Communication Skills Lab',
                    credits: 1,
                    type: 'Practical',
                    exam: 'CIE: 25 | SEE: 25',
                    duration: 'Lab Sessions',
                    units: [
                        {
                            title: 'Practical Exercises (Any 6-8)',
                            topics: ['Barriers to Communication', 'Non-verbal Communication', 'Listening and Reading Skills', 'Speaking and Presentation Skills', 'Group Discussion', 'Interview Techniques']
                        }
                    ]
                }
            ],
            2: [
                {
                    code: 'BIT2T05',
                    name: 'Mathematical Foundation of CS',
                    credits: 3,
                    type: 'Theory',
                    exam: 'SEE: 70 | CIE: 30',
                    duration: '3 Hours',
                    units: [
                        {
                            title: 'Unit 1: Relations and Functions (8 Hours)',
                            topics: ['Relations: Ordered pairs and n-tuples', 'Types of relations', 'Composite relation', 'Transitive closure', 'Partially ordered set, Hasse diagrams', 'Functions and Composition', 'Characteristics function']
                        },
                        {
                            title: 'Unit 2: Set Theory & Fuzzy Logic (7 Hours)',
                            topics: ['Sets: Types and operations', 'Mathematical induction', 'Fuzzy sets and systems', 'Crisp set', 'Fuzzy relations', 'Fuzzy logic vs classical logic']
                        },
                        {
                            title: 'Unit 3: Curve Fitting (7 Hours)',
                            topics: ['Method of Least Squares', 'Fitting straight line y = a+bx', 'Second degree parabola', 'Exponential curves', 'Coefficient of correlation', 'Lines of regression', 'Rank correlation']
                        },
                        {
                            title: 'Unit 4: Algebraic Structures (8 Hours)',
                            topics: ['Algebraic Systems', 'Groups and properties', 'Semi groups, Monoids, Subgroup', 'Lagrange\'s theorem', 'Cosets, Normal Subgroup', 'Homomorphism, Isomorphism']
                        },
                        {
                            title: 'Unit 5: Elementary Combinatorics (7 Hours)',
                            topics: ['Counting techniques', 'Pigeonhole principle', 'Generating functions', 'Recurrence relations', 'Linear Recurrence Relations', 'Inclusion-Exclusion principle']
                        }
                    ]
                },
                {
                    code: 'BIT2P05',
                    name: 'Mathematical Foundation using Python Lab',
                    credits: 1,
                    type: 'Practical',
                    exam: 'CIE: 25 | SEE: 25',
                    duration: 'Lab Sessions',
                    units: [
                        {
                            title: 'Python Programming Exercises',
                            topics: ['Introduction to Python', 'Basic Commands', 'Functions, Relations & Graphs', 'Curve fitting exercises', 'Correlation coefficient', 'Recurrence Relations', 'Lattices and Boolean Algebra', 'Counting techniques']
                        }
                    ]
                },
                {
                    code: 'BIT2T06',
                    name: 'Essentials of Physics',
                    credits: 3,
                    type: 'Theory',
                    exam: 'SEE: 70 | CIE: 30',
                    duration: '3 Hours',
                    units: [
                        {
                            title: 'Unit 1: Quantum Computing (7 Hours)',
                            topics: ['Bits and qubits', 'Quantum entanglement', 'Quantum computers introduction', 'Wave-particle duality', 'De-Broglie Hypothesis, Matter Waves', 'Heisenberg Uncertainty Principle', 'Schrodinger wave equation', 'Infinite potential well']
                        },
                        {
                            title: 'Unit 2: Optical Fiber (7 Hours)',
                            topics: ['Structure of optical fiber', 'Total internal reflection', 'Modes of propagation', 'Graded index profile', 'Numerical aperture', 'Fiber classification', 'Attenuation and dispersion', 'Fiber optic communication']
                        },
                        {
                            title: 'Unit 3: Semiconductor Physics (7 Hours)',
                            topics: ['Band gap classification', 'Conductivity, drift and diffusion', 'Intrinsic and extrinsic semiconductors', 'Diode types: PN, Zener, LED, Tunnel, Photo', 'Transistors', 'Common base, common emitter']
                        },
                        {
                            title: 'Unit 4: Electron Optics (8 Hours)',
                            topics: ['Motion in electric and magnetic fields', 'Bethe\'s law', 'Electrostatic lens', 'CRT and CRO block diagram', 'Trigger circuit, time base circuit', 'CRO Applications']
                        },
                        {
                            title: 'Unit 5: Nanotechnology (7 Hours)',
                            topics: ['Concept of nanotechnology', 'Top-down and bottom-up approach', 'Bulk vs nanomaterials properties', 'Sol gel and ball mill process', 'Zeolite and Graphene', 'Nanotechnology applications']
                        }
                    ]
                },
                {
                    code: 'BIT2P06',
                    name: 'Essentials of Physics Lab',
                    credits: 1,
                    type: 'Practical',
                    exam: 'CIE: 50',
                    duration: 'Lab Sessions',
                    units: [
                        {
                            title: 'Physics Experiments (Minimum 6)',
                            topics: ['Quantum computers introduction', 'Semiconductor energy gap', 'V-I characteristics of diodes', 'Transistor characteristics', 'Hall Effect study', 'Optical fiber NA determination', 'CRO calibration', 'LASER experiments', 'Virtual Labs']
                        }
                    ]
                },
                {
                    code: 'BIT2T07',
                    name: 'Python Programming',
                    credits: 3,
                    type: 'Theory',
                    exam: 'SEE: 70 | CIE: 30',
                    duration: '3 Hours',
                    units: [
                        {
                            title: 'Unit 1: Installation & Data Types (7 Hours)',
                            topics: ['Python Installation (Windows & Ubuntu)', 'Executing Python programs', 'Comments in Python', 'Python character set, Tokens', 'Core Data Types', 'print() and input() functions', 'The eval() function']
                        },
                        {
                            title: 'Unit 2: Operators & Control (7 Hours)',
                            topics: ['Arithmetic and Bitwise Operators', 'Operator precedence', 'Boolean operators and Expressions', 'Decision making statements', 'while and for loops', 'break and continue statements']
                        },
                        {
                            title: 'Unit 3: Functions and Lists (7 Hours)',
                            topics: ['Function syntax and basics', 'Parameters and arguments', 'Local and global scope', 'return statement', 'Recursive functions', 'Lambda functions', 'Creating and accessing Lists', 'List Comprehension', 'List Methods']
                        },
                        {
                            title: 'Unit 4: Tuples, Sets & Dictionaries (8 Hours)',
                            topics: ['Creating and operating on tuples', 'Inbuilt tuple functions', 'Variable length arguments', 'Sets and Set operations', 'Dictionary basics', 'Adding and retrieving values', 'Dictionary methods', 'Nested dictionaries']
                        },
                        {
                            title: 'Unit 5: Files (7 Hours)',
                            topics: ['File Handling basics', 'Opening a file', 'Writing and Reading Text', 'Writing and Reading numbers', 'Appending data', 'seek() function', 'Closing files']
                        }
                    ]
                },
                {
                    code: 'BIT2P07',
                    name: 'Python Programming Lab',
                    credits: 1,
                    type: 'Practical',
                    exam: 'CIE: 25 | SEE: 25',
                    duration: 'Lab Sessions',
                    units: [
                        {
                            title: 'Python Lab Exercises',
                            topics: ['Basic Python programs', 'Control flow exercises', 'List and tuple operations', 'Dictionary implementations', 'File handling programs', 'Function and recursion exercises']
                        }
                    ]
                },
                {
                    code: 'BIT2T08',
                    name: 'Computer Architecture & Organization',
                    credits: 2,
                    type: 'Theory',
                    exam: 'SEE: 70 | CIE: 30',
                    duration: '3 Hours',
                    units: [
                        {
                            title: 'Unit 1: Basic Functional Blocks (6 Hours)',
                            topics: ['Functional blocks of a computer', 'CPU, memory, I/O subsystems', 'Instruction set architecture', 'Registers, instruction execution cycle', 'RTL interpretation', 'Addressing modes', 'Case study of CPU instruction sets']
                        },
                        {
                            title: 'Unit 2: Data Representation (6 Hours)',
                            topics: ['Signed number representation', 'Fixed and floating-point', 'Character representation', 'Integer addition and subtraction', 'Ripple carry, carry look-ahead adder', 'Multiplication algorithms', 'Division techniques', 'Floating point arithmetic']
                        },
                        {
                            title: 'Unit 3: Memory Organization (6 Hours)',
                            topics: ['Memory interleaving', 'Hierarchical memory organization', 'Cache memory concepts', 'Cache size vs block size', 'Mapping functions', 'Replacement algorithms', 'Write policy']
                        },
                        {
                            title: 'Unit 4: Peripheral Devices & I/O (6 Hours)',
                            topics: ['Peripheral devices characteristics', 'Input-output subsystems', 'I/O transfers: program controlled, interrupt, DMA', 'Privileged instructions', 'Software interrupts and exceptions', 'Programs and processes', 'Interrupts in process state transitions']
                        }
                    ]
                },
                {
                    code: 'BSE2P01',
                    name: 'Linux & Shell Programming',
                    credits: 2,
                    type: 'Practical',
                    exam: 'CIE: 50 | SEE: 50',
                    duration: 'Lab Sessions',
                    units: [
                        {
                            title: 'Shell Programming Exercises',
                            topics: ['Learn Linux Commands', 'Shell script to reverse a number', 'Print username as banner', 'List directories script', 'File type checking', 'Using cp command', 'Word length finder', 'Append line to file', 'Login count script', 'User information', 'Mail merging']
                        }
                    ]
                },
                {
                    code: 'BIK2T01',
                    name: 'Indian Knowledge System (IKS)',
                    credits: 2,
                    type: 'Theory',
                    exam: 'SEE: 70 | CIE: 30',
                    duration: '3 Hours',
                    units: [
                        {
                            title: 'Choose ONE from Multiple Options',
                            topics: ['Option A: Consciousness Studies - Psychology and cognitive processes', 'Option B: Art, Culture & Tradition - Vedas, Indian civilization, artistic traditions', 'Option C: Wellness & Yoga - Health, traditional medicines, yoga practices', 'Option D: Ancient Science - Mathematics, Physics, Chemistry & Metallurgy in ancient India']
                        }
                    ]
                },
                {
                    code: 'BCC2P01',
                    name: 'Co-curricular Course-II',
                    credits: 2,
                    type: 'Practical',
                    exam: 'CIE: 100',
                    duration: 'Continuous',
                    units: [
                        {
                            title: 'Co-curricular Activities',
                            topics: ['Refer to CC Basket for subject selection', 'Various skill development courses', 'Internship opportunities', 'Mini projects']
                        }
                    ]
                }
            ]
        };

        // Common subjects for all branches (First year is mostly common)
        const commonFirstYearSubjects = syllabusData;

        // Branch-specific syllabus data (using IT as template for all branches in first year)
        const branchSyllabusData = {
            'IT': syllabusData,
            'CSE': syllabusData, // First year is same for CSE and IT
            'Civil': syllabusData, // First year has common subjects
            'Mechanical': syllabusData, // First year has common subjects
            'Electrical': syllabusData, // First year has common subjects
            'Electronics': syllabusData // First year has common subjects
        };

        // Navigation Functions
        function showBranches() {
            document.getElementById('mainNav').style.display = 'none';
            document.getElementById('branchSelection').classList.add('active');
            document.getElementById('semesterSelection').classList.remove('active');
            document.getElementById('subjectDetails').classList.remove('active');
            document.getElementById('statsSection').style.display = 'none';
        }

        function selectBranch(branch) {
            currentBranch = branch;
            document.getElementById('branchSelection').classList.remove('active');
            document.getElementById('semesterSelection').classList.add('active');
            document.getElementById('selectedBranchTitle').innerHTML = `
                <i class="fas fa-${getBranchIcon(branch)}"></i> ${branchNames[branch]}
            `;
        }

        function getBranchIcon(branch) {
            const icons = {
                'IT': 'laptop-code',
                'CSE': 'code',
                'Civil': 'building',
                'Mechanical': 'cogs',
                'Electrical': 'bolt',
                'Electronics': 'microchip'
            };
            return icons[branch] || 'graduation-cap';
        }

        function showSemesters() {
            document.getElementById('mainNav').style.display = 'none';
            document.getElementById('semesterSelection').classList.add('active');
            document.getElementById('subjectDetails').classList.remove('active');
            document.getElementById('statsSection').style.display = 'none';
            document.getElementById('dashboardBtn').classList.add('active');
        }

        function showSubjects(semester) {
            document.getElementById('semesterSelection').classList.remove('active');
            document.getElementById('subjectDetails').classList.add('active');
            
            const container = document.getElementById('subjectsContainer');
            container.innerHTML = '';
            
            // Get subjects based on current branch and semester
            const subjects = branchSyllabusData[currentBranch][semester];
            
            // Add branch title
            const branchTitle = document.createElement('h2');
            branchTitle.style.cssText = 'text-align: center; color: white; margin-bottom: 30px; font-size: 28px; text-shadow: 0 4px 15px rgba(0,0,0,0.3);';
            branchTitle.innerHTML = `
                <i class="fas fa-${getBranchIcon(currentBranch)}"></i> 
                ${branchNames[currentBranch]} - Semester ${semester}
            `;
            container.appendChild(branchTitle);
            
            subjects.forEach(subject => {
                const card = createSubjectCard(subject);
                container.appendChild(card);
            });
            
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function createSubjectCard(subject) {
            const card = document.createElement('div');
            card.className = 'subject-card';
            
            let unitsHTML = '';
            subject.units.forEach(unit => {
                let topicsHTML = unit.topics.map(topic => `<li>${topic}</li>`).join('');
                unitsHTML += `
                    <div class="unit">
                        <h4>${unit.title}</h4>
                        <ul>${topicsHTML}</ul>
                    </div>
                `;
            });
            
            card.innerHTML = `
                <div class="subject-header">
                    <div class="subject-info">
                        <div class="subject-code">${subject.code}</div>
                        <h3>${subject.name}</h3>
                        <div class="subject-meta">
                            <span class="meta-badge credits"><i class="fas fa-star"></i> ${subject.credits} Credits</span>
                            <span class="meta-badge exam"><i class="fas fa-file-alt"></i> ${subject.exam}</span>
                            <span class="meta-badge duration"><i class="fas fa-clock"></i> ${subject.duration}</span>
                        </div>
                    </div>
                    <i class="fas fa-chevron-down expand-icon"></i>
                </div>
                <div class="subject-content">
                    ${unitsHTML}
                </div>
            `;
            
            card.addEventListener('click', function() {
                this.classList.toggle('expanded');
            });
            
            return card;
        }

        function backToMain() {
            document.getElementById('mainNav').style.display = 'grid';
            document.getElementById('branchSelection').classList.remove('active');
            document.getElementById('semesterSelection').classList.remove('active');
            document.getElementById('subjectDetails').classList.remove('active');
            document.getElementById('statsSection').style.display = 'none';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function backToBranches() {
            document.getElementById('branchSelection').classList.add('active');
            document.getElementById('semesterSelection').classList.remove('active');
        }

        function backToSemesters() {
            document.getElementById('semesterSelection').classList.add('active');
            document.getElementById('subjectDetails').classList.remove('active');
        }

        function showStats() {
            document.getElementById('mainNav').style.display = 'none';
            document.getElementById('branchSelection').classList.remove('active');
            document.getElementById('semesterSelection').classList.remove('active');
            document.getElementById('subjectDetails').classList.remove('active');
            document.getElementById('statsSection').style.display = 'block';
        }

        function downloadSyllabus() {
            alert(`📚 Download ${branchNames[currentBranch]} Syllabus\n\nThis feature would download the complete syllabus PDF for ${branchNames[currentBranch]}. For now, you can use the browser's print function (Ctrl+P) to save this page as PDF!`);
        }
    </script>
</body>
</html>