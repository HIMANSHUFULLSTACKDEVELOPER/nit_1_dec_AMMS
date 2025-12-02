<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../db.php';
checkRole(['admin']);

$user = getCurrentUser();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_teacher'])) {
        try {
            $username = sanitize($_POST['username']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $full_name = sanitize($_POST['full_name']);
            $email = sanitize($_POST['email']);
            $phone = sanitize($_POST['phone']);
            $department_id = intval($_POST['department_id']);
            $joining_year = isset($_POST['joining_year']) ? sanitize($_POST['joining_year']) : NULL;
            $is_active = 1;
            
            // Handle photo upload
            $photo_path = NULL;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/teachers/';
                
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($file_ext, $allowed) && $_FILES['photo']['size'] <= 5242880) {
                    $new_filename = 'teacher_' . time() . '_' . uniqid() . '.' . $file_ext;
                    $photo_path = 'uploads/teachers/' . $new_filename;
                    
                    if (!move_uploaded_file($_FILES['photo']['tmp_name'], '../' . $photo_path)) {
                        $photo_path = NULL;
                    }
                }
            }
            
            // Check if photo and joining_year columns exist
            $check_photo = $conn->query("SHOW COLUMNS FROM users LIKE 'photo'");
            $check_year = $conn->query("SHOW COLUMNS FROM users LIKE 'joining_year'");
            
            $photo_exists = ($check_photo && $check_photo->num_rows > 0);
            $year_exists = ($check_year && $check_year->num_rows > 0);
            
            // Build INSERT query based on available columns
            if ($photo_exists && $year_exists) {
                $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, phone, role, department_id, photo, joining_year, is_active) VALUES (?, ?, ?, ?, ?, 'teacher', ?, ?, ?, ?)");
                $stmt->bind_param("sssssissi", $username, $password, $full_name, $email, $phone, $department_id, $photo_path, $joining_year, $is_active);
            } elseif ($year_exists) {
                $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, phone, role, department_id, joining_year, is_active) VALUES (?, ?, ?, ?, ?, 'teacher', ?, ?, ?)");
                $stmt->bind_param("sssssisi", $username, $password, $full_name, $email, $phone, $department_id, $joining_year, $is_active);
            } elseif ($photo_exists) {
                $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, phone, role, department_id, photo, is_active) VALUES (?, ?, ?, ?, ?, 'teacher', ?, ?, ?)");
                $stmt->bind_param("sssssisi", $username, $password, $full_name, $email, $phone, $department_id, $photo_path, $is_active);
            } else {
                $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, phone, role, department_id, is_active) VALUES (?, ?, ?, ?, ?, 'teacher', ?, ?)");
                $stmt->bind_param("ssssssii", $username, $password, $full_name, $email, $phone, $department_id, $is_active);
            }
            
            if ($stmt->execute()) {
                $success = "✓ Teacher added successfully!";
            } else {
                $error = "Error adding teacher: " . $stmt->error;
            }
            $stmt->close();
            
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['toggle_status'])) {
        try {
            $teacher_id = intval($_POST['teacher_id']);
            $new_status = intval($_POST['new_status']);
            
            $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ?");
            $stmt->bind_param("ii", $new_status, $teacher_id);
            $stmt->execute();
            $stmt->close();
            
            $success = "✓ Teacher status updated!";
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['delete_teacher'])) {
        try {
            $teacher_id = intval($_POST['teacher_id']);
            
            // Get photo path before deleting
            $photo_stmt = $conn->prepare("SELECT photo FROM users WHERE id = ?");
            $photo_stmt->bind_param("i", $teacher_id);
            $photo_stmt->execute();
            $photo_result = $photo_stmt->get_result();
            
            if ($photo_row = $photo_result->fetch_assoc()) {
                if (!empty($photo_row['photo'])) {
                    $photo_file = '../' . $photo_row['photo'];
                    if (file_exists($photo_file)) {
                        unlink($photo_file);
                    }
                }
            }
            $photo_stmt->close();
            
            $delete_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $delete_stmt->bind_param("i", $teacher_id);
            $delete_stmt->execute();
            $delete_stmt->close();
            
            $success = "✓ Teacher deleted successfully!";
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Get selected year filter
$current_year = date('Y');
$selected_year = isset($_GET['year']) ? sanitize($_GET['year']) : 'all';

// Check if joining_year column exists
$check_joining_year = $conn->query("SHOW COLUMNS FROM users LIKE 'joining_year'");
$has_joining_year = ($check_joining_year && $check_joining_year->num_rows > 0);

if ($has_joining_year) {
    // Get all unique joining years from database
    $years_query = "SELECT DISTINCT joining_year FROM users WHERE role = 'teacher' AND joining_year IS NOT NULL ORDER BY joining_year DESC";
    $years_result = $conn->query($years_query);
    $available_years = [];
    if ($years_result) {
        while ($year_row = $years_result->fetch_assoc()) {
            if (!empty($year_row['joining_year'])) {
                $available_years[] = $year_row['joining_year'];
            }
        }
    }
    
    // If no years in database, add current and previous years
    if (empty($available_years)) {
        for ($i = 0; $i < 5; $i++) {
            $year = ($current_year - $i) . '-' . ($current_year - $i + 1);
            $available_years[] = $year;
        }
    }
    
    // Get teachers filtered by selected year
    if ($selected_year === 'all') {
        $teachers_query = "SELECT u.*, d.dept_name,
                           (SELECT COUNT(*) FROM classes WHERE teacher_id = u.id) as class_count
                           FROM users u
                           LEFT JOIN departments d ON u.department_id = d.id
                           WHERE u.role = 'teacher'
                           ORDER BY u.full_name";
        $teachers = $conn->query($teachers_query);
    } else {
        $teachers_query = "SELECT u.*, d.dept_name,
                           (SELECT COUNT(*) FROM classes WHERE teacher_id = u.id) as class_count
                           FROM users u
                           LEFT JOIN departments d ON u.department_id = d.id
                           WHERE u.role = 'teacher' AND u.joining_year = ?
                           ORDER BY u.full_name";
        $stmt = $conn->prepare($teachers_query);
        $stmt->bind_param("s", $selected_year);
        $stmt->execute();
        $teachers = $stmt->get_result();
    }
    
    // Get total count for selected year
    if ($selected_year === 'all') {
        $count_query = "SELECT COUNT(*) as total FROM users WHERE role = 'teacher'";
        $count_result = $conn->query($count_query);
    } else {
        $count_query = "SELECT COUNT(*) as total FROM users WHERE role = 'teacher' AND joining_year = ?";
        $count_stmt = $conn->prepare($count_query);
        $count_stmt->bind_param("s", $selected_year);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
    }
    $count_row = $count_result->fetch_assoc();
    $total_teachers = $count_row ? $count_row['total'] : 0;
} else {
    // No joining_year column - show all teachers
    $teachers_query = "SELECT u.*, d.dept_name,
                       (SELECT COUNT(*) FROM classes WHERE teacher_id = u.id) as class_count
                       FROM users u
                       LEFT JOIN departments d ON u.department_id = d.id
                       WHERE u.role = 'teacher'
                       ORDER BY u.full_name";
    $teachers = $conn->query($teachers_query);
    
    $count_result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'teacher'");
    $count_row = $count_result->fetch_assoc();
    $total_teachers = $count_row ? $count_row['total'] : 0;
}

// Get departments
$departments = $conn->query("SELECT * FROM departments ORDER BY dept_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teachers - Admin</title>
    <link rel="icon" href="../Nit_logo.png" type="image/svg+xml" />
    <link rel="stylesheet" href="manage_teachers.css">
</head>
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
    max-width: 900px;
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
    grid-template-columns: 1fr 1fr;
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

/* Footer Styles */
.footer {
    background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 50%, #2a3254 100%);
    position: relative;
    overflow: hidden;
    margin-top: 50px;
}

.footer-content {
    background: rgba(255, 255, 255, 0.03);
    padding: 20px 20px;
    border-radius: 15px;
    border: 1px solid rgba(74, 158, 255, 0.15);
    text-align: center;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
}

.footer-link {
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

.footer-link:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(74, 158, 255, 0.5);
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
<script>
    // Modal Functions
function openModal() {
    document.getElementById('addTeacherModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('addTeacherModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('addTeacherModal');
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
    const table = document.getElementById('teachersTable');
    
    if (!table) {
        return;
    }
    
    const tr = table.getElementsByTagName('tr');

    for (let i = 1; i < tr.length; i++) {
        let txtValue = '';
        const td = tr[i].getElementsByTagName('td');
        
        // Search through username, full name, email, phone, department columns
        for (let j = 1; j <= 5; j++) {
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
<body>
    <nav class="navbar">
        <div>
            <h1>🎓 NIT AMMS - Manage Teachers</h1>
        </div>
        <div class="user-info">
            <a href="index.php" class="btn btn-secondary">← Back</a>
            <span>👨‍💼 <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="page-header">
            <h2>👨‍🏫 Teachers Management</h2>
            <button onclick="openModal()" class="btn btn-primary">
                ➕ Add New Teacher
            </button>
        </div>

        <?php if ($has_joining_year): ?>
        <div class="year-filter-container">
            <div class="year-filter-header">
                <h3>
                    <span>📅</span>
                    <span>Filter by Joining Year</span>
                </h3>
                <div class="stats-card">
                    <span>👥</span>
                    <div>
                        <div style="font-size: 12px; opacity: 0.9;">Total Teachers</div>
                        <div style="font-size: 20px;"><?php echo $total_teachers; ?></div>
                    </div>
                </div>
            </div>
            <div class="year-buttons">
                <a href="?year=all" 
                   class="year-btn <?php echo ($selected_year === 'all') ? 'active' : ''; ?>">
                    All Teachers
                </a>
                <?php foreach ($available_years as $year): ?>
                    <a href="?year=<?php echo urlencode($year); ?>" 
                       class="year-btn <?php echo ($year === $selected_year) ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($year); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="table-container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h3 style="color: #333; margin: 0;">
                    <?php echo $has_joining_year ? ($selected_year === 'all' ? 'All Teachers' : 'Teachers - ' . htmlspecialchars($selected_year)) : 'All Teachers'; ?>
                </h3>
                <div style="position: relative; width: 400px;">
                    <input type="text" id="searchInput" onkeyup="searchTable()" 
                           placeholder="🔍 Search by name, username, email, department..." 
                           style="width: 100%; padding: 12px 20px; border: 2px solid #e0e0e0; border-radius: 25px; font-size: 14px; transition: all 0.3s;">
                </div>
            </div>

            <?php if ($teachers && $teachers->num_rows > 0): ?>
            <table id="teachersTable">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Department</th>
                        <?php if ($has_joining_year): ?>
                        <th>Joining Year</th>
                        <?php endif; ?>
                        <th>Classes</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($teacher = $teachers->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?php if (isset($teacher['photo']) && !empty($teacher['photo']) && file_exists('../' . $teacher['photo'])): ?>
                                <img src="../<?php echo htmlspecialchars($teacher['photo']); ?>" 
                                     alt="Photo" class="profile-photo">
                            <?php else: ?>
                                <span style="font-size: 40px;">👨‍🏫</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($teacher['username']); ?></td>
                        <td><?php echo htmlspecialchars($teacher['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                        <td><?php echo htmlspecialchars($teacher['phone']); ?></td>
                        <td><?php echo htmlspecialchars($teacher['dept_name'] ?? 'Not Assigned'); ?></td>
                        <?php if ($has_joining_year): ?>
                        <td>
                            <span class="badge badge-info">
                                <?php echo htmlspecialchars($teacher['joining_year'] ?? 'N/A'); ?>
                            </span>
                        </td>
                        <?php endif; ?>
                        <td><span class="badge badge-info"><?php echo $teacher['class_count']; ?></span></td>
                        <td>
                            <?php if ($teacher['is_active']): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="teacher_id" value="<?php echo $teacher['id']; ?>">
                                <input type="hidden" name="new_status" value="<?php echo $teacher['is_active'] ? 0 : 1; ?>">
                                <button type="submit" name="toggle_status" class="btn btn-warning btn-sm">
                                    <?php echo $teacher['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            </form>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this teacher?');">
                                <input type="hidden" name="teacher_id" value="<?php echo $teacher['id']; ?>">
                                <button type="submit" name="delete_teacher" class="btn btn-danger btn-sm">
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
                <h3>No Teachers Found</h3>
                <p>There are no teachers <?php echo ($has_joining_year && $selected_year !== 'all') ? 'for joining year ' . htmlspecialchars($selected_year) : 'in the system'; ?></p>
                <p style="margin-top: 10px;">Click "Add New Teacher" to add teachers.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Teacher Modal -->
    <div id="addTeacherModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>➕ Add New Teacher</h3>
                <span class="close-btn" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Username:</label>
                            <input type="text" name="username" required placeholder="Enter username">
                        </div>
                        
                        <div class="form-group">
                            <label>Password:</label>
                            <input type="password" name="password" required placeholder="Enter password">
                        </div>
                        
                        <div class="form-group">
                            <label>Full Name:</label>
                            <input type="text" name="full_name" required placeholder="Enter full name">
                        </div>
                        
                        <div class="form-group">
                            <label>Email:</label>
                            <input type="email" name="email" required placeholder="teacher@nit.edu">
                        </div>
                        
                        <div class="form-group">
                            <label>Phone:</label>
                            <input type="text" name="phone" required placeholder="10-digit number">
                        </div>
                        
                        <div class="form-group">
                            <label>Department:</label>
                            <select name="department_id" required>
                                <option value="">Select Department</option>
                                <?php 
                                if ($departments) {
                                    $departments->data_seek(0);
                                    while ($dept = $departments->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $dept['id']; ?>">
                                        <?php echo htmlspecialchars($dept['dept_name']); ?>
                                    </option>
                                <?php 
                                    endwhile;
                                }
                                ?>
                            </select>
                        </div>
                        
                        <?php if ($has_joining_year): ?>
                        <div class="form-group">
                            <label>Joining Year:</label>
                            <select name="joining_year" required>
                                <option value="">Select Year</option>
                                <?php
                                $start_year = $current_year - 10;
                                $end_year = $current_year + 2;
                                for ($y = $end_year; $y >= $start_year; $y--) {
                                    $year_string = $y . '-' . ($y + 1);
                                    $selected_attr = ($year_string === $selected_year && $selected_year !== 'all') ? 'selected' : '';
                                    echo "<option value='$year_string' $selected_attr>$year_string</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <div class="form-group form-full-width">
                            <label>Profile Photo (Optional):</label>
                            <input type="file" name="photo" accept="image/*" onchange="previewPhoto(this)">
                            <img id="photoPreview" class="photo-preview" alt="Preview">
                        </div>
                    </div>
                    
                    <div style="text-align: center; margin-top: 30px;">
                        <button type="submit" name="add_teacher" class="btn btn-primary" style="padding: 14px 40px;">
                            ➕ Add Teacher
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div style="height: 2px; background: linear-gradient(90deg, #4a9eff, #00d4ff, #4a9eff, #00d4ff); background-size: 200% 100%;"></div>
        <div style="max-width: 1000px; margin: 0 auto; padding: 30px 20px 20px;">
            <div class="footer-content">
                <p style="color: #ffffff; font-size: 14px; margin: 0 0 12px; font-weight: 500; letter-spacing: 0.5px;">✨ Designed & Developed by</p>
                <a href="https://himanshufullstackdeveloper.github.io/techyugsoftware/" class="footer-link">
                    🚀 Techyug Software Pvt. Ltd.
                </a>
            </div>
        </div>
    </div>

    <script src="manage_teachers.js"></script>
</body>
</html>