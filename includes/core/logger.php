<?php
/**
 * Logger Class
 * 
 * @package PSSource\Chat\Core
 */

namespace PSSource\Chat\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Logger Class for handling chat logs
 */
class Logger {
    
    /**
     * Log levels
     */
    const EMERGENCY = 'emergency';
    const ALERT = 'alert';
    const CRITICAL = 'critical';
    const ERROR = 'error';
    const WARNING = 'warning';
    const NOTICE = 'notice';
    const INFO = 'info';
    const DEBUG = 'debug';
    
    /**
     * Log types
     */
    const TYPE_ERROR = 'error';
    const TYPE_ACTIVITY = 'activity';
    const TYPE_SYSTEM = 'system';
    
    /**
     * Logger instance
     */
    private static $instance = null;
    
    /**
     * Get logger instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Private constructor for singleton
    }
    
    /**
     * Log emergency message
     */
    public static function emergency($message, array $context = [], $type = self::TYPE_ERROR) {
        return self::log(self::EMERGENCY, $message, $context, $type);
    }
    
    /**
     * Log alert message
     */
    public static function alert($message, array $context = [], $type = self::TYPE_ERROR) {
        return self::log(self::ALERT, $message, $context, $type);
    }
    
    /**
     * Log critical message
     */
    public static function critical($message, array $context = [], $type = self::TYPE_ERROR) {
        return self::log(self::CRITICAL, $message, $context, $type);
    }
    
    /**
     * Log error message
     */
    public static function error($message, array $context = [], $type = self::TYPE_ERROR) {
        return self::log(self::ERROR, $message, $context, $type);
    }
    
    /**
     * Log warning message
     */
    public static function warning($message, array $context = [], $type = self::TYPE_SYSTEM) {
        return self::log(self::WARNING, $message, $context, $type);
    }
    
    /**
     * Log notice message
     */
    public static function notice($message, array $context = [], $type = self::TYPE_SYSTEM) {
        return self::log(self::NOTICE, $message, $context, $type);
    }
    
    /**
     * Log info message
     */
    public static function info($message, array $context = [], $type = self::TYPE_ACTIVITY) {
        return self::log(self::INFO, $message, $context, $type);
    }
    
    /**
     * Log debug message
     */
    public static function debug($message, array $context = [], $type = self::TYPE_SYSTEM) {
        if (!WP_DEBUG) {
            return true; // Skip debug logs in production
        }
        return self::log(self::DEBUG, $message, $context, $type);
    }
    
    /**
     * Main log method
     */
    public static function log($level, $message, array $context = [], $type = self::TYPE_SYSTEM) {
        global $wpdb;
        
        // Get table name
        $table_name = Database::get_table_name('logs');
        
        // Prepare context data
        $context_json = !empty($context) ? wp_json_encode($context) : null;
        
        // Get current user info
        $user_id = get_current_user_id();
        $ip_address = self::get_client_ip();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Insert log entry
        $result = $wpdb->insert(
            $table_name,
            [
                'log_type' => sanitize_text_field($type),
                'level' => sanitize_text_field($level),
                'message' => sanitize_textarea_field($message),
                'context' => $context_json,
                'user_id' => $user_id ?: null,
                'ip_address' => sanitize_text_field($ip_address),
                'user_agent' => sanitize_textarea_field($user_agent),
                'created_at' => current_time('mysql')
            ],
            [
                '%s', // log_type
                '%s', // level
                '%s', // message
                '%s', // context
                '%d', // user_id
                '%s', // ip_address
                '%s', // user_agent
                '%s'  // created_at
            ]
        );
        
        // Also log to WordPress debug.log if WP_DEBUG is enabled
        if (WP_DEBUG && WP_DEBUG_LOG) {
            $log_message = sprintf(
                '[PSChat-%s] %s: %s',
                strtoupper($level),
                $type,
                $message
            );
            
            if (!empty($context)) {
                $log_message .= ' Context: ' . wp_json_encode($context);
            }
            
            error_log($log_message);
        }
        
        return $result !== false;
    }
    
    /**
     * Log user activity
     */
    public static function log_activity($action, $details = '', array $context = []) {
        $user = wp_get_current_user();
        $user_name = $user->exists() ? $user->display_name : 'Guest';
        
        $message = sprintf(
            'User %s performed action: %s',
            $user_name,
            $action
        );
        
        if (!empty($details)) {
            $message .= ' - ' . $details;
        }
        
        // Add action and details to context
        $context['action'] = $action;
        $context['details'] = $details;
        $context['user_id'] = get_current_user_id();
        
        return self::info($message, $context, self::TYPE_ACTIVITY);
    }
    
    /**
     * Log system event
     */
    public static function log_system($component, $event, $description, $status = 'info', array $context = []) {
        $message = sprintf(
            'System event in %s: %s - %s',
            $component,
            $event,
            $description
        );
        
        // Add system info to context
        $context['component'] = $component;
        $context['event'] = $event;
        $context['description'] = $description;
        $context['status'] = $status;
        
        $level = self::INFO;
        if ($status === 'error') {
            $level = self::ERROR;
        } elseif ($status === 'warning') {
            $level = self::WARNING;
        }
        
        return self::log($level, $message, $context, self::TYPE_SYSTEM);
    }
    
