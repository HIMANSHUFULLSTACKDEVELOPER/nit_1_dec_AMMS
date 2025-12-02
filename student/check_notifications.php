<?php
/**
 * AJAX Endpoint for checking unread message count
 * Returns JSON with unread count
 * Place in the root directory of each user type folder
 */

session_start();
require_once '../db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode(['error' => 'Not authenticated', 'unread' => 0]);
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$unread_count = 0;

try {
    if ($role === 'teacher') {
        $query = "SELECT COUNT(*) as cnt FROM hod_messages WHERE recipient_id = ? AND recipient_type = 'teacher' AND is_read = 0";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $unread_count = $result->fetch_assoc()['cnt'];
    }
    elseif ($role === 'student') {
        $query = "SELECT COUNT(*) as cnt FROM hod_messages WHERE recipient_id = ? AND recipient_type = 'student' AND is_read = 0";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $unread_count = $result->fetch_assoc()['cnt'];
    }
    elseif ($role === 'parent') {
        $query = "SELECT COUNT(*) as cnt FROM hod_messages WHERE recipient_id = ? AND recipient_type = 'parent' AND is_read = 0";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $unread_count = $result->fetch_assoc()['cnt'];
    }
    
    echo json_encode([
        'success' => true,
        'unread' => intval($unread_count),
        'role' => $role
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'unread' => 0
    ]);
}
?>