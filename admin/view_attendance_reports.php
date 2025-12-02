<?php
require_once '../db.php';
checkRole(['admin']);

$user = getCurrentUser();

// Date range filters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get current academic year for semester display
$current_year = date('Y');
$semester_name = "First Semester " . $current_year;

// Extract department/class from class_name (before the first " - ")
$departments_query = "SELECT 
    SUBSTRING_INDEX(class_name, ' - ', 1) as department,
    GROUP_CONCAT(id) as class_ids,
    COUNT(*) as section_count
FROM classes 
GROUP BY SUBSTRING_INDEX(class_name, ' - ', 1)
ORDER BY department";

$departments_result = $conn->query($departments_query);
$departments = [];
while ($dept = $departments_result->fetch_assoc()) {
    $departments[] = $dept;
}

// Get total enrolled students per department
$enrollment_data = [];
$total_enrollment = 0;
foreach ($departments as $dept) {
    $class_ids = $dept['class_ids'];
    $enrollment_query = "SELECT COUNT(DISTINCT s.id) as total_enrolled
                         FROM students s
                         WHERE s.class_id IN ($class_ids)";
    $enrollment_result = $conn->query($enrollment_query);
    $row = $enrollment_result->fetch_assoc();
    $enrollment_data[$dept['department']] = $row['total_enrolled'];
    $total_enrollment += $row['total_enrolled'];
}

// Get date-wise attendance data by department
$attendance_query = "SELECT 
    sa.attendance_date,
    SUBSTRING_INDEX(c.class_name, ' - ', 1) as department,
    COUNT(DISTINCT CASE WHEN sa.status = 'present' THEN sa.student_id END) as present_count
FROM student_attendance sa
JOIN students s ON sa.student_id = s.id
JOIN classes c ON sa.class_id = c.id
WHERE sa.attendance_date BETWEEN '$start_date' AND '$end_date'
GROUP BY sa.attendance_date, SUBSTRING_INDEX(c.class_name, ' - ', 1)
ORDER BY sa.attendance_date DESC";

$attendance_result = $conn->query($attendance_query);

// Organize data by date
$date_wise_data = [];
while ($row = $attendance_result->fetch_assoc()) {
    $date = $row['attendance_date'];
    if (!isset($date_wise_data[$date])) {
        $date_wise_data[$date] = [];
    }
    $date_wise_data[$date][$row['department']] = $row['present_count'];
}

// Get overall stats
$overall_stats_query = "SELECT 
    COUNT(DISTINCT CASE WHEN status = 'present' THEN student_id END) as total_present
