<?php
require_once '../db.php';
checkRole(['hod']);

$user = getCurrentUser();
$department_id = $_SESSION['department_id'];

// Get department info
$dept_query = "SELECT * FROM departments WHERE id = $department_id";
$dept_result = $conn->query($dept_query);
$department = $dept_result->fetch_assoc();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Filter options
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query - EXCLUDE the latest active notice (shown on dashboard)
$where_clauses = ["(n.target_role = 'hod' OR n.target_role = 'all')"];

// Get the latest active notice ID to exclude it
$latest_query = "SELECT id FROM notices 
                 WHERE (target_role = 'hod' OR target_role = 'all')
                 AND status = 'active'
                 AND (expiry_date IS NULL OR expiry_date >= CURDATE())
                 ORDER BY created_at DESC
                 LIMIT 1";
$latest_result = $conn->query($latest_query);
if ($latest_result->num_rows > 0) {
    $latest_notice = $latest_result->fetch_assoc();
    $where_clauses[] = "n.id != " . $latest_notice['id'];
}

if ($filter_status !== 'all') {
    if ($filter_status === 'active') {
        $where_clauses[] = "n.status = 'active'";
    } elseif ($filter_status === 'expired') {
        $where_clauses[] = "(n.status = 'expired' OR n.expiry_date < CURDATE())";
    } elseif ($filter_status === 'archived') {
        $where_clauses[] = "n.status = 'archived'";
    }
}

if (!empty($search)) {
    $search_safe = $conn->real_escape_string($search);
    $where_clauses[] = "(n.title LIKE '%$search_safe%' OR n.message LIKE '%$search_safe%')";
}

$where_sql = implode(' AND ', $where_clauses);

// Get total count
$count_query = "SELECT COUNT(*) as total FROM notices n WHERE $where_sql";
$count_result = $conn->query($count_query);
$total_notices = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_notices / $per_page);

// Get notices with pagination
$notices_query = "SELECT n.*, u.full_name as created_by_name 
                  FROM notices n
                  LEFT JOIN users u ON n.created_by = u.id
                  WHERE $where_sql
                  ORDER BY n.created_at DESC
                  LIMIT $per_page OFFSET $offset";
$notices = $conn->query($notices_query);

// Get statistics (excluding latest)
$stats_query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'active' AND expiry_date >= CURDATE() THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'expired' OR expiry_date < CURDATE() THEN 1 ELSE 0 END) as expired,
                SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived
                FROM notices
                WHERE (target_role = 'hod' OR target_role = 'all')
                AND id != (SELECT id FROM notices 
                           WHERE (target_role = 'hod' OR target_role = 'all')
                           AND status = 'active'
                           AND (expiry_date IS NULL OR expiry_date >= CURDATE())
                           ORDER BY created_at DESC
                           LIMIT 1)";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../Nit_logo.png" type="image/svg+xml" />
    <title>Notice History - NIT AMMS</title>
    <link rel="stylesheet" href="notice_history_style.css">
    <style>
        /* notice_history_style.css - Place this in /hod/ directory */

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
    position: sticky;
    top: 0;
    z-index: 1000;
}

.navbar-brand {
    display: flex;
    align-items: center;
    gap: 15px;
}

