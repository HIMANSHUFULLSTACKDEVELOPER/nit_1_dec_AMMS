<?php
require_once '../db.php';
checkRole(['admin']);

$user = getCurrentUser();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['assign_teacher'])) {
        $teacher_id = intval($_POST['teacher_id']);
        $class_id = intval($_POST['class_id']);
        $subject_id = !empty($_POST['subject_id']) ? intval($_POST['subject_id']) : NULL;
        $academic_year = sanitize($_POST['academic_year']);
        
        // Check if already assigned
        $check = $conn->prepare("SELECT id FROM teacher_assignments WHERE teacher_id = ? AND class_id = ? AND subject_id <=> ? AND academic_year = ?");
        $check->bind_param("iiis", $teacher_id, $class_id, $subject_id, $academic_year);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $error = "This teacher is already assigned to this class!";
        } else {
            $stmt = $conn->prepare("INSERT INTO teacher_assignments (teacher_id, class_id, subject_id, academic_year) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiis", $teacher_id, $class_id, $subject_id, $academic_year);
            
            if ($stmt->execute()) {
                $success = "Teacher assigned successfully!";
            } else {
                $error = "Error assigning teacher: " . $conn->error;
            }
        }
    }
    
    if (isset($_POST['remove_assignment'])) {
        $assignment_id = intval($_POST['assignment_id']);
        
        if ($conn->query("DELETE FROM teacher_assignments WHERE id = $assignment_id")) {
            $success = "Assignment removed successfully!";
        } else {
            $error = "Error removing assignment: " . $conn->error;
        }
    }
}

// Get all assignments
$assignments_query = "SELECT ta.*, 
                      u.full_name as teacher_name,
                      c.class_name, c.section, c.year, c.semester,
                      d.dept_name,
                      s.subject_name, s.subject_code
                      FROM teacher_assignments ta
                      JOIN users u ON ta.teacher_id = u.id
                      JOIN classes c ON ta.class_id = c.id
                      JOIN departments d ON c.department_id = d.id
                      LEFT JOIN subjects s ON ta.subject_id = s.id
                      ORDER BY c.section, c.year, c.semester, u.full_name";
$assignments = $conn->query($assignments_query);

// Get classes
$classes = $conn->query("SELECT c.*, d.dept_name FROM classes c JOIN departments d ON c.department_id = d.id ORDER BY c.section, c.year");

// Get teachers
$teachers = $conn->query("SELECT id, full_name, department_id FROM users WHERE role = 'teacher' AND is_active = 1 ORDER BY full_name");

// Get subjects
$subjects = $conn->query("SELECT * FROM subjects ORDER BY subject_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NIT AMMS - Admin</title>
     <link rel="icon" href="Nit_logo.png" type="image/svg+xml" />
    <link rel="stylesheet" href="../assets/style.css">
     <link rel="icon" href="../Nit_logo.png" type="image/svg+xml" />
</head>
<body class="dashboard-container">
    <nav class="navbar">
        <div>
            <h1>🎓 NIT AMMS - Assign Teachers to Classes</h1>
        </div>
        <div class="user-info">
            <a href="manage_classes.php" class="btn btn-secondary">← Back to Classes</a>
            <span>👨‍💼 <?php echo htmlspecialchars($user['full_name']); ?></span>
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

        <div class="table-container" style="margin-bottom: 30px;">
            <h3>➕ Assign Teacher to Class</h3>
            <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <strong>📌 Note:</strong> You can assign multiple teachers to the same class. Each teacher can teach different subjects.
            </div>
            <form method="POST" style="max-width: 900px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>Select Class:</label>
                    <select name="class_id" required>
                        <option value="">-- Select Class --</option>
                        <?php 
                        $classes->data_seek(0);
                        while ($class = $classes->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $class['id']; ?>">
                                <?php echo htmlspecialchars($class['section'] . ' - Year ' . $class['year'] . ' - Sem ' . $class['semester'] . ' (' . $class['dept_name'] . ')'); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Select Teacher:</label>
                    <select name="teacher_id" required>
                        <option value="">-- Select Teacher --</option>
                        <?php while ($teacher = $teachers->fetch_assoc()): ?>
                            <option value="<?php echo $teacher['id']; ?>">
                                <?php echo htmlspecialchars($teacher['full_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Subject (Optional):</label>
                    <select name="subject_id">
                        <option value="">-- Select Subject (Optional) --</option>
                        <?php while ($subject = $subjects->fetch_assoc()): ?>
                            <option value="<?php echo $subject['id']; ?>">
                                <?php echo htmlspecialchars($subject['subject_code'] . ' - ' . $subject['subject_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Academic Year:</label>
                    <input type="text" name="academic_year" required placeholder="e.g., 2024-25" value="2024-25">
                </div>
                
                <div style="grid-column: 1 / -1;">
                    <button type="submit" name="assign_teacher" class="btn btn-primary">➕ Assign Teacher</button>
                </div>
            </form>
        </div>

        <div class="table-container">
            <h3>👨‍🏫 Current Teacher Assignments</h3>
            <table>
                <thead>
                    <tr>
                        <th>Class</th>
                        <th>Section</th>
                        <th>Year</th>
                        <th>Semester</th>
                        <th>Department</th>
                        <th>Teacher</th>
                        <th>Subject</th>
                        <th>Academic Year</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($assignments->num_rows > 0):
                        $current_class = '';
                        $row_color = '#ffffff';
                        while ($assignment = $assignments->fetch_assoc()): 
                            $class_key = $assignment['section'] . '-' . $assignment['year'] . '-' . $assignment['semester'];
                            if ($class_key != $current_class) {
                                $current_class = $class_key;
                                $row_color = ($row_color == '#ffffff') ? '#f8f9fa' : '#ffffff';
                            }
                    ?>
                    <tr style="background: <?php echo $row_color; ?>">
                        <td><?php echo htmlspecialchars($assignment['class_name']); ?></td>
                        <td><span class="badge badge-info"><?php echo htmlspecialchars($assignment['section']); ?></span></td>
                        <td><?php echo $assignment['year']; ?></td>
                        <td><?php echo $assignment['semester']; ?></td>
                        <td><?php echo htmlspecialchars($assignment['dept_name']); ?></td>
                        <td><strong><?php echo htmlspecialchars($assignment['teacher_name']); ?></strong></td>
                        <td>
                            <?php if ($assignment['subject_name']): ?>
                                <span class="badge badge-success"><?php echo htmlspecialchars($assignment['subject_code']); ?></span>
                                <?php echo htmlspecialchars($assignment['subject_name']); ?>
                            <?php else: ?>
                                <span class="badge badge-secondary">General</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($assignment['academic_year']); ?></td>
                        <td>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Remove this assignment?');">
                                <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                                <button type="submit" name="remove_assignment" class="btn btn-danger btn-sm">Remove</button>
                            </form>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <tr>
                        <td colspan="9" style="text-align: center;">No teacher assignments found. Start by assigning teachers above.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 8px;">
                <strong>💡 Tip:</strong> Classes with the same section are grouped with alternating colors. You can assign multiple teachers to teach different subjects in the same class.
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
</body>
</html>