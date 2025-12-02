<?php
require_once '../db.php';
checkRole(['student']);

$student_id = $_SESSION['user_id'];

// Get student info
$student_query = "SELECT s.*, d.dept_name, c.class_name, c.section
                  FROM students s
                  LEFT JOIN departments d ON s.department_id = d.id
                  LEFT JOIN classes c ON s.class_id = c.id
                  WHERE s.id = $student_id";
$student = $conn->query($student_query)->fetch_assoc();

// Handle pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Get total count of notifications
$count_query = "SELECT COUNT(*) as total FROM student_notifications 
                WHERE student_id = $student_id";
$count_result = $conn->query($count_query);
$total_notifications = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_notifications / $per_page);

// Get all notifications with pagination
$messages_query = "SELECT sn.*, u.full_name as teacher_name, c.section as class_section
                   FROM student_notifications sn
                   LEFT JOIN users u ON sn.teacher_id = u.id
                   LEFT JOIN classes c ON sn.class_id = c.id
                   WHERE sn.student_id = $student_id
                   ORDER BY sn.created_at DESC
                   LIMIT $per_page OFFSET $offset";
$messages = $conn->query($messages_query);

// Handle mark as read
if (isset($_POST['mark_as_read'])) {
    $notification_id = (int)$_POST['notification_id'];
    $update_query = "UPDATE student_notifications SET is_read = 1 
                     WHERE id = $notification_id AND student_id = $student_id";
    $conn->query($update_query);
    header("Location: all_messages.php?page=$page");
    exit();
}

// Handle mark all as read
if (isset($_POST['mark_all_read'])) {
    $update_query = "UPDATE student_notifications SET is_read = 1 
                     WHERE student_id = $student_id";
    $conn->query($update_query);
    header("Location: all_messages.php");
    exit();
}

// Get unread count
$unread_query = "SELECT COUNT(*) as unread FROM student_notifications 
                 WHERE student_id = $student_id AND is_read = 0";
