<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../db.php';
checkRole(['admin']);

// Get selected filters
$department_filter = isset($_GET['department']) ? $_GET['department'] : '';
$class_filter = isset($_GET['class']) ? $_GET['class'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query
$query = "SELECT s.id, s.full_name, s.roll_number, s.email, s.phone,
          d.dept_name, c.class_name, c.section,
          r.objective, r.skills, r.theme, r.updated_at
          FROM students s
          LEFT JOIN departments d ON s.department_id = d.id
          LEFT JOIN classes c ON s.class_id = c.id
          INNER JOIN student_resumes r ON s.id = r.student_id
          WHERE 1=1";

$params = [];
$types = '';

if ($department_filter) {
    $query .= " AND s.department_id = ?";
    $params[] = $department_filter;
    $types .= 'i';
}

if ($class_filter) {
    $query .= " AND s.class_id = ?";
    $params[] = $class_filter;
    $types .= 'i';
}

if ($search) {
    $query .= " AND (s.full_name LIKE ? OR s.roll_number LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

$query .= " ORDER BY s.full_name ASC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$students = $stmt->get_result();

// Get departments for filter
$dept_query = "SELECT id, dept_name FROM departments ORDER BY dept_name";
$departments = $conn->query($dept_query);

// Get classes for filter
$class_query = "SELECT id, class_name, section FROM classes ORDER BY class_name, section";
$classes = $conn->query($class_query);

// Get total resume count
$total_query = "SELECT COUNT(*) as total FROM student_resumes";
$total_result = $conn->query($total_query);
$total_resumes = $total_result->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Resumes - NIT College</title>
    <link rel="icon" href="../Nit_logo.png" type="image/png" />
    <style>
   
       

        .header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header-content h1 {
            color: #667eea;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .header-content p {
            color: #666;
            font-size: 16px;
        }

        .filters {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
        }

        .filters h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-weight: 600;
            color: #555;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .filter-group input,
        .filter-group select {
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-card .number {
            font-size: 36px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
        }

        .stat-card .label {
            color: #666;
            font-size: 14px;
        }

        .students-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .student-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }

        .student-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .student-header {
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }

        .student-name {
            font-size: 20px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .student-roll {
            color: #667eea;
            font-weight: 600;
            font-size: 14px;
        }

        .student-info {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 15px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #666;
        }

        .info-item .icon {
            width: 18px;
            text-align: center;
        }

        .theme-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .theme-professional {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            color: white;
        }

        .theme-modern {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .theme-creative {
            background: linear-gradient(135deg, #f093fb, #f5576c);
            color: white;
        }

        .theme-minimal {
            background: #2c3e50;
            color: white;
        }

        .objective-preview {
            font-size: 13px;
            color: #555;
            line-height: 1.6;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .skills-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-bottom: 15px;
        }

        .skill-tag {
            padding: 4px 10px;
            background: #f0f0f0;
            border-radius: 15px;
            font-size: 12px;
            color: #555;
        }

        .last-updated {
            font-size: 12px;
            color: #999;
            margin-bottom: 15px;
        }

        .no-results {
            background: white;
            padding: 60px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .no-results .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .no-results h3 {
            color: #333;
            font-size: 24px;
            margin-bottom: 10px;
        }

        .no-results p {
            color: #666;
            font-size: 16px;
        }

       /* ========================================
   RESPONSIVE MEDIA QUERIES - OPTIMIZED
   ======================================== */

/* Base Styles - Desktop First */
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
    max-width: 1400px;
    margin: 0 auto;
}

/* ======================================
   LARGE DESKTOP - 1920px and above
   ====================================== */
@media (min-width: 1920px) {
    .container {
        max-width: 1800px;
    }

    .students-grid {
        grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
        gap: 25px;
    }

    .header-content h1 {
        font-size: 40px;
    }

    .student-card {
        padding: 30px;
    }

    .stat-card .number {
        font-size: 42px;
    }
}

/* ======================================
   DESKTOP - 1440px to 1919px
   ====================================== */
@media (min-width: 1440px) and (max-width: 1919px) {
    .container {
        max-width: 1600px;
    }

    .students-grid {
        grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    }

    .header-content h1 {
        font-size: 36px;
    }

    .stat-card .number {
        font-size: 42px;
    }
}

/* ======================================
   LAPTOP - 1025px to 1439px (Default)
   ====================================== */

/* ======================================
   TABLET LANDSCAPE - 768px to 1024px
   ====================================== */
@media (max-width: 1024px) {
    .container {
        padding: 0 15px;
    }

    .students-grid {
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 18px;
    }

    .header {
        padding: 20px;
    }

    .header-content h1 {
        font-size: 28px;
    }

    .filters {
        padding: 20px;
    }

    .stat-card {
        padding: 20px;
    }
}

/* ======================================
   TABLET PORTRAIT - 481px to 768px
   ====================================== */
@media (max-width: 768px) {
    body {
        padding: 10px;
    }

    .container {
        padding: 0;
    }

    /* Header Adjustments */
    .header {
        padding: 20px;
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }

    .header-content h1 {
        font-size: 24px;
    }

    .header-content p {
        font-size: 14px;
    }

    /* Button Full Width */
    .btn {
        width: 100%;
    }

    /* Filter Section */
    .filters {
        padding: 20px;
    }

    .filter-row {
        grid-template-columns: 1fr;
        gap: 15px;
    }

    /* Stats Grid */
    .stats {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }

    .stat-card {
        padding: 20px;
    }

    .stat-card .number {
        font-size: 28px;
    }

    /* Students Grid */
    .students-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }

    .student-card {
        padding: 20px;
    }

    .student-name {
        font-size: 18px;
    }
}

/* ======================================
   MOBILE - 361px to 480px
   ====================================== */
@media (max-width: 480px) {
    body {
        padding: 8px;
    }

    /* Header */
    .header {
        padding: 15px;
        border-radius: 12px;
        margin-bottom: 15px;
    }

    .header-content h1 {
        font-size: 20px;
        margin-bottom: 8px;
    }

    .header-content p {
        font-size: 13px;
    }

    /* Filters */
    .filters {
        padding: 15px;
        border-radius: 12px;
        margin-bottom: 15px;
    }

    .filters h3 {
        font-size: 16px;
        margin-bottom: 12px;
    }

    .filter-group label {
        font-size: 13px;
        margin-bottom: 6px;
    }

    .filter-group input,
    .filter-group select {
        font-size: 14px;
        padding: 10px 12px;
    }

    /* Stats - Single Column */
    .stats {
        grid-template-columns: 1fr;
        gap: 12px;
        margin-bottom: 15px;
    }

    .stat-card {
        padding: 18px;
        border-radius: 12px;
    }

    .stat-card .number {
        font-size: 32px;
    }

    .stat-card .label {
        font-size: 13px;
    }

    /* Student Cards */
    .students-grid {
        gap: 15px;
    }

    .student-card {
        padding: 15px;
        border-radius: 12px;
    }

    .student-name {
        font-size: 17px;
    }

    .student-roll {
        font-size: 13px;
    }

    .info-item {
        font-size: 13px;
    }

    .info-item .icon {
        width: 16px;
    }

    /* Buttons */
    .btn {
        padding: 11px 16px;
        font-size: 14px;
    }

    /* Skills & Theme */
    .skill-tag {
        font-size: 11px;
        padding: 4px 9px;
    }

    .theme-badge {
        font-size: 11px;
        padding: 5px 11px;
    }

    .objective-preview {
        font-size: 12px;
        margin-bottom: 12px;
    }

    .last-updated {
        font-size: 11px;
    }

    /* No Results */
    .no-results {
        padding: 50px 20px;
    }

    .no-results .icon {
        font-size: 56px;
    }

    .no-results h3 {
        font-size: 20px;
    }

    .no-results p {
        font-size: 14px;
    }
}

/* ======================================
   SMALL MOBILE - 320px to 360px
   ====================================== */
@media (max-width: 360px) {
    body {
        padding: 5px;
    }

    /* Header */
    .header {
        padding: 12px;
        margin-bottom: 12px;
    }

    .header-content h1 {
        font-size: 18px;
        margin-bottom: 6px;
    }

    .header-content p {
        font-size: 12px;
    }

    /* Filters */
    .filters {
        padding: 12px;
        margin-bottom: 12px;
    }

    .filters h3 {
        font-size: 15px;
        margin-bottom: 10px;
    }

    .filter-group label {
        font-size: 12px;
    }

    .filter-group input,
    .filter-group select {
        font-size: 13px;
        padding: 8px 10px;
        border-radius: 6px;
    }

    /* Stats */
    .stats {
        gap: 10px;
        margin-bottom: 12px;
    }

    .stat-card {
        padding: 14px;
        border-radius: 10px;
    }

    .stat-card .number {
        font-size: 26px;
    }

    .stat-card .label {
        font-size: 12px;
    }

    /* Student Cards */
    .students-grid {
        gap: 12px;
    }

    .student-card {
        padding: 12px;
        border-radius: 10px;
    }

    .student-name {
        font-size: 15px;
        margin-bottom: 4px;
    }

    .student-roll {
        font-size: 12px;
    }

    .student-header {
        padding-bottom: 12px;
        margin-bottom: 12px;
    }

    .info-item {
        font-size: 12px;
        gap: 6px;
    }

    .info-item .icon {
        width: 14px;
        font-size: 12px;
    }

    /* Buttons */
    .btn {
        padding: 9px 12px;
        font-size: 13px;
        border-radius: 6px;
    }

    /* Skills & Theme */
    .skills-preview {
        gap: 4px;
        margin-bottom: 12px;
    }

    .skill-tag {
        padding: 3px 8px;
        font-size: 10px;
        border-radius: 12px;
    }

    .theme-badge {
        font-size: 10px;
        padding: 4px 9px;
        margin-bottom: 8px;
    }

    .objective-preview {
        font-size: 11px;
        margin-bottom: 10px;
        line-height: 1.5;
    }

    .last-updated {
        font-size: 10px;
        margin-bottom: 12px;
    }

    /* No Results */
    .no-results {
        padding: 40px 15px;
    }

    .no-results .icon {
        font-size: 48px;
        margin-bottom: 15px;
    }

    .no-results h3 {
        font-size: 18px;
        margin-bottom: 8px;
    }

    .no-results p {
        font-size: 13px;
    }
}

/* ======================================
   EXTRA SMALL MOBILE - 280px to 319px
   ====================================== */
@media (max-width: 319px) {
    body {
        padding: 3px;
    }

    .header {
        padding: 10px;
    }

    .header-content h1 {
        font-size: 16px;
    }

    .filters {
        padding: 10px;
    }

    .filters h3 {
        font-size: 14px;
    }

    .stat-card {
        padding: 12px;
    }

    .stat-card .number {
        font-size: 22px;
    }

    .student-card {
        padding: 10px;
    }

    .student-name {
        font-size: 14px;
    }

    .btn {
        padding: 8px 10px;
        font-size: 12px;
    }
}

/* ======================================
   LANDSCAPE MODE - Mobile Devices
   ====================================== */
@media (max-width: 768px) and (orientation: landscape) {
    body {
        padding: 8px;
    }

    .header {
        padding: 15px;
        flex-direction: row;
        align-items: center;
    }

    .header-content h1 {
        font-size: 22px;
    }

    .btn {
        width: auto;
        min-width: 150px;
    }

    .filters {
        padding: 15px;
    }

    .filter-row {
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
    }

    .stats {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }

    .students-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }

    .student-card {
        padding: 12px;
    }

    .student-name {
        font-size: 16px;
    }
}

/* ======================================
   PRINT STYLES
   ====================================== */
@media print {
    body {
        background: white;
        padding: 0;
    }

    .header,
    .filters,
    .btn-secondary {
        display: none;
    }

    .student-card {
        break-inside: avoid;
        box-shadow: none;
        border: 1px solid #ddd;
        margin-bottom: 20px;
    }

    .students-grid {
        display: block;
    }
}

/* ======================================
   HIGH RESOLUTION DISPLAYS
   ====================================== */
@media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
    .student-card,
    .header,
    .filters,
    .stat-card {
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }
}
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-content">
                <h1>📄 Student Resumes</h1>
                <p>View and manage all student resumes</p>
            </div>
            <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>

        <!-- Stats -->
        <div class="stats">
            <div class="stat-card">
                <div class="number"><?php echo $total_resumes; ?></div>
                <div class="label">Total Resumes</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo $students->num_rows; ?></div>
                <div class="label">Filtered Results</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters">
            <h3>🔍 Filter Resumes</h3>
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Department</label>
                        <select name="department">
                            <option value="">All Departments</option>
                            <?php 
                            if ($departments && $departments->num_rows > 0):
                                while($dept = $departments->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $dept['id']; ?>" <?php echo $department_filter == $dept['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['dept_name']); ?>
                                </option>
                            <?php 
                                endwhile;
                            endif;
                            ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Class</label>
                        <select name="class">
                            <option value="">All Classes</option>
                            <?php 
                            if ($classes && $classes->num_rows > 0):
                                while($class = $classes->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $class['id']; ?>" <?php echo $class_filter == $class['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($class['class_name'] . ' - ' . $class['section']); ?>
                                </option>
                            <?php 
                                endwhile;
                            endif;
                            ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Search</label>
                        <input type="text" name="search" placeholder="Name or Roll Number" value="<?php echo htmlspecialchars($search); ?>">
                    </div>

                    <div class="filter-group">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Apply Filters</button>
                    </div>

                    <div class="filter-group">
                        <a href="student_resumes.php" class="btn btn-secondary" style="width: 100%;">Clear Filters</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Students Grid -->
        <?php if ($students->num_rows > 0): ?>
            <div class="students-grid">
                <?php while($student = $students->fetch_assoc()): ?>
                    <div class="student-card">
                        <div class="student-header">
                            <div class="student-name"><?php echo htmlspecialchars($student['full_name']); ?></div>
                            <div class="student-roll">Roll: <?php echo htmlspecialchars($student['roll_number']); ?></div>
                        </div>

                        <div class="student-info">
                            
                            <div class="info-item">
                                <span class="icon">📚</span>
                                <span><?php echo htmlspecialchars($student['dept_name']); ?></span>
                            </div>
                            
                            <div class="info-item">
                                <span class="icon">📱</span>
                                <span><?php echo htmlspecialchars($student['phone']); ?></span>
                            </div>
                        </div>

                        <span class="theme-badge theme-<?php echo htmlspecialchars($student['theme']); ?>">
                            <?php echo ucfirst(htmlspecialchars($student['theme'])); ?> Theme
                        </span>

                        <?php if (!empty($student['objective'])): ?>
                            <div class="objective-preview">
                                <strong>Objective:</strong> <?php echo htmlspecialchars($student['objective']); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($student['skills'])): ?>
                            <div class="skills-preview">
                                <?php
                                $skills = array_slice(array_filter(array_map('trim', explode(',', $student['skills']))), 0, 5);
                                foreach ($skills as $skill):
                                ?>
                                    <span class="skill-tag"><?php echo htmlspecialchars($skill); ?></span>
                                <?php endforeach; ?>
                                <?php if (count(explode(',', $student['skills'])) > 5): ?>
                                    <span class="skill-tag">+more</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="last-updated">
                            Last updated: <?php echo date('M d, Y', strtotime($student['updated_at'])); ?>
                        </div>

                        <a href="view_student_resume.php?id=<?php echo $student['id']; ?>" class="btn btn-primary" style="width: 100%; text-align: center;">
                            👁️ View Full Resume
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <div class="icon">📭</div>
                <h3>No Resumes Found</h3>
                <p>No students have created resumes yet, or no results match your filters.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>