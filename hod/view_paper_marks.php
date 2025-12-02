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

// Get filter values
$filter_subject = $_GET['subject_id'] ?? '';
$filter_academic_year = $_GET['academic_year'] ?? '';
$filter_exam_type = $_GET['exam_type'] ?? '';

// Build query - only show marks from teacher's department
$query = "SELECT * FROM v_student_paper_marks WHERE department_name = (SELECT name FROM departments WHERE id = $teacher_dept)";
$params = [];
$types = "";

if ($filter_subject) {
    $query .= " AND subject_id = ?";
    $params[] = $filter_subject;
    $types .= "i";
}
if ($filter_academic_year) {
    $query .= " AND academic_year = ?";
    $params[] = $filter_academic_year;
    $types .= "s";
}
if ($filter_exam_type) {
    $query .= " AND exam_type = ?";
    $params[] = $filter_exam_type;
    $types .= "s";
}

$query .= " ORDER BY academic_year DESC, year, semester, roll_number, exam_type";

$stmt = $conn->prepare($query);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$marks_result = $stmt->get_result();

// Get filter options
$academic_years = $conn->query("SELECT DISTINCT year_name FROM academic_years ORDER BY year_name DESC");
$subjects = $conn->query("SELECT * FROM subjects WHERE department_id = $teacher_dept ORDER BY year, semester, subject_name");

// Calculate statistics for teacher's department
$stats_query = "SELECT 
    COUNT(*) as total_entries,
    SUM(CASE WHEN is_published = 1 THEN 1 ELSE 0 END) as published_count,
    AVG(CASE WHEN marks_obtained IS NOT NULL THEN percentage END) as avg_percentage
FROM v_student_paper_marks 
WHERE department_name = (SELECT name FROM departments WHERE id = $teacher_dept)";

$stats = $conn->query($stats_query)->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Paper Marks - Teacher</title>
    <link rel="stylesheet" href="../includes/paper_marks.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="marks-container">
        <!-- Header -->
        <div class="marks-header">
            <h1><i class="fas fa-chart-line"></i> View Paper Marks</h1>
            <p>Teacher Dashboard - View marks for your department</p>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['total_entries']); ?></div>
                <div class="stat-label">Total Entries</div>
            </div>
            <div class="stat-card success">
                <div class="stat-value"><?php echo number_format($stats['published_count']); ?></div>
                <div class="stat-label">Published Marks</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['avg_percentage'], 2); ?>%</div>
                <div class="stat-label">Average Percentage</div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" action="">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label>Subject</label>
                        <select name="subject_id">
                            <option value="">All Subjects</option>
                            <?php while ($subject = $subjects->fetch_assoc()): ?>
                                <option value="<?php echo $subject['id']; ?>" 
                                    <?php echo $filter_subject == $subject['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($subject['subject_code'] . ' - ' . $subject['subject_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Academic Year</label>
                        <select name="academic_year">
                            <option value="">All Years</option>
                            <?php while ($year = $academic_years->fetch_assoc()): ?>
                                <option value="<?php echo $year['year_name']; ?>" 
                                    <?php echo $filter_academic_year == $year['year_name'] ? 'selected' : ''; ?>>
                                    <?php echo $year['year_name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Exam Type</label>
                        <select name="exam_type">
                            <option value="">All Exams</option>
                            <option value="MST1" <?php echo $filter_exam_type == 'MST1' ? 'selected' : ''; ?>>MST1</option>
                            <option value="MST2" <?php echo $filter_exam_type == 'MST2' ? 'selected' : ''; ?>>MST2</option>
                            <option value="PREBOARD" <?php echo $filter_exam_type == 'PREBOARD' ? 'selected' : ''; ?>>PREBOARD</option>
                        </select>
                    </div>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <a href="view_paper_marks.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                    <a href="edit_paper_marks.php" class="btn btn-success">
                        <i class="fas fa-edit"></i> Enter Marks
                    </a>
                    <button type="button" onclick="window.print()" class="btn btn-info">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
            </form>
        </div>

        <!-- Marks Table -->
        <div class="marks-card">
            <h2 class="card-title">Paper Marks Records</h2>
            
            <?php if ($marks_result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="marks-table">
                        <thead>
                            <tr>
                                <th>Roll No</th>
                                <th>Student Name</th>
                                <th>Academic Year</th>
                                <th>Year/Sem</th>
                                <th>Subject</th>
                                <th>Exam Type</th>
                                <th>Marks</th>
                                <th>Percentage</th>
                                <th>Status</th>
                                <th>Entry Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($mark = $marks_result->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($mark['roll_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($mark['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($mark['academic_year']); ?></td>
                                    <td>Year <?php echo $mark['year']; ?> / Sem <?php echo $mark['semester']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($mark['subject_code']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($mark['subject_name']); ?></small>
                                    </td>
                                    <td>
                                        <span class="exam-badge <?php echo strtolower($mark['exam_type']); ?>">
                                            <?php echo $mark['exam_type']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($mark['marks_obtained'] !== null): ?>
                                            <strong><?php echo number_format($mark['marks_obtained'], 2); ?></strong> / <?php echo $mark['max_marks']; ?>
                                        <?php else: ?>
                                            <span style="color: #9ca3af;">Not Entered</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($mark['percentage'] !== null): ?>
                                            <?php
                                            $perf_class = 'poor';
                                            if ($mark['percentage'] >= 75) $perf_class = 'excellent';
                                            elseif ($mark['percentage'] >= 60) $perf_class = 'good';
                                            elseif ($mark['percentage'] >= 40) $perf_class = 'average';
                                            ?>
                                            <span class="performance-indicator <?php echo $perf_class; ?>">
                                                <?php echo number_format($mark['percentage'], 2); ?>%
                                            </span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $mark['is_published'] ? 'published' : 'draft'; ?>">
                                            <?php echo $mark['is_published'] ? 'Published' : 'Draft'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($mark['entry_date'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No marks found with the selected filters.
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>