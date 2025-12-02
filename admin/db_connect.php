<?php
// Database connection for marks management system
// This can be included in any marks-related file that needs direct DB access

// Use existing connection from parent db.php if available
if (file_exists(__DIR__ . '/../db.php')) {
    require_once __DIR__ . '/../db.php';
} else {
    // Fallback direct connection
    $db_host = 'localhost';
    $db_username = 'root';
    $db_password = '';
    $db_name = 'nit_student_attendance';

    $conn = new mysqli($db_host, $db_username, $db_password, $db_name);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $conn->set_charset("utf8mb4");
}

// Helper function to sanitize input
if (!function_exists('sanitize')) {
    function sanitize($data) {
        global $conn;
        return htmlspecialchars(strip_tags(trim($data)));
    }
}

// Helper function to check role
if (!function_exists('checkRole')) {
    function checkRole($allowed_roles) {
        if (!isset($_SESSION)) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
            header("Location: ../login.php");
            exit();
        }
        
        if (!in_array($_SESSION['role'], $allowed_roles)) {
            header("Location: ../unauthorized.php");
            exit();
        }
    }
}

// Helper function to get current user
if (!function_exists('getCurrentUser')) {
    function getCurrentUser() {
        if (!isset($_SESSION)) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        
        global $conn;
        $user_id = $_SESSION['user_id'];
        $role = $_SESSION['role'];
        
        if ($role === 'student') {
            $query = "SELECT * FROM students WHERE id = ?";
        } elseif ($role === 'parent') {
            $query = "SELECT * FROM parents WHERE id = ?";
        } else {
            $query = "SELECT * FROM users WHERE id = ?";
        }
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}
?>