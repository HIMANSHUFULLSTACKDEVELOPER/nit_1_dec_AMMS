<?php
require_once '../db.php';
checkRole(['student']);

$student_id = $_SESSION['user_id'];
$assignment_id = isset($_GET['assignment_id']) ? intval($_GET['assignment_id']) : 0;

// First, get student's class details
$student_query = "SELECT s.*, c.section, c.year, c.semester, c.academic_year
                  FROM students s
                  JOIN classes c ON s.class_id = c.id
                  WHERE s.id = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student_info = $stmt->get_result()->fetch_assoc();

if (!$student_info) {
    header('Location: assignments.php');
    exit;
}

// FIXED: Get assignment details matching student's section/year/semester/academic_year
$assignment_query = "SELECT a.*, u.full_name as teacher_name, c.section
                     FROM assignments a
                     JOIN users u ON a.teacher_id = u.id
                     JOIN classes c ON a.class_id = c.id
                     WHERE a.id = ?
                       AND c.section = ?
                       AND c.year = ?
                       AND c.semester = ?
                       AND c.academic_year = ?";
$stmt = $conn->prepare($assignment_query);
$stmt->bind_param("issis", 
    $assignment_id,
    $student_info['section'],
    $student_info['year'],
    $student_info['semester'],
    $student_info['academic_year']
);
$stmt->execute();
$assignment = $stmt->get_result()->fetch_assoc();

if (!$assignment) {
    header('Location: assignments.php?error=not_found');
    exit;
}

// Check if already submitted
$check_query = "SELECT id FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("ii", $assignment_id, $student_id);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();

if ($existing) {
    header('Location: assignments.php?error=already_submitted');
    exit;
}

