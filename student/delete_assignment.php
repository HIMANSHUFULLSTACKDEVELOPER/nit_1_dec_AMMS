<?php
require_once '../db.php';
checkRole(['teacher']);

$user = getCurrentUser();
$teacher_id = $user['id'];
$assignment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Verify assignment belongs to this teacher
$verify_query = "SELECT id, file_path FROM assignments WHERE id = ? AND teacher_id = ?";
$stmt = $conn->prepare($verify_query);
$stmt->bind_param("ii", $assignment_id, $teacher_id);
$stmt->execute();
$assignment = $stmt->get_result()->fetch_assoc();

if (!$assignment) {
    header('Location: assignments.php?error=not_found');
    exit;
}

// Delete associated file if exists
if ($assignment['file_path']) {
    $file_path = '../uploads/assignments/' . $assignment['file_path'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
}

// Delete submission files
$submissions_query = "SELECT file_path FROM assignment_submissions WHERE assignment_id = ?";
$stmt = $conn->prepare($submissions_query);
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$submissions = $stmt->get_result();

while ($sub = $submissions->fetch_assoc()) {
    if ($sub['file_path']) {
        $sub_file_path = '../uploads/submissions/' . $sub['file_path'];
        if (file_exists($sub_file_path)) {
            unlink($sub_file_path);
        }
    }
}

// Delete assignment (CASCADE will delete submissions)
$delete_query = "DELETE FROM assignments WHERE id = ? AND teacher_id = ?";
$stmt = $conn->prepare($delete_query);
$stmt->bind_param("ii", $assignment_id, $teacher_id);

if ($stmt->execute()) {
    header('Location: assignments.php?success=deleted');
} else {
    header('Location: assignments.php?error=delete_failed');
}
exit;
?>