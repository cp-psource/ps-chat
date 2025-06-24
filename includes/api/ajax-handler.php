<?php
/**
 * AJAX Handler
 * 
 * @package PSSource\Chat\API
 */

namespace PSSource\Chat\API;

use PSSource\Chat\Core\Database;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles all AJAX requests for chat functionality
 */
class Ajax_Handler {
    
    /**
     * Handle AJAX request
     */
    public function handle_request($action) {
        $method = 'handle_' . $action;
        
        if (method_exists($this, $method)) {
            $this->$method();
        } else {
            wp_send_json_error(['message' => __('Invalid action', 'psource-chat')]);
        }
    }
    
    /**
     * Join chat session
     */
    private function handle_join_session() {
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        $session_type = sanitize_text_field($_POST['session_type'] ?? 'site');
        
        if (empty($session_id)) {
            // Create new session
            $session_id = Database::create_session($session_type);
            if (!$session_id) {
                wp_send_json_error(['message' => __('Could not create session', 'psource-chat')]);
            }
        }
        
        // Get current user data
        $user_data = $this->get_user_data();
        
        // Add user to session
        Database::add_user_to_session($session_id, $user_data);
        
        // Get recent messages
        $messages = Database::get_messages($session_id, 20);
        $active_users = Database::get_active_users($session_id);
        
        wp_send_json_success([
            'session_id' => $session_id,
            'messages' => $this->format_messages($messages),
            'active_users' => $this->format_users($active_users),
            'user_data' => $user_data
        ]);
    }
    
    /**
     * Send message
     */
    private function handle_send_message() {
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        $message_text = sanitize_textarea_field($_POST['message'] ?? '');
        $is_private = (bool) ($_POST['is_private'] ?? false);
        $recipient_id = (int) ($_POST['recipient_id'] ?? 0);
        
        if (empty($session_id) || empty($message_text)) {
            wp_send_json_error(['message' => __('Missing required data', 'psource-chat')]);
        }
        
        // Check message length
        $max_length = Plugin::get_instance()->get_option('max_message_length', 500);
        if (strlen($message_text) > $max_length) {
            wp_send_json_error(['message' => sprintf(__('Message too long. Maximum %d characters allowed.', 'psource-chat'), $max_length)]);
        }
        
        // Filter bad words if enabled
        if (Plugin::get_instance()->get_option('bad_words_filter', true)) {
            $message_text = $this->filter_bad_words($message_text);
        }
        
        // Get user data
        $user_data = $this->get_user_data();
        
        // Prepare message data
        $message_data = [
            'message_text' => $message_text,
            'user_id' => $user_data['user_id'],
            'user_login' => $user_data['user_login'],
            'user_name' => $user_data['user_name'],
            'user_email' => $user_data['user_email'],
            'user_ip' => $user_data['user_ip'],
            'is_private' => $is_private ? 1 : 0,
            'is_moderated' => Plugin::get_instance()->get_option('moderate_messages', false) ? 1 : 0
        ];
        
        // Add message to database
        $message_id = Database::add_message($session_id, $message_data);
        
        if (!$message_id) {
            wp_send_json_error(['message' => __('Could not save message', 'psource-chat')]);
        }
        
        // Update user activity
        Database::update_user_activity($session_id, $user_data['user_id']);
        
        // Send notification if private message
        if ($is_private && $recipient_id) {
            $this->send_private_message_notification($recipient_id, $user_data, $message_text);
        }
        
        wp_send_json_success([
            'message_id' => $message_id,
            'message' => $this->format_message($message_data + ['id' => $message_id, 'message_time' => current_time('mysql')])
        ]);
    }
    
    /**
     * Get messages update
     */
    private function handle_get_messages() {
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        $since = sanitize_text_field($_POST['since'] ?? '');
        
        if (empty($session_id)) {
            wp_send_json_error(['message' => __('Missing session ID', 'psource-chat')]);
        }
        
        // Update user activity
        $user_data = $this->get_user_data();
        Database::update_user_activity($session_id, $user_data['user_id']);
        
        // Get new messages
        $messages = Database::get_messages($session_id, 50, 0, $since);
        $active_users = Database::get_active_users($session_id);
        
        wp_send_json_success([
            'messages' => $this->format_messages($messages),
            'active_users' => $this->format_users($active_users),
            'timestamp' => current_time('mysql')
        ]);
    }
    
    /**
     * Leave session
     */
    private function handle_leave_session() {
        global $wpdb;
        
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        $user_data = $this->get_user_data();
        
        if (empty($session_id)) {
            wp_send_json_error(['message' => __('Missing session ID', 'psource-chat')]);
        }
        
        // Update user status to inactive
        $table = Database::get_table_name('user_sessions');
        $wpdb->update(
            $table,
            ['session_status' => 'inactive'],
            [
                'session_id' => $session_id,
                'user_id' => $user_data['user_id']
            ]
        );
        
        wp_send_json_success(['message' => __('Left session successfully', 'psource-chat')]);
    }
    
