<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../db.php';

// Check if HOD is logged in
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'hod') {
    header("Location: ../index.php");
    exit();
}

// Get HOD info
$user = getCurrentUser();
$department_id = $_SESSION['department_id'] ?? null;

// Handle form submission
$success_msg = '';
$error_msg = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $exam_date = mysqli_real_escape_string($conn, $_POST['exam_date']);
    $section = mysqli_real_escape_string($conn, $_POST['section']);
    $day = mysqli_real_escape_string($conn, $_POST['day']);
    $time = mysqli_real_escape_string($conn, $_POST['time']);
    $marks = mysqli_real_escape_string($conn, $_POST['marks']);
    
    $insert_query = "INSERT INTO nit_importnoticess (subject, exam_date, section, day, time, marks, created_at, department_id) 
                     VALUES ('$subject', '$exam_date', '$section', '$day', '$time', '$marks', NOW(), '$department_id')";
    
    if(mysqli_query($conn, $insert_query)) {
        $success_msg = "Exam schedule sent successfully!";
    } else {
        $error_msg = "Error: " . mysqli_error($conn);
    }
}

// Handle delete request
if(isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $delete_query = "DELETE FROM nit_importnoticess WHERE id = $delete_id AND department_id = '$department_id'";
    
    if(mysqli_query($conn, $delete_query)) {
        header("Location: hod_create_examtime.php?deleted=1");
        exit();
    }
}

// Fetch existing schedules for this department
$fetch_query = "SELECT * FROM nit_importnoticess WHERE department_id = '$department_id' ORDER BY exam_date ASC, time ASC";
$result = mysqli_query($conn, $fetch_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../Nit_logo.png" type="image/svg+xml" />
    <title>HOD - Create Exam Schedule</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            min-height: 100vh;
            padding: 20px;
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
        
        .header h2 {
            color: #333;
            font-size: 32px;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .header p {
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
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            font-weight: 500;
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        
        input[type="text"],
        input[type="date"],
        select {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
            font-family: inherit;
        }
        
        input:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23667eea' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            padding-right: 40px;
        }
        
        .btn-submit {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .table-section {
            margin-top: 50px;
        }
        
        .table-section h3 {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #667eea;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        thead {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }
        
        thead th {
            padding: 16px 12px;
            color: white;
            font-weight: 600;
            text-align: left;
            font-size: 14px;
        }
        
        tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s;
        }
        
        tbody tr:hover {
            background: rgba(102, 126, 234, 0.05);
        }
        
        tbody td {
            padding: 16px 12px;
            color: #333;
            font-size: 14px;
        }
        
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }
        
        .badge-marks {
            background: #fff3cd;
            color: #856404;
        }
        
        .btn-delete {
            background: linear-gradient(135deg, #ff6b6b, #ee5a5a);
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .nav-buttons {
                flex-direction: column;
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
            <h2>📋 Create Exam Schedule</h2>
            <p>Send exam schedules to students and teachers</p>
        </div>
        
        <div class="nav-buttons">
            <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
            <a href="view_teachers.php" class="btn btn-primary">👨‍🏫 View Teachers</a>
            <a href="view_students.php" class="btn btn-primary">👨‍🎓 View Students</a>
        </div>
        
        <?php if(isset($_GET['deleted'])): ?>
            <div class="alert alert-success">✅ Schedule deleted successfully!</div>
        <?php endif; ?>
        
        <?php if($success_msg): ?>
            <div class="alert alert-success">✅ <?php echo $success_msg; ?></div>
        <?php endif; ?>
        
        <?php if($error_msg): ?>
            <div class="alert alert-error">❌ <?php echo $error_msg; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-grid">
                <div class="form-group">
                    <label>📚 Subject Name</label>
                    <input type="text" name="subject" required placeholder="e.g., Communication Skills">
                </div>
                
                <div class="form-group">
                    <label>📅 Exam Date</label>
                    <input type="date" name="exam_date" required>
                </div>
                
                <div class="form-group">
                    <label>🏫 Section</label>
                    <input type="text" name="section" required placeholder="e.g., CSE A, CSE B & IT">
                </div>
                
                <div class="form-group">
                    <label>📆 Day</label>
                    <select name="day" required>
                        <option value="">Select Day</option>
                        <option value="MONDAY">MONDAY</option>
                        <option value="TUESDAY">TUESDAY</option>
                        <option value="WEDNESDAY">WEDNESDAY</option>
                        <option value="THURSDAY">THURSDAY</option>
                        <option value="FRIDAY">FRIDAY</option>
                        <option value="SATURDAY">SATURDAY</option>
                        <option value="SUNDAY">SUNDAY</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>🕐 Time</label>
                    <input type="text" name="time" required placeholder="e.g., 10:00 to 11:30 AM">
                </div>
                
                <div class="form-group">
                    <label>💯 Total Marks</label>
                    <input type="text" name="marks" required placeholder="e.g., 100">
                </div>
            </div>
            
            <button type="submit" class="btn-submit">📤 Send Schedule to All</button>
        </form>
        
        <div class="table-section">
            <h3>📅 Current Exam Schedules</h3>
            
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
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $sr_no = 1;
                        while($row = mysqli_fetch_assoc($result)): 
                        ?>
                        <tr>
                            <td><strong><?php echo $sr_no++; ?></strong></td>
                            <td><?php echo htmlspecialchars($row['subject']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($row['exam_date'])); ?></td>
                            <td><?php echo htmlspecialchars($row['section']); ?></td>
                            <td><strong><?php echo htmlspecialchars($row['day']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['time']); ?></td>
                            <td><span class="badge badge-marks"><?php echo htmlspecialchars($row['marks']); ?></span></td>
                            <td>
                                <button class="btn-delete" onclick="deleteSchedule(<?php echo $row['id']; ?>)">🗑️ Delete</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <h3>No Exam Schedules Yet</h3>
                    <p>Create your first exam schedule using the form above</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function deleteSchedule(id) {
            if(confirm('Are you sure you want to delete this schedule? This action cannot be undone.')) {
                window.location.href = 'hod_create_examtime.php?delete_id=' + id;
            }
        }
        
        // Set minimum date to today
        document.querySelector('input[type="date"]').min = new Date().toISOString().split('T')[0];
    </script>
</body>
</html>