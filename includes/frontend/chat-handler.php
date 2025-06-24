<?php
/**
 * Frontend Chat Handler
 * 
 * @package PSSource\Chat\Frontend
 */

namespace PSSource\Chat\Frontend;

use PSSource\Chat\Core\Plugin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles frontend chat functionality
 */
class Chat_Handler {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_footer', [$this, 'maybe_render_chat']);
        add_action('admin_footer', [$this, 'maybe_render_admin_chat']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (!$this->should_show_chat()) {
            return;
        }
        
        // Enqueue CSS
        wp_enqueue_style(
            'psource-chat-frontend',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/css/frontend.css',
            [],
            '1.0.1'
        );
        
        // Enqueue dropdown menu CSS
        wp_enqueue_style(
            'psource-chat-dropdowns',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/css/chat-dropdowns.css',
            ['psource-chat-frontend'],
            '1.0.1'
        );
        
        // Enqueue jQuery (required for original functionality)
        wp_enqueue_script('jquery');
        
        // Enqueue our original Seitenkanten Chat JavaScript
        wp_enqueue_script(
            'psource-chat-seitenkanten',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/js/seitenkanten-chat.js',
            ['jquery'],
            '1.0.1',
            true
        );
        
        // Enqueue debug helper if debugging is enabled
        if (defined('WP_DEBUG') && WP_DEBUG || isset($_GET['ps_chat_debug'])) {
            wp_enqueue_script(
                'psource-chat-debug',
                plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/js/debug-helper.js',
                ['jquery', 'psource-chat-seitenkanten'],
                '1.0.1',
                true
            );
        }
        
        // Backward compatibility localization
        wp_localize_script('psource-chat-seitenkanten', 'psourceChatFrontend', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('psource_chat_nonce'),
            'userId' => get_current_user_id(),
            'debug' => (defined('WP_DEBUG') && WP_DEBUG) || isset($_GET['ps_chat_debug']),
            'extensionOptions' => get_option('psource_chat_extensions', []),
            'strings' => [
                'connecting' => __('Verbinde...', 'psource-chat'),
                'connected' => __('Verbunden', 'psource-chat'),
                'disconnected' => __('Verbindung unterbrochen', 'psource-chat'),
                'messageEmpty' => __('Nachricht darf nicht leer sein.', 'psource-chat'),
                'messageTooLong' => __('Nachricht zu lang.', 'psource-chat'),
                'connectionError' => __('Verbindungsfehler', 'psource-chat'),
                'noMessages' => __('Noch keine Nachrichten.', 'psource-chat'),
                'chat_title' => __('PS Chat', 'psource-chat'),
                'online' => __('Online', 'psource-chat'),
                'type_message' => __('Nachricht eingeben...', 'psource-chat')
            ]
        ]);
    }
    
    /**
     * Maybe render chat in footer
     */
    public function maybe_render_chat() {
        if (!$this->should_show_chat()) {
            return;
        }
        
        $renderer = new Chat_Renderer();
        $renderer->render_footer_chat();
    }
    
    /**
     * Maybe render admin chat
     */
    public function maybe_render_admin_chat() {
        if (!$this->should_show_admin_chat()) {
            return;
        }
        
        $renderer = new Chat_Renderer();
        $renderer->render_admin_chat();
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets() {
        if (!$this->should_show_admin_chat()) {
            return;
        }
        
        // Enqueue CSS for admin
        wp_enqueue_style(
            'psource-chat-admin-frontend',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/css/frontend.css',
            [],
            '1.0.1'
        );
        
        // Enqueue dropdown menu CSS for admin
        wp_enqueue_style(
            'psource-chat-admin-dropdowns',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/css/chat-dropdowns.css',
            ['psource-chat-admin-frontend'],
            '1.0.1'
        );
        
        // Enqueue jQuery (required for original functionality)
        wp_enqueue_script('jquery');
        
        // Enqueue our original Seitenkanten Chat JavaScript for admin
        wp_enqueue_script(
            'psource-chat-admin-seitenkanten',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/js/seitenkanten-chat.js',
            ['jquery'],
            '1.0.1',
            true
        );
        
        // Localization for admin
        wp_localize_script('psource-chat-admin-seitenkanten', 'psourceChatFrontend', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('psource_chat_nonce'),
            'userId' => get_current_user_id(),
            'isAdmin' => true,
            'strings' => [
                'connecting' => __('Verbinde...', 'psource-chat'),
                'connected' => __('Verbunden', 'psource-chat'),
                'disconnected' => __('Verbindung unterbrochen', 'psource-chat'),
                'messageEmpty' => __('Nachricht darf nicht leer sein.', 'psource-chat'),
                'messageTooLong' => __('Nachricht zu lang.', 'psource-chat'),
                'connectionError' => __('Verbindungsfehler', 'psource-chat'),
                'noMessages' => __('Noch keine Nachrichten.', 'psource-chat'),
                'chat_title' => __('PS Chat (Admin)', 'psource-chat'),
                'online' => __('Online', 'psource-chat'),
                'type_message' => __('Nachricht eingeben...', 'psource-chat')
            ]
        ]);
    }
    
    /**
     * Check if chat should be shown
     */
    private function should_show_chat() {
        // Don't show on admin pages
        if (is_admin()) {
            return false;
        }
        
        // Check if frontend extension is enabled
        $extension_options = get_option('psource_chat_extensions', []);
        $frontend_options = $extension_options['frontend'] ?? [];
        
        if (($frontend_options['enabled'] ?? 'disabled') !== 'enabled') {
            return false;
        }
        
        // Check if guest chat is allowed (if user is not logged in)
        if (!is_user_logged_in()) {
            $allow_guest_chat = $frontend_options['allow_guest_chat'] ?? 'no';
            if ($allow_guest_chat !== 'yes') {
                return false;
            }
        }
        
        // Allow filtering
        return apply_filters('psource_chat_show_frontend', true);
    }
     /**
     * Check if admin chat should be shown
     */
    private function should_show_admin_chat() {
        if (!is_admin()) {
            return false;
        }

        if (!current_user_can('read')) {
            return false;
        }
        
        // Check if frontend extension is enabled and allows admin display
        $extension_options = get_option('psource_chat_extensions', []);
        $frontend_options = $extension_options['frontend'] ?? [];
        
        if (($frontend_options['enabled'] ?? 'disabled') !== 'enabled') {
            return false;
        }
        
        if (($frontend_options['show_in_admin'] ?? 'no') !== 'yes') {
            return false;
        }

        return apply_filters('psource_chat_show_admin', true);
    }
}
