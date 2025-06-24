<?php
/**
 * Admin Dashboard
 * 
 * @package PSSource\Chat\Admin
 */

namespace PSSource\Chat\Admin;

use PSSource\Chat\Core\Database;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles the main dashboard page
 */
class Dashboard {
    
    /**
     * Render dashboard
     */
    public function render() {
        $stats = $this->get_chat_statistics();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="psource-chat-dashboard">
                <!-- Statistics Cards -->
                <div class="psource-chat-stats-grid">
                    <div class="psource-chat-stat-card">
                        <div class="stat-icon">
                            <span class="dashicons dashicons-format-chat"></span>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo number_format($stats['active_sessions']); ?></h3>
                            <p><?php _e('Active Sessions', 'psource-chat'); ?></p>
                        </div>
                    </div>
                    
                    <div class="psource-chat-stat-card">
                        <div class="stat-icon">
                            <span class="dashicons dashicons-groups"></span>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo number_format($stats['online_users']); ?></h3>
                            <p><?php _e('Online Users', 'psource-chat'); ?></p>
                        </div>
                    </div>
                    
                    <div class="psource-chat-stat-card">
                        <div class="stat-icon">
                            <span class="dashicons dashicons-email-alt"></span>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo number_format($stats['messages_today']); ?></h3>
                            <p><?php _e('Messages Today', 'psource-chat'); ?></p>
                        </div>
                    </div>
                    
                    <div class="psource-chat-stat-card">
                        <div class="stat-icon">
                            <span class="dashicons dashicons-chart-line"></span>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo number_format($stats['total_messages']); ?></h3>
                            <p><?php _e('Total Messages', 'psource-chat'); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Main Content Grid -->
                <div class="psource-chat-main-grid">
                    <!-- Recent Activity -->
                    <div class="psource-chat-panel">
                        <div class="panel-header">
                            <h3><?php _e('Recent Activity', 'psource-chat'); ?></h3>
                            <a href="<?php echo admin_url('admin.php?page=psource-chat-logs'); ?>" class="button button-secondary">
                                <?php _e('View All Logs', 'psource-chat'); ?>
                            </a>
                        </div>
                        <div class="panel-content">
                            <?php $this->render_recent_activity(); ?>
                        </div>
                    </div>
                    
                    <!-- Active Sessions -->
                    <div class="psource-chat-panel">
                        <div class="panel-header">
                            <h3><?php _e('Active Sessions', 'psource-chat'); ?></h3>
                            <a href="<?php echo admin_url('admin.php?page=psource-chat-sessions'); ?>" class="button button-secondary">
                                <?php _e('Manage Sessions', 'psource-chat'); ?>
                            </a>
                        </div>
                        <div class="panel-content">
                            <?php $this->render_active_sessions(); ?>
                        </div>
                    </div>
                </div>
                
                <!-- Bottom Grid -->
                <div class="psource-chat-bottom-grid">
                    <!-- Quick Settings -->
                    <div class="psource-chat-panel">
                        <div class="panel-header">
                            <h3><?php _e('Quick Settings', 'psource-chat'); ?></h3>
                        </div>
                        <div class="panel-content">
                            <?php $this->render_quick_settings(); ?>
                        </div>
                    </div>
                    
                    <!-- System Status -->
                    <div class="psource-chat-panel">
                        <div class="panel-header">
                            <h3><?php _e('System Status', 'psource-chat'); ?></h3>
                        </div>
                        <div class="panel-content">
                            <?php $this->render_system_status(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php $this->render_dashboard_styles(); ?>
        <?php
    }
    
    /**
     * Get chat statistics
     */
    private function get_chat_statistics() {
        global $wpdb;
        
        $sessions_table = Database::get_table_name('sessions');
        $messages_table = Database::get_table_name('messages');
        $user_sessions_table = Database::get_table_name('user_sessions');
        
        // Active sessions (last 5 minutes)
        $active_sessions = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $sessions_table 
                 WHERE session_status = 'active' 
                 AND updated_on > %s",
                date('Y-m-d H:i:s', strtotime('-5 minutes'))
            )
        );
        
