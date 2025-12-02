<?php
require_once '../db.php';
checkRole(['admin']);

$user = getCurrentUser();

// Get statistics
$stats = [];
$result = $conn->query("SELECT COUNT(*) as count FROM departments");
$stats['departments'] = $result->fetch_assoc()['count'];
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'hod' AND is_active = 1");
$stats['hods'] = $result->fetch_assoc()['count'];
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'teacher' AND is_active = 1");
$stats['teachers'] = $result->fetch_assoc()['count'];
$result = $conn->query("SELECT COUNT(*) as count FROM students WHERE is_active = 1");
$stats['students'] = $result->fetch_assoc()['count'];
$result = $conn->query("SELECT COUNT(*) as count FROM classes");
$stats['classes'] = $result->fetch_assoc()['count'];
$result = $conn->query("SELECT COUNT(*) as count FROM parents");
$stats['parents'] = $result->fetch_assoc()['count'];
$result = $conn->query("SELECT COUNT(*) as count FROM student_attendance WHERE attendance_date = CURDATE()");
$stats['today_attendance'] = $result->fetch_assoc()['count'];
$result = $conn->query("SELECT COUNT(*) as count FROM student_attendance WHERE attendance_date = CURDATE() AND status = 'present'");
$stats['today_present'] = $result->fetch_assoc()['count'];
$result = $conn->query("SELECT COUNT(*) as count FROM student_attendance WHERE attendance_date = CURDATE() AND status = 'absent'");
$stats['today_absent'] = $result->fetch_assoc()['count'];
$stats['attendance_rate'] = $stats['today_attendance'] > 0 ? round(($stats['today_present'] / $stats['today_attendance']) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gemini AI Assistant - NIT AMMS</title>
    <link rel="icon" href="../Nit_logo.png" type="image/svg+xml" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Animated Background */
        .particles { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; pointer-events: none; }
        .particle { position: absolute; background: rgba(255, 255, 255, 0.15); border-radius: 50%; animation: float 15s infinite ease-in-out; }
        @keyframes float { 0%, 100% { transform: translateY(0) rotate(0deg); opacity: 0; } 10% { opacity: 1; } 90% { opacity: 1; } 100% { transform: translateY(-100vh) rotate(360deg); opacity: 0; } }
        
        /* Navbar */
        .navbar { background: rgba(26, 31, 58, 0.95); backdrop-filter: blur(20px); padding: 20px 40px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3); position: sticky; top: 0; z-index: 1000; display: flex; justify-content: space-between; align-items: center; }
        .navbar-brand { display: flex; align-items: center; gap: 15px; }
        .navbar-logo { width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #4285F4, #34A853); display: flex; align-items: center; justify-content: center; font-size: 28px; box-shadow: 0 4px 15px rgba(66, 133, 244, 0.5); animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.05); } }
        .navbar h1 { color: white; font-size: 24px; font-weight: 700; }
        .nav-actions { display: flex; gap: 15px; }
        .btn { padding: 12px 24px; border-radius: 12px; text-decoration: none; font-weight: 600; transition: all 0.3s; display: inline-flex; align-items: center; gap: 10px; border: none; cursor: pointer; }
        .btn-primary { background: linear-gradient(135deg, #667eea, #764ba2); color: white; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6); }
        .btn-danger { background: linear-gradient(135deg, #ff6b6b, #ee5a5a); color: white; box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4); }
        .btn-danger:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(255, 107, 107, 0.6); }

        /* Main Container */
        .container { max-width: 1400px; margin: 0 auto; padding: 40px 20px; position: relative; z-index: 1; }
        
        /* Hero Section */
        .hero-section { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); border-radius: 30px; padding: 50px; margin-bottom: 40px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); text-align: center; position: relative; overflow: hidden; }
        .hero-section::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(135deg, rgba(66, 133, 244, 0.1), rgba(52, 168, 83, 0.1)); z-index: 0; }
        .hero-content { position: relative; z-index: 1; }
        .hero-icon { font-size: 80px; margin-bottom: 20px; animation: bounce 2s infinite; }
        @keyframes bounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-20px); } }
        .hero-title { font-size: 48px; font-weight: 800; background: linear-gradient(135deg, #4285F4, #34A853, #FBBC05, #EA4335); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 15px; }
        .hero-subtitle { font-size: 20px; color: #666; margin-bottom: 30px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px; margin-top: 30px; }
        .stat-item { background: white; padding: 20px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .stat-value { font-size: 32px; font-weight: 700; color: #4285F4; }
        .stat-label { font-size: 13px; color: #666; margin-top: 5px; }

        /* Chat Container */
        .chat-container { display: grid; grid-template-columns: 300px 1fr; gap: 30px; }
        
        /* Sidebar */
        .sidebar { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); border-radius: 25px; padding: 30px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2); height: fit-content; }
        .sidebar h3 { font-size: 20px; margin-bottom: 20px; color: #2c3e50; display: flex; align-items: center; gap: 10px; }
        .feature-list { list-style: none; }
        .feature-list li { padding: 12px 0; color: #666; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #f0f0f0; }
        .feature-list li:last-child { border-bottom: none; }
        .feature-list i { color: #4285F4; }
        .quick-actions { margin-top: 30px; }
        .quick-action-btn { display: block; width: 100%; padding: 12px; margin-bottom: 10px; background: linear-gradient(135deg, #4285F4, #34A853); color: white; border: none; border-radius: 12px; cursor: pointer; font-weight: 600; transition: all 0.3s; }
        .quick-action-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(66, 133, 244, 0.4); }

        /* Chat Main */
        .chat-main { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); border-radius: 25px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2); display: flex; flex-direction: column; height: 700px; }
        
        /* Chat Header */
        .chat-header { background: linear-gradient(135deg, #4285F4, #34A853); padding: 25px 30px; border-radius: 25px 25px 0 0; display: flex; align-items: center; justify-content: space-between; }
        .chat-header-info { display: flex; align-items: center; gap: 15px; }
        .chat-avatar { width: 55px; height: 55px; border-radius: 50%; background: white; display: flex; align-items: center; justify-content: center; font-size: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .chat-header h3 { color: white; font-size: 22px; margin: 0; }
        .chat-header p { color: rgba(255,255,255,0.9); font-size: 14px; margin: 3px 0 0; }
        .chat-controls { display: flex; gap: 10px; }
        .control-btn { background: rgba(255,255,255,0.2); border: none; width: 40px; height: 40px; border-radius: 50%; color: white; cursor: pointer; font-size: 18px; transition: all 0.3s; }
        .control-btn:hover { background: rgba(255,255,255,0.3); transform: scale(1.1); }
        .control-btn.active { background: rgba(255,255,255,0.4); }

        /* Chat Messages */
        .chat-messages { flex: 1; overflow-y: auto; padding: 30px; background: linear-gradient(to bottom, #f8f9fa 0%, #ffffff 100%); }
        .message { margin-bottom: 25px; display: flex; gap: 15px; animation: slideIn 0.3s ease; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .message.user { flex-direction: row-reverse; }
        .message-avatar { width: 42px; height: 42px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .message.ai .message-avatar { background: linear-gradient(135deg, #4285F4, #34A853); color: white; }
        .message.user .message-avatar { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        .message-content { max-width: 70%; padding: 18px 22px; border-radius: 20px; font-size: 15px; line-height: 1.6; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .message.ai .message-content { background: white; border-bottom-left-radius: 5px; border: 1px solid #e9ecef; }
        .message.user .message-content { background: linear-gradient(135deg, #667eea, #764ba2); color: white; border-bottom-right-radius: 5px; }
        .message-content strong { font-weight: 700; }
        .message-content a { color: #4285F4; text-decoration: none; font-weight: 600; }
        .message-content a:hover { text-decoration: underline; }
        .typing-indicator { display: flex; gap: 5px; padding: 18px 22px; }
        .typing-indicator span { width: 10px; height: 10px; background: linear-gradient(135deg, #4285F4, #34A853); border-radius: 50%; animation: typing 1.4s infinite; }
        .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
        .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes typing { 0%, 60%, 100% { transform: translateY(0); opacity: 0.7; } 30% { transform: translateY(-12px); opacity: 1; } }

        /* Chat Input */
        .chat-input-area { padding: 25px 30px; background: white; border-top: 2px solid #f0f2f5; display: flex; gap: 15px; align-items: center; border-radius: 0 0 25px 25px; }
        .voice-wave { display: none; align-items: center; gap: 4px; padding: 10px; }
        .voice-wave.active { display: flex; }
        .voice-bar { width: 4px; background: linear-gradient(135deg, #4285F4, #34A853); border-radius: 2px; animation: wave 1s infinite ease-in-out; }
        .voice-bar:nth-child(1) { height: 10px; animation-delay: 0s; }
        .voice-bar:nth-child(2) { height: 20px; animation-delay: 0.1s; }
        .voice-bar:nth-child(3) { height: 15px; animation-delay: 0.2s; }
        .voice-bar:nth-child(4) { height: 25px; animation-delay: 0.3s; }
        .voice-bar:nth-child(5) { height: 18px; animation-delay: 0.4s; }
        @keyframes wave { 0%, 100% { transform: scaleY(0.5); } 50% { transform: scaleY(1); } }
        .chat-input { flex: 1; padding: 16px 24px; border: 2px solid #e9ecef; border-radius: 30px; font-size: 15px; outline: none; transition: all 0.3s; font-family: inherit; }
        .chat-input:focus { border-color: #4285F4; box-shadow: 0 0 0 3px rgba(66, 133, 244, 0.1); }
        .send-btn { width: 52px; height: 52px; border-radius: 50%; background: linear-gradient(135deg, #4285F4, #34A853); border: none; color: white; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; justify-content: center; font-size: 20px; box-shadow: 0 4px 15px rgba(66, 133, 244, 0.3); }
        .send-btn:hover { transform: scale(1.1); box-shadow: 0 6px 20px rgba(66, 133, 244, 0.5); }
        .send-btn:disabled { opacity: 0.5; cursor: not-allowed; }

        /* Suggestions */
        .suggestions { padding: 20px 30px; background: #f8f9fa; display: flex; gap: 10px; flex-wrap: wrap; border-top: 1px solid #e9ecef; }
        .suggestion-chip { padding: 10px 18px; background: white; border: 2px solid #4285F4; border-radius: 20px; color: #4285F4; cursor: pointer; font-size: 13px; font-weight: 600; transition: all 0.3s; }
        .suggestion-chip:hover { background: #4285F4; color: white; transform: translateY(-2px); }

        /* Responsive */
        @media (max-width: 968px) {
            .chat-container { grid-template-columns: 1fr; }
            .sidebar { display: none; }
            .hero-title { font-size: 32px; }
        }
    </style>
</head>
<body>
    <div class="particles" id="particles"></div>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-brand">
            <div class="navbar-logo">✨</div>
            <h1>Gemini AI Assistant</h1>
        </div>
        <div class="nav-actions">
            <a href="index.php" class="btn btn-primary"><i class="fas fa-home"></i> Dashboard</a>
            <a href="../logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="container">
        <!-- Hero Section -->
        <div class="hero-section">
            <div class="hero-content">
                <div class="hero-icon">🤖</div>
                <h1 class="hero-title">Gemini AI Assistant</h1>
                <p class="hero-subtitle">Ask me anything! I'm powered by Google Gemini AI with voice capabilities</p>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $stats['students']; ?></div>
                        <div class="stat-label">Students</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $stats['teachers']; ?></div>
                        <div class="stat-label">Teachers</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $stats['attendance_rate']; ?>%</div>
                        <div class="stat-label">Attendance</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $stats['departments']; ?></div>
                        <div class="stat-label">Departments</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chat Container -->
        <div class="chat-container">
            <!-- Sidebar -->
            <div class="sidebar">
                <h3><i class="fas fa-info-circle"></i> Features</h3>
                <ul class="feature-list">
                    <li><i class="fas fa-microphone"></i> Voice Input</li>
                    <li><i class="fas fa-volume-up"></i> Voice Output</li>
                    <li><i class="fas fa-robot"></i> AI Powered</li>
                    <li><i class="fas fa-book"></i> System Help</li>
                    <li><i class="fas fa-globe"></i> General Knowledge</li>
                    <li><i class="fas fa-chart-bar"></i> Live Stats</li>
                </ul>
                
                <div class="quick-actions">
                    <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                    <button class="quick-action-btn" onclick="askQuestion('Show me today\'s attendance summary')">
                        <i class="fas fa-calendar-check"></i> Today's Summary
                    </button>
                    <button class="quick-action-btn" onclick="askQuestion('How to add a new student?')">
                        <i class="fas fa-user-plus"></i> Add Student
                    </button>
                    <button class="quick-action-btn" onclick="askQuestion('Explain artificial intelligence')">
                        <i class="fas fa-brain"></i> Learn AI
                    </button>
                    <button class="quick-action-btn" onclick="askQuestion('Tell me a programming joke')">
                        <i class="fas fa-laugh"></i> Tell Joke
                    </button>
                </div>
            </div>

            <!-- Chat Main -->
            <div class="chat-main">
                <div class="chat-header">
                    <div class="chat-header-info">
                        <div class="chat-avatar">✨</div>
                        <div>
                            <h3>Gemini AI</h3>
                            <p>Powered by Google • Voice Enabled</p>
                        </div>
                    </div>
                    <div class="chat-controls">
                        <button class="control-btn" id="voiceBtn" onclick="toggleVoice()" title="Voice Input">
                            <i class="fas fa-microphone"></i>
                        </button>
                        <button class="control-btn active" id="soundBtn" onclick="toggleSound()" title="Voice Output">
                            <i class="fas fa-volume-up"></i>
                        </button>
                        <button class="control-btn" onclick="clearChat()" title="Clear Chat">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>

                <div class="chat-messages" id="chatMessages">
                    <div class="message ai">
                        <div class="message-avatar">✨</div>
                        <div class="message-content">
                            <strong>👋 Hello <?php echo htmlspecialchars($user['full_name']); ?>!</strong><br><br>
                            I'm Gemini AI, your intelligent assistant with voice capabilities! I can help you with:<br><br>
                            <strong>🎯 NIT AMMS System:</strong><br>
                            • Your current stats: <?php echo $stats['students']; ?> students, <?php echo $stats['attendance_rate']; ?>% attendance today<br>
                            • Student, Teacher, HOD & Parent management<br>
                            • Attendance tracking & reports<br><br>
                            <strong>🌍 General Knowledge:</strong><br>
                            • Science, Technology, History<br>
                            • Programming & Coding help<br>
                            • Math problems & calculations<br>
                            • Life advice & tips<br><br>
                            <strong>🎤 Voice Features:</strong><br>
                            • Click 🎤 to speak your question<br>
                            • I'll respond with voice (click 🔊 to toggle)<br><br>
                            Ask me anything!
                        </div>
                    </div>
                </div>

                <div class="suggestions">
                    <div class="suggestion-chip" onclick="askQuestion('What is machine learning?')">What is machine learning?</div>
                    <div class="suggestion-chip" onclick="askQuestion('Show attendance stats')">Show attendance stats</div>
                    <div class="suggestion-chip" onclick="askQuestion('Explain quantum computing')">Explain quantum computing</div>
                    <div class="suggestion-chip" onclick="askQuestion('How to manage time?')">Time management tips</div>
                </div>

                <div class="chat-input-area">
                    <div class="voice-wave" id="voiceWave">
                        <div class="voice-bar"></div>
                        <div class="voice-bar"></div>
                        <div class="voice-bar"></div>
                        <div class="voice-bar"></div>
                        <div class="voice-bar"></div>
                    </div>
                    <input type="text" class="chat-input" id="chatInput" placeholder="Type your question or use voice..." onkeypress="if(event.key==='Enter')sendMessage()">
                    <button class="send-btn" id="sendBtn" onclick="sendMessage()">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // System Stats
        const systemStats = {
            adminName: '<?php echo htmlspecialchars($user['full_name']); ?>',
            students: <?php echo $stats['students']; ?>,
            teachers: <?php echo $stats['teachers']; ?>,
            hods: <?php echo $stats['hods']; ?>,
            departments: <?php echo $stats['departments']; ?>,
            classes: <?php echo $stats['classes']; ?>,
            parents: <?php echo $stats['parents']; ?>,
            todayPresent: <?php echo $stats['today_present']; ?>,
            todayAbsent: <?php echo $stats['today_absent']; ?>,
            attendanceRate: <?php echo $stats['attendance_rate']; ?>
        };

        // Voice Recognition
        let recognition = null;
        let isListening = false;
        let isSpeaking = false;
        let soundEnabled = true;

        // Initialize Speech Recognition
        if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            recognition = new SpeechRecognition();
            recognition.continuous = false;
            recognition.interimResults = false;
            recognition.lang = 'en-US';

            recognition.onstart = () => {
                isListening = true;
                document.getElementById('voiceBtn').classList.add('active');
                document.getElementById('voiceWave').classList.add('active');
                document.getElementById('chatInput').style.display = 'none';
            };

            recognition.onend = () => {
                isListening = false;
                document.getElementById('voiceBtn').classList.remove('active');
                document.getElementById('voiceWave').classList.remove('active');
                document.getElementById('chatInput').style.display = 'block';
            };

            recognition.onresult = (event) => {
                const transcript = event.results[0][0].transcript;
                document.getElementById('chatInput').value = transcript;
                sendMessage();
            };

            recognition.onerror = (event) => {
                console.error('Speech recognition error:', event.error);
                isListening = false;
                document.getElementById('voiceBtn').classList.remove('active');
                document.getElementById('voiceWave').classList.remove('active');
                document.getElementById('chatInput').style.display = 'block';
                
                if (event.error === 'no-speech') {
                    addMessage('ai', '⚠️ No speech detected. Please try again.');
                } else if (event.error === 'not-allowed') {
                    addMessage('ai', '⚠️ Microphone access denied. Please enable it in browser settings.');
                }
            };
        }

        function toggleVoice() {
            if (!recognition) {
                alert('❌ Voice recognition is not supported in your browser.\n\nPlease use:\n• Chrome (Recommended)\n• Edge\n• Safari');
                return;
            }

            if (isListening) {
                recognition.stop();
            } else {
                recognition.start();
            }
        }

        function toggleSound() {
            soundEnabled = !soundEnabled;
            const btn = document.getElementById('soundBtn');
            if (soundEnabled) {
                btn.classList.add('active');
                btn.innerHTML = '<i class="fas fa-volume-up"></i>';
                speak('Voice output enabled');
            } else {
                btn.classList.remove('active');
                btn.innerHTML = '<i class="fas fa-volume-mute"></i>';
                speechSynthesis.cancel();
            }
        }

        function speak(text) {
            if (!soundEnabled || isSpeaking) return;

            // Stop any ongoing speech
            speechSynthesis.cancel();

            // Clean text for speech
            const cleanText = text
                .replace(/<[^>]*>/g, '') // Remove HTML tags
                .replace(/•/g, '') // Remove bullets
                .replace(/[🎯📊👥🏫📈💡✨🤖👋🌍🎤⚠️❌✅]/g, '') // Remove emojis
                .substring(0, 300); // Limit length

            const utterance = new SpeechSynthesisUtterance(cleanText);
            utterance.rate = 1.1;
            utterance.pitch = 1;
            utterance.volume = 1;
            utterance.lang = 'en-US';

            utterance.onstart = () => { 
                isSpeaking = true; 
            };
            utterance.onend = () => { 
                isSpeaking = false; 
            };
            utterance.onerror = () => { 
                isSpeaking = false; 
            };

            speechSynthesis.speak(utterance);
        }

        // Particles
        function createParticles() {
            const p = document.getElementById('particles');
            for (let i = 0; i < 30; i++) {
                const d = document.createElement('div');
                d.className = 'particle';
                d.style.left = Math.random() * 100 + '%';
                d.style.top = Math.random() * 100 + '%';
                d.style.width = d.style.height = Math.random() * 8 + 3 + 'px';
                d.style.animationDelay = Math.random() * 15 + 's';
                d.style.animationDuration = (Math.random() * 10 + 10) + 's';
                p.appendChild(d);
            }
        }
        createParticles();

        // Chat Functions
        function addMessage(type, content) {
            const messagesDiv = document.getElementById('chatMessages');
            const avatar = type === 'ai' ? '✨' : systemStats.adminName.charAt(0).toUpperCase();
            const messageHTML = `
                <div class="message ${type}">
                    <div class="message-avatar">${avatar}</div>
                    <div class="message-content">${content}</div>
                </div>
            `;
            messagesDiv.innerHTML += messageHTML;
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }

        function askQuestion(question) {
            document.getElementById('chatInput').value = question;
            sendMessage();
        }

        async function sendMessage() {
            const input = document.getElementById('chatInput');
            const message = input.value.trim();
            if (!message) return;

            const sendBtn = document.getElementById('sendBtn');
            sendBtn.disabled = true;

            // Add user message
            addMessage('user', message);
            input.value = '';

            // Show typing indicator
            const messagesDiv = document.getElementById('chatMessages');
            messagesDiv.innerHTML += `
                <div class="message ai" id="typingIndicator">
                    <div class="message-avatar">✨</div>
                    <div class="message-content">
                        <div class="typing-indicator">
                            <span></span><span></span><span></span>
                        </div>
                    </div>
                </div>
            `;
            messagesDiv.scrollTop = messagesDiv.scrollHeight;

            // Get AI response
            try {
                const response = await getGeminiResponse(message);
                document.getElementById('typingIndicator')?.remove();
                addMessage('ai', response);
                speak(response);
            } catch (error) {
                document.getElementById('typingIndicator')?.remove();
                addMessage('ai', '⚠️ Sorry, I encountered an error. Please try again.');
            }

            sendBtn.disabled = false;
        }

        async function getGeminiResponse(query) {
            const API_KEY = 'AIzaSyDlghSs0RQ8FM6d3eHqFyGl-OjZ3dS-4Xo';
            const API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

            const systemContext = `You are Gemini AI, an intelligent assistant for NIT AMMS. You're helping ${systemStats.adminName}.

System Stats:
- Students: ${systemStats.students} (${systemStats.todayPresent} present, ${systemStats.todayAbsent} absent)
- Attendance: ${systemStats.attendanceRate}%
- Teachers: ${systemStats.teachers}
- HODs: ${systemStats.hods}
- Departments: ${systemStats.departments}

Answer ANY question - AMMS system, general knowledge, programming, science, life advice, etc.
Format: Use <strong>, <br>, and • bullets. Keep responses 200-400 words, friendly & helpful.`;

            try {
                const response = await fetch(`${API_URL}?key=${API_KEY}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        contents: [{
                            parts: [{ text: `${systemContext}\n\nUser: ${query}\n\nRespond:` }]
                        }],
                        generationConfig: {
                            temperature: 0.9,
                            topK: 40,
                            topP: 0.95,
                            maxOutputTokens: 1024,
                        }
                    })
                });

                if (!response.ok) throw new Error(`API Error: ${response.status}`);

                const data = await response.json();
                if (data.candidates?.[0]?.content?.parts?.[0]?.text) {
                    let aiResponse = data.candidates[0].content.parts[0].text;
                    
                    // Format response
                    aiResponse = aiResponse
                        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                        .replace(/\*(.*?)\*/g, '<em>$1</em>')
                        .replace(/\n\n/g, '<br><br>')
                        .replace(/\n- /g, '<br>• ')
                        .replace(/`(.*?)`/g, '<code>$1</code>');
                    
                    return aiResponse;
                }
                throw new Error('Invalid response');
            } catch (error) {
                console.error('Gemini Error:', error);
                return `⚠️ <strong>Connection Error</strong><br><br>I'm having trouble connecting to Gemini AI. This could be due to:<br>• Network connectivity issues<br>• API rate limits<br>• Server problems<br><br>Please try again in a moment!`;
            }
        }

        function clearChat() {
            if (confirm('Clear chat history?')) {
                document.getElementById('chatMessages').innerHTML = `
                    <div class="message ai">
                        <div class="message-avatar">✨</div>
                        <div class="message-content">
                            <strong>Chat cleared!</strong><br><br>
                            I'm ready to help you again. Ask me anything!
                        </div>
                    </div>
                `;
                speechSynthesis.cancel();
            }
        }

        // Focus input on load
        window.onload = () => {
            document.getElementById('chatInput').focus();
        };
    </script>
</body>
</html>