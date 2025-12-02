<?php
require_once '../db.php';
checkRole(['teacher']);

$user = getCurrentUser();
$teacher_id = $user['id'];
$assignment_id = isset($_GET['assignment_id']) ? intval($_GET['assignment_id']) : 0;

// Verify assignment belongs to this teacher
$assignment_query = "SELECT a.*, c.section, c.class_name, c.id as class_id, 
                     c.year, c.semester, c.academic_year
                     FROM assignments a
                     JOIN classes c ON a.class_id = c.id
                     WHERE a.id = ? AND a.teacher_id = ?";
$stmt = $conn->prepare($assignment_query);
$stmt->bind_param("ii", $assignment_id, $teacher_id);
$stmt->execute();
$assignment = $stmt->get_result()->fetch_assoc();

if (!$assignment) {
    header('Location: assignments.php');
    exit;
}

// Handle marking submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_submission'])) {
    $submission_id = intval($_POST['submission_id']);
    $marks_obtained = intval($_POST['marks_obtained']);
    $feedback = sanitize($_POST['feedback']);
    
    $update_query = "UPDATE assignment_submissions 
                     SET marks_obtained = ?, feedback = ?, status = 'marked', marked_at = NOW()
                     WHERE id = ? AND assignment_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("isii", $marks_obtained, $feedback, $submission_id, $assignment_id);
    $stmt->execute();
    
    header("Location: view_submissions.php?assignment_id=$assignment_id&success=marked");
    exit;
}

// FIXED: Get ALL students matching the assignment's section, year, semester, and academic year
$submissions_query = "SELECT 
                        s.id as student_id, 
                        s.roll_number, 
                        s.full_name, 
                        s.email,
                        asub.id as submission_id, 
                        asub.submission_text, 
                        asub.file_path,
                        asub.submitted_at, 
                        asub.marks_obtained, 
                        asub.feedback, 
                        asub.status, 
                        asub.marked_at
                      FROM students s
                      INNER JOIN classes c ON s.class_id = c.id
                      LEFT JOIN assignment_submissions asub 
                        ON s.id = asub.student_id 
                        AND asub.assignment_id = ?
                      WHERE c.section = ?
                        AND c.year = ?
                        AND c.semester = ?
                        AND c.academic_year = ?
                      ORDER BY s.roll_number";

$stmt = $conn->prepare($submissions_query);
$stmt->bind_param("issis", 
    $assignment_id, 
    $assignment['section'],
    $assignment['year'],
    $assignment['semester'],
    $assignment['academic_year']
);
$stmt->execute();
$submissions = $stmt->get_result();

// Count statistics
$total_students = 0;
$submitted_count = 0;
$marked_count = 0;
$pending_count = 0;
$not_submitted_count = 0;

