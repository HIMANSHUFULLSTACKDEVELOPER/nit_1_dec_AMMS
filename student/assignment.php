<?php
require_once '../db.php';
checkRole(['student']);

$student_id = $_SESSION['user_id'];

// Get student info with class details
$student_query = "SELECT s.*, c.id as class_id, c.section, c.class_name, 
                  c.year, c.semester, c.academic_year
                  FROM students s
                  LEFT JOIN classes c ON s.class_id = c.id
                  WHERE s.id = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student || !$student['class_id']) {
    die("Error: Student class information not found.");
}

// FIXED: Get all assignments matching student's section, year, semester, and academic year
$assignments_query = "SELECT a.*, u.full_name as teacher_name, c.section as assignment_section,
                      asub.id as submission_id, asub.status, asub.submitted_at,
                      asub.marks_obtained, asub.feedback, asub.marked_at
                      FROM assignments a
                      JOIN users u ON a.teacher_id = u.id
                      JOIN classes c ON a.class_id = c.id
                      LEFT JOIN assignment_submissions asub ON a.id = asub.assignment_id AND asub.student_id = ?
                      WHERE c.section = ?
                        AND c.year = ?
                        AND c.semester = ?
                        AND c.academic_year = ?
                      ORDER BY a.due_date DESC, a.created_at DESC";
$stmt = $conn->prepare($assignments_query);
$stmt->bind_param("issis", 
    $student_id,
    $student['section'],
    $student['year'],
    $student['semester'],
    $student['academic_year']
);
$stmt->execute();
$assignments = $stmt->get_result();

// Calculate statistics
$total_assignments = 0;
$submitted_count = 0;
$pending_count = 0;
$marked_count = 0;
$total_marks_obtained = 0;
$total_marks_possible = 0;

$assignments->data_seek(0);
while ($assignment = $assignments->fetch_assoc()) {
    $total_assignments++;
    if ($assignment['status'] === 'submitted' || $assignment['status'] === 'marked') {
        $submitted_count++;
    }
    if ($assignment['status'] === 'marked') {
        $marked_count++;
        $total_marks_obtained += $assignment['marks_obtained'];
        $total_marks_possible += $assignment['total_marks'];
    }
    if (!$assignment['status'] || $assignment['status'] === 'pending') {
        $pending_count++;
    }
}
$assignments->data_seek(0);

