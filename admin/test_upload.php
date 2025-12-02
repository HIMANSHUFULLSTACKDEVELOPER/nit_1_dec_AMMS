<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>📋 Upload Diagnostic Tool</h1>";
echo "<style>
    body { font-family: Arial; padding: 20px; background: #f5f5f5; }
    .success { background: #d4edda; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745; }
    .error { background: #f8d7da; padding: 15px; margin: 10px 0; border-left: 4px solid #dc3545; }
    .info { background: #d1ecf1; padding: 15px; margin: 10px 0; border-left: 4px solid #0c5460; }
    code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
</style>";

// Test 1: Check current directory
echo "<h2>📁 Current Directory</h2>";
echo "<div class='info'>Current script location: <code>" . __DIR__ . "</code></div>";
echo "<div class='info'>Current working directory: <code>" . getcwd() . "</code></div>";

// Test 2: Check if uploads folder exists
echo "<h2>📂 Check Folders</h2>";

$folders_to_check = [
    '../uploads/' => 'Main uploads folder',
    '../uploads/teachers/' => 'Teachers folder',
    '../uploads/students/' => 'Students folder',
    '../uploads/parents/' => 'Parents folder'
];

foreach ($folders_to_check as $folder => $description) {
    $full_path = realpath($folder);
    
    if (file_exists($folder)) {
        $perms = substr(sprintf('%o', fileperms($folder)), -4);
        $writable = is_writable($folder) ? '✅ Writable' : '❌ NOT Writable';
        
        echo "<div class='success'>";
        echo "✅ <strong>$description</strong> EXISTS<br>";
        echo "Path: <code>$full_path</code><br>";
        echo "Permissions: <code>$perms</code> - $writable";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "❌ <strong>$description</strong> does NOT exist<br>";
        echo "Expected at: <code>" . realpath(dirname($folder)) . "/" . basename($folder) . "</code>";
        echo "</div>";
    }
}

// Test 3: Try to create folders
echo "<h2>🔧 Auto-Create Folders</h2>";

if (!file_exists('../uploads/teachers/')) {
    if (mkdir('../uploads/teachers/', 0777, true)) {
        chmod('../uploads/teachers/', 0777);
        echo "<div class='success'>✅ Created ../uploads/teachers/ folder</div>";
    } else {
        echo "<div class='error'>❌ Failed to create ../uploads/teachers/ folder</div>";
    }
} else {
    echo "<div class='info'>Folder already exists</div>";
}

// Test 4: Try to write a test file
echo "<h2>✍️ Test File Write</h2>";

$test_file = '../uploads/teachers/test_' . time() . '.txt';

if (file_put_contents($test_file, 'Test content')) {
    echo "<div class='success'>✅ Successfully wrote test file: <code>$test_file</code></div>";
    
    // Delete test file
    if (unlink($test_file)) {
        echo "<div class='success'>✅ Successfully deleted test file</div>";
    }
} else {
    echo "<div class='error'>❌ Failed to write test file to: <code>$test_file</code></div>";
}

// Test 5: PHP Upload Settings
echo "<h2>⚙️ PHP Upload Settings</h2>";

echo "<div class='info'>";
echo "file_uploads: <code>" . ini_get('file_uploads') . "</code><br>";
echo "upload_max_filesize: <code>" . ini_get('upload_max_filesize') . "</code><br>";
echo "post_max_size: <code>" . ini_get('post_max_size') . "</code><br>";
echo "max_file_uploads: <code>" . ini_get('max_file_uploads') . "</code><br>";
echo "upload_tmp_dir: <code>" . (ini_get('upload_tmp_dir') ?: 'default') . "</code>";
echo "</div>";

// Test 6: Test actual upload
echo "<h2>📸 Test Photo Upload</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_photo'])) {
    echo "<div class='info'><strong>Upload Details:</strong><br>";
    echo "File name: <code>" . $_FILES['test_photo']['name'] . "</code><br>";
    echo "File size: <code>" . $_FILES['test_photo']['size'] . " bytes</code><br>";
    echo "File type: <code>" . $_FILES['test_photo']['type'] . "</code><br>";
    echo "Temp file: <code>" . $_FILES['test_photo']['tmp_name'] . "</code><br>";
    echo "Error code: <code>" . $_FILES['test_photo']['error'] . "</code><br>";
    echo "</div>";
    
    if ($_FILES['test_photo']['error'] === UPLOAD_ERR_OK) {
        $file_ext = strtolower(pathinfo($_FILES['test_photo']['name'], PATHINFO_EXTENSION));
        $new_filename = 'test_' . time() . '.' . $file_ext;
        $destination = '../uploads/teachers/' . $new_filename;
        
        echo "<div class='info'>Attempting to move to: <code>$destination</code></div>";
        
        if (move_uploaded_file($_FILES['test_photo']['tmp_name'], $destination)) {
            echo "<div class='success'>✅ <strong>SUCCESS!</strong> File uploaded to: <code>$destination</code></div>";
            echo "<img src='$destination' style='max-width: 200px; border-radius: 10px; margin-top: 10px;'>";
        } else {
            echo "<div class='error'>❌ <strong>FAILED!</strong> Could not move uploaded file to: <code>$destination</code></div>";
            echo "<div class='error'>Possible reasons:<br>";
            echo "- Folder doesn't exist<br>";
            echo "- No write permissions<br>";
            echo "- Incorrect path</div>";
        }
    } else {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
        ];
        
        $error_msg = $error_messages[$_FILES['test_photo']['error']] ?? 'Unknown error';
        echo "<div class='error'>❌ Upload Error: <strong>$error_msg</strong></div>";
    }
}

// Upload form
echo "<form method='POST' enctype='multipart/form-data' style='margin-top: 20px; padding: 20px; background: white; border-radius: 10px;'>";
echo "<h3>Upload Test Photo:</h3>";
echo "<input type='file' name='test_photo' accept='image/*' required style='margin: 10px 0;'><br>";
echo "<button type='submit' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Upload Test Photo</button>";
echo "</form>";

echo "<hr style='margin: 30px 0;'>";
echo "<div class='info'><strong>💡 Next Steps:</strong><br>";
echo "1. Check that all folders show as ✅ Writable<br>";
echo "2. Try uploading a test photo above<br>";
echo "3. If everything works here, go back to manage_teachers.php and try again</div>";
?>