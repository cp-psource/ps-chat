<?php
/**
 * Sessions Management Page
 * 
 * @package PSSource\Chat\Admin
 */

namespace PSSource\Chat\Admin;

use PSSource\Chat\Core\Database;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles the sessions management page
 */
class Sessions_Page {
    
    /**
     * Render sessions page
     */
    public function render() {
        // Handle actions
        $this->handle_actions();
        
        $sessions = $this->get_active_sessions();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="psource-chat-sessions-page">
                <div class="psource-chat-stats-bar">
                    <div class="stat-item">
                        <strong><?php echo count($sessions); ?></strong>
                        <span><?php _e('Active Sessions', 'psource-chat'); ?></span>
                    </div>
                    <div class="stat-item">
                        <strong><?php echo $this->get_total_users(); ?></strong>
                        <span><?php _e('Online Users', 'psource-chat'); ?></span>
                    </div>
                    <div class="actions">
                        <button class="button button-secondary" id="refresh-sessions">
                            <?php _e('Refresh', 'psource-chat'); ?>
                        </button>
                        <button class="button button-secondary" id="cleanup-sessions">
                            <?php _e('Cleanup Inactive', 'psource-chat'); ?>
                        </button>
                    </div>
                </div>
                
                <?php if (empty($sessions)): ?>
                    <div class="psource-chat-no-sessions">
                        <h3><?php _e('No Active Sessions', 'psource-chat'); ?></h3>
                        <p><?php _e('There are currently no active chat sessions.', 'psource-chat'); ?></p>
                    </div>
                <?php else: ?>
                    <div class="psource-chat-sessions-grid">
                        <?php foreach ($sessions as $session): ?>
                            <div class="session-card" data-session-id="<?php echo esc_attr($session->session_id); ?>">
                                <div class="session-header">
                                    <h3><?php echo esc_html($session->session_type); ?></h3>
                                    <div class="session-actions">
                                        <button class="button button-small view-session" 
                                                data-session-id="<?php echo esc_attr($session->session_id); ?>">
                                            <?php _e('View', 'psource-chat'); ?>
                                        </button>
                                        <button class="button button-small button-link-delete close-session" 
                                                data-session-id="<?php echo esc_attr($session->session_id); ?>">
                                            <?php _e('Close', 'psource-chat'); ?>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="session-info">
                                    <div class="info-item">
                                        <strong><?php _e('Host:', 'psource-chat'); ?></strong>
                                        <?php echo esc_html($session->session_host); ?>
                                    </div>
                                    <div class="info-item">
                                        <strong><?php _e('Created:', 'psource-chat'); ?></strong>
                                        <?php echo human_time_diff(strtotime($session->created_on)) . ' ' . __('ago', 'psource-chat'); ?>
                                    </div>
                                    <div class="info-item">
                                        <strong><?php _e('Last Activity:', 'psource-chat'); ?></strong>
                                        <?php echo human_time_diff(strtotime($session->updated_on)) . ' ' . __('ago', 'psource-chat'); ?>
                                    </div>
                                </div>
                                
                                <div class="session-users">
                                    <h4><?php _e('Active Users', 'psource-chat'); ?> (<?php echo $session->user_count; ?>)</h4>
                                    <div class="users-list">
                                        <?php $this->render_session_users($session->session_id); ?>
                                    </div>
                                </div>
                                
                                <div class="session-stats">
                                    <div class="stat">
                                        <span class="label"><?php _e('Messages:', 'psource-chat'); ?></span>
                                        <span class="value"><?php echo $this->get_session_message_count($session->session_id); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php $this->render_session_modal(); ?>
        <?php $this->render_styles_and_scripts(); ?>
        <?php
    }
    
    /**
     * Handle page actions
     */
    private function handle_actions() {
        if (!isset($_POST['action']) || !wp_verify_nonce($_POST['_wpnonce'], 'psource_chat_sessions')) {
            return;
        }
        
        $action = sanitize_text_field($_POST['action']);
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        
        switch ($action) {
            case 'close_session':
                $this->close_session($session_id);
                break;
            case 'cleanup_sessions':
                $this->cleanup_inactive_sessions();
                break;
        }
    }
    