FROM student_attendance sa
JOIN classes c ON sa.class_id = c.id
WHERE sa.attendance_date BETWEEN '$start_date' AND '$end_date'";
$overall_stats = $conn->query($overall_stats_query)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Attendance Report</title>
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
            font-family: 'Arial', 'Helvetica', sans-serif;
            padding: 20px;
        }

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
            margin-bottom: 30px;
            border-radius: 16px;
        }
        
        .navbar h1 {
            color: white;
            font-size: 24px;
            font-weight: 700;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 25px;
            color: white;
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
            background: #6c757d;
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b, #ee5a5a);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #11998e, #38ef7d);
            color: white;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-print {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .main-content {
            max-width: 1600px;
            margin: 0 auto;
        }

        .filter-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        }

        .filter-container h3 {
            color: #1a1a2e;
            margin: 0 0 20px;
            font-size: 24px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: end;
        }

        .form-group label {
            color: #64748b;
            font-weight: 600;
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 14px;
            background: white;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .btn-filter {
            padding: 14px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.5);
        }

        /* Report Container */
        .report-container {
            background: white;
            border-radius: 16px;
            padding: 50px 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            margin-bottom: 30px;
        }

        .report-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 4px solid #003d82;
        }

        .report-header h1 {
            font-size: 42px;
            font-weight: 900;
            color: #000;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 3px;
        }

        .report-header h2 {
            font-size: 32px;
            font-weight: 700;
            color: #003d82;
            margin-top: 10px;
        }

        .export-buttons {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-bottom: 30px;
        }

        /* Excel-style Table */
        .excel-table {
            width: 100%;
            border-collapse: collapse;
            border: 3px solid #000;
            margin-top: 20px;
        }

        .excel-table thead tr {
            background: #003d82;
            color: white;
        }

        .excel-table th {
            padding: 18px 15px;
            text-align: center;
            font-weight: 900;
            font-size: 17px;
            border: 2px solid #000;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .excel-table tbody tr {
            background: white;
        }

        .excel-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        .excel-table tbody tr:hover {
            background: #e3f2fd;
        }

        .excel-table td {
            padding: 16px 15px;
            font-size: 16px;
            color: #000;
            text-align: center;
            border: 2px solid #000;
            font-weight: 500;
        }

        .excel-table td:first-child {
            font-weight: 700;
            background: #e9ecef;
        }

        .excel-table td:nth-child(2) {
            font-weight: 700;
            text-align: center;
            background: #e9ecef;
        }

        .total-column {
            background: #fff3cd !important;
            font-weight: 900 !important;
            font-size: 18px !important;
            color: #000 !important;
        }

        .percentage-column {
            background: #d4edda !important;
            font-weight: 900 !important;
            font-size: 17px !important;
        }

        .percentage-high {
            color: #155724 !important;
        }

        .percentage-medium {
            color: #856404 !important;
        }

        .percentage-low {
            color: #721c24 !important;
        }

        .summary-row {
            background: #003d82 !important;
            color: white !important;
            font-weight: 900 !important;
        }

        .summary-row td {
            font-size: 18px !important;
            padding: 20px 15px !important;
            border: 3px solid #000 !important;
        }

        .no-data {
            text-align: center;
            padding: 60px 40px;
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border-radius: 20px;
        }

        .no-data h3 {
            color: #64748b;
            margin: 0 0 10px;
        }

        .no-data p {
            color: #94a3b8;
            margin: 0;
        }

       /* ========================================
   RESPONSIVE MEDIA QUERIES
   ======================================== */

/* Extra Large Screens (1400px and up) */
@media (min-width: 1400px) {
    .main-content {
        max-width: 1800px;
    }
    
    .excel-table th,
    .excel-table td {
        padding: 20px 18px;
        font-size: 17px;
    }
}

/* Large Tablets and Small Desktops (992px - 1199px) */
@media (max-width: 1199px) {
    .navbar {
        padding: 15px 30px;
    }
    
    .navbar h1 {
        font-size: 20px;
    }
    
    .report-header h1 {
        font-size: 36px;
    }
    
    .report-header h2 {
        font-size: 28px;
    }
    
    .excel-table th,
    .excel-table td {
        padding: 14px 12px;
        font-size: 15px;
    }
}

/* Tablets (768px - 991px) */
@media (max-width: 991px) {
    body {
        padding: 15px;
    }
    
    .navbar {
        flex-direction: column;
        gap: 15px;
        padding: 20px;
        text-align: center;
    }
    
    .user-info {
        flex-direction: column;
        gap: 10px;
        width: 100%;
    }
    
    .btn {
        padding: 10px 20px;
        font-size: 13px;
        width: 100%;
        text-align: center;
    }
    
    .filter-form {
        grid-template-columns: 1fr;
    }
    
    .report-container {
        padding: 30px 20px;
    }
    
    .report-header h1 {
        font-size: 30px;
        letter-spacing: 2px;
    }
    
    .report-header h2 {
        font-size: 24px;
    }
    
    .export-buttons {
        flex-direction: column;
    }
    
    .excel-table {
        font-size: 13px;
    }
    
    .excel-table th,
    .excel-table td {
        padding: 12px 8px;
        font-size: 13px;
    }
    
    .excel-table th {
        font-size: 14px;
    }
}

