<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../db.php';
checkRole(['admin']);

$user = getCurrentUser();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_student'])) {
        try {
            $roll_number = sanitize($_POST['roll_number']);
            $admission_number = sanitize($_POST['admission_number']);
            $full_name = sanitize($_POST['full_name']);
            $email = sanitize($_POST['email']);
            $phone = sanitize($_POST['phone']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $department_id = intval($_POST['department_id']);
            $class_id = intval($_POST['class_id']);
            $year = intval($_POST['year']);
            $semester = intval($_POST['semester']);
            $admission_year = sanitize($_POST['admission_year']);
            $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : null;
        
        // Handle photo upload
        $photo_path = NULL;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/students/';
            
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_ext, $allowed) && $_FILES['photo']['size'] <= 5242880) {
                $new_filename = 'student_' . time() . '_' . uniqid() . '.' . $file_ext;
                $photo_path = $new_filename;
                move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $new_filename);
            }
        }
        
        if ($student_id) {
            // Update existing student
            if ($photo_path) {
                $stmt = $conn->prepare("UPDATE students SET roll_number=?, admission_number=?, full_name=?, email=?, phone=?, department_id=?, class_id=?, year=?, semester=?, admission_year=?, photo=? WHERE id=?");
                $stmt->bind_param("sssssiiiiissi", $roll_number, $admission_number, $full_name, $email, $phone, $department_id, $class_id, $year, $semester, $admission_year, $photo_path, $student_id);
            } else {
                $stmt = $conn->prepare("UPDATE students SET roll_number=?, admission_number=?, full_name=?, email=?, phone=?, department_id=?, class_id=?, year=?, semester=?, admission_year=? WHERE id=?");
                $stmt->bind_param("sssssiiiiisi", $roll_number, $admission_number, $full_name, $email, $phone, $department_id, $class_id, $year, $semester, $admission_year, $student_id);
            }
            
            if ($stmt->execute()) {
                $success = "Student updated successfully!";
            } else {
                $error = "Error updating student: " . $stmt->error;
            }
            $stmt->close();
        } else {
            // Add new student
            $stmt = $conn->prepare("INSERT INTO students (roll_number, admission_number, full_name, email, phone, password, department_id, class_id, year, semester, admission_year, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssiiiisss", $roll_number, $admission_number, $full_name, $email, $phone, $password, $department_id, $class_id, $year, $semester, $admission_year, $photo_path);
            
            if ($stmt->execute()) {
                $success = "Student added successfully!";
            } else {
                $error = "Error adding student: " . $stmt->error;
            }
            $stmt->close();
        }
        
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['toggle_status'])) {
        try {
            $student_id = intval($_POST['student_id']);
            $new_status = intval($_POST['new_status']);
            
            $stmt = $conn->prepare("UPDATE students SET is_active = ? WHERE id = ?");
            $stmt->bind_param("ii", $new_status, $student_id);
            $stmt->execute();
            $stmt->close();
            
            $success = "Student status updated!";
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['delete_student'])) {
        try {
            $student_id = intval($_POST['student_id']);
            
            // Get photo path before deleting
            $photo_stmt = $conn->prepare("SELECT photo FROM students WHERE id = ?");
            $photo_stmt->bind_param("i", $student_id);
            $photo_stmt->execute();
            $photo_result = $photo_stmt->get_result();
            
            if ($photo_row = $photo_result->fetch_assoc()) {
                if (!empty($photo_row['photo'])) {
                    $photo_file = '../uploads/students/' . $photo_row['photo'];
                    if (file_exists($photo_file)) {
                        unlink($photo_file);
                    }
                }
            }
            $photo_stmt->close();
            
            $delete_stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
            $delete_stmt->bind_param("i", $student_id);
            $delete_stmt->execute();
            $delete_stmt->close();
            
            $success = "Student deleted successfully!";
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Get selected year filter (default to current academic year)
$current_year = date('Y');
$selected_year = isset($_GET['year']) ? sanitize($_GET['year']) : $current_year . '-' . ($current_year + 1);

// Get all unique admission years from database
$years_query = "SELECT DISTINCT admission_year FROM students ORDER BY admission_year DESC";
$years_result = $conn->query($years_query);
$available_years = [];
while ($year_row = $years_result->fetch_assoc()) {
    $available_years[] = $year_row['admission_year'];
}

// If no years in database, add current and previous years
if (empty($available_years)) {
    $available_years = [
        $current_year . '-' . ($current_year + 1),
        ($current_year - 1) . '-' . $current_year,
        ($current_year - 2) . '-' . ($current_year - 1)
    ];
}

// Get students filtered by selected year
$students_query = "SELECT s.*, d.dept_name, c.class_name, c.section
                   FROM students s
                   LEFT JOIN departments d ON s.department_id = d.id
                   LEFT JOIN classes c ON s.class_id = c.id
                   WHERE s.admission_year = ?
                   ORDER BY s.roll_number";
$stmt = $conn->prepare($students_query);
$stmt->bind_param("s", $selected_year);
$stmt->execute();
$students = $stmt->get_result();

// Get total count for selected year
$count_query = "SELECT COUNT(*) as total FROM students WHERE admission_year = ?";
$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param("s", $selected_year);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_students = $count_result->fetch_assoc()['total'];

// Get departments
$departments = $conn->query("SELECT * FROM departments ORDER BY dept_name");

// Get all classes
$classes = $conn->query("SELECT * FROM classes ORDER BY section, year, semester");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - Admin</title>

    <link rel="icon" href="../Nit_logo.png" type="image/svg+xml" />
    <link rel="stylesheet" href="styles.css">


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

.page-header {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    padding: 30px 40px;
    border-radius: 20px;
    margin-bottom: 30px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.page-header h2 {
    font-size: 32px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.profile-photo {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #ddd;
}

/* Year Filter Styles */
.year-filter-container {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    padding: 25px 30px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    margin-bottom: 30px;
}

.year-filter-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.year-filter-header h3 {
    font-size: 22px;
    color: #333;
    display: flex;
    align-items: center;
    gap: 10px;
}

.year-buttons {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.year-btn {
    padding: 12px 28px;
    border-radius: 12px;
    border: 2px solid #e0e0e0;
    background: white;
    color: #333;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-block;
}

.year-btn:hover {
    border-color: #667eea;
    background: rgba(102, 126, 234, 0.1);
    transform: translateY(-2px);
}

.year-btn.active {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-color: transparent;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.stats-card {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 15px 25px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    gap: 12px;
    font-weight: 600;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.stats-card span {
    font-size: 24px;
}

.table-container {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    overflow-x: auto;
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
    background: #6c757d;
    color: white;
}

.btn-danger {
    background: linear-gradient(135deg, #ff6b6b, #ee5a5a);
    color: white;
}

.btn-warning {
    background: #ffc107;
    color: #000;
}

.btn-sm {
    padding: 8px 16px;
    font-size: 13px;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 2000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(5px);
    animation: fadeIn 0.3s;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-content {
    background: white;
    margin: 3% auto;
    padding: 0;
    border-radius: 25px;
    width: 90%;
    max-width: 1000px;
    max-height: 85vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: slideDown 0.4s;
}

@keyframes slideDown {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.modal-header {
    background: linear-gradient(135deg, #667eea, #764ba2);
    padding: 25px 30px;
    border-radius: 25px 25px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    color: white;
    font-size: 24px;
    font-weight: 700;
}

.close-btn {
    color: white;
    font-size: 32px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
}

.close-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
}

.modal-body {
    padding: 30px;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 20px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
    font-size: 14px;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 12px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.3s;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

#searchInput:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

#searchInput::placeholder {
    color: #999;
}

.form-full-width {
    grid-column: 1 / -1;
}

.photo-preview {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    margin: 15px auto;
    display: none;
    border: 4px solid #667eea;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.alert {
    padding: 15px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    font-weight: 500;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border-left: 4px solid #28a745;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border-left: 4px solid #dc3545;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.empty-state-icon {
    font-size: 80px;
    margin-bottom: 20px;
}

.empty-state h3 {
    font-size: 24px;
    color: #666;
    margin-bottom: 10px;
}

.empty-state p {
    font-size: 16px;
    color: #999;
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .modal-content {
        width: 95%;
        margin: 5% auto;
    }

    .year-buttons {
        gap: 10px;
    }

    .year-btn {
        padding: 10px 20px;
        font-size: 13px;
    }

    .navbar {
        padding: 15px 20px;
    }

    .navbar h1 {
        font-size: 18px;
    }

    .main-content {
        padding: 20px;
    }

    .page-header {
        flex-direction: column;
        gap: 20px;
        text-align: center;
    }

    .year-filter-header {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
    }

    table {
        font-size: 12px;
    }

    th, td {
        padding: 10px;
    }
}
    </style>
</head>
<body>
    <nav class="navbar">
        <div>
            <h1>🎓 NIT AMMS - Manage Students</h1>
        </div>
        <div class="user-info">
            <a href="index.php" class="btn btn-secondary">← Back</a>
            <span>👨‍💼 <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="page-header">
            <h2>👨‍🎓 Students Management</h2>
            <button onclick="openModal()" class="btn btn-primary">
                ➕ Add New Student
            </button>
        </div>

        <!-- Year Filter Section -->
        <div class="year-filter-container">
            <div class="year-filter-header">
                <h3>
                    <span>📅</span>
                    <span>Filter by Admission Year</span>
                </h3>
                <div class="stats-card">
                    <span>👥</span>
                    <div>
                        <div style="font-size: 12px; opacity: 0.9;">Total Students</div>
                        <div style="font-size: 20px;"><?php echo $total_students; ?></div>
                    </div>
                </div>
            </div>
            <div class="year-buttons">
                <?php foreach ($available_years as $year): ?>
                    <a href="?year=<?php echo urlencode($year); ?>" 
                       class="year-btn <?php echo ($year === $selected_year) ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($year); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="table-container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h3 style="color: #333; margin: 0;">
                    Students - <?php echo htmlspecialchars($selected_year); ?>
                </h3>
                <div style="position: relative; width: 400px;">
                    <input type="text" id="searchInput" onkeyup="searchTable()" 
                           placeholder="🔍 Search by name, roll no, email, department..." 
                           style="width: 100%; padding: 12px 20px; border: 2px solid #e0e0e0; border-radius: 25px; font-size: 14px; transition: all 0.3s;">
                </div>
            </div>

            <?php if ($students->num_rows > 0): ?>
            <table id="studentsTable">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Roll No</th>
                        <th>Admission No</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Department</th>
                        <th>Class/Section</th>
                        <th>Year</th>
                        <th>Semester</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($student = $students->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?php if (!empty($student['photo']) && file_exists("../uploads/students/" . $student['photo'])): ?>
                                <img src="../uploads/students/<?php echo htmlspecialchars($student['photo']); ?>" 
                                     alt="Photo" class="profile-photo">
                            <?php else: ?>
                                <span style="font-size: 35px;">👤</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                        <td><?php echo htmlspecialchars($student['admission_number'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                        <td><?php echo htmlspecialchars($student['phone']); ?></td>
                        <td><?php echo htmlspecialchars($student['dept_name']); ?></td>
                        <td>
                            <span class="badge badge-info">
                                <?php 
                                if ($student['section']) {
                                    echo htmlspecialchars($student['section']);
                                } else {
                                    echo htmlspecialchars($student['class_name']);
                                }
                                ?>
                            </span>
                        </td>
                        <td><?php echo $student['year']; ?></td>
                        <td><?php echo $student['semester']; ?></td>
                        <td>
                            <?php if ($student['is_active']): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button onclick="editStudent(<?php echo htmlspecialchars(json_encode($student)); ?>)" class="btn btn-primary btn-sm">
                                ✏️ Edit
                            </button>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                <input type="hidden" name="new_status" value="<?php echo $student['is_active'] ? 0 : 1; ?>">
                                <button type="submit" name="toggle_status" class="btn btn-warning btn-sm">
                                    <?php echo $student['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            </form>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this student?');">
                                <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                <button type="submit" name="delete_student" class="btn btn-danger btn-sm">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <h3>No Students Found</h3>
                <p>There are no students for admission year <?php echo htmlspecialchars($selected_year); ?></p>
                <p style="margin-top: 10px;">Click "Add New Student" to add students for this year.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add/Edit Student Modal -->
    <div id="addStudentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">➕ Add New Student</h3>
                <span class="close-btn" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data" id="studentForm">
                    <input type="hidden" name="student_id" id="studentId" value="">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Roll Number:</label>
                            <input type="text" name="roll_number" id="rollNumber" required placeholder="e.g., CSE2023001">
                        </div>

                        <div class="form-group">
                            <label>Admission Number:</label>
                            <input type="text" name="admission_number" id="admissionNumber" required placeholder="e.g., ADM2023001">
                        </div>
                        
                        <div class="form-group">
                            <label>Full Name:</label>
                            <input type="text" name="full_name" id="fullName" required placeholder="Enter full name">
                        </div>
                        
                        <div class="form-group">
                            <label>Email:</label>
                            <input type="email" name="email" id="email" required placeholder="student@nit.edu">
                        </div>
                        
                        <div class="form-group">
                            <label>Phone:</label>
                            <input type="text" name="phone" id="phone" required placeholder="10-digit number">
                        </div>
                        
                        <div class="form-group">
                            <label>Password:</label>
                            <input type="password" name="password" id="password" placeholder="Enter password (leave blank to keep current)">
                        </div>
                        
                        <div class="form-group">
                            <label>Admission Year:</label>
                            <select name="admission_year" id="admissionYear" required>
                                <option value="">Select Year</option>
                                <?php
                                $start_year = $current_year - 5;
                                $end_year = $current_year + 2;
                                for ($y = $end_year; $y >= $start_year; $y--) {
                                    $year_string = $y . '-' . ($y + 1);
                                    $selected = ($year_string === $selected_year) ? 'selected' : '';
                                    echo "<option value='$year_string' $selected>$year_string</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Department:</label>
                            <select name="department_id" id="departmentId" required>
                                <option value="">Select Department</option>
                                <?php 
                                $departments->data_seek(0);
                                while ($dept = $departments->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $dept['id']; ?>">
                                        <?php echo htmlspecialchars($dept['dept_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Class/Section:</label>
                            <select name="class_id" id="classId" required>
                                <option value="">Select Class</option>
                                <?php 
                                $classes->data_seek(0);
                                while ($class = $classes->fetch_assoc()): ?>
                                    <option value="<?php echo $class['id']; ?>">
                                        <?php echo htmlspecialchars($class['section']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Year:</label>
                            <select name="year" id="year" required>
                                <option value="">Select Year</option>
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Semester:</label>
                            <select name="semester" id="semester" required>
                                <option value="">Select Semester</option>
                                <option value="1">1st Semester</option>
                                <option value="2">2nd Semester</option>
                                <option value="3">3rd Semester</option>
                                <option value="4">4th Semester</option>
                                <option value="5">5th Semester</option>
                                <option value="6">6th Semester</option>
                                <option value="7">7th Semester</option>
                                <option value="8">8th Semester</option>
                            </select>
                        </div>
                        
                        <div class="form-group form-full-width">
                            <label>Student Photo:</label>
                            <input type="file" name="photo" id="photo" accept="image/*" onchange="previewPhoto(this)">
                            <img id="photoPreview" class="photo-preview" alt="Photo Preview">
                        </div>
                    </div>
                    
                    <div style="text-align: center; margin-top: 30px;">
                        <button type="submit" name="add_student" class="btn btn-primary" style="padding: 14px 40px;">
                            <span id="submitBtnText">➕ Add Student</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script>
    // Modal Functions
function openModal() {
    resetForm();
    document.getElementById('addStudentModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
    document.getElementById('modalTitle').innerHTML = '➕ Add New Student';
    document.getElementById('submitBtnText').innerHTML = '➕ Add Student';
    document.getElementById('password').required = true;
}

function editStudent(student) {
    document.getElementById('modalTitle').innerHTML = '✏️ Edit Student';
    document.getElementById('submitBtnText').innerHTML = '💾 Update Student';
    document.getElementById('password').required = false;
    document.getElementById('password').placeholder = 'Leave blank to keep current password';
    
    document.getElementById('studentId').value = student.id;
    document.getElementById('rollNumber').value = student.roll_number;
    document.getElementById('admissionNumber').value = student.admission_number;
    document.getElementById('fullName').value = student.full_name;
    document.getElementById('email').value = student.email;
    document.getElementById('phone').value = student.phone;
    document.getElementById('departmentId').value = student.department_id;
    document.getElementById('classId').value = student.class_id;
    document.getElementById('year').value = student.year;
    document.getElementById('semester').value = student.semester;
    document.getElementById('admissionYear').value = student.admission_year;
    
    if (student.photo) {
        document.getElementById('photoPreview').src = '../uploads/students/' + student.photo;
        document.getElementById('photoPreview').style.display = 'block';
    }
    
    document.getElementById('addStudentModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function resetForm() {
    document.getElementById('studentForm').reset();
    document.getElementById('studentId').value = '';
    document.getElementById('photoPreview').style.display = 'none';
    document.getElementById('password').required = true;
    document.getElementById('password').placeholder = 'Enter password';
}

function closeModal() {
    document.getElementById('addStudentModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    resetForm();
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('addStudentModal');
    if (event.target == modal) {
        closeModal();
    }
}

// Photo Preview
function previewPhoto(input) {
    const preview = document.getElementById('photoPreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Search Table
function searchTable() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toUpperCase();
    const table = document.getElementById('studentsTable');
    const tr = table.getElementsByTagName('tr');

    for (let i = 1; i < tr.length; i++) {
        let txtValue = '';
        const td = tr[i].getElementsByTagName('td');
        
        // Search through roll no, admission no, name, email, department columns
        for (let j = 1; j <= 6; j++) {
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

// Close alert messages after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
});
</script>
</body>
</html>