$average_percentage = $total_marks_possible > 0 
    ? round(($total_marks_obtained / $total_marks_possible) * 100, 2) 
    : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Assignments - NIT AMMS</title>
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
            position: sticky;
            top: 0;
            z-index: 1000;
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

        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b, #ee5a5a);
            color: white;
        }

        .container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .header-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 40px;
            border-radius: 25px;
            margin-bottom: 30px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
        }

        .header-section h2 {
            font-size: 32px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            padding: 25px;
            border-radius: 15px;
            border-left: 4px solid #667eea;
            text-align: center;
        }

        .stat-card h3 {
            color: #666;
            font-size: 13px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 36px;
            font-weight: 800;
            color: #667eea;
        }

        .assignments-grid {
            display: grid;
            gap: 25px;
        }

        .assignment-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            transition: all 0.3s;
            border: 2px solid rgba(102, 126, 234, 0.2);
        }

        .assignment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 50px rgba(102, 126, 234, 0.3);
        }

        .assignment-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 20px;
        }

        .assignment-title {
            font-size: 24px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .teacher-name {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }

        .badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-success {
            background: linear-gradient(135deg, #d4edda, #a8d5ba);
            color: #155724;
        }

        .badge-warning {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            color: #856404;
        }

        .badge-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
        }

        .badge-info {
            background: linear-gradient(135deg, #d1ecf1, #b8daff);
            color: #0c5460;
        }

        .assignment-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #666;
            font-size: 14px;
        }

        .meta-item strong {
            color: #2c3e50;
        }

        .file-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            padding: 10px 20px;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 10px;
            transition: all 0.3s;
            margin: 15px 0;
        }

        .file-link:hover {
            background: rgba(102, 126, 234, 0.2);
            transform: translateY(-2px);
        }

        .marks-display {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
            text-align: center;
        }

        .marks-display h3 {
            font-size: 16px;
            margin-bottom: 10px;
            opacity: 0.9;
        }

        .marks-value {
            font-size: 48px;
            font-weight: 800;
            margin: 10px 0;
        }

        .feedback-box {
            background: #fff3cd;
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
            border-left: 4px solid #ffc107;
        }

        .feedback-box h4 {
            color: #856404;
            margin-bottom: 10px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .alert {
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: rgba(212, 237, 218, 0.95);
            border: 2px solid #28a745;
            color: #155724;
        }

        .alert-info {
            background: rgba(209, 236, 241, 0.95);
            border: 2px solid #17a2b8;
            color: #0c5460;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
            }

            .assignment-header {
                flex-direction: column;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>📚 My Assignments</h1>
        <div class="nav-links">
            <a href="index.php" class="btn btn-primary">🏠 Dashboard</a>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="container">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                ✅ <?php 
                    if ($_GET['success'] === 'submitted') echo 'Assignment submitted successfully!';
                ?>
            </div>
        <?php endif; ?>

        <div class="header-section">
            <h2>📊 Assignment Dashboard</h2>
            <p style="color: #666; margin-bottom: 20px;">
                <strong>Class:</strong> <?php echo htmlspecialchars($student['section']); ?> | 
                <strong>Roll No:</strong> <?php echo htmlspecialchars($student['roll_number']); ?> |
                <strong>Academic Year:</strong> <?php echo htmlspecialchars($student['academic_year']); ?>
            </p>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>📝 Total Assignments</h3>
                    <div class="stat-value"><?php echo $total_assignments; ?></div>
                </div>
                <div class="stat-card">
                    <h3>✅ Submitted</h3>
                    <div class="stat-value" style="color: #28a745;"><?php echo $submitted_count; ?></div>
                </div>
                <div class="stat-card">
                    <h3>⏳ Pending</h3>
                    <div class="stat-value" style="color: #ffc107;"><?php echo $pending_count; ?></div>
                </div>
                <div class="stat-card">
                    <h3>💯 Marked</h3>
                    <div class="stat-value" style="color: #667eea;"><?php echo $marked_count; ?></div>
                </div>
                <?php if ($marked_count > 0): ?>
                <div class="stat-card">
                    <h3>📊 Average Score</h3>
                    <div class="stat-value" style="color: <?php echo $average_percentage >= 60 ? '#28a745' : '#dc3545'; ?>">
                        <?php echo $average_percentage; ?>%
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($assignments->num_rows > 0): ?>
            <div class="assignments-grid">
                <?php while ($assignment = $assignments->fetch_assoc()): 
                    $is_overdue = strtotime($assignment['due_date']) < time();
                    $can_submit = !$is_overdue && (!$assignment['status'] || $assignment['status'] === 'pending');
                ?>
                    <div class="assignment-card">
                        <div class="assignment-header">
                            <div>
                                <div class="assignment-title">
                                    📄 <?php echo htmlspecialchars($assignment['title']); ?>
                                </div>
                                <div class="teacher-name">
                                    👨‍🏫 <?php echo htmlspecialchars($assignment['teacher_name']); ?>
                                </div>
                            </div>
                            <div>
                                <?php if ($assignment['status'] === 'marked'): ?>
                                    <span class="badge badge-success">✅ Marked</span>
                                <?php elseif ($assignment['status'] === 'submitted'): ?>
                                    <span class="badge badge-info">⏳ Under Review</span>
                                <?php elseif ($is_overdue): ?>
                                    <span class="badge badge-danger">⏰ Overdue</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">📝 Pending</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($assignment['description']): ?>
                            <p style="color: #666; margin: 15px 0; line-height: 1.6;">
                                <?php echo nl2br(htmlspecialchars($assignment['description'])); ?>
                            </p>
                        <?php endif; ?>

                        <div class="assignment-meta">
                            <div class="meta-item">
                                <span>📅</span>
                                <div>
                                    <strong>Due Date:</strong> 
                                    <?php echo date('d M Y', strtotime($assignment['due_date'])); ?>
                                </div>
                            </div>
                            <div class="meta-item">
                                <span>💯</span>
                                <div>
                                    <strong>Total Marks:</strong> 
                                    <?php echo $assignment['total_marks']; ?>
                                </div>
                            </div>
                            <?php if ($assignment['submitted_at']): ?>
                            <div class="meta-item">
                                <span>🕒</span>
                                <div>
                                    <strong>Submitted:</strong> 
                                    <?php echo date('d M Y', strtotime($assignment['submitted_at'])); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($assignment['file_path']): ?>
                            <?php
                            $filename = basename($assignment['file_path']);
                            $filepath = dirname(dirname(__FILE__)) . "/uploads/assignments/" . $filename;
                            $file_exists = file_exists($filepath);
                            ?>
                            
                            <?php if ($file_exists): ?>
                                <a href="download_assignment.php?assignment_id=<?php echo $assignment['id']; ?>" 
                                   class="file-link"
                                   target="_blank">
                                    📎 Download Assignment File
                                </a>
                                <small style="display: block; margin-top: 5px; color: #666;">
                                    File: <?php echo htmlspecialchars($filename); ?>
                                </small>
                            <?php else: ?>
                                <div style="background: #fff3cd; padding: 15px; border-radius: 10px; border-left: 4px solid #ffc107; margin: 15px 0;">
                                    <strong>⚠️ File Not Available</strong><br>
                                    <p style="margin-top: 10px; color: #856404;">
                                        The teacher attached a file to this assignment, but it's not available on the server. 
                                        Please contact your teacher about this assignment.
                                    </p>
                                    <small style="color: #666; display: block; margin-top: 10px;">
                                        Expected file: <?php echo htmlspecialchars($filename); ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($assignment['status'] === 'marked'): ?>
                            <div class="marks-display">
                                <h3>Your Score</h3>
                                <div class="marks-value">
                                    <?php echo $assignment['marks_obtained']; ?> / <?php echo $assignment['total_marks']; ?>
                                </div>
                                <p style="opacity: 0.9; font-size: 18px;">
                                    <?php 
                                    $percentage = ($assignment['marks_obtained'] / $assignment['total_marks']) * 100;
                                    echo round($percentage, 2); 
                                    ?>%
                                </p>
                            </div>

                            <?php if ($assignment['feedback']): ?>
                                <div class="feedback-box">
                                    <h4>📢 Teacher's Feedback</h4>
                                    <p style="color: #2c3e50; line-height: 1.6;">
                                        <?php echo nl2br(htmlspecialchars($assignment['feedback'])); ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <div class="action-buttons">
                            <?php if ($can_submit): ?>
                                <a href="submit_assignment.php?assignment_id=<?php echo $assignment['id']; ?>" 
                                   class="btn btn-success">
                                    📤 Submit Assignment
                                </a>
                            <?php elseif ($assignment['status'] === 'submitted'): ?>
                                <button class="btn" style="background: #6c757d; color: white;" disabled>
                                    ⏳ Awaiting Review
                                </button>
                            <?php elseif ($assignment['status'] === 'marked'): ?>
                                <button class="btn" style="background: #28a745; color: white;" disabled>
                                    ✅ Completed
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <h3>📝 No Assignments Yet</h3>
                <p>Your teachers haven't assigned any work yet for <strong><?php echo htmlspecialchars($student['section']); ?></strong>. Check back later!</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>