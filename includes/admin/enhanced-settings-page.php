<?php
/**
 * Enhanced Settings Page with Tabs
 * 
 * @package PSSource\Chat\Admin
 */

namespace PSSource\Chat\Admin;

use PSSource\Chat\Core\Plugin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enhanced Settings Page Class
 */
class Enhanced_Settings_Page {
    
    private $options;
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', [$this, 'admin_init']);
        $this->options = get_option('psource_chat_options', $this->get_default_options());
    }
    
    /**
     * Initialize admin settings
     */
    public function admin_init() {
        register_setting('psource_chat_settings', 'psource_chat_options', [
            'sanitize_callback' => [$this, 'sanitize_options']
        ]);
    }
    
    /**
     * Get default options
     */
    private function get_default_options() {
        return [
            // General Settings
            'enable_sound' => true,
            'enable_emoji' => true,
            'max_message_length' => 500,
            'chat_timeout' => 300,
            'auto_refresh_interval' => 5,
            'show_user_list' => true,
            'allow_guest_chat' => false,
            'moderate_messages' => false,
            'blocked_words_active' => 'disabled',
            'blocked_ip_addresses_active' => 'disabled',
            'session_status_message' => 'Chat ist geschlossen',
            
            // Appearance Settings
            'box_height' => '300px',
            'box_width' => '100%',
            'box_title' => 'Chat',
            'box_border' => '1px solid #ccc',
            'box_border_radius' => '4px',
            'box_background' => '#ffffff',
            'message_color' => '#333333',
            'message_background' => '#f9f9f9',
            'input_background' => '#ffffff',
            'input_border' => '1px solid #ddd',
            'button_background' => '#0073aa',
            'button_color' => '#ffffff',
            
            // Message Settings
            'row_name_avatar' => 'avatar',
            'row_date' => 'disabled',
            'row_date_format' => get_option('date_format'),
            'row_time' => 'enabled',
            'row_time_format' => get_option('time_format'),
            'message_limit' => 100,
            'enable_emoticons' => true,
            'enable_message_sounds' => true,
            'auto_scroll' => true,
            
            // Authentication Settings
            'login_options' => ['administrator', 'editor'],
            'guest_name_required' => true,
            'guest_email_required' => false,
            'auth_method' => 'wordpress',
            'require_registration' => false,
            'min_role_to_chat' => 'subscriber',
            'moderator_roles' => ['administrator'],
            
            // Widget Settings
            'dashboard_widget' => 'enabled',
            'dashboard_widget_title' => 'Chat',
            'dashboard_widget_height' => '300px',
            'dashboard_widget_roles' => ['administrator', 'editor'],
            'dashboard_widget_archive' => false,
            'dashboard_widget_sound' => true,
            'widget_blocked_on_shortcode' => 'disabled',
            'widget_show_on_pages' => true,
            'widget_show_on_posts' => true,
            'widget_show_on_home' => true,
            
            // Shortcode Settings
            'enable_shortcode' => true,
            'shortcode_default_height' => '400px',
            'shortcode_default_width' => '100%',
            'shortcode_allow_guests' => false,
            'shortcode_moderation' => false,
            'shortcode_user_list' => true,
            'shortcode_emoticons' => true,
            'shortcode_sound' => true,
            
            // Advanced Settings
            'log_creation' => 'enabled',
            'log_display' => 'disabled',
            'log_display_label' => 'Chat Archive',
            'log_display_limit' => 10,
            'log_display_hide_session' => 'show',
            'log_display_role_level' => 'level_10',
            'log_limit' => 100,
            'cleanup_interval' => 'weekly',
            'cleanup_older_than' => '30',
            'enable_private_chat' => false,
            'enable_file_uploads' => false,
            'max_file_size' => '2MB',
            'allowed_file_types' => 'jpg,jpeg,png,gif',
            
            // BuddyPress Integration
            'bp_integration' => false,
            'bp_group_chat' => false,
            'bp_group_admin_show_widget' => 'disabled',
            'bp_activity_integration' => false,
            
            // TinyMCE Settings
            'tinymce_button_enabled' => true,
            'tinymce_button_post_types' => ['post', 'page'],
            'tinymce_button_roles' => ['administrator', 'editor'],
            
            // Security Settings
            'enable_flood_protection' => true,
            'flood_protection_timeout' => 10,
            'enable_captcha' => false,
            'blocked_words' => '',
            'blocked_ips' => '',
            'enable_profanity_filter' => false,
            'auto_moderate_links' => true,
            
            // Notification Settings
            'email_notifications' => false,
            'email_notification_roles' => ['administrator'],
            'browser_notifications' => false,
            'desktop_notifications' => false,
            'show_user_count' => true,
            'show_timestamps' => true,
            
            // Messages
            'enable_private_messages' => true,
            'allow_guest_chat' => false,
            'moderate_messages' => false,
            'bad_words_filter' => true,
            'enable_message_history' => true,
            'message_history_limit' => 100,
            
            // Authentication
            'require_login' => true,
            'allowed_user_roles' => ['subscriber', 'contributor', 'author', 'editor', 'administrator'],
            'enable_guest_names' => false,
            
            // Widget & Dashboard
            'enable_dashboard_chat' => false,
            'enable_widget_chat' => true,
            'dashboard_chat_position' => 'bottom-right',
            'widget_default_title' => 'Chat',
            
            // Advanced
            'enable_logs' => true,
            'log_retention_days' => 30,
            'enable_file_uploads' => false,
            'max_file_size' => '2MB',
            'allowed_file_types' => 'jpg,png,gif,pdf',
            'blocked_words' => '',
            'blocked_ips' => ''
        ];
    }
    
    /**
     * Render settings page
     */
    public function render() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'psource-chat'));
        }
        
        // Handle form submission
        if (isset($_POST['submit']) && check_admin_referer('psource_chat_settings')) {
            $this->save_settings();
        }
        
        $current_tab = sanitize_text_field($_GET['tab'] ?? 'general');
        ?>
        <div class="wrap psource-chat-admin">
            <h1><?php _e('Chat Settings', 'psource-chat'); ?></h1>
            
            <nav class="nav-tab-wrapper psource-chat-nav-tabs">
                <a href="?page=psource-chat-settings&tab=general" class="nav-tab <?php echo $current_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('General', 'psource-chat'); ?>
                </a>
                <a href="?page=psource-chat-settings&tab=appearance" class="nav-tab <?php echo $current_tab === 'appearance' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Appearance', 'psource-chat'); ?>
                </a>
                <a href="?page=psource-chat-settings&tab=messages" class="nav-tab <?php echo $current_tab === 'messages' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Messages', 'psource-chat'); ?>
                </a>
                <a href="?page=psource-chat-settings&tab=authentication" class="nav-tab <?php echo $current_tab === 'authentication' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Authentication', 'psource-chat'); ?>
                </a>
                <a href="?page=psource-chat-settings&tab=widget" class="nav-tab <?php echo $current_tab === 'widget' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Widget & Dashboard', 'psource-chat'); ?>
                </a>
                <a href="?page=psource-chat-settings&tab=shortcode" class="nav-tab <?php echo $current_tab === 'shortcode' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Shortcode Builder', 'psource-chat'); ?>
                </a>
                <a href="?page=psource-chat-settings&tab=advanced" class="nav-tab <?php echo $current_tab === 'advanced' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Advanced', 'psource-chat'); ?>
                </a>
            </nav>
            
            <div class="psource-chat-tab-content">
                <form method="post" action="admin.php?page=psource-chat-settings&tab=<?php echo esc_attr($current_tab); ?>">
                    <?php wp_nonce_field('psource_chat_settings'); ?>
                    
                    <?php
                    switch ($current_tab) {
                        case 'general':
                            $this->render_general_tab();
                            break;
                        case 'appearance':
                            $this->render_appearance_tab();
                            break;
                        case 'messages':
                            $this->render_messages_tab();
                            break;
                        case 'authentication':
                            $this->render_authentication_tab();
                            break;
                        case 'widget':
                            $this->render_widget_tab();
                            break;
                        case 'shortcode':
                            $this->render_shortcode_tab();
                            break;
                        case 'advanced':
                            $this->render_advanced_tab();
                            break;
                        default:
                            $this->render_general_tab();
                    }
                    ?>
                    
                    <?php submit_button(); ?>
                </form>
            </div>
        </div>
        <?php
        $this->render_styles();
    }
    
    /**
     * Render General tab
     */
    private function render_general_tab() {
        ?>
        <div class="psource-chat-form-section">
            <h3><?php _e('Basic Settings', 'psource-chat'); ?></h3>
            
            <div class="psource-chat-form-field">
                <label>
                    <input type="checkbox" name="psource_chat_options[enable_sound]" value="1" <?php checked($this->options['enable_sound']); ?>>
                    <?php _e('Enable sound notifications', 'psource-chat'); ?>
                </label>
                <p class="description"><?php _e('Play sound when new messages arrive', 'psource-chat'); ?></p>
            </div>
            
            <div class="psource-chat-form-field">
                <label>
                    <input type="checkbox" name="psource_chat_options[enable_emoji]" value="1" <?php checked($this->options['enable_emoji']); ?>>
                    <?php _e('Enable emoji support', 'psource-chat'); ?>
                </label>
                <p class="description"><?php _e('Allow users to use emojis in messages', 'psource-chat'); ?></p>
            </div>
            
            <div class="psource-chat-form-field">
                <label for="max_message_length"><?php _e('Maximum message length', 'psource-chat'); ?></label>
                <input type="number" id="max_message_length" name="psource_chat_options[max_message_length]" value="<?php echo esc_attr($this->options['max_message_length']); ?>" min="50" max="2000">
                <p class="description"><?php _e('Maximum number of characters per message', 'psource-chat'); ?></p>
            </div>
            
            <div class="psource-chat-form-field">
                <label for="auto_refresh_interval"><?php _e('Auto refresh interval (seconds)', 'psource-chat'); ?></label>
                <input type="number" id="auto_refresh_interval" name="psource_chat_options[auto_refresh_interval]" value="<?php echo esc_attr($this->options['auto_refresh_interval']); ?>" min="1" max="60">
                <p class="description"><?php _e('How often to check for new messages', 'psource-chat'); ?></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render Appearance tab
     */
    private function render_appearance_tab() {
        ?>
        <div class="psource-chat-form-section">
            <h3><?php _e('Chat Box Appearance', 'psource-chat'); ?></h3>
            
            <div class="psource-chat-form-field">
                <label for="chat_theme"><?php _e('Chat Theme', 'psource-chat'); ?></label>
                <select id="chat_theme" name="psource_chat_options[chat_theme]">
                    <option value="default" <?php selected($this->options['chat_theme'], 'default'); ?>><?php _e('Default', 'psource-chat'); ?></option>
                    <option value="dark" <?php selected($this->options['chat_theme'], 'dark'); ?>><?php _e('Dark', 'psource-chat'); ?></option>
                    <option value="light" <?php selected($this->options['chat_theme'], 'light'); ?>><?php _e('Light', 'psource-chat'); ?></option>
                </select>
            </div>
            
            <div class="psource-chat-form-field">
                <label for="box_height"><?php _e('Chat box height', 'psource-chat'); ?></label>
                <input type="text" id="box_height" name="psource_chat_options[box_height]" value="<?php echo esc_attr($this->options['box_height']); ?>">
                <p class="description"><?php _e('Height in pixels (e.g., 300px)', 'psource-chat'); ?></p>
            </div>
            
            <div class="psource-chat-form-field">
                <label>
                    <input type="checkbox" name="psource_chat_options[show_avatars]" value="1" <?php checked($this->options['show_avatars']); ?>>
                    <?php _e('Show user avatars', 'psource-chat'); ?>
                </label>
            </div>
            
            <div class="psource-chat-form-field">
                <label>
                    <input type="checkbox" name="psource_chat_options[show_timestamps]" value="1" <?php checked($this->options['show_timestamps']); ?>>
                    <?php _e('Show message timestamps', 'psource-chat'); ?>
                </label>
            </div>
            
            <div class="psource-chat-form-field">
                <label>
                    <input type="checkbox" name="psource_chat_options[show_user_count]" value="1" <?php checked($this->options['show_user_count']); ?>>
                    <?php _e('Show online user count', 'psource-chat'); ?>
                </label>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render Messages tab
     */
    private function render_messages_tab() {
        ?>
        <div class="psource-chat-form-section">
            <h3><?php _e('Message Settings', 'psource-chat'); ?></h3>
            
            <div class="psource-chat-form-field">
                <label>
                    <input type="checkbox" name="psource_chat_options[enable_private_messages]" value="1" <?php checked($this->options['enable_private_messages']); ?>>
                    <?php _e('Enable private messages', 'psource-chat'); ?>
                </label>
                <p class="description"><?php _e('Allow users to send private messages to each other', 'psource-chat'); ?></p>
            </div>
            
            <div class="psource-chat-form-field">
                <label>
                    <input type="checkbox" name="psource_chat_options[moderate_messages]" value="1" <?php checked($this->options['moderate_messages']); ?>>
                    <?php _e('Moderate messages', 'psource-chat'); ?>
                </label>
                <p class="description"><?php _e('Messages need approval before appearing in chat', 'psource-chat'); ?></p>
            </div>
            
            <div class="psource-chat-form-field">
                <label>
                    <input type="checkbox" name="psource_chat_options[bad_words_filter]" value="1" <?php checked($this->options['bad_words_filter']); ?>>
                    <?php _e('Enable bad words filter', 'psource-chat'); ?>
                </label>
                <p class="description"><?php _e('Filter out inappropriate words from messages', 'psource-chat'); ?></p>
            </div>
            
            <div class="psource-chat-form-field">
                <label for="message_history_limit"><?php _e('Message history limit', 'psource-chat'); ?></label>
                <input type="number" id="message_history_limit" name="psource_chat_options[message_history_limit]" value="<?php echo esc_attr($this->options['message_history_limit']); ?>" min="10" max="1000">
                <p class="description"><?php _e('Number of messages to show in chat history', 'psource-chat'); ?></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render Authentication tab
     */
    private function render_authentication_tab() {
        global $wp_roles;
        ?>
        <div class="psource-chat-form-section">
            <h3><?php _e('User Authentication', 'psource-chat'); ?></h3>
            
            <div class="psource-chat-form-field">
                <label>
                    <input type="checkbox" name="psource_chat_options[require_login]" value="1" <?php checked($this->options['require_login']); ?>>
                    <?php _e('Require user login', 'psource-chat'); ?>
                </label>
                <p class="description"><?php _e('Only logged-in users can participate in chat', 'psource-chat'); ?></p>
            </div>
            
            <div class="psource-chat-form-field">
                <label>
                    <input type="checkbox" name="psource_chat_options[allow_guest_chat]" value="1" <?php checked($this->options['allow_guest_chat']); ?>>
                    <?php _e('Allow guest chat', 'psource-chat'); ?>
                </label>
                <p class="description"><?php _e('Allow non-registered users to participate with a name', 'psource-chat'); ?></p>
            </div>
            
            <div class="psource-chat-form-field">
                <label><?php _e('Allowed user roles', 'psource-chat'); ?></label>
                <?php foreach ($wp_roles->roles as $role_key => $role): ?>
                    <label>
                        <input type="checkbox" name="psource_chat_options[allowed_user_roles][]" value="<?php echo esc_attr($role_key); ?>" 
                               <?php checked(in_array($role_key, $this->options['allowed_user_roles'] ?? [])); ?>>
                        <?php echo esc_html($role['name']); ?>
                    </label><br>
                <?php endforeach; ?>
                <p class="description"><?php _e('Select which user roles can participate in chat', 'psource-chat'); ?></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render Widget tab
     */
    private function render_widget_tab() {
        ?>
        <div class="psource-chat-form-section">
            <h3><?php _e('Dashboard Chat', 'psource-chat'); ?></h3>
            
            <div class="psource-chat-form-field">
                <label>
                    <input type="checkbox" name="psource_chat_options[enable_dashboard_chat]" value="1" <?php checked($this->options['enable_dashboard_chat']); ?>>
                    <?php _e('Enable dashboard chat', 'psource-chat'); ?>
                </label>
                <p class="description"><?php _e('Show a chat widget in WordPress admin dashboard', 'psource-chat'); ?></p>
            </div>
            
            <div class="psource-chat-form-field">
                <label for="dashboard_chat_position"><?php _e('Dashboard chat position', 'psource-chat'); ?></label>
                <select id="dashboard_chat_position" name="psource_chat_options[dashboard_chat_position]">
                    <option value="bottom-right" <?php selected($this->options['dashboard_chat_position'], 'bottom-right'); ?>><?php _e('Bottom Right', 'psource-chat'); ?></option>
                    <option value="bottom-left" <?php selected($this->options['dashboard_chat_position'], 'bottom-left'); ?>><?php _e('Bottom Left', 'psource-chat'); ?></option>
                    <option value="top-right" <?php selected($this->options['dashboard_chat_position'], 'top-right'); ?>><?php _e('Top Right', 'psource-chat'); ?></option>
                    <option value="top-left" <?php selected($this->options['dashboard_chat_position'], 'top-left'); ?>><?php _e('Top Left', 'psource-chat'); ?></option>
                </select>
            </div>
        </div>
        
        <div class="psource-chat-form-section">
            <h3><?php _e('Widget Settings', 'psource-chat'); ?></h3>
            
            <div class="psource-chat-form-field">
                <label>
                    <input type="checkbox" name="psource_chat_options[enable_widget_chat]" value="1" <?php checked($this->options['enable_widget_chat']); ?>>
                    <?php _e('Enable widget chat', 'psource-chat'); ?>
                </label>
                <p class="description"><?php _e('Allow chat widget to be added to sidebars', 'psource-chat'); ?></p>
            </div>
            
            <div class="psource-chat-form-field">
                <label for="widget_default_title"><?php _e('Default widget title', 'psource-chat'); ?></label>
                <input type="text" id="widget_default_title" name="psource_chat_options[widget_default_title]" value="<?php echo esc_attr($this->options['widget_default_title']); ?>">
            </div>
            
            <div class="psource-chat-info-box">
                <h4><?php _e('Widget Usage', 'psource-chat'); ?></h4>
                <p><?php _e('To use the chat widget:', 'psource-chat'); ?></p>
                <ol>
                    <li><?php _e('Go to Appearance → Widgets', 'psource-chat'); ?></li>
                    <li><?php _e('Find "PS Chat Widget" in the available widgets', 'psource-chat'); ?></li>
                    <li><?php _e('Drag it to your desired sidebar', 'psource-chat'); ?></li>
                    <li><?php _e('Configure the widget settings', 'psource-chat'); ?></li>
                </ol>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render Shortcode tab
     */
    private function render_shortcode_tab() {
        ?>
        <div class="psource-chat-form-section">
            <h3><?php _e('Shortcode Builder', 'psource-chat'); ?></h3>
            <p><?php _e('Use this tool to generate customized shortcodes for your posts and pages.', 'psource-chat'); ?></p>
            
            <div class="psource-chat-shortcode-builder">
                <div class="psource-chat-form-field">
                    <label for="shortcode_session"><?php _e('Session/Room ID', 'psource-chat'); ?></label>
                    <input type="text" id="shortcode_session" placeholder="e.g., my-room">
                    <p class="description"><?php _e('Unique identifier for this chat room (optional)', 'psource-chat'); ?></p>
                </div>
                
                <div class="psource-chat-form-field">
                    <label for="shortcode_height"><?php _e('Height', 'psource-chat'); ?></label>
                    <input type="text" id="shortcode_height" placeholder="300px">
                    <p class="description"><?php _e('Chat box height (e.g., 300px, 50vh)', 'psource-chat'); ?></p>
                </div>
                
                <div class="psource-chat-form-field">
                    <label for="shortcode_width"><?php _e('Width', 'psource-chat'); ?></label>
                    <input type="text" id="shortcode_width" placeholder="100%">
                    <p class="description"><?php _e('Chat box width (e.g., 400px, 100%)', 'psource-chat'); ?></p>
                </div>
                
                <div class="psource-chat-form-field">
                    <label for="shortcode_theme"><?php _e('Theme', 'psource-chat'); ?></label>
                    <select id="shortcode_theme">
                        <option value=""><?php _e('Default', 'psource-chat'); ?></option>
                        <option value="dark"><?php _e('Dark', 'psource-chat'); ?></option>
                        <option value="light"><?php _e('Light', 'psource-chat'); ?></option>
                    </select>
                </div>
                
                <div class="psource-chat-form-field">
                    <label>
                        <input type="checkbox" id="shortcode_private">
                        <?php _e('Private chat', 'psource-chat'); ?>
                    </label>
                    <p class="description"><?php _e('Enable private messaging in this chat', 'psource-chat'); ?></p>
                </div>
                
                <div class="psource-chat-form-field">
                    <label for="shortcode_max_users"><?php _e('Maximum users', 'psource-chat'); ?></label>
                    <input type="number" id="shortcode_max_users" placeholder="50" min="1">
                    <p class="description"><?php _e('Maximum number of users allowed (optional)', 'psource-chat'); ?></p>
                </div>
                
                <button type="button" class="button" onclick="generateShortcode()"><?php _e('Generate Shortcode', 'psource-chat'); ?></button>
                
                <div class="psource-chat-shortcode-output" id="shortcode-output">
                    <strong><?php _e('Generated Shortcode:', 'psource-chat'); ?></strong><br>
                    <code id="generated-shortcode">[psource_chat]</code>
                </div>
            </div>
        </div>
        
        <div class="psource-chat-form-section">
            <h3><?php _e('Available Shortcode Parameters', 'psource-chat'); ?></h3>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e('Parameter', 'psource-chat'); ?></th>
                        <th><?php _e('Description', 'psource-chat'); ?></th>
                        <th><?php _e('Example', 'psource-chat'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>session</code></td>
                        <td><?php _e('Unique room identifier', 'psource-chat'); ?></td>
                        <td><code>session="my-room"</code></td>
                    </tr>
                    <tr>
                        <td><code>height</code></td>
                        <td><?php _e('Chat box height', 'psource-chat'); ?></td>
                        <td><code>height="400px"</code></td>
                    </tr>
                    <tr>
                        <td><code>width</code></td>
                        <td><?php _e('Chat box width', 'psource-chat'); ?></td>
                        <td><code>width="100%"</code></td>
                    </tr>
                    <tr>
                        <td><code>theme</code></td>
                        <td><?php _e('Visual theme', 'psource-chat'); ?></td>
                        <td><code>theme="dark"</code></td>
                    </tr>
                    <tr>
                        <td><code>private</code></td>
                        <td><?php _e('Enable private messaging', 'psource-chat'); ?></td>
                        <td><code>private="true"</code></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <script>
        function generateShortcode() {
            let shortcode = '[psource_chat';
            
            const session = document.getElementById('shortcode_session').value.trim();
            if (session) shortcode += ' session="' + session + '"';
            
            const height = document.getElementById('shortcode_height').value.trim();
            if (height) shortcode += ' height="' + height + '"';
            
            const width = document.getElementById('shortcode_width').value.trim();
            if (width) shortcode += ' width="' + width + '"';
            
            const theme = document.getElementById('shortcode_theme').value;
            if (theme) shortcode += ' theme="' + theme + '"';
            
            const private_chat = document.getElementById('shortcode_private').checked;
            if (private_chat) shortcode += ' private="true"';
            
            const max_users = document.getElementById('shortcode_max_users').value.trim();
            if (max_users) shortcode += ' max_users="' + max_users + '"';
            
            shortcode += ']';
            
            document.getElementById('generated-shortcode').textContent = shortcode;
        }
        </script>
        <?php
    }
    
    /**
     * Render Advanced tab
     */
    private function render_advanced_tab() {
        ?>
        <div class="psource-chat-form-section">
            <h3><?php _e('Logging & Storage', 'psource-chat'); ?></h3>
            
            <div class="psource-chat-form-field">
                <label>
                    <input type="checkbox" name="psource_chat_options[enable_logs]" value="1" <?php checked($this->options['enable_logs']); ?>>
                    <?php _e('Enable message logging', 'psource-chat'); ?>
                </label>
                <p class="description"><?php _e('Store chat messages in database for moderation and history', 'psource-chat'); ?></p>
            </div>
            
            <div class="psource-chat-form-field">
                <label for="log_retention_days"><?php _e('Log retention (days)', 'psource-chat'); ?></label>
                <input type="number" id="log_retention_days" name="psource_chat_options[log_retention_days]" value="<?php echo esc_attr($this->options['log_retention_days']); ?>" min="1" max="365">
                <p class="description"><?php _e('How long to keep chat logs before automatic cleanup', 'psource-chat'); ?></p>
            </div>
        </div>
        
        <div class="psource-chat-form-section">
            <h3><?php _e('File Uploads', 'psource-chat'); ?></h3>
            
            <div class="psource-chat-form-field">
                <label>
                    <input type="checkbox" name="psource_chat_options[enable_file_uploads]" value="1" <?php checked($this->options['enable_file_uploads']); ?>>
                    <?php _e('Enable file uploads', 'psource-chat'); ?>
                </label>
                <p class="description"><?php _e('Allow users to share files in chat', 'psource-chat'); ?></p>
            </div>
            
            <div class="psource-chat-form-field">
                <label for="allowed_file_types"><?php _e('Allowed file types', 'psource-chat'); ?></label>
                <input type="text" id="allowed_file_types" name="psource_chat_options[allowed_file_types]" value="<?php echo esc_attr($this->options['allowed_file_types']); ?>">
                <p class="description"><?php _e('Comma-separated list (e.g., jpg,png,gif,pdf)', 'psource-chat'); ?></p>
            </div>
        </div>
        
        <div class="psource-chat-form-section">
            <h3><?php _e('Content Filtering', 'psource-chat'); ?></h3>
            
            <div class="psource-chat-form-field">
                <label for="blocked_words"><?php _e('Blocked words', 'psource-chat'); ?></label>
                <textarea id="blocked_words" name="psource_chat_options[blocked_words]" rows="4"><?php echo esc_textarea($this->options['blocked_words']); ?></textarea>
                <p class="description"><?php _e('One word/phrase per line. These will be filtered from messages.', 'psource-chat'); ?></p>
            </div>
            
            <div class="psource-chat-form-field">
                <label for="blocked_ips"><?php _e('Blocked IP addresses', 'psource-chat'); ?></label>
                <textarea id="blocked_ips" name="psource_chat_options[blocked_ips]" rows="4"><?php echo esc_textarea($this->options['blocked_ips']); ?></textarea>
                <p class="description"><?php _e('One IP address per line. These IPs will be blocked from chat.', 'psource-chat'); ?></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Save settings
     */
    private function save_settings() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $options = $this->sanitize_options($_POST['psource_chat_options'] ?? []);
        update_option('psource_chat_options', $options);
        $this->options = $options;
        
        add_settings_error('psource_chat_messages', 'psource_chat_message', __('Settings saved successfully!', 'psource-chat'), 'updated');
    }
    
    /**
     * Sanitize options
     */
    public function sanitize_options($input) {
        $sanitized = [];
        $defaults = $this->get_default_options();
        
        foreach ($defaults as $key => $default_value) {
            if (isset($input[$key])) {
                switch ($key) {
                    case 'max_message_length':
                    case 'auto_refresh_interval':
                    case 'message_history_limit':
                    case 'log_retention_days':
                        $sanitized[$key] = intval($input[$key]);
                        break;
                    case 'enable_sound':
                    case 'enable_emoji':
                    case 'enable_private_messages':
                    case 'allow_guest_chat':
                    case 'moderate_messages':
                    case 'bad_words_filter':
                    case 'require_login':
                    case 'show_avatars':
                    case 'show_timestamps':
                    case 'show_user_count':
                    case 'enable_dashboard_chat':
                    case 'enable_widget_chat':
                    case 'enable_logs':
                    case 'enable_file_uploads':
                        $sanitized[$key] = !empty($input[$key]);
                        break;
                    case 'allowed_user_roles':
                        $sanitized[$key] = is_array($input[$key]) ? array_map('sanitize_text_field', $input[$key]) : [];
                        break;
                    case 'blocked_words':
                    case 'blocked_ips':
                        $sanitized[$key] = sanitize_textarea_field($input[$key]);
                        break;
                    default:
                        $sanitized[$key] = sanitize_text_field($input[$key]);
                }
            } else {
                $sanitized[$key] = $default_value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Render CSS styles
     */
    private function render_styles() {
        ?>
        <style>
        .psource-chat-nav-tabs {
            margin-bottom: 20px;
        }
        .psource-chat-tab-content {
            background: #fff;
            padding: 20px;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        .psource-chat-form-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e1e1e1;
        }
        .psource-chat-form-section:last-child {
            border-bottom: none;
        }
        .psource-chat-form-section h3 {
            margin-top: 0;
            color: #23282d;
        }
        .psource-chat-form-field {
            margin-bottom: 15px;
        }
        .psource-chat-form-field label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .psource-chat-form-field input[type="text"],
        .psource-chat-form-field input[type="number"],
        .psource-chat-form-field select,
        .psource-chat-form-field textarea {
            width: 100%;
            max-width: 400px;
        }
        .psource-chat-form-field .description {
            font-style: italic;
            color: #666;
            margin-top: 5px;
        }
        .psource-chat-shortcode-builder {
            background: #f7f7f7;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .psource-chat-shortcode-output {
            background: #fff;
            border: 1px solid #ddd;
            padding: 10px;
            font-family: monospace;
            margin-top: 10px;
        }
        .psource-chat-info-box {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            padding: 15px;
            border-radius: 4px;
            margin-top: 15px;
        }
        .psource-chat-info-box h4 {
            margin-top: 0;
            color: #0073aa;
        }
        </style>
        <?php
    }
}
