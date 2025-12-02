<?php
// teacher/add_marks.php
require_once '../db.php';
checkRole(['teacher']);

$user = getCurrentUser();
$teacher_id = $user['id'];

// Get parameters
$subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;
$section = isset($_GET['section']) ? sanitize($_GET['section']) : '';
$year = isset($_GET['year']) ? intval($_GET['year']) : 1;
$semester = isset($_GET['semester']) ? intval($_GET['semester']) : 1;
$academic_year = isset($_GET['academic_year']) ? sanitize($_GET['academic_year']) : '';

if (!$subject_id || !$section) {
    header("Location: manage_marks.php");
    exit();
}

// Get subject details
$subject_query = "SELECT * FROM subjects WHERE id = ?";
$stmt = $conn->prepare($subject_query);
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$subject = $stmt->get_result()->fetch_assoc();

// Get exam types
$exam_types_query = "SELECT * FROM exam_types WHERE is_active = 1 ORDER BY display_order";
$exam_types = $conn->query($exam_types_query);

// Get students in the section
$students_query = "SELECT DISTINCT s.* 
                   FROM students s
                   JOIN classes c ON s.class_id = c.id
                   WHERE c.section = ? 
                   AND s.year = ? 
                   AND s.is_active = 1
                   ORDER BY s.roll_number";
$stmt = $conn->prepare($students_query);
$stmt->bind_param("si", $section, $year);
$stmt->execute();
$students = $stmt->get_result();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_marks'])) {
    $exam_type_id = intval($_POST['exam_type_id']);
    $exam_date = sanitize($_POST['exam_date']);
    $max_marks = floatval($_POST['max_marks']);
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    
    $marks_data = $_POST['marks'];
    $success_count = 0;
    
    foreach ($marks_data as $student_id => $marks_obtained) {
        if ($marks_obtained === '' || $marks_obtained === null) continue;
        
        $student_id = intval($student_id);
        $marks_obtained = floatval($marks_obtained);
        $remarks = isset($_POST['remarks'][$student_id]) ? sanitize($_POST['remarks'][$student_id]) : '';
        
        // ✅ Calculate percentage and grade
        $percentage = ($marks_obtained / $max_marks) * 100;
        
        // Calculate grade based on percentage
        if ($percentage >= 90) {
            $grade = 'O';
        } elseif ($percentage >= 75) {
            $grade = 'A+';
        } elseif ($percentage >= 60) {
            $grade = 'B+';
        } elseif ($percentage >= 40) {
            $grade = 'C';
        } else {
            $grade = 'F';
        }
        
        // Insert or update marks with percentage and grade
        $query = "INSERT INTO paper_marks 
                  (student_id, subject_id, exam_type_id, teacher_id, marks_obtained, max_marks, 
                   percentage, grade, remarks, year, semester, academic_year, exam_date, is_published)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                  ON DUPLICATE KEY UPDATE
                  marks_obtained = VALUES(marks_obtained),
                  max_marks = VALUES(max_marks),
                  percentage = VALUES(percentage),
                  grade = VALUES(grade),
                  remarks = VALUES(remarks),
                  exam_date = VALUES(exam_date),
                  is_published = VALUES(is_published),
                  updated_at = CURRENT_TIMESTAMP";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iiiiddssiisssi", $student_id, $subject_id, $exam_type_id, $teacher_id,
                          $marks_obtained, $max_marks, $percentage, $grade, $remarks, 
                          $year, $semester, $academic_year, $exam_date, $is_published);
        
        if ($stmt->execute()) {
            $success_count++;
        }
    }
    
    $_SESSION['success'] = "✅ Marks saved successfully for $success_count students!";
    header("Location: add_marks.php?subject_id=$subject_id&section=" . urlencode($section) . "&year=$year&semester=$semester&academic_year=" . urlencode($academic_year));
    exit();
}

