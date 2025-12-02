<?php
// test_login.php - Simple login without redirects for testing
session_start();

$error = '';
$success = '';

// Handle logout first
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    session_start();
    $success = 'Logged out successfully!';
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Test credentials
    if ($username === 'superadmin' && $password === 'Super@2024#Admin') {
        $_SESSION['superadmin_logged_in'] = true;
        $_SESSION['superadmin_username'] = $username;
        $_SESSION['login_time'] = time();
        $success = 'Login successful! You are now logged in.';
    } else {
        $error = 'Invalid username or password!';
    }
}

// Check login status
$is_logged_in = isset($_SESSION['superadmin_logged_in']) && $_SESSION['superadmin_logged_in'] === true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Login - NIT AMMS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 16px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-box {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .status-box h3 {
            color: #667eea;
            font-size: 14px;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .status-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .status-item:last-child {
            border-bottom: none;
        }
        
        .status-label {
            color: #666;
            font-size: 13px;
        }
        
        .status-value {
            font-weight: 600;
            font-size: 13px;
        }
        
        .status-value.success {
            color: #28a745;
        }
        
        .status-value.danger {
            color: #dc3545;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            margin-top: 10px;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .dashboard-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            padding: 12px;
            background: #e7f3ff;
            border-radius: 8px;
            color: #0056b3;
            text-decoration: none;
            font-weight: 600;
        }
        
        .dashboard-link:hover {
            background: #cce5ff;
        }
        
        .logged-in-box {
            background: #d4edda;
            border: 2px solid #c3e6cb;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .logged-in-box h2 {
            color: #155724;
            margin-bottom: 10px;
        }
        
        .user-info {
            background: white;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Test Login Page</h1>
        <p class="subtitle">NIT AMMS - Redirect Loop Testing</p>
        
        <?php if ($error): ?>
            <div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <!-- System Status -->
        <div class="status-box">
            <h3>🔍 System Status</h3>
            <div class="status-item">
                <span class="status-label">Session Active:</span>
                <span class="status-value <?php echo session_status() === PHP_SESSION_ACTIVE ? 'success' : 'danger'; ?>">
                    <?php echo session_status() === PHP_SESSION_ACTIVE ? '✅ Yes' : '❌ No'; ?>
                </span>
            </div>
            <div class="status-item">
                <span class="status-label">Logged In:</span>
                <span class="status-value <?php echo $is_logged_in ? 'success' : 'danger'; ?>">
                    <?php echo $is_logged_in ? '✅ Yes' : '❌ No'; ?>
                </span>
            </div>
            <div class="status-item">
                <span class="status-label">Dashboard File:</span>
                <span class="status-value <?php echo file_exists('superadmin/index.php') ? 'success' : 'danger'; ?>">
                    <?php echo file_exists('superadmin/index.php') ? '✅ Found' : '❌ Not Found'; ?>
                </span>
            </div>
            <div class="status-item">
                <span class="status-label">Session ID:</span>
                <span class="status-value"><?php echo substr(session_id(), 0, 10); ?>...</span>
            </div>
        </div>
        
        <?php if ($is_logged_in): ?>
            <!-- Logged In State -->
            <div class="logged-in-box">
                <h2>✅ Successfully Logged In!</h2>
                <div class="user-info">
                    <strong>Username:</strong> <?php echo htmlspecialchars($_SESSION['superadmin_username']); ?><br>
                    <strong>Login Time:</strong> <?php echo date('Y-m-d H:i:s', $_SESSION['login_time']); ?>
                </div>
            </div>
            
            <a href="superadmin/index.php" class="dashboard-link">
                📊 Go to Dashboard →
            </a>
            
            <a href="?action=logout" class="btn btn-secondary">🚪 Logout</a>
            
        <?php else: ?>
            <!-- Login Form -->
            <form method="POST">
                <div class="form-group">
                    <label>👤 Username</label>
                    <input type="text" name="username" value="superadmin" required>
                </div>
                
                <div class="form-group">
                    <label>🔑 Password</label>
                    <input type="password" name="password" value="Super@2024#Admin" required>
                </div>
                
                <button type="submit" class="btn btn-primary">🔓 Login</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>