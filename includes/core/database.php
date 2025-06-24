<?php
/**
 * Database Handler
 * 
 * @package PSSource\Chat\Core
 */

namespace PSSource\Chat\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database operations for chat functionality
 */
class Database {
    
    /**
     * Get table name with proper prefix
     */
    public static function get_table_name($table) {
        global $wpdb;
        return $wpdb->base_prefix . 'psource_chat_' . $table;
    }
    
    /**
     * Maybe create database tables
     */
    public static function maybe_create_tables() {
        $installed_version = get_option('psource_chat_db_version', '0.0.0');
        
        if (version_compare($installed_version, PSOURCE_CHAT_VERSION, '<')) {
            self::create_tables();
            update_option('psource_chat_db_version', PSOURCE_CHAT_VERSION);
        }
    }
    
    /**
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Sessions table
        $sessions_table = self::get_table_name('sessions');
        $sessions_sql = "CREATE TABLE IF NOT EXISTS $sessions_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL,
            session_type varchar(50) NOT NULL DEFAULT 'site',
            session_status varchar(20) NOT NULL DEFAULT 'active',
            session_host varchar(255) NOT NULL,
            blog_id bigint(20) unsigned NOT NULL DEFAULT 1,
            created_on datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_on datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY session_id (session_id),
            KEY session_type (session_type),
            KEY session_status (session_status),
            KEY blog_id (blog_id),
            KEY created_on (created_on)
        ) $charset_collate;";
        
        // Messages table
        $messages_table = self::get_table_name('messages');
        $messages_sql = "CREATE TABLE IF NOT EXISTS $messages_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL,
            session_type varchar(50) NOT NULL DEFAULT 'site',
            message_type varchar(50) NOT NULL DEFAULT 'public',
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            user_login varchar(60) NOT NULL,
            user_name varchar(250) NOT NULL,
            user_email varchar(100) NOT NULL,
            user_ip varchar(45) NOT NULL,
            message_text longtext NOT NULL,
            message_time datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            blog_id bigint(20) unsigned NOT NULL DEFAULT 1,
            room_id bigint(20) unsigned NOT NULL DEFAULT 0,
            recipient_id bigint(20) unsigned NOT NULL DEFAULT 0,
            is_private tinyint(1) NOT NULL DEFAULT 0,
            is_moderated tinyint(1) NOT NULL DEFAULT 0,
            is_deleted tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY session_type (session_type),
            KEY message_type (message_type),
            KEY user_id (user_id),
            KEY message_time (message_time),
            KEY blog_id (blog_id),
            KEY room_id (room_id),
            KEY recipient_id (recipient_id),
            KEY is_private (is_private),
            KEY is_deleted (is_deleted)
        ) $charset_collate;";
        
        // User sessions table
        $user_sessions_table = self::get_table_name('user_sessions');
        $user_sessions_sql = "CREATE TABLE IF NOT EXISTS $user_sessions_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            user_login varchar(60) NOT NULL,
            user_name varchar(250) NOT NULL,
            user_email varchar(100) NOT NULL,
            user_ip varchar(45) NOT NULL,
            user_agent text,
            session_status varchar(20) NOT NULL DEFAULT 'active',
            last_seen datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            joined_on datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            blog_id bigint(20) unsigned NOT NULL DEFAULT 1,
            user_settings longtext,
            PRIMARY KEY (id),
            UNIQUE KEY session_user (session_id, user_id),
            KEY session_id (session_id),
            KEY user_id (user_id),
            KEY session_status (session_status),
            KEY last_seen (last_seen),
            KEY blog_id (blog_id)
        ) $charset_collate;";
        
        // Logs table
        $logs_table = self::get_table_name('logs');
        $logs_sql = "CREATE TABLE IF NOT EXISTS $logs_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            log_type varchar(50) NOT NULL DEFAULT 'system',
            level varchar(20) NOT NULL DEFAULT 'info',
            message text NOT NULL,
            context longtext,
            user_id bigint(20) unsigned DEFAULT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            blog_id bigint(20) unsigned NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            KEY log_type (log_type),
            KEY level (level),
            KEY user_id (user_id),
            KEY created_at (created_at),
            KEY blog_id (blog_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sessions_sql);
        dbDelta($messages_sql);
        dbDelta($user_sessions_sql);
        dbDelta($logs_sql);
    }
    
    /**
     * Create a new chat session
     */
    public static function create_session($session_type = 'site', $session_host = '') {
        global $wpdb;
        
        $session_id = wp_generate_uuid4();
        $table = self::get_table_name('sessions');
        
        $result = $wpdb->insert(
            $table,
            [
                'session_id' => $session_id,
                'session_type' => $session_type,
                'session_host' => $session_host ?: $_SERVER['HTTP_HOST'],
                'blog_id' => get_current_blog_id()
            ],
            ['%s', '%s', '%s', '%d']
        );
        
        return $result ? $session_id : false;
    }
    
