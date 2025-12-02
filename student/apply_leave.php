<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../db.php';
checkRole(['student']);

$student_id = $_SESSION['user_id'];

// Get student info with class details
$student_query = "SELECT s.*, c.id as class_id, c.section, c.year, c.semester, 
                  d.dept_name, c.teacher_id
                  FROM students s
                  LEFT JOIN classes c ON s.class_id = c.id
                  LEFT JOIN departments d ON s.department_id = d.id
                  WHERE s.id = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// Get teacher info
$teacher_query = "SELECT id, full_name, email FROM users WHERE id = ? AND role = 'teacher'";
$stmt = $conn->prepare($teacher_query);
$stmt->bind_param("i", $student['teacher_id']);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();

// Get student's leave application history
$history_query = "SELECT la.*, u.full_name as teacher_name 
                  FROM leave_applications la
                  LEFT JOIN users u ON la.teacher_id = u.id
                  WHERE la.student_id = ?
                  ORDER BY la.created_at DESC
                  LIMIT 10";
$stmt = $conn->prepare($history_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$history = $stmt->get_result();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_leave'])) {
    $leave_type = sanitize($_POST['leave_type']);
    $start_date = sanitize($_POST['start_date']);
    $end_date = sanitize($_POST['end_date']);
    $reason = sanitize($_POST['reason']);
    $subject = sanitize($_POST['subject']);
    
    // File upload handling
    $attachment = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === 0) {
        $upload_dir = '../uploads/leave_applications/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
        $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
        
        if (in_array(strtolower($file_extension), $allowed_extensions)) {
            $file_name = 'leave_' . $student_id . '_' . time() . '.' . $file_extension;
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $file_path)) {
                $attachment = $file_name;
            }
        }
    }
    
    // Validate dates
    if (strtotime($end_date) < strtotime($start_date)) {
        $error = "End date cannot be before start date!";
    } else {
        // Insert leave application
        $insert_query = "INSERT INTO leave_applications 
                        (student_id, teacher_id, class_id, leave_type, start_date, end_date, 
                         reason, subject, attachment, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("iiissssss", 
            $student_id, 
            $student['teacher_id'], 
            $student['class_id'],
            $leave_type,
            $start_date,
            $end_date,
            $reason,
            $subject,
            $attachment
        );
        
        if ($stmt->execute()) {
            // Send email notification to teacher (optional)
            $teacher_email = $teacher['email'];
            $subject_email = "New Leave Application from " . $student['full_name'];
            $message_email = "Student: " . $student['full_name'] . "\n";
            $message_email .= "Roll No: " . $student['roll_number'] . "\n";
            $message_email .= "Leave Type: " . $leave_type . "\n";
            $message_email .= "Duration: " . $start_date . " to " . $end_date . "\n";
            $message_email .= "Reason: " . $reason;
            
            // Uncomment to enable email
            // mail($teacher_email, $subject_email, $message_email);
            
            $success = "Leave application submitted successfully!";
            header("Location: apply_leave.php?success=1");
            exit();
        } else {
            $error = "Failed to submit leave application. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Leave - NIT College</title>
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
            margin-bottom: 10px;
        }

        .form-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .btn {
            padding: 14px 28px;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            font-size: 16px;
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

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .history-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        .history-container h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
        }

        .leave-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
        }

        .leave-card.pending {
            border-left-color: #ffc107;
        }

        .leave-card.approved {
            border-left-color: #28a745;
        }

        .leave-card.rejected {
            border-left-color: #dc3545;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
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

        .file-upload {
            position: relative;
        }

        .file-upload input[type="file"] {
            display: none;
        }

        .file-upload-label {
            display: inline-block;
            padding: 12px 20px;
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .file-upload-label:hover {
            border-color: #667eea;
            background: #f0f2ff;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .container {
                padding: 10px;
            }

            .form-container,
            .history-container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📝 Apply for Leave</h1>
            <p><strong>Student:</strong> <?php echo htmlspecialchars($student['full_name']); ?></p>
            <p><strong>Roll No:</strong> <?php echo htmlspecialchars($student['roll_number']); ?></p>
            <p><strong>Class:</strong> <?php echo htmlspecialchars($student['section']); ?></p>
            <p><strong>Teacher:</strong> <?php echo htmlspecialchars($teacher['full_name']); ?></p>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                ✅ Leave application submitted successfully! Your teacher will review it soon.
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <h2 style="margin-bottom: 25px; color: #333;">Submit Leave Application</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="leave_type">Leave Type *</label>
                        <select name="leave_type" id="leave_type" required>
                            <option value="">Select Leave Type</option>
                            <option value="sick">🤒 Sick Leave</option>
                            <option value="emergency">🚨 Emergency</option>
                            <option value="personal">👤 Personal</option>
                            <option value="family">👨‍👩‍👧 Family Function</option>
                            <option value="other">📋 Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="subject">Subject/Title *</label>
                        <input type="text" name="subject" id="subject" placeholder="e.g., Medical Leave" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="start_date">Start Date *</label>
                        <input type="date" name="start_date" id="start_date" required min="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="end_date">End Date *</label>
                        <input type="date" name="end_date" id="end_date" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="reason">Reason for Leave *</label>
                    <textarea name="reason" id="reason" placeholder="Please explain the reason for your leave..." required></textarea>
                </div>

                <div class="form-group">
                    <label>Attachment (Medical Certificate, etc.)</label>
                    <div class="file-upload">
                        <input type="file" name="attachment" id="attachment" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        <label for="attachment" class="file-upload-label">
                            📎 Choose File (PDF, Image, Doc)
                        </label>
                        <span id="file-name" style="margin-left: 10px; color: #666;"></span>
                    </div>
                </div>

                <div style="display: flex; gap: 15px; margin-top: 30px;">
                    <button type="submit" name="submit_leave" class="btn btn-primary">
                        ✉️ Submit Application
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        ← Back to Dashboard
                    </a>
                </div>
            </form>
        </div>

        <div class="history-container">
            <h2>📋 Your Leave Applications History</h2>
            
            <?php if ($history->num_rows > 0): ?>
                <?php while ($leave = $history->fetch_assoc()): ?>
                    <div class="leave-card <?php echo $leave['status']; ?>">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <h3 style="color: #333; font-size: 16px;">
                                <?php echo htmlspecialchars($leave['subject'] ?? $leave['leave_type']); ?>
                            </h3>
                            <span class="status-badge <?php echo $leave['status']; ?>">
                                <?php echo strtoupper($leave['status']); ?>
                            </span>
                        </div>
                        
                        <p><strong>Type:</strong> <?php echo ucfirst($leave['leave_type']); ?></p>
                        <p><strong>Duration:</strong> <?php echo date('d M Y', strtotime($leave['start_date'])); ?> to <?php echo date('d M Y', strtotime($leave['end_date'])); ?></p>
                        <p><strong>Reason:</strong> <?php echo htmlspecialchars($leave['reason']); ?></p>
                        <p><strong>Applied on:</strong> <?php echo date('d M Y, h:i A', strtotime($leave['created_at'])); ?></p>
                        
                        <?php if ($leave['teacher_remarks']): ?>
                            <div style="margin-top: 10px; padding: 10px; background: white; border-radius: 8px;">
                                <strong>Teacher's Remarks:</strong>
                                <p style="margin-top: 5px;"><?php echo htmlspecialchars($leave['teacher_remarks']); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($leave['attachment']): ?>
                            <a href="../uploads/leave_applications/<?php echo htmlspecialchars($leave['attachment']); ?>" 
                               target="_blank" 
                               style="display: inline-block; margin-top: 10px; color: #667eea;">
                                📎 View Attachment
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align: center; color: #666; padding: 40px;">
                    No leave applications found. Submit your first application above.
                </p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.getElementById('attachment').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            document.getElementById('file-name').textContent = fileName || '';
        });

        // Validate end date is not before start date
        document.getElementById('end_date').addEventListener('change', function() {
            const startDate = document.getElementById('start_date').value;
            const endDate = this.value;
            
            if (startDate && endDate && endDate < startDate) {
                alert('End date cannot be before start date!');
                this.value = startDate;
            }
        });
    </script>
</body>
</html>