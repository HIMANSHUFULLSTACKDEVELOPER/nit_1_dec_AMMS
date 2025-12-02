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

// Weekly attendance trend
$weekly_query = "SELECT DATE(attendance_date) as date, COUNT(*) as total, SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present FROM student_attendance WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(attendance_date) ORDER BY date";
$weekly_result = $conn->query($weekly_query);
$weekly_labels = []; $weekly_data = [];
while ($row = $weekly_result->fetch_assoc()) {
    $weekly_labels[] = date('D', strtotime($row['date']));
    $weekly_data[] = $row['total'] > 0 ? round(($row['present'] / $row['total']) * 100, 1) : 0;
}

// Section-wise attendance
$section_attendance_query = "SELECT c.section, COUNT(sa.id) as total_marked, SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) as present_count, SUM(CASE WHEN sa.status = 'absent' THEN 1 ELSE 0 END) as absent_count, SUM(CASE WHEN sa.status = 'late' THEN 1 ELSE 0 END) as late_count, (SELECT COUNT(*) FROM students s WHERE s.class_id IN (SELECT id FROM classes WHERE section = c.section)) as total_students FROM student_attendance sa JOIN classes c ON sa.class_id = c.id WHERE sa.attendance_date = CURDATE() GROUP BY c.section ORDER BY c.section";
$section_attendance = $conn->query($section_attendance_query);
$section_labels = []; $present_data = []; $absent_data = []; $late_data = []; $attendance_percentage = [];
while ($row = $section_attendance->fetch_assoc()) {
    $section_labels[] = $row['section'];
    $present_data[] = $row['present_count'];
    $absent_data[] = $row['absent_count'];
    $late_data[] = $row['late_count'];
    $attendance_percentage[] = $row['total_students'] > 0 ? round(($row['present_count'] / $row['total_students']) * 100, 1) : 0;
}

// Recent activities
$recent_query = "SELECT sa.*, s.full_name as student_name, s.roll_number, c.class_name, u.full_name as teacher_name FROM student_attendance sa JOIN students s ON sa.student_id = s.id JOIN classes c ON sa.class_id = c.id JOIN users u ON sa.marked_by = u.id ORDER BY sa.marked_at DESC LIMIT 10";
$recent_activities = $conn->query($recent_query);