// Get existing marks if exam type is selected
$existing_marks = [];
if (isset($_GET['exam_type_id'])) {
    $exam_type_id = intval($_GET['exam_type_id']);
    $marks_query = "SELECT * FROM paper_marks 
                    WHERE subject_id = ? 
                    AND exam_type_id = ? 
                    AND year = ? 
                    AND semester = ? 
                    AND academic_year = ?";
    $stmt = $conn->prepare($marks_query);
    $stmt->bind_param("iiiis", $subject_id, $exam_type_id, $year, $semester, $academic_year);
    $stmt->execute();
    $marks_result = $stmt->get_result();
    
    while ($row = $marks_result->fetch_assoc()) {
        $existing_marks[$row['student_id']] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Marks - <?php echo htmlspecialchars($subject['subject_name']); ?></title>
    <link rel="icon" href="../Nit_logo.png" type="image/svg+xml" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar {
            background: rgba(26, 31, 58, 0.95);
            backdrop-filter: blur(20px);
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }
        .navbar h1 { color: white; font-size: 24px; }
        .navbar a {
            color: white;
            text-decoration: none;
            background: linear-gradient(135deg, #667eea, #764ba2);
            padding: 10px 20px;
            border-radius: 10px;
            margin-left: 10px;
        }
        .main-content {
            padding: 40px;
            max-width: 1600px;
            margin: 0 auto;
        }
        .container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            padding: 40px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
        }
        h2 {
            font-size: 28px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 30px;
        }
        .subject-info {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .info-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .info-item span {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .info-item strong {
            font-size: 18px;
            color: #667eea;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
        }
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        .marks-table-container {
            overflow-x: auto;
            margin-top: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead {
            background: linear-gradient(135deg, #667eea, #764ba2);
            position: sticky;
            top: 0;
        }
        thead th {
            padding: 15px 10px;
            color: white;
            text-align: left;
            font-weight: 600;
        }
        tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s;
        }
        tbody tr:hover {
            background: rgba(102, 126, 234, 0.05);
        }
        tbody td {
            padding: 15px 10px;
        }
        .marks-input {
            width: 120px;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            text-align: center;
        }
        .marks-input:focus {
            border-color: #667eea;
            outline: none;
        }
        .remarks-input {
            width: 100%;
            padding: 8px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 13px;
        }
        .btn {
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        .btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        .actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            justify-content: space-between;
            align-items: center;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }
    </style>
    <script>
        function fillAllMarks() {
            const maxMarks = document.getElementById('max_marks').value;
            const inputs = document.querySelectorAll('.marks-input');
            inputs.forEach(input => {
                if (input.value === '') {
                    input.value = maxMarks;
                }
            });
        }
        
        function validateForm() {
            const examType = document.getElementById('exam_type_id').value;
            const examDate = document.getElementById('exam_date').value;
            
            if (!examType) {
                alert('Please select an exam type');
                return false;
            }
            
            if (!examDate) {
                alert('Please select exam date');
                return false;
            }
            
            return confirm('Are you sure you want to save these marks?');
        }
    </script>
</head>
<body>
    <nav class="navbar">
        <h1>📝 Add Marks</h1>
        <div>
            <a href="manage_marks.php">← Back</a>
            <a href="index.php">🏠 Dashboard</a>
        </div>
    </nav>

    <div class="main-content">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-success">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <div class="container">
            <h2>📚 <?php echo htmlspecialchars($subject['subject_name']); ?></h2>
            
            <div class="subject-info">
                <div class="info-item">
                    <span>Subject Code</span>
                    <strong><?php echo htmlspecialchars($subject['subject_code']); ?></strong>
                </div>
                <div class="info-item">
                    <span>Section</span>
                    <strong><?php echo htmlspecialchars($section); ?></strong>
                </div>
                <div class="info-item">
                    <span>Year / Semester</span>
                    <strong><?php echo $year; ?> Year / Sem <?php echo $semester; ?></strong>
                </div>
                <div class="info-item">
                    <span>Academic Year</span>
                    <strong><?php echo htmlspecialchars($academic_year); ?></strong>
                </div>
                <div class="info-item">
                    <span>Total Students</span>
                    <strong><?php echo $students->num_rows; ?></strong>
                </div>
            </div>

            <form method="POST" onsubmit="return validateForm()">
                <div class="form-row">
                    <div class="form-group">
                        <label for="exam_type_id">Exam Type *</label>
                        <select name="exam_type_id" id="exam_type_id" required onchange="window.location.href='add_marks.php?subject_id=<?php echo $subject_id; ?>&section=<?php echo urlencode($section); ?>&year=<?php echo $year; ?>&semester=<?php echo $semester; ?>&academic_year=<?php echo urlencode($academic_year); ?>&exam_type_id=' + this.value">
                            <option value="">Select Exam Type</option>
                            <?php 
                            $exam_types->data_seek(0);
                            while ($exam = $exam_types->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $exam['id']; ?>" 
                                        <?php echo (isset($_GET['exam_type_id']) && $_GET['exam_type_id'] == $exam['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($exam['exam_name']); ?> (Max: <?php echo $exam['max_marks']; ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="exam_date">Exam Date *</label>
                        <input type="date" name="exam_date" id="exam_date" 
                               value="<?php echo isset($existing_marks) && !empty($existing_marks) ? current($existing_marks)['exam_date'] : date('Y-m-d'); ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="max_marks">Maximum Marks *</label>
                        <input type="number" name="max_marks" id="max_marks" 
                               value="<?php echo isset($_GET['exam_type_id']) && isset($exam) ? ($exam['max_marks'] ?? 100) : 100; ?>" 
                               min="1" max="200" required step="0.01">
                    </div>
                </div>

                <?php if (isset($_GET['exam_type_id'])): ?>
                    <div class="marks-table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Sr. No.</th>
                                    <th>Roll No</th>
                                    <th>Student Name</th>
                                    <th>Marks Obtained (Out of <?php echo isset($exam) ? $exam['max_marks'] : 100; ?>)</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $sr_no = 1;
                                $students->data_seek(0);
                                while ($student = $students->fetch_assoc()): 
                                    $current_marks = isset($existing_marks[$student['id']]) ? $existing_marks[$student['id']] : null;
                                ?>
                                    <tr>
                                        <td><strong><?php echo $sr_no++; ?></strong></td>
                                        <td><strong><?php echo htmlspecialchars($student['roll_number']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                        <td>
                                            <input type="number" 
                                                   name="marks[<?php echo $student['id']; ?>]" 
                                                   class="marks-input"
                                                   value="<?php echo $current_marks ? $current_marks['marks_obtained'] : ''; ?>"
                                                   min="0" 
                                                   max="<?php echo isset($exam) ? $exam['max_marks'] : 100; ?>" 
                                                   step="0.01"
                                                   placeholder="Enter marks">
                                        </td>
                                        <td>
                                            <input type="text" 
                                                   name="remarks[<?php echo $student['id']; ?>]" 
                                                   class="remarks-input"
                                                   value="<?php echo $current_marks ? htmlspecialchars($current_marks['remarks']) : ''; ?>"
                                                   placeholder="Optional remarks">
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="actions">
                        <div>
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_published" value="1">
                                <span>Publish marks (students & parents can view)</span>
                            </label>
                        </div>
                        <div style="display: flex; gap: 15px;">
                            <button type="button" class="btn btn-primary" onclick="fillAllMarks()">
                                🎯 Fill All with Max Marks
                            </button>
                            <button type="submit" name="save_marks" class="btn btn-success">
                                💾 Save Marks
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 60px; color: #999;">
                        <p style="font-size: 48px; margin-bottom: 20px;">📝</p>
                        <p>Please select an exam type to add marks</p>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</body>
</html>