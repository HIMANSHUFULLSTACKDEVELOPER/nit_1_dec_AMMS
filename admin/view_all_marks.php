<?php
require_once '../db.php';
checkRole(['admin']);

$user = getCurrentUser();

// Get filter parameters
$selected_department = $_GET['department_id'] ?? '';
$selected_year = $_GET['year'] ?? '';
$selected_semester = $_GET['semester'] ?? '';
$selected_academic_year = $_GET['academic_year'] ?? '';
$selected_exam_type = $_GET['exam_type_id'] ?? '';
$selected_section = $_GET['section'] ?? '';

// Get all departments
$departments_query = "SELECT * FROM departments ORDER BY dept_name";
$departments = $conn->query($departments_query);

// Get all academic years
$years_query = "SELECT DISTINCT academic_year FROM paper_marks ORDER BY academic_year DESC";
$academic_years = $conn->query($years_query);

// Get all exam types
$exam_types_query = "SELECT * FROM exam_types WHERE is_active = 1 ORDER BY display_order";
$exam_types = $conn->query($exam_types_query);

// Get all sections
$sections_query = "SELECT DISTINCT section FROM classes WHERE section IS NOT NULL ORDER BY section";
$sections = $conn->query($sections_query);

// Initialize data structures
$students_data = [];
$subjects_list = [];
$overall_stats = [
    'total_students' => 0,
    'passed_students' => 0,
    'failed_students' => 0,
    'total_backlogs' => 0
];

