<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is HOD
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
    header("Location: ../login.php");
    exit();
}

$hod_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipient_type = $_POST['recipient_type'] ?? '';
    $recipient_selection = $_POST['recipient_selection'] ?? 'all';
    $specific_recipients = $_POST['specific_recipients'] ?? [];
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $priority = $_POST['priority'] ?? 'normal';
    $send_email = isset($_POST['send_email']) ? 1 : 0;
    
    // Validation
    if (empty($recipient_type) || empty($subject) || empty($message)) {
        $error_message = "Please fill in all required fields.";
    } else {
        // Get recipients based on selection
        $recipients = [];
        
        if ($recipient_selection === 'all') {
            // Send to all of the selected type
            if ($recipient_type === 'teacher') {
                $query = "SELECT id FROM users WHERE role = 'teacher' AND status = 'active'";
            } elseif ($recipient_type === 'student') {
                $query = "SELECT id FROM users WHERE role = 'student' AND status = 'active'";
            } elseif ($recipient_type === 'parent') {
                $query = "SELECT id FROM users WHERE role = 'parent' AND status = 'active'";
            }
            
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $recipients[] = $row['id'];
            }
        } else {
            // Send to specific recipients
            $recipients = $specific_recipients;
        }
        
        // Insert messages
        $insert_query = "INSERT INTO hod_messages (hod_id, recipient_id, recipient_type, subject, message, send_email, priority, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($insert_query);
        
        $success_count = 0;
        foreach ($recipients as $recipient_id) {
            $stmt->bind_param("iisssss", $hod_id, $recipient_id, $recipient_type, $subject, $message, $send_email, $priority);
            if ($stmt->execute()) {
                $success_count++;
            }
        }
        
        $stmt->close();
        
        if ($success_count > 0) {
            $success_message = "Message sent successfully to {$success_count} recipient(s)!";
        } else {
            $error_message = "Failed to send message. Please try again.";
        }
    }
}

// Get teachers for dropdown
$teachers_query = "SELECT id, name FROM users WHERE role = 'teacher' AND status = 'active' ORDER BY name";
$teachers_result = $conn->query($teachers_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Message - HOD Dashboard</title>
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
            max-width: 900px;
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
        }

        .card-header h2 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }

        .card-body {
            padding: 30px;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            border: none;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 10px;
        }

        .alert {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }

        .alert-danger {
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
            color: white;
        }

        .recipient-options {
            display: none;
            margin-top: 15px;
        }

        .recipient-options.show {
            display: block;
        }

        .checkbox-group {
            max-height: 300px;
            overflow-y: auto;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
        }

        .form-check {
            padding: 8px;
            border-radius: 5px;
            transition: background 0.2s;
        }

        .form-check:hover {
            background: #f8f9fa;
        }

        .priority-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }

        .priority-normal {
            background: #e3f2fd;
            color: #1976d2;
        }

        .priority-important {
            background: #fff3e0;
            color: #f57c00;
        }

        .priority-urgent {
            background: #ffebee;
            color: #d32f2f;
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
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-paper-plane"></i> Send Message</h2>
            </div>
            <div class="card-body">
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <!-- Recipient Type -->
                    <div class="mb-4">
                        <label class="form-label">Send To: <span class="text-danger">*</span></label>
                        <select name="recipient_type" id="recipient_type" class="form-select" required>
                            <option value="">Select Recipient Type</option>
                            <option value="teacher">Teachers</option>
                            <option value="student">Students</option>
                            <option value="parent">Parents</option>
                        </select>
                    </div>

                    <!-- Recipient Selection -->
                    <div class="mb-4">
                        <label class="form-label">Recipient Selection:</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="recipient_selection" id="all_recipients" value="all" checked>
                            <label class="form-check-label" for="all_recipients">
                                Send to All
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="recipient_selection" id="specific_recipients" value="specific">
                            <label class="form-check-label" for="specific_recipients">
                                Select Specific Recipients (Teachers only)
                            </label>
                        </div>
                    </div>

                    <!-- Specific Teachers Selection -->
                    <div id="teacher_selection" class="recipient-options">
                        <label class="form-label">Select Teachers:</label>
                        <div class="checkbox-group">
                            <?php while ($teacher = $teachers_result->fetch_assoc()): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="specific_recipients[]" value="<?php echo $teacher['id']; ?>" id="teacher_<?php echo $teacher['id']; ?>">
                                    <label class="form-check-label" for="teacher_<?php echo $teacher['id']; ?>">
                                        <?php echo htmlspecialchars($teacher['name']); ?>
                                    </label>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <!-- Subject -->
                    <div class="mb-4">
                        <label class="form-label">Subject: <span class="text-danger">*</span></label>
                        <input type="text" name="subject" class="form-control" placeholder="Enter message subject" required>
                    </div>

                    <!-- Message -->
                    <div class="mb-4">
                        <label class="form-label">Message: <span class="text-danger">*</span></label>
                        <textarea name="message" class="form-control" placeholder="Enter your message here..." required></textarea>
                    </div>

                    <!-- Priority -->
                    <div class="mb-4">
                        <label class="form-label">Priority:</label>
                        <select name="priority" class="form-select">
                            <option value="normal">Normal <span class="priority-badge priority-normal">Normal</span></option>
                            <option value="important">Important <span class="priority-badge priority-important">⭐ Important</span></option>
                            <option value="urgent">Urgent <span class="priority-badge priority-urgent">🚨 Urgent</span></option>
                        </select>
                    </div>

                    <!-- Email Notification -->
                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="send_email" id="send_email">
                            <label class="form-check-label" for="send_email">
                                Also send email notification to recipients
                            </label>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="d-flex gap-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Send Message
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Show/hide teacher selection based on recipient type and selection
        document.getElementById('recipient_type').addEventListener('change', function() {
            const teacherSelection = document.getElementById('teacher_selection');
            const specificRadio = document.getElementById('specific_recipients');
            
            if (this.value === 'teacher' && specificRadio.checked) {
                teacherSelection.classList.add('show');
            } else {
                teacherSelection.classList.remove('show');
            }
            
            // Disable specific selection for students and parents
            if (this.value === 'student' || this.value === 'parent') {
                document.getElementById('all_recipients').checked = true;
                specificRadio.disabled = true;
            } else {
                specificRadio.disabled = false;
            }
        });

        document.querySelectorAll('input[name="recipient_selection"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const teacherSelection = document.getElementById('teacher_selection');
                const recipientType = document.getElementById('recipient_type').value;
                
                if (this.value === 'specific' && recipientType === 'teacher') {
                    teacherSelection.classList.add('show');
                } else {
                    teacherSelection.classList.remove('show');
                }
            });
        });
    </script>
</body>
</html>