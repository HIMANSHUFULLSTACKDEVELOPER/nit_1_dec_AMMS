<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News & Updates</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
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

        /* Navigation Bar */
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

        .back-btn {
            padding: 12px 28px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            text-decoration: none;
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        /* Main Content */
        .main-content {
            padding: 40px;
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        /* Header Section */
        .news-header {
            text-align: center;
            margin-bottom: 40px;
            animation: fadeInDown 0.8s ease;
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

        .news-header h2 {
            font-size: 48px;
            color: white;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            margin-bottom: 15px;
            font-weight: 800;
        }

        .news-header p {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.9);
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        /* Category Tabs */
        .category-tabs {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 40px;
            flex-wrap: wrap;
            animation: fadeIn 1s ease 0.2s backwards;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .tab-btn {
            padding: 15px 30px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.5);
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #2c3e50;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .tab-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .tab-btn.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-color: transparent;
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }

        /* Loading Spinner */
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 50px;
        }

        .loading-spinner.active {
            display: block;
        }

        .spinner {
            width: 60px;
            height: 60px;
            border: 5px solid rgba(255, 255, 255, 0.3);
            border-top: 5px solid white;
            border-radius: 50%;
            margin: 0 auto 20px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-text {
            color: white;
            font-size: 18px;
            font-weight: 600;
        }

        /* News Grid */
        .news-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
            animation: fadeInUp 0.8s ease 0.4s backwards;
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

        /* News Card */
        .news-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid rgba(255, 255, 255, 0.5);
            position: relative;
            animation: cardSlideIn 0.5s ease backwards;
        }

        @keyframes cardSlideIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .news-card::before {
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

        .news-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 60px rgba(102, 126, 234, 0.4);
        }

        .news-card.hidden {
            display: none;
        }

        .news-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            position: relative;
            overflow: hidden;
        }

        .news-image::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 10s linear infinite;
        }

        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .news-content {
            padding: 25px;
        }

        .news-category {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 15px;
        }

        .category-sports {
            background: #d4edda;
            color: #155724;
        }

        .category-technology {
            background: #d1ecf1;
            color: #0c5460;
        }

        .category-it {
            background: #fff3cd;
            color: #856404;
        }

        .news-title {
            font-size: 20px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 12px;
            line-height: 1.4;
        }

        .news-description {
            font-size: 14px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .news-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }

        .news-date {
            font-size: 12px;
            color: #999;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .read-more-btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .read-more-btn:hover {
            transform: translateX(5px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .news-grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 25px;
            }
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }

            .navbar h1 {
                font-size: 18px;
            }

            .main-content {
                padding: 20px;
            }

            .news-header h2 {
                font-size: 32px;
            }

            .news-header p {
                font-size: 15px;
            }

            .category-tabs {
                gap: 10px;
            }

            .tab-btn {
                padding: 12px 20px;
                font-size: 14px;
            }

            .news-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .back-btn {
                padding: 10px 20px;
                font-size: 13px;
            }
        }

        @media (max-width: 480px) {
            .navbar {
                padding: 12px;
            }

            .navbar-logo {
                width: 40px;
                height: 40px;
                font-size: 20px;
            }

            .navbar h1 {
                font-size: 16px;
            }

            .main-content {
                padding: 15px;
            }

            .news-header h2 {
                font-size: 24px;
            }

            .news-header p {
                font-size: 13px;
            }

            .tab-btn {
                padding: 10px 16px;
                font-size: 12px;
            }

            .news-card {
                border-radius: 15px;
            }

            .news-image {
                height: 160px;
                font-size: 50px;
            }

            .news-content {
                padding: 20px;
            }

            .news-title {
                font-size: 16px;
            }

            .news-description {
                font-size: 13px;
            }

            .back-btn {
                padding: 8px 16px;
                font-size: 12px;
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Background Particles -->
    <div class="particles" id="particles"></div>

    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="navbar-brand">
            <div class="navbar-logo">📰</div>
            <h1>News & Updates Center</h1>
        </div>
        <a href="index.php" class="back-btn">
            <span>←</span>
            Back to Dashboard
        </a>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="news-header">
            <h2>Latest News & Updates</h2>
            <p>Stay informed with the latest developments across various sectors</p>
        </div>

        <!-- Category Tabs -->
        <div class="category-tabs">
            <button class="tab-btn active" data-category="all" onclick="filterNews('all')">
                🌐 All News
            </button>
            <button class="tab-btn" data-category="sports" onclick="filterNews('sports')">
                ⚽ Sports
            </button>
            <button class="tab-btn" data-category="technology" onclick="filterNews('technology')">
                💻 Technology
            </button>
            <button class="tab-btn" data-category="it" onclick="filterNews('it')">
                🏢 IT Industry
            </button>
              <button class="tab-btn"  data-category="news" onclick="filterNews('news')">
    <a href="https://alumni.nit.edu.in/newsroom.dz" 
   style="text-decoration: none; color: inherit;">
    NIT NEWS
</a>

            </button>

        </div>

        <!-- Loading Spinner -->
        <div class="loading-spinner" id="loadingSpinner">
            <div class="spinner"></div>
            <p class="loading-text">Loading news...</p>
        </div>

        <!-- News Grid -->
        <div class="news-grid" id="newsGrid"></div>
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
        // Sample News Data
        const newsData = [
            {
                category: 'sports',
                icon: '🏏',
                title: 'India Wins T20 Cricket Series',
                description: 'Team India clinches the T20 series with a spectacular performance in the final match, showcasing exceptional batting and bowling skills.',
              
            },
            {
                category: 'technology',
                icon: '🤖',
                title: 'AI Breakthrough in Healthcare',
                description: 'Revolutionary AI system achieves 99% accuracy in early disease detection, promising to transform preventive healthcare worldwide.',
               
            },
            {
                category: 'it',
                icon: '💼',
                title: 'Tech Hiring Surge Expected in 2025',
                description: 'Major IT companies announce plans to hire 500,000+ professionals in various tech domains, focusing on AI and cloud computing.',
               
            },
            {
                category: 'sports',
                icon: '🏅',
                title: 'Olympic Preparations in Full Swing',
                description: 'Athletes from around the globe intensify training for the upcoming Summer Olympics, with new records already being set.',
                
            },
            {
                category: 'technology',
                icon: '⚡',
                title: 'Quantum Computing Milestone Reached',
                description: 'Scientists achieve quantum supremacy with 1000-qubit processor, opening doors to solving previously impossible computational problems.',
               
            },
            {
                category: 'it',
                icon: '🚀',
                title: 'Startup Funding Hits Record High',
                description: 'Tech startups secure $50 billion in funding this quarter, with fintech and edtech sectors leading the investment surge.',
                
            },
            {
                category: 'sports',
                icon: '⚽',
                title: 'Football Championship Finals Set',
                description: 'Top teams prepare for the championship showdown as the season reaches its thrilling conclusion with unprecedented fan engagement.',
                
            },
            {
                category: 'technology',
                icon: '🌐',
                title: '6G Network Tests Begin Globally',
                description: 'Next-generation 6G wireless technology enters testing phase, promising speeds 100 times faster than current 5G networks.',
               
            },
            {
                category: 'it',
                icon: '🏠',
                title: 'Remote Work Tools Revolutionized',
                description: 'New AI-powered collaboration platforms transform remote work experience with immersive virtual workspaces and real-time translation.',
           
            },
            {
                category: 'technology',
                icon: '🔋',
                title: 'Solid-State Battery Breakthrough',
                description: 'Revolutionary battery technology promises 1000km electric vehicle range and 10-minute charging times, accelerating EV adoption.',
               
            },
            {
                category: 'sports',
                icon: '🎾',
                title: 'Tennis Grand Slam Surprises',
                description: 'Unseeded players create history with remarkable performances in the tournament, rewriting record books and inspiring millions.',
                
            },
            {
                category: 'it',
                icon: '💡',
                title: 'AI Ethics Framework Launched',
                description: 'Global tech consortium unveils comprehensive AI ethics guidelines, setting new standards for responsible AI development and deployment.',
   
            },
            {
                category: 'news',
                icon: '📰',
                title: 'NIT Alumni Achieve Global Recognition',
                description: 'NIT alumni continue to make waves globally with groundbreaking innovations and leadership in various industries.',
            },
            {
                category: 'news',
                icon: '🏆',
                title: 'NIT Wins Prestigious Education Award',
                description: 'NIT has been honored with a top education award for its excellence in research, innovation, and student success.',
            },
            {
                category: 'news',
                icon: '🌍',
                title: 'NIT Launches Sustainability Initiative',
                description: 'NIT has launched a new sustainability initiative aimed at reducing campus carbon footprint and promoting green practices.',
            }

             
        ];

        // Initialize particles
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            for (let i = 0; i < 30; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.width = Math.random() * 10 + 5 + 'px';
                particle.style.height = particle.style.width;
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 15 + 's';
                particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
                particlesContainer.appendChild(particle);
            }
        }

        // Render news cards
        function renderNews(filter = 'all') {
            const newsGrid = document.getElementById('newsGrid');
            const loadingSpinner = document.getElementById('loadingSpinner');
            
            // Show loading spinner
            loadingSpinner.classList.add('active');
            newsGrid.style.opacity = '0';
            
            setTimeout(() => {
                newsGrid.innerHTML = '';
                
                const filteredNews = filter === 'all' 
                    ? newsData 
                    : newsData.filter(news => news.category === filter);
                
                filteredNews.forEach((news, index) => {
                    const card = document.createElement('div');
                    card.className = 'news-card';
                    card.style.animationDelay = (index * 0.1) + 's';
                    
                    const categoryClass = `category-${news.category}`;
                    const categoryLabel = news.category.charAt(0).toUpperCase() + news.category.slice(1);
                    
                    card.innerHTML = `
                        <div class="news-image">${news.icon}</div>
                        <div class="news-content">
                            <span class="news-category ${categoryClass}">${categoryLabel}</span>
                            <h3 class="news-title">${news.title}</h3>
                            <p class="news-description">${news.description}</p>
                            <div class="news-footer">
                                <span class="news-date">📅 ${news.date}</span>
                                
                            </div>
                        </div>
                    `;
                    
                    newsGrid.appendChild(card);
                });
                
                // Hide loading spinner and show news
                loadingSpinner.classList.remove('active');
                newsGrid.style.opacity = '1';
            }, 500);
        }

        // Filter news by category
        function filterNews(category) {
            // Update active tab
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.dataset.category === category) {
                    btn.classList.add('active');
                }
            });
            
            // Render filtered news
            renderNews(category);
        }

        // Read more action
        function readMore(title) {
            alert(`Opening article: "${title}"\n\nThis would typically open the full article in a new page or modal.`);
        }

        // Initialize on page load
        window.addEventListener('DOMContentLoaded', () => {
            createParticles();
            renderNews();
        });
    </script>
</body>
</html>