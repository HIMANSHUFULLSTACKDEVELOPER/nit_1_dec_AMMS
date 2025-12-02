<?php
/**
 * Email Handler for Attendance System
 * This file handles AJAX email requests from the attendance page
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['teacher_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Include database and email configuration
require_once '../db_connect.php';
require_once 'email_config.php';

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get POST data
$student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
$subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

// Validate inputs
if (empty($student_id) || empty($subject) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Get student information
$student_query = "SELECT name, email FROM students WHERE id = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student_result = $stmt->get_result();

if ($student_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit;
}

$student = $student_result->fetch_assoc();

// Validate student email
if (empty($student['email']) || !isValidEmail($student['email'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid student email address']);
    exit;
}

// Get teacher information
$teacher_query = "SELECT name FROM teachers WHERE id = ?";
$stmt = $conn->prepare($teacher_query);
$stmt->bind_param("i", $_SESSION['teacher_id']);
$stmt->execute();
$teacher_result = $stmt->get_result();
$teacher = $teacher_result->fetch_assoc();

// Send email
$result = sendStudentEmail(
    $student['email'],
    $student['name'],
    $subject,
    $message,
    $teacher['name']
);

// Log email activity (optional)
if ($result['success']) {
    $log_query = "INSERT INTO email_logs (teacher_id, student_id, subject, message, status, sent_at) 
                  VALUES (?, ?, ?, ?, 'sent', NOW())";
    $stmt = $conn->prepare($log_query);
    $stmt->bind_param("iiss", $_SESSION['teacher_id'], $student_id, $subject, $message);
    $stmt->execute();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($result);

?>