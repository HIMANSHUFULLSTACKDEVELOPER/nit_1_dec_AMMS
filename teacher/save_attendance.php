<?php
require_once '../db.php';
checkRole(['teacher']);

// Check if summary functions file exists
if (file_exists('attendance_summary_functions.php')) {
    require_once 'attendance_summary_functions.php';
    $summary_enabled = attendanceSummaryTableExists($conn);
} else {
    $summary_enabled = false;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];
$class_id = intval($_POST['class_id']);
$attendance_date = $_POST['attendance_date'];
$attendance_data = isset($_POST['attendance']) ? $_POST['attendance'] : [];
$remarks_data = isset($_POST['remarks']) ? $_POST['remarks'] : [];

// Verify this class belongs to the teacher
$verify_stmt = $conn->prepare("SELECT id FROM classes WHERE id = ? AND teacher_id = ?");
$verify_stmt->bind_param("ii", $class_id, $teacher_id);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();

if ($verify_result->num_rows === 0) {
    header("Location: index.php?error=unauthorized");
    exit();
}

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $attendance_date)) {
    header("Location: mark_attendance.php?class_id=$class_id&error=invalid_date");
    exit();
}

// Check if attendance data exists
if (empty($attendance_data)) {
    header("Location: mark_attendance.php?class_id=$class_id&date=$attendance_date&error=no_data");
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Delete existing attendance for this class and date
    $delete_stmt = $conn->prepare("DELETE FROM student_attendance WHERE class_id = ? AND attendance_date = ?");
    $delete_stmt->bind_param("is", $class_id, $attendance_date);
    
    if (!$delete_stmt->execute()) {
        throw new Exception("Failed to delete existing records");
    }
    
    // Prepare insert statement
    $insert_stmt = $conn->prepare("INSERT INTO student_attendance (student_id, class_id, attendance_date, status, marked_by, remarks) VALUES (?, ?, ?, ?, ?, ?)");
    
    if (!$insert_stmt) {
        throw new Exception("Failed to prepare insert statement: " . $conn->error);
    }
    
    $success_count = 0;
    $error_count = 0;
    $student_ids_updated = [];
    
    foreach ($attendance_data as $student_id => $status) {
        $student_id = intval($student_id);
        $status = trim($status);
        $remarks = isset($remarks_data[$student_id]) ? trim($remarks_data[$student_id]) : '';
        
        // Validate status
        if (!in_array($status, ['present', 'absent', 'late'])) {
            $error_count++;
            continue;
        }
        
        // Handle empty remarks
        $remarks_value = empty($remarks) ? null : $remarks;
        
        $insert_stmt->bind_param("iissis", $student_id, $class_id, $attendance_date, $status, $teacher_id, $remarks_value);
        
        if ($insert_stmt->execute()) {
            $success_count++;
            $student_ids_updated[] = $student_id;
        } else {
            $error_count++;
            error_log("Failed to insert attendance for student $student_id: " . $insert_stmt->error);
        }
    }
    
    // Check if any records were saved
    if ($success_count === 0) {
        throw new Exception("No attendance records were saved");
    }
    
    // ===== UPDATE ATTENDANCE SUMMARY (if enabled) =====
    $summary_update_count = 0;
    if ($summary_enabled && function_exists('batchUpdateAttendanceSummary')) {
        try {
            // Use batch update for better performance
            $summary_update_count = batchUpdateAttendanceSummary($conn, $attendance_date);
            
            // Log summary update
            error_log("Attendance summary updated for $summary_update_count students on $attendance_date");
        } catch (Exception $e) {
            // Don't fail the whole transaction if summary update fails
            error_log("Warning: Attendance summary update failed: " . $e->getMessage());
        }
    }
    // ================================================
    
    // Commit transaction
    $conn->commit();
    
    // Redirect with success message
    $redirect_url = "index.php?success=attendance_saved&count=$success_count&date=$attendance_date";
    
    if ($summary_update_count > 0) {
        $redirect_url .= "&summary_updated=$summary_update_count";
    }
    
    if ($error_count > 0) {
        $redirect_url .= "&errors=$error_count";
    }
    
    header("Location: $redirect_url");
    exit();
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    
    // Log error
    error_log("Attendance save error: " . $e->getMessage());
    
    // Redirect with error
    header("Location: mark_attendance.php?class_id=$class_id&date=$attendance_date&error=save_failed&message=" . urlencode($e->getMessage()));
    exit();
}
?>