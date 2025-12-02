<?php
require_once '../db.php';
checkRole(['admin']);

$user = getCurrentUser();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_notice'])) {
        $title = sanitize($_POST['title']);
        $message = sanitize($_POST['message']);
        $notice_type = sanitize($_POST['notice_type']);
        $priority = sanitize($_POST['priority']);
        $target_audience = sanitize($_POST['target_audience']);
        $start_date = sanitize($_POST['start_date']);
        $end_date = !empty($_POST['end_date']) ? sanitize($_POST['end_date']) : null;
        
        $stmt = $conn->prepare("INSERT INTO notices (title, message, notice_type, priority, target_audience, start_date, end_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssi", $title, $message, $notice_type, $priority, $target_audience, $start_date, $end_date, $user['id']);
        
        if ($stmt->execute()) {
            header("Location: manage_notices.php?success=added");
            exit;
        }
    } elseif (isset($_POST['delete_notice'])) {
        $notice_id = intval($_POST['notice_id']);
        $conn->query("DELETE FROM notices WHERE id = $notice_id");
        header("Location: manage_notices.php?success=deleted");
        exit;
    } elseif (isset($_POST['toggle_status'])) {
        $notice_id = intval($_POST['notice_id']);
        $new_status = intval($_POST['new_status']);
        $conn->query("UPDATE notices SET is_active = $new_status WHERE id = $notice_id");
        header("Location: manage_notices.php?success=updated");
        exit;
    }
}

