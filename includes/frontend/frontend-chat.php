<?php
/**
 * Frontend Chat Integration
 * 
 * @package PSSource\Chat\Frontend
 */

namespace PSSource\Chat\Frontend;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Frontend Chat Class
 */
class Frontend_Chat {
    
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
        $this->options = $extension_options['frontend'] ?? [];
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
        add_action('wp_footer', [$this, 'render_chat_interface']);
        
        // Auto-embed in content
        $auto_embed = $this->options['auto_embed'] ?? 'disabled';
        if ($auto_embed !== 'disabled') {
            add_filter('the_content', [$this, 'auto_embed_chat']);
        }
        
        // Shortcode support
        add_shortcode('ps_chat', [$this, 'chat_shortcode']);
        add_shortcode('ps_chat_status', [$this, 'status_shortcode']);
        add_shortcode('ps_chat_users', [$this, 'users_shortcode']);
        add_shortcode('ps_chat_rooms', [$this, 'rooms_shortcode']);
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts() {
        if (!is_admin()) {
            wp_enqueue_script(
                'psource-chat-frontend',
                PSOURCE_CHAT_PLUGIN_URL . 'assets/js/frontend.js',
                ['jquery'],
                PSOURCE_CHAT_VERSION,
                true
            );
            
            wp_enqueue_style(
                'psource-chat-frontend',
                PSOURCE_CHAT_PLUGIN_URL . 'assets/css/enhanced-frontend.css',
                [],
                PSOURCE_CHAT_VERSION
            );
            
            wp_localize_script('psource-chat-frontend', 'psourceChatFrontend', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('psource_chat_nonce'),
                'userId' => get_current_user_id(),
                'isLoggedIn' => is_user_logged_in(),
                'pollingInterval' => 3000,
                'heartbeatInterval' => 30000,
                'maxMessageLength' => 500,
                'strings' => [
                    'sendMessage' => __('Nachricht senden', 'psource-chat'),
                    'typeMessage' => __('Nachricht eingeben...', 'psource-chat'),
                    'loginRequired' => __('Du musst eingeloggt sein, um zu chatten.', 'psource-chat'),
                    'messageEmpty' => __('Nachricht darf nicht leer sein.', 'psource-chat'),
                    'messageTooLong' => __('Nachricht zu lang.', 'psource-chat'),
                    'connectionError' => __('Verbindungsfehler', 'psource-chat'),
                    'connecting' => __('Verbinde...', 'psource-chat'),
                    'connected' => __('Verbunden', 'psource-chat'),
                    'disconnected' => __('Verbindung unterbrochen', 'psource-chat'),
                    'noMessages' => __('Noch keine Nachrichten.', 'psource-chat'),
                    'loadingMessages' => __('Lade Nachrichten...', 'psource-chat'),
                    'userJoined' => __('ist dem Chat beigetreten', 'psource-chat'),
                    'userLeft' => __('hat den Chat verlassen', 'psource-chat'),
                    'typingIndicator' => __('tippt...', 'psource-chat'),
                    'newMessage' => __('Neue Nachricht', 'psource-chat')
                ]
            ]);
        }
    }
    
    /**
     * Render chat interface
     */
    public function render_chat_interface() {
        $position = $this->options['position'] ?? 'after_content';
        
        if ($position === 'floating') {
            $this->render_floating_chat();
        }
    }
    
    /**
     * Render floating chat
     */
    private function render_floating_chat() {
        ?>
        <div id="psource-chat-floating" class="psource-chat-floating">
            <div class="chat-toggle" id="chat-toggle">
                <span class="dashicons dashicons-format-chat"></span>
                <span class="chat-badge" id="chat-badge" style="display: none;">0</span>
            </div>
            
            <div class="chat-window" id="chat-window" style="display: none;">
                <div class="chat-header">
                    <div class="chat-title">
                        <span class="dashicons dashicons-format-chat"></span>
                        <?php _e('Chat', 'psource-chat'); ?>
                        <span class="users-count" id="floating-users-count">(0)</span>
                    </div>
                    <div class="chat-controls">
                        <button type="button" id="chat-minimize" class="chat-control-btn" title="<?php esc_attr_e('Minimieren', 'psource-chat'); ?>">
                            <span class="dashicons dashicons-minus"></span>
                        </button>
                        <button type="button" id="chat-close" class="chat-control-btn" title="<?php esc_attr_e('Schließen', 'psource-chat'); ?>">
                            <span class="dashicons dashicons-no"></span>
                        </button>
                    </div>
                </div>
                
                <div class="chat-content">
                    <div class="chat-messages" id="floating-chat-messages">
                        <div class="welcome-message">
                            <p><?php _e('Willkommen im Chat! Schreibe eine Nachricht, um zu beginnen.', 'psource-chat'); ?></p>
                        </div>
                    </div>
                    
                    <div class="chat-input-area">
                        <?php if (is_user_logged_in()): ?>
                            <div class="chat-input-wrapper">
                                <textarea id="floating-chat-input" 
                                          placeholder="<?php esc_attr_e('Nachricht eingeben...', 'psource-chat'); ?>"
                                          rows="1"></textarea>
                                <button type="button" id="floating-chat-send" class="chat-send-btn">
                                    <span class="dashicons dashicons-arrow-up-alt2"></span>
                                </button>
                            </div>
                            <div class="chat-status-bar">
                                <span class="char-counter" id="floating-char-counter">0/500</span>
                                <span class="connection-status" id="floating-connection-status">
                                    <span class="status-dot"></span>
                                    <span class="status-text"><?php _e('Verbinde...', 'psource-chat'); ?></span>
                                </span>
                            </div>
                        <?php else: ?>
                            <div class="chat-login-prompt">
                                <p><?php _e('Du musst eingeloggt sein, um zu chatten.', 'psource-chat'); ?></p>
                                <a href="<?php echo wp_login_url(get_permalink()); ?>" class="button button-primary">
                                    <?php _e('Anmelden', 'psource-chat'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Auto-embed chat in content
     */
    public function auto_embed_chat($content) {
        if (!is_singular()) {
            return $content;
        }
        
        $auto_embed = $this->options['auto_embed'] ?? 'disabled';
        $position = $this->options['position'] ?? 'after_content';
        
        $show_chat = false;
        
        switch ($auto_embed) {
            case 'posts':
                $show_chat = is_single();
                break;
            case 'pages':
                $show_chat = is_page();
                break;
            case 'both':
                $show_chat = is_single() || is_page();
                break;
        }
        
        if (!$show_chat) {
            return $content;
        }
        
        $chat_html = $this->get_embedded_chat_html();
        
        if ($position === 'before_content') {
            return $chat_html . $content;
        } else {
            return $content . $chat_html;
        }
    }
    
    /**
     * Get embedded chat HTML
     */
    private function get_embedded_chat_html() {
        ob_start();
        ?>
        <div class="psource-chat-embedded" id="psource-chat-embedded">
            <div class="chat-container">
                <div class="chat-header">
                    <h3><?php _e('Chat', 'psource-chat'); ?></h3>
                    <div class="chat-info">
                        <span class="users-online" id="embedded-users-count">
                            <span class="dashicons dashicons-groups"></span>
                            <span class="count">0</span> <?php _e('Online', 'psource-chat'); ?>
                        </span>
                    </div>
                </div>
                
                <div class="chat-messages-container" id="embedded-chat-messages">
                    <div class="loading-indicator">
                        <span class="dashicons dashicons-update spin"></span>
                        <?php _e('Lade Chat...', 'psource-chat'); ?>
                    </div>
                </div>
                
                <?php if (is_user_logged_in()): ?>
                    <div class="chat-input-section">
                        <div class="chat-input-group">
                            <textarea id="embedded-chat-input" 
                                      placeholder="<?php esc_attr_e('Nachricht eingeben...', 'psource-chat'); ?>"
                                      rows="3"></textarea>
                            <button type="button" id="embedded-chat-send" class="button button-primary">
                                <?php _e('Senden', 'psource-chat'); ?>
                            </button>
                        </div>
                        <div class="chat-input-meta">
                            <span class="char-counter" id="embedded-char-counter">0/500</span>
                            <span class="chat-status" id="embedded-chat-status">
                                <span class="status-indicator"></span>
                                <span class="status-text"><?php _e('Verbinde...', 'psource-chat'); ?></span>
                            </span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="chat-guest-section">
                        <p><?php _e('Melde dich an, um am Chat teilzunehmen.', 'psource-chat'); ?></p>
                        <div class="chat-auth-buttons">
                            <a href="<?php echo wp_login_url(get_permalink()); ?>" class="button button-primary">
                                <?php _e('Anmelden', 'psource-chat'); ?>
                            </a>
                            <a href="<?php echo wp_registration_url(); ?>" class="button">
                                <?php _e('Registrieren', 'psource-chat'); ?>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Chat shortcode
     */
    public function chat_shortcode($atts) {
        $atts = shortcode_atts([
            'height' => '400px',
            'room' => '',
            'title' => __('Chat', 'psource-chat'),
            'show_users' => 'true'
        ], $atts);
        
        $room_id = 0;
        if (!empty($atts['room'])) {
            global $wpdb;
            $room = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}psource_chat_rooms WHERE name = %s",
                $atts['room']
            ));
            if ($room) {
                $room_id = $room->id;
            }
        }
        
        ob_start();
        ?>
        <div class="psource-chat-shortcode" data-room-id="<?php echo esc_attr($room_id); ?>" style="height: <?php echo esc_attr($atts['height']); ?>;">
            <div class="chat-widget-header">
                <h4><?php echo esc_html($atts['title']); ?></h4>
                <?php if ($atts['show_users'] === 'true'): ?>
                    <div class="users-indicator">
                        <span class="dashicons dashicons-groups"></span>
                        <span class="users-count">0</span>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="chat-widget-messages"></div>
            
            <?php if (is_user_logged_in()): ?>
                <div class="chat-widget-input">
                    <textarea placeholder="<?php esc_attr_e('Nachricht eingeben...', 'psource-chat'); ?>"></textarea>
                    <button type="button" class="button"><?php _e('Senden', 'psource-chat'); ?></button>
                </div>
            <?php else: ?>
                <div class="chat-widget-login">
                    <p><?php _e('Anmeldung erforderlich', 'psource-chat'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Status shortcode
     */
    public function status_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Anmeldung erforderlich', 'psource-chat') . '</p>';
        }
        
        $atts = shortcode_atts([
            'show_avatar' => 'true',
            'show_message' => 'true'
        ], $atts);
        
        ob_start();
        ?>
        <div class="psource-chat-status-shortcode">
            <?php if ($atts['show_avatar'] === 'true'): ?>
                <div class="status-avatar">
                    <?php echo get_avatar(get_current_user_id(), 48); ?>
                </div>
            <?php endif; ?>
            
            <div class="status-controls">
                <select class="status-selector">
                    <option value="online"><?php _e('Online', 'psource-chat'); ?></option>
                    <option value="away"><?php _e('Abwesend', 'psource-chat'); ?></option>
                    <option value="busy"><?php _e('Beschäftigt', 'psource-chat'); ?></option>
                    <option value="invisible"><?php _e('Unsichtbar', 'psource-chat'); ?></option>
                </select>
                
                <?php if ($atts['show_message'] === 'true'): ?>
                    <input type="text" class="status-message" placeholder="<?php esc_attr_e('Status-Nachricht...', 'psource-chat'); ?>"/>
                <?php endif; ?>
                
                <button type="button" class="button status-save"><?php _e('Speichern', 'psource-chat'); ?></button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Users shortcode
     */
    public function users_shortcode($atts) {
        $atts = shortcode_atts([
            'limit' => '10',
            'show_avatars' => 'true',
            'show_status' => 'true'
        ], $atts);
        
        ob_start();
        ?>
        <div class="psource-chat-users-shortcode" data-limit="<?php echo esc_attr($atts['limit']); ?>">
            <div class="users-list">
                <div class="loading-users">
                    <span class="dashicons dashicons-update spin"></span>
                    <?php _e('Lade Benutzer...', 'psource-chat'); ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Rooms shortcode
     */
    public function rooms_shortcode($atts) {
        $atts = shortcode_atts([
            'limit' => '5',
            'show_description' => 'true',
            'show_user_count' => 'true'
        ], $atts);
        
        ob_start();
        ?>
        <div class="psource-chat-rooms-shortcode" data-limit="<?php echo esc_attr($atts['limit']); ?>">
            <div class="rooms-list">
                <div class="loading-rooms">
                    <span class="dashicons dashicons-update spin"></span>
                    <?php _e('Lade Räume...', 'psource-chat'); ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
