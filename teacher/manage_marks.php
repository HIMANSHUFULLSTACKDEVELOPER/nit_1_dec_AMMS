<?php
// teacher/manage_marks.php
require_once '../db.php';
checkRole(['teacher']);

$user = getCurrentUser();
$teacher_id = $user['id'];

// Get academic year
$current_year = date('Y');
$academic_year = isset($_GET['year']) ? sanitize($_GET['year']) : "$current_year-" . ($current_year + 1);

// Get teacher's subjects with sections
$subjects_query = "SELECT DISTINCT 
    st.id as st_id,
    s.id as subject_id,
    s.subject_code,
    s.subject_name,
    st.section,
    st.year,
    st.semester,
    st.academic_year,
    d.dept_name,
    (SELECT COUNT(DISTINCT students.id) 
     FROM students 
     JOIN classes ON students.class_id = classes.id
     WHERE classes.section = st.section 
     AND students.year = st.year 
     AND students.is_active = 1) as student_count
FROM subject_teachers st
JOIN subjects s ON st.subject_id = s.id
LEFT JOIN departments d ON s.department_id = d.id
WHERE st.teacher_id = ? AND st.academic_year = ? AND st.is_active = 1
ORDER BY st.section, s.subject_name";

$stmt = $conn->prepare($subjects_query);
$stmt->bind_param("is", $teacher_id, $academic_year);
$stmt->execute();
$teacher_subjects = $stmt->get_result();

// Get exam types
$exam_types_query = "SELECT * FROM exam_types WHERE is_active = 1 ORDER BY display_order";
$exam_types = $conn->query($exam_types_query);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_marks'])) {
    $subject_id = intval($_POST['subject_id']);
    $section = sanitize($_POST['section']);
    $exam_type_id = intval($_POST['exam_type_id']);
    $exam_date = sanitize($_POST['exam_date']);
    $year = intval($_POST['year']);
    $semester = intval($_POST['semester']);
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    
    $marks_data = $_POST['marks'];
    $success_count = 0;
    
    foreach ($marks_data as $student_id => $marks_obtained) {
        if ($marks_obtained === '' || $marks_obtained === null) continue;
        
        $student_id = intval($student_id);
        $marks_obtained = floatval($marks_obtained);
        $max_marks = intval($_POST['max_marks']);
        $remarks = isset($_POST['remarks'][$student_id]) ? sanitize($_POST['remarks'][$student_id]) : '';
        
        // Insert or update marks
        $query = "INSERT INTO paper_marks 
                  (student_id, subject_id, exam_type_id, teacher_id, marks_obtained, max_marks, 
                   remarks, year, semester, academic_year, exam_date, is_published)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                  ON DUPLICATE KEY UPDATE
                  marks_obtained = VALUES(marks_obtained),
                  max_marks = VALUES(max_marks),
                  remarks = VALUES(remarks),
                  exam_date = VALUES(exam_date),
                  is_published = VALUES(is_published),
                  updated_at = CURRENT_TIMESTAMP";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iiiidisiissi", $student_id, $subject_id, $exam_type_id, $teacher_id,
                          $marks_obtained, $max_marks, $remarks, $year, $semester, 
                          $academic_year, $exam_date, $is_published);
        
        if ($stmt->execute()) {
            $success_count++;
        }
    }
    
    $_SESSION['success'] = "Marks saved successfully for $success_count students!";
    header("Location: manage_marks.php?year=$academic_year");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Paper Marks - Teacher</title>
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
            margin-bottom: 30px;
        }
        h2 {
            font-size: 28px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 30px;
        }
        .subject-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }
        .subject-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.7));
            padding: 30px;
            border-radius: 20px;
            border: 2px solid rgba(102, 126, 234, 0.3);
            transition: all 0.3s;
        }
        .subject-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.3);
        }
        .subject-card h3 {
            color: #667eea;
            font-size: 22px;
            margin-bottom: 20px;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .btn {
            padding: 12px 24px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            width: 100%;
            margin-top: 15px;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        .marks-form {
            display: none;
        }
        .marks-form.active {
            display: block;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        thead {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }
        thead th {
            padding: 15px;
            color: white;
            text-align: left;
        }
        tbody tr {
            border-bottom: 1px solid #f0f0f0;
        }
        tbody td {
            padding: 12px;
        }
        .marks-input {
            width: 80px;
            padding: 8px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>📚 Manage Paper Marks</h1>
        <a href="index.php">🏠 Dashboard</a>
    </nav>

    <div class="main-content">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-success">
                ✅ <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <!-- Subject Selection -->
        <div class="container">
            <h2>📖 Your Subjects - <?php echo htmlspecialchars($academic_year); ?></h2>
            
            <?php if ($teacher_subjects->num_rows > 0): ?>
                <div class="subject-grid">
                    <?php while ($subject = $teacher_subjects->fetch_assoc()): ?>
                        <div class="subject-card">
                            <h3><?php echo htmlspecialchars($subject['subject_name']); ?></h3>
                            <div class="info-item">
                                <span>Code:</span>
                                <strong><?php echo htmlspecialchars($subject['subject_code']); ?></strong>
                            </div>
                            <div class="info-item">
                                <span>Section:</span>
                                <strong><?php echo htmlspecialchars($subject['section']); ?></strong>
                            </div>
                            <div class="info-item">
                                <span>Year/Sem:</span>
                                <strong><?php echo $subject['year']; ?> Year / Sem <?php echo $subject['semester']; ?></strong>
                            </div>
                            <div class="info-item">
                                <span>Students:</span>
                                <strong><?php echo $subject['student_count']; ?></strong>
                            </div>
                            <a href="add_marks.php?subject_id=<?php echo $subject['subject_id']; ?>&section=<?php echo urlencode($subject['section']); ?>&year=<?php echo $subject['year']; ?>&semester=<?php echo $subject['semester']; ?>&academic_year=<?php echo urlencode($academic_year); ?>" 
                               class="btn btn-primary">
                                📝 Add/Edit Marks
                            </a>
                            <a href="view_marks.php?subject_id=<?php echo $subject['subject_id']; ?>&section=<?php echo urlencode($subject['section']); ?>" 
                               class="btn btn-primary" style="margin-top: 10px; background: linear-gradient(135deg, #28a745, #20c997);">
                                📊 View Reports
                            </a>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 60px; color: #999;">
                    <p style="font-size: 48px; margin-bottom: 20px;">📚</p>
                    <p>No subjects assigned for <?php echo htmlspecialchars($academic_year); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>