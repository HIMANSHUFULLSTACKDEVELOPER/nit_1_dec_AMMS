<?php
// parent/view_child_marks.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Database connection
if (file_exists('../db.php')) {
    require_once '../db.php';
} elseif (file_exists('../includes/db_connect.php')) {
    require_once '../includes/db_connect.php';
} else {
    die("Database connection file not found.");
}

// Check if user is parent
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header('Location: ../login.php');
    exit();
}

$parent_id = $_SESSION['user_id'];

// Get parent info and linked student
$parent_query = "SELECT p.*, s.id as student_id, s.full_name as student_name, 
                 s.roll_number, s.email as student_email, s.year, s.semester,
                 d.dept_name, c.section, c.class_name
                 FROM parents p
                 LEFT JOIN students s ON p.student_id = s.id
                 LEFT JOIN departments d ON s.department_id = d.id
                 LEFT JOIN classes c ON s.class_id = c.id
                 WHERE p.id = ?";
$stmt = $conn->prepare($parent_query);
if (!$stmt) {
    die("Database error: " . $conn->error);
}
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$parent_data = $stmt->get_result()->fetch_assoc();

if (!$parent_data || !$parent_data['student_id']) {
    die("No student linked to this parent account. Please contact the administrator.");
}

$student_id = $parent_data['student_id'];

// Get academic years with marks
$years_query = "SELECT DISTINCT academic_year 
                FROM paper_marks 
                WHERE student_id = ? 
                ORDER BY academic_year DESC";
