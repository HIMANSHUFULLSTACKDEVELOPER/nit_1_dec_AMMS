<?php
require_once '../db.php';
checkRole(['admin']);

$user = getCurrentUser();

// Get selected date (default to today)
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Get all sections for 1st Year
$sections_query = "SELECT DISTINCT section FROM classes WHERE year = 1 ORDER BY section";
$sections_result = $conn->query($sections_query);
$sections = [];
while ($row = $sections_result->fetch_assoc()) {
    $sections[] = $row['section'];
}

// Get attendance data for each section
$attendance_data = [];
$grand_total_students = 0;
$grand_total_present_first = 0;
$grand_total_present_fifth = 0;

foreach ($sections as $section) {
    // Get total students in this section
    $total_students_query = "SELECT COUNT(*) as count FROM students s 
                            WHERE s.class_id IN (SELECT id FROM classes WHERE section = '$section' AND year = 1)
                            AND s.is_active = 1";
    $total_students_result = $conn->query($total_students_query);
    $total_students = $total_students_result->fetch_assoc()['count'];
    
    // Get attendance for 1st lecture (assuming morning session before 12 PM)
    $first_lecture_query = "SELECT COUNT(DISTINCT sa.student_id) as count 
                           FROM student_attendance sa
                           JOIN classes c ON sa.class_id = c.id
                           WHERE c.section = '$section' 
                           AND c.year = 1
                           AND sa.attendance_date = '$selected_date'
                           AND sa.status = 'present'
                           AND HOUR(sa.marked_at) < 12";
    $first_lecture_result = $conn->query($first_lecture_query);
    $first_lecture_present = $first_lecture_result->fetch_assoc()['count'];
    
    // Get attendance for 5th lecture (assuming afternoon session after 12 PM)
    $fifth_lecture_query = "SELECT COUNT(DISTINCT sa.student_id) as count 
                           FROM student_attendance sa
                           JOIN classes c ON sa.class_id = c.id
                           WHERE c.section = '$section' 
                           AND c.year = 1
                           AND sa.attendance_date = '$selected_date'
                           AND sa.status = 'present'
                           AND HOUR(sa.marked_at) >= 12";
    $fifth_lecture_result = $conn->query($fifth_lecture_query);
    $fifth_lecture_present = $fifth_lecture_result->fetch_assoc()['count'];
    
    $first_percentage = $total_students > 0 ? round(($first_lecture_present / $total_students) * 100, 2) : 0;
    $fifth_percentage = $total_students > 0 ? round(($fifth_lecture_present / $total_students) * 100, 2) : 0;
    
    $attendance_data[$section] = [
        'total_students' => $total_students,
        'first_lecture' => $first_lecture_present,
        'first_percentage' => $first_percentage,
        'fifth_lecture' => $fifth_lecture_present,
        'fifth_percentage' => $fifth_percentage
    ];
    
    $grand_total_students += $total_students;
    $grand_total_present_first += $first_lecture_present;
    $grand_total_present_fifth += $fifth_lecture_present;
}

