<?php

require_once '../db.php';
checkRole(['teacher']);

$user = getCurrentUser();
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
$section = isset($_GET['section']) ? $_GET['section'] : '';

// Get academic year from URL or from class
$academic_year = '';
if (isset($_GET['academic_year'])) {
    $academic_year = sanitize($_GET['academic_year']);
} else {
    // Get academic year from class
    $year_query = "SELECT academic_year FROM classes WHERE id = ? AND teacher_id = ?";
    $stmt = $conn->prepare($year_query);
    $stmt->bind_param("ii", $class_id, $user['id']);
    $stmt->execute();
    $year_result = $stmt->get_result()->fetch_assoc();
    $academic_year = $year_result['academic_year'] ?? '';
}

// Handle message sending via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    header('Content-Type: application/json');
    
    $student_id = intval($_POST['student_id']);
    $student_name = sanitize($_POST['student_name']);
    $student_email = sanitize($_POST['student_email']);
    $message = sanitize($_POST['message']);
    $date = sanitize($_POST['date']);
    
    // Check if student_notifications table exists, if not create it
    $check_table = "SHOW TABLES LIKE 'student_notifications'";
    $result = $conn->query($check_table);
    
    if ($result->num_rows == 0) {
        $create_table = "CREATE TABLE IF NOT EXISTS student_notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            teacher_id INT NOT NULL,
            class_id INT NOT NULL,
            message TEXT NOT NULL,
            email_subject VARCHAR(255) DEFAULT NULL,
            email_preview TEXT DEFAULT NULL,
            notification_date DATE NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
            INDEX idx_student_read (student_id, is_read),
            INDEX idx_date (notification_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        if (!$conn->query($create_table)) {
            echo json_encode(['success' => false, 'error' => 'Failed to create notifications table: ' . $conn->error]);
            exit();
        }
    }
    
    // Insert message into database
    $insert_msg = "INSERT INTO student_notifications 
                   (student_id, teacher_id, class_id, message, notification_date, is_read, created_at) 
                   VALUES (?, ?, ?, ?, ?, 0, NOW())";
    
    $stmt = $conn->prepare($insert_msg);
    if ($stmt) {
        $stmt->bind_param("iiiss", $student_id, $user['id'], $class_id, $message, $date);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'message' => 'Message sent successfully to ' . $student_name,
                'info' => 'Message saved. Student can view in their dashboard.'
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to save message: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to prepare statement: ' . $conn->error]);
    }
    
    exit();
}

// Verify teacher has access to this class
$verify_query = "SELECT c.*, d.dept_name FROM classes c 
                 JOIN departments d ON c.department_id = d.id
                 WHERE c.id = ? AND c.teacher_id = ?";
$stmt = $conn->prepare($verify_query);
$stmt->bind_param("ii", $class_id, $user['id']);
$stmt->execute();
$class = $stmt->get_result()->fetch_assoc();

if (!$class) {
    header("Location: index.php");
    exit();
}

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    $attendance_date = sanitize($_POST['attendance_date']);
    $attendance_data = $_POST['attendance'] ?? [];
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($attendance_data as $student_id => $status) {
        $student_id = intval($student_id);
        $status = sanitize($status);
        $remarks = isset($_POST['remarks'][$student_id]) ? sanitize($_POST['remarks'][$student_id]) : '';
        
        // Check if attendance already exists for today
        $check_query = "SELECT id FROM student_attendance 
                       WHERE student_id = ? AND class_id = ? AND attendance_date = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("iis", $student_id, $class_id, $attendance_date);
        $check_stmt->execute();
        $existing = $check_stmt->get_result()->fetch_assoc();
        
        if ($existing) {
            // Update existing attendance
            $update_query = "UPDATE student_attendance 
                           SET status = ?, remarks = ?, marked_by = ?, marked_at = NOW()
                           WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("ssii", $status, $remarks, $user['id'], $existing['id']);
            
            if ($update_stmt->execute()) {
                $success_count++;
            } else {
                $error_count++;
            }
        } else {
            // Insert new attendance
            $insert_query = "INSERT INTO student_attendance 
                           (student_id, class_id, attendance_date, status, remarks, marked_by) 
                           VALUES (?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("iisssi", $student_id, $class_id, $attendance_date, $status, $remarks, $user['id']);
            
            if ($insert_stmt->execute()) {
                $success_count++;
            } else {
                $error_count++;
            }
        }
    }
    
    if ($success_count > 0) {
        $success = "✅ Attendance saved successfully! ($success_count students marked)";
    }
    if ($error_count > 0) {
        $error = "⚠️ Some errors occurred while saving attendance ($error_count failed)";
    }
}

