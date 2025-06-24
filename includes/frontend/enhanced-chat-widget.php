<?php
/**
 * Enhanced Chat Widget
 * 
 * @package PSSource\Chat\Frontend
 */

namespace PSSource\Chat\Frontend;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enhanced Chat Widget Class
 */
class Enhanced_Chat_Widget extends \WP_Widget {
    
    private $defaults = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        // Set defaults
        $this->defaults = array(
            'title' => __('Chat', 'psource-chat'),
            'id' => '',
            'box_height' => '300px',
            'box_width' => '100%',
            'box_sound' => 'enabled',
            'row_name_avatar' => 'avatar',
            'box_emoticons' => 'enabled',
            'row_date' => 'disabled',
            'row_date_format' => get_option('date_format'),
            'row_time' => 'enabled',
            'row_time_format' => get_option('time_format'),
            'show_user_list' => 'enabled',
            'enable_moderation' => 'disabled',
            'max_message_length' => 500,
        );
        
        parent::__construct(
            'psource_enhanced_chat_widget',
            __('PS Chat Widget (Enhanced)', 'psource-chat'),
            array(
                'description' => __('Ein erweiterbares Chat-Widget für die Sidebar mit vielen Optionen', 'psource-chat'),
                'classname' => 'psource-chat-widget-enhanced'
            )
        );
        