// Get all notices
$notices = $conn->query("SELECT n.*, u.full_name as created_by_name FROM notices n JOIN users u ON n.created_by = u.id ORDER BY n.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Notices - NIT AMMS</title>
    <link rel="icon" href="../Nit_logo.png" type="image/svg+xml" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%); min-height: 100vh; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .navbar { background: rgba(26, 31, 58, 0.95); backdrop-filter: blur(20px); padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3); position: sticky; top: 0; z-index: 1000; }
        .navbar h1 { color: white; font-size: 24px; }
        .main-content { padding: 40px; max-width: 1400px; margin: 0 auto; }
        .card { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); border-radius: 25px; padding: 40px; box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2); margin-bottom: 30px; }
        .card h2 { font-size: 28px; background: linear-gradient(135deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 25px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #333; font-weight: 600; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 14px; transition: all 0.3s; }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { border-color: #667eea; outline: none; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .form-group textarea { min-height: 120px; resize: vertical; }
        .btn { padding: 12px 28px; border-radius: 12px; border: none; cursor: pointer; font-weight: 600; transition: all 0.3s; text-decoration: none; display: inline-block; }
        .btn-primary { background: linear-gradient(135deg, #667eea, #764ba2); color: white; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6); }
        .btn-danger { background: linear-gradient(135deg, #ff6b6b, #ee5a5a); color: white; box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4); }
        .btn-success { background: linear-gradient(135deg, #28a745, #20c997); color: white; }
        .btn-warning { background: linear-gradient(135deg, #ffc107, #ff9800); color: white; }
        .btn-sm { padding: 8px 16px; font-size: 13px; }
        .alert { padding: 15px 20px; border-radius: 12px; margin-bottom: 20px; animation: slideDown 0.5s ease-out; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        .alert-success { background: rgba(212, 237, 218, 0.95); border: 2px solid #28a745; color: #155724; }
        .notice-card { background: white; border-radius: 15px; padding: 25px; margin-bottom: 20px; box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1); border-left: 5px solid; transition: all 0.3s; }
        .notice-card:hover { transform: translateX(10px); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15); }
        .notice-card.info { border-left-color: #17a2b8; }
        .notice-card.warning { border-left-color: #ffc107; }
        .notice-card.success { border-left-color: #28a745; }
        .notice-card.danger { border-left-color: #dc3545; }
        .notice-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .notice-title { font-size: 20px; font-weight: 700; color: #2c3e50; }
        .notice-badges { display: flex; gap: 10px; flex-wrap: wrap; }
        .badge { padding: 6px 12px; border-radius: 15px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .notice-message { color: #555; line-height: 1.8; margin-bottom: 15px; }
        .notice-meta { display: flex; gap: 20px; font-size: 12px; color: #999; margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee; }
        .notice-actions { display: flex; gap: 10px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 768px) {
            .grid-2 { grid-template-columns: 1fr; }
            .main-content { padding: 20px; }
            .card { padding: 25px; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>📢 Manage Notices</h1>
        <a href="index.php" class="btn btn-primary">← Back to Dashboard</a>
    </nav>

    <div class="main-content">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                ✅ Notice <?php echo htmlspecialchars($_GET['success']); ?> successfully!
            </div>
        <?php endif; ?>

        <!-- Add New Notice Form -->
        <div class="card">
            <h2>📝 Create New Notice</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Notice Title *</label>
                    <input type="text" name="title" required placeholder="Enter notice title...">
                </div>

                <div class="form-group">
                    <label>Notice Message *</label>
                    <textarea name="message" required placeholder="Enter notice message..."></textarea>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Notice Type *</label>
                        <select name="notice_type" required>
                            <option value="info">ℹ️ Information</option>
                            <option value="success">✅ Success</option>
                            <option value="warning">⚠️ Warning</option>
                            <option value="danger">🚨 Urgent</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Priority *</label>
                        <select name="priority" required>
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Target Audience *</label>
                        <select name="target_audience" required>
                            <option value="all" selected>🌐 All Users</option>
                            <option value="students">👨‍🎓 Students Only</option>
                            <option value="teachers">👨‍🏫 Teachers Only</option>
                            <option value="hods">👔 HODs Only</option>
                            <option value="parents">👨‍👩‍👦 Parents Only</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Start Date *</label>
                        <input type="date" name="start_date" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>End Date (Optional)</label>
                    <input type="date" name="end_date" placeholder="Leave blank for permanent">
                </div>

                <button type="submit" name="add_notice" class="btn btn-primary">📢 Publish Notice</button>
            </form>
        </div>

        <!-- Existing Notices -->
        <div class="card">
            <h2>📋 All Notices (<?php echo $notices->num_rows; ?>)</h2>
            
            <?php if ($notices->num_rows > 0): ?>
                <?php while ($notice = $notices->fetch_assoc()): ?>
                    <div class="notice-card <?php echo $notice['notice_type']; ?>">
                        <div class="notice-header">
                            <div class="notice-title"><?php echo htmlspecialchars($notice['title']); ?></div>
                            <div class="notice-badges">
                                <span class="badge badge-<?php echo $notice['notice_type']; ?>">
                                    <?php echo strtoupper($notice['notice_type']); ?>
                                </span>
                                <span class="badge badge-<?php echo $notice['is_active'] ? 'success' : 'danger'; ?>">
                                    <?php echo $notice['is_active'] ? '🟢 Active' : '🔴 Inactive'; ?>
                                </span>
                            </div>
                        </div>

                        <div class="notice-message"><?php echo nl2br(htmlspecialchars($notice['message'])); ?></div>

                        <div class="notice-meta">
                            <span>👥 Audience: <strong><?php echo ucfirst($notice['target_audience']); ?></strong></span>
                            <span>🎯 Priority: <strong><?php echo ucfirst($notice['priority']); ?></strong></span>
                            <span>📅 <?php echo date('M d, Y', strtotime($notice['start_date'])); ?></span>
                            <?php if ($notice['end_date']): ?>
                                <span>⏰ Until: <?php echo date('M d, Y', strtotime($notice['end_date'])); ?></span>
                            <?php endif; ?>
                            <span>👤 By: <?php echo htmlspecialchars($notice['created_by_name']); ?></span>
                        </div>

                        <div class="notice-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="notice_id" value="<?php echo $notice['id']; ?>">
                                <input type="hidden" name="new_status" value="<?php echo $notice['is_active'] ? 0 : 1; ?>">
                                <button type="submit" name="toggle_status" class="btn <?php echo $notice['is_active'] ? 'btn-warning' : 'btn-success'; ?> btn-sm">
                                    <?php echo $notice['is_active'] ? '⏸️ Deactivate' : '▶️ Activate'; ?>
                                </button>
                            </form>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this notice?');">
                                <input type="hidden" name="notice_id" value="<?php echo $notice['id']; ?>">
                                <button type="submit" name="delete_notice" class="btn btn-danger btn-sm">🗑️ Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 60px; color: #999;">
                    <div style="font-size: 64px; margin-bottom: 20px;">📭</div>
                    <p style="font-size: 18px;">No notices created yet</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>