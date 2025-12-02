<?php
/**
 * Email Configuration File
 * NIT College Attendance System
 */

// Load PHPMailer
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Email Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'patilhimanshu46875@gmail.com');
define('SMTP_PASSWORD', 'deypfietdxggmrsa');
define('SENDER_EMAIL', 'patilhimanshu46875@gmail.com');
define('SENDER_NAME', 'NIT Attendance System');

/**
 * Send email to student
 */
function sendStudentEmail($to_email, $student_name, $subject, $message, $teacher_name) {
    $mail = new PHPMailer(true);
    
    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        
        // Recipients
        $mail->setFrom(SENDER_EMAIL, SENDER_NAME);
        $mail->addAddress($to_email, $student_name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = generateEmailTemplate($student_name, $message, $teacher_name, $subject);
        $mail->AltBody = strip_tags(str_replace('<br>', "\n", $message));
        
        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully!'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Email failed: ' . $mail->ErrorInfo];
    }
}

/**
 * Generate HTML email template
 */
function generateEmailTemplate($student_name, $message, $teacher_name, $subject) {
    $current_date = date('F j, Y');
    $current_time = date('g:i A');
    
    $html = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    </head>
    <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f5f5;'>
        <table width='100%' cellpadding='0' cellspacing='0' style='background-color: #f5f5f5; padding: 20px;'>
            <tr>
                <td align='center'>
                    <table width='600' cellpadding='0' cellspacing='0' style='background-color: #ffffff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                        <tr>
                            <td style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 10px 10px 0 0; text-align: center;'>
                                <h1 style='color: #ffffff; margin: 0; font-size: 28px;'>📧 NIT Attendance System</h1>
                            </td>
                        </tr>
                        
                        <tr>
                            <td style='padding: 40px 30px;'>
                                <h2 style='color: #333; margin: 0 0 20px 0; font-size: 22px; border-bottom: 3px solid #667eea; padding-bottom: 10px;'>
                                    Attendance Notification
                                </h2>
                                
                                <p style='color: #555; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;'>
                                    Dear <strong style='color: #333;'>" . htmlspecialchars($student_name) . "</strong>,
                                </p>
                                
                                <div style='background-color: #f9f9f9; padding: 20px; border-left: 4px solid #667eea; margin: 20px 0; border-radius: 4px;'>
                                    <p style='color: #333; font-size: 15px; line-height: 1.8; margin: 0; white-space: pre-wrap;'>" . nl2br(htmlspecialchars($message)) . "</p>
                                </div>
                                
                                <table width='100%' cellpadding='10' style='margin: 20px 0; border: 1px solid #e0e0e0; border-radius: 4px;'>
                                    <tr>
                                        <td style='background-color: #fafafa; padding: 15px;'>
                                            <p style='margin: 5px 0; color: #555; font-size: 14px;'>
                                                <strong style='color: #333;'>👨‍🏫 From:</strong> " . htmlspecialchars($teacher_name) . "
                                            </p>
                                            <p style='margin: 5px 0; color: #555; font-size: 14px;'>
                                                <strong style='color: #333;'>📚 Subject:</strong> " . htmlspecialchars($subject) . "
                                            </p>
                                            <p style='margin: 5px 0; color: #555; font-size: 14px;'>
                                                <strong style='color: #333;'>📅 Date:</strong> " . $current_date . "
                                            </p>
                                            <p style='margin: 5px 0; color: #555; font-size: 14px;'>
                                                <strong style='color: #333;'>🕐 Time:</strong> " . $current_time . "
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                                
                                <p style='color: #555; font-size: 15px; line-height: 1.6; margin: 20px 0 0 0;'>
                                    Best regards,<br>
                                    <strong style='color: #333;'>" . htmlspecialchars($teacher_name) . "</strong><br>
                                    NIT College
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <td style='background-color: #f8f8f8; padding: 20px 30px; border-radius: 0 0 10px 10px; text-align: center; border-top: 1px solid #e0e0e0;'>
                                <p style='color: #999; font-size: 12px; margin: 0; line-height: 1.5;'>
                                    This is an automated message from NIT College Attendance System.<br>
                                    Please do not reply to this email.
                                </p>
                                <p style='color: #999; font-size: 11px; margin: 10px 0 0 0;'>
                                    © " . date('Y') . " NIT College. All rights reserved.
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>";
    
    return $html;
}

/**
 * Validate email address
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Send bulk email to multiple students
 */
function sendBulkEmail($recipients, $subject, $message, $teacher_name) {
    $results = [
        'success' => 0,
        'failed' => 0,
        'details' => []
    ];
    
    foreach ($recipients as $recipient) {
        $result = sendStudentEmail(
            $recipient['email'],
            $recipient['name'],
            $subject,
            $message,
            $teacher_name
        );
        
        if ($result['success']) {
            $results['success']++;
        } else {
            $results['failed']++;
        }
        
        $results['details'][] = [
            'email' => $recipient['email'],
            'name' => $recipient['name'],
            'status' => $result['success'] ? 'sent' : 'failed',
            'message' => $result['message']
        ];
        
        usleep(100000); // 0.1 second delay
    }
    
    return $results;
}

/**
 * Send test email
 */
function sendTestEmail($test_email) {
    return sendStudentEmail(
        $test_email,
        'Test Student',
        'Test Email - NIT Attendance System',
        "This is a test email to verify your email configuration is working correctly.\n\nIf you receive this email, your setup is successful!",
        'System Administrator'
    );
}
?>