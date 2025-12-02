<?php
require_once '../db.php';

// This script populates/updates the attendance_summary table
// Can be run manually or set up as a cron job

// Set execution time limit for large datasets
set_time_limit(300); // 5 minutes

// Start output buffering
ob_start();

// Get filter parameters
$filter_class = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
$filter_month = isset($_GET['month']) ? intval($_GET['month']) : 0;
$filter_year = isset($_GET['year']) ? intval($_GET['year']) : 0;
$filter_min_attendance = isset($_GET['min_attendance']) ? floatval($_GET['min_attendance']) : 0;
$update_data = isset($_GET['update']) ? true : false;

echo "<h2>🔄 Attendance Summary Management</h2>";
echo "<p>Started at: " . date('Y-m-d H:i:s') . "</p>";
echo "<hr>";

// Check if table exists
$table_check = $conn->query("SHOW TABLES LIKE 'attendance_summary'");
if ($table_check->num_rows === 0) {
    echo "<div style='color: red; padding: 20px; background: #f8d7da; border-radius: 10px;'>";
    echo "<h3>❌ ERROR: attendance_summary table does not exist!</h3>";
    echo "<p>Please run the SQL schema first to create the table.</p>";
    echo "<p>You can find the SQL script in the artifacts provided.</p>";
    echo "</div>";
    echo "<style>body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }</style>";
    ob_end_flush();
    exit;
}

$conn->begin_transaction();

