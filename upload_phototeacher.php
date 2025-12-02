<?php
session_start();
require_once 'db.php';

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Please login first';
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'teacher';

// Validate file upload
if (!isset($_FILES['photo'])) {
    $_SESSION['error'] = 'No file selected';
    redirectBack($user_role);
}

$file = $_FILES['photo'];

// Check for upload errors
if ($file['error'] !== UPLOAD_ERR_OK) {
    $error_messages = [
        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
    ];
    $_SESSION['error'] = $error_messages[$file['error']] ?? 'Unknown upload error';
    redirectBack($user_role);
}

$file_tmp = $file['tmp_name'];
$file_size = $file['size'];
$file_name = $file['name'];

// Get file extension
$file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

// Allowed extensions
$allowed = ['jpg', 'jpeg', 'png', 'gif'];

if (!in_array($file_ext, $allowed)) {
    $_SESSION['error'] = 'Invalid file type. Only JPG, PNG, and GIF allowed.';
    redirectBack($user_role);
}

// Validate MIME type
if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file_tmp);
    finfo_close($finfo);
    
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($mime, $allowed_mimes)) {
        $_SESSION['error'] = 'Invalid file type detected.';
        redirectBack($user_role);
    }
}

// Validate file size (5MB)
if ($file_size > 5242880) {
    $_SESSION['error'] = 'File too large. Maximum 5MB allowed.';
    redirectBack($user_role);
}

// Create upload directory
$upload_dir = 'uploads/photos/';
if (!file_exists($upload_dir)) {
    if (!mkdir($upload_dir, 0777, true)) {
        $_SESSION['error'] = 'Failed to create uploads directory';
        redirectBack($user_role);
    }
    chmod($upload_dir, 0777);
}

// Generate unique filename
$new_filename = 'teacher_' . $user_id . '_' . uniqid() . '.' . $file_ext;
$upload_path = $upload_dir . $new_filename;

// Get old photo
$stmt = $conn->prepare("SELECT photo FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$old_data = $result->fetch_assoc();
$old_photo = $old_data['photo'] ?? null;
$stmt->close();

// Upload file
if (!move_uploaded_file($file_tmp, $upload_path)) {
    $_SESSION['error'] = 'Failed to move uploaded file. Check folder permissions.';
    error_log("Failed to move file to: " . $upload_path);
    redirectBack($user_role);
}

// Update database
$stmt = $conn->prepare("UPDATE users SET photo = ? WHERE id = ?");
$stmt->bind_param("si", $upload_path, $user_id);

if ($stmt->execute()) {
    // Delete old photo
    if ($old_photo && $old_photo !== $upload_path && file_exists($old_photo)) {
        unlink($old_photo);
    }
    
    $_SESSION['success'] = 'Photo uploaded successfully!';
    error_log("Photo updated for user $user_id: $upload_path");
} else {
    // Delete uploaded file if DB update fails
    unlink($upload_path);
    $_SESSION['error'] = 'Database update failed: ' . $stmt->error;
    error_log("DB Error: " . $stmt->error);
}

$stmt->close();
$conn->close();
redirectBack($user_role);

function redirectBack($role) {
    if ($role === 'admin') {
        header('Location: admin/profile.php');
    } elseif ($role === 'teacher') {
        header('Location: teacher/profile.php');
    } else {
        header('Location: student/profile.php');
    }
    exit;
}
?>