<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is HOD
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
    header("Location: ../login.php");
    exit();
}

$hod_id = $_SESSION['user_id'];

// Get all sent messages with recipient info
$query = "SELECT 
    m.*,
    u.name as recipient_name,
    u.email as recipient_email
FROM hod_messages m
JOIN users u ON m.recipient_id = u.id
WHERE m.hod_id = ?
ORDER BY m.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $hod_id);
$stmt->execute();
$messages = $stmt->get_result();

// Get statistics
$stats_query = "SELECT 
    COUNT(*) as total_sent,
    SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as total_read,
    COUNT(DISTINCT recipient_id) as unique_recipients
FROM hod_messages 
WHERE hod_id = ?";

$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("i", $hod_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sent Messages - HOD Dashboard</title>
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
            max-width: 1200px;
            margin: 0 auto;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
        }

        .stat-card i {
            font-size: 40px;
            color: #667eea;
            margin-bottom: 15px;
        }

        .stat-card h3 {
            font-size: 36px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-card p {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border: none;
        }

        .card-header h2 {
            margin: 0;
            font-size: 28px;
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
        }

        .message-item:hover {
            border-color: #667eea;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
            transform: translateY(-2px);
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }

        .message-subject {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .message-meta {
            font-size: 14px;
            color: #666;
        }

        .message-body {
            color: #555;
            line-height: 1.6;
            margin-bottom: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
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

        .badge-teacher {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .badge-student {
            background: #e1f5fe;
            color: #0277bd;
        }

        .badge-parent {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .read-status {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 13px;
        }

        .read-status.read {
            color: #4caf50;
        }

        .read-status.unread {
            color: #ff9800;
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

        <!-- Statistics -->
        <div class="stats-row">
            <div class="stat-card">
                <i class="fas fa-paper-plane"></i>
                <h3><?php echo $stats['total_sent']; ?></h3>
                <p>Total Sent</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-envelope-open"></i>
                <h3><?php echo $stats['total_read']; ?></h3>
                <p>Messages Read</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <h3><?php echo $stats['unique_recipients']; ?></h3>
                <p>Unique Recipients</p>
            </div>
        </div>

        <!-- Messages List -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-inbox"></i> Sent Messages</h2>
            </div>
            <div class="card-body">
                <?php if ($messages->num_rows > 0): ?>
                    <?php while ($msg = $messages->fetch_assoc()): ?>
                        <div class="message-item">
                            <div class="message-header">
                                <div>
                                    <div class="message-subject">
                                        <?php echo htmlspecialchars($msg['subject']); ?>
                                    </div>
                                    <div class="message-meta">
                                        <i class="fas fa-user"></i> 
                                        To: <?php echo htmlspecialchars($msg['recipient_name']); ?>
                                        <span class="badge badge-<?php echo $msg['recipient_type']; ?>">
                                            <?php echo ucfirst($msg['recipient_type']); ?>
                                        </span>
                                        <br>
                                        <i class="fas fa-clock"></i> 
                                        <?php echo date('M d, Y h:i A', strtotime($msg['created_at'])); ?>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="badge badge-<?php echo $msg['priority']; ?>">
                                        <?php 
                                        if ($msg['priority'] === 'urgent') echo '🚨 ';
                                        if ($msg['priority'] === 'important') echo '⭐ ';
                                        echo ucfirst($msg['priority']); 
                                        ?>
                                    </span>
                                    <br>
                                    <div class="read-status <?php echo $msg['is_read'] ? 'read' : 'unread'; ?>" style="margin-top: 10px;">
                                        <i class="fas fa-<?php echo $msg['is_read'] ? 'check-double' : 'check'; ?>"></i>
                                        <?php echo $msg['is_read'] ? 'Read' : 'Unread'; ?>
                                        <?php if ($msg['is_read'] && $msg['read_at']): ?>
                                            <br>
                                            <small><?php echo date('M d, h:i A', strtotime($msg['read_at'])); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="message-body">
                                <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                            </div>
                            <?php if ($msg['send_email']): ?>
                                <div class="text-muted small">
                                    <i class="fas fa-envelope"></i> Email notification sent to: <?php echo htmlspecialchars($msg['recipient_email']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>No Messages Sent Yet</h3>
                        <p>You haven't sent any messages yet. Click "Send Message" to compose your first message.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>