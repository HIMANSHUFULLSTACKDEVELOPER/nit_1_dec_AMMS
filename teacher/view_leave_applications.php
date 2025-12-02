<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../db.php';
checkRole(['teacher']);

$teacher_id = $_SESSION['user_id'];

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $leave_id = intval($_POST['leave_id']);
    $new_status = sanitize($_POST['status']);
    $remarks = sanitize($_POST['remarks']);
    
    $update_query = "UPDATE leave_applications 
                     SET status = ?, teacher_remarks = ?, updated_at = NOW()
                     WHERE id = ? AND teacher_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssii", $new_status, $remarks, $leave_id, $teacher_id);
    
    if ($stmt->execute()) {
        // Get student email to send notification
        $email_query = "SELECT s.email, s.full_name, la.subject 
                       FROM leave_applications la
                       JOIN students s ON la.student_id = s.id
                       WHERE la.id = ?";
        $stmt = $conn->prepare($email_query);
        $stmt->bind_param("i", $leave_id);
        $stmt->execute();
        $email_data = $stmt->get_result()->fetch_assoc();
        
        // Send email notification (uncomment to enable)
        /*
        $to = $email_data['email'];
        $subject = "Leave Application " . ucfirst($new_status);
        $message = "Dear " . $email_data['full_name'] . ",\n\n";
        $message .= "Your leave application '" . $email_data['subject'] . "' has been " . $new_status . ".\n\n";
        if ($remarks) {
            $message .= "Teacher's Remarks: " . $remarks . "\n\n";
        }
        $message .= "Thank you.";
        mail($to, $subject, $message);
        */
        
        $success = "Leave application updated successfully!";
        header("Location: view_leave_applications.php?success=1");
        exit();
    }
}

// Filter parameters
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';
$date_filter = isset($_GET['date']) ? sanitize($_GET['date']) : '';

// Build query
$query = "SELECT la.*, s.full_name, s.roll_number, s.email, s.phone,
          c.section, c.year, c.semester
          FROM leave_applications la
          JOIN students s ON la.student_id = s.id
          JOIN classes c ON la.class_id = c.id
          WHERE la.teacher_id = ?";

$params = [$teacher_id];
$types = "i";

if ($status_filter !== 'all') {
    $query .= " AND la.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($date_filter) {
    $query .= " AND (la.start_date <= ? AND la.end_date >= ?)";
    $params[] = $date_filter;
    $params[] = $date_filter;
    $types .= "ss";
}

$query .= " ORDER BY la.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$applications = $stmt->get_result();

