<?php
session_start();
require_once '../includes/db_connect.php';

// Check if user is HOD
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
    header('Location: ../login.php');
    exit();
}

$hod_id = $_SESSION['user_id'];
$hod_dept = $_SESSION['department_id'];

// Handle mark verification
if (isset($_POST['verify_mark'])) {
    $mark_id = $_POST['mark_id'];
    
    $stmt = $conn->prepare("UPDATE paper_marks SET verified_by = ? WHERE id = ?");
    $stmt->bind_param("ii", $hod_id, $mark_id);
    
    if ($stmt->execute()) {
        $success = "Mark verified successfully!";
    } else {
        $error = "Failed to verify mark.";
    }
}

// Handle bulk publish
if (isset($_POST['bulk_publish'])) {
    $mark_ids = $_POST['mark_ids'] ?? [];
    
    if (!empty($mark_ids)) {
        $ids = implode(',', array_map('intval', $mark_ids));
        $conn->query("UPDATE paper_marks SET is_published = 1, verified_by = $hod_id WHERE id IN ($ids)");
        $success = "Successfully published " . count($mark_ids) . " marks!";
    }
}

// Get filter values
$filter_academic_year = $_GET['academic_year'] ?? '';
$filter_year = $_GET['year'] ?? '';
$filter_semester = $_GET['semester'] ?? '';
$filter_exam_type = $_GET['exam_type'] ?? '';
$filter_verified = $_GET['verified'] ?? '';

// Build query - only show marks from HOD's department
$query = "SELECT * FROM v_student_paper_marks WHERE department_name = (SELECT name FROM departments WHERE id = $hod_dept)";
$params = [];
$types = "";

if ($filter_academic_year) {
    $query .= " AND academic_year = ?";
    $params[] = $filter_academic_year;
    $types .= "s";
}
if ($filter_year) {
    $query .= " AND year = ?";
    $params[] = $filter_year;
    $types .= "i";
}
if ($filter_semester) {
    $query .= " AND semester = ?";
    $params[] = $filter_semester;
    $types .= "i";
}
if ($filter_exam_type) {
    $query .= " AND exam_type = ?";
    $params[] = $filter_exam_type;
    $types .= "s";
}
if ($filter_verified === '0') {
    $query .= " AND verified_by_name IS NULL";
} elseif ($filter_verified === '1') {
    $query .= " AND verified_by_name IS NOT NULL";
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

// Calculate statistics
$stats_query = "SELECT 
    COUNT(*) as total_entries,
    SUM(CASE WHEN is_published = 1 THEN 1 ELSE 0 END) as published_count,
    SUM(CASE WHEN verified_by_name IS NOT NULL THEN 1 ELSE 0 END) as verified_count,
    AVG(CASE WHEN marks_obtained IS NOT NULL THEN percentage END) as avg_percentage
FROM v_student_paper_marks 
WHERE department_name = (SELECT name FROM departments WHERE id = $hod_dept)";

$stats = $conn->query($stats_query)->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Paper Marks - HOD</title>
    <link rel="stylesheet" href="../includes/paper_marks.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="marks-container">
        <!-- Header -->
        <div class="marks-header">
            <h1><i class="fas fa-user-shield"></i> Manage Department Paper Marks</h1>
            <p>HOD Dashboard - Verify and manage marks for your department</p>
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

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['total_entries']); ?></div>
                <div class="stat-label">Total Entries</div>
            </div>
            <div class="stat-card success">
                <div class="stat-value"><?php echo number_format($stats['verified_count']); ?></div>
                <div class="stat-label">Verified Marks</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-value"><?php echo number_format($stats['total_entries'] - $stats['verified_count']); ?></div>
                <div class="stat-label">Pending Verification</div>
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
                        <label>Year</label>
                        <select name="year">
                            <option value="">All Years</option>
                            <option value="1" <?php echo $filter_year == '1' ? 'selected' : ''; ?>>1st Year</option>
                            <option value="2" <?php echo $filter_year == '2' ? 'selected' : ''; ?>>2nd Year</option>
                            <option value="3" <?php echo $filter_year == '3' ? 'selected' : ''; ?>>3rd Year</option>
                            <option value="4" <?php echo $filter_year == '4' ? 'selected' : ''; ?>>4th Year</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Semester</label>
                        <select name="semester">
                            <option value="">All Semesters</option>
                            <option value="1" <?php echo $filter_semester == '1' ? 'selected' : ''; ?>>Semester 1</option>
                            <option value="2" <?php echo $filter_semester == '2' ? 'selected' : ''; ?>>Semester 2</option>
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

                    <div class="filter-group">
                        <label>Verification Status</label>
                        <select name="verified">
                            <option value="">All</option>
                            <option value="1" <?php echo $filter_verified === '1' ? 'selected' : ''; ?>>Verified</option>
                            <option value="0" <?php echo $filter_verified === '0' ? 'selected' : ''; ?>>Not Verified</option>
                        </select>
                    </div>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <a href="manage_paper_marks.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
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
                <form method="POST" id="bulkForm">
                    <div style="margin-bottom: 15px;">
                        <button type="submit" name="bulk_publish" class="btn btn-success" onclick="return confirm('Publish selected marks?')">
                            <i class="fas fa-check-double"></i> Publish Selected
                        </button>
                        <button type="button" onclick="selectAll()" class="btn btn-secondary">
                            <i class="fas fa-check-square"></i> Select All
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="marks-table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAllCheckbox" onclick="selectAll()"></th>
                                    <th>Roll No</th>
                                    <th>Student Name</th>
                                    <th>Academic Year</th>
                                    <th>Year/Sem</th>
                                    <th>Subject</th>
                                    <th>Exam</th>
                                    <th>Marks</th>
                                    <th>Percentage</th>
                                    <th>Entered By</th>
                                    <th>Verified</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($mark = $marks_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <?php if (!$mark['is_published']): ?>
                                                <input type="checkbox" name="mark_ids[]" value="<?php echo $mark['id']; ?>" class="mark-checkbox">
                                            <?php endif; ?>
                                        </td>
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
                                        <td><small><?php echo htmlspecialchars($mark['entered_by_name']); ?></small></td>
                                        <td>
                                            <?php if ($mark['verified_by_name']): ?>
                                                <span class="status-badge verified">
                                                    <i class="fas fa-check"></i> Verified
                                                </span>
                                            <?php else: ?>
                                                <span class="status-badge draft">Not Verified</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 5px;">
                                                <?php if (!$mark['verified_by_name']): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="mark_id" value="<?php echo $mark['id']; ?>">
                                                        <button type="submit" name="verify_mark" 
                                                                class="btn btn-sm btn-success" 
                                                                title="Verify Mark">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <a href="view_paper_marks.php?student_id=<?php echo $mark['student_id']; ?>" 
                                                   class="btn btn-sm btn-info" title="View All Student Marks">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No marks found with the selected filters.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function selectAll() {
            const checkboxes = document.querySelectorAll('.mark-checkbox');
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
        }
    </script>
</body>
</html>