        // Online users (last 5 minutes)
        $online_users = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT user_id) FROM $user_sessions_table 
                 WHERE session_status = 'active' 
                 AND last_seen > %s",
                date('Y-m-d H:i:s', strtotime('-5 minutes'))
            )
        );
        
        // Messages today
        $messages_today = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $messages_table 
                 WHERE DATE(message_time) = %s 
                 AND is_deleted = 0",
                date('Y-m-d')
            )
        );
        
        // Total messages
        $total_messages = $wpdb->get_var(
            "SELECT COUNT(*) FROM $messages_table WHERE is_deleted = 0"
        );
        
        return [
            'active_sessions' => $active_sessions ?: 0,
            'online_users' => $online_users ?: 0,
            'messages_today' => $messages_today ?: 0,
            'total_messages' => $total_messages ?: 0
        ];
    }
    
    /**
     * Render recent activity
     */
    private function render_recent_activity() {
        global $wpdb;
        
        $messages_table = Database::get_table_name('messages');
        
        $recent_messages = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $messages_table 
                 WHERE is_deleted = 0 
                 ORDER BY message_time DESC 
                 LIMIT %d",
                10
            )
        );
        
        if (empty($recent_messages)) {
            echo '<p>' . __('No recent activity.', 'psource-chat') . '</p>';
            return;
        }
        
        echo '<div class="psource-chat-activity-list">';
        foreach ($recent_messages as $message) {
            $time_ago = human_time_diff(strtotime($message->message_time));
            echo '<div class="activity-item">';
            echo '<div class="activity-avatar">' . get_avatar($message->user_id, 32) . '</div>';
            echo '<div class="activity-content">';
            echo '<strong>' . esc_html($message->user_name) . '</strong>';
            echo '<span class="activity-text">' . wp_trim_words(esc_html($message->message_text), 10) . '</span>';
            echo '<span class="activity-time">' . sprintf(__('%s ago', 'psource-chat'), $time_ago) . '</span>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
    }
    
    /**
     * Render active sessions
     */
    private function render_active_sessions() {
        global $wpdb;
        
        $sessions_table = Database::get_table_name('sessions');
        $user_sessions_table = Database::get_table_name('user_sessions');
        
        $active_sessions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT s.*, COUNT(us.user_id) as user_count 
                 FROM $sessions_table s 
                 LEFT JOIN $user_sessions_table us ON s.session_id = us.session_id 
                 WHERE s.session_status = 'active' 
                 AND s.updated_on > %s 
                 GROUP BY s.id 
                 ORDER BY s.updated_on DESC 
                 LIMIT 5",
                date('Y-m-d H:i:s', strtotime('-30 minutes'))
            )
        );
        
        if (empty($active_sessions)) {
            echo '<p>' . __('No active sessions.', 'psource-chat') . '</p>';
            return;
        }
        
        echo '<div class="psource-chat-sessions-list">';
        foreach ($active_sessions as $session) {
            $time_ago = human_time_diff(strtotime($session->updated_on));
            echo '<div class="session-item">';
            echo '<div class="session-info">';
            echo '<strong>' . esc_html($session->session_type) . '</strong>';
            echo '<span class="session-host">' . esc_html($session->session_host) . '</span>';
            echo '</div>';
            echo '<div class="session-stats">';
            echo '<span class="user-count">' . sprintf(_n('%d user', '%d users', $session->user_count, 'psource-chat'), $session->user_count) . '</span>';
            echo '<span class="session-time">' . sprintf(__('Updated %s ago', 'psource-chat'), $time_ago) . '</span>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
    }
    
    /**
     * Render quick settings
     */
    private function render_quick_settings() {
        $plugin = \PSSource\Chat\Core\Plugin::get_instance();
        $options = $plugin->get_options();
        
        echo '<div class="psource-chat-quick-settings">';
        
        // Sound toggle
        $sound_status = $options['enable_sound'] ? __('Enabled', 'psource-chat') : __('Disabled', 'psource-chat');
        echo '<div class="setting-item">';
        echo '<span class="setting-label">' . __('Sound Notifications:', 'psource-chat') . '</span>';
        echo '<span class="setting-value ' . ($options['enable_sound'] ? 'enabled' : 'disabled') . '">' . $sound_status . '</span>';
        echo '</div>';
        
        // Guest chat toggle
        $guest_status = $options['allow_guest_chat'] ? __('Allowed', 'psource-chat') : __('Not Allowed', 'psource-chat');
        echo '<div class="setting-item">';
        echo '<span class="setting-label">' . __('Guest Chat:', 'psource-chat') . '</span>';
        echo '<span class="setting-value ' . ($options['allow_guest_chat'] ? 'enabled' : 'disabled') . '">' . $guest_status . '</span>';
        echo '</div>';
        
        // Moderation status
        $mod_status = $options['moderate_messages'] ? __('Active', 'psource-chat') : __('Inactive', 'psource-chat');
        echo '<div class="setting-item">';
        echo '<span class="setting-label">' . __('Message Moderation:', 'psource-chat') . '</span>';
        echo '<span class="setting-value ' . ($options['moderate_messages'] ? 'enabled' : 'disabled') . '">' . $mod_status . '</span>';
        echo '</div>';
        
        echo '<div class="setting-actions">';
        echo '<a href="' . admin_url('admin.php?page=psource-chat-settings') . '" class="button button-primary">' . __('Configure Settings', 'psource-chat') . '</a>';
        echo '</div>';
        
        echo '</div>';
    }
    
    /**
     * Render system status
     */
    private function render_system_status() {
        global $wpdb;
        
        echo '<div class="psource-chat-system-status">';
        
        // Database status
        $tables_exist = true;
        $required_tables = ['sessions', 'messages', 'user_sessions'];
        foreach ($required_tables as $table) {
            $table_name = Database::get_table_name($table);
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
                $tables_exist = false;
                break;
            }
        }
        
        echo '<div class="status-item">';
        echo '<span class="status-label">' . __('Database Tables:', 'psource-chat') . '</span>';
        echo '<span class="status-indicator ' . ($tables_exist ? 'good' : 'error') . '">';
        echo $tables_exist ? __('OK', 'psource-chat') : __('Missing', 'psource-chat');
        echo '</span>';
        echo '</div>';
        
        // WordPress version
        $wp_version_ok = version_compare(get_bloginfo('version'), '5.0', '>=');
        echo '<div class="status-item">';
        echo '<span class="status-label">' . __('WordPress Version:', 'psource-chat') . '</span>';
        echo '<span class="status-indicator ' . ($wp_version_ok ? 'good' : 'warning') . '">';
        echo get_bloginfo('version');
        echo '</span>';
        echo '</div>';
        
        // PHP version
        $php_version_ok = version_compare(PHP_VERSION, '7.4', '>=');
        echo '<div class="status-item">';
        echo '<span class="status-label">' . __('PHP Version:', 'psource-chat') . '</span>';
        echo '<span class="status-indicator ' . ($php_version_ok ? 'good' : 'warning') . '">';
        echo PHP_VERSION;
        echo '</span>';
        echo '</div>';
        
        // Last cleanup
        $last_cleanup = get_option('psource_chat_last_cleanup', false);
        echo '<div class="status-item">';
        echo '<span class="status-label">' . __('Last Cleanup:', 'psource-chat') . '</span>';
        echo '<span class="status-value">';
        echo $last_cleanup ? human_time_diff($last_cleanup) . ' ' . __('ago', 'psource-chat') : __('Never', 'psource-chat');
        echo '</span>';
        echo '</div>';
        
        echo '</div>';
    }
    
    /**
     * Render dashboard styles
     */
    private function render_dashboard_styles() {
        ?>
        <style>
        .psource-chat-dashboard {
            margin-top: 20px;
        }
        
        .psource-chat-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .psource-chat-stat-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .stat-icon {
            background: #2271b1;
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .stat-content h3 {
            margin: 0 0 5px 0;
            font-size: 24px;
            font-weight: 600;
        }
        
        .stat-content p {
            margin: 0;
            color: #646970;
            font-size: 14px;
        }
        
        .psource-chat-main-grid,
        .psource-chat-bottom-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .psource-chat-panel {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
        }
        
        .panel-header {
            padding: 15px 20px;
            border-bottom: 1px solid #ccd0d4;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .panel-header h3 {
            margin: 0;
            font-size: 16px;
        }
        
        .panel-content {
            padding: 20px;
        }
        
        .psource-chat-activity-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .activity-item {
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }
        
        .activity-avatar img {
            border-radius: 50%;
        }
        
        .activity-content {
            flex: 1;
            min-width: 0;
        }
        
        .activity-content strong {
            display: block;
            margin-bottom: 2px;
        }
        
        .activity-text {
            display: block;
            color: #646970;
            font-size: 14px;
            margin-bottom: 2px;
        }
        
        .activity-time {
            font-size: 12px;
            color: #8c8f94;
        }
        
        .psource-chat-sessions-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .session-item {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #f9f9f9;
        }
        
        .session-info strong {
            display: block;
            margin-bottom: 2px;
            text-transform: capitalize;
        }
        
        .session-host {
            font-size: 14px;
            color: #646970;
        }
        
        .session-stats {
            margin-top: 8px;
            font-size: 12px;
            color: #8c8f94;
        }
        
        .user-count {
            margin-right: 15px;
        }
        
        .psource-chat-quick-settings {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .setting-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f1;
        }
        
        .setting-item:last-child {
            border-bottom: none;
        }
        
        .setting-value.enabled {
            color: #00a32a;
            font-weight: 600;
        }
        
        .setting-value.disabled {
            color: #d63638;
            font-weight: 600;
        }
        
        .setting-actions {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #f0f0f1;
        }
        
        .psource-chat-system-status {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .status-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
        }
        
        .status-indicator.good {
            color: #00a32a;
            font-weight: 600;
        }
        
        .status-indicator.warning {
            color: #dba617;
            font-weight: 600;
        }
        
        .status-indicator.error {
            color: #d63638;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .psource-chat-main-grid,
            .psource-chat-bottom-grid {
                grid-template-columns: 1fr;
            }
            
            .psource-chat-stats-grid {
                grid-template-columns: 1fr;
            }
        }
        </style>
        <?php
    }
}
