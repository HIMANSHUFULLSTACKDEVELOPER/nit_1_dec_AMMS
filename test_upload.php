<?php
// test_upload.php - Quick diagnostic tool
// Visit this file to test if uploads will work

error_reporting(E_ALL);
ini_set('display_errors', 1);

$base_path = dirname(__FILE__);
$upload_path = $base_path . '/uploads/submissions/';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Upload System Test</title>
    <style>
        body { font-family: Arial; margin: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .test-item { padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #007bff; }
        .pass { background: #d4edda; border-left-color: #28a745; color: #155724; }
        .fail { background: #f8d7da; border-left-color: #dc3545; color: #721c24; }
        .warn { background: #fff3cd; border-left-color: #ffc107; color: #856404; }
        h1 { color: #333; }
        code { background: #f0f0f0; padding: 2px 5px; border-radius: 3px; }
        .icon { margin-right: 8px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
        th { background: #f0f0f0; }
        .form-test { background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Upload System Diagnostic Test</h1>
        
        <h2>📋 System Requirements</h2>
        
        <!-- PHP Version -->
        <?php
        $php_version = phpversion();
        $version_ok = version_compare($php_version, '7.0.0', '>=');
        $class = $version_ok ? 'pass' : 'fail';
        ?>
        <div class="test-item <?php echo $class; ?>">
            <span class="icon"><?php echo $version_ok ? '✅' : '❌'; ?></span>
            <strong>PHP Version:</strong> <?php echo $php_version; ?>
            <?php echo $version_ok ? '(OK)' : '(Minimum 7.0 required)'; ?>
        </div>
        
        <!-- File Upload Support -->
        <?php $upload_enabled = ini_get('file_uploads') ? 'On' : 'Off'; ?>
        <div class="test-item <?php echo $upload_enabled === 'On' ? 'pass' : 'fail'; ?>">
            <span class="icon"><?php echo $upload_enabled === 'On' ? '✅' : '❌'; ?></span>
            <strong>File Uploads:</strong> <?php echo $upload_enabled; ?>
        </div>
        
        <!-- Upload Max Size -->
        <?php 
        $upload_max = ini_get('upload_max_filesize');
        $post_max = ini_get('post_max_size');
        ?>
        <div class="test-item pass">
            <span class="icon">ℹ️</span>
            <strong>Max Upload Size:</strong> <?php echo $upload_max; ?> (Post: <?php echo $post_max; ?>)
        </div>
        
        <h2>📁 Directory Tests</h2>
        
        <!-- Base Path -->
        <div class="test-item pass">
            <span class="icon">ℹ️</span>
            <strong>Base Path:</strong> <code><?php echo $base_path; ?></code>
        </div>
        
        <!-- Uploads Directory -->
        <?php
        $uploads_dir = $base_path . '/uploads/';
        $uploads_exists = is_dir($uploads_dir);
        $uploads_writable = is_writable($uploads_dir);
        $class = ($uploads_exists && $uploads_writable) ? 'pass' : 'fail';
        ?>
        <div class="test-item <?php echo $class; ?>">
            <span class="icon"><?php echo ($uploads_exists ? '✅' : '❌'); ?></span>
            <strong>Uploads Directory:</strong> 
            <code><?php echo $uploads_dir; ?></code>
            <?php if (!$uploads_exists) echo '<br>❌ Directory does not exist'; ?>
            <?php if ($uploads_exists && !$uploads_writable) echo '<br>❌ Directory exists but not writable'; ?>
        </div>
        
        <!-- Submissions Directory -->
        <?php
        $submissions_exists = is_dir($upload_path);
        $submissions_writable = is_writable($upload_path);
        $class = ($submissions_exists && $submissions_writable) ? 'pass' : 'fail';
        ?>
        <div class="test-item <?php echo $class; ?>">
            <span class="icon"><?php echo ($submissions_exists ? '✅' : '❌'); ?></span>
            <strong>Submissions Directory:</strong> 
            <code><?php echo $upload_path; ?></code>
            <?php if (!$submissions_exists) echo '<br>❌ Directory does not exist'; ?>
            <?php if ($submissions_exists && !$submissions_writable) echo '<br>❌ Directory exists but not writable'; ?>
        </div>
        
        <h2>🧪 Upload Test Form</h2>
        
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            echo "<div class='test-item warn'>";
            echo "<strong>📤 Test Upload Initiated</strong><br>";
            
            if (!isset($_FILES['test_file']) || $_FILES['test_file']['error'] === UPLOAD_ERR_NO_FILE) {
                echo "No file selected";
            } else {
                $file = $_FILES['test_file'];
                echo "File: " . htmlspecialchars($file['name']) . "<br>";
                echo "Size: " . ($file['size'] / 1024) . " KB<br>";
                echo "Temp: " . htmlspecialchars($file['tmp_name']) . "<br>";
                
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    echo "Upload Error: " . $file['error'] . "<br>";
                    $error_messages = [
                        0 => "No error",
                        1 => "File exceeds upload_max_filesize",
                        2 => "File exceeds MAX_FILE_SIZE",
                        3 => "File only partially uploaded",
                        4 => "No file uploaded",
                        5 => "Missing temp folder",
                        6 => "Failed to write file",
                        7 => "Extension blocked"
                    ];
                    echo "Error: " . $error_messages[$file['error']] ?? "Unknown error";
                } else {
                    // Try to move file
                    $test_file = $upload_path . 'test_' . time() . '.txt';
                    if (move_uploaded_file($file['tmp_name'], $test_file)) {
                        echo "<div class='test-item pass'>";
                        echo "✅ <strong>Upload Successful!</strong><br>";
                        echo "File saved to: <code>" . $test_file . "</code>";
                        
                        // Clean up
                        if (is_file($test_file)) {
                            unlink($test_file);
                            echo "<br>Cleaned up test file";
                        }
                        echo "</div>";
                    } else {
                        echo "<div class='test-item fail'>";
                        echo "❌ <strong>Failed to save file</strong><br>";
                        echo "Upload path writable: " . (is_writable($upload_path) ? 'Yes' : 'No') . "<br>";
                        echo "Upload path exists: " . (is_dir($upload_path) ? 'Yes' : 'No');
                        echo "</div>";
                    }
                }
            }
            echo "</div>";
        }
        ?>
        
        <form method="POST" enctype="multipart/form-data" class="form-test">
            <h3>Test File Upload</h3>
            <p>Upload a small test file to verify the upload system works:</p>
            <input type="file" name="test_file" required>
            <button type="submit" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; margin-top: 10px;">
                Test Upload
            </button>
        </form>
        
        <h2>⚙️ PHP Settings</h2>
        <table>
            <tr>
                <th>Setting</th>
                <th>Value</th>
            </tr>
            <tr>
                <td>file_uploads</td>
                <td><?php echo ini_get('file_uploads') ? 'On' : 'Off'; ?></td>
            </tr>
            <tr>
                <td>upload_max_filesize</td>
                <td><?php echo ini_get('upload_max_filesize'); ?></td>
            </tr>
            <tr>
                <td>post_max_size</td>
                <td><?php echo ini_get('post_max_size'); ?></td>
            </tr>
            <tr>
                <td>memory_limit</td>
                <td><?php echo ini_get('memory_limit'); ?></td>
            </tr>
            <tr>
                <td>max_execution_time</td>
                <td><?php echo ini_get('max_execution_time'); ?>s</td>
            </tr>
            <tr>
                <td>upload_tmp_dir</td>
                <td><?php echo ini_get('upload_tmp_dir') ?: '(default)'; ?></td>
            </tr>
        </table>
        
        <h2>🔧 Solutions</h2>
        
        <?php if (!$submissions_exists): ?>
        <div class="test-item warn">
            <strong>Missing Submissions Folder:</strong>
            <p>Run this to create it:</p>
            <code style="display: block; background: #f0f0f0; padding: 10px; border-radius: 5px; margin: 10px 0;">
                mkdir -p <?php echo $upload_path; ?> && chmod 777 <?php echo $upload_path; ?>
            </code>
            <p>Or access: <code><?php echo basename(dirname(__FILE__)); ?>/setup_directories.php</code></p>
        </div>
        <?php endif; ?>
        
        <?php if ($submissions_exists && !$submissions_writable): ?>
        <div class="test-item warn">
            <strong>Directory Not Writable:</strong>
            <p>Run this command:</p>
            <code style="display: block; background: #f0f0f0; padding: 10px; border-radius: 5px; margin: 10px 0;">
                chmod -R 777 <?php echo $upload_path; ?>
            </code>
        </div>
        <?php endif; ?>
        
    </div>
</body>
</html>