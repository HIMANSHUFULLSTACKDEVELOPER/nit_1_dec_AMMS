<?php
require_once '../db.php';
checkRole(['admin']);

$user = getCurrentUser();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_parent'])) {
        $parent_name = sanitize($_POST['parent_name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $student_id = intval($_POST['student_id']);
        $relationship = sanitize($_POST['relationship']);
        
        // Handle photo upload
        $photo_path = NULL;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/parents/';
            
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_ext, $allowed) && $_FILES['photo']['size'] <= 5242880) {
                $new_filename = 'parent_' . time() . '_' . uniqid() . '.' . $file_ext;
                $photo_path = $new_filename;
                move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $new_filename);
            }
        }
        
        $stmt = $conn->prepare("INSERT INTO parents (parent_name, email, phone, password, student_id, relationship, photo) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssiss", $parent_name, $email, $phone, $password, $student_id, $relationship, $photo_path);
        
        if ($stmt->execute()) {
            $success = "Parent added successfully!";
        } else {
            $error = "Error adding parent: " . $conn->error;
        }
    }
    
    if (isset($_POST['delete_parent'])) {
        $parent_id = intval($_POST['parent_id']);
        
        // Get photo path before deleting
        $result = $conn->query("SELECT photo FROM parents WHERE id = $parent_id");
        if ($row = $result->fetch_assoc() && $row['photo']) {
            @unlink('../uploads/parents/' . $row['photo']);
        }
        
        if ($conn->query("DELETE FROM parents WHERE id = $parent_id")) {
            $success = "Parent deleted successfully!";
        } else {
            $error = "Error deleting parent: " . $conn->error;
        }
    }
}

// Get all parents
$parents_query = "SELECT p.*, s.roll_number, s.full_name as student_name, d.dept_name
                  FROM parents p
                  JOIN students s ON p.student_id = s.id
                  LEFT JOIN departments d ON s.department_id = d.id
                  ORDER BY p.parent_name";
$parents = $conn->query($parents_query);