$submissions_array = [];
while ($sub = $submissions->fetch_assoc()) {
    $submissions_array[] = $sub;
    $total_students++;
    
    if ($sub['status'] === 'marked') {
        $marked_count++;
        $submitted_count++;
    } elseif ($sub['status'] === 'submitted') {
        $submitted_count++;
        $pending_count++;
    } else {
        $not_submitted_count++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Submissions - <?php echo htmlspecialchars($assignment['title']); ?></title>
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
            font-size: 28px;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            border-left: 4px solid #667eea;
        }

        .stat-card h3 {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 36px;
            font-weight: 800;
            color: #667eea;
        }

        .submissions-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            padding: 40px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
        }

        .filter-btn {
            padding: 10px 20px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            color: #666;
        }

        .filter-btn:hover {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }

        .filter-btn.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-color: #667eea;
        }

        .submission-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            transition: all 0.3s;
            display: block;
        }

        .submission-card.hidden {
            display: none;
        }

        .submission-card:hover {
            border-color: #667eea;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.2);
        }

        .submission-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .student-info h3 {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .student-info p {
            color: #666;
            font-size: 14px;
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

        .submission-content {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 15px 0;
        }

        .submission-content p {
            color: #2c3e50;
            line-height: 1.8;
            margin-bottom: 15px;
        }

        .marking-form {
            background: rgba(102, 126, 234, 0.05);
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            border: 2px solid rgba(102, 126, 234, 0.2);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .marks-display {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin: 15px 0;
            font-weight: 600;
        }

        .feedback-display {
            background: #fff3cd;
            padding: 15px 20px;
            border-radius: 10px;
            margin: 15px 0;
            border-left: 4px solid #ffc107;
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

        .not-submitted-notice {
            text-align: center;
            padding: 40px 20px;
            background: linear-gradient(135deg, rgba(255, 107, 107, 0.1), rgba(238, 90, 90, 0.05));
            border-radius: 10px;
            border: 2px dashed #ff6b6b;
        }

        .not-submitted-notice p {
            color: #721c24;
            font-size: 16px;
            font-weight: 600;
        }

        .file-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            padding: 8px 16px;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 8px;
            transition: all 0.3s;
        }

        .file-link:hover {
            background: rgba(102, 126, 234, 0.2);
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
            }

            .submission-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>📝 <?php echo htmlspecialchars($assignment['title']); ?> - Submissions</h1>
        <div class="nav-links">
            <a href="assignments.php" class="btn btn-primary">📚 Back to Assignments</a>
        </div>
    </nav>

    <div class="container">
        <?php if (isset($_GET['success']) && $_GET['success'] === 'marked'): ?>
            <div class="alert alert-success">
                ✅ Assignment marked successfully!
            </div>
        <?php endif; ?>

        <div class="header-section">
            <h2>📊 Submission Overview</h2>
            <p style="color: #666; margin-top: 10px;">
                <strong>Class:</strong> <?php echo htmlspecialchars($assignment['section']); ?> | 
                <strong>Due Date:</strong> <?php echo date('d M Y', strtotime($assignment['due_date'])); ?> |
                <strong>Total Marks:</strong> <?php echo $assignment['total_marks']; ?>
            </p>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>👥 Total Students</h3>
                    <div class="stat-value"><?php echo $total_students; ?></div>
                </div>
                <div class="stat-card">
                    <h3>📤 Submitted</h3>
                    <div class="stat-value" style="color: #28a745;"><?php echo $submitted_count; ?></div>
                </div>
                <div class="stat-card">
                    <h3>⏳ Pending Review</h3>
                    <div class="stat-value" style="color: #ffc107;"><?php echo $pending_count; ?></div>
                </div>
                <div class="stat-card">
                    <h3>✅ Marked</h3>
                    <div class="stat-value" style="color: #667eea;"><?php echo $marked_count; ?></div>
                </div>
                <div class="stat-card">
                    <h3>❌ Not Submitted</h3>
                    <div class="stat-value" style="color: #dc3545;"><?php echo $not_submitted_count; ?></div>
                </div>
            </div>
        </div>

        <div class="submissions-container">
            <h3 style="font-size: 24px; color: #2c3e50; margin-bottom: 25px;">📋 All Students (<?php echo count($submissions_array); ?>)</h3>

            <!-- Filter Tabs -->
            <div style="display: flex; gap: 10px; margin-bottom: 25px; flex-wrap: wrap;">
                <button class="filter-btn active" onclick="filterSubmissions('all')" data-filter="all">
                    👥 All Students (<?php echo $total_students; ?>)
                </button>
                <button class="filter-btn" onclick="filterSubmissions('submitted')" data-filter="submitted">
                    📤 Submitted (<?php echo $submitted_count; ?>)
                </button>
                <button class="filter-btn" onclick="filterSubmissions('pending')" data-filter="pending">
                    ⏳ Pending Review (<?php echo $pending_count; ?>)
                </button>
                <button class="filter-btn" onclick="filterSubmissions('marked')" data-filter="marked">
                    ✅ Marked (<?php echo $marked_count; ?>)
                </button>
                <button class="filter-btn" onclick="filterSubmissions('not-submitted')" data-filter="not-submitted">
                    ❌ Not Submitted (<?php echo $not_submitted_count; ?>)
                </button>
            </div>

            <?php if (count($submissions_array) > 0): ?>
                <?php foreach ($submissions_array as $submission): 
                    // Determine filter class
                    $filter_class = 'not-submitted';
                    if ($submission['status'] === 'marked') {
                        $filter_class = 'marked submitted';
                    } elseif ($submission['status'] === 'submitted') {
                        $filter_class = 'pending submitted';
                    }
                ?>
                    <div class="submission-card" data-status="<?php echo $filter_class; ?>">
                        <div class="submission-header">
                            <div class="student-info">
                                <h3>👤 <?php echo htmlspecialchars($submission['full_name']); ?></h3>
                                <p>
                                    Roll No: <?php echo htmlspecialchars($submission['roll_number']); ?> | 
                                    <?php echo htmlspecialchars($submission['email']); ?>
                                </p>
                            </div>
                            <div>
                                <?php if ($submission['status'] === 'marked'): ?>
                                    <span class="badge badge-success">✅ Marked</span>
                                <?php elseif ($submission['status'] === 'submitted'): ?>
                                    <span class="badge badge-warning">⏳ Pending Review</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">❌ Not Submitted</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($submission['status'] === 'submitted' || $submission['status'] === 'marked'): ?>
                            <div class="submission-content">
                                <?php if ($submission['submission_text']): ?>
                                    <p><strong>📝 Submission:</strong></p>
                                    <p><?php echo nl2br(htmlspecialchars($submission['submission_text'])); ?></p>
                                <?php endif; ?>

                                <?php if ($submission['file_path']): ?>
                                    <p>
                                        <strong>📎 Attachment:</strong><br>
                                        <?php
                                        // Extract just the filename from the stored path
                                        $filename = basename($submission['file_path']);
                                        $file_url = "../uploads/submissions/" . $filename;
                                        
                                        // Check if file exists
                                        $full_path = dirname(__DIR__) . "/uploads/submissions/" . $filename;
                                        $file_exists = file_exists($full_path);
                                        ?>
                                        
                                        <?php if ($file_exists): ?>
                                            <a href="<?php echo htmlspecialchars($file_url); ?>" 
                                               class="file-link"
                                               target="_blank">
                                                📄 <?php echo htmlspecialchars($filename); ?>
                                            </a>
                                        <?php else: ?>
                                            <span style="color: #dc3545;">
                                                ⚠️ File not found: <?php echo htmlspecialchars($filename); ?>
                                            </span>
                                        <?php endif; ?>
                                    </p>
                                <?php endif; ?>

                                <p style="color: #666; font-size: 13px; margin-top: 15px;">
                                    🕒 Submitted on: <?php echo date('d M Y, g:i A', strtotime($submission['submitted_at'])); ?>
                                </p>
                            </div>

                            <?php if ($submission['status'] === 'marked'): ?>
                                <div class="marks-display">
                                    💯 Marks Awarded: <?php echo $submission['marks_obtained']; ?> / <?php echo $assignment['total_marks']; ?>
                                    <span style="font-size: 12px; opacity: 0.9; margin-left: 10px;">
                                        (Marked on <?php echo date('d M Y', strtotime($submission['marked_at'])); ?>)
                                    </span>
                                </div>
                                
                                <?php if ($submission['feedback']): ?>
                                    <div class="feedback-display">
                                        <strong>📢 Feedback:</strong>
                                        <p style="margin-top: 10px; color: #2c3e50;">
                                            <?php echo nl2br(htmlspecialchars($submission['feedback'])); ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <form method="POST" class="marking-form">
                                    <h4 style="color: #2c3e50; margin-bottom: 15px;">✏️ Mark This Submission</h4>
                                    <input type="hidden" name="submission_id" value="<?php echo $submission['submission_id']; ?>">
                                    
                                    <div class="form-group">
                                        <label for="marks_<?php echo $submission['submission_id']; ?>">
                                            💯 Marks Obtained (out of <?php echo $assignment['total_marks']; ?>)
                                        </label>
                                        <input type="number" 
                                               name="marks_obtained" 
                                               id="marks_<?php echo $submission['submission_id']; ?>"
                                               min="0" 
                                               max="<?php echo $assignment['total_marks']; ?>" 
                                               required>
                                    </div>

                                    <div class="form-group">
                                        <label for="feedback_<?php echo $submission['submission_id']; ?>">
                                            📢 Feedback (Optional)
                                        </label>
                                        <textarea name="feedback" 
                                                  id="feedback_<?php echo $submission['submission_id']; ?>"
                                                  rows="3"
                                                  placeholder="Provide feedback to the student..."></textarea>
                                    </div>

                                    <button type="submit" name="mark_submission" class="btn btn-success">
                                        ✅ Submit Marks
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="not-submitted-notice">
                                <p style="font-size: 48px; margin-bottom: 15px;">📭</p>
                                <p>This student has not submitted the assignment yet.</p>
                                <p style="font-size: 14px; color: #999; margin-top: 10px;">
                                    <?php 
                                    $days_left = floor((strtotime($assignment['due_date']) - time()) / 86400);
                                    if ($days_left > 0) {
                                        echo "⏰ $days_left day(s) remaining until due date";
                                    } else {
                                        echo "⚠️ Assignment is past due date";
                                    }
                                    ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 60px 20px; background: #f8f9fa; border-radius: 15px;">
                    <p style="font-size: 64px; margin-bottom: 20px;">🔭</p>
                    <h3 style="color: #666; margin-bottom: 15px;">No Students Found</h3>
                    <p style="color: #999; font-size: 14px;">
                        No students are enrolled in this class yet.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function filterSubmissions(filter) {
            // Update active button
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');

            // Filter cards
            const cards = document.querySelectorAll('.submission-card');
            cards.forEach(card => {
                const status = card.getAttribute('data-status');
                
                if (filter === 'all') {
                    card.classList.remove('hidden');
                } else if (status.includes(filter)) {
                    card.classList.remove('hidden');
                } else {
                    card.classList.add('hidden');
                }
            });
        }
    </script>
</body>
</html>