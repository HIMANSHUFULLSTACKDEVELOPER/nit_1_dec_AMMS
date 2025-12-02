<?php
require_once '../db.php';
checkRole(['parent']);

$parent_id = $_SESSION['user_id'];
$student_id = $_SESSION['student_id'];

// Get parent info
$parent = $conn->query("SELECT * FROM parents WHERE id = $parent_id")->fetch_assoc();

// Get student info
$student_query = "SELECT s.*, d.dept_name, c.class_name 
                  FROM students s
                  LEFT JOIN departments d ON s.department_id = d.id
                  LEFT JOIN classes c ON s.class_id = c.id
                  WHERE s.id = $student_id";
$student = $conn->query($student_query)->fetch_assoc();

// Get filter parameters
$filter_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$filter_year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Get attendance for selected month
$attendance_query = "SELECT * FROM student_attendance 
                     WHERE student_id = $student_id 
                     AND DATE_FORMAT(attendance_date, '%Y-%m') = '$filter_month'
                     ORDER BY attendance_date DESC";
$attendance_records = $conn->query($attendance_query);

// Get monthly statistics
$stats_query = "SELECT 
                COUNT(*) as total_days,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late
                FROM student_attendance
                WHERE student_id = $student_id 
                AND DATE_FORMAT(attendance_date, '%Y-%m') = '$filter_month'";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

$total_days = $stats['total_days'];
$percentage = $total_days > 0 ? round(($stats['present'] / $total_days) * 100, 2) : 0;

// Get yearly comparison
$yearly_query = "SELECT 
                 DATE_FORMAT(attendance_date, '%Y-%m') as month,
                 COUNT(*) as total,
                 SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present
                 FROM student_attendance
                 WHERE student_id = $student_id 
                 AND YEAR(attendance_date) = '$filter_year'
                 GROUP BY DATE_FORMAT(attendance_date, '%Y-%m')
                 ORDER BY month";
