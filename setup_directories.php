<?php
// setup_directories.php
// Run this file once to create all required directories with proper permissions

$base_path = dirname(__FILE__);
$directories = [
    $base_path . '/uploads',
    $base_path . '/uploads/submissions',
    $base_path . '/uploads/assignments',
    $base_path . '/logs'
];

echo "<!DOCTYPE html>
<html>
<head>
    <title>Setup Directories</title>
    <style>
        body { font-family: Arial; margin: 40px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; padding: 10px; margin: 10px 0; background: #d4edda; border-radius: 5px; }
        .error { color: #dc3545; padding: 10px; margin: 10px 0; background: #f8d7da; border-radius: 5px; }
        .warning { color: #856404; padding: 10px; margin: 10px 0; background: #fff3cd; border-radius: 5px; }
        h1 { color: #333; }
        code { background: #f0f0f0; padding: 2px 5px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>📁 Directory Setup</h1>";

$all_success = true;

foreach ($directories as $dir) {
    $dir_name = basename($dir);
    
    if (is_dir($dir)) {
        echo "<div class='success'>✅ <code>" . $dir_name . "</code> - Already exists</div>";
        
        // Check if writable
        if (!is_writable($dir)) {
            echo "<div class='warning'>⚠️ Directory exists but may not be writable. Attempting to fix...</div>";
            if (chmod($dir, 0777)) {
                echo "<div class='success'>✅ Permissions fixed for <code>" . $dir_name . "</code></div>";
            } else {
                echo "<div class='error'>❌ Failed to fix permissions for <code>" . $dir_name . "</code></div>";
                $all_success = false;
            }
        }
    } else {
        // Create directory
        if (mkdir($dir, 0777, true)) {
            chmod($dir, 0777);
            echo "<div class='success'>✅ Created <code>" . $dir_name . "</code> successfully</div>";
        } else {
            echo "<div class='error'>❌ Failed to create <code>" . $dir_name . "</code></div>";
            $all_success = false;
        }
    }
}

// Create .htaccess file for submissions folder
$htaccess_content = "# Prevent direct access to uploaded files
<FilesMatch \"\\.(exe|sh|bat|php|phtml|php3|php4|php5|php6|php7|pht|shtml|phar)$\">
    Deny from all
</FilesMatch>

# Allow viewing of PDFs and images
<FilesMatch \"\\.(pdf|jpg|jpeg|png|gif|txt|doc|docx|ppt|pptx|zip)$\">
    Allow from all
</FilesMatch>";

$htaccess_path = $base_path . '/uploads/submissions/.htaccess';
if (file_put_contents($htaccess_path, $htaccess_content)) {
    echo "<div class='success'>✅ Security file (.htaccess) created</div>";
} else {
    echo "<div class='warning'>⚠️ Could not create .htaccess (not critical on non-Apache servers)</div>";
}

// Create .gitkeep for version control
$gitkeep_files = [
    $base_path . '/uploads/.gitkeep',
    $base_path . '/uploads/submissions/.gitkeep',
    $base_path . '/uploads/assignments/.gitkeep',
    $base_path . '/logs/.gitkeep'
];

foreach ($gitkeep_files as $gitkeep) {
    if (!file_exists($gitkeep)) {
        file_put_contents($gitkeep, '');
    }
}

echo "<div class='success'>✅ Version control files created</div>";

echo "<hr style='margin: 30px 0;'>";

// Display directory info
echo "<h2>📊 Directory Information</h2>";
echo "<table style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'><th style='padding: 10px; text-align: left; border: 1px solid #ddd;'>Directory</th><th style='padding: 10px; text-align: left; border: 1px solid #ddd;'>Status</th><th style='padding: 10px; text-align: left; border: 1px solid #ddd;'>Writable</th></tr>";

foreach ($directories as $dir) {
    $exists = is_dir($dir) ? '✅ Exists' : '❌ Missing';
    $writable = is_writable($dir) ? '✅ Yes' : '❌ No';
    echo "<tr>";
    echo "<td style='padding: 10px; border: 1px solid #ddd;'><code>" . $dir_name = basename($dir) . "</code></td>";
    echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . $exists . "</td>";
    echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . $writable . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr style='margin: 30px 0;'>";

if ($all_success) {
    echo "<div class='success'><h2>✅ All directories are ready!</h2><p>You can now proceed with file uploads.</p></div>";
} else {
    echo "<div class='error'><h2>❌ Some directories need attention</h2><p>Check the errors above and try again.</p></div>";
}

// Provide commands for manual setup
echo "<h2>🖥️ Manual Setup (if web setup doesn't work)</h2>";
echo "<p>Run these commands in your terminal:</p>";
echo "<pre style='background: #f0f0f0; padding: 15px; border-radius: 5px; overflow-x: auto;'>";
echo "cd " . $base_path . "\n";
echo "mkdir -p uploads/submissions uploads/assignments logs\n";
echo "chmod -R 777 uploads logs\n";
echo "touch uploads/submissions/.gitkeep uploads/assignments/.gitkeep logs/.gitkeep";
echo "</pre>";

echo "</div>
</body>
</html>";
?>