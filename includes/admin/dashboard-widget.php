<?php
/**
 * Dashboard Widget
 * 
 * @package PSSource\Chat\Admin
 */

namespace PSSource\Chat\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dashboard Widget Class
 */
class Dashboard_Widget {
    
    private $options;
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_dashboard_setup', [$this, 'add_dashboard_widget']);
        add_action('wp_network_dashboard_setup', [$this, 'add_dashboard_widget']);
        
        $this->options = get_option('psource_chat_options', []);
    }
    
    /**
     * Add dashboard widget
     */
    public function add_dashboard_widget() {
        global $current_user;
        
        // Check if dashboard widget is enabled
        if (!$this->is_dashboard_widget_enabled()) {
            return;
        }
        
        // Check user permissions
        if (!$this->user_can_access_dashboard_widget()) {
            return;
        }
        
        $widget_title = $this->get_option('dashboard_widget_title', 'Chat');
        
        wp_add_dashboard_widget(
            'psource_chat_dashboard_widget',
            $widget_title,
            [$this, 'render_dashboard_widget'],
            [$this, 'render_dashboard_widget_controls']
        );
    }
    
    /**
     * Check if dashboard widget is enabled
     */
    private function is_dashboard_widget_enabled() {
        return $this->get_option('dashboard_widget', 'enabled') === 'enabled';
    }
    
    /**
     * Check if user can access dashboard widget
     */
    private function user_can_access_dashboard_widget() {
        global $current_user;
        
        if (!$current_user || !$current_user->ID) {
            return false;
        }
        
        // Check if user has admin capabilities or is in allowed roles
        if (current_user_can('manage_options')) {
            return true;
        }
        
        $allowed_roles = $this->get_option('dashboard_widget_roles', ['administrator', 'editor']);
        if (!is_array($allowed_roles)) {
            $allowed_roles = ['administrator'];
        }
        
        $user_roles = $current_user->roles;
        return !empty(array_intersect($allowed_roles, $user_roles));
    }
    
    /**
     * Render dashboard widget
     */
    public function render_dashboard_widget() {
        $widget_height = $this->get_option('dashboard_widget_height', '300px');
        $chat_id = 'dashboard-' . get_current_blog_id();
        
        ?>
        <div id="psource-chat-dashboard-container" style="height: <?php echo esc_attr($widget_height); ?>;">
            <?php $this->render_chat_box($chat_id); ?>
        </div>
        
        <style>
            #psource_chat_dashboard_widget .inside { 
                padding: 0px; 
                margin-top: 0;
            }
            #psource_chat_dashboard_widget .inside .psource-chat-box { 
                border: 0px;
                height: 100%;
            }
            #psource-chat-dashboard-container {
                min-height: 250px;
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Initialize dashboard chat
            PSChatDashboard.init('<?php echo esc_js($chat_id); ?>');
        });
        </script>
        <?php
    }
    
    /**
     * Render dashboard widget controls
     */
    public function render_dashboard_widget_controls() {
        ?>
        <p>
            <label for="chat-dashboard-height">
                <strong><?php _e('Chat Höhe:', 'psource-chat'); ?></strong>
            </label>
            <input type="text" id="chat-dashboard-height" name="chat_dashboard_height" 
                   value="<?php echo esc_attr($this->get_option('dashboard_widget_height', '300px')); ?>" 
                   placeholder="300px" style="width: 80px;" />
            <span class="description"><?php _e('z.B. 300px oder 20em', 'psource-chat'); ?></span>
        </p>
        
        <p>
            <input type="checkbox" id="chat-dashboard-archive" name="chat_dashboard_archive" value="1" 
                   <?php checked($this->get_option('dashboard_widget_archive', false)); ?> />
            <label for="chat-dashboard-archive">
                <?php _e('Chat-Nachrichten archivieren', 'psource-chat'); ?>
            </label>
        </p>
        
        <p>
            <input type="checkbox" id="chat-dashboard-sound" name="chat_dashboard_sound" value="1" 
                   <?php checked($this->get_option('dashboard_widget_sound', true)); ?> />
            <label for="chat-dashboard-sound">
                <?php _e('Sound-Benachrichtigungen aktivieren', 'psource-chat'); ?>
            </label>
        </p>
        <?php
    }
    
    /**
     * Render chat box
     */
    private function render_chat_box($chat_id) {
        $user = wp_get_current_user();
        $is_moderator = current_user_can('manage_options');
        
        ?>
        <div class="psource-chat-box dashboard-chat" id="<?php echo esc_attr($chat_id); ?>">
            <!-- Chat Header -->
            <div class="psource-chat-header">
                <div class="chat-title">
                    <strong><?php echo esc_html($this->get_option('dashboard_widget_title', 'Dashboard Chat')); ?></strong>
                </div>
                <div class="chat-status">
                    <span class="status-indicator online" title="<?php _e('Online', 'psource-chat'); ?>"></span>
                    <span class="user-count">0</span> <?php _e('Benutzer', 'psource-chat'); ?>
                </div>
            </div>
            
            <!-- Messages Area -->
            <div class="psource-chat-messages" id="<?php echo esc_attr($chat_id); ?>-messages">
                <div class="chat-loading">
                    <p><em><?php _e('Chat wird geladen...', 'psource-chat'); ?></em></p>
                </div>
            </div>
            
            <!-- User List (minimized by default) -->
            <div class="psource-chat-users" id="<?php echo esc_attr($chat_id); ?>-users" style="display: none;">
                <h4><?php _e('Online Benutzer', 'psource-chat'); ?></h4>
                <ul class="user-list"></ul>
            </div>
            
            <!-- Input Area -->
            <div class="psource-chat-input-area">
                <?php if ($user && $user->ID): ?>
                    <div class="chat-user-info">
                        <?php echo get_avatar($user->ID, 24); ?>
                        <span class="username"><?php echo esc_html($user->display_name); ?></span>
                        <?php if ($is_moderator): ?>
                            <span class="moderator-badge" title="<?php _e('Moderator', 'psource-chat'); ?>">★</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="chat-input-controls">
                        <div class="input-wrapper">
                            <input type="text" 
                                   class="chat-message-input" 
                                   id="<?php echo esc_attr($chat_id); ?>-input"
                                   placeholder="<?php _e('Nachricht eingeben...', 'psource-chat'); ?>" 
                                   maxlength="<?php echo intval($this->get_option('max_message_length', 500)); ?>" />
                            <button type="button" 
                                    class="chat-send-button" 
                                    id="<?php echo esc_attr($chat_id); ?>-send">
                                <?php _e('Senden', 'psource-chat'); ?>
                            </button>
                        </div>
                        
                        <div class="chat-controls">
                            <?php if ($this->get_option('enable_emoji', true)): ?>
                                <button type="button" class="emoji-button" title="<?php _e('Emojis', 'psource-chat'); ?>">
                                    😀
                                </button>
                            <?php endif; ?>
                            
                            <button type="button" class="users-toggle" title="<?php _e('Benutzer anzeigen/verbergen', 'psource-chat'); ?>">
                                👥
                            </button>
                            
                            <?php if ($this->get_option('enable_sound', true)): ?>
                                <button type="button" class="sound-toggle active" title="<?php _e('Sound an/aus', 'psource-chat'); ?>">
                                    🔊
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($is_moderator): ?>
                                <button type="button" class="clear-chat" title="<?php _e('Chat leeren', 'psource-chat'); ?>">
                                    🗑️
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="chat-login-required">
                        <p><?php _e('Sie müssen angemeldet sein, um zu chatten.', 'psource-chat'); ?></p>
                        <a href="<?php echo wp_login_url(admin_url()); ?>" class="button">
                            <?php _e('Anmelden', 'psource-chat'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get option with fallback
     */
    private function get_option($key, $default = '') {
        // Check dashboard-specific options first
        if (isset($this->options['dashboard'][$key])) {
            return $this->options['dashboard'][$key];
        }
        
        // Check global options
        if (isset($this->options[$key])) {
            return $this->options[$key];
        }
        
        return $default;
    }
}

// Initialize Dashboard Widget
new Dashboard_Widget();
