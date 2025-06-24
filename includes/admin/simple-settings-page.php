<?php
/**
 * Simple Settings Page (Fallback)
 * 
 * @package PSSource\Chat\Admin
 */

namespace PSSource\Chat\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Simple Settings Page Class
 */
class Simple_Settings_Page {
    
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
            'show_timestamps' => true,
            
            // Appearance
            'box_height' => '300px',
            'box_width' => '100%',
            'box_title' => 'Chat',
            'theme' => 'default',
            
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
            'allowed_file_types' => 'jpg,jpeg,png,gif',
            'max_file_size' => 1048576, // 1MB
        ];
    }
    
    /**
     * Render page
     */
    public function render_page() {
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['psource_chat_nonce'], 'psource_chat_settings')) {
            $this->handle_form_submission();
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('PS Chat Einstellungen', 'psource-chat'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('psource_chat_settings', 'psource_chat_nonce'); ?>
                
                <h2 class="nav-tab-wrapper">
                    <a href="#general" class="nav-tab nav-tab-active"><?php _e('Allgemein', 'psource-chat'); ?></a>
                    <a href="#appearance" class="nav-tab"><?php _e('Aussehen', 'psource-chat'); ?></a>
                    <a href="#messages" class="nav-tab"><?php _e('Nachrichten', 'psource-chat'); ?></a>
                    <a href="#users" class="nav-tab"><?php _e('Benutzer', 'psource-chat'); ?></a>
                    <a href="#advanced" class="nav-tab"><?php _e('Erweitert', 'psource-chat'); ?></a>
                </h2>
                
                <div id="general" class="tab-content">
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
                                       min="50" max="2000" class="regular-text" />
                                <p class="description"><?php _e('Maximale Anzahl Zeichen pro Nachricht (50-2000)', 'psource-chat'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Chat-Timeout', 'psource-chat'); ?></th>
                            <td>
                                <input type="number" name="psource_chat_options[chat_timeout]" 
                                       value="<?php echo esc_attr($this->get_option('chat_timeout', 300)); ?>" 
                                       min="60" max="3600" class="regular-text" />
                                <p class="description"><?php _e('Sekunden bis ein Benutzer als offline gilt (60-3600)', 'psource-chat'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Auto-Refresh Intervall', 'psource-chat'); ?></th>
                            <td>
                                <input type="number" name="psource_chat_options[auto_refresh_interval]" 
                                       value="<?php echo esc_attr($this->get_option('auto_refresh_interval', 5)); ?>" 
                                       min="1" max="60" class="regular-text" />
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
                </div>
                
                <div id="appearance" class="tab-content" style="display: none;">
                    <h3><?php _e('Aussehen und Design', 'psource-chat'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Standard Höhe', 'psource-chat'); ?></th>
                            <td>
                                <input type="text" name="psource_chat_options[box_height]" 
                                       value="<?php echo esc_attr($this->get_option('box_height', '300px')); ?>" 
                                       class="regular-text" />
                                <p class="description"><?php _e('Standard-Höhe für Chat-Boxen (z.B. 300px, 20em)', 'psource-chat'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Standard Breite', 'psource-chat'); ?></th>
                            <td>
                                <input type="text" name="psource_chat_options[box_width]" 
                                       value="<?php echo esc_attr($this->get_option('box_width', '100%')); ?>" 
                                       class="regular-text" />
                                <p class="description"><?php _e('Standard-Breite für Chat-Boxen (z.B. 100%, 400px)', 'psource-chat'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Standard Titel', 'psource-chat'); ?></th>
                            <td>
                                <input type="text" name="psource_chat_options[box_title]" 
                                       value="<?php echo esc_attr($this->get_option('box_title', 'Chat')); ?>" 
                                       class="regular-text" />
                                <p class="description"><?php _e('Standard-Titel für Chat-Boxen', 'psource-chat'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div id="messages" class="tab-content" style="display: none;">
                    <h3><?php _e('Nachrichten-Einstellungen', 'psource-chat'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Private Nachrichten', 'psource-chat'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="psource_chat_options[enable_private_messages]" value="1" 
                                           <?php checked($this->get_option('enable_private_messages')); ?> />
                                    <?php _e('Private Nachrichten zwischen Benutzern erlauben', 'psource-chat'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Gast-Chat', 'psource-chat'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="psource_chat_options[allow_guest_chat]" value="1" 
                                           <?php checked($this->get_option('allow_guest_chat')); ?> />
                                    <?php _e('Gästen das Chatten erlauben (ohne Anmeldung)', 'psource-chat'); ?>
                                </label>
                            </td>
                        </tr>
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
                            <th scope="row"><?php _e('Schimpfwort-Filter', 'psource-chat'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="psource_chat_options[bad_words_filter]" value="1" 
                                           <?php checked($this->get_option('bad_words_filter')); ?> />
                                    <?php _e('Automatischen Schimpfwort-Filter aktivieren', 'psource-chat'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div id="users" class="tab-content" style="display: none;">
                    <h3><?php _e('Benutzer-Einstellungen', 'psource-chat'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Login erforderlich', 'psource-chat'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="psource_chat_options[require_login]" value="1" 
                                           <?php checked($this->get_option('require_login')); ?> />
                                    <?php _e('Nur angemeldete Benutzer können chatten', 'psource-chat'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Dashboard-Chat aktivieren', 'psource-chat'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="psource_chat_options[enable_dashboard_chat]" value="1" 
                                           <?php checked($this->get_option('enable_dashboard_chat')); ?> />
                                    <?php _e('Chat-Widget im WordPress-Dashboard anzeigen', 'psource-chat'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Widget-Chat aktivieren', 'psource-chat'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="psource_chat_options[enable_widget_chat]" value="1" 
                                           <?php checked($this->get_option('enable_widget_chat')); ?> />
                                    <?php _e('Chat-Widget für Sidebars verfügbar machen', 'psource-chat'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div id="advanced" class="tab-content" style="display: none;">
                    <h3><?php _e('Erweiterte Einstellungen', 'psource-chat'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Protokollierung aktivieren', 'psource-chat'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="psource_chat_options[enable_logs]" value="1" 
                                           <?php checked($this->get_option('enable_logs')); ?> />
                                    <?php _e('Chat-Aktivitäten protokollieren', 'psource-chat'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Log-Aufbewahrung', 'psource-chat'); ?></th>
                            <td>
                                <input type="number" name="psource_chat_options[log_retention_days]" 
                                       value="<?php echo esc_attr($this->get_option('log_retention_days', 30)); ?>" 
                                       min="1" max="365" class="regular-text" />
                                <p class="description"><?php _e('Tage, für die Logs aufbewahrt werden (1-365)', 'psource-chat'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Datei-Uploads', 'psource-chat'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="psource_chat_options[enable_file_uploads]" value="1" 
                                           <?php checked($this->get_option('enable_file_uploads')); ?> />
                                    <?php _e('Datei-Uploads im Chat erlauben', 'psource-chat'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <?php submit_button(); ?>
            </form>
            
            <div style="margin-top: 30px; padding: 15px; background: #f0f8ff; border: 1px solid #ccc;">
                <h3><?php _e('Shortcode-Generator', 'psource-chat'); ?></h3>
                <p><?php _e('Verwende diesen Shortcode, um einen Chat in Beiträge oder Seiten einzufügen:', 'psource-chat'); ?></p>
                <code>[psource_chat]</code>
                <p><?php _e('Mit Optionen:', 'psource-chat'); ?></p>
                <code>[psource_chat height="400" title="Mein Chat" allow_guests="true"]</code>
            </div>
        </div>
        
        <style>
        .nav-tab-wrapper {
            border-bottom: 1px solid #ccc;
            margin-bottom: 20px;
        }
        .tab-content {
            display: none;
        }
        .tab-content:first-of-type {
            display: block;
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
            $('.nav-tab-wrapper a').on('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all tabs
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                // Hide all tab content
                $('.tab-content').hide();
                
                // Show selected tab content
                var target = $(this).attr('href');
                $(target).show();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Handle form submission
     */
    private function handle_form_submission() {
        $options = $_POST['psource_chat_options'] ?? [];
        $sanitized = $this->sanitize_options($options);
        
        update_option('psource_chat_options', $sanitized);
        
        echo '<div class="notice notice-success is-dismissible"><p>' . 
             __('Einstellungen gespeichert!', 'psource-chat') . '</p></div>';
    }
    
    /**
     * Sanitize options
     */
    public function sanitize_options($options) {
        $sanitized = [];
        $defaults = $this->get_default_options();
        
        foreach ($defaults as $key => $default_value) {
            if (isset($options[$key])) {
                switch ($key) {
                    case 'max_message_length':
                    case 'chat_timeout':
                    case 'auto_refresh_interval':
                    case 'log_retention_days':
                        $sanitized[$key] = absint($options[$key]);
                        break;
                    case 'box_height':
                    case 'box_width':
                        $sanitized[$key] = sanitize_text_field($options[$key]);
                        break;
                    case 'box_title':
                        $sanitized[$key] = sanitize_text_field($options[$key]);
                        break;
                    default:
                        if (is_bool($default_value)) {
                            $sanitized[$key] = !empty($options[$key]);
                        } else {
                            $sanitized[$key] = sanitize_text_field($options[$key]);
                        }
                        break;
                }
            } else {
                $sanitized[$key] = is_bool($default_value) ? false : $default_value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Get option value
     */
    private function get_option($key, $default = false) {
        return isset($this->options[$key]) ? $this->options[$key] : $default;
    }
}
