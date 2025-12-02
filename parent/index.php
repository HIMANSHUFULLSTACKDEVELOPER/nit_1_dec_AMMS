<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);



require_once '../db.php';
checkRole(['parent']);

$parent_id = $_SESSION['user_id'];
$student_id = $_SESSION['student_id'];

// Get parent info
$parent_query = "SELECT * FROM parents WHERE id = $parent_id";
$parent_result = $conn->query($parent_query);
$parent = $parent_result ? $parent_result->fetch_assoc() : ['parent_name' => 'Parent'];

// Get student info
$student_query = "SELECT s.*, d.dept_name, c.class_name, c.section
                  FROM students s
                  LEFT JOIN departments d ON s.department_id = d.id
                  LEFT JOIN classes c ON s.class_id = c.id
                  WHERE s.id = $student_id";
$student_result = $conn->query($student_query);
$student = $student_result ? $student_result->fetch_assoc() : [];

// Get today's attendance
$today = date('Y-m-d');
$today_query = "SELECT * FROM student_attendance 
                WHERE student_id = $student_id AND attendance_date = '$today'";
$today_result = $conn->query($today_query);
$today_attendance = $today_result ? $today_result->fetch_assoc() : null;

// Get current month statistics
$current_month = date('Y-m');
$month_stats_query = "SELECT 
                      COUNT(*) as total_days,
                      SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                      SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                      SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late
                      FROM student_attendance
                      WHERE student_id = $student_id 
                      AND DATE_FORMAT(attendance_date, '%Y-%m') = '$current_month'";
$month_stats_result = $conn->query($month_stats_query);
$month_stats = $month_stats_result ? $month_stats_result->fetch_assoc() : ['total_days' => 0, 'present' => 0, 'absent' => 0, 'late' => 0];

$total_days = $month_stats['total_days'] ?? 0;
$attendance_percentage = $total_days > 0 ? round(($month_stats['present'] / $total_days) * 100, 2) : 0;

// Get overall statistics
$overall_stats_query = "SELECT 
                        COUNT(*) as total_days,
                        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late
                        FROM student_attendance
                        WHERE student_id = $student_id";
$overall_stats_result = $conn->query($overall_stats_query);
$overall_stats = $overall_stats_result ? $overall_stats_result->fetch_assoc() : ['total_days' => 0, 'present' => 0, 'absent' => 0, 'late' => 0];

$overall_total = $overall_stats['total_days'] ?? 0;
$overall_percentage = $overall_total > 0 ? round(($overall_stats['present'] / $overall_total) * 100, 2) : 0;

// Get recent attendance - simplified query first
$recent_query = "SELECT sa.*, 
                 s.class_id, s.department_id
                 FROM student_attendance sa
                 LEFT JOIN students s ON sa.student_id = s.id
                 WHERE sa.student_id = $student_id 
                 ORDER BY sa.attendance_date DESC LIMIT 10";
$recent_attendance = $conn->query($recent_query);

// Display class section with proper formatting
$section_names = [
    'Civil' => '🏗️ Civil Engineering',
    'Mechanical' => '⚙️ Mechanical Engineering',
    'CSE-A' => '💻 Computer Science - A',
    'CSE-B' => '💻 Computer Science - B',
    'Electrical' => '⚡ Electrical Engineering'
];

$display_section = isset($section_names[$student['section'] ?? '']) ? 
                   $section_names[$student['section']] : 
                   htmlspecialchars($student['section'] ?? $student['class_name'] ?? 'N/A');

// Parenting tips array
$parenting_tips = [
    ["tip" => "Encourage your child to maintain regular study habits. Consistency is key to academic success.", "icon" => "📚"],
    ["tip" => "Celebrate small victories and progress. Positive reinforcement builds confidence and motivation.", "icon" => "🌟"],
    ["tip" => "Maintain open communication with teachers. Regular engagement helps track your child's progress.", "icon" => "👨‍🏫"],
    ["tip" => "Ensure your child gets adequate sleep and nutrition. A healthy body supports a healthy mind.", "icon" => "😴"],
    ["tip" => "Create a dedicated study space at home. A quiet, organized environment improves focus.", "icon" => "🏠"],
    ["tip" => "Encourage extracurricular activities. Balance between academics and hobbies is essential.", "icon" => "⚽"],
    ["tip" => "Monitor screen time and ensure a healthy balance between study and recreation.", "icon" => "📱"],
    ["tip" => "Be involved but not overbearing. Guide your child while allowing them to learn independence.", "icon" => "🤝"],
    ["tip" => "Teach time management skills early. Help your child create schedules and stick to them.", "icon" => "⏰"],
    ["tip" => "Show interest in what they're learning. Ask questions about their day and their subjects.", "icon" => "💭"]
];

