<?php
require_once '../db.php';
// Check if user is student or parent
$is_student = isset($_SESSION['role']) && $_SESSION['role'] === 'student';
$is_parent = isset($_SESSION['role']) && $_SESSION['role'] === 'parent';

if (!$is_student && !$is_parent) {
    header("Location: ../login.php");
    exit;
}

if ($is_student) {
    $student_id = $_SESSION['user_id'];
} else {
    // Parent viewing child's marks
    $student_id = $_SESSION['student_id'];
}

// Get student info
$student_query = "SELECT s.*, d.dept_name, c.section, c.class_name
                  FROM students s
                  LEFT JOIN departments d ON s.department_id = d.id
                  LEFT JOIN classes c ON s.class_id = c.id
                  WHERE s.id = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// Get all marks with subject and exam details
$marks_query = "SELECT pm.*, s.subject_code, s.subject_name, s.credits,
                et.exam_name, et.max_marks as exam_max_marks,
                u.full_name as teacher_name
                FROM paper_marks pm
                JOIN subjects s ON pm.subject_id = s.id
                JOIN exam_types et ON pm.exam_type_id = et.id
                JOIN users u ON pm.teacher_id = u.id
                WHERE pm.student_id = ?
                ORDER BY pm.year DESC, pm.semester DESC, s.subject_name, et.display_order";