// Get the date
if (isset($_GET['date'])) {
    $attendance_date = sanitize($_GET['date']);
} elseif (isset($_POST['attendance_date'])) {
    $attendance_date = sanitize($_POST['attendance_date']);
} else {
    $attendance_date = date('Y-m-d');
}

// Calculate previous day's date
$previous_date = date('Y-m-d', strtotime($attendance_date . ' -1 day'));

// FIXED QUERY: Get students from the SAME academic year as the selected class
$students_query = "SELECT s.*, 
                   sa.status as today_status, sa.remarks as today_remarks
                   FROM students s
                   LEFT JOIN student_attendance sa ON s.id = sa.student_id 
                       AND sa.attendance_date = ? AND sa.class_id = ?
                   INNER JOIN classes c ON s.class_id = c.id
                   WHERE c.section = ? 
                   AND c.year = ?
                   AND c.semester = ?
                   AND c.academic_year = ?
                   AND s.is_active = 1
                   ORDER BY s.roll_number";

$stmt = $conn->prepare($students_query);
$stmt->bind_param("sissss", $attendance_date, $class_id, $class['section'], $class['year'], $class['semester'], $class['academic_year']);
$stmt->execute();
$students = $stmt->get_result();

// Calculate attendance statistics
$total_students = 0;
$present_count = 0;
$absent_count = 0;
$late_count = 0;
$not_marked = 0;

$students_array = [];
while ($student = $students->fetch_assoc()) {
    $students_array[] = $student;
    $total_students++;
    
    if ($student['today_status'] == 'present') {
        $present_count++;
    } elseif ($student['today_status'] == 'absent') {
        $absent_count++;
    } elseif ($student['today_status'] == 'late') {
        $late_count++;
    } else {
        $not_marked++;
    }
}

// Calculate percentages
$present_percentage = $total_students > 0 ? round(($present_count / $total_students) * 100, 1) : 0;
$absent_percentage = $total_students > 0 ? round(($absent_count / $total_students) * 100, 1) : 0;
$late_percentage = $total_students > 0 ? round(($late_count / $total_students) * 100, 1) : 0;

// Get previous day attendance
$prev_attendance_query = "SELECT student_id, status FROM student_attendance 
                         WHERE class_id = ? AND attendance_date = ?";
$prev_stmt = $conn->prepare($prev_attendance_query);
$prev_stmt->bind_param("is", $class_id, $previous_date);
$prev_stmt->execute();
$prev_result = $prev_stmt->get_result();

