<?php
// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is a student
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    die("Unauthorized access. Please login as a student.");
}

if (!isset($_GET['assignment_id'])) {
    die("Assignment ID not provided");
}

// Include database connection
require_once '../db.php';

$assignment_id = intval($_GET['assignment_id']);

// Get assignment file path
$query = "SELECT file_path FROM assignments WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$result = $stmt->get_result();
$assignment = $result->fetch_assoc();

if (!$assignment || !$assignment['file_path']) {
    die("Assignment file not found in database");
}

$filename = basename($assignment['file_path']);

// Try to find the file
$base_path = dirname(dirname(__FILE__));
$filepath = $base_path . "/uploads/assignments/" . $filename;

// If file doesn't exist, show debug info
if (!file_exists($filepath)) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>File Not Found - Debug</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
            .container { background: white; padding: 30px; border-radius: 10px; max-width: 800px; margin: 0 auto; }
            h2 { color: #dc3545; }
            .info { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; }
            .success { color: #28a745; }
            .error { color: #dc3545; }
            ul { background: #fff3cd; padding: 20px; border-radius: 5px; }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>🔍 File Not Found - Debug Information</h2>
            
            <div class="info">
                <strong>Database stored path:</strong><br>
                <?php echo htmlspecialchars($assignment['file_path']); ?>
            </div>
            
            <div class="info">
                <strong>Extracted filename:</strong><br>
                <?php echo htmlspecialchars($filename); ?>
            </div>
            
            <div class="info">
                <strong>Looking for file at:</strong><br>
                <?php echo htmlspecialchars($filepath); ?>
            </div>
            
            <div class="info">
                <strong>Base directory:</strong><br>
                <?php echo htmlspecialchars($base_path); ?>
            </div>
            
            <div class="info">
                <strong>Uploads directory exists:</strong> 
                <?php 
                $uploads_dir = $base_path . "/uploads/assignments/";
                echo is_dir($uploads_dir) ? '<span class="success">✅ YES</span>' : '<span class="error">❌ NO</span>'; 
                ?>
            </div>
            
            <?php if (is_dir($uploads_dir)): ?>
                <h3>📁 Files in uploads/assignments directory:</h3>
                <ul>
                    <?php
                    $files = scandir($uploads_dir);
                    $found = false;
                    foreach ($files as $file) {
                        if ($file != '.' && $file != '..') {
                            $found = true;
                            echo "<li>" . htmlspecialchars($file);
                            if ($file === $filename) {
                                echo " <span class='success'>← THIS IS THE FILE WE NEED!</span>";
                            }
                            echo "</li>";
                        }
                    }
                    if (!$found) {
                        echo "<li><em>No files found in this directory</em></li>";
                    }
                    ?>
                </ul>
            <?php endif; ?>
            
            <div style="margin-top: 20px; padding: 15px; background: #d1ecf1; border-radius: 5px;">
                <strong>💡 What to do:</strong><br>
                If the file is not listed above, it means the file was never uploaded to the server. 
                Check the teacher's assignment creation code to ensure files are being uploaded correctly.
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// File exists, proceed with download
$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

// Set content type
$content_types = [
    'pdf' => 'application/pdf',
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif' => 'image/gif',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls' => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'txt' => 'text/plain',
    'zip' => 'application/zip'
];

$content_type = isset($content_types[$extension]) ? $content_types[$extension] : 'application/octet-stream';

// Clear any output
while (ob_get_level()) {
    ob_end_clean();
}

// Send headers
header('Content-Type: ' . $content_type);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: must-revalidate');
header('Pragma: public');

// Output file
readfile($filepath);
exit;
?>