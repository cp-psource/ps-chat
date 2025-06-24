<?php
/**
 * Chat Engine - Core Chat Functionality
 * 
 * @package PSSource\Chat\Core
 */

namespace PSSource\Chat\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Chat Engine Class
 */
class Chat_Engine {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Chat options
     */
    private $options = [];
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_options();
        $this->init_hooks();
    }
    
    /**
     * Load options
     */
    private function load_options() {
        $defaults = [
            'enable_sound' => true,
            'enable_emoji' => true,
            'max_message_length' => 500,
            'chat_timeout' => 300,
            'enable_private_chat' => true,
            'allow_guest_chat' => false,
            'moderate_messages' => false,
            'bad_words_filter' => true,
            'polling_interval' => 3000,
            'heartbeat_interval' => 30000
        ];
        
        $this->options = get_option('psource_chat_options', $defaults);
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_psource_chat_send_message', [$this, 'send_message']);
        add_action('wp_ajax_psource_chat_get_messages', [$this, 'get_messages']);
        add_action('wp_ajax_psource_chat_set_status', [$this, 'set_user_status']);
        add_action('wp_ajax_psource_chat_get_users', [$this, 'get_online_users']);
        add_action('wp_ajax_psource_chat_get_rooms', [$this, 'get_chat_rooms']);
        add_action('wp_ajax_psource_chat_join_room', [$this, 'join_room']);
        add_action('wp_ajax_psource_chat_leave_room', [$this, 'leave_room']);
        add_action('wp_ajax_psource_chat_heartbeat', [$this, 'heartbeat']);
        add_action('wp_ajax_psource_chat_typing', [$this, 'handle_typing']);
        
        // Non-privileged users
        if (($this->options['allow_guest_chat'] ?? false)) {
            add_action('wp_ajax_nopriv_psource_chat_send_message', [$this, 'send_message']);
            add_action('wp_ajax_nopriv_psource_chat_get_messages', [$this, 'get_messages']);
            add_action('wp_ajax_nopriv_psource_chat_set_status', [$this, 'set_user_status']);
            add_action('wp_ajax_nopriv_psource_chat_get_users', [$this, 'get_online_users']);
            add_action('wp_ajax_nopriv_psource_chat_get_rooms', [$this, 'get_chat_rooms']);
            add_action('wp_ajax_nopriv_psource_chat_join_room', [$this, 'join_room']);
            add_action('wp_ajax_nopriv_psource_chat_leave_room', [$this, 'leave_room']);
            add_action('wp_ajax_nopriv_psource_chat_heartbeat', [$this, 'heartbeat']);
            add_action('wp_ajax_nopriv_psource_chat_typing', [$this, 'handle_typing']);
        }
    }
    
    /**
     * Send message
     */
    public function send_message() {
        // Improved nonce verification with better error handling
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'psource_chat_nonce')) {
            wp_die(json_encode([
                'success' => false, 
                'error' => __('Sicherheitsprüfung fehlgeschlagen. Bitte Seite neu laden.', 'psource-chat')
            ]));
        }
        
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $room_id = intval($_POST['room_id'] ?? 0);
        $recipient_id = intval($_POST['recipient_id'] ?? 0);
        $message_type = sanitize_text_field($_POST['type'] ?? 'public');
        
        if (empty($message)) {
            wp_die(json_encode(['success' => false, 'error' => __('Nachricht darf nicht leer sein.', 'psource-chat')]));
        }
        
        if (strlen($message) > $this->get_option('max_message_length', 1000)) {
            wp_die(json_encode(['success' => false, 'error' => __('Nachricht zu lang.', 'psource-chat')]));
        }
        
        $user_id = get_current_user_id();
        $user_name = '';
        $user_avatar = '';
        
        if ($user_id > 0) {
            $user = get_userdata($user_id);
            $user_name = $user->display_name;
            $user_avatar = get_avatar_url($user_id, ['size' => 32]);
        } else {
            if (!($this->options['allow_guest_chat'] ?? false)) {
                wp_die(json_encode(['success' => false, 'error' => __('Gäste dürfen nicht chatten.', 'psource-chat')]));
            }
            $user_name = sanitize_text_field($_POST['guest_name'] ?? 'Gast');
            $user_avatar = get_avatar_url(0, ['size' => 32]);
        }
        
        // Filter bad words
        if ($this->options['bad_words_filter']) {
            $message = $this->filter_bad_words($message);
        }
        
        // Save message to database
        global $wpdb;
        $table_name = \PSSource\Chat\Core\Database::get_table_name('messages');
        
        $result = $wpdb->insert(
            $table_name,
            [
                'user_id' => $user_id,
                'user_login' => $user_id > 0 ? get_userdata($user_id)->user_login : '',
                'user_name' => $user_name,
                'user_email' => $user_id > 0 ? get_userdata($user_id)->user_email : '',
                'user_ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                'message_text' => $message,
                'message_time' => current_time('mysql'),
                'session_id' => 'dashboard_' . time(),
                'session_type' => 'dashboard',
                'message_type' => $message_type,
                'room_id' => $room_id,
                'recipient_id' => $recipient_id,
                'blog_id' => get_current_blog_id()
            ],
            [
                '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d'
            ]
        );
        
        if ($result === false) {
            wp_die(json_encode(['success' => false, 'error' => __('Fehler beim Speichern der Nachricht.', 'psource-chat')]));
        }
        
        $message_id = $wpdb->insert_id;
        
        wp_die(json_encode([
            'success' => true,
            'message' => [
                'id' => $message_id,
                'user_id' => $user_id,
                'user_name' => $user_name,
                'user_avatar' => $user_avatar,
                'message' => $message,
                'timestamp' => current_time('timestamp'),
                'formatted_time' => current_time('H:i'),
                'message_type' => $message_type,
                'room_id' => $room_id
            ]
        ]));
    }
    
    /**
     * Get messages
     */
    public function get_messages() {
        // Improved nonce verification
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'psource_chat_nonce')) {
            wp_die(json_encode([
                'success' => false, 
                'error' => __('Sicherheitsprüfung fehlgeschlagen.', 'psource-chat')
            ]));
        }
        
        $room_id = intval($_POST['room_id'] ?? 0);
        $last_message_id = intval($_POST['last_message_id'] ?? 0);
        $message_type = sanitize_text_field($_POST['type'] ?? 'public');
        
        global $wpdb;
        $table_name = \PSSource\Chat\Core\Database::get_table_name('messages');
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        if (!$table_exists) {
            wp_die(json_encode([
                'success' => false, 
                'error' => __('Chat-Tabelle nicht gefunden. Plugin neu aktivieren.', 'psource-chat')
            ]));
        }
        
        $where_clauses = [];
        $where_values = [];
        
        if ($last_message_id > 0) {
            $where_clauses[] = 'id > %d';
            $where_values[] = $last_message_id;
        }
        
        if ($room_id > 0) {
            $where_clauses[] = 'room_id = %d';
            $where_values[] = $room_id;
        }
        
        $where_clauses[] = 'message_type = %s';
        $where_values[] = $message_type;
        
        // Private messages for current user
        if ($message_type === 'private' && is_user_logged_in()) {
            $user_id = get_current_user_id();
            $where_clauses[] = '(recipient_id = %d OR user_id = %d)';
            $where_values[] = $user_id;
            $where_values[] = $user_id;
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        
        $sql = "SELECT * FROM {$table_name} WHERE {$where_sql} ORDER BY message_time ASC LIMIT 50";
        $prepared_sql = $wpdb->prepare($sql, $where_values);
        
        $messages = $wpdb->get_results($prepared_sql);
        
        $formatted_messages = [];
        foreach ($messages as $message) {
            $user_avatar = '';
            if ($message->user_id > 0) {
                $user_avatar = get_avatar_url($message->user_id, ['size' => 32]);
            } else {
                $user_avatar = get_avatar_url(0, ['size' => 32]);
            }
            
            $formatted_messages[] = [
                'id' => intval($message->id),
                'user_id' => intval($message->user_id),
                'user_name' => esc_html($message->user_name),
                'user_avatar' => $user_avatar,
                'message' => wp_kses_post($message->message_text),
                'timestamp' => strtotime($message->message_time),
                'formatted_time' => date_i18n(get_option('time_format'), strtotime($message->message_time)),
                'message_type' => $message->message_type
            ];
        }
        
        wp_die(json_encode(['success' => true, 'data' => ['messages' => $formatted_messages]]));
    }
    
    /**
     * Set user status
     */
    public function set_user_status() {
        check_ajax_referer('psource_chat_nonce', 'nonce');
        
        $status = sanitize_text_field($_POST['status'] ?? 'online');
        $user_id = get_current_user_id();
        
        if ($user_id === 0) {
            wp_die(json_encode(['success' => false, 'error' => __('Benutzer nicht eingeloggt.', 'psource-chat')]));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'psource_chat_users';
        
        $wpdb->replace(
            $table_name,
            [
                'user_id' => $user_id,
                'status' => $status,
                'last_activity' => current_time('mysql')
            ],
            ['%d', '%s', '%s']
        );
        
        wp_die(json_encode(['success' => true, 'status' => $status]));
    }
    
    /**
     * Get online users
     */
    public function get_online_users() {
        check_ajax_referer('psource_chat_nonce', 'nonce');
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'psource_chat_users';
        
        // Use default timeout if not set - check both locations
        $timeout = 900; // 15 minutes default
        if (isset($this->options['chat_timeout'])) {
            $timeout = intval($this->options['chat_timeout']);
        } elseif (get_option('psource_chat_options') && isset(get_option('psource_chat_options')['chat_timeout'])) {
            $timeout = intval(get_option('psource_chat_options')['chat_timeout']);
        }
        $cutoff_time = date('Y-m-d H:i:s', time() - $timeout);
        
        // Check if table exists and has correct structure
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
        if (!$table_exists) {
            wp_send_json_success(['users' => []]);
            return;
        }
        
        // Check if user_id column exists
        $columns = $wpdb->get_col("DESCRIBE {$table_name}");
        if (!in_array('user_id', $columns)) {
            // Try with id column instead
            $users = $wpdb->get_results($wpdb->prepare(
                "SELECT id as user_id, status, last_activity FROM {$table_name} WHERE last_activity > %s ORDER BY last_activity DESC",
                $cutoff_time
            ));
        } else {
            $users = $wpdb->get_results($wpdb->prepare(
                "SELECT user_id, status, last_activity FROM {$table_name} WHERE last_activity > %s ORDER BY last_activity DESC",
                $cutoff_time
            ));
        }
        
        $online_users = [];
        foreach ($users as $user) {
            $user_data = get_userdata($user->user_id);
            if ($user_data) {
                $online_users[] = [
                    'id' => intval($user->user_id),
                    'name' => esc_html($user_data->display_name),
                    'avatar' => get_avatar_url($user->user_id, ['size' => 32]),
                    'status' => $user->status,
                    'last_activity' => strtotime($user->last_activity)
                ];
            }
        }
        
        wp_send_json_success(['users' => $online_users]);
    }
    
    /**
     * Get chat rooms
     */
    public function get_chat_rooms() {
        check_ajax_referer('psource_chat_nonce', 'nonce');
        
        global $wpdb;
        $rooms_table = $wpdb->prefix . 'psource_chat_rooms';
        
        $rooms = $wpdb->get_results("SELECT * FROM {$rooms_table} WHERE active = 1 ORDER BY name ASC");
        
        $formatted_rooms = [];
        foreach ($rooms as $room) {
            // Count active users in room
            $users_table = $wpdb->prefix . 'psource_chat_room_users';
            $user_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$users_table} WHERE room_id = %d AND last_activity > %s",
                $room->id,
                date('Y-m-d H:i:s', time() - $this->options['chat_timeout'])
            ));
            
            $formatted_rooms[] = [
                'id' => intval($room->id),
                'name' => esc_html($room->name),
                'description' => esc_html($room->description),
                'user_count' => intval($user_count),
                'private' => intval($room->private),
                'created' => strtotime($room->created)
            ];
        }
        
        wp_die(json_encode(['success' => true, 'rooms' => $formatted_rooms]));
    }
    
    /**
     * Join room
     */
    public function join_room() {
        check_ajax_referer('psource_chat_nonce', 'nonce');
        
        $room_id = intval($_POST['room_id'] ?? 0);
        $user_id = get_current_user_id();
        
        if ($user_id === 0) {
            wp_die(json_encode(['success' => false, 'error' => __('Benutzer nicht eingeloggt.', 'psource-chat')]));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'psource_chat_room_users';
        
        $wpdb->replace(
            $table_name,
            [
                'room_id' => $room_id,
                'user_id' => $user_id,
                'joined' => current_time('mysql'),
                'last_activity' => current_time('mysql')
            ],
            ['%d', '%d', '%s', '%s']
        );
        
        wp_die(json_encode(['success' => true, 'room_id' => $room_id]));
    }
    
    /**
     * Leave room
     */
    public function leave_room() {
        check_ajax_referer('psource_chat_nonce', 'nonce');
        
        $room_id = intval($_POST['room_id'] ?? 0);
        $user_id = get_current_user_id();
        
        if ($user_id === 0) {
            wp_die(json_encode(['success' => false, 'error' => __('Benutzer nicht eingeloggt.', 'psource-chat')]));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'psource_chat_room_users';
        
        $wpdb->delete(
            $table_name,
            [
                'room_id' => $room_id,
                'user_id' => $user_id
            ],
            ['%d', '%d']
        );
        
        wp_die(json_encode(['success' => true, 'room_id' => $room_id]));
    }
    
    /**
     * Heartbeat - Keep user activity alive
     */
    public function heartbeat() {
        check_ajax_referer('psource_chat_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        
        if ($user_id > 0) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'psource_chat_users';
            
            $wpdb->query($wpdb->prepare(
                "UPDATE {$table_name} SET last_activity = %s WHERE user_id = %d",
                current_time('mysql'),
                $user_id
            ));
        }
        
        wp_die(json_encode(['success' => true, 'timestamp' => time()]));
    }
    
    /**
     * Handle typing indicator
     */
    public function handle_typing() {
        check_ajax_referer('psource_chat_nonce', 'nonce');
        
        $typing = isset($_POST['typing']) && $_POST['typing'] === 'true';
        $user_id = get_current_user_id();
        
        // Store typing status in transient (expires after 5 seconds)
        if ($typing) {
            set_transient('psource_chat_typing_' . $user_id, time(), 5);
        } else {
            delete_transient('psource_chat_typing_' . $user_id);
        }
        
        wp_die(json_encode(['success' => true]));
    }
    
    /**
     * Filter bad words
     */
    private function filter_bad_words($message) {
        $bad_words_file = PSOURCE_CHAT_PLUGIN_DIR . 'lib/bad_words_list.php';
        
        if (file_exists($bad_words_file)) {
            include $bad_words_file;
            
            if (isset($bad_words) && is_array($bad_words)) {
                foreach ($bad_words as $bad_word) {
                    $replacement = str_repeat('*', strlen($bad_word));
                    $message = str_ireplace($bad_word, $replacement, $message);
                }
            }
        }
        
        return $message;
    }
    
    /**
     * Get chat options
     */
    public function get_options() {
        return $this->options;
    }
    
    /**
     * Update chat options
     */
    public function update_options($new_options) {
        $this->options = array_merge($this->options, $new_options);
        update_option('psource_chat_options', $this->options);
    }
    
    /**
     * Get a chat option with fallback
     */
    private function get_option($key, $default = null) {
        // Zuerst in den geladenen Optionen schauen
        if (isset($this->options[$key])) {
            return $this->options[$key];
        }
        
        // Dann in den neuen WordPress-Optionen schauen
        $wp_option = get_option('psource_chat_' . $key, null);
        if ($wp_option !== null) {
            return $wp_option;
        }
        
        // Fallback auf Default
        return $default;
    }
}
