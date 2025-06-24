<?php
/**
 * Chat Extensions System
 * 
 * Provides an extensible system for third-party plugins to integrate chat settings
 * 
 * @package PSSource\Chat\Admin
 */

namespace PSSource\Chat\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Chat Extensions Manager
 */
class Chat_Extensions {
    
    private $extensions = [];
    private $extension_tabs = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->register_core_extensions();
        add_action('psource_chat_register_extensions', [$this, 'register_extensions_hook']);
    }
    
    /**
     * Register core extensions (Dashboard, Frontend, etc.)
     */
    public function register_core_extensions() {
        // Dashboard Extension
        $this->register_extension('dashboard', [
            'title' => __('Dashboard Chat', 'psource-chat'),
            'description' => __('Chat-Widget für das WordPress Dashboard', 'psource-chat'),
            'icon' => 'dashicons-dashboard',
            'callback' => [$this, 'render_dashboard_extension'],
            'priority' => 10
        ]);
        
        // Seitenkanten Chat Extension (Frontend Floating Chat)
        $this->register_extension('frontend', [
            'title' => __('Seitenkanten Chat', 'psource-chat'),
            'description' => __('Schwimmender Chat-Button am unteren Bildschirmrand', 'psource-chat'),
            'icon' => 'dashicons-admin-site-alt3',
            'callback' => [$this, 'render_frontend_extension'],
            'priority' => 20
        ]);
        
        // Widgets Extension
        $this->register_extension('widgets', [
            'title' => __('Chat Widgets', 'psource-chat'),
            'description' => __('Sidebar-Widgets für Chat, Status, Freunde und Räume', 'psource-chat'),
            'icon' => 'dashicons-admin-generic',
            'callback' => [$this, 'render_widgets_extension'],
            'priority' => 15
        ]);
        
        // Admin Bar Extension
        $this->register_extension('admin_bar', [
            'title' => __('Admin Bar Chat', 'psource-chat'),
            'description' => __('Chat-Status und Freunde in der WordPress Admin Bar', 'psource-chat'),
            'icon' => 'dashicons-admin-network',
            'callback' => [$this, 'render_adminbar_extension'],
            'priority' => 25
        ]);
        
        // Performance Extension
        $this->register_extension('performance', [
            'title' => __('Performance & Polling', 'psource-chat'),
            'description' => __('Abfrageintervalle und Performance-Einstellungen', 'psource-chat'),
            'icon' => 'dashicons-performance',
            'callback' => [$this, 'render_performance_extension'],
            'priority' => 30
        ]);
        
        // Security Extension
        $this->register_extension('security', [
            'title' => __('Sicherheit & Blockierung', 'psource-chat'),
            'description' => __('IP-Blockierung, Wort-Filter und URL-Blockierung', 'psource-chat'),
            'icon' => 'dashicons-shield',
            'callback' => [$this, 'render_security_extension'],
            'priority' => 40
        ]);
        
        // BuddyPress Extension (wenn verfügbar)
        if (class_exists('BuddyPress')) {
            $this->register_extension('buddypress', [
                'title' => __('BuddyPress Integration', 'psource-chat'),
                'description' => __('Integration mit BuddyPress Community-Features', 'psource-chat'),
                'icon' => 'dashicons-groups',
                'callback' => [$this, 'render_buddypress_extension'],
                'priority' => 50
            ]);
        }
        
        // Support Chat Extension
        $this->register_extension('support_chat', [
            'title' => __('Support Chat', 'psource-chat'),
            'description' => __('Kundenbetreuung mit privaten Chat-Sessions und Kategorien', 'psource-chat'),
            'icon' => 'dashicons-sos',
            'callback' => [$this, 'render_support_chat_extension'],
            'priority' => 25
        ]);
        
        // Attachments Extension
        $this->register_extension('attachments', [
            'title' => __('Anhänge & Medien', 'psource-chat'),
            'description' => __('Emojis, GIFs und Datei-Uploads für Chat-Nachrichten', 'psource-chat'),
            'icon' => 'dashicons-paperclip',
            'callback' => [$this, 'render_attachments_extension'],
            'priority' => 15
        ]);
        
        // Private Chat Extension  
        $this->register_extension('private_chat', [
            'title' => __('Privater Chat', 'psource-chat'),
            'description' => __('Eins-zu-Eins Chat-Sessions zwischen Benutzern', 'psource-chat'),
            'icon' => 'dashicons-privacy',
            'callback' => [$this, 'render_private_chat_extension'],
            'priority' => 20
        ]);
        
        // WYSIWYG Button Extension
        $this->register_extension('wysiwyg_button', [
            'title' => __('WYSIWYG Chat-Button', 'psource-chat'),
            'description' => __('Chat-Button im WordPress Editor für Beiträge und Seiten', 'psource-chat'),
            'icon' => 'dashicons-editor-code',
            'callback' => [$this, 'render_wysiwyg_extension'],
            'priority' => 35
        ]);

        // Allow third-party plugins to register extensions
        do_action('psource_chat_register_extensions', $this);
    }
    
    /**
     * Register an extension
     */
    public function register_extension($id, $args) {
        $defaults = [
            'title' => '',
            'description' => '',
            'icon' => 'dashicons-admin-generic',
            'callback' => null,
            'priority' => 100,
            'capability' => 'manage_options'
        ];
        
        $extension = array_merge($defaults, $args);
        $extension['id'] = $id;
        
        $this->extensions[$id] = $extension;
        
        // Debug: Ensure extension is stored
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("PS Chat: Registered extension '$id' - Total: " . count($this->extensions));
        }
    }
    
    /**
     * Get registered extensions
     */
    public function get_extensions() {
        // Sort by priority
        uasort($this->extensions, function($a, $b) {
            return $a['priority'] - $b['priority'];
        });
        
        return $this->extensions;
    }
    
    /**
     * Render extensions page
     */
    public function render_extensions_page() {
        
        // Handle database fix request
        if (isset($_GET['fix_database']) && $_GET['fix_database'] === '1') {
            $this->handle_database_fix();
        }
        
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['psource_chat_extensions_nonce'])) {
            if (wp_verify_nonce($_POST['psource_chat_extensions_nonce'], 'psource_chat_extensions')) {
                $this->handle_form_submission();
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>' . 
                     __('Nonce-Überprüfung fehlgeschlagen.', 'psource-chat') . '</p></div>';
            }
        }
        
        $extensions = $this->get_extensions();
        
        // Debug-Ausgabe
        if (defined('WP_DEBUG') && WP_DEBUG) {
            echo '<div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border-left: 4px solid #0073aa;">';
            echo '<strong>Debug Info:</strong><br>';
            echo 'Anzahl registrierte Erweiterungen: ' . count($extensions) . '<br>';
            if (!empty($extensions)) {
                echo 'Erweiterungen: ' . implode(', ', array_keys($extensions)) . '<br>';
            }
            echo '</div>';
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('PS Chat Erweiterungen', 'psource-chat'); ?></h1>
            
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 20px 0; border-radius: 3px;">
                <h3 style="margin-top: 0;"><?php _e('Datenbank-Probleme?', 'psource-chat'); ?></h3>
                <p><?php _e('Falls Chat-Nachrichten nicht korrekt gesendet/empfangen werden können, korrigiere die Datenbank-Struktur:', 'psource-chat'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=psource-chat-extensions&fix_database=1'); ?>" 
                   class="button button-primary" 
                   onclick="return confirm('<?php _e('Datenbank-Tabellen werden neu erstellt. Fortfahren?', 'psource-chat'); ?>');">
                    <?php _e('🔧 Datenbank reparieren', 'psource-chat'); ?>
                </a>
            </div>
            
            <p><?php _e('Hier findest Du erweiterte Chat-Funktionen und Integrationen. Jede Erweiterung kann individuell konfiguriert werden.', 'psource-chat'); ?></p>
            
            <?php if (empty($extensions)): ?>
                <div class="notice notice-info">
                    <p><?php _e('Keine Erweiterungen verfügbar. Du kannst Plugins installieren, die Chat-Erweiterungen bereitstellen.', 'psource-chat'); ?></p>
                </div>
            <?php else: ?>
                <div id="chat_extensions_pane" class="chat_extensions_pane">
                    <ul class="extension-nav">
                        <?php $first_ext = true; foreach ($extensions as $ext_id => $extension): ?>
                            <?php if (current_user_can($extension['capability'])): ?>
                                <li class="extension-item">
                                    <a href="#" data-extension="<?php echo esc_attr($ext_id); ?>_panel" 
                                       class="extension-link<?php echo $first_ext ? ' active' : ''; ?>">
                                        <span class="dashicons <?php echo esc_attr($extension['icon']); ?>"></span>
                                        <span class="extension-title"><?php echo esc_html($extension['title']); ?></span>
                                        <span class="extension-description"><?php echo esc_html($extension['description']); ?></span>
                                    </a>
                                </li>
                            <?php $first_ext = false; endif; ?>
                        <?php endforeach; ?>
                    </ul>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('psource_chat_extensions', 'psource_chat_extensions_nonce'); ?>
                        
                        <?php $first_panel = true; foreach ($extensions as $ext_id => $extension): ?>
                            <?php if (current_user_can($extension['capability'])): ?>
                                <div id="<?php echo esc_attr($ext_id); ?>_panel" class="extension-panel<?php echo $first_panel ? ' active' : ''; ?>">
                                    <div class="extension-header">
                                        <h2>
                                            <span class="dashicons <?php echo esc_attr($extension['icon']); ?>"></span>
                                            <?php echo esc_html($extension['title']); ?>
                                        </h2>
                                        <p class="description"><?php echo esc_html($extension['description']); ?></p>
                                    </div>
                                    
                                    <div class="extension-content">
                                        <?php 
                                        if (is_callable($extension['callback'])) {
                                            call_user_func($extension['callback'], $ext_id);
                                        } else {
                                            echo '<p>' . __('Diese Erweiterung ist noch nicht implementiert.', 'psource-chat') . '</p>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            <?php $first_panel = false; endif; ?>
                        <?php endforeach; ?>
                        
                        <?php submit_button(__('Erweiterungseinstellungen speichern', 'psource-chat')); ?>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        
        <style>
        .chat_extensions_pane {
            margin-top: 20px;
        }
        
        .extension-nav {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
            list-style: none;
            margin: 0 0 20px 0;
            padding: 0;
        }
        
        .extension-item {
            margin: 0;
        }
        
        .extension-link {
            display: block;
            padding: 20px;
            text-decoration: none;
            border: 2px solid #ddd;
            border-radius: 8px;
            background: #fff;
            color: #32373c;
            transition: all 0.3s ease;
            cursor: pointer;
            min-height: 120px;
        }
        
        .extension-link:hover {
            border-color: #0073aa;
            box-shadow: 0 2px 8px rgba(0,115,170,0.1);
            transform: translateY(-2px);
        }
        
        .extension-link.active {
            border-color: #0073aa;
            background: #f8f9fa;
            box-shadow: 0 2px 8px rgba(0,115,170,0.15);
        }
        
        .extension-link .dashicons {
            display: block;
            font-size: 32px;
            width: 32px;
            height: 32px;
            margin: 0 0 10px 0;
            color: #0073aa;
        }
        
        .extension-title {
            display: block;
            font-size: 16px;
            font-weight: 600;
            margin: 0 0 8px 0;
            color: #23282d;
        }
        
        .extension-description {
            display: block;
            font-size: 13px;
            color: #666;
            line-height: 1.4;
        }
        
        .extension-panel {
            display: none;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            background: #fff;
            margin-bottom: 20px;
        }
        
        .extension-panel.active {
            display: block;
        }
        
        .extension-header {
            padding: 20px 20px 0 20px;
            border-bottom: 1px solid #eee;
            margin-bottom: 0;
        }
        
        .extension-header h2 {
            display: flex;
            align-items: center;
            margin: 0 0 10px 0;
            font-size: 20px;
        }
        
        .extension-header h2 .dashicons {
            margin-right: 10px;
            color: #0073aa;
        }
        
        .extension-header .description {
            margin: 0 0 20px 0;
            font-style: italic;
            color: #666;
        }
        
        .extension-content {
            padding: 20px;
        }
        
        .extension-settings-fieldset,
        .attachments-settings-fieldset {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            background: #f9f9f9;
        }
        
        .extension-settings-fieldset legend,
        .attachments-settings-fieldset legend {
            font-weight: 600;
            font-size: 16px;
            padding: 0 10px;
            color: #23282d;
        }
        
        .extension-settings-fieldset .form-table,
        .attachments-settings-fieldset .form-table {
            margin-top: 15px;
        }
        
        /* Legacy table styling for older fieldsets */
        .extension-settings-fieldset table[border],
        .attachments-settings-fieldset table[border] {
            width: 100%;
            border-collapse: collapse;
        }
        
        .extension-settings-fieldset table[border] td,
        .attachments-settings-fieldset table[border] td {
            padding: 10px;
            vertical-align: top;
        }
        
        .chat-label-column {
            width: 30%;
            font-weight: 600;
        }
        
        .chat-value-column {
            width: 70%;
        }
        
        @media (max-width: 768px) {
            .extension-nav {
                grid-template-columns: 1fr;
            }
        }
        </style>
        
        <script>
        (function() {
            function initExtensions() {
                var extensionLinks = document.querySelectorAll('.extension-link');
                var extensionPanels = document.querySelectorAll('.extension-panel');
                
                function showExtension(targetId) {
                    // Hide all panels
                    extensionPanels.forEach(function(panel) {
                        panel.classList.remove('active');
                    });
                    
                    // Remove active from all links
                    extensionLinks.forEach(function(link) {
                        link.classList.remove('active');
                    });
                    
                    // Show target panel
                    var targetPanel = document.getElementById(targetId);
                    if (targetPanel) {
                        targetPanel.classList.add('active');
                    }
                    
                    // Set active link
                    var activeLink = document.querySelector('.extension-link[data-extension="' + targetId + '"]');
                    if (activeLink) {
                        activeLink.classList.add('active');
                    }
                }
                
                // Add click handlers
                extensionLinks.forEach(function(link) {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        var targetId = this.getAttribute('data-extension');
                        if (targetId) {
                            showExtension(targetId);
                        }
                    });
                });
                
                // Ensure first extension is visible on load
                if (extensionPanels.length > 0) {
                    var firstPanel = extensionPanels[0];
                    if (!firstPanel.classList.contains('active')) {
                        var firstPanelId = firstPanel.getAttribute('id');
                        showExtension(firstPanelId);
                    }
                }
            }
            
            // Initialize when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initExtensions);
            } else {
                initExtensions();
            }
        })();
        </script>
        <?php
    }
    
    /**
     * Handle form submission
     */
    private function handle_form_submission() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Keine Berechtigung zum Speichern von Einstellungen.', 'psource-chat'));
        }
        
        $options = $_POST['psource_chat_extensions'] ?? [];
        
        // Sanitize options
        $sanitized_options = [];
        foreach ($options as $extension_id => $extension_options) {
            $sanitized_options[$extension_id] = [];
            foreach ($extension_options as $key => $value) {
                if (is_array($value)) {
                    $sanitized_options[$extension_id][$key] = array_map('sanitize_text_field', $value);
                } else {
                    $sanitized_options[$extension_id][$key] = sanitize_text_field($value);
                }
            }
        }
        
        // Save options
        update_option('psource_chat_extensions', $sanitized_options);
        
        // Force dashboard widgets to reload by clearing any caches
        if (function_exists('wp_cache_delete')) {
            wp_cache_delete('psource_chat_extensions', 'options');
        }
        
        echo '<div class="notice notice-success is-dismissible"><p>' . 
             __('Erweiterungseinstellungen gespeichert!', 'psource-chat') . '</p></div>';
    }
    
    /**
     * Handle database fix
     */
    private function handle_database_fix() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Keine Berechtigung zum Reparieren der Datenbank.', 'psource-chat'));
        }
        
        echo '<div class="notice notice-info"><p><strong>' . __('Datenbank wird repariert...', 'psource-chat') . '</strong></p></div>';
        
        global $wpdb;
        
        // 1. Lösche alte Tabellen
        $old_tables = [
            $wpdb->prefix . 'psource_chat_messages',
            $wpdb->prefix . 'psource_chat_sessions',
            $wpdb->prefix . 'psource_chat_user_sessions',
            $wpdb->prefix . 'psource_chat_logs'
        ];
        
        foreach ($old_tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        // 2. Erstelle neue Tabellen
        \PSSource\Chat\Core\Database::create_tables();
        
        // 3. Setze Standard-Optionen
        $defaults = [
            'psource_chat_enable_guest_chat' => 'yes',
            'psource_chat_enable_dashboard_widget' => 'yes',
            'psource_chat_enable_frontend_chat' => 'yes',
            'psource_chat_enable_admin_bar' => 'yes',
            'psource_chat_enable_buddypress' => 'no',
            'psource_chat_max_message_length' => '1000',
            'psource_chat_refresh_interval' => '3',
            'psource_chat_enable_sound' => 'yes',
            'psource_chat_enable_smilies' => 'yes'
        ];
        
        foreach ($defaults as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }
        
        echo '<div class="notice notice-success is-dismissible"><p><strong>' . 
             __('✅ Datenbank erfolgreich repariert! Du kannst jetzt Chat-Nachrichten senden.', 'psource-chat') . 
             '</strong></p></div>';
    }
    
    /**
     * Render Dashboard Extension
     */
    public function render_dashboard_extension($ext_id) {
        $options = get_option('psource_chat_extensions', []);
        $dashboard_options = $options['dashboard'] ?? [];
        ?>
        <fieldset class="extension-settings-fieldset">
            <legend><?php _e('Dashboard Chat Widget', 'psource-chat'); ?></legend>
            <table class="form-table">
                <tr>
                    <td class="chat-label-column">
                        <label for="dashboard_widget_enabled"><?php _e('Dashboard Widget aktivieren', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="dashboard_widget_enabled" name="psource_chat_extensions[dashboard][widget_enabled]">
                            <option value="enabled" <?php selected($dashboard_options['widget_enabled'] ?? 'disabled', 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                            <option value="disabled" <?php selected($dashboard_options['widget_enabled'] ?? 'disabled', 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Hauptchat-Widget im WordPress Dashboard', 'psource-chat'); ?></p>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="dashboard_widget_title"><?php _e('Widget Titel', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="text" id="dashboard_widget_title" name="psource_chat_extensions[dashboard][widget_title]" 
                               value="<?php echo esc_attr($dashboard_options['widget_title'] ?? 'Chat'); ?>"/>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="dashboard_widget_height"><?php _e('Widget Höhe', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="text" id="dashboard_widget_height" name="psource_chat_extensions[dashboard][widget_height]" 
                               value="<?php echo esc_attr($dashboard_options['widget_height'] ?? '380px'); ?>" 
                               placeholder="z.B. 380px"/>
                    </td>
                </tr>
            </table>
        </fieldset>
        
        <fieldset class="extension-settings-fieldset">
            <legend><?php _e('Dashboard Status Widget', 'psource-chat'); ?></legend>
            <table class="form-table">
                <tr>
                    <td class="chat-label-column">
                        <label for="dashboard_status_widget_enabled"><?php _e('Status Widget aktivieren', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="dashboard_status_widget_enabled" name="psource_chat_extensions[dashboard][status_widget_enabled]">
                            <option value="enabled" <?php selected($dashboard_options['status_widget_enabled'] ?? 'disabled', 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                            <option value="disabled" <?php selected($dashboard_options['status_widget_enabled'] ?? 'disabled', 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Ermöglicht schnelle Status-Änderung im Dashboard', 'psource-chat'); ?></p>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="dashboard_friends_widget_enabled"><?php _e('Freunde Widget aktivieren', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="dashboard_friends_widget_enabled" name="psource_chat_extensions[dashboard][friends_widget_enabled]">
                            <option value="enabled" <?php selected($dashboard_options['friends_widget_enabled'] ?? 'disabled', 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                            <option value="disabled" <?php selected($dashboard_options['friends_widget_enabled'] ?? 'disabled', 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Zeigt Online-Freunde im Dashboard (benötigt BuddyPress oder PS Freunde Plugin)', 'psource-chat'); ?></p>
                    </td>
                </tr>
            </table>
        </fieldset>
        
        <fieldset>
            <legend><?php _e('Dashboard Widget Berechtigungen', 'psource-chat'); ?></legend>
            <table border="0" cellpadding="4" cellspacing="0">
                <tr>
                    <td class="chat-label-column">
                        <label for="dashboard_user_control"><?php _e('Benutzer-Kontrolle', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="dashboard_user_control" name="psource_chat_extensions[dashboard][user_control]">
                            <option value="enabled" <?php selected($dashboard_options['user_control'] ?? 'enabled', 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                            <option value="disabled" <?php selected($dashboard_options['user_control'] ?? 'enabled', 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Ermöglicht Benutzern, Dashboard-Widgets über ihr Profil zu steuern', 'psource-chat'); ?></p>
                    </td>
                </tr>
                <?php if (is_multisite() && is_network_admin()): ?>
                <tr>
                    <td class="chat-label-column">
                        <label for="dashboard_network_mode"><?php _e('Netzwerk-Modus', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="dashboard_network_mode" name="psource_chat_extensions[dashboard][network_mode]">
                            <option value="site" <?php selected($dashboard_options['network_mode'] ?? 'site', 'site'); ?>><?php _e('Pro Website (Standard)', 'psource-chat'); ?></option>
                            <option value="network" <?php selected($dashboard_options['network_mode'] ?? 'site', 'network'); ?>><?php _e('Netzwerk-weit', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Steuert ob Dashboard-Widgets pro Website oder netzwerk-weit verwaltet werden.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                <?php endif; ?>
            </table>
        </fieldset>
        <?php
    }
    
    /**
     * Render Frontend Extension (Seitenkanten Chat)
     */
    public function render_frontend_extension($ext_id) {
        $options = get_option('psource_chat_extensions', []);
        $frontend_options = $options['frontend'] ?? [];
        
        // Tab navigation
        $active_tab = $_GET['tab'] ?? 'general';
        ?>
        <div class="psource-chat-extension-tabs">
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo admin_url('admin.php?page=psource-chat-extensions&extension=frontend&tab=general'); ?>" 
                   class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Allgemeine Einstellungen', 'psource-chat'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=psource-chat-extensions&extension=frontend&tab=attachments'); ?>" 
                   class="nav-tab <?php echo $active_tab === 'attachments' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Attachments', 'psource-chat'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=psource-chat-extensions&extension=frontend&tab=appearance'); ?>" 
                   class="nav-tab <?php echo $active_tab === 'appearance' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Darstellung/Aussehen', 'psource-chat'); ?>
                </a>
            </h2>
            
            <?php if ($active_tab === 'general'): ?>
                <?php $this->render_frontend_general_tab($frontend_options); ?>
            <?php elseif ($active_tab === 'attachments'): ?>
                <?php $this->render_frontend_attachments_tab($frontend_options); ?>
            <?php elseif ($active_tab === 'appearance'): ?>
                <?php $this->render_frontend_appearance_tab($frontend_options); ?>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render general settings tab for frontend
     */
    private function render_frontend_general_tab($frontend_options) {
        ?>
        <fieldset class="extension-settings-fieldset">
            <legend><?php _e('Seitenkanten Chat Einstellungen', 'psource-chat'); ?></legend>
            <p class="description"><?php _e('Der Seitenkanten Chat ist ein schwimmender Chat-Button, der auf allen Seiten der Website angezeigt wird.', 'psource-chat'); ?></p>
            
            <table class="form-table">
                <tr>
                    <td class="chat-label-column">
                        <label for="frontend_enabled"><?php _e('Seitenkanten Chat aktivieren', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="frontend_enabled" name="psource_chat_extensions[frontend][enabled]">
                            <option value="disabled" <?php selected($frontend_options['enabled'] ?? 'disabled', 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                            <option value="enabled" <?php selected($frontend_options['enabled'] ?? 'disabled', 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Zeigt einen schwimmenden Chat-Button am Bildschirmrand an.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <td class="chat-label-column">
                        <label for="frontend_show_in_admin"><?php _e('Im Dashboard anzeigen', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="frontend_show_in_admin" name="psource_chat_extensions[frontend][show_in_admin]">
                            <option value="no" <?php selected($frontend_options['show_in_admin'] ?? 'no', 'no'); ?>><?php _e('Nein - nur Frontend', 'psource-chat'); ?></option>
                            <option value="yes" <?php selected($frontend_options['show_in_admin'] ?? 'no', 'yes'); ?>><?php _e('Ja - auch im WordPress Dashboard', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Zeigt den Chat auch im WordPress Admin-Bereich an.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <td class="chat-label-column">
                        <label for="frontend_title"><?php _e('Chat-Titel', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="text" id="frontend_title" name="psource_chat_extensions[frontend][title]" 
                               value="<?php echo esc_attr($frontend_options['title'] ?? __('Chat', 'psource-chat')); ?>" 
                               class="regular-text" />
                        <p class="description"><?php _e('Titel wird in der Chat-Kopfleiste angezeigt.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <td class="chat-label-column">
                        <label for="frontend_position"><?php _e('Position', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="frontend_position" name="psource_chat_extensions[frontend][position]">
                            <option value="bottom-right" <?php selected($frontend_options['position'] ?? 'bottom-right', 'bottom-right'); ?>><?php _e('Unten rechts', 'psource-chat'); ?></option>
                            <option value="bottom-left" <?php selected($frontend_options['position'] ?? 'bottom-right', 'bottom-left'); ?>><?php _e('Unten links', 'psource-chat'); ?></option>
                            <option value="top-right" <?php selected($frontend_options['position'] ?? 'bottom-right', 'top-right'); ?>><?php _e('Oben rechts', 'psource-chat'); ?></option>
                            <option value="top-left" <?php selected($frontend_options['position'] ?? 'bottom-right', 'top-left'); ?>><?php _e('Oben links', 'psource-chat'); ?></option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <td class="chat-label-column">
                        <label for="frontend_initial_state"><?php _e('Anfangszustand', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="frontend_initial_state" name="psource_chat_extensions[frontend][initial_state]">
                            <option value="minimized" <?php selected($frontend_options['initial_state'] ?? 'minimized', 'minimized'); ?>><?php _e('Minimiert (nur Titel-Leiste)', 'psource-chat'); ?></option>
                            <option value="maximized" <?php selected($frontend_options['initial_state'] ?? 'minimized', 'maximized'); ?>><?php _e('Maximiert (vollständig geöffnet)', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Wie der Chat beim ersten Laden der Seite erscheint.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <td class="chat-label-column">
                        <label for="frontend_notifications"><?php _e('Benachrichtigungen', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="frontend_notifications" name="psource_chat_extensions[frontend][notifications]">
                            <option value="enabled" <?php selected($frontend_options['notifications'] ?? 'enabled', 'enabled'); ?>><?php _e('Aktiviert - bei neuen Nachrichten', 'psource-chat'); ?></option>
                            <option value="disabled" <?php selected($frontend_options['notifications'] ?? 'enabled', 'disabled'); ?>><?php _e('Deaktiviert - keine Benachrichtigungen', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Benachrichtigungen bei neuen Nachrichten, wenn Chat minimiert ist.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <td class="chat-label-column">
                        <label for="frontend_width"><?php _e('Chat-Breite', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="number" id="frontend_width" name="psource_chat_extensions[frontend][width]" value="<?php echo esc_attr($frontend_options['width'] ?? '400'); ?>" min="300" max="600" /> px
                        <p class="description"><?php _e('Breite des Chat-Fensters (300-600px)', 'psource-chat'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <td class="chat-label-column">
                        <label for="frontend_height"><?php _e('Chat-Höhe', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="number" id="frontend_height" name="psource_chat_extensions[frontend][height]" value="<?php echo esc_attr($frontend_options['height'] ?? '500'); ?>" min="300" max="800" /> px
                        <p class="description"><?php _e('Maximale Höhe des Chat-Fensters (300-800px)', 'psource-chat'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <td class="chat-label-column">
                        <label for="frontend_guest_chat"><?php _e('Gäste-Chat erlauben', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="frontend_guest_chat" name="psource_chat_extensions[frontend][allow_guest_chat]">
                            <option value="no" <?php selected($frontend_options['allow_guest_chat'] ?? 'no', 'no'); ?>><?php _e('Nein - nur angemeldete Benutzer', 'psource-chat'); ?></option>
                            <option value="yes" <?php selected($frontend_options['allow_guest_chat'] ?? 'no', 'yes'); ?>><?php _e('Ja - auch Gäste können chatten', 'psource-chat'); ?></option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <td class="chat-label-column">
                        <label for="frontend_enable_emoji"><?php _e('Emoji-Button aktivieren', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="frontend_enable_emoji" name="psource_chat_extensions[frontend][enable_emoji]">
                            <option value="yes" <?php selected($frontend_options['enable_emoji'] ?? 'yes', 'yes'); ?>><?php _e('Ja - Emoji-Button anzeigen', 'psource-chat'); ?></option>
                            <option value="no" <?php selected($frontend_options['enable_emoji'] ?? 'yes', 'no'); ?>><?php _e('Nein - Emoji-Button ausblenden', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Zeigt einen Button zum Einfügen von Emojis und GIFs in Nachrichten.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <td class="chat-label-column">
                        <label for="frontend_enable_gifs"><?php _e('GIF-Button aktivieren', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="frontend_enable_gifs" name="psource_chat_extensions[frontend][enable_gifs]">
                            <option value="no" <?php selected($frontend_options['enable_gifs'] ?? 'no', 'no'); ?>><?php _e('Nein - kein GIF-Button', 'psource-chat'); ?></option>
                            <option value="yes" <?php selected($frontend_options['enable_gifs'] ?? 'no', 'yes'); ?>><?php _e('Ja - GIF-Button anzeigen', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Zeigt einen Button zum Einfügen von GIFs in Nachrichten.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <td class="chat-label-column">
                        <label for="frontend_enable_uploads"><?php _e('Upload-Button aktivieren', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="frontend_enable_uploads" name="psource_chat_extensions[frontend][enable_uploads]">
                            <option value="no" <?php selected($frontend_options['enable_uploads'] ?? 'no', 'no'); ?>><?php _e('Nein - kein Upload-Button', 'psource-chat'); ?></option>
                            <option value="yes" <?php selected($frontend_options['enable_uploads'] ?? 'no', 'yes'); ?>><?php _e('Ja - Upload-Button anzeigen', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Zeigt einen Button zum Hochladen von Dateien in Nachrichten.', 'psource-chat'); ?></p>
                    </td>
                </tr>

                <tr>
                    <td class="chat-label-column">
                        <label for="frontend_visual_notifications"><?php _e('Visuelle Benachrichtigungen', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="frontend_visual_notifications" name="psource_chat_extensions[frontend][visual_notifications]">
                            <option value="yes" <?php selected($frontend_options['visual_notifications'] ?? 'yes', 'yes'); ?>><?php _e('Ja - Titel blinkt bei neuen Nachrichten', 'psource-chat'); ?></option>
                            <option value="no" <?php selected($frontend_options['visual_notifications'] ?? 'yes', 'no'); ?>><?php _e('Nein - keine visuellen Effekte', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Lässt den Chat-Titel blinken oder pulsieren, wenn neue Nachrichten eingehen.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <td class="chat-label-column">
                        <label for="frontend_auto_open"><?php _e('Automatisch öffnen', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="frontend_auto_open" name="psource_chat_extensions[frontend][auto_open_on_message]">
                            <option value="no" <?php selected($frontend_options['auto_open_on_message'] ?? 'no', 'no'); ?>><?php _e('Nein - bleibt minimiert', 'psource-chat'); ?></option>
                            <option value="yes" <?php selected($frontend_options['auto_open_on_message'] ?? 'no', 'yes'); ?>><?php _e('Ja - öffnet sich bei neuen Nachrichten', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Öffnet den Chat automatisch, wenn eine neue Nachricht eingeht und er minimiert ist.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <td class="chat-label-column">
                        <label for="frontend_show_user_settings"><?php _e('Benutzer-Einstellungen anzeigen', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="frontend_show_user_settings" name="psource_chat_extensions[frontend][show_user_settings]">
                            <option value="yes" <?php selected($frontend_options['show_user_settings'] ?? 'yes', 'yes'); ?>><?php _e('Ja - Zahnrad-Button für Benutzereinstellungen', 'psource-chat'); ?></option>
                            <option value="no" <?php selected($frontend_options['show_user_settings'] ?? 'yes', 'no'); ?>><?php _e('Nein - keine Benutzereinstellungen', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Zeigt einen Zahnrad-Button, über den Benutzer persönliche Chat-Einstellungen vornehmen können.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <td class="chat-label-column">
                        <label for="frontend_show_moderation"><?php _e('Moderations-Tools anzeigen', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="frontend_show_moderation" name="psource_chat_extensions[frontend][show_moderation_tools]">
                            <option value="moderators" <?php selected($frontend_options['show_moderation_tools'] ?? 'moderators', 'moderators'); ?>><?php _e('Nur für Moderatoren', 'psource-chat'); ?></option>
                            <option value="admins" <?php selected($frontend_options['show_moderation_tools'] ?? 'moderators', 'admins'); ?>><?php _e('Nur für Administratoren', 'psource-chat'); ?></option>
                            <option value="no" <?php selected($frontend_options['show_moderation_tools'] ?? 'moderators', 'no'); ?>><?php _e('Nicht anzeigen', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Zeigt Moderations-Tools wie "Chat leeren" und "Chat sperren" für berechtigte Benutzer.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <td class="chat-label-column">
                        <label for="frontend_max_message_length"><?php _e('Maximale Nachrichtenlänge', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="number" id="frontend_max_message_length" name="psource_chat_extensions[frontend][max_message_length]" 
                               value="<?php echo esc_attr($frontend_options['max_message_length'] ?? '500'); ?>" 
                               min="50" max="2000" /> <?php _e('Zeichen', 'psource-chat'); ?>
                        <p class="description"><?php _e('Maximale Anzahl Zeichen pro Nachricht (50-2000)', 'psource-chat'); ?></p>
                    </td>
                </tr>
        </fieldset>
        <?php
    }
    
    /**
     * Render attachments tab for frontend
     */
    private function render_frontend_attachments_tab($frontend_options) {
        ?>
        <?php 
        // Check if Attachments extension is enabled to show attachment options
        $extension_options = get_option('psource_chat_extensions', []);
        $attachments_options = $extension_options['attachments'] ?? [];
        
        if (($attachments_options['enabled'] ?? 'disabled') === 'enabled'): ?>
        <fieldset>
            <legend><?php _e('Attachment-Einstellungen für Frontend Chat', 'psource-chat'); ?></legend>
            <p class="description"><?php _e('Diese Optionen steuern, welche Attachment-Funktionen im Frontend Chat verfügbar sind. Die globalen Attachment-Einstellungen werden in der Attachments-Erweiterung konfiguriert.', 'psource-chat'); ?></p>
            
            <table class="form-table">
                <?php if (($attachments_options['emojis_enabled'] ?? 'yes') === 'yes'): ?>
                <tr>
                    <td class="chat-label-column">
                        <label for="frontend_enable_emoji"><?php _e('Emojis aktivieren', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="frontend_enable_emoji" name="psource_chat_extensions[frontend][enable_emoji]">
                            <option value="yes" <?php selected($frontend_options['enable_emoji'] ?? 'yes', 'yes'); ?>><?php _e('Ja - Emoji-Button anzeigen', 'psource-chat'); ?></option>
                            <option value="no" <?php selected($frontend_options['enable_emoji'] ?? 'yes', 'no'); ?>><?php _e('Nein - keine Emojis', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Zeigt einen Emoji-Button unter dem Chat-Eingabefeld.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                <?php endif; ?>
                
                <?php if (($attachments_options['gifs_enabled'] ?? 'no') === 'yes'): ?>
                <tr>
                    <td class="chat-label-column">
                        <label for="frontend_enable_gifs"><?php _e('GIFs aktivieren', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="frontend_enable_gifs" name="psource_chat_extensions[frontend][enable_gifs]">
                            <option value="no" <?php selected($frontend_options['enable_gifs'] ?? 'no', 'no'); ?>><?php _e('Nein - keine GIFs', 'psource-chat'); ?></option>
                            <option value="yes" <?php selected($frontend_options['enable_gifs'] ?? 'no', 'yes'); ?>><?php _e('Ja - GIF-Button anzeigen', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Zeigt einen GIF-Suchbutton unter dem Chat-Eingabefeld.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                <?php endif; ?>
                
                <?php if (($attachments_options['uploads_enabled'] ?? 'no') === 'yes'): ?>
                <tr>
                    <td class="chat-label-column">
                        <label for="frontend_enable_uploads"><?php _e('Datei-Uploads aktivieren', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="frontend_enable_uploads" name="psource_chat_extensions[frontend][enable_uploads]">
                            <option value="no" <?php selected($frontend_options['enable_uploads'] ?? 'no', 'no'); ?>><?php _e('Nein - keine Datei-Uploads', 'psource-chat'); ?></option>
                            <option value="yes" <?php selected($frontend_options['enable_uploads'] ?? 'no', 'yes'); ?>><?php _e('Ja - Upload-Button anzeigen', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Zeigt einen Datei-Upload-Button unter dem Chat-Eingabefeld.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                <?php endif; ?>
                
                <?php if (($attachments_options['emojis_enabled'] ?? 'yes') === 'no' && 
                         ($attachments_options['gifs_enabled'] ?? 'no') === 'no' && 
                         ($attachments_options['uploads_enabled'] ?? 'no') === 'no'): ?>
                <tr>
                    <td colspan="2" class="chat-value-column">
                        <div class="notice notice-info inline">
                            <p><?php _e('Keine Attachment-Funktionen aktiviert. Aktiviere zunächst Emojis, GIFs oder Uploads in der Attachments-Erweiterung.', 'psource-chat'); ?></p>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
            </table>
        </fieldset>
        <?php else: ?>
        <fieldset>
            <legend><?php _e('Attachment-Funktionen', 'psource-chat'); ?></legend>
            <div class="notice notice-warning inline">
                <p>
                    <?php _e('Attachment-Funktionen sind nicht verfügbar.', 'psource-chat'); ?> 
                    <strong><?php _e('Aktiviere zunächst die Attachments-Erweiterung, um Emojis, GIFs und Datei-Uploads zu verwenden.', 'psource-chat'); ?></strong>
                </p>
            </div>
        </fieldset>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Render appearance tab for frontend
     */
    private function render_frontend_appearance_tab($frontend_options) {
        ?>
        <fieldset class="extension-settings-fieldset">
            <legend><?php _e('Darstellung & Aussehen', 'psource-chat'); ?></legend>
            <p class="description"><?php _e('Gestalte das Aussehen deines Seitenkanten-Chats individuell.', 'psource-chat'); ?></p>
            
            <table class="form-table">
                <tr>
                    <td class="chat-label-column">
                        <label for="frontend_theme"><?php _e('Chat-Theme', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="frontend_theme" name="psource_chat_extensions[frontend][theme]">
                            <option value="default" <?php selected($frontend_options['theme'] ?? 'default', 'default'); ?>><?php _e('Standard (Hell)', 'psource-chat'); ?></option>
                            <option value="dark" <?php selected($frontend_options['theme'] ?? 'default', 'dark'); ?>><?php _e('Dunkel', 'psource-chat'); ?></option>
                            <option value="minimal" <?php selected($frontend_options['theme'] ?? 'default', 'minimal'); ?>><?php _e('Minimal', 'psource-chat'); ?></option>
                            <option value="custom" <?php selected($frontend_options['theme'] ?? 'default', 'custom'); ?>><?php _e('Benutzerdefiniert', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Wähle ein vorgefertigtes Theme oder erstelle ein benutzerdefiniertes Design.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <td class="chat-label-column">
                        <label for="frontend_header_bg_color"><?php _e('Header-Hintergrundfarbe', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="color" id="frontend_header_bg_color" name="psource_chat_extensions[frontend][header_bg_color]" 
                               value="<?php echo esc_attr($frontend_options['header_bg_color'] ?? '#007cba'); ?>" />
                        <p class="description"><?php _e('Hintergrundfarbe der Chat-Kopfzeile.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <td class="chat-label-column">
                        <label for="frontend_header_text_color"><?php _e('Header-Textfarbe', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="color" id="frontend_header_text_color" name="psource_chat_extensions[frontend][header_text_color]" 
                               value="<?php echo esc_attr($frontend_options['header_text_color'] ?? '#ffffff'); ?>" />
                        <p class="description"><?php _e('Textfarbe in der Chat-Kopfzeile.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <td class="chat-label-column">
                        <label for="frontend_chat_bg_color"><?php _e('Chat-Hintergrundfarbe', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="color" id="frontend_chat_bg_color" name="psource_chat_extensions[frontend][chat_bg_color]" 
                               value="<?php echo esc_attr($frontend_options['chat_bg_color'] ?? '#ffffff'); ?>" />
                        <p class="description"><?php _e('Hintergrundfarbe des Chat-Bereichs.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <td class="chat-label-column">
                        <label for="frontend_input_bg_color"><?php _e('Eingabefeld-Hintergrund', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="color" id="frontend_input_bg_color" name="psource_chat_extensions[frontend][input_bg_color]" 
                               value="<?php echo esc_attr($frontend_options['input_bg_color'] ?? '#f8f9fa'); ?>" />
                        <p class="description"><?php _e('Hintergrundfarbe des Nachrichteneingabefelds.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <td class="chat-label-column">
                        <label for="frontend_font_size"><?php _e('Schriftgröße', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="number" id="frontend_font_size" name="psource_chat_extensions[frontend][font_size]" 
                               value="<?php echo esc_attr($frontend_options['font_size'] ?? '14'); ?>" 
                               min="10" max="24" /> px
                        <p class="description"><?php _e('Schriftgröße für Chat-Nachrichten (10-24px).', 'psource-chat'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <td class="chat-label-column">
                        <label for="frontend_border_radius"><?php _e('Ecken-Rundung', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="number" id="frontend_border_radius" name="psource_chat_extensions[frontend][border_radius]" 
                               value="<?php echo esc_attr($frontend_options['border_radius'] ?? '12'); ?>" 
                               min="0" max="25" /> px
                        <p class="description"><?php _e('Rundung der Chat-Fenster-Ecken (0-25px).', 'psource-chat'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <td class="chat-label-column">
                        <label for="frontend_button_style"><?php _e('Button-Stil', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="frontend_button_style" name="psource_chat_extensions[frontend][button_style]">
                            <option value="default" <?php selected($frontend_options['button_style'] ?? 'default', 'default'); ?>><?php _e('Standard', 'psource-chat'); ?></option>
                            <option value="flat" <?php selected($frontend_options['button_style'] ?? 'default', 'flat'); ?>><?php _e('Flach', 'psource-chat'); ?></option>
                            <option value="rounded" <?php selected($frontend_options['button_style'] ?? 'default', 'rounded'); ?>><?php _e('Abgerundet', 'psource-chat'); ?></option>
                            <option value="square" <?php selected($frontend_options['button_style'] ?? 'default', 'square'); ?>><?php _e('Eckig', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Stil der Chat-Buttons (Senden, Emoji, Upload, etc.).', 'psource-chat'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <td class="chat-label-column">
                        <label for="frontend_button_color"><?php _e('Button-Farbe', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="color" id="frontend_button_color" name="psource_chat_extensions[frontend][button_color]" 
                               value="<?php echo esc_attr($frontend_options['button_color'] ?? '#007cba'); ?>" />
                        <p class="description"><?php _e('Farbe für Chat-Buttons.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <td class="chat-label-column">
                        <label for="frontend_custom_css"><?php _e('Benutzerdefiniertes CSS', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <textarea id="frontend_custom_css" name="psource_chat_extensions[frontend][custom_css]" 
                                  rows="6" cols="50" class="large-text code" 
                                  placeholder="/* Eigenes CSS hier eingeben */"><?php echo esc_textarea($frontend_options['custom_css'] ?? ''); ?></textarea>
                        <p class="description"><?php _e('Zusätzliches CSS für erweiterte Anpassungen. Verwende .psource-chat-widget als Basis-Selektor.', 'psource-chat'); ?></p>
                    </td>
                </tr>
            </table>
        </fieldset>
        <?php
    }
    
    /**
     * Render Widgets Extension
     */
    public function render_widgets_extension($ext_id) {
        $options = get_option('psource_chat_extensions', []);
        $widget_options = $options['widgets'] ?? [];
        ?>
        <fieldset class="extension-settings-fieldset">
            <legend><?php _e('Verfügbare Chat Widgets', 'psource-chat'); ?></legend>
            <p class="description"><?php _e('Diese Widgets können in den WordPress Widgets-Bereichen (Design > Widgets) verwendet werden.', 'psource-chat'); ?></p>
            
            <table class="form-table">
                <tr>
                    <td class="chat-label-column">
                        <label for="enable_chat_widget"><?php _e('Haupt Chat Widget', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="enable_chat_widget" name="psource_chat_extensions[widgets][enable_chat_widget]">
                            <option value="enabled" <?php selected($widget_options['enable_chat_widget'] ?? 'enabled', 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                            <option value="disabled" <?php selected($widget_options['enable_chat_widget'] ?? 'enabled', 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Haupt-Chat-Widget für Sidebars', 'psource-chat'); ?></p>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="enable_status_widget"><?php _e('Status Widget', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="enable_status_widget" name="psource_chat_extensions[widgets][enable_status_widget]">
                            <option value="enabled" <?php selected($widget_options['enable_status_widget'] ?? 'enabled', 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                            <option value="disabled" <?php selected($widget_options['enable_status_widget'] ?? 'enabled', 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Ermöglicht Benutzern ihren Chat-Status zu setzen', 'psource-chat'); ?></p>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="enable_friends_widget"><?php _e('Freunde Widget', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="enable_friends_widget" name="psource_chat_extensions[widgets][enable_friends_widget]">
                            <option value="enabled" <?php selected($widget_options['enable_friends_widget'] ?? 'enabled', 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                            <option value="disabled" <?php selected($widget_options['enable_friends_widget'] ?? 'enabled', 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Zeigt Online-Freunde und Chat-Status (benötigt BuddyPress oder PS Freunde Plugin)', 'psource-chat'); ?></p>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="enable_rooms_widget"><?php _e('Räume Widget', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="enable_rooms_widget" name="psource_chat_extensions[widgets][enable_rooms_widget]">
                            <option value="enabled" <?php selected($widget_options['enable_rooms_widget'] ?? 'enabled', 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                            <option value="disabled" <?php selected($widget_options['enable_rooms_widget'] ?? 'enabled', 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Zeigt aktive Chat-Sitzungen der gesamten Website', 'psource-chat'); ?></p>
                    </td>
                </tr>
            </table>
        </fieldset>
        
        <fieldset>
            <legend><?php _e('Widget Standard-Einstellungen', 'psource-chat'); ?></legend>
            <table border="0" cellpadding="4" cellspacing="0">
                <tr>
                    <td class="chat-label-column">
                        <label for="widget_default_height"><?php _e('Standard Widget Höhe', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="text" id="widget_default_height" name="psource_chat_extensions[widgets][default_height]" 
                               value="<?php echo esc_attr($widget_options['default_height'] ?? '300px'); ?>" 
                               placeholder="z.B. 300px"/>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="widget_show_avatars"><?php _e('Avatare in Widgets anzeigen', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="widget_show_avatars" name="psource_chat_extensions[widgets][show_avatars]">
                            <option value="enabled" <?php selected($widget_options['show_avatars'] ?? 'enabled', 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                            <option value="disabled" <?php selected($widget_options['show_avatars'] ?? 'enabled', 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="widget_sound_enabled"><?php _e('Sound in Widgets', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="widget_sound_enabled" name="psource_chat_extensions[widgets][sound_enabled]">
                            <option value="enabled" <?php selected($widget_options['sound_enabled'] ?? 'disabled', 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                            <option value="disabled" <?php selected($widget_options['sound_enabled'] ?? 'disabled', 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                        </select>
                    </td>
                </tr>
            </table>
        </fieldset>
        <?php
    }
    
    /**
     * Render Admin Bar Extension
     */
    public function render_admin_bar_extension($ext_id) {
        $options = get_option('psource_chat_extensions', []);
        $adminbar_options = $options['admin_bar'] ?? [];
        ?>
        <fieldset class="extension-settings-fieldset">
            <legend><?php _e('Admin Bar Chat Integration', 'psource-chat'); ?></legend>
            <table class="form-table">
                <tr>
                    <td class="chat-label-column">
                        <label for="adminbar_enabled"><?php _e('Admin Bar Chat aktivieren', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="adminbar_enabled" name="psource_chat_extensions[admin_bar][enabled]">
                            <option value="enabled" <?php selected($adminbar_options['enabled'] ?? 'disabled', 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                            <option value="disabled" <?php selected($adminbar_options['enabled'] ?? 'disabled', 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Zeigt Chat-Optionen in der WordPress Admin Bar', 'psource-chat'); ?></p>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="adminbar_show_status"><?php _e('Status in Admin Bar', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="adminbar_show_status" name="psource_chat_extensions[admin_bar][show_status]">
                            <option value="enabled" <?php selected($adminbar_options['show_status'] ?? 'enabled', 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                            <option value="disabled" <?php selected($adminbar_options['show_status'] ?? 'enabled', 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Zeigt aktuellen Chat-Status mit Änderungsmöglichkeit', 'psource-chat'); ?></p>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="adminbar_show_friends"><?php _e('Freunde in Admin Bar', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="adminbar_show_friends" name="psource_chat_extensions[admin_bar][show_friends]">
                            <option value="enabled" <?php selected($adminbar_options['show_friends'] ?? 'enabled', 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                            <option value="disabled" <?php selected($adminbar_options['show_friends'] ?? 'enabled', 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Zeigt Online-Freunde in der Admin Bar (benötigt BuddyPress oder PS Freunde Plugin)', 'psource-chat'); ?></p>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="adminbar_positions"><?php _e('Admin Bar Positionen', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <?php
                        $selected_positions = $adminbar_options['positions'] ?? ['frontend', 'admin'];
                        if (!is_array($selected_positions)) {
                            $selected_positions = ['frontend', 'admin'];
                        }
                        ?>
                        <label>
                            <input type="checkbox" name="psource_chat_extensions[admin_bar][positions][]" 
                                   value="frontend" <?php checked(in_array('frontend', $selected_positions)); ?>/>
                            <?php _e('Frontend', 'psource-chat'); ?>
                        </label><br/>
                        <label>
                            <input type="checkbox" name="psource_chat_extensions[admin_bar][positions][]" 
                                   value="admin" <?php checked(in_array('admin', $selected_positions)); ?>/>
                            <?php _e('Admin-Bereich', 'psource-chat'); ?>
                        </label><br/>
                        <p class="description"><?php _e('Wo soll die Admin Bar Chat-Integration angezeigt werden?', 'psource-chat'); ?></p>
                    </td>
                </tr>
            </table>
        </fieldset>
        <?php
    }
    
    /**
     * Render Performance Extension
     */
    public function render_performance_extension($ext_id) {
        $options = get_option('psource_chat_extensions', []);
        $performance_options = $options['performance'] ?? [];
        ?>
        <fieldset class="extension-settings-fieldset">
            <legend><?php _e('Polling und Performance', 'psource-chat'); ?></legend>
            <table class="form-table">
                <tr>
                    <td class="chat-label-column">
                        <label for="polling_interval"><?php _e('Abfrageintervall', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="number" id="polling_interval" name="psource_chat_extensions[performance][polling_interval]" 
                               value="<?php echo esc_attr($performance_options['polling_interval'] ?? '3'); ?>" 
                               min="1" max="60" step="1"/> <?php _e('Sekunden', 'psource-chat'); ?>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="max_messages"><?php _e('Max. Nachrichten pro Abfrage', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="number" id="max_messages" name="psource_chat_extensions[performance][max_messages]" 
                               value="<?php echo esc_attr($performance_options['max_messages'] ?? '50'); ?>" 
                               min="10" max="200" step="10"/>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="cache_enabled"><?php _e('Nachrichten-Cache', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="cache_enabled" name="psource_chat_extensions[performance][cache_enabled]">
                            <option value="enabled" <?php selected($performance_options['cache_enabled'] ?? 'enabled', 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                            <option value="disabled" <?php selected($performance_options['cache_enabled'] ?? 'enabled', 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                        </select>
                    </td>
                </tr>
            </table>
        </fieldset>
        <?php
    }
    
    /**
     * Render Security Extension
     */
    public function render_security_extension($ext_id) {
        $options = get_option('psource_chat_extensions', []);
        $security_options = $options['security'] ?? [];
        ?>
        <fieldset class="extension-settings-fieldset">
            <legend><?php _e('IP-Blockierung', 'psource-chat'); ?></legend>
            <table class="form-table">
                <tr>
                    <td class="chat-label-column">
                        <label for="blocked_ips"><?php _e('Blockierte IP-Adressen', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <textarea id="blocked_ips" name="psource_chat_extensions[security][blocked_ips]" rows="6" cols="50"><?php echo esc_textarea($security_options['blocked_ips'] ?? ''); ?></textarea>
                        <p class="description"><?php _e('Eine IP-Adresse pro Zeile. Wildcards (*) werden unterstützt.', 'psource-chat'); ?></p>
                    </td>
                </tr>
            </table>
        </fieldset>
        
        <fieldset class="extension-settings-fieldset">
            <legend><?php _e('Wort-Filter', 'psource-chat'); ?></legend>
            <table class="form-table">
                <tr>
                    <td class="chat-label-column">
                        <label for="blocked_words"><?php _e('Blockierte Wörter', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <textarea id="blocked_words" name="psource_chat_extensions[security][blocked_words]" rows="6" cols="50"><?php echo esc_textarea($security_options['blocked_words'] ?? ''); ?></textarea>
                        <p class="description"><?php _e('Ein Wort pro Zeile. Diese Wörter werden automatisch blockiert.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="blocked_urls"><?php _e('Blockierte URLs', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <textarea id="blocked_urls" name="psource_chat_extensions[security][blocked_urls]" rows="4" cols="50"><?php echo esc_textarea($security_options['blocked_urls'] ?? ''); ?></textarea>
                        <p class="description"><?php _e('Eine URL oder Domain pro Zeile.', 'psource-chat'); ?></p>
                    </td>
                </tr>
            </table>
        </fieldset>
        <?php
    }
    
    /**
     * Render BuddyPress Extension
     */
    public function render_buddypress_extension($ext_id) {
        $options = get_option('psource_chat_extensions', []);
        $bp_options = $options['buddypress'] ?? [];
        ?>
        <fieldset class="extension-settings-fieldset">
            <legend><?php _e('BuddyPress Chat Integration', 'psource-chat'); ?></legend>
            <table class="form-table">
                <tr>
                    <td class="chat-label-column">
                        <label for="bp_profile_chat"><?php _e('Chat auf Profil-Seiten', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="bp_profile_chat" name="psource_chat_extensions[buddypress][profile_chat]">
                            <option value="enabled" <?php selected($bp_options['profile_chat'] ?? 'disabled', 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                            <option value="disabled" <?php selected($bp_options['profile_chat'] ?? 'disabled', 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Ermöglicht private Chats über BuddyPress Profile', 'psource-chat'); ?></p>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="bp_group_chat"><?php _e('Chat in Gruppen', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="bp_group_chat" name="psource_chat_extensions[buddypress][group_chat]">
                            <option value="enabled" <?php selected($bp_options['group_chat'] ?? 'disabled', 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                            <option value="disabled" <?php selected($bp_options['group_chat'] ?? 'disabled', 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Fügt Chat-Funktionalität zu BuddyPress Gruppen hinzu', 'psource-chat'); ?></p>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="bp_activity_stream"><?php _e('Chat im Activity Stream', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="bp_activity_stream" name="psource_chat_extensions[buddypress][activity_stream]">
                            <option value="enabled" <?php selected($bp_options['activity_stream'] ?? 'disabled', 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                            <option value="disabled" <?php selected($bp_options['activity_stream'] ?? 'disabled', 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Ermöglicht Chat-Diskussionen zu Activity Stream Posts', 'psource-chat'); ?></p>
                    </td>
                </tr>
            </table>
        </fieldset>
        
        <fieldset class="extension-settings-fieldset">
            <legend><?php _e('BuddyPress Freunde Integration', 'psource-chat'); ?></legend>
            <table class="form-table">
                <tr>
                    <td class="chat-label-column">
                        <label for="bp_friends_integration"><?php _e('Freunde-System verwenden', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="bp_friends_integration" name="psource_chat_extensions[buddypress][friends_integration]">
                            <option value="enabled" <?php selected($bp_options['friends_integration'] ?? 'enabled', 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                            <option value="disabled" <?php selected($bp_options['friends_integration'] ?? 'enabled', 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Nutzt BuddyPress Freundschaften für Chat-Berechtigung', 'psource-chat'); ?></p>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="bp_online_friends_widget"><?php _e('Online-Freunde Widget', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="bp_online_friends_widget" name="psource_chat_extensions[buddypress][online_friends_widget]">
                            <option value="enabled" <?php selected($bp_options['online_friends_widget'] ?? 'enabled', 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                            <option value="disabled" <?php selected($bp_options['online_friends_widget'] ?? 'enabled', 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Spezielle Widget-Integration für BuddyPress Freunde', 'psource-chat'); ?></p>
                    </td>
                </tr>
            </table>
        </fieldset>
        
        <fieldset>
            <legend><?php _e('BuddyPress Benachrichtigungen', 'psource-chat'); ?></legend>
            <table border="0" cellpadding="4" cellspacing="0">
                <tr>
                    <td class="chat-label-column">
                        <label for="bp_notifications"><?php _e('BuddyPress Benachrichtigungen', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="bp_notifications" name="psource_chat_extensions[buddypress][notifications]">
                            <option value="enabled" <?php selected($bp_options['notifications'] ?? 'enabled', 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                            <option value="disabled" <?php selected($bp_options['notifications'] ?? 'enabled', 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Sendet BuddyPress Benachrichtigungen für neue Chat-Nachrichten', 'psource-chat'); ?></p>
                    </td>
                </tr>
                <tr>
                    <td class="chat-label-column">
                        <label for="bp_notification_types"><?php _e('Benachrichtigungs-Typen', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <?php
                        $selected_types = $bp_options['notification_types'] ?? ['group_chat', 'private_chat'];
                        if (!is_array($selected_types)) {
                            $selected_types = ['group_chat', 'private_chat'];
                        }
                        ?>
                        <label>
                            <input type="checkbox" name="psource_chat_extensions[buddypress][notification_types][]" 
                                   value="group_chat" <?php checked(in_array('group_chat', $selected_types)); ?>/>
                            <?php _e('Gruppen-Chat', 'psource-chat'); ?>
                        </label><br/>
                        <label>
                            <input type="checkbox" name="psource_chat_extensions[buddypress][notification_types][]" 
                                   value="private_chat" <?php checked(in_array('private_chat', $selected_types)); ?>/>
                            <?php _e('Privater Chat', 'psource-chat'); ?>
                        </label><br/>
                        <label>
                            <input type="checkbox" name="psource_chat_extensions[buddypress][notification_types][]" 
                                   value="friend_invite" <?php checked(in_array('friend_invite', $selected_types)); ?>/>
                            <?php _e('Chat-Einladungen', 'psource-chat'); ?>
                        </label><br/>
                    </td>
                </tr>
            </table>
        </fieldset>
        <?php
    }
    
    /**
     * Render Support Chat Extension
     */
    public function render_support_chat_extension($ext_id) {
        $options = get_option('psource_chat_extensions', []);
        $support_chat_options = $options['support_chat'] ?? [];
        ?>
        <fieldset class="extension-settings-fieldset">
            <legend><?php _e('Support Chat Einstellungen', 'psource-chat'); ?></legend>
            <p class="description"><?php _e('Konfigurieren Sie den Support-Chat für professionelle Kundenbetreuung mit privaten Sessions und Kategorien.', 'psource-chat'); ?></p>
            
            <table class="form-table">
                <tr>
                    <td class="chat-label-column">
                        <label for="support_chat_enabled"><?php _e('Support Chat aktivieren', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="support_chat_enabled" name="psource_chat_extensions[support_chat][enabled]">
                            <option value="enabled" <?php selected($support_chat_options['enabled'] ?? 'disabled', 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                            <option value="disabled" <?php selected($support_chat_options['enabled'] ?? 'disabled', 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Aktiviert den Support Chat für Kundenanfragen', 'psource-chat'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <td class="chat-label-column">
                        <label for="support_chat_button_position"><?php _e('Button Position', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="support_chat_button_position" name="psource_chat_extensions[support_chat][button_position]">
                            <option value="bottom-right" <?php selected($support_chat_options['button_position'] ?? 'bottom-right', 'bottom-right'); ?>><?php _e('Unten Rechts', 'psource-chat'); ?></option>
                            <option value="bottom-left" <?php selected($support_chat_options['button_position'] ?? 'bottom-right', 'bottom-left'); ?>><?php _e('Unten Links', 'psource-chat'); ?></option>
                            <option value="top-right" <?php selected($support_chat_options['button_position'] ?? 'bottom-right', 'top-right'); ?>><?php _e('Oben Rechts', 'psource-chat'); ?></option>
                            <option value="top-left" <?php selected($support_chat_options['button_position'] ?? 'bottom-right', 'top-left'); ?>><?php _e('Oben Links', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Position des Support-Chat-Buttons auf der Website', 'psource-chat'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <td class="chat-label-column">
                        <label for="support_chat_button_text"><?php _e('Button Beschriftung', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="text" id="support_chat_button_text" name="psource_chat_extensions[support_chat][button_text]" 
                               value="<?php echo esc_attr($support_chat_options['button_text'] ?? __('Klick to chat with support', 'psource-chat')); ?>" />
                        <p class="description"><?php _e('Text der auf dem Support-Chat-Button angezeigt wird', 'psource-chat'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <td class="chat-label-column">
                        <label for="support_chat_widget_title"><?php _e('Widget Titel', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="text" id="support_chat_widget_title" name="psource_chat_extensions[support_chat][widget_title]" 
                               value="<?php echo esc_attr($support_chat_options['widget_title'] ?? __('Support Chat', 'psource-chat')); ?>" />
                        <p class="description"><?php _e('Titel des Chat-Widgets', 'psource-chat'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <td class="chat-label-column">
                        <label for="support_chat_allow_anonymous"><?php _e('Anonyme Nutzung', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="support_chat_allow_anonymous" name="psource_chat_extensions[support_chat][allow_anonymous]">
                            <option value="enabled" <?php selected($support_chat_options['allow_anonymous'] ?? 'disabled', 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                            <option value="disabled" <?php selected($support_chat_options['allow_anonymous'] ?? 'disabled', 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Erlaubt nicht-eingeloggten Benutzern den Support-Chat zu nutzen (mit Email & Name)', 'psource-chat'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <td class="chat-label-column">
                        <label for="support_chat_categories"><?php _e('Support Kategorien', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <textarea id="support_chat_categories" name="psource_chat_extensions[support_chat][categories]" rows="4" cols="50"><?php echo esc_textarea($support_chat_options['categories'] ?? ''); ?></textarea>
                        <p class="description">
                            <?php _e('Eine Kategorie pro Zeile. Format: "wert|Anzeigename" oder nur "Kategoriename".', 'psource-chat'); ?><br>
                            <?php _e('Beispiel:', 'psource-chat'); ?><br>
                            <code>technical|Technischer Support<br>billing|Abrechnung<br>general|Allgemeine Fragen</code>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <td class="chat-label-column">
                        <label for="support_chat_agents"><?php _e('Support Agenten', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <?php
                        $support_agents = $support_chat_options['support_agents'] ?? [];
                        $users = get_users(['role__in' => ['administrator', 'editor']]);
                        ?>
                        <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
                            <?php foreach ($users as $user): ?>
                                <label style="display: block; margin-bottom: 5px;">
                                    <input type="checkbox" 
                                           name="psource_chat_extensions[support_chat][support_agents][]" 
                                           value="<?php echo $user->ID; ?>"
                                           <?php checked(in_array($user->ID, $support_agents)); ?>>
                                    <?php echo esc_html($user->display_name . ' (' . $user->user_email . ')'); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="description"><?php _e('Benutzer die als Support-Agenten agieren können. Alle oder spezifische Kategorien-Zuweisungen möglich.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <td class="chat-label-column">
                        <label for="support_chat_privacy"><?php _e('Privatsphäre', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <label>
                            <input type="checkbox" name="psource_chat_extensions[support_chat][private_sessions]" value="1" 
                                   <?php checked($support_chat_options['private_sessions'] ?? 1, 1); ?>>
                            <?php _e('Private Chat-Sessions (andere Benutzer können Chat nicht mitlesen)', 'psource-chat'); ?>
                        </label>
                        <p class="description"><?php _e('Jeder Support-Chat ist eine private Session zwischen User und Support-Agent. Dritte können diese nicht einsehen.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <td class="chat-label-column">
                        <label><?php _e('Support Interface', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <p class="description">
                            <?php _e('Support-Agenten finden eine eigene Admin-Seite unter:', 'psource-chat'); ?> 
                            <strong><?php _e('PS Chat → Support Chat', 'psource-chat'); ?></strong><br>
                            <?php _e('Dort können alle aktiven Sessions verwaltet und beantwortet werden.', 'psource-chat'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </fieldset>
        <?php
    }
    
    /**
     * Render Private Chat extension settings
     */
    public function render_private_chat_extension($extension_id) {
        $extension_options = get_option('psource_chat_extensions', []);
        $private_options = $extension_options['private_chat'] ?? [];
        
        ?>
        <fieldset class="extension-settings-fieldset">
            <legend><?php _e('Privater Chat', 'psource-chat'); ?></legend>
            <p class="description"><?php _e('Die privaten Chats funktionieren ähnlich wie die Chat-Sitzung in der unteren Ecke. Ein privater Chat ist eine Eins-zu-Eins-Chat-Sitzung zwischen zwei Benutzern. Mit den folgenden Einstellungen können Sie die Optionen für Private und deren Auswirkungen auf Benutzer steuern.', 'psource-chat'); ?></p>
            
            <table class="form-table" role="presentation">
                <tr>
                    <td class="chat-label-column">
                        <label for="private_chat_enabled"><?php _e('Private Chats aktivieren', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <label>
                            <input type="checkbox" name="psource_chat_extensions[private_chat][enabled]" id="private_chat_enabled" value="1" 
                                   <?php checked($private_options['enabled'] ?? false, 1); ?>>
                            <?php _e('Private Chat-Funktionalität aktivieren', 'psource-chat'); ?>
                        </label>
                        <p class="description"><?php _e('Ermöglicht Benutzern private Eins-zu-Eins-Chats.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <td class="chat-label-column">
                        <label for="chat_private_reopen_after_exit"><?php _e('Privates Chat-Popup nach dem Verlassen wieder öffnen?', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select id="chat_private_reopen_after_exit" name="psource_chat_extensions[private_chat][private_reopen_after_exit]">
                            <option value="enabled" <?php selected($private_options['private_reopen_after_exit'] ?? 'disabled', 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                            <option value="disabled" <?php selected($private_options['private_reopen_after_exit'] ?? 'disabled', 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Soll das Chat-Fenster automatisch wieder geöffnet werden, wenn der Benutzer zurückkehrt?', 'psource-chat'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <td class="chat-label-column">
                        <label for="private_chat_capability"><?php _e('Mindestberechtigung', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <select name="psource_chat_extensions[private_chat][capability]" id="private_chat_capability">
                            <option value="read" <?php selected($private_options['capability'] ?? 'read', 'read'); ?>><?php _e('Alle Benutzer', 'psource-chat'); ?></option>
                            <option value="edit_posts" <?php selected($private_options['capability'] ?? 'read', 'edit_posts'); ?>><?php _e('Autoren+', 'psource-chat'); ?></option>
                            <option value="moderate_comments" <?php selected($private_options['capability'] ?? 'read', 'moderate_comments'); ?>><?php _e('Moderatoren+', 'psource-chat'); ?></option>
                            <option value="manage_options" <?php selected($private_options['capability'] ?? 'read', 'manage_options'); ?>><?php _e('Administratoren', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Mindestberechtigung zum Verwenden privater Chats.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <td class="chat-label-column">
                        <label for="private_chat_max_participants"><?php _e('Maximale Teilnehmer', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <input type="number" name="psource_chat_extensions[private_chat][max_participants]" id="private_chat_max_participants" 
                               value="<?php echo esc_attr($private_options['max_participants'] ?? 2); ?>" min="2" max="10" class="small-text">
                        <p class="description"><?php _e('Maximale Anzahl Teilnehmer in einem privaten Chat (Standard: 2 für Eins-zu-Eins).', 'psource-chat'); ?></p>
                    </td>
                </tr>
            </table>
        </fieldset>
        <?php
    }
    
    /**
     * Render WYSIWYG Button extension settings
     */
    public function render_wysiwyg_extension($extension_id) {
        global $wp_roles;
        
        $extension_options = get_option('psource_chat_extensions', []);
        $wysiwyg_options = $extension_options['wysiwyg_button'] ?? [];
        
        ?>
        <fieldset class="extension-settings-fieldset">
            <legend><?php _e('WYSIWYG Chat-Schaltfläche Benutzerrollen', 'psource-chat'); ?></legend>
            <p class="description"><?php _e('Wähle mit der Schaltfläche Rollen aus welche die Chat WYSIWYG Schaltfläche, verwenden dürfen. Beachte, dass der Benutzer auch über Bearbeitungsfunktionen für den Beitragstyp verfügen muss.', 'psource-chat'); ?></p>
            
            <table class="form-table" role="presentation">
                <tr>
                    <td class="chat-label-column">
                        <label><?php _e('Benutzerrollen', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <?php 
                        $selected_roles = $wysiwyg_options['tinymce_roles'] ?? ['administrator', 'editor'];
                        foreach ($wp_roles->role_names as $role => $name): ?>
                            <label style="display: block; margin-bottom: 5px;">
                                <input type="checkbox" name="psource_chat_extensions[wysiwyg_button][tinymce_roles][]" 
                                       value="<?php echo esc_attr($role); ?>" 
                                       <?php checked(in_array($role, $selected_roles)); ?>>
                                <?php echo esc_html($name); ?>
                            </label>
                        <?php endforeach; ?>
                        <p class="description"><?php _e('Benutzerrollen die den Chat-Button im WYSIWYG-Editor verwenden können.', 'psource-chat'); ?></p>
                    </td>
                </tr>
            </table>
        </fieldset>
        
        <fieldset class="extension-settings-fieldset">
            <legend><?php _e('WYSIWYG Chat Button Beitragstypen', 'psource-chat'); ?></legend>
            <p class="description"><?php _e('Wähle aus, für welche Beitragstypen die Schaltfläche Chat WYSIWYG verfügbar sein soll.', 'psource-chat'); ?></p>
            
            <table class="form-table" role="presentation">
                <tr>
                    <td class="chat-label-column">
                        <label><?php _e('Beitragstypen', 'psource-chat'); ?></label>
                    </td>
                    <td class="chat-value-column">
                        <?php 
                        $selected_post_types = $wysiwyg_options['tinymce_post_types'] ?? ['post', 'page'];
                        foreach (get_post_types(['show_ui' => true], 'objects') as $post_type => $details): 
                            if ($post_type === 'attachment') continue; ?>
                            <label style="display: block; margin-bottom: 5px;">
                                <input type="checkbox" name="psource_chat_extensions[wysiwyg_button][tinymce_post_types][]" 
                                       value="<?php echo esc_attr($post_type); ?>" 
                                       <?php checked(in_array($post_type, $selected_post_types)); ?>>
                                <?php echo esc_html($details->labels->name); ?>
                            </label>
                        <?php endforeach; ?>
                        <p class="description"><?php _e('Beitragstypen für die der Chat-Button im Editor verfügbar ist.', 'psource-chat'); ?></p>
                    </td>
                </tr>
            </table>
        </fieldset>
        <?php
    }

    /**
     * Render Attachments extension
     */
    public function render_attachments_extension($ext_id) {
        $options = get_option('psource_chat_extensions', []);
        $attachments_options = $options['attachments'] ?? [];
        
        // Merge with defaults
        $defaults = [
            'enabled' => 'disabled',
            'emojis_enabled' => 'yes',
            'emojis_source' => 'builtin',
            'emojis_custom_set' => '',
            'gifs_enabled' => 'no',
            'gifs_api_key' => '',
            'gifs_source' => 'giphy',
            'uploads_enabled' => 'no',
            'uploads_max_size' => '5',
            'uploads_allowed_types' => 'jpg,jpeg,png,gif,pdf,doc,docx',
            'uploads_require_login' => 'yes',
            'attachment_history' => 'yes',
            'moderate_uploads' => 'yes'
        ];
        
        $options = array_merge($defaults, $attachments_options);
        ?>
        <fieldset class="attachments-settings-fieldset">
            <legend><?php _e('Allgemeine Einstellungen', 'psource-chat'); ?></legend>
            
            <div class="attachments-help-box" style="background: #e7f3ff; border: 1px solid #72aee6; border-radius: 4px; padding: 15px; margin-bottom: 20px;">
                <p><strong><?php _e('So funktioniert das Attachments-System:', 'psource-chat'); ?></strong></p>
                <ul style="margin: 10px 0 0 20px;">
                    <li><?php _e('Diese Einstellungen definieren, welche Attachment-Typen global verfügbar sind.', 'psource-chat'); ?></li>
                    <li><?php _e('In den einzelnen Chat-Einstellungen (Frontend, Dashboard, etc.) kann dann für jeden Chat individuell gewählt werden, welche der hier aktivierten Funktionen verwendet werden sollen.', 'psource-chat'); ?></li>
                    <li><?php _e('Beispiel: Wenn hier Emojis und GIFs aktiviert sind, kann im Frontend-Chat nur Emojis aktiviert werden, während im Dashboard-Chat beide verfügbar sind.', 'psource-chat'); ?></li>
                </ul>
            </div>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Attachments aktivieren', 'psource-chat'); ?></th>
                    <td>
                        <select name="psource_chat_extensions[attachments][enabled]" class="regular-text">
                            <option value="disabled" <?php selected($options['enabled'], 'disabled'); ?>><?php _e('Deaktiviert', 'psource-chat'); ?></option>
                            <option value="enabled" <?php selected($options['enabled'], 'enabled'); ?>><?php _e('Aktiviert', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Aktiviert das Attachments-System für alle Chats.', 'psource-chat'); ?></p>
                    </td>
                </tr>
            </table>
        </fieldset>
        
        <fieldset class="attachments-settings-fieldset">
            <legend><?php _e('Emoji-Einstellungen', 'psource-chat'); ?></legend>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Emojis aktivieren', 'psource-chat'); ?></th>
                    <td>
                        <select name="psource_chat_extensions[attachments][emojis_enabled]" class="regular-text">
                            <option value="no" <?php selected($options['emojis_enabled'], 'no'); ?>><?php _e('Nein', 'psource-chat'); ?></option>
                            <option value="yes" <?php selected($options['emojis_enabled'], 'yes'); ?>><?php _e('Ja', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Ermöglicht es Benutzern, Emojis in Nachrichten zu verwenden.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Emoji-Quelle', 'psource-chat'); ?></th>
                    <td>
                        <select name="psource_chat_extensions[attachments][emojis_source]" class="regular-text">
                            <option value="builtin" <?php selected($options['emojis_source'], 'builtin'); ?>><?php _e('Eingebaute Emojis', 'psource-chat'); ?></option>
                            <option value="custom" <?php selected($options['emojis_source'], 'custom'); ?>><?php _e('Benutzerdefiniert', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Wähle die Quelle für die verfügbaren Emojis.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Benutzerdefinierte Emoji-Liste', 'psource-chat'); ?></th>
                    <td>
                        <textarea name="psource_chat_extensions[attachments][emojis_custom_set]" class="large-text" rows="3" placeholder="😀,😃,😄,😁,😆,🤣,😂"><?php echo esc_textarea($options['emojis_custom_set']); ?></textarea>
                        <p class="description"><?php _e('Kommagetrennte Liste von Emojis (nur wenn "Benutzerdefiniert" gewählt ist).', 'psource-chat'); ?></p>
                    </td>
                </tr>
            </table>
        </fieldset>
        
        <fieldset class="attachments-settings-fieldset">
            <legend><?php _e('GIF-Einstellungen', 'psource-chat'); ?></legend>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('GIFs aktivieren', 'psource-chat'); ?></th>
                    <td>
                        <select name="psource_chat_extensions[attachments][gifs_enabled]" class="regular-text">
                            <option value="no" <?php selected($options['gifs_enabled'], 'no'); ?>><?php _e('Nein', 'psource-chat'); ?></option>
                            <option value="yes" <?php selected($options['gifs_enabled'], 'yes'); ?>><?php _e('Ja', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Ermöglicht es Benutzern, GIFs in Nachrichten zu verwenden.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('GIF-Anbieter', 'psource-chat'); ?></th>
                    <td>
                        <select name="psource_chat_extensions[attachments][gifs_source]" class="regular-text">
                            <option value="giphy" <?php selected($options['gifs_source'], 'giphy'); ?>><?php _e('Giphy', 'psource-chat'); ?></option>
                            <option value="tenor" <?php selected($options['gifs_source'], 'tenor'); ?>><?php _e('Tenor', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Wähle den Anbieter für GIF-Suche.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('API-Schlüssel', 'psource-chat'); ?></th>
                    <td>
                        <input type="text" name="psource_chat_extensions[attachments][gifs_api_key]" class="regular-text" value="<?php echo esc_attr($options['gifs_api_key']); ?>" placeholder="Dein Giphy/Tenor API-Schlüssel" />
                        <p class="description">
                            <?php _e('API-Schlüssel für GIF-Anbieter.', 'psource-chat'); ?>
                            <a href="https://developers.giphy.com/" target="_blank"><?php _e('Giphy API', 'psource-chat'); ?></a> | 
                            <a href="https://tenor.com/developer/keyregistration" target="_blank"><?php _e('Tenor API', 'psource-chat'); ?></a>
                        </p>
                    </td>
                </tr>
            </table>
        </fieldset>
        
        <fieldset class="attachments-settings-fieldset">
            <legend><?php _e('Datei-Upload-Einstellungen', 'psource-chat'); ?></legend>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Datei-Uploads aktivieren', 'psource-chat'); ?></th>
                    <td>
                        <select name="psource_chat_extensions[attachments][uploads_enabled]" class="regular-text">
                            <option value="no" <?php selected($options['uploads_enabled'], 'no'); ?>><?php _e('Nein', 'psource-chat'); ?></option>
                            <option value="yes" <?php selected($options['uploads_enabled'], 'yes'); ?>><?php _e('Ja', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Ermöglicht es Benutzern, Dateien hochzuladen.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Maximale Dateigröße (MB)', 'psource-chat'); ?></th>
                    <td>
                        <input type="number" name="psource_chat_extensions[attachments][uploads_max_size]" class="small-text" value="<?php echo esc_attr($options['uploads_max_size']); ?>" min="1" max="100" />
                        <p class="description"><?php _e('Maximale Größe für hochgeladene Dateien in Megabyte.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Erlaubte Dateitypen', 'psource-chat'); ?></th>
                    <td>
                        <input type="text" name="psource_chat_extensions[attachments][uploads_allowed_types]" class="regular-text" value="<?php echo esc_attr($options['uploads_allowed_types']); ?>" placeholder="jpg,png,pdf,doc" />
                        <p class="description"><?php _e('Kommagetrennte Liste von erlaubten Dateierweiterungen.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Anmeldung erforderlich', 'psource-chat'); ?></th>
                    <td>
                        <select name="psource_chat_extensions[attachments][uploads_require_login]" class="regular-text">
                            <option value="no" <?php selected($options['uploads_require_login'], 'no'); ?>><?php _e('Nein', 'psource-chat'); ?></option>
                            <option value="yes" <?php selected($options['uploads_require_login'], 'yes'); ?>><?php _e('Ja', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Ob sich Benutzer anmelden müssen, um Dateien hochzuladen.', 'psource-chat'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Uploads moderieren', 'psource-chat'); ?></th>
                    <td>
                        <select name="psource_chat_extensions[attachments][moderate_uploads]" class="regular-text">
                            <option value="no" <?php selected($options['moderate_uploads'], 'no'); ?>><?php _e('Nein', 'psource-chat'); ?></option>
                            <option value="yes" <?php selected($options['moderate_uploads'], 'yes'); ?>><?php _e('Ja', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Hochgeladene Dateien müssen vor der Anzeige genehmigt werden.', 'psource-chat'); ?></p>
                    </td>
                </tr>
            </table>
        </fieldset>
        
        <fieldset class="attachments-settings-fieldset">
            <legend><?php _e('Erweiterte Optionen', 'psource-chat'); ?></legend>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Attachment-Verlauf speichern', 'psource-chat'); ?></th>
                    <td>
                        <select name="psource_chat_extensions[attachments][attachment_history]" class="regular-text">
                            <option value="no" <?php selected($options['attachment_history'], 'no'); ?>><?php _e('Nein', 'psource-chat'); ?></option>
                            <option value="yes" <?php selected($options['attachment_history'], 'yes'); ?>><?php _e('Ja', 'psource-chat'); ?></option>
                        </select>
                        <p class="description"><?php _e('Speichert eine Liste der verwendeten Attachments für schnellen Zugriff.', 'psource-chat'); ?></p>
                    </td>
                </tr>
            </table>
        </fieldset>
        <?php
    }

    /**
     * Hook for external extensions registration
     */
    public function register_extensions_hook($extensions_manager) {
        // This method is called by the action hook
        // Third-party plugins can use this to register their extensions
    }
}