$stmt = $conn->prepare($marks_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$all_marks = $stmt->get_result();

// Group marks by academic year, semester and subject
$grouped_marks = [];
while ($mark = $all_marks->fetch_assoc()) {
    $year_key = $mark['academic_year'];
    $sem_key = "Year {$mark['year']} - Semester {$mark['semester']}";
    $subject_key = $mark['subject_code'];
    
    if (!isset($grouped_marks[$year_key])) {
        $grouped_marks[$year_key] = [];
    }
    if (!isset($grouped_marks[$year_key][$sem_key])) {
        $grouped_marks[$year_key][$sem_key] = [];
    }
    if (!isset($grouped_marks[$year_key][$sem_key][$subject_key])) {
        $grouped_marks[$year_key][$sem_key][$subject_key] = [
            'subject_name' => $mark['subject_name'],
            'credits' => $mark['credits'],
            'teacher_name' => $mark['teacher_name'],
            'exams' => []
        ];
    }
    
    $grouped_marks[$year_key][$sem_key][$subject_key]['exams'][] = [
        'exam_name' => $mark['exam_name'],
        'marks_obtained' => $mark['marks_obtained'],
        'max_marks' => $mark['max_marks'],
        'percentage' => $mark['percentage'],
        'grade' => $mark['grade'],
        'remarks' => $mark['remarks']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_student ? 'My Marks' : "Child's Marks"; ?> - NIT AMMS</title>
    <link rel="stylesheet" href="../admin/paper_marks.css">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .marks-header { background: linear-gradient(135deg, #667eea, #764ba2); }
        .academic-year-section { margin: 30px 0; }
        .semester-section { margin: 20px 0; }
        .subject-card { background: white; padding: 25px; border-radius: 15px; margin: 15px 0; 
                       box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .subject-header { border-bottom: 3px solid #667eea; padding-bottom: 15px; margin-bottom: 20px; }
        .subject-title { font-size: 20px; font-weight: 700; color: #2c3e50; }
        .subject-meta { font-size: 14px; color: #666; margin-top: 5px; }
        .exam-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .exam-item { background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
                    padding: 15px; border-radius: 10px; text-align: center; }
        .exam-name { font-weight: 600; color: #667eea; margin-bottom: 10px; }
        .exam-marks { font-size: 28px; font-weight: 700; color: #2c3e50; }
        .exam-grade { font-size: 20px; font-weight: 700; margin-top: 5px; }
        .grade-O { color: #28a745; }
        .grade-A { color: #5cb85c; }
        .grade-B { color: #5bc0de; }
        .grade-C { color: #f0ad4e; }
        .grade-F { color: #d9534f; }
        .no-marks { text-align: center; padding: 60px; color: #999; }
        .stats-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
                        gap: 20px; margin: 30px 0; }
        .stat-box { background: white; padding: 20px; border-radius: 15px; text-align: center; }
        .stat-value { font-size: 36px; font-weight: 700; color: #667eea; }
        .stat-label { font-size: 14px; color: #666; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="marks-container">
        <div class="marks-header">
            <h1>📊 <?php echo $is_student ? 'My Examination Marks' : "Your Child's Marks"; ?></h1>
            <p><?php echo htmlspecialchars($student['full_name']); ?> - <?php echo htmlspecialchars($student['roll_number']); ?></p>
        </div>

        <div class="marks-card">
            <div class="card-title">📋 Student Information</div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; padding: 20px;">
                <div><strong>Name:</strong> <?php echo htmlspecialchars($student['full_name']); ?></div>
                <div><strong>Roll Number:</strong> <?php echo htmlspecialchars($student['roll_number']); ?></div>
                <div><strong>Department:</strong> <?php echo htmlspecialchars($student['dept_name'] ?? 'N/A'); ?></div>
                <div><strong>Section:</strong> <?php echo htmlspecialchars($student['section'] ?? 'N/A'); ?></div>
                <div><strong>Year:</strong> <?php echo $student['year']; ?></div>
                <div><strong>Semester:</strong> <?php echo $student['semester']; ?></div>
            </div>
        </div>

        <?php if (empty($grouped_marks)): ?>
            <div class="no-marks">
                <div style="font-size: 64px; margin-bottom: 20px;">📝</div>
                <h3>No Marks Available Yet</h3>
                <p>Your teachers haven't uploaded any marks yet. Please check back later.</p>
            </div>
        <?php else: ?>
            <?php foreach ($grouped_marks as $academic_year => $semesters): ?>
                <div class="academic-year-section">
                    <h2 style="color: white; text-shadow: 0 2px 10px rgba(0,0,0,0.3); margin-bottom: 20px;">
                        📅 Academic Year: <?php echo htmlspecialchars($academic_year); ?>
                    </h2>
                    
                    <?php foreach ($semesters as $semester_label => $subjects): ?>
                        <div class="semester-section">
                            <h3 style="color: white; text-shadow: 0 2px 10px rgba(0,0,0,0.3);">
                                📚 <?php echo $semester_label; ?>
                            </h3>
                            
                            <?php foreach ($subjects as $subject_code => $subject_data): ?>
                                <div class="subject-card">
                                    <div class="subject-header">
                                        <div class="subject-title">
                                            <?php echo htmlspecialchars($subject_code); ?> - 
                                            <?php echo htmlspecialchars($subject_data['subject_name']); ?>
                                        </div>
                                        <div class="subject-meta">
                                            Credits: <?php echo $subject_data['credits']; ?> | 
                                            Teacher: <?php echo htmlspecialchars($subject_data['teacher_name']); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="exam-grid">
                                        <?php foreach ($subject_data['exams'] as $exam): ?>
                                            <div class="exam-item">
                                                <div class="exam-name"><?php echo htmlspecialchars($exam['exam_name']); ?></div>
                                                <div class="exam-marks">
                                                    <?php echo $exam['marks_obtained']; ?> / <?php echo $exam['max_marks']; ?>
                                                </div>
                                                <div style="font-size: 14px; color: #666; margin-top: 5px;">
                                                    <?php echo number_format($exam['percentage'], 2); ?>%
                                                </div>
                                                <div class="exam-grade grade-<?php echo $exam['grade']; ?>">
                                                    Grade: <?php echo $exam['grade']; ?>
                                                </div>
                                                <?php if ($exam['remarks']): ?>
                                                    <div style="font-size: 12px; color: #888; margin-top: 10px; font-style: italic;">
                                                        "<?php echo htmlspecialchars($exam['remarks']); ?>"
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div style="text-align: center; margin: 40px 0;">
            <a href="<?php echo $is_student ? 'index.php' : '../parent/index.php'; ?>" 
               class="btn btn-primary">
                🏠 Back to Dashboard
            </a>
            <button onclick="window.print()" class="btn btn-info">
                🖨️ Print Marks
            </button>
        </div>
    </div>

    <style>
        @media print {
            body { background: white; }
            .btn, .marks-header { display: none; }
            .subject-card { page-break-inside: avoid; }
        }

        /* ==================== RESET & BASE STYLES ==================== */
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
    line-height: 1.6;
}

/* ==================== CONTAINER ==================== */
.marks-container {
    max-width: 1400px;
    margin: 0 auto;
    animation: fadeIn 0.6s ease-in;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* ==================== HEADER ==================== */
.marks-header {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 40px 30px;
    border-radius: 20px;
    text-align: center;
    margin-bottom: 30px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    position: relative;
    overflow: hidden;
}

.marks-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: pulse 15s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.marks-header h1 {
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 10px;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    position: relative;
    z-index: 1;
}

.marks-header p {
    font-size: 1.2rem;
    opacity: 0.95;
    position: relative;
    z-index: 1;
}

/* ==================== CARDS ==================== */
.marks-card {
    background: white;
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.marks-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 50px rgba(0, 0, 0, 0.18);
}

.card-title {
    font-size: 1.8rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 3px solid #667eea;
}

/* ==================== ACADEMIC YEAR SECTION ==================== */
.academic-year-section {
    margin: 40px 0;
    animation: slideIn 0.5s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.academic-year-section h2 {
    color: white;
    font-size: 2rem;
    font-weight: 700;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    margin-bottom: 25px;
    padding: 15px 25px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 15px;
    backdrop-filter: blur(10px);
}

/* ==================== SEMESTER SECTION ==================== */
.semester-section {
    margin: 30px 0;
}

.semester-section h3 {
    color: white;
    font-size: 1.6rem;
    font-weight: 600;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    margin-bottom: 20px;
    padding: 12px 20px;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 12px;
    backdrop-filter: blur(8px);
}

/* ==================== SUBJECT CARDS ==================== */
.subject-card {
    background: white;
    padding: 30px;
    border-radius: 20px;
    margin: 20px 0;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.subject-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 5px;
    height: 100%;
    background: linear-gradient(135deg, #667eea, #764ba2);
}

.subject-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 50px rgba(102, 126, 234, 0.3);
}

.subject-header {
    border-bottom: 3px solid #667eea;
    padding-bottom: 15px;
    margin-bottom: 25px;
}

.subject-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 8px;
}

.subject-meta {
    font-size: 0.95rem;
    color: #666;
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

/* ==================== EXAM GRID ==================== */
.exam-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.exam-item {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.08), rgba(118, 75, 162, 0.08));
    padding: 25px 20px;
    border-radius: 15px;
    text-align: center;
    transition: all 0.3s ease;
    border: 2px solid transparent;
    position: relative;
    overflow: hidden;
}

.exam-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
    opacity: 0;
    transition: opacity 0.3s ease;
}

.exam-item:hover {
    transform: translateY(-5px);
    border-color: #667eea;
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.2);
}

.exam-item:hover::before {
    opacity: 1;
}

.exam-name {
    font-weight: 600;
    font-size: 1rem;
    color: #667eea;
    margin-bottom: 15px;
    position: relative;
    z-index: 1;
}

.exam-marks {
    font-size: 2.2rem;
    font-weight: 800;
    color: #2c3e50;
    margin: 10px 0;
    position: relative;
    z-index: 1;
}

.exam-grade {
    font-size: 1.4rem;
    font-weight: 700;
    margin-top: 10px;
    position: relative;
    z-index: 1;
}

/* ==================== GRADE COLORS ==================== */
.grade-O { color: #28a745; text-shadow: 0 2px 5px rgba(40, 167, 69, 0.3); }
.grade-A { color: #5cb85c; text-shadow: 0 2px 5px rgba(92, 184, 92, 0.3); }
.grade-B { color: #5bc0de; text-shadow: 0 2px 5px rgba(91, 192, 222, 0.3); }
.grade-C { color: #f0ad4e; text-shadow: 0 2px 5px rgba(240, 173, 78, 0.3); }
.grade-F { color: #d9534f; text-shadow: 0 2px 5px rgba(217, 83, 79, 0.3); }

/* ==================== NO MARKS STATE ==================== */
.no-marks {
    text-align: center;
    padding: 80px 20px;
    background: white;
    border-radius: 20px;
    margin: 30px 0;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.no-marks div {
    font-size: 5rem;
    margin-bottom: 20px;
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-20px); }
}

.no-marks h3 {
    font-size: 1.8rem;
    color: #2c3e50;
    margin-bottom: 15px;
}

.no-marks p {
    font-size: 1.1rem;
    color: #666;
}

/* ==================== BUTTONS ==================== */
.btn {
    display: inline-block;
    padding: 15px 35px;
    margin: 10px;
    font-size: 1rem;
    font-weight: 600;
    text-decoration: none;
    border-radius: 12px;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.btn-primary {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

.btn-info {
    background: linear-gradient(135deg, #00b4db, #0083b0);
    color: white;
}

.btn-info:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 180, 219, 0.4);
}

/* ==================== STATISTICS SUMMARY ==================== */
.stats-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 25px;
    margin: 40px 0;
}

.stat-box {
    background: white;
    padding: 30px 25px;
    border-radius: 20px;
    text-align: center;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    transition: all 0.3s ease;
    border-top: 4px solid #667eea;
}

.stat-box:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 40px rgba(102, 126, 234, 0.25);
}

.stat-value {
    font-size: 2.5rem;
    font-weight: 800;
    color: #667eea;
    margin-bottom: 10px;
}

.stat-label {
    font-size: 1rem;
    color: #666;
    font-weight: 500;
}

/* ==================== PRINT STYLES ==================== */
@media print {
    body {
        background: white;
        padding: 0;
    }
    
    .btn,
    .marks-header::before {
        display: none !important;
    }
    
    .marks-header {
        background: #667eea !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    .subject-card {
        page-break-inside: avoid;
        box-shadow: none;
        border: 1px solid #ddd;
    }
    
    .exam-item {
        page-break-inside: avoid;
    }
    
    .academic-year-section h2,
    .semester-section h3 {
        color: #2c3e50 !important;
        background: #f0f0f0 !important;
    }
}

/* ==================== RESPONSIVE MEDIA QUERIES ==================== */

/* Extra Large Devices (1400px and up) */
@media (min-width: 1400px) {
    .marks-header h1 {
        font-size: 3rem;
    }
    
    .exam-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

/* Large Devices (1200px and up) */
@media (min-width: 1200px) {
    .marks-container {
        max-width: 1200px;
    }
}

/* Medium Devices - Tablets (768px to 1024px) */
@media (max-width: 1024px) {
    .marks-header {
        padding: 35px 25px;
    }
    
    .marks-header h1 {
        font-size: 2.2rem;
    }
    
    .exam-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
    }
    
    .subject-card {
        padding: 25px;
    }
    
    .card-title {
        font-size: 1.6rem;
    }
}

@media (max-width: 768px) {
    body {
        padding: 15px;
    }
    
    .marks-header {
        padding: 30px 20px;
        border-radius: 15px;
    }
    
    .marks-header h1 {
        font-size: 1.8rem;
        margin-bottom: 8px;
    }
    
    .marks-header p {
        font-size: 1rem;
    }
    
    .marks-card {
        padding: 20px;
        border-radius: 15px;
    }
    
    .card-title {
        font-size: 1.4rem;
    }
    
    .academic-year-section h2 {
        font-size: 1.6rem;
        padding: 12px 20px;
    }
    
    .semester-section h3 {
        font-size: 1.3rem;
        padding: 10px 15px;
    }
    
    .subject-card {
        padding: 20px;
        margin: 15px 0;
    }
    
    .subject-title {
        font-size: 1.2rem;
    }
    
    .subject-meta {
        font-size: 0.85rem;
        gap: 10px;
    }
    
    .exam-grid {
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 12px;
    }
    
    .exam-item {
        padding: 20px 15px;
    }
    
    .exam-marks {
        font-size: 1.8rem;
    }
    
    .exam-grade {
        font-size: 1.2rem;
    }
    
    .stats-summary {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
    }
    
    .stat-value {
        font-size: 2rem;
    }
    
    .btn {
        padding: 12px 25px;
        font-size: 0.95rem;
        margin: 8px;
    }
}

/* Small Devices - Mobile (up to 576px) */
@media (max-width: 576px) {
    body {
        padding: 10px;
    }
    
    .marks-header {
        padding: 25px 15px;
        border-radius: 12px;
    }
    
    .marks-header h1 {
        font-size: 1.5rem;
    }
    
    .marks-header p {
        font-size: 0.9rem;
    }
    
    .marks-card {
        padding: 15px;
    }
    
    .card-title {
        font-size: 1.2rem;
        padding-bottom: 10px;
    }
    
    .academic-year-section {
        margin: 25px 0;
    }
    
    .academic-year-section h2 {
        font-size: 1.3rem;
        padding: 10px 15px;
        margin-bottom: 15px;
    }
    
    .semester-section {
        margin: 20px 0;
    }
    
    .semester-section h3 {
        font-size: 1.1rem;
        padding: 8px 12px;
    }
    
    .subject-card {
        padding: 15px;
        margin: 12px 0;
        border-radius: 15px;
    }
    
    .subject-title {
        font-size: 1.1rem;
        line-height: 1.4;
    }
    
    .subject-meta {
        font-size: 0.8rem;
        flex-direction: column;
        gap: 5px;
    }
    
    .exam-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .exam-item {
        padding: 18px 12px;
    }
    
    .exam-name {
        font-size: 0.95rem;
        margin-bottom: 12px;
    }
    
    .exam-marks {
        font-size: 1.6rem;
    }
    
    .exam-grade {
        font-size: 1.1rem;
    }
    
    .no-marks {
        padding: 50px 15px;
    }
    
    .no-marks div {
        font-size: 3.5rem;
    }
    
    .no-marks h3 {
        font-size: 1.4rem;
    }
    
    .no-marks p {
        font-size: 0.95rem;
    }
    
    .stats-summary {
        grid-template-columns: 1fr;
        gap: 12px;
        margin: 25px 0;
    }
    
    .stat-box {
        padding: 20px 15px;
    }
    
    .stat-value {
        font-size: 1.8rem;
    }
    
    .stat-label {
        font-size: 0.9rem;
    }
    
    .btn {
        display: block;
        width: 100%;
        padding: 12px 20px;
        margin: 8px 0;
        font-size: 0.9rem;
    }
}

/* Extra Small Devices (up to 375px) */
@media (max-width: 375px) {
    .marks-header h1 {
        font-size: 1.3rem;
    }
    
    .subject-title {
        font-size: 1rem;
    }
    
    .exam-marks {
        font-size: 1.4rem;
    }
    
    .exam-grade {
        font-size: 1rem;
    }
}

/* Landscape Orientation - Mobile */
@media (max-width: 768px) and (orientation: landscape) {
    .marks-header {
        padding: 20px 15px;
    }
    
    .marks-header h1 {
        font-size: 1.6rem;
    }
    
    .exam-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .no-marks {
        padding: 40px 15px;
    }
}

/* High DPI Displays */
@media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
    .marks-header,
    .subject-card,
    .exam-item {
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
    /* Add dark mode styles if needed */
}
    </style>
</body>
</html>