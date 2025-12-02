<?php
require_once '../db.php';
checkRole(['student']);

$user = getCurrentUser();
$student_id = $user['id'];

// Get all notifications for this student
$query = "SELECT sn.*, u.full_name as teacher_name, c.class_name, c.section 
          FROM student_notifications sn
          JOIN users u ON sn.teacher_id = u.id
          JOIN classes c ON sn.class_id = c.id
          WHERE sn.student_id = ?
          ORDER BY sn.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$notifications = $stmt->get_result();
?>

<!-- Display notifications here -->