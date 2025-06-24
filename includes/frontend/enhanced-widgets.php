<?php
/**
 * Enhanced Chat Widgets
 * 
 * Modern WordPress widgets for chat functionality
 * 
 * @package PSSource\Chat\Frontend
 */

namespace PSSource\Chat\Frontend;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Chat Widget
 */
class Enhanced_Chat_Widget extends \WP_Widget {
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(
            'psource_chat_widget',
            __('PS Chat Widget', 'psource-chat'),
            [
                'classname' => 'psource-chat-widget',
                'description' => __('Fügt einen Chat zur Seitenleiste hinzu.', 'psource-chat')
            ]
        );
    }
    
    /**
     * Widget frontend output
     */
    public function widget($args, $instance) {
        // Check if widgets are enabled
        $widget_options = psource_chat_get_extension_option('widgets', 'enable_chat_widget', 'enabled');
        if ($widget_options !== 'enabled') {
            return;
        }
        
        // Check if user can access chat
        if (!$this->can_user_access_chat()) {
            return;
        }
        
        $instance = wp_parse_args($instance, $this->get_defaults());
        
        echo $args['before_widget'];
        
        $title = apply_filters('widget_title', $instance['title']);
        if ($title) {
            echo $args['before_title'] . $title . $args['after_title'];
        }
        
        // Render chat interface
        $chat_renderer = new \PSSource\Chat\Frontend\Chat_Renderer();
        echo $chat_renderer->render_shortcode_chat([
            'session_type' => 'widget',
            'session_id' => 'widget-' . $this->id,
            'width' => '100%',
            'height' => $instance['height'],
            'show_avatars' => $instance['show_avatars'],
            'enable_sound' => $instance['enable_sound'],
            'show_emoticons' => $instance['show_emoticons'],
            'show_date' => $instance['show_date'],
            'show_time' => $instance['show_time'],
            'theme' => 'widget'
        ]);
        
        echo $args['after_widget'];
    }
    
    /**
     * Widget backend form
     */
    public function form($instance) {
        $instance = wp_parse_args($instance, $this->get_defaults());
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Titel:', 'psource-chat'); ?></label>
            <input type="text" class="widefat" id="<?php echo $this->get_field_id('title'); ?>" 
                   name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo esc_attr($instance['title']); ?>"/>
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('height'); ?>"><?php _e('Höhe:', 'psource-chat'); ?></label>
            <input type="text" class="widefat" id="<?php echo $this->get_field_id('height'); ?>" 
                   name="<?php echo $this->get_field_name('height'); ?>" value="<?php echo esc_attr($instance['height']); ?>"
                   placeholder="z.B. 300px"/>
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('show_avatars'); ?>"><?php _e('Avatare anzeigen:', 'psource-chat'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('show_avatars'); ?>" 
                    name="<?php echo $this->get_field_name('show_avatars'); ?>">
                <option value="enabled" <?php selected($instance['show_avatars'], 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                <option value="disabled" <?php selected($instance['show_avatars'], 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
            </select>
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('enable_sound'); ?>"><?php _e('Sound aktivieren:', 'psource-chat'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('enable_sound'); ?>" 
                    name="<?php echo $this->get_field_name('enable_sound'); ?>">
                <option value="enabled" <?php selected($instance['enable_sound'], 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                <option value="disabled" <?php selected($instance['enable_sound'], 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
            </select>
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('show_emoticons'); ?>"><?php _e('Emoticons anzeigen:', 'psource-chat'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('show_emoticons'); ?>" 
                    name="<?php echo $this->get_field_name('show_emoticons'); ?>">
                <option value="enabled" <?php selected($instance['show_emoticons'], 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                <option value="disabled" <?php selected($instance['show_emoticons'], 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
            </select>
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('show_date'); ?>"><?php _e('Datum anzeigen:', 'psource-chat'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('show_date'); ?>" 
                    name="<?php echo $this->get_field_name('show_date'); ?>">
                <option value="enabled" <?php selected($instance['show_date'], 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                <option value="disabled" <?php selected($instance['show_date'], 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
            </select>
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('show_time'); ?>"><?php _e('Zeit anzeigen:', 'psource-chat'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('show_time'); ?>" 
                    name="<?php echo $this->get_field_name('show_time'); ?>">
                <option value="enabled" <?php selected($instance['show_time'], 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                <option value="disabled" <?php selected($instance['show_time'], 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
            </select>
        </p>
        
        <p class="description">
            <?php printf(__('Weitere Einstellungen finden Sie unter %s', 'psource-chat'), 
                '<a href="' . admin_url('admin.php?page=psource-chat-extensions') . '">' . __('Chat Erweiterungen', 'psource-chat') . '</a>'); ?>
        </p>
        <?php
    }
    
    /**
     * Widget update
     */
    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = sanitize_text_field($new_instance['title']);
        $instance['height'] = sanitize_text_field($new_instance['height']);
        $instance['show_avatars'] = sanitize_text_field($new_instance['show_avatars']);
        $instance['enable_sound'] = sanitize_text_field($new_instance['enable_sound']);
        $instance['show_emoticons'] = sanitize_text_field($new_instance['show_emoticons']);
        $instance['show_date'] = sanitize_text_field($new_instance['show_date']);
        $instance['show_time'] = sanitize_text_field($new_instance['show_time']);
        
        return $instance;
    }
    
    /**
     * Get default widget values
     */
    private function get_defaults() {
        $widget_options = psource_chat_get_extension_option('widgets', [], []);
        
        return [
            'title' => __('Chat', 'psource-chat'),
            'height' => $widget_options['default_height'] ?? '300px',
            'show_avatars' => $widget_options['show_avatars'] ?? 'enabled',
            'enable_sound' => $widget_options['sound_enabled'] ?? 'disabled',
            'show_emoticons' => 'enabled',
            'show_date' => 'disabled',
            'show_time' => 'enabled'
        ];
    }
    
    /**
     * Check if user can access chat
     */
    private function can_user_access_chat() {
        // Basic access check
        if (!is_user_logged_in()) {
            $plugin = \PSSource\Chat\Core\Plugin::get_instance();
            return $plugin->get_option('allow_guest_chat', false);
        }
        
        return true;
    }
}

/**
 * Chat Status Widget
 */
class Enhanced_Chat_Status_Widget extends \WP_Widget {
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(
            'psource_chat_status_widget',
            __('PS Chat Status', 'psource-chat'),
            [
                'classname' => 'psource-chat-status-widget',
                'description' => __('Ermöglicht Benutzern ihren Chat-Status zu setzen.', 'psource-chat')
            ]
        );
    }
    
    /**
     * Widget frontend output
     */
    public function widget($args, $instance) {
        // Check if status widget is enabled
        $widget_options = psource_chat_get_extension_option('widgets', 'enable_status_widget', 'enabled');
        if ($widget_options !== 'enabled') {
            return;
        }
        
        // Only for logged-in users
        if (!is_user_logged_in()) {
            return;
        }
        
        $instance = wp_parse_args($instance, $this->get_defaults());
        $current_user = wp_get_current_user();
        
        echo $args['before_widget'];
        
        $title = apply_filters('widget_title', $instance['title']);
        if ($title) {
            echo $args['before_title'] . $title . $args['after_title'];
        }
        
        // Get current status
        $current_status = get_user_meta($current_user->ID, 'psource_chat_user_status', true);
        if (empty($current_status)) {
            $current_status = 'away';
        }
        
        // Status dropdown
        echo '<div class="psource-chat-status-widget-wrapper">';
        echo '<select id="psource-chat-status-widget-' . $this->number . '" class="psource-chat-status-widget-select">';
        
        $statuses = [
            'available' => __('Verfügbar', 'psource-chat'),
            'busy' => __('Beschäftigt', 'psource-chat'),
            'away' => __('Abwesend', 'psource-chat'),
            'offline' => __('Offline', 'psource-chat')
        ];
        
        foreach ($statuses as $status_key => $status_label) {
            $selected = ($current_status === $status_key) ? ' selected="selected"' : '';
            $class = ($status_key === 'available') ? ' class="available"' : '';
            echo '<option value="' . esc_attr($status_key) . '"' . $selected . $class . '>' . esc_html($status_label) . '</option>';
        }
        
        echo '</select>';
        echo '</div>';
        
        // Add JavaScript for status changes
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('#psource-chat-status-widget-<?php echo $this->number; ?>').on('change', function() {
                var newStatus = $(this).val();
                
                $.ajax({
                    url: psource_chat_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'psource_chat_update_status',
                        status: newStatus,
                        nonce: psource_chat_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update admin bar if present
                            $('.psource-chat-current-status-label').text(response.data.status_label);
                            
                            // Trigger status change event
                            $(document).trigger('psource_chat_status_changed', [newStatus]);
                        }
                    }
                });
            });
        });
        </script>
        <?php
        
        echo $args['after_widget'];
    }
    
    /**
     * Widget backend form
     */
    public function form($instance) {
        $instance = wp_parse_args($instance, $this->get_defaults());
        ?>
        <p class="description">
            <?php _e('Dieses Widget zeigt Informationen an, die für authentifizierte Benutzer spezifisch sind. Wenn der Benutzer nicht angemeldet ist, wird das Widget nicht angezeigt.', 'psource-chat'); ?>
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Titel:', 'psource-chat'); ?></label>
            <input type="text" class="widefat" id="<?php echo $this->get_field_id('title'); ?>" 
                   name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo esc_attr($instance['title']); ?>"/>
        </p>
        <?php
    }
    
    /**
     * Widget update
     */
    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = sanitize_text_field($new_instance['title']);
        return $instance;
    }
    
    /**
     * Get default widget values
     */
    private function get_defaults() {
        return [
            'title' => __('Chat Status', 'psource-chat')
        ];
    }
}

/**
 * Chat Friends Widget (requires BuddyPress or PS Friends)
 */
class Enhanced_Chat_Friends_Widget extends \WP_Widget {
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(
            'psource_chat_friends_widget',
            __('PS Chat Freunde', 'psource-chat'),
            [
                'classname' => 'psource-chat-friends-widget',
                'description' => __('Zeigt Online-Freunde und Chat-Status an. (BuddyPress oder PS Freunde Plugin erforderlich)', 'psource-chat')
            ]
        );
    }
    
    /**
     * Widget frontend output
     */
    public function widget($args, $instance) {
        // Check if friends widget is enabled
        $widget_options = psource_chat_get_extension_option('widgets', 'enable_friends_widget', 'enabled');
        if ($widget_options !== 'enabled') {
            return;
        }
        
        // Only for logged-in users
        if (!is_user_logged_in()) {
            return;
        }
        
        // Check if BuddyPress or Friends plugin is available
        if (!$this->is_friends_system_available()) {
            return;
        }
        
        $instance = wp_parse_args($instance, $this->get_defaults());
        $current_user = wp_get_current_user();
        
        // Get friends list
        $friends_list = $this->get_friends_list($current_user->ID);
        if (empty($friends_list)) {
            return;
        }
        
        echo $args['before_widget'];
        
        $title = apply_filters('widget_title', $instance['title']);
        if ($title) {
            echo $args['before_title'] . $title . $args['after_title'];
        }
        
        // Get friends status
        $friends_status = $this->get_friends_status($current_user->ID, $friends_list);
        
        if (!empty($friends_status)) {
            echo '<ul class="psource-chat-friends-list">';
            
            foreach ($friends_status as $friend) {
                if ($friend->chat_status === 'available') {
                    echo '<li class="psource-chat-friend-item">';
                    
                    if ($instance['show_avatars'] === 'enabled') {
                        $avatar = get_avatar($friend->ID, $instance['avatar_size'], '', $friend->display_name);
                        echo '<span class="psource-chat-friend-avatar">' . $avatar . '</span>';
                    }
                    
                    echo '<span class="psource-chat-friend-name">' . esc_html($friend->display_name) . '</span>';
                    echo '<span class="psource-chat-friend-status psource-chat-status-' . esc_attr($friend->chat_status) . '"></span>';
                    
                    // Chat invite button
                    echo '<a href="#" class="psource-chat-friend-invite" data-friend-id="' . esc_attr($friend->ID) . '">';
                    echo __('Chat', 'psource-chat');
                    echo '</a>';
                    
                    echo '</li>';
                }
            }
            
            echo '</ul>';
        } else {
            echo '<p class="psource-chat-no-friends">' . __('Keine Freunde online.', 'psource-chat') . '</p>';
        }
        
        echo $args['after_widget'];
    }
    
    /**
     * Widget backend form
     */
    public function form($instance) {
        if (!$this->is_friends_system_available()) {
            echo '<p class="description error">' . __('Für dieses Widget ist BuddyPress Friends oder das PS Freunde Plugin erforderlich.', 'psource-chat') . '</p>';
            return;
        }
        
        $instance = wp_parse_args($instance, $this->get_defaults());
        ?>
        <p class="description">
            <?php _e('Dieses Widget zeigt Informationen an, die für authentifizierte Benutzer spezifisch sind.', 'psource-chat'); ?>
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Titel:', 'psource-chat'); ?></label>
            <input type="text" class="widefat" id="<?php echo $this->get_field_id('title'); ?>" 
                   name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo esc_attr($instance['title']); ?>"/>
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('show_avatars'); ?>"><?php _e('Avatare anzeigen:', 'psource-chat'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('show_avatars'); ?>" 
                    name="<?php echo $this->get_field_name('show_avatars'); ?>">
                <option value="enabled" <?php selected($instance['show_avatars'], 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                <option value="disabled" <?php selected($instance['show_avatars'], 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
            </select>
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('avatar_size'); ?>"><?php _e('Avatar Größe:', 'psource-chat'); ?></label>
            <input type="number" class="widefat" id="<?php echo $this->get_field_id('avatar_size'); ?>" 
                   name="<?php echo $this->get_field_name('avatar_size'); ?>" value="<?php echo esc_attr($instance['avatar_size']); ?>"
                   min="16" max="128" step="2"/> px
        </p>
        <?php
    }
    
    /**
     * Widget update
     */
    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = sanitize_text_field($new_instance['title']);
        $instance['show_avatars'] = sanitize_text_field($new_instance['show_avatars']);
        $instance['avatar_size'] = intval($new_instance['avatar_size']);
        
        return $instance;
    }
    
    /**
     * Get default widget values
     */
    private function get_defaults() {
        return [
            'title' => __('Online Freunde', 'psource-chat'),
            'show_avatars' => 'enabled',
            'avatar_size' => 32
        ];
    }
    
    /**
     * Check if friends system is available
     */
    private function is_friends_system_available() {
        global $bp;
        
        // Check BuddyPress
        if (!empty($bp) && function_exists('bp_get_friend_ids')) {
            return true;
        }
        
        // Check PS Friends plugin
        if (function_exists('friends_get_list')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get friends list
     */
    private function get_friends_list($user_id) {
        global $bp;
        
        if (!empty($bp) && function_exists('bp_get_friend_ids')) {
            $friends_ids = bp_get_friend_ids($user_id);
            return !empty($friends_ids) ? explode(',', $friends_ids) : [];
        }
        
        if (function_exists('friends_get_list')) {
            return friends_get_list($user_id);
        }
        
        return [];
    }
    
    /**
     * Get friends status
     */
    private function get_friends_status($user_id, $friends_list) {
        if (function_exists('psource_chat_get_friends_status')) {
            return psource_chat_get_friends_status($user_id, $friends_list);
        }
        
        // Fallback implementation
        global $wpdb;
        
        if (empty($friends_list) || !is_array($friends_list)) {
            return [];
        }
        
        $time_threshold = time() - 300; // 5 minutes
        
        $sql = "SELECT u.ID, u.display_name, 
                       COALESCE(um1.meta_value, 0) as last_activity,
                       COALESCE(um2.meta_value, 'away') as chat_status
                FROM {$wpdb->users} u
                LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'psource_chat_last_activity'
                LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'psource_chat_user_status'
                WHERE u.ID IN (" . implode(',', array_map('intval', $friends_list)) . ")
                AND COALESCE(um1.meta_value, 0) > %d
                ORDER BY u.display_name ASC
                LIMIT 50";
        
        return $wpdb->get_results($wpdb->prepare($sql, $time_threshold));
    }
}

/**
 * Chat Rooms Widget
 */
class Enhanced_Chat_Rooms_Widget extends \WP_Widget {
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(
            'psource_chat_rooms_widget',
            __('PS Chat Räume', 'psource-chat'),
            [
                'classname' => 'psource-chat-rooms-widget',
                'description' => __('Zeigt aktive Chat-Sitzungen der gesamten Website an.', 'psource-chat')
            ]
        );
    }
    
    /**
     * Widget frontend output
     */
    public function widget($args, $instance) {
        // Check if rooms widget is enabled
        $widget_options = psource_chat_get_extension_option('widgets', 'enable_rooms_widget', 'enabled');
        if ($widget_options !== 'enabled') {
            return;
        }
        
        // Only for logged-in users
        if (!is_user_logged_in()) {
            return;
        }
        
        $instance = wp_parse_args($instance, $this->get_defaults());
        
        echo $args['before_widget'];
        
        $title = apply_filters('widget_title', $instance['title']);
        if ($title) {
            echo $args['before_title'] . $title . $args['after_title'];
        }
        
        // Get active sessions
        $active_sessions = $this->get_active_sessions($instance['session_types']);
        
        if (!empty($active_sessions)) {
            echo '<ul class="psource-chat-rooms-list">';
            
            foreach ($active_sessions as $session) {
                echo '<li class="psource-chat-room-item">';
                
                $link_title = $this->get_session_display_title($session, $instance);
                echo '<a href="' . esc_url($session['session_url']) . '" class="psource-chat-room-link">';
                echo esc_html($link_title);
                echo '</a>';
                
                if ($instance['show_user_count'] === 'enabled') {
                    $user_count = $this->get_session_user_count($session);
                    echo ' <span class="psource-chat-room-users">(' . $user_count . ')</span>';
                }
                
                echo '</li>';
            }
            
            echo '</ul>';
        } else {
            echo '<p class="psource-chat-no-rooms">' . __('Keine aktiven Chat-Räume.', 'psource-chat') . '</p>';
        }
        
        echo $args['after_widget'];
    }
    
    /**
     * Widget backend form
     */
    public function form($instance) {
        $instance = wp_parse_args($instance, $this->get_defaults());
        ?>
        <p class="description">
            <?php _e('Dieses Widget zeigt alle aktiven Chat-Sitzungen auf der Website an.', 'psource-chat'); ?>
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Titel:', 'psource-chat'); ?></label>
            <input type="text" class="widefat" id="<?php echo $this->get_field_id('title'); ?>" 
                   name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo esc_attr($instance['title']); ?>"/>
        </p>
        
        <p>
            <label><?php _e('Session-Typen anzeigen:', 'psource-chat'); ?></label><br/>
            <?php
            $session_types = [
                'page' => __('Seiten-Chat', 'psource-chat'),
                'post' => __('Post-Chat', 'psource-chat'),
                'widget' => __('Widget-Chat', 'psource-chat'),
                'private' => __('Private Chats', 'psource-chat')
            ];
            
            if (class_exists('BuddyPress')) {
                $session_types['bp-group'] = __('BuddyPress Gruppen', 'psource-chat');
            }
            
            foreach ($session_types as $type => $label) {
                $checked = in_array($type, $instance['session_types']) ? 'checked="checked"' : '';
                ?>
                <label>
                    <input type="checkbox" name="<?php echo $this->get_field_name('session_types'); ?>[]" 
                           value="<?php echo esc_attr($type); ?>" <?php echo $checked; ?>/>
                    <?php echo esc_html($label); ?>
                </label><br/>
                <?php
            }
            ?>
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('show_user_count'); ?>"><?php _e('Benutzeranzahl anzeigen:', 'psource-chat'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('show_user_count'); ?>" 
                    name="<?php echo $this->get_field_name('show_user_count'); ?>">
                <option value="enabled" <?php selected($instance['show_user_count'], 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                <option value="disabled" <?php selected($instance['show_user_count'], 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
            </select>
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('show_title'); ?>"><?php _e('Titel-Format:', 'psource-chat'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('show_title'); ?>" 
                    name="<?php echo $this->get_field_name('show_title'); ?>">
                <option value="chat" <?php selected($instance['show_title'], 'chat'); ?>><?php _e('Chat-Titel', 'psource-chat'); ?></option>
                <option value="page" <?php selected($instance['show_title'], 'page'); ?>><?php _e('Seiten-Titel', 'psource-chat'); ?></option>
                <option value="both" <?php selected($instance['show_title'], 'both'); ?>><?php _e('Beide', 'psource-chat'); ?></option>
            </select>
        </p>
        <?php
    }
    
    /**
     * Widget update
     */
    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = sanitize_text_field($new_instance['title']);
        $instance['session_types'] = isset($new_instance['session_types']) ? 
            array_map('sanitize_text_field', $new_instance['session_types']) : [];
        $instance['show_user_count'] = sanitize_text_field($new_instance['show_user_count']);
        $instance['show_title'] = sanitize_text_field($new_instance['show_title']);
        
        return $instance;
    }
    
    /**
     * Get default widget values
     */
    private function get_defaults() {
        return [
            'title' => __('Aktive Chat-Räume', 'psource-chat'),
            'session_types' => ['page', 'post', 'widget'],
            'show_user_count' => 'enabled',
            'show_title' => 'page'
        ];
    }
    
    /**
     * Get active sessions
     */
    private function get_active_sessions($session_types) {
        if (function_exists('psource_chat_get_active_sessions')) {
            return psource_chat_get_active_sessions($session_types);
        }
        
        // Fallback implementation
        global $wpdb;
        
        $database = new \PSSource\Chat\Core\Database();
        return $database->get_active_sessions($session_types);
    }
    
    /**
     * Get session display title
     */
    private function get_session_display_title($session, $instance) {
        $title = '';
        
        switch ($instance['show_title']) {
            case 'chat':
                $title = $session['session_title'] ?? __('Chat', 'psource-chat');
                break;
            case 'page':
                $title = $session['page_title'] ?? $session['session_title'] ?? __('Chat', 'psource-chat');
                break;
            case 'both':
                $chat_title = $session['session_title'] ?? '';
                $page_title = $session['page_title'] ?? '';
                $title = !empty($page_title) ? $page_title : $chat_title;
                if (!empty($chat_title) && !empty($page_title) && $chat_title !== $page_title) {
                    $title .= ' (' . $chat_title . ')';
                }
                break;
        }
        
        return !empty($title) ? $title : __('Chat', 'psource-chat');
    }
    
    /**
     * Get session user count
     */
    private function get_session_user_count($session) {
        if (function_exists('psource_chat_get_session_user_count')) {
            return psource_chat_get_session_user_count($session['session_id']);
        }
        
        // Fallback implementation
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}psource_chat_messages 
             WHERE session_id = %s AND timestamp > %d",
            $session['session_id'],
            time() - 300 // Active in last 5 minutes
        ));
        
        return intval($count);
    }
}

/**
 * Register all widgets
 */
function psource_chat_register_enhanced_widgets() {
    // Check if widgets extension is enabled
    $widget_options = psource_chat_get_extension_option('widgets', [], []);
    
    if (($widget_options['enable_chat_widget'] ?? 'enabled') === 'enabled') {
        register_widget('PSSource\Chat\Frontend\Enhanced_Chat_Widget');
    }
    
    if (($widget_options['enable_status_widget'] ?? 'enabled') === 'enabled') {
        register_widget('PSSource\Chat\Frontend\Enhanced_Chat_Status_Widget');
    }
    
    if (($widget_options['enable_friends_widget'] ?? 'enabled') === 'enabled') {
        register_widget('PSSource\Chat\Frontend\Enhanced_Chat_Friends_Widget');
    }
    
    if (($widget_options['enable_rooms_widget'] ?? 'enabled') === 'enabled') {
        register_widget('PSSource\Chat\Frontend\Enhanced_Chat_Rooms_Widget');
    }
}

// Register widgets when extension is active
add_action('widgets_init', 'PSSource\Chat\Frontend\psource_chat_register_enhanced_widgets');
