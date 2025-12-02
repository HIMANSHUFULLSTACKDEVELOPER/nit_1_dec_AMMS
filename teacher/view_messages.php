<?php
require_once '../db.php';
checkRole(['teacher']);

$user = getCurrentUser();

// Mark message as read if ID provided
if (isset($_GET['mark_read']) && isset($_GET['id'])) {
    $message_id = intval($_GET['id']);
    $update_query = "UPDATE hod_messages SET is_read = 1, read_at = NOW() WHERE id = ? AND recipient_id = ? AND recipient_type = 'teacher'";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ii", $message_id, $user['id']);
    $stmt->execute();
    header("Location: view_messages.php");
    exit;
}

// Get all messages for this teacher
$messages_query = "SELECT hm.*, u.full_name as sender_name, u.email as sender_email,
                   d.dept_name
                   FROM hod_messages hm
                   JOIN users u ON hm.hod_id = u.id
                   JOIN departments d ON u.department_id = d.id
                   WHERE hm.recipient_id = ? AND hm.recipient_type = 'teacher'
                   ORDER BY hm.created_at DESC";
$stmt = $conn->prepare($messages_query);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$messages = $stmt->get_result();

// Get unread count
$unread_query = "SELECT COUNT(*) as unread FROM hod_messages WHERE recipient_id = ? AND recipient_type = 'teacher' AND is_read = 0";
$stmt = $conn->prepare($unread_query);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$unread_count = $stmt->get_result()->fetch_assoc()['unread'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Messages - Teacher Portal</title>
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
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        h1 {
            font-size: 32px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .unread-badge {
            background: linear-gradient(135deg, #ff6b6b, #ee5a5a);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }

        .message-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            border-left: 5px solid #667eea;
        }

        .message-card.unread {
            background: rgba(255, 243, 205, 0.95);
            border-left-color: #ffc107;
        }

        .message-card.urgent {
            border-left-color: #dc3545;
        }

        .message-card.important {
            border-left-color: #ff9800;
        }

        .message-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }

        .message-subject {
            font-size: 20px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .message-meta {
            font-size: 13px;
            color: #666;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .message-body {
            color: #555;
            line-height: 1.8;
            margin: 15px 0;
            padding: 15px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 10px;
            white-space: pre-wrap;
        }

        .priority-badge {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .priority-urgent {
            background: #dc3545;
            color: white;
        }

        .priority-important {
            background: #ff9800;
            color: white;
        }

        .priority-normal {
            background: #28a745;
            color: white;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 10px;
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

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>📬 My Messages</h1>
                <p style="color: #666; margin-top: 5px;">Messages from HOD</p>
            </div>
            <div style="display: flex; gap: 15px; align-items: center;">
                <?php if ($unread_count > 0): ?>
                    <div class="unread-badge">
                        🔔 <?php echo $unread_count; ?> Unread
                    </div>
                <?php endif; ?>
                <a href="index.php" class="btn btn-primary">🔙 Back to Dashboard</a>
            </div>
        </div>

        <?php if ($messages && $messages->num_rows > 0): ?>
            <?php while ($message = $messages->fetch_assoc()): ?>
                <div class="message-card <?php echo $message['is_read'] ? '' : 'unread'; ?> <?php echo $message['priority']; ?>">
                    <div class="message-header">
                        <div style="flex: 1;">
                            <div class="message-subject">
                                <?php if (!$message['is_read']): ?>
                                    <span style="color: #ffc107;">🆕</span>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($message['subject']); ?>
                            </div>
                            <div class="message-meta">
                                <span>📤 From: <strong><?php echo htmlspecialchars($message['sender_name']); ?></strong></span>
                                <span>🏢 <?php echo htmlspecialchars($message['dept_name']); ?></span>
                                <span>📅 <?php echo date('d M Y, g:i A', strtotime($message['created_at'])); ?></span>
                                <span class="priority-badge priority-<?php echo $message['priority']; ?>">
                                    <?php echo strtoupper($message['priority']); ?>
                                </span>
                            </div>
                        </div>
                        <?php if (!$message['is_read']): ?>
                            <a href="?mark_read=1&id=<?php echo $message['id']; ?>" class="btn btn-success">
                                ✅ Mark as Read
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="message-body">
                        <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                    </div>
                    
                    <?php if ($message['is_read']): ?>
                        <div style="font-size: 12px; color: #999; margin-top: 10px;">
                            ✔️ Read on <?php echo date('d M Y, g:i A', strtotime($message['read_at'])); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <h2 style="color: #666; margin-bottom: 10px;">No Messages</h2>
                <p style="color: #999;">You don't have any messages from HOD yet</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>