$hour = date('H');
if ($hour < 12) $greeting = "Good Morning";
elseif ($hour < 17) $greeting = "Good Afternoon";
else $greeting = "Good Evening";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - NIT College</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="icon" href="../Nit_logo.png" type="image/svg+xml" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%); min-height: 100vh; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; position: relative; overflow-x: hidden; }
        .particles { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; pointer-events: none; }
        .particle { position: absolute; background: rgba(255, 255, 255, 0.15); border-radius: 50%; animation: float 15s infinite ease-in-out; }
        @keyframes float { 0%, 100% { transform: translateY(0) rotate(0deg); opacity: 0; } 10% { opacity: 1; } 90% { opacity: 1; } 100% { transform: translateY(-100vh) rotate(360deg); opacity: 0; } }
        .navbar { background: rgba(26, 31, 58, 0.95); backdrop-filter: blur(20px); padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3); border-bottom: 2px solid rgba(255, 255, 255, 0.1); position: sticky; top: 0; z-index: 1000; }
        .navbar-brand { display: flex; align-items: center; gap: 15px; }
        .navbar-logo { width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #667eea, #764ba2); display: flex; align-items: center; justify-content: center; font-size: 24px; animation: rotateLogo 10s linear infinite; }
        @keyframes rotateLogo { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .navbar h1 { color: white; font-size: 24px; font-weight: 700; text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3); }
        .user-info { display: flex; align-items: center; gap: 25px; color: white; }
        .user-profile { display: flex; align-items: center; gap: 12px; background: rgba(255, 255, 255, 0.1); padding: 10px 20px; border-radius: 50px; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #f093fb, #f5576c); display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: bold; }
        .main-content { padding: 40px; max-width: 1600px; margin: 0 auto; position: relative; z-index: 1; }
        .hero-welcome { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); padding: 50px; border-radius: 30px; margin-bottom: 40px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); position: relative; overflow: hidden; border: 2px solid rgba(255, 255, 255, 0.5); }
        .hero-background { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%); opacity: 0.1; z-index: 0; }
        .hero-content { position: relative; z-index: 1; display: grid; grid-template-columns: 1fr auto; gap: 40px; align-items: center; }
        .hero-text h2 { font-size: 42px; background: linear-gradient(135deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 15px; font-weight: 800; }
        .hero-text p { font-size: 18px; color: #666; margin-bottom: 25px; }
        .hero-stats { display: flex; gap: 30px; margin-top: 30px; flex-wrap: wrap; }
        .hero-stat-item { text-align: center; }
        .hero-stat-value { font-size: 36px; font-weight: 700; background: linear-gradient(135deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .hero-stat-label { font-size: 13px; color: #666; text-transform: uppercase; letter-spacing: 1px; margin-top: 5px; }
        .glass-clock { background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(20px); padding: 30px; border-radius: 25px; text-align: center; border: 2px solid rgba(255, 255, 255, 0.3); box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1); min-width: 280px; }
        .clock-icon { font-size: 48px; margin-bottom: 15px; animation: pulse 2s ease-in-out infinite; }
        @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.1); } }
        .glass-clock .time { font-size: 48px; font-weight: 800; font-family: 'Courier New', monospace; background: linear-gradient(135deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .glass-clock .date { font-size: 14px; color: #666; margin-top: 8px; font-weight: 500; }
        .premium-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 25px; margin: 40px 0; }
        .premium-stat-card { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); padding: 30px; border-radius: 20px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15); position: relative; overflow: hidden; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); border: 2px solid rgba(255, 255, 255, 0.5); }
        .premium-stat-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: linear-gradient(90deg, #667eea, #764ba2, #f093fb); background-size: 200% 100%; animation: gradientShift 3s ease infinite; }
        @keyframes gradientShift { 0%, 100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }
        .premium-stat-card:hover { transform: translateY(-10px) scale(1.02); box-shadow: 0 20px 60px rgba(102, 126, 234, 0.4); }
        .stat-icon-wrapper { width: 70px; height: 70px; border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 32px; margin-bottom: 20px; animation: iconFloat 3s ease-in-out infinite; }
        @keyframes iconFloat { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
        .stat-details h4 { color: #666; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; }
        .stat-value-large { font-size: 42px; font-weight: 800; background: linear-gradient(135deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 10px; }
        .stat-trend { font-size: 13px; display: flex; align-items: center; gap: 8px; font-weight: 600; }
        .trend-up { color: #28a745; } .trend-down { color: #dc3545; }
        .section-title { font-size: 28px; font-weight: 700; color: white; margin: 50px 0 30px; display: flex; align-items: center; gap: 15px; text-shadow: 0 2px 10px rgba(0,0,0,0.3); }
        .section-title::before { content: ''; width: 5px; height: 40px; background: linear-gradient(180deg, #fff, transparent); border-radius: 10px; }
        .quick-actions-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 25px; margin: 30px 0; }
        .action-card { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); padding: 35px 25px; border-radius: 20px; text-align: center; text-decoration: none; color: #2c3e50; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15); border: 2px solid rgba(255, 255, 255, 0.5); position: relative; overflow: hidden; }
        .action-card::before { content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%; background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent); transition: left 0.5s; }
        .action-card:hover::before { left: 100%; }
        .action-card:hover { transform: translateY(-15px) scale(1.05); box-shadow: 0 25px 60px rgba(102, 126, 234, 0.5); }
        .action-icon { width: 80px; height: 80px; margin: 0 auto 20px; border-radius: 50%; background: linear-gradient(135deg, #667eea, #764ba2); display: flex; align-items: center; justify-content: center; font-size: 40px; color: white; box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4); }
        .action-label { font-size: 16px; font-weight: 700; letter-spacing: 0.5px; }
        .charts-section { margin: 50px 0; }
        .chart-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px; }
        .chart-card { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); padding: 35px; border-radius: 25px; box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2); border: 2px solid rgba(255, 255, 255, 0.5); transition: all 0.3s ease; }
        .chart-card:hover { box-shadow: 0 20px 60px rgba(102, 126, 234, 0.3); transform: translateY(-5px); }
        .chart-header { display: flex; align-items: center; gap: 15px; margin-bottom: 25px; padding-bottom: 20px; border-bottom: 3px solid #f0f0f0; }
        .chart-icon { width: 50px; height: 50px; border-radius: 12px; background: linear-gradient(135deg, #667eea, #764ba2); display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; }
        .chart-title { font-size: 20px; font-weight: 700; color: #2c3e50; }
        .activity-section { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); border-radius: 25px; padding: 40px; box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2); margin-top: 50px; border: 2px solid rgba(255, 255, 255, 0.5); }
        .activity-header { display: flex; align-items: center; gap: 15px; margin-bottom: 30px; }
        .activity-header h3 { font-size: 24px; font-weight: 700; color: #2c3e50; }
        .timeline-item { display: flex; gap: 20px; padding: 20px; border-bottom: 1px solid #f0f0f0; transition: all 0.3s; border-radius: 15px; }
        .timeline-item:hover { background: rgba(102, 126, 234, 0.05); transform: translateX(10px); }
        .timeline-dot { width: 14px; height: 14px; border-radius: 50%; margin-top: 5px; flex-shrink: 0; box-shadow: 0 0 15px currentColor; }
        .timeline-dot.present { background: #28a745; color: #28a745; }
        .timeline-dot.absent { background: #dc3545; color: #dc3545; }
        .timeline-dot.late { background: #ffc107; color: #ffc107; }
        .timeline-content { flex: 1; }
        .student-name { font-weight: 700; font-size: 16px; color: #2c3e50; margin-bottom: 5px; }
        .timeline-details { font-size: 13px; color: #666; }
        .timeline-time { text-align: right; }
        .status-badge { padding: 6px 14px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: inline-block; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .btn { padding: 12px 24px; border-radius: 12px; text-decoration: none; font-weight: 600; transition: all 0.3s; display: inline-block; border: none; cursor: pointer; }
        .btn-danger { background: linear-gradient(135deg, #ff6b6b, #ee5a5a); color: white; box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4); }
        .btn-danger:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(255, 107, 107, 0.6); }
        .btn-info { background: linear-gradient(135deg, #667eea, #764ba2); color: white; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4); }
        .btn-sm { padding: 8px 16px; font-size: 13px; }

        /* Enhanced AI Assistant Styles */
        .ai-assistant-btn { position: fixed; bottom: 30px; right: 30px; width: 70px; height: 70px; border-radius: 50%; background: linear-gradient(135deg, #4285F4, #34A853, #FBBC05, #EA4335); border: none; cursor: pointer; box-shadow: 0 10px 40px rgba(66, 133, 244, 0.5); z-index: 9998; display: flex; align-items: center; justify-content: center; transition: all 0.3s; animation: pulseBtn 2s infinite; }
        @keyframes pulseBtn { 0%, 100% { box-shadow: 0 10px 40px rgba(66, 133, 244, 0.5); } 50% { box-shadow: 0 10px 60px rgba(66, 133, 244, 0.8), 0 0 0 10px rgba(66, 133, 244, 0.2); } }
        .ai-assistant-btn:hover { transform: scale(1.1) rotate(5deg); }
        .ai-assistant-btn i { font-size: 32px; color: white; animation: sparkle 2s infinite; }
        @keyframes sparkle { 0%, 100% { filter: brightness(1); } 50% { filter: brightness(1.5); } }
        
        .ai-chat-container { position: fixed; bottom: 120px; right: 30px; width: 450px; max-height: 650px; background: white; border-radius: 25px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); z-index: 9999; display: none; flex-direction: column; overflow: hidden; animation: slideUp 0.3s ease; border: 3px solid transparent; background-image: linear-gradient(white, white), linear-gradient(135deg, #4285F4, #34A853, #FBBC05, #EA4335); background-origin: border-box; background-clip: padding-box, border-box; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        
        .ai-chat-header { background: linear-gradient(135deg, #4285F4, #34A853); padding: 25px; display: flex; align-items: center; justify-content: space-between; position: relative; overflow: hidden; }
        .ai-chat-header::before { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); animation: rotate 10s linear infinite; }
        @keyframes rotate { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        
        .ai-chat-header-info { display: flex; align-items: center; gap: 15px; position: relative; z-index: 1; }
        .ai-avatar { width: 50px; height: 50px; border-radius: 50%; background: white; display: flex; align-items: center; justify-content: center; font-size: 28px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .ai-chat-header h4 { color: white; font-size: 20px; margin: 0; font-weight: 700; text-shadow: 0 2px 10px rgba(0,0,0,0.2); }
        .ai-chat-header p { color: rgba(255,255,255,0.9); font-size: 13px; margin: 3px 0 0; }
        
        .ai-header-controls { display: flex; gap: 10px; position: relative; z-index: 1; }
        .ai-control-btn { background: rgba(255,255,255,0.2); border: none; width: 38px; height: 38px; border-radius: 50%; color: white; cursor: pointer; font-size: 18px; transition: all 0.3s; backdrop-filter: blur(10px); }
        .ai-control-btn:hover { background: rgba(255,255,255,0.3); transform: scale(1.1); }
        .ai-control-btn.active { background: rgba(255,255,255,0.4); }
        
        .ai-chat-messages { flex: 1; overflow-y: auto; padding: 25px; max-height: 420px; background: linear-gradient(to bottom, #f8f9fa 0%, #ffffff 100%); }
        .ai-message { margin-bottom: 20px; display: flex; gap: 12px; animation: messageSlide 0.3s ease; }
        @keyframes messageSlide { from { opacity: 0; transform: translateX(-10px); } to { opacity: 1; transform: translateX(0); } }
        .ai-message.user { flex-direction: row-reverse; }
        .ai-message.user .ai-message-content { animation: messageSlideRight 0.3s ease; }
        @keyframes messageSlideRight { from { opacity: 0; transform: translateX(10px); } to { opacity: 1; transform: translateX(0); } }
        
        .ai-message-avatar { width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .ai-message.bot .ai-message-avatar { background: linear-gradient(135deg, #4285F4, #34A853); color: white; }
        .ai-message.user .ai-message-avatar { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        
        .ai-message-content { max-width: 75%; padding: 15px 18px; border-radius: 20px; font-size: 14px; line-height: 1.6; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .ai-message.bot .ai-message-content { background: white; border-bottom-left-radius: 5px; border: 1px solid #e9ecef; }
        .ai-message.user .ai-message-content { background: linear-gradient(135deg, #667eea, #764ba2); color: white; border-bottom-right-radius: 5px; }
        .ai-message-content a { color: #4285F4; font-weight: 600; text-decoration: none; }
        .ai-message-content a:hover { text-decoration: underline; }
        
        .ai-chat-input { padding: 20px; background: white; border-top: 2px solid #f0f2f5; display: flex; gap: 12px; align-items: center; }
        .ai-chat-input input { flex: 1; padding: 14px 20px; border: 2px solid #e9ecef; border-radius: 25px; font-size: 14px; outline: none; transition: all 0.3s; }
        .ai-chat-input input:focus { border-color: #4285F4; box-shadow: 0 0 0 3px rgba(66, 133, 244, 0.1); }
        .ai-chat-input button { width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, #4285F4, #34A853); border: none; color: white; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 15px rgba(66, 133, 244, 0.3); }
        .ai-chat-input button:hover { transform: scale(1.1); box-shadow: 0 6px 20px rgba(66, 133, 244, 0.4); }
        .ai-chat-input button:disabled { opacity: 0.5; cursor: not-allowed; }
        
        .ai-quick-actions { padding: 15px 20px; background: #f8f9fa; border-top: 1px solid #e9ecef; }
        .ai-quick-actions p { font-size: 12px; color: #666; margin-bottom: 12px; font-weight: 600; }
        .ai-quick-btn { padding: 10px 16px; font-size: 12px; background: white; border: 2px solid #4285F4; border-radius: 20px; color: #4285F4; cursor: pointer; margin: 4px; transition: all 0.3s; font-weight: 600; }
        .ai-quick-btn:hover { background: #4285F4; color: white; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(66, 133, 244, 0.3); }
        
        .typing-indicator { display: flex; gap: 5px; padding: 15px 18px; }
        .typing-indicator span { width: 10px; height: 10px; background: linear-gradient(135deg, #4285F4, #34A853); border-radius: 50%; animation: typing 1.4s infinite; }
        .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
        .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes typing { 0%, 60%, 100% { transform: translateY(0); opacity: 0.7; } 30% { transform: translateY(-12px); opacity: 1; } }
        
        .voice-wave { display: none; align-items: center; gap: 3px; padding: 10px; }
        .voice-wave.active { display: flex; }
        .voice-bar { width: 4px; background: linear-gradient(135deg, #4285F4, #34A853); border-radius: 2px; animation: wave 1s infinite ease-in-out; }
        .voice-bar:nth-child(1) { height: 10px; animation-delay: 0s; }
        .voice-bar:nth-child(2) { height: 20px; animation-delay: 0.1s; }
        .voice-bar:nth-child(3) { height: 15px; animation-delay: 0.2s; }
        .voice-bar:nth-child(4) { height: 25px; animation-delay: 0.3s; }
        .voice-bar:nth-child(5) { height: 18px; animation-delay: 0.4s; }
        @keyframes wave { 0%, 100% { transform: scaleY(0.5); } 50% { transform: scaleY(1); } }

        @media (max-width: 768px) {
            .navbar { padding: 12px 15px; flex-wrap: wrap; }
            .navbar h1 { font-size: 16px; }
            .main-content { padding: 15px; }
            .hero-content { grid-template-columns: 1fr; text-align: center; }
            .hero-text h2 { font-size: 24px; }
            .premium-stats { grid-template-columns: 1fr; }
            .quick-actions-grid { grid-template-columns: repeat(2, 1fr); }
            .ai-chat-container { width: calc(100% - 30px); right: 15px; bottom: 100px; }
            .ai-assistant-btn { width: 60px; height: 60px; right: 15px; bottom: 15px; }
        }


/* ============================================
   PROFESSIONAL RESPONSIVE DESIGN
   ============================================ */

/* Extra Large Desktop (1920px+) - Premium Experience */
@media (min-width: 1920px) {
    .main-content { max-width: 1800px; padding: 50px; }
    .navbar h1 { font-size: 28px; }
    .hero-text h2 { font-size: 48px; }
    .premium-stats { grid-template-columns: repeat(5, 1fr); gap: 30px; }
    .quick-actions-grid { grid-template-columns: repeat(8, 1fr); }
    .chart-grid { grid-template-columns: repeat(3, 1fr); }
    .stat-value-large { font-size: 48px; }
    .action-icon { width: 90px; height: 90px; font-size: 44px; }
}

/* Large Desktop (1400px - 1919px) */
@media (max-width: 1919px) and (min-width: 1400px) {
    .main-content { max-width: 1600px; padding: 40px; }
    .navbar h1 { font-size: 26px; }
    .hero-text h2 { font-size: 44px; }
    .premium-stats { grid-template-columns: repeat(5, 1fr); gap: 25px; }
    .quick-actions-grid { grid-template-columns: repeat(7, 1fr); }
    .chart-grid { grid-template-columns: repeat(3, 1fr); }
}

/* Standard Desktop (1200px - 1399px) */
@media (max-width: 1399px) and (min-width: 1200px) {
    .main-content { max-width: 1200px; padding: 35px; }
    .navbar { padding: 18px 35px; }
    .navbar h1 { font-size: 22px; }
    .hero-text h2 { font-size: 38px; }
    .hero-text p { font-size: 16px; }
    .premium-stats { grid-template-columns: repeat(4, 1fr); gap: 20px; }
    .quick-actions-grid { grid-template-columns: repeat(6, 1fr); gap: 20px; }
    .chart-grid { grid-template-columns: repeat(2, 1fr); }
    .stat-value-large { font-size: 38px; }
    .action-icon { width: 75px; height: 75px; font-size: 36px; }
}

/* Small Desktop / Large Tablet Landscape (1024px - 1199px) */
@media (max-width: 1199px) and (min-width: 1024px) {
    .main-content { max-width: 100%; padding: 30px; }
    .navbar { padding: 15px 25px; }
    .navbar h1 { font-size: 20px; }
    .navbar-logo { width: 45px; height: 45px; font-size: 22px; }
    
    .hero-welcome { padding: 35px; margin-bottom: 30px; }
    .hero-content { gap: 30px; }
    .hero-text h2 { font-size: 34px; }
    .hero-text p { font-size: 15px; }
    .hero-stats { gap: 20px; }
    .hero-stat-value { font-size: 32px; }
    .glass-clock { min-width: 260px; padding: 25px; }
    .glass-clock .time { font-size: 42px; }
    
    .premium-stats { grid-template-columns: repeat(3, 1fr); gap: 18px; }
    .premium-stat-card { padding: 25px; }
    .stat-value-large { font-size: 36px; }
    .stat-icon-wrapper { width: 65px; height: 65px; font-size: 28px; }
    
    .section-title { font-size: 24px; margin: 40px 0 25px; }
    .quick-actions-grid { grid-template-columns: repeat(5, 1fr); gap: 18px; }
    .action-card { padding: 30px 20px; }
    .action-icon { width: 70px; height: 70px; font-size: 34px; }
    .action-label { font-size: 15px; }
    
    .chart-card { padding: 30px; }
    .chart-title { font-size: 19px; }
    .activity-section { padding: 35px; }
}

/* Tablet Portrait (768px - 1023px) - Key Breakpoint */
@media (max-width: 1023px) and (min-width: 768px) {
    body { font-size: 15px; }
    
    /* Navbar Adjustments */
    .navbar { padding: 15px 20px; flex-direction: column; gap: 15px; }
    .navbar-brand { width: 100%; justify-content: center; }
    .navbar h1 { font-size: 18px; text-align: center; }
    .navbar-logo { width: 40px; height: 40px; font-size: 20px; }
    .user-info { width: 100%; justify-content: space-between; padding: 0 10px; }
    .user-profile { padding: 8px 15px; font-size: 14px; }
    .user-avatar { width: 35px; height: 35px; font-size: 16px; }
    .btn-danger { padding: 10px 20px; font-size: 13px; }
    
    /* Main Content */
    .main-content { padding: 20px; }
    .hero-welcome { padding: 25px; margin-bottom: 25px; border-radius: 25px; }
    .hero-content { grid-template-columns: 1fr; gap: 20px; text-align: center; }
    .hero-text h2 { font-size: 28px; }
    .hero-text p { font-size: 14px; }
    .hero-stats { justify-content: center; gap: 15px; }
    .hero-stat-value { font-size: 30px; }
    .hero-stat-label { font-size: 12px; }
    .glass-clock { min-width: 220px; padding: 20px; margin: 0 auto; }
    .glass-clock .time { font-size: 36px; }
    .glass-clock .date { font-size: 13px; }
    
    /* Stats Grid */
    .premium-stats { grid-template-columns: repeat(2, 1fr); gap: 15px; }
    .premium-stat-card { padding: 20px; border-radius: 18px; }
    .stat-value-large { font-size: 32px; }
    .stat-icon-wrapper { width: 60px; height: 60px; font-size: 24px; margin-bottom: 15px; }
    .stat-details h4 { font-size: 12px; }
    .stat-trend { font-size: 12px; }
    
    /* Sections */
    .section-title { font-size: 20px; margin: 30px 0 20px; gap: 10px; }
    .section-title::before { width: 4px; height: 35px; }
    
    /* Quick Actions */
    .quick-actions-grid { grid-template-columns: repeat(3, 1fr); gap: 15px; }
    .action-card { padding: 25px 15px; border-radius: 18px; }
    .action-icon { width: 65px; height: 65px; font-size: 32px; margin-bottom: 12px; }
    .action-label { font-size: 13px; }
    
    /* Charts */
    .chart-grid { grid-template-columns: 1fr; gap: 20px; }
    .chart-card { padding: 25px; border-radius: 22px; }
    .chart-header { gap: 12px; margin-bottom: 20px; }
    .chart-icon { width: 45px; height: 45px; font-size: 22px; }
    .chart-title { font-size: 17px; }
    
    /* Activity Timeline */
    .activity-section { padding: 25px; margin-top: 30px; border-radius: 22px; }
    .activity-header h3 { font-size: 19px; }
    .timeline-item { padding: 15px; gap: 12px; }
    .student-name { font-size: 14px; }
    .timeline-details { font-size: 12px; }
    .status-badge { padding: 5px 12px; font-size: 10px; }
    
    /* AI Assistant */
    .ai-chat-container { width: calc(100% - 30px); right: 15px; bottom: 100px; max-height: 550px; }
    .ai-chat-messages { max-height: 320px; padding: 18px; }
    .ai-message-content { max-width: 85%; font-size: 13px; padding: 12px 15px; }
    .ai-assistant-btn { width: 60px; height: 60px; bottom: 25px; right: 15px; }
    .ai-assistant-btn i { font-size: 28px; }
}

/* Mobile Landscape / Small Tablet (600px - 767px) */
@media (max-width: 767px) and (min-width: 600px) {
    body { font-size: 14px; }
    
    /* Navbar - Compact */
    .navbar { flex-direction: column; padding: 12px 15px; gap: 12px; }
    .navbar-brand { justify-content: center; margin-bottom: 5px; }
    .navbar h1 { font-size: 16px; }
    .navbar-logo { width: 36px; height: 36px; font-size: 18px; }
    .user-info { flex-direction: column; gap: 10px; width: 100%; align-items: stretch; }
    .user-profile { width: 100%; justify-content: center; padding: 10px; }
    .btn-danger { width: 100%; padding: 10px; }
    
    /* Hero Section */
    .main-content { padding: 15px; }
    .hero-welcome { padding: 20px; margin-bottom: 20px; border-radius: 20px; }
    .hero-content { grid-template-columns: 1fr; gap: 18px; }
    .hero-text h2 { font-size: 24px; margin-bottom: 10px; }
    .hero-text p { font-size: 13px; }
    .hero-stats { gap: 12px; flex-wrap: wrap; }
    .hero-stat-item { flex: 0 0 48%; text-align: center; }
    .hero-stat-value { font-size: 26px; }
    .hero-stat-label { font-size: 11px; }
    .glass-clock { padding: 18px; min-width: 200px; }
    .clock-icon { font-size: 38px; margin-bottom: 12px; }
    .glass-clock .time { font-size: 32px; }
    .glass-clock .date { font-size: 12px; }
    
    /* Stats - Single Column */
    .premium-stats { grid-template-columns: 1fr; gap: 12px; }
    .premium-stat-card { padding: 18px; border-radius: 16px; }
    .stat-icon-wrapper { width: 55px; height: 55px; font-size: 22px; margin-bottom: 12px; }
    .stat-value-large { font-size: 28px; }
    .stat-details h4 { font-size: 11px; }
    .stat-trend { font-size: 11px; }
    
    /* Section Titles */
    .section-title { font-size: 18px; margin: 25px 0 15px; gap: 8px; }
    .section-title::before { width: 3px; height: 30px; }
    
    /* Quick Actions - 2 Column */
    .quick-actions-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
    .action-card { padding: 20px 12px; border-radius: 15px; }
    .action-icon { width: 55px; height: 55px; font-size: 26px; margin-bottom: 10px; }
    .action-label { font-size: 12px; }
    
    /* Charts */
    .chart-grid { grid-template-columns: 1fr; gap: 15px; }
    .chart-card { padding: 20px; border-radius: 20px; }
    .chart-header { gap: 10px; margin-bottom: 15px; }
    .chart-icon { width: 40px; height: 40px; font-size: 20px; }
    .chart-title { font-size: 16px; }
    
    /* Activity Section */
    .activity-section { padding: 20px; margin-top: 25px; border-radius: 20px; }
    .activity-header { gap: 10px; margin-bottom: 20px; }
    .activity-header i { font-size: 22px; }
    .activity-header h3 { font-size: 17px; }
    .timeline-item { padding: 12px; gap: 10px; border-radius: 12px; }
    .student-name { font-size: 13px; }
    .timeline-details { font-size: 11px; }
    .timeline-dot { width: 12px; height: 12px; }
    .status-badge { padding: 5px 10px; font-size: 9px; }
    
    /* AI Assistant */
    .ai-chat-container { width: calc(100% - 20px); right: 10px; bottom: 85px; max-height: 480px; }
    .ai-chat-header { padding: 15px; }
    .ai-chat-header h4 { font-size: 16px; }
    .ai-chat-header p { font-size: 12px; }
    .ai-avatar { width: 40px; height: 40px; font-size: 22px; }
    .ai-chat-messages { max-height: 280px; padding: 15px; }
    .ai-message-content { font-size: 12px; padding: 10px 13px; }
    .ai-quick-actions { padding: 12px; }
    .ai-quick-btn { padding: 8px 12px; font-size: 11px; }
    .ai-chat-input { padding: 12px; }
    .ai-chat-input input { padding: 10px 15px; font-size: 12px; }
    .ai-chat-input button { width: 42px; height: 42px; }
    .ai-assistant-btn { width: 55px; height: 55px; bottom: 18px; right: 10px; }
    .ai-assistant-btn i { font-size: 26px; }
}

/* Mobile Portrait (480px - 599px) */
@media (max-width: 599px) and (min-width: 480px) {
    body { font-size: 13px; }
    
    /* Navbar - Ultra Compact */
    .navbar { flex-direction: column; padding: 10px; gap: 10px; }
    .navbar-brand { justify-content: center; }
    .navbar h1 { font-size: 14px; }
    .navbar-logo { width: 32px; height: 32px; font-size: 16px; }
    .user-info { flex-direction: column; gap: 8px; width: 100%; }
    .user-profile { padding: 8px 12px; font-size: 12px; }
    .user-avatar { width: 30px; height: 30px; font-size: 14px; }
    .btn-danger { width: 100%; padding: 8px; font-size: 11px; }
    
    /* Hero Section */
    .main-content { padding: 12px; }
    .hero-welcome { padding: 15px; margin-bottom: 15px; border-radius: 15px; }
    .hero-content { gap: 15px; }
    .hero-text h2 { font-size: 20px; line-height: 1.3; }
    .hero-text p { font-size: 12px; margin-bottom: 12px; }
    .hero-stats { gap: 10px; }
    .hero-stat-item { flex: 0 0 30%; }
    .hero-stat-value { font-size: 22px; }
    .hero-stat-label { font-size: 9px; letter-spacing: 0.5px; }
    .glass-clock { padding: 15px; min-width: 180px; }
    .clock-icon { font-size: 32px; }
    .glass-clock .time { font-size: 26px; }
    .glass-clock .date { font-size: 11px; }
    
    /* Stats Cards */
    .premium-stats { grid-template-columns: 1fr; gap: 10px; }
    .premium-stat-card { padding: 15px; border-radius: 14px; }
    .premium-stat-card::before { height: 3px; }
    .stat-icon-wrapper { width: 48px; height: 48px; font-size: 20px; margin-bottom: 10px; border-radius: 14px; }
    .stat-value-large { font-size: 24px; }
    .stat-details h4 { font-size: 10px; margin-bottom: 8px; }
    .stat-trend { font-size: 10px; gap: 5px; }
    
    /* Sections */
    .section-title { font-size: 16px; margin: 20px 0 12px; }
    .section-title::before { width: 3px; height: 25px; }
    
    /* Quick Actions */
    .quick-actions-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
    .action-card { padding: 18px 10px; border-radius: 12px; }
    .action-card:hover { transform: translateY(-8px) scale(1.03); }
    .action-icon { width: 48px; height: 48px; font-size: 22px; margin-bottom: 8px; }
    .action-label { font-size: 11px; letter-spacing: 0.3px; }
    
    /* Charts */
    .chart-grid { gap: 12px; }
    .chart-card { padding: 18px; border-radius: 18px; }
    .chart-header { gap: 8px; margin-bottom: 12px; padding-bottom: 12px; }
    .chart-icon { width: 36px; height: 36px; font-size: 18px; border-radius: 10px; }
    .chart-title { font-size: 14px; }
    
    /* Activity Section */
    .activity-section { padding: 18px; margin-top: 20px; border-radius: 18px; }
    .activity-header { margin-bottom: 15px; gap: 8px; }
    .activity-header i { font-size: 20px; }
    .activity-header h3 { font-size: 15px; }
    .timeline-item { padding: 10px; gap: 8px; }
    .timeline-item:hover { transform: translateX(5px); }
    .student-name { font-size: 12px; margin-bottom: 4px; }
    .timeline-details { font-size: 10px; }
    .timeline-dot { width: 11px; height: 11px; }
    .status-badge { padding: 4px 10px; font-size: 9px; }
    .timeline-time { font-size: 10px; }
    
    /* AI Assistant */
    .ai-chat-container { width: calc(100% - 15px); right: 7.5px; bottom: 75px; max-height: 420px; border-radius: 20px; }
    .ai-chat-header { padding: 12px; }
    .ai-chat-header-info { gap: 10px; }
    .ai-avatar { width: 36px; height: 36px; font-size: 18px; }
    .ai-chat-header h4 { font-size: 14px; }
    .ai-chat-header p { font-size: 11px; }
    .ai-control-btn { width: 32px; height: 32px; font-size: 14px; }
    .ai-chat-messages { max-height: 240px; padding: 12px; }
    .ai-message { margin-bottom: 15px; gap: 8px; }
    .ai-message-avatar { width: 32px; height: 32px; font-size: 16px; }
    .ai-message-content { max-width: 85%; font-size: 11px; padding: 9px 12px; line-height: 1.5; }
    .ai-quick-actions { padding: 10px; }
    .ai-quick-actions p { font-size: 10px; margin-bottom: 8px; }
    .ai-quick-btn { padding: 7px 11px; font-size: 10px; margin: 3px; }
    .ai-chat-input { padding: 10px; gap: 8px; }
    .ai-chat-input input { padding: 9px 14px; font-size: 11px; }
    .ai-chat-input button { width: 38px; height: 38px; }
    .ai-assistant-btn { width: 50px; height: 50px; bottom: 15px; right: 7.5px; }
    .ai-assistant-btn i { font-size: 22px; }
}

/* Small Mobile (375px - 479px) */
@media (max-width: 479px) and (min-width: 375px) {
    body { font-size: 12px; }
    
    /* Navbar */
    .navbar { padding: 8px; gap: 8px; }
    .navbar h1 { font-size: 13px; }
    .navbar-logo { width: 30px; height: 30px; font-size: 15px; }
    .user-profile { padding: 6px 10px; font-size: 11px; }
    .user-avatar { width: 28px; height: 28px; font-size: 13px; }
    .btn-danger { padding: 7px; font-size: 10px; }
    
    /* Hero */
    .main-content { padding: 10px; }
    .hero-welcome { padding: 12px; margin-bottom: 12px; border-radius: 12px; }
    .hero-text h2 { font-size: 18px; }
    .hero-text p { font-size: 11px; }
    .hero-stat-value { font-size: 20px; }
    .hero-stat-label { font-size: 8px; }
    .glass-clock { padding: 12px; min-width: 160px; }
    .glass-clock .time { font-size: 22px; }
    .glass-clock .date { font-size: 10px; }
    
    /* Stats */
    .premium-stats { gap: 8px; }
    .premium-stat-card { padding: 12px; }
    .stat-icon-wrapper { width: 42px; height: 42px; font-size: 18px; }
    .stat-value-large { font-size: 22px; }
    .stat-details h4 { font-size: 9px; }
    
    /* Actions */
    .section-title { font-size: 14px; margin: 15px 0 10px; }
    .quick-actions-grid { gap: 8px; }
    .action-card { padding: 15px 8px; }
    .action-icon { width: 42px; height: 42px; font-size: 20px; }
    .action-label { font-size: 10px; }
    
    /* Charts & Activity */
    .chart-card { padding: 15px; }
    .chart-title { font-size: 13px; }
    .activity-section { padding: 15px; }
    .activity-header h3 { font-size: 13px; }
    .timeline-item { padding: 8px; }
    .student-name { font-size: 11px; }
    .timeline-details { font-size: 9px; }
    
    /* AI */
    .ai-chat-container { max-height: 380px; bottom: 65px; }
    .ai-chat-messages { max-height: 200px; padding: 10px; }
    .ai-message-content { font-size: 10px; padding: 8px 10px; }
    .ai-quick-btn { padding: 6px 9px; font-size: 9px; }
    .ai-assistant-btn { width: 45px; height: 45px; }
}

/* Extra Small Mobile (320px - 374px) */
@media (max-width: 374px) {
    body { font-size: 11px; }
    
    .navbar { padding: 6px; }
    .navbar h1 { font-size: 12px; }
    .navbar-logo { width: 28px; height: 28px; font-size: 14px; }
    .user-avatar { width: 26px; height: 26px; font-size: 12px; }
    
    .main-content { padding: 8px; }
    .hero-welcome { padding: 10px; border-radius: 10px; }
    .hero-text h2 { font-size: 16px; }
    .hero-text p { font-size: 10px; }
    .hero-stat-value { font-size: 18px; }
    .hero-stat-label { font-size: 7px; }
    
    .glass-clock { padding: 10px; min-width: 140px; }
    .glass-clock .time { font-size: 20px; }
    
    .premium-stats { gap: 6px; }
    .premium-stat-card { padding: 10px; }
    .stat-icon-wrapper { width: 38px; height: 38px; font-size: 16px; }
    .stat-value-large { font-size: 20px; }
    
    .section-title { font-size: 13px; margin: 12px 0 8px; }
    .quick-actions-grid { gap: 6px; }
    .action-card { padding: 12px 6px; }
    .action-icon { width: 38px; height: 38px; font-size: 18px; }
    .action-label { font-size: 9px; }
    
    .chart-card { padding: 12px; }
    .activity-section { padding: 12px; }
    .timeline-item { padding: 6px; }
    
    .ai-chat-container { max-height: 340px; bottom: 55px; }
    .ai-chat-messages { max-height: 180px; }
    .ai-assistant-btn { width: 40px; height: 40px; bottom: 8px; }
    .ai-assistant-btn i { font-size: 18px; }
}

/* Landscape Mode Optimization */
@media (max-height: 500px) and (orientation: landscape) {
    .hero-welcome { padding: 15px; margin-bottom: 15px; }
    .hero-text h2 { font-size: 20px; }
    .premium-stats { grid-template-columns: repeat(4, 1fr); gap: 10px; }
    .premium-stat-card { padding: 12px; }
    .section-title { margin: 15px 0 10px; }
    .ai-chat-container { max-height: 300px; }
    .ai-chat-messages { max-height: 150px; }
}

/* Print Styles */
@media print {
    .navbar, .ai-assistant-btn, .ai-chat-container, .particles { display: none !important; }
    .main-content { padding: 0; max-width: 100%; }
    .hero-welcome, .premium-stat-card, .action-card, .chart-card { 
        break-inside: avoid; 
        box-shadow: none !important;
        border: 1px solid #ddd;
    }
    body { background: white !important; }
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
    .premium-stat-card, .action-card, .chart-card { border: 2px solid #000; }
    .hero-text h2, .stat-value-large { filter: contrast(1.5); }
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
    .particle, .navbar-logo, .action-card:hover { animation: none !important; }
}

/* Dark Mode Support (if needed in future) */
@media (prefers-color-scheme: dark) {
    /* Add dark mode styles here if required */
}
    </style>
</head>
<body>
    <div class="particles" id="particles"></div>
    <nav class="navbar">
        <div class="navbar-brand">
            <div class="navbar-logo">🎓</div>
            <h1>NIT AMMS - Admin Command Center</h1>
        </div>
        <div class="user-info">
            <div class="user-profile">
                <div class="user-avatar"><?php echo strtoupper(substr($user['full_name'], 0, 1)); ?></div>
                <span><?php echo htmlspecialchars($user['full_name']); ?></span>
            </div>
            <a href="../logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <div class="hero-welcome">
            <div class="hero-background"></div>
            <div class="hero-content">
                <div class="hero-text">
                    <h2><?php echo $greeting; ?>, <?php echo htmlspecialchars($user['full_name']); ?>! 👋</h2>
                    <p>Welcome back to your command center. Here's your comprehensive overview of NIT College today.</p>
                    <div class="hero-stats">
                         <div class="hero-stat-item"><div class="hero-stat-value"><?php echo $stats['students']; ?></div><div class="hero-stat-label">Total Students</div></div>
                    </div>
                </div>
                <div class="glass-clock">
                    <div class="clock-icon">⏰</div>
                    <div class="time" id="liveClock">--:--:--</div>
                    <div class="date" id="liveDate">Loading...</div>
                </div>
            </div>
        </div>

        <!-- Premium Stats Cards -->
        <div class="premium-stats">
            <div class="premium-stat-card">
                <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #6a11cb, #2575fc);"><i class="fas fa-user-tie"></i></div>
                <div class="stat-details"><h4>Total Teachers</h4><div class="stat-value-large"><?php echo $stats['teachers']; ?></div><div class="stat-trend"><i class="fas fa-chalkboard-teacher"></i> Active</div></div>
            </div>

            <div class="premium-stat-card">
                <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #ffc107, #ffb300);"><i class="fas fa-users"></i></div>
                <div class="stat-details"><h4>Total Students</h4><div class="stat-value-large"><?php echo $stats['students']; ?></div><div class="stat-trend"><i class="fas fa-graduation-cap"></i> Enrolled</div></div>
            </div> 
            
           
        </div>

        <!-- Quick Actions -->
        <div class="section-title"><i class="fas fa-bolt"></i> Quick Actions</div>
        <div class="quick-actions-grid">
            <a href="daily_attendance_report.php" class="action-card"><div class="action-icon"><i class="fas fa-calendar-check"></i></div><div class="action-label">Daily Report</div></a>
            <a href="manage_students.php" class="action-card"><div class="action-icon"><i class="fas fa-user-graduate"></i></div><div class="action-label">Manage Students</div></a>
            <a href="manage_teachers.php" class="action-card"><div class="action-icon"><i class="fas fa-chalkboard-teacher"></i></div><div class="action-label">Manage Teachers</div></a>
            <a href="manage_classes.php" class="action-card"><div class="action-icon"><i class="fas fa-book-open"></i></div><div class="action-label">Manage Classes</div></a>
            <a href="manage_parents.php" class="action-card"><div class="action-icon"><i class="fas fa-user-friends"></i></div><div class="action-label">Manage Parents</div></a>
            <a href="view_attendance_reports.php" class="action-card"><div class="action-icon"><i class="fas fa-chart-bar"></i></div><div class="action-label">View Reports</div></a>
           <a href="report_summary.php" class="action-card"><div class="action-icon"><i class="fas fa-chart-bar"></i></div><div class="action-label"> Reports Summary </div></a>
           
           
            <a href="manage_departments.php" class="action-card"><div class="action-icon"><i class="fas fa-building"></i></div><div class="action-label">Departments</div></a>
           <a href="manage_subjects.php" class="action-card"><div class="action-icon"><i class="fas fa-book-open"></i></div><div class="action-label">Manage Subject</div></a>
            <a href="manage_subject_teachers.php" class="action-card"><div class="action-icon"><i class="fas fa-chalkboard-teacher"></i></div><div class="action-label">📚 Manage Subject Teachers</div></a>
           
           <a href="view_all_marks.php" class="action-card"><div class="action-icon"><i class="fas fa-chalkboard-teacher"></i></div><div class="action-label">view all mark</div></a>
           
        
           

            <!-- Add this in the Quick Actions section after other links: -->
<a href="manage_notices.php" class="action-card">
    <div class="action-icon"><i class="fas fa-bullhorn"></i></div>
    <div class="action-label">Manage Notices</div>
</a>
<a href="all_student_resume.php" class="action-card">
    <div class="action-icon"><i class="fas fa-bullhorn"></i></div>
    <div class="action-label">Manage Student Resume</div>
</a>

        </div>

        <!-- System Overview -->
        <div class="section-title"><i class="fas fa-th-large"></i> System Overview</div>
        <div class="premium-stats">
            <div class="premium-stat-card">
                <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #667eea, #764ba2);"><i class="fas fa-building"></i></div>
                <div class="stat-details"><h4>Departments</h4><div class="stat-value-large"><?php echo $stats['departments']; ?></div><a href="manage_departments.php" class="btn btn-info btn-sm">Manage</a></div>
            </div>
            <div class="premium-stat-card">
                <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #f093fb, #f5576c);"><i class="fas fa-user-tie"></i></div>
                <div class="stat-details"><h4>HODs</h4><div class="stat-value-large"><?php echo $stats['hods']; ?></div><a href="manage_hod.php" class="btn btn-info btn-sm">Manage</a></div>
            </div>
            <div class="premium-stat-card">
                <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #4facfe, #00f2fe);"><i class="fas fa-chalkboard-teacher"></i></div>
                <div class="stat-details"><h4>Teachers</h4><div class="stat-value-large"><?php echo $stats['teachers']; ?></div><a href="manage_teachers.php" class="btn btn-info btn-sm">Manage</a></div>
            </div>
            <div class="premium-stat-card">
                <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #43e97b, #38f9d7);"><i class="fas fa-book"></i></div>
                <div class="stat-details"><h4>Classes</h4><div class="stat-value-large"><?php echo $stats['classes']; ?></div><a href="manage_classes.php" class="btn btn-info btn-sm">Manage</a></div>
            </div>
            <div class="premium-stat-card">
                <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #fa709a, #fee140);"><i class="fas fa-user-friends"></i></div>
                <div class="stat-details"><h4>Parents</h4><div class="stat-value-large"><?php echo $stats['parents']; ?></div><a href="manage_parents.php" class="btn btn-info btn-sm">Manage</a></div>
            </div>

            
        </div>

        <!-- Charts -->
        <div class="charts-section">
            <div class="section-title"><i class="fas fa-chart-pie"></i> Analytics & Insights</div>
            <div class="chart-grid">
                <div class="chart-card"><div class="chart-header"><div class="chart-icon"><i class="fas fa-chart-bar"></i></div><div class="chart-title">Section-Wise Attendance</div></div><canvas id="sectionBarChart"></canvas></div>
                <div class="chart-card"><div class="chart-header"><div class="chart-icon"><i class="fas fa-pie-chart"></i></div><div class="chart-title">Today's Distribution</div></div><canvas id="overallPieChart"></canvas></div>
            </div>
        </div>

        <!-- Activity Timeline -->
        <div class="activity-section">
            <div class="activity-header"><i class="fas fa-history" style="font-size: 28px; color: #667eea;"></i><h3>Recent Attendance Activities</h3></div>
            <?php if ($recent_activities->num_rows > 0): ?>
                <?php while ($activity = $recent_activities->fetch_assoc()): ?>
                <div class="timeline-item">
                    <div class="timeline-dot <?php echo $activity['status']; ?>"></div>
                    <div class="timeline-content">
                        <div class="student-name"><i class="fas fa-user"></i> <?php echo htmlspecialchars($activity['student_name']); ?></div>
                        <div class="timeline-details"><i class="fas fa-id-card"></i> Roll: <?php echo htmlspecialchars($activity['roll_number']); ?> • <i class="fas fa-book"></i> <?php echo htmlspecialchars($activity['class_name']); ?></div>
                    </div>
                    <div class="timeline-time">
                        <span class="status-badge badge-<?php echo $activity['status'] === 'present' ? 'success' : ($activity['status'] === 'absent' ? 'danger' : 'warning'); ?>"><?php echo strtoupper($activity['status']); ?></span>
                        <div style="font-size: 12px; color: #999; margin-top: 5px;"><i class="fas fa-clock"></i> <?php echo date('d M, H:i', strtotime($activity['marked_at'])); ?></div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 60px; color: #999;"><i class="fas fa-inbox" style="font-size: 60px; margin-bottom: 20px; opacity: 0.5;"></i><p>No recent attendance activities</p></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- AI Assistant Button -->
    <button class="ai-assistant-btn" onclick="toggleAIChat()" title="Gemini AI Assistant">
        <i class="fas fa-brain"></i>
    </button>

    <!-- AI Chat Container -->
    <div class="ai-chat-container" id="aiChatContainer">
        <div class="ai-chat-header">
            <div class="ai-chat-header-info">
                <div class="ai-avatar">✨</div>
                <div>
                    <h4>Gemini AI Assistant</h4>
                    <p>Powered by Google Gemini</p>
                </div>
            </div>
            <div class="ai-header-controls">
                <button class="ai-control-btn" id="voiceBtn" onclick="toggleVoice()" title="Voice Input">
                    <i class="fas fa-microphone"></i>
                </button>
                <button class="ai-control-btn" id="speakerBtn" onclick="toggleSpeaker()" title="Voice Output">
                    <i class="fas fa-volume-up"></i>
                </button>
                <button class="ai-control-btn" onclick="toggleAIChat()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div class="ai-chat-messages" id="aiChatMessages">
            <div class="ai-message bot">
                <div class="ai-message-avatar">✨</div>
                <div class="ai-message-content">
                    <strong>👋 Hello <?php echo htmlspecialchars($user['full_name']); ?>!</strong><br><br>
                    I'm your intelligent Gemini AI Assistant for NIT AMMS. I have access to all your system data and can help you with:<br><br>
                    <strong>📊 Your Current Stats:</strong><br>
                    • Students: <?php echo $stats['students']; ?> (<?php echo $stats['today_present']; ?> present today)<br>
                    • Teachers: <?php echo $stats['teachers']; ?><br>
                    • HODs: <?php echo $stats['hods']; ?><br>
                    • Departments: <?php echo $stats['departments']; ?><br>
                    • Attendance Rate: <?php echo $stats['attendance_rate']; ?>%<br><br>
                    <strong>🎯 I can help with:</strong><br>
                    • Student, Teacher, HOD & Parent management<br>
                    • Attendance tracking & reports<br>
                    • Department & Class information<br>
                    • Analytics & insights<br>
                    • Voice commands (click 🎤)<br><br>
                    How can I assist you today?
                </div>
            </div>
        </div>
        <div class="ai-quick-actions">
            <p>✨ Quick Actions:</p>
            <button class="ai-quick-btn" onclick="askQuestion('Show me today\'s attendance summary')">Today's Summary</button>
            <button class="ai-quick-btn" onclick="askQuestion('How to add a new student?')">Add Student</button>
            <button class="ai-quick-btn" onclick="askQuestion('Show teacher statistics')">Teacher Stats</button>
            <button class="ai-quick-btn" onclick="askQuestion('Department overview')">Departments</button>
            <button class="ai-quick-btn" onclick="askQuestion('Tell me about all features')">All Features</button>
            <button class="ai-quick-btn" onclick="askQuestion('Generate attendance report')">Reports</button>
        </div>
        <div class="ai-chat-input">
            <div class="voice-wave" id="voiceWave">
                <div class="voice-bar"></div>
                <div class="voice-bar"></div>
                <div class="voice-bar"></div>
                <div class="voice-bar"></div>
                <div class="voice-bar"></div>
            </div>
            <input type="text" id="aiInput" placeholder="Ask me anything or use voice..." onkeypress="if(event.key==='Enter')sendAIMessage()">
            <button onclick="sendAIMessage()" id="sendBtn"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>

    <!-- Footer -->
    <div style="background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 50%, #2a3254 100%); position: relative; overflow: hidden; margin-top: 60px;">
        <div style="height: 4px; background: linear-gradient(90deg, #667eea, #764ba2, #f093fb, #667eea); background-size: 200% 100%; animation: gradientMove 3s linear infinite;"></div>
        <div style="max-width: 1200px; margin: 0 auto; padding: 50px 30px 30px; text-align: center;">
            <p style="color: #fff; font-size: 16px; margin-bottom: 20px;">✨ Designed & Developed by</p>
            <a href="https://himanshufullstackdeveloper.github.io/techyugsoftware/" style="display: inline-block; color: #fff; font-size: 20px; font-weight: 800; text-decoration: none; padding: 15px 40px; border: 2px solid #667eea; border-radius: 50px; background: linear-gradient(135deg, rgba(102, 126, 234, 0.4), rgba(118, 75, 162, 0.4)); margin-bottom: 30px;">🚀 Techyug Software Pvt. Ltd.</a>
           <p style="color: #fff; font-size: 16px; margin-bottom: 20px;">✨ Developnment Team</p>
           <a href="https://himanshufullstackdeveloper.github.io/portfoilohimanshu/" style="display: inline-block; color: #fff; font-size: 20px; font-weight: 800; text-decoration: none; padding: 15px 40px; border: 2px solid #667eea; border-radius: 50px; background: linear-gradient(135deg, rgba(102, 126, 234, 0.4), rgba(118, 75, 162, 0.4)); margin-bottom: 30px;">MR.Himanshu Patil</a>
           <a href="https://devpranaypanore.github.io/Pranaypanore-live-.html/" style="display: inline-block; color: #fff; font-size: 20px; font-weight: 800; text-decoration: none; padding: 15px 40px; border: 2px solid #667eea; border-radius: 50px; background: linear-gradient(135deg, rgba(102, 126, 234, 0.4), rgba(118, 75, 162, 0.4)); margin-bottom: 30px;">MR.Pranay Panore</a>
         
            <p style="color: #888; font-size: 14px;">© 2025 NIT AMMS. All rights reserved.</p>
        </div>
    </div>
    <style>@keyframes gradientMove { 0% { background-position: 0% 50%; } 100% { background-position: 200% 50%; } }</style>

    <script>
    // System Stats for AI
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
    let speakerEnabled = true;

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
            document.getElementById('aiInput').style.display = 'none';
        };

        recognition.onend = () => {
            isListening = false;
            document.getElementById('voiceBtn').classList.remove('active');
            document.getElementById('voiceWave').classList.remove('active');
            document.getElementById('aiInput').style.display = 'block';
        };

        recognition.onresult = (event) => {
            const transcript = event.results[0][0].transcript;
            document.getElementById('aiInput').value = transcript;
            sendAIMessage();
        };

        recognition.onerror = (event) => {
            console.error('Speech recognition error:', event.error);
            isListening = false;
            document.getElementById('voiceBtn').classList.remove('active');
            document.getElementById('voiceWave').classList.remove('active');
            document.getElementById('aiInput').style.display = 'block';
        };
    }

    function toggleVoice() {
        if (!recognition) {
            alert('Voice recognition is not supported in your browser. Please use Chrome, Edge, or Safari.');
            return;
        }

        if (isListening) {
            recognition.stop();
        } else {
            recognition.start();
        }
    }

    function toggleSpeaker() {
        speakerEnabled = !speakerEnabled;
        const btn = document.getElementById('speakerBtn');
        if (speakerEnabled) {
            btn.classList.remove('active');
            btn.innerHTML = '<i class="fas fa-volume-up"></i>';
        } else {
            btn.classList.add('active');
            btn.innerHTML = '<i class="fas fa-volume-mute"></i>';
        }
    }

    function speak(text) {
        if (!speakerEnabled || isSpeaking) return;

        const utterance = new SpeechSynthesisUtterance(text.replace(/<[^>]*>/g, '').replace(/•/g, ''));
        utterance.rate = 1.1;
        utterance.pitch = 1;
        utterance.volume = 1;

        utterance.onstart = () => { isSpeaking = true; };
        utterance.onend = () => { isSpeaking = false; };
        utterance.onerror = () => { isSpeaking = false; };

        speechSynthesis.speak(utterance);
    }

    // Particles
    function createParticles() { const p = document.getElementById('particles'); for (let i = 0; i < 30; i++) { const d = document.createElement('div'); d.className = 'particle'; d.style.left = Math.random() * 100 + '%'; d.style.top = Math.random() * 100 + '%'; d.style.width = d.style.height = Math.random() * 8 + 3 + 'px'; d.style.animationDelay = Math.random() * 15 + 's'; d.style.animationDuration = (Math.random() * 10 + 10) + 's'; p.appendChild(d); } } createParticles();

    // Clock
    function updateClock() { const now = new Date(); document.getElementById('liveClock').textContent = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true }); document.getElementById('liveDate').textContent = now.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }); } setInterval(updateClock, 1000); updateClock();

    // Charts
    const sectionLabels = <?php echo json_encode($section_labels); ?>;
    const presentData = <?php echo json_encode($present_data); ?>;
    const absentData = <?php echo json_encode($absent_data); ?>;
    const lateData = <?php echo json_encode($late_data); ?>;
    
    Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
    new Chart(document.getElementById('sectionBarChart'), { type: 'bar', data: { labels: sectionLabels, datasets: [{ label: 'Present', data: presentData, backgroundColor: 'rgba(40, 167, 69, 0.8)', borderRadius: 10 }, { label: 'Absent', data: absentData, backgroundColor: 'rgba(220, 53, 69, 0.8)', borderRadius: 10 }, { label: 'Late', data: lateData, backgroundColor: 'rgba(255, 193, 7, 0.8)', borderRadius: 10 }] }, options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true }, x: { grid: { display: false } } } } });
    
    const totalPresent = presentData.reduce((a, b) => a + b, 0);
    const totalAbsent = absentData.reduce((a, b) => a + b, 0);
    const totalLate = lateData.reduce((a, b) => a + b, 0);
    new Chart(document.getElementById('overallPieChart'), { type: 'doughnut', data: { labels: ['Present', 'Absent', 'Late'], datasets: [{ data: [totalPresent, totalAbsent, totalLate], backgroundColor: ['rgba(40, 167, 69, 0.8)', 'rgba(220, 53, 69, 0.8)', 'rgba(255, 193, 7, 0.8)'], borderWidth: 0, cutout: '70%' }] }, options: { responsive: true, plugins: { legend: { position: 'bottom' } } } });

    // AI Assistant Functions
    function toggleAIChat() { const c = document.getElementById('aiChatContainer'); c.style.display = c.style.display === 'flex' ? 'none' : 'flex'; if (c.style.display === 'flex') { document.getElementById('aiInput').focus(); } }
    
    function askQuestion(q) { document.getElementById('aiInput').value = q; sendAIMessage(); }
    
    async function sendAIMessage() {
        const input = document.getElementById('aiInput');
        const msg = input.value.trim();
        if (!msg) return;
        
        const messagesDiv = document.getElementById('aiChatMessages');
        const sendBtn = document.getElementById('sendBtn');
        
        // Add user message
        messagesDiv.innerHTML += `<div class="ai-message user"><div class="ai-message-avatar">${systemStats.adminName.charAt(0).toUpperCase()}</div><div class="ai-message-content">${msg}</div></div>`;
        input.value = '';
        sendBtn.disabled = true;
        
        // Show typing indicator
        messagesDiv.innerHTML += `<div class="ai-message bot" id="typingIndicator"><div class="ai-message-avatar">✨</div><div class="ai-message-content"><div class="typing-indicator"><span></span><span></span><span></span></div></div></div>`;
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
        
        // Get AI response
        try {
            const response = await getGeminiResponse(msg);
            document.getElementById('typingIndicator')?.remove();
            messagesDiv.innerHTML += `<div class="ai-message bot"><div class="ai-message-avatar">✨</div><div class="ai-message-content">${response}</div></div>`;
            speak(response);
        } catch (error) {
            document.getElementById('typingIndicator')?.remove();
            const fallbackResponse = getLocalResponse(msg);
            messagesDiv.innerHTML += `<div class="ai-message bot"><div class="ai-message-avatar">✨</div><div class="ai-message-content">${fallbackResponse}</div></div>`;
            speak(fallbackResponse);
        }
        
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
        sendBtn.disabled = false;
    }
    
    async function getGeminiResponse(query) {
        // Note: Replace 'YOUR_GEMINI_API_KEY' with actual API key
        const API_KEY = 'YOUR_GEMINI_API_KEY';
        const API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';
        
        const systemContext = `You are an intelligent AI assistant for NIT AMMS (Attendance Management System). You are helping ${systemStats.adminName}, the system administrator.

Current System Statistics:
- Total Students: ${systemStats.students}
- Present Today: ${systemStats.todayPresent}
- Absent Today: ${systemStats.todayAbsent}
- Attendance Rate: ${systemStats.attendanceRate}%
- Total Teachers: ${systemStats.teachers}
- Total HODs: ${systemStats.hods}
- Total Departments: ${systemStats.departments}
- Total Classes: ${systemStats.classes}
- Total Parents: ${systemStats.parents}

Provide helpful, concise responses with actionable information. Format responses with HTML tags like <strong>, <br>, and bullet points using •. Include relevant statistics when applicable.`;

        try {
            const response = await fetch(`${API_URL}?key=${API_KEY}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    contents: [{
                        parts: [{
                            text: `${systemContext}\n\nUser Question: ${query}`
                        }]
                    }],
                    generationConfig: {
                        temperature: 0.7,
                        maxOutputTokens: 800,
                    }
                })
            });

            const data = await response.json();
            if (data.candidates && data.candidates[0]?.content?.parts[0]?.text) {
                return data.candidates[0].content.parts[0].text;
            }
            throw new Error('Invalid response from Gemini');
        } catch (error) {
            console.error('Gemini API Error:', error);
            return getLocalResponse(query);
        }
    }
    
    function getLocalResponse(q) {
        const query = q.toLowerCase();
        
        // Personal Statistics
        if (query.includes('my') || query.includes('today') && query.includes('summary')) {
            return `<strong>📊 Your Dashboard Summary, ${systemStats.adminName}!</strong><br><br>
                <strong>👥 User Management:</strong><br>
                • Students: ${systemStats.students} (${systemStats.todayPresent} present, ${systemStats.todayAbsent} absent)<br>
                • Teachers: ${systemStats.teachers}<br>
                • HODs: ${systemStats.hods}<br>
                • Parents: ${systemStats.parents}<br><br>
                <strong>🏢 Academic:</strong><br>
                • Departments: ${systemStats.departments}<br>
                • Classes: ${systemStats.classes}<br><br>
                <strong>📈 Today's Performance:</strong><br>
                • Attendance Rate: ${systemStats.attendanceRate}%<br>
                • Status: ${systemStats.attendanceRate > 75 ? '✅ Excellent!' : systemStats.attendanceRate > 60 ? '⚠️ Good' : '❌ Needs Attention'}<br><br>
                ${systemStats.attendanceRate < 70 ? '💡 <strong>Tip:</strong> Consider sending reminders to improve attendance.' : '🎉 Keep up the great work!'}`;
        }
        
        // Student Management
        if (query.includes('add') && query.includes('student')) {
            return `<strong>📝 Adding a New Student - Step by Step</strong><br><br>
                <strong>1. Navigate:</strong><br>
                Go to <a href="manage_students.php">Manage Students</a> page<br><br>
                <strong>2. Click Button:</strong><br>
                Click "➕ Add New Student" (top right)<br><br>
                <strong>3. Fill Required Info:</strong><br>
                • Roll Number (e.g., CSE2023001) *Required<br>
                • Full Name *Required<br>
                • Email Address *Required<br>
                • Phone Number<br>
                • Password *Required<br>
                • Department (Select from ${systemStats.departments} departments)<br>
                • Class/Section<br>
                • Year (1st-4th)<br>
                • Semester (1-8)<br>
                • Admission Year<br>
                • Profile Photo (Optional)<br><br>
                <strong>4. Submit:</strong><br>
                Click "Add Student" button<br><br>
                <strong>✅ Done!</strong> Student can now login with:<br>
                • Username: Roll Number<br>
                • Password: As set<br><br>
                <strong>Current:</strong> You have ${systemStats.students} students`;
        }
        
        if (query.includes('student') && (query.includes('manage') || query.includes('edit') || query.includes('view'))) {
            return `<strong>👨‍🎓 Student Management Portal</strong><br><br>
                📍 <a href="manage_students.php">Manage Students</a><br><br>
                <strong>🎯 Available Features:</strong><br>
                • View all ${systemStats.students} students with profile photos<br>
                • Search by name, roll number, or email<br>
                • Add new students with complete details<br>
                • Edit student information<br>
                • Activate/Deactivate student accounts<br>
                • Upload/Update profile photos<br>
                • View attendance history<br>
                • Export student list<br><br>
                <strong>📊 Current Statistics:</strong><br>
                • Total Students: ${systemStats.students}<br>
                • Present Today: ${systemStats.todayPresent}<br>
                • Absent Today: ${systemStats.todayAbsent}<br>
                • Attendance Rate: ${systemStats.attendanceRate}%<br><br>
                <strong>💡 Quick Tip:</strong> Use the search feature to quickly find students!`;
        }
        
        // Teacher Management
        if (query.includes('teacher')) {
            return `<strong>👨‍🏫 Teacher Management</strong><br><br>
                📍 <a href="manage_teachers.php">Manage Teachers</a><br><br>
                <strong>➕ Add New Teacher:</strong><br>
                1. Click "Add New Teacher" button<br>
                2. Enter username (unique identifier)<br>
                3. Set secure password<br>
                4. Add full name<br>
                5. Enter email & phone number<br>
                6. Select department<br>
                7. Upload profile photo (optional)<br>
                8. Click "Add Teacher"<br><br>
                <strong>🎯 Management Features:</strong><br>
                • View all ${systemStats.teachers} teachers<br>
                • Search & filter teachers<br>
                • Edit teacher details<br>
                • Activate/Deactivate accounts<br>
                • Assign to classes<br>
                • View teaching schedule<br><br>
                <strong>📊 Current:</strong> ${systemStats.teachers} active teachers`;
        }
        
        // HOD Management
        if (query.includes('hod')) {
            return `<strong>👔 HOD Management System</strong><br><br>
                📍 <a href="manage_hod.php">Manage HODs</a><br><br>
                <strong>➕ Add New HOD:</strong><br>
                1. Navigate to Manage HODs<br>
                2. Click "Add HOD" button<br>
                3. Enter credentials (username, password)<br>
                4. Add personal details (name, email, phone)<br>
                5. Select department to manage<br>
                6. Upload profile photo (optional)<br>
                7. Submit form<br><br>
                <strong>🎯 HOD Capabilities:</strong><br>
                • View department teachers & students<br>
                • Monitor department attendance<br>
                • Generate department reports<br>
                • Manage department classes<br>
                • Access analytics dashboard<br><br>
                <strong>📊 System Status:</strong><br>
                • Active HODs: ${systemStats.hods}<br>
                • Departments: ${systemStats.departments}<br>
                • Teachers Under HODs: ${systemStats.teachers}<br><br>
                <strong>💡 Note:</strong> Each department should have one HOD`;
        }
        
        // Parent Management
        if (query.includes('parent')) {
            return `<strong>👨‍👩‍👦 Parent Management Portal</strong><br><br>
                📍 <a href="manage_parents.php">Manage Parents</a><br><br>
                <strong>➕ Add New Parent:</strong><br>
                1. Go to Manage Parents page<br>
                2. Click "Add Parent" button<br>
                3. Enter parent's name<br>
                4. Add email & phone number<br>
                5. Create password for parent login<br>
                6. Link to student (select from ${systemStats.students} students)<br>
                7. Select relationship (Father/Mother/Guardian)<br>
                8. Save details<br><br>
                <strong>🎯 Parent Portal Features:</strong><br>
                • View child's real-time attendance<br>
                • Receive attendance notifications<br>
                • Track academic performance<br>
                • Access attendance history<br>
                • Download reports<br><br>
                <strong>📊 Current Statistics:</strong><br>
                • Total Parents: ${systemStats.parents}<br>
                • Linked Students: ${systemStats.students}<br><br>
                <strong>💡 Tip:</strong> Parents get instant notifications when attendance is marked!`;
        }
        
        // Class Management
        if (query.includes('class') && (query.includes('add') || query.includes('manage') || query.includes('create'))) {
            return `<strong>📚 Class Management System</strong><br><br>
                📍 <a href="manage_classes.php">Manage Classes</a><br><br>
                <strong>➕ Create New Class:</strong><br>
                1. Navigate to Manage Classes<br>
                2. Click "➕ Add Class with Teacher"<br>
                3. Select Department (from ${systemStats.departments} options)<br>
                4. Choose Section (e.g., CSE-A, CSE-B, ECE-A)<br>
                5. Select Year (1st Year - 4th Year)<br>
                6. Choose Semester (1-8)<br>
                7. Enter Academic Year (e.g., 2024-2025)<br>
                8. Assign Teacher (from ${systemStats.teachers} teachers)<br>
                9. Add Subject (optional)<br>
                10. Submit form<br><br>
                <strong>📌 Important Notes:</strong><br>
                • Same section can have multiple teachers for different subjects<br>
                • Each class must have at least one teacher<br>
                • Teachers can handle multiple classes<br><br>
                <strong>📊 Current:</strong> ${systemStats.classes} active classes<br><br>
                <strong>💡 Pro Tip:</strong> Organize classes by sections for better management!`;
        }
        
        // Department Management
        if (query.includes('department')) {
            return `<strong>🏢 Department Management</strong><br><br>
                📍 <a href="manage_departments.php">Manage Departments</a><br><br>
                <strong>➕ Add New Department:</strong><br>
                1. Click "Add Department" button<br>
                2. Enter department name (e.g., Computer Science Engineering)<br>
                3. Add department code (e.g., CSE, ECE, ME)<br>
                4. Assign HOD (optional, from ${systemStats.hods} HODs)<br>
                5. Add description (optional)<br>
                6. Save department<br><br>
                <strong>🎯 Management Features:</strong><br>
                • View all ${systemStats.departments} departments<br>
                • Edit department details<br>
                • Assign/Change HOD<br>
                • View department statistics<br>
                • Delete unused departments<br><br>
                <strong>📊 Department Hierarchy:</strong><br>
                • Departments: ${systemStats.departments}<br>
                • HODs: ${systemStats.hods}<br>
                • Teachers: ${systemStats.teachers}<br>
                • Classes: ${systemStats.classes}<br>
                • Students: ${systemStats.students}<br><br>
                <strong>💡 Best Practice:</strong> Assign HOD before adding teachers to department`;
        }
        
        // Attendance Reports
        if (query.includes('report') || query.includes('attendance') && query.includes('view')) {
            return `<strong>📊 Attendance Reports Center</strong><br><br>
                <strong>📅 Daily Report:</strong><br>
                📍 <a href="daily_attendance_report.php">Daily Attendance Report</a><br>
                • Today's section-wise breakdown<br>
                • 1st & 5th lecture statistics<br>
                • Present/Absent/Late counts<br>
                • Print-ready format<br>
                • Export to PDF/Excel<br><br>
                <strong>📈 Detailed Reports:</strong><br>
                📍 <a href="view_attendance_reports.php">View Attendance Reports</a><br>
                • Filter by date range<br>
                • Filter by class/section<br>
                • Filter by department<br>
                • Student-wise attendance<br>
                • Percentage calculations<br>
                • Visual charts & graphs<br><br>
                <strong>📊 Today's Statistics:</strong><br>
                • Total Present: ${systemStats.todayPresent}<br>
                • Total Absent: ${systemStats.todayAbsent}<br>
                • Attendance Rate: ${systemStats.attendanceRate}%<br>
                • Status: ${systemStats.attendanceRate > 75 ? '🟢 Excellent' : systemStats.attendanceRate > 60 ? '🟡 Good' : '🔴 Needs Improvement'}<br><br>
                <strong>💡 Quick Actions:</strong><br>
                • Generate monthly report<br>
                • Send low attendance alerts<br>
                • Export data for analysis`;
        }
        
        // All Features Overview
        if (query.includes('all feature') || query.includes('everything') || query.includes('what can') || query.includes('help')) {
            return `<strong>🎯 Complete Admin Control Panel - ${systemStats.adminName}</strong><br><br>
                <strong>📊 Dashboard Overview:</strong><br>
                • Real-time statistics & analytics<br>
                • Live attendance tracking<br>
                • Interactive charts & graphs<br>
                • Recent activity timeline<br><br>
                <strong>👥 User Management:</strong><br>
                • <a href="manage_students.php">Students</a> (${systemStats.students} total, ${systemStats.todayPresent} present today)<br>
                • <a href="manage_teachers.php">Teachers</a> (${systemStats.teachers} active)<br>
                • <a href="manage_hod.php">HODs</a> (${systemStats.hods} department heads)<br>
                • <a href="manage_parents.php">Parents</a> (${systemStats.parents} registered)<br><br>
                <strong>🏫 Academic Management:</strong><br>
                • <a href="manage_departments.php">Departments</a> (${systemStats.departments} departments)<br>
                • <a href="manage_classes.php">Classes</a> (${systemStats.classes} active classes)<br>
                • Subject allocation & scheduling<br>
                • Academic year management<br><br>
                <strong>📈 Attendance & Reports:</strong><br>
                • <a href="daily_attendance_report.php">Daily Reports</a> (Today: ${systemStats.attendanceRate}%)<br>
                • <a href="view_attendance_reports.php">Detailed Analytics</a><br>
                • Custom date range reports<br>
                • Export to PDF/Excel<br>
                • Low attendance alerts<br><br>
                <strong>🎤 AI Assistant Features:</strong><br>
                • Voice commands (click 🎤 to speak)<br>
                • Voice responses (toggle 🔊)<br>
                • Personalized assistance<br>
                • Real-time system data<br>
                • Quick actions & shortcuts<br><br>
                <strong>💡 Pro Tips:</strong><br>
                • Use voice commands for hands-free operation<br>
                • Set up automated attendance reminders<br>
                • Export reports weekly for records<br>
                • Monitor attendance trends regularly`;
        }
        
        // Voice Commands
        if (query.includes('voice') || query.includes('speak') || query.includes('how to use')) {
            return `<strong>🎤 Voice Control Guide</strong><br><br>
                <strong>Activate Voice Input:</strong><br>
                1. Click the 🎤 microphone button<br>
                2. Allow microphone access (browser will ask)<br>
                3. Speak your question clearly<br>
                4. AI will process and respond<br><br>
                <strong>🔊 Voice Output:</strong><br>
                • Click 🔊 button to toggle voice responses<br>
                • AI will read responses aloud<br>
                • Click 🔇 to mute voice output<br><br>
                <strong>📝 Example Voice Commands:</strong><br>
                • "Show me today's attendance"<br>
                • "How many students are present?"<br>
                • "Add a new student"<br>
                • "Show teacher statistics"<br>
                • "Generate attendance report"<br>
                • "What's the attendance rate?"<br><br>
                <strong>💡 Tips for Best Results:</strong><br>
                • Speak clearly and naturally<br>
                • Use short, specific commands<br>
                • Avoid background noise<br>
                • Wait for AI response before next command<br><br>
                <strong>✅ Browser Support:</strong><br>
                • Chrome ✅ (Recommended)<br>
                • Edge ✅<br>
                • Safari ✅<br>
                • Firefox ⚠️ (Limited)`;
        }
        
        // Statistics Query
        if (query.includes('statistic') || query.includes('stats') || query.includes('how many')) {
            return `<strong>📊 System Statistics Overview</strong><br><br>
                <strong>👥 Users:</strong><br>
                • Total Students: ${systemStats.students}<br>
                • Active Teachers: ${systemStats.teachers}<br>
                • Department HODs: ${systemStats.hods}<br>
                • Registered Parents: ${systemStats.parents}<br>
                • Total Users: ${systemStats.students + systemStats.teachers + systemStats.hods + systemStats.parents}<br><br>
                <strong>🏫 Academic Structure:</strong><br>
                • Departments: ${systemStats.departments}<br>
                • Active Classes: ${systemStats.classes}<br>
                • Average Students per Class: ${Math.round(systemStats.students / systemStats.classes)}<br><br>
                <strong>📈 Today's Attendance:</strong><br>
                • Present: ${systemStats.todayPresent} (${Math.round((systemStats.todayPresent/systemStats.students)*100)}%)<br>
                • Absent: ${systemStats.todayAbsent} (${Math.round((systemStats.todayAbsent/systemStats.students)*100)}%)<br>
                • Attendance Rate: ${systemStats.attendanceRate}%<br>
                • Performance: ${systemStats.attendanceRate > 75 ? '🟢 Excellent' : systemStats.attendanceRate > 60 ? '🟡 Good' : '🔴 Needs Attention'}<br><br>
                <strong>📊 Performance Insights:</strong><br>
                ${systemStats.attendanceRate > 80 ? '✅ Outstanding attendance! Keep it up!' : systemStats.attendanceRate > 70 ? '⚠️ Good attendance, slight improvement needed.' : '❌ Low attendance! Immediate action required.'}<br><br>
                <strong>💡 Quick Actions:</strong><br>
                ${systemStats.attendanceRate < 70 ? '• Send attendance reminders<br>• Contact absent students<br>• Review attendance policies' : '• Maintain current strategies<br>• Monitor trends<br>• Reward good attendance'}`;
        }
        
        // Default Response
        return `<strong>🤔 I understand you're asking about "${q}"</strong><br><br>
            I'm here to help you manage NIT AMMS efficiently!<br><br>
            <strong>I can assist with:</strong><br>
            • <strong>User Management:</strong> Students (${systemStats.students}), Teachers (${systemStats.teachers}), HODs (${systemStats.hods}), Parents (${systemStats.parents})<br>
            • <strong>Attendance:</strong> Today's rate is ${systemStats.attendanceRate}%<br>
            • <strong>Reports:</strong> Daily & detailed analytics<br>
            • <strong>Departments:</strong> Manage ${systemStats.departments} departments<br>
            • <strong>Classes:</strong> Organize ${systemStats.classes} classes<br><br>
            <strong>📝 Try asking:</strong><br>
            • "Show me today's attendance summary"<br>
            • "How to add a new student?"<br>
            • "Show teacher statistics"<br>
            • "Department overview"<br>
            • "Tell me about all features"<br>
            • "Generate attendance report"<br><br>
            <strong>🎤 Voice Commands:</strong><br>
            Click the microphone button and speak your question!<br><br>
            Or use the quick action buttons below 👇`;
    }
    </script>
</body>
</html>