<?php
// Database connection configuration
// Adjust these settings according to your setup

$db_host = 'localhost';
$db_username = 'root';
$db_password = '';
$db_name = 'nit_student_attendance';

// Create connection
$conn = new mysqli($db_host, $db_username, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");
?>