    /**
     * Log chat message
     */
    public static function log_chat_message($session_id, $user_id, $message, $action = 'sent') {
        $user = get_userdata($user_id);
        $user_name = $user ? $user->display_name : 'Unknown User';
        
        $log_message = sprintf(
            'Chat message %s by %s in session %d',
            $action,
            $user_name,
            $session_id
        );
        
        $context = [
            'session_id' => $session_id,
            'user_id' => $user_id,
            'message_length' => strlen($message),
            'action' => $action
        ];
        
        return self::log_activity('chat_message_' . $action, $log_message, $context);
    }
    
    /**
     * Log session activity
     */
    public static function log_session($session_id, $action, $details = '', array $context = []) {
        $message = sprintf(
            'Session %d: %s',
            $session_id,
            $action
        );
        
        if (!empty($details)) {
            $message .= ' - ' . $details;
        }
        
        $context['session_id'] = $session_id;
        $context['action'] = $action;
        
        return self::log_activity('session_' . $action, $message, $context);
    }
    
    /**
     * Log user ban/unban
     */
    public static function log_user_ban($user_id, $action, $reason = '', $moderator_id = null) {
        $user = get_userdata($user_id);
        $user_name = $user ? $user->display_name : 'Unknown User';
        
        $moderator = $moderator_id ? get_userdata($moderator_id) : null;
        $moderator_name = $moderator ? $moderator->display_name : 'System';
        
        $message = sprintf(
            'User %s was %s by %s',
            $user_name,
            $action,
            $moderator_name
        );
        
        if (!empty($reason)) {
            $message .= ' - Reason: ' . $reason;
        }
        
        $context = [
            'target_user_id' => $user_id,
            'moderator_id' => $moderator_id,
            'action' => $action,
            'reason' => $reason
        ];
        
        return self::log_activity('user_' . $action, $message, $context);
    }
    
    /**
     * Log database error
     */
    public static function log_database_error($query, $error, array $context = []) {
        $message = sprintf(
            'Database error: %s',
            $error
        );
        
        $context['query'] = $query;
        $context['mysql_error'] = $error;
        
        return self::error($message, $context);
    }
    
    /**
     * Get recent logs
     */
    public static function get_recent_logs($limit = 50, $type = null, $level = null) {
        global $wpdb;
        
        $table_name = Database::get_table_name('logs');
        
        $where_conditions = [];
        $where_values = [];
        
        if ($type) {
            $where_conditions[] = 'log_type = %s';
            $where_values[] = $type;
        }
        
        if ($level) {
            $where_conditions[] = 'level = %s';
            $where_values[] = $level;
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $query = "
            SELECT * FROM {$table_name}
            {$where_clause}
            ORDER BY created_at DESC
            LIMIT %d
        ";
        
        $where_values[] = $limit;
        
        return $wpdb->get_results($wpdb->prepare($query, $where_values));
    }
    
    /**
     * Clean old logs
     */
    public static function clean_old_logs($days = 90) {
        global $wpdb;
        
        $table_name = Database::get_table_name('logs');
        
        $result = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table_name} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
        
        if ($result !== false) {
            self::log_system(
                'logger',
                'cleanup',
                sprintf('Cleaned %d old log entries (older than %d days)', $result, $days)
            );
        }
        
        return $result;
    }
    
    /**
     * Get log statistics
     */
    public static function get_log_stats() {
        global $wpdb;
        
        $table_name = Database::get_table_name('logs');
        
        $stats = $wpdb->get_results("
            SELECT 
                log_type,
                level,
                COUNT(*) as count,
                MAX(created_at) as latest
            FROM {$table_name}
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY log_type, level
            ORDER BY count DESC
        ");
        
        $formatted_stats = [];
        foreach ($stats as $stat) {
            $formatted_stats[] = [
                'type' => $stat->log_type,
                'level' => $stat->level,
                'count' => intval($stat->count),
                'latest' => $stat->latest
            ];
        }
        
        return $formatted_stats;
    }
    
    /**
     * Export logs
     */
    public static function export_logs($type = null, $format = 'json') {
        global $wpdb;
        
        $table_name = Database::get_table_name('logs');
        
        $where_clause = $type ? $wpdb->prepare('WHERE log_type = %s', $type) : '';
        
        $logs = $wpdb->get_results("
            SELECT * FROM {$table_name}
            {$where_clause}
            ORDER BY created_at DESC
        ", ARRAY_A);
        
        if ($format === 'csv') {
            return self::convert_to_csv($logs);
        }
        
        return wp_json_encode($logs, JSON_PRETTY_PRINT);
    }
    
    /**
     * Convert logs to CSV format
     */
    private static function convert_to_csv($logs) {
        if (empty($logs)) {
            return '';
        }
        
        $csv = fopen('php://temp', 'r+');
        
        // Write header
        fputcsv($csv, array_keys($logs[0]));
        
        // Write data
        foreach ($logs as $log) {
            fputcsv($csv, $log);
        }
        
        rewind($csv);
        $csv_content = stream_get_contents($csv);
        fclose($csv);
        
        return $csv_content;
    }
    
    /**
     * Get client IP address
     */
    private static function get_client_ip() {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                
                // Handle comma-separated IPs (forwarded headers)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                // Validate IP address
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
