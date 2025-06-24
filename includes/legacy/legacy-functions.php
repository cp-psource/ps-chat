<?php
/**
 * Legacy Functions
 * 
 * Diese Datei enthält die wichtigsten Legacy-Funktionen aus dem alten Plugin,
 * die für die Kompatibilität und Vollständigkeit benötigt werden.
 * 
 * @package PSSource\Chat\Legacy
 */

namespace PSSource\Chat\Legacy;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Legacy Functions Class
 */
class Legacy_Functions {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', [$this, 'init_legacy_support']);
    }
    
    /**
     * Initialize legacy support
     */
    public function init_legacy_support() {
        // Stelle sicher, dass die wichtigsten Legacy-Funktionen verfügbar sind
        $this->setup_legacy_globals();
        $this->setup_legacy_functions();
    }
    
    /**
     * Setup legacy global variables
     */
    private function setup_legacy_globals() {
        global $psource_chat;
        
        if (!isset($psource_chat)) {
            $psource_chat = new \stdClass();
        }
        
        // Legacy compatibility methods
        if (!method_exists($psource_chat, 'get_option')) {
            $psource_chat->get_option = [$this, 'legacy_get_option'];
        }
        
        if (!method_exists($psource_chat, 'set_option')) {
            $psource_chat->set_option = [$this, 'legacy_set_option'];
        }
        
        if (!method_exists($psource_chat, 'process_chat_shortcode')) {
            $psource_chat->process_chat_shortcode = [$this, 'legacy_process_chat_shortcode'];
        }
    }
    
    /**
     * Setup legacy functions that don't exist
     */
    private function setup_legacy_functions() {
        // Include legacy function definitions if they don't exist
        if (!function_exists('psource_chat_get_help_item')) {
            function psource_chat_get_help_item($item, $type = 'tip') {
                return '<span class="help-icon" title="Hilfe für ' . esc_attr($item) . '">?</span>';
            }
        }
        
        if (!function_exists('psource_chat_utility_get_post_info')) {
            function psource_chat_utility_get_post_info() {
                $post_info = array();
                
                if (isset($_GET['post'])) {
                    $post_info['post_id'] = (int) $_GET['post'];
                } elseif (isset($_POST['post_ID'])) {
                    $post_info['post_id'] = (int) $_POST['post_ID'];
                } else {
                    $post_info['post_id'] = 0;
                }
                
                if ($post_info['post_id']) {
                    $post = get_post($post_info['post_id']);
                    if ($post) {
                        $post_info['post_type'] = $post->post_type;
                    }
                } else {
                    if (isset($_GET['post_type'])) {
                        $post_info['post_type'] = $_GET['post_type'];
                    } else {
                        $post_info['post_type'] = 'post';
                    }
                }
                
                return $post_info;
            }
        }
        
        if (!function_exists('psource_chat_check_size_qualifier')) {
            function psource_chat_check_size_qualifier($size_str = '', $size_qualifiers = array('px', 'pt', 'em', '%')) {
                if (empty($size_str)) {
                    $size_str = "0";
                }
                
                if (count($size_qualifiers)) {
                    foreach ($size_qualifiers as $size_qualifier) {
                        if (empty($size_qualifier)) {
                            continue;
                        }
                        
                        if (substr($size_str, strlen($size_qualifier) * -1, strlen($size_qualifier)) === $size_qualifier) {
                            return $size_str;
                        }
                    }
                    
                    return intval($size_str) . "px";
                }
                
                return $size_str;
            }
        }
        
        if (!function_exists('psource_chat_get_user_role_highest_level')) {
            function psource_chat_get_user_role_highest_level($user_role_capabilities = array()) {
                $user_role_highest_level = 0;
                
                foreach ($user_role_capabilities as $capability => $is_set) {
                    if (strncasecmp($capability, 'level_', strlen('level_')) == 0) {
                        $capability_int = intval(str_replace('level_', '', $capability));
                        if ($capability_int > $user_role_highest_level) {
                            $user_role_highest_level = $capability_int;
                        }
                    }
                }
                
                return $user_role_highest_level;
            }
        }
        
        if (!function_exists('psource_chat_is_moderator')) {
            function psource_chat_is_moderator($chat_session, $debug = false) {
                global $current_user, $bp;
                
                if ($chat_session['session_type'] === "bp-group") {
                    if ((function_exists('groups_is_user_mod')) && (function_exists('groups_is_user_admin'))) {
                        if ((groups_is_user_mod($bp->loggedin_user->id, $bp->groups->current_group->id))
                            || (groups_is_user_admin($bp->loggedin_user->id, $bp->groups->current_group->id))
                            || (is_super_admin())
                        ) {
                            return true;
                        }
                    }
                    return false;
                }
                
                if ($chat_session['session_type'] === "private") {
                    global $psource_chat;
                    
                    if (!isset($chat_session['invite-info']['message']['host']['auth_hash'])) {
                        return false;
                    } else if (!isset($psource_chat->chat_auth['auth_hash'])) {
                        return false;
                    } else if ($chat_session['invite-info']['message']['host']['auth_hash'] === $psource_chat->chat_auth['auth_hash']) {
                        return true;
                    } else {
                        return false;
                    }
                }
                
                // all others
                if ((!is_array($chat_session['moderator_roles'])) || (!count($chat_session['moderator_roles']))) {
                    return false;
                }
                
                if (!is_multisite()) {
                    if ($current_user->ID) {
                        foreach ($chat_session['moderator_roles'] as $role) {
                            if (in_array($role, $current_user->roles)) {
                                return true;
                            }
                        }
                    }
                } else {
                    if ((is_super_admin()) && (array_search('administrator', $chat_session['moderator_roles']) !== false)) {
                        return true;
                    }
                    
                    if ($current_user->ID) {
                        foreach ($chat_session['moderator_roles'] as $role) {
                            if (in_array($role, $current_user->roles)) {
                                return true;
                            }
                        }
                    }
                }
                
                return false;
            }
        }
    }
    
    /**
     * Legacy get_option method
     */
    public function legacy_get_option($option_key, $section = 'page', $default = '') {
        $options = get_option('psource_chat_options', []);
        
        // Handle section-based options
        if ($section && isset($options[$section][$option_key])) {
            return $options[$section][$option_key];
        }
        
        // Handle global options
        if (isset($options[$option_key])) {
            return $options[$option_key];
        }
        
        // Legacy option mappings
        $legacy_mappings = [
            'enable_sound' => true,
            'enable_emoji' => true,
            'max_message_length' => 500,
            'chat_timeout' => 300,
            'auto_refresh_interval' => 5,
            'show_user_list' => true,
            'allow_guest_chat' => false,
            'moderate_messages' => false,
            'log_creation' => 'enabled',
            'log_display' => 'disabled',
            'session_status_message' => 'Chat ist geschlossen',
            'blocked_words_active' => 'disabled',
            'blocked_ip_addresses_active' => 'disabled',
            'box_height' => '300px',
            'box_width' => '100%',
            'box_title' => 'Chat',
            'dashboard_widget' => 'enabled',
            'dashboard_widget_title' => 'Chat',
            'dashboard_widget_height' => '300px',
        ];
        
        if (isset($legacy_mappings[$option_key])) {
            return $legacy_mappings[$option_key];
        }
        
        return $default;
    }
    
    /**
     * Legacy set_option method
     */
    public function legacy_set_option($option_key, $value, $section = 'page') {
        $options = get_option('psource_chat_options', []);
        
        if ($section) {
            if (!isset($options[$section])) {
                $options[$section] = [];
            }
            $options[$section][$option_key] = $value;
        } else {
            $options[$option_key] = $value;
        }
        
        update_option('psource_chat_options', $options);
    }
    
    /**
     * Legacy process_chat_shortcode method
     */
    public function legacy_process_chat_shortcode($instance = []) {
        // Fallback für Chat-Shortcode-Verarbeitung
        $defaults = [
            'id' => 'chat-' . wp_generate_password(8, false),
            'box_height' => '300px',
            'box_width' => '100%',
            'box_title' => 'Chat',
            'session_status' => 'open'
        ];
        
        $instance = wp_parse_args($instance, $defaults);
        
        ob_start();
        ?>
        <div class="psource-chat-box" id="<?php echo esc_attr($instance['id']); ?>" style="height: <?php echo esc_attr($instance['box_height']); ?>; width: <?php echo esc_attr($instance['box_width']); ?>;">
            <div class="psource-chat-module-header">
                <h3><?php echo esc_html($instance['box_title']); ?></h3>
            </div>
            <div class="psource-chat-module-message-area">
                <div class="psource-chat-messages">
                    <p><em>Chat wird geladen...</em></p>
                </div>
                <?php if ($instance['session_status'] === 'open'): ?>
                <div class="psource-chat-send-meta">
                    <input type="text" class="psource-chat-message-input" placeholder="Nachricht eingeben..." />
                    <button type="button" class="psource-chat-send-button">Senden</button>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Initialize Legacy Functions
new Legacy_Functions();
