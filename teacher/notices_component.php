<?php
/**
 * NOTICES COMPONENT - UNIVERSAL DISPLAY FOR ALL USER ROLES
 * Place this file in your project root or admin folder
 * Include it in all dashboard files
 */

if (!function_exists('getActiveNotices')) {
    function getActiveNotices($user_role) {
        global $conn;
        
        $today = date('Y-m-d');
        
        // Normalize role name to handle both singular and plural forms
        $role_variants = [$user_role];
        
        // Add plural/singular variants
        if (substr($user_role, -1) !== 's') {
            $role_variants[] = $user_role . 's';
        } else {
            $role_variants[] = substr($user_role, 0, -1);
        }
        
        // Also add lowercase variants
        foreach ($role_variants as $variant) {
            $role_variants[] = strtolower($variant);
        }
        
        // Remove duplicates
        $role_variants = array_unique($role_variants);
        
        // Build SQL query with proper escaping
        $placeholders = implode(',', array_fill(0, count($role_variants), '?'));
        
        $query = "SELECT * FROM notices 
                  WHERE is_active = 1 
                  AND start_date <= ? 
                  AND (end_date IS NULL OR end_date >= ?)
                  AND (target_audience = 'all' OR target_audience IN ($placeholders))
                  ORDER BY 
                      FIELD(priority, 'high', 'medium', 'low'),
                      created_at DESC";
        
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            error_log("Notice query preparation failed: " . $conn->error);
            return false;
        }
        
        // Build parameters array
        $types = 'ss' . str_repeat('s', count($role_variants));
        $params = array_merge([$today, $today], $role_variants);
        
        // Bind parameters dynamically
        $bind_params = [$types];
        foreach ($params as $key => $value) {
            $bind_params[] = &$params[$key];
        }
        
        call_user_func_array([$stmt, 'bind_param'], $bind_params);
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result;
    }
}

