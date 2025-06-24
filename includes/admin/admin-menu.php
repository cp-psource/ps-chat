<?php
/**
 * Admin Menu Handler
 * 
 * @package PSSource\Chat\Admin
 */

namespace PSSource\Chat\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles admin menu creation and management
 */
class Admin_Menu {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('network_admin_menu', [$this, 'add_network_admin_menu']);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Main menu page
        add_menu_page(
            __('PS Chat', 'psource-chat'),
            __('PS Chat', 'psource-chat'),
            'manage_chat',
            'psource-chat',
            [$this, 'render_dashboard_page'],
            'dashicons-format-chat',
            30
        );
        
        // Dashboard (same as main menu)
        add_submenu_page(
            'psource-chat',
            __('Dashboard', 'psource-chat'),
            __('Dashboard', 'psource-chat'),
            'manage_chat',
            'psource-chat',
            [$this, 'render_dashboard_page']
        );
        
        // Settings
        add_submenu_page(
            'psource-chat',
            __('Settings', 'psource-chat'),
            __('Settings', 'psource-chat'),
            'manage_options',
            'psource-chat-settings',
            [$this, 'render_settings_page']
        );
        
        // Extensions
        add_submenu_page(
            'psource-chat',
            __('Erweiterungen', 'psource-chat'),
            __('Erweiterungen', 'psource-chat'),
            'manage_options',
            'psource-chat-extensions',
            [$this, 'render_extensions_page']
        );
        
        // Active Sessions
        add_submenu_page(
            'psource-chat',
            __('Sessions', 'psource-chat'),
            __('Sessions', 'psource-chat'),
            'moderate_chat',
            'psource-chat-sessions',
            [$this, 'render_sessions_page']
        );
        
        // Message Logs
        add_submenu_page(
            'psource-chat',
            __('Logs', 'psource-chat'),
            __('Logs', 'psource-chat'),
            'view_chat_logs',
            'psource-chat-logs',
            [$this, 'render_logs_page']
        );
        
        // User Management
        add_submenu_page(
            'psource-chat',
            __('Users', 'psource-chat'),
            __('Users', 'psource-chat'),
            'moderate_chat',
            'psource-chat-users',
            [$this, 'render_users_page']
        );
        
        // Tools
        add_submenu_page(
            'psource-chat',
            __('Tools', 'psource-chat'),
            __('Tools', 'psource-chat'),
            'manage_options',
            'psource-chat-tools',
            [$this, 'render_tools_page']
        );
    }
    
    /**
     * Add network admin menu
     */
    public function add_network_admin_menu() {
        if (!current_user_can('manage_network')) {
            return;
        }
        
        add_menu_page(
            __('PS Chat Network', 'psource-chat'),
            __('PS Chat', 'psource-chat'),
            'manage_network',
            'psource-chat-network',
            [$this, 'render_network_dashboard_page'],
            'dashicons-format-chat',
            30
        );
        
        add_submenu_page(
            'psource-chat-network',
            __('Network Settings', 'psource-chat'),
            __('Settings', 'psource-chat'),
            'manage_network',
            'psource-chat-network-settings',
            [$this, 'render_network_settings_page']
        );
    }
    
    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        $dashboard = new Dashboard();
        $dashboard->render();
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        // Use Legacy Compatible Settings Page that matches original functionality
        static $settings_instance = null;
        if ($settings_instance === null) {
            $settings_instance = new \PSSource\Chat\Admin\Legacy_Compatible_Settings_Page();
        }
        $settings_instance->render_page();
    }
    
    /**
     * Render extensions page
     */
    public function render_extensions_page() {
        static $extensions_instance = null;
        if ($extensions_instance === null) {
            $extensions_instance = new \PSSource\Chat\Admin\Chat_Extensions();
        }
        $extensions_instance->render_extensions_page();
    }
    
    /**
     * Render active sessions page
     */
    public function render_sessions_page() {
        $sessions = new Sessions_Page();
        $sessions->render();
    }
    
    /**
     * Render logs page
     */
    public function render_logs_page() {
        $logs = new Logs_Page();
        $logs->render_page();
    }
    
    /**
     * Render users page
     */
    public function render_users_page() {
        $users = new Users_Page();
        $users->render_page();
    }
    
    /**
     * Render tools page
     */
    public function render_tools_page() {
        $tools = new Tools_Page();
        $tools->render_page();
    }
    
    /**
     * Render network dashboard page
     */
    public function render_network_dashboard_page() {
        $dashboard = new Network_Dashboard();
        $dashboard->render();
    }
    
    /**
     * Render network settings page
     */
    public function render_network_settings_page() {
        $settings = new Network_Settings();
        $settings->render();
    }
}
