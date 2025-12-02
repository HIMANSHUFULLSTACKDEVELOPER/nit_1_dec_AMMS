<?php
// test_email.php - Place this in your teacher/ directory
session_start();

// Include your email function
require_once 'send_email.php'; // Adjust path if needed

// Test email parameters
$test_email = 'student.test@example.com'; // Change to a real email for testing
$test_student_name = 'Test Student';
$test_subject = 'Test Email - Attendance Notification';
$test_message = "Dear Student,\n\nThis is a test message to verify email functionality.\n\nBest regards,\nYour Teacher";
$test_teacher_name = 'Prof. Test Teacher';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Function Test</title>
      <link rel="icon" href="../Nit_logo.png" type="image/svg+xml" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
            font-size: 28px;
        }
        .test-form {
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
        }
        input, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        input:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        textarea {
            resize: vertical;
            min-height: 120px;
            font-family: inherit;
        }
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 14px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            font-weight: 500;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .info-box h3 {
            color: #1976D2;
            margin-bottom: 8px;
            font-size: 16px;
        }
        .info-box p {
            color: #555;
            font-size: 14px;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Email Function Test</h1>
        
        <div class="info-box">
            <h3>📝 Testing Instructions</h3>
            <p>Fill in the form below and click "Send Test Email" to verify your email configuration is working correctly.</p>
        </div>

        <form method="POST" class="test-form">
            <div class="form-group">
                <label for="to_email">📨 Recipient Email Address:</label>
                <input type="email" id="to_email" name="to_email" value="<?php echo $test_email; ?>" required>
            </div>

            <div class="form-group">
                <label for="student_name">👤 Student Name:</label>
                <input type="text" id="student_name" name="student_name" value="<?php echo $test_student_name; ?>" required>
            </div>

            <div class="form-group">
                <label for="subject">📚 Email Subject:</label>
                <input type="text" id="subject" name="subject" value="<?php echo $test_subject; ?>" required>
            </div>

            <div class="form-group">
                <label for="message">✉️ Message:</label>
                <textarea id="message" name="message" required><?php echo $test_message; ?></textarea>
            </div>

            <div class="form-group">
                <label for="teacher_name">👨‍🏫 Teacher Name:</label>
                <input type="text" id="teacher_name" name="teacher_name" value="<?php echo $test_teacher_name; ?>" required>
            </div>

            <button type="submit" name="send_test" class="btn">🚀 Send Test Email</button>
        </form>

        <?php
        if (isset($_POST['send_test'])) {
            $to_email = $_POST['to_email'];
            $student_name = $_POST['student_name'];
            $subject = $_POST['subject'];
            $message = $_POST['message'];
            $teacher_name = $_POST['teacher_name'];
            
            echo '<div class="result">';
            echo '<h3>⏳ Sending email...</h3>';
            echo '<p>To: ' . htmlspecialchars($to_email) . '</p>';
            
            $result = sendStudentEmail($to_email, $student_name, $subject, $message, $teacher_name);
            
            echo '</div>';
            echo '<div class="result ' . ($result['success'] ? 'success' : 'error') . '">';
            echo '<h3>' . ($result['success'] ? '✅ Success!' : '❌ Error!') . '</h3>';
            echo '<p>' . htmlspecialchars($result['message']) . '</p>';
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>