    /**
     * Get active sessions
     */
    private function get_active_sessions() {
        global $wpdb;
        
        $sessions_table = Database::get_table_name('sessions');
        $user_sessions_table = Database::get_table_name('user_sessions');
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT s.*, COUNT(us.user_id) as user_count 
                 FROM $sessions_table s 
                 LEFT JOIN $user_sessions_table us ON s.session_id = us.session_id 
                     AND us.session_status = 'active'
                     AND us.last_seen > %s
                 WHERE s.session_status = 'active' 
                 GROUP BY s.id 
                 ORDER BY s.updated_on DESC",
                date('Y-m-d H:i:s', strtotime('-30 minutes'))
            )
        );
    }
    
    /**
     * Get total online users
     */
    private function get_total_users() {
        global $wpdb;
        
        $user_sessions_table = Database::get_table_name('user_sessions');
        
        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT user_id) 
                 FROM $user_sessions_table 
                 WHERE session_status = 'active' 
                 AND last_seen > %s",
                date('Y-m-d H:i:s', strtotime('-5 minutes'))
            )
        );
    }
    
    /**
     * Render session users
     */
    private function render_session_users($session_id) {
        $users = Database::get_active_users($session_id);
        
        if (empty($users)) {
            echo '<p class="no-users">' . __('No active users', 'psource-chat') . '</p>';
            return;
        }
        
        foreach ($users as $user) {
            $time_ago = human_time_diff(strtotime($user->last_seen));
            echo '<div class="user-item">';
            echo get_avatar($user->user_id, 24);
            echo '<span class="user-name">' . esc_html($user->user_name) . '</span>';
            echo '<span class="user-time">' . sprintf(__('Active %s ago', 'psource-chat'), $time_ago) . '</span>';
            echo '</div>';
        }
    }
    
    /**
     * Get session message count
     */
    private function get_session_message_count($session_id) {
        global $wpdb;
        
        $messages_table = Database::get_table_name('messages');
        
        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $messages_table 
                 WHERE session_id = %s AND is_deleted = 0",
                $session_id
            )
        );
    }
    
    /**
     * Close session
     */
    private function close_session($session_id) {
        global $wpdb;
        
        $sessions_table = Database::get_table_name('sessions');
        $user_sessions_table = Database::get_table_name('user_sessions');
        
        // Update session status
        $wpdb->update(
            $sessions_table,
            ['session_status' => 'closed'],
            ['session_id' => $session_id],
            ['%s'],
            ['%s']
        );
        
        // Update user sessions
        $wpdb->update(
            $user_sessions_table,
            ['session_status' => 'inactive'],
            ['session_id' => $session_id],
            ['%s'],
            ['%s']
        );
        
        add_settings_error('psource_chat_sessions', 'session_closed', __('Session closed successfully.', 'psource-chat'), 'updated');
    }
    
    /**
     * Cleanup inactive sessions
     */
    private function cleanup_inactive_sessions() {
        global $wpdb;
        
        $sessions_table = Database::get_table_name('sessions');
        $user_sessions_table = Database::get_table_name('user_sessions');
        
        $cutoff_time = date('Y-m-d H:i:s', strtotime('-1 hour'));
        
        // Close old sessions
        $updated = $wpdb->query(
            $wpdb->prepare(
                "UPDATE $sessions_table 
                 SET session_status = 'inactive' 
                 WHERE session_status = 'active' 
                 AND updated_on < %s",
                $cutoff_time
            )
        );
        
        // Cleanup user sessions
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE $user_sessions_table 
                 SET session_status = 'inactive' 
                 WHERE session_status = 'active' 
                 AND last_seen < %s",
                $cutoff_time
            )
        );
        
        add_settings_error('psource_chat_sessions', 'cleanup_done', 
            sprintf(__('Cleaned up %d inactive sessions.', 'psource-chat'), $updated), 'updated');
    }
    
    /**
     * Render session modal
     */
    private function render_session_modal() {
        ?>
        <div id="session-modal" class="psource-chat-modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3><?php _e('Session Details', 'psource-chat'); ?></h3>
                    <button class="modal-close">&times;</button>
                </div>
                <div class="modal-body">
                    <div id="session-details-content">
                        <?php _e('Loading...', 'psource-chat'); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render styles and scripts
     */
    private function render_styles_and_scripts() {
        ?>
        <style>
        .psource-chat-sessions-page {
            margin-top: 20px;
        }
        
        .psource-chat-stats-bar {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 20px;
        }
        
        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .stat-item strong {
            font-size: 24px;
            color: #2271b1;
            display: block;
        }
        
        .stat-item span {
            font-size: 14px;
            color: #646970;
        }
        
        .actions {
            margin-left: auto;
            display: flex;
            gap: 10px;
        }
        
        .psource-chat-no-sessions {
            text-align: center;
            padding: 60px 20px;
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
        }
        
        .psource-chat-sessions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .session-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
        }
        
        .session-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f1;
        }
        
        .session-header h3 {
            margin: 0;
            text-transform: capitalize;
        }
        
        .session-actions {
            display: flex;
            gap: 5px;
        }
        
        .session-info {
            margin-bottom: 15px;
        }
        
        .info-item {
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .info-item strong {
            display: inline-block;
            width: 100px;
        }
        
        .session-users h4 {
            margin: 0 0 10px 0;
            font-size: 14px;
        }
        
        .users-list {
            max-height: 150px;
            overflow-y: auto;
        }
        
        .user-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 5px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        
        .user-item:last-child {
            border-bottom: none;
        }
        
        .user-item img {
            border-radius: 50%;
        }
        
        .user-name {
            font-weight: 500;
            flex: 1;
        }
        
        .user-time {
            font-size: 12px;
            color: #646970;
        }
        
        .no-users {
            color: #646970;
            font-style: italic;
            margin: 0;
        }
        
        .session-stats {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #f0f0f1;
        }
        
        .stat {
            display: flex;
            justify-content: space-between;
        }
        
        .psource-chat-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999999;
        }
        
        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            border-radius: 4px;
            min-width: 500px;
            max-width: 80%;
            max-height: 80%;
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #ccd0d4;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            margin: 0;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #646970;
        }
        
        .modal-body {
            padding: 20px;
            max-height: 400px;
            overflow-y: auto;
        }
        
        @media (max-width: 768px) {
            .psource-chat-sessions-grid {
                grid-template-columns: 1fr;
            }
            
            .psource-chat-stats-bar {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .actions {
                margin-left: 0;
            }
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Refresh sessions
            $('#refresh-sessions').on('click', function() {
                location.reload();
            });
            
            // Cleanup sessions
            $('#cleanup-sessions').on('click', function() {
                if (confirm('<?php esc_js(_e('Are you sure you want to cleanup inactive sessions?', 'psource-chat')); ?>')) {
                    var form = $('<form method="post">')
                        .append('<input type="hidden" name="action" value="cleanup_sessions">')
                        .append('<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('psource_chat_sessions'); ?>">');
                    $('body').append(form);
                    form.submit();
                }
            });
            
            // Close session
            $('.close-session').on('click', function() {
                var sessionId = $(this).data('session-id');
                if (confirm('<?php esc_js(_e('Are you sure you want to close this session?', 'psource-chat')); ?>')) {
                    var form = $('<form method="post">')
                        .append('<input type="hidden" name="action" value="close_session">')
                        .append('<input type="hidden" name="session_id" value="' + sessionId + '">')
                        .append('<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('psource_chat_sessions'); ?>">');
                    $('body').append(form);
                    form.submit();
                }
            });
            
            // View session
            $('.view-session').on('click', function() {
                var sessionId = $(this).data('session-id');
                // TODO: Load session details via AJAX
                $('#session-modal').show();
            });
            
            // Close modal
            $('.modal-close, .psource-chat-modal').on('click', function(e) {
                if (e.target === this) {
                    $('#session-modal').hide();
                }
            });
        });
        </script>
        <?php
    }
}