$previous_attendance = [];
while ($prev_row = $prev_result->fetch_assoc()) {
    $previous_attendance[$prev_row['student_id']] = $prev_row['status'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance - <?php echo htmlspecialchars($class['section']); ?> (<?php echo htmlspecialchars($class['academic_year']); ?>)</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="icon" href="../Nit_logo.png" type="image/svg+xml" />
    <link rel="stylesheet" href="mark_attendance_styles.css">
    <style>
        /* ================================
   ATTENDANCE MARKING SYSTEM STYLES
   ================================ */

/* Reset & Base Styles */
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
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
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

.user-profile {
    display: flex;
    align-items: center;
    gap: 12px;
    background: rgba(255, 255, 255, 0.1);
    padding: 10px 20px;
    border-radius: 50px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

/* Main Content */
.main-content {
    padding: 40px;
    max-width: 1600px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
}

/* Academic Year Badge */
.academic-year-badge {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 15px;
    padding: 20px 30px;
    margin-bottom: 30px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    border: 2px solid rgba(102, 126, 234, 0.3);
    font-size: 18px;
    color: #2c3e50;
    text-align: center;
}

.academic-year-badge strong {
    color: #667eea;
    font-size: 22px;
}

/* Alert Messages */
.alert {
    padding: 20px;
    border-radius: 15px;
    margin-bottom: 30px;
    animation: slideDown 0.5s ease-out;
    backdrop-filter: blur(10px);
    border: 2px solid;
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

.alert-error,
.alert-warning {
    background: rgba(248, 215, 218, 0.95);
    border-color: #dc3545;
    color: #721c24;
}

/* Stats Card */
.stats-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 25px;
    padding: 35px;
    margin: 20px 0;
    box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
    border: 2px solid rgba(255, 255, 255, 0.5);
    position: relative;
    overflow: hidden;
}

.stats-card::before {
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

.stats-card h2 {
    font-size: 28px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 20px;
}

.date-badge {
    display: inline-block;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 12px 25px;
    border-radius: 30px;
    font-size: 16px;
    font-weight: 600;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 20px;
    margin-top: 25px;
}

.stat-box {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 25px;
    text-align: center;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border: 2px solid rgba(102, 126, 234, 0.2);
}

.stat-box:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: 0 15px 40px rgba(102, 126, 234, 0.3);
}

.stat-icon {
    font-size: 40px;
    margin-bottom: 12px;
}

.stat-number {
    font-size: 36px;
    font-weight: 800;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.stat-label {
    font-size: 13px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-top: 8px;
}

.stat-percentage {
    font-size: 16px;
    color: #764ba2;
    margin-top: 5px;
    font-weight: 600;
}

/* Summary Card */
.summary-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 25px;
    padding: 35px;
    box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
    margin: 30px 0;
    border: 2px solid rgba(255, 255, 255, 0.5);
}

.summary-card h2 {
    font-size: 28px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 25px;
}

.summary-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
}

.summary-stat {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.08), rgba(118, 75, 162, 0.08));
    padding: 20px;
    border-radius: 15px;
    text-align: center;
    border: 1px solid rgba(102, 126, 234, 0.2);
}

.summary-stat .label {
    color: #666;
    font-size: 13px;
    margin-bottom: 8px;
}

.summary-stat .number {
    font-size: 24px;
    font-weight: 700;
    color: #2c3e50;
}

/* Table Container */
.table-container {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 25px;
    padding: 40px;
    box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
    margin: 30px 0;
    border: 2px solid rgba(255, 255, 255, 0.5);
}

.table-container h3 {
    font-size: 22px;
    color: #2c3e50;
    margin-bottom: 20px;
}

table {
    width: 100%;
    border-collapse: collapse;
}

table thead tr {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
}

table th {
    padding: 18px;
    text-align: left;
    font-weight: 600;
    color: #2c3e50;
    border-bottom: 2px solid rgba(102, 126, 234, 0.2);
}

table tbody tr {
    transition: all 0.3s ease;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

table tbody tr:hover {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
}

table td {
    padding: 18px;
}

/* Status Buttons */
.status-btn {
    cursor: pointer;
    padding: 12px 24px;
    border-radius: 12px;
    display: inline-block;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 2px solid #ddd;
    background: rgba(248, 249, 250, 0.9);
    color: #666;
    font-weight: 600;
    min-width: 100px;
    text-align: center;
}

.status-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
}

.status-btn.present {
    border-color: rgba(40, 167, 69, 0.3);
}

.status-btn.present.active {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    border-color: #28a745;
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
}

.status-btn.absent {
    border-color: rgba(220, 53, 69, 0.3);
}

