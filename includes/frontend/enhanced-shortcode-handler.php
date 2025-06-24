<?php
/**
 * Enhanced Shortcode Handler
 * 
 * @package PSSource\Chat\Frontend
 */

namespace PSSource\Chat\Frontend;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enhanced Shortcode Handler Class
 */
class Enhanced_Shortcode_Handler {
    
    private $options;
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', [$this, 'register_shortcodes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_shortcode_assets']);
        
        $this->options = get_option('psource_chat_options', []);
    }
    
    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('chat', [$this, 'render_chat_shortcode']);
        add_shortcode('psource-chat', [$this, 'render_chat_shortcode']);
        add_shortcode('ps-chat', [$this, 'render_chat_shortcode']);
    }
    
    /**
     * Enqueue shortcode assets
     */
    public function enqueue_shortcode_assets() {
        global $post;
        
        // Check if shortcode is used in current post
        if (is_a($post, 'WP_Post') && $this->has_chat_shortcode($post->post_content)) {
            wp_enqueue_script('psource-chat-frontend', PSOURCE_CHAT_URL . 'assets/js/frontend.js', ['jquery'], '1.0.0', true);
            wp_enqueue_style('psource-chat-frontend', PSOURCE_CHAT_URL . 'assets/css/frontend.css', [], '1.0.0');
            
            // Localize script
            wp_localize_script('psource-chat-frontend', 'PSChatShortcode', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('psource_chat_shortcode'),
                'rest_url' => rest_url('psource-chat/v1/'),
                'strings' => [
                    'loading' => __('Chat wird geladen...', 'psource-chat'),
                    'send' => __('Senden', 'psource-chat'),
                    'placeholder' => __('Nachricht eingeben...', 'psource-chat'),
                    'error' => __('Fehler beim Laden des Chats', 'psource-chat'),
                    'login_required' => __('Sie müssen angemeldet sein, um zu chatten.', 'psource-chat'),
                    'message_too_long' => __('Nachricht ist zu lang', 'psource-chat'),
                    'flood_protection' => __('Bitte warten Sie vor der nächsten Nachricht', 'psource-chat'),
                    'user_joined' => __('ist dem Chat beigetreten', 'psource-chat'),
                    'user_left' => __('hat den Chat verlassen', 'psource-chat'),
                    'moderator' => __('Moderator', 'psource-chat'),
                    'online_users' => __('Online Benutzer', 'psource-chat'),
                    'no_users' => __('Keine Benutzer online', 'psource-chat'),
                ]
            ]);
        }
    }
    
    /**
     * Check if post content has chat shortcode
     */
    private function has_chat_shortcode($content) {
        return (strpos($content, '[chat') !== false || 
                strpos($content, '[psource-chat') !== false || 
                strpos($content, '[ps-chat') !== false);
    }
    
    /**
     * Render chat shortcode
     */
    public function render_chat_shortcode($atts, $content = '') {
        // Parse shortcode attributes
        $atts = shortcode_atts([
            'id' => '',
            'title' => $this->get_option('box_title', 'Chat'),
            'height' => $this->get_option('shortcode_default_height', '400px'),
            'width' => $this->get_option('shortcode_default_width', '100%'),
            'sound' => $this->get_option('shortcode_sound', true) ? 'true' : 'false',
            'emoticons' => $this->get_option('shortcode_emoticons', true) ? 'true' : 'false',
            'userlist' => $this->get_option('shortcode_user_list', true) ? 'true' : 'false',
            'avatar' => $this->get_option('row_name_avatar', 'avatar'),
            'date' => $this->get_option('row_date', 'disabled'),
            'time' => $this->get_option('row_time', 'enabled'),
            'guests' => $this->get_option('shortcode_allow_guests', false) ? 'true' : 'false',
            'moderation' => $this->get_option('shortcode_moderation', false) ? 'true' : 'false',
            'theme' => 'default',
            'session' => '',
            'private' => 'false',
            'invite' => '',
        ], $atts);
        
        // Generate unique chat ID if not provided
        if (empty($atts['id'])) {
            global $post;
            $post_id = $post ? $post->ID : 0;
            $atts['id'] = 'page-' . $post_id . '-' . uniqid();
        }
        
        // Check if shortcodes are enabled
        if (!$this->get_option('enable_shortcode', true)) {
            return '<p><em>' . __('Chat-Shortcodes sind deaktiviert.', 'psource-chat') . '</em></p>';
        }
        
        // Check user permissions
        if (!$this->user_can_access_chat($atts)) {
            return $this->render_access_denied_message();
        }
        
        // Check for blocked URLs if configured
        if ($this->is_url_blocked()) {
            return '';
        }
        
        return $this->render_chat_box($atts);
    }
    
    /**
     * Check if user can access chat
     */
    private function user_can_access_chat($atts) {
        $current_user = wp_get_current_user();
        
        // Check if guests are allowed
        if (!$current_user->ID) {
            if ($atts['guests'] === 'true' || $this->get_option('allow_guest_chat', false)) {
                return true;
            }
            return false;
        }
        
        // Check user roles
        $allowed_roles = $this->get_option('login_options', ['administrator', 'editor']);
        if (is_array($allowed_roles) && !empty($allowed_roles)) {
            $user_roles = $current_user->roles;
            return !empty(array_intersect($allowed_roles, $user_roles));
        }
        
        return true;
    }
    
    /**
     * Check if current URL is blocked
     */
    private function is_url_blocked() {
        $blocked_urls = $this->get_option('blocked_urls', '');
        if (empty($blocked_urls)) {
            return false;
        }
        
        $current_url = $_SERVER['REQUEST_URI'];
        $blocked_array = explode("\n", $blocked_urls);
        
        foreach ($blocked_array as $blocked_url) {
            $blocked_url = trim($blocked_url);
            if (!empty($blocked_url) && strpos($current_url, $blocked_url) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Render access denied message
     */
    private function render_access_denied_message() {
        $current_user = wp_get_current_user();
        
        if (!$current_user->ID) {
            return sprintf(
                '<div class="psource-chat-access-denied"><p>%s <a href="%s">%s</a></p></div>',
                __('Sie müssen angemeldet sein, um zu chatten.', 'psource-chat'),
                wp_login_url(get_permalink()),
                __('Anmelden', 'psource-chat')
            );
        } else {
            return '<div class="psource-chat-access-denied"><p>' . 
                   __('Sie haben keine Berechtigung für diesen Chat.', 'psource-chat') . 
                   '</p></div>';
        }
    }
    
    /**
     * Render chat box
     */
    private function render_chat_box($atts) {
        $current_user = wp_get_current_user();
        $is_logged_in = $current_user && $current_user->ID;
        $is_moderator = $this->is_user_moderator($current_user);
        
        // Prepare chat configuration
        $chat_config = [
            'id' => $atts['id'],
            'title' => $atts['title'],
            'height' => $atts['height'],
            'width' => $atts['width'],
            'sound' => $atts['sound'] === 'true',
            'emoticons' => $atts['emoticons'] === 'true',
            'userlist' => $atts['userlist'] === 'true',
            'avatar' => $atts['avatar'],
            'show_date' => $atts['date'] === 'enabled',
            'show_time' => $atts['time'] === 'enabled',
            'guests_allowed' => $atts['guests'] === 'true',
            'moderation' => $atts['moderation'] === 'true',
            'theme' => $atts['theme'],
            'session' => $atts['session'],
            'private' => $atts['private'] === 'true',
            'invite' => $atts['invite'],
            'max_message_length' => $this->get_option('max_message_length', 500),
            'auto_refresh_interval' => $this->get_option('auto_refresh_interval', 5),
        ];
        
        ob_start();
        ?>
        <div class="psource-chat-shortcode-container" 
             style="height: <?php echo esc_attr($atts['height']); ?>; width: <?php echo esc_attr($atts['width']); ?>;">
            
            <div class="psource-chat-box shortcode-chat <?php echo esc_attr($atts['theme']); ?>" 
                 id="<?php echo esc_attr($atts['id']); ?>"
                 data-chat-config="<?php echo esc_attr(json_encode($chat_config)); ?>">
                
                <!-- Chat Header -->
                <div class="psource-chat-header">
                    <div class="chat-title">
                        <h3><?php echo esc_html($atts['title']); ?></h3>
                    </div>
                    
                    <div class="chat-status">
                        <span class="status-indicator" title="<?php _e('Chat Status', 'psource-chat'); ?>"></span>
                        <span class="user-count">0</span> <?php _e('online', 'psource-chat'); ?>
                    </div>
                    
                    <div class="chat-controls">
                        <?php if ($atts['userlist'] === 'true'): ?>
                            <button type="button" class="users-toggle" title="<?php _e('Benutzer anzeigen/verbergen', 'psource-chat'); ?>">
                                👥
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($atts['sound'] === 'true'): ?>
                            <button type="button" class="sound-toggle active" title="<?php _e('Sound an/aus', 'psource-chat'); ?>">
                                🔊
                            </button>
                        <?php endif; ?>
                        
                        <button type="button" class="fullscreen-toggle" title="<?php _e('Vollbild', 'psource-chat'); ?>">
                            ⛶
                        </button>
                        
                        <?php if ($is_moderator): ?>
                            <button type="button" class="clear-chat" title="<?php _e('Chat leeren', 'psource-chat'); ?>">
                                🗑️
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Messages Area -->
                <div class="psource-chat-messages" id="<?php echo esc_attr($atts['id']); ?>-messages">
                    <div class="chat-loading">
                        <p><em><?php _e('Chat wird geladen...', 'psource-chat'); ?></em></p>
                    </div>
                </div>
                
                <!-- User List (collapsible) -->
                <?php if ($atts['userlist'] === 'true'): ?>
                    <div class="psource-chat-users" id="<?php echo esc_attr($atts['id']); ?>-users" style="display: none;">
                        <h4><?php _e('Online Benutzer', 'psource-chat'); ?></h4>
                        <ul class="user-list"></ul>
                    </div>
                <?php endif; ?>
                
                <!-- Input Area -->
                <div class="psource-chat-input-area">
                    <?php if ($is_logged_in || $atts['guests'] === 'true'): ?>
                        <?php if ($is_logged_in): ?>
                            <div class="chat-user-info">
                                <?php if ($atts['avatar'] === 'avatar' || $atts['avatar'] === 'name-avatar'): ?>
                                    <?php echo get_avatar($current_user->ID, 32); ?>
                                <?php endif; ?>
                                
                                <?php if ($atts['avatar'] === 'name' || $atts['avatar'] === 'name-avatar'): ?>
                                    <span class="username"><?php echo esc_html($current_user->display_name); ?></span>
                                <?php endif; ?>
                                
                                <?php if ($is_moderator): ?>
                                    <span class="moderator-badge" title="<?php _e('Moderator', 'psource-chat'); ?>">★</span>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <!-- Guest user input -->
                            <div class="chat-guest-info">
                                <input type="text" class="guest-name-input" placeholder="<?php _e('Ihr Name...', 'psource-chat'); ?>" maxlength="50" />
                            </div>
                        <?php endif; ?>
                        
                        <div class="chat-input-controls">
                            <div class="input-wrapper">
                                <input type="text" 
                                       class="chat-message-input" 
                                       id="<?php echo esc_attr($atts['id']); ?>-input"
                                       placeholder="<?php _e('Nachricht eingeben...', 'psource-chat'); ?>" 
                                       maxlength="<?php echo intval($chat_config['max_message_length']); ?>" />
                                
                                <?php if ($atts['emoticons'] === 'true'): ?>
                                    <button type="button" class="emoji-button" title="<?php _e('Emojis', 'psource-chat'); ?>">
                                        😀
                                    </button>
                                <?php endif; ?>
                                
                                <button type="button" 
                                        class="chat-send-button" 
                                        id="<?php echo esc_attr($atts['id']); ?>-send">
                                    <?php _e('Senden', 'psource-chat'); ?>
                                </button>
                            </div>
                            
                            <!-- Character counter -->
                            <div class="character-counter">
                                <span class="current-length">0</span>/<span class="max-length"><?php echo intval($chat_config['max_message_length']); ?></span>
                            </div>
                        </div>
                        
                        <!-- Emoji Picker (hidden by default) -->
                        <?php if ($atts['emoticons'] === 'true'): ?>
                            <div class="emoji-picker" style="display: none;">
                                <div class="emoji-grid">
                                    <?php
                                    $emojis = ['😀', '😃', '😄', '😁', '😆', '😅', '😂', '🤣', '😊', '😇', 
                                              '🙂', '🙃', '😉', '😌', '😍', '🥰', '😘', '😗', '😙', '😚',
                                              '😋', '😛', '😝', '😜', '🤪', '🤨', '🧐', '🤓', '😎', '🤩',
                                              '👍', '👎', '👌', '🤝', '👏', '🙌', '👋', '💪', '❤️', '💔'];
                                    foreach ($emojis as $emoji) {
                                        echo '<span class="emoji-item" data-emoji="' . esc_attr($emoji) . '">' . $emoji . '</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="chat-login-required">
                            <p><?php _e('Sie müssen angemeldet sein, um zu chatten.', 'psource-chat'); ?></p>
                            <a href="<?php echo wp_login_url(get_permalink()); ?>" class="button">
                                <?php _e('Anmelden', 'psource-chat'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Chat Archive Link (if enabled) -->
        <?php if ($this->get_option('log_display', 'disabled') !== 'disabled'): ?>
            <div class="psource-chat-archive-link">
                <?php
                $log_display = $this->get_option('log_display');
                $log_label = $this->get_option('log_display_label', 'Chat Archive');
                $archive_url = add_query_arg(['chat_archive' => $atts['id']], get_permalink());
                
                if (strpos($log_display, 'link') !== false) {
                    echo '<a href="' . esc_url($archive_url) . '">' . esc_html($log_label) . '</a>';
                }
                ?>
            </div>
        <?php endif; ?>
        
        <script>
        jQuery(document).ready(function($) {
            // Initialize shortcode chat
            if (typeof PSChatShortcode !== 'undefined') {
                PSChatShortcode.initChat('<?php echo esc_js($atts['id']); ?>');
            }
        });
        </script>
        
        <?php
        return ob_get_clean();
    }
    
    /**
     * Check if user is moderator
     */
    private function is_user_moderator($user) {
        if (!$user || !$user->ID) {
            return false;
        }
        
        $moderator_roles = $this->get_option('moderator_roles', ['administrator']);
        if (!is_array($moderator_roles)) {
            $moderator_roles = ['administrator'];
        }
        
        $user_roles = $user->roles;
        return !empty(array_intersect($moderator_roles, $user_roles));
    }
    
    /**
     * Get option with fallback
     */
    private function get_option($key, $default = '') {
        return isset($this->options[$key]) ? $this->options[$key] : $default;
    }
}

// Initialize Enhanced Shortcode Handler
new Enhanced_Shortcode_Handler();
