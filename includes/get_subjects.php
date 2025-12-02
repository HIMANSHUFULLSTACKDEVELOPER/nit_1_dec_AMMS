<?php
require_once 'db_connect.php';

header('Content-Type: application/json');

$dept_id = $_GET['dept_id'] ?? 0;
$year = $_GET['year'] ?? 0;
$semester = $_GET['semester'] ?? 0;

if (!$dept_id || !$year || !$semester) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT id, subject_code, subject_name, max_marks 
                       FROM subjects 
                       WHERE department_id = ? AND year = ? AND semester = ? AND is_active = 1
                       ORDER BY subject_code");
$stmt->bind_param("iii", $dept_id, $year, $semester);
$stmt->execute();
$result = $stmt->get_result();

$subjects = [];
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
}

echo json_encode($subjects);
?>