// Get students for dropdown
$students = $conn->query("SELECT id, roll_number, full_name FROM students WHERE is_active = 1 ORDER BY roll_number");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Parents - Admin</title>
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

        .search-bar-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 20px 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .search-wrapper {
            flex: 1;
            position: relative;
        }

        #searchInput {
            width: 100%;
            padding: 15px 50px 15px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
        }

        #searchInput:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 20px;
            color: #667eea;
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

        tbody tr:hover {
            background: rgba(102, 126, 234, 0.05);
            transition: all 0.3s;
        }

        .badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-info { background: #d1ecf1; color: #0c5460; }

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

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b, #ee5a5a);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
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
            animation: slideIn 0.5s;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
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

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                width: 95%;
                margin: 5% auto;
            }

            .search-bar-container {
                flex-direction: column;
            }

            .navbar {
                padding: 15px 20px;
            }

            .navbar h1 {
                font-size: 18px;
            }

            .user-info {
                gap: 10px;
                font-size: 14px;
            }

            .main-content {
                padding: 20px;
            }

            table {
                font-size: 13px;
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
            <h1>🎓 NIT AMMS - Manage Parents</h1>
        </div>
        <div class="user-info">
            <a href="index.php" class="btn btn-secondary">← Back</a>
            <span>👨‍💼 <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <?php if (isset($success)): ?>
            <div class="alert alert-success">✅ <?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">❌ <?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Search Bar and Add Button -->
        <div class="search-bar-container">
            <div class="search-wrapper">
                <input type="text" id="searchInput" placeholder="🔍 Search by Parent Name, Email, Phone, Student Name, Roll Number...">
                <span class="search-icon">🔎</span>
            </div>
            <button class="btn btn-primary" onclick="openModal()">➕ Add Parent</button>
        </div>

        <!-- Parents Table -->
        <div class="table-container">
            <h3 style="margin-bottom: 20px; font-size: 20px; color: #333;">👨‍👩‍👦 All Parents (<?php echo $parents->num_rows; ?>)</h3>
            <table id="parentsTable">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Parent Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Student</th>
                        <th>Roll Number</th>
                        <th>Department</th>
                        <th>Relationship</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($parents->num_rows > 0) {
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
                        <td><?php echo htmlspecialchars($parent['parent_name']); ?></td>
                        <td><?php echo htmlspecialchars($parent['email']); ?></td>
                        <td><?php echo htmlspecialchars($parent['phone']); ?></td>
                        <td><?php echo htmlspecialchars($parent['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($parent['roll_number']); ?></td>
                        <td><?php echo htmlspecialchars($parent['dept_name']); ?></td>
                        <td><span class="badge badge-info"><?php echo ucfirst($parent['relationship']); ?></span></td>
                        <td>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this parent?');">
                                <input type="hidden" name="parent_id" value="<?php echo $parent['id']; ?>">
                                <button type="submit" name="delete_parent" class="btn btn-danger btn-sm">🗑️ Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                    } else {
                        echo '<tr><td colspan="9" style="text-align:center; padding: 40px; color: #999;">No parents found. Click "Add Parent" to get started.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Parent Modal -->
    <div id="addParentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>➕ Add New Parent</h3>
                <span class="close-btn" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data" id="parentForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>👤 Parent Name: *</label>
                            <input type="text" name="parent_name" required placeholder="Enter full name">
                        </div>
                        
                        <div class="form-group">
                            <label>📧 Email: *</label>
                            <input type="email" name="email" required placeholder="parent@example.com">
                        </div>
                        
                        <div class="form-group">
                            <label>📱 Phone: *</label>
                            <input type="text" name="phone" required placeholder="Enter phone number" pattern="[0-9]{10}" title="Please enter 10 digit phone number">
                        </div>
                        
                        <div class="form-group">
                            <label>🔒 Password: *</label>
                            <input type="password" name="password" required placeholder="Enter password" minlength="6">
                        </div>
                        
                        <div class="form-group">
                            <label>🎓 Student: *</label>
                            <select name="student_id" required>
                                <option value="">-- Select Student --</option>
                                <?php 
                                $students->data_seek(0);
                                while ($student = $students->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $student['id']; ?>">
                                        <?php echo htmlspecialchars($student['roll_number'] . ' - ' . $student['full_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>👨‍👩‍👦 Relationship: *</label>
                            <select name="relationship" required>
                                <option value="">-- Select Relationship --</option>
                                <option value="father">Father</option>
                                <option value="mother">Mother</option>
                                <option value="guardian">Guardian</option>
                            </select>
                        </div>
                        
                        <div class="form-group form-full-width">
                            <label>📸 Profile Photo (Optional - JPG, PNG, GIF - Max 5MB):</label>
                            <input type="file" name="photo" accept="image/*" onchange="previewPhoto(this)">
                            <img id="photoPreview" class="photo-preview" alt="Preview">
                        </div>
                        
                        <div class="form-full-width" style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 20px;">
                            <button type="button" class="btn btn-secondary" onclick="closeModal()">❌ Cancel</button>
                            <button type="submit" name="add_parent" class="btn btn-primary">✅ Add Parent</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div style="background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 50%, #2a3254 100%); position: relative; overflow: hidden; margin-top: 40px;">
        <div style="height: 2px; background: linear-gradient(90deg, #4a9eff, #00d4ff, #4a9eff, #00d4ff); background-size: 200% 100%;"></div>
        
        <div style="max-width: 1000px; margin: 0 auto; padding: 30px 20px 20px;">
            <div style="background: rgba(255, 255, 255, 0.03); padding: 20px 20px; border-radius: 15px; border: 1px solid rgba(74, 158, 255, 0.15); text-align: center; box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);">
                <p style="color: #ffffff; font-size: 14px; margin: 0 0 12px; font-weight: 500; letter-spacing: 0.5px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">✨ Designed & Developed by</p>
                
                <a href="https://himanshufullstackdeveloper.github.io/techyugsoftware/" style="display: inline-block; color: #ffffff; font-size: 16px; font-weight: 700; text-decoration: none; padding: 8px 24px; border: 2px solid #4a9eff; border-radius: 30px; background: linear-gradient(135deg, rgba(74, 158, 255, 0.2), rgba(0, 212, 255, 0.2)); box-shadow: 0 3px 12px rgba(74, 158, 255, 0.3); margin-bottom: 15px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
                    🚀 Techyug Software Pvt. Ltd.
                </a>
                
                <div style="width: 50%; height: 1px; background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent); margin: 15px auto;"></div>
                
                <p style="color: #888; font-size: 10px; margin: 0 0 12px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">💼 Development Team</p>
                
                <div style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; margin-top: 12px;">
                    <a href="https://himanshufullstackdeveloper.github.io/portfoilohimanshu/" style="color: #ffffff; font-size: 13px; text-decoration: none; padding: 8px 16px; background: linear-gradient(135deg, rgba(74, 158, 255, 0.25), rgba(0, 212, 255, 0.25)); border-radius: 20px; border: 1px solid rgba(74, 158, 255, 0.4); display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 3px 10px rgba(74, 158, 255, 0.2); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
                        <span style="font-size: 16px;">👨‍💻</span>
                        <span style="font-weight: 600;">Himanshu Patil</span>
                    </a>
                    
                    <a href="https://devpranaypanore.github.io/Pranaypanore-live-.html/" style="color: #ffffff; font-size: 13px; text-decoration: none; padding: 8px 16px; background: linear-gradient(135deg, rgba(74, 158, 255, 0.25), rgba(0, 212, 255, 0.25)); border-radius: 20px; border: 1px solid rgba(74, 158, 255, 0.4); display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 3px 10px rgba(74, 158, 255, 0.2); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
                        <span style="font-size: 16px;">👨‍💻</span>
                        <span style="font-weight: 600;">Pranay Panore</span>
                    </a>
                </div>
                
                <div style="margin-top: 15px; display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;">
                    <span style="color: #4a9eff; font-size: 10px; padding: 4px 12px; background: rgba(74, 158, 255, 0.1); border-radius: 12px; border: 1px solid rgba(74, 158, 255, 0.3); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">Full Stack</span>
                    <span style="color: #00d4ff; font-size: 10px; padding: 4px 12px; background: rgba(0, 212, 255, 0.1); border-radius: 12px; border: 1px solid rgba(0, 212, 255, 0.3); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">UI/UX</span>
                    <span style="color: #4a9eff; font-size: 10px; padding: 4px 12px; background: rgba(74, 158, 255, 0.1); border-radius: 12px; border: 1px solid rgba(74, 158, 255, 0.3); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">Database</span>
                </div>
            </div>
            
            <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.1); text-align: center;">
                <p style="color: #888; font-size: 12px; margin: 0 0 10px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">© 2025 NIT AMMS. All rights reserved.</p>
                
                <p style="color: #666; font-size: 11px; margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
                    Made with <span style="color: #ff4757; font-size: 14px;">❤️</span> by Techyug Software
                </p>
                
                <div style="margin-top: 15px; display: flex; justify-content: center; gap: 10px;">
                    <a href="#" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; background: rgba(74, 158, 255, 0.1); border: 1px solid rgba(74, 158, 255, 0.3); border-radius: 50%; color: #4a9eff; text-decoration: none; font-size: 14px;">📧</a>
                    <a href="#" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; background: rgba(74, 158, 255, 0.1); border: 1px solid rgba(74, 158, 255, 0.3); border-radius: 50%; color: #4a9eff; text-decoration: none; font-size: 14px;">🌐</a>
                    <a href="#" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; background: rgba(74, 158, 255, 0.1); border: 1px solid rgba(74, 158, 255, 0.3); border-radius: 50%; color: #4a9eff; text-decoration: none; font-size: 14px;">💼</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Open Modal
        function openModal() {
            document.getElementById('addParentModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        // Close Modal
        function closeModal() {
            document.getElementById('addParentModal').style.display = 'none';
            document.body.style.overflow = 'auto';
            document.getElementById('parentForm').reset();
            document.getElementById('photoPreview').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('addParentModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        // Photo Preview Function
        function previewPhoto(input) {
            const preview = document.getElementById('photoPreview');
            
            if (input.files && input.files[0]) {
                const file = input.files[0];
                
                // Check file size (5MB)
                if (file.size > 5242880) {
                    alert('File size must be less than 5MB!');
                    input.value = '';
                    preview.style.display = 'none';
                    return;
                }
                
                // Check file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Only JPG, PNG, and GIF files are allowed!');
                    input.value = '';
                    preview.style.display = 'none';
                    return;
                }
                
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        }

        // Search Functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const table = document.getElementById('parentsTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            let visibleCount = 0;
            
            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const cells = row.getElementsByTagName('td');
                let found = false;
                
                // Skip if it's the "no parents" message row
                if (cells.length === 1 && cells[0].getAttribute('colspan')) {
                    continue;
                }
                
                for (let j = 0; j < cells.length; j++) {
                    const cellText = cells[j].textContent || cells[j].innerText;
                    if (cellText.toLowerCase().indexOf(searchValue) > -1) {
                        found = true;
                        break;
                    }
                }
                
                if (found) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            }
            
            // Show message if no results found
            const tbody = table.getElementsByTagName('tbody')[0];
            let noResultRow = document.getElementById('noResultRow');
            
            if (visibleCount === 0 && searchValue !== '') {
                if (!noResultRow) {
                    noResultRow = document.createElement('tr');
                    noResultRow.id = 'noResultRow';
                    noResultRow.innerHTML = '<td colspan="9" style="text-align:center; padding: 40px; color: #999;">🔍 No results found for "' + searchValue + '"</td>';
                    tbody.appendChild(noResultRow);
                } else {
                    noResultRow.style.display = '';
                    noResultRow.innerHTML = '<td colspan="9" style="text-align:center; padding: 40px; color: #999;">🔍 No results found for "' + searchValue + '"</td>';
                }
            } else {
                if (noResultRow) {
                    noResultRow.style.display = 'none';
                }
            }
        });

        // Form Validation
        document.getElementById('parentForm').addEventListener('submit', function(e) {
            const phone = this.querySelector('input[name="phone"]').value;
            const password = this.querySelector('input[name="password"]').value;
            
            // Validate phone number (10 digits)
            if (!/^\d{10}$/.test(phone)) {
                alert('Please enter a valid 10-digit phone number!');
                e.preventDefault();
                return false;
            }
            
            // Validate password length
            if (password.length < 6) {
                alert('Password must be at least 6 characters long!');
                e.preventDefault();
                return false;
            }
            
            return true;
        });

        // Auto-hide alerts after 5 seconds
        window.addEventListener('load', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.remove();
                    }, 500);
                }, 5000);
            });
        });

        // Escape key to close modal
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>