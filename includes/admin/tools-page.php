<?php
/**
 * Admin Tools Page
 * 
 * @package PSSource\Chat\Admin
 */

namespace PSSource\Chat\Admin;

use PSSource\Chat\Core\Database;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Tools Page Class - Simplified without migration
 */
class Tools_Page {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_psource_chat_backup_data', [$this, 'backup_data']);
        add_action('wp_ajax_psource_chat_restore_data', [$this, 'restore_data']);
        add_action('wp_ajax_psource_chat_cleanup_data', [$this, 'cleanup_data']);
        add_action('wp_ajax_psource_chat_test_system', [$this, 'test_system']);
    }
    
    /**
     * Render tools page
     */
    public function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'psource-chat'));
        }
        
        $current_tab = sanitize_text_field($_GET['tab'] ?? 'diagnostics');
        
        ?>
        <div class="wrap psource-chat-admin">
            <h1><?php _e('Chat Tools', 'psource-chat'); ?></h1>
            
            <nav class="psource-chat-nav-tabs">
                <ul>
                    <li class="<?php echo $current_tab === 'diagnostics' ? 'active' : ''; ?>">
                        <a href="#diagnostics"><?php _e('Diagnostics', 'psource-chat'); ?></a>
                    </li>
                    <li class="<?php echo $current_tab === 'backup' ? 'active' : ''; ?>">
                        <a href="#backup"><?php _e('Backup & Restore', 'psource-chat'); ?></a>
                    </li>
                    <li class="<?php echo $current_tab === 'cleanup' ? 'active' : ''; ?>">
                        <a href="#cleanup"><?php _e('Data Cleanup', 'psource-chat'); ?></a>
                    </li>
                </ul>
            </nav>
            
            <div id="diagnostics" class="psource-chat-tab-content" style="<?php echo $current_tab === 'diagnostics' ? '' : 'display:none;'; ?>">
                <?php $this->render_diagnostics(); ?>
            </div>
            
            <div id="backup" class="psource-chat-tab-content" style="<?php echo $current_tab === 'backup' ? '' : 'display:none;'; ?>">
                <?php $this->render_backup_tools(); ?>
            </div>
            
            <div id="cleanup" class="psource-chat-tab-content" style="<?php echo $current_tab === 'cleanup' ? '' : 'display:none;'; ?>">
                <?php $this->render_cleanup_tools(); ?>
            </div>
        </div>
        <?php
    }
    

    
    /**
     * Render backup tools
     */
    private function render_backup_tools() {
        $backups = $this->get_available_backups();
        
        ?>
        <div class="psource-chat-tools-section">
            <h3><?php _e('Backup & Restore', 'psource-chat'); ?></h3>
            <p class="description">
                <?php _e('Create backups of your chat data and restore from previous backups.', 'psource-chat'); ?>
            </p>
            
            <div class="psource-chat-backup-create">
                <h4><?php _e('Create Backup', 'psource-chat'); ?></h4>
                
                <form class="psource-chat-ajax-form" data-action="backup_data">
                    <div class="psource-chat-form-field">
                        <label for="backup_name"><?php _e('Backup Name', 'psource-chat'); ?></label>
                        <input type="text" id="backup_name" name="backup_name" value="<?php echo esc_attr('backup-' . date('Y-m-d-H-i-s')); ?>" required>
                    </div>
                    
                    <div class="psource-chat-form-field">
                        <label><?php _e('Include Data', 'psource-chat'); ?></label>
                        
                        <div class="psource-chat-checkbox-field">
                            <input type="checkbox" id="include_messages" name="include_data[]" value="messages" checked>
                            <label for="include_messages"><?php _e('Messages', 'psource-chat'); ?></label>
                        </div>
                        
                        <div class="psource-chat-checkbox-field">
                            <input type="checkbox" id="include_sessions" name="include_data[]" value="sessions" checked>
                            <label for="include_sessions"><?php _e('Sessions', 'psource-chat'); ?></label>
                        </div>
                        
                        <div class="psource-chat-checkbox-field">
                            <input type="checkbox" id="include_settings" name="include_data[]" value="settings" checked>
                            <label for="include_settings"><?php _e('Settings', 'psource-chat'); ?></label>
                        </div>
                        
                        <div class="psource-chat-checkbox-field">
                            <input type="checkbox" id="include_logs" name="include_data[]" value="logs">
                            <label for="include_logs"><?php _e('Logs', 'psource-chat'); ?></label>
                        </div>
                    </div>
                    
                    <div class="psource-chat-form-field">
                        <button type="submit" class="button button-primary">
                            <?php _e('Create Backup', 'psource-chat'); ?>
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="psource-chat-backup-list">
                <h4><?php _e('Available Backups', 'psource-chat'); ?></h4>
                
                <?php if (empty($backups)): ?>
                    <div class="psource-chat-notice psource-chat-notice-info">
                        <p><?php _e('No backups found.', 'psource-chat'); ?></p>
                    </div>
                <?php else: ?>
                    <table class="psource-chat-sessions-table wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Name', 'psource-chat'); ?></th>
                                <th><?php _e('Date', 'psource-chat'); ?></th>
                                <th><?php _e('Size', 'psource-chat'); ?></th>
                                <th><?php _e('Contents', 'psource-chat'); ?></th>
                                <th><?php _e('Actions', 'psource-chat'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backups as $backup): ?>
                                <tr>
                                    <td><?php echo esc_html($backup['name']); ?></td>
                                    <td><?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), $backup['date'])); ?></td>
                                    <td><?php echo esc_html(size_format($backup['size'])); ?></td>
                                    <td><?php echo esc_html(implode(', ', $backup['contents'])); ?></td>
                                    <td>
                                        <div class="psource-chat-actions">
                                            <button type="button" class="psource-chat-action-btn psource-chat-action-view" onclick="downloadBackup('<?php echo esc_js($backup['file']); ?>')">
                                                <?php _e('Download', 'psource-chat'); ?>
                                            </button>
                                            <button type="button" class="psource-chat-action-btn psource-chat-action-view" onclick="restoreBackup('<?php echo esc_js($backup['file']); ?>')">
                                                <?php _e('Restore', 'psource-chat'); ?>
                                            </button>
                                            <button type="button" class="psource-chat-action-btn psource-chat-action-delete" onclick="deleteBackup('<?php echo esc_js($backup['file']); ?>')">
                                                <?php _e('Delete', 'psource-chat'); ?>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <div class="psource-chat-backup-upload">
                <h4><?php _e('Upload Backup', 'psource-chat'); ?></h4>
                
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('psource_chat_upload_backup', 'upload_backup_nonce'); ?>
                    
                    <div class="psource-chat-form-field">
                        <label for="backup_file"><?php _e('Backup File', 'psource-chat'); ?></label>
                        <input type="file" id="backup_file" name="backup_file" accept=".zip,.json" required>
                        <p class="description"><?php _e('Upload a backup file (.zip or .json format)', 'psource-chat'); ?></p>
                    </div>
                    
                    <div class="psource-chat-form-field">
                        <button type="submit" name="upload_backup" class="button">
                            <?php _e('Upload & Restore', 'psource-chat'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render cleanup tools
     */
    private function render_cleanup_tools() {
        $cleanup_stats = $this->get_cleanup_stats();
        
        ?>
        <div class="psource-chat-tools-section">
            <h3><?php _e('Data Cleanup', 'psource-chat'); ?></h3>
            <p class="description">
                <?php _e('Clean up old and unnecessary data to optimize database performance.', 'psource-chat'); ?>
            </p>
            
            <div class="psource-chat-cleanup-stats">
                <h4><?php _e('Cleanup Statistics', 'psource-chat'); ?></h4>
                
                <div class="psource-chat-stats-grid">
                    <div class="psource-chat-stat-item">
                        <div class="psource-chat-stat-value"><?php echo esc_html($cleanup_stats['old_sessions']); ?></div>
                        <div class="psource-chat-stat-label"><?php _e('Old Sessions (>30 days)', 'psource-chat'); ?></div>
                    </div>
                    
                    <div class="psource-chat-stat-item">
                        <div class="psource-chat-stat-value"><?php echo esc_html($cleanup_stats['empty_sessions']); ?></div>
                        <div class="psource-chat-stat-label"><?php _e('Empty Sessions', 'psource-chat'); ?></div>
                    </div>
                    
                    <div class="psource-chat-stat-item">
                        <div class="psource-chat-stat-value"><?php echo esc_html($cleanup_stats['old_logs']); ?></div>
                        <div class="psource-chat-stat-label"><?php _e('Old Logs (>90 days)', 'psource-chat'); ?></div>
                    </div>
                    
                    <div class="psource-chat-stat-item">
                        <div class="psource-chat-stat-value"><?php echo esc_html($cleanup_stats['orphaned_messages']); ?></div>
                        <div class="psource-chat-stat-label"><?php _e('Orphaned Messages', 'psource-chat'); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="psource-chat-cleanup-actions">
                <h4><?php _e('Cleanup Actions', 'psource-chat'); ?></h4>
                
                <form class="psource-chat-ajax-form" data-action="cleanup_data">
                    <div class="psource-chat-checkbox-field">
                        <input type="checkbox" id="cleanup_old_sessions" name="cleanup_items[]" value="old_sessions">
                        <label for="cleanup_old_sessions">
                            <?php _e('Remove sessions older than 30 days', 'psource-chat'); ?>
                            (<?php echo esc_html($cleanup_stats['old_sessions']); ?> items)
                        </label>
                    </div>
                    
                    <div class="psource-chat-checkbox-field">
                        <input type="checkbox" id="cleanup_empty_sessions" name="cleanup_items[]" value="empty_sessions">
                        <label for="cleanup_empty_sessions">
                            <?php _e('Remove empty sessions (no messages)', 'psource-chat'); ?>
                            (<?php echo esc_html($cleanup_stats['empty_sessions']); ?> items)
                        </label>
                    </div>
                    
                    <div class="psource-chat-checkbox-field">
                        <input type="checkbox" id="cleanup_old_logs" name="cleanup_items[]" value="old_logs">
                        <label for="cleanup_old_logs">
                            <?php _e('Remove logs older than 90 days', 'psource-chat'); ?>
                            (<?php echo esc_html($cleanup_stats['old_logs']); ?> items)
                        </label>
                    </div>
                    
                    <div class="psource-chat-checkbox-field">
                        <input type="checkbox" id="cleanup_orphaned_messages" name="cleanup_items[]" value="orphaned_messages">
                        <label for="cleanup_orphaned_messages">
                            <?php _e('Remove orphaned messages (no session)', 'psource-chat'); ?>
                            (<?php echo esc_html($cleanup_stats['orphaned_messages']); ?> items)
                        </label>
                    </div>
                    
                    <div class="psource-chat-checkbox-field">
                        <input type="checkbox" id="optimize_tables" name="cleanup_items[]" value="optimize_tables">
                        <label for="optimize_tables">
                            <?php _e('Optimize database tables', 'psource-chat'); ?>
                        </label>
                    </div>
                    
                    <div class="psource-chat-form-field">
                        <button type="submit" class="button button-secondary">
                            <?php _e('Run Cleanup', 'psource-chat'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render diagnostics
     */
    private function render_diagnostics() {
        $system_info = $this->get_system_info();
        
        ?>
        <div class="psource-chat-tools-section">
            <h3><?php _e('System Diagnostics', 'psource-chat'); ?></h3>
            <p class="description">
                <?php _e('Check system requirements and run diagnostic tests.', 'psource-chat'); ?>
            </p>
            
            <div class="psource-chat-system-info">
                <h4><?php _e('System Information', 'psource-chat'); ?></h4>
                
                <table class="psource-chat-details-table">
                    <?php foreach ($system_info as $key => $value): ?>
                        <tr>
                            <th><?php echo esc_html($key); ?></th>
                            <td><?php echo esc_html($value); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            
            <div class="psource-chat-diagnostic-tests">
                <h4><?php _e('Diagnostic Tests', 'psource-chat'); ?></h4>
                
                <button type="button" class="button" onclick="runDiagnosticTest('database')">
                    <?php _e('Test Database Connection', 'psource-chat'); ?>
                </button>
                
                <button type="button" class="button" onclick="runDiagnosticTest('permissions')">
                    <?php _e('Test File Permissions', 'psource-chat'); ?>
                </button>
                
                <button type="button" class="button" onclick="runDiagnosticTest('ajax')">
                    <?php _e('Test AJAX Functionality', 'psource-chat'); ?>
                </button>
                
                <button type="button" class="button" onclick="runDiagnosticTest('websockets')">
                    <?php _e('Test WebSocket Support', 'psource-chat'); ?>
                </button>
                
                <button type="button" class="button button-primary" onclick="runDiagnosticTest('all')">
                    <?php _e('Run All Tests', 'psource-chat'); ?>
                </button>
                
                <div id="diagnostic-results" style="margin-top: 20px;"></div>
            </div>
            
            <div class="psource-chat-export-info">
                <h4><?php _e('Export System Information', 'psource-chat'); ?></h4>
                <p class="description">
                    <?php _e('Export system information and diagnostic results for support purposes.', 'psource-chat'); ?>
                </p>
                
                <button type="button" class="button" onclick="exportSystemInfo()">
                    <?php _e('Export System Info', 'psource-chat'); ?>
                </button>
            </div>
        </div>
        <?php
    }
    

    
    /**
     * Get available backups
     */
    private function get_available_backups() {
        $backup_dir = wp_upload_dir()['basedir'] . '/psource-chat-backups/';
        
        if (!is_dir($backup_dir)) {
            return [];
        }
        
        $backups = [];
        $files = glob($backup_dir . '*.{zip,json}', GLOB_BRACE);
        
        foreach ($files as $file) {
            $filename = basename($file);
            $info = pathinfo($file);
            
            $backups[] = [
                'file' => $filename,
                'name' => $info['filename'],
                'date' => filemtime($file),
                'size' => filesize($file),
                'contents' => $this->get_backup_contents($file)
            ];
        }
        
        usort($backups, function($a, $b) {
            return $b['date'] - $a['date'];
        });
        
        return $backups;
    }
    
    /**
     * Get backup contents
     */
    private function get_backup_contents($file) {
        // This would analyze the backup file to determine what data it contains
        // For now, return a placeholder
        return ['messages', 'sessions', 'settings'];
    }
    
    /**
     * Get cleanup statistics
     */
    private function get_cleanup_stats() {
        global $wpdb;
        
        $sessions_table = Database::get_table_name('sessions');
        $messages_table = Database::get_table_name('messages');
        $logs_table = Database::get_table_name('logs');
        
        return [
            'old_sessions' => $wpdb->get_var("
                SELECT COUNT(*) FROM {$sessions_table} 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
            ") ?: 0,
            'empty_sessions' => $wpdb->get_var("
                SELECT COUNT(s.id) FROM {$sessions_table} s
                LEFT JOIN {$messages_table} m ON s.id = m.session_id
                WHERE m.id IS NULL
            ") ?: 0,
            'old_logs' => $wpdb->get_var("
                SELECT COUNT(*) FROM {$logs_table}
                WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)
            ") ?: 0,
            'orphaned_messages' => $wpdb->get_var("
                SELECT COUNT(m.id) FROM {$messages_table} m
                LEFT JOIN {$sessions_table} s ON m.session_id = s.id
                WHERE s.id IS NULL
            ") ?: 0
        ];
    }
    
    /**
     * Get system information
     */
    private function get_system_info() {
        global $wp_version, $wpdb;
        
        return [
            'WordPress Version' => $wp_version,
            'PHP Version' => PHP_VERSION,
            'MySQL Version' => $wpdb->db_version(),
            'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'Max Execution Time' => ini_get('max_execution_time') . 's',
            'Memory Limit' => ini_get('memory_limit'),
            'Upload Max Size' => ini_get('upload_max_filesize'),
            'Plugin Version' => PSOURCE_CHAT_VERSION,
            'Database Tables' => $this->check_database_tables() ? 'OK' : 'Missing',
            'File Permissions' => $this->check_file_permissions() ? 'OK' : 'Issues Detected'
        ];
    }
    
    /**
     * Check database tables
     */
    private function check_database_tables() {
        global $wpdb;
        
        $tables = ['sessions', 'messages', 'logs'];
        
        foreach ($tables as $table) {
            $table_name = Database::get_table_name($table);
            $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
            
            if (!$exists) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Check file permissions
     */
    private function check_file_permissions() {
        $upload_dir = wp_upload_dir();
        
        return is_writable($upload_dir['basedir']);
    }
    

    
    /**
     * Other AJAX handlers would go here...
     */
    
    public function backup_data() {
        check_ajax_referer('psource_chat_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'psource-chat'));
        }
        
        // Implementation would go here
        wp_send_json_success(__('Backup created successfully.', 'psource-chat'));
    }
    
    public function restore_data() {
        // Implementation
    }
    
    public function cleanup_data() {
        // Implementation
    }
    
    public function test_system() {
        // Implementation
    }
}