// Check if overdue
$is_overdue = strtotime($assignment['due_date']) < time();
if ($is_overdue) {
    header('Location: assignments.php?error=overdue');
    exit;
}

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submission_text = sanitize($_POST['submission_text']);
    
    // Handle file upload
    $file_path = null;
    if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/submissions/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = pathinfo($_FILES['submission_file']['name'], PATHINFO_EXTENSION);
        $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'txt', 'zip'];
        
        if (in_array(strtolower($file_extension), $allowed_extensions)) {
            $file_name = 'submission_' . $student_id . '_' . $assignment_id . '_' . time() . '.' . $file_extension;
            $file_path = $file_name;
            move_uploaded_file($_FILES['submission_file']['tmp_name'], $upload_dir . $file_name);
        }
    }
    
    // Insert submission
    $insert_query = "INSERT INTO assignment_submissions (assignment_id, student_id, submission_text, file_path, status) 
                     VALUES (?, ?, ?, ?, 'submitted')";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("iiss", $assignment_id, $student_id, $submission_text, $file_path);
    
    if ($stmt->execute()) {
        header('Location: assignments.php?success=submitted');
        exit;
    } else {
        $error = "Failed to submit assignment. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Assignment - <?php echo htmlspecialchars($assignment['title']); ?></title>
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
            font-size: 20px;
            max-width: 600px;
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

        .assignment-info {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 25px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        }

        .assignment-info h2 {
            font-size: 28px;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #666;
            font-size: 14px;
        }

        .info-item strong {
            color: #2c3e50;
        }

        .form-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 40px;
            border-radius: 25px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
        }

        .form-container h3 {
            font-size: 24px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 25px;
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

        .form-group textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
            resize: vertical;
            min-height: 150px;
            transition: all 0.3s;
        }

        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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
            padding: 20px;
            border: 3px dashed #667eea;
            border-radius: 15px;
            cursor: pointer;
            transition: all 0.3s;
            background: rgba(102, 126, 234, 0.05);
            text-align: center;
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

        .alert-warning {
            background: rgba(255, 243, 205, 0.95);
            border: 2px solid #ffc107;
            color: #856404;
        }

        .submit-btn {
            width: 100%;
            padding: 15px;
            font-size: 16px;
            font-weight: 700;
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
            }

            .form-container {
                padding: 25px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>📤 Submit: <?php echo htmlspecialchars($assignment['title']); ?></h1>
        <div class="nav-links">
            <a href="assignment.php" class="btn btn-primary">📚 My Assignments</a>
        </div>
    </nav>

    <div class="container">
        <div class="assignment-info">
            <h2>📄 Assignment Details</h2>
            
            <?php if ($assignment['description']): ?>
                <p style="color: #666; margin: 15px 0; line-height: 1.6;">
                    <?php echo nl2br(htmlspecialchars($assignment['description'])); ?>
                </p>
            <?php endif; ?>

            <div class="info-grid">
                <div class="info-item">
                    <span>👨‍🏫</span>
                    <div>
                        <strong>Teacher:</strong> 
                        <?php echo htmlspecialchars($assignment['teacher_name']); ?>
                    </div>
                </div>
                <div class="info-item">
                    <span>📅</span>
                    <div>
                        <strong>Due Date:</strong> 
                        <?php echo date('d M Y', strtotime($assignment['due_date'])); ?>
                    </div>
                </div>
                <div class="info-item">
                    <span>💯</span>
                    <div>
                        <strong>Total Marks:</strong> 
                        <?php echo $assignment['total_marks']; ?>
                    </div>
                </div>
            </div>

            <?php if ($assignment['file_path']): ?>
                <div style="margin-top: 20px; padding: 15px; background: rgba(102, 126, 234, 0.1); border-radius: 10px;">
                    <span>📎</span>
                    <a href="../uploads/assignments/<?php echo htmlspecialchars($assignment['file_path']); ?>" 
                       target="_blank" style="color: #667eea; text-decoration: none; margin-left: 10px;">
                        View Assignment File
                    </a>
                </div>
            <?php endif; ?>

            <?php 
            $days_left = floor((strtotime($assignment['due_date']) - time()) / 86400);
            if ($days_left >= 0):
            ?>
                <div class="alert <?php echo $days_left <= 2 ? 'alert-warning' : ''; ?>" 
                     style="margin-top: 20px; background: rgba(255, 193, 7, 0.1); border: 2px solid #ffc107;">
                    ⏰ <strong><?php echo $days_left; ?> day(s) left</strong> to submit this assignment
                </div>
            <?php endif; ?>
        </div>

        <div class="form-container">
            <h3>✍️ Your Submission</h3>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    ❌ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="submission_text">📝 Write Your Answer / Solution *</label>
                    <textarea name="submission_text" id="submission_text" required 
                              placeholder="Type your answer or solution here. Be detailed and clear in your response."></textarea>
                    <small style="color: #666; font-size: 12px;">
                        💡 Tip: Provide clear explanations and show your work
                    </small>
                </div>

                <div class="form-group">
                    <label for="submission_file">📎 Attach File (Optional)</label>
                    <!-- <div class="file-upload-wrapper">
                        <input type="file" name="submission_file" id="submission_file" 
                               class="file-upload-input" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.txt,.zip"
                               onchange="displayFileName(this)">
                        <label for="submission_file" class="file-upload-label">
                            <div>
                                <p style="font-size: 48px; margin-bottom: 10px;">📁</p>
                                <p style="font-weight: 600;">Click to upload supporting files</p>
                                <p style="font-size: 13px; color: #666; margin-top: 5px;">
                                    PDF, DOC, DOCX, JPG, PNG, TXT, ZIP
                                </p>
                            </div>
                        </label>
                    </div>
                    <div id="fileName" class="file-name"></div>
                    <small style="color: #666; font-size: 12px;">Maximum file size: 10MB</small>
                </div>

                <div class="alert alert-warning">
                    ⚠️ <strong>Important:</strong> Once submitted, you cannot edit your assignment. 
                    Please review your work carefully before submitting.
                </div> -->

                <button type="submit" class="btn btn-success submit-btn">
                    ✅ Submit Assignment
                </button>
            </form>
        </div>
    </div>

    <script>
        function displayFileName(input) {
            const fileName = document.getElementById('fileName');
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const fileSize = (file.size / 1024 / 1024).toFixed(2);
                fileName.innerHTML = `<span style="color: #28a745;">✅ Selected: ${file.name}</span><br>
                                     <span style="color: #666; font-size: 12px;">Size: ${fileSize} MB</span>`;
            } else {
                fileName.textContent = '';
            }
        }

        document.querySelector('form').addEventListener('submit', function(e) {
            if (!confirm('Are you sure you want to submit this assignment? You cannot edit it after submission.')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>