$grand_percentage_first = $grand_total_students > 0 ? round(($grand_total_present_first / $grand_total_students) * 100, 2) : 0;
$grand_percentage_fifth = $grand_total_students > 0 ? round(($grand_total_present_fifth / $grand_total_students) * 100, 2) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Attendance Report - NIT AMMS</title>
    <link rel="stylesheet" href="../assets/style.css">
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

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
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
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
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
        
        .report-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 40px;
            margin: 30px auto;
            max-width: 1200px;
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.5);
            position: relative;
            z-index: 1;
        }
        
        .report-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid transparent;
            background: linear-gradient(white, white) padding-box,
                        linear-gradient(135deg, #667eea, #764ba2) border-box;
            border-radius: 0 0 20px 20px;
            padding-bottom: 25px;
        }
        
        .report-header h1 {
            font-size: 32px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 0;
            font-weight: 800;
        }
        
        .report-header h2 {
            font-size: 20px;
            color: #666;
            margin: 10px 0 0 0;
            font-weight: 500;
        }
        
        .date-selector {
            text-align: center;
            margin-bottom: 30px;
            padding: 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(102, 126, 234, 0.4);
        }
        
        .date-selector label {
            font-size: 18px;
            font-weight: bold;
            margin-right: 10px;
            color: white;
        }
        
        .date-selector input[type="date"] {
            padding: 12px 20px;
            font-size: 16px;
            border: 2px solid white;
            border-radius: 12px;
            margin-right: 10px;
            background: rgba(255, 255, 255, 0.9);
        }
        
        .date-selector button {
            padding: 12px 30px;
            font-size: 16px;
            background: white;
            color: #667eea;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: bold;
            margin: 5px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .date-selector button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }
        
        .attendance-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 20px 0;
            font-size: 15px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .attendance-table th {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 18px 15px;
            text-align: center;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .attendance-table td {
            padding: 15px;
            text-align: center;
            background: #f8f9ff;
            border-bottom: 1px solid #e8e8e8;
            transition: all 0.3s;
        }

        .attendance-table tr:hover td {
            background: #f0f2ff;
        }
        
        .attendance-table tr.total-row td {
            background: linear-gradient(135deg, #667eea, #764ba2);
            font-weight: bold;
            color: white;
            font-size: 16px;
        }
        
        .attendance-table tr.year-header td {
            background: linear-gradient(135deg, #5a67d8, #6b46c1);
            font-weight: bold;
            text-align: left;
            padding-left: 25px;
            color: white;
            font-size: 16px;
        }
        
        .percentage-high {
            color: #10b981;
            font-weight: bold;
            text-shadow: 0 0 10px rgba(16, 185, 129, 0.3);
        }
        
        .percentage-medium {
            color: #f59e0b;
            font-weight: bold;
            text-shadow: 0 0 10px rgba(245, 158, 11, 0.3);
        }
        
        .percentage-low {
            color: #ef4444;
            font-weight: bold;
            text-shadow: 0 0 10px rgba(239, 68, 68, 0.3);
        }
        
        .summary-section {
            margin-top: 30px;
            padding: 30px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            border-radius: 20px;
            border-left: 5px solid #667eea;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .summary-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            border-left: 4px solid #667eea;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
        }

        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
        }
        
        .summary-card strong {
            display: block;
            color: #666;
            margin-bottom: 10px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .summary-card .value {
            font-size: 36px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .notes-section {
            margin-top: 30px;
            padding: 25px;
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-left: 4px solid #f59e0b;
            border-radius: 15px;
        }

        .notes-section strong {
            font-size: 18px;
            color: #92400e;
        }

        .notes-section ul {
            margin: 15px 0;
            padding-left: 25px;
            line-height: 2;
        }

        .notes-section li {
            color: #78350f;
        }

        .generated-info {
            margin-top: 30px;
            text-align: right;
            padding: 20px 0;
            border-top: 2px solid #e5e7eb;
            color: #666;
        }

        /* Modern Footer */
        .modern-footer {
            background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 50%, #2a3254 100%);
            position: relative;
            overflow: hidden;
            margin-top: 40px;
        }

        .footer-glow {
            height: 3px;
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb, #667eea);
            background-size: 200% 100%;
            animation: gradientShift 3s ease infinite;
        }

        .footer-content {
            max-width: 1000px;
            margin: 0 auto;
            padding: 35px 20px 25px;
        }

        .footer-card {
            background: rgba(255, 255, 255, 0.03);
            padding: 25px;
            border-radius: 20px;
            border: 1px solid rgba(102, 126, 234, 0.2);
            text-align: center;
        }

        .footer-card p {
            color: #ffffff;
            font-size: 14px;
            margin: 0 0 15px;
            font-weight: 500;
        }

        .footer-card a {
            display: inline-block;
            color: #ffffff;
            font-size: 16px;
            font-weight: 700;
            text-decoration: none;
            padding: 10px 28px;
            border: 2px solid #667eea;
            border-radius: 30px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.2), rgba(118, 75, 162, 0.2));
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
            transition: all 0.3s;
        }

        .footer-card a:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(102, 126, 234, 0.5);
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
            margin: 0;
        }
    /* ============================================
   COMPREHENSIVE RESPONSIVE DESIGN
   FOR DAILY ATTENDANCE REPORT
   ============================================ */

/* Extra Large Screens (1400px and above) */
@media (min-width: 1400px) {
    .report-container {
        max-width: 1400px;
        padding: 50px;
    }
    .attendance-table {
        font-size: 16px;
    }
    .attendance-table th,
    .attendance-table td {
        padding: 20px 18px;
    }
}

/* Large Screens (1024px to 1399px) */
@media (max-width: 1399px) and (min-width: 1024px) {
    .report-container {
        max-width: 1200px;
        padding: 40px;
        margin: 30px auto;
    }
    .report-header h1 {
        font-size: 28px;
    }
    .report-header h2 {
        font-size: 18px;
    }
    .date-selector {
        padding: 20px;
    }
    .attendance-table {
        font-size: 15px;
    }
    .attendance-table th,
    .attendance-table td {
        padding: 15px 12px;
    }
    .summary-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

/* Medium Screens / Tablets (768px to 1023px) */
@media (max-width: 1023px) and (min-width: 768px) {
    .navbar {
        padding: 15px 20px;
        flex-wrap: wrap;
        gap: 15px;
    }
    .navbar h1 {
        font-size: 18px;
        width: 100%;
        text-align: center;
    }
    .user-info {
        width: 100%;
        justify-content: space-around;
        gap: 10px;
        flex-wrap: wrap;
    }
    .user-profile {
        padding: 8px 15px;
        font-size: 13px;
        gap: 10px;
    }
    
    .report-container {
        margin: 20px;
        padding: 25px;
        border-radius: 20px;
    }
    .report-header {
        margin-bottom: 25px;
        padding-bottom: 20px;
    }
    .report-header h1 {
        font-size: 22px;
    }
    .report-header h2 {
        font-size: 16px;
    }
    
    .date-selector {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 15px;
    }
    .date-selector label {
        font-size: 15px;
    }
    .date-selector input[type="date"] {
        padding: 10px 15px;
        font-size: 14px;
        margin-bottom: 10px;
    }
    .date-selector button {
        padding: 10px 20px;
        font-size: 13px;
        width: auto;
        margin: 5px;
    }
    
    .attendance-table {
        font-size: 13px;
        margin: 15px 0;
    }
    .attendance-table th {
        padding: 12px 8px;
        font-size: 12px;
    }
    .attendance-table td {
        padding: 10px 8px;
    }
    .attendance-table tr.year-header td {
        padding-left: 15px;
        font-size: 14px;
    }
    .attendance-table tr.total-row td {
        font-size: 14px;
        padding: 12px 8px;
    }
    
    .summary-section {
        margin-top: 25px;
        padding: 20px;
    }
    .summary-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }
    .summary-card {
        padding: 18px;
    }
    .summary-card strong {
        font-size: 12px;
    }
    .summary-card .value {
        font-size: 28px;
    }
    
    .notes-section {
        padding: 20px;
        margin-top: 20px;
    }
    .notes-section strong {
        font-size: 16px;
    }
    .notes-section ul {
        line-height: 1.8;
    }
    .notes-section li {
        font-size: 13px;
    }
    
    .generated-info {
        font-size: 12px;
        padding: 15px 0;
    }
}

/* Small Tablets (600px to 767px) */
@media (max-width: 767px) and (min-width: 600px) {
    body {
        font-size: 13px;
    }
    
    .navbar {
        padding: 12px 15px;
        flex-direction: column;
        gap: 10px;
    }
    .navbar h1 {
        font-size: 16px;
        text-align: center;
    }
    .user-info {
        width: 100%;
        flex-direction: column;
        gap: 8px;
        align-items: center;
    }
    .user-profile {
        padding: 7px 12px;
        font-size: 12px;
        gap: 8px;
    }
    .btn {
        padding: 8px 16px;
        font-size: 12px;
    }
    .btn-secondary {
        width: 100%;
        text-align: center;
    }
    
    .report-container {
        margin: 12px;
        padding: 15px;
        border-radius: 15px;
    }
    .report-header {
        margin-bottom: 20px;
        padding-bottom: 15px;
    }
    .report-header h1 {
        font-size: 18px;
    }
    .report-header h2 {
        font-size: 14px;
    }
    
    .date-selector {
        padding: 12px;
        margin-bottom: 15px;
        border-radius: 12px;
    }
    .date-selector label {
        font-size: 13px;
        display: block;
        margin-bottom: 8px;
    }
    .date-selector input[type="date"] {
        padding: 8px 12px;
        font-size: 12px;
        margin-bottom: 8px;
        width: 100%;
        border-radius: 8px;
    }
    .date-selector button {
        padding: 8px 14px;
        font-size: 11px;
        margin: 3px;
        width: 48%;
        display: inline-block;
    }
    
    .attendance-table {
        font-size: 11px;
        margin: 12px 0;
        border-radius: 10px;
        overflow-x: auto;
        display: block;
    }
    .attendance-table thead,
    .attendance-table tbody,
    .attendance-table tr,
    .attendance-table th,
    .attendance-table td {
        display: block;
    }
    .attendance-table th {
        padding: 10px 6px;
        font-size: 11px;
    }
    .attendance-table td {
        padding: 8px 6px;
    }
    .attendance-table tr:not(:last-child) {
        margin-bottom: 10px;
    }
    .attendance-table tr.year-header {
        background: linear-gradient(135deg, #5a67d8, #6b46c1) !important;
        margin: 8px 0;
    }
    .attendance-table tr.year-header td {
        padding: 10px 12px;
        font-size: 12px;
    }
    .attendance-table tr.total-row {
        background: linear-gradient(135deg, #667eea, #764ba2) !important;
        margin: 8px 0;
    }
    .attendance-table tr.total-row td {
        padding: 10px 6px;
        font-size: 12px;
    }
    
    .summary-section {
        margin-top: 15px;
        padding: 12px;
    }
    .summary-section h3 {
        font-size: 16px;
    }
    .summary-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    .summary-card {
        padding: 12px;
    }
    .summary-card strong {
        font-size: 11px;
    }
    .summary-card .value {
        font-size: 22px;
    }
    
    .notes-section {
        padding: 12px;
        margin-top: 12px;
    }
    .notes-section strong {
        font-size: 13px;
    }
    .notes-section ul {
        margin: 10px 0;
        padding-left: 18px;
        line-height: 1.6;
    }
    .notes-section li {
        font-size: 11px;
        margin-bottom: 4px;
    }
    
    .generated-info {
        font-size: 11px;
        padding: 10px 0;
        margin-top: 12px;
    }
}

/* Small Phones (480px to 599px) */
@media (max-width: 599px) and (min-width: 480px) {
    body {
        font-size: 12px;
        background-attachment: fixed;
    }
    
    .navbar {
        padding: 10px 12px;
        flex-direction: column;
        gap: 8px;
    }
    .navbar h1 {
        font-size: 14px;
        text-align: center;
    }
    .user-info {
        width: 100%;
        flex-direction: column;
        gap: 6px;
        align-items: center;
    }
    .user-profile {
        padding: 6px 10px;
        font-size: 11px;
        gap: 6px;
        width: 100%;
        justify-content: center;
    }
    .btn {
        padding: 8px 14px;
        font-size: 11px;
        width: 100%;
        text-align: center;
    }
    
    .report-container {
        margin: 10px;
        padding: 12px;
        border-radius: 12px;
    }
    .report-header {
        margin-bottom: 15px;
        padding-bottom: 12px;
    }
    .report-header h1 {
        font-size: 16px;
        margin-bottom: 5px;
    }
    .report-header h2 {
        font-size: 12px;
    }
    
    .date-selector {
        padding: 10px;
        margin-bottom: 12px;
        border-radius: 10px;
    }
    .date-selector label {
        font-size: 12px;
        display: block;
        margin-bottom: 6px;
        font-weight: bold;
    }
    .date-selector input[type="date"] {
        padding: 7px 10px;
        font-size: 11px;
        margin-bottom: 6px;
        width: 100%;
        border-radius: 6px;
    }
    .date-selector button {
        padding: 7px 12px;
        font-size: 10px;
        margin: 2px;
        width: 48%;
        display: inline-block;
    }
    
    .attendance-table {
        font-size: 10px;
        margin: 10px 0;
        border-radius: 8px;
        display: block;
        overflow-x: auto;
    }
    .attendance-table thead {
        display: none;
    }
    .attendance-table tbody {
        display: block;
    }
    .attendance-table tr {
        display: block;
        margin-bottom: 8px;
        border: 1px solid #e8e8e8;
        border-radius: 8px;
        padding: 8px;
        background: white;
    }
    .attendance-table td {
        display: block;
        padding: 6px 0;
        text-align: left;
        border: none;
        background: none;
    }
    .attendance-table td::before {
        content: attr(data-label);
        font-weight: bold;
        color: #667eea;
        display: inline-block;
        width: 80px;
    }
    .attendance-table tr.year-header {
        background: linear-gradient(135deg, #5a67d8, #6b46c1);
        padding: 8px;
        margin: 6px 0;
    }
    .attendance-table tr.year-header td {
        color: white;
        font-weight: bold;
        padding: 6px 8px;
        text-align: left;
    }
    .attendance-table tr.year-header td::before {
        display: none;
    }
    .attendance-table tr.total-row {
        background: linear-gradient(135deg, #667eea, #764ba2);
        padding: 8px;
        margin: 6px 0;
    }
    .attendance-table tr.total-row td {
        color: white;
        font-weight: bold;
        padding: 6px 8px;
    }
    .attendance-table tr.total-row td::before {
        color: white;
    }
    
    .summary-section {
        margin-top: 12px;
        padding: 10px;
    }
    .summary-section h3 {
        font-size: 14px;
        margin: 0 0 10px 0;
    }
    .summary-grid {
        grid-template-columns: 1fr;
        gap: 8px;
    }
    .summary-card {
        padding: 10px;
    }
    .summary-card strong {
        font-size: 10px;
    }
    .summary-card .value {
        font-size: 20px;
    }
    
    .notes-section {
        padding: 10px;
        margin-top: 10px;
    }
    .notes-section strong {
        font-size: 12px;
    }
    .notes-section ul {
        margin: 8px 0;
        padding-left: 16px;
        line-height: 1.5;
    }
    .notes-section li {
        font-size: 10px;
        margin-bottom: 3px;
    }
    
    .generated-info {
        font-size: 10px;
        padding: 8px 0;
        margin-top: 10px;
    }
    
    .footer-content {
        padding: 20px 12px 12px;
    }
    .footer-card p {
        font-size: 11px;
    }
    .footer-card a {
        font-size: 12px;
        padding: 8px 16px;
    }
}

/* Extra Small Phones (320px to 479px) */
@media (max-width: 479px) {
    * {
        font-size: 11px;
    }
    
    body {
        background-attachment: fixed;
    }
    
    .navbar {
        padding: 8px 10px;
        flex-direction: column;
        gap: 6px;
    }
    .navbar h1 {
        font-size: 13px;
        text-align: center;
    }
    .user-info {
        width: 100%;
        flex-direction: column;
        gap: 5px;
        align-items: stretch;
    }
    .user-profile {
        padding: 5px 8px;
        font-size: 10px;
        gap: 5px;
        justify-content: center;
    }
    .btn {
        padding: 6px 10px;
        font-size: 10px;
        width: 100%;
    }
    
    .report-container {
        margin: 8px;
        padding: 10px;
        border-radius: 10px;
    }
    .report-header {
        margin-bottom: 12px;
        padding-bottom: 10px;
    }
    .report-header h1 {
        font-size: 14px;
        margin-bottom: 3px;
    }
    .report-header h2 {
        font-size: 11px;
    }
    
    .date-selector {
        padding: 8px;
        margin-bottom: 10px;
        border-radius: 8px;
    }
    .date-selector label {
        font-size: 10px;
        display: block;
        margin-bottom: 5px;
    }
    .date-selector input[type="date"] {
        padding: 6px 8px;
        font-size: 10px;
        margin-bottom: 5px;
        width: 100%;
        border-radius: 5px;
    }
    .date-selector button {
        padding: 6px 10px;
        font-size: 9px;
        margin: 1px;
        width: 48%;
        display: inline-block;
    }
    
    .attendance-table {
        font-size: 9px;
        margin: 8px 0;
        border-radius: 6px;
        display: block;
    }
    .attendance-table thead {
        display: none;
    }
    .attendance-table tbody {
        display: block;
    }
    .attendance-table tr {
        display: block;
        margin-bottom: 6px;
        border: 1px solid #e8e8e8;
        border-radius: 6px;
        padding: 6px;
        background: white;
    }
    .attendance-table td {
        display: block;
        padding: 4px 0;
        text-align: left;
        border: none;
        background: none;
    }
    .attendance-table td::before {
        content: attr(data-label);
        font-weight: bold;
        color: #667eea;
        display: inline-block;
        width: 70px;
        font-size: 8px;
    }
    .attendance-table tr.year-header {
        background: linear-gradient(135deg, #5a67d8, #6b46c1);
        padding: 6px;
        margin: 4px 0;
    }
    .attendance-table tr.year-header td {
        color: white;
        font-weight: bold;
        padding: 4px 6px;
    }
    .attendance-table tr.year-header td::before {
        display: none;
    }
    .attendance-table tr.total-row {
        background: linear-gradient(135deg, #667eea, #764ba2);
        padding: 6px;
        margin: 4px 0;
    }
    .attendance-table tr.total-row td {
        color: white;
        font-weight: bold;
        padding: 4px 6px;
        font-size: 10px;
    }
    
    .summary-section {
        margin-top: 10px;
        padding: 8px;
    }
    .summary-section h3 {
        font-size: 12px;
        margin: 0 0 8px 0;
    }
    .summary-grid {
        grid-template-columns: 1fr;
        gap: 6px;
    }
    .summary-card {
        padding: 8px;
    }
    .summary-card strong {
        font-size: 9px;
    }
    .summary-card .value {
        font-size: 18px;
    }
    
    .notes-section {
        padding: 8px;
        margin-top: 8px;
    }
    .notes-section strong {
        font-size: 11px;
    }
    .notes-section ul {
        margin: 6px 0;
        padding-left: 14px;
        line-height: 1.4;
    }
    .notes-section li {
        font-size: 9px;
        margin-bottom: 2px;
    }
    
    .generated-info {
        font-size: 9px;
        padding: 6px 0;
        margin-top: 8px;
    }
    
    .footer-glow {
        height: 2px;
    }
    .footer-content {
        padding: 15px 10px 10px;
    }
    .footer-card {
        padding: 15px 10px;
    }
    .footer-card p {
        font-size: 10px;
    }
    .footer-card a {
        font-size: 11px;
        padding: 6px 12px;
    }
}

/* Ultra Small Screens (Below 320px) */
@media (max-width: 319px) {
    .navbar h1 {
        font-size: 12px;
    }
    .report-header h1 {
        font-size: 13px;
    }
    .attendance-table {
        display: block;
        overflow: hidden;
    }
    .attendance-table tr {
        margin-bottom: 4px;
        padding: 4px;
    }
    .summary-card .value {
        font-size: 16px;
    }
}
  </style>
</head>
<body>
    <!-- Animated Particles Background -->
    <div class="particles no-print">
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const particles = document.querySelector('.particles');
                for (let i = 0; i < 30; i++) {
                    const particle = document.createElement('div');
                    particle.className = 'particle';
                    particle.style.left = Math.random() * 100 + '%';
                    particle.style.width = particle.style.height = (Math.random() * 20 + 5) + 'px';
                    particle.style.animationDelay = Math.random() * 15 + 's';
                    particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
                    particles.appendChild(particle);
                }
            });
        </script>
    </div>

    <nav class="navbar no-print">
        <div>
            <h1>🎓 NIT AMMS - Daily Attendance Report</h1>
        </div>
        <div class="user-info">
            <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
            <span class="user-profile">👨‍💼 <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="report-container">
        <div class="report-header">
            <h1>Nagpur Institute of Technology, Nagpur</h1>
            <h2>📊 Daily Attendance Report</h2>
        </div>

        <div class="date-selector no-print">
            <form method="GET" style="display: flex; justify-content: center; align-items: center; gap: 15px; flex-wrap: wrap;">
                <label>📅 Select Date:</label>
                <input type="date" name="date" value="<?php echo $selected_date; ?>" max="<?php echo date('Y-m-d'); ?>">
                <button type="submit">🔍 View Report</button>
                <button type="button" onclick="window.print()">🖨️ Print Report</button>
            </form>
        </div>

        <table class="attendance-table">
            <thead>
                <tr>
                    <th rowspan="2">Date</th>
                    <th rowspan="2">Year / Section</th>
                    <th rowspan="2">Total Students<br>on Roll</th>
                    <th colspan="2">Present</th>
                    <th colspan="2">Present</th>
                </tr>
                <tr>
                    <th>1st<br>Lecture</th>
                    <th>%</th>
                    <th>5th<br>Lecture</th>
                    <th>%</th>
                </tr>
            </thead>
            <tbody>
                <tr class="year-header">
                    <td rowspan="<?php echo count($sections) + 2; ?>"><?php echo date('d-M-y', strtotime($selected_date)); ?></td>
                    <td colspan="6">📚 1st Year</td>
                </tr>
                
                <?php foreach ($sections as $section): ?>
                    <?php 
                    $data = $attendance_data[$section];
                    $first_class = $data['first_percentage'] >= 85 ? 'percentage-high' : 
                                   ($data['first_percentage'] >= 70 ? 'percentage-medium' : 'percentage-low');
                    $fifth_class = $data['fifth_percentage'] >= 85 ? 'percentage-high' : 
                                  ($data['fifth_percentage'] >= 70 ? 'percentage-medium' : 'percentage-low');
                    
                    $section_names = [
                        'ME' => 'ME', 'Mechanical' => 'ME',
                        'CE' => 'CE', 'Civil' => 'CE',
                        'EE' => 'EE', 'Electrical' => 'EE',
                        'CSE' => 'CSE', 'CSE-A' => 'CSE', 'CSE-B' => 'CSE',
                        'IT' => 'IT'
                    ];
                    
                    $display_section = isset($section_names[$section]) ? $section_names[$section] : $section;
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($display_section); ?></strong></td>
                        <td><strong><?php echo $data['total_students']; ?></strong></td>
                        <td><strong><?php echo $data['first_lecture']; ?></strong></td>
                        <td class="<?php echo $first_class; ?>"><strong><?php echo $data['first_percentage']; ?>%</strong></td>
                        <td><strong><?php echo $data['fifth_lecture']; ?></strong></td>
                        <td class="<?php echo $fifth_class; ?>"><strong><?php echo $data['fifth_percentage']; ?>%</strong></td>
                    </tr>
                <?php endforeach; ?>
                
                <tr class="total-row">
                    <td><strong>📈 Total</strong></td>
                    <td><strong><?php echo $grand_total_students; ?></strong></td>
                    <td><strong><?php echo $grand_total_present_first; ?></strong></td>
                    <td><strong><?php echo $grand_percentage_first; ?>%</strong></td>
                    <td><strong><?php echo $grand_total_present_fifth; ?></strong></td>
                    <td><strong><?php echo $grand_percentage_fifth; ?>%</strong></td>
                </tr>
            </tbody>
        </table>

        <div class="summary-section">
            <h3 style="margin-top: 0; color: #4c1d95; font-size: 20px;">📊 Summary Statistics</h3>
            <div class="summary-grid">
                <div class="summary-card" style="border-left-color: #667eea;">
                    <strong>📚 Total Students</strong>
                    <div class="value"><?php echo $grand_total_students; ?></div>
                </div>
                <div class="summary-card" style="border-left-color: #10b981;">
                    <strong>✅ Present (1st Lecture)</strong>
                    <div class="value" style="-webkit-text-fill-color: #10b981;"><?php echo $grand_total_present_first; ?></div>
                </div>
                <div class="summary-card" style="border-left-color: #8b5cf6;">
                    <strong>📈 Attendance % (1st)</strong>
                    <div class="value" style="-webkit-text-fill-color: <?php echo $grand_percentage_first >= 85 ? '#10b981' : ($grand_percentage_first >= 70 ? '#f59e0b' : '#ef4444'); ?>;">
                        <?php echo $grand_percentage_first; ?>%
                    </div>
                </div>
                <div class="summary-card" style="border-left-color: #f59e0b;">
                    <strong>✅ Present (5th Lecture)</strong>
                    <div class="value" style="-webkit-text-fill-color: #f59e0b;"><?php echo $grand_total_present_fifth; ?></div>
                </div>
            </div>
        </div>

        <div class="notes-section">
            <strong>📝 Important Notes:</strong>
            <ul>
                <li><strong>1st Lecture:</strong> Attendance marked before 12:00 PM (Morning Session)</li>
                <li><strong>5th Lecture:</strong> Attendance marked after 12:00 PM (Afternoon Session)</li>
                <li><span style="color: #10b981; font-size: 20px;">●</span> <strong>Green:</strong> Above 85% attendance (Excellent)</li>
                <li><span style="color: #f59e0b; font-size: 20px;">●</span> <strong>Orange:</strong> 70-85% attendance (Good)</li>
                <li><span style="color: #ef4444; font-size: 20px;">●</span> <strong>Red:</strong> Below 70% attendance (Needs Attention)</li>
            </ul>
        </div>

        <div class="generated-info">
            <p><strong>Generated on:</strong> <?php echo date('d F Y, h:i A'); ?></p>
            <p><strong>Generated by:</strong> <?php echo htmlspecialchars($user['full_name']); ?></p>
        </div>
    </div>

    <!-- Modern Footer -->
    <div class="modern-footer no-print">
        <div class="footer-glow"></div>
        <div class="footer-content">
            <div class="footer-card">
                <p>✨ Designed & Developed by</p>
                <a href="https://himanshufullstackdeveloper.github.io/techyugsoftware/">
                    🚀 Techyug Software Pvt. Ltd.
                </a>
            </div>
            <div class="footer-bottom">
                <p>© 2025 NIT AMMS. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>