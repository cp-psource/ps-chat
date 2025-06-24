<?php
/**
 * Plugin Installer
 * 
 * @package PSSource\Chat\Core
 */

namespace PSSource\Chat\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles plugin activation, deactivation and upgrades
 */
class Installer {
    
    /**
     * Plugin activation
     */
    public static function activate() {
        // Create database tables
        Database::create_tables();
        
        // Set default options
        self::set_default_options();
        
        // Create upload directory
        self::create_upload_directory();
        
        // Schedule cleanup events
        self::schedule_cleanup();
        
        // Set version
        update_option('psource_chat_version', PSOURCE_CHAT_VERSION);
        update_option('psource_chat_db_version', PSOURCE_CHAT_VERSION);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public static function deactivate() {
        // Clear scheduled events
        self::clear_scheduled_events();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin upgrade
     */
    public static function upgrade($from_version, $to_version) {
        // Version-specific upgrades
        
        if (version_compare($from_version, '3.0.0', '<')) {
            self::upgrade_to_3_0_0();
        }
        
        // Always update database structure
        Database::create_tables();
    }
    
    /**
     * Set default options
     */
    private static function set_default_options() {
        $defaults = [
            'enable_sound' => true,
            'enable_emoji' => true,
            'max_message_length' => 500,
            'chat_timeout' => 300,
            'enable_private_chat' => true,
            'allow_guest_chat' => false,
            'moderate_messages' => false,
            'bad_words_filter' => true,
            'cleanup_days' => 30,
            'max_users_per_session' => 50,
            'enable_file_uploads' => false,
            'allowed_file_types' => ['jpg', 'jpeg', 'png', 'gif'],
            'max_file_size' => 2048, // KB
            'chat_history_limit' => 100,
            'enable_push_notifications' => false,
            'admin_email_notifications' => false,
            'theme' => 'default',
            'position' => 'bottom-right',
            'width' => 400,
            'height' => 500,
            'minimized_height' => 40
        ];
        
        if (is_multisite()) {
            add_site_option('psource_chat_options', $defaults);
        } else {
            add_option('psource_chat_options', $defaults);
        }
    }
    
    /**
     * Create upload directory for chat files
     */
    private static function create_upload_directory() {
        $upload_dir = wp_upload_dir();
        $chat_dir = $upload_dir['basedir'] . '/psource-chat';
        
        if (!file_exists($chat_dir)) {
            wp_mkdir_p($chat_dir);
            
            // Create .htaccess file for security
            $htaccess_content = "Options -Indexes\n";
            $htaccess_content .= "<Files *.php>\n";
            $htaccess_content .= "deny from all\n";
            $htaccess_content .= "</Files>\n";
            
            file_put_contents($chat_dir . '/.htaccess', $htaccess_content);
            
            // Create index.php file
            file_put_contents($chat_dir . '/index.php', '<?php // Silence is golden');
        }
    }
    
    /**
     * Schedule cleanup events
     */
    private static function schedule_cleanup() {
        if (!wp_next_scheduled('psource_chat_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'psource_chat_daily_cleanup');
        }
        
        // Schedule hourly inactive user cleanup
        if (!wp_next_scheduled('psource_chat_hourly_cleanup')) {
            wp_schedule_event(time(), 'hourly', 'psource_chat_hourly_cleanup');
        }
    }
    
    /**
     * Clear scheduled events
     */
    private static function clear_scheduled_events() {
        wp_clear_scheduled_hook('psource_chat_daily_cleanup');
        wp_clear_scheduled_hook('psource_chat_hourly_cleanup');
    }
    
    /**
     * Upgrade to version 3.0.0
     */
    private static function upgrade_to_3_0_0() {
        global $wpdb;
        
        // Migrate old options
        $old_options = get_option('psource-chat-options', []);
        if (!empty($old_options)) {
            $new_options = self::migrate_old_options($old_options);
            update_option('psource_chat_options', $new_options);
            delete_option('psource-chat-options');
        }
        
        // Migrate old table data if exists
        $old_tables = [
            $wpdb->base_prefix . 'psource_chat_log',
            $wpdb->base_prefix . 'psource_chat_message'
        ];
        
        foreach ($old_tables as $old_table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$old_table'") === $old_table) {
                self::migrate_table_data($old_table);
            }
        }
    }
    
    /**
     * Migrate old options to new structure
     */
    private static function migrate_old_options($old_options) {
        $mapping = [
            'chat_enable_sound' => 'enable_sound',
            'chat_enable_emoticons' => 'enable_emoji',
            'chat_message_max_length' => 'max_message_length',
            'chat_session_timeout' => 'chat_timeout',
            'chat_enable_private' => 'enable_private_chat',
            'chat_allow_anon' => 'allow_guest_chat'
        ];
        
        $new_options = [];
        foreach ($mapping as $old_key => $new_key) {
            if (isset($old_options[$old_key])) {
                $new_options[$new_key] = $old_options[$old_key];
            }
        }
        
        return $new_options;
    }
    
    /**
     * Migrate old table data
     */
    private static function migrate_table_data($old_table) {
        global $wpdb;
        
        // This is a placeholder - implement actual migration logic
        // based on your old table structure
        
        // Example migration for old message table
        if (strpos($old_table, 'message') !== false) {
            $old_messages = $wpdb->get_results("SELECT * FROM $old_table LIMIT 1000");
            
            foreach ($old_messages as $message) {
                // Convert old message format to new format
                Database::add_message($message->session_id ?? 'migrated', [
                    'message_text' => $message->message ?? '',
                    'user_id' => $message->user_id ?? 0,
                    'user_name' => $message->user_name ?? '',
                    'user_email' => $message->user_email ?? '',
                    'message_time' => $message->timestamp ?? current_time('mysql')
                ]);
            }
        }
    }
    
    /**
     * Create necessary WordPress capabilities
     */
    private static function create_capabilities() {
        $capabilities = [
            'manage_chat' => __('Manage Chat', 'psource-chat'),
            'moderate_chat' => __('Moderate Chat', 'psource-chat'),
            'view_chat_logs' => __('View Chat Logs', 'psource-chat'),
            'delete_chat_messages' => __('Delete Chat Messages', 'psource-chat')
        ];
        
        // Add capabilities to administrator role
        $admin_role = get_role('administrator');
        if ($admin_role) {
            foreach ($capabilities as $cap => $description) {
                $admin_role->add_cap($cap);
            }
        }
        
        // Add basic capabilities to editor role
        $editor_role = get_role('editor');
        if ($editor_role) {
            $editor_role->add_cap('moderate_chat');
            $editor_role->add_cap('view_chat_logs');
        }
    }
    
    /**
     * Remove capabilities on uninstall
     */
    public static function remove_capabilities() {
        $capabilities = [
            'manage_chat',
            'moderate_chat', 
            'view_chat_logs',
            'delete_chat_messages'
        ];
        
        $roles = ['administrator', 'editor'];
        
        foreach ($roles as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                foreach ($capabilities as $cap) {
                    $role->remove_cap($cap);
                }
            }
        }
    }
    
    /**
     * Complete plugin uninstall (called from uninstall.php)
     */
    public static function uninstall() {
        global $wpdb;
        
        // Remove all options
        delete_option('psource_chat_options');
        delete_option('psource_chat_version');
        delete_option('psource_chat_db_version');
        delete_site_option('psource_chat_options');
        
        // Remove capabilities
        self::remove_capabilities();
        
        // Clear scheduled events
        self::clear_scheduled_events();
        
        // Remove database tables
        $tables = [
            Database::get_table_name('sessions'),
            Database::get_table_name('messages'),
            Database::get_table_name('user_sessions')
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        // Remove upload directory
        $upload_dir = wp_upload_dir();
        $chat_dir = $upload_dir['basedir'] . '/psource-chat';
        
        if (file_exists($chat_dir)) {
            self::remove_directory($chat_dir);
        }
        
        // Clear any cached data
        wp_cache_flush();
    }
    
    /**
     * Recursively remove directory
     */
    private static function remove_directory($dir) {
        if (!file_exists($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? self::remove_directory($path) : unlink($path);
        }
        
        rmdir($dir);
    }
}