/* Mobile Landscape and Small Tablets (576px - 767px) */
@media (max-width: 767px) {
    body {
        padding: 10px;
    }
    
    .navbar {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 12px;
    }
    
    .navbar h1 {
        font-size: 16px;
    }
    
    .filter-container {
        padding: 20px;
        border-radius: 16px;
        margin-bottom: 20px;
    }
    
    .filter-container h3 {
        font-size: 20px;
    }
    
    .form-group input {
        padding: 12px;
        font-size: 13px;
    }
    
    .btn-filter {
        padding: 12px 24px;
        font-size: 13px;
    }
    
    .report-container {
        padding: 15px 5px;
        border-radius: 12px;
        overflow-x: hidden;
    }
    
    .report-header {
        margin-bottom: 20px;
        padding: 0 10px;
    }
    
    .report-header h1 {
        font-size: 22px;
        letter-spacing: 1px;
        word-break: break-word;
    }
    
    .report-header h2 {
        font-size: 18px;
        word-break: break-word;
    }
    
    .export-buttons {
        margin-bottom: 20px;
        padding: 0 10px;
    }
    
    /* Table wrapper for horizontal scroll */
    .excel-table {
        display: block;
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border: 2px solid #000;
        font-size: 11px;
    }
    
    .excel-table thead,
    .excel-table tbody {
        display: table;
        width: 100%;
    }
    
    .excel-table tr {
        display: table-row;
    }
    
    /* Header cells - prevent text overlap */
    .excel-table th {
        padding: 10px 4px;
        font-size: 10px;
        white-space: normal;
        word-wrap: break-word;
        line-height: 1.2;
        min-width: 45px;
        vertical-align: middle;
        letter-spacing: 0;
    }
    
    /* Data cells */
    .excel-table td {
        padding: 10px 4px;
        font-size: 11px;
        white-space: nowrap;
        min-width: 45px;
        vertical-align: middle;
    }
    
    /* First two columns (SR NO and DATE) */
    .excel-table td:first-child {
        min-width: 40px;
        font-size: 11px;
    }
    
    .excel-table td:nth-child(2) {
        min-width: 75px;
        font-size: 10px;
    }
    
    /* Department columns */
    .excel-table td:nth-child(n+3):nth-last-child(n+4) {
        min-width: 40px;
    }
    
    /* Total column */
    .total-column {
        font-size: 13px !important;
        min-width: 50px !important;
        font-weight: 900 !important;
    }
    
    /* Percentage column */
    .percentage-column {
        font-size: 12px !important;
        min-width: 55px !important;
        font-weight: 800 !important;
    }
    
    /* Remark column */
    .excel-table td:last-child {
        min-width: 45px;
    }
    
    /* Summary row */
    .summary-row td {
        font-size: 12px !important;
        padding: 12px 4px !important;
        white-space: normal !important;
        word-wrap: break-word !important;
    }
    
    .summary-row td:first-child {
        font-size: 10px !important;
    }
    
    .no-data {
        padding: 40px 20px;
    }
    
    .no-data h3 {
        font-size: 18px;
    }
}

