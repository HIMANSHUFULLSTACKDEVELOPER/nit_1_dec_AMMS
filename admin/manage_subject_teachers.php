<?php
// admin/manage_subject_teachers.php
require_once '../db.php';
checkRole(['admin']);

// Handle subject teacher assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_teacher'])) {
    $subject_id = intval($_POST['subject_id']);
    $teacher_id = intval($_POST['teacher_id']);
    $section = sanitize($_POST['section']);
    $year = intval($_POST['year']);
    $semester = intval($_POST['semester']);
    $academic_year = sanitize($_POST['academic_year']);
    
    $query = "INSERT INTO subject_teachers (subject_id, teacher_id, section, year, semester, academic_year)
              VALUES (?, ?, ?, ?, ?, ?)
              ON DUPLICATE KEY UPDATE is_active = 1";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iisiis", $subject_id, $teacher_id, $section, $year, $semester, $academic_year);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "✅ Teacher assigned successfully!";
    } else {
        $_SESSION['error'] = "❌ Error assigning teacher!";
    }
    
    header("Location: manage_subject_teachers.php");
    exit();
}

// Handle remove assignment
if (isset($_GET['remove_id'])) {
    $id = intval($_GET['remove_id']);
    $conn->query("UPDATE subject_teachers SET is_active = 0 WHERE id = $id");
    $_SESSION['success'] = "✅ Assignment removed!";
    header("Location: manage_subject_teachers.php");
    exit();
}

// Get all subjects
$subjects_query = "SELECT s.*, d.dept_name 
                   FROM subjects s 
                   LEFT JOIN departments d ON s.department_id = d.id
                   WHERE s.is_active = 1 
                   ORDER BY s.year, s.semester, s.subject_name";
$subjects = $conn->query($subjects_query);

// Get all teachers
$teachers_query = "SELECT * FROM users WHERE role = 'teacher' AND is_active = 1 ORDER BY full_name";
$teachers = $conn->query($teachers_query);

// Get all sections
$sections_query = "SELECT DISTINCT section FROM classes ORDER BY section";
$sections = $conn->query($sections_query);

// Get current assignments
$assignments_query = "SELECT 
    st.*,
    s.subject_name,
    s.subject_code,
    u.full_name as teacher_name,
    d.dept_name
FROM subject_teachers st
JOIN subjects s ON st.subject_id = s.id
JOIN users u ON st.teacher_id = u.id
LEFT JOIN departments d ON s.department_id = d.id
WHERE st.is_active = 1
ORDER BY st.academic_year DESC, st.section, s.subject_name";
$assignments = $conn->query($assignments_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subject Teachers - Admin</title>
    <link rel="icon" href="../Nit_logo.png" type="image/svg+xml" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar {
            background: rgba(26, 31, 58, 0.95);
            backdrop-filter: blur(20px);
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }
        .navbar h1 { color: white; font-size: 24px; }
        .navbar a {
            color: white;
            text-decoration: none;
            background: linear-gradient(135deg, #667eea, #764ba2);
            padding: 10px 20px;
            border-radius: 10px;
        }
        .main-content {
            padding: 40px;
            max-width: 1600px;
            margin: 0 auto;
        }
        .container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            padding: 40px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
        }
        h2 {
            font-size: 28px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 30px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .form-group select, .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
        }
        .btn {
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b, #ee5a5a);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }
        thead th {
            padding: 15px;
            color: white;
            text-align: left;
            font-weight: 600;
        }
        tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s;
        }
        tbody tr:hover {
            background: rgba(102, 126, 234, 0.05);
        }
        tbody td {
            padding: 15px;
        }
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>📚 Manage Subject Teachers</h1>
        <a href="index.php">🏠 Dashboard</a>
    </nav>

    <div class="main-content">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Assignment Form -->
        <div class="container">
            <h2>➕ Assign Teacher to Subject</h2>
            
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="subject_id">Subject *</label>
                        <select name="subject_id" id="subject_id" required>
                            <option value="">Select Subject</option>
                            <?php 
                            $subjects->data_seek(0);
                            while ($sub = $subjects->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $sub['id']; ?>">
                                    <?php echo htmlspecialchars($sub['subject_code']); ?> - <?php echo htmlspecialchars($sub['subject_name']); ?>
                                    (Year <?php echo $sub['year']; ?> - Sem <?php echo $sub['semester']; ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="teacher_id">Teacher *</label>
                        <select name="teacher_id" id="teacher_id" required>
                            <option value="">Select Teacher</option>
                            <?php 
                            $teachers->data_seek(0);
                            while ($teacher = $teachers->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $teacher['id']; ?>">
                                    <?php echo htmlspecialchars($teacher['full_name']); ?> (<?php echo htmlspecialchars($teacher['email']); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="section">Section *</label>
                        <select name="section" id="section" required>
                            <option value="">Select Section</option>
                            <?php 
                            $sections->data_seek(0);
                            while ($sec = $sections->fetch_assoc()): 
                            ?>
                                <option value="<?php echo htmlspecialchars($sec['section']); ?>">
                                    <?php echo htmlspecialchars($sec['section']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="year">Year *</label>
                        <select name="year" id="year" required>
                            <option value="1">1st Year</option>
                            <option value="2">2nd Year</option>
                            <option value="3">3rd Year</option>
                            <option value="4">4th Year</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="semester">Semester *</label>
                        <select name="semester" id="semester" required>
                            <option value="1">Semester 1</option>
                            <option value="2">Semester 2</option>
                            <option value="3">Semester 3</option>
                            <option value="4">Semester 4</option>
                            <option value="5">Semester 5</option>
                            <option value="6">Semester 6</option>
                            <option value="7">Semester 7</option>
                            <option value="8">Semester 8</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="academic_year">Academic Year *</label>
                        <input type="text" name="academic_year" id="academic_year" 
                               value="<?php echo date('Y') . '-' . (date('Y') + 1); ?>" 
                               placeholder="2025-2026" required>
                    </div>
                </div>
                
                <button type="submit" name="assign_teacher" class="btn">
                    ✅ Assign Teacher to Subject
                </button>
            </form>
        </div>

        <!-- Current Assignments -->
        <div class="container">
            <h2>📋 Current Assignments</h2>
            
            <?php if ($assignments->num_rows > 0): ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Teacher</th>
                                <th>Section</th>
                                <th>Year/Sem</th>
                                <th>Academic Year</th>
                                <th>Department</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($assign = $assignments->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($assign['subject_code']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($assign['subject_name']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($assign['teacher_name']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($assign['section']); ?></strong></td>
                                    <td>Year <?php echo $assign['year']; ?> / Sem <?php echo $assign['semester']; ?></td>
                                    <td><?php echo htmlspecialchars($assign['academic_year']); ?></td>
                                    <td><?php echo htmlspecialchars($assign['dept_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <a href="?remove_id=<?php echo $assign['id']; ?>" 
                                           class="btn btn-danger"
                                           onclick="return confirm('Are you sure you want to remove this assignment?')">
                                            ❌ Remove
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 60px; color: #999;">
                    <p style="font-size: 48px; margin-bottom: 20px;">📚</p>
                    <p>No teacher assignments yet. Start assigning teachers to subjects!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>