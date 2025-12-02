<?php
require_once '../db.php';
checkRole(['teacher']);

$user = getCurrentUser();
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
$section = isset($_GET['section']) ? $_GET['section'] : '';

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

// Get students for this section (by matching section name across all class IDs)
$attendance_date = isset($_POST['attendance_date']) ? $_POST['attendance_date'] : date('Y-m-d');

// Get all students from the same section
$students_query = "SELECT s.*, 
                   sa.status as today_status, sa.remarks as today_remarks
                   FROM students s
                   LEFT JOIN student_attendance sa ON s.id = sa.student_id 
                       AND sa.attendance_date = ? AND sa.class_id = ?
                   WHERE s.class_id IN (SELECT id FROM classes WHERE section = ?) 
                   AND s.is_active = 1
                   ORDER BY s.roll_number";

$stmt = $conn->prepare($students_query);
$stmt->bind_param("sis", $attendance_date, $class_id, $class['section']);
$stmt->execute();
$students = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance - <?php echo htmlspecialchars($class['section']); ?></title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="../assets/teacher.css">
    <script src="../assets/attendance.js"></script>
         <link rel="icon" href="../Nit_logo.png" type="image/svg+xml" />
</head>
<body class="dashboard-container">
    <nav class="navbar">
        <div>
            <h1>🎓 Mark Attendance - <?php echo htmlspecialchars($class['section']); ?></h1>
        </div>
        <div class="user-info">
            <a href="index.php" class="btn btn-secondary">← Back</a>
            <span>👨‍🏫 <?php echo htmlspecialchars($user['full_name']); ?></span>
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
                    <div class="number"><?php echo $students->num_rows; ?></div>
                </div>
            </div>
        </div>

        <div class="table-container">
            <form method="POST" onsubmit="return validateAttendance()">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                    <div>
                        <label style="font-weight: bold; margin-right: 10px;">📅 Attendance Date:</label>
                        <input type="date" name="attendance_date" value="<?php echo $attendance_date; ?>" 
                               max="<?php echo date('Y-m-d'); ?>" required 
                               style="padding: 10px; border-radius: 5px; border: 2px solid #ddd;"
                               onchange="this.form.submit()">
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <button type="button" onclick="markAll('present')" class="btn btn-success">
                            ✅ Mark All Present
                        </button>
                        <button type="button" onclick="markAll('absent')" class="btn btn-danger">
                            ❌ Mark All Absent
                        </button>
                    </div>
                </div>

                <?php if ($students->num_rows > 0): ?>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8f9fa;">
                                <th style="padding: 15px; text-align: left;">Roll Number</th>
                                <th style="padding: 15px; text-align: left;">Student Name</th>
                                <th style="padding: 15px; text-align: center;">✅ Present</th>
                                <th style="padding: 15px; text-align: center;">❌ Absent</th>
                                <th style="padding: 15px; text-align: center;">⏰ Late</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($student = $students->fetch_assoc()): ?>
                                <tr style="border-bottom: 1px solid #e0e0e0;">
                                    <td style="padding: 15px;">
                                        <strong><?php echo htmlspecialchars($student['roll_number']); ?></strong>
                                    </td>
                                    <td style="padding: 15px;">
                                        <strong><?php echo htmlspecialchars($student['full_name']); ?></strong><br>
                                        <small style="color: #666;"><?php echo htmlspecialchars($student['email']); ?></small>
                                    </td>
                                    <td style="padding: 15px; text-align: center;">
                                        <label class="status-btn present <?php echo ($student['today_status'] == 'present') ? 'active' : ''; ?>" 
                                               style="cursor: pointer; padding: 10px 20px; border-radius: 8px; display: inline-block;">
                                            <input type="radio" name="attendance[<?php echo $student['id']; ?>]" 
                                                   value="present" style="display:none;"
                                                   <?php echo ($student['today_status'] == 'present') ? 'checked' : ''; ?>
                                                   onchange="this.parentElement.classList.add('active'); 
                                                            this.closest('tr').querySelectorAll('.status-btn').forEach(b => {
                                                                if(b !== this.parentElement) b.classList.remove('active');
                                                            })">
                                            Present
                                        </label>
                                    </td>
                                    <td style="padding: 15px; text-align: center;">
                                        <label class="status-btn absent <?php echo ($student['today_status'] == 'absent') ? 'active' : ''; ?>"
                                               style="cursor: pointer; padding: 10px 20px; border-radius: 8px; display: inline-block;">
                                            <input type="radio" name="attendance[<?php echo $student['id']; ?>]" 
                                                   value="absent" style="display:none;"
                                                   <?php echo ($student['today_status'] == 'absent') ? 'checked' : ''; ?>
                                                   onchange="this.parentElement.classList.add('active'); 
                                                            this.closest('tr').querySelectorAll('.status-btn').forEach(b => {
                                                                if(b !== this.parentElement) b.classList.remove('active');
                                                            })">
                                            Absent
                                        </label>
                                    </td>
                                    <td style="padding: 15px; text-align: center;">
                                        <label class="status-btn late <?php echo ($student['today_status'] == 'late') ? 'active' : ''; ?>"
                                               style="cursor: pointer; padding: 10px 20px; border-radius: 8px; display: inline-block;">
                                            <input type="radio" name="attendance[<?php echo $student['id']; ?>]" 
                                                   value="late" style="display:none;"
                                                   <?php echo ($student['today_status'] == 'late') ? 'checked' : ''; ?>
                                                   onchange="this.parentElement.classList.add('active'); 
                                                            this.closest('tr').querySelectorAll('.status-btn').forEach(b => {
                                                                if(b !== this.parentElement) b.classList.remove('active');
                                                            })">
                                            Late
                                        </label>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    
                    <div style="margin-top: 30px; text-align: center;">
                        <button type="submit" name="save_attendance" class="btn btn-primary" style="padding: 15px 50px; font-size: 16px;">
                            💾 Save Attendance
                        </button>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        ⚠️ No students found in section "<?php echo htmlspecialchars($class['section']); ?>". 
                        Please contact the administrator to assign students to this section.
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <h3>💡 Important Notes</h3>
            <ul style="line-height: 2; padding-left: 20px;">
                <li><strong>Section:</strong> <?php echo htmlspecialchars($class['section']); ?> - You are viewing all students enrolled in this section</li>
                <li>Click on Present/Absent/Late buttons to mark attendance</li>
                <li>Green highlighting indicates Present, Red for Absent, Yellow for Late</li>
                <li>Use quick action buttons to mark all students at once</li>
                <li>Don't forget to click "Save Attendance" when done!</li>
            </ul>
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
</body>
</html>