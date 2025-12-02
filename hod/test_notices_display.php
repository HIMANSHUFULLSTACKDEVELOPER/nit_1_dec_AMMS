<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Testing Notices Component</h1>";

// Test if db.php exists and loads
require_once '../db.php';
echo "<p>✅ db.php loaded successfully</p>";

// Check database connection
if (isset($conn)) {
    echo "<p>✅ Database connection exists</p>";
} else {
    echo "<p>❌ No database connection!</p>";
}

// Test if notices table exists
$check = $conn->query("SHOW TABLES LIKE 'notices'");
if ($check && $check->num_rows > 0) {
    echo "<p>✅ Notices table exists</p>";
} else {
    echo "<p>❌ Notices table NOT found!</p>";
}

// Try to include the component
echo "<hr><h2>Trying to load notices component:</h2>";
$notices_path = __DIR__ . '/../admin/notices_component.php';
echo "<p>Path: $notices_path</p>";

if (file_exists($notices_path)) {
    echo "<p>✅ File exists</p>";
    require_once $notices_path;
    
    if (function_exists('displayNotices')) {
        echo "<p>✅ displayNotices function loaded</p>";
        echo "<hr>";
        displayNotices('hod');
    } else {
        echo "<p>❌ displayNotices function NOT found!</p>";
    }
} else {
    echo "<p>❌ File does NOT exist at: $notices_path</p>";
}
?>