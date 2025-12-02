<?php
// notices_component.php - Include this in all dashboard files

function getActiveNotices($user_role) {
    global $conn;
    
    $today = date('Y-m-d');
    
    // Get notices for current user role
    $query = "SELECT * FROM notices 
              WHERE is_active = 1 
              AND start_date <= '$today'
              AND (end_date IS NULL OR end_date >= '$today')
              AND (target_audience = 'all' OR target_audience = '$user_role')
              ORDER BY 
                  FIELD(priority, 'high', 'medium', 'low'),
                  created_at DESC";
    
    $result = $conn->query($query);
    return $result;
}

function displayNotices($user_role) {
    $notices = getActiveNotices($user_role);
    
    if ($notices && $notices->num_rows > 0):
?>
<style>
.notices-section {
    margin: 30px 0;
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
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(50px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
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
}

.notice-priority {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
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
}

.notice-date {
    display: flex;
    gap: 15px;
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
}

.notices-header h3 {
    font-size: 24px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: 10px;
}

.notice-count {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 700;
}

@media (max-width: 768px) {
    .notice-banner {
        padding: 20px;
    }
    
    .notice-header {
        flex-direction: column;
    }
    
    .notice-message {
        padding-left: 0;
    }
    
    .notice-footer {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
        padding-left: 0;
    }
}
</style>

<div class="notices-section">
    <div class="notices-header">
        <h3>📢 Important Notices</h3>
        <span class="notice-count"><?php echo $notices->num_rows; ?> Active</span>
    </div>
    
    <?php while ($notice = $notices->fetch_assoc()): 
        $icon = '📢';
        if ($notice['notice_type'] === 'info') $icon = 'ℹ️';
        elseif ($notice['notice_type'] === 'success') $icon = '✅';
        elseif ($notice['notice_type'] === 'warning') $icon = '⚠️';
        elseif ($notice['notice_type'] === 'danger') $icon = '🚨';
    ?>
        <div class="notice-banner <?php echo $notice['notice_type']; ?>">
            <div class="notice-header">
                <div class="notice-title">
                    <span class="notice-icon"><?php echo $icon; ?></span>
                    <?php echo htmlspecialchars($notice['title']); ?>
                </div>
                <span class="notice-priority priority-<?php echo $notice['priority']; ?>">
                    <?php echo strtoupper($notice['priority']); ?> PRIORITY
                </span>
            </div>
            
            <div class="notice-message">
                <?php echo nl2br(htmlspecialchars($notice['message'])); ?>
            </div>
            
            <div class="notice-footer">
                <div class="notice-date">
                    <span>📅 <?php echo date('M d, Y', strtotime($notice['start_date'])); ?></span>
                    <?php if ($notice['end_date']): ?>
                        <span>⏰ Valid until: <?php echo date('M d, Y', strtotime($notice['end_date'])); ?></span>
                    <?php endif; ?>
                </div>
                <span class="notice-audience">
                    👥 <?php echo ucfirst($notice['target_audience']); ?>
                </span>
            </div>
        </div>
    <?php endwhile; ?>
</div>
<?php
    endif;
}
?>