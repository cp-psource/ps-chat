<?php
/**
 * Admin Bar Chat Integration
 * 
 * @package PSSource\Chat\Frontend
 */

namespace PSSource\Chat\Frontend;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Bar Chat Class
 */
class Admin_Bar_Chat {
    
    /**
     * Extension options
     */
    private $options = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->load_options();
        $this->init_hooks();
    }
    
    /**
     * Load options
     */
    private function load_options() {
        $extension_options = get_option('psource_chat_extensions', []);
        $this->options = $extension_options['admin_bar'] ?? [];
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        if (($this->options['enabled'] ?? 'disabled') === 'enabled') {
            add_action('admin_bar_menu', [$this, 'add_admin_bar_menu'], 999);
            add_action('wp_enqueue_scripts', [$this, 'enqueue_admin_bar_scripts']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_bar_scripts']);
            add_action('wp_footer', [$this, 'render_admin_bar_chat']);
            add_action('admin_footer', [$this, 'render_admin_bar_chat']);
        }
    }
    
    /**
     * Add admin bar menu
     */
    public function add_admin_bar_menu($wp_admin_bar) {
        if (!is_user_logged_in()) {
            return;
        }
        
        $user_id = get_current_user_id();
        $unread_count = $this->get_unread_message_count($user_id);
        
        // Main chat menu
        $wp_admin_bar->add_node([
            'id' => 'ps-chat',
            'title' => $this->get_chat_menu_title($unread_count),
            'href' => '#',
            'meta' => [
                'class' => 'ps-chat-admin-bar-item',
                'title' => __('PS Chat', 'psource-chat')
            ]
        ]);
        
        // Quick status submenu
        $wp_admin_bar->add_node([
            'id' => 'ps-chat-status',
            'parent' => 'ps-chat',
            'title' => __('Status ändern', 'psource-chat'),
            'href' => '#',
            'meta' => [
                'class' => 'ps-chat-status-menu'
            ]
        ]);
        
        // Status options
        $statuses = [
            'online' => __('Online', 'psource-chat'),
            'away' => __('Abwesend', 'psource-chat'),
            'busy' => __('Beschäftigt', 'psource-chat'),
            'invisible' => __('Unsichtbar', 'psource-chat')
        ];
        
        foreach ($statuses as $status => $label) {
            $wp_admin_bar->add_node([
                'id' => 'ps-chat-status-' . $status,
                'parent' => 'ps-chat-status',
                'title' => '<span class="status-indicator status-' . $status . '"></span> ' . $label,
                'href' => '#',
                'meta' => [
                    'class' => 'ps-chat-status-option',
                    'data-status' => $status
                ]
            ]);
        }
        
        // Online friends submenu (if available)
        if ($this->has_friends_support()) {
            $online_friends = $this->get_online_friends($user_id);
            
            $wp_admin_bar->add_node([
                'id' => 'ps-chat-friends',
                'parent' => 'ps-chat',
                'title' => sprintf(__('Online Freunde (%d)', 'psource-chat'), count($online_friends)),
                'href' => '#',
                'meta' => [
                    'class' => 'ps-chat-friends-menu'
                ]
            ]);
            
            if (!empty($online_friends)) {
                foreach ($online_friends as $friend) {
                    $wp_admin_bar->add_node([
                        'id' => 'ps-chat-friend-' . $friend->ID,
                        'parent' => 'ps-chat-friends',
                        'title' => $this->get_friend_menu_item($friend),
                        'href' => '#',
                        'meta' => [
                            'class' => 'ps-chat-friend-item',
                            'data-user-id' => $friend->ID
                        ]
                    ]);
                }
            } else {
                $wp_admin_bar->add_node([
                    'id' => 'ps-chat-no-friends',
                    'parent' => 'ps-chat-friends',
                    'title' => __('Keine Freunde online', 'psource-chat'),
                    'href' => '#',
                    'meta' => [
                        'class' => 'ps-chat-no-friends'
                    ]
                ]);
            }
        }
        
        // Quick chat toggle
        $wp_admin_bar->add_node([
            'id' => 'ps-chat-toggle',
            'parent' => 'ps-chat',
            'title' => __('Chat öffnen/schließen', 'psource-chat'),
            'href' => '#',
            'meta' => [
                'class' => 'ps-chat-toggle-menu'
            ]
        ]);
        
        // Settings link (for admins)
        if (current_user_can('manage_options')) {
            $wp_admin_bar->add_node([
                'id' => 'ps-chat-settings',
                'parent' => 'ps-chat',
                'title' => __('Chat Einstellungen', 'psource-chat'),
                'href' => admin_url('admin.php?page=psource-chat-settings'),
                'meta' => [
                    'class' => 'ps-chat-settings-menu'
                ]
            ]);
        }
    }
    
    /**
     * Get chat menu title with notification badge
     */
    private function get_chat_menu_title($unread_count) {
        $title = '<span class="ab-icon dashicons dashicons-format-chat"></span>';
        $title .= '<span class="ab-label">' . __('Chat', 'psource-chat') . '</span>';
        
        if ($unread_count > 0) {
            $title .= '<span class="ps-chat-notification-badge">' . $unread_count . '</span>';
        }
        
        return $title;
    }
    
    /**
     * Get friend menu item HTML
     */
    private function get_friend_menu_item($friend) {
        $avatar = get_avatar($friend->ID, 20);
        $status = $this->get_user_status($friend->ID);
        $status_class = 'status-' . $status;
        
        return $avatar . ' <span class="friend-name">' . esc_html($friend->display_name) . '</span>' .
               '<span class="friend-status ' . $status_class . '"></span>';
    }
    
    /**
     * Enqueue admin bar scripts
     */
    public function enqueue_admin_bar_scripts() {
        if (!is_admin_bar_showing() || !is_user_logged_in()) {
            return;
        }
        
        wp_enqueue_script(
            'psource-chat-admin-bar',
            PSOURCE_CHAT_PLUGIN_URL . 'assets/js/admin-bar.js',
            ['jquery'],
            PSOURCE_CHAT_VERSION,
            true
        );
        
        wp_enqueue_style(
            'psource-chat-admin-bar',
            PSOURCE_CHAT_PLUGIN_URL . 'assets/css/admin-bar.css',
            [],
            PSOURCE_CHAT_VERSION
        );
        
        wp_localize_script('psource-chat-admin-bar', 'psourceChatAdminBar', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('psource_chat_nonce'),
            'userId' => get_current_user_id(),
            'strings' => [
                'statusChanged' => __('Status wurde geändert', 'psource-chat'),
                'statusError' => __('Fehler beim Ändern des Status', 'psource-chat'),
                'startPrivateChat' => __('Privaten Chat starten', 'psource-chat'),
                'viewProfile' => __('Profil anzeigen', 'psource-chat'),
                'newMessage' => __('Neue Nachricht', 'psource-chat'),
                'chatOpened' => __('Chat geöffnet', 'psource-chat'),
                'chatClosed' => __('Chat geschlossen', 'psource-chat')
            ]
        ]);
    }
    
    /**
     * Render admin bar chat popup
     */
    public function render_admin_bar_chat() {
        if (!is_admin_bar_showing() || !is_user_logged_in()) {
            return;
        }
        ?>
        <div id="ps-chat-admin-bar-popup" class="ps-chat-admin-bar-popup" style="display: none;">
            <div class="chat-popup-header">
                <h4><?php _e('Quick Chat', 'psource-chat'); ?></h4>
                <button type="button" class="chat-popup-close">
                    <span class="dashicons dashicons-no"></span>
                </button>
            </div>
            
            <div class="chat-popup-content">
                <div class="chat-popup-messages" id="admin-bar-chat-messages">
                    <div class="loading-messages">
                        <span class="dashicons dashicons-update spin"></span>
                        <?php _e('Lade Nachrichten...', 'psource-chat'); ?>
                    </div>
                </div>
                
                <div class="chat-popup-input">
                    <textarea id="admin-bar-chat-input" 
                              placeholder="<?php esc_attr_e('Nachricht eingeben...', 'psource-chat'); ?>"
                              rows="2"></textarea>
                    <button type="button" id="admin-bar-chat-send" class="button button-primary">
                        <span class="dashicons dashicons-arrow-up-alt2"></span>
                    </button>
                </div>
                
                <div class="chat-popup-status">
                    <span class="connection-status" id="admin-bar-connection-status">
                        <span class="status-dot"></span>
                        <span class="status-text"><?php _e('Verbinde...', 'psource-chat'); ?></span>
                    </span>
                    <span class="char-counter" id="admin-bar-char-counter">0/500</span>
                </div>
            </div>
        </div>
        
        <style>
        .ps-chat-admin-bar-popup {
            position: fixed;
            top: 32px;
            right: 20px;
            width: 350px;
            height: 400px;
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.15);
            z-index: 99999;
        }
        
        .chat-popup-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            border-bottom: 1px solid #ddd;
            background: #f8f9fa;
        }
        
        .chat-popup-header h4 {
            margin: 0;
            font-size: 14px;
        }
        
        .chat-popup-close {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            color: #666;
        }
        
        .chat-popup-content {
            height: calc(100% - 50px);
            display: flex;
            flex-direction: column;
        }
        
        .chat-popup-messages {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
            background: #f9f9f9;
        }
        
        .chat-popup-input {
            display: flex;
            padding: 10px;
            border-top: 1px solid #ddd;
        }
        
        .chat-popup-input textarea {
            flex: 1;
            margin-right: 10px;
            resize: none;
        }
        
        .chat-popup-status {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px 10px;
            background: #f8f9fa;
            border-top: 1px solid #ddd;
            font-size: 11px;
            color: #666;
        }
        
        .ps-chat-notification-badge {
            display: inline-block;
            background: #d63638;
            color: white;
            border-radius: 10px;
            padding: 2px 6px;
            font-size: 10px;
            line-height: 1;
            margin-left: 5px;
            min-width: 16px;
            text-align: center;
        }
        
        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 5px;
        }
        
        .status-indicator.status-online { background: #46b450; }
        .status-indicator.status-away { background: #ffb900; }
        .status-indicator.status-busy { background: #d63638; }
        .status-indicator.status-invisible { background: #82878c; }
        
        @media screen and (max-width: 782px) {
            .ps-chat-admin-bar-popup {
                top: 46px;
                right: 10px;
                width: calc(100% - 20px);
                max-width: 350px;
            }
        }
        </style>
        <?php
    }
    
    /**
     * Get unread message count for user
     */
    private function get_unread_message_count($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'psource_chat_messages';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} 
             WHERE recipient_id = %d AND read_status = 0 AND message_type = 'private'",
            $user_id
        ));
        
        return intval($count);
    }
    
    /**
     * Check if friends support is available
     */
    private function has_friends_support() {
        return class_exists('BuddyPress') || function_exists('ps_friends_get_friends');
    }
    
    /**
     * Get online friends for user
     */
    private function get_online_friends($user_id) {
        $friends = [];
        
        if (class_exists('BuddyPress') && function_exists('friends_get_friend_user_ids')) {
            $friend_ids = friends_get_friend_user_ids($user_id);
            if (!empty($friend_ids)) {
                $friends = get_users([
                    'include' => $friend_ids,
                    'fields' => ['ID', 'display_name']
                ]);
            }
        } elseif (function_exists('ps_friends_get_friends')) {
            $friends = ps_friends_get_friends($user_id);
        }
        
        // Filter only online friends
        $online_friends = [];
        foreach ($friends as $friend) {
            if ($this->is_user_online($friend->ID)) {
                $online_friends[] = $friend;
            }
        }
        
        return $online_friends;
    }
    
    /**
     * Check if user is online
     */
    private function is_user_online($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'psource_chat_users';
        
        $timeout = 300; // 5 minutes
        $cutoff_time = date('Y-m-d H:i:s', time() - $timeout);
        
        $is_online = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} 
             WHERE user_id = %d AND last_activity > %s",
            $user_id,
            $cutoff_time
        ));
        
        return intval($is_online) > 0;
    }
    
    /**
     * Get user status
     */
    private function get_user_status($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'psource_chat_users';
        
        $status = $wpdb->get_var($wpdb->prepare(
            "SELECT status FROM {$table_name} WHERE user_id = %d",
            $user_id
        ));
        
        return $status ?: 'online';
    }
}
