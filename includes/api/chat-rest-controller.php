<?php
/**
 * Chat REST API Controller
 * 
 * @package PSSource\Chat\API
 */

namespace PSSource\Chat\API;

use PSSource\Chat\Core\Database;
use PSSource\Chat\Core\Plugin;
use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST API endpoints for chat functionality
 */
class Chat_REST_Controller extends WP_REST_Controller {
    
    /**
     * Namespace
     */
    protected $namespace = 'psource-chat/v1';
    
    /**
     * Resource name
     */
    protected $resource_name = 'chat';
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }
    
    /**
     * Register routes
     */
    public function register_routes() {
        // Get messages
        register_rest_route($this->namespace, '/messages/(?P<session_id>[\w\-]+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_messages'],
                'permission_callback' => [$this, 'check_permission'],
                'args' => $this->get_messages_args()
            ]
        ]);
        
        // Send message
        register_rest_route($this->namespace, '/messages', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'send_message'],
                'permission_callback' => [$this, 'check_send_permission'],
                'args' => $this->get_send_message_args()
            ]
        ]);
        
        // Get sessions
        register_rest_route($this->namespace, '/sessions', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_sessions'],
                'permission_callback' => [$this, 'check_admin_permission']
            ]
        ]);
        
        // Create session
        register_rest_route($this->namespace, '/sessions', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_session'],
                'permission_callback' => [$this, 'check_permission'],
                'args' => $this->get_create_session_args()
            ]
        ]);
        
        // Join session
        register_rest_route($this->namespace, '/sessions/(?P<session_id>[\w\-]+)/join', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'join_session'],
                'permission_callback' => [$this, 'check_permission']
            ]
        ]);
        
        // Leave session
        register_rest_route($this->namespace, '/sessions/(?P<session_id>[\w\-]+)/leave', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'leave_session'],
                'permission_callback' => [$this, 'check_permission']
            ]
        ]);
        
        // Get active users
        register_rest_route($this->namespace, '/sessions/(?P<session_id>[\w\-]+)/users', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_active_users'],
                'permission_callback' => [$this, 'check_permission']
            ]
        ]);
        
        // Typing status
        register_rest_route($this->namespace, '/sessions/(?P<session_id>[\w\-]+)/typing', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'update_typing_status'],
                'permission_callback' => [$this, 'check_permission'],
                'args' => [
                    'is_typing' => [
                        'required' => true,
                        'type' => 'boolean'
                    ]
                ]
            ]
        ]);
    }
    
    /**
     * Get messages
     */
    public function get_messages(WP_REST_Request $request) {
        $session_id = $request['session_id'];
        $limit = $request['limit'] ?? 50;
        $offset = $request['offset'] ?? 0;
        $since = $request['since'] ?? null;
        
        // Validate session exists
        $session = Database::get_session($session_id);
        if (!$session) {
            return new WP_Error('session_not_found', __('Session not found', 'psource-chat'), ['status' => 404]);
        }
        
        // Get messages
        $messages = Database::get_messages($session_id, $limit, $offset, $since);
        
        // Format messages
        $formatted_messages = array_map([$this, 'format_message'], $messages);
        
        return new WP_REST_Response([
            'messages' => $formatted_messages,
            'session_id' => $session_id,
            'timestamp' => current_time('mysql')
        ]);
    }
    
    /**
     * Send message
     */
    public function send_message(WP_REST_Request $request) {
        $session_id = $request['session_id'];
        $message_text = sanitize_textarea_field($request['message']);
        $is_private = (bool) $request['is_private'];
        
        // Validate session
        $session = Database::get_session($session_id);
        if (!$session) {
            return new WP_Error('session_not_found', __('Session not found', 'psource-chat'), ['status' => 404]);
        }
        
        // Check message length
        $plugin = Plugin::get_instance();
        $max_length = $plugin->get_option('max_message_length', 500);
        if (strlen($message_text) > $max_length) {
            return new WP_Error('message_too_long', 
                sprintf(__('Message too long. Maximum %d characters allowed.', 'psource-chat'), $max_length), 
                ['status' => 400]);
        }
        
        // Get user data
        $user_data = $this->get_current_user_data();
        
        // Filter message
        if ($plugin->get_option('bad_words_filter', true)) {
            $message_text = $this->filter_bad_words($message_text);
        }
        
        // Prepare message data
        $message_data = [
            'message_text' => $message_text,
            'user_id' => $user_data['user_id'],
            'user_login' => $user_data['user_login'],
            'user_name' => $user_data['user_name'],
            'user_email' => $user_data['user_email'],
            'user_ip' => $user_data['user_ip'],
            'is_private' => $is_private ? 1 : 0,
            'is_moderated' => $plugin->get_option('moderate_messages', false) ? 1 : 0
        ];
        
        // Save message
        $message_id = Database::add_message($session_id, $message_data);
        
        if (!$message_id) {
            return new WP_Error('save_failed', __('Could not save message', 'psource-chat'), ['status' => 500]);
        }
        
        // Update user activity
        Database::update_user_activity($session_id, $user_data['user_id']);
        
        // Format and return message
        $message_data['id'] = $message_id;
        $message_data['message_time'] = current_time('mysql');
        
        return new WP_REST_Response([
            'message' => $this->format_message((object) $message_data),
            'success' => true
        ], 201);
    }
    
    /**
     * Get sessions (admin only)
     */
    public function get_sessions(WP_REST_Request $request) {
        global $wpdb;
        
        $sessions_table = Database::get_table_name('sessions');
        $user_sessions_table = Database::get_table_name('user_sessions');
        
        $sessions = $wpdb->get_results(
            "SELECT s.*, COUNT(us.user_id) as user_count 
             FROM $sessions_table s 
             LEFT JOIN $user_sessions_table us ON s.session_id = us.session_id 
                 AND us.session_status = 'active'
             WHERE s.session_status = 'active' 
             GROUP BY s.id 
             ORDER BY s.updated_on DESC"
        );
        
        return new WP_REST_Response([
            'sessions' => array_map([$this, 'format_session'], $sessions)
        ]);
    }
    
    /**
     * Create session
     */
    public function create_session(WP_REST_Request $request) {
        $session_type = sanitize_text_field($request['session_type'] ?? 'site');
        $session_host = sanitize_text_field($request['session_host'] ?? $_SERVER['HTTP_HOST']);
        
        $session_id = Database::create_session($session_type, $session_host);
        
        if (!$session_id) {
            return new WP_Error('create_failed', __('Could not create session', 'psource-chat'), ['status' => 500]);
        }
        
        return new WP_REST_Response([
            'session_id' => $session_id,
            'session_type' => $session_type,
            'created' => true
        ], 201);
    }
    
    /**
     * Join session
     */
    public function join_session(WP_REST_Request $request) {
        $session_id = $request['session_id'];
        
        // Validate session
        $session = Database::get_session($session_id);
        if (!$session) {
            return new WP_Error('session_not_found', __('Session not found', 'psource-chat'), ['status' => 404]);
        }
        
        // Add user to session
        $user_data = $this->get_current_user_data();
        $result = Database::add_user_to_session($session_id, $user_data);
        
        if (!$result) {
            return new WP_Error('join_failed', __('Could not join session', 'psource-chat'), ['status' => 500]);
        }
        
        // Get recent messages and active users
        $messages = Database::get_messages($session_id, 20);
        $active_users = Database::get_active_users($session_id);
        
        return new WP_REST_Response([
            'session_id' => $session_id,
            'messages' => array_map([$this, 'format_message'], $messages),
            'active_users' => array_map([$this, 'format_user'], $active_users),
            'joined' => true
        ]);
    }
    
    /**
     * Leave session
     */
    public function leave_session(WP_REST_Request $request) {
        global $wpdb;
        
        $session_id = $request['session_id'];
        $user_data = $this->get_current_user_data();
        
        $table = Database::get_table_name('user_sessions');
        $result = $wpdb->update(
            $table,
            ['session_status' => 'inactive'],
            [
                'session_id' => $session_id,
                'user_id' => $user_data['user_id']
            ]
        );
        
        return new WP_REST_Response([
            'session_id' => $session_id,
            'left' => true
        ]);
    }
    
    /**
     * Get active users
     */
    public function get_active_users(WP_REST_Request $request) {
        $session_id = $request['session_id'];
        
        $users = Database::get_active_users($session_id);
        
        return new WP_REST_Response([
            'users' => array_map([$this, 'format_user'], $users),
            'count' => count($users)
        ]);
    }
    
    /**
     * Update typing status
     */
    public function update_typing_status(WP_REST_Request $request) {
        $session_id = $request['session_id'];
        $is_typing = $request['is_typing'];
        
        $user_data = $this->get_current_user_data();
        $transient_key = "chat_typing_{$session_id}_{$user_data['user_id']}";
        
        if ($is_typing) {
            set_transient($transient_key, $user_data['user_name'], 10);
        } else {
            delete_transient($transient_key);
        }
        
        return new WP_REST_Response([
            'typing_status' => $is_typing,
            'updated' => true
        ]);
    }
    
    /**
     * Permission callbacks
     */
    public function check_permission($request) {
        // Allow if user is logged in or guest chat is enabled
        if (is_user_logged_in()) {
            return true;
        }
        
        $plugin = Plugin::get_instance();
        return $plugin->get_option('allow_guest_chat', false);
    }
    
    public function check_send_permission($request) {
        return $this->check_permission($request);
    }
    
    public function check_admin_permission($request) {
        return current_user_can('manage_chat');
    }
    
    /**
     * Argument definitions
     */
    private function get_messages_args() {
        return [
            'session_id' => [
                'required' => true,
                'type' => 'string'
            ],
            'limit' => [
                'type' => 'integer',
                'default' => 50,
                'minimum' => 1,
                'maximum' => 100
            ],
            'offset' => [
                'type' => 'integer',
                'default' => 0,
                'minimum' => 0
            ],
            'since' => [
                'type' => 'string',
                'format' => 'date-time'
            ]
        ];
    }
    
    private function get_send_message_args() {
        return [
            'session_id' => [
                'required' => true,
                'type' => 'string'
            ],
            'message' => [
                'required' => true,
                'type' => 'string',
                'minLength' => 1,
                'maxLength' => 2000
            ],
            'is_private' => [
                'type' => 'boolean',
                'default' => false
            ]
        ];
    }
    
    private function get_create_session_args() {
        return [
            'session_type' => [
                'type' => 'string',
                'default' => 'site',
                'enum' => ['site', 'page', 'post', 'custom']
            ],
            'session_host' => [
                'type' => 'string'
            ]
        ];
    }
    
    /**
     * Formatting methods
     */
    private function format_message($message) {
        return [
            'id' => (int) $message->id,
            'user_id' => (int) $message->user_id,
            'user_name' => $message->user_name,
            'message_text' => $this->format_message_text($message->message_text),
            'message_time' => $message->message_time,
            'is_private' => (bool) $message->is_private,
            'is_moderated' => (bool) $message->is_moderated,
            'time_ago' => human_time_diff(strtotime($message->message_time)),
            'avatar' => get_avatar_url($message->user_id, ['size' => 32])
        ];
    }
    
    private function format_user($user) {
        return [
            'user_id' => (int) $user->user_id,
            'user_name' => $user->user_name,
            'last_seen' => $user->last_seen,
            'time_ago' => human_time_diff(strtotime($user->last_seen)),
            'avatar' => get_avatar_url($user->user_id, ['size' => 24])
        ];
    }
    
    private function format_session($session) {
        return [
            'id' => (int) $session->id,
            'session_id' => $session->session_id,
            'session_type' => $session->session_type,
            'session_host' => $session->session_host,
            'created_on' => $session->created_on,
            'updated_on' => $session->updated_on,
            'user_count' => (int) $session->user_count
        ];
    }
    
    /**
     * Helper methods
     */
    private function get_current_user_data() {
        $current_user = wp_get_current_user();
        
        if ($current_user->ID) {
            return [
                'user_id' => $current_user->ID,
                'user_login' => $current_user->user_login,
                'user_name' => $current_user->display_name ?: $current_user->user_login,
                'user_email' => $current_user->user_email,
                'user_ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ];
        } else {
            return [
                'user_id' => 0,
                'user_login' => '',
                'user_name' => __('Guest', 'psource-chat'),
                'user_email' => '',
                'user_ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ];
        }
    }
    
    private function format_message_text($text) {
        // Convert URLs to links
        $text = make_clickable($text);
        
        // Convert emoji if enabled
        $plugin = Plugin::get_instance();
        if ($plugin->get_option('enable_emoji', true)) {
            $text = $this->convert_emoji($text);
        }
        
        return apply_filters('psource_chat_format_message_text', $text);
    }
    
    private function convert_emoji($text) {
        $emoji_map = [
            ':)' => '😊',
            ':(' => '😞',
            ':D' => '😃',
            ':P' => '😛',
            ';)' => '😉',
            ':o' => '😮',
            ':/' => '😕',
            '<3' => '❤️',
            '</3' => '💔'
        ];
        
        return str_replace(array_keys($emoji_map), array_values($emoji_map), $text);
    }
    
    private function filter_bad_words($text) {
        $bad_words_file = PSOURCE_CHAT_PLUGIN_DIR . 'lib/bad_words_list.php';
        if (file_exists($bad_words_file)) {
            include $bad_words_file;
            if (isset($bad_words) && is_array($bad_words)) {
                foreach ($bad_words as $word) {
                    $text = str_ireplace($word, str_repeat('*', strlen($word)), $text);
                }
            }
        }
        
        return apply_filters('psource_chat_filter_bad_words', $text);
    }
}
