<?php
require_once 'db.php';

echo "<h1>HOD Photo Diagnostic Tool</h1>";
echo "<style>body { font-family: Arial; padding: 20px; } table { border-collapse: collapse; width: 100%; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background: #667eea; color: white; }</style>";

// Check uploads folder
echo "<h2>1. Folder Check</h2>";
$upload_dir = 'uploads/hods/';

if (file_exists($upload_dir)) {
    echo "✅ Folder exists: <code>$upload_dir</code><br>";
    echo "Permissions: " . substr(sprintf('%o', fileperms($upload_dir)), -4) . "<br>";
    
    // List files
    $files = scandir($upload_dir);
    echo "<br><strong>Files in folder:</strong><br>";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "📄 $file<br>";
        }
    }
} else {
    echo "❌ Folder does NOT exist: <code>$upload_dir</code><br>";
    echo "Creating folder...<br>";
    mkdir($upload_dir, 0777, true);
    chmod($upload_dir, 0777);
    echo "✅ Folder created!<br>";
}

// Check database
echo "<h2>2. Database Check</h2>";
$query = "SELECT id, username, full_name, photo FROM users WHERE role = 'hod'";
$result = $conn->query($query);

echo "<table>";
echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Photo in DB</th><th>File Exists?</th><th>Path</th></tr>";

while ($row = $result->fetch_assoc()) {
    $photo = $row['photo'];
    $file_exists = false;
    $actual_path = '';
    
    if (!empty($photo)) {
        // Check both possible paths
        if (file_exists("uploads/hods/" . $photo)) {
            $file_exists = true;
            $actual_path = "uploads/hods/" . $photo;
        } elseif (file_exists($photo)) {
            $file_exists = true;
            $actual_path = $photo;
        }
    }
    
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['username']) . "</td>";
    echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
    echo "<td>" . ($photo ? htmlspecialchars($photo) : '<em>NULL</em>') . "</td>";
    echo "<td>" . ($file_exists ? '✅ YES' : '❌ NO') . "</td>";
    echo "<td>" . ($actual_path ? $actual_path : '-') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Fix suggestions
echo "<h2>3. Fix Old Paths (if needed)</h2>";
echo "<p>If photos have old paths like 'uploads/hods/filename.jpg', run this SQL:</p>";
echo "<pre style='background: #f4f4f4; padding: 10px;'>";
echo "UPDATE users \n";
echo "SET photo = REPLACE(photo, 'uploads/hods/', '') \n";
echo "WHERE role = 'hod' AND photo LIKE 'uploads/hods/%';";
echo "</pre>";

echo "<h2>4. Test Upload</h2>";
echo "<form method='POST' enctype='multipart/form-data'>";
echo "<input type='file' name='test_photo' accept='image/*'>";
echo "<button type='submit' name='test_upload'>Test Upload</button>";
echo "</form>";

if (isset($_POST['test_upload']) && isset($_FILES['test_photo'])) {
    echo "<h3>Upload Test Result:</h3>";
    
    if ($_FILES['test_photo']['error'] === UPLOAD_ERR_OK) {
        $test_filename = 'test_' . time() . '.jpg';
        $test_path = $upload_dir . $test_filename;
        
        if (move_uploaded_file($_FILES['test_photo']['tmp_name'], $test_path)) {
            echo "✅ Upload successful!<br>";
            echo "File saved to: <code>$test_path</code><br>";
            echo "File size: " . filesize($test_path) . " bytes<br>";
            echo "<img src='$test_path' style='max-width: 200px; margin-top: 10px;'><br>";
            echo "<small>You can delete this test file manually.</small>";
        } else {
            echo "❌ Upload failed!<br>";
            echo "Check folder permissions.";
        }
    } else {
        echo "❌ Upload error: " . $_FILES['test_photo']['error'];
    }
}
?>

<br><br>
<a href="admin/manage_hods.php" style="padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;">← Back to Manage HODs</a>