$unread_result = $conn->query($unread_query);
$unread_count = $unread_result->fetch_assoc()['unread'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Messages - Student Portal</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="icon" href="../Nit_logo.png" type="image/svg+xml" />
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
            position: relative;
            overflow-x: hidden;
        }

        /* Animated Background Particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            animation: float 15s infinite ease-in-out;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-100vh) rotate(360deg); opacity: 0; }
        }

        /* Enhanced Navbar */
        .navbar {
            background: rgba(26, 31, 58, 0.95);
            backdrop-filter: blur(20px);
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .navbar h1 {
            color: white;
            font-size: 24px;
            font-weight: 700;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            color: white;
        }

        .main-content {
            padding: 40px;
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .message-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            border: 2px solid rgba(255, 255, 255, 0.5);
            flex-wrap: wrap;
            gap: 20px;
        }

        .message-header h2 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 28px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .message-header p {
            color: #666;
            font-size: 14px;
            font-weight: 500;
            margin-top: 5px;
        }

        .unread-badge {
            background: linear-gradient(135deg, #ff6b6b, #ee5a5a);
            color: white;
            border-radius: 50%;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: bold;
            animation: bounce 2s ease-in-out infinite;
        }

        .unread-badge.all-read {
            background: linear-gradient(135deg, #28a745, #20c997);
            animation: none;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        .message-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .message-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            border: 2px solid rgba(255, 255, 255, 0.5);
        }

        .message-card {
            background: white;
            border-left: 5px solid #667eea;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .message-card:hover {
            transform: translateX(8px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.2);
        }

        .message-card.unread {
            background: linear-gradient(135deg, rgba(255, 243, 205, 0.7), rgba(255, 236, 179, 0.7));
            border-left-color: #ffc107;
        }

        .message-header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .message-from {
            font-weight: 700;
            color: #764ba2;
            font-size: 16px;
            background: linear-gradient(135deg, rgba(118, 75, 162, 0.1), rgba(102, 126, 234, 0.1));
            padding: 6px 12px;
            border-radius: 20px;
            border: 1px solid rgba(118, 75, 162, 0.2);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .message-time {
            font-size: 13px;
            color: #999;
            background: rgba(102, 126, 234, 0.1);
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
        }

        .message-content {
            color: #555;
            line-height: 1.8;
            margin: 15px 0;
            padding: 15px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.7), rgba(255, 255, 255, 0.5));
            border-radius: 10px;
            white-space: pre-wrap;
            border-left: 3px solid #667eea;
            font-size: 15px;
        }

        .message-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            font-size: 12px;
            color: #666;
            flex-wrap: wrap;
            gap: 12px;
        }

        .message-date {
            background: rgba(102, 126, 234, 0.1);
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
        }

        .email-badge {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .new-badge {
            background: linear-gradient(135deg, #ffc107, #ff9800);
            color: #000;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .mark-read-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            box-shadow: 0 3px 10px rgba(102, 126, 234, 0.3);
        }

        .mark-read-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.5);
        }

        .no-messages {
            text-align: center;
            padding: 80px 20px;
            color: #999;
        }

        .no-messages p:first-child {
            font-size: 80px;
            margin-bottom: 20px;
            animation: float 3s ease-in-out infinite;
        }

        .no-messages h3 {
            font-size: 24px;
            color: #667eea;
            margin-bottom: 10px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 40px;
            flex-wrap: wrap;
        }

        .pagination a, .pagination span {
            padding: 10px 14px;
            border: 2px solid rgba(102, 126, 234, 0.3);
            border-radius: 8px;
            text-decoration: none;
            color: #667eea;
            transition: all 0.3s;
            font-weight: 600;
            font-size: 13px;
            background: white;
        }

        .pagination a:hover {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-color: transparent;
            transform: translateY(-2px);
        }

        .pagination .active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-color: transparent;
        }

        .pagination .disabled {
            color: #ccc;
            cursor: not-allowed;
            border-color: #eee;
        }

        /* Buttons */
        .btn {
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-block;
            border: none;
            cursor: pointer;
            font-size: 14px;
            text-align: center;
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

        .btn-info {
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
            box-shadow: 0 4px 15px rgba(23, 162, 184, 0.4);
        }

        .btn-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(23, 162, 184, 0.6);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b, #ee5a5a);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4);
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.6);
        }

        .btn-warning {
            background: linear-gradient(135deg, #ffc107, #ff9800);
            color: #000;
            box-shadow: 0 4px 15px rgba(255, 152, 0, 0.4);
            font-weight: 700;
        }

        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 152, 0, 0.6);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.4);
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.6);
        }

        /* Footer */
        footer {
            background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 50%, #2a3254 100%);
            position: relative;
            overflow: hidden;
            margin-top: 60px;
        }

        .footer-border {
            height: 2px;
            background: linear-gradient(90deg, #4a9eff, #00d4ff, #4a9eff);
            background-size: 200% 100%;
            animation: borderMove 3s linear infinite;
        }

        @keyframes borderMove {
            0% { background-position: 0%; }
            100% { background-position: 200%; }
        }

        .footer-content {
            max-width: 1000px;
            margin: 0 auto;
            padding: 30px 20px 20px;
        }

        .developer-section {
            background: rgba(255, 255, 255, 0.03);
            padding: 20px;
            border-radius: 15px;
            border: 1px solid rgba(74, 158, 255, 0.15);
            text-align: center;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }

        .developer-section p {
            color: #ffffff;
            font-size: 14px;
            margin: 0 0 12px;
            font-weight: 500;
            letter-spacing: 0.5px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .company-link {
            display: inline-block;
            color: #ffffff;
            font-size: 16px;
            font-weight: 700;
            text-decoration: none;
            padding: 8px 24px;
            border: 2px solid #4a9eff;
            border-radius: 30px;
            background: linear-gradient(135deg, rgba(74, 158, 255, 0.2), rgba(0, 212, 255, 0.2));
            box-shadow: 0 3px 12px rgba(74, 158, 255, 0.3);
            margin-bottom: 15px;
            transition: all 0.3s;
        }

        .company-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(74, 158, 255, 0.5);
            border-color: #00d4ff;
        }

        .divider {
            width: 50%;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            margin: 15px auto;
        }

        .team-label {
            color: #888;
            font-size: 10px;
            margin: 0 0 12px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 600;
        }

        .dev-badges {
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 12px;
        }

        .dev-badge {
            color: #ffffff;
            font-size: 13px;
            text-decoration: none;
            padding: 8px 16px;
            background: linear-gradient(135deg, rgba(74, 158, 255, 0.25), rgba(0, 212, 255, 0.25));
            border-radius: 20px;
            border: 1px solid rgba(74, 158, 255, 0.4);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 3px 10px rgba(74, 158, 255, 0.2);
            transition: all 0.3s;
            font-weight: 600;
        }

        .dev-badge:hover {
            transform: translateY(-2px);
            border-color: #00d4ff;
            box-shadow: 0 5px 15px rgba(74, 158, 255, 0.4);
        }

        .role-tags {
            margin-top: 15px;
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .role-tag {
            font-size: 10px;
            padding: 4px 12px;
            border-radius: 12px;
            border: 1px solid rgba(74, 158, 255, 0.3);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .role-tag.full-stack {
            color: #4a9eff;
            background: rgba(74, 158, 255, 0.1);
        }

        .role-tag.ui-ux {
            color: #00d4ff;
            background: rgba(0, 212, 255, 0.1);
        }

        .role-tag.database {
            color: #4a9eff;
            background: rgba(74, 158, 255, 0.1);
        }

        .footer-bottom {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .footer-copyright {
            color: #888;
            font-size: 12px;
            margin: 0 0 10px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .footer-made-with {
            color: #666;
            font-size: 11px;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .footer-made-with .heart {
            color: #ff4757;
            font-size: 14px;
        }

        .social-links {
            margin-top: 15px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .social-link {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(74, 158, 255, 0.1);
            border: 1px solid rgba(74, 158, 255, 0.3);
            border-radius: 50%;
            color: #4a9eff;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }

        .social-link:hover {
            transform: translateY(-3px);
            background: rgba(74, 158, 255, 0.2);
            border-color: #00d4ff;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
                padding: 15px 20px;
            }

            .message-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .message-actions {
                width: 100%;
                flex-direction: column;
            }

            .main-content {
                padding: 20px;
            }

            .message-header-row {
                flex-direction: column;
            }

            .message-header h2 {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div>
            <h1>📬 All Messages - Student Portal</h1>
        </div>
        <div class="user-info">
            <a href="index.php" class="btn btn-info">🏠 Dashboard</a>
            <span>👨‍🎓 <?php echo htmlspecialchars($student['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <div class="message-header">
            <div>
                <h2>
                    📬 All Messages
                    <?php if ($unread_count > 0): ?>
                        <span class="unread-badge"><?php echo $unread_count; ?> Unread</span>
                    <?php else: ?>
                        <span class="unread-badge all-read">✅ All Read</span>
                    <?php endif; ?>
                </h2>
                <p>Total Messages: <strong><?php echo $total_notifications; ?></strong></p>
            </div>
            <div class="message-actions">
                <?php if ($unread_count > 0): ?>
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="mark_all_read" class="btn btn-warning" onclick="return confirm('Mark all messages as read?')">
                            ✓ Mark All Read
                        </button>
                    </form>
                <?php endif; ?>
                <a href="index.php" class="btn btn-secondary">← Back</a>
            </div>
        </div>

        <div class="message-container">
            <?php if ($messages && $messages->num_rows > 0): ?>
                <?php while ($message = $messages->fetch_assoc()): ?>
                    <div class="message-card <?php echo $message['is_read'] == 0 ? 'unread' : ''; ?>">
                        <div class="message-header-row">
                            <div>
                                <span class="message-from">
                                    👨‍🏫 <?php echo htmlspecialchars($message['teacher_name']); ?>
                                </span>
                                <?php if ($message['class_section']): ?>
                                    <span style="color: #666; font-size: 14px; margin-left: 10px;">
                                        (<?php echo htmlspecialchars($message['class_section']); ?>)
                                    </span>
                                <?php endif; ?>
                            </div>
                            <span class="message-time">
                                <?php 
                                $date = strtotime($message['created_at']);
                                $today_start = strtotime('today');
                                $yesterday_start = strtotime('yesterday');
                                
                                if ($date >= $today_start) {
                                    echo 'Today, ' . date('g:i A', $date);
                                } elseif ($date >= $yesterday_start) {
                                    echo 'Yesterday, ' . date('g:i A', $date);
                                } else {
                                    echo date('d M Y, g:i A', $date);
                                }
                                ?>
                            </span>
                        </div>

                        <div class="message-content">
                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                        </div>

                        <div class="message-footer">
                            <span class="message-date">
                                📅 <?php echo date('d M Y', strtotime($message['notification_date'])); ?>
                            </span>
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <?php if ($message['email_sent'] == 1): ?>
                                    <span class="email-badge">✉️ Email Sent</span>
                                <?php endif; ?>
                                <?php if ($message['is_read'] == 0): ?>
                                    <span class="new-badge">🆕 New</span>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="notification_id" value="<?php echo $message['id']; ?>">
                                        <button type="submit" name="mark_as_read" class="mark-read-btn">
                                            ✓ Mark as Read
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>

                <!-- PAGINATION -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="all_messages.php?page=1">« First</a>
                            <a href="all_messages.php?page=<?php echo $page - 1; ?>">‹ Previous</a>
                        <?php else: ?>
                            <span class="disabled">« First</span>
                            <span class="disabled">‹ Previous</span>
                        <?php endif; ?>

                        <?php
                        $start = max(1, $page - 2);
                        $end = min($total_pages, $page + 2);
                        
                        if ($start > 1) echo '<span>...</span>';
                        
                        for ($i = $start; $i <= $end; $i++) {
                            if ($i == $page) {
                                echo '<span class="active">' . $i . '</span>';
                            } else {
                                echo '<a href="all_messages.php?page=' . $i . '">' . $i . '</a>';
                            }
                        }
                        
                        if ($end < $total_pages) echo '<span>...</span>';
                        ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="all_messages.php?page=<?php echo $page + 1; ?>">Next ›</a>
                            <a href="all_messages.php?page=<?php echo $total_pages; ?>">Last »</a>
                        <?php else: ?>
                            <span class="disabled">Next ›</span>
                            <span class="disabled">Last »</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="no-messages">
                    <p>📭</p>
                    <h3>No Messages Yet</h3>
                    <p>You don't have any messages from teachers yet.</p>
                    <p style="margin-top: 20px;">
                        <a href="index.php" class="btn btn-primary">← Back to Dashboard</a>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-border"></div>
        <div class="footer-content">
            <div class="developer-section">
                <p>✨ Designed & Developed by</p>
                <a href="https://himanshufullstackdeveloper.github.io/techyugsoftware/" class="company-link">
                    🚀 Techyug Software Pvt. Ltd.
                </a>
                <div class="divider"></div>
                <p class="team-label">💼 Development Team</p>
                <div class="dev-badges">
                    <a href="https://himanshufullstackdeveloper.github.io/portfoilohimanshu/" class="dev-badge">
                        <span>👨‍💻</span>
                        <span>Himanshu Patil</span>
                    </a>
                    <a href="https://devpranaypanore.github.io/Pranaypanore-live-.html/" class="dev-badge">
                        <span>👨‍💻</span>
                        <span>Pranay Panore</span>
                    </a>
                </div>
                <div class="role-tags">
                    <span class="role-tag full-stack">Full Stack</span>
                    <span class="role-tag ui-ux">UI/UX</span>
                    <span class="role-tag database">Database</span>
                </div>
            </div>
            <div class="footer-bottom">
                <p class="footer-copyright">© 2025 NIT AMMS. All rights reserved.</p>
                <p class="footer-made-with">
                    Made with <span class="heart">❤️</span> by Techyug Software
                </p>
                <div class="social-links">
                    <a href="#" class="social-link">📧</a>
                    <a href="#" class="social-link">🌐</a>
                    <a href="#" class="social-link">💼</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>