/* Mobile Portrait (up to 575px) */
@media (max-width: 575px) {
    body {
        padding: 5px;
    }
    
    .navbar {
        padding: 10px;
    }
    
    .navbar h1 {
        font-size: 13px;
    }
    
    .user-info {
        font-size: 12px;
    }
    
    .btn {
        padding: 8px 14px;
        font-size: 11px;
        border-radius: 8px;
    }
    
    .filter-container {
        padding: 12px;
        margin-bottom: 15px;
    }
    
    .filter-container h3 {
        font-size: 16px;
        margin-bottom: 12px;
    }
    
    .form-group label {
        font-size: 11px;
        margin-bottom: 6px;
    }
    
    .form-group input {
        padding: 10px;
        font-size: 12px;
    }
    
    .btn-filter {
        padding: 10px 18px;
        font-size: 12px;
    }
    
    .report-container {
        padding: 10px 3px;
        border-radius: 10px;
    }
    
    .report-header {
        padding: 0 8px;
        margin-bottom: 15px;
    }
    
    .report-header h1 {
        font-size: 18px;
        letter-spacing: 0.5px;
    }
    
    .report-header h2 {
        font-size: 15px;
    }
    
    .export-buttons {
        gap: 8px;
        padding: 0 8px;
        margin-bottom: 15px;
    }
    
    /* Table styling for very small screens */
    .excel-table {
        font-size: 9px;
        border: 1px solid #000;
    }
    
    /* Header styling - wrap text properly */
    .excel-table th {
        padding: 8px 2px;
        font-size: 8px;
        white-space: normal;
        word-wrap: break-word;
        line-height: 1.1;
        min-width: 35px;
        max-width: 70px;
        letter-spacing: 0;
        border: 1px solid #000;
    }
    
    /* Data cells */
    .excel-table td {
        padding: 8px 2px;
        font-size: 10px;
        border: 1px solid #000;
        min-width: 35px;
    }
    
    /* SR NO column */
    .excel-table td:first-child,
    .excel-table th:first-child {
        min-width: 30px;
        max-width: 35px;
    }
    
    /* DATE column */
    .excel-table td:nth-child(2),
    .excel-table th:nth-child(2) {
        min-width: 70px;
        max-width: 75px;
        font-size: 9px;
    }
    
    /* Department columns */
    .excel-table td:nth-child(n+3):nth-last-child(n+4),
    .excel-table th:nth-child(n+3):nth-last-child(n+4) {
        min-width: 35px;
        max-width: 45px;
    }
    
    /* Total column */
    .total-column {
        font-size: 11px !important;
        font-weight: 900 !important;
        min-width: 45px !important;
    }
    
    /* Percentage column */
    .percentage-column {
        font-size: 10px !important;
        font-weight: 800 !important;
        min-width: 50px !important;
    }
    
    /* Remark column */
    .excel-table td:last-child,
    .excel-table th:last-child {
        min-width: 35px;
        max-width: 40px;
    }
    
    /* Summary row */
    .summary-row td {
        font-size: 10px !important;
        padding: 10px 2px !important;
        border: 1px solid #000 !important;
        white-space: normal !important;
    }
    
    .summary-row td:first-child {
        font-size: 8px !important;
        line-height: 1.2;
    }
    
    .no-data {
        padding: 25px 12px;
    }
    
    .no-data h3 {
        font-size: 15px;
    }
    
    .no-data p {
        font-size: 12px;
    }
}

/* Extra Small Mobile (up to 375px) */
@media (max-width: 375px) {
    .navbar h1 {
        font-size: 11px;
    }
    
    .report-header h1 {
        font-size: 16px;
    }
    
    .report-header h2 {
        font-size: 14px;
    }
    
    .excel-table {
        font-size: 8px;
    }
    
    .excel-table th {
        padding: 6px 2px;
        font-size: 7px;
        line-height: 1.1;
        min-width: 30px;
        max-width: 60px;
    }
    
    .excel-table td {
        padding: 6px 2px;
        font-size: 9px;
        min-width: 30px;
    }
    
    .excel-table td:nth-child(2) {
        font-size: 8px;
        min-width: 65px;
    }
    
    .total-column,
    .percentage-column {
        font-size: 10px !important;
    }
    
    .summary-row td {
        font-size: 9px !important;
        padding: 8px 2px !important;
    }
    
    .summary-row td:first-child {
        font-size: 7px !important;
    }
}

/* Mobile table scroll indicator */
@media (max-width: 767px) {
    /* Add scroll hint */
    .report-container::after {
        content: '← Swipe to see more →';
        display: block;
        text-align: center;
        padding: 12px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        font-size: 12px;
        font-weight: 600;
        border-radius: 8px;
        margin-top: 15px;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.6; }
    }
    
    /* Table container with scroll shadow */
    .excel-table {
        background: 
            linear-gradient(90deg, white 0%, transparent 20px),
            linear-gradient(-90deg, white 0%, transparent 20px),
            linear-gradient(90deg, rgba(0,0,0,0.2) 0%, transparent 10px),
            linear-gradient(-90deg, rgba(0,0,0,0.2) 0%, transparent 10px);
        background-repeat: no-repeat;
        background-size: 20px 100%, 20px 100%, 10px 100%, 10px 100%;
        background-position: 0 0, 100% 0, 0 0, 100% 0;
        background-attachment: local, local, scroll, scroll;
    }
}

/* Print Styles */
@media print {
    body {
        background: white;
        padding: 0;
    }

    .navbar, 
    .filter-container, 
    .export-buttons, 
    .btn {
        display: none !important;
    }
    
    .report-container {
        box-shadow: none;
        border: none;
        padding: 20px;
        margin: 0;
    }
    
    .report-header {
        border-bottom: 3px solid #003d82;
        margin-bottom: 20px;
    }

    .excel-table {
        page-break-inside: auto;
        border-collapse: collapse;
    }

    .excel-table tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }
    
    .excel-table thead {
        display: table-header-group;
    }
    
    .excel-table tfoot {
        display: table-footer-group;
    }
    
    /* Ensure summary row prints at the end */
    .summary-row {
        page-break-inside: avoid;
    }
}

