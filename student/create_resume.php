<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../db.php';
checkRole(['student']);

$student_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Get existing resume data
$query = "SELECT * FROM student_resumes WHERE student_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$resume = $stmt->get_result()->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $objective = trim($_POST['objective']);
    $skills = trim($_POST['skills']);
    $education = trim($_POST['education']);
    $experience = trim($_POST['experience']);
    $projects = trim($_POST['projects']);
    $certifications = trim($_POST['certifications']);
    $languages = trim($_POST['languages']);
    $hobbies = trim($_POST['hobbies']);
    $theme = $_POST['theme'];

    if (empty($objective)) {
        $error = "Career objective is required!";
    } else {
        if ($resume) {
            // Update existing resume
            $update_query = "UPDATE student_resumes SET 
                objective = ?, skills = ?, education = ?, experience = ?, 
                projects = ?, certifications = ?, languages = ?, hobbies = ?, theme = ?
                WHERE student_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("sssssssssi", $objective, $skills, $education, $experience, 
                $projects, $certifications, $languages, $hobbies, $theme, $student_id);
        } else {
            // Insert new resume
            $insert_query = "INSERT INTO student_resumes 
                (student_id, objective, skills, education, experience, projects, certifications, languages, hobbies, theme) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("isssssssss", $student_id, $objective, $skills, $education, 
                $experience, $projects, $certifications, $languages, $hobbies, $theme);
        }

        if ($stmt->execute()) {
            header("Location: view_resume.php");
            exit();
        } else {
            $error = "Failed to save resume. Please try again.";
        }
    }
}

// Get student info
$student_query = "SELECT full_name FROM students WHERE id = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Resume - NIT College</title>
    <link rel="icon" href="../Nit_logo.png" type="image/png" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 30px;
            border-radius: 15px 15px 0 0;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            color: #667eea;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
            font-size: 16px;
        }

        .form-container {
            background: white;
            padding: 40px;
            border-radius: 0 0 15px 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 15px;
        }

        .form-group label .required {
            color: #e74c3c;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-group .hint {
            font-size: 13px;
            color: #888;
            margin-top: 5px;
        }

        .theme-selector {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }

        .theme-option {
            position: relative;
            cursor: pointer;
        }

        .theme-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }

        .theme-card {
            padding: 20px;
            border: 3px solid #e0e0e0;
            border-radius: 12px;
            text-align: center;
            transition: all 0.3s;
            background: white;
        }

        .theme-option input[type="radio"]:checked + .theme-card {
            border-color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            transform: scale(1.05);
        }

        .theme-card .theme-name {
            font-weight: 700;
            font-size: 16px;
            color: #333;
            margin-bottom: 5px;
        }

        .theme-card .theme-desc {
            font-size: 13px;
            color: #666;
        }

        .theme-card .theme-preview {
            width: 100%;
            height: 60px;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .theme-professional .theme-preview {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
        }

        .theme-modern .theme-preview {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .theme-creative .theme-preview {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .theme-minimal .theme-preview {
            background: #2c3e50;
        }

        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 14px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            flex: 1;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .container {
                padding: 0;
            }

            .header, .form-container {
                border-radius: 0;
            }

            .theme-selector {
                grid-template-columns: 1fr;
            }

            .button-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo $resume ? '✏️ Edit Resume' : '📄 Create Your Resume'; ?></h1>
            <p>Build your professional resume with our easy-to-use form</p>
        </div>

        <div class="form-container">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label>🎯 Career Objective <span class="required">*</span></label>
                    <textarea name="objective" required><?php echo htmlspecialchars($resume['objective'] ?? ''); ?></textarea>
                    <div class="hint">Describe your career goals and what you're looking for</div>
                </div>

                <div class="form-group">
                    <label>💡 Skills</label>
                    <input type="text" name="skills" value="<?php echo htmlspecialchars($resume['skills'] ?? ''); ?>" placeholder="Python, Java, Web Development, Data Analysis">
                    <div class="hint">Separate skills with commas</div>
                </div>

                <div class="form-group">
                    <label>🎓 Education</label>
                    <textarea name="education"><?php echo htmlspecialchars($resume['education'] ?? ''); ?></textarea>
                    <div class="hint">Include your degrees, institutions, and years</div>
                </div>

                <div class="form-group">
                    <label>💼 Experience</label>
                    <textarea name="experience"><?php echo htmlspecialchars($resume['experience'] ?? ''); ?></textarea>
                    <div class="hint">List your work experience, internships, or relevant positions</div>
                </div>

                <div class="form-group">
                    <label>🚀 Projects</label>
                    <textarea name="projects"><?php echo htmlspecialchars($resume['projects'] ?? ''); ?></textarea>
                    <div class="hint">Describe your notable projects with technologies used</div>
                </div>

                <div class="form-group">
                    <label>📜 Certifications</label>
                    <textarea name="certifications"><?php echo htmlspecialchars($resume['certifications'] ?? ''); ?></textarea>
                    <div class="hint">List your certifications and achievements</div>
                </div>

                <div class="form-group">
                    <label>🌐 Languages</label>
                    <input type="text" name="languages" value="<?php echo htmlspecialchars($resume['languages'] ?? ''); ?>" placeholder="English, Hindi, Marathi">
                    <div class="hint">Languages you can speak or write</div>
                </div>

                <div class="form-group">
                    <label>🎮 Hobbies & Interests</label>
                    <input type="text" name="hobbies" value="<?php echo htmlspecialchars($resume['hobbies'] ?? ''); ?>" placeholder="Reading, Coding, Sports">
                    <div class="hint">Your personal interests and hobbies</div>
                </div>

                <div class="form-group">
                    <label>🎨 Choose Resume Theme</label>
                    <div class="theme-selector">
                        <label class="theme-option theme-professional">
                            <input type="radio" name="theme" value="professional" <?php echo (!isset($resume['theme']) || $resume['theme'] == 'professional') ? 'checked' : ''; ?>>
                            <div class="theme-card">
                                <div class="theme-preview"></div>
                                <div class="theme-name">Professional</div>
                                <div class="theme-desc">Classic blue theme</div>
                            </div>
                        </label>

                        <label class="theme-option theme-modern">
                            <input type="radio" name="theme" value="modern" <?php echo ($resume['theme'] ?? '') == 'modern' ? 'checked' : ''; ?>>
                            <div class="theme-card">
                                <div class="theme-preview"></div>
                                <div class="theme-name">Modern</div>
                                <div class="theme-desc">Gradient purple theme</div>
                            </div>
                        </label>

                        <label class="theme-option theme-creative">
                            <input type="radio" name="theme" value="creative" <?php echo ($resume['theme'] ?? '') == 'creative' ? 'checked' : ''; ?>>
                            <div class="theme-card">
                                <div class="theme-preview"></div>
                                <div class="theme-name">Creative</div>
                                <div class="theme-desc">Pink gradient theme</div>
                            </div>
                        </label>

                        <label class="theme-option theme-minimal">
                            <input type="radio" name="theme" value="minimal" <?php echo ($resume['theme'] ?? '') == 'minimal' ? 'checked' : ''; ?>>
                            <div class="theme-card">
                                <div class="theme-preview"></div>
                                <div class="theme-name">Minimal</div>
                                <div class="theme-desc">Clean dark theme</div>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $resume ? '💾 Update Resume' : '✨ Create Resume'; ?>
                    </button>
                    <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>