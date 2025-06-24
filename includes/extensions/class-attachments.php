<?php
/**
 * Attachments Extension - Modular file and media attachment system
 * 
 * @package PSSource\Chat\Extensions
 */

namespace PSSource\Chat\Extensions;

use PSSource\Chat\Core\Extension_Base;
use PSSource\Chat\Core\Plugin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Attachments extension class
 */
class Attachments extends Extension_Base {
    
    /**
     * Extension ID
     */
    protected $id = 'attachments';
    
    /**
     * Extension title
     */
    protected $title = 'Attachments';
    
    /**
     * Extension description
     */
    protected $description = 'Ermöglicht das Anhängen von Emojis, GIFs und Dateien an Chat-Nachrichten';
    
    /**
     * Extension icon
     */
    protected $icon = 'dashicons-paperclip';
    
    /**
     * Required capability
     */
    protected $capability = 'manage_options';
    
    /**
     * Initialize the extension
     */
    public function init() {
        // Add AJAX handlers
        add_action('wp_ajax_psource_chat_upload_file', [$this, 'handle_file_upload']);
        add_action('wp_ajax_nopriv_psource_chat_upload_file', [$this, 'handle_file_upload']);
        
        // Add frontend assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // Add attachment shortcodes and filters
        add_filter('psource_chat_message_content', [$this, 'process_attachments'], 10, 2);
    }
    
    /**
     * Get default options
     */
    public function get_default_options() {
        return [
            'enabled' => 'disabled',
            'emojis_enabled' => 'yes',
            'emojis_source' => 'builtin', // builtin, giphy, custom
            'emojis_custom_set' => '',
            'gifs_enabled' => 'no',
            'gifs_api_key' => '',
            'gifs_source' => 'giphy', // giphy, tenor
            'uploads_enabled' => 'no',
            'uploads_max_size' => '5', // MB
            'uploads_allowed_types' => 'jpg,jpeg,png,gif,pdf,doc,docx',
            'uploads_require_login' => 'yes',
            'attachment_history' => 'yes', // Store attachment history
            'moderate_uploads' => 'yes' // Require moderation for uploads
        ];
    }
    
    /**
     * Render extension options
     */
    public function render_options() {
        $options = $this->get_options();
        ?>
        <div class="extension-section">
            <h3><?php _e('Allgemeine Einstellungen', 'psource-chat'); ?></h3>
            
            <div class="notice notice-info" style="margin-bottom: 20px;">
                <p><strong><?php _e('So funktioniert das Attachments-System:', 'psource-chat'); ?></strong></p>
                <ul>
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
        </div>
        
        <div class="extension-section">
            <h3><?php _e('Emoji-Einstellungen', 'psource-chat'); ?></h3>
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
        </div>
        
        <div class="extension-section">
            <h3><?php _e('GIF-Einstellungen', 'psource-chat'); ?></h3>
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
        </div>
        
        <div class="extension-section">
            <h3><?php _e('Datei-Upload-Einstellungen', 'psource-chat'); ?></h3>
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
        </div>
        
        <div class="extension-section">
            <h3><?php _e('Erweiterte Optionen', 'psource-chat'); ?></h3>
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
        </div>
        <?php
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (!$this->is_enabled()) {
            return;
        }
        
        wp_enqueue_script(
            'psource-chat-attachments',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/js/attachments.js',
            ['jquery'],
            '1.0.0',
            true
        );
        
        wp_enqueue_style(
            'psource-chat-attachments',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/css/attachments.css',
            [],
            '1.0.0'
        );
        
        // Localize attachment settings
        wp_localize_script('psource-chat-attachments', 'psourceChatAttachments', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('psource_chat_attachments'),
            'emojisEnabled' => $this->get_option('emojis_enabled') === 'yes',
            'gifsEnabled' => $this->get_option('gifs_enabled') === 'yes',
            'uploadsEnabled' => $this->get_option('uploads_enabled') === 'yes',
            'emojiSource' => $this->get_option('emojis_source'),
            'customEmojis' => $this->get_custom_emojis(),
            'maxFileSize' => $this->get_option('uploads_max_size'),
            'allowedTypes' => explode(',', $this->get_option('uploads_allowed_types')),
            'strings' => [
                'selectEmoji' => __('Emoji auswählen', 'psource-chat'),
                'searchGifs' => __('GIFs suchen...', 'psource-chat'),
                'uploadFile' => __('Datei hochladen', 'psource-chat'),
                'fileTooBig' => __('Datei zu groß', 'psource-chat'),
                'fileTypeNotAllowed' => __('Dateityp nicht erlaubt', 'psource-chat'),
                'uploadError' => __('Upload-Fehler', 'psource-chat')
            ]
        ]);
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets() {
        if (!$this->is_enabled()) {
            return;
        }
        
        $this->enqueue_frontend_assets();
    }
    