/* Landscape Orientation */
@media (max-width: 991px) and (orientation: landscape) {
    .navbar {
        flex-direction: row;
        padding: 15px 20px;
    }
    
    .user-info {
        flex-direction: row;
        gap: 15px;
    }
    
    .btn {
        width: auto;
    }
    
    .filter-form {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .excel-table th,
    .excel-table td {
        padding: 10px 8px;
    }
}

/* High DPI Screens (Retina) */
@media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
    .excel-table {
        border-width: 2px;
    }
    
    .excel-table th,
    .excel-table td {
        border-width: 1px;
    }
}

/* Accessibility - Reduced Motion */
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
    
    .btn,
    .btn-filter {
        transition: none;
    }
}

/* Dark Mode Support (Optional) */
@media (prefers-color-scheme: dark) {
    /* Add dark mode styles if needed */
    .excel-table tbody tr:nth-child(even) {
        background: #f8f9fa;
    }
}

        
    </style>
    <script>
        function exportToExcel() {
            const table = document.getElementById('attendanceTable');
            const html = table.outerHTML;
            const url = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'Daily_Attendance_Report_<?php echo $start_date; ?>_to_<?php echo $end_date; ?>.xls';
            link.click();
        }

        function printReport() {
            window.print();
        }
    </script>
</head>
<body>
    <nav class="navbar">
        <div><h1>📊 NIT AMMS - Daily Attendance Report</h1></div>
        <div class="user-info">
            <a href="index.php" class="btn btn-secondary">← Back to Reports</a>
            <span>👨‍💼 <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <div class="filter-container">
            <h3>🔍 Select Date Range</h3>
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label>📅 Start Date</label>
                    <input type="date" name="start_date" value="<?php echo $start_date; ?>" required>
                </div>
                <div class="form-group">
                    <label>📅 End Date</label>
                    <input type="date" name="end_date" value="<?php echo $end_date; ?>" required>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn-filter">🔎 Generate Report</button>
                </div>
            </form>
        </div>

        <div class="report-container">
            <div class="report-header">
                <h1>DAILY ATTENDANCE</h1>
                <h2><?php echo htmlspecialchars($semester_name); ?></h2>
            </div>

            <div class="export-buttons">
                <button onclick="exportToExcel()" class="btn btn-success">
                    📥 Export to Excel
                </button>
                <button onclick="printReport()" class="btn btn-print">
                    🖨️ Print
                </button>
            </div>

            <?php if (count($date_wise_data) > 0): ?>
            <table class="excel-table" id="attendanceTable">
                <thead>
                    <tr>
                        <th>SR NO</th>
                        <th>DATE</th>
                        <?php foreach ($departments as $dept): ?>
                            <th><?php echo strtoupper(htmlspecialchars($dept['department'])); ?></th>
                        <?php endforeach; ?>
                        <th>TOTAL</th>
                        <th>%</th>
                        <th>REMARK</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $sr_no = 1;
                    foreach ($date_wise_data as $date => $dept_data): 
                        $daily_total = 0;
                        
                        // Calculate daily total
                        foreach ($dept_data as $department => $present_count) {
                            $daily_total += $present_count;
                        }
                        
                        $daily_percentage = $total_enrollment > 0 ? round(($daily_total / $total_enrollment) * 100, 2) : 0;
                        
                        $percentage_class = 'percentage-high';
                        if ($daily_percentage < 75) $percentage_class = 'percentage-medium';
                        if ($daily_percentage < 50) $percentage_class = 'percentage-low';
                    ?>
                    <tr>
                        <td><?php echo $sr_no++; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($date)); ?></td>
                        
                        <?php foreach ($departments as $dept): ?>
                            <td>
                                <?php 
                                echo isset($dept_data[$dept['department']]) ? $dept_data[$dept['department']] : '0';
                                ?>
                            </td>
                        <?php endforeach; ?>
                        
                        <td class="total-column"><?php echo $daily_total; ?></td>
                        <td class="percentage-column <?php echo $percentage_class; ?>">
                            <?php echo $daily_percentage; ?>%
                        </td>
                        <td>-</td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <!-- Summary Row -->
                    <tr class="summary-row">
                        <td colspan="2">TOTAL / AVERAGE</td>
                        <?php foreach ($departments as $dept): ?>
                            <td>
                                <?php 
                                $dept_total = 0;
                                foreach ($date_wise_data as $date => $dept_data) {
                                    if (isset($dept_data[$dept['department']])) {
                                        $dept_total += $dept_data[$dept['department']];
                                    }
                                }
                                echo $dept_total;
                                ?>
                            </td>
                        <?php endforeach; ?>
                        <td>
                            <?php echo $overall_stats['total_present']; ?>
                        </td>
                        <td>
                            <?php 
                            $total_days = count($date_wise_data);
                            $avg_attendance = ($total_enrollment > 0 && $total_days > 0) ? 
                                round(($overall_stats['total_present'] / ($total_enrollment * $total_days)) * 100, 1) : 0;
                            echo $avg_attendance . '%';
                            ?>
                        </td>
                        <td>-</td>
                    </tr>
                </tbody>
            </table>
            <?php else: ?>
            <div class="no-data">
                <h3>📭 No Data Found</h3>
                <p>No attendance records found for the selected date range.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div style="background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 50%, #2a3254 100%); position: relative; overflow: hidden; margin-top: 50px; border-radius: 16px;">
        <div style="height: 2px; background: linear-gradient(90deg, #4a9eff, #00d4ff, #4a9eff, #00d4ff); background-size: 200% 100%;"></div>
        <div style="max-width: 1000px; margin: 0 auto; padding: 30px 20px 20px;">
            <div style="background: rgba(255, 255, 255, 0.03); padding: 20px 20px; border-radius: 15px; border: 1px solid rgba(74, 158, 255, 0.15); text-align: center; box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);">
                <p style="color: #ffffff; font-size: 14px; margin: 0 0 12px; font-weight: 500; letter-spacing: 0.5px;">✨ Designed & Developed by</p>
                <a href="https://himanshufullstackdeveloper.github.io/techyugsoftware/" style="display: inline-block; color: #ffffff; font-size: 16px; font-weight: 700; text-decoration: none; padding: 8px 24px; border: 2px solid #4a9eff; border-radius: 30px; background: linear-gradient(135deg, rgba(74, 158, 255, 0.2), rgba(0, 212, 255, 0.2)); box-shadow: 0 3px 12px rgba(74, 158, 255, 0.3); margin-bottom: 15px;">
                    🚀 Techyug Software Pvt. Ltd.
                </a>
                <div style="width: 50%; height: 1px; background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent); margin: 15px auto;"></div>
                <p style="color: #888; font-size: 10px; margin: 0 0 12px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600;">💼 Development Team</p>
                <div style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; margin-top: 12px;">
                    <a href="https://himanshufullstackdeveloper.github.io/portfoilohimanshu/" style="color: #ffffff; font-size: 13px; text-decoration: none; padding: 8px 16px; background: linear-gradient(135deg, rgba(74, 158, 255, 0.25), rgba(0, 212, 255, 0.25)); border-radius: 20px; border: 1px solid rgba(74, 158, 255, 0.4); display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 3px 10px rgba(74, 158, 255, 0.2);">
                        <span style="font-size: 16px;">👨‍💻</span>
                        <span style="font-weight: 600;">Himanshu Patil</span>
                    </a>
                    <a href="https://devpranaypanore.github.io/Pranaypanore-live-.html/" style="color: #ffffff; font-size: 13px; text-decoration: none; padding: 8px 16px; background: linear-gradient(135deg, rgba(74, 158, 255, 0.25), rgba(0, 212, 255, 0.25)); border-radius: 20px; border: 1px solid rgba(74, 158, 255, 0.4); display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 3px 10px rgba(74, 158, 255, 0.2);">
                        <span style="font-size: 16px;">👨‍💻</span>
                        <span style="font-weight: 600;">Pranay Panore</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html