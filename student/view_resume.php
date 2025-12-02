<?php
require_once '../db.php';
checkRole(['student']);

$student_id = $_SESSION['user_id'];

// Get student info and resume
$query = "SELECT s.*, d.dept_name, c.class_name, c.section, r.*
          FROM students s
          LEFT JOIN departments d ON s.department_id = d.id
          LEFT JOIN classes c ON s.class_id = c.id
          LEFT JOIN student_resumes r ON s.id = r.student_id
          WHERE s.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data || !$data['objective']) {
    header("Location: create_resume.php");
    exit();
}

$theme = $data['theme'] ?? 'professional';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Resume - <?php echo htmlspecialchars($data['full_name']); ?></title>
    <link rel="icon" href="../Nit_logo.png" type="image/png" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .controls {
            max-width: 900px;
            margin: 0 auto 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        /* Resume Container */
        .resume {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            min-height: 1000px;
        }

        /* Professional Theme */
        .theme-professional .resume-header {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            color: white;
            padding: 50px 40px;
        }

        .theme-professional .resume-header h1 {
            font-size: 42px;
            margin-bottom: 10px;
        }

        .theme-professional .resume-header .contact-info {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 20px;
            font-size: 14px;
        }

        .theme-professional .resume-content {
            padding: 40px;
        }

        .theme-professional .section {
            margin-bottom: 35px;
        }

        .theme-professional .section-title {
            font-size: 22px;
            color: #1e3c72;
            border-bottom: 3px solid #1e3c72;
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-weight: 700;
        }

        /* Modern Theme */
        .theme-modern .resume-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 50px 40px;
            position: relative;
            overflow: hidden;
        }

        .theme-modern .resume-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        }

        .theme-modern .resume-header h1 {
            font-size: 38px;
            margin-bottom: 15px;
            position: relative;
            z-index: 1;
        }

        .theme-modern .resume-content {
            padding: 40px;
            background: linear-gradient(to bottom, #ffffff 0%, #f8f9fa 100%);
        }

        .theme-modern .section-title {
            font-size: 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
            font-weight: 700;
            display: inline-block;
        }

        /* Creative Theme */
        .theme-creative .resume-header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 50px 40px;
            clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%);
        }

        .theme-creative .resume-header h1 {
            font-size: 40px;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .theme-creative .resume-content {
            padding: 40px;
            background: #fff;
        }

        .theme-creative .section {
            background: linear-gradient(135deg, rgba(240, 147, 251, 0.1), rgba(245, 87, 108, 0.1));
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            border-left: 5px solid #f093fb;
        }

        .theme-creative .section-title {
            font-size: 22px;
            color: #f5576c;
            margin-bottom: 15px;
            font-weight: 700;
        }

        /* Minimal Theme */
        .theme-minimal .resume-header {
            background: #2c3e50;
            color: white;
            padding: 40px;
            border-left: 8px solid #3498db;
        }

        .theme-minimal .resume-header h1 {
            font-size: 36px;
            margin-bottom: 10px;
            font-weight: 300;
            letter-spacing: 2px;
        }

        .theme-minimal .resume-content {
            padding: 40px;
        }

        .theme-minimal .section {
            margin-bottom: 30px;
            padding-left: 20px;
            border-left: 4px solid #ecf0f1;
        }

        .theme-minimal .section-title {
            font-size: 18px;
            color: #2c3e50;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 15px;
            font-weight: 600;
        }

        /* Common Styles */
        .contact-info span {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .section-content {
            line-height: 1.8;
            color: #333;
            white-space: pre-wrap;
        }

        .skills-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .skill-tag {
            padding: 8px 16px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .controls {
                display: none;
            }

            .resume {
                box-shadow: none;
                max-width: 100%;
            }
        }

        @media (max-width: 768px) {
            .resume {
                margin: 0;
            }

            .theme-professional .resume-header,
            .theme-modern .resume-header,
            .theme-creative .resume-header,
            .theme-minimal .resume-header {
                padding: 30px 20px;
            }

            .theme-professional .resume-content,
            .theme-modern .resume-content,
            .theme-creative .resume-content,
            .theme-minimal .resume-content {
                padding: 20px;
            }

            .controls {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="controls">
        <a href="create_resume.php" class="btn btn-primary">✏️ Edit Resume</a>
        <button onclick="window.print()" class="btn btn-success">🖨️ Print / Save PDF</button>
        <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
    </div>

    <div class="resume theme-<?php echo $theme; ?>">
        <!-- Header -->
        <div class="resume-header">
            <h1><?php echo htmlspecialchars($data['full_name']); ?></h1>
           
            <div class="contact-info">
                
           




                <span>📱 <?php echo htmlspecialchars($data['phone']); ?></span>
               
            </div>
        </div>

        <!-- Content -->
        <div class="resume-content">
            <?php if (!empty($data['objective'])): ?>
            <div class="section">
                <div class="section-title">🎯 CAREER OBJECTIVE</div>
                <div class="section-content"><?php echo nl2br(htmlspecialchars($data['objective'])); ?></div>
            </div>
            <?php endif; ?>

            <?php if (!empty($data['skills'])): ?>
            <div class="section">
                <div class="section-title">💡 SKILLS</div>
                <div class="skills-list">
                    <?php
                    $skills = array_filter(array_map('trim', explode(',', $data['skills'])));
                    foreach ($skills as $skill):
                    ?>
                        <span class="skill-tag"><?php echo htmlspecialchars($skill); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($data['education'])): ?>
            <div class="section">
                <div class="section-title">🎓 EDUCATION</div>
                <div class="section-content"><?php echo nl2br(htmlspecialchars($data['education'])); ?></div>
            </div>
            <?php endif; ?>

            <?php if (!empty($data['experience'])): ?>
            <div class="section">
                <div class="section-title">💼 EXPERIENCE</div>
                <div class="section-content"><?php echo nl2br(htmlspecialchars($data['experience'])); ?></div>
            </div>
            <?php endif; ?>

            <?php if (!empty($data['projects'])): ?>
            <div class="section">
                <div class="section-title">🚀 PROJECTS</div>
                <div class="section-content"><?php echo nl2br(htmlspecialchars($data['projects'])); ?></div>
            </div>
            <?php endif; ?>

            <?php if (!empty($data['certifications'])): ?>
            <div class="section">
                <div class="section-title">📜 CERTIFICATIONS</div>
                <div class="section-content"><?php echo nl2br(htmlspecialchars($data['certifications'])); ?></div>
            </div>
            <?php endif; ?>

            <?php if (!empty($data['languages'])): ?>
            <div class="section">
                <div class="section-title">🌐 LANGUAGES</div>
                <div class="section-content"><?php echo htmlspecialchars($data['languages']); ?></div>
            </div>
            <?php endif; ?>

            <?php if (!empty($data['hobbies'])): ?>
            <div class="section">
                <div class="section-title">🎮 HOBBIES & INTERESTS</div>
                <div class="section-content"><?php echo htmlspecialchars($data['hobbies']); ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>