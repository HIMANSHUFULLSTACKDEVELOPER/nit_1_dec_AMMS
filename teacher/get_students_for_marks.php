<?php
require_once '../db.php';
checkRole(['teacher']);

header('Content-Type: application/json');

$subject_id = intval($_GET['subject_id'] ?? 0);
$year = intval($_GET['year'] ?? 0);
$semester = intval($_GET['semester'] ?? 0);
$section = sanitize($_GET['section'] ?? '');
$exam_type_id = intval($_GET['exam_type_id'] ?? 0);
$academic_year = sanitize($_GET['academic_year'] ?? '');

if (!$subject_id || !$year || !$semester || !$section || !$exam_type_id || !$academic_year) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Get students enrolled in this section with their existing marks
$query = "SELECT DISTINCT s.id, s.roll_number, s.full_name, s.email,
          pm.marks_obtained, pm.percentage, pm.grade, pm.remarks
          FROM students s
          INNER JOIN classes c ON s.class_id = c.id
          LEFT JOIN paper_marks pm ON s.id = pm.student_id 
              AND pm.subject_id = ? 
              AND pm.exam_type_id = ?
              AND pm.academic_year = ?
          WHERE c.section = ?
          AND c.year = ?
          AND c.semester = ?
          AND c.academic_year = ?
          AND s.is_active = 1
          ORDER BY s.roll_number";

$stmt = $conn->prepare($query);
$stmt->bind_param("iississi", $subject_id, $exam_type_id, $academic_year, 
                  $section, $year, $semester, $academic_year);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

echo json_encode([
    'success' => true,
    'students' => $students,
    'count' => count($students)
]);
?>