    /**
     * Get user typing status
     */
    private function handle_user_typing() {
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        $is_typing = (bool) ($_POST['is_typing'] ?? false);
        
        if (empty($session_id)) {
            wp_send_json_error(['message' => __('Missing session ID', 'psource-chat')]);
        }
        
        // Store typing status in transient
        $user_data = $this->get_user_data();
        $transient_key = "chat_typing_{$session_id}_{$user_data['user_id']}";
        
        if ($is_typing) {
            set_transient($transient_key, $user_data['user_name'], 10); // 10 seconds
        } else {
            delete_transient($transient_key);
        }
        
        wp_send_json_success(['status' => $is_typing ? 'typing' : 'stopped']);
    }
    
    /**
     * Get typing users
     */
    private function handle_get_typing_users() {
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        
        if (empty($session_id)) {
            wp_send_json_error(['message' => __('Missing session ID', 'psource-chat')]);
        }
        
        $typing_users = [];
        $active_users = Database::get_active_users($session_id);
        
        foreach ($active_users as $user) {
            $transient_key = "chat_typing_{$session_id}_{$user->user_id}";
            if (get_transient($transient_key)) {
                $typing_users[] = $user->user_name;
            }
        }
        
        wp_send_json_success(['typing_users' => $typing_users]);
    }
    
    /**
     * Get current user data
     */
    private function get_user_data() {
        $current_user = wp_get_current_user();
        
        if ($current_user->ID) {
            return [
                'user_id' => $current_user->ID,
                'user_login' => $current_user->user_login,
                'user_name' => $current_user->display_name,
                'user_email' => $current_user->user_email,
                'user_ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ];
        } else {
            // Guest user
            $guest_name = sanitize_text_field($_POST['guest_name'] ?? __('Guest', 'psource-chat'));
            $guest_email = sanitize_email($_POST['guest_email'] ?? '');
            
            return [
                'user_id' => 0,
                'user_login' => '',
                'user_name' => $guest_name,
                'user_email' => $guest_email,
                'user_ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ];
        }
    }
    
    /**
     * Format messages for output
     */
    private function format_messages($messages) {
        $formatted = [];
        foreach ($messages as $message) {
            $formatted[] = $this->format_message($message);
        }
        return array_reverse($formatted); // Show newest first
    }
    
    /**
     * Format single message
     */
    private function format_message($message) {
        return [
            'id' => $message->id ?? $message['id'],
            'user_id' => $message->user_id ?? $message['user_id'],
            'user_name' => $message->user_name ?? $message['user_name'],
            'message_text' => $this->format_message_text($message->message_text ?? $message['message_text']),
            'message_time' => $message->message_time ?? $message['message_time'],
            'is_private' => (bool) ($message->is_private ?? $message['is_private']),
            'is_moderated' => (bool) ($message->is_moderated ?? $message['is_moderated']),
            'time_ago' => human_time_diff(strtotime($message->message_time ?? $message['message_time'])),
            'avatar' => get_avatar_url($message->user_id ?? $message['user_id'], ['size' => 32])
        ];
    }
    
    /**
     * Format users for output
     */
    private function format_users($users) {
        $formatted = [];
        foreach ($users as $user) {
            $formatted[] = [
                'user_id' => $user->user_id,
                'user_name' => $user->user_name,
                'last_seen' => $user->last_seen,
                'time_ago' => human_time_diff(strtotime($user->last_seen)),
                'avatar' => get_avatar_url($user->user_id, ['size' => 24])
            ];
        }
        return $formatted;
    }
    
    /**
     * Format message text (apply filters, convert links, etc.)
     */
    private function format_message_text($text) {
        // Convert URLs to links
        $text = make_clickable($text);
        
        // Convert emoji if enabled
        if (Plugin::get_instance()->get_option('enable_emoji', true)) {
            $text = $this->convert_emoji($text);
        }
        
        // Apply custom filters
        return apply_filters('psource_chat_format_message_text', $text);
    }
    
    /**
     * Convert emoji codes to actual emoji
     */
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
    
    /**
     * Filter bad words
     */
    private function filter_bad_words($text) {
        // Load bad words list
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
    
    /**
     * Send private message notification
     */
    private function send_private_message_notification($recipient_id, $sender_data, $message_text) {
        $recipient = get_user_by('ID', $recipient_id);
        if (!$recipient) {
            return;
        }
        
        // Check if notifications are enabled
        if (!Plugin::get_instance()->get_option('enable_push_notifications', false)) {
            return;
        }
        
        // Send email notification if enabled
        if (Plugin::get_instance()->get_option('admin_email_notifications', false)) {
            $subject = sprintf(__('New private message from %s', 'psource-chat'), $sender_data['user_name']);
            $message = sprintf(
                __("You have received a new private message:\n\nFrom: %s\nMessage: %s\n\nLogin to view: %s", 'psource-chat'),
                $sender_data['user_name'],
                wp_strip_all_tags($message_text),
                admin_url()
            );
            
            wp_mail($recipient->user_email, $subject, $message);
        }
    }
}
