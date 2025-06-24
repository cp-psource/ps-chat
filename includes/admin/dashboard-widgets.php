<?php
/**
 * Dashboard Widgets for PS Chat
 * 
 * @package PSSource\Chat\Admin
 */

namespace PSSource\Chat\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dashboard Widgets Class
 */
class Dashboard_Widgets {
    
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
        $this->options = $extension_options['dashboard'] ?? [];
        
        // Debug for dashboard widget activation
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('PS Chat Dashboard Widget - Options loaded: ' . print_r($this->options, true));
            error_log('PS Chat Dashboard Widget - widget_enabled status: ' . ($this->options['widget_enabled'] ?? 'not_set'));
        }
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Check if dashboard extension and main widget are enabled
        if (($this->options['widget_enabled'] ?? 'disabled') !== 'enabled') {
            return;
        }
        
        add_action('wp_dashboard_setup', [$this, 'setup_dashboard_widgets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_dashboard_scripts']);
    }
    
    /**
     * Setup dashboard widgets
     */
    public function setup_dashboard_widgets() {
        // Main chat widget
        if (($this->options['widget_enabled'] ?? 'disabled') === 'enabled') {
            wp_add_dashboard_widget(
                'psource_chat_dashboard_widget',
                $this->options['widget_title'] ?? __('Chat', 'psource-chat'),
                [$this, 'render_chat_widget']
            );
        }
        
        // Status widget
        if (($this->options['status_widget_enabled'] ?? 'disabled') === 'enabled') {
            wp_add_dashboard_widget(
                'psource_chat_status_widget',
                __('Chat Status', 'psource-chat'),
                [$this, 'render_status_widget']
            );
        }
        
        // Friends widget (if BuddyPress or PS Friends is available)
        if (($this->options['friends_widget_enabled'] ?? 'disabled') === 'enabled' && 
            (class_exists('BuddyPress') || function_exists('ps_friends_get_friends'))) {
            wp_add_dashboard_widget(
                'psource_chat_friends_widget',
                __('Online Freunde', 'psource-chat'),
                [$this, 'render_friends_widget']
            );
        }
    }
    
    /**
     * Enqueue dashboard scripts
     */
    public function enqueue_dashboard_scripts($hook) {
        if ($hook !== 'index.php') {
            return;
        }
        
        wp_enqueue_script(
            'psource-chat-dashboard',
            PSOURCE_CHAT_PLUGIN_URL . 'assets/js/dashboard.js',
            ['jquery'],
            PSOURCE_CHAT_VERSION,
            true
        );
        
        wp_enqueue_style(
            'psource-chat-dashboard',
            PSOURCE_CHAT_PLUGIN_URL . 'assets/css/dashboard.css',
            [],
            PSOURCE_CHAT_VERSION
        );
        
        wp_localize_script('psource-chat-dashboard', 'psourceChatDashboard', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('psource_chat_nonce'),
            'strings' => [
                'sendMessage' => __('Nachricht senden', 'psource-chat'),
                'typeMessage' => __('Nachricht eingeben...', 'psource-chat'),
                'noMessages' => __('Noch keine Nachrichten.', 'psource-chat'),
                'connectionError' => __('Verbindungsfehler', 'psource-chat'),
                'userOffline' => __('Offline', 'psource-chat'),
                'userOnline' => __('Online', 'psource-chat'),
                'userAway' => __('Abwesend', 'psource-chat'),
                'userBusy' => __('Beschäftigt', 'psource-chat')
            ]
        ]);
    }
    
    /**
     * Render chat widget
     */
    public function render_chat_widget() {
        $widget_height = $this->options['widget_height'] ?? '300px';
        ?>
        <div id="psource-chat-dashboard" class="psource-chat-widget" style="height: <?php echo esc_attr($widget_height); ?>;">
            <div class="chat-header">
                <div class="chat-users-count">
                    <span class="dashicons dashicons-groups"></span>
                    <span id="users-count">0</span> <?php _e('Online', 'psource-chat'); ?>
                </div>
                <div class="chat-controls">
                    <button type="button" id="chat-toggle-sound" class="button button-small" title="<?php esc_attr_e('Sound ein/aus', 'psource-chat'); ?>">
                        <span class="dashicons dashicons-controls-volumeon"></span>
                    </button>
                    <button type="button" id="chat-refresh" class="button button-small" title="<?php esc_attr_e('Aktualisieren', 'psource-chat'); ?>">
                        <span class="dashicons dashicons-update"></span>
                    </button>
                </div>
            </div>
            
            <div class="chat-messages" id="chat-messages">
                <div class="chat-loading">
                    <span class="dashicons dashicons-update spin"></span>
                    <?php _e('Lade Nachrichten...', 'psource-chat'); ?>
                </div>
            </div>
            
            <div class="chat-input-wrapper">
                <div class="chat-input-container">
                    <textarea id="chat-message-input" 
                              placeholder="<?php esc_attr_e('Nachricht eingeben...', 'psource-chat'); ?>"
                              rows="2"></textarea>
                    <button type="button" id="chat-send-button" class="button button-primary">
                        <span class="dashicons dashicons-arrow-up-alt2"></span>
                    </button>
                </div>
                <div class="chat-input-info">
                    <span id="char-counter">0/500</span>
                    <span class="chat-status-indicator">
                        <span class="status-dot status-online"></span>
                        <span id="connection-status"><?php _e('Verbunden', 'psource-chat'); ?></span>
                    </span>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render status widget
     */
    public function render_status_widget() {
        $current_user = wp_get_current_user();
        ?>
        <div id="psource-chat-status-widget" class="psource-chat-status">
            <div class="user-status-section">
                <div class="user-info">
                    <?php echo get_avatar($current_user->ID, 48); ?>
                    <div class="user-details">
                        <strong><?php echo esc_html($current_user->display_name); ?></strong>
                        <div class="status-selector">
                            <select id="user-status-select">
                                <option value="online"><?php _e('Online', 'psource-chat'); ?></option>
                                <option value="away"><?php _e('Abwesend', 'psource-chat'); ?></option>
                                <option value="busy"><?php _e('Beschäftigt', 'psource-chat'); ?></option>
                                <option value="invisible"><?php _e('Unsichtbar', 'psource-chat'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="status-message-section">
                <label for="status-message"><?php _e('Status-Nachricht:', 'psource-chat'); ?></label>
                <input type="text" id="status-message" 
                       placeholder="<?php esc_attr_e('Was machst du gerade?', 'psource-chat'); ?>"
                       maxlength="100"/>
                <button type="button" id="save-status" class="button button-small">
                    <?php _e('Speichern', 'psource-chat'); ?>
                </button>
            </div>
            
            <div class="quick-stats">
                <div class="stat-item">
                    <span class="dashicons dashicons-admin-comments"></span>
                    <span id="my-messages-count">0</span>
                    <small><?php _e('Heute gesendet', 'psource-chat'); ?></small>
                </div>
                <div class="stat-item">
                    <span class="dashicons dashicons-groups"></span>
                    <span id="online-friends-count">0</span>
                    <small><?php _e('Freunde online', 'psource-chat'); ?></small>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render friends widget
     */
    public function render_friends_widget() {
        ?>
        <div id="psource-chat-friends-widget" class="psource-chat-friends">
            <div class="friends-search">
                <input type="text" id="friends-search" 
                       placeholder="<?php esc_attr_e('Freunde suchen...', 'psource-chat'); ?>"/>
            </div>
            
            <div class="friends-list" id="friends-list">
                <div class="loading-friends">
                    <span class="dashicons dashicons-update spin"></span>
                    <?php _e('Lade Freunde...', 'psource-chat'); ?>
                </div>
            </div>
            
            <div class="friends-actions">
                <button type="button" id="refresh-friends" class="button button-small">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Aktualisieren', 'psource-chat'); ?>
                </button>
                <button type="button" id="start-group-chat" class="button button-small">
                    <span class="dashicons dashicons-groups"></span>
                    <?php _e('Gruppenchat', 'psource-chat'); ?>
                </button>
            </div>
        </div>
        <?php
    }
    
    /**
     * Check if user can control dashboard widgets
     */
    public function can_user_control_widgets() {
        return ($this->options['user_control'] ?? 'enabled') === 'enabled';
    }
    
    /**
     * Check if network mode is enabled
     */
    public function is_network_mode() {
        return ($this->options['network_mode'] ?? 'site') === 'network';
    }
}