$yearly_data = $conn->query($yearly_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Child's Attendance Report - Parent</title>
    <link rel="stylesheet" href="../assets/style.css">
      <link rel="icon" href="../Nit_logo.png" type="image/svg+xml" />
</head>
<body class="dashboard-container">
    <nav class="navbar">
        <div>
            <h1>🎓 NIT AMMS- Child's Attendance Report</h1>
        </div>
        <div class="user-info">
            <a href="index.php" class="btn btn-secondary">← Back</a>
            <span>👨‍👩‍👦 <?php echo htmlspecialchars($parent['parent_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <div style="background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
            <h2>📊 Attendance Report - <?php echo htmlspecialchars($student['full_name']); ?></h2>
            <p><strong>Roll Number:</strong> <?php echo htmlspecialchars($student['roll_number']); ?></p>
            <p><strong>Class:</strong> <?php echo htmlspecialchars($student['class_name']); ?></p>
        </div>

        <div class="table-container" style="margin-bottom: 30px;">
            <h3>🔍 Filter Report</h3>
            <form method="GET" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 15px;">
                <div class="form-group">
                    <label>Select Month:</label>
                    <input type="month" name="month" value="<?php echo $filter_month; ?>">
                </div>
                
                <div class="form-group">
                    <label>Select Year:</label>
                    <select name="year">
                        <?php for($y = date('Y'); $y >= 2020; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php echo $filter_year == $y ? 'selected' : ''; ?>>
                                <?php echo $y; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="form-group" style="display: flex; align-items: flex-end;">
                    <button type="submit" class="btn btn-primary">View Report</button>
                </div>
            </form>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>📅 Total Days</h3>
                <div class="stat-value"><?php echo $total_days; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>✅ Present</h3>
                <div class="stat-value" style="color: #28a745;"><?php echo $stats['present']; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>❌ Absent</h3>
                <div class="stat-value" style="color: #dc3545;"><?php echo $stats['absent']; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>⏰ Late</h3>
                <div class="stat-value" style="color: #ffc107;"><?php echo $stats['late']; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>📈 Attendance %</h3>
                <div class="stat-value" style="color: <?php echo $percentage >= 75 ? '#28a745' : '#dc3545'; ?>">
                    <?php echo $percentage; ?>%
                </div>
            </div>
        </div>

        <?php if ($percentage < 75): ?>
            <div class="alert alert-warning" style="margin-top: 20px;">
                <strong>⚠️ Warning:</strong> Your child's attendance is below 75%. Please ensure regular attendance.
            </div>
        <?php endif; ?>

        <div class="table-container" style="margin-top: 30px;">
            <h3>📝 Detailed Attendance for <?php echo date('F Y', strtotime($filter_month.'-01')); ?></h3>
            
            <?php if ($attendance_records->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Day</th>
                            <th>Status</th>
                            <th>Remarks</th>
                            <th>Marked At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($record = $attendance_records->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d M Y', strtotime($record['attendance_date'])); ?></td>
                            <td><?php echo date('l', strtotime($record['attendance_date'])); ?></td>
                            <td>
                                <?php
                                $status_class = '';
                                if ($record['status'] === 'present') $status_class = 'badge-success';
                                elseif ($record['status'] === 'absent') $status_class = 'badge-danger';
                                else $status_class = 'badge-warning';
                                ?>
                                <span class="badge <?php echo $status_class; ?>">
                                    <?php echo strtoupper($record['status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($record['remarks'] ?? '-'); ?></td>
                            <td><?php echo date('H:i', strtotime($record['marked_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">No attendance records found for <?php echo date('F Y', strtotime($filter_month.'-01')); ?></div>
            <?php endif; ?>
        </div>

        <div class="table-container" style="margin-top: 30px;">
            <h3>📊 Yearly Comparison - <?php echo $filter_year; ?></h3>
            <table>
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Total Days</th>
                        <th>Present</th>
                        <th>Attendance %</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($year_data = $yearly_data->fetch_assoc()): 
                        $year_percentage = $year_data['total'] > 0 ? round(($year_data['present'] / $year_data['total']) * 100, 2) : 0;
                    ?>
                    <tr>
                        <td><?php echo date('F Y', strtotime($year_data['month'].'-01')); ?></td>
                        <td><?php echo $year_data['total']; ?></td>
                        <td><span class="badge badge-success"><?php echo $year_data['present']; ?></span></td>
                        <td>
                            <strong style="color: <?php echo $year_percentage >= 75 ? '#28a745' : '#dc3545'; ?>">
                                <?php echo $year_percentage; ?>%
                            </strong>
                        </td>
                        <td>
                            <?php if ($year_percentage >= 75): ?>
                                <span class="badge badge-success">✅ Good</span>
                            <?php else: ?>
                                <span class="badge badge-danger">⚠️ Low</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

     <!-- Compact Footer -->
    <div style="background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 50%, #2a3254 100%); position: relative; overflow: hidden;">
        
        <!-- Animated Top Border -->
        <div style="height: 2px; background: linear-gradient(90deg, #4a9eff, #00d4ff, #4a9eff, #00d4ff); background-size: 200% 100%;"></div>
        
        <!-- Main Footer Container -->
        <div style="max-width: 1000px; margin: 0 auto; padding: 30px 20px 20px;">
            
            <!-- Developer Section -->
            <div style="background: rgba(255, 255, 255, 0.03); padding: 20px 20px; border-radius: 15px; border: 1px solid rgba(74, 158, 255, 0.15); text-align: center; box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);">
                
                <!-- Title -->
                <p style="color: #ffffff; font-size: 14px; margin: 0 0 12px; font-weight: 500; letter-spacing: 0.5px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">✨ Designed & Developed by</p>
                
                <!-- Company Link -->
                <a href="https://himanshufullstackdeveloper.github.io/techyugsoftware/" style="display: inline-block; color: #ffffff; font-size: 16px; font-weight: 700; text-decoration: none; padding: 8px 24px; border: 2px solid #4a9eff; border-radius: 30px; background: linear-gradient(135deg, rgba(74, 158, 255, 0.2), rgba(0, 212, 255, 0.2)); box-shadow: 0 3px 12px rgba(74, 158, 255, 0.3); margin-bottom: 15px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
                    🚀 Techyug Software Pvt. Ltd.
                </a>
                
                <!-- Divider -->
                <div style="width: 50%; height: 1px; background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent); margin: 15px auto;"></div>
                
                <!-- Team Label -->
                <p style="color: #888; font-size: 10px; margin: 0 0 12px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">💼 Development Team</p>
                
                <!-- Developer Badges -->
                <div style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; margin-top: 12px;">
                    
                    <!-- Developer 1 -->
                    <a href="https://himanshufullstackdeveloper.github.io/portfoilohimanshu/" style="color: #ffffff; font-size: 13px; text-decoration: none; padding: 8px 16px; background: linear-gradient(135deg, rgba(74, 158, 255, 0.25), rgba(0, 212, 255, 0.25)); border-radius: 20px; border: 1px solid rgba(74, 158, 255, 0.4); display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 3px 10px rgba(74, 158, 255, 0.2); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
                        <span style="font-size: 16px;">👨‍💻</span>
                        <span style="font-weight: 600;">Himanshu Patil</span>
                    </a>
                    
                    <!-- Developer 2 -->
                    <a href="https://devpranaypanore.github.io/Pranaypanore-live-.html/" style="color: #ffffff; font-size: 13px; text-decoration: none; padding: 8px 16px; background: linear-gradient(135deg, rgba(74, 158, 255, 0.25), rgba(0, 212, 255, 0.25)); border-radius: 20px; border: 1px solid rgba(74, 158, 255, 0.4); display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 3px 10px rgba(74, 158, 255, 0.2); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
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
                    <a href="#" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; background: rgba(74, 158, 255, 0.1); border: 1px solid rgba(74, 158, 255, 0.3); border-radius: 50%; color: #4a9eff; text-decoration: none; font-size: 14px;">📧</a>
                    <a href="#" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; background: rgba(74, 158, 255, 0.1); border: 1px solid rgba(74, 158, 255, 0.3); border-radius: 50%; color: #4a9eff; text-decoration: none; font-size: 14px;">🌐</a>
                    <a href="#" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; background: rgba(74, 158, 255, 0.1); border: 1px solid rgba(74, 158, 255, 0.3); border-radius: 50%; color: #4a9eff; text-decoration: none; font-size: 14px;">💼</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>