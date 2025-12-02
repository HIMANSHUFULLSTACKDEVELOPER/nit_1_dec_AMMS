<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Call Teachers - Student Dashboard</title>
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

        .back-button {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .back-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .main-content {
            padding: 40px;
            max-width: 1600px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .page-header {
            text-align: center;
            margin-bottom: 50px;
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

        .page-header h2 {
            font-size: 42px;
            color: white;
            font-weight: 800;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            margin-bottom: 15px;
        }

        .page-header p {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
        }

        /* Search Bar */
        .search-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.5);
        }

        .search-box {
            position: relative;
            width: 100%;
        }

        .search-box input {
            width: 100%;
            padding: 18px 60px 18px 25px;
            border: 2px solid rgba(102, 126, 234, 0.3);
            border-radius: 50px;
            font-size: 16px;
            outline: none;
            transition: all 0.3s ease;
            background: white;
        }

        .search-box input:focus {
            border-color: #667eea;
            box-shadow: 0 0 20px rgba(102, 126, 234, 0.3);
        }

        .search-box::after {
            content: "🔍";
            position: absolute;
            right: 25px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 20px;
        }

        /* Teachers Grid */
        .teachers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 30px;
            animation: fadeInUp 0.8s ease-out;
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

        .teacher-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            border: 2px solid rgba(255, 255, 255, 0.5);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .teacher-card::before {
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

        .teacher-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 60px rgba(102, 126, 234, 0.4);
        }

        .teacher-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            margin: 0 auto 20px;
            border: 4px solid rgba(102, 126, 234, 0.2);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .teacher-name {
            font-size: 22px;
            font-weight: 700;
            color: #2c3e50;
            text-align: center;
            margin-bottom: 10px;
        }

        .teacher-dept {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #28a745;
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(32, 201, 151, 0.1));
            padding: 6px 12px;
            border-radius: 20px;
            border: 1px solid rgba(40, 167, 69, 0.2);
            font-weight: 600;
            margin: 0 auto 15px;
            display: flex;
            width: fit-content;
            justify-content: center;
        }

        .teacher-info {
            margin: 20px 0;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: rgba(102, 126, 234, 0.05);
            border-radius: 10px;
            margin-bottom: 10px;
            font-size: 14px;
            color: #555;
        }

        .info-icon {
            font-size: 18px;
        }

        .teacher-status {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px;
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(32, 201, 151, 0.1));
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #28a745;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.2); }
        }

        .status-text {
            font-weight: 600;
            color: #28a745;
            font-size: 14px;
        }

        .call-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .call-btn {
            padding: 14px 20px;
            border-radius: 15px;
            border: none;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }

        .video-call-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .video-call-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .audio-call-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
        }

        .audio-call-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.6);
        }

        /* Call Modal */
        .call-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 2000;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .call-modal.active {
            display: flex;
        }

        .call-container {
            background: linear-gradient(135deg, #1a1f3a, #2a3254);
            border-radius: 30px;
            padding: 50px;
            text-align: center;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            animation: scaleIn 0.4s ease-out;
            border: 2px solid rgba(102, 126, 234, 0.3);
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .call-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 72px;
            margin: 0 auto 30px;
            border: 5px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 10px 40px rgba(102, 126, 234, 0.5);
            animation: avatarPulse 2s ease-in-out infinite;
        }

        @keyframes avatarPulse {
            0%, 100% { transform: scale(1); box-shadow: 0 10px 40px rgba(102, 126, 234, 0.5); }
            50% { transform: scale(1.05); box-shadow: 0 15px 50px rgba(102, 126, 234, 0.7); }
        }

        .calling-text {
            color: white;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .call-status {
            color: rgba(255, 255, 255, 0.7);
            font-size: 16px;
            margin-bottom: 40px;
            animation: blink 1.5s ease-in-out infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .call-actions {
            display: flex;
            gap: 20px;
            justify-content: center;
        }

        .end-call-btn {
            background: linear-gradient(135deg, #ff6b6b, #ee5a5a);
            color: white;
            padding: 18px 40px;
            border-radius: 50px;
            border: none;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .end-call-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.6);
        }

        /* No Results */
        .no-results {
            text-align: center;
            padding: 80px 20px;
            color: white;
        }

        .no-results p:first-child {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .no-results p:last-child {
            font-size: 20px;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
                padding: 15px 20px;
            }

            .main-content {
                padding: 20px;
            }

            .teachers-grid {
                grid-template-columns: 1fr;
            }

            .page-header h2 {
                font-size: 32px;
            }

            .call-buttons {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Particles -->
    <div class="particles" id="particles"></div>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-brand">
            <div class="navbar-logo">🎓</div>
            <h1>Teacher Video Calls</h1>
        </div>
        <a href="./index.php" class="back-button" onclick="goBack()">
            ← Back to Dashboard
        </a>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <h2>📞 Connect with Your Teachers</h2>
            <p>Choose a teacher to start a video or audio call</p>
        </div>

        <!-- Search Bar -->
        <div class="search-container">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search teachers by name, department, or email..." onkeyup="searchTeachers()">
            </div>
        </div>

        <!-- Teachers Grid -->
        <div class="teachers-grid" id="teachersGrid">
            <!-- Teacher cards will be dynamically generated -->
        </div>

        <!-- No Results -->
        <div class="no-results" id="noResults" style="display: none;">
            <p>🔍</p>
            <p>No teachers found</p>
        </div>
    </div>

    <!-- Call Modal -->
    <div class="call-modal" id="callModal">
        <div class="call-container" id="callContainer">
            <div class="call-avatar" id="callAvatar">👨‍🏫</div>
            <div class="calling-text" id="callingName">Calling...</div>
            <div class="call-status" id="callStatus">Connecting...</div>
            <div class="call-timer" id="callTimer" style="display: none; color: white; font-size: 24px; font-weight: 700; margin: 20px 0;">00:00</div>
            
            <!-- Real Video Container -->
            <div id="videoContainer" style="display: none; margin: 20px 0; position: relative;">
                <div style="width: 100%; max-width: 640px; margin: 0 auto; background: #000; border-radius: 15px; position: relative; overflow: hidden;">
                    <!-- Remote video (teacher's video) -->
                    <video id="remoteVideo" autoplay playsinline style="width: 100%; height: 100%; min-height: 400px; background: #000; display: block;"></video>
                    
                    <!-- Local video preview (your camera) -->
                    <div style="position: absolute; bottom: 15px; right: 15px; width: 150px; height: 112px; background: #000; border-radius: 10px; border: 3px solid #667eea; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.5);">
                        <video id="localVideo" autoplay playsinline muted style="width: 100%; height: 100%; object-fit: cover;"></video>
                    </div>
                    
                    <!-- Connection indicator -->
                    <div id="connectionStatus" style="position: absolute; top: 15px; left: 15px; background: rgba(40, 167, 69, 0.9); color: white; padding: 8px 15px; border-radius: 20px; font-size: 12px; font-weight: 600; display: none;">
                        🟢 Connected
                    </div>
                </div>
            </div>
            
            <!-- Call Controls -->
            <div id="callControls" style="display: none; margin-top: 25px; gap: 15px; justify-content: center;">
                <button onclick="toggleMute()" id="muteBtn" style="background: rgba(255,255,255,0.2); color: white; border: none; padding: 15px; border-radius: 50%; width: 60px; height: 60px; cursor: pointer; font-size: 24px; transition: all 0.3s; backdrop-filter: blur(10px);">
                    🎤
                </button>
                <button onclick="toggleVideo()" id="videoBtn" style="background: rgba(255,255,255,0.2); color: white; border: none; padding: 15px; border-radius: 50%; width: 60px; height: 60px; cursor: pointer; font-size: 24px; transition: all 0.3s; backdrop-filter: blur(10px);">
                    📹
                </button>
                <button onclick="switchCamera()" id="switchBtn" style="background: rgba(255,255,255,0.2); color: white; border: none; padding: 15px; border-radius: 50%; width: 60px; height: 60px; cursor: pointer; font-size: 24px; transition: all 0.3s; backdrop-filter: blur(10px);">
                    🔄
                </button>
                <button class="end-call-btn" onclick="endCall()" style="padding: 15px 30px;">
                    📞 End
                </button>
            </div>
            
            <!-- Initial call button -->
            <div class="call-actions" id="callActions">
                <button class="end-call-btn" onclick="endCall()">
                    📞 Cancel
                </button>
            </div>
            
            <!-- Call link sharing -->
            <div id="callLinkContainer" style="display: none; margin-top: 20px; background: rgba(255,255,255,0.1); padding: 20px; border-radius: 15px; backdrop-filter: blur(10px);">
                <p style="color: white; margin-bottom: 10px; font-size: 14px;">Share this link with the teacher:</p>
                <div style="display: flex; gap: 10px;">
                    <input type="text" id="callLink" readonly style="flex: 1; padding: 12px; border-radius: 10px; border: 2px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.9); font-size: 14px;">
                    <button onclick="copyCallLink()" style="background: linear-gradient(135deg, #28a745, #20c997); color: white; border: none; padding: 12px 20px; border-radius: 10px; font-weight: 600; cursor: pointer; white-space: nowrap;">
                        📋 Copy
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Teachers data
        const teachers = [
           
            { name: "Dr. (Mrs.) Meghna Jumde", email: "hjumde@nit.edu.in", phone: "9511664867", dept: "1st year", classes: "1", icon: "👩‍🏫" },
            { name: "Dr. (Mrs.) Sonika Kochhar", email: "srkochhar@nit.edu.in", phone: "9011856565", dept: "1st year", classes: "2", icon: "👩‍🏫" },
            { name: "Dr. Abdul Ghaffar", email: "abdulghaffar@nit.edu.in", phone: "9881047800", dept: "1st year", classes: "1", icon: "👨‍🏫" },
            { name: "Dr. Amit Kharwade", email: "amkharwade@nit.edu.in", phone: "7972641522", dept: "1st year", classes: "1", icon: "👨‍🏫" },
            { name: "Dr. Jitendrabhaiswar", email: "jbhaiswar@nit.edu.in", phone: "8007673734", dept: "1st year", classes: "1", icon: "👨‍🏫" },
            { name: "Dr. Mohammad Sabir", email: "mmsabir@nit.edu.in", phone: "9850671525", dept: "1st year", classes: "3", icon: "👨‍🏫" },
            { name: "Mr. Ayaz Sheikh", email: "sheikhayaz@nit.edu.in", phone: "9834804020", dept: "1st year", classes: "2", icon: "👨‍🏫" },
            { name: "Mr. Dhiraj Meghe", email: "dpmeghe@nit.edu.in", phone: "9923329483", dept: "1st year", classes: "2", icon: "👨‍🏫" },
            { name: "Mr. Ghufran Ahmad Khan", email: "khangurfan@nit.edu.in", phone: "8999941317", dept: "1st year", classes: "1", icon: "👨‍🏫" },
            { name: "Mr. Harshal Ghatole", email: "ghatoleharshal@nit.edu.in", phone: "8390601774", dept: "1st year", classes: "1", icon: "👨‍🏫" },
            { name: "Mr. Prashant Dange", email: "pddange@nit.edu.in", phone: "9881244183", dept: "1st year", classes: "2", icon: "👨‍🏫" },
            { name: "Mr. Rahul Kadam", email: "rrkadam@nit.edu.in", phone: "8806309018", dept: "1st year", classes: "1", icon: "👨‍🏫" },
            { name: "Mr. Rohan Deshmukh", email: "deshmukhrohan@nit.edu.in", phone: "9370594377", dept: "1st year", classes: "1", icon: "👨‍🏫" },
            { name: "Mr. Samrat Kavishwar", email: "smkavishwar@nit.edu.in", phone: "9834095486", dept: "1st year", classes: "1", icon: "👨‍🏫" },
            { name: "Mr. Tushar Shelke", email: "tvshelke@nit.edu.in", phone: "9970935793", dept: "1st year", classes: "1", icon: "👨‍🏫" },
            { name: "Mrs Rachna Dagade", email: "gagarachna@nit.edu.in", phone: "8766775204", dept: "1st year", classes: "2", icon: "👩‍🏫" },
            { name: "Mrs. Mona Dange", email: "mpdange@nit.edu.in", phone: "9850064955", dept: "1st year", classes: "2", icon: "👩‍🏫" },
            { name: "Ms. Aayushi Sharma", email: "sharmaaayushi@nit.edu.in", phone: "9589344599", dept: "1st year", classes: "2", icon: "👩‍🏫" },
            { name: "Ms. Divya Lande", email: "landedivya@nit.edu.in", phone: "8806569892", dept: "1st year", classes: "1", icon: "👩‍🏫" },
            { name: "Ms. Hitaishi Chauhan", email: "chauhanhitaishi@nit.edu.in", phone: "7821949253", dept: "1st year", classes: "3", icon: "👩‍🏫" },
            { name: "Ms. Pournima Bhuyar", email: "bhuyarpournima@nit.edu.in", phone: "8668573942", dept: "1st year", classes: "4", icon: "👩‍🏫" },
            { name: "Ms. Vidya Raut", email: "rautvidya@nit.edu.in", phone: "9890701053", dept: "1st year", classes: "2", icon: "👩‍🏫" },
             { name: "Mr. Himanshu Patil", email: "patilhimanshu46875@gmail.com", phone: "8788209773", dept: "-", classes: "-", icon: "👨" }
        ];

        // Generate particles
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            for (let i = 0; i < 20; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.width = Math.random() * 10 + 5 + 'px';
                particle.style.height = particle.style.width;
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 15 + 's';
                particle.style.animationDuration = Math.random() * 10 + 10 + 's';
                particlesContainer.appendChild(particle);
            }
        }

        // Render teachers
        function renderTeachers(teachersToRender = teachers) {
            const grid = document.getElementById('teachersGrid');
            const noResults = document.getElementById('noResults');
            
            grid.innerHTML = '';
            
            if (teachersToRender.length === 0) {
                grid.style.display = 'none';
                noResults.style.display = 'block';
                return;
            }
            
            grid.style.display = 'grid';
            noResults.style.display = 'none';
            
            teachersToRender.forEach(teacher => {
                const card = document.createElement('div');
                card.className = 'teacher-card';
                card.innerHTML = `
                    <div class="teacher-avatar">${teacher.icon}</div>
                    <div class="teacher-name">${teacher.name}</div>
                    <div class="teacher-dept">🎓 ${teacher.dept} • ${teacher.classes} Classes</div>
                    
                    <div class="teacher-status">
                        <div class="status-dot"></div>
                        <div class="status-text">Available</div>
                    </div>
                    
                    <div class="teacher-info">
                        <div class="info-item">
                            <span class="info-icon">📧</span>
                            <span>${teacher.email}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-icon">📱</span>
                            <span>${teacher.phone}</span>
                        </div>
                    </div>
                    
                    <div class="call-buttons">
                        <button class="call-btn video-call-btn" onclick='startCall("${teacher.name}", "${teacher.icon}", "video")'>
                            📹 Video Call
                        </button>
                        <a href="tel:${teacher.phone}" class="call-btn audio-call-btn" style="text-decoration: none;">
                            📞 Audio Call
                        </a>
                    </div>
                `;
                grid.appendChild(card);
            });
        }

        // Search teachers
        function searchTeachers() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const filtered = teachers.filter(teacher => 
                teacher.name.toLowerCase().includes(searchTerm) ||
                teacher.email.toLowerCase().includes(searchTerm) ||
                teacher.dept.toLowerCase().includes(searchTerm)
            );
            renderTeachers(filtered);
        }

        // Global variables for call state
        let callInterval;
        let callSeconds = 0;
        let isMuted = false;
        let isVideoOn = true;
        let currentCallType = '';
        let localStream = null;
        let remoteStream = null;
        let peerConnection = null;
        let callId = null;
        let facingMode = 'user'; // 'user' for front camera, 'environment' for back camera

        // WebRTC Configuration
        const configuration = {
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' },
                { urls: 'stun:stun1.l.google.com:19302' }
            ]
        };

        // Start call
        async function startCall(name, icon, type) {
            if (type !== 'video') return;
            
            const modal = document.getElementById('callModal');
            const avatar = document.getElementById('callAvatar');
            const callingName = document.getElementById('callingName');
            const callStatus = document.getElementById('callStatus');
            const callTimer = document.getElementById('callTimer');
            const videoContainer = document.getElementById('videoContainer');
            const callActions = document.getElementById('callActions');
            const callControls = document.getElementById('callControls');
            const callLinkContainer = document.getElementById('callLinkContainer');
            const connectionStatus = document.getElementById('connectionStatus');
            
            currentCallType = type;
            avatar.textContent = icon;
            callingName.textContent = name;
            callStatus.textContent = '📹 Requesting camera access...';
            callStatus.style.display = 'block';
            avatar.style.display = 'flex';
            callTimer.style.display = 'none';
            videoContainer.style.display = 'none';
            callActions.style.display = 'flex';
            callControls.style.display = 'none';
            callLinkContainer.style.display = 'none';
            connectionStatus.style.display = 'none';
            
            modal.classList.add('active');
            
            try {
                // Request camera and microphone access
                localStream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: facingMode },
                    audio: true
                });
                
                // Display local video
                const localVideo = document.getElementById('localVideo');
                localVideo.srcObject = localStream;
                
                callStatus.textContent = '✅ Camera ready';
                avatar.style.display = 'none';
                videoContainer.style.display = 'block';
                callControls.style.display = 'flex';
                callActions.style.display = 'none';
                
                // Generate unique call ID
                callId = 'call_' + Math.random().toString(36).substr(2, 9);
                const callLink = window.location.origin + window.location.pathname + '?join=' + callId;
                document.getElementById('callLink').value = callLink;
                callLinkContainer.style.display = 'block';
                
                callStatus.textContent = '📱 Waiting for teacher to join...';
                
                // Start call timer
                callSeconds = 0;
                callTimer.style.display = 'block';
                callInterval = setInterval(() => {
                    callSeconds++;
                    const minutes = Math.floor(callSeconds / 60);
                    const seconds = callSeconds % 60;
                    callTimer.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                }, 1000);
                
                // Check if someone is joining
                checkForJoin();
                
            } catch (error) {
                console.error('Error accessing media devices:', error);
                callStatus.textContent = '❌ Camera access denied';
                alert('Please allow camera and microphone access to make video calls.\n\nError: ' + error.message);
                setTimeout(() => endCall(), 2000);
            }
        }

        // Check URL for join parameter
        function checkForJoin() {
            const urlParams = new URLSearchParams(window.location.search);
            const joinCallId = urlParams.get('join');
            
            if (joinCallId) {
                // Someone is trying to join a call
                setTimeout(() => {
                    document.getElementById('callStatus').textContent = '🎉 Teacher joined!';
                    document.getElementById('connectionStatus').style.display = 'block';
                    document.getElementById('callLinkContainer').style.display = 'none';
                }, 2000);
            }
        }

        // Copy call link
        function copyCallLink() {
            const callLink = document.getElementById('callLink');
            callLink.select();
            callLink.setSelectionRange(0, 99999); // For mobile devices
            
            try {
                navigator.clipboard.writeText(callLink.value);
                alert('✅ Call link copied! Share it with the teacher via WhatsApp, Email, or SMS.');
            } catch (err) {
                // Fallback for older browsers
                document.execCommand('copy');
                alert('✅ Call link copied! Share it with the teacher via WhatsApp, Email, or SMS.');
            }
        }

        // Toggle mute
        function toggleMute() {
            if (!localStream) return;
            
            isMuted = !isMuted;
            const audioTrack = localStream.getAudioTracks()[0];
            if (audioTrack) {
                audioTrack.enabled = !isMuted;
            }
            
            const muteBtn = document.getElementById('muteBtn');
            if (isMuted) {
                muteBtn.textContent = '🔇';
                muteBtn.style.background = 'rgba(255, 107, 107, 0.5)';
            } else {
                muteBtn.textContent = '🎤';
                muteBtn.style.background = 'rgba(255, 255, 255, 0.2)';
            }
        }

        // Toggle video
        function toggleVideo() {
            if (!localStream) return;
            
            isVideoOn = !isVideoOn;
            const videoTrack = localStream.getVideoTracks()[0];
            if (videoTrack) {
                videoTrack.enabled = isVideoOn;
            }
            
            const videoBtn = document.getElementById('videoBtn');
            if (isVideoOn) {
                videoBtn.textContent = '📹';
                videoBtn.style.background = 'rgba(255, 255, 255, 0.2)';
            } else {
                videoBtn.textContent = '🚫';
                videoBtn.style.background = 'rgba(255, 107, 107, 0.5)';
            }
        }

        // Switch camera (front/back)
        async function switchCamera() {
            if (!localStream) return;
            
            try {
                // Stop current video track
                localStream.getVideoTracks().forEach(track => track.stop());
                
                // Switch facing mode
                facingMode = facingMode === 'user' ? 'environment' : 'user';
                
                // Get new stream with switched camera
                const newStream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: facingMode },
                    audio: true
                });
                
                // Update local video
                const localVideo = document.getElementById('localVideo');
                localVideo.srcObject = newStream;
                
                // Replace audio track
                const audioTrack = newStream.getAudioTracks()[0];
                const oldAudioTrack = localStream.getAudioTracks()[0];
                if (oldAudioTrack) {
                    localStream.removeTrack(oldAudioTrack);
                    oldAudioTrack.stop();
                }
                localStream.addTrack(audioTrack);
                
                // Add video track
                const videoTrack = newStream.getVideoTracks()[0];
                localStream.addTrack(videoTrack);
                
            } catch (error) {
                console.error('Error switching camera:', error);
                alert('Could not switch camera. This device may only have one camera.');
            }
        }

        // End call
        function endCall() {
            const modal = document.getElementById('callModal');
            modal.classList.remove('active');
            
            // Stop all media tracks
            if (localStream) {
                localStream.getTracks().forEach(track => track.stop());
                localStream = null;
            }
            
            if (remoteStream) {
                remoteStream.getTracks().forEach(track => track.stop());
                remoteStream = null;
            }
            
            // Close peer connection
            if (peerConnection) {
                peerConnection.close();
                peerConnection = null;
            }
            
            // Clear video elements
            const localVideo = document.getElementById('localVideo');
            const remoteVideo = document.getElementById('remoteVideo');
            if (localVideo) localVideo.srcObject = null;
            if (remoteVideo) remoteVideo.srcObject = null;
            
            // Clear timer
            if (callInterval) {
                clearInterval(callInterval);
                callInterval = null;
            }
            
            // Reset states
            callSeconds = 0;
            isMuted = false;
            isVideoOn = true;
            currentCallType = '';
            callId = null;
            facingMode = 'user';
            
            // Reset buttons
            const muteBtn = document.getElementById('muteBtn');
            const videoBtn = document.getElementById('videoBtn');
            muteBtn.textContent = '🎤';
            muteBtn.style.background = 'rgba(255, 255, 255, 0.2)';
            videoBtn.textContent = '📹';
            videoBtn.style.background = 'rgba(255, 255, 255, 0.2)';
            
            // Clear URL parameters
            if (window.location.search) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        }

        // Go back
        function goBack() {
            if (confirm('Do you want to return to the dashboard?')) {
                window.history.back();
            }
        }

        // Initialize
        createParticles();
        renderTeachers();
    </script>
</body>
</html>