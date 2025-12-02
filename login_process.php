<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$role = sanitize($_POST['role']);
$username = sanitize($_POST['username']);
$password = $_POST['password'];

// Validate role
$allowed_roles = ['admin', 'hod', 'teacher', 'student', 'parent'];
if (!in_array($role, $allowed_roles)) {
    header("Location: index.php?error=invalid");
    exit();
}

// Check based on role
if ($role === 'student') {
    // Student login (using roll_number)
    $query = "SELECT * FROM students WHERE (roll_number = ? OR email = ?) AND is_active = 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = 'student';
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['roll_number'] = $user['roll_number'];
            $_SESSION['department_id'] = $user['department_id'];
            $_SESSION['class_id'] = $user['class_id'];
            
            header("Location: student/index.php");
            exit();
        }
    }
} 
elseif ($role === 'parent') {
    // Parent login (using email)
    $query = "SELECT * FROM parents WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = 'parent';
            $_SESSION['parent_name'] = $user['parent_name'];
            $_SESSION['student_id'] = $user['student_id'];
            
            header("Location: parent/index.php");
            exit();
        }
    }
} 
else {
    // Admin, HOD, Teacher login
    $query = "SELECT * FROM users WHERE username = ? AND role = ? AND is_active = 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $username, $role);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            
            if ($role === 'hod' || $role === 'teacher') {
                $_SESSION['department_id'] = $user['department_id'];
            }
            
            header("Location: $role/index.php");
            exit();
        }
    }
}

// If we reach here, login failed
header("Location: index.php?error=invalid");
exit();
?>