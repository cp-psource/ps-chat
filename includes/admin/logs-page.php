<?php
/**
 * Admin Logs Page
 * 
 * @package PSSource\Chat\Admin
 */

namespace PSSource\Chat\Admin;

use PSSource\Chat\Core\Database;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Logs Page Class
 */
class Logs_Page {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_psource_chat_clear_logs', [$this, 'clear_logs']);
        add_action('wp_ajax_psource_chat_export_logs', [$this, 'export_logs']);
    }
    
    /**
     * Render logs page
     */
    public function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'psource-chat'));
        }
        
        $current_tab = sanitize_text_field($_GET['tab'] ?? 'error-logs');
        
        ?>
        <div class="wrap psource-chat-admin">
            <h1><?php _e('Chat Logs', 'psource-chat'); ?></h1>
            
            <nav class="psource-chat-nav-tabs">
                <ul>
                    <li class="<?php echo $current_tab === 'error-logs' ? 'active' : ''; ?>">
                        <a href="#error-logs"><?php _e('Error Logs', 'psource-chat'); ?></a>
                    </li>
                    <li class="<?php echo $current_tab === 'activity-logs' ? 'active' : ''; ?>">
                        <a href="#activity-logs"><?php _e('Activity Logs', 'psource-chat'); ?></a>
                    </li>
                    <li class="<?php echo $current_tab === 'system-logs' ? 'active' : ''; ?>">
                        <a href="#system-logs"><?php _e('System Logs', 'psource-chat'); ?></a>
                    </li>
                </ul>
            </nav>
            
            <div id="error-logs" class="psource-chat-tab-content" style="<?php echo $current_tab === 'error-logs' ? '' : 'display:none;'; ?>">
                <?php $this->render_error_logs(); ?>
            </div>
            
            <div id="activity-logs" class="psource-chat-tab-content" style="<?php echo $current_tab === 'activity-logs' ? '' : 'display:none;'; ?>">
                <?php $this->render_activity_logs(); ?>
            </div>
            
            <div id="system-logs" class="psource-chat-tab-content" style="<?php echo $current_tab === 'system-logs' ? '' : 'display:none;'; ?>">
                <?php $this->render_system_logs(); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render error logs
     */
    private function render_error_logs() {
        $logs = $this->get_error_logs();
        
        ?>
        <div class="psource-chat-logs-header">
            <h3><?php _e('Error Logs', 'psource-chat'); ?></h3>
            <div class="psource-chat-actions">
                <button type="button" class="button" onclick="exportChatData('error-logs', 'csv')">
                    <?php _e('Export CSV', 'psource-chat'); ?>
                </button>
                <button type="button" class="button button-secondary" onclick="clearChatData('error-logs')">
                    <?php _e('Clear Logs', 'psource-chat'); ?>
                </button>
            </div>
        </div>
        
        <?php if (empty($logs)): ?>
            <div class="psource-chat-notice psource-chat-notice-info">
                <p><?php _e('No error logs found.', 'psource-chat'); ?></p>
            </div>
        <?php else: ?>
            <table class="psource-chat-sessions-table wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Time', 'psource-chat'); ?></th>
                        <th><?php _e('Level', 'psource-chat'); ?></th>
                        <th><?php _e('Message', 'psource-chat'); ?></th>
                        <th><?php _e('Context', 'psource-chat'); ?></th>
                        <th><?php _e('Actions', 'psource-chat'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr data-log-id="<?php echo esc_attr($log->id); ?>">
                            <td><?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->created_at))); ?></td>
                            <td>
                                <span class="psource-chat-log-level psource-chat-log-level-<?php echo esc_attr($log->level); ?>">
                                    <?php echo esc_html(ucfirst($log->level)); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($log->message); ?></td>
                            <td>
                                <?php if (!empty($log->context)): ?>
                                    <button type="button" class="button button-small" data-modal="log-context-modal-<?php echo esc_attr($log->id); ?>">
                                        <?php _e('View Context', 'psource-chat'); ?>
                                    </button>
                                <?php else: ?>
                                    <span class="description"><?php _e('No context', 'psource-chat'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="psource-chat-actions">
                                    <button type="button" class="psource-chat-action-btn psource-chat-action-delete" onclick="deleteLog(<?php echo esc_attr($log->id); ?>)">
                                        <?php _e('Delete', 'psource-chat'); ?>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php foreach ($logs as $log): ?>
                <?php if (!empty($log->context)): ?>
                    <div id="log-context-modal-<?php echo esc_attr($log->id); ?>" class="psource-chat-modal" style="display:none;">
                        <div class="psource-chat-modal-header">
                            <h3 class="psource-chat-modal-title"><?php _e('Log Context', 'psource-chat'); ?></h3>
                            <button type="button" class="psource-chat-modal-close">&times;</button>
                        </div>
                        <div class="psource-chat-modal-content">
                            <pre><?php echo esc_html(print_r(json_decode($log->context, true), true)); ?></pre>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Render activity logs
     */
    private function render_activity_logs() {
        $logs = $this->get_activity_logs();
        
        ?>
        <div class="psource-chat-logs-header">
            <h3><?php _e('Activity Logs', 'psource-chat'); ?></h3>
            <div class="psource-chat-actions">
                <button type="button" class="button" onclick="exportChatData('activity-logs', 'csv')">
                    <?php _e('Export CSV', 'psource-chat'); ?>
                </button>
                <button type="button" class="button button-secondary" onclick="clearChatData('activity-logs')">
                    <?php _e('Clear Logs', 'psource-chat'); ?>
                </button>
            </div>
        </div>
        
        <?php if (empty($logs)): ?>
            <div class="psource-chat-notice psource-chat-notice-info">
                <p><?php _e('No activity logs found.', 'psource-chat'); ?></p>
            </div>
        <?php else: ?>
            <table class="psource-chat-sessions-table wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Time', 'psource-chat'); ?></th>
                        <th><?php _e('User', 'psource-chat'); ?></th>
                        <th><?php _e('Action', 'psource-chat'); ?></th>
                        <th><?php _e('Details', 'psource-chat'); ?></th>
                        <th><?php _e('IP Address', 'psource-chat'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->created_at))); ?></td>
                            <td>
                                <?php if ($log->user_id): ?>
                                    <?php 
                                    $user = get_userdata($log->user_id);
                                    echo $user ? esc_html($user->display_name) : __('Unknown User', 'psource-chat');
                                    ?>
                                <?php else: ?>
                                    <?php _e('Guest User', 'psource-chat'); ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($log->action); ?></td>
                            <td><?php echo esc_html($log->details); ?></td>
                            <td><?php echo esc_html($log->ip_address); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Render system logs
     */
    private function render_system_logs() {
        $logs = $this->get_system_logs();
        
        ?>
        <div class="psource-chat-logs-header">
            <h3><?php _e('System Logs', 'psource-chat'); ?></h3>
            <div class="psource-chat-actions">
                <button type="button" class="button" onclick="exportChatData('system-logs', 'csv')">
                    <?php _e('Export CSV', 'psource-chat'); ?>
                </button>
                <button type="button" class="button button-secondary" onclick="clearChatData('system-logs')">
                    <?php _e('Clear Logs', 'psource-chat'); ?>
                </button>
            </div>
        </div>
        
        <?php if (empty($logs)): ?>
            <div class="psource-chat-notice psource-chat-notice-info">
                <p><?php _e('No system logs found.', 'psource-chat'); ?></p>
            </div>
        <?php else: ?>
            <table class="psource-chat-sessions-table wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Time', 'psource-chat'); ?></th>
                        <th><?php _e('Component', 'psource-chat'); ?></th>
                        <th><?php _e('Event', 'psource-chat'); ?></th>
                        <th><?php _e('Description', 'psource-chat'); ?></th>
                        <th><?php _e('Status', 'psource-chat'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->created_at))); ?></td>
                            <td><?php echo esc_html($log->component); ?></td>
                            <td><?php echo esc_html($log->event); ?></td>
                            <td><?php echo esc_html($log->description); ?></td>
                            <td>
                                <span class="psource-chat-status psource-chat-status-<?php echo esc_attr($log->status); ?>">
                                    <?php echo esc_html(ucfirst($log->status)); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <div class="psource-chat-modal-overlay"></div>
        <?php
    }
    
    /**
     * Get error logs
     */
    private function get_error_logs() {
        global $wpdb;
        
        $table_name = Database::get_table_name('logs');
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE log_type = %s ORDER BY created_at DESC LIMIT 100",
            'error'
        ));
    }
    
    /**
     * Get activity logs
     */
    private function get_activity_logs() {
        global $wpdb;
        
        $table_name = Database::get_table_name('logs');
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE log_type = %s ORDER BY created_at DESC LIMIT 100",
            'activity'
        ));
    }
    
    /**
     * Get system logs
     */
    private function get_system_logs() {
        global $wpdb;
        
        $table_name = Database::get_table_name('logs');
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE log_type = %s ORDER BY created_at DESC LIMIT 100",
            'system'
        ));
    }
    
    /**
     * Clear logs via AJAX
     */
    public function clear_logs() {
        check_ajax_referer('psource_chat_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'psource-chat'));
        }
        
        $type = sanitize_text_field($_POST['type'] ?? '');
        
        global $wpdb;
        $table_name = Database::get_table_name('logs');
        
        $log_type = str_replace('-logs', '', $type);
        
        $result = $wpdb->delete(
            $table_name,
            ['log_type' => $log_type],
            ['%s']
        );
        
        if ($result !== false) {
            wp_send_json_success(__('Logs cleared successfully.', 'psource-chat'));
        } else {
            wp_send_json_error(__('Failed to clear logs.', 'psource-chat'));
        }
    }
    
    /**
     * Export logs via AJAX
     */
    public function export_logs() {
        check_ajax_referer('psource_chat_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'psource-chat'));
        }
        
        $type = sanitize_text_field($_GET['type'] ?? '');
        $format = sanitize_text_field($_GET['format'] ?? 'csv');
        
        $log_type = str_replace('-logs', '', $type);
        
        global $wpdb;
        $table_name = Database::get_table_name('logs');
        
        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE log_type = %s ORDER BY created_at DESC",
            $log_type
        ), ARRAY_A);
        
        if ($format === 'csv') {
            $this->export_csv($logs, $type);
        } else {
            $this->export_json($logs, $type);
        }
    }
    
    /**
     * Export logs as CSV
     */
    private function export_csv($logs, $type) {
        $filename = 'psource-chat-' . $type . '-' . date('Y-m-d-H-i-s') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        if (!empty($logs)) {
            // Write header
            fputcsv($output, array_keys($logs[0]));
            
            // Write data
            foreach ($logs as $log) {
                fputcsv($output, $log);
            }
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Export logs as JSON
     */
    private function export_json($logs, $type) {
        $filename = 'psource-chat-' . $type . '-' . date('Y-m-d-H-i-s') . '.json';
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        echo json_encode($logs, JSON_PRETTY_PRINT);
        exit;
    }
}