// Father's love quotes
$father_quotes = [
    ["quote" => "A father's love is the foundation upon which children build their dreams.", "author" => "Unknown"],
    ["quote" => "Behind every great child is a father who believed in them first.", "author" => "Unknown"],
    ["quote" => "A father is someone you look up to no matter how tall you grow.", "author" => "Unknown"],
    ["quote" => "The greatest gift I ever had came from God; I call him Dad.", "author" => "Unknown"],
    ["quote" => "A father's guidance shapes the person their child becomes.", "author" => "Unknown"],
    ["quote" => "No matter how old we are, we always need our father's wisdom.", "author" => "Unknown"],
    ["quote" => "A father's love knows no boundaries, no limits, only endless devotion.", "author" => "Unknown"],
    ["quote" => "Being a father means being there - in presence, in spirit, in heart.", "author" => "Unknown"]
];

// Select random items
$daily_tip = $parenting_tips[array_rand($parenting_tips)];
$daily_quote = $father_quotes[array_rand($father_quotes)];




// Include notices component
$notices_path = __DIR__ . '/../admin/notices_component.php';
if (!file_exists($notices_path)) {
    $notices_path = __DIR__ . '/notices_component.php';
}
if (file_exists($notices_path)) {
    require_once $notices_path;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Dashboard - NIT College</title>
    <link rel="icon" href="../Nit_logo.png" type="image/svg+xml" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
            overflow-x: hidden;
        }
        .particles {
            position: fixed; top: 0; left: 0;
            width: 100%; height: 100%;
            z-index: 0; pointer-events: none;
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
        .navbar {
            background: rgba(26, 31, 58, 0.95);
            backdrop-filter: blur(20px);
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
            position: sticky; top: 0; z-index: 1000;
        }
        .navbar-brand { display: flex; align-items: center; gap: 15px; }
        .navbar-logo {
            width: 50px; height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f093fb, #f5576c);
            display: flex; align-items: center; justify-content: center;
            font-size: 24px;
            animation: rotateLogo 10s linear infinite;
        }
        @keyframes rotateLogo {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .navbar h1 {
            color: white; font-size: 24px; font-weight: 700;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        .user-info { display: flex; align-items: center; gap: 15px; color: white; }
        .user-profile {
            display: flex; align-items: center; gap: 12px;
            background: rgba(255, 255, 255, 0.1);
            padding: 10px 20px; border-radius: 50px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .main-content {
            padding: 40px; max-width: 1600px;
            margin: 0 auto; position: relative; z-index: 1;
        }
        .glass-clock {
            background: rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(20px);
            padding: 30px; border-radius: 25px; text-align: center;
            border: 2px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            min-width: 280px;
            animation: slideInRight 0.8s ease-out;
        }
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(50px); }
            to { opacity: 1; transform: translateX(0); }
        }
        .clock-icon { font-size: 48px; margin-bottom: 15px; animation: pulse 2s ease-in-out infinite; }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        .glass-clock .time {
            font-size: 48px; font-weight: 800;
            font-family: 'Courier New', monospace;
            color: #2c3e50; letter-spacing: 2px;
        }
        .glass-clock .date { font-size: 14px; color: #666; margin-top: 10px; font-weight: 500; }
        .tip-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 50px; border-radius: 30px; margin-bottom: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            position: relative; overflow: hidden;
            border: 2px solid rgba(255, 255, 255, 0.5);
        }
        .tip-background {
            position: absolute; top: 0; left: 0;
            width: 100%; height: 100%;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 50%, #4facfe 100%);
            opacity: 0.1; z-index: 0;
        }
        .tip-content {
            position: relative; z-index: 1;
            display: grid; grid-template-columns: 1fr auto;
            gap: 40px; align-items: center;
        }
        .tip-text-area h3 {
            font-size: 28px;
            background: linear-gradient(135deg, #f093fb, #f5576c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 20px; font-weight: 800;
        }
        .tip-text {
            font-size: 20px; color: #2c3e50;
            line-height: 1.8; font-weight: 500;
            padding-left: 30px; border-left: 4px solid #f093fb;
        }
        /* Father's Love Section */
        .father-love-section {
            background: linear-gradient(135deg, rgba(255, 182, 193, 0.95), rgba(255, 218, 185, 0.95));
            backdrop-filter: blur(20px);
            padding: 50px; border-radius: 30px; margin-bottom: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            position: relative; overflow: hidden;
            border: 3px solid rgba(255, 107, 107, 0.3);
        }
        .father-love-content {
            position: relative; z-index: 1;
            display: grid; grid-template-columns: auto 1fr auto;
            gap: 40px; align-items: center;
        }
        .father-icon { font-size: 100px; animation: heartbeat 1.5s ease-in-out infinite; }
        @keyframes heartbeat {
            0%, 100% { transform: scale(1); }
            25% { transform: scale(1.1); }
            50% { transform: scale(1); }
            75% { transform: scale(1.15); }
        }
        .father-love-text h3 {
            font-size: 32px;
            background: linear-gradient(135deg, #ff6b6b, #ee5a5a, #ff8e53);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 20px; font-weight: 800;
        }
        .father-quote {
            font-size: 22px; color: #5a3e36;
            line-height: 1.8; font-style: italic;
            padding: 25px; background: rgba(255, 255, 255, 0.6);
            border-radius: 20px; border-left: 5px solid #ff6b6b;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        .father-quote-author {
            margin-top: 15px; text-align: right;
            color: #888; font-size: 14px; font-weight: 600;
        }
        .love-stats {
            display: flex; flex-direction: column; gap: 15px;
            background: rgba(255, 255, 255, 0.7);
            padding: 25px; border-radius: 20px; text-align: center;
        }
        .love-stat-item { display: flex; flex-direction: column; gap: 5px; }
        .love-stat-icon { font-size: 30px; }
        .love-stat-label { font-size: 12px; color: #888; text-transform: uppercase; letter-spacing: 1px; }
        .love-stat-value { font-size: 18px; font-weight: 700; color: #ff6b6b; }
        .floating-hearts {
            position: absolute; top: 0; left: 0;
            width: 100%; height: 100%;
            pointer-events: none; overflow: hidden;
        }
        .floating-heart {
            position: absolute; font-size: 20px;
            animation: floatHeart 6s ease-in-out infinite;
            opacity: 0.6;
        }
        @keyframes floatHeart {
            0%, 100% { transform: translateY(0) rotate(0deg); opacity: 0; }
            10% { opacity: 0.6; }
            90% { opacity: 0.6; }
            100% { transform: translateY(-200px) rotate(360deg); opacity: 0; }
        }
        .profile-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px; padding: 40px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
            margin: 30px 0;
            border: 2px solid rgba(255, 255, 255, 0.5);
        }
        .profile-card h2 {
            font-size: 28px;
            background: linear-gradient(135deg, #f093fb, #f5576c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 25px; font-weight: 800;
        }
        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px; margin-top: 20px;
        }
        .profile-item {
            background: linear-gradient(135deg, rgba(240, 147, 251, 0.05), rgba(245, 87, 108, 0.05));
            padding: 15px 20px; border-radius: 12px;
            border-left: 4px solid #f093fb;
        }
        .profile-item strong {
            color: #f093fb; display: block; margin-bottom: 5px;
            font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px; margin: 40px 0;
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 30px; border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            position: relative; overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid rgba(255, 255, 255, 0.5);
        }
        .stat-card::before {
            content: ''; position: absolute; top: 0; left: 0;
            width: 100%; height: 4px;
            background: linear-gradient(90deg, #f093fb, #f5576c, #4facfe);
            background-size: 200% 100%;
            animation: gradientShift 3s ease infinite;
        }
        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        .stat-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 60px rgba(240, 147, 251, 0.4);
        }
        .stat-card h3 {
            color: #666; font-size: 13px; font-weight: 600;
            text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px;
        }
        .stat-value {
            font-size: 48px; font-weight: 800;
            background: linear-gradient(135deg, #f093fb, #f5576c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .alert {
            padding: 20px 30px; border-radius: 15px;
            margin: 30px 0; animation: slideDown 0.5s ease-out;
            backdrop-filter: blur(10px); border: 2px solid;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .alert-success { background: rgba(212, 237, 218, 0.95); border-color: #28a745; color: #155724; }
        .alert-warning { background: rgba(255, 243, 205, 0.95); border-color: #ffc107; color: #856404; }
        .table-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px; padding: 40px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
            margin: 30px 0;
            border: 2px solid rgba(255, 255, 255, 0.5);
            overflow-x: auto;
        }
        .table-container h3 { font-size: 24px; font-weight: 700; color: #2c3e50; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; min-width: 900px; }
        thead { background: linear-gradient(135deg, #f093fb, #f5576c); }
        thead th {
            padding: 15px 12px; color: white; font-weight: 600;
            text-align: left; font-size: 13px;
            text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;
        }
        tbody tr { border-bottom: 1px solid #f0f0f0; transition: all 0.3s; }
        tbody tr:hover { background: rgba(240, 147, 251, 0.05); transform: translateX(5px); }
        tbody td { padding: 16px 12px; color: #2c3e50; font-size: 13px; }
        .badge {
            padding: 6px 14px; border-radius: 20px; font-size: 11px;
            font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.5px; display: inline-block;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .btn {
            padding: 12px 24px; border-radius: 12px;
            text-decoration: none; font-weight: 600;
            transition: all 0.3s; display: inline-block;
            border: none; cursor: pointer; font-size: 14px;
            text-align: center; margin: 5px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #f093fb, #f5576c);
            color: white; box-shadow: 0 4px 15px rgba(240, 147, 251, 0.4);
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(240, 147, 251, 0.6); }
        .btn-info {
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            color: white; box-shadow: 0 4px 15px rgba(79, 172, 254, 0.4);
        }
        .btn-info:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(79, 172, 254, 0.6); }
        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b, #ee5a5a);
            color: white; box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4);
        }
        .btn-danger:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(255, 107, 107, 0.6); }
        .teacher-badge {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white; padding: 4px 10px; border-radius: 15px;
            font-size: 11px; font-weight: 600;
        }
        .class-badge {
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            color: white; padding: 4px 10px; border-radius: 15px;
            font-size: 11px; font-weight: 600;
        }
        .dept-badge {
            background: linear-gradient(135deg, #f093fb, #f5576c);
            color: white; padding: 4px 10px; border-radius: 15px;
            font-size: 11px; font-weight: 600;
        }
        .time-badge {
            background: rgba(102, 126, 234, 0.1); color: #667eea;
            padding: 4px 10px; border-radius: 15px;
            font-size: 11px; font-weight: 600;
            border: 1px solid rgba(102, 126, 234, 0.3);
        }
        .footer {
            background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 50%, #2a3254 100%);
            position: relative; overflow: hidden; margin-top: 60px;
        }
        .footer-border {
            height: 2px;
            background: linear-gradient(90deg, #f093fb, #f5576c, #4facfe);
            background-size: 200% 100%;
            animation: borderMove 3s linear infinite;
        }
        @keyframes borderMove {
            0% { background-position: 0% 50%; }
            100% { background-position: 200% 50%; }
        }
        @media (max-width: 768px) {
            .navbar { flex-direction: column; gap: 15px; padding: 15px 20px; }
            .user-info { flex-direction: column; gap: 10px; }
            .main-content { padding: 20px; }
            .tip-content, .father-love-content { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: 1fr; }
            .father-icon { font-size: 60px; text-align: center; }
        }
    </style>
</head>
<body>
    <div class="particles">
        <div class="particle" style="width: 10px; height: 10px; left: 10%; animation-delay: 0s;"></div>
        <div class="particle" style="width: 15px; height: 15px; left: 20%; animation-delay: 2s;"></div>
        <div class="particle" style="width: 8px; height: 8px; left: 30%; animation-delay: 4s;"></div>
        <div class="particle" style="width: 12px; height: 12px; left: 40%; animation-delay: 6s;"></div>
        <div class="particle" style="width: 10px; height: 10px; left: 50%; animation-delay: 8s;"></div>
        <div class="particle" style="width: 14px; height: 14px; left: 60%; animation-delay: 10s;"></div>
        <div class="particle" style="width: 9px; height: 9px; left: 70%; animation-delay: 12s;"></div>
        <div class="particle" style="width: 11px; height: 11px; left: 80%; animation-delay: 14s;"></div>
    </div>

    <nav class="navbar">
        <div class="navbar-brand">
            <div class="navbar-logo">👨‍👩‍👦</div>
            <h1>NIT AMMS - Parent Portal</h1>
        </div>
        <div class="user-info">
            <a href="profile.php" class="btn btn-info">👤 My Profile</a>
            <div class="user-profile">
                <span>👨‍👩‍👦</span>
                <span><?php echo htmlspecialchars($parent['parent_name'] ?? 'Parent'); ?></span>
            </div>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <!-- Parenting Tip Section -->
        <div class="tip-container">
            <div class="tip-background"></div>
            <div class="tip-content">
                <div class="tip-text-area">
                    <h3>💡 Daily Parenting Tip</h3>
                    <p class="tip-text"><?php echo htmlspecialchars($daily_tip['tip']); ?></p>
                </div>
                <div class="glass-clock">
                    <div class="clock-icon">⏰</div>
                    <div class="time" id="clock">--:--:--</div>
                    <div class="date" id="date">Loading...</div>
                </div>
            </div>
        </div>

       
        <!-- Child's Profile Card -->
        <div class="profile-card">
            <h2>👤 Your Child's Profile</h2>
            <div class="profile-grid">
                <div class="profile-item">
                    <strong>Full Name</strong>
                    <div><?php echo htmlspecialchars($student['full_name'] ?? 'N/A'); ?></div>
                </div>
                <div class="profile-item">
                    <strong>Roll Number</strong>
                    <div><?php echo htmlspecialchars($student['roll_number'] ?? 'N/A'); ?></div>
                </div>
                <div class="profile-item">
                    <strong>Email</strong>
                    <div><?php echo htmlspecialchars($student['email'] ?? 'N/A'); ?></div>
                </div>
                <div class="profile-item">
                    <strong>Phone</strong>
                    <div><?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></div>
                </div>
                <div class="profile-item">
                    <strong>Department</strong>
                    <div><?php echo htmlspecialchars($student['dept_name'] ?? 'N/A'); ?></div>
                </div>
                <div class="profile-item">
                    <strong>Class/Section</strong>
                    <div><?php echo $display_section; ?></div>
                </div>
                <div class="profile-item">
                    <strong>Year</strong>
                    <div><?php echo htmlspecialchars($student['year'] ?? 'N/A'); ?></div>
                </div>
                <div class="profile-item">
                    <strong>Semester</strong>
                    <div><?php echo htmlspecialchars($student['semester'] ?? 'N/A'); ?></div>
                </div>
                <div class="profile-item">
                    <strong>Status</strong>
                    <div>
                        <?php if (isset($student['is_active']) && $student['is_active']): ?>
                            <span class="badge badge-success">Active</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Inactive</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

<a href="view_child_paper_marks.php" class="btn btn-primary">📊 Child's Marks</a>
<a href="new.php" class="btn btn-primary"> News </a>
<a href="teachercall.php" class="btn btn-primary"> Teacher Call </a>
<a href="dailytimetable.php" class="btn btn-primary"> Daily class timetable </a>



          <!-- DISPLAY NOTICES HERE -->
        <?php 
        if (function_exists('displayNotices')) {
            displayNotices('parent'); 
        }
        ?>

        <!-- Today's Attendance Alert -->
        <?php if ($today_attendance): ?>
            <?php $alert_class = ($today_attendance['status'] === 'present') ? 'success' : 'warning'; ?>
            <div class="alert alert-<?php echo $alert_class; ?>">
                <?php if ($today_attendance['status'] === 'present'): ?>
                    <strong>✅ Your child was marked PRESENT today</strong>
                <?php elseif ($today_attendance['status'] === 'absent'): ?>
                    <strong>❌ Your child was marked ABSENT today</strong>
                <?php else: ?>
                    <strong>⏰ Your child was marked LATE today</strong>
                <?php endif; ?>
                <?php if (!empty($today_attendance['remarks'])): ?>
                    <br><strong>Remarks:</strong> <?php echo htmlspecialchars($today_attendance['remarks']); ?>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                <strong>⚠️ Attendance not marked yet for today</strong>
            </div>
        <?php endif; ?>

        <!-- Monthly Statistics -->
        <h3 style="font-size: 28px; background: linear-gradient(135deg, #f093fb, #f5576c); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-weight: 800; margin: 40px 0 20px;">📊 This Month's Attendance Statistics</h3>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>📅 Total Classes</h3>
                <div class="stat-value"><?php echo $total_days; ?></div>
            </div>
            <div class="stat-card">
                <h3>✅ Present</h3>
                <div class="stat-value" style="color: #28a745;"><?php echo $month_stats['present'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <h3>❌ Absent</h3>
                <div class="stat-value" style="color: #dc3545;"><?php echo $month_stats['absent'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <h3>⏰ Late</h3>
                <div class="stat-value" style="color: #ffc107;"><?php echo $month_stats['late'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <h3>📈 Attendance %</h3>
                <div class="stat-value" style="color: <?php echo $attendance_percentage >= 75 ? '#28a745' : '#dc3545'; ?>">
                    <?php echo $attendance_percentage; ?>%
                </div>
            </div>
        </div>

        <!-- Overall Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>📊 Overall Statistics</h3>
                <p style="margin: 10px 0;"><strong>Total Days:</strong> <?php echo $overall_total; ?></p>
                <p style="margin: 10px 0;"><strong>Present:</strong> <span style="color: #28a745; font-weight: 700;"><?php echo $overall_stats['present'] ?? 0; ?></span></p>
                <p style="margin: 10px 0;"><strong>Absent:</strong> <span style="color: #dc3545; font-weight: 700;"><?php echo $overall_stats['absent'] ?? 0; ?></span></p>
                <p style="margin: 10px 0;"><strong>Late:</strong> <span style="color: #ffc107; font-weight: 700;"><?php echo $overall_stats['late'] ?? 0; ?></span></p>
                <p style="margin: 10px 0;"><strong>Overall %:</strong> 
                    <span style="color: <?php echo $overall_percentage >= 75 ? '#28a745' : '#dc3545'; ?>; font-size: 24px; font-weight: 800;">
                        <?php echo $overall_percentage; ?>%
                    </span>
                </p>
                <?php if ($overall_percentage < 75): ?>
                    <div style="margin-top: 15px; padding: 15px; background: rgba(255, 193, 7, 0.1); border-left: 4px solid #ffc107; border-radius: 8px;">
                        <strong style="color: #856404;">⚠️ Attention Required</strong>
                        <p style="margin: 5px 0 0; color: #856404; font-size: 13px;">Your child's attendance is below 75%.</p>
                    </div>
                <?php else: ?>
                    <div style="margin-top: 15px; padding: 15px; background: rgba(40, 167, 69, 0.1); border-left: 4px solid #28a745; border-radius: 8px;">
                        <strong style="color: #155724;">✅ Excellent!</strong>
                        <p style="margin: 5px 0 0; color: #155724; font-size: 13px;">Your child maintains good attendance!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Attendance Table -->
        <div class="table-container">
            <h3>📝 Recent Attendance Records</h3>
            <table>
                <thead>
                    <tr>
                        <th>📅 Date</th>
                        <th>📆 Day</th>
                        <th>👨‍🏫 Marked By</th>
                        <th>🏛️ Class</th>
                        <th>🎓 Department</th>
                        <th>✓ Status</th>
                        <th>📝 Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_attendance && $recent_attendance->num_rows > 0): ?>
                        <?php while ($record = $recent_attendance->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo date('d M Y', strtotime($record['attendance_date'])); ?></strong></td>
                            <td><?php echo date('l', strtotime($record['attendance_date'])); ?></td>
                            <td><span class="teacher-badge"><?php echo htmlspecialchars($record['marked_by'] ?? 'System'); ?></span></td>
                            <td><span class="class-badge"><?php echo htmlspecialchars($student['class_name'] ?? 'N/A') . ' - ' . htmlspecialchars($student['section'] ?? ''); ?></span></td>
                            <td><span class="dept-badge"><?php echo htmlspecialchars($student['dept_name'] ?? 'N/A'); ?></span></td>
                            <td>
                                <?php
                                $status = $record['status'] ?? 'unknown';
                                if ($status === 'present') { $sc = 'badge-success'; $si = '✅'; }
                                elseif ($status === 'absent') { $sc = 'badge-danger'; $si = '❌'; }
                                else { $sc = 'badge-warning'; $si = '⏰'; }
                                ?>
                                <span class="badge <?php echo $sc; ?>"><?php echo $si . ' ' . strtoupper($status); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($record['remarks'] ?? '-'); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #999;">No attendance records found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Action Buttons -->
        <div style="text-align: center; margin: 40px 0;">
            <a href="attendance_report.php" class="btn btn-primary">📊 View Detailed Report</a>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-border"></div>
        <div style="max-width: 1000px; margin: 0 auto; padding: 30px 20px 20px;">
            <div style="background: rgba(255, 255, 255, 0.03); padding: 20px; border-radius: 15px; border: 1px solid rgba(240, 147, 251, 0.15); text-align: center;">
                <p style="color: #ffffff; font-size: 14px; margin: 0 0 12px;">✨ Designed & Developed by</p>
                <a href="https://himanshufullstackdeveloper.github.io/techyugsoftware/" style="display: inline-block; color: #ffffff; font-size: 16px; font-weight: 700; text-decoration: none; padding: 8px 24px; border: 2px solid #f093fb; border-radius: 30px; background: linear-gradient(135deg, rgba(240, 147, 251, 0.2), rgba(245, 87, 108, 0.2)); margin-bottom: 15px;">🚀 Techyug Software Pvt. Ltd.</a>
                <div style="margin-top: 12px; display: flex; justify-content: center; gap: 12px; flex-wrap: wrap;">
                    <a href="https://himanshufullstackdeveloper.github.io/portfoilohimanshu/" style="color: #ffffff; font-size: 13px; text-decoration: none; padding: 8px 16px; background: linear-gradient(135deg, rgba(240, 147, 251, 0.25), rgba(245, 87, 108, 0.25)); border-radius: 20px; border: 1px solid rgba(240, 147, 251, 0.4);">👨‍💻 Himanshu Patil</a>
                    <a href="https://devpranaypanore.github.io/Pranaypanore-live-.html/" style="color: #ffffff; font-size: 13px; text-decoration: none; padding: 8px 16px; background: linear-gradient(135deg, rgba(240, 147, 251, 0.25), rgba(245, 87, 108, 0.25)); border-radius: 20px; border: 1px solid rgba(240, 147, 251, 0.4);">👨‍💻 Pranay Panore</a>
                </div>
            </div>
            <div style="margin-top: 25px; text-align: center;">
                <p style="color: #888; font-size: 12px;">© 2025 NIT AMMS. All rights reserved.</p>
                <p style="color: #666; font-size: 11px;">Made with <span style="color: #ff4757;">❤️</span> by Techyug Software</p>
            </div>
        </div>
    </div>

    <script>
        function updateClock() {
            const now = new Date();
            const h = String(now.getHours()).padStart(2, '0');
            const m = String(now.getMinutes()).padStart(2, '0');
            const s = String(now.getSeconds()).padStart(2, '0');
            document.getElementById('clock').textContent = h + ':' + m + ':' + s;
            const opts = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('date').textContent = now.toLocaleDateString('en-US', opts);
        }
        updateClock();
        setInterval(updateClock, 1000);
    </script>
</body>
</html>