$stmt = $conn->prepare($years_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$years_result = $stmt->get_result();
$available_years = [];
while ($row = $years_result->fetch_assoc()) {
    $available_years[] = $row['academic_year'];
}

$selected_year = isset($_GET['year']) ? $_GET['year'] : (count($available_years) > 0 ? $available_years[0] : '');

// Get all marks with subject and exam details
$marks_query = "SELECT pm.*, s.subject_code, s.subject_name, s.credits,
                et.exam_name, et.max_marks as exam_max_marks,
                u.full_name as teacher_name
                FROM paper_marks pm
                JOIN subjects s ON pm.subject_id = s.id
                JOIN exam_types et ON pm.exam_type_id = et.id
                LEFT JOIN users u ON pm.teacher_id = u.id
                WHERE pm.student_id = ?";

$params = [$student_id];
$types = "i";

if ($selected_year) {
    $marks_query .= " AND pm.academic_year = ?";
    $params[] = $selected_year;
    $types .= "s";
}

$marks_query .= " ORDER BY pm.year DESC, pm.semester DESC, s.subject_name, et.display_order";

$stmt = $conn->prepare($marks_query);
if (!$stmt) {
    die("Marks query error: " . $conn->error);
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$all_marks = $stmt->get_result();

// Group marks by academic year, semester and subject
$grouped_marks = [];
$total_marks_obtained = 0;
$total_max_marks = 0;
$exam_count = 0;

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
            'teacher_name' => $mark['teacher_name'] ?? 'N/A',
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
    
    // Calculate totals
    $total_marks_obtained += $mark['marks_obtained'];
    $total_max_marks += $mark['max_marks'];
    $exam_count++;
}

// Calculate overall percentage
$overall_percentage = $total_max_marks > 0 ? ($total_marks_obtained / $total_max_marks) * 100 : 0;

// Parenting tips based on performance
$parenting_tips = [
    'excellent' => [
        "🌟 Celebrate your child's achievement! Positive reinforcement builds confidence.",
        "📚 Encourage them to help peers who might be struggling - teaching reinforces learning.",
        "🎯 Challenge them with advanced material to maintain their interest.",
        "💪 Remind them that consistency is key to maintaining excellence."
    ],
    'good' => [
        "👍 Acknowledge their effort and progress. Good grades are worth celebrating!",
        "📖 Identify subjects where they can improve and offer support.",
        "⏰ Help establish a consistent study routine.",
        "🤝 Communicate with teachers to understand areas of improvement."
    ],
    'needs_improvement' => [
        "❤️ Show unconditional love and support - grades don't define their worth.",
        "🔍 Work with teachers to identify specific challenges.",
        "📅 Create a structured study schedule together.",
        "🎓 Consider additional tutoring or study groups.",
        "💬 Have open conversations about any difficulties they're facing."
    ]
];

$performance_level = $overall_percentage >= 75 ? 'excellent' : ($overall_percentage >= 60 ? 'good' : 'needs_improvement');
$current_tip = $parenting_tips[$performance_level][array_rand($parenting_tips[$performance_level])];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Child's Academic Performance - Parent Portal</title>
    <link rel="icon" href="../Nit_logo.png" type="image/png" />
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
            background: linear-gradient(135deg, #f093fb, #f5576c);
            padding: 10px 20px;
            border-radius: 10px;
            transition: all 0.3s;
        }
        .navbar a:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(240, 147, 251, 0.4); }
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
        .parenting-tip {
            background: linear-gradient(135deg, rgba(240, 147, 251, 0.2), rgba(245, 87, 108, 0.2));
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            border-left: 5px solid #f093fb;
        }
        .parenting-tip h3 {
            color: #f093fb;
            margin-bottom: 15px;
            font-size: 20px;
        }
        .parenting-tip p {
            color: #666;
            line-height: 1.8;
            font-size: 15px;
        }
        h2 {
            font-size: 28px;
            background: linear-gradient(135deg, #f093fb, #f5576c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 30px;
        }
        .student-info {
            background: linear-gradient(135deg, rgba(240, 147, 251, 0.1), rgba(245, 87, 108, 0.1));
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
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
            color: #f093fb;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.7));
            padding: 30px;
            border-radius: 20px;
            border: 2px solid rgba(240, 147, 251, 0.3);
            transition: all 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(240, 147, 251, 0.3);
        }
        .stat-value {
            font-size: 48px;
            font-weight: 800;
            background: linear-gradient(135deg, #f093fb, #f5576c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .stat-label {
            font-size: 13px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 10px;
        }
        .year-filter {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            align-items: center;
        }
        .year-btn {
            padding: 12px 24px;
            border-radius: 12px;
            background: white;
            border: 2px solid #e0e0e0;
            color: #333;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        .year-btn:hover, .year-btn.active {
            background: linear-gradient(135deg, #f093fb, #f5576c);
            color: white;
            border-color: transparent;
            transform: translateY(-2px);
        }
        .academic-year-section {
            margin-bottom: 40px;
        }
        .year-title {
            font-size: 26px;
            color: #667eea;
            margin-bottom: 20px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .semester-section {
            margin-bottom: 30px;
        }
        .semester-title {
            font-size: 22px;
            color: #764ba2;
            margin-bottom: 20px;
            padding: 12px;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 10px;
            border-left: 5px solid #764ba2;
        }
        .subject-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            border-left: 5px solid #f093fb;
        }
        .subject-header {
            margin-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 15px;
        }
        .subject-title {
            font-size: 20px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        .subject-meta {
            font-size: 14px;
            color: #666;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            align-items: center;
        }
        .subject-code {
            background: linear-gradient(135deg, #f093fb, #f5576c);
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 13px;
            font-weight: 600;
        }
        .exam-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }
        .exam-item {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            transition: all 0.3s;
        }
        .exam-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.2);
        }
        .exam-name {
            font-weight: 600;
            color: #667eea;
            margin-bottom: 12px;
            font-size: 15px;
        }
        .exam-marks {
            font-size: 32px;
            font-weight: 700;
            color: #2c3e50;
            margin: 10px 0;
        }
        .exam-percentage {
            font-size: 14px;
            color: #666;
            margin: 8px 0;
        }
        .exam-grade {
            font-size: 24px;
            font-weight: 700;
            margin-top: 8px;
        }
        .grade-O { color: #28a745; }
        .grade-A { color: #5cb85c; }
        .grade-B { color: #5bc0de; }
        .grade-C { color: #f0ad4e; }
        .grade-F { color: #d9534f; }
        .exam-remarks {
            font-size: 12px;
            color: #888;
            margin-top: 10px;
            font-style: italic;
            padding-top: 10px;
            border-top: 1px solid rgba(0,0,0,0.1);
        }
        .no-marks {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 12px 30px;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        .btn-secondary {
            background: linear-gradient(135deg, #f093fb, #f5576c);
            color: white;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }
        @media print {
            body { background: white; }
            .navbar, .action-buttons, .year-filter, .parenting-tip { display: none; }
            .container { box-shadow: none; }
            .subject-card { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>📊 Child's Academic Performance</h1>
        <a href="index.php">🏠 Dashboard</a>
    </nav>

    <div class="main-content">
        <div class="container">
            <h2>👨‍👩‍👦 Parent: <?php echo htmlspecialchars($parent_data['full_name'] ?? $parent_data['name'] ?? 'Parent'); ?></h2>
            
            <div class="parenting-tip">
                <h3>💡 Parenting Tip</h3>
                <p><?php echo $current_tip; ?></p>
            </div>

            <div class="student-info">
                <div class="info-item">
                    <span>Child's Name</span>
                    <strong><?php echo htmlspecialchars($parent_data['student_name']); ?></strong>
                </div>
                <div class="info-item">
                    <span>Roll Number</span>
                    <strong><?php echo htmlspecialchars($parent_data['roll_number']); ?></strong>
                </div>
                <div class="info-item">
                    <span>Email</span>
                    <strong><?php echo htmlspecialchars($parent_data['student_email']); ?></strong>
                </div>
                <?php if (isset($parent_data['dept_name'])): ?>
                <div class="info-item">
                    <span>Department</span>
                    <strong><?php echo htmlspecialchars($parent_data['dept_name'] ?? 'N/A'); ?></strong>
                </div>
                <?php endif; ?>
                <?php if (isset($parent_data['year']) && isset($parent_data['semester'])): ?>
                <div class="info-item">
                    <span>Year & Semester</span>
                    <strong>Year <?php echo $parent_data['year']; ?>, Sem <?php echo $parent_data['semester']; ?></strong>
                </div>
                <?php endif; ?>
                <?php if (isset($parent_data['section'])): ?>
                <div class="info-item">
                    <span>Section</span>
                    <strong><?php echo htmlspecialchars($parent_data['section'] ?? 'N/A'); ?></strong>
                </div>
                <?php endif; ?>
            </div>

            <?php if (count($available_years) > 0): ?>
                <div class="year-filter">
                    <span style="font-weight: 600; color: #333; padding: 12px 0;">📅 Academic Year:</span>
                    <?php foreach ($available_years as $year): ?>
                        <a href="?year=<?php echo urlencode($year); ?>" 
                           class="year-btn <?php echo $year === $selected_year ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($year); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($overall_percentage, 2); ?>%</div>
                    <div class="stat-label">Overall Percentage</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" style="background: linear-gradient(135deg, #28a745, #20c997); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                        <?php echo number_format($total_marks_obtained, 2); ?>
                    </div>
                    <div class="stat-label">Total Marks Obtained</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" style="background: linear-gradient(135deg, #17a2b8, #138496); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                        <?php echo $total_max_marks; ?>
                    </div>
                    <div class="stat-label">Maximum Marks</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" style="background: linear-gradient(135deg, #ffc107, #ff9800); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                        <?php echo $exam_count; ?>
                    </div>
                    <div class="stat-label">Total Exams</div>
                </div>
            </div>

            <?php if (count($grouped_marks) > 0): ?>
                <?php foreach ($grouped_marks as $academic_year => $semesters): ?>
                    <div class="academic-year-section">
                        <div class="year-title">📅 Academic Year: <?php echo htmlspecialchars($academic_year); ?></div>
                        
                        <?php foreach ($semesters as $semester_label => $subjects): ?>
                            <div class="semester-section">
                                <div class="semester-title">📚 <?php echo $semester_label; ?></div>
                                
                                <?php foreach ($subjects as $subject_code => $subject_data): ?>
                                    <div class="subject-card">
                                        <div class="subject-header">
                                            <div class="subject-title">
                                                <?php echo htmlspecialchars($subject_data['subject_name']); ?>
                                            </div>
                                            <div class="subject-meta">
                                                <span class="subject-code"><?php echo htmlspecialchars($subject_code); ?></span>
                                                <span>📘 Credits: <?php echo $subject_data['credits']; ?></span>
                                                <span>👨‍🏫 Teacher: <?php echo htmlspecialchars($subject_data['teacher_name']); ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="exam-grid">
                                            <?php foreach ($subject_data['exams'] as $exam): ?>
                                                <div class="exam-item">
                                                    <div class="exam-name"><?php echo htmlspecialchars($exam['exam_name']); ?></div>
                                                    <div class="exam-marks">
                                                        <?php echo $exam['marks_obtained']; ?> / <?php echo $exam['max_marks']; ?>
                                                    </div>
                                                    <div class="exam-percentage">
                                                        <?php echo number_format($exam['percentage'], 2); ?>%
                                                    </div>
                                                    <div class="exam-grade grade-<?php echo $exam['grade']; ?>">
                                                        Grade: <?php echo $exam['grade']; ?>
                                                    </div>
                                                    <?php if ($exam['remarks']): ?>
                                                        <div class="exam-remarks">
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
            <?php else: ?>
                <div class="no-marks">
                    <p style="font-size: 64px; margin-bottom: 20px;">📋</p>
                    <h3>No Marks Available Yet</h3>
                    <p>Your child's marks will appear here once teachers publish them.</p>
                </div>
            <?php endif; ?>

            <div class="action-buttons">
                <a href="index.php" class="btn btn-primary">🏠 Back to Dashboard</a>
                <button onclick="window.print()" class="btn btn-secondary">🖨️ Print Report</button>
            </div>
        </div>
    </div>
</body>
</html>