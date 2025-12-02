<?php
require_once '../db.php';
checkRole(['teacher']);

$user = getCurrentUser();

// Get filter parameters
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
$section = isset($_GET['section']) ? $_GET['section'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
$format = isset($_GET['format']) ? $_GET['format'] : 'excel';

// Verify teacher has access to this class
$verify_query = "SELECT c.*, d.dept_name FROM classes c 
                 JOIN departments d ON c.department_id = d.id
                 WHERE c.id = ? AND c.teacher_id = ?";
$stmt = $conn->prepare($verify_query);
$stmt->bind_param("ii", $class_id, $user['id']);
$stmt->execute();
$class = $stmt->get_result()->fetch_assoc();

if (!$class) {
    die("Access denied or class not found!");
}

// Get section from class if not provided
if (empty($section)) {
    $section = $class['section'];
}

// FIXED: Get attendance records for ALL students in the SECTION
$attendance_query = "SELECT sa.*, s.roll_number, s.full_name as student_name
                     FROM student_attendance sa
                     JOIN students s ON sa.student_id = s.id
                     JOIN classes c ON s.class_id = c.id
                     WHERE c.section = ? 
                     AND sa.attendance_date BETWEEN ? AND ?
                     AND sa.marked_by = ?
                     ORDER BY s.roll_number ASC, sa.attendance_date ASC";

$stmt = $conn->prepare($attendance_query);
$stmt->bind_param("sssi", $section, $date_from, $date_to, $user['id']);
$stmt->execute();
$attendance_records = $stmt->get_result();

// Process data for student summary
$students_data = [];
$total_days = 0;
$dates_set = [];

while ($record = $attendance_records->fetch_assoc()) {
    $student_id = $record['student_id'];
    $dates_set[$record['attendance_date']] = true;
    
    if (!isset($students_data[$student_id])) {
        $students_data[$student_id] = [
            'roll_number' => $record['roll_number'],
            'student_name' => $record['student_name'],
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'total' => 0
        ];
    }
    
    $students_data[$student_id]['total']++;
    $students_data[$student_id][strtolower($record['status'])]++;
}

$total_days = count($dates_set);

// Export based on format
if ($format === 'excel') {
    // Excel CSV Export
    $section_clean = preg_replace('/[^a-zA-Z0-9]/', '_', $section);
    $filename = "Attendance_Summary_" . $section_clean . "_" . date('Ymd') . ".csv";
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for Excel UTF-8 support
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Header information
    fputcsv($output, ['ATTENDANCE SUMMARY REPORT']);
    fputcsv($output, ['Section: ' . $section]);
    fputcsv($output, ['Department: ' . $class['dept_name']]);
    fputcsv($output, ['Class: ' . $class['class_name']]);
    fputcsv($output, ['Year: ' . $class['year'] . ' | Semester: ' . $class['semester']]);
    fputcsv($output, ['Teacher: ' . $user['full_name']]);
    fputcsv($output, ['Period: ' . date('d M Y', strtotime($date_from)) . ' to ' . date('d M Y', strtotime($date_to))]);
    fputcsv($output, ['Total Days: ' . $total_days]);
    fputcsv($output, ['Generated: ' . date('d M Y H:i:s')]);
    fputcsv($output, []); // Empty row
    
    // Column headers
    fputcsv($output, ['Roll Number', 'Student Name', 'Total Days', 'Present', 'Absent', 'Late', 'Attendance %']);
    
    // Data rows
    if (!empty($students_data)) {
        // Sort by roll number
        usort($students_data, function($a, $b) {
            return strcmp($a['roll_number'], $b['roll_number']);
        });
        
        foreach ($students_data as $student) {
            $attendance_pct = $student['total'] > 0 ? round(($student['present'] / $student['total']) * 100, 2) : 0;
            
            $row = [
                $student['roll_number'],
                $student['student_name'],
                $student['total'],
                $student['present'],
                $student['absent'],
                $student['late'],
                $attendance_pct . '%'
            ];
            fputcsv($output, $row);
        }
    } else {
        fputcsv($output, ['No records found']);
    }
    
    fclose($output);
    exit;
    
} elseif ($format === 'pdf') {
    // HTML format with print
    $section_clean = preg_replace('/[^a-zA-Z0-9]/', '_', $section);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
          <link rel="icon" href="../Nit_logo.png" type="image/svg+xml" />
        <meta charset="UTF-8">
        <title>Attendance Summary - <?php echo htmlspecialchars($section); ?></title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            
            @media print {
                body { margin: 0; }
                @page { margin: 1cm; }
                .no-print { display: none !important; }
            }
            
            body {
                font-family: 'Segoe UI', Arial, sans-serif;
                padding: 20px;
                font-size: 13px;
                line-height: 1.6;
                background: #f5f5f5;
            }
            
            .container {
                max-width: 1200px;
                margin: 0 auto;
                background: white;
                padding: 30px;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }
            
            .header {
                text-align: center;
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 3px solid #333;
            }
            
            .header h1 {
                color: #333;
                font-size: 28px;
                margin-bottom: 15px;
            }
            
            .info-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
                margin: 15px 0;
            }
            
            .info-item {
                padding: 8px;
                background: #f8f9fa;
                border-left: 3px solid #667eea;
            }
            
            .info-item strong {
                color: #333;
                font-weight: 600;
            }
            
            .total-days-highlight {
                background: #d4edda;
                border-left: 3px solid #28a745;
                font-size: 16px;
                padding: 12px;
                text-align: center;
                margin: 15px 0;
                border-radius: 5px;
            }
            
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }
            
            th {
                background: #667eea;
                color: white;
                padding: 12px 8px;
                text-align: left;
                font-weight: 600;
                border: 1px solid #5568d3;
            }
            
            td {
                padding: 10px 8px;
                border: 1px solid #ddd;
            }
            
            tr:nth-child(even) {
                background: #f8f9fa;
            }
            
            .stat-cell {
                text-align: center;
                font-weight: 600;
            }
            
            .present-count { color: #28a745; }
            .absent-count { color: #dc3545; }
            .late-count { color: #ffc107; }
            .percentage { 
                background: #e7f3ff;
                padding: 4px 8px;
                border-radius: 8px;
                font-weight: 700;
                color: #0066cc;
            }
            
            .footer {
                margin-top: 40px;
                padding-top: 20px;
                border-top: 2px solid #ddd;
                text-align: center;
                color: #666;
                font-size: 11px;
            }
            
            .print-buttons {
                margin: 20px 0;
                text-align: center;
                padding: 20px;
                background: #667eea;
                border-radius: 10px;
            }
            
            .btn {
                background: white;
                color: #667eea;
                border: none;
                padding: 12px 30px;
                font-size: 16px;
                border-radius: 8px;
                cursor: pointer;
                margin: 0 10px;
                font-weight: 600;
            }
            
            .btn:hover {
                background: #f0f0f0;
            }
            
            .btn-secondary {
                background: #6c757d;
                color: white;
            }
            
            .empty-state {
                text-align: center;
                padding: 60px 20px;
                background: #fff3cd;
                border: 2px solid #ffc107;
                border-radius: 10px;
                margin: 20px 0;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="print-buttons no-print">
                <button onclick="window.print()" class="btn">🖨️ Print / Save as PDF</button>
                <button onclick="window.close()" class="btn btn-secondary">✕ Close</button>
            </div>

            <div class="header">
                <h1>📊 ATTENDANCE SUMMARY REPORT</h1>
                <div class="info-grid">
                    <div class="info-item">
                        <strong>Section:</strong> <?php echo htmlspecialchars($section); ?>
                    </div>
                    <div class="info-item">
                        <strong>Department:</strong> <?php echo htmlspecialchars($class['dept_name']); ?>
                    </div>
                    <div class="info-item">
                        <strong>Class:</strong> <?php echo htmlspecialchars($class['class_name']); ?>
                    </div>
                    <div class="info-item">
                        <strong>Year:</strong> <?php echo $class['year']; ?> | <strong>Semester:</strong> <?php echo $class['semester']; ?>
                    </div>
                    <div class="info-item">
                        <strong>Teacher:</strong> <?php echo htmlspecialchars($user['full_name']); ?>
                    </div>
                    <div class="info-item">
                        <strong>Date:</strong> <?php echo date('d M Y', strtotime($date_from)) . ' to ' . date('d M Y', strtotime($date_to)); ?>
                    </div>
                </div>
                <div class="total-days-highlight">
                    <strong>📅 Total Days in Period: <?php echo $total_days; ?></strong>
                </div>
                <div style="margin-top: 15px; color: #666;">
                    <strong>Generated:</strong> <?php echo date('d M Y H:i:s'); ?>
                </div>
            </div>
            
            <?php if (!empty($students_data)): ?>
            <?php
            // Sort by roll number
            usort($students_data, function($a, $b) {
                return strcmp($a['roll_number'], $b['roll_number']);
            });
            ?>
            <table>
                <thead>
                    <tr>
                        <th>Roll Number</th>
                        <th>Student Name</th>
                        <th style="text-align: center;">Total Days</th>
                        <th style="text-align: center;">Present</th>
                        <th style="text-align: center;">Absent</th>
                        <th style="text-align: center;">Late</th>
                        <th style="text-align: center;">Attendance %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students_data as $student): 
                        $attendance_pct = $student['total'] > 0 ? round(($student['present'] / $student['total']) * 100, 2) : 0;
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($student['roll_number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                        <td class="stat-cell"><?php echo $student['total']; ?></td>
                        <td class="stat-cell present-count"><?php echo $student['present']; ?></td>
                        <td class="stat-cell absent-count"><?php echo $student['absent']; ?></td>
                        <td class="stat-cell late-count"><?php echo $student['late']; ?></td>
                        <td class="stat-cell">
                            <span class="percentage"><?php echo $attendance_pct; ?>%</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <h3>⚠️ No Records Found</h3>
                <p>No attendance records found for the selected date range.</p>
            </div>
            <?php endif; ?>
            
            <div class="footer">
                <p><strong>NIT  AMMS</strong></p>
                <p>This is a computer-generated document. No signature required.</p>
                <p>© <?php echo date('Y'); ?> NIT AMMS. All rights reserved.</p>
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
    <?php
    exit;
}
?>