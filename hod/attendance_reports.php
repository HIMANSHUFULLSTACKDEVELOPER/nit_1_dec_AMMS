<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Check if user has HOD role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'hod') {
    header('Location: ../index.php');
    exit();
}

// Get current user
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Check if department_id exists
if (!isset($_SESSION['department_id'])) {
    die("Error: Department ID not found in session.");
}

$department_id = $_SESSION['department_id'];

// Get department info
$dept_query = "SELECT * FROM departments WHERE id = ?";
$stmt = $conn->prepare($dept_query);
$stmt->bind_param("i", $department_id);
$stmt->execute();
$department = $stmt->get_result()->fetch_assoc();

if (!$department) {
    die("Error: Department not found.");
}

// Get filter parameters
$filter_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

// Validate month format
if (!preg_match('/^\d{4}-\d{2}$/', $filter_month)) {
    $filter_month = date('Y-m');
}

// Get monthly attendance summary by class
$summary_query = "SELECT c.class_name, c.year, c.section,
                  COUNT(DISTINCT sa.student_id) as total_students,
                  COUNT(sa.id) as total_records,
                  SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) as present_count,
                  SUM(CASE WHEN sa.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                  SUM(CASE WHEN sa.status = 'late' THEN 1 ELSE 0 END) as late_count
                  FROM classes c
                  LEFT JOIN student_attendance sa ON c.id = sa.class_id 
                  AND DATE_FORMAT(sa.attendance_date, '%Y-%m') = ?
                  WHERE c.department_id = ?
                  GROUP BY c.id, c.class_name, c.year, c.section
                  ORDER BY c.class_name";
$stmt = $conn->prepare($summary_query);
$stmt->bind_param("si", $filter_month, $department_id);
$stmt->execute();
$summary = $stmt->get_result();

// Get low attendance students (below 75%)
$low_attendance_query = "SELECT s.roll_number, s.full_name, c.class_name,
                         COUNT(sa.id) as total_days,
                         SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) as present_days
                         FROM students s
                         JOIN classes c ON s.class_id = c.id
                         LEFT JOIN student_attendance sa ON s.id = sa.student_id 
                         AND DATE_FORMAT(sa.attendance_date, '%Y-%m') = ?
                         WHERE s.department_id = ? AND s.is_active = 1
                         GROUP BY s.id, s.roll_number, s.full_name, c.class_name
                         HAVING COUNT(sa.id) > 0 
                         AND (SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) / COUNT(sa.id) * 100) < 75
                         ORDER BY (SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) / COUNT(sa.id) * 100) ASC";
