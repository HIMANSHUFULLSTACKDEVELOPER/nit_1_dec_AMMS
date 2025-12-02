<?php
require_once '../db.php';
checkRole(['admin']);

$user = getCurrentUser();

// Get filter parameters
$selected_year = isset($_GET['year']) ? sanitize($_GET['year']) : date('Y');
$view_type = isset($_GET['view']) ? sanitize($_GET['view']) : 'students';
$filter_department = isset($_GET['department']) ? intval($_GET['department']) : '';
$filter_class = isset($_GET['class']) ? intval($_GET['class']) : '';

// Get available years (from admission_year and academic_year)
$years_query = "SELECT DISTINCT admission_year FROM students WHERE admission_year IS NOT NULL
                UNION
                SELECT DISTINCT SUBSTRING(academic_year, 1, 4) FROM classes WHERE academic_year IS NOT NULL
                ORDER BY admission_year DESC";
$years_result = $conn->query($years_query);
$available_years = [];
while ($row = $years_result->fetch_assoc()) {
    $available_years[] = $row['admission_year'];
}

// Build WHERE clause for students
$where_clauses = ["s.admission_year = '$selected_year'"];
if ($filter_department) $where_clauses[] = "s.department_id = $filter_department";
if ($filter_class) $where_clauses[] = "s.class_id = $filter_class";
$where_sql = implode(' AND ', $where_clauses);

// Get students data
$students_query = "SELECT s.*, 
                   d.dept_name,
                   c.class_name, c.section,
                   (SELECT COUNT(*) FROM parents WHERE student_id = s.id) as parent_count
                   FROM students s
                   LEFT JOIN departments d ON s.department_id = d.id
                   LEFT JOIN classes c ON s.class_id = c.id
                   WHERE $where_sql
                   ORDER BY s.roll_number";
$students = $conn->query($students_query);

// Get parents data for selected year
$parents_query = "SELECT p.*, 
                  s.roll_number, s.full_name as student_name, s.admission_year,
                  d.dept_name, c.class_name
                  FROM parents p
                  JOIN students s ON p.student_id = s.id
                  LEFT JOIN departments d ON s.department_id = d.id
                  LEFT JOIN classes c ON s.class_id = c.id
                  WHERE s.admission_year = '$selected_year'
                  ORDER BY s.roll_number, p.relationship";
$parents = $conn->query($parents_query);

// Get statistics
$stats_query = "SELECT 
                COUNT(DISTINCT s.id) as total_students,
                COUNT(DISTINCT CASE WHEN s.is_active = 1 THEN s.id END) as active_students,
                COUNT(DISTINCT p.id) as total_parents,
                COUNT(DISTINCT s.department_id) as departments_count
                FROM students s
                LEFT JOIN parents p ON s.student_id = p.student_id
                WHERE s.admission_year = '$selected_year'";
$stats = $conn->query($stats_query)->fetch_assoc();

// Get departments
$departments = $conn->query("SELECT * FROM departments ORDER BY dept_name");

