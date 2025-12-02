<?php
require_once '../db.php';
checkRole(['hod']);

$user = getCurrentUser();
$department_id = $_SESSION['department_id'];

// Get selected date (default to today)
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Get department info
$dept_query = "SELECT * FROM departments WHERE id = ?";
$stmt = $conn->prepare($dept_query);
$stmt->bind_param("i", $department_id);
$stmt->execute();
$department = $stmt->get_result()->fetch_assoc();

// Get available dates with attendance records
$available_dates_query = "SELECT DISTINCT DATE(sa.attendance_date) as date 
FROM student_attendance sa
INNER JOIN students s ON sa.student_id = s.id
WHERE s.department_id = ?
ORDER BY date DESC
LIMIT 90";
$stmt = $conn->prepare($available_dates_query);
$stmt->bind_param("i", $department_id);
$stmt->execute();
$available_dates_result = $stmt->get_result();
$available_dates = [];
while ($row = $available_dates_result->fetch_assoc()) {
    $available_dates[] = $row['date'];
}

// Get section-wise attendance summary for selected date
$section_summary_query = "SELECT 
    c.class_name,
    c.id as class_id,
    COUNT(DISTINCT s.id) as total_students,
    COALESCE(SUM(CASE WHEN sa.status = 'present' AND DATE(sa.attendance_date) = ? THEN 1 ELSE 0 END), 0) as daily_present,
    COALESCE(SUM(CASE WHEN sa.status = 'absent' AND DATE(sa.attendance_date) = ? THEN 1 ELSE 0 END), 0) as daily_absent,
    COALESCE(SUM(CASE WHEN DATE(sa.attendance_date) = ? THEN 1 ELSE 0 END), 0) as daily_total
FROM classes c
LEFT JOIN students s ON c.id = s.class_id AND s.is_active = 1 AND s.department_id = ?
LEFT JOIN student_attendance sa ON s.id = sa.student_id
WHERE c.department_id = ?
GROUP BY c.id, c.class_name
HAVING daily_total > 0
ORDER BY c.class_name";
$stmt = $conn->prepare($section_summary_query);
$stmt->bind_param("sssii", $selected_date, $selected_date, $selected_date, $department_id, $department_id);
$stmt->execute();
$section_summary = $stmt->get_result();

// Get department students
$students_query = "SELECT s.*, c.class_name,
                   (SELECT COUNT(*) FROM student_attendance WHERE student_id = s.id) as total_attendance,
                   (SELECT COUNT(*) FROM student_attendance WHERE student_id = s.id AND status = 'present') as present_count
                   FROM students s
                   LEFT JOIN classes c ON s.class_id = c.id
                   WHERE s.department_id = ? AND s.is_active = 1
                   ORDER BY s.roll_number";