try {
    // Only update data if update button is clicked
    if ($update_data) {
        // Step 1: Get current record count
        $count_query = "SELECT COUNT(*) as count FROM attendance_summary";
        $result = $conn->query($count_query);
        $old_count = $result->fetch_assoc()['count'];
        echo "<p>📊 Current records in attendance_summary: <strong>$old_count</strong></p>";
        
        // Step 2: Clear existing summary (full refresh approach)
        echo "<p>🗑️ Clearing existing summary data...</p>";
        $conn->query("DELETE FROM attendance_summary");
        echo "<p>✅ Cleared successfully</p>";
        
        // Step 3: Insert aggregated data
        echo "<p>📥 Calculating and inserting new summary data...</p>";
        $query = "INSERT INTO attendance_summary 
                  (student_id, class_id, month, year, total_days, present_days, absent_days, late_days, attendance_percentage)
                  SELECT 
                      sa.student_id,
                      s.class_id,
                      MONTH(sa.attendance_date) as month,
                      YEAR(sa.attendance_date) as year,
                      COUNT(*) as total_days,
                      SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) as present_days,
                      SUM(CASE WHEN sa.status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                      SUM(CASE WHEN sa.status = 'late' THEN 1 ELSE 0 END) as late_days,
                      ROUND((SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as attendance_percentage
                  FROM student_attendance sa
                  INNER JOIN students s ON sa.student_id = s.id
                  WHERE s.is_active = 1
                  GROUP BY sa.student_id, s.class_id, YEAR(sa.attendance_date), MONTH(sa.attendance_date)";
        
        $result = $conn->query($query);
        
        if (!$result) {
            throw new Exception("Insert failed: " . $conn->error);
        }
        
        $new_count = $conn->affected_rows;
        echo "<p>✅ Inserted <strong>$new_count</strong> summary records</p>";
        echo "<hr>";
    }
    
    // Step 4: Get statistics
    $stats_query = "SELECT 
                    COUNT(*) as total_records,
                    COUNT(DISTINCT student_id) as unique_students,
                    COUNT(DISTINCT class_id) as unique_classes,
                    ROUND(AVG(attendance_percentage), 2) as avg_attendance,
                    MIN(attendance_percentage) as min_attendance,
                    MAX(attendance_percentage) as max_attendance
                    FROM attendance_summary";
    
    $stats_result = $conn->query($stats_query);
    
    if (!$stats_result) {
        throw new Exception("Stats query failed: " . $conn->error);
    }
    
    $stats = $stats_result->fetch_assoc();
    
    echo "<hr>";
    echo "<h3>📈 Summary Statistics</h3>";
    echo "<ul>";
    echo "<li>Total Records: <strong>{$stats['total_records']}</strong></li>";
    echo "<li>Unique Students: <strong>{$stats['unique_students']}</strong></li>";
    echo "<li>Unique Classes: <strong>{$stats['unique_classes']}</strong></li>";
    echo "<li>Average Attendance: <strong>{$stats['avg_attendance']}%</strong></li>";
    echo "<li>Min Attendance: <strong>{$stats['min_attendance']}%</strong></li>";
    echo "<li>Max Attendance: <strong>{$stats['max_attendance']}%</strong></li>";
    echo "</ul>";
    
    // Display Update Button
    echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2196F3;'>";
    echo "<p style='margin: 0; font-size: 14px;'><strong>💡 Note:</strong> Click the button below to refresh/update the summary data from attendance records.</p>";
    echo "<a href='?update=1' style='display: inline-block; margin-top: 10px; padding: 10px 20px; background: #667eea; color: white; border-radius: 5px; text-decoration: none; font-weight: bold;'>🔄 Update Summary Data</a>";
    echo "</div>";
    
    // Filter Form
    echo "<hr>";
    echo "<div style='background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin: 20px 0;'>";
    echo "<h3 style='margin-top: 0; color: #667eea;'>🔍 Filter Records</h3>";
    echo "<form method='GET' action='' style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;'>";
    
    // Get classes for dropdown
    $classes_query = "SELECT id, class_name, section FROM classes ORDER BY class_name, section";
    $classes_result = $conn->query($classes_query);
    
    echo "<div>";
    echo "<label style='display: block; margin-bottom: 5px; font-weight: 600; color: #555;'>Class:</label>";
    echo "<select name='class_id' style='width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px;'>";
    echo "<option value='0'>All Classes</option>";
    while ($class = $classes_result->fetch_assoc()) {
        $selected = ($filter_class == $class['id']) ? 'selected' : '';
        echo "<option value='{$class['id']}' {$selected}>{$class['class_name']} - {$class['section']}</option>";
    }
    echo "</select>";
    echo "</div>";
    
    // Get available years
    $years_query = "SELECT DISTINCT year FROM attendance_summary ORDER BY year DESC";
    $years_result = $conn->query($years_query);
    
    echo "<div>";
    echo "<label style='display: block; margin-bottom: 5px; font-weight: 600; color: #555;'>Year:</label>";
    echo "<select name='year' style='width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px;'>";
    echo "<option value='0'>All Years</option>";
    while ($year_row = $years_result->fetch_assoc()) {
        $selected = ($filter_year == $year_row['year']) ? 'selected' : '';
        echo "<option value='{$year_row['year']}' {$selected}>{$year_row['year']}</option>";
    }
    echo "</select>";
    echo "</div>";
    
    echo "<div>";
    echo "<label style='display: block; margin-bottom: 5px; font-weight: 600; color: #555;'>Month:</label>";
    echo "<select name='month' style='width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px;'>";
    echo "<option value='0'>All Months</option>";
    $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    for ($i = 1; $i <= 12; $i++) {
        $selected = ($filter_month == $i) ? 'selected' : '';
        echo "<option value='{$i}' {$selected}>{$months[$i-1]}</option>";
    }
    echo "</select>";
    echo "</div>";
    
    echo "<div>";
    echo "<label style='display: block; margin-bottom: 5px; font-weight: 600; color: #555;'>Min Attendance %:</label>";
    echo "<input type='number' name='min_attendance' value='{$filter_min_attendance}' min='0' max='100' step='0.01' placeholder='0' style='width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px;'>";
    echo "</div>";
    
    echo "<div style='display: flex; align-items: flex-end; gap: 10px;'>";
    echo "<button type='submit' style='padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; flex: 1;'>Apply Filters</button>";
    echo "<a href='update_summary.php' style='padding: 10px 20px; background: #f093fb; color: white; border-radius: 5px; text-decoration: none; text-align: center; font-weight: 600; flex: 1;'>Reset</a>";
    echo "</div>";
    
    echo "</form>";
    echo "</div>";
    
    // Step 5: Show ALL data (not just sample) with filters
    echo "<hr>";
    echo "<h3>📋 ";
    
    // Build filter description
    $filter_desc = [];
    if ($filter_class > 0) {
        $class_info = $conn->query("SELECT class_name, section FROM classes WHERE id = $filter_class")->fetch_assoc();
        $filter_desc[] = "Class: {$class_info['class_name']} - {$class_info['section']}";
    }
    if ($filter_year > 0) $filter_desc[] = "Year: $filter_year";
    if ($filter_month > 0) {
        $month_name = date('F', mktime(0, 0, 0, $filter_month, 1));
        $filter_desc[] = "Month: $month_name";
    }
    if ($filter_min_attendance > 0) $filter_desc[] = "Min Attendance: {$filter_min_attendance}%";
    
    if (count($filter_desc) > 0) {
        echo "Filtered Attendance Records (" . implode(", ", $filter_desc) . ")";
    } else {
        echo "All Attendance Summary Records";
    }
    echo "</h3>";
    
    // Build WHERE clause for filters
    $where_conditions = [];
    if ($filter_class > 0) $where_conditions[] = "ats.class_id = $filter_class";
    if ($filter_year > 0) $where_conditions[] = "ats.year = $filter_year";
    if ($filter_month > 0) $where_conditions[] = "ats.month = $filter_month";
    if ($filter_min_attendance > 0) $where_conditions[] = "ats.attendance_percentage >= $filter_min_attendance";
    
    $where_clause = count($where_conditions) > 0 ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    $all_query = "SELECT 
                     ats.*, 
                     s.full_name, 
                     s.roll_number,
                     c.class_name,
                     c.section
                     FROM attendance_summary ats
                     JOIN students s ON ats.student_id = s.id
                     JOIN classes c ON ats.class_id = c.id
                     $where_clause
                     ORDER BY ats.year DESC, ats.month DESC, c.class_name, s.roll_number";
    
    $all_data = $conn->query($all_query);
    
    if ($all_data && $all_data->num_rows > 0) {
        echo "<p><strong>Total Records Found: {$all_data->num_rows}</strong></p>";
        echo "<div style='overflow-x: auto;'>";
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%; min-width: 1200px;'>";
        echo "<thead>";
        echo "<tr style='background: #667eea; color: white;'>";
        echo "<th>#</th>";
        echo "<th>Student Name</th>";
        echo "<th>Roll No</th>";
        echo "<th>Class</th>";
        echo "<th>Section</th>";
        echo "<th>Month/Year</th>";
        echo "<th>Total Days</th>";
        echo "<th>Present</th>";
        echo "<th>Absent</th>";
        echo "<th>Late</th>";
        echo "<th>Attendance %</th>";
        echo "<th>Last Updated</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";
        
        $counter = 1;
        while ($row = $all_data->fetch_assoc()) {
            $month_name = date('F', mktime(0, 0, 0, $row['month'], 1));
            $att_color = $row['attendance_percentage'] >= 75 ? 'green' : ($row['attendance_percentage'] >= 60 ? 'orange' : 'red');
            $row_bg = $counter % 2 == 0 ? '#f9f9f9' : 'white';
            
            echo "<tr style='background: {$row_bg};'>";
            echo "<td style='text-align: center;'>{$counter}</td>";
            echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
            echo "<td style='text-align: center;'>" . htmlspecialchars($row['roll_number']) . "</td>";
            echo "<td>" . htmlspecialchars($row['class_name']) . "</td>";
            echo "<td style='text-align: center;'>" . htmlspecialchars($row['section']) . "</td>";
            echo "<td>{$month_name} {$row['year']}</td>";
            echo "<td style='text-align: center;'>{$row['total_days']}</td>";
            echo "<td style='text-align: center; color: green; font-weight: bold;'>{$row['present_days']}</td>";
            echo "<td style='text-align: center; color: red; font-weight: bold;'>{$row['absent_days']}</td>";
            echo "<td style='text-align: center; color: orange; font-weight: bold;'>{$row['late_days']}</td>";
            echo "<td style='text-align: center; color: {$att_color}; font-weight: bold; font-size: 16px;'>{$row['attendance_percentage']}%</td>";
            echo "<td style='text-align: center; font-size: 12px; color: #666;'>" . date('M d, Y H:i', strtotime($row['last_updated'])) . "</td>";
            echo "</tr>";
            
            $counter++;
        }
        
        echo "</tbody>";
        echo "</table>";
        echo "</div>";
        
        // Additional breakdown by class
        echo "<hr>";
        echo "<h3>📊 Summary by Class</h3>";
        $class_where = "";
        if ($filter_year > 0) $class_where .= ($class_where ? " AND " : "WHERE ") . "ats.year = $filter_year";
        if ($filter_month > 0) $class_where .= ($class_where ? " AND " : "WHERE ") . "ats.month = $filter_month";
        if ($filter_min_attendance > 0) $class_where .= ($class_where ? " AND " : "WHERE ") . "ats.attendance_percentage >= $filter_min_attendance";
        
        $class_summary = "SELECT 
                          c.class_name,
                          c.section,
                          COUNT(DISTINCT ats.student_id) as total_students,
                          ROUND(AVG(ats.attendance_percentage), 2) as avg_attendance,
                          MIN(ats.attendance_percentage) as min_attendance,
                          MAX(ats.attendance_percentage) as max_attendance
                          FROM attendance_summary ats
                          JOIN classes c ON ats.class_id = c.id
                          $class_where
                          GROUP BY c.id
                          ORDER BY c.class_name, c.section";
        
        $class_result = $conn->query($class_summary);
        
        if ($class_result && $class_result->num_rows > 0) {
            echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%; background: white;'>";
            echo "<tr style='background: #764ba2; color: white;'>";
            echo "<th>Class Name</th>";
            echo "<th>Section</th>";
            echo "<th>Total Students</th>";
            echo "<th>Avg Attendance</th>";
            echo "<th>Min Attendance</th>";
            echo "<th>Max Attendance</th>";
            echo "</tr>";
            
            while ($class = $class_result->fetch_assoc()) {
                $avg_color = $class['avg_attendance'] >= 75 ? 'green' : ($class['avg_attendance'] >= 60 ? 'orange' : 'red');
                echo "<tr>";
                echo "<td>" . htmlspecialchars($class['class_name']) . "</td>";
                echo "<td style='text-align: center;'>" . htmlspecialchars($class['section']) . "</td>";
                echo "<td style='text-align: center;'>{$class['total_students']}</td>";
                echo "<td style='text-align: center; color: {$avg_color}; font-weight: bold;'>{$class['avg_attendance']}%</td>";
                echo "<td style='text-align: center;'>{$class['min_attendance']}%</td>";
                echo "<td style='text-align: center;'>{$class['max_attendance']}%</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Monthly breakdown
        echo "<hr>";
        echo "<h3>📅 Summary by Month</h3>";
        $month_where = "";
        if ($filter_class > 0) $month_where .= ($month_where ? " AND " : "WHERE ") . "ats.class_id = $filter_class";
        if ($filter_min_attendance > 0) $month_where .= ($month_where ? " AND " : "WHERE ") . "ats.attendance_percentage >= $filter_min_attendance";
        
        $month_summary = "SELECT 
                          ats.month,
                          ats.year,
                          COUNT(*) as total_records,
                          COUNT(DISTINCT ats.student_id) as unique_students,
                          ROUND(AVG(ats.attendance_percentage), 2) as avg_attendance
                          FROM attendance_summary ats
                          $month_where
                          GROUP BY ats.year, ats.month
                          ORDER BY ats.year DESC, ats.month DESC";
        
        $month_result = $conn->query($month_summary);
        
        if ($month_result && $month_result->num_rows > 0) {
            echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%; background: white;'>";
            echo "<tr style='background: #f093fb; color: white;'>";
            echo "<th>Month/Year</th>";
            echo "<th>Total Records</th>";
            echo "<th>Unique Students</th>";
            echo "<th>Average Attendance</th>";
            echo "</tr>";
            
            while ($month = $month_result->fetch_assoc()) {
                $month_name = date('F', mktime(0, 0, 0, $month['month'], 1));
                $avg_color = $month['avg_attendance'] >= 75 ? 'green' : ($month['avg_attendance'] >= 60 ? 'orange' : 'red');
                echo "<tr>";
                echo "<td>{$month_name} {$month['year']}</td>";
                echo "<td style='text-align: center;'>{$month['total_records']}</td>";
                echo "<td style='text-align: center;'>{$month['unique_students']}</td>";
                echo "<td style='text-align: center; color: {$avg_color}; font-weight: bold;'>{$month['avg_attendance']}%</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } else {
        echo "<p style='color: orange;'>⚠️ No records available yet. This could mean:</p>";
        echo "<ul>";
        echo "<li>No attendance has been marked yet</li>";
        echo "<li>All students are marked as inactive</li>";
        echo "<li>There's an issue with the data relationships</li>";
        echo "</ul>";
    }
    
    // Commit transaction
    $conn->commit();
    
    echo "<hr>";
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; border-left: 5px solid #28a745;'>";
    echo "<h3 style='color: #155724; margin: 0;'>✅ Attendance summary updated successfully!</h3>";
    echo "<p style='margin: 10px 0 0 0;'>Completed at: " . date('Y-m-d H:i:s') . "</p>";
    echo "<p style='margin: 10px 0 0 0;'><a href='index.php' style='color: #155724; font-weight: bold;'>← Return to Dashboard</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    $conn->rollback();
    
    echo "<hr>";
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 10px; border-left: 5px solid #dc3545;'>";
    echo "<h3 style='color: #721c24; margin: 0;'>❌ Error occurred!</h3>";
    echo "<p style='margin: 10px 0 0 0;'><strong>Error Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p style='margin: 10px 0 0 0;'><strong>Error Location:</strong> Line " . $e->getLine() . "</p>";
    echo "<hr style='margin: 15px 0;'>";
    echo "<h4>Troubleshooting Steps:</h4>";
    echo "<ol>";
    echo "<li>Ensure the attendance_summary table exists (run the SQL schema)</li>";
    echo "<li>Check that student_attendance table has data</li>";
    echo "<li>Verify all students have valid class_id assignments</li>";
    echo "<li>Check database error logs for more details</li>";
    echo "</ol>";
    echo "<p><a href='index.php' style='color: #721c24; font-weight: bold;'>← Return to Dashboard</a></p>";
    echo "</div>";
}

// Style the output
echo "<style>
    body { 
        font-family: 'Segoe UI', Arial, sans-serif; 
        padding: 20px; 
        background: #f5f5f5; 
        line-height: 1.6;
    }
    h2, h3 { 
        color: #333; 
        margin-top: 20px;
    }
    h2 {
        border-bottom: 3px solid #667eea;
        padding-bottom: 10px;
    }
    p { 
        line-height: 1.8;
        margin: 10px 0;
    }
    ul, ol { 
        line-height: 1.8; 
        margin: 10px 0;
        padding-left: 30px;
    }
    table { 
        background: white; 
        margin-top: 10px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    th { 
        text-align: left;
        padding: 12px 8px !important;
        font-weight: 600;
    }
    td {
        padding: 10px 8px !important;
    }
    hr {
        border: none;
        border-top: 2px solid #ddd;
        margin: 30px 0;
    }
    a {
        text-decoration: none;
        transition: all 0.3s;
    }
    a:hover {
        text-decoration: underline;
    }
    thead tr {
        position: sticky;
        top: 0;
        z-index: 10;
    }
    .container {
        max-width: 1400px;
        margin: 0 auto;
    }
</style>";

ob_end_flush();
?>