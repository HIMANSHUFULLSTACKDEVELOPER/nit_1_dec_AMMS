<?php
/**
 * Notices Component - Display notices for all user roles
 * Place this file in: /admin/notices_component.php
 */

function displayNotices($userRole) {
    global $conn;
    
    $today = date('Y-m-d');
    
    // Build the query based on user role
    $query = "SELECT * FROM notices 
              WHERE is_active = 1 
              AND start_date <= '$today'
              AND (end_date IS NULL OR end_date >= '$today')
              AND (target_audience = 'all' OR target_audience = '$userRole')
              ORDER BY 
                FIELD(priority, 'high', 'medium', 'low'),
                created_at DESC";
    
    $notices = $conn->query($query);
    
    if (!$notices || $notices->num_rows === 0) {
        return; // Don't display anything if no notices
    }
    
    echo '<div class="notices-container">';
    echo '<h2 class="notices-title">📢 Important Notices</h2>';
    echo '<div class="notices-wrapper">';
    
    while ($notice = $notices->fetch_assoc()) {
        $noticeClass = htmlspecialchars($notice['notice_type']);
        $priority = htmlspecialchars($notice['priority']);
        $title = htmlspecialchars($notice['title']);
        $message = nl2br(htmlspecialchars($notice['message']));
        $createdAt = date('M d, Y', strtotime($notice['created_at']));
        
        // Determine icon based on notice type
        $icon = '📌';
        switch ($notice['notice_type']) {
            case 'success': $icon = '✅'; break;
            case 'warning': $icon = '⚠️'; break;
            case 'danger': $icon = '🚨'; break;
            case 'info': $icon = 'ℹ️'; break;
        }
        
        echo '<div class="notice-item notice-' . $noticeClass . ' notice-priority-' . $priority . '">';
        echo '  <div class="notice-icon">' . $icon . '</div>';
        echo '  <div class="notice-content">';
        echo '    <div class="notice-header-row">';
        echo '      <h3 class="notice-item-title">' . $title . '</h3>';
        echo '      <span class="notice-badge notice-badge-' . $priority . '">' . strtoupper($priority) . '</span>';
        echo '    </div>';
        echo '    <p class="notice-message">' . $message . '</p>';
        echo '    <div class="notice-footer">';
        echo '      <span class="notice-date">📅 ' . $createdAt . '</span>';
        if ($notice['end_date']) {
            echo '      <span class="notice-expiry">⏰ Valid until: ' . date('M d, Y', strtotime($notice['end_date'])) . '</span>';
        }
        echo '    </div>';
        echo '  </div>';
        echo '</div>';
    }
    
    echo '</div>';
    echo '</div>';
}

// Auto-include CSS if not already included
if (!defined('NOTICES_CSS_INCLUDED')) {
    define('NOTICES_CSS_INCLUDED', true);
    ?>
    <style>
        .notices-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            padding: 40px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
            margin: 30px 0;
            border: 2px solid rgba(255, 255, 255, 0.5);
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .notices-title {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .notices-wrapper {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .notice-item {
            display: flex;
            gap: 20px;
            padding: 25px;
            border-radius: 15px;
            background: white;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            border-left: 5px solid;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .notice-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.03), rgba(118, 75, 162, 0.03));
            z-index: 0;
        }
        
        .notice-item > * {
            position: relative;
            z-index: 1;
        }
        
        .notice-item:hover {
            transform: translateX(10px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }
        
        /* Notice Type Colors */
        .notice-info {
            border-left-color: #17a2b8;
        }
        
        .notice-success {
            border-left-color: #28a745;
        }
        
        .notice-warning {
            border-left-color: #ffc107;
        }
        
        .notice-danger {
            border-left-color: #dc3545;
        }
        
        /* Priority Glow Effect */
        .notice-priority-high {
            animation: priorityPulse 2s ease-in-out infinite;
        }
        
        @keyframes priorityPulse {
            0%, 100% {
                box-shadow: 0 5px 20px rgba(220, 53, 69, 0.3);
            }
            50% {
                box-shadow: 0 5px 30px rgba(220, 53, 69, 0.5);
            }
        }
        
        .notice-icon {
            font-size: 36px;
            min-width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border-radius: 12px;
        }
        
        .notice-content {
            flex: 1;
        }
        
        .notice-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .notice-item-title {
            font-size: 20px;
            font-weight: 700;
            color: #2c3e50;
            margin: 0;
        }
        
        .notice-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }
        
        .notice-badge-high {
            background: linear-gradient(135deg, #ff6b6b, #ee5a5a);
            color: white;
            box-shadow: 0 3px 10px rgba(255, 107, 107, 0.3);
        }
        
        .notice-badge-medium {
            background: linear-gradient(135deg, #ffc107, #ff9800);
            color: #000;
            box-shadow: 0 3px 10px rgba(255, 193, 7, 0.3);
        }
        
        .notice-badge-low {
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
            box-shadow: 0 3px 10px rgba(23, 162, 184, 0.3);
        }
        
        .notice-message {
            color: #555;
            line-height: 1.8;
            margin-bottom: 15px;
            font-size: 15px;
        }
        
        .notice-footer {
            display: flex;
            gap: 20px;
            font-size: 13px;
            color: #999;
            padding-top: 12px;
            border-top: 1px solid #f0f0f0;
            flex-wrap: wrap;
        }
        
        .notice-date,
        .notice-expiry {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .notices-container {
                padding: 25px 20px;
                margin: 20px 0;
            }
            
            .notices-title {
                font-size: 22px;
            }
            
            .notice-item {
                flex-direction: column;
                padding: 20px;
                gap: 15px;
            }
            
            .notice-icon {
                font-size: 28px;
                min-width: 40px;
                height: 40px;
            }
            
            .notice-item-title {
                font-size: 18px;
            }
            
            .notice-header-row {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .notice-footer {
                flex-direction: column;
                gap: 8px;
            }
        }
        
        @media (max-width: 480px) {
            .notices-container {
                padding: 20px 15px;
                border-radius: 15px;
            }
            
            .notices-title {
                font-size: 20px;
            }
            
            .notice-item {
                padding: 15px;
            }
            
            .notice-item-title {
                font-size: 16px;
            }
            
            .notice-message {
                font-size: 14px;
            }
        }
    </style>
    <?php
}
?>