<?php

function updateAttendanceSummary($conn, $student_id, $class_id, $attendance_date) {
    try {
        // Extract month and year from attendance date
        $date_obj = new DateTime($attendance_date);
        $month = (int)$date_obj->format('m');
        $year = (int)$date_obj->format('Y');
        
        // Get class_id from student if not provided
        if (empty($class_id)) {
            $class_query = "SELECT class_id FROM students WHERE id = ?";
            $stmt = $conn->prepare($class_query);
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $class_id = $row['class_id'];
            } else {
                error_log("Student ID $student_id not found");
                return false;
            }
        }
        
        // Calculate statistics for this student/class/month
        $stats_query = "SELECT 
                        COUNT(*) as total_days,
                        SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) as present_days,
                        SUM(CASE WHEN sa.status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                        SUM(CASE WHEN sa.status = 'late' THEN 1 ELSE 0 END) as late_days
                        FROM student_attendance sa
                        WHERE sa.student_id = ? 
                        AND MONTH(sa.attendance_date) = ? 
                        AND YEAR(sa.attendance_date) = ?";
        
        $stmt = $conn->prepare($stats_query);
        $stmt->bind_param("iii", $student_id, $month, $year);
        $stmt->execute();
        $stats = $stmt->get_result()->fetch_assoc();
        
        if ($stats['total_days'] > 0) {
            $attendance_percentage = round(($stats['present_days'] / $stats['total_days']) * 100, 2);
            
            // Insert or update summary
            $upsert_query = "INSERT INTO attendance_summary 
                            (student_id, class_id, month, year, total_days, present_days, absent_days, late_days, attendance_percentage)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE
                            total_days = VALUES(total_days),
                            present_days = VALUES(present_days),
                            absent_days = VALUES(absent_days),
                            late_days = VALUES(late_days),
                            attendance_percentage = VALUES(attendance_percentage),
                            last_updated = CURRENT_TIMESTAMP";
            
            $stmt = $conn->prepare($upsert_query);
            $stmt->bind_param("iiiiiiid", 
                $student_id, 
                $class_id, 
                $month, 
                $year, 
                $stats['total_days'],
                $stats['present_days'],
                $stats['absent_days'],
                $stats['late_days'],
                $attendance_percentage
            );
            
            return $stmt->execute();
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Failed to update attendance summary: " . $e->getMessage());
        return false;
    }
}

/**
 * Batch update for multiple students
 * More efficient when marking attendance for an entire class
 * 
 * @param mysqli $conn Database connection
 * @param string $attendance_date Date in Y-m-d format
 * @return int Number of students updated
 */
function batchUpdateAttendanceSummary($conn, $attendance_date) {
    try {
        $date_obj = new DateTime($attendance_date);
        $month = (int)$date_obj->format('m');
        $year = (int)$date_obj->format('Y');
        
        // Get all students who have attendance on this date
        $students_query = "SELECT DISTINCT sa.student_id, s.class_id
                          FROM student_attendance sa
                          INNER JOIN students s ON sa.student_id = s.id
                          WHERE sa.attendance_date = ?";
        
        $stmt = $conn->prepare($students_query);
        $stmt->bind_param("s", $attendance_date);
        $stmt->execute();
        $students = $stmt->get_result();
        
        $success_count = 0;
        while ($row = $students->fetch_assoc()) {
            if (updateAttendanceSummary($conn, $row['student_id'], $row['class_id'], $attendance_date)) {
                $success_count++;
            }
        }
        
        return $success_count;
        
    } catch (Exception $e) {
        error_log("Batch update failed: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get attendance summary for a student
 * 
 * @param mysqli $conn Database connection
 * @param int $student_id Student ID
 * @param int|null $class_id Optional class filter
 * @param int|null $month Optional month filter (1-12)
 * @param int|null $year Optional year filter
 * @return mysqli_result|false Result set or false on error
 */
function getStudentAttendanceSummary($conn, $student_id, $class_id = null, $month = null, $year = null) {
    try {
        $query = "SELECT ats.*, c.class_name, c.section
                  FROM attendance_summary ats
                  JOIN classes c ON ats.class_id = c.id
                  WHERE ats.student_id = ?";
        $params = [$student_id];
        $types = "i";
        
        if ($class_id !== null) {
            $query .= " AND ats.class_id = ?";
            $params[] = $class_id;
            $types .= "i";
        }
        
        if ($month !== null) {
            $query .= " AND ats.month = ?";
            $params[] = $month;
            $types .= "i";
        }
        
        if ($year !== null) {
            $query .= " AND ats.year = ?";
            $params[] = $year;
            $types .= "i";
        }
        
        $query .= " ORDER BY ats.year DESC, ats.month DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        return $stmt->get_result();
        
    } catch (Exception $e) {
        error_log("Failed to get summary: " . $e->getMessage());
        return false;
    }
}

/**
 * Get class-wide attendance summary
 * 
 * @param mysqli $conn Database connection
 * @param int $class_id Class ID
 * @param int|null $month Optional month filter
 * @param int|null $year Optional year filter
 * @return mysqli_result|false Result set or false on error
 */
function getClassAttendanceSummary($conn, $class_id, $month = null, $year = null) {
    try {
        $query = "SELECT ats.*, s.roll_number, s.full_name
                  FROM attendance_summary ats
                  JOIN students s ON ats.student_id = s.id
                  WHERE ats.class_id = ? AND s.is_active = 1";
        $params = [$class_id];
        $types = "i";
        
        if ($month !== null) {
            $query .= " AND ats.month = ?";
            $params[] = $month;
            $types .= "i";
        }
        
        if ($year !== null) {
            $query .= " AND ats.year = ?";
            $params[] = $year;
            $types .= "i";
        }
        
        $query .= " ORDER BY s.roll_number";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        return $stmt->get_result();
        
    } catch (Exception $e) {
        error_log("Failed to get class summary: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if summary table exists
 * 
 * @param mysqli $conn Database connection
 * @return bool True if table exists
 */
function attendanceSummaryTableExists($conn) {
    $result = $conn->query("SHOW TABLES LIKE 'attendance_summary'");
    return $result && $result->num_rows > 0;
}
?>