    /**
     * Get session by ID
     */
    public static function get_session($session_id) {
        global $wpdb;
        
        $table = self::get_table_name('sessions');
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE session_id = %s AND session_status = 'active'",
                $session_id
            )
        );
    }
    
    /**
     * Add user to session
     */
    public static function add_user_to_session($session_id, $user_data) {
        global $wpdb;
        
        $table = self::get_table_name('user_sessions');
        
        $defaults = [
            'user_id' => get_current_user_id(),
            'user_login' => '',
            'user_name' => '',
            'user_email' => '',
            'user_ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'blog_id' => get_current_blog_id(),
            'user_settings' => json_encode([])
        ];
        
        $user_data = wp_parse_args($user_data, $defaults);
        $user_data['session_id'] = $session_id;
        
        return $wpdb->replace($table, $user_data);
    }
    
    /**
     * Update user session activity
     */
    public static function update_user_activity($session_id, $user_id) {
        global $wpdb;
        
        $table = self::get_table_name('user_sessions');
        
        return $wpdb->update(
            $table,
            ['last_seen' => current_time('mysql')],
            [
                'session_id' => $session_id,
                'user_id' => $user_id
            ],
            ['%s'],
            ['%s', '%d']
        );
    }
    
    /**
     * Add message to session
     */
    public static function add_message($session_id, $message_data) {
        global $wpdb;
        
        $table = self::get_table_name('messages');
        
        $defaults = [
            'session_type' => 'site',
            'user_id' => get_current_user_id(),
            'user_login' => '',
            'user_name' => '',
            'user_email' => '',
            'user_ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'blog_id' => get_current_blog_id(),
            'is_private' => 0,
            'is_moderated' => 0
        ];
        
        $message_data = wp_parse_args($message_data, $defaults);
        $message_data['session_id'] = $session_id;
        
        $result = $wpdb->insert($table, $message_data);
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Get messages for session
     */
    public static function get_messages($session_id, $limit = 50, $offset = 0, $since = null) {
        global $wpdb;
        
        $table = self::get_table_name('messages');
        
        $where = "session_id = %s AND is_deleted = 0";
        $params = [$session_id];
        
        if ($since) {
            $where .= " AND message_time > %s";
            $params[] = $since;
        }
        
        $sql = $wpdb->prepare(
            "SELECT * FROM $table 
             WHERE $where 
             ORDER BY message_time DESC 
             LIMIT %d OFFSET %d",
            array_merge($params, [$limit, $offset])
        );
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Get active users in session
     */
    public static function get_active_users($session_id, $timeout_minutes = 5) {
        global $wpdb;
        
        $table = self::get_table_name('user_sessions');
        $timeout = date('Y-m-d H:i:s', strtotime("-{$timeout_minutes} minutes"));
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table 
                 WHERE session_id = %s 
                 AND session_status = 'active' 
                 AND last_seen > %s 
                 ORDER BY last_seen DESC",
                $session_id,
                $timeout
            )
        );
    }
    
    /**
     * Cleanup user data
     */
    public static function cleanup_user_data($user_id) {
        global $wpdb;
        
        $messages_table = self::get_table_name('messages');
        $user_sessions_table = self::get_table_name('user_sessions');
        
        // Mark messages as deleted instead of removing them
        $wpdb->update(
            $messages_table,
            ['is_deleted' => 1],
            ['user_id' => $user_id],
            ['%d'],
            ['%d']
        );
        
        // Remove user sessions
        $wpdb->delete(
            $user_sessions_table,
            ['user_id' => $user_id],
            ['%d']
        );
    }
    
    /**
     * Cleanup old data
     */
    public static function cleanup_old_data($days = 30) {
        global $wpdb;
        
        $messages_table = self::get_table_name('messages');
        $user_sessions_table = self::get_table_name('user_sessions');
        $sessions_table = self::get_table_name('sessions');
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        // Delete old messages
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $messages_table WHERE message_time < %s",
                $cutoff_date
            )
        );
        
        // Delete old user sessions
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $user_sessions_table WHERE last_seen < %s",
                $cutoff_date
            )
        );
        
        // Delete old sessions
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $sessions_table WHERE updated_on < %s",
                $cutoff_date
            )
        );
    }
}
