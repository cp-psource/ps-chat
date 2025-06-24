<?php
/**
 * Legacy-Compatible Comprehensive Settings Page
 * 
 * Vollständige Implementierung aller Original-Einstellungen
 * 
 * @package PSSource\Chat\Admin
 */

namespace PSSource\Chat\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Legacy Compatible Settings Page Class
 */
class Legacy_Compatible_Settings_Page {
    
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
     * Setup tabs structure (matching original)
     */
    private function setup_tabs() {
        $this->tabs = [
            'box_appearance' => [
                'title' => __('Box Aussehen', 'psource-chat'),
                'icon' => 'dashicons-admin-appearance'
            ],
            'messages_appearance' => [
                'title' => __('Nachrichten Darstellung', 'psource-chat'),
                'icon' => 'dashicons-format-chat'
            ],
            'messages_input' => [
                'title' => __('Eingabebox', 'psource-chat'),
                'icon' => 'dashicons-edit'
            ],
            'users_list' => [
                'title' => __('Benutzerliste', 'psource-chat'),
                'icon' => 'dashicons-groups'
            ],
            'authentication' => [
                'title' => __('Authentifizierung', 'psource-chat'),
                'icon' => 'dashicons-lock'
            ],
            'tinymce_button' => [
                'title' => __('WYSIWYG Button', 'psource-chat'),
                'icon' => 'dashicons-editor-code'
            ],
            'advanced' => [
                'title' => __('Erweitert', 'psource-chat'),
                'icon' => 'dashicons-admin-settings'
            ]
        ];
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
            wp_enqueue_script('jquery');
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
            // Don't rely on jQuery-UI, use custom implementation
        }
    }
    
    /**
     * Get default options (from original plugin)
     */
    private function get_default_options() {
        return [
            // Box Appearance
            'box_width' => '500px',
            'box_width_mobile_adjust' => '',
            'box_height' => '300px',
            'box_height_mobile_adjust' => '',
            'box_font_family' => '',
            'box_font_size' => '12px',
            'box_text_color' => '#000000',
            'box_background_color' => '#FFFFFF',
            'box_border_color' => '#CCCCCC',
            'box_border_width' => '1px',
            'box_title' => 'Chat',
            'box_title_color' => '#000000',
            'box_title_background_color' => '#F0F0F0',
            
            // Messages Appearance
            'messages_wrapper_background_color' => '#FFFFFF',
            'messages_wrapper_border_color' => '#CCCCCC',
            'messages_wrapper_border_width' => '1px',
            'messages_wrapper_height' => '200px',
            'messages_order' => 'desc',
            'messages_show_date' => 'enabled',
            'messages_show_time' => 'enabled',
            'messages_show_avatar' => 'enabled',
            'messages_avatar_size' => '32',
            'messages_text_color_logged_in' => '#000000',
            'messages_text_color_guest' => '#666666',
            'messages_background_color_logged_in' => '#F9F9F9',
            'messages_background_color_guest' => '#FFFFFF',
            
            // Messages Input
            'box_input_position' => 'bottom',
            'row_message_input_height' => '60px',
            'row_message_input_length' => '500',
            'row_message_input_font_family' => '',
            'row_message_input_font_size' => '12px',
            'row_message_input_text_color' => '#000000',
            'row_message_input_background_color' => '#FFFFFF',
            'box_emoticons' => 'enabled',
            'box_send_button_enable' => 'enabled',
            'box_send_button_position' => 'right',
            'box_send_button_label' => 'Senden',
            
            // Users List
            'users_list_show' => 'enabled',
            'users_list_position' => 'right',
            'users_list_width' => '150px',
            'users_list_height' => '100px',
            'users_list_text_color' => '#000000',
            'users_list_background_color' => '#F9F9F9',
            'users_list_border_color' => '#CCCCCC',
            'users_list_border_width' => '1px',
            'users_list_show_avatars' => 'enabled',
            'users_list_avatar_size' => '16',
            'user_enter_message' => '{user} ist dem Chat beigetreten',
            'user_exit_message' => '{user} hat den Chat verlassen',
            
            // Authentication
            'login_options' => ['subscriber', 'contributor', 'author', 'editor', 'administrator'],
            'login_view_options' => 'all',
            'moderator_roles' => ['administrator', 'editor'],
            'blocked_user_capability' => 'read',
            
            // TinyMCE Button
            'tinymce_button_enabled' => 'enabled',
            'tinymce_button_post_types' => ['post', 'page'],
            'tinymce_button_roles' => ['administrator', 'editor', 'author'],
            
            // Advanced
            'log_creation' => 'enabled',
            'log_display' => 'disabled',
            'log_display_label' => 'Chat Archive',
            'log_display_limit' => '10',
            'log_display_hide_session' => 'show',
            'log_display_role_level' => 'level_10',
            'log_limit' => '100',
            'session_status_message' => 'Diese Chat-Session ist beendet.',
            'blocked_words' => '',
            'blocked_ip_addresses' => '',
            'blocked_ip_addresses_active' => 'disabled',
            'enable_sound' => 'enabled',
            'sound_file' => 'chime',
            'enable_emoticons' => 'enabled',
            'chat_session_timeout' => '300'
        ];
    }
    
    /**
     * Render page
     */
    public function render_page() {
        // Handle form submission
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['psource_chat_nonce'], 'psource_chat_settings')) {
            $this->handle_form_submission();
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('PS Chat Einstellungen - Seiten', 'psource-chat'); ?></h1>
            
            <p><?php _e('Die folgenden Einstellungen werden verwendet, um die Inline-Chat-Shortcodes zu steuern, die auf Posts, Seiten usw. angewendet werden. Hier kannst Du Standardoptionen einrichten. Überschreibe diese Standardoptionen nicht nur mit Shortcode-Parametern für den jeweiligen Beitrag, die Seite usw.', 'psource-chat'); ?></p>
            
            <div id="chat_tab_pane" class="chat_tab_pane">
                <ul class="tab-nav">
                    <?php $first_tab = true; foreach ($this->tabs as $tab_id => $tab_data): ?>
                        <li class="tab-item">
                            <a href="#" data-tab="<?php echo esc_attr($tab_id); ?>_panel" 
                               class="tab-link<?php echo $first_tab ? ' active' : ''; ?>">
                                <span class="dashicons <?php echo esc_attr($tab_data['icon']); ?>"></span>
                                <span><?php echo esc_html($tab_data['title']); ?></span>
                            </a>
                        </li>
                    <?php $first_tab = false; endforeach; ?>
                </ul>
                
                <form method="post" action="">
                    <?php wp_nonce_field('psource_chat_settings', 'psource_chat_nonce'); ?>
                    
                    <?php $first_panel = true; foreach ($this->tabs as $tab_id => $tab_data): ?>
                        <div id="<?php echo esc_attr($tab_id); ?>_panel" class="tab-panel<?php echo $first_panel ? ' active' : ''; ?>">
                            <?php $this->render_tab_content($tab_id); ?>
                        </div>
                    <?php $first_panel = false; endforeach; ?>
                    
                    <?php submit_button(__('Einstellungen speichern', 'psource-chat')); ?>
                </form>
            </div>
        </div>
        
        <style>
        .chat_tab_pane {
            border: 1px solid #ccd0d4;
            background: #fff;
            margin-top: 20px;
        }
        
        .tab-nav {
            list-style: none;
            margin: 0;
            padding: 0;
            background: #f1f1f1;
            border-bottom: 1px solid #ccd0d4;
            display: flex;
            flex-wrap: wrap;
        }
        
        .tab-item {
            margin: 0;
        }
        
        .tab-link {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            text-decoration: none;
            border-right: 1px solid #ccd0d4;
            background: #f1f1f1;
            color: #32373c;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .tab-link:hover {
            background: #fff;
            color: #0073aa;
        }
        
        .tab-link.active {
            background: #fff;
            color: #0073aa;
            font-weight: 600;
        }
        
        .tab-link .dashicons {
            margin-right: 8px;
            font-size: 16px;
            width: 16px;
            height: 16px;
        }
        
        .tab-panel {
            padding: 20px;
            display: none;
        }
        
        .tab-panel.active {
            display: block;
        }
        
        .chat-label-column {
            width: 200px;
            vertical-align: top;
            font-weight: bold;
        }
        
        .chat-label-column-wide {
            width: 100%;
            vertical-align: top;
        }
        
        .chat-value-column {
            width: 300px;
            vertical-align: top;
        }
        
        .chat-help-column {
            width: 50px;
            vertical-align: top;
        }
        
        .psource-chat-input-with-select {
            margin-right: 5px;
        }
        
        .pickcolor_input {
            width: 100px;
        }
        
        fieldset {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            background: #f9f9f9;
        }
        
        legend {
            font-weight: bold;
            padding: 0 10px;
            background: #fff;
        }
        
        .psource-chat-user-roles-list {
            list-style: none;
            margin: 10px 0;
            padding: 0;
        }
        
        .psource-chat-user-roles-list li {
            margin: 5px 0;
        }
        
        .info {
            font-style: italic;
            color: #666;
            margin: 10px 0;
        }
        
        .description {
            font-style: italic;
            color: #666;
            margin: 5px 0;
            font-size: 13px;
        }
        
        @media (max-width: 782px) {
            .tab-nav {
                flex-direction: column;
            }
            
            .tab-link {
                border-right: none;
                border-bottom: 1px solid #ccd0d4;
            }
        }
        </style>
        
        <script>
        (function() {
            // Vanilla JavaScript Tab Implementation (robust, no jQuery dependencies)
            function initTabs() {
                var tabLinks = document.querySelectorAll('.tab-link');
                var tabPanels = document.querySelectorAll('.tab-panel');
                
                function showTab(targetId) {
                    // Hide all panels
                    tabPanels.forEach(function(panel) {
                        panel.classList.remove('active');
                    });
                    
                    // Remove active from all links
                    tabLinks.forEach(function(link) {
                        link.classList.remove('active');
                    });
                    
                    // Show target panel
                    var targetPanel = document.getElementById(targetId);
                    if (targetPanel) {
                        targetPanel.classList.add('active');
                    }
                    
                    // Set active link
                    var activeLink = document.querySelector('.tab-link[data-tab="' + targetId + '"]');
                    if (activeLink) {
                        activeLink.classList.add('active');
                    }
                }
                
                // Add click handlers
                tabLinks.forEach(function(link) {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        var targetId = this.getAttribute('data-tab');
                        if (targetId) {
                            showTab(targetId);
                        }
                    });
                });
                
                // Ensure first tab is visible on load
                if (tabPanels.length > 0) {
                    var firstPanel = tabPanels[0];
                    if (!firstPanel.classList.contains('active')) {
                        var firstPanelId = firstPanel.getAttribute('id');
                        showTab(firstPanelId);
                    }
                }
            }
            
            // Initialize color pickers when available
            function initColorPickers() {
                if (typeof jQuery !== 'undefined' && jQuery.fn.wpColorPicker) {
                    jQuery('.pickcolor_input').wpColorPicker();
                }
            }
            
            // Initialize when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    initTabs();
                    initColorPickers();
                });
            } else {
                initTabs();
                initColorPickers();
            }
        })();
        </script>
        <?php
    }
    
    /**
     * Render tab content
     */
    private function render_tab_content($tab_id) {
        switch ($tab_id) {
            case 'box_appearance':
                $this->render_box_appearance_tab();
                break;
            case 'messages_appearance':
                $this->render_messages_appearance_tab();
                break;
            case 'messages_input':
                $this->render_messages_input_tab();
                break;
            case 'users_list':
                $this->render_users_list_tab();
                break;
            case 'authentication':
                $this->render_authentication_tab();
                break;
            case 'tinymce_button':
                $this->render_tinymce_button_tab();
                break;
            case 'advanced':
                $this->render_advanced_tab();
                break;
        }
    }
    
    /**
     * Render Box Appearance Tab
     */
    private function render_box_appearance_tab() {
        ?>
        <fieldset>
            <legend><?php _e('Chat Box Container', 'psource-chat'); ?></legend>
            <table border="0" cellpadding="4" cellspacing="0">
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_box_width"><?php _e('Breite', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="text" id="chat_box_width" name="psource_chat_options[box_width]" 
                               class="psource-chat-input-with-select" size="10"
                               value="<?php echo esc_attr($this->get_option('box_width')); ?>" 
                               placeholder="z.B. 500px oder 100%"/>
                        
                        <select id="chat_box_width_mobile_adjust" name="psource_chat_options[box_width_mobile_adjust]">
                            <option value=""><?php _e('-- Anpassen für Mobile Endgeräte --', 'psource-chat'); ?></option>
                            <option value="window" <?php selected($this->get_option('box_width_mobile_adjust'), 'window'); ?>><?php _e('Fensterbreite', 'psource-chat'); ?></option>
                            <option value="full" <?php selected($this->get_option('box_width_mobile_adjust'), 'full'); ?>><?php _e('Gesamtbreite', 'psource-chat'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_box_height"><?php _e('Höhe', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="text" id="chat_box_height" name="psource_chat_options[box_height]" 
                               class="psource-chat-input-with-select" size="10"
                               value="<?php echo esc_attr($this->get_option('box_height')); ?>" 
                               placeholder="z.B. 300px oder 20em"/>
                        
                        <select id="chat_box_height_mobile_adjust" name="psource_chat_options[box_height_mobile_adjust]">
                            <option value=""><?php _e('-- Anpassen für Mobile Endgeräte --', 'psource-chat'); ?></option>
                            <option value="window" <?php selected($this->get_option('box_height_mobile_adjust'), 'window'); ?>><?php _e('Fensterhöhe', 'psource-chat'); ?></option>
                            <option value="full" <?php selected($this->get_option('box_height_mobile_adjust'), 'full'); ?>><?php _e('Volle Höhe', 'psource-chat'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_box_font_family"><?php _e('Schrift', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="chat_box_font_family" name="psource_chat_options[box_font_family]">
                            <option value=""><?php _e('-- Vererbt vom Thema --', 'psource-chat'); ?></option>
                            <option value="Arial" <?php selected($this->get_option('box_font_family'), 'Arial'); ?>>Arial</option>
                            <option value="Helvetica" <?php selected($this->get_option('box_font_family'), 'Helvetica'); ?>>Helvetica</option>
                            <option value="Georgia" <?php selected($this->get_option('box_font_family'), 'Georgia'); ?>>Georgia</option>
                            <option value="Times" <?php selected($this->get_option('box_font_family'), 'Times'); ?>>Times</option>
                            <option value="Verdana" <?php selected($this->get_option('box_font_family'), 'Verdana'); ?>>Verdana</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_box_font_size"><?php _e('Schriftgröße', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="text" id="chat_box_font_size" name="psource_chat_options[box_font_size]"
                               value="<?php echo esc_attr($this->get_option('box_font_size')); ?>" 
                               placeholder="z.B. 12px oder 1em"/>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_box_text_color"><?php _e('Textfarbe', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="text" id="chat_box_text_color" name="psource_chat_options[box_text_color]" 
                               class="pickcolor_input" value="<?php echo esc_attr($this->get_option('box_text_color')); ?>"/>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_box_background_color"><?php _e('Hintergrundfarbe', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="text" id="chat_box_background_color" name="psource_chat_options[box_background_color]" 
                               class="pickcolor_input" value="<?php echo esc_attr($this->get_option('box_background_color')); ?>"/>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_box_border_color"><?php _e('Rahmenfarbe', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="text" id="chat_box_border_color" name="psource_chat_options[box_border_color]" 
                               class="pickcolor_input" value="<?php echo esc_attr($this->get_option('box_border_color')); ?>"/>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_box_border_width"><?php _e('Rahmenbreite', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="text" id="chat_box_border_width" name="psource_chat_options[box_border_width]"
                               value="<?php echo esc_attr($this->get_option('box_border_width')); ?>" 
                               placeholder="z.B. 1px"/>
                    </td>
                </tr>
            </table>
        </fieldset>
        
        <fieldset>
            <legend><?php _e('Chat Box Information', 'psource-chat'); ?></legend>
            <table border="0" cellpadding="4" cellspacing="0">
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_box_title"><?php _e('Titel', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="text" id="chat_box_title" name="psource_chat_options[box_title]"
                               value="<?php echo esc_attr($this->get_option('box_title')); ?>"/>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_box_title_color"><?php _e('Titel Textfarbe', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="text" id="chat_box_title_color" name="psource_chat_options[box_title_color]" 
                               class="pickcolor_input" value="<?php echo esc_attr($this->get_option('box_title_color')); ?>"/>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_box_title_background_color"><?php _e('Titel Hintergrundfarbe', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="text" id="chat_box_title_background_color" name="psource_chat_options[box_title_background_color]" 
                               class="pickcolor_input" value="<?php echo esc_attr($this->get_option('box_title_background_color')); ?>"/>
                    </td>
                </tr>
            </table>
        </fieldset>
        <?php
    }
    
    /**
     * Render Messages Appearance Tab  
     */
    private function render_messages_appearance_tab() {
        ?>
        <fieldset>
            <legend><?php _e('Nachrichten Container', 'psource-chat'); ?></legend>
            <table border="0" cellpadding="4" cellspacing="0">
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_messages_wrapper_height"><?php _e('Höhe', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="text" id="chat_messages_wrapper_height" name="psource_chat_options[messages_wrapper_height]"
                               value="<?php echo esc_attr($this->get_option('messages_wrapper_height')); ?>" 
                               placeholder="z.B. 200px"/>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_messages_wrapper_background_color"><?php _e('Hintergrundfarbe', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="text" id="chat_messages_wrapper_background_color" name="psource_chat_options[messages_wrapper_background_color]" 
                               class="pickcolor_input" value="<?php echo esc_attr($this->get_option('messages_wrapper_background_color')); ?>"/>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_messages_wrapper_border_color"><?php _e('Rahmenfarbe', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="text" id="chat_messages_wrapper_border_color" name="psource_chat_options[messages_wrapper_border_color]" 
                               class="pickcolor_input" value="<?php echo esc_attr($this->get_option('messages_wrapper_border_color')); ?>"/>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_messages_wrapper_border_width"><?php _e('Rahmenbreite', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="text" id="chat_messages_wrapper_border_width" name="psource_chat_options[messages_wrapper_border_width]"
                               value="<?php echo esc_attr($this->get_option('messages_wrapper_border_width')); ?>" 
                               placeholder="z.B. 1px"/>
                    </td>
                </tr>
            </table>
        </fieldset>
        
        <fieldset>
            <legend><?php _e('Nachrichten Zeilen', 'psource-chat'); ?></legend>
            <table border="0" cellpadding="4" cellspacing="0">
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_messages_order"><?php _e('Nachrichten Reihenfolge', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="chat_messages_order" name="psource_chat_options[messages_order]">
                            <option value="desc" <?php selected($this->get_option('messages_order'), 'desc'); ?>><?php _e('Neueste zuerst', 'psource-chat'); ?></option>
                            <option value="asc" <?php selected($this->get_option('messages_order'), 'asc'); ?>><?php _e('Älteste zuerst', 'psource-chat'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_messages_show_date"><?php _e('Datum anzeigen', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="chat_messages_show_date" name="psource_chat_options[messages_show_date]">
                            <option value="enabled" <?php selected($this->get_option('messages_show_date'), 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                            <option value="disabled" <?php selected($this->get_option('messages_show_date'), 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_messages_show_time"><?php _e('Zeit anzeigen', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="chat_messages_show_time" name="psource_chat_options[messages_show_time]">
                            <option value="enabled" <?php selected($this->get_option('messages_show_time'), 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                            <option value="disabled" <?php selected($this->get_option('messages_show_time'), 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_messages_show_avatar"><?php _e('Avatar anzeigen', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="chat_messages_show_avatar" name="psource_chat_options[messages_show_avatar]">
                            <option value="enabled" <?php selected($this->get_option('messages_show_avatar'), 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                            <option value="disabled" <?php selected($this->get_option('messages_show_avatar'), 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_messages_avatar_size"><?php _e('Avatar Größe', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="number" id="chat_messages_avatar_size" name="psource_chat_options[messages_avatar_size]"
                               value="<?php echo esc_attr($this->get_option('messages_avatar_size')); ?>" 
                               min="16" max="128" step="2"/> px
                    </td>
                </tr>
            </table>
        </fieldset>
        <?php
    }
    
    /**
     * Continue with other tab methods...
     */
    private function render_messages_input_tab() {
        ?>
        <fieldset>
            <legend><?php _e('Chat-Nachrichteneingabe', 'psource-chat'); ?></legend>
            <table border="0" cellpadding="4" cellspacing="0">
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_box_input_position"><?php _e('Platzierung der Nachrichteneingabe', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="chat_box_input_position" name="psource_chat_options[box_input_position]">
                            <option value="top" <?php selected($this->get_option('box_input_position'), 'top'); ?>><?php _e('Oben - Neueste Nachrichten oben', 'psource-chat'); ?></option>
                            <option value="bottom" <?php selected($this->get_option('box_input_position'), 'bottom'); ?>><?php _e('Unten - Neueste Nachrichten unten', 'psource-chat'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_row_message_input_height"><?php _e('Eingabefenster Höhe', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="text" id="chat_row_message_input_height" name="psource_chat_options[row_message_input_height]"
                               value="<?php echo esc_attr($this->get_option('row_message_input_height')); ?>" 
                               placeholder="z.B. 60px"/>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_row_message_input_length"><?php _e('Max Zeichen', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="number" id="chat_row_message_input_length" name="psource_chat_options[row_message_input_length]"
                               value="<?php echo esc_attr($this->get_option('row_message_input_length')); ?>" 
                               min="50" max="2000"/>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_box_emoticons"><?php _e('Emoticons', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="chat_box_emoticons" name="psource_chat_options[box_emoticons]">
                            <option value="enabled" <?php selected($this->get_option('box_emoticons'), 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                            <option value="disabled" <?php selected($this->get_option('box_emoticons'), 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                        </select>
                    </td>
                </tr>
            </table>
        </fieldset>
        
        <fieldset>
            <legend><?php _e('Nachricht Sendenschaltfläche', 'psource-chat'); ?></legend>
            <table border="0" cellpadding="4" cellspacing="0">
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_box_send_button_enable"><?php _e('Sendenschaltfläche anzeigen', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="chat_box_send_button_enable" name="psource_chat_options[box_send_button_enable]">
                            <option value="enabled" <?php selected($this->get_option('box_send_button_enable'), 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                            <option value="disabled" <?php selected($this->get_option('box_send_button_enable'), 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                            <option value="mobile_only" <?php selected($this->get_option('box_send_button_enable'), 'mobile_only'); ?>><?php _e('Nur mobile Endgeräte', 'psource-chat'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_box_send_button_label"><?php _e('Sendenschaltfläche Beschriftung', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="text" id="chat_box_send_button_label" name="psource_chat_options[box_send_button_label]"
                               value="<?php echo esc_attr($this->get_option('box_send_button_label')); ?>"/>
                    </td>
                </tr>
            </table>
        </fieldset>
        <?php
    }
    
    private function render_users_list_tab() {
        ?>
        <fieldset>
            <legend><?php _e('Benutzerliste', 'psource-chat'); ?></legend>
            <table border="0" cellpadding="4" cellspacing="0">
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_users_list_show"><?php _e('Benutzerliste anzeigen', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="chat_users_list_show" name="psource_chat_options[users_list_show]">
                            <option value="enabled" <?php selected($this->get_option('users_list_show'), 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                            <option value="disabled" <?php selected($this->get_option('users_list_show'), 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_users_list_position"><?php _e('Position der Benutzerliste', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="chat_users_list_position" name="psource_chat_options[users_list_position]">
                            <option value="right" <?php selected($this->get_option('users_list_position'), 'right'); ?>><?php _e('Rechts', 'psource-chat'); ?></option>
                            <option value="left" <?php selected($this->get_option('users_list_position'), 'left'); ?>><?php _e('Links', 'psource-chat'); ?></option>
                            <option value="top" <?php selected($this->get_option('users_list_position'), 'top'); ?>><?php _e('Oben', 'psource-chat'); ?></option>
                            <option value="bottom" <?php selected($this->get_option('users_list_position'), 'bottom'); ?>><?php _e('Unten', 'psource-chat'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_users_list_width"><?php _e('Breite der Benutzerliste', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="text" id="chat_users_list_width" name="psource_chat_options[users_list_width]"
                               value="<?php echo esc_attr($this->get_option('users_list_width')); ?>" 
                               placeholder="z.B. 150px"/>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_users_list_show_avatars"><?php _e('Avatare in Benutzerliste', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="chat_users_list_show_avatars" name="psource_chat_options[users_list_show_avatars]">
                            <option value="enabled" <?php selected($this->get_option('users_list_show_avatars'), 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                            <option value="disabled" <?php selected($this->get_option('users_list_show_avatars'), 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                        </select>
                    </td>
                </tr>
            </table>
        </fieldset>
        
        <fieldset>
            <legend><?php _e('Benutzer Ein-/Austrittsnachrichten', 'psource-chat'); ?></legend>
            <table border="0" cellpadding="4" cellspacing="0">
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_user_enter_message"><?php _e('Beitritt Nachricht', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="text" id="chat_user_enter_message" name="psource_chat_options[user_enter_message]"
                               value="<?php echo esc_attr($this->get_option('user_enter_message')); ?>" 
                               placeholder="{user} ist dem Chat beigetreten"/>
                        <p class="description"><?php _e('Verwende {user} als Platzhalter für den Benutzernamen', 'psource-chat'); ?></p>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_user_exit_message"><?php _e('Austritt Nachricht', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="text" id="chat_user_exit_message" name="psource_chat_options[user_exit_message]"
                               value="<?php echo esc_attr($this->get_option('user_exit_message')); ?>" 
                               placeholder="{user} hat den Chat verlassen"/>
                        <p class="description"><?php _e('Verwende {user} als Platzhalter für den Benutzernamen', 'psource-chat'); ?></p>
                    </td>
                </tr>
            </table>
        </fieldset>
        <?php
    }
    
    private function render_authentication_tab() {
        global $wp_roles;
        ?>
        <fieldset>
            <legend><?php _e('Anmeldeoptionen', 'psource-chat'); ?> - <?php _e('Authentifizierungsmethoden, die Benutzer verwenden können', 'psource-chat'); ?></legend>
            <table border="0" cellpadding="4" cellspacing="0">
                <tr>
                    <td class="chat-label-column-wide">
                        <p class="info">
                            <strong><?php _e('Für WordPress-Benutzer anzeigen. Schließt automatisch Admin- und SuperAdmin-Benutzer ein. Der Benutzer muss bereits authentifiziert sein.', 'psource-chat'); ?></strong>
                        </p>
                        <ul class="psource-chat-user-roles-list">
                            <?php
                            $selected_roles = $this->get_option('login_options', []);
                            if (!is_array($selected_roles)) {
                                $selected_roles = [];
                            }
                            
                            if (is_object($wp_roles) && count($wp_roles->roles)) {
                                foreach ($wp_roles->roles as $role_slug => $role) {
                                    $checked = '';
                                    $disabled = '';
                                    
                                    if (isset($role['capabilities']['level_10'])) {
                                        $checked = ' checked="checked" ';
                                        $disabled = ' disabled="disabled" ';
                                    } else if (in_array($role_slug, $selected_roles)) {
                                        $checked = ' checked="checked" ';
                                    }
                                    ?>
                                    <li>
                                        <input type="checkbox" id="chat_login_options_<?php echo esc_attr($role_slug); ?>"
                                               <?php echo $checked; ?> <?php echo $disabled; ?>
                                               name="psource_chat_options[login_options][]" value="<?php echo esc_attr($role_slug); ?>"/>
                                        <label for="chat_login_options_<?php echo esc_attr($role_slug); ?>"><?php echo esc_html($role['name']); ?></label>
                                    </li>
                                    <?php
                                }
                            }
                            ?>
                        </ul>
                        
                        <p class="info">
                            <strong><?php _e('Andere Anmeldeoptionen:', 'psource-chat'); ?></strong>
                        </p>
                        <ul class="psource-chat-user-roles-list">
                            <li>
                                <input type="checkbox" id="chat_login_options_public_user"
                                       name="psource_chat_options[login_options][]" value="public_user"
                                       <?php checked(in_array('public_user', $selected_roles)); ?>/>
                                <label for="chat_login_options_public_user"><?php _e('Öffentliche Benutzer', 'psource-chat'); ?></label>
                            </li>
                        </ul>
                    </td>
                </tr>
            </table>
        </fieldset>
        
        <fieldset>
            <legend><?php _e('Moderator-Rollen', 'psource-chat'); ?></legend>
            <table border="0" cellpadding="4" cellspacing="0">
                <tr>
                    <td class="chat-label-column-wide">
                        <ul class="psource-chat-user-roles-list">
                            <?php
                            $moderator_roles = $this->get_option('moderator_roles', []);
                            if (!is_array($moderator_roles)) {
                                $moderator_roles = [];
                            }
                            
                            if (is_object($wp_roles) && count($wp_roles->roles)) {
                                foreach ($wp_roles->roles as $role_slug => $role) {
                                    $checked = in_array($role_slug, $moderator_roles) ? ' checked="checked" ' : '';
                                    ?>
                                    <li>
                                        <input type="checkbox" id="chat_moderator_roles_<?php echo esc_attr($role_slug); ?>"
                                               name="psource_chat_options[moderator_roles][]" value="<?php echo esc_attr($role_slug); ?>"
                                               <?php echo $checked; ?>/>
                                        <label for="chat_moderator_roles_<?php echo esc_attr($role_slug); ?>"><?php echo esc_html($role['name']); ?></label>
                                    </li>
                                    <?php
                                }
                            }
                            ?>
                        </ul>
                    </td>
                </tr>
            </table>
        </fieldset>
        <?php
    }
    
    private function render_tinymce_button_tab() {
        ?>
        <fieldset>
            <legend><?php _e('TinyMCE Editor Button', 'psource-chat'); ?></legend>
            <table border="0" cellpadding="4" cellspacing="0">
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_tinymce_button_enabled"><?php _e('Button aktivieren', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="chat_tinymce_button_enabled" name="psource_chat_options[tinymce_button_enabled]">
                            <option value="enabled" <?php selected($this->get_option('tinymce_button_enabled'), 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                            <option value="disabled" <?php selected($this->get_option('tinymce_button_enabled'), 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_tinymce_button_post_types"><?php _e('Post Types', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <?php
                        $selected_post_types = $this->get_option('tinymce_button_post_types', []);
                        if (!is_array($selected_post_types)) {
                            $selected_post_types = [];
                        }
                        
                        $post_types = get_post_types(['public' => true], 'objects');
                        foreach ($post_types as $post_type) {
                            $checked = in_array($post_type->name, $selected_post_types) ? ' checked="checked" ' : '';
                            ?>
                            <label>
                                <input type="checkbox" name="psource_chat_options[tinymce_button_post_types][]" 
                                       value="<?php echo esc_attr($post_type->name); ?>" <?php echo $checked; ?>/>
                                <?php echo esc_html($post_type->label); ?>
                            </label><br/>
                            <?php
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_tinymce_button_roles"><?php _e('Benutzerrollen', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <?php
                        global $wp_roles;
                        $selected_roles = $this->get_option('tinymce_button_roles', []);
                        if (!is_array($selected_roles)) {
                            $selected_roles = [];
                        }
                        
                        if (is_object($wp_roles) && count($wp_roles->roles)) {
                            foreach ($wp_roles->roles as $role_slug => $role) {
                                $checked = in_array($role_slug, $selected_roles) ? ' checked="checked" ' : '';
                                ?>
                                <label>
                                    <input type="checkbox" name="psource_chat_options[tinymce_button_roles][]" 
                                           value="<?php echo esc_attr($role_slug); ?>" <?php echo $checked; ?>/>
                                    <?php echo esc_html($role['name']); ?>
                                </label><br/>
                                <?php
                            }
                        }
                        ?>
                    </td>
                </tr>
            </table>
        </fieldset>
        <?php
    }
    
    private function render_advanced_tab() {
        ?>
        <fieldset>
            <legend><?php _e('Chatprotokolle', 'psource-chat'); ?></legend>
            <table border="0" cellpadding="4" cellspacing="0">
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_log_creation"><?php _e('Protokollerstellung', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="chat_log_creation" name="psource_chat_options[log_creation]">
                            <option value="enabled" <?php selected($this->get_option('log_creation'), 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                            <option value="disabled" <?php selected($this->get_option('log_creation'), 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_log_limit"><?php _e('Begrenzt Nachrichten anzeigen', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="number" id="chat_log_limit" name="psource_chat_options[log_limit]"
                               value="<?php echo esc_attr($this->get_option('log_limit')); ?>" 
                               min="10" max="1000"/>
                        <p class="description"><?php _e('Standard 100. Für alle leer lassen.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_session_status_message"><?php _e('Sitzung geschlossen Nachricht', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="text" id="chat_session_status_message" name="psource_chat_options[session_status_message]"
                               value="<?php echo esc_attr($this->get_option('session_status_message')); ?>"/>
                    </td>
                </tr>
            </table>
        </fieldset>
        
        <fieldset>
            <legend><?php _e('Blockierte Inhalte', 'psource-chat'); ?></legend>
            <table border="0" cellpadding="4" cellspacing="0">
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_blocked_words"><?php _e('Blockierte Wörter', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <textarea id="chat_blocked_words" name="psource_chat_options[blocked_words]" rows="4" cols="50"><?php echo esc_textarea($this->get_option('blocked_words')); ?></textarea>
                        <p class="description"><?php _e('Ein Wort pro Zeile. Diese Wörter werden automatisch blockiert.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_blocked_ip_addresses"><?php _e('Blockierte IP-Adressen', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <textarea id="chat_blocked_ip_addresses" name="psource_chat_options[blocked_ip_addresses]" rows="4" cols="50"><?php echo esc_textarea($this->get_option('blocked_ip_addresses')); ?></textarea>
                        <p class="description"><?php _e('Eine IP-Adresse pro Zeile. Diese IPs werden blockiert.', 'psource-chat'); ?></p>
                    </td>
                </tr>
            </table>
        </fieldset>
        
        <fieldset>
            <legend><?php _e('Sound und Effekte', 'psource-chat'); ?></legend>
            <table border="0" cellpadding="4" cellspacing="0">
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_enable_sound"><?php _e('Sound aktivieren', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="chat_enable_sound" name="psource_chat_options[enable_sound]">
                            <option value="enabled" <?php selected($this->get_option('enable_sound'), 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                            <option value="disabled" <?php selected($this->get_option('enable_sound'), 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_sound_file"><?php _e('Sound-Datei', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="chat_sound_file" name="psource_chat_options[sound_file]">
                            <option value="chime" <?php selected($this->get_option('sound_file'), 'chime'); ?>><?php _e('Chime', 'psource-chat'); ?></option>
                            <option value="ping" <?php selected($this->get_option('sound_file'), 'ping'); ?>><?php _e('Ping', 'psource-chat'); ?></option>
                            <option value="custom" <?php selected($this->get_option('sound_file'), 'custom'); ?>><?php _e('Benutzerdefiniert', 'psource-chat'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_chat_session_timeout"><?php _e('Chat Session Timeout', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="number" id="chat_chat_session_timeout" name="psource_chat_options[chat_session_timeout]"
                               value="<?php echo esc_attr($this->get_option('chat_session_timeout')); ?>" 
                               min="60" max="3600"/> <?php _e('Sekunden', 'psource-chat'); ?>
                    </td>
                </tr>
            </table>
        </fieldset>
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
                if (strpos($key, '_color') !== false) {
                    $sanitized[$key] = sanitize_hex_color($options[$key]);
                } else if (in_array($key, ['box_width', 'box_height', 'box_font_size', 'box_border_width'])) {
                    $sanitized[$key] = sanitize_text_field($options[$key]);
                } else if (is_array($default_value)) {
                    $sanitized[$key] = array_map('sanitize_text_field', (array)$options[$key]);
                } else {
                    $sanitized[$key] = sanitize_text_field($options[$key]);
                }
            } else {
                $sanitized[$key] = $default_value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Get option value
     */
    private function get_option($key, $default = '') {
        return isset($this->options[$key]) ? $this->options[$key] : $default;
    }
}