    /**
     * Get custom emojis array
     */
    private function get_custom_emojis() {
        $custom_set = $this->get_option('emojis_custom_set');
        if (empty($custom_set)) {
            // Default emoji set
            return [
                '😀','😃','😄','😁','😆','😅','🤣','😂','😊','😇','😍','🤩','😘','😗','😚','😛','🤪','😜','😝','🤑','🤗','🤭','🤫','🤔','🤐','🤨','😐','😑','😶','😏',
                '👋','🤚','🖐️','✋','🖖','👌','🤏','✌️','🤞','🤘','🤙','👍','👎','✊','👊','🤝','🙏','💪','👂','👃','🧠','👅','👄'
            ];
        }
        
        return array_map('trim', explode(',', $custom_set));
    }
    
    /**
     * Handle file upload
     */
    public function handle_file_upload() {
        // Security checks
        if (!wp_verify_nonce($_POST['nonce'], 'psource_chat_attachments')) {
            wp_die('Security check failed');
        }
        
        if (!$this->is_enabled() || $this->get_option('uploads_enabled') !== 'yes') {
            wp_die('File uploads are disabled');
        }
        
        if ($this->get_option('uploads_require_login') === 'yes' && !is_user_logged_in()) {
            wp_die('Login required for file uploads');
        }
        
        // Handle upload logic here
        // This would be implemented based on WordPress file upload best practices
        
        wp_die('Upload handler not implemented yet');
    }
    
    /**
     * Process attachments in message content
     */
    public function process_attachments($content, $message_data) {
        if (!$this->is_enabled()) {
            return $content;
        }
        
        // Process emoji shortcodes, file attachments, etc.
        // This would be implemented to handle the various attachment types
        
        return $content;
    }
     /**
     * Get attachment buttons HTML for chat interface
     */
    public function get_attachment_buttons($chat_options = []) {
        if (!$this->is_enabled()) {
            return '';
        }

        $buttons = [];
        
        // Emoji button
        if ($this->get_option('emojis_enabled') === 'yes' && ($chat_options['enable_emoji'] ?? true)) {
            $buttons[] = '<button type="button" class="psource-chat-btn psource-chat-emoji-btn" title="' . esc_attr__('Emoji hinzufügen', 'psource-chat') . '">' .
                '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">' .
                '<path d="M12,2C6.47,2 2,6.47 2,12C2,17.53 6.47,22 12,22A10,10 0 0,0 22,12C22,6.47 17.5,2 12,2M12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20M12,6C14.2,6 16,7.8 16,10C16,12.2 14.2,14 12,14C9.8,14 8,12.2 8,10C8,7.8 9.8,6 12,6M7,19C7.5,18.25 8.75,17.5 10.5,17.5C11.25,17.5 12,17.75 12.75,18C13.5,17.75 14.25,17.5 15,17.5C16.75,17.5 18,18.25 18.5,19H7Z"/>' .
                '</svg></button>';
        }
        
        // GIF button  
        if ($this->get_option('gifs_enabled') === 'yes' && ($chat_options['enable_gifs'] ?? false)) {
            $buttons[] = '<button type="button" class="psource-chat-btn psource-chat-gif-btn" title="' . esc_attr__('GIF hinzufügen', 'psource-chat') . '">' .
                '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">' .
                '<path d="M11.5,9H13V7H11.5V9M12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M7,13H9V11H11V13H13V11H15V9H11V7H9V13H7V13Z"/>' .
                '</svg></button>';
        }
        
        // Upload button
        if ($this->get_option('uploads_enabled') === 'yes' && ($chat_options['enable_uploads'] ?? false)) {
            $buttons[] = '<button type="button" class="psource-chat-btn psource-chat-upload-btn" title="' . esc_attr__('Datei hochladen', 'psource-chat') . '">' .
                '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">' .
                '<path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>' .
                '</svg></button>' .
                '<input type="file" class="psource-chat-upload-input" style="display: none;" accept="' . esc_attr($this->get_allowed_file_types()) . '" />';
        }

        if (empty($buttons)) {
            return '';
        }

        return implode(' ', $buttons);
    }
    
    /**
     * Static helper to get attachment buttons from any context
     */
    public static function render_attachment_buttons($chat_options = []) {
        $plugin = Plugin::get_instance();
        $attachments_ext = $plugin->get_extension('attachments');
        
        if (!$attachments_ext) {
            return '';
        }
        
        return $attachments_ext->get_attachment_buttons($chat_options);
    }
    
    /**
     * Get allowed file types for uploads
     */
    private function get_allowed_file_types() {
        $allowed_types = $this->get_option('uploads_allowed_types');
        if (empty($allowed_types)) {
            return 'image/*,.pdf,.doc,.docx';
        }
        
        // Convert comma-separated extensions to file type accepts
        $types = array_map('trim', explode(',', $allowed_types));
        $accept_types = [];
        
        foreach ($types as $type) {
            if (in_array($type, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $accept_types[] = 'image/*';
            } else {
                $accept_types[] = '.' . ltrim($type, '.');
            }
        }
        
        return implode(',', array_unique($accept_types));
    }
}
