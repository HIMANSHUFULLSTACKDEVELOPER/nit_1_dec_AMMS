<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../db.php';
checkRole(['admin']);

$user = getCurrentUser();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_class'])) {
        try {
            $class_name = sanitize($_POST['class_name']);
            $department_id = intval($_POST['department_id']);
            $year = intval($_POST['year']);
            $section = sanitize($_POST['section']);
            $teacher_id = intval($_POST['teacher_id']);
            $semester = intval($_POST['semester']);
            $academic_year = sanitize($_POST['academic_year']);
            
            // Always create a new class entry (allows same section with different teachers)
            $stmt = $conn->prepare("INSERT INTO classes (class_name, department_id, year, section, teacher_id, semester, academic_year) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("siisiss", $class_name, $department_id, $year, $section, $teacher_id, $semester, $academic_year);
            
            if ($stmt->execute()) {
                $success = "Class added successfully!";
            } else {
                $error = "Error adding class: " . $conn->error;
            }
            $stmt->close();
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['delete_class'])) {
        try {
            $class_id = intval($_POST['class_id']);
            
            $stmt = $conn->prepare("DELETE FROM classes WHERE id = ?");
            $stmt->bind_param("i", $class_id);
            
            if ($stmt->execute()) {
                $success = "Class deleted successfully!";
            } else {
                $error = "Error deleting class: " . $conn->error;
            }
            $stmt->close();
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Get selected year filter (default to current academic year)
$current_year = date('Y');
$selected_year = isset($_GET['year']) ? sanitize($_GET['year']) : $current_year . '-' . ($current_year + 1);

// Get all unique academic years from database
$years_query = "SELECT DISTINCT academic_year FROM classes ORDER BY academic_year DESC";
$years_result = $conn->query($years_query);
$available_years = [];
while ($year_row = $years_result->fetch_assoc()) {
    $available_years[] = $year_row['academic_year'];
}

// If no years in database, add current and previous years
if (empty($available_years)) {
    $available_years = [
        $current_year . '-' . ($current_year + 1),
        ($current_year - 1) . '-' . $current_year,
        ($current_year - 2) . '-' . ($current_year - 1)
    ];
}
// ============================================
// REPLACE LINES 73-105 in manage_classes.php
// ============================================

// Get selected year filter (default to current academic year)
$current_year = date('Y');
$selected_year = isset($_GET['year']) ? sanitize($_GET['year']) : $current_year . '-' . ($current_year + 1);

// Get all unique academic years from database
$years_query = "SELECT DISTINCT academic_year FROM classes ORDER BY academic_year DESC";
$years_result = $conn->query($years_query);
$available_years = [];
while ($year_row = $years_result->fetch_assoc()) {
    $available_years[] = $year_row['academic_year'];
}

// If no years in database, add current and previous years
if (empty($available_years)) {
    $available_years = [
        $current_year . '-' . ($current_year + 1),
        ($current_year - 1) . '-' . $current_year,
        ($current_year - 2) . '-' . ($current_year - 1)
    ];
}

// CORRECTED QUERY - Fixed to show students for selected academic year only
$classes_query = "SELECT 
    c.id,
    c.class_name,
    c.department_id,
    c.year,
    c.section,
    c.semester,
    c.academic_year,
    c.teacher_id,
    d.dept_name,
    u.full_name as teacher_name,
    COALESCE(
        (SELECT COUNT(DISTINCT s.id)
         FROM students s
         INNER JOIN classes c2 ON s.class_id = c2.id
         WHERE c2.section = c.section
         AND c2.year = c.year
         AND c2.semester = c.semester
         AND c2.academic_year = c.academic_year
        ), 0
    ) as student_count
FROM classes c
LEFT JOIN departments d ON c.department_id = d.id
LEFT JOIN users u ON c.teacher_id = u.id
WHERE c.academic_year = ?
ORDER BY c.section, c.year, c.semester, u.full_name";

$stmt = $conn->prepare($classes_query);
$stmt->bind_param("s", $selected_year);
$stmt->execute();
$classes = $stmt->get_result();

// Get total count for selected year
$count_query = "SELECT COUNT(*) as total FROM classes WHERE academic_year = ?";
$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param("s", $selected_year);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_classes = $count_result->fetch_assoc()['total'];
// Get departments
$departments = $conn->query("SELECT * FROM departments ORDER BY dept_name");

// Get teachers
$teachers = $conn->query("SELECT id, full_name, department_id FROM users WHERE role = 'teacher' AND is_active = 1 ORDER BY full_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Classes - Admin</title>
    <link rel="stylesheet" href="manage_classes_style.css">
    <link rel="icon" href="../Nit_logo.png" type="image/svg+xml" />
    <style>
        * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

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
    border-bottom: 2px solid rgba(255, 255, 255, 0.1);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.navbar h1 {
    color: white;
    font-size: 24px;
    font-weight: 700;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 25px;
    color: white;
}

.main-content {
    padding: 40px;
    max-width: 1600px;
    margin: 0 auto;
}

.page-header {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    padding: 30px 40px;
    border-radius: 20px;
    margin-bottom: 30px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.page-header h2 {
    font-size: 32px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

/* Year Filter Styles */
.year-filter-container {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    padding: 25px 30px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    margin-bottom: 30px;
}

.year-filter-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.year-filter-header h3 {
    font-size: 22px;
    color: #333;
    display: flex;
    align-items: center;
    gap: 10px;
}

.year-buttons {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.year-btn {
    padding: 12px 28px;
    border-radius: 12px;
    border: 2px solid #e0e0e0;
    background: white;
    color: #333;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-block;
}

.year-btn:hover {
    border-color: #667eea;
    background: rgba(102, 126, 234, 0.1);
    transform: translateY(-2px);
}

.year-btn.active {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-color: transparent;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.stats-card {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 15px 25px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    gap: 12px;
    font-weight: 600;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.stats-card span {
    font-size: 24px;
}

.table-container {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #f0f0f0;
}

th {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    font-weight: 600;
}

.badge {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.badge-success { 
    background: #d4edda; 
    color: #155724; 
}

.badge-danger { 
    background: #f8d7da; 
    color: #721c24; 
}

.badge-info { 
    background: #d1ecf1; 
    color: #0c5460; 
}

.btn {
    padding: 12px 24px;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s;
    display: inline-block;
    border: none;
    cursor: pointer;
    font-size: 14px;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-danger {
    background: linear-gradient(135deg, #ff6b6b, #ee5a5a);
    color: white;
}

.btn-warning {
    background: #ffc107;
    color: #000;
}

.btn-sm {
    padding: 8px 16px;
    font-size: 13px;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 2000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(5px);
    animation: fadeIn 0.3s;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-content {
    background: white;
    margin: 3% auto;
    padding: 0;
    border-radius: 25px;
    width: 90%;
    max-width: 1000px;
    max-height: 85vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: slideDown 0.4s;
}

@keyframes slideDown {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.modal-header {
    background: linear-gradient(135deg, #667eea, #764ba2);
    padding: 25px 30px;
    border-radius: 25px 25px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    color: white;
    font-size: 24px;
    font-weight: 700;
}

.close-btn {
    color: white;
    font-size: 32px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
}

.close-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
}

.modal-body {
    padding: 30px;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 20px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
    font-size: 14px;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 12px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.3s;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

#searchInput:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

#searchInput::placeholder {
    color: #999;
}

.form-full-width {
    grid-column: 1 / -1;
}

.alert {
    padding: 15px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    font-weight: 500;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border-left: 4px solid #28a745;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border-left: 4px solid #dc3545;
}

.info-box {
    background: #e3f2fd;
    padding: 15px;
    border-radius: 12px;
    margin-bottom: 20px;
    border-left: 4px solid #2196f3;
}

.tip-box {
    background: #fff3cd;
    padding: 15px;
    border-radius: 12px;
    margin-top: 20px;
    border-left: 4px solid #ffc107;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.empty-state-icon {
    font-size: 80px;
    margin-bottom: 20px;
}

.empty-state h3 {
    font-size: 24px;
    color: #666;
    margin-bottom: 10px;
}

.empty-state p {
    font-size: 16px;
    color: #999;
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .modal-content {
        width: 95%;
        margin: 5% auto;
    }

    .year-buttons {
        gap: 10px;
    }

    .year-btn {
        padding: 10px 20px;
        font-size: 13px;
    }

    .navbar {
        padding: 15px 20px;
    }

    .navbar h1 {
        font-size: 18px;
    }

    .main-content {
        padding: 20px;
    }

    .page-header {
        flex-direction: column;
        gap: 20px;
        text-align: center;
    }

    .year-filter-header {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
    }

    table {
        font-size: 12px;
    }

    th, td {
        padding: 10px;
    }
}
    </style>
</head>
<body>
    <nav class="navbar">
        <div>
            <h1>🎓 NIT AMMS - Manage Classes & Teacher Assignments</h1>
        </div>
        <div class="user-info">
            <a href="index.php" class="btn btn-secondary">← Back</a>
            <span>👨‍💼 <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="page-header">
            <h2>📚 Classes & Teacher Management</h2>
            <button onclick="openModal()" class="btn btn-primary">
                ➕ Add Class with Teacher
            </button>
        </div>

        <!-- Year Filter Section -->
        <div class="year-filter-container">
            <div class="year-filter-header">
                <h3>
                    <span>📅</span>
                    <span>Filter by Academic Year</span>
                </h3>
                <div class="stats-card">
                    <span>📚</span>
                    <div>
                        <div style="font-size: 12px; opacity: 0.9;">Total Classes</div>
                        <div style="font-size: 20px;"><?php echo $total_classes; ?></div>
                    </div>
                </div>
            </div>
            <div class="year-buttons">
                <?php foreach ($available_years as $year): ?>
                    <a href="?year=<?php echo urlencode($year); ?>" 
                       class="year-btn <?php echo ($year === $selected_year) ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($year); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="table-container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h3 style="color: #333; margin: 0;">
                    All Classes & Teacher Assignments - <?php echo htmlspecialchars($selected_year); ?>
                </h3>
                <div style="position: relative; width: 400px;">
                    <input type="text" id="searchInput" onkeyup="searchTable()" 
                           placeholder="🔍 Search by class, section, teacher, department..." 
                           style="width: 100%; padding: 12px 20px; border: 2px solid #e0e0e0; border-radius: 25px; font-size: 14px; transition: all 0.3s;">
                </div>
            </div>

            <?php if ($classes->num_rows > 0): ?>
            <table id="classesTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Class Name</th>
                        <th>Department</th>
                        <th>Year</th>
                        <th>Section</th>
                        <th>Semester</th>
                        <th>Academic Year</th>
                        <th>Teacher</th>
                        <th>Students</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $current_section = '';
                    $section_color = '';
                    while ($class = $classes->fetch_assoc()): 
                        // Highlight same sections with different background
                        $section_key = $class['section'] . '-' . $class['year'] . '-' . $class['semester'];
                        if ($section_key != $current_section) {
                            $current_section = $section_key;
                            $section_color = ($section_color == '#f8f9fa') ? '#ffffff' : '#f8f9fa';
                        }
                    ?>
                    <tr style="background: <?php echo $section_color; ?>">
                        <td><?php echo $class['id']; ?></td>
                        <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                        <td><?php echo htmlspecialchars($class['dept_name']); ?></td>
                        <td><?php echo $class['year']; ?></td>
                        <td><span class="badge badge-info"><?php echo htmlspecialchars($class['section']); ?></span></td>
                        <td><?php echo $class['semester']; ?></td>
                        <td><?php echo htmlspecialchars($class['academic_year']); ?></td>
                        <td><strong><?php echo htmlspecialchars($class['teacher_name']); ?></strong></td>
                        <td><span class="badge badge-success"><?php echo $class['student_count']; ?></span></td>
                        <td>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this class assignment?');">
                                <input type="hidden" name="class_id" value="<?php echo $class['id']; ?>">
                                <button type="submit" name="delete_class" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <div class="tip-box">
                <strong>💡 Tip:</strong> Classes with the same section, year, and semester are grouped with alternating background colors. Each row represents one teacher teaching that class. Student count shows ALL students in that section.
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <h3>No Classes Found</h3>
                <p>There are no classes for academic year <?php echo htmlspecialchars($selected_year); ?></p>
                <p style="margin-top: 10px;">Click "Add Class with Teacher" to add classes for this year.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Class Modal -->
    <div id="addClassModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>➕ Add Class with Teacher Assignment</h3>
                <span class="close-btn" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="info-box">
                    <strong>📌 Note:</strong> You can add the same section multiple times with different teachers. This allows multiple teachers to teach the same class (different subjects).
                </div>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group form-full-width">
                            <label>Class Name:</label>
                            <input type="text" name="class_name" required placeholder="Will be auto-filled based on section, year and teacher">
                        </div>
                        
                        <div class="form-group">
                            <label>Department:</label>
                            <select name="department_id" required>
                                <option value="">-- Select Department --</option>
                                <?php 
                                $departments->data_seek(0);
                                while ($dept = $departments->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $dept['id']; ?>">
                                        <?php echo htmlspecialchars($dept['dept_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Section:</label>
                            <select name="section" required onchange="updateClassName()">
                                <option value="">-- Select Section --</option>
                                <option value="Civil">🏗️ Civil Engineering</option>
                                <option value="Mechanical">⚙️ Mechanical Engineering</option>
                                <option value="CSE-A">💻 Computer Science - A</option>
                                <option value="CSE-B">💻 Computer Science - B</option>
                                <option value="Electrical">⚡ Electrical Engineering</option>
                                <option value="" disabled>──────────────</option>
                                <option value="IT">IT</option>
                                <option value="B">Section B</option>
                                <option value="C">Section C</option>
                                <option value="D">Section D</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Year:</label>
                            <select name="year" required onchange="updateClassName()">
                                <option value="">-- Select --</option>
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Semester:</label>
                            <select name="semester" required>
                                <option value="">-- Select --</option>
                                <?php for($i=1; $i<=8; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Academic Year:</label>
                            <select name="academic_year" required>
                                <option value="">-- Select Year --</option>
                                <?php
                                $start_year = $current_year - 5;
                                $end_year = $current_year + 2;
                                for ($y = $end_year; $y >= $start_year; $y--) {
                                    $year_string = $y . '-' . ($y + 1);
                                    $selected = ($year_string === $selected_year) ? 'selected' : '';
                                    echo "<option value='$year_string' $selected>$year_string</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="form-group form-full-width">
                            <label>Assign Teacher:</label>
                            <select name="teacher_id" required onchange="updateClassName()">
                                <option value="">-- Select Teacher --</option>
                                <?php 
                                $teachers->data_seek(0);
                                while ($teacher = $teachers->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $teacher['id']; ?>">
                                        <?php echo htmlspecialchars($teacher['full_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-full-width" style="text-align: right; margin-top: 20px;">
                            <button type="button" onclick="closeModal()" class="btn btn-secondary" style="margin-right: 10px;">Cancel</button>
                            <button type="submit" name="add_class" class="btn btn-primary">➕ Add Class with Teacher</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div style="background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 50%, #2a3254 100%); position: relative; overflow: hidden; margin-top: 50px;">
        <div style="height: 2px; background: linear-gradient(90deg, #4a9eff, #00d4ff, #4a9eff, #00d4ff); background-size: 200% 100%;"></div>
        <div style="max-width: 1000px; margin: 0 auto; padding: 30px 20px 20px;">
            <div style="background: rgba(255, 255, 255, 0.03); padding: 20px 20px; border-radius: 15px; border: 1px solid rgba(74, 158, 255, 0.15); text-align: center; box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);">
                <p style="color: #ffffff; font-size: 14px; margin: 0 0 12px; font-weight: 500; letter-spacing: 0.5px;">✨ Designed & Developed by</p>
                <a href="https://himanshufullstackdeveloper.github.io/techyugsoftware/" style="display: inline-block; color: #ffffff; font-size: 16px; font-weight: 700; text-decoration: none; padding: 8px 24px; border: 2px solid #4a9eff; border-radius: 30px; background: linear-gradient(135deg, rgba(74, 158, 255, 0.2), rgba(0, 212, 255, 0.2)); box-shadow: 0 3px 12px rgba(74, 158, 255, 0.3); margin-bottom: 15px;">
                    🚀 Techyug Software Pvt. Ltd.
                </a>
                <div style="width: 50%; height: 1px; background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent); margin: 15px auto;"></div>
                <p style="color: #888; font-size: 10px; margin: 0 0 12px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600;">💼 Development Team</p>
                <div style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; margin-top: 12px;">
                    <a href="https://himanshufullstackdeveloper.github.io/portfoilohimanshu/" style="color: #ffffff; font-size: 13px; text-decoration: none; padding: 8px 16px; background: linear-gradient(135deg, rgba(74, 158, 255, 0.25), rgba(0, 212, 255, 0.25)); border-radius: 20px; border: 1px solid rgba(74, 158, 255, 0.4); display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 3px 10px rgba(74, 158, 255, 0.2);">
                        <span style="font-size: 16px;">👨‍💻</span>
                        <span style="font-weight: 600;">Himanshu Patil</span>
                    </a>
                    <a href="https://devpranaypanore.github.io/Pranaypanore-live-.html/" style="color: #ffffff; font-size: 13px; text-decoration: none; padding: 8px 16px; background: linear-gradient(135deg, rgba(74, 158, 255, 0.25), rgba(0, 212, 255, 0.25)); border-radius: 20px; border: 1px solid rgba(74, 158, 255, 0.4); display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 3px 10px rgba(74, 158, 255, 0.2);">
                        <span style="font-size: 16px;">👨‍💻</span>
                        <span style="font-weight: 600;">Pranay Panore</span>
                    </a>
                </div>
                <div style="margin-top: 15px; display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;">
                    <span style="color: #4a9eff; font-size: 10px; padding: 4px 12px; background: rgba(74, 158, 255, 0.1); border-radius: 12px; border: 1px solid rgba(74, 158, 255, 0.3);">Full Stack</span>
                    <span style="color: #00d4ff; font-size: 10px; padding: 4px 12px; background: rgba(0, 212, 255, 0.1); border-radius: 12px; border: 1px solid rgba(0, 212, 255, 0.3);">UI/UX</span>
                    <span style="color: #4a9eff; font-size: 10px; padding: 4px 12px; background: rgba(74, 158, 255, 0.1); border-radius: 12px; border: 1px solid rgba(74, 158, 255, 0.3);">Database</span>
                </div>
            </div>
            <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.1); text-align: center;">
                <p style="color: #888; font-size: 12px; margin: 0 0 10px;">© 2025 NIT AMMS. All rights reserved.</p>
                <p style="color: #666; font-size: 11px; margin: 0;">Made with <span style="color: #ff4757; font-size: 14px;">❤️</span> by Techyug Software</p>
                <div style="margin-top: 15px; display: flex; justify-content: center; gap: 10px;">
                    <a href="#" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; background: rgba(74, 158, 255, 0.1); border: 1px solid rgba(74, 158, 255, 0.3); border-radius: 50%; color: #4a9eff; text-decoration: none; font-size: 14px;">📧</a>
                    <a href="#" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; background: rgba(74, 158, 255, 0.1); border: 1px solid rgba(74, 158, 255, 0.3); border-radius: 50%; color: #4a9eff; text-decoration: none; font-size: 14px;">🌐</a>
                    <a href="#" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; background: rgba(74, 158, 255, 0.1); border: 1px solid rgba(74, 158, 255, 0.3); border-radius: 50%; color: #4a9eff; text-decoration: none; font-size: 14px;">💼</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('addClassModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            document.getElementById('addClassModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('addClassModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        // Auto-fill class name based on selections
        function updateClassName() {
            const year = document.querySelector('select[name="year"]').value;
            const section = document.querySelector('select[name="section"]').value;
            const teacher = document.querySelector('select[name="teacher_id"]');
            const teacherName = teacher.options[teacher.selectedIndex].text;
            const classNameInput = document.querySelector('input[name="class_name"]');
            
            if (year && section && teacher.value) {
                const sectionNames = {
                    'Civil': 'Civil Engineering',
                    'Mechanical': 'Mechanical Engineering',
                    'CSE-A': 'Computer Science & Engineering - A',
                    'CSE-B': 'Computer Science & Engineering - B',
                    'Electrical': 'Electrical Engineering',
                    'IT': 'IT',
                    'B': 'Section B',
                    'C': 'Section C',
                    'D': 'Section D'
                };
                
                const yearMap = {
                    '1': '1st Year',
                    '2': '2nd Year',
                    '3': '3rd Year',
                    '4': '4th Year'
                };
                
                const sectionName = sectionNames[section] || section;
                const yearName = yearMap[year] || year + ' Year';
                
                classNameInput.value = `${sectionName} - ${yearName} (${teacherName})`;
            }
        }

        function searchTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('classesTable');
            const tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                let txtValue = '';
                const td = tr[i].getElementsByTagName('td');
                
                // Search through class name, department, section, teacher columns
                for (let j = 1; j <= 7; j++) {
                    if (td[j]) {
                        txtValue += td[j].textContent || td[j].innerText;
                        txtValue += ' ';
                    }
                }
                
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = '';
                } else {
                    tr[i].style.display = 'none';
                }
            }
        }

        // Close alert messages after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transition = 'opacity 0.5s';
                    setTimeout(() => alert.remove(), 500);
                }, 5000);
            });
        });
    </script>
</body>
</html>