// If filters are applied, fetch the data
if ($selected_section && $selected_academic_year && $selected_exam_type) {
    // Build query to get all marks for the filtered section
    $query = "SELECT pm.*, 
              s.id as student_id, s.roll_number, s.full_name as student_name,
              sub.subject_code, sub.subject_name, sub.id as subject_id,
              d.dept_name
              FROM paper_marks pm
              JOIN students s ON pm.student_id = s.id
              JOIN subjects sub ON pm.subject_id = sub.id
              LEFT JOIN departments d ON s.department_id = d.id
              LEFT JOIN classes c ON s.class_id = c.id
              WHERE c.section = ?
              AND pm.academic_year = ?
              AND pm.exam_type_id = ?";
    
    $params = [$selected_section, $selected_academic_year, $selected_exam_type];
    $types = "ssi";
    
    if ($selected_department) {
        $query .= " AND s.department_id = ?";
        $params[] = $selected_department;
        $types .= "i";
    }
    
    if ($selected_year) {
        $query .= " AND pm.year = ?";
        $params[] = $selected_year;
        $types .= "i";
    }
    
    if ($selected_semester) {
        $query .= " AND pm.semester = ?";
        $params[] = $selected_semester;
        $types .= "i";
    }
    
    $query .= " ORDER BY s.roll_number, sub.subject_name";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $marks_result = $stmt->get_result();
    
    // Process the data
    while ($mark = $marks_result->fetch_assoc()) {
        $student_id = $mark['student_id'];
        
        // Initialize student if not exists
        if (!isset($students_data[$student_id])) {
            $students_data[$student_id] = [
                'roll_number' => $mark['roll_number'],
                'name' => $mark['student_name'],
                'department' => $mark['dept_name'],
                'subjects' => [],
                'total_marks_obtained' => 0,
                'total_max_marks' => 0,
                'backlogs' => 0
            ];
        }
        
        // Add subject marks
        $subject_id = $mark['subject_id'];
        $students_data[$student_id]['subjects'][$subject_id] = [
            'code' => $mark['subject_code'],
            'name' => $mark['subject_name'],
            'marks_obtained' => $mark['marks_obtained'],
            'max_marks' => $mark['max_marks'],
            'percentage' => $mark['percentage'],
            'grade' => $mark['grade']
        ];
        
        // Track unique subjects
        if (!isset($subjects_list[$subject_id])) {
            $subjects_list[$subject_id] = [
                'code' => $mark['subject_code'],
                'name' => $mark['subject_name']
            ];
        }
        
        // Calculate totals
        $students_data[$student_id]['total_marks_obtained'] += $mark['marks_obtained'];
        $students_data[$student_id]['total_max_marks'] += $mark['max_marks'];
        
        // Count backlogs (failed subjects)
        if ($mark['percentage'] < 40) {
            $students_data[$student_id]['backlogs']++;
        }
    }
    
    // Calculate percentages and result status
    foreach ($students_data as $student_id => &$student) {
        if ($student['total_max_marks'] > 0) {
            $student['percentage'] = ($student['total_marks_obtained'] / $student['total_max_marks']) * 100;
        } else {
            $student['percentage'] = 0;
        }
        
        $student['result'] = ($student['backlogs'] == 0) ? 'PASS' : 'FAIL';
        
        // Update overall stats
        $overall_stats['total_students']++;
        if ($student['result'] == 'PASS') {
            $overall_stats['passed_students']++;
        } else {
            $overall_stats['failed_students']++;
            $overall_stats['total_backlogs'] += $student['backlogs'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Section-wise Marks Report</title>
    <link rel="icon" href="../Nit_logo.png" type="image/png" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
        }

        .header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 40px;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .stat-card h4 {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #333;
        }

        .stat-card.success .stat-value { color: #28a745; }
        .stat-card.danger .stat-value { color: #dc3545; }
        .stat-card.warning .stat-value { color: #ffc107; }

        /* Filter Section */
        .filter-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .filter-section h3 {
            margin-bottom: 20px;
            color: #333;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }

        .filter-group select {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .filter-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .required-label::after {
            content: " *";
            color: #dc3545;
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        /* Report Card */
        .report-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .report-header {
            padding: 20px 30px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            font-size: 1.3rem;
            font-weight: 600;
        }

        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
        }

        .marks-table {
            width: 100%;
            border-collapse: collapse;
        }

        .marks-table thead {
            background: #f8f9fa;
        }

        .marks-table th {
            padding: 15px 10px;
            text-align: center;
            font-weight: 600;
            color: #333;
            border: 1px solid #dee2e6;
            font-size: 0.9rem;
        }

        .marks-table td {
            padding: 12px 10px;
            border: 1px solid #e9ecef;
            color: #555;
            text-align: center;
        }

        .marks-table tbody tr:hover {
            background: #f8f9fa;
        }

        .student-info {
            text-align: left !important;
            font-weight: 600;
        }

        .subject-cell {
            min-width: 80px;
        }

        .grade-excellent { color: #28a745; font-weight: bold; }
        .grade-good { color: #17a2b8; font-weight: bold; }
        .grade-average { color: #ffc107; font-weight: bold; }
        .grade-poor { color: #dc3545; font-weight: bold; }

        .result-pass {
            background: #d4edda;
            color: #155724;
            padding: 5px 12px;
            border-radius: 6px;
            font-weight: bold;
        }

        .result-fail {
            background: #f8d7da;
            color: #721c24;
            padding: 5px 12px;
            border-radius: 6px;
            font-weight: bold;
        }

        .backlog-count {
            background: #fff3cd;
            color: #856404;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
        }

        .empty-state {
            text-align: center;
            padding: 60px;
            color: #999;
        }

        .empty-state svg {
            font-size: 64px;
            margin-bottom: 20px;
        }

        /* Responsive */
        @media screen and (max-width: 1199px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media screen and (max-width: 767px) {
            .header h1 {
                font-size: 1.5rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }
            
            .filter-grid {
                grid-template-columns: 1fr;
            }
            
            .marks-table {
                font-size: 0.85rem;
            }
            
            .marks-table th,
            .marks-table td {
                padding: 8px 5px;
            }
        }

        @media print {
            body { background: white; padding: 0; }
            .btn, .filter-section, .header { display: none; }
            .report-card { box-shadow: none; }
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Section-wise Comprehensive Marks Report</h1>
            <p>Detailed student performance analysis with subject-wise breakdown</p>
        </div>

        <?php if (!empty($students_data)): ?>
        <div class="stats-grid">
            <div class="stat-card">
                <h4>👥 Total Students</h4>
                <div class="stat-value"><?php echo $overall_stats['total_students']; ?></div>
            </div>
            <div class="stat-card success">
                <h4>✅ Passed</h4>
                <div class="stat-value"><?php echo $overall_stats['passed_students']; ?></div>
            </div>
            <div class="stat-card danger">
                <h4>❌ Failed</h4>
                <div class="stat-value"><?php echo $overall_stats['failed_students']; ?></div>
            </div>
            <div class="stat-card warning">
                <h4>📚 Total Backlogs</h4>
                <div class="stat-value"><?php echo $overall_stats['total_backlogs']; ?></div>
            </div>
        </div>
        <?php endif; ?>

        <div class="filter-section">
            <h3>🔍 Report Filters</h3>
            <?php if (!$selected_section || !$selected_academic_year || !$selected_exam_type): ?>
            <div class="alert">
                ⚠️ <strong>Required:</strong> Please select Section, Academic Year, and Exam Type to generate the report.
            </div>
            <?php endif; ?>
            
            <form method="GET" action="">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label>Department</label>
                        <select name="department_id">
                            <option value="">All Departments</option>
                            <?php 
                            $departments->data_seek(0);
                            while ($dept = $departments->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $dept['id']; ?>"
                                        <?php echo $selected_department == $dept['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['dept_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label class="required-label">Section</label>
                        <select name="section" required>
                            <option value="">Select Section</option>
                            <?php 
                            if ($sections && $sections->num_rows > 0) {
                                $sections->data_seek(0);
                                while ($section = $sections->fetch_assoc()): 
                            ?>
                                <option value="<?php echo htmlspecialchars($section['section']); ?>"
                                        <?php echo $selected_section === $section['section'] ? 'selected' : ''; ?>>
                                    Section <?php echo htmlspecialchars($section['section']); ?>
                                </option>
                            <?php 
                                endwhile;
                            }
                            ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label class="required-label">Academic Year</label>
                        <select name="academic_year" required>
                            <option value="">Select Academic Year</option>
                            <?php 
                            $academic_years->data_seek(0);
                            while ($year = $academic_years->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $year['academic_year']; ?>"
                                        <?php echo $selected_academic_year === $year['academic_year'] ? 'selected' : ''; ?>>
                                    <?php echo $year['academic_year']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label class="required-label">Exam Type</label>
                        <select name="exam_type_id" required>
                            <option value="">Select Exam Type</option>
                            <?php 
                            $exam_types->data_seek(0);
                            while ($exam = $exam_types->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $exam['id']; ?>"
                                        <?php echo $selected_exam_type == $exam['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($exam['exam_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Year</label>
                        <select name="year">
                            <option value="">All Years</option>
                            <?php for ($i = 1; $i <= 4; $i++): ?>
                                <option value="<?php echo $i; ?>"
                                        <?php echo $selected_year == $i ? 'selected' : ''; ?>>
                                    Year <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Semester</label>
                        <select name="semester">
                            <option value="">All Semesters</option>
                            <?php for ($i = 1; $i <= 8; $i++): ?>
                                <option value="<?php echo $i; ?>"
                                        <?php echo $selected_semester == $i ? 'selected' : ''; ?>>
                                    Semester <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">🔍 Generate Report</button>
                    <a href="section_report.php" class="btn btn-secondary">🔄 Clear All</a>
                    <a href="index.php" class="btn btn-info">🏠 Dashboard</a>
                </div>
            </form>
        </div>

        <?php if (!empty($students_data)): ?>
        <div class="report-card">
            <div class="report-header">
                📋 Detailed Marks Report - Section <?php echo htmlspecialchars($selected_section); ?>
                (<?php echo count($students_data); ?> students)
            </div>

            <div class="table-responsive">
                <table class="marks-table">
                    <thead>
                        <tr>
                            <th rowspan="2">Roll No</th>
                            <th rowspan="2">Student Name</th>
                            <th rowspan="2">Department</th>
                            <?php foreach ($subjects_list as $subject): ?>
                                <th colspan="2"><?php echo htmlspecialchars($subject['code']); ?></th>
                            <?php endforeach; ?>
                            <th rowspan="2">Total<br>Marks</th>
                            <th rowspan="2">Percentage</th>
                            <th rowspan="2">Backlogs</th>
                            <th rowspan="2">Result</th>
                        </tr>
                        <tr>
                            <?php foreach ($subjects_list as $subject): ?>
                                <th>Marks</th>
                                <th>Grade</th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students_data as $student): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($student['roll_number']); ?></strong></td>
                                <td class="student-info"><?php echo htmlspecialchars($student['name']); ?></td>
                                <td><?php echo htmlspecialchars($student['department']); ?></td>
                                
                                <?php foreach ($subjects_list as $subject_id => $subject): ?>
                                    <?php if (isset($student['subjects'][$subject_id])): ?>
                                        <?php $sub_data = $student['subjects'][$subject_id]; ?>
                                        <td class="subject-cell">
                                            <?php echo $sub_data['marks_obtained']; ?>/<?php echo $sub_data['max_marks']; ?>
                                        </td>
                                        <td class="<?php 
                                            $grade = $sub_data['grade'];
                                            if ($grade === 'O' || $grade === 'A+' || $grade === 'A') echo 'grade-excellent';
                                            elseif ($grade === 'B+' || $grade === 'B') echo 'grade-good';
                                            elseif ($grade === 'C') echo 'grade-average';
                                            else echo 'grade-poor';
                                        ?>">
                                            <?php echo $sub_data['grade']; ?>
                                        </td>
                                    <?php else: ?>
                                        <td colspan="2">-</td>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                
                                <td><strong><?php echo $student['total_marks_obtained']; ?>/<?php echo $student['total_max_marks']; ?></strong></td>
                                <td><strong><?php echo number_format($student['percentage'], 2); ?>%</strong></td>
                                <td>
                                    <?php if ($student['backlogs'] > 0): ?>
                                        <span class="backlog-count"><?php echo $student['backlogs']; ?></span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="<?php echo $student['result'] == 'PASS' ? 'result-pass' : 'result-fail'; ?>">
                                        <?php echo $student['result']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div style="text-align: center; margin: 40px 0;">
            <button onclick="window.print()" class="btn btn-success">🖨️ Print Report</button>
            <button onclick="exportToExcel()" class="btn btn-info">📊 Export to Excel</button>
        </div>
        <?php elseif ($selected_section && $selected_academic_year && $selected_exam_type): ?>
        <div class="report-card">
            <div class="empty-state">
                <div style="font-size: 64px; margin-bottom: 20px;">📝</div>
                <h3>No Data Found</h3>
                <p>No marks found for the selected filters. Please check if marks have been uploaded for this section.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
    function exportToExcel() {
        // Create CSV header
        let csv = 'Roll No,Student Name,Department,';
        
        // Add subject headers
        const subjectHeaders = [];
        document.querySelectorAll('.marks-table thead tr:first-child th[colspan="2"]').forEach(th => {
            const subjectCode = th.textContent.trim();
            subjectHeaders.push(subjectCode + ' - Marks');
            subjectHeaders.push(subjectCode + ' - Grade');
        });
        csv += subjectHeaders.join(',') + ',Total Marks,Percentage,Backlogs,Result\n';
        
        // Add data rows
        document.querySelectorAll('.marks-table tbody tr').forEach(row => {
            const cells = row.querySelectorAll('td');
            const rowData = [];
            
            cells.forEach(cell => {
                let text = cell.textContent.trim().replace(/\n/g, ' ');
                rowData.push('"' + text.replace(/"/g, '""') + '"');
            });
            
            csv += rowData.join(',') + '\n';
        });
        
        // Download CSV
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'section_marks_report_' + new Date().toISOString().slice(0,10) + '.csv';
        a.click();
        window.URL.revokeObjectURL(url);
    }
    </script>
</body>
</html>