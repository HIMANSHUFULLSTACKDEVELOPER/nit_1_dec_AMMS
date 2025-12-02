<?php
require_once '../db.php';
checkRole(['teacher']);

$user = getCurrentUser();
$teacher_id = $user['id']; // FIXED: Changed from $_SESSION['user_id'] to $user['id']

if (!isset($_GET['class_id']) || !isset($_GET['date'])) {
    header("Location: index.php");
    exit();
}

$class_id = intval($_GET['class_id']);
$edit_date = $_GET['date'];

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $edit_date)) {
    header("Location: index.php?error=invalid_date");
    exit();
}

// Verify this class belongs to the teacher
$class_query = "SELECT c.*, d.dept_name FROM classes c 
                LEFT JOIN departments d ON c.department_id = d.id
                WHERE c.id = ? AND c.teacher_id = ?";
$stmt = $conn->prepare($class_query);
$stmt->bind_param("ii", $class_id, $teacher_id);
$stmt->execute();
$class_result = $stmt->get_result();

if ($class_result->num_rows === 0) {
    header("Location: index.php?error=unauthorized");
    exit();
}

$class = $class_result->fetch_assoc();

// Check if attendance exists for this date
$check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM student_attendance 
                               WHERE class_id = ? AND attendance_date = ?");
$check_stmt->bind_param("is", $class_id, $edit_date);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$attendance_exists = $check_result->fetch_assoc()['count'] > 0;

if (!$attendance_exists) {
    header("Location: mark_attendance.php?class_id=$class_id&error=no_attendance_found");
    exit();
}

// Get students with attendance for selected date
$students_query = "SELECT s.*, sa.status, sa.remarks 
                   FROM students s
                   LEFT JOIN student_attendance sa ON s.id = sa.student_id 
                   AND sa.class_id = ? AND sa.attendance_date = ?
                   WHERE s.class_id = ? AND s.is_active = 1 
                   ORDER BY s.roll_number";
$students_stmt = $conn->prepare($students_query);
$students_stmt->bind_param("isi", $class_id, $edit_date, $class_id);
$students_stmt->execute();
$students = $students_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Attendance - Teacher</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="icon" href="../Nit_logo.png" type="image/png" />
    <style>
        .attendance-form { max-width: 1200px; margin: 0 auto; }
        .student-row {
            display: grid;
            grid-template-columns: 100px 1fr 150px 150px 150px 200px;
            gap: 15px;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            background: white;
        }
        .student-row:hover { background: #f8f9fa; }
        .student-row label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 5px;
            transition: all 0.3s;
        }
        .student-row input[type="radio"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        .student-row input[type="text"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .present-label:has(input:checked) { background: #d4edda; color: #155724; }
        .absent-label:has(input:checked) { background: #f8d7da; color: #721c24; }
        .late-label:has(input:checked) { background: #fff3cd; color: #856404; }
        .header-row {
            display: grid;
            grid-template-columns: 100px 1fr 150px 150px 150px 200px;
            gap: 15px;
            padding: 15px;
            background: #667eea;
            color: white;
            font-weight: bold;
            border-radius: 10px 10px 0 0;
        }
        .quick-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .student-row, .header-row {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .student-row > div {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .header-row > div {
                display: none;
            }
            
            .header-row > div:first-child {
                display: block;
            }
        }
    </style>
</head>
<body class="dashboard-container">
    <nav class="navbar">
        <div>
            <h1>🎓 NIT AMMS - Edit Attendance</h1>
        </div>
        <div class="user-info">
            <a href="view_attendance.php?class_id=<?php echo $class_id; ?>" class="btn btn-secondary">← Back</a>
            <span>👨‍🏫 <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <div style="background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
            <h2><?php echo htmlspecialchars($class['class_name']); ?></h2>
            <p><strong>Department:</strong> <?php echo htmlspecialchars($class['dept_name']); ?></p>
            <p><strong>Editing Date:</strong> <?php echo date('l, d F Y', strtotime($edit_date)); ?></p>
            
            <div class="alert alert-warning">
                ⚠️ You are editing attendance for <?php echo date('d M Y', strtotime($edit_date)); ?>. Changes will update the existing records.
            </div>
        </div>

        <div class="quick-actions">
            <button type="button" onclick="markAll('present')" class="btn btn-success">✅ Mark All Present</button>
            <button type="button" onclick="markAll('absent')" class="btn btn-danger">❌ Mark All Absent</button>
        </div>

        <form method="POST" action="save_attendance.php" class="attendance-form" onsubmit="return confirmUpdate();">
            <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
            <input type="hidden" name="attendance_date" value="<?php echo $edit_date; ?>">
            <input type="hidden" name="is_edit" value="1">
            
            <div class="table-container" style="padding: 0;">
                <div class="header-row">
                    <div>Roll No</div>
                    <div>Student Name</div>
                    <div>Present</div>
                    <div>Absent</div>
                    <div>Late</div>
                    <div>Remarks</div>
                </div>
                
                <?php 
                $student_count = 0;
                while ($student = $students->fetch_assoc()): 
                    $student_id = $student['id'];
                    $status = $student['status'] ?? 'present';
                    $remarks = $student['remarks'] ?? '';
                    $student_count++;
                ?>
                <div class="student-row">
                    <div><strong>Roll No:</strong> <?php echo htmlspecialchars($student['roll_number']); ?></div>
                    <div><strong>Name:</strong> <?php echo htmlspecialchars($student['full_name']); ?></div>
                    
                    <div>
                        <label class="present-label">
                            <input type="radio" 
                                   name="attendance[<?php echo $student_id; ?>]" 
                                   value="present" 
                                   <?php echo $status === 'present' ? 'checked' : ''; ?>>
                            Present
                        </label>
                    </div>
                    
                    <div>
                        <label class="absent-label">
                            <input type="radio" 
                                   name="attendance[<?php echo $student_id; ?>]" 
                                   value="absent"
                                   <?php echo $status === 'absent' ? 'checked' : ''; ?>>
                            Absent
                        </label>
                    </div>
                    
                    <div>
                        <label class="late-label">
                            <input type="radio" 
                                   name="attendance[<?php echo $student_id; ?>]" 
                                   value="late"
                                   <?php echo $status === 'late' ? 'checked' : ''; ?>>
                            Late
                        </label>
                    </div>
                    
                    <div>
                        <input type="text" 
                               name="remarks[<?php echo $student_id; ?>]" 
                               placeholder="Optional remarks"
                               value="<?php echo htmlspecialchars($remarks); ?>">
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            
            <?php if ($student_count === 0): ?>
                <div style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 10px; margin-top: 20px;">
                    <p style="font-size: 48px; margin-bottom: 15px;">📭</p>
                    <h3 style="color: #666;">No Students Found</h3>
                    <p style="color: #999;">No active students in this class.</p>
                </div>
            <?php else: ?>
                <div style="margin-top: 20px; text-align: center;">
                    <button type="submit" class="btn btn-primary" style="padding: 15px 50px; font-size: 16px;">
                        💾 Update Attendance
                    </button>
                </div>
            <?php endif; ?>
        </form>
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
    function markAll(status) {
        const radios = document.querySelectorAll(`input[type="radio"][value="${status}"]`);
        radios.forEach(radio => {
            radio.checked = true;
        });
    }

    function confirmUpdate() {
        return confirm('Are you sure you want to update this attendance? This will overwrite the existing records.');
    }
    </script>
</body>
</html>