if (!function_exists('displayNotices')) {
    function displayNotices($user_role) {
        $notices = getActiveNotices($user_role);
        
        if (!$notices || $notices->num_rows === 0) {
            return; // Don't display anything if no notices
        }
?>
<style>
/* Notices Section Styles */
.notices-section {
    margin: 30px 0;
    animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.notice-banner {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    padding: 25px 30px;
    margin-bottom: 20px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    border-left: 5px solid;
    animation: slideInRight 0.5s ease-out;
    position: relative;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.notice-banner:hover {
    transform: translateX(5px);
    box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
}

@keyframes slideInRight {
    from { opacity: 0; transform: translateX(50px); }
    to { opacity: 1; transform: translateX(0); }
}

.notice-banner::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, transparent, currentColor, transparent);
    animation: shimmer 2s infinite;
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.notice-banner.info {
    border-left-color: #17a2b8;
    background: linear-gradient(135deg, rgba(23, 162, 184, 0.05), rgba(255, 255, 255, 0.95));
}

.notice-banner.info::before { color: #17a2b8; }

.notice-banner.warning {
    border-left-color: #ffc107;
    background: linear-gradient(135deg, rgba(255, 193, 7, 0.1), rgba(255, 255, 255, 0.95));
}

.notice-banner.warning::before { color: #ffc107; }

.notice-banner.success {
    border-left-color: #28a745;
    background: linear-gradient(135deg, rgba(40, 167, 69, 0.05), rgba(255, 255, 255, 0.95));
}

.notice-banner.success::before { color: #28a745; }

.notice-banner.danger {
    border-left-color: #dc3545;
    background: linear-gradient(135deg, rgba(220, 53, 69, 0.1), rgba(255, 255, 255, 0.95));
    animation: pulse 2s infinite;
}

.notice-banner.danger::before { color: #dc3545; }

@keyframes pulse {
    0%, 100% { box-shadow: 0 10px 40px rgba(220, 53, 69, 0.2); }
    50% { box-shadow: 0 15px 50px rgba(220, 53, 69, 0.4); }
}

.notice-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
    gap: 15px;
    flex-wrap: wrap;
}

.notice-icon {
    font-size: 32px;
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.notice-title {
    font-size: 20px;
    font-weight: 700;
    color: #2c3e50;
    flex: 1;
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 200px;
}

.notice-priority {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
}

.priority-high {
    background: linear-gradient(135deg, #ff6b6b, #ee5a5a);
    color: white;
    animation: blink 1s infinite;
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.priority-medium {
    background: linear-gradient(135deg, #ffc107, #ff9800);
    color: #000;
}

.priority-low {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
}

.notice-message {
    color: #555;
    line-height: 1.8;
    font-size: 15px;
    margin-bottom: 15px;
    padding-left: 44px;
    word-wrap: break-word;
}

.notice-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-left: 44px;
    padding-top: 15px;
    border-top: 1px solid rgba(0, 0, 0, 0.1);
    font-size: 13px;
    color: #999;
    flex-wrap: wrap;
    gap: 10px;
}

.notice-date {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.notice-audience {
    background: rgba(102, 126, 234, 0.1);
    color: #667eea;
    padding: 4px 12px;
    border-radius: 15px;
    font-weight: 600;
}

.notices-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 20px 0;
    flex-wrap: wrap;
    gap: 15px;
}

.notices-header h3 {
    font-size: 24px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
}

.notice-count {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 700;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

/* Responsive Design */
@media (max-width: 768px) {
    .notice-banner {
        padding: 20px;
    }
    
    .notice-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .notice-message {
        padding-left: 0;
    }
    
    .notice-footer {
        flex-direction: column;
        align-items: flex-start;
        padding-left: 0;
    }
    
    .notices-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .notices-header h3 {
        font-size: 20px;
    }
}

@media (max-width: 480px) {
    .notice-title {
        font-size: 18px;
    }
    
    .notice-message {
        font-size: 14px;
    }
    
    .notice-icon {
        font-size: 24px;
    }
}
</style>

<div class="notices-section">
    <div class="notices-header">
        <h3>📢 Important Notices</h3>
        <span class="notice-count"><?php echo $notices->num_rows; ?> Active</span>
    </div>
    
    <?php while ($notice = $notices->fetch_assoc()): 
        // Determine icon based on notice type
        $icon = '📢';
        if ($notice['notice_type'] === 'info') $icon = 'ℹ️';
        elseif ($notice['notice_type'] === 'success') $icon = '✅';
        elseif ($notice['notice_type'] === 'warning') $icon = '⚠️';
        elseif ($notice['notice_type'] === 'danger') $icon = '🚨';
    ?>
        <div class="notice-banner <?php echo htmlspecialchars($notice['notice_type']); ?>">
            <div class="notice-header">
                <div class="notice-title">
                    <span class="notice-icon"><?php echo $icon; ?></span>
                    <?php echo htmlspecialchars($notice['title']); ?>
                </div>
                <span class="notice-priority priority-<?php echo htmlspecialchars($notice['priority']); ?>">
                    <?php echo strtoupper(htmlspecialchars($notice['priority'])); ?> PRIORITY
                </span>
            </div>
            
            <div class="notice-message">
                <?php echo nl2br(htmlspecialchars($notice['message'])); ?>
            </div>
            
            <div class="notice-footer">
                <div class="notice-date">
                    <span>📅 <?php echo date('M d, Y', strtotime($notice['start_date'])); ?></span>
                    <?php if ($notice['end_date']): ?>
                        <span>⏰ Until: <?php echo date('M d, Y', strtotime($notice['end_date'])); ?></span>
                    <?php endif; ?>
                </div>
                <span class="notice-audience">
                    👥 <?php echo ucfirst(htmlspecialchars($notice['target_audience'])); ?>
                </span>
            </div>
        </div>
    <?php endwhile; ?>
</div>
<?php
    }
}
?>