        add_action('wp_enqueue_scripts', [$this, 'enqueue_widget_scripts']);
    }
    
    /**
     * Enqueue widget scripts
     */
    public function enqueue_widget_scripts() {
        if (is_active_widget(false, false, $this->id_base)) {
            wp_enqueue_script('psource-chat-widget', PSOURCE_CHAT_PLUGIN_URL . 'assets/js/widget.js', ['jquery'], '1.0.0', true);
            wp_enqueue_style('psource-chat-widget', PSOURCE_CHAT_PLUGIN_URL . 'assets/css/widget.css', [], '1.0.0');
            
            wp_localize_script('psource-chat-widget', 'PSChatWidget', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('psource_chat_widget'),
                'strings' => [
                    'loading' => __('Chat wird geladen...', 'psource-chat'),
                    'send' => __('Senden', 'psource-chat'),
                    'placeholder' => __('Nachricht eingeben...', 'psource-chat'),
                    'error' => __('Fehler beim Laden des Chats', 'psource-chat'),
                    'login_required' => __('Sie müssen angemeldet sein, um zu chatten.', 'psource-chat'),
                ]
            ]);
        }
    }
    
    /**
     * Convert legacy settings keys
     */
    private function convert_settings_keys($instance) {
        if (isset($instance['height'])) {
            $instance['box_height'] = $instance['height'];
            unset($instance['height']);
        }
        
        if (isset($instance['width'])) {
            $instance['box_width'] = $instance['width'];
            unset($instance['width']);
        }
        
        if (isset($instance['sound'])) {
            $instance['box_sound'] = $instance['sound'];
            unset($instance['sound']);
        }
        
        if (isset($instance['avatar'])) {
            $instance['row_name_avatar'] = $instance['avatar'];
            unset($instance['avatar']);
        }
        
        return $instance;
    }
    
    /**
     * Output the widget on the frontend
     */
    public function widget($args, $instance) {
        global $post, $bp;
        
        $instance = wp_parse_args($this->convert_settings_keys($instance), $this->defaults);
        
        // Check if chat should be blocked on shortcode pages
        $chat_options = get_option('psource_chat_options', []);
        if (isset($chat_options['widget']['blocked_on_shortcode']) && $chat_options['widget']['blocked_on_shortcode'] == "enabled") {
            if ($post && strstr($post->post_content, '[chat ') !== false) {
                return;
            }
        }
        
        // BuddyPress group admin check
        if (function_exists('bp_is_group') && bp_is_group()) {
            if (isset($bp->groups->current_group->id) && intval($bp->groups->current_group->id)) {
                $bp_group_admin_url_path = parse_url(bp_get_group_admin_permalink($bp->groups->current_group), PHP_URL_PATH);
                $request_url_path = parse_url(get_option('siteurl') . $_SERVER['REQUEST_URI'], PHP_URL_PATH);
                
                if ((!empty($request_url_path)) && (!empty($bp_group_admin_url_path))
                    && (substr($request_url_path, 0, strlen($bp_group_admin_url_path)) == $bp_group_admin_url_path)) {
                    $show_widget = isset($chat_options['global']['bp_group_admin_show_widget']) 
                                  ? $chat_options['global']['bp_group_admin_show_widget'] 
                                  : 'disabled';
                    if ($show_widget != "enabled") {
                        return;
                    }
                }
            }
        }
        
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        $this->render_chat_widget($instance);
        
        echo $args['after_widget'];
    }
    
    /**
     * Render the chat widget
     */
    private function render_chat_widget($instance) {
        $widget_id = 'widget-' . uniqid();
        $current_user = wp_get_current_user();
        $is_logged_in = $current_user && $current_user->ID;
        
        ?>
        <div class="psource-chat-widget-container" 
             style="height: <?php echo esc_attr($instance['box_height']); ?>; width: <?php echo esc_attr($instance['box_width']); ?>;">
            
            <div class="psource-chat-box widget-chat" id="<?php echo esc_attr($widget_id); ?>" 
                 data-chat-id="<?php echo esc_attr($widget_id); ?>"
                 data-sound="<?php echo esc_attr($instance['box_sound']); ?>"
                 data-emoticons="<?php echo esc_attr($instance['box_emoticons']); ?>"
                 data-avatar="<?php echo esc_attr($instance['row_name_avatar']); ?>"
                 data-show-date="<?php echo esc_attr($instance['row_date']); ?>"
                 data-show-time="<?php echo esc_attr($instance['row_time']); ?>"
                 data-show-users="<?php echo esc_attr($instance['show_user_list']); ?>">
                
                <!-- Chat Header -->
                <div class="psource-chat-header">
                    <div class="chat-status">
                        <span class="status-indicator" title="<?php _e('Chat Status', 'psource-chat'); ?>"></span>
                        <span class="user-count">0</span> <?php _e('online', 'psource-chat'); ?>
                    </div>
                    
                    <div class="chat-controls">
                        <?php if ($instance['show_user_list'] === 'enabled'): ?>
                            <button type="button" class="users-toggle" title="<?php _e('Benutzer anzeigen/verbergen', 'psource-chat'); ?>">
                                👥
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($instance['box_sound'] === 'enabled'): ?>
                            <button type="button" class="sound-toggle active" title="<?php _e('Sound an/aus', 'psource-chat'); ?>">
                                🔊
                            </button>
                        <?php endif; ?>
                        
                        <button type="button" class="minimize-toggle" title="<?php _e('Minimieren/Maximieren', 'psource-chat'); ?>">
                            ⬇️
                        </button>
                    </div>
                </div>
                
                <!-- Messages Area -->
                <div class="psource-chat-messages" id="<?php echo esc_attr($widget_id); ?>-messages">
                    <div class="chat-loading">
                        <p><em><?php _e('Chat wird geladen...', 'psource-chat'); ?></em></p>
                    </div>
                </div>
                
                <!-- User List (collapsible) -->
                <?php if ($instance['show_user_list'] === 'enabled'): ?>
                    <div class="psource-chat-users" id="<?php echo esc_attr($widget_id); ?>-users" style="display: none;">
                        <h4><?php _e('Online Benutzer', 'psource-chat'); ?></h4>
                        <ul class="user-list"></ul>
                    </div>
                <?php endif; ?>
                
                <!-- Input Area -->
                <div class="psource-chat-input-area">
                    <?php if ($is_logged_in): ?>
                        <div class="chat-user-info">
                            <?php if ($instance['row_name_avatar'] === 'avatar' || $instance['row_name_avatar'] === 'name-avatar'): ?>
                                <?php echo get_avatar($current_user->ID, 24); ?>
                            <?php endif; ?>
                            
                            <?php if ($instance['row_name_avatar'] === 'name' || $instance['row_name_avatar'] === 'name-avatar'): ?>
                                <span class="username"><?php echo esc_html($current_user->display_name); ?></span>
                            <?php endif; ?>
                            
                            <?php if (current_user_can('moderate_comments')): ?>
                                <span class="moderator-badge" title="<?php _e('Moderator', 'psource-chat'); ?>">★</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="chat-input-controls">
                            <div class="input-wrapper">
                                <input type="text" 
                                       class="chat-message-input" 
                                       id="<?php echo esc_attr($widget_id); ?>-input"
                                       placeholder="<?php _e('Nachricht eingeben...', 'psource-chat'); ?>" 
                                       maxlength="<?php echo intval($instance['max_message_length']); ?>" />
                                
                                <?php if ($instance['box_emoticons'] === 'enabled'): ?>
                                    <button type="button" class="emoji-button" title="<?php _e('Emojis', 'psource-chat'); ?>">
                                        😀
                                    </button>
                                <?php endif; ?>
                                
                                <button type="button" 
                                        class="chat-send-button" 
                                        id="<?php echo esc_attr($widget_id); ?>-send">
                                    <?php _e('Senden', 'psource-chat'); ?>
                                </button>
                            </div>
                        </div>
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
        <?php
    }
    
    /**
     * Widget form in admin
     */
    public function form($instance) {
        $instance = wp_parse_args($this->convert_settings_keys($instance), $this->defaults);
        ?>
        <input type="hidden" name="<?php echo $this->get_field_name('id'); ?>" 
               id="<?php echo $this->get_field_id('id'); ?>" value="<?php echo esc_attr($instance['id']); ?>" />
        
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Titel:', 'psource-chat'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" 
                   value="<?php echo esc_attr($instance['title']); ?>">
        </p>
        
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('box_height')); ?>"><?php _e('Höhe des Widgets:', 'psource-chat'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('box_height')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('box_height')); ?>" type="text" 
                   value="<?php echo esc_attr($instance['box_height']); ?>">
            <span class="description"><?php _e('Die Breite beträgt 100% des Widget-Bereichs', 'psource-chat'); ?></span>
        </p>
        
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('box_sound')); ?>"><?php _e('Sound aktivieren:', 'psource-chat'); ?></label><br />
            <select id="<?php echo esc_attr($this->get_field_id('box_sound')); ?>" 
                    name="<?php echo esc_attr($this->get_field_name('box_sound')); ?>">
                <option value="enabled" <?php selected($instance['box_sound'], 'enabled'); ?>><?php _e("Aktiviert", 'psource-chat'); ?></option>
                <option value="disabled" <?php selected($instance['box_sound'], 'disabled'); ?>><?php _e("Deaktiviert", 'psource-chat'); ?></option>
            </select>
        </p>
        
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('row_name_avatar')); ?>"><?php _e("Avatar/Name anzeigen:", 'psource-chat'); ?></label><br />
            <select id="<?php echo esc_attr($this->get_field_id('row_name_avatar')); ?>" 
                    name="<?php echo esc_attr($this->get_field_name('row_name_avatar')); ?>">
                <option value="avatar" <?php selected($instance['row_name_avatar'], 'avatar'); ?>><?php _e("Nur Avatar", 'psource-chat'); ?></option>
                <option value="name" <?php selected($instance['row_name_avatar'], 'name'); ?>><?php _e("Nur Name", 'psource-chat'); ?></option>
                <option value="name-avatar" <?php selected($instance['row_name_avatar'], 'name-avatar'); ?>><?php _e("Avatar und Name", 'psource-chat'); ?></option>
                <option value="disabled" <?php selected($instance['row_name_avatar'], 'disabled'); ?>><?php _e("Nichts anzeigen", 'psource-chat'); ?></option>
            </select>
        </p>
        
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('box_emoticons')); ?>"><?php _e('Emoticons anzeigen:', 'psource-chat'); ?></label><br />
            <select id="<?php echo esc_attr($this->get_field_id('box_emoticons')); ?>" 
                    name="<?php echo esc_attr($this->get_field_name('box_emoticons')); ?>">
                <option value="enabled" <?php selected($instance['box_emoticons'], 'enabled'); ?>><?php _e("Aktiviert", 'psource-chat'); ?></option>
                <option value="disabled" <?php selected($instance['box_emoticons'], 'disabled'); ?>><?php _e("Deaktiviert", 'psource-chat'); ?></option>
            </select>
        </p>
        
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('show_user_list')); ?>"><?php _e('Benutzerliste anzeigen:', 'psource-chat'); ?></label><br />
            <select id="<?php echo esc_attr($this->get_field_id('show_user_list')); ?>" 
                    name="<?php echo esc_attr($this->get_field_name('show_user_list')); ?>">
                <option value="enabled" <?php selected($instance['show_user_list'], 'enabled'); ?>><?php _e("Aktiviert", 'psource-chat'); ?></option>
                <option value="disabled" <?php selected($instance['show_user_list'], 'disabled'); ?>><?php _e("Deaktiviert", 'psource-chat'); ?></option>
            </select>
        </p>
        
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('row_time')); ?>"><?php _e('Uhrzeit anzeigen:', 'psource-chat'); ?></label><br />
            <select id="<?php echo esc_attr($this->get_field_id('row_time')); ?>" 
                    name="<?php echo esc_attr($this->get_field_name('row_time')); ?>">
                <option value="enabled" <?php selected($instance['row_time'], 'enabled'); ?>><?php _e("Aktiviert", 'psource-chat'); ?></option>
                <option value="disabled" <?php selected($instance['row_time'], 'disabled'); ?>><?php _e("Deaktiviert", 'psource-chat'); ?></option>
            </select>
            <input id="<?php echo esc_attr($this->get_field_id('row_time_format')); ?>" type="text" 
                   style="width:100px;" name="<?php echo esc_attr($this->get_field_name('row_time_format')); ?>" 
                   value="<?php echo esc_attr($instance['row_time_format']); ?>" placeholder="H:i"/>
        </p>
        
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('row_date')); ?>"><?php _e('Datum anzeigen:', 'psource-chat'); ?></label><br />
            <select id="<?php echo esc_attr($this->get_field_id('row_date')); ?>" 
                    name="<?php echo esc_attr($this->get_field_name('row_date')); ?>">
                <option value="enabled" <?php selected($instance['row_date'], 'enabled'); ?>><?php _e("Aktiviert", 'psource-chat'); ?></option>
                <option value="disabled" <?php selected($instance['row_date'], 'disabled'); ?>><?php _e("Deaktiviert", 'psource-chat'); ?></option>
            </select>
            <input id="<?php echo esc_attr($this->get_field_id('row_date_format')); ?>" type="text" 
                   style="width:100px;" name="<?php echo esc_attr($this->get_field_name('row_date_format')); ?>" 
                   value="<?php echo esc_attr($instance['row_date_format']); ?>" placeholder="d.m.Y"/>
        </p>
        
        <p>
            <a href="<?php echo admin_url('admin.php?page=ps-chat-settings&tab=widget'); ?>" target="_blank">
                <?php _e('Weitere Widget-Optionen in den Einstellungen →', 'psource-chat'); ?>
            </a>
        </p>
        <?php
    }
    
    /**
     * Save widget settings
     */
    public function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance = $this->convert_settings_keys($instance);
        
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['box_height'] = (!empty($new_instance['box_height'])) ? sanitize_text_field($new_instance['box_height']) : '300px';
        $instance['box_sound'] = (!empty($new_instance['box_sound'])) ? sanitize_text_field($new_instance['box_sound']) : 'enabled';
        $instance['row_name_avatar'] = (!empty($new_instance['row_name_avatar'])) ? sanitize_text_field($new_instance['row_name_avatar']) : 'avatar';
        $instance['box_emoticons'] = (!empty($new_instance['box_emoticons'])) ? sanitize_text_field($new_instance['box_emoticons']) : 'enabled';
        $instance['show_user_list'] = (!empty($new_instance['show_user_list'])) ? sanitize_text_field($new_instance['show_user_list']) : 'enabled';
        $instance['row_date'] = (!empty($new_instance['row_date'])) ? sanitize_text_field($new_instance['row_date']) : 'disabled';
        $instance['row_date_format'] = (!empty($new_instance['row_date_format'])) ? sanitize_text_field($new_instance['row_date_format']) : get_option('date_format');
        $instance['row_time'] = (!empty($new_instance['row_time'])) ? sanitize_text_field($new_instance['row_time']) : 'enabled';
        $instance['row_time_format'] = (!empty($new_instance['row_time_format'])) ? sanitize_text_field($new_instance['row_time_format']) : get_option('time_format');
        
        return $instance;
    }
}

// Register the enhanced widget
function register_enhanced_chat_widget() {
    register_widget('PSSource\Chat\Frontend\Enhanced_Chat_Widget');
}
add_action('widgets_init', 'PSSource\Chat\Frontend\register_enhanced_chat_widget');
