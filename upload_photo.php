<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    $user_type = $_POST['user_type'] ?? ''; // 'teacher', 'student', 'parent', 'hod'
    $user_id = intval($_POST['user_id'] ?? 0);
    
    if (!$user_id || !$user_type) {
        header("Location: " . $_SERVER['HTTP_REFERER'] . "?error=Invalid request");
        exit;
    }
    
    // Validate file
    if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        header("Location: " . $_SERVER['HTTP_REFERER'] . "?error=Upload failed");
        exit;
    }
    
    $file_ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (!in_array($file_ext, $allowed)) {
        header("Location: " . $_SERVER['HTTP_REFERER'] . "?error=Invalid file type");
        exit;
    }
    
    if ($_FILES['photo']['size'] > 5242880) { // 5MB
        header("Location: " . $_SERVER['HTTP_REFERER'] . "?error=File too large");
        exit;
    }
    
    // Set upload directory and table based on user type
    $upload_configs = [
        'teacher' => [
            'dir' => 'uploads/teachers/',
            'table' => 'users',
            'prefix' => 'teacher_'
        ],
        'student' => [
            'dir' => 'uploads/students/',
            'table' => 'students',
            'prefix' => 'student_'
        ],
        'parent' => [
            'dir' => 'uploads/parents/',
            'table' => 'parents',
            'prefix' => 'parent_'
        ],
        'hod' => [
            'dir' => 'uploads/hods/',
            'table' => 'users',
            'prefix' => 'hod_'
        ]
    ];
    
    if (!isset($upload_configs[$user_type])) {
        header("Location: " . $_SERVER['HTTP_REFERER'] . "?error=Invalid user type");
        exit;
    }
    
    $config = $upload_configs[$user_type];
    $upload_dir = $config['dir'];
    
    // Create directory if not exists
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Delete old photo
    $table = $config['table'];
    $old_photo_query = "SELECT photo FROM $table WHERE id = $user_id";
    $result = $conn->query($old_photo_query);
    
    if ($result && $row = $result->fetch_assoc()) {
        if (!empty($row['photo']) && file_exists($upload_dir . $row['photo'])) {
            @unlink($upload_dir . $row['photo']);
        }
    }
    
    // Generate new filename
    $new_filename = $config['prefix'] . $user_id . '_' . time() . '.' . $file_ext;
    $target_path = $upload_dir . $new_filename;
    
    // Move uploaded file
    if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
        // Update database with only filename (not full path)
        $update_query = "UPDATE $table SET photo = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $new_filename, $user_id);
        
        if ($stmt->execute()) {
            header("Location: " . $_SERVER['HTTP_REFERER'] . "?success=1");
        } else {
            @unlink($target_path); // Delete uploaded file if DB update fails
            header("Location: " . $_SERVER['HTTP_REFERER'] . "?error=Database update failed");
        }
    } else {
        header("Location: " . $_SERVER['HTTP_REFERER'] . "?error=Failed to save file");
    }
} else {
    header("Location: " . $_SERVER['HTTP_REFERER'] . "?error=No file uploaded");
}
exit;
?>