.navbar-logo {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.navbar h1 {
    color: white;
    font-size: 24px;
    font-weight: 700;
}

.nav-links {
    display: flex;
    gap: 15px;
    align-items: center;
}

.main-content {
    padding: 40px;
    max-width: 1600px;
    margin: 0 auto;
}

.page-header {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    padding: 40px;
    border-radius: 25px;
    margin-bottom: 30px;
    box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
}

.page-header h2 {
    font-size: 36px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 10px;
}

.breadcrumb {
    color: #666;
    font-size: 14px;
    margin-bottom: 15px;
}

.breadcrumb a {
    color: #667eea;
    text-decoration: none;
}

.page-subtitle {
    color: #888;
    font-size: 14px;
    margin-top: 10px;
    padding: 15px;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-radius: 12px;
    border-left: 4px solid #667eea;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: rgba(255, 255, 255, 0.95);
    padding: 25px;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    text-align: center;
    transition: transform 0.3s;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-icon {
    font-size: 36px;
    margin-bottom: 10px;
}

.stat-value {
    font-size: 32px;
    font-weight: 700;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 5px;
}

.stat-label {
    color: #666;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.filters-section {
    background: rgba(255, 255, 255, 0.95);
    padding: 30px;
    border-radius: 20px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
}

.filters-grid {
    display: grid;
    grid-template-columns: 2fr 1fr auto;
    gap: 15px;
    align-items: end;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-group label {
    font-size: 13px;
    font-weight: 600;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.form-control {
    padding: 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.3s;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.notices-container {
    background: rgba(255, 255, 255, 0.95);
    padding: 30px;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
}

.notice-item {
    background: white;
    border: 2px solid #f0f0f0;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 20px;
    transition: all 0.3s;
    position: relative;
    overflow: hidden;
}

.notice-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 5px;
    height: 100%;
    background: linear-gradient(135deg, #667eea, #764ba2);
}

.notice-item:hover {
    transform: translateX(5px);
    box-shadow: 0 5px 20px rgba(102, 126, 234, 0.2);
}

.notice-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 15px;
}

.notice-title {
    font-size: 20px;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 8px;
}

.notice-meta {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    font-size: 13px;
    color: #666;
    margin-bottom: 15px;
}

.notice-meta span {
    display: flex;
    align-items: center;
    gap: 5px;
}

.notice-message {
    color: #555;
    line-height: 1.6;
    margin-bottom: 15px;
}

.notice-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 15px;
    border-top: 1px solid #f0f0f0;
}

.badge {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-success {
    background: #d4edda;
    color: #155724;
}

.badge-warning {
    background: #fff3cd;
    color: #856404;
}

.badge-danger {
    background: #f8d7da;
    color: #721c24;
}

.badge-secondary {
    background: #e2e3e5;
    color: #383d41;
}

.badge-primary {
    background: #cfe2ff;
    color: #084298;
}

.priority-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 600;
}

.priority-high {
    background: #ffe5e5;
    color: #d32f2f;
}

.priority-medium {
    background: #fff3e0;
    color: #f57c00;
}

.priority-low {
    background: #e8f5e9;
    color: #388e3c;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-top: 30px;
    padding: 20px;
}

.pagination a, .pagination span {
    padding: 10px 15px;
    border-radius: 8px;
    text-decoration: none;
    color: #667eea;
    background: white;
    border: 2px solid #e0e0e0;
    transition: all 0.3s;
}

.pagination a:hover {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

.pagination .active {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-color: transparent;
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
    box-shadow: 0 4px 15px rgba(108, 117, 125, 0.4);
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

.btn-sm {
    padding: 8px 16px;
    font-size: 13px;
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

.empty-state h3 {
    color: #666;
    margin-bottom: 10px;
}

@media (max-width: 768px) {
    .navbar {
        padding: 15px 20px;
        flex-direction: column;
        gap: 15px;
    }
    
    .main-content {
        padding: 20px;
    }
    
    .filters-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .notice-header {
        flex-direction: column;
    }
    
    .notice-footer {
        flex-direction: column;
        gap: 10px;
        align-items: start;
    }
}
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-brand">
            <div class="navbar-logo">📜</div>
            <h1>Notice History</h1>
        </div>
        <div class="nav-links">
            <a href="dashboard.php" class="btn btn-secondary btn-sm">← Back to Dashboard</a>
            <a href="../logout.php" class="btn btn-danger btn-sm">🚪 Logout</a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h2>📜 Notice History & Archive</h2>
            <div class="breadcrumb">
                <a href="dashboard.php">Dashboard</a> / Notice History
            </div>
            <p class="page-subtitle">
                💡 <em>The latest active notice is displayed on the dashboard. This page shows all previous notices.</em>
            </p>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📋</div>
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Historical Notices</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-value"><?php echo $stats['active']; ?></div>
                <div class="stat-label">Active (Older)</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⏰</div>
                <div class="stat-value"><?php echo $stats['expired']; ?></div>
                <div class="stat-label">Expired</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-value"><?php echo $stats['archived']; ?></div>
                <div class="stat-label">Archived</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-section">
            <form method="GET" action="">
                <div class="filters-grid">
                    <div class="form-group">
                        <label>🔍 Search Notices</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search by title or message..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="form-group">
                        <label>📊 Filter by Status</label>
                        <select name="status" class="form-control">
                            <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Notices</option>
                            <option value="active" <?php echo $filter_status === 'active' ? 'selected' : ''; ?>>Active Only</option>
                            <option value="expired" <?php echo $filter_status === 'expired' ? 'selected' : ''; ?>>Expired Only</option>
                            <option value="archived" <?php echo $filter_status === 'archived' ? 'selected' : ''; ?>>Archived Only</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </div>
            </form>
        </div>

        <!-- Notices List -->
        <div class="notices-container">
            <h3 style="margin-bottom: 25px; color: #2c3e50;">
                📌 Previous Notices (<?php echo $total_notices; ?> total)
            </h3>

            <?php if ($notices->num_rows > 0): ?>
                <?php while ($notice = $notices->fetch_assoc()): 
                    // Determine status
                    $is_expired = strtotime($notice['expiry_date']) < time();
                    $current_status = $notice['status'];
                    if ($is_expired && $current_status !== 'archived') {
                        $current_status = 'expired';
                    }
                ?>
                <div class="notice-item">
                    <div class="notice-header">
                        <div>
                            <div class="notice-title">
                                <?php echo htmlspecialchars($notice['title']); ?>
                            </div>
                            <div class="notice-meta">
                                <span>👤 <?php echo htmlspecialchars($notice['created_by_name']); ?></span>
                                <span>📅 <?php echo date('M d, Y', strtotime($notice['created_at'])); ?></span>
                                <span>⏰ <?php echo date('h:i A', strtotime($notice['created_at'])); ?></span>
                                <?php if ($notice['expiry_date']): ?>
                                <span>⏳ Expires: <?php echo date('M d, Y', strtotime($notice['expiry_date'])); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 8px; align-items: flex-end;">
                            <?php
                            if ($current_status === 'active') {
                                echo '<span class="badge badge-success">✅ Active</span>';
                            } elseif ($current_status === 'expired') {
                                echo '<span class="badge badge-warning">⏰ Expired</span>';
                            } elseif ($current_status === 'archived') {
                                echo '<span class="badge badge-secondary">📦 Archived</span>';
                            }
                            
                            // Priority badge
                            $priority = $notice['priority'];
                            if ($priority === 'high') {
                                echo '<span class="priority-badge priority-high">🔴 High Priority</span>';
                            } elseif ($priority === 'medium') {
                                echo '<span class="priority-badge priority-medium">🟡 Medium</span>';
                            } else {
                                echo '<span class="priority-badge priority-low">🟢 Low</span>';
                            }
                            ?>
                        </div>
                    </div>

                    <div class="notice-message">
                        <?php echo nl2br(htmlspecialchars($notice['message'])); ?>
                    </div>

                    <div class="notice-footer">
                        <div>
                            <span class="badge badge-primary">
                                🎯 Target: <?php echo ucfirst($notice['target_role']); ?>
                            </span>
                        </div>
                        <div style="font-size: 12px; color: #999;">
                            ID: #<?php echo $notice['id']; ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search); ?>">
                            ← Previous
                        </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search); ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search); ?>">
                            Next →
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🔭</div>
                    <h3>No Previous Notices Found</h3>
                    <p>There are no historical notices matching your current filters.</p>
                    <?php if (!empty($search) || $filter_status !== 'all'): ?>
                        <a href="notice_history.php" class="btn btn-primary" style="margin-top: 20px;">
                            Clear Filters
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>