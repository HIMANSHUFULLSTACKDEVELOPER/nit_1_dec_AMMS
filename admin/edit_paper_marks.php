<?php
session_start();
require_once '../includes/db_connect.php';

// Check if user is teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../login.php');
    exit();
}

$teacher_id = $_SESSION['user_id'];
$teacher_dept = $_SESSION['department_id'];

// Handle bulk marks entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_marks'])) {
    $subject_id = $_POST['subject_id'];
    $academic_year_id = $_POST['academic_year_id'];
    $exam_type = $_POST['exam_type'];
    $max_marks = $_POST['max_marks'];
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($_POST['marks'] as $student_id => $marks_data) {
        $marks_obtained = $marks_data['obtained'] !== '' ? $marks_data['obtained'] : null;
        $remarks = $marks_data['remarks'];
        
        // Check if mark already exists
        $check_stmt = $conn->prepare("SELECT id, marks_obtained FROM paper_marks 
                                      WHERE student_id = ? AND subject_id = ? 
                                      AND academic_year_id = ? AND exam_type = ?");
        $check_stmt->bind_param("iiis", $student_id, $subject_id, $academic_year_id, $exam_type);
        $check_stmt->execute();
        $existing = $check_stmt->get_result()->fetch_assoc();
        
        if ($existing) {
            // Update existing mark
            $old_marks = $existing['marks_obtained'];
            $stmt = $conn->prepare("UPDATE paper_marks SET 
                                   marks_obtained = ?, max_marks = ?, remarks = ?, 
                                   is_published = ?, entry_date = CURRENT_TIMESTAMP
                                   WHERE id = ?");
            $stmt->bind_param("disii", $marks_obtained, $max_marks, $remarks, $is_published, $existing['id']);
            
            if ($stmt->execute()) {
                // Add to history if marks changed
                if ($old_marks != $marks_obtained) {
                    $history_stmt = $conn->prepare("INSERT INTO paper_marks_history 
                                                   (paper_mark_id, old_marks, new_marks, changed_by, change_reason)
                                                   VALUES (?, ?, ?, ?, ?)");
                    $change_reason = "Updated by teacher";
                    $history_stmt->bind_param("iddis", $existing['id'], $old_marks, $marks_obtained, $teacher_id, $change_reason);
                    $history_stmt->execute();
                }
                $success_count++;
            } else {
                $error_count++;
            }
        } else {
            // Insert new mark
            $stmt = $conn->prepare("INSERT INTO paper_marks 
                                   (student_id, subject_id, academic_year_id, exam_type, 
                                    marks_obtained, max_marks, remarks, entered_by, is_published)
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiisdisii", $student_id, $subject_id, $academic_year_id, $exam_type,
                             $marks_obtained, $max_marks, $remarks, $teacher_id, $is_published);
            
            if ($stmt->execute()) {
                $success_count++;
            } else {
                $error_count++;
            }
        }
    }
    
    if ($success_count > 0) {
        $success = "Successfully saved marks for $success_count student(s)!";
    }
    if ($error_count > 0) {
        $error = "Failed to save marks for $error_count student(s).";
    }
}

// Get filter values
$selected_subject = $_GET['subject_id'] ?? '';
$selected_academic_year = $_GET['academic_year_id'] ?? '';
$selected_exam_type = $_GET['exam_type'] ?? '';

// Get academic years
$academic_years = $conn->query("SELECT * FROM academic_years WHERE is_active = 1 ORDER BY year_name DESC");

// Get subjects taught by teacher in their department
$subjects = $conn->query("SELECT * FROM subjects 
                         WHERE department_id = $teacher_dept AND is_active = 1 
                         ORDER BY year, semester, subject_name");

// Get students if filters are applied
$students_result = null;
if ($selected_subject && $selected_academic_year && $selected_exam_type) {
    // Get subject details
    $subject_details = $conn->query("SELECT * FROM subjects WHERE id = $selected_subject")->fetch_assoc();
    
    // Get students for this subject
    $students_query = "SELECT s.*, 
                       pm.id as mark_id, pm.marks_obtained, pm.max_marks, pm.remarks, pm.is_published
                       FROM students s
                       LEFT JOIN paper_marks pm ON s.id = pm.student_id 
                           AND pm.subject_id = $selected_subject 
                           AND pm.academic_year_id = $selected_academic_year
                           AND pm.exam_type = '$selected_exam_type'
                       WHERE s.department_id = $teacher_dept 
                       AND s.year = {$subject_details['year']}
                       AND s.semester = {$subject_details['semester']}
                       AND s.is_active = 1
                       ORDER BY s.roll_number";
    
    $students_result = $conn->query($students_query);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Paper Marks - Teacher</title>
    <link rel="stylesheet" href="../includes/paper_marks.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="marks-container">
        <!-- Header -->
        <div class="marks-header">
            <h1><i class="fas fa-edit"></i> Enter/Edit Paper Marks</h1>
            <p>Teacher Dashboard - Enter marks for your subjects</p>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" action="">
                <div class="form-grid">
                    <div class="form-group required">
                        <label for="subject_id">Subject</label>
                        <select name="subject_id" id="subject_id" required>
                            <option value="">-- Select Subject --</option>
                            <?php while ($subject = $subjects->fetch_assoc()): ?>
                                <option value="<?php echo $subject['id']; ?>" 
                                        <?php echo $selected_subject == $subject['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($subject['subject_code'] . ' - ' . $subject['subject_name'] . 
                                              ' (Year ' . $subject['year'] . ', Sem ' . $subject['semester'] . ')'); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group required">
                        <label for="academic_year_id">Academic Year</label>
                        <select name="academic_year_id" id="academic_year_id" required>
                            <option value="">-- Select Year --</option>
                            <?php while ($year = $academic_years->fetch_assoc()): ?>
                                <option value="<?php echo $year['id']; ?>" 
                                        <?php echo $selected_academic_year == $year['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($year['year_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group required">
                        <label for="exam_type">Exam Type</label>
                        <select name="exam_type" id="exam_type" required>
                            <option value="">-- Select Exam --</option>
                            <option value="MST1" <?php echo $selected_exam_type == 'MST1' ? 'selected' : ''; ?>>MST1</option>
                            <option value="MST2" <?php echo $selected_exam_type == 'MST2' ? 'selected' : ''; ?>>MST2</option>
                            <option value="PREBOARD" <?php echo $selected_exam_type == 'PREBOARD' ? 'selected' : ''; ?>>PREBOARD</option>
                        </select>
                    </div>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Load Students
                    </button>
                    <a href="edit_paper_marks.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                    <a href="view_paper_marks.php" class="btn btn-info">
                        <i class="fas fa-eye"></i> View All Marks
                    </a>
                </div>
            </form>
        </div>

        <!-- Marks Entry Form -->
        <?php if ($students_result && $students_result->num_rows > 0): ?>
            <div class="marks-card">
                <h2 class="card-title">Enter Marks for Students</h2>
                
                <form method="POST" action="">
                    <input type="hidden" name="subject_id" value="<?php echo $selected_subject; ?>">
                    <input type="hidden" name="academic_year_id" value="<?php echo $selected_academic_year; ?>">
                    <input type="hidden" name="exam_type" value="<?php echo $selected_exam_type; ?>">
                    
                    <div class="form-group">
                        <label for="max_marks">Maximum Marks</label>
                        <input type="number" name="max_marks" id="max_marks" 
                               value="<?php echo $subject_details['max_marks']; ?>" 
                               min="1" required style="max-width: 200px;">
                    </div>

                    <div class="table-responsive">
                        <table class="marks-table">
                            <thead>
                                <tr>
                                    <th>Roll No</th>
                                    <th>Student Name</th>
                                    <th>Year/Sem</th>
                                    <th>Marks Obtained</th>
                                    <th>Remarks</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($student = $students_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($student['roll_number']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                        <td>Year <?php echo $student['year']; ?> / Sem <?php echo $student['semester']; ?></td>
                                        <td>
                                            <input type="number" 
                                                   name="marks[<?php echo $student['id']; ?>][obtained]" 
                                                   value="<?php echo $student['marks_obtained']; ?>"
                                                   step="0.01" min="0" max="<?php echo $subject_details['max_marks']; ?>"
                                                   placeholder="Enter marks"
                                                   style="width: 120px; padding: 8px;">
                                        </td>
                                        <td>
                                            <input type="text" 
                                                   name="marks[<?php echo $student['id']; ?>][remarks]" 
                                                   value="<?php echo htmlspecialchars($student['remarks']); ?>"
                                                   placeholder="Optional remarks"
                                                   style="width: 200px; padding: 8px;">
                                        </td>
                                        <td>
                                            <?php if ($student['mark_id']): ?>
                                                <span class="status-badge <?php echo $student['is_published'] ? 'published' : 'draft'; ?>">
                                                    <?php echo $student['is_published'] ? 'Published' : 'Draft'; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="status-badge" style="background: #f3f4f6; color: #6b7280;">New</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <div style="margin-top: 25px;">
                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                <input type="checkbox" name="is_published" value="1">
                                <span>Publish marks (make visible to students and parents)</span>
                            </label>
                        </div>

                        <div style="display: flex; gap: 12px; margin-top: 20px;">
                            <button type="submit" name="submit_marks" class="btn btn-success">
                                <i class="fas fa-save"></i> Save All Marks
                            </button>
                            <button type="button" onclick="clearAllMarks()" class="btn btn-secondary">
                                <i class="fas fa-eraser"></i> Clear All
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        <?php elseif ($selected_subject && $selected_academic_year && $selected_exam_type): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No students found for the selected criteria.
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-arrow-up"></i> Please select subject, academic year, and exam type to load students.
            </div>
        <?php endif; ?>
    </div>

    <script>
        function clearAllMarks() {
            if (confirm('Are you sure you want to clear all marks?')) {
                document.querySelectorAll('input[type="number"]').forEach(input => {
                    if (input.name.includes('marks[')) {
                        input.value = '';
                    }
                });
                document.querySelectorAll('input[type="text"]').forEach(input => {
                    if (input.name.includes('remarks')) {
                        input.value = '';
                    }
                });
            }
        }
    </script>
</body>
</html>