// Get classes
$classes = $conn->query("SELECT c.*, d.dept_name FROM classes c LEFT JOIN departments d ON c.department_id = d.id ORDER BY c.section, c.year");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Previous Year Data - Admin</title>
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

        .main-content {
            padding: 40px;
            max-width: 1600px;
            margin: 0 auto;
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

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            border-left: 5px solid #667eea;
        }

        .stat-card h4 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .stat-card .value {
            font-size: 36px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Filter Container */
        .filter-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
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

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            background: white;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        /* View Toggle */
        .view-toggle {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .view-toggle-btn {
            padding: 12px 24px;
            background: rgba(255, 255, 255, 0.95);
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            color: #333;
        }

        .view-toggle-btn.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-color: transparent;
        }

        /* Table Container */
        .table-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            overflow-x: auto;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .table-header h3 {
            color: #333;
            font-size: 20px;
        }

        #searchInput {
            width: 400px;
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            font-size: 14px;
            transition: all 0.3s;
        }

        #searchInput:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }

        th {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            font-weight: 600;
        }

        tbody tr:hover {
            background: rgba(102, 126, 234, 0.05);
        }

        .badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        .profile-photo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ddd;
        }

        .no-data {
            text-align: center;
            padding: 60px;
            color: #999;
        }

        .export-btn {
            padding: 10px 20px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .filter-form {
                grid-template-columns: 1fr;
            }

            .table-header {
                flex-direction: column;
                gap: 15px;
            }

            #searchInput {
                width: 100%;
            }

            .view-toggle {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div>
            <h1>📊 Previous Year Data Viewer</h1>
        </div>
        <div class="user-info">
            <a href="index.php" class="btn btn-secondary">← Back</a>
            <span>👨‍💼 <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <h4>📅 Academic Year</h4>
                <div class="value"><?php echo $selected_year; ?>-<?php echo $selected_year + 1; ?></div>
            </div>
            <div class="stat-card">
                <h4>👨‍🎓 Total Students</h4>
                <div class="value"><?php echo $stats['total_students']; ?></div>
            </div>
            <div class="stat-card">
                <h4>✅ Active Students</h4>
                <div class="value"><?php echo $stats['active_students']; ?></div>
            </div>
            <div class="stat-card">
                <h4>👨‍👩‍👦 Total Parents</h4>
                <div class="value"><?php echo $stats['total_parents']; ?></div>
            </div>
        </div>

        <!-- Filter Container -->
        <div class="filter-container">
            <h3>🔍 Filter Data</h3>
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label>📅 Academic Year</label>
                    <select name="year" required>
                        <option value="">Select Year</option>
                        <?php foreach ($available_years as $year): ?>
                            <option value="<?php echo $year; ?>" <?php echo $selected_year == $year ? 'selected' : ''; ?>>
                                <?php echo $year; ?>-<?php echo $year + 1; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>👁️ View Type</label>
                    <select name="view">
                        <option value="students" <?php echo $view_type == 'students' ? 'selected' : ''; ?>>Students</option>
                        <option value="parents" <?php echo $view_type == 'parents' ? 'selected' : ''; ?>>Parents</option>
                        <option value="both" <?php echo $view_type == 'both' ? 'selected' : ''; ?>>Both</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>🏢 Department</label>
                    <select name="department">
                        <option value="">All Departments</option>
                        <?php 
                        $departments->data_seek(0);
                        while ($dept = $departments->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $dept['id']; ?>" <?php echo $filter_department == $dept['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['dept_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>📚 Class</label>
                    <select name="class">
                        <option value="">All Classes</option>
                        <?php 
                        $classes->data_seek(0);
                        while ($class = $classes->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $class['id']; ?>" <?php echo $filter_class == $class['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['class_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">🔎 Apply Filter</button>
                </div>
            </form>
        </div>

        <!-- Students Data -->
        <?php if ($view_type == 'students' || $view_type == 'both'): ?>
        <div class="table-container">
            <div class="table-header">
                <h3>👨‍🎓 Students Data (<?php echo $selected_year; ?>-<?php echo $selected_year + 1; ?>)</h3>
                <input type="text" id="searchInput" onkeyup="searchTable('studentsTable')" 
                       placeholder="🔍 Search students...">
            </div>

            <?php if ($students->num_rows > 0): ?>
            <table id="studentsTable">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Roll No</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Department</th>
                        <th>Class/Section</th>
                        <th>Year</th>
                        <th>Semester</th>
                        <th>Admission Year</th>
                        <th>Parents</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $students->data_seek(0);
                    while ($student = $students->fetch_assoc()): 
                    ?>
                    <tr>
                        <td>
                            <?php if (!empty($student['photo']) && file_exists("../uploads/students/" . $student['photo'])): ?>
                                <img src="../uploads/students/<?php echo htmlspecialchars($student['photo']); ?>" 
                                     alt="Photo" class="profile-photo">
                            <?php else: ?>
                                <span style="font-size: 35px;">👤</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo htmlspecialchars($student['roll_number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                        <td><?php echo htmlspecialchars($student['phone']); ?></td>
                        <td><?php echo htmlspecialchars($student['dept_name']); ?></td>
                        <td>
                            <span class="badge badge-info">
                                <?php echo htmlspecialchars($student['section'] ?? $student['class_name']); ?>
                            </span>
                        </td>
                        <td><?php echo $student['year']; ?></td>
                        <td><?php echo $student['semester']; ?></td>
                        <td><strong><?php echo $student['admission_year']; ?></strong></td>
                        <td>
                            <span class="badge badge-success">
                                <?php echo $student['parent_count']; ?> Parent(s)
                            </span>
                        </td>
                        <td>
                            <?php if ($student['is_active']): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="no-data">
                <h3>📭 No Students Found</h3>
                <p>No student records found for academic year <?php echo $selected_year; ?>-<?php echo $selected_year + 1; ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Parents Data -->
        <?php if ($view_type == 'parents' || $view_type == 'both'): ?>
        <div class="table-container" style="margin-top: 30px;">
            <div class="table-header">
                <h3>👨‍👩‍👦 Parents Data (<?php echo $selected_year; ?>-<?php echo $selected_year + 1; ?>)</h3>
                <input type="text" id="searchInputParents" onkeyup="searchTable('parentsTable')" 
                       placeholder="🔍 Search parents...">
            </div>

            <?php if ($parents->num_rows > 0): ?>
            <table id="parentsTable">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Parent Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Relationship</th>
                        <th>Student Name</th>
                        <th>Roll Number</th>
                        <th>Department</th>
                        <th>Class</th>
                        <th>Admission Year</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $parents->data_seek(0);
                    while ($parent = $parents->fetch_assoc()): 
                    ?>
                    <tr>
                        <td>
                            <?php if (!empty($parent['photo']) && file_exists("../uploads/parents/" . $parent['photo'])): ?>
                                <img src="../uploads/parents/<?php echo htmlspecialchars($parent['photo']); ?>" 
                                     alt="Photo" class="profile-photo">
                            <?php else: ?>
                                <span style="font-size: 35px;">👤</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo htmlspecialchars($parent['parent_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($parent['email']); ?></td>
                        <td><?php echo htmlspecialchars($parent['phone']); ?></td>
                        <td>
                            <span class="badge badge-info">
                                <?php echo ucfirst($parent['relationship']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($parent['student_name']); ?></td>
                        <td><strong><?php echo htmlspecialchars($parent['roll_number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($parent['dept_name']); ?></td>
                        <td><?php echo htmlspecialchars($parent['class_name']); ?></td>
                        <td><strong><?php echo $parent['admission_year']; ?></strong></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="no-data">
                <h3>📭 No Parents Found</h3>
                <p>No parent records found for academic year <?php echo $selected_year; ?>-<?php echo $selected_year + 1; ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <div style="background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 50%, #2a3254 100%); position: relative; overflow: hidden; margin-top: 50px;">
        <div style="height: 2px; background: linear-gradient(90deg, #4a9eff, #00d4ff, #4a9eff, #00d4ff); background-size: 200% 100%;"></div>
        <div style="max-width: 1000px; margin: 0 auto; padding: 30px 20px 20px;">
            <div style="background: rgba(255, 255, 255, 0.03); padding: 20px; border-radius: 15px; border: 1px solid rgba(74, 158, 255, 0.15); text-align: center;">
                <p style="color: #ffffff; font-size: 14px; margin: 0 0 12px;">✨ Designed & Developed by</p>
                <a href="https://himanshufullstackdeveloper.github.io/techyugsoftware/" style="display: inline-block; color: #ffffff; font-size: 16px; font-weight: 700; text-decoration: none; padding: 8px 24px; border: 2px solid #4a9eff; border-radius: 30px; background: linear-gradient(135deg, rgba(74, 158, 255, 0.2), rgba(0, 212, 255, 0.2)); box-shadow: 0 3px 12px rgba(74, 158, 255, 0.3);">
                    🚀 Techyug Software Pvt. Ltd.
                </a>
            </div>
            <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.1); text-align: center;">
                <p style="color: #888; font-size: 12px;">© 2025 NIT AMMS. All rights reserved.</p>
            </div>
        </div>
    </div>

    <script>
        function searchTable(tableId) {
            const input = tableId === 'studentsTable' ? 
                document.getElementById('searchInput') : 
                document.getElementById('searchInputParents');
            const filter = input.value.toUpperCase();
            const table = document.getElementById(tableId);
            const tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                let txtValue = '';
                const td = tr[i].getElementsByTagName('td');
                
                for (let j = 0; j < td.length; j++) {
                    if (td[j]) {
                        txtValue += td[j].textContent || td[j].innerText;
                        txtValue += ' ';
                    }
                }
                
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = '';
                } else {
                    tr[i].style.display = 'none';
                }
            }
        }
    </script>
</body>
</html>