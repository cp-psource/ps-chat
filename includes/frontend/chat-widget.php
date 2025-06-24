<?php
/**
 * Modern Chat Widget
 * 
 * @package PSSource\Chat\Frontend
 */

namespace PSSource\Chat\Frontend;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Chat Widget Class
 */
class Chat_Widget extends \WP_Widget {
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(
            'psource_chat_widget',
            __('PS Chat Widget', 'psource-chat'),
            [
                'description' => __('Add a chat box to your sidebar', 'psource-chat'),
                'classname' => 'psource-chat-widget'
            ]
        );
    }
    
    /**
     * Widget output
     */
    public function widget($args, $instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Chat', 'psource-chat');
        $height = !empty($instance['height']) ? $instance['height'] : '300px';
        $session = !empty($instance['session']) ? $instance['session'] : 'widget-' . get_the_ID();
        $theme = !empty($instance['theme']) ? $instance['theme'] : 'default';
        $enable_private = !empty($instance['enable_private']);
        
        echo $args['before_widget'];
        
        if ($title) {
            echo $args['before_title'] . apply_filters('widget_title', $title) . $args['after_title'];
        }
        
        // Render chat
        $shortcode_atts = [
            'session' => $session,
            'height' => $height,
            'theme' => $theme,
            'widget' => 'true'
        ];
        
        if ($enable_private) {
            $shortcode_atts['private'] = 'true';
        }
        
        echo $this->render_chat($shortcode_atts);
        
        echo $args['after_widget'];
    }
    
    /**
     * Widget form
     */
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Chat', 'psource-chat');
        $height = !empty($instance['height']) ? $instance['height'] : '300px';
        $session = !empty($instance['session']) ? $instance['session'] : '';
        $theme = !empty($instance['theme']) ? $instance['theme'] : 'default';
        $enable_private = !empty($instance['enable_private']);
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'psource-chat'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('session'); ?>"><?php _e('Chat Session ID:', 'psource-chat'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('session'); ?>" name="<?php echo $this->get_field_name('session'); ?>" type="text" value="<?php echo esc_attr($session); ?>">
            <small><?php _e('Leave empty for automatic session based on current page', 'psource-chat'); ?></small>
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('height'); ?>"><?php _e('Height:', 'psource-chat'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('height'); ?>" name="<?php echo $this->get_field_name('height'); ?>" type="text" value="<?php echo esc_attr($height); ?>">
            <small><?php _e('e.g., 300px, 50vh', 'psource-chat'); ?></small>
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('theme'); ?>"><?php _e('Theme:', 'psource-chat'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('theme'); ?>" name="<?php echo $this->get_field_name('theme'); ?>">
                <option value="default" <?php selected($theme, 'default'); ?>><?php _e('Default', 'psource-chat'); ?></option>
                <option value="light" <?php selected($theme, 'light'); ?>><?php _e('Light', 'psource-chat'); ?></option>
                <option value="dark" <?php selected($theme, 'dark'); ?>><?php _e('Dark', 'psource-chat'); ?></option>
            </select>
        </p>
        
        <p>
            <input class="checkbox" type="checkbox" <?php checked($enable_private); ?> id="<?php echo $this->get_field_id('enable_private'); ?>" name="<?php echo $this->get_field_name('enable_private'); ?>" value="1">
            <label for="<?php echo $this->get_field_id('enable_private'); ?>"><?php _e('Enable private messaging', 'psource-chat'); ?></label>
        </p>
        <?php
    }
    
    /**
     * Update widget
     */
    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['height'] = (!empty($new_instance['height'])) ? sanitize_text_field($new_instance['height']) : '300px';
        $instance['session'] = (!empty($new_instance['session'])) ? sanitize_text_field($new_instance['session']) : '';
        $instance['theme'] = (!empty($new_instance['theme'])) ? sanitize_text_field($new_instance['theme']) : 'default';
        $instance['enable_private'] = (!empty($new_instance['enable_private'])) ? 1 : 0;
        
        return $instance;
    }
    
    /**
     * Render chat interface
     */
    private function render_chat($atts) {
        $defaults = [
            'session' => 'default',
            'height' => '300px',
            'theme' => 'default',
            'widget' => 'false',
            'private' => 'false'
        ];
        
        $atts = wp_parse_args($atts, $defaults);
        
        // Check if user can chat
        if (!$this->can_user_chat()) {
            return $this->render_login_message();
        }
        
        $chat_id = 'psource-chat-' . uniqid();
        
        ob_start();
        ?>
        <div id="<?php echo esc_attr($chat_id); ?>" class="psource-chat-container psource-chat-widget psource-chat-theme-<?php echo esc_attr($atts['theme']); ?>" data-session="<?php echo esc_attr($atts['session']); ?>">
            <div class="psource-chat-header">
                <div class="psource-chat-title">
                    <span class="psource-chat-status-indicator"></span>
                    <?php _e('Chat', 'psource-chat'); ?>
                    <span class="psource-chat-user-count" data-count="0">(0)</span>
                </div>
                <div class="psource-chat-controls">
                    <button class="psource-chat-toggle-sound" title="<?php _e('Toggle sound', 'psource-chat'); ?>">🔊</button>
                    <?php if ($atts['private'] === 'true'): ?>
                        <button class="psource-chat-toggle-private" title="<?php _e('Private messages', 'psource-chat'); ?>">💬</button>
                    <?php endif; ?>
                    <button class="psource-chat-minimize" title="<?php _e('Minimize', 'psource-chat'); ?>">−</button>
                </div>
            </div>
            
            <div class="psource-chat-messages" style="height: <?php echo esc_attr($atts['height']); ?>;">
                <div class="psource-chat-loading">
                    <?php _e('Loading chat...', 'psource-chat'); ?>
                </div>
            </div>
            
            <div class="psource-chat-users-panel" style="display: none;">
                <div class="psource-chat-users-header">
                    <?php _e('Online Users', 'psource-chat'); ?>
                    <button class="psource-chat-close-users">×</button>
                </div>
                <div class="psource-chat-users-list"></div>
            </div>
            
            <div class="psource-chat-input-area">
                <div class="psource-chat-input-container">
                    <input type="text" class="psource-chat-input" placeholder="<?php _e('Type your message...', 'psource-chat'); ?>" maxlength="500">
                    <div class="psource-chat-input-controls">
                        <button class="psource-chat-emoji-btn" title="<?php _e('Emojis', 'psource-chat'); ?>">😊</button>
                        <button class="psource-chat-users-btn" title="<?php _e('Users', 'psource-chat'); ?>">👥</button>
                        <button class="psource-chat-send-btn" title="<?php _e('Send', 'psource-chat'); ?>">➤</button>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof PSChatWidget === 'undefined') {
                console.warn('PS Chat: Widget script not loaded');
                return;
            }
            
            new PSChatWidget('<?php echo esc_js($chat_id); ?>', {
                session: '<?php echo esc_js($atts['session']); ?>',
                theme: '<?php echo esc_js($atts['theme']); ?>',
                enablePrivate: <?php echo $atts['private'] === 'true' ? 'true' : 'false'; ?>,
                widget: true
            });
        });
        </script>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Check if user can chat
     */
    private function can_user_chat() {
        $options = get_option('psource_chat_options', []);
        
        // Check if login required
        if (!empty($options['require_login']) && !is_user_logged_in()) {
            return !empty($options['allow_guest_chat']);
        }
        
        // Check user role permissions
        if (is_user_logged_in() && !empty($options['allowed_user_roles'])) {
            $user = wp_get_current_user();
            $user_roles = $user->roles;
            $allowed_roles = $options['allowed_user_roles'];
            
            if (!array_intersect($user_roles, $allowed_roles)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Render login message
     */
    private function render_login_message() {
        $options = get_option('psource_chat_options', []);
        
        ob_start();
        ?>
        <div class="psource-chat-login-required">
            <div class="psource-chat-login-message">
                <?php if (!empty($options['allow_guest_chat'])): ?>
                    <p><?php _e('Enter your name to join the chat:', 'psource-chat'); ?></p>
                    <div class="psource-chat-guest-form">
                        <input type="text" placeholder="<?php _e('Your name', 'psource-chat'); ?>" class="psource-chat-guest-name">
                        <button class="psource-chat-guest-join"><?php _e('Join Chat', 'psource-chat'); ?></button>
                    </div>
                <?php else: ?>
                    <p><?php _e('Please log in to join the chat.', 'psource-chat'); ?></p>
                    <a href="<?php echo wp_login_url(get_permalink()); ?>" class="button"><?php _e('Log In', 'psource-chat'); ?></a>
                <?php endif; ?>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
}