$stmt = $conn->prepare($students_query);
$stmt->bind_param("i", $department_id);
$stmt->execute();
$students = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Students - HOD</title>
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
            position: relative;
            overflow-x: hidden;
        }

        /* Animated Background Particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            animation: float 15s infinite ease-in-out;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-100vh) rotate(360deg); opacity: 0; }
        }

        /* Enhanced Navbar with Glass Effect */
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
        
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .navbar-logo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            animation: rotateLogo 10s linear infinite;
        }

        @keyframes rotateLogo {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .navbar h1 {
            color: white;
            font-size: 24px;
            font-weight: 700;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 25px;
            color: white;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255, 255, 255, 0.1);
            padding: 10px 20px;
            border-radius: 50px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f093fb, #f5576c);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: bold;
        }

        .main-content {
            padding: 40px;
            max-width: 1600px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        /* Date Picker Section */
        .page-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            border: 2px solid rgba(255, 255, 255, 0.5);
        }

        .page-header h2 {
            color: #2c3e50;
            font-size: 28px;
            margin-bottom: 20px;
        }

        .date-picker-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            border-radius: 12px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }

        .date-picker-label {
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .date-controls {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .date-input-wrapper {
            position: relative;
        }

        .date-input {
            padding: 12px 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            background: rgba(255,255,255,0.95);
            color: #2c3e50;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 200px;
        }

        .date-input:hover {
            background: white;
            border-color: rgba(255,255,255,0.8);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .date-input:focus {
            outline: none;
            border-color: white;
            box-shadow: 0 0 0 4px rgba(255,255,255,0.3);
        }

        .quick-dates {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .quick-date-btn {
            padding: 10px 18px;
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 8px;
            color: white;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .quick-date-btn:hover {
            background: rgba(255,255,255,0.3);
            border-color: rgba(255,255,255,0.6);
            transform: translateY(-2px);
        }

        .quick-date-btn.active {
            background: white;
            color: #667eea;
            border-color: white;
        }

        .available-dates-dropdown {
            position: relative;
        }

        .dropdown-btn {
            padding: 12px 20px;
            background: rgba(255,255,255,0.95);
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 10px;
            color: #2c3e50;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }

        .dropdown-btn:hover {
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .dropdown-content {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 10px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
            min-width: 200px;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
        }

        .available-dates-dropdown:hover .dropdown-content {
            display: block;
        }

        .dropdown-content a {
            display: block;
            padding: 12px 20px;
            color: #2c3e50;
            text-decoration: none;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s ease;
        }

        .dropdown-content a:hover {
            background: #f8f9fa;
            padding-left: 25px;
        }

        .dropdown-content a.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
        }

        /* Summary Section */
        .summary-section {
            margin-bottom: 30px;
        }

        .summary-section h3 {
            color: #2c3e50;
            font-size: 24px;
            margin-bottom: 20px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px;
        }
        
        .summary-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            padding: 28px;
            color: white;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
        }
        
        .summary-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
        }
        
        .summary-card h4 {
            margin: 0 0 20px 0;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(255,255,255,0.2);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 20px;
        }
        
        .stat-box {
            background: rgba(255,255,255,0.15);
            padding: 18px;
            border-radius: 12px;
            text-align: center;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .stat-box:hover {
            background: rgba(255,255,255,0.25);
            transform: scale(1.05);
        }
        
        .stat-label {
            font-size: 12px;
            opacity: 0.9;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
        }
        
        .progress-section {
            margin-top: 20px;
        }

        .progress-label {
            font-size: 13px;
            margin-bottom: 10px;
            opacity: 0.95;
            font-weight: 600;
        }

        .progress-bar-container {
            width: 100%;
            height: 12px;
            background: rgba(255,255,255,0.2);
            border-radius: 6px;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #4ade80 0%, #22c55e 100%);
            transition: width 0.5s ease;
            border-radius: 6px;
        }
        
        .progress-percentage {
            text-align: right;
            margin-top: 8px;
            font-size: 18px;
            font-weight: 700;
        }

        /* Table Container */
        .table-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            padding: 40px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
            margin: 30px 0;
            border: 2px solid rgba(255, 255, 255, 0.5);
        }

        .table-container h3 {
            color: #2c3e50;
            font-size: 24px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Search Box */
        .search-box {
            position: relative;
            min-width: 300px;
        }

        .search-input {
            width: 100%;
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .search-input::placeholder {
            color: #999;
        }

        /* Modern Tables */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        thead th {
            padding: 15px;
            color: white;
            font-weight: 600;
            text-align: left;
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
            transform: translateX(5px);
        }

        tbody td {
            padding: 18px 15px;
            color: #2c3e50;
            font-size: 14px;
        }

        .badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        /* Alerts */
        .alert {
            padding: 20px 24px;
            border-radius: 12px;
            margin: 20px 0;
            text-align: center;
            font-size: 16px;
        }

        .alert-info {
            background: #e3f2fd;
            color: #1976d2;
            border: 2px solid #90caf9;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 2px solid #ffc107;
        }

        .no-results {
            text-align: center;
            padding: 40px;
            color: #666;
            font-size: 16px;
        }

        /* Buttons */
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
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.4);
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.6);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b, #ee5a5a);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4);
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.6);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .navbar { 
                padding: 15px 20px; 
                flex-direction: column; 
                gap: 15px; 
            }
            
            .navbar h1 { 
                font-size: 18px; 
            }
            
            .main-content { 
                padding: 20px; 
            }
            
            .page-header { 
                padding: 20px; 
            }
            
            .date-picker-section {
                flex-direction: column;
                align-items: stretch;
            }

            .date-controls {
                flex-direction: column;
                width: 100%;
            }

            .date-input,
            .dropdown-btn {
                width: 100%;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .search-box {
                min-width: 100%;
            }
            
            .table-container { 
                padding: 20px; 
                overflow-x: auto; 
            }
            
            table { 
                font-size: 12px; 
            }
            
            .user-info { 
                flex-direction: column; 
                gap: 10px; 
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background Particles -->
    <div class="particles" id="particles"></div>

    <nav class="navbar">
        <div class="navbar-brand">
            <div class="navbar-logo">🎓</div>
            <h1>NIT AMMS <?php echo htmlspecialchars($department['dept_name']); ?> - Students</h1>
        </div>
        <div class="user-info">
            <a href="index.php" class="btn btn-secondary">← Back</a>
            <div class="user-profile">
                <div class="user-avatar">👔</div>
                <span><?php echo htmlspecialchars($user['full_name']); ?></span>
            </div>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <!-- Date Picker Section -->
        <div class="page-header">
            <h2>📊 Attendance Overview</h2>
            
            <div class="date-picker-section">
                <div class="date-picker-label">
                    <span>📅</span>
                    <span>Select Date:</span>
                </div>
                
                <div class="date-controls">
                    <div class="date-input-wrapper">
                        <input type="date" 
                               class="date-input" 
                               id="dateInput" 
                               value="<?php echo $selected_date; ?>"
                               max="<?php echo date('Y-m-d'); ?>"
                               onchange="window.location.href='?date=' + this.value">
                    </div>

                    <div class="quick-dates">
                        <a href="?date=<?php echo date('Y-m-d'); ?>" 
                           class="quick-date-btn <?php echo $selected_date === date('Y-m-d') ? 'active' : ''; ?>">
                            Today
                        </a>
                        <a href="?date=<?php echo date('Y-m-d', strtotime('-1 day')); ?>" 
                           class="quick-date-btn <?php echo $selected_date === date('Y-m-d', strtotime('-1 day')) ? 'active' : ''; ?>">
                            Yesterday
                        </a>
                        <a href="?date=<?php echo date('Y-m-d', strtotime('-7 days')); ?>" 
                           class="quick-date-btn <?php echo $selected_date === date('Y-m-d', strtotime('-7 days')) ? 'active' : ''; ?>">
                            Last Week
                        </a>
                    </div>

                    <?php if (count($available_dates) > 0): ?>
                    <div class="available-dates-dropdown">
                        <div class="dropdown-btn">
                            📋 Recent Dates
                            <span>▼</span>
                        </div>
                        <div class="dropdown-content">
                            <?php foreach ($available_dates as $date): ?>
                                <a href="?date=<?php echo $date; ?>" 
                                   class="<?php echo $date === $selected_date ? 'active' : ''; ?>">
                                    <?php echo date('D, M d, Y', strtotime($date)); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Section Summary Section -->
        <div class="summary-section">
            <h3>📚 Section-wise Attendance - <?php echo date('l, F d, Y', strtotime($selected_date)); ?></h3>
            
            <?php if ($section_summary->num_rows > 0): ?>
                <div class="summary-grid">
                    <?php while ($section = $section_summary->fetch_assoc()): 
                        $daily_percentage = $section['daily_total'] > 0
                            ? round(($section['daily_present'] / $section['daily_total']) * 100, 1)
                            : 0;
                    ?>
                    <div class="summary-card">
                        <h4>
                            <span>📚</span>
                            <?php echo htmlspecialchars($section['class_name']); ?>
                        </h4>
                        
                        <div class="stats-grid">
                            <div class="stat-box">
                                <div class="stat-label">👥 Total</div>
                                <div class="stat-value"><?php echo $section['total_students']; ?></div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-label">✅ Present</div>
                                <div class="stat-value"><?php echo $section['daily_present']; ?></div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-label">❌ Absent</div>
                                <div class="stat-value"><?php echo $section['daily_absent']; ?></div>
                            </div>
                        </div>
                        
                        <div class="progress-section">
                            <div class="progress-label">Attendance Percentage</div>
                            <div class="progress-bar-container">
                                <div class="progress-bar" style="width: <?php echo $daily_percentage; ?>%"></div>
                            </div>
                            <div class="progress-percentage"><?php echo $daily_percentage; ?>%</div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    ⚠️ No attendance records found for <?php echo date('F d, Y', strtotime($selected_date)); ?>. 
                    Please select a different date.
                </div>
            <?php endif; ?>
        </div>

        <!-- Students Table -->
        <div class="table-container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                <h3 style="margin: 0;">👨‍🎓 All Department Students</h3>
                <div class="search-box">
                    <input type="text" 
                           id="searchInput" 
                           placeholder="🔍 Search by name, roll number, email..." 
                           class="search-input">
                </div>
            </div>
            
            <?php if ($students->num_rows > 0): ?>
                <table id="studentsTable">
                    <thead>
                        <tr>
                            <th>Roll Number</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Class</th>
                            <th>Year</th>
                            <th>Semester</th>
                            <th>Overall Attendance %</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($student = $students->fetch_assoc()): 
                            $attendance_percentage = $student['total_attendance'] > 0 
                                ? round(($student['present_count'] / $student['total_attendance']) * 100, 2) 
                                : 0;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                            <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td><?php echo htmlspecialchars($student['class_name']); ?></td>
                            <td><?php echo $student['year']; ?></td>
                            <td><?php echo $student['semester']; ?></td>
                            <td>
                                <strong style="color: <?php echo $attendance_percentage >= 75 ? '#28a745' : '#dc3545'; ?>">
                                    <?php echo $attendance_percentage; ?>%
                                </strong>
                            </td>
                            <td><span class="badge badge-success">Active</span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">No students found in this department.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Premium Footer -->
    <div style="background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 50%, #2a3254 100%); position: relative; overflow: hidden;">
        
        <!-- Animated Top Border -->
        <div style="height: 2px; background: linear-gradient(90deg, #4a9eff, #00d4ff, #4a9eff, #00d4ff); background-size: 200% 100%; animation: borderMove 3s linear infinite;"></div>
        
        <!-- Main Footer Container -->
        <div style="max-width: 1000px; margin: 0 auto; padding: 30px 20px 20px;">
            
            <!-- Developer Section -->
            <div style="background: rgba(255, 255, 255, 0.03); padding: 20px 20px; border-radius: 15px; border: 1px solid rgba(74, 158, 255, 0.15); text-align: center; box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);">
                
                <!-- Title -->
                <p style="color: #ffffff; font-size: 14px; margin: 0 0 12px; font-weight: 500; letter-spacing: 0.5px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">✨ Designed & Developed by</p>
                
                <!-- Company Link -->
                <a href="https://himanshufullstackdeveloper.github.io/techyugsoftware/" style="display: inline-block; color: #ffffff; font-size: 16px; font-weight: 700; text-decoration: none; padding: 8px 24px; border: 2px solid #4a9eff; border-radius: 30px; background: linear-gradient(135deg, rgba(74, 158, 255, 0.2), rgba(0, 212, 255, 0.2)); box-shadow: 0 3px 12px rgba(74, 158, 255, 0.3); margin-bottom: 15px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; transition: all 0.3s;">
                    🚀 Techyug Software Pvt. Ltd.
                </a>
                
                <!-- Divider -->
                <div style="width: 50%; height: 1px; background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent); margin: 15px auto;"></div>
                
                <!-- Team Label -->
                <p style="color: #888; font-size: 10px; margin: 0 0 12px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">💼 Development Team</p>
                
                <!-- Developer Badges -->
                <div style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; margin-top: 12px;">
                    
                    <!-- Developer 1 -->
                    <a href="https://himanshufullstackdeveloper.github.io/portfoilohimanshu/" style="color: #ffffff; font-size: 13px; text-decoration: none; padding: 8px 16px; background: linear-gradient(135deg, rgba(74, 158, 255, 0.25), rgba(0, 212, 255, 0.25)); border-radius: 20px; border: 1px solid rgba(74, 158, 255, 0.4); display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 3px 10px rgba(74, 158, 255, 0.2); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; transition: all 0.3s;">
                        <span style="font-size: 16px;">👨‍💻</span>
                        <span style="font-weight: 600;">Himanshu Patil</span>
                    </a>
                    
                    <!-- Developer 2 -->
                    <a href="https://devpranaypanore.github.io/Pranaypanore-live-.html/" style="color: #ffffff; font-size: 13px; text-decoration: none; padding: 8px 16px; background: linear-gradient(135deg, rgba(74, 158, 255, 0.25), rgba(0, 212, 255, 0.25)); border-radius: 20px; border: 1px solid rgba(74, 158, 255, 0.4); display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 3px 10px rgba(74, 158, 255, 0.2); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; transition: all 0.3s;">
                        <span style="font-size: 16px;">👨‍💻</span>
                        <span style="font-weight: 600;">Pranay Panore</span>
                    </a>
                </div>
                
                <!-- Role Tags -->
                <div style="margin-top: 15px; display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;">
                    <span style="color: #4a9eff; font-size: 10px; padding: 4px 12px; background: rgba(74, 158, 255, 0.1); border-radius: 12px; border: 1px solid rgba(74, 158, 255, 0.3); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">Full Stack</span>
                    <span style="color: #00d4ff; font-size: 10px; padding: 4px 12px; background: rgba(0, 212, 255, 0.1); border-radius: 12px; border: 1px solid rgba(0, 212, 255, 0.3); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">UI/UX</span>
                    <span style="color: #4a9eff; font-size: 10px; padding: 4px 12px; background: rgba(74, 158, 255, 0.1); border-radius: 12px; border: 1px solid rgba(74, 158, 255, 0.3); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">Database</span>
                </div>
            </div>
            
            <!-- Bottom Section -->
            <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.1); text-align: center;">
                
                <!-- Copyright -->
                <p style="color: #888; font-size: 12px; margin: 0 0 10px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">© 2025 NIT AMMS. All rights reserved.</p>
                
                <!-- Made With Love -->
                <p style="color: #666; font-size: 11px; margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
                    Made with <span style="color: #ff4757; font-size: 14px;">❤️</span> by Techyug Software
                </p>
                
                <!-- Social Links -->
                <div style="margin-top: 15px; display: flex; justify-content: center; gap: 10px;">
                    <a href="#" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; background: rgba(74, 158, 255, 0.1); border: 1px solid rgba(74, 158, 255, 0.3); border-radius: 50%; color: #4a9eff; text-decoration: none; font-size: 14px; transition: all 0.3s;">📧</a>
                    <a href="#" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; background: rgba(74, 158, 255, 0.1); border: 1px solid rgba(74, 158, 255, 0.3); border-radius: 50%; color: #4a9eff; text-decoration: none; font-size: 14px; transition: all 0.3s;">🌐</a>
                    <a href="#" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; background: rgba(74, 158, 255, 0.1); border: 1px solid rgba(74, 158, 255, 0.3); border-radius: 50%; color: #4a9eff; text-decoration: none; font-size: 14px; transition: all 0.3s;">💼</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Create animated background particles
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 30;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                const size = Math.random() * 5 + 2;
                particle.style.width = size + 'px';
                particle.style.height = size + 'px';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 15 + 's';
                particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
                
                particlesContainer.appendChild(particle);
            }
        }

        // Search functionality
        document.addEventListener('DOMContentLoaded', function() {
            createParticles();

            const searchInput = document.getElementById('searchInput');
            const studentsTable = document.getElementById('studentsTable');
            
            if (!searchInput || !studentsTable) return;

            const tbody = studentsTable.querySelector('tbody');
            if (!tbody) return;

            const tableRows = tbody.querySelectorAll('tr');

            searchInput.addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase().trim();
                let visibleCount = 0;

                tableRows.forEach(row => {
                    const cells = row.getElementsByTagName('td');
                    if (cells.length === 0) return;

                    const rollNumber = cells[0].textContent.toLowerCase();
                    const name = cells[1].textContent.toLowerCase();
                    const email = cells[2].textContent.toLowerCase();
                    const className = cells[3].textContent.toLowerCase();

                    if (rollNumber.includes(searchTerm) || 
                        name.includes(searchTerm) || 
                        email.includes(searchTerm) ||
                        className.includes(searchTerm)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                let noResultsMsg = document.getElementById('noResultsMsg');
                if (visibleCount === 0 && searchTerm !== '') {
                    if (!noResultsMsg) {
                        noResultsMsg = document.createElement('div');
                        noResultsMsg.id = 'noResultsMsg';
                        noResultsMsg.className = 'no-results';
                        studentsTable.parentElement.appendChild(noResultsMsg);
                    }
                    noResultsMsg.innerHTML = '🔍 No students found matching "<strong>' + searchTerm + '</strong>"';
                    noResultsMsg.style.display = 'block';
                    studentsTable.style.display = 'none';
                } else {
                    if (noResultsMsg) {
                        noResultsMsg.style.display = 'none';
                    }
                    studentsTable.style.display = 'table';
                }
            });
        });
    </script>
</body>
</html>