// Get statistics
$stats_query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                FROM leave_applications
                WHERE teacher_id = ?";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Applications - Teacher Portal</title>
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
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        .header h1 {
            font-size: 32px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-card h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .stat-value {
            font-size: 36px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .filters {
            background: rgba(255, 255, 255, 0.95);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .filters form {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
            font-size: 14px;
        }

        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }

        .applications-list {
            display: grid;
            gap: 20px;
        }

        .application-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            border-left: 5px solid #667eea;
        }

        .application-card.pending {
            border-left-color: #ffc107;
        }

        .application-card.approved {
            border-left-color: #28a745;
        }

        .application-card.rejected {
            border-left-color: #dc3545;
        }

        .application-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .student-info h3 {
            font-size: 20px;
            color: #333;
            margin-bottom: 5px;
        }

        .student-info p {
            color: #666;
            font-size: 14px;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-badge.pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-badge.approved {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .application-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .detail-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .detail-item {
            padding: 10px;
            background: white;
            border-radius: 8px;
        }

        .detail-item strong {
            color: #667eea;
            display: block;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .action-form {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .action-form select,
        .action-form textarea {
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }

        .action-form textarea {
            flex: 1;
            min-width: 250px;
            min-height: 80px;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
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
            background: #28a745;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }

        @media (max-width: 768px) {
            .application-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .detail-row {
                grid-template-columns: 1fr;
            }

            .action-form {
                flex-direction: column;
            }

            .filters form {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Student Leave Applications</h1>
            <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                ✅ Leave application status updated successfully!
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>📊 Total Applications</h3>
                <div class="stat-value"><?php echo $stats['total']; ?></div>
            </div>
            <div class="stat-card">
                <h3>⏳ Pending</h3>
                <div class="stat-value" style="color: #ffc107;"><?php echo $stats['pending']; ?></div>
            </div>
            <div class="stat-card">
                <h3>✅ Approved</h3>
                <div class="stat-value" style="color: #28a745;"><?php echo $stats['approved']; ?></div>
            </div>
            <div class="stat-card">
                <h3>❌ Rejected</h3>
                <div class="stat-value" style="color: #dc3545;"><?php echo $stats['rejected']; ?></div>
            </div>
        </div>

        <div class="filters">
            <form method="GET">
                <div class="filter-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Date</label>
                    <input type="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
                </div>
                <button type="submit" class="btn btn-primary">🔍 Filter</button>
                <a href="view_leave_applications.php" class="btn btn-secondary">🔄 Reset</a>
            </form>
        </div>

        <div class="applications-list">
            <?php if ($applications->num_rows > 0): ?>
                <?php while ($app = $applications->fetch_assoc()): ?>
                    <div class="application-card <?php echo $app['status']; ?>">
                        <div class="application-header">
                            <div class="student-info">
                                <h3>👨‍🎓 <?php echo htmlspecialchars($app['full_name']); ?></h3>
                                <p>Roll No: <?php echo htmlspecialchars($app['roll_number']); ?> | 
                                   Class: <?php echo htmlspecialchars($app['section']); ?> | 
                                   Year: <?php echo $app['year']; ?></p>
                            </div>
                            <span class="status-badge <?php echo $app['status']; ?>">
                                <?php echo strtoupper($app['status']); ?>
                            </span>
                        </div>

                        <div class="application-details">
                            <div class="detail-row">
                                <div class="detail-item">
                                    <strong>📋 Subject</strong>
                                    <?php echo htmlspecialchars($app['subject'] ?? $app['leave_type']); ?>
                                </div>
                                <div class="detail-item">
                                    <strong>🏷️ Leave Type</strong>
                                    <?php echo ucfirst($app['leave_type']); ?>
                                </div>
                                <div class="detail-item">
                                    <strong>📅 Duration</strong>
                                    <?php 
                                    $start = date('d M Y', strtotime($app['start_date']));
                                    $end = date('d M Y', strtotime($app['end_date']));
                                    $days = (strtotime($app['end_date']) - strtotime($app['start_date'])) / (60 * 60 * 24) + 1;
                                    echo "$start to $end ($days days)";
                                    ?>
                                </div>
                                <div class="detail-item">
                                    <strong>📆 Applied On</strong>
                                    <?php echo date('d M Y, h:i A', strtotime($app['created_at'])); ?>
                                </div>
                            </div>

                            <div style="margin-top: 15px; padding: 15px; background: white; border-radius: 8px;">
                                <strong style="color: #667eea;">💬 Reason:</strong>
                                <p style="margin-top: 8px; line-height: 1.6;">
                                    <?php echo nl2br(htmlspecialchars($app['reason'])); ?>
                                </p>
                            </div>

                            <?php if ($app['attachment']): ?>
                                <div style="margin-top: 15px;">
                                    <a href="../uploads/leave_applications/<?php echo htmlspecialchars($app['attachment']); ?>" 
                                       target="_blank" 
                                       class="btn btn-secondary">
                                        📎 View Attachment
                                    </a>
                                </div>
                            <?php endif; ?>

                            <?php if ($app['teacher_remarks']): ?>
                                <div style="margin-top: 15px; padding: 15px; background: white; border-radius: 8px; border-left: 3px solid #667eea;">
                                    <strong style="color: #667eea;">👨‍🏫 Your Remarks:</strong>
                                    <p style="margin-top: 8px;"><?php echo nl2br(htmlspecialchars($app['teacher_remarks'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($app['status'] === 'pending'): ?>
                            <form method="POST" class="action-form">
                                <input type="hidden" name="leave_id" value="<?php echo $app['id']; ?>">
                                
                                <select name="status" required>
                                    <option value="">Select Action</option>
                                    <option value="approved">✅ Approve</option>
                                    <option value="rejected">❌ Reject</option>
                                </select>

                                <textarea name="remarks" placeholder="Add your remarks (optional)"></textarea>

                                <button type="submit" name="update_status" class="btn btn-primary">
                                    💾 Update Status
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-data">
                    <p style="font-size: 48px; margin-bottom: 20px;">📭</p>
                    <p style="font-size: 18px;">No leave applications found</p>
                    <p style="margin-top: 10px; color: #999;">
                        <?php 
                        if ($status_filter !== 'all') {
                            echo "Try changing the filter to see more applications.";
                        } else {
                            echo "Students haven't submitted any leave applications yet.";
                        }
                        ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Confirm before rejecting
        document.querySelectorAll('form[method="POST"]').forEach(form => {
            form.addEventListener('submit', function(e) {
                const status = this.querySelector('select[name="status"]').value;
                if (status === 'rejected') {
                    if (!confirm('Are you sure you want to reject this leave application?')) {
                        e.preventDefault();
                    }
                }
            });
        });
    </script>
</body>
</html>