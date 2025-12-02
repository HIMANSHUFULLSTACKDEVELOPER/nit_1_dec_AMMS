<?php
require_once '../db.php';
checkRole(['teacher']);

$user = getCurrentUser();
$teacher_id = $user['id'];

// Get teacher's classes
$classes_query = "SELECT DISTINCT c.id, c.section, c.class_name, c.year, c.semester, c.academic_year
                  FROM classes c
                  WHERE c.teacher_id = ?
                  ORDER BY c.academic_year DESC, c.section";
$stmt = $conn->prepare($classes_query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$classes = $stmt->get_result();

// Get all assignments created by this teacher
$assignments_query = "SELECT a.*, c.section, c.class_name, c.year, c.semester,
                      COUNT(DISTINCT asub.id) as total_submissions,
                      COUNT(DISTINCT CASE WHEN asub.status = 'marked' THEN asub.id END) as marked_count
                      FROM assignments a
                      LEFT JOIN classes c ON a.class_id = c.id
                      LEFT JOIN assignment_submissions asub ON a.id = asub.assignment_id
                      WHERE a.teacher_id = ?
                      GROUP BY a.id
                      ORDER BY a.created_at DESC";
$stmt = $conn->prepare($assignments_query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$assignments = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignment Management - NIT AMMS</title>
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
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
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
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            padding: 25px;
            border-radius: 15px;
            border-left: 4px solid #667eea;
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

        .badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11px;
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

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .progress-bar {
            background: #e0e0e0;
            border-radius: 10px;
            height: 10px;
            overflow: hidden;
            margin: 10px 0;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            transition: width 0.3s;
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

            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>📚 Assignment Management</h1>
        <div class="nav-links">
            <a href="index.php" class="btn btn-primary">🏠 Dashboard</a>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="container">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                ✅ <?php 
                    if ($_GET['success'] === 'created') echo 'Assignment created successfully!';
                    elseif ($_GET['success'] === 'deleted') echo 'Assignment deleted successfully!';
                    elseif ($_GET['success'] === 'marked') echo 'Assignment marked successfully!';
                ?>
            </div>
        <?php endif; ?>

        <div class="header-section">
            <h2>📋 Your Assignments</h2>
            <p style="color: #666; margin-bottom: 20px;">Manage all your assignments and track student submissions</p>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>📝 Total Assignments</h3>
                    <div class="stat-value"><?php echo $assignments->num_rows; ?></div>
                </div>
                <div class="stat-card">
                    <h3>👥 Your Classes</h3>
                    <div class="stat-value"><?php echo $classes->num_rows; ?></div>
                </div>
            </div>

            <div style="margin-top: 30px;">
                <a href="create_assignment.php" class="btn btn-success">➕ Create New Assignment</a>
            </div>
        </div>

        <?php if ($assignments->num_rows > 0): ?>
            <div class="assignments-grid">
                <?php while ($assignment = $assignments->fetch_assoc()): 
                    $is_overdue = strtotime($assignment['due_date']) < time();
                    $progress = $assignment['total_submissions'] > 0 
                        ? round(($assignment['marked_count'] / $assignment['total_submissions']) * 100) 
                        : 0;
                ?>
                    <div class="assignment-card">
                        <div class="assignment-header">
                            <div>
                                <div class="assignment-title">
                                    📄 <?php echo htmlspecialchars($assignment['title']); ?>
                                </div>
                                <span class="badge <?php echo $is_overdue ? 'badge-danger' : 'badge-success'; ?>">
                                    <?php echo $is_overdue ? '⏰ Overdue' : '✅ Active'; ?>
                                </span>
                            </div>
                        </div>

                        <?php if ($assignment['description']): ?>
                            <p style="color: #666; margin: 15px 0; line-height: 1.6;">
                                <?php echo nl2br(htmlspecialchars($assignment['description'])); ?>
                            </p>
                        <?php endif; ?>

                        <div class="assignment-meta">
                            <div class="meta-item">
                                <span>🏫</span>
                                <div>
                                    <strong>Class:</strong> 
                                    <?php echo htmlspecialchars($assignment['section']); ?>
                                </div>
                            </div>
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
                            <div class="meta-item">
                                <span>📊</span>
                                <div>
                                    <strong>Submissions:</strong> 
                                    <?php echo $assignment['total_submissions']; ?>
                                </div>
                            </div>
                        </div>

                        <?php if ($assignment['total_submissions'] > 0): ?>
                            <div style="margin: 20px 0;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                    <span style="font-size: 13px; color: #666;">Marking Progress</span>
                                    <span style="font-size: 13px; font-weight: 600; color: #28a745;">
                                        <?php echo $assignment['marked_count']; ?>/<?php echo $assignment['total_submissions']; ?> Marked
                                    </span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($assignment['file_path']): ?>
                            <div class="meta-item" style="margin: 15px 0;">
                                <span>📎</span>
                                <a href="../uploads/assignments/<?php echo htmlspecialchars($assignment['file_path']); ?>" 
                                   target="_blank" style="color: #667eea; text-decoration: none;">
                                    View Attachment
                                </a>
                            </div>
                        <?php endif; ?>

                        <div class="action-buttons">
                            <a href="view_submissions.php?assignment_id=<?php echo $assignment['id']; ?>" 
                               class="btn btn-primary">
                                📝 View Submissions (<?php echo $assignment['total_submissions']; ?>)
                            </a>
                            <a href="edit_assignment.php?id=<?php echo $assignment['id']; ?>" 
                               class="btn btn-success">
                                ✏️ Edit
                            </a>
                            <a href="delete_assignment.php?id=<?php echo $assignment['id']; ?>" 
                               class="btn btn-danger"
                               onclick="return confirm('Are you sure you want to delete this assignment?')">
                                🗑️ Delete
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <h3>📝 No Assignments Yet</h3>
                <p>You haven't created any assignments yet. Click "Create New Assignment" to get started!</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>