<?php
/**
 * Support Chat Extension
 * 
 * Provides customer support chat functionality with categories and private sessions
 * 
 * @package PSSource\Chat\Extensions
 */

namespace PSSource\Chat\Extensions;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Support Chat Extension Class
 */
class Support_Chat {
    
    /**
     * Extension options
     */
    private $options = [];
    
    /**
     * Database table names
     */
    private $sessions_table;
    private $messages_table;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        
        $this->sessions_table = $wpdb->prefix . 'psource_support_chat_sessions';
        $this->messages_table = $wpdb->prefix . 'psource_support_chat_messages';
        
        $this->load_options();
        $this->init_hooks();
    }
    
    /**
     * Load options
     */
    private function load_options() {
        $extension_options = get_option('psource_chat_extensions', []);
        $this->options = $extension_options['support_chat'] ?? [];
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        if (($this->options['enabled'] ?? 'disabled') === 'enabled') {
            add_action('wp_enqueue_scripts', [$this, 'enqueue_support_scripts']);
            add_action('wp_footer', [$this, 'render_support_button']);
            
            // AJAX handlers for frontend
            add_action('wp_ajax_psource_support_chat_initialize_session', [$this, 'ajax_initialize_session']);
            add_action('wp_ajax_psource_support_chat_send_message', [$this, 'ajax_send_message']);
            add_action('wp_ajax_psource_support_chat_get_new_messages', [$this, 'ajax_get_new_messages']);
            add_action('wp_ajax_psource_support_chat_typing_indicator', [$this, 'ajax_typing_indicator']);
            
            // AJAX handlers for non-logged-in users (if anonymous chat is enabled)
            if (($this->options['allow_anonymous'] ?? 'disabled') === 'enabled') {
                add_action('wp_ajax_nopriv_psource_support_chat_initialize_session', [$this, 'ajax_initialize_session']);
                add_action('wp_ajax_nopriv_psource_support_chat_send_message', [$this, 'ajax_send_message']);
                add_action('wp_ajax_nopriv_psource_support_chat_get_new_messages', [$this, 'ajax_get_new_messages']);
                add_action('wp_ajax_nopriv_psource_support_chat_typing_indicator', [$this, 'ajax_typing_indicator']);
            }
            
            // Admin hooks
            add_action('wp_ajax_psource_support_chat_get_sessions', [$this, 'ajax_get_admin_sessions']);
            add_action('wp_ajax_psource_support_chat_respond_to_session', [$this, 'ajax_admin_respond']);
            add_action('wp_ajax_psource_support_chat_close_session', [$this, 'ajax_close_session']);
            
            // Database tables
            add_action('init', [$this, 'create_database_tables']);
            
            // Admin menu for support agents
            add_action('admin_menu', [$this, 'add_admin_menu']);
        }
    }
    
    /**
     * Enqueue support chat scripts and styles
     */
    public function enqueue_support_scripts() {
        // Only load on frontend for appropriate users
        if (is_admin() || !$this->can_user_access_support()) {
            return;
        }
        
        wp_enqueue_style(
            'psource-support-chat',
            PSOURCE_CHAT_URL . 'assets/css/support-chat.css',
            [],
            PSOURCE_CHAT_VERSION
        );
        
        wp_enqueue_script(
            'psource-support-chat',
            PSOURCE_CHAT_URL . 'assets/js/support-chat.js',
            ['jquery'],
            PSOURCE_CHAT_VERSION,
            true
        );
        
        // Localize script with configuration
        wp_localize_script('psource-support-chat', 'psource_support_chat_config', [
            'enabled' => true,
            'position' => $this->options['button_position'] ?? 'bottom-right',
            'button_text' => $this->options['button_text'] ?? __('Support', 'psource-chat'),
            'widget_title' => $this->options['widget_title'] ?? __('Support Chat', 'psource-chat'),
            'input_placeholder' => __('Nachricht eingeben...', 'psource-chat'),
            'categories' => $this->get_formatted_categories(),
            'ajax_url' => admin_url('admin-ajax.php')
        ]);
        
        wp_localize_script('psource-support-chat', 'psource_support_chat_nonce', wp_create_nonce('psource_support_chat'));
    }
    
    /**
     * Check if user can access support chat
     */
    private function can_user_access_support() {
        // If anonymous is allowed, everyone can access
        if (($this->options['allow_anonymous'] ?? 'disabled') === 'enabled') {
            return true;
        }
        
        // Otherwise only logged-in users
        return is_user_logged_in();
    }
    
    /**
     * Get formatted categories for frontend
     */
    private function get_formatted_categories() {
        $categories = $this->options['categories'] ?? '';
        if (empty($categories)) {
            return [];
        }
        
        $lines = explode("\n", $categories);
        $formatted = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            if (strpos($line, '|') !== false) {
                list($value, $label) = explode('|', $line, 2);
                $formatted[] = [
                    'value' => trim($value),
                    'label' => trim($label)
                ];
            } else {
                $formatted[] = [
                    'value' => $line,
                    'label' => $line
                ];
            }
        }
        
        return $formatted;
    }
    
    /**
     * Render support button
     */
    public function render_support_button() {
        if (is_admin() || !$this->can_user_access_support()) {
            return;
        }
        
        // Trigger event for JavaScript initialization
        echo '<script>document.dispatchEvent(new Event("psource_support_chat_ready"));</script>';
    }
    
    /**
     * Create database tables
     */
    public function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Sessions table
        $sessions_sql = "CREATE TABLE {$this->sessions_table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL,
            user_id int(11) DEFAULT NULL,
            user_email varchar(255) DEFAULT NULL,
            user_name varchar(255) DEFAULT NULL,
            category varchar(255) DEFAULT NULL,
            assigned_agent int(11) DEFAULT NULL,
            status enum('active', 'waiting', 'closed') DEFAULT 'waiting',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY session_id (session_id),
            KEY user_id (user_id),
            KEY assigned_agent (assigned_agent),
            KEY status (status)
        ) {$charset_collate};";
        
        // Messages table
        $messages_sql = "CREATE TABLE {$this->messages_table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL,
            sender_type enum('user', 'support') NOT NULL,
            sender_id int(11) DEFAULT NULL,
            sender_name varchar(255) DEFAULT NULL,
            message text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            is_read tinyint(1) DEFAULT 0,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY sender_type (sender_type),
            KEY sender_id (sender_id),
            KEY created_at (created_at)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sessions_sql);
        dbDelta($messages_sql);
    }
    
    /**
     * AJAX: Initialize chat session
     */
    public function ajax_initialize_session() {
        check_ajax_referer('psource_support_chat', 'nonce');
        
        $category = sanitize_text_field($_POST['category'] ?? '');
        
        // Create session
        $session_id = $this->generate_session_id();
        $user_id = get_current_user_id();
        
        if ($user_id) {
            $user = get_userdata($user_id);
            $user_email = $user->user_email;
            $user_name = $user->display_name;
        } else {
            // Anonymous user
            $user_email = sanitize_email($_POST['email'] ?? '');
            $user_name = sanitize_text_field($_POST['name'] ?? __('Gast', 'psource-chat'));
        }
        
        global $wpdb;
        $result = $wpdb->insert(
            $this->sessions_table,
            [
                'session_id' => $session_id,
                'user_id' => $user_id ?: null,
                'user_email' => $user_email,
                'user_name' => $user_name,
                'category' => $category,
                'status' => 'waiting'
            ],
            ['%s', '%d', '%s', '%s', '%s', '%s']
        );
        
        if ($result) {
            wp_send_json_success([
                'session_id' => $session_id,
                'welcome_message' => $this->get_welcome_message($category)
            ]);
        } else {
            wp_send_json_error(['message' => __('Fehler beim Erstellen der Chat-Session', 'psource-chat')]);
        }
    }
    
    /**
     * AJAX: Send message
     */
    public function ajax_send_message() {
        check_ajax_referer('psource_support_chat', 'nonce');
        
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $category = sanitize_text_field($_POST['category'] ?? '');
        
        if (empty($session_id) || empty($message)) {
            wp_send_json_error(['message' => __('Ungültige Parameter', 'psource-chat')]);
        }
        
        // Verify session belongs to current user
        if (!$this->verify_session_access($session_id)) {
            wp_send_json_error(['message' => __('Zugriff verweigert', 'psource-chat')]);
        }
        
        $user_id = get_current_user_id();
        $user_name = $user_id ? get_userdata($user_id)->display_name : __('Gast', 'psource-chat');
        
        global $wpdb;
        $result = $wpdb->insert(
            $this->messages_table,
            [
                'session_id' => $session_id,
                'sender_type' => 'user',
                'sender_id' => $user_id ?: null,
                'sender_name' => $user_name,
                'message' => $message
            ],
            ['%s', '%s', '%d', '%s', '%s']
        );
        
        if ($result) {
            // Update session timestamp
            $wpdb->update(
                $this->sessions_table,
                ['updated_at' => current_time('mysql')],
                ['session_id' => $session_id],
                ['%s'],
                ['%s']
            );
            
            wp_send_json_success(['message' => __('Nachricht gesendet', 'psource-chat')]);
        } else {
            wp_send_json_error(['message' => __('Fehler beim Senden der Nachricht', 'psource-chat')]);
        }
    }
    
    /**
     * AJAX: Get new messages
     */
    public function ajax_get_new_messages() {
        check_ajax_referer('psource_support_chat', 'nonce');
        
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        $last_message_id = intval($_POST['last_message_id'] ?? 0);
        
        if (empty($session_id)) {
            wp_send_json_error(['message' => __('Session ID fehlt', 'psource-chat')]);
        }
        
        // Verify session access
        if (!$this->verify_session_access($session_id)) {
            wp_send_json_error(['message' => __('Zugriff verweigert', 'psource-chat')]);
        }
        
        global $wpdb;
        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->messages_table} 
             WHERE session_id = %s AND id > %d 
             ORDER BY created_at ASC",
            $session_id,
            $last_message_id
        ));
        
        $formatted_messages = [];
        foreach ($messages as $message) {
            $formatted_messages[] = [
                'id' => $message->id,
                'sender_type' => $message->sender_type,
                'sender_name' => $message->sender_name,
                'message' => $message->message,
                'created_at' => $message->created_at
            ];
        }
        
        wp_send_json_success(['messages' => $formatted_messages]);
    }
    
    /**
     * AJAX: Typing indicator
     */
    public function ajax_typing_indicator() {
        check_ajax_referer('psource_support_chat', 'nonce');
        
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        $typing = filter_var($_POST['typing'] ?? false, FILTER_VALIDATE_BOOLEAN);
        
        // Just acknowledge - could be extended to notify support agents
        wp_send_json_success(['typing' => $typing]);
    }
    
    /**
     * AJAX: Get admin sessions (for support agents)
     */
    public function ajax_get_admin_sessions() {
        if (!current_user_can('manage_options') && !current_user_can('moderate_comments')) {
            wp_send_json_error(['message' => __('Zugriff verweigert', 'psource-chat')]);
        }
        
        check_ajax_referer('psource_support_chat', 'nonce');
        
        global $wpdb;
        $sessions = $wpdb->get_results(
            "SELECT s.*, 
                    (SELECT COUNT(*) FROM {$this->messages_table} m WHERE m.session_id = s.session_id) as message_count,
                    (SELECT COUNT(*) FROM {$this->messages_table} m WHERE m.session_id = s.session_id AND m.sender_type = 'user' AND m.is_read = 0) as unread_count
             FROM {$this->sessions_table} s 
             WHERE s.status IN ('waiting', 'active')
             ORDER BY s.updated_at DESC"
        );
        
        wp_send_json_success(['sessions' => $sessions]);
    }
    
    /**
     * AJAX: Admin respond to session
     */
    public function ajax_admin_respond() {
        if (!current_user_can('manage_options') && !current_user_can('moderate_comments')) {
            wp_send_json_error(['message' => __('Zugriff verweigert', 'psource-chat')]);
        }
        
        check_ajax_referer('psource_support_chat', 'nonce');
        
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        
        if (empty($session_id) || empty($message)) {
            wp_send_json_error(['message' => __('Ungültige Parameter', 'psource-chat')]);
        }
        
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        
        global $wpdb;
        
        // Assign session to agent if not already assigned
        $wpdb->update(
            $this->sessions_table,
            [
                'assigned_agent' => $user_id,
                'status' => 'active'
            ],
            ['session_id' => $session_id],
            ['%d', '%s'],
            ['%s']
        );
        
        // Add support message
        $result = $wpdb->insert(
            $this->messages_table,
            [
                'session_id' => $session_id,
                'sender_type' => 'support',
                'sender_id' => $user_id,
                'sender_name' => $user->display_name,
                'message' => $message
            ],
            ['%s', '%s', '%d', '%s', '%s']
        );
        
        if ($result) {
            wp_send_json_success(['message' => __('Antwort gesendet', 'psource-chat')]);
        } else {
            wp_send_json_error(['message' => __('Fehler beim Senden der Antwort', 'psource-chat')]);
        }
    }
    
    /**
     * AJAX: Close session
     */
    public function ajax_close_session() {
        if (!current_user_can('manage_options') && !current_user_can('moderate_comments')) {
            wp_send_json_error(['message' => __('Zugriff verweigert', 'psource-chat')]);
        }
        
        check_ajax_referer('psource_support_chat', 'nonce');
        
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        
        if (empty($session_id)) {
            wp_send_json_error(['message' => __('Session ID fehlt', 'psource-chat')]);
        }
        
        global $wpdb;
        $result = $wpdb->update(
            $this->sessions_table,
            ['status' => 'closed'],
            ['session_id' => $session_id],
            ['%s'],
            ['%s']
        );
        
        if ($result !== false) {
            wp_send_json_success(['message' => __('Session geschlossen', 'psource-chat')]);
        } else {
            wp_send_json_error(['message' => __('Fehler beim Schließen der Session', 'psource-chat')]);
        }
    }
    
    /**
     * Add admin menu for support agents
     */
    public function add_admin_menu() {
        if (!current_user_can('manage_options') && !current_user_can('moderate_comments')) {
            return;
        }
        
        add_submenu_page(
            'psource-chat',
            __('Support Chat', 'psource-chat'),
            __('Support Chat', 'psource-chat'),
            'moderate_comments',
            'psource-support-chat',
            [$this, 'render_admin_page']
        );
    }
    
    /**
     * Render admin page for support agents
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Support Chat', 'psource-chat'); ?></h1>
            
            <div id="support-chat-admin">
                <div class="support-chat-sessions">
                    <h2><?php _e('Aktive Sessions', 'psource-chat'); ?></h2>
                    <div id="sessions-list">
                        <p><?php _e('Lade Sessions...', 'psource-chat'); ?></p>
                    </div>
                </div>
                
                <div class="support-chat-conversation" style="display: none;">
                    <h2><?php _e('Conversation', 'psource-chat'); ?></h2>
                    <div id="conversation-messages"></div>
                    <div id="conversation-input">
                        <textarea placeholder="<?php _e('Antwort eingeben...', 'psource-chat'); ?>"></textarea>
                        <button type="button" class="button button-primary"><?php _e('Senden', 'psource-chat'); ?></button>
                        <button type="button" class="button close-session"><?php _e('Session schließen', 'psource-chat'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Load sessions
            function loadSessions() {
                $.post(ajaxurl, {
                    action: 'psource_support_chat_get_sessions',
                    nonce: '<?php echo wp_create_nonce('psource_support_chat'); ?>'
                }, function(response) {
                    if (response.success) {
                        renderSessions(response.data.sessions);
                    }
                });
            }
            
            function renderSessions(sessions) {
                var html = '<table class="wp-list-table widefat fixed striped">';
                html += '<thead><tr><th>User</th><th>Category</th><th>Status</th><th>Messages</th><th>Last Update</th><th>Actions</th></tr></thead>';
                html += '<tbody>';
                
                sessions.forEach(function(session) {
                    html += '<tr>';
                    html += '<td>' + session.user_name + '</td>';
                    html += '<td>' + (session.category || '-') + '</td>';
                    html += '<td>' + session.status + '</td>';
                    html += '<td>' + session.message_count + ' (' + session.unread_count + ' unread)</td>';
                    html += '<td>' + session.updated_at + '</td>';
                    html += '<td><button class="button open-session" data-session="' + session.session_id + '">Open</button></td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
                
                if (sessions.length === 0) {
                    html = '<p><?php _e('Keine aktiven Sessions', 'psource-chat'); ?></p>';
                }
                
                $('#sessions-list').html(html);
            }
            
            // Load sessions on page load
            loadSessions();
            
            // Refresh every 10 seconds
            setInterval(loadSessions, 10000);
        });
        </script>
        
        <style>
        #support-chat-admin {
            display: flex;
            gap: 20px;
        }
        
        .support-chat-sessions {
            flex: 1;
        }
        
        .support-chat-conversation {
            flex: 1;
            border: 1px solid #ccd0d4;
            padding: 20px;
        }
        
        #conversation-messages {
            height: 400px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 10px;
        }
        
        #conversation-input textarea {
            width: 100%;
            margin-bottom: 10px;
        }
        </style>
        <?php
    }
    
    /**
     * Generate unique session ID
     */
    private function generate_session_id() {
        return 'support_' . time() . '_' . wp_generate_password(12, false);
    }
    
    /**
     * Verify session access
     */
    private function verify_session_access($session_id) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        
        if ($user_id) {
            // Logged-in user - check if session belongs to them
            $session = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$this->sessions_table} WHERE session_id = %s AND user_id = %d",
                $session_id,
                $user_id
            ));
        } else {
            // Anonymous user - check by session existence (could be enhanced with IP/browser checks)
            $session = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$this->sessions_table} WHERE session_id = %s AND user_id IS NULL",
                $session_id
            ));
        }
        
        return !empty($session);
    }
    
    /**
     * Get welcome message for category
     */
    private function get_welcome_message($category) {
        $messages = [
            'technical' => __('Willkommen beim technischen Support! Wie können wir Ihnen helfen?', 'psource-chat'),
            'billing' => __('Willkommen bei der Rechnungsabteilung! Wie können wir Ihnen bei Ihrer Abrechnung helfen?', 'psource-chat'),
            'general' => __('Willkommen beim allgemeinen Support! Wie können wir Ihnen helfen?', 'psource-chat')
        ];
        
        return $messages[$category] ?? __('Willkommen! Ein Support-Mitarbeiter wird sich bald bei Ihnen melden.', 'psource-chat');
    }
    
    /**
     * Render extension settings tab
     */
    public static function render_extension_tab() {
        $extension_options = get_option('psource_chat_extensions', []);
        $options = $extension_options['support_chat'] ?? [];
        ?>
        <div class="psource-chat-extension-content">
            <h3><?php _e('Support Chat Einstellungen', 'psource-chat'); ?></h3>
            <p><?php _e('Konfigurieren Sie die Support-Chat-Funktionalität für Kundenbetreuung.', 'psource-chat'); ?></p>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Support Chat aktivieren', 'psource-chat'); ?></th>
                    <td>
                        <label>
                            <input type="radio" name="psource_chat_extensions[support_chat][enabled]" value="enabled" 
                                   <?php checked(($options['enabled'] ?? 'disabled'), 'enabled'); ?>>
                            <?php _e('Aktiviert', 'psource-chat'); ?>
                        </label><br>
                        <label>
                            <input type="radio" name="psource_chat_extensions[support_chat][enabled]" value="disabled" 
                                   <?php checked(($options['enabled'] ?? 'disabled'), 'disabled'); ?>>
                            <?php _e('Deaktiviert', 'psource-chat'); ?>
                        </label>
                        <p class="description"><?php _e('Aktiviert oder deaktiviert die Support-Chat-Funktionalität.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Button Position', 'psource-chat'); ?></th>
                    <td>
                        <select name="psource_chat_extensions[support_chat][button_position]">
                            <option value="bottom-right" <?php selected(($options['button_position'] ?? 'bottom-right'), 'bottom-right'); ?>><?php _e('Unten rechts', 'psource-chat'); ?></option>
                            <option value="bottom-left" <?php selected(($options['button_position'] ?? 'bottom-right'), 'bottom-left'); ?>><?php _e('Unten links', 'psource-chat'); ?></option>
                            <option value="top-right" <?php selected(($options['button_position'] ?? 'bottom-right'), 'top-right'); ?>><?php _e('Oben rechts', 'psource-chat'); ?></option>
                            <option value="top-left" <?php selected(($options['button_position'] ?? 'bottom-right'), 'top-left'); ?>><?php _e('Oben links', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Position des Support-Chat-Buttons auf der Website.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Button Text', 'psource-chat'); ?></th>
                    <td>
                        <input type="text" name="psource_chat_extensions[support_chat][button_text]" 
                               value="<?php echo esc_attr($options['button_text'] ?? __('Support', 'psource-chat')); ?>" 
                               class="regular-text">
                        <p class="description"><?php _e('Text, der auf dem Support-Chat-Button angezeigt wird.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Widget Titel', 'psource-chat'); ?></th>
                    <td>
                        <input type="text" name="psource_chat_extensions[support_chat][widget_title]" 
                               value="<?php echo esc_attr($options['widget_title'] ?? __('Support Chat', 'psource-chat')); ?>" 
                               class="regular-text">
                        <p class="description"><?php _e('Titel des Chat-Widgets.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Anonyme Nutzung erlauben', 'psource-chat'); ?></th>
                    <td>
                        <label>
                            <input type="radio" name="psource_chat_extensions[support_chat][allow_anonymous]" value="enabled" 
                                   <?php checked(($options['allow_anonymous'] ?? 'disabled'), 'enabled'); ?>>
                            <?php _e('Aktiviert', 'psource-chat'); ?>
                        </label><br>
                        <label>
                            <input type="radio" name="psource_chat_extensions[support_chat][allow_anonymous]" value="disabled" 
                                   <?php checked(($options['allow_anonymous'] ?? 'disabled'), 'disabled'); ?>>
                            <?php _e('Deaktiviert', 'psource-chat'); ?>
                        </label>
                        <p class="description"><?php _e('Erlaubt nicht-eingeloggten Benutzern die Nutzung des Support-Chats.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Kategorien', 'psource-chat'); ?></th>
                    <td>
                        <textarea name="psource_chat_extensions[support_chat][categories]" rows="6" cols="50" class="large-text"><?php echo esc_textarea($options['categories'] ?? ''); ?></textarea>
                        <p class="description">
                            <?php _e('Eine Kategorie pro Zeile. Format: "wert|Anzeigename" oder nur "Kategoriename".', 'psource-chat'); ?><br>
                            <?php _e('Beispiel:', 'psource-chat'); ?><br>
                            <code>technical|Technischer Support<br>billing|Abrechnung<br>general|Allgemeine Fragen</code>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Support-Agenten', 'psource-chat'); ?></th>
                    <td>
                        <?php
                        $support_agents = $options['support_agents'] ?? [];
                        $users = get_users(['role__in' => ['administrator', 'editor']]);
                        ?>
                        <fieldset>
                            <?php foreach ($users as $user): ?>
                                <label>
                                    <input type="checkbox" 
                                           name="psource_chat_extensions[support_chat][support_agents][]" 
                                           value="<?php echo $user->ID; ?>"
                                           <?php checked(in_array($user->ID, $support_agents)); ?>>
                                    <?php echo esc_html($user->display_name . ' (' . $user->user_email . ')'); ?>
                                </label><br>
                            <?php endforeach; ?>
                        </fieldset>
                        <p class="description"><?php _e('Benutzer, die als Support-Agenten agieren können.', 'psource-chat'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    /**
     * Check activation toggle
     */
    public function is_activation_toggle_enabled() {
        return ($this->options['enabled'] ?? 'disabled') === 'enabled';
    }
    
    /**
     * Get button label configuration
     */
    public function get_button_label() {
        return $this->options['button_label'] ?? __('Support Chat', 'psource-chat');
    }
    
    /**
     * Check if category assignment is configured
     */
    public function has_category_assignment() {
        return !empty($this->options['category_assignment']) && $this->options['category_assignment'] !== 'none';
    }
    
    /**
     * Check if private sessions are enabled
     */
    public function are_private_sessions_enabled() {
        return ($this->options['private_sessions'] ?? true) === true;
    }
}

// Initialize the extension if enabled
if (class_exists('PSSource\Chat\Extensions\Support_Chat')) {
    new Support_Chat();
}
