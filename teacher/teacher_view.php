<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../db.php';

// Check if teacher is logged in
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../index.php");
    exit();
}

// Get teacher info
$user = getCurrentUser();
$department_id = $_SESSION['department_id'] ?? null;

// Fetch exam schedules for teacher's department
$fetch_query = "SELECT * FROM nit_importnoticess WHERE department_id = '$department_id' ORDER BY exam_date ASC, time ASC";
$result = mysqli_query($conn, $fetch_query);

// Get statistics
$total_exams = mysqli_num_rows($result);
$upcoming_exams = 0;
$today = date('Y-m-d');

mysqli_data_seek($result, 0);
while($row = mysqli_fetch_assoc($result)) {
    if($row['exam_date'] >= $today) {
        $upcoming_exams++;
    }
}
mysqli_data_seek($result, 0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../Nit_logo.png" type="image/svg+xml" />
    <title>Teacher - Exam Schedule</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
             background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%); 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
          
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        h1 {
            color: #333;
            font-size: 36px;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #11998e, #38ef7d);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .subtitle {
            color: #666;
            font-size: 16px;
        }
        
        .nav-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
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
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #11998e, #38ef7d);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }
        
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            padding: 25px;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(17, 153, 142, 0.3);
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(17, 153, 142, 0.4);
        }
        
        .stat-number {
            font-size: 42px;
            font-weight: 800;
            margin-bottom: 8px;
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.95;
            font-weight: 500;
        }
        
        .table-container {
            overflow-x: auto;
            margin-top: 30px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        thead {
            background: linear-gradient(135deg, #11998e, #38ef7d);
        }
        
        thead th {
            padding: 16px 12px;
            color: white;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s;
        }
        
        tbody tr:hover {
            background: rgba(17, 153, 142, 0.05);
            transform: translateX(5px);
        }
        
        tbody td {
            padding: 16px 12px;
            color: #333;
            font-size: 14px;
        }
        
        .badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .badge-marks {
            background: #ffc107;
            color: #000;
        }
        
        .badge-upcoming {
            background: #28a745;
            color: white;
        }
        
        .badge-past {
            background: #6c757d;
            color: white;
        }
        
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .no-data-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .print-btn {
            background: linear-gradient(135deg, #11998e, #38ef7d);
            color: white;
            padding: 14px 28px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            margin-top: 30px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .print-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(17, 153, 142, 0.4);
        }
        
        @media print {
            body {
                background: white;
            }
            .container {
                box-shadow: none;
            }
            .print-btn, .nav-buttons, .stats-bar {
                display: none;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            h1 {
                font-size: 28px;
            }
            
            .stats-bar {
                grid-template-columns: 1fr;
            }
            
            table {
                font-size: 12px;
            }
            
            thead th, tbody td {
                padding: 10px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎓 Examination Schedule</h1>
            <p class="subtitle">Teacher Dashboard - Monitor Exam Schedule</p>
        </div>
        
        <div class="nav-buttons">
            <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
           
        </div>
        
        <div class="stats-bar">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_exams; ?></div>
                <div class="stat-label">Total Exams</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $upcoming_exams; ?></div>
                <div class="stat-label">Upcoming Exams</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_exams - $upcoming_exams; ?></div>
                <div class="stat-label">Completed Exams</div>
            </div>
        </div>
        
        <div class="table-container">
            <?php if($result && mysqli_num_rows($result) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Sr.No</th>
                            <th>Subject</th>
                            <th>Date</th>
                            <th>Section</th>
                            <th>Day</th>
                            <th>Time</th>
                            <th>Marks</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $sr_no = 1;
                        mysqli_data_seek($result, 0);
                        while($row = mysqli_fetch_assoc($result)): 
                            $is_upcoming = $row['exam_date'] >= $today;
                        ?>
                        <tr>
                            <td><strong><?php echo $sr_no++; ?></strong></td>
                            <td><?php echo htmlspecialchars($row['subject']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($row['exam_date'])); ?></td>
                            <td><?php echo htmlspecialchars($row['section']); ?></td>
                            <td><strong><?php echo htmlspecialchars($row['day']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['time']); ?></td>
                            <td><span class="badge badge-marks"><?php echo htmlspecialchars($row['marks']); ?> Marks</span></td>
                            <td>
                                <?php if($is_upcoming): ?>
                                    <span class="badge badge-upcoming">Upcoming</span>
                                <?php else: ?>
                                    <span class="badge badge-past">Completed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                
                <center>
                    <button class="print-btn" onclick="window.print()">
                        <span>🖨️</span>
                        <span>Print Schedule</span>
                    </button>
                </center>
            <?php else: ?>
                <div class="no-data">
                    <div class="no-data-icon">📅</div>
                    <h3>No Exam Schedules Available</h3>
                    <p>Please check back later or contact your HOD</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>