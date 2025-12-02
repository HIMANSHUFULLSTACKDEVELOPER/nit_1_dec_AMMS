<?php
session_start();
require_once '../includes/db_connect.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Handle mark publication toggle
if (isset($_POST['toggle_publish'])) {
    $mark_id = $_POST['mark_id'];
    $is_published = $_POST['is_published'];
    
    $stmt = $conn->prepare("UPDATE paper_marks SET is_published = ? WHERE id = ?");
    $stmt->bind_param("ii", $is_published, $mark_id);
    
    if ($stmt->execute()) {
        $success = "Mark publication status updated successfully!";
    } else {
        $error = "Failed to update publication status.";
    }
}

// Handle mark deletion
if (isset($_POST['delete_mark'])) {
    $mark_id = $_POST['mark_id'];
    
    $stmt = $conn->prepare("DELETE FROM paper_marks WHERE id = ?");
    $stmt->bind_param("i", $mark_id);
    
    if ($stmt->execute()) {
        $success = "Mark deleted successfully!";
    } else {
        $error = "Failed to delete mark.";
    }
}

// Get filter values
$filter_academic_year = $_GET['academic_year'] ?? '';
$filter_department = $_GET['department'] ?? '';
$filter_year = $_GET['year'] ?? '';
$filter_semester = $_GET['semester'] ?? '';
$filter_exam_type = $_GET['exam_type'] ?? '';
$filter_published = $_GET['published'] ?? '';

// Build query
$query = "SELECT * FROM v_student_paper_marks WHERE 1=1";
$params = [];
$types = "";

if ($filter_academic_year) {
    $query .= " AND academic_year = ?";
    $params[] = $filter_academic_year;
    $types .= "s";
}
if ($filter_department) {
    $query .= " AND department_name = ?";
    $params[] = $filter_department;
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
if ($filter_published !== '') {
    $query .= " AND is_published = ?";
    $params[] = $filter_published;
    $types .= "i";
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
$departments = $conn->query("SELECT DISTINCT name FROM departments ORDER BY name");

// Calculate statistics
$stats_query = "SELECT 
    COUNT(*) as total_entries,
    SUM(CASE WHEN is_published = 1 THEN 1 ELSE 0 END) as published_count,
    SUM(CASE WHEN is_published = 0 THEN 1 ELSE 0 END) as draft_count,
    AVG(CASE WHEN marks_obtained IS NOT NULL THEN percentage END) as avg_percentage
FROM v_student_paper_marks";

$stats = $conn->query($stats_query)->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Paper Marks - Admin</title>
    <link rel="stylesheet" href="../includes/paper_marks.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="marks-container">
        <!-- Header -->
        <div class="marks-header">
            <h1><i class="fas fa-clipboard-list"></i> Manage Paper Marks</h1>
            <p>Admin Dashboard - View and manage all paper marks across departments</p>
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
                <div class="stat-value"><?php echo number_format($stats['published_count']); ?></div>
                <div class="stat-label">Published Marks</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-value"><?php echo number_format($stats['draft_count']); ?></div>
                <div class="stat-label">Draft Marks</div>
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
                        <label>Department</label>
                        <select name="department">
                            <option value="">All Departments</option>
                            <?php while ($dept = $departments->fetch_assoc()): ?>
                                <option value="<?php echo $dept['name']; ?>" 
                                    <?php echo $filter_department == $dept['name'] ? 'selected' : ''; ?>>
                                    <?php echo $dept['name']; ?>
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
                        <label>Status</label>
                        <select name="published">
                            <option value="">All Status</option>
                            <option value="1" <?php echo $filter_published === '1' ? 'selected' : ''; ?>>Published</option>
                            <option value="0" <?php echo $filter_published === '0' ? 'selected' : ''; ?>>Draft</option>
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
                    <a href="edit_paper_marks.php" class="btn btn-success">
                        <i class="fas fa-plus"></i> Add New Marks
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
                                <th>Actions</th>
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
                                    <td>
                                        <div style="display: flex; gap: 5px;">
                                            <a href="edit_paper_marks.php?id=<?php echo $mark['id']; ?>" 
                                               class="btn btn-sm btn-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <form method="POST" style="display: inline;" 
                                                  onsubmit="return confirm('Change publication status?');">
                                                <input type="hidden" name="mark_id" value="<?php echo $mark['id']; ?>">
                                                <input type="hidden" name="is_published" 
                                                       value="<?php echo $mark['is_published'] ? 0 : 1; ?>">
                                                <button type="submit" name="toggle_publish" 
                                                        class="btn btn-sm <?php echo $mark['is_published'] ? 'btn-warning' : 'btn-success'; ?>" 
                                                        title="<?php echo $mark['is_published'] ? 'Unpublish' : 'Publish'; ?>">
                                                    <i class="fas fa-<?php echo $mark['is_published'] ? 'eye-slash' : 'eye'; ?>"></i>
                                                </button>
                                            </form>
                                            
                                            <form method="POST" style="display: inline;" 
                                                  onsubmit="return confirm('Are you sure you want to delete this mark?');">
                                                <input type="hidden" name="mark_id" value="<?php echo $mark['id']; ?>">
                                                <button type="submit" name="delete_mark" 
                                                        class="btn btn-sm btn-danger" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
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