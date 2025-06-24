<?php
/**
 * Comprehensive Settings Page with Tabs
 * 
 * @package PSSource\Chat\Admin
 */

namespace PSSource\Chat\Admin;

use PSSource\Chat\Core\Plugin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Comprehensive Settings Page Class
 */
class Comprehensive_Settings_Page {
    
    private $options;
    private $tabs;
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', [$this, 'admin_init']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        
        $this->options = get_option('psource_chat_options', $this->get_default_options());
        $this->setup_tabs();
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
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'ps-chat-settings') !== false) {
            wp_enqueue_script('jquery-ui-tabs');
            wp_enqueue_style('jquery-ui', 'https://code.jquery.com/ui/1.12.1/themes/ui-lightness/jquery-ui.css');
            
            $inline_script = "
            jQuery(document).ready(function($) {
                $('#chat-settings-tabs').tabs({
                    activate: function(event, ui) {
                        window.location.hash = ui.newPanel.attr('id');
                    }
                });
                
                // Activate tab from URL hash
                if (window.location.hash) {
                    var activeTab = $('a[href=\"' + window.location.hash + '\"]').parent().index();
                    $('#chat-settings-tabs').tabs('option', 'active', activeTab);
                }
            });
            ";
            wp_add_inline_script('jquery-ui-tabs', $inline_script);
        }
    }
    
    /**
     * Setup tabs
     */
    private function setup_tabs() {
        $this->tabs = [
            'general' => [
                'title' => __('Allgemein', 'psource-chat'),
                'icon' => 'dashicons-admin-generic'
            ],
            'appearance' => [
                'title' => __('Aussehen', 'psource-chat'),
                'icon' => 'dashicons-admin-appearance'
            ],
            'messages' => [
                'title' => __('Nachrichten', 'psource-chat'),
                'icon' => 'dashicons-format-chat'
            ],
            'authentication' => [
                'title' => __('Authentifizierung', 'psource-chat'),
                'icon' => 'dashicons-admin-users'
            ],
            'widget' => [
                'title' => __('Widget', 'psource-chat'),
                'icon' => 'dashicons-admin-post'
            ],
            'shortcode' => [
                'title' => __('Shortcode', 'psource-chat'),
                'icon' => 'dashicons-shortcode'
            ],
            'moderation' => [
                'title' => __('Moderation', 'psource-chat'),
                'icon' => 'dashicons-shield-alt'
            ],
            'advanced' => [
                'title' => __('Erweitert', 'psource-chat'),
                'icon' => 'dashicons-admin-settings'
            ]
        ];
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
        ];
    }
    
    /**
     * Render page
     */
    public function render_page() {
        // Enqueue scripts and styles directly
        wp_enqueue_script('jquery-ui-tabs');
        wp_enqueue_style('jquery-ui-style', 'https://code.jquery.com/ui/1.12.1/themes/ui-lightness/jquery-ui.css');
        
        if (isset($_POST['submit'])) {
            $this->handle_form_submission();
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('PS Chat Einstellungen', 'psource-chat'); ?></h1>
            
            <div id="chat-settings-tabs" class="nav-tab-wrapper">
                <ul>
                    <?php foreach ($this->tabs as $tab_id => $tab_data): ?>
                        <li>
                            <a href="#tab-<?php echo esc_attr($tab_id); ?>">
                                <span class="dashicons <?php echo esc_attr($tab_data['icon']); ?>"></span>
                                <?php echo esc_html($tab_data['title']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <form method="post" action="">
                    <?php wp_nonce_field('psource_chat_settings', 'psource_chat_nonce'); ?>
                    
                    <?php foreach ($this->tabs as $tab_id => $tab_data): ?>
                        <div id="tab-<?php echo esc_attr($tab_id); ?>" class="tab-content">
                            <?php $this->render_tab_content($tab_id); ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php submit_button(); ?>
                </form>
            </div>
        </div>
        
        <style>
        .nav-tab-wrapper ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
        }
        .nav-tab-wrapper li {
            margin-right: 2px;
        }
        .nav-tab-wrapper a {
            display: inline-block;
            padding: 8px 12px;
            text-decoration: none;
            border: 1px solid #ccc;
            border-bottom: none;
            background: #f9f9f9;
            color: #333;
        }
        .nav-tab-wrapper a:hover,
        .nav-tab-wrapper .ui-tabs-active a {
            background: #fff;
            color: #000;
        }
        .tab-content {
            border: 1px solid #ccc;
            padding: 20px;
            background: #fff;
            display: none;
        }
        .tab-content.ui-tabs-panel {
            display: block !important;
        }
        .dashicons {
            margin-right: 5px;
        }
        .form-table th {
            width: 200px;
        }
        .description {
            font-style: italic;
            color: #666;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#chat-settings-tabs').tabs();
        });
        </script>
        <?php
    }
    
    /**
     * Render tab content
     */
    private function render_tab_content($tab_id) {
        switch ($tab_id) {
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
            case 'moderation':
                $this->render_moderation_tab();
                break;
            case 'advanced':
                $this->render_advanced_tab();
                break;
        }
    }
    
    /**
     * Render general tab
     */
    private function render_general_tab() {
        ?>
        <h3><?php _e('Allgemeine Einstellungen', 'psource-chat'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Sound aktivieren', 'psource-chat'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="psource_chat_options[enable_sound]" value="1" 
                               <?php checked($this->get_option('enable_sound')); ?> />
                        <?php _e('Chat-Sounds für neue Nachrichten aktivieren', 'psource-chat'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Emojis aktivieren', 'psource-chat'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="psource_chat_options[enable_emoji]" value="1" 
                               <?php checked($this->get_option('enable_emoji')); ?> />
                        <?php _e('Emoji-Unterstützung in Nachrichten aktivieren', 'psource-chat'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Maximale Nachrichtenlänge', 'psource-chat'); ?></th>
                <td>
                    <input type="number" name="psource_chat_options[max_message_length]" 
                           value="<?php echo esc_attr($this->get_option('max_message_length', 500)); ?>" 
                           min="50" max="2000" />
                    <p class="description"><?php _e('Maximale Anzahl Zeichen pro Nachricht (50-2000)', 'psource-chat'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Chat-Timeout', 'psource-chat'); ?></th>
                <td>
                    <input type="number" name="psource_chat_options[chat_timeout]" 
                           value="<?php echo esc_attr($this->get_option('chat_timeout', 300)); ?>" 
                           min="60" max="3600" />
                    <p class="description"><?php _e('Sekunden bis ein Benutzer als offline gilt (60-3600)', 'psource-chat'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Auto-Refresh Intervall', 'psource-chat'); ?></th>
                <td>
                    <input type="number" name="psource_chat_options[auto_refresh_interval]" 
                           value="<?php echo esc_attr($this->get_option('auto_refresh_interval', 5)); ?>" 
                           min="1" max="60" />
                    <p class="description"><?php _e('Sekunden zwischen automatischen Updates (1-60)', 'psource-chat'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Benutzerliste anzeigen', 'psource-chat'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="psource_chat_options[show_user_list]" value="1" 
                               <?php checked($this->get_option('show_user_list')); ?> />
                        <?php _e('Liste der online Benutzer anzeigen', 'psource-chat'); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render appearance tab
     */
    private function render_appearance_tab() {
        ?>
        <h3><?php _e('Aussehen und Design', 'psource-chat'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Standard Höhe', 'psource-chat'); ?></th>
                <td>
                    <input type="text" name="psource_chat_options[box_height]" 
                           value="<?php echo esc_attr($this->get_option('box_height', '300px')); ?>" />
                    <p class="description"><?php _e('Standard-Höhe für Chat-Boxen (z.B. 300px, 20em)', 'psource-chat'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Standard Breite', 'psource-chat'); ?></th>
                <td>
                    <input type="text" name="psource_chat_options[box_width]" 
                           value="<?php echo esc_attr($this->get_option('box_width', '100%')); ?>" />
                    <p class="description"><?php _e('Standard-Breite für Chat-Boxen (z.B. 100%, 400px)', 'psource-chat'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Standard Titel', 'psource-chat'); ?></th>
                <td>
                    <input type="text" name="psource_chat_options[box_title]" 
                           value="<?php echo esc_attr($this->get_option('box_title', 'Chat')); ?>" />
                    <p class="description"><?php _e('Standard-Titel für Chat-Boxen', 'psource-chat'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Avatar/Name Anzeige', 'psource-chat'); ?></th>
                <td>
                    <select name="psource_chat_options[row_name_avatar]">
                        <option value="avatar" <?php selected($this->get_option('row_name_avatar'), 'avatar'); ?>><?php _e('Nur Avatar', 'psource-chat'); ?></option>
                        <option value="name" <?php selected($this->get_option('row_name_avatar'), 'name'); ?>><?php _e('Nur Name', 'psource-chat'); ?></option>
                        <option value="name-avatar" <?php selected($this->get_option('row_name_avatar'), 'name-avatar'); ?>><?php _e('Avatar und Name', 'psource-chat'); ?></option>
                        <option value="disabled" <?php selected($this->get_option('row_name_avatar'), 'disabled'); ?>><?php _e('Nichts anzeigen', 'psource-chat'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Datum anzeigen', 'psource-chat'); ?></th>
                <td>
                    <select name="psource_chat_options[row_date]">
                        <option value="enabled" <?php selected($this->get_option('row_date'), 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                        <option value="disabled" <?php selected($this->get_option('row_date'), 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                    </select>
                    <input type="text" name="psource_chat_options[row_date_format]" 
                           value="<?php echo esc_attr($this->get_option('row_date_format', get_option('date_format'))); ?>" 
                           placeholder="d.m.Y" style="width: 100px; margin-left: 10px;" />
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Uhrzeit anzeigen', 'psource-chat'); ?></th>
                <td>
                    <select name="psource_chat_options[row_time]">
                        <option value="enabled" <?php selected($this->get_option('row_time'), 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                        <option value="disabled" <?php selected($this->get_option('row_time'), 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                    </select>
                    <input type="text" name="psource_chat_options[row_time_format]" 
                           value="<?php echo esc_attr($this->get_option('row_time_format', get_option('time_format'))); ?>" 
                           placeholder="H:i" style="width: 100px; margin-left: 10px;" />
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render messages tab
     */
    private function render_messages_tab() {
        ?>
        <h3><?php _e('Nachrichten-Einstellungen', 'psource-chat'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Nachrichten-Limit', 'psource-chat'); ?></th>
                <td>
                    <input type="number" name="psource_chat_options[message_limit]" 
                           value="<?php echo esc_attr($this->get_option('message_limit', 100)); ?>" 
                           min="10" max="1000" />
                    <p class="description"><?php _e('Maximale Anzahl angezeigter Nachrichten (10-1000)', 'psource-chat'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Emoticons aktivieren', 'psource-chat'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="psource_chat_options[enable_emoticons]" value="1" 
                               <?php checked($this->get_option('enable_emoticons')); ?> />
                        <?php _e('Emoticon-Unterstützung in Nachrichten', 'psource-chat'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Nachrichten-Sounds', 'psource-chat'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="psource_chat_options[enable_message_sounds]" value="1" 
                               <?php checked($this->get_option('enable_message_sounds')); ?> />
                        <?php _e('Sound bei neuen Nachrichten abspielen', 'psource-chat'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Auto-Scroll', 'psource-chat'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="psource_chat_options[auto_scroll]" value="1" 
                               <?php checked($this->get_option('auto_scroll')); ?> />
                        <?php _e('Automatisch zu neuen Nachrichten scrollen', 'psource-chat'); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render authentication tab
     */
    private function render_authentication_tab() {
        global $wp_roles;
        ?>
        <h3><?php _e('Authentifizierung und Berechtigungen', 'psource-chat'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Gast-Chat erlauben', 'psource-chat'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="psource_chat_options[allow_guest_chat]" value="1" 
                               <?php checked($this->get_option('allow_guest_chat')); ?> />
                        <?php _e('Nicht-angemeldete Benutzer können chatten', 'psource-chat'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Erlaubte Benutzerrollen', 'psource-chat'); ?></th>
                <td>
                    <?php $login_options = $this->get_option('login_options', ['administrator', 'editor']); ?>
                    <?php foreach ($wp_roles->roles as $role_slug => $role): ?>
                        <label style="display: block; margin-bottom: 5px;">
                            <input type="checkbox" name="psource_chat_options[login_options][]" 
                                   value="<?php echo esc_attr($role_slug); ?>" 
                                   <?php checked(in_array($role_slug, $login_options)); ?> />
                            <?php echo esc_html($role['name']); ?>
                        </label>
                    <?php endforeach; ?>
                    <p class="description"><?php _e('Wählen Sie die Benutzerrollen aus, die chatten dürfen', 'psource-chat'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Moderator-Rollen', 'psource-chat'); ?></th>
                <td>
                    <?php $moderator_roles = $this->get_option('moderator_roles', ['administrator']); ?>
                    <?php foreach ($wp_roles->roles as $role_slug => $role): ?>
                        <label style="display: block; margin-bottom: 5px;">
                            <input type="checkbox" name="psource_chat_options[moderator_roles][]" 
                                   value="<?php echo esc_attr($role_slug); ?>" 
                                   <?php checked(in_array($role_slug, $moderator_roles)); ?> />
                            <?php echo esc_html($role['name']); ?>
                        </label>
                    <?php endforeach; ?>
                    <p class="description"><?php _e('Benutzerrollen mit Moderator-Rechten', 'psource-chat'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render widget tab
     */
    private function render_widget_tab() {
        ?>
        <h3><?php _e('Widget-Einstellungen', 'psource-chat'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Dashboard Widget', 'psource-chat'); ?></th>
                <td>
                    <select name="psource_chat_options[dashboard_widget]">
                        <option value="enabled" <?php selected($this->get_option('dashboard_widget'), 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                        <option value="disabled" <?php selected($this->get_option('dashboard_widget'), 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                    </select>
                    <p class="description"><?php _e('Chat-Widget im WordPress Dashboard anzeigen', 'psource-chat'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Dashboard Widget Titel', 'psource-chat'); ?></th>
                <td>
                    <input type="text" name="psource_chat_options[dashboard_widget_title]" 
                           value="<?php echo esc_attr($this->get_option('dashboard_widget_title', 'Chat')); ?>" />
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Dashboard Widget Höhe', 'psource-chat'); ?></th>
                <td>
                    <input type="text" name="psource_chat_options[dashboard_widget_height]" 
                           value="<?php echo esc_attr($this->get_option('dashboard_widget_height', '300px')); ?>" />
                    <p class="description"><?php _e('Höhe des Dashboard-Widgets (z.B. 300px)', 'psource-chat'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Widget bei Shortcode blockieren', 'psource-chat'); ?></th>
                <td>
                    <select name="psource_chat_options[widget_blocked_on_shortcode]">
                        <option value="enabled" <?php selected($this->get_option('widget_blocked_on_shortcode'), 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                        <option value="disabled" <?php selected($this->get_option('widget_blocked_on_shortcode'), 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                    </select>
                    <p class="description"><?php _e('Widget auf Seiten mit Chat-Shortcode ausblenden', 'psource-chat'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render shortcode tab
     */
    private function render_shortcode_tab() {
        ?>
        <h3><?php _e('Shortcode-Einstellungen', 'psource-chat'); ?></h3>
        <p><?php _e('Verwenden Sie <code>[chat]</code> oder <code>[psource-chat]</code> um einen Chat in Seiten oder Beiträge einzufügen.', 'psource-chat'); ?></p>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Shortcode aktivieren', 'psource-chat'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="psource_chat_options[enable_shortcode]" value="1" 
                               <?php checked($this->get_option('enable_shortcode')); ?> />
                        <?php _e('Chat-Shortcodes aktivieren', 'psource-chat'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Standard Höhe', 'psource-chat'); ?></th>
                <td>
                    <input type="text" name="psource_chat_options[shortcode_default_height]" 
                           value="<?php echo esc_attr($this->get_option('shortcode_default_height', '400px')); ?>" />
                    <p class="description"><?php _e('Standard-Höhe für Shortcode-Chats', 'psource-chat'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Standard Breite', 'psource-chat'); ?></th>
                <td>
                    <input type="text" name="psource_chat_options[shortcode_default_width]" 
                           value="<?php echo esc_attr($this->get_option('shortcode_default_width', '100%')); ?>" />
                    <p class="description"><?php _e('Standard-Breite für Shortcode-Chats', 'psource-chat'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Gäste in Shortcodes', 'psource-chat'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="psource_chat_options[shortcode_allow_guests]" value="1" 
                               <?php checked($this->get_option('shortcode_allow_guests')); ?> />
                        <?php _e('Gäste können in Shortcode-Chats teilnehmen', 'psource-chat'); ?>
                    </label>
                </td>
            </tr>
        </table>
        
        <h4><?php _e('Shortcode-Builder', 'psource-chat'); ?></h4>
        <div style="background: #f9f9f9; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">
            <p><?php _e('Erstellen Sie individuelle Chat-Shortcodes:', 'psource-chat'); ?></p>
            
            <table>
                <tr>
                    <td><label><?php _e('Höhe:', 'psource-chat'); ?></label></td>
                    <td><input type="text" id="shortcode-height" value="400px" /></td>
                </tr>
                <tr>
                    <td><label><?php _e('Breite:', 'psource-chat'); ?></label></td>
                    <td><input type="text" id="shortcode-width" value="100%" /></td>
                </tr>
                <tr>
                    <td><label><?php _e('Titel:', 'psource-chat'); ?></label></td>
                    <td><input type="text" id="shortcode-title" value="" placeholder="Chat" /></td>
                </tr>
                <tr>
                    <td><label><?php _e('ID:', 'psource-chat'); ?></label></td>
                    <td><input type="text" id="shortcode-id" value="" placeholder="eindeutige-id" /></td>
                </tr>
            </table>
            
            <p>
                <label>
                    <input type="checkbox" id="shortcode-sound" checked />
                    <?php _e('Sound aktivieren', 'psource-chat'); ?>
                </label>
            </p>
            <p>
                <label>
                    <input type="checkbox" id="shortcode-emoticons" checked />
                    <?php _e('Emoticons aktivieren', 'psource-chat'); ?>
                </label>
            </p>
            <p>
                <label>
                    <input type="checkbox" id="shortcode-userlist" checked />
                    <?php _e('Benutzerliste anzeigen', 'psource-chat'); ?>
                </label>
            </p>
            
            <button type="button" id="generate-shortcode" class="button"><?php _e('Shortcode generieren', 'psource-chat'); ?></button>
            
            <h5><?php _e('Generierter Shortcode:', 'psource-chat'); ?></h5>
            <input type="text" id="generated-shortcode" readonly style="width: 100%; font-family: monospace;" />
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#generate-shortcode').click(function() {
                var shortcode = '[chat';
                
                var height = $('#shortcode-height').val();
                if (height && height !== '400px') {
                    shortcode += ' height="' + height + '"';
                }
                
                var width = $('#shortcode-width').val();
                if (width && width !== '100%') {
                    shortcode += ' width="' + width + '"';
                }
                
                var title = $('#shortcode-title').val();
                if (title) {
                    shortcode += ' title="' + title + '"';
                }
                
                var id = $('#shortcode-id').val();
                if (id) {
                    shortcode += ' id="' + id + '"';
                }
                
                if (!$('#shortcode-sound').is(':checked')) {
                    shortcode += ' sound="false"';
                }
                
                if (!$('#shortcode-emoticons').is(':checked')) {
                    shortcode += ' emoticons="false"';
                }
                
                if (!$('#shortcode-userlist').is(':checked')) {
                    shortcode += ' userlist="false"';
                }
                
                shortcode += ']';
                
                $('#generated-shortcode').val(shortcode);
            });
            
            // Generate initial shortcode
            $('#generate-shortcode').click();
        });
        </script>
        <?php
    }
    
    /**
     * Render moderation tab
     */
    private function render_moderation_tab() {
        ?>
        <h3><?php _e('Moderation und Sicherheit', 'psource-chat'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Nachrichten moderieren', 'psource-chat'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="psource_chat_options[moderate_messages]" value="1" 
                               <?php checked($this->get_option('moderate_messages')); ?> />
                        <?php _e('Alle Nachrichten vor Veröffentlichung prüfen', 'psource-chat'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Badwords-Filter', 'psource-chat'); ?></th>
                <td>
                    <select name="psource_chat_options[blocked_words_active]">
                        <option value="enabled" <?php selected($this->get_option('blocked_words_active'), 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                        <option value="disabled" <?php selected($this->get_option('blocked_words_active'), 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Gesperrte Wörter', 'psource-chat'); ?></th>
                <td>
                    <textarea name="psource_chat_options[blocked_words]" rows="5" cols="50"><?php echo esc_textarea($this->get_option('blocked_words', '')); ?></textarea>
                    <p class="description"><?php _e('Ein Wort pro Zeile. Diese Wörter werden in Nachrichten blockiert.', 'psource-chat'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('IP-Adressen blockieren', 'psource-chat'); ?></th>
                <td>
                    <select name="psource_chat_options[blocked_ip_addresses_active]">
                        <option value="enabled" <?php selected($this->get_option('blocked_ip_addresses_active'), 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                        <option value="disabled" <?php selected($this->get_option('blocked_ip_addresses_active'), 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Gesperrte IP-Adressen', 'psource-chat'); ?></th>
                <td>
                    <textarea name="psource_chat_options[blocked_ips]" rows="5" cols="50"><?php echo esc_textarea($this->get_option('blocked_ips', '')); ?></textarea>
                    <p class="description"><?php _e('Eine IP-Adresse pro Zeile. Diese IPs können nicht chatten.', 'psource-chat'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Flood-Schutz', 'psource-chat'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="psource_chat_options[enable_flood_protection]" value="1" 
                               <?php checked($this->get_option('enable_flood_protection')); ?> />
                        <?php _e('Schutz vor zu vielen Nachrichten aktivieren', 'psource-chat'); ?>
                    </label>
                    <br />
                    <input type="number" name="psource_chat_options[flood_protection_timeout]" 
                           value="<?php echo esc_attr($this->get_option('flood_protection_timeout', 10)); ?>" 
                           min="1" max="300" style="width: 80px;" />
                    <span><?php _e('Sekunden Wartezeit zwischen Nachrichten', 'psource-chat'); ?></span>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render advanced tab
     */
    private function render_advanced_tab() {
        ?>
        <h3><?php _e('Erweiterte Einstellungen', 'psource-chat'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Chat-Protokolle', 'psource-chat'); ?></th>
                <td>
                    <select name="psource_chat_options[log_creation]">
                        <option value="enabled" <?php selected($this->get_option('log_creation'), 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                        <option value="disabled" <?php selected($this->get_option('log_creation'), 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                    </select>
                    <p class="description"><?php _e('Chat-Sitzungen für spätere Anzeige speichern', 'psource-chat'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Protokoll-Anzeige', 'psource-chat'); ?></th>
                <td>
                    <select name="psource_chat_options[log_display]">
                        <option value="disabled" <?php selected($this->get_option('log_display'), 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                        <option value="enabled-list-above" <?php selected($this->get_option('log_display'), 'enabled-list-above'); ?>><?php _e('Aktiviert - Liste über dem Chat', 'psource-chat'); ?></option>
                        <option value="enabled-list-below" <?php selected($this->get_option('log_display'), 'enabled-list-below'); ?>><?php _e('Aktiviert - Liste unter dem Chat', 'psource-chat'); ?></option>
                        <option value="enabled-link-above" <?php selected($this->get_option('log_display'), 'enabled-link-above'); ?>><?php _e('Aktiviert - Link über dem Chat', 'psource-chat'); ?></option>
                        <option value="enabled-link-below" <?php selected($this->get_option('log_display'), 'enabled-link-below'); ?>><?php _e('Aktiviert - Link unter dem Chat', 'psource-chat'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Protokoll-Limit', 'psource-chat'); ?></th>
                <td>
                    <input type="number" name="psource_chat_options[log_limit]" 
                           value="<?php echo esc_attr($this->get_option('log_limit', 100)); ?>" 
                           min="10" max="1000" />
                    <p class="description"><?php _e('Maximale Anzahl angezeigter Chat-Nachrichten', 'psource-chat'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Session-Nachricht', 'psource-chat'); ?></th>
                <td>
                    <input type="text" name="psource_chat_options[session_status_message]" 
                           value="<?php echo esc_attr($this->get_option('session_status_message', 'Chat ist geschlossen')); ?>" 
                           style="width: 100%;" />
                    <p class="description"><?php _e('Nachricht wenn Chat geschlossen ist', 'psource-chat'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('TinyMCE Button', 'psource-chat'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="psource_chat_options[tinymce_button_enabled]" value="1" 
                               <?php checked($this->get_option('tinymce_button_enabled')); ?> />
                        <?php _e('Chat-Button im Editor anzeigen', 'psource-chat'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Automatische Bereinigung', 'psource-chat'); ?></th>
                <td>
                    <select name="psource_chat_options[cleanup_interval]">
                        <option value="never" <?php selected($this->get_option('cleanup_interval'), 'never'); ?>><?php _e('Niemals', 'psource-chat'); ?></option>
                        <option value="daily" <?php selected($this->get_option('cleanup_interval'), 'daily'); ?>><?php _e('Täglich', 'psource-chat'); ?></option>
                        <option value="weekly" <?php selected($this->get_option('cleanup_interval'), 'weekly'); ?>><?php _e('Wöchentlich', 'psource-chat'); ?></option>
                        <option value="monthly" <?php selected($this->get_option('cleanup_interval'), 'monthly'); ?>><?php _e('Monatlich', 'psource-chat'); ?></option>
                    </select>
                    <br />
                    <input type="number" name="psource_chat_options[cleanup_older_than]" 
                           value="<?php echo esc_attr($this->get_option('cleanup_older_than', 30)); ?>" 
                           min="1" max="365" style="width: 80px;" />
                    <span><?php _e('Tage - Ältere Daten löschen', 'psource-chat'); ?></span>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Handle form submission
     */
    private function handle_form_submission() {
        if (!isset($_POST['psource_chat_nonce']) || !wp_verify_nonce($_POST['psource_chat_nonce'], 'psource_chat_settings')) {
            wp_die(__('Sicherheitsüberprüfung fehlgeschlagen', 'psource-chat'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Keine Berechtigung', 'psource-chat'));
        }
        
        $new_options = $this->sanitize_options($_POST['psource_chat_options'] ?? []);
        update_option('psource_chat_options', $new_options);
        
        add_settings_error('psource_chat_settings', 'settings_updated', __('Einstellungen gespeichert.', 'psource-chat'), 'updated');
    }
    
    /**
     * Sanitize options
     */
    public function sanitize_options($input) {
        $sanitized = [];
        
        // Sanitize all input fields
        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = array_map('sanitize_text_field', $value);
            } else {
                switch ($key) {
                    case 'blocked_words':
                    case 'blocked_ips':
                        $sanitized[$key] = sanitize_textarea_field($value);
                        break;
                    case 'session_status_message':
                    case 'box_title':
                    case 'dashboard_widget_title':
                        $sanitized[$key] = sanitize_text_field($value);
                        break;
                    case 'max_message_length':
                    case 'chat_timeout':
                    case 'auto_refresh_interval':
                    case 'message_limit':
                    case 'log_limit':
                    case 'flood_protection_timeout':
                    case 'cleanup_older_than':
                        $sanitized[$key] = intval($value);
                        break;
                    default:
                        $sanitized[$key] = sanitize_text_field($value);
                }
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Get option with fallback
     */
    private function get_option($key, $default = false) {
        return isset($this->options[$key]) ? $this->options[$key] : $default;
    }
}

// Don't auto-initialize - let admin menu handle it