.status-btn.absent.active {
    background: linear-gradient(135deg, #dc3545, #e74c3c);
    color: white;
    border-color: #dc3545;
    box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
}

.status-btn.late {
    border-color: rgba(255, 193, 7, 0.3);
}

.status-btn.late.active {
    background: linear-gradient(135deg, #ffc107, #f39c12);
    color: #000;
    border-color: #ffc107;
    box-shadow: 0 6px 20px rgba(255, 193, 7, 0.4);
}

/* Previous Status Badge */
.prev-status {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.prev-status.present {
    background: linear-gradient(135deg, #d4edda, #c3e6cb);
    color: #155724;
}

.prev-status.absent {
    background: linear-gradient(135deg, #f8d7da, #f5c6cb);
    color: #721c24;
}

.prev-status.late {
    background: linear-gradient(135deg, #fff3cd, #ffeeba);
    color: #856404;
}

.prev-status.not-marked {
    background: #e2e3e5;
    color: #6c757d;
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
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
}

.btn-secondary {
    background: linear-gradient(135deg, #6c757d, #5a6268);
    color: white;
}

.btn-secondary:hover {
    transform: translateY(-2px);
}

.btn-success {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
}

.btn-danger {
    background: linear-gradient(135deg, #dc3545, #e74c3c);
    color: white;
    box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4);
}

.btn-warning {
    background: linear-gradient(135deg, #ffc107, #f39c12);
    color: #000;
    box-shadow: 0 4px 15px rgba(255, 193, 7, 0.4);
}

/* Message Button */
.message-btn {
    padding: 10px 18px;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.message-btn.send-msg {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
}

.message-btn.send-msg:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
}

.message-btn.sent {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    cursor: not-allowed;
}

/* Modal Styling */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(5px);
    animation: fadeIn 0.3s;
}

.modal-content {
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(20px);
    margin: 8% auto;
    padding: 35px;
    border-radius: 25px;
    width: 90%;
    max-width: 600px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: slideDown 0.3s;
    border: 2px solid rgba(102, 126, 234, 0.2);
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding-bottom: 18px;
    border-bottom: 2px solid rgba(102, 126, 234, 0.2);
}

.modal-header h2 {
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-size: 22px;
    margin: 0;
}

.close-modal {
    font-size: 32px;
    font-weight: bold;
    color: #999;
    cursor: pointer;
    transition: all 0.3s;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.close-modal:hover {
    color: #dc3545;
    background: rgba(220, 53, 69, 0.1);
}

.message-templates {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 20px;
}

.template-btn {
    padding: 10px 18px;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
    border: 2px solid rgba(102, 126, 234, 0.3);
    border-radius: 25px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.3s;
    color: #2c3e50;
}

.template-btn:hover {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-color: transparent;
    transform: translateY(-2px);
}

#messageText {
    width: 100%;
    padding: 18px;
    border: 2px solid rgba(102, 126, 234, 0.3);
    border-radius: 15px;
    font-size: 14px;
    font-family: inherit;
    resize: vertical;
    transition: all 0.3s;
}

#messageText:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

/* Instructions Box */
.instructions-box {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
    padding: 30px;
    border-radius: 20px;
    border: 2px solid rgba(102, 126, 234, 0.2);
}

.instructions-box ul {
    list-style-position: inside;
    line-height: 2.2;
    color: #2c3e50;
}

.instructions-box li {
    padding-left: 10px;
    font-size: 15px;
}

/* Responsive Design */
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

    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .user-info {
        flex-direction: column;
        gap: 10px;
    }

    .table-container {
        padding: 20px;
        overflow-x: auto;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }

    .summary-stats {
        grid-template-columns: 1fr;
    }
}
    </style>

    <script>
        // ================================
// ATTENDANCE MARKING SYSTEM JAVASCRIPT
// ================================

// Function to change date without submitting attendance
function changeDate(dateValue) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('date', dateValue);
    window.location.href = 'mark_attendance.php?' + urlParams.toString();
}

// Function to navigate to previous or next day
function navigateDate(days) {
    const currentDate = new Date(document.getElementById('date_selector').value);
    currentDate.setDate(currentDate.getDate() + days);
    
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    // Don't allow future dates
    if (currentDate > today && days > 0) {
        return;
    }
    
    const year = currentDate.getFullYear();
    const month = String(currentDate.getMonth() + 1).padStart(2, '0');
    const day = String(currentDate.getDate()).padStart(2, '0');
    const newDate = `${year}-${month}-${day}`;
    
    changeDate(newDate);
}

// Function to mark all students with a specific status
function markAll(status) {
    const radioButtons = document.querySelectorAll(`input[type="radio"][value="${status}"]`);
    radioButtons.forEach(radio => {
        radio.checked = true;
        const row = radio.closest('tr');
        row.querySelectorAll('.status-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        radio.parentElement.classList.add('active');
    });
}

// Validate attendance before submission
function validateAttendance() {
    const checkedRadios = document.querySelectorAll('input[type="radio"]:checked');
    if (checkedRadios.length === 0) {
        alert('⚠️ Please mark attendance for at least one student before saving!');
        return false;
    }
    return confirm(`Are you sure you want to save attendance for ${checkedRadios.length} students?`);
}

// Message Modal Functions
let currentStudentData = {};

function openMessageModal(studentId, studentName, studentEmail, status) {
    currentStudentData = {
        id: studentId,
        name: studentName,
        email: studentEmail,
        status: status
    };

    document.getElementById('messageModal').style.display = 'block';
    document.getElementById('studentNameDisplay').textContent = studentName;
    document.getElementById('studentEmailDisplay').textContent = studentEmail;
    document.getElementById('messageText').value = '';
}

function closeMessageModal() {
    document.getElementById('messageModal').style.display = 'none';
    currentStudentData = {};
}

function useTemplate(template) {
    const templates = {
        absent: `Dear ${currentStudentData.name},

We noticed you were absent from class today. Please ensure to attend regularly and catch up on missed coursework.

If you have any valid reason for absence, please contact us.

Best regards,
Your Teacher`,
        
        consecutive: `Dear ${currentStudentData.name},

We have observed consecutive absences from your side. Regular attendance is crucial for your academic performance.

Please meet with me to discuss this matter.

Best regards,
Your Teacher`,
        
        late: `Dear ${currentStudentData.name},

You were marked late for today's class. Please try to arrive on time to avoid missing important information.

Best regards,
Your Teacher`,
        
        concern: `Dear ${currentStudentData.name},

I wanted to reach out regarding your attendance. Is everything okay? Please feel free to discuss any concerns with me.

Best regards,
Your Teacher`
    };

    document.getElementById('messageText').value = templates[template];
}

function sendMessage() {
    const message = document.getElementById('messageText').value.trim();
    
    if (!message) {
        alert('⚠️ Please enter a message!');
        return;
    }

    const formData = new FormData();
    formData.append('send_message', '1');
    formData.append('student_id', currentStudentData.id);
    formData.append('student_name', currentStudentData.name);
    formData.append('student_email', currentStudentData.email);
    formData.append('message', message);
    formData.append('date', document.getElementById('date_selector').value);

    // Get class_id and academic_year from URL
    const urlParams = new URLSearchParams(window.location.search);
    const classIdParam = urlParams.get('class_id');
    const academicYearParam = urlParams.get('academic_year') || '';

    let fetchUrl = `mark_attendance.php?class_id=${classIdParam}`;
    if (academicYearParam) {
        fetchUrl += `&academic_year=${encodeURIComponent(academicYearParam)}`;
    }

    fetch(fetchUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Message sent successfully to ' + currentStudentData.name + '!\n\n📱 Student can view this in their dashboard.');
            closeMessageModal();
            
            // Update button to show message was sent
            const btn = document.querySelector(`button[data-student-id="${currentStudentData.id}"]`);
            if (btn) {
                btn.classList.remove('send-msg');
                btn.classList.add('sent');
                btn.innerHTML = '✓ Sent';
                btn.disabled = true;
            }
        } else {
            alert('❌ Failed to send message: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        alert('❌ Error sending message. Please try again.');
        console.error('Error:', error);
    });
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('messageModal');
    if (event.target == modal) {
        closeMessageModal();
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Handle radio button and label clicks
    const labels = document.querySelectorAll('.status-btn');
    labels.forEach(label => {
        label.addEventListener('click', function(e) {
            const radio = this.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
                const row = this.closest('tr');
                row.querySelectorAll('.status-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                this.classList.add('active');
            }
        });
    });

    // Create animated particles
    const particlesContainer = document.querySelector('.particles');
    if (particlesContainer) {
        for (let i = 0; i < 20; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.width = Math.random() * 20 + 5 + 'px';
            particle.style.height = particle.style.width;
            particle.style.left = Math.random() * 100 + '%';
            particle.style.top = Math.random() * 100 + '%';
            particle.style.animationDelay = Math.random() * 15 + 's';
            particle.style.animationDuration = Math.random() * 10 + 10 + 's';
            particlesContainer.appendChild(particle);
        }
    }

    // Add smooth scroll behavior
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });

    // Add keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + S to save attendance
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            const saveButton = document.querySelector('button[name="save_attendance"]');
            if (saveButton) {
                saveButton.click();
            }
        }
        
        // Escape to close modal
        if (e.key === 'Escape') {
            closeMessageModal();
        }
    });

    // Add confirmation before leaving page with unsaved changes
    let hasUnsavedChanges = false;
    const radioButtons = document.querySelectorAll('input[type="radio"]');
    radioButtons.forEach(radio => {
        radio.addEventListener('change', function() {
            hasUnsavedChanges = true;
        });
    });

    window.addEventListener('beforeunload', function(e) {
        if (hasUnsavedChanges) {
            e.preventDefault();
            e.returnValue = '';
            return '';
        }
    });

    // Reset unsaved changes flag on form submit
    const attendanceForm = document.querySelector('form');
    if (attendanceForm) {
        attendanceForm.addEventListener('submit', function() {
            hasUnsavedChanges = false;
        });
    }

    console.log('✅ Attendance Marking System Initialized');
    console.log('📊 Total Students Loaded:', document.querySelectorAll('tbody tr').length);
    
    // Log academic year info if available
    if (typeof academicYear !== 'undefined') {
        console.log('📚 Academic Year:', academicYear);
    }
});
    </script>
</head>
<body>
    <!-- Animated Particles Background -->
    <div class="particles"></div>
    
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="navbar-brand">
            <div class="navbar-logo">🎓</div>
            <h1>Mark Attendance - <?php echo htmlspecialchars($class['section']); ?> (<?php echo htmlspecialchars($class['academic_year']); ?>)</h1>
        </div>
        <div class="user-info">
            <a href="index.php?academic_year=<?php echo urlencode($class['academic_year']); ?>" class="btn btn-secondary">← Back</a>
            <div class="user-profile">
                <span>👨‍🏫 <?php echo htmlspecialchars($user['full_name']); ?></span>
            </div>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Academic Year Badge -->
        <div class="academic-year-badge">
            📚 Academic Year: <strong><?php echo htmlspecialchars($class['academic_year']); ?></strong>
        </div>

        <!-- Attendance Statistics Card -->
        <div class="stats-card">
            <h2>📊 Attendance Statistics</h2>
            <div class="date-badge">
                📅 <?php echo date('l, F j, Y', strtotime($attendance_date)); ?>
            </div>
            
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-icon">👥</div>
                    <div class="stat-number"><?php echo $total_students; ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
                
                <div class="stat-box" style="background: linear-gradient(135deg, rgba(40, 167, 69, 0.15), rgba(32, 201, 151, 0.15));">
                    <div class="stat-icon">✅</div>
                    <div class="stat-number"><?php echo $present_count; ?></div>
                    <div class="stat-label">Present</div>
                    <div class="stat-percentage"><?php echo $present_percentage; ?>%</div>
                </div>
                
                <div class="stat-box" style="background: linear-gradient(135deg, rgba(220, 53, 69, 0.15), rgba(231, 76, 60, 0.15));">
                    <div class="stat-icon">❌</div>
                    <div class="stat-number"><?php echo $absent_count; ?></div>
                    <div class="stat-label">Absent</div>
                    <div class="stat-percentage"><?php echo $absent_percentage; ?>%</div>
                </div>
                
                <div class="stat-box" style="background: linear-gradient(135deg, rgba(255, 193, 7, 0.15), rgba(243, 156, 18, 0.15));">
                    <div class="stat-icon">⏰</div>
                    <div class="stat-number"><?php echo $late_count; ?></div>
                    <div class="stat-label">Late</div>
                    <div class="stat-percentage"><?php echo $late_percentage; ?>%</div>
                </div>
                
                <?php if ($not_marked > 0): ?>
                <div class="stat-box" style="background: rgba(108, 117, 125, 0.15);">
                    <div class="stat-icon">⚠️</div>
                    <div class="stat-number"><?php echo $not_marked; ?></div>
                    <div class="stat-label">Not Marked</div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Class Summary Card -->
        <div class="summary-card">
            <h2><?php echo htmlspecialchars($class['section']); ?></h2>
            <div class="summary-stats">
                <div class="summary-stat">
                    <div class="label">📖 Class</div>
                    <div class="number" style="font-size: 16px;"><?php echo htmlspecialchars($class['class_name']); ?></div>
                </div>
                <div class="summary-stat">
                    <div class="label">🏢 Department</div>
                    <div class="number" style="font-size: 16px;"><?php echo htmlspecialchars($class['dept_name']); ?></div>
                </div>
                <div class="summary-stat">
                    <div class="label">📅 Year</div>
                    <div class="number"><?php echo $class['year']; ?></div>
                </div>
                <div class="summary-stat">
                    <div class="label">📆 Semester</div>
                    <div class="number"><?php echo $class['semester']; ?></div>
                </div>
                <div class="summary-stat">
                    <div class="label">👥 Total Students</div>
                    <div class="number"><?php echo $total_students; ?></div>
                </div>
            </div>
        </div>

        <!-- Attendance Table -->
        <div class="table-container">
            <form method="POST" onsubmit="return validateAttendance()">
                <input type="hidden" name="attendance_date" value="<?php echo $attendance_date; ?>">
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <label style="font-weight: 600; color: #2c3e50;">📅 Attendance Date:</label>
                        
                        <button type="button" onclick="navigateDate(-1)" class="btn btn-secondary" style="padding: 10px 15px; font-size: 18px;">
                            ◀️
                        </button>
                        
                        <input type="date" id="date_selector" value="<?php echo $attendance_date; ?>" 
                               max="<?php echo date('Y-m-d'); ?>" required 
                               style="padding: 12px; border-radius: 10px; border: 2px solid rgba(102, 126, 234, 0.3); min-width: 160px; font-size: 14px;"
                               onchange="changeDate(this.value)">
                        
                        <button type="button" onclick="navigateDate(1)" class="btn btn-secondary" 
                                style="padding: 10px 15px; font-size: 18px;"
                                <?php echo ($attendance_date >= date('Y-m-d')) ? 'disabled' : ''; ?>>
                            ▶️
                        </button>
                        
                        <?php if ($attendance_date != date('Y-m-d')): ?>
                        <button type="button" onclick="changeDate('<?php echo date('Y-m-d'); ?>')" 
                                class="btn btn-primary" style="padding: 10px 20px;">
                            📅 Today
                        </button>
                        <?php endif; ?>
                    </div>
                    
                    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                        <button type="button" onclick="markAll('present')" class="btn btn-success">
                            ✅ Mark All Present
                        </button>
                        <button type="button" onclick="markAll('absent')" class="btn btn-danger">
                            ❌ Mark All Absent
                        </button>
                        <button type="button" onclick="markAll('late')" class="btn btn-warning">
                            ⏰ Mark All Late
                        </button>
                    </div>
                </div>

                <?php if ($total_students > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Roll Number</th>
                                <th>Student Name</th>
                                <th style="text-align: center;">
                                    📆 Previous Day<br>
                                    <small style="font-weight: normal; color: #666;">
                                        <?php echo date('d M Y', strtotime($previous_date)); ?>
                                    </small>
                                </th>
                                <th style="text-align: center;">✅ Present</th>
                                <th style="text-align: center;">❌ Absent</th>
                                <th style="text-align: center;">⏰ Late</th>
                                <th style="text-align: center;">💬 Message</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students_array as $student): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($student['roll_number']); ?></strong>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($student['full_name']); ?></strong><br>
                                        <small style="color: #666;"><?php echo htmlspecialchars($student['email']); ?></small>
                                    </td>
                                    <td style="text-align: center;">
                                        <?php 
                                        $student_id = $student['id'];
                                        if (isset($previous_attendance[$student_id])) {
                                            $prev_status = $previous_attendance[$student_id];
                                            $status_icon = '';
                                            if ($prev_status == 'present') {
                                                $status_icon = '✅';
                                            } elseif ($prev_status == 'absent') {
                                                $status_icon = '❌';
                                            } elseif ($prev_status == 'late') {
                                                $status_icon = '⏰';
                                            }
                                            echo '<span class="prev-status ' . htmlspecialchars($prev_status) . '">' . $status_icon . ' ' . ucfirst(htmlspecialchars($prev_status)) . '</span>';
                                        } else {
                                            echo '<span class="prev-status not-marked">➖ Not Marked</span>';
                                        }
                                        ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <label class="status-btn present <?php echo ($student['today_status'] == 'present') ? 'active' : ''; ?>">
                                            <input type="radio" name="attendance[<?php echo $student['id']; ?>]" 
                                                   value="present" style="display:none;"
                                                   <?php echo ($student['today_status'] == 'present') ? 'checked' : ''; ?>>
                                            Present
                                        </label>
                                    </td>
                                    <td style="text-align: center;">
                                        <label class="status-btn absent <?php echo ($student['today_status'] == 'absent') ? 'active' : ''; ?>">
                                            <input type="radio" name="attendance[<?php echo $student['id']; ?>]" 
                                                   value="absent" style="display:none;"
                                                   <?php echo ($student['today_status'] == 'absent') ? 'checked' : ''; ?>>
                                            Absent
                                        </label>
                                    </td>
                                    <td style="text-align: center;">
                                        <label class="status-btn late <?php echo ($student['today_status'] == 'late') ? 'active' : ''; ?>">
                                            <input type="radio" name="attendance[<?php echo $student['id']; ?>]" 
                                                   value="late" style="display:none;"
                                                   <?php echo ($student['today_status'] == 'late') ? 'checked' : ''; ?>>
                                            Late
                                        </label>
                                    </td>
                                    <td style="text-align: center;">
                                        <button type="button" 
                                                class="message-btn send-msg" 
                                                data-student-id="<?php echo $student['id']; ?>"
                                                onclick="openMessageModal(
                                                    <?php echo $student['id']; ?>, 
                                                    '<?php echo htmlspecialchars($student['full_name'], ENT_QUOTES); ?>', 
                                                    '<?php echo htmlspecialchars($student['email'], ENT_QUOTES); ?>',
                                                    '<?php echo htmlspecialchars($student['today_status'] ?? 'not_marked', ENT_QUOTES); ?>'
                                                )">
                                            📧 Send
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div style="margin-top: 35px; text-align: center;">
                        <button type="submit" name="save_attendance" class="btn btn-primary" style="padding: 18px 60px; font-size: 16px;">
                            💾 Save Attendance
                        </button>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        ⚠️ No students found in section "<?php echo htmlspecialchars($class['section']); ?>" for academic year <?php echo htmlspecialchars($class['academic_year']); ?>. 
                        Please contact the administrator to assign students to this section.
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Instructions Box -->
        <div class="table-container">
            <h3>💡 Important Notes</h3>
            <div class="instructions-box">
                <ul>
                    <li><strong>Academic Year:</strong> <?php echo htmlspecialchars($class['academic_year']); ?> - Showing students from this year only</li>
                    <li><strong>Section:</strong> <?php echo htmlspecialchars($class['section']); ?> - All students enrolled in this section</li>
                    <li>Click on Present/Absent/Late buttons to mark attendance</li>
                    <li>Green highlighting indicates Present, Red for Absent, Yellow for Late</li>
                    <li>Use quick action buttons to mark all students at once</li>
                    <li>Change the date to view or mark attendance for previous days</li>
                    <li><strong>New:</strong> Click "📧 Send" button to send messages to students about their attendance</li>
                    <li>Don't forget to click "Save Attendance" when done!</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Message Modal -->
    <div id="messageModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>📧 Send Message to Student</h2>
                <span class="close-modal" onclick="closeMessageModal()">&times;</span>
            </div>
            
            <div style="background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)); padding: 18px; border-radius: 15px; margin-bottom: 25px;">
                <p style="margin: 6px 0;"><strong>Student:</strong> <span id="studentNameDisplay"></span></p>
                <p style="margin: 6px 0;"><strong>Email:</strong> <span id="studentEmailDisplay"></span></p>
                <p style="margin: 6px 0;"><strong>Date:</strong> <?php echo date('d M Y', strtotime($attendance_date)); ?></p>
            </div>

            <div>
                <label style="font-weight: 600; display: block; margin-bottom: 12px; color: #2c3e50;">
                    📝 Quick Templates (Click to use):
                </label>
                <div class="message-templates">
                    <button type="button" class="template-btn" onclick="useTemplate('absent')">
                        ❌ Absent Today
                    </button>
                    <button type="button" class="template-btn" onclick="useTemplate('consecutive')">
                        🚫 Consecutive Absences
                    </button>
                    <button type="button" class="template-btn" onclick="useTemplate('late')">
                        ⏰ Late Arrival
                    </button>
                    <button type="button" class="template-btn" onclick="useTemplate('concern')">
                        💭 General Concern
                    </button>
                </div>
            </div>

            <div style="margin: 25px 0;">
                <label style="font-weight: 600; display: block; margin-bottom: 12px; color: #2c3e50;">
                    ✉️ Message:
                </label>
                <textarea id="messageText" 
                          rows="8" 
                          placeholder="Type your message here..."></textarea>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" onclick="closeMessageModal()" class="btn btn-secondary">
                    ❌ Cancel
                </button>
                <button type="button" onclick="sendMessage()" class="btn btn-primary">
                    📤 Send Message
                </button>
            </div>
        </div>
    </div>

    <script src="mark_attendance_script.js"></script>
    <script>
        // Pass PHP variables to JavaScript
        const classId = <?php echo $class_id; ?>;
        const academicYear = "<?php echo htmlspecialchars($class['academic_year']); ?>";
    </script>
</body>
</html>