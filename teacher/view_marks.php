<?php
// teacher/view_marks.php
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
$exam_type_id = isset($_GET['exam_type_id']) ? intval($_GET['exam_type_id']) : 0;

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

// Get exam types for this subject
$exam_types_query = "SELECT DISTINCT et.* 
                     FROM exam_types et
                     JOIN paper_marks pm ON et.id = pm.exam_type_id
                     WHERE pm.subject_id = ? AND et.is_active = 1
                     ORDER BY et.display_order";
$stmt = $conn->prepare($exam_types_query);
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$exam_types = $stmt->get_result();

// Get students and their marks
if ($exam_type_id > 0) {
    $marks_query = "SELECT 
                        s.id,
                        s.roll_number,
                        s.full_name,
                        s.email,
                        pm.marks_obtained,
                        pm.max_marks,
                        pm.percentage,
                        pm.grade,
                        pm.remarks,
                        pm.exam_date,
                        pm.is_published
                    FROM students s
                    JOIN classes c ON s.class_id = c.id
                    LEFT JOIN paper_marks pm ON s.id = pm.student_id 
                        AND pm.subject_id = ? 
                        AND pm.exam_type_id = ?
                    WHERE c.section = ? 
                    AND s.year = ? 
                    AND s.is_active = 1
                    ORDER BY s.roll_number";
    
    $stmt = $conn->prepare($marks_query);
    $stmt->bind_param("iisi", $subject_id, $exam_type_id, $section, $year);
    $stmt->execute();
    $students = $stmt->get_result();
    
    // Calculate statistics
    $total_students = 0;
    $students_with_marks = 0;
    $total_marks = 0;
    $passed_students = 0;
    $failed_students = 0;
    $highest_marks = 0;
    $lowest_marks = 100;
    
    $temp_students = [];
    while ($row = $students->fetch_assoc()) {
        $temp_students[] = $row;
        $total_students++;
        
        if ($row['marks_obtained'] !== null) {
            $students_with_marks++;
            $total_marks += $row['percentage'];
            
            if ($row['percentage'] >= 40) {
                $passed_students++;
            } else {
                $failed_students++;
            }
            
            if ($row['percentage'] > $highest_marks) {
                $highest_marks = $row['percentage'];
            }
            if ($row['percentage'] < $lowest_marks) {
                $lowest_marks = $row['percentage'];
            }
        }
    }
    
    $average_percentage = $students_with_marks > 0 ? ($total_marks / $students_with_marks) : 0;
    $pass_percentage = $total_students > 0 ? (($passed_students / $students_with_marks) * 100) : 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marks Report - <?php echo htmlspecialchars($subject['subject_name']); ?></title>
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
            max-width: 1800px;
            margin: 0 auto;
        }
        .container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            padding: 40px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
        }
        h2 {
            font-size: 28px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 30px;
        }
        .subject-header {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
        }
        .subject-header h3 {
            color: #667eea;
            font-size: 24px;
            margin-bottom: 15px;
        }
        .header-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .info-box {
            background: white;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .info-box label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .info-box strong {
            display: block;
            font-size: 20px;
            color: #667eea;
            margin-top: 5px;
        }
        
        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card.green {
            background: linear-gradient(135deg, #28a745, #20c997);
        }
        .stat-card.red {
            background: linear-gradient(135deg, #dc3545, #c82333);
        }
        .stat-card.orange {
            background: linear-gradient(135deg, #fd7e14, #e55300);
        }
        .stat-card.blue {
            background: linear-gradient(135deg, #007bff, #0056b3);
        }
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 10px;
        }
        .stat-value {
            font-size: 36px;
            font-weight: bold;
        }
        
        /* Exam Type Selector */
        .exam-selector {
            margin: 30px 0;
        }
        .exam-selector label {
            display: block;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }
        .exam-selector select {
            width: 100%;
            max-width: 500px;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 16px;
            background: white;
        }
        
        /* Table Styles */
        .marks-table-container {
            overflow-x: auto;
            margin-top: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        thead {
            background: linear-gradient(135deg, #667eea, #764ba2);
            position: sticky;
            top: 0;
            z-index: 10;
        }
        thead th {
            padding: 18px 12px;
            color: white;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s;
        }
        tbody tr:hover {
            background: rgba(102, 126, 234, 0.05);
        }
        tbody td {
            padding: 16px 12px;
            font-size: 14px;
        }
        
        /* Grade Badges */
        .grade-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 13px;
            text-align: center;
            min-width: 60px;
        }
        .grade-O {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        .grade-A {
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
        }
        .grade-B {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
        }
        .grade-C {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: white;
        }
        .grade-F {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }
        
        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-published {
            background: #d4edda;
            color: #155724;
        }
        .status-draft {
            background: #fff3cd;
            color: #856404;
        }
        .status-absent {
            background: #f8d7da;
            color: #721c24;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        .btn {
            padding: 12px 28px;
            border-radius: 12px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 15px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        .btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }
        
        /* Print Styles */
        @media print {
            body {
                background: white;
            }
            .navbar, .action-buttons, .exam-selector {
                display: none;
            }
            .container {
                box-shadow: none;
                padding: 20px;
            }
        }
        
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #999;
        }
        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
    </style>
    <script>
        function printReport() {
            window.print();
        }
        
        function changeExamType(select) {
            const examTypeId = select.value;
            if (examTypeId) {
                window.location.href = 'view_marks.php?subject_id=<?php echo $subject_id; ?>&section=<?php echo urlencode($section); ?>&year=<?php echo $year; ?>&semester=<?php echo $semester; ?>&academic_year=<?php echo urlencode($academic_year); ?>&exam_type_id=' + examTypeId;
            }
        }
    </script>
</head>
<body>
    <nav class="navbar">
        <h1>📊 Marks Report</h1>
        <div>
            <a href="manage_marks.php">← Back</a>
            <a href="index.php">🏠 Dashboard</a>
        </div>
    </nav>

    <div class="main-content">
        <div class="container">
            <div class="subject-header">
                <h3>📚 <?php echo htmlspecialchars($subject['subject_name']); ?></h3>
                <div class="header-info">
                    <div class="info-box">
                        <label>Subject Code</label>
                        <strong><?php echo htmlspecialchars($subject['subject_code']); ?></strong>
                    </div>
                    <div class="info-box">
                        <label>Section</label>
                        <strong><?php echo htmlspecialchars($section); ?></strong>
                    </div>
                    <div class="info-box">
                        <label>Year / Semester</label>
                        <strong><?php echo $year; ?> Year / Sem <?php echo $semester; ?></strong>
                    </div>
                    <div class="info-box">
                        <label>Academic Year</label>
                        <strong><?php echo htmlspecialchars($academic_year); ?></strong>
                    </div>
                </div>
            </div>

            <div class="exam-selector">
                <label>📝 Select Exam Type</label>
                <select onchange="changeExamType(this)">
                    <option value="">-- Select Exam Type --</option>
                    <?php 
                    $exam_types->data_seek(0);
                    while ($exam = $exam_types->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $exam['id']; ?>" 
                                <?php echo ($exam_type_id == $exam['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($exam['exam_name']); ?> (Max: <?php echo $exam['max_marks']; ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <?php if ($exam_type_id > 0 && isset($temp_students)): ?>
                
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Total Students</div>
                        <div class="stat-value"><?php echo $total_students; ?></div>
                    </div>
                    <div class="stat-card green">
                        <div class="stat-label">✅ Passed</div>
                        <div class="stat-value"><?php echo $passed_students; ?></div>
                    </div>
                    <div class="stat-card red">
                        <div class="stat-label">❌ Failed</div>
                        <div class="stat-value"><?php echo $failed_students; ?></div>
                    </div>
                    <div class="stat-card orange">
                        <div class="stat-label">📊 Pass %</div>
                        <div class="stat-value"><?php echo number_format($pass_percentage, 1); ?>%</div>
                    </div>
                    <div class="stat-card blue">
                        <div class="stat-label">📈 Average %</div>
                        <div class="stat-value"><?php echo number_format($average_percentage, 1); ?>%</div>
                    </div>
                    <div class="stat-card green">
                        <div class="stat-label">🏆 Highest %</div>
                        <div class="stat-value"><?php echo number_format($highest_marks, 1); ?>%</div>
                    </div>
                    <div class="stat-card red">
                        <div class="stat-label">📉 Lowest %</div>
                        <div class="stat-value"><?php echo $students_with_marks > 0 ? number_format($lowest_marks, 1) : '0'; ?>%</div>
                    </div>
                </div>

                <!-- Marks Table -->
                <div class="marks-table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Sr. No.</th>
                                <th>Roll No</th>
                                <th>Student Name</th>
                                <th>Marks Obtained</th>
                                <th>Max Marks</th>
                                <th>Percentage</th>
                                <th>Grade</th>
                                <th>Status</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $sr_no = 1;
                            foreach ($temp_students as $student): 
                            ?>
                                <tr>
                                    <td><strong><?php echo $sr_no++; ?></strong></td>
                                    <td><strong><?php echo htmlspecialchars($student['roll_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                    <td>
                                        <?php if ($student['marks_obtained'] !== null): ?>
                                            <strong style="font-size: 18px; color: #667eea;">
                                                <?php echo number_format($student['marks_obtained'], 2); ?>
                                            </strong>
                                        <?php else: ?>
                                            <span class="status-badge status-absent">AB</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($student['max_marks'] !== null): ?>
                                            <strong><?php echo $student['max_marks']; ?></strong>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($student['percentage'] !== null): ?>
                                            <strong><?php echo number_format($student['percentage'], 2); ?>%</strong>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($student['grade']): ?>
                                            <span class="grade-badge grade-<?php echo $student['grade']; ?>">
                                                <?php echo $student['grade']; ?>
                                            </span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($student['marks_obtained'] !== null): ?>
                                            <?php if ($student['percentage'] >= 40): ?>
                                                <span class="status-badge status-published">✅ PASS</span>
                                            <?php else: ?>
                                                <span class="status-badge status-absent">❌ FAIL</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="status-badge status-absent">AB</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo $student['remarks'] ? htmlspecialchars($student['remarks']) : '-'; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="action-buttons">
                    <button class="btn btn-success" onclick="printReport()">
                        🖨️ Print Report
                    </button>
                    <a href="add_marks.php?subject_id=<?php echo $subject_id; ?>&section=<?php echo urlencode($section); ?>&year=<?php echo $year; ?>&semester=<?php echo $semester; ?>&academic_year=<?php echo urlencode($academic_year); ?>&exam_type_id=<?php echo $exam_type_id; ?>" 
                       class="btn btn-primary">
                        ✏️ Edit Marks
                    </a>
                </div>

            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📝</div>
                    <h3>No Exam Selected</h3>
                    <p>Please select an exam type to view the marks report</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>