<?php
session_start();
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Only allow teacher, student, parent roles
if (!in_array($user_role, ['teacher', 'student', 'parent'])) {
    header("Location: ../login.php");
    exit();
}

// Handle mark as read
if (isset($_GET['mark_read']) && isset($_GET['msg_id'])) {
    $msg_id = intval($_GET['msg_id']);
    $update_query = "UPDATE hod_messages SET is_read = 1, read_at = NOW() WHERE id = ? AND recipient_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ii", $msg_id, $user_id);
    $stmt->execute();
    header("Location: messages_inbox.php");
    exit();
}

// Get messages for current user
$query = "SELECT 
    m.*,
    u.name as hod_name,
    u.email as hod_email,
    d.department_name
FROM hod_messages m
JOIN users u ON m.hod_id = u.id
LEFT JOIN departments d ON u.department_id = d.id
WHERE m.recipient_id = ? AND m.recipient_type = ?
ORDER BY m.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("is", $user_id, $user_role);
$stmt->execute();
$messages = $stmt->get_result();

// Get unread count
$unread_query = "SELECT COUNT(*) as unread_count FROM hod_messages WHERE recipient_id = ? AND recipient_type = ? AND is_read = 0";
$unread_stmt = $conn->prepare($unread_query);
$unread_stmt->bind_param("is", $user_id, $user_role);
$unread_stmt->execute();
$unread_count = $unread_stmt->get_result()->fetch_assoc()['unread_count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HOD Messages - <?php echo ucfirst($user_role); ?> Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            max-width: 1000px;
            margin: 0 auto;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h2 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }

        .unread-badge {
            background: #ff5252;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 16px;
            font-weight: 600;
        }

        .card-body {
            padding: 30px;
        }

        .message-item {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
            position: relative;
        }

        .message-item.unread {
            background: linear-gradient(135deg, #e3f2fd 0%, #e1f5fe 100%);
            border-color: #2196f3;
        }

        .message-item:hover {
            border-color: #667eea;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
            transform: translateY(-2px);
        }

        .unread-indicator {
            position: absolute;
            top: 20px;
            right: 20px;
            background: #ff5252;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }

        .message-subject {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .message-meta {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }

        .message-body {
            color: #555;
            line-height: 1.6;
            margin-bottom: 15px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 10px;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 5px;
        }

        .badge-normal {
            background: #e3f2fd;
            color: #1976d2;
        }

        .badge-important {
            background: #fff3e0;
            color: #f57c00;
        }

        .badge-urgent {
            background: #ffebee;
            color: #d32f2f;
        }

        .btn-mark-read {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-mark-read:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(17, 153, 142, 0.3);
            color: white;
        }

        .back-button {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid white;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
            transition: all 0.3s;
        }

        .back-button:hover {
            background: white;
            color: #667eea;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .empty-state h3 {
            color: #666;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-inbox"></i> HOD Messages</h2>
                <?php if ($unread_count > 0): ?>
                    <span class="unread-badge">
                        <?php echo $unread_count; ?> Unread
                    </span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if ($messages->num_rows > 0): ?>
                    <?php while ($msg = $messages->fetch_assoc()): ?>
                        <div class="message-item <?php echo !$msg['is_read'] ? 'unread' : ''; ?>">
                            <?php if (!$msg['is_read']): ?>
                                <span class="unread-indicator">NEW</span>
                            <?php endif; ?>
                            
                            <div class="message-header">
                                <div style="flex-grow: 1;">
                                    <div class="message-subject">
                                        <?php echo htmlspecialchars($msg['subject']); ?>
                                        <span class="badge badge-<?php echo $msg['priority']; ?>">
                                            <?php 
                                            if ($msg['priority'] === 'urgent') echo '🚨 ';
                                            if ($msg['priority'] === 'important') echo '⭐ ';
                                            echo ucfirst($msg['priority']); 
                                            ?>
                                        </span>
                                    </div>
                                    <div class="message-meta">
                                        <i class="fas fa-user"></i> 
                                        From: <strong><?php echo htmlspecialchars($msg['hod_name']); ?></strong>
                                        <?php if ($msg['department_name']): ?>
                                            (<?php echo htmlspecialchars($msg['department_name']); ?>)
                                        <?php endif; ?>
                                    </div>
                                    <div class="message-meta">
                                        <i class="fas fa-clock"></i> 
                                        <?php echo date('M d, Y h:i A', strtotime($msg['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="message-body">
                                <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <?php if (!$msg['is_read']): ?>
                                    <a href="messages_inbox.php?mark_read=1&msg_id=<?php echo $msg['id']; ?>" class="btn-mark-read">
                                        <i class="fas fa-check"></i> Mark as Read
                                    </a>
                                <?php else: ?>
                                    <div class="text-success">
                                        <i class="fas fa-check-double"></i> 
                                        Read on <?php echo date('M d, Y h:i A', strtotime($msg['read_at'])); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($msg['send_email']): ?>
                                    <div class="text-muted small">
                                        <i class="fas fa-envelope"></i> Also sent via email
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>No Messages Yet</h3>
                        <p>You don't have any messages from HOD yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>