<?php
require_once '../db.php';
checkRole(['teacher']);

$user = getCurrentUser();
$teacher_id = $user['id'];

// Get teacher's classes WITH student count (FIXED QUERY)
$classes_query = "SELECT 
    c.id,
    c.class_name,
    c.section,
    c.year,
    c.semester,
    c.academic_year,
    COALESCE(
        (SELECT COUNT(DISTINCT s.id)
         FROM students s
         INNER JOIN classes c2 ON s.class_id = c2.id
         WHERE c2.section = c.section
         AND c2.year = c.year
         AND c2.semester = c.semester
         AND c2.academic_year = c.academic_year
        ), 0
    ) as student_count
FROM classes c
WHERE c.teacher_id = ?
GROUP BY c.id, c.class_name, c.section, c.year, c.semester, c.academic_year
ORDER BY c.academic_year DESC, c.section";
$stmt = $conn->prepare($classes_query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$classes = $stmt->get_result();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id = intval($_POST['class_id']);
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $due_date = sanitize($_POST['due_date']);
    $total_marks = intval($_POST['total_marks']);
    
    // Handle file upload
    $file_path = null;
    if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/assignments/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = pathinfo($_FILES['assignment_file']['name'], PATHINFO_EXTENSION);
        $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'txt'];
        
        if (in_array(strtolower($file_extension), $allowed_extensions)) {
            $file_name = 'assignment_' . time() . '_' . uniqid() . '.' . $file_extension;
            $file_path = $file_name;
            move_uploaded_file($_FILES['assignment_file']['tmp_name'], $upload_dir . $file_name);
        }
    }
    
    // Insert assignment
    $insert_query = "INSERT INTO assignments (teacher_id, class_id, title, description, due_date, total_marks, file_path) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("iisssis", $teacher_id, $class_id, $title, $description, $due_date, $total_marks, $file_path);
    
    if ($stmt->execute()) {
        header('Location: assignments.php?success=created');
        exit;
    } else {
        $error = "Failed to create assignment. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Assignment - NIT AMMS</title>
    <link rel="icon" href="../Nit_logo.png" type="image/png" />
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

        .navbar h1 {
            color: white;
            font-size: 24px;
        }

        .nav-links {
            display: flex;
            gap: 15px;
        }

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
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .form-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 40px;
            border-radius: 25px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
        }

        .form-container h2 {
            font-size: 32px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: inherit;
        }

        .form-group select {
            cursor: pointer;
        }

        .form-group select option {
            padding: 10px;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .file-upload-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }

        .file-upload-input {
            position: absolute;
            left: -9999px;
        }

        .file-upload-label {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
            border: 2px dashed #667eea;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            background: rgba(102, 126, 234, 0.05);
        }

        .file-upload-label:hover {
            background: rgba(102, 126, 234, 0.1);
            border-color: #764ba2;
        }

        .file-name {
            margin-top: 10px;
            font-size: 13px;
            color: #666;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .alert-danger {
            background: rgba(248, 215, 218, 0.95);
            border: 2px solid #dc3545;
            color: #721c24;
        }

        .submit-btn {
            width: 100%;
            padding: 15px;
            font-size: 16px;
            font-weight: 700;
            margin-top: 10px;
        }

        .student-count-badge {
            display: inline-block;
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
            margin-left: 8px;
        }

        .no-students-badge {
            background: linear-gradient(135deg, #ff6b6b, #ee5a5a);
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
            }

            .form-container {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>➕ Create New Assignment</h1>
        <div class="nav-links">
            <a href="assignments.php" class="btn btn-primary">📚 My Assignments</a>
            <a href="index.php" class="btn btn-primary">🏠 Dashboard</a>
        </div>
    </nav>

    <div class="container">
        <div class="form-container">
            <h2>📝 Assignment Details</h2>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    ❌ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="class_id">🏫 Select Class *</label>
                    <select name="class_id" id="class_id" required>
                        <option value="">-- Choose a class --</option>
                        <?php while ($class = $classes->fetch_assoc()): ?>
                            <option value="<?php echo $class['id']; ?>">
                                <?php echo htmlspecialchars($class['section']); ?> - 
                                <?php echo htmlspecialchars($class['class_name']); ?> 
                                (<?php echo $class['year']; ?> Year, Sem <?php echo $class['semester']; ?>)
                                - <?php echo htmlspecialchars($class['academic_year']); ?>
                                [👥 <?php echo $class['student_count']; ?> Students]
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <small style="color: #666; font-size: 12px; margin-top: 8px; display: block;">
                        💡 Student count shown in brackets for each class
                    </small>
                </div>

                <div class="form-group">
                    <label for="title">📄 Assignment Title *</label>
                    <input type="text" name="title" id="title" required 
                           placeholder="e.g., Physics - Newton's Laws Assignment">
                </div>

                <div class="form-group">
                    <label for="description">📝 Description</label>
                    <textarea name="description" id="description" 
                              placeholder="Provide detailed instructions for the assignment..."></textarea>
                </div>

                <div class="form-group">
                    <label for="due_date">📅 Due Date *</label>
                    <input type="date" name="due_date" id="due_date" required
                           min="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label for="total_marks">💯 Total Marks *</label>
                    <input type="number" name="total_marks" id="total_marks" required 
                           value="100" min="1" max="1000">
                </div>

                <div class="form-group">
                    <label for="assignment_file">📎 Attach File (Optional)</label>
                    <div class="file-upload-wrapper">
                        <input type="file" name="assignment_file" id="assignment_file" 
                               class="file-upload-input" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.txt"
                               onchange="displayFileName(this)">
                        <label for="assignment_file" class="file-upload-label">
                            <span>📁 Click to upload file (PDF, DOC, DOCX, JPG, PNG, TXT)</span>
                        </label>
                    </div>
                    <div id="fileName" class="file-name"></div>
                    <small style="color: #666; font-size: 12px;">Maximum file size: 10MB</small>
                </div>

                <button type="submit" class="btn btn-success submit-btn">
                    ✅ Create Assignment
                </button>
            </form>
        </div>
    </div>

    <script>
        function displayFileName(input) {
            const fileName = document.getElementById('fileName');
            if (input.files && input.files[0]) {
                fileName.textContent = '✅ Selected: ' + input.files[0].name;
                fileName.style.color = '#28a745';
            } else {
                fileName.textContent = '';
            }
        }

        // Set minimum date to today
        document.getElementById('due_date').min = new Date().toISOString().split('T')[0];
    </script>
</body>
</html>