$stmt = $conn->prepare($low_attendance_query);
$stmt->bind_param("si", $filter_month, $department_id);
$stmt->execute();
$low_attendance = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Reports - HOD Dashboard</title>
    <link rel="icon" href="../Nit_logo.png" type="image/png" />
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
        
        .navbar h1 {
            color: white;
            font-size: 24px;
            font-weight: 700;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            color: white;
        }

        .user-info span {
            color: white;
            font-weight: 500;
        }

        .main-content {
            padding: 40px;
            max-width: 1600px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        /* Filter Section */
        .filter-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            border: 2px solid rgba(255, 255, 255, 0.5);
        }

        .filter-container h3 {
            font-size: 20px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 20px;
        }

        .filter-form {
            display: flex;
            gap: 15px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .form-group {
            flex: 1;
            min-width: 200px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid rgba(102, 126, 234, 0.3);
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Modern Tables */
        .table-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            padding: 40px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
            margin: 30px 0;
            border: 2px solid rgba(255, 255, 255, 0.5);
            overflow-x: auto;
        }

        .table-container h3 {
            font-size: 24px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
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

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        .alert {
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
            font-weight: 500;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
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

        /* Footer */
        .footer {
            background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 50%, #2a3254 100%);
            position: relative;
            overflow: hidden;
            margin-top: 60px;
        }

        .footer-border {
            height: 2px;
            background: linear-gradient(90deg, #4a9eff, #00d4ff, #4a9eff, #00d4ff);
            background-size: 200% 100%;
            animation: borderMove 3s linear infinite;
        }

        @keyframes borderMove {
            0% { background-position: 0% 50%; }
            100% { background-position: 200% 50%; }
        }

        .footer-content {
            max-width: 1000px;
            margin: 0 auto;
            padding: 30px 20px 20px;
        }

        .developer-section {
            background: rgba(255, 255, 255, 0.03);
            padding: 20px;
            border-radius: 15px;
            border: 1px solid rgba(74, 158, 255, 0.15);
            text-align: center;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }

        .developer-section p {
            color: #ffffff;
            font-size: 14px;
            margin: 0 0 12px;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        .company-link {
            display: inline-block;
            color: #ffffff;
            font-size: 16px;
            font-weight: 700;
            text-decoration: none;
            padding: 8px 24px;
            border: 2px solid #4a9eff;
            border-radius: 30px;
            background: linear-gradient(135deg, rgba(74, 158, 255, 0.2), rgba(0, 212, 255, 0.2));
            box-shadow: 0 3px 12px rgba(74, 158, 255, 0.3);
            margin-bottom: 15px;
            transition: all 0.3s;
        }

        .company-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(74, 158, 255, 0.5);
        }

        .divider {
            width: 50%;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            margin: 15px auto;
        }

        .team-label {
            color: #888;
            font-size: 10px;
            margin: 0 0 12px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 600;
        }

        .developer-badges {
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 12px;
        }

        .developer-badge {
            color: #ffffff;
            font-size: 13px;
            text-decoration: none;
            padding: 8px 16px;
            background: linear-gradient(135deg, rgba(74, 158, 255, 0.25), rgba(0, 212, 255, 0.25));
            border-radius: 20px;
            border: 1px solid rgba(74, 158, 255, 0.4);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 3px 10px rgba(74, 158, 255, 0.2);
            transition: all 0.3s;
        }

        .developer-badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(74, 158, 255, 0.4);
        }

        .role-tags {
            margin-top: 15px;
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .role-tag {
            color: #4a9eff;
            font-size: 10px;
            padding: 4px 12px;
            background: rgba(74, 158, 255, 0.1);
            border-radius: 12px;
            border: 1px solid rgba(74, 158, 255, 0.3);
        }

        .footer-bottom {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .footer-bottom p {
            color: #888;
            font-size: 12px;
            margin: 0 0 10px;
        }

        .social-links {
            margin-top: 15px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .social-link {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(74, 158, 255, 0.1);
            border: 1px solid rgba(74, 158, 255, 0.3);
            border-radius: 50%;
            color: #4a9eff;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }

        .social-link:hover {
            background: rgba(74, 158, 255, 0.3);
            transform: translateY(-3px);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .navbar { 
                padding: 15px 20px; 
                flex-direction: column; 
                gap: 15px; 
            }
            .navbar h1 { font-size: 18px; }
            .main-content { padding: 20px; }
            .table-container { padding: 20px; }
            .user-info { flex-direction: column; gap: 10px; width: 100%; }
            .filter-form { flex-direction: column; }
        }
    </style>
</head>
<body>
    <!-- Animated Background Particles -->
    <div class="particles">
        <div class="particle" style="width: 80px; height: 80px; left: 10%; animation-delay: 0s;"></div>
        <div class="particle" style="width: 60px; height: 60px; left: 20%; animation-delay: 2s;"></div>
        <div class="particle" style="width: 100px; height: 100px; left: 35%; animation-delay: 4s;"></div>
        <div class="particle" style="width: 50px; height: 50px; left: 50%; animation-delay: 1s;"></div>
        <div class="particle" style="width: 90px; height: 90px; left: 65%; animation-delay: 3s;"></div>
        <div class="particle" style="width: 70px; height: 70px; left: 80%; animation-delay: 5s;"></div>
    </div>

    <nav class="navbar">
        <div>
            <h1>🎓 <?php echo htmlspecialchars($department['dept_name']); ?> - Attendance Reports</h1>
        </div>
        <div class="user-info">
            <a href="index.php" class="btn btn-secondary">← Back</a>
            <span>👔 <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <!-- Filter Section -->
        <div class="filter-container">
            <h3>🔍 Select Reporting Period</h3>
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label>Month:</label>
                    <input type="month" name="month" value="<?php echo htmlspecialchars($filter_month); ?>" required>
                </div>
                <button type="submit" class="btn btn-primary">📊 View Report</button>
            </form>
        </div>

        <!-- Class-wise Summary -->
        <div class="table-container">
            <h3>📊 Class-wise Attendance Summary - <?php echo date('F Y', strtotime($filter_month.'-01')); ?></h3>
            
            <?php if ($summary && $summary->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Class Name</th>
                            <th>Year</th>
                            <th>Section</th>
                            <th>Students</th>
                            <th>Total Records</th>
                            <th>Present</th>
                            <th>Absent</th>
                            <th>Late</th>
                            <th>Attendance %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $summary->fetch_assoc()): 
                            $percentage = $row['total_records'] > 0 
                                ? round(($row['present_count'] / $row['total_records']) * 100, 2) 
                                : 0;
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['class_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['year']); ?></td>
                            <td><span class="badge badge-info"><?php echo htmlspecialchars($row['section']); ?></span></td>
                            <td><?php echo $row['total_students']; ?></td>
                            <td><?php echo $row['total_records']; ?></td>
                            <td><span class="badge badge-success"><?php echo $row['present_count']; ?></span></td>
                            <td><span class="badge badge-danger"><?php echo $row['absent_count']; ?></span></td>
                            <td><span class="badge badge-warning"><?php echo $row['late_count']; ?></span></td>
                            <td>
                                <strong style="color: <?php echo $percentage >= 75 ? '#28a745' : '#dc3545'; ?>; font-size: 16px;">
                                    <?php echo $percentage; ?>%
                                </strong>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">📋 No attendance data found for the selected month.</div>
            <?php endif; ?>
        </div>

        <!-- Low Attendance Students -->
        <?php if ($low_attendance && $low_attendance->num_rows > 0): ?>
        <div class="table-container">
            <h3>⚠️ Students with Low Attendance (Below 75%)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Roll Number</th>
                        <th>Name</th>
                        <th>Class</th>
                        <th>Total Days</th>
                        <th>Present</th>
                        <th>Attendance %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($student = $low_attendance->fetch_assoc()): 
                        $percentage = round(($student['present_days'] / $student['total_days']) * 100, 2);
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($student['roll_number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($student['class_name']); ?></td>
                        <td><?php echo $student['total_days']; ?></td>
                        <td><?php echo $student['present_days']; ?></td>
                        <td>
                            <strong style="color: #dc3545; font-size: 16px;">
                                <?php echo $percentage; ?>%
                            </strong>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-border"></div>
        <div class="footer-content">
            <div class="developer-section">
                <p>✨ Designed & Developed by</p>
                <a href="https://himanshufullstackdeveloper.github.io/techyugsoftware/" class="company-link">
                    🚀 Techyug Software Pvt. Ltd.
                </a>
                <div class="divider"></div>
                <p class="team-label">💼 Development Team</p>
                <div class="developer-badges">
                    <a href="https://himanshufullstackdeveloper.github.io/portfoilohimanshu/" class="developer-badge">
                        <span>👨‍💻</span>
                        <span>Himanshu Patil</span>
                    </a>
                    <a href="https://devpranaypanore.github.io/Pranaypanore-live-.html/" class="developer-badge">
                        <span>👨‍💻</span>
                        <span>Pranay Panore</span>
                    </a>
                </div>
                <div class="role-tags">
                    <span class="role-tag">Full Stack</span>
                    <span class="role-tag">UI/UX</span>
                    <span class="role-tag">Database</span>
                </div>
            </div>
            <div class="footer-bottom">
                <p>© 2025 NIT AMMS. All rights reserved.</p>
                <p>Made with <span style="color: #ff4757;">❤️</span> by Techyug Software</p>
                <div class="social-links">
                    <a href="#" class="social-link">📧</a>
                    <a href="#" class="social-link">🌐</a>
                    <a href="#" class="social-link">💼</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>