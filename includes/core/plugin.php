<?php
/**
 * Main Plugin Class
 * 
 * @package PSSource\Chat\Core
 */

namespace PSSource\Chat\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Plugin Class
 */
class Plugin {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Plugin version
     */
    private $version;
    
    /**
     * Chat options
     */
    private $options = [];
    
    /**
     * Get plugin instance
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
        // Set version with fallback
        $this->version = defined('PSOURCE_CHAT_VERSION') ? PSOURCE_CHAT_VERSION : '3.0.0';
        $this->init();
    }
    
    /**
     * Initialize plugin
     */
    private function init() {
        // Ensure database tables exist
        Database::maybe_create_tables();
        
        // Load options
        $this->load_options();
        
        // Initialize components
        $this->init_hooks();
        $this->init_components();
        
        // Check for updates
        $this->init_updater();
    }
    
    /**
     * Load plugin options
     */
    private function load_options() {
        $defaults = [
            'enable_sound' => true,
            'enable_emoji' => true,
            'max_message_length' => 500,
            'chat_timeout' => 300,
            'enable_private_chat' => true,
            'allow_guest_chat' => false,
            'moderate_messages' => false,
            'bad_words_filter' => true
        ];
        
        if (is_multisite()) {
            $this->options = get_site_option('psource_chat_options', $defaults);
        } else {
            $this->options = get_option('psource_chat_options', $defaults);
        }
    }
    
    /**
     * Get plugin options
     */
    public function get_options() {
        return $this->options;
    }
    
    /**
     * Get specific option value
     */
    public function get_option($key, $default = null) {
        return $this->options[$key] ?? $default;
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Basic plugin hooks only - admin components are initialized in init_components
    }
    
    /**
     * Initialize components
     */
    private function init_components() {
        // Initialize core chat engine ALWAYS (needed for AJAX handlers)
        \PSSource\Chat\Core\Chat_Engine::get_instance();
        
        // Initialize admin components
        if (is_admin()) {
            // Always initialize admin menu
            new \PSSource\Chat\Admin\Admin_Menu();
            
            // Always initialize dashboard widgets (they will check their own options)
            new \PSSource\Chat\Admin\Dashboard_Widgets();
        }
        
        // Initialize frontend components
        if (!is_admin()) {
            new \PSSource\Chat\Frontend\Frontend_Chat();
            new \PSSource\Chat\Frontend\Admin_Bar_Chat();
        }
        
        // Initialize widgets if enabled
        $this->init_widgets();
        
        // Load integrations
        if (class_exists('BuddyPress')) {
            new \PSSource\Chat\Integrations\BuddyPress();
        }
        
        // Load API
        new \PSSource\Chat\API\Chat_REST_Controller();
        
        // Initialize extensions
        $this->init_extensions();
        
        // Load frontend components (only if extensions are enabled)
        if (!is_admin()) {
            $extension_options = get_option('psource_chat_extensions', []);
            
            // Only load Chat_Handler if Frontend extension is enabled
            $frontend_options = $extension_options['frontend'] ?? [];
            if (($frontend_options['enabled'] ?? 'disabled') === 'enabled' && class_exists('\PSSource\Chat\Frontend\Chat_Handler')) {
                new \PSSource\Chat\Frontend\Chat_Handler();
            }
            
            // Always load shortcodes for backward compatibility
            if (class_exists('\PSSource\Chat\Frontend\Shortcode_Handler')) {
                new \PSSource\Chat\Frontend\Shortcode_Handler();
            }
        }
    }
    
    /**
     * Initialize widgets
     */
    private function init_widgets() {
        $extension_options = get_option('psource_chat_extensions', []);
        $widget_options = $extension_options['widgets'] ?? [];
        
        // Register enhanced widgets if enabled
        if (($widget_options['enable_chat_widget'] ?? 'enabled') === 'enabled' ||
            ($widget_options['enable_status_widget'] ?? 'enabled') === 'enabled' ||
            ($widget_options['enable_friends_widget'] ?? 'enabled') === 'enabled' ||
            ($widget_options['enable_rooms_widget'] ?? 'enabled') === 'enabled') {
            
            add_action('widgets_init', function() use ($widget_options) {
                if (file_exists(PSOURCE_CHAT_INCLUDES_DIR . 'frontend/enhanced-widgets.php')) {
                    require_once PSOURCE_CHAT_INCLUDES_DIR . 'frontend/enhanced-widgets.php';
                    
                    if (($widget_options['enable_chat_widget'] ?? 'enabled') === 'enabled') {
                        register_widget('\PSSource\Chat\Frontend\Enhanced_Chat_Widget');
                    }
                    if (($widget_options['enable_status_widget'] ?? 'enabled') === 'enabled') {
                        register_widget('\PSSource\Chat\Frontend\Enhanced_Chat_Status_Widget');
                    }
                    if (($widget_options['enable_friends_widget'] ?? 'enabled') === 'enabled') {
                        register_widget('\PSSource\Chat\Frontend\Enhanced_Chat_Friends_Widget');
                    }
                    if (($widget_options['enable_rooms_widget'] ?? 'enabled') === 'enabled') {
                        register_widget('\PSSource\Chat\Frontend\Enhanced_Chat_Rooms_Widget');
                    }
                }
            });
        }
    }
    
    /**
     * Initialize extensions
     */
    private function init_extensions() {
        // Load Support Chat extension
        $extension_options = get_option('psource_chat_extensions', []);
        $support_chat_options = $extension_options['support_chat'] ?? [];
        
        if (($support_chat_options['enabled'] ?? 'disabled') === 'enabled') {
            if (file_exists(PSOURCE_CHAT_INCLUDES_DIR . 'extensions/support-chat.php')) {
                require_once PSOURCE_CHAT_INCLUDES_DIR . 'extensions/support-chat.php';
                new \PSSource\Chat\Extensions\Support_Chat();
            }
        }
        
        // Load Attachments extension
        $attachments_options = $extension_options['attachments'] ?? [];
        if (($attachments_options['enabled'] ?? 'disabled') === 'enabled') {
            if (file_exists(PSOURCE_CHAT_INCLUDES_DIR . 'core/extension-base.php')) {
                require_once PSOURCE_CHAT_INCLUDES_DIR . 'core/extension-base.php';
            }
            if (file_exists(PSOURCE_CHAT_INCLUDES_DIR . 'extensions/class-attachments.php')) {
                require_once PSOURCE_CHAT_INCLUDES_DIR . 'extensions/class-attachments.php';
                new \PSSource\Chat\Extensions\Attachments();
            }
        }
        
        // Hook for external extensions
        do_action('psource_chat_load_extensions');
    }
    
    /**
     * Initialize updater
     */
    private function init_updater() {
        // Placeholder for future update functionality
    }
}
