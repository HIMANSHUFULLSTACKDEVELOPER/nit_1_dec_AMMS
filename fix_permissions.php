<?php
/**
 * Complete Permission Fix Script
 * Run this ONCE to fix all upload issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Fix Permissions</title>
    <style>
        body { font-family: Arial; margin: 40px; background: #1a1a2e; color: #fff; }
        .container { max-width: 800px; margin: 0 auto; background: #16213e; padding: 30px; border-radius: 10px; }
        .success { background: #0f3460; color: #00ff00; padding: 15px; margin: 10px 0; border-left: 4px solid #00ff00; border-radius: 5px; }
        .error { background: #0f3460; color: #ff4444; padding: 15px; margin: 10px 0; border-left: 4px solid #ff4444; border-radius: 5px; }
        .info { background: #0f3460; color: #00ddff; padding: 15px; margin: 10px 0; border-left: 4px solid #00ddff; border-radius: 5px; }
        h1 { color: #00ff00; }
        h2 { color: #00ddff; }
        code { background: #0a0f1c; padding: 5px 10px; border-radius: 3px; }
        pre { background: #0a0f1c; padding: 15px; overflow-x: auto; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Fix Upload Directory Permissions</h1>";

// Get the actual document root
$doc_root = $_SERVER['DOCUMENT_ROOT'];
$script_dir = dirname(__FILE__);

// Get project root (one level up from student folder)
if (basename($script_dir) === 'student') {
    $project_root = dirname($script_dir);
} else {
    $project_root = $script_dir;
}

$upload_dir = $project_root . '/uploads';
$submissions_dir = $upload_dir . '/submissions';

echo "<div class='info'>
    <strong>📍 Paths Found:</strong><br>
    Document Root: <code>" . $doc_root . "</code><br>
    Project Root: <code>" . $project_root . "</code><br>
    Upload Dir: <code>" . $upload_dir . "</code><br>
    Submissions Dir: <code>" . $submissions_dir . "</code>
</div>";

// Step 1: Create uploads directory
echo "<h2>Step 1: Creating /uploads directory...</h2>";
if (!is_dir($upload_dir)) {
    if (@mkdir($upload_dir, 0777, true)) {
        echo "<div class='success'>✅ Created: $upload_dir</div>";
    } else {
        echo "<div class='error'>❌ Failed to create: $upload_dir</div>";
    }
} else {
    echo "<div class='success'>✅ Already exists: $upload_dir</div>";
}

// Step 2: Set permissions on uploads
echo "<h2>Step 2: Setting permissions on /uploads...</h2>";
if (is_dir($upload_dir)) {
    if (@chmod($upload_dir, 0777)) {
        echo "<div class='success'>✅ Permissions set (777) for: $upload_dir</div>";
    } else {
        echo "<div class='error'>❌ Could not set permissions for: $upload_dir</div>";
    }
    
    // Verify
    $perms = substr(sprintf('%o', fileperms($upload_dir)), -4);
    echo "<div class='info'>Current permissions: " . $perms . "</div>";
} else {
    echo "<div class='error'>❌ Directory does not exist: $upload_dir</div>";
}

// Step 3: Create submissions directory
echo "<h2>Step 3: Creating /uploads/submissions directory...</h2>";
if (!is_dir($submissions_dir)) {
    if (@mkdir($submissions_dir, 0777, true)) {
        echo "<div class='success'>✅ Created: $submissions_dir</div>";
    } else {
        echo "<div class='error'>❌ Failed to create: $submissions_dir</div>";
    }
} else {
    echo "<div class='success'>✅ Already exists: $submissions_dir</div>";
}

// Step 4: Set permissions on submissions
echo "<h2>Step 4: Setting permissions on /uploads/submissions...</h2>";
if (is_dir($submissions_dir)) {
    if (@chmod($submissions_dir, 0777)) {
        echo "<div class='success'>✅ Permissions set (777) for: $submissions_dir</div>";
    } else {
        echo "<div class='error'>❌ Could not set permissions for: $submissions_dir</div>";
    }
    
    // Verify
    $perms = substr(sprintf('%o', fileperms($submissions_dir)), -4);
    echo "<div class='info'>Current permissions: " . $perms . "</div>";
} else {
    echo "<div class='error'>❌ Directory does not exist: $submissions_dir</div>";
}

// Step 5: Check if writable
echo "<h2>Step 5: Checking if directories are writable...</h2>";
if (is_writable($upload_dir)) {
    echo "<div class='success'>✅ /uploads is WRITABLE</div>";
} else {
    echo "<div class='error'>❌ /uploads is NOT writable</div>";
}

if (is_writable($submissions_dir)) {
    echo "<div class='success'>✅ /uploads/submissions is WRITABLE</div>";
} else {
    echo "<div class='error'>❌ /uploads/submissions is NOT writable</div>";
}

// Step 6: Test file creation
echo "<h2>Step 6: Test file creation...</h2>";
$test_file = $submissions_dir . '/test_' . time() . '.txt';
if (@file_put_contents($test_file, 'test')) {
    echo "<div class='success'>✅ Successfully wrote test file: $test_file</div>";
    
    // Cleanup
    if (@unlink($test_file)) {
        echo "<div class='success'>✅ Test file cleaned up</div>";
    }
} else {
    echo "<div class='error'>❌ Failed to write test file in: $submissions_dir</div>";
}

// Step 7: Create .htaccess for security
echo "<h2>Step 7: Creating security configuration...</h2>";
$htaccess = $submissions_dir . '/.htaccess';
$htaccess_content = "# Prevent direct access to uploaded files
<FilesMatch \"\\.php\$\">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Allow viewing specific file types
<FilesMatch \"\\.(pdf|jpg|jpeg|png|gif|txt|doc|docx|ppt|pptx|zip)$\">
    Allow from all
</FilesMatch>";

if (@file_put_contents($htaccess, $htaccess_content)) {
    echo "<div class='success'>✅ .htaccess created for security</div>";
} else {
    echo "<div class='info'>ℹ️ .htaccess could not be created (not critical)</div>";
}

// Final Status
echo "<h2>✨ Final Status</h2>";

$all_ok = is_dir($submissions_dir) && is_writable($submissions_dir) && is_writable($upload_dir);

if ($all_ok) {
    echo "<div class='success' style='font-size: 18px;'>
        ✅ <strong>All permissions fixed successfully!</strong><br>
        <br>You can now proceed with file uploads.
    </div>";
} else {
    echo "<div class='error' style='font-size: 18px;'>
        ⚠️ <strong>Some issues remain.</strong><br>
        <br>Try the manual fix below.
    </div>";
}

// Manual commands
echo "<h2>🖥️ If Web Fix Didn't Work - Use These Commands</h2>";
echo "<p><strong>For Linux/Mac Terminal:</strong></p>";
echo "<pre>
cd " . $project_root . "
mkdir -p uploads/submissions
chmod -R 777 uploads
ls -la uploads/
</pre>";

echo "<p><strong>For Windows Command Prompt:</strong></p>";
echo "<pre>
cd " . $project_root . "
mkdir uploads
mkdir uploads\\submissions
icacls uploads /grant %username%:F /T
</pre>";

// FTP Alternative
echo "<h2>📁 Alternative: Using FTP</h2>";
echo "<div class='info'>
    <strong>If you have FTP access:</strong><br>
    1. Connect via FTP client (FileZilla, WinSCP, etc.)<br>
    2. Create folder: <code>uploads</code><br>
    3. Create subfolder: <code>uploads/submissions</code><br>
    4. Right-click both folders → Properties<br>
    5. Set Permissions to <code>777</code><br>
    6. Apply recursively
</div>";

// Contact Hosting
echo "<h2>📞 If Still Not Working</h2>";
echo "<div class='error'>
    <strong>Contact your hosting provider with:</strong><br>
    • Request to enable write permissions on /uploads directory<br>
    • Current PHP user: <code>" . get_current_user() . "</code><br>
    • Server OS: <code>" . php_uname() . "</code><br>
    • PHP Version: <code>" . phpversion() . "</code>
</div>";

echo "</div>
</body>
</html>";
?>