<?php
/**
 * Chat Renderer
 * 
 * @package PSSource\Chat\Frontend
 */

namespace PSSource\Chat\Frontend;

use PSSource\Chat\Core\Plugin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles rendering of chat interfaces
 */
class Chat_Renderer {
    
    /**
     * Render footer chat - Original PS Chat Design
     */
    public function render_footer_chat() {
        // Get extension options
        $extension_options = get_option('psource_chat_extensions', []);
        $frontend_options = $extension_options['frontend'] ?? [];
        
        // Chat configuration
        $chat_id = 'psource-chat-seitenkanten';
        $position = $frontend_options['position'] ?? 'bottom-right';
        $width = $frontend_options['width'] ?? 400;
        $height = $frontend_options['height'] ?? 500;
        $title = $frontend_options['title'] ?? __('PS Chat', 'psource-chat');
        $initial_state = $frontend_options['initial_state'] ?? 'minimized';
        $allow_guest_chat = ($frontend_options['allow_guest_chat'] ?? 'no') === 'yes';
        $enable_emoji = ($frontend_options['enable_emoji'] ?? 'yes') === 'yes';
        $enable_sound = ($frontend_options['enable_sound'] ?? 'yes') === 'yes';
        $visual_notifications = ($frontend_options['visual_notifications'] ?? 'yes') === 'yes';
        $auto_open_on_message = ($frontend_options['auto_open_on_message'] ?? 'no') === 'yes';
        $show_user_settings = ($frontend_options['show_user_settings'] ?? 'yes') === 'yes';
        $show_moderation_tools = $frontend_options['show_moderation_tools'] ?? 'moderators';
        $max_message_length = (int)($frontend_options['max_message_length'] ?? 500);
        
        // Check if current user can see moderation tools
        $can_moderate = false;
        if ($show_moderation_tools === 'admins' && current_user_can('administrator')) {
            $can_moderate = true;
        } elseif ($show_moderation_tools === 'moderators' && (current_user_can('administrator') || current_user_can('moderate_comments'))) {
            $can_moderate = true;
        }
        
        // CSS classes
        $container_classes = [
            'psource-chat-container',
            'psource-chat-' . $position
        ];
        
        if ($initial_state === 'minimized') {
            $container_classes[] = 'minimized';
        }
        
        // CRITICAL: Output buffering to ensure clean output and then use JavaScript to position correctly
        ob_start();
        ?>
        <div id="<?php echo esc_attr($chat_id); ?>" 
             class="<?php echo esc_attr(implode(' ', $container_classes)); ?>" 
             data-ps-chat-positioned="true"
             style="position: fixed; z-index: 2147483647; width: <?php echo esc_attr($width); ?>px; max-height: <?php echo esc_attr($height); ?>px; 
                    <?php 
                    // Set position based on configuration
                    switch($position) {
                        case 'bottom-right':
                            echo 'bottom: 20px; right: 20px; top: auto; left: auto;';
                            break;
                        case 'bottom-left':
                            echo 'bottom: 20px; left: 20px; top: auto; right: auto;';
                            break;
                        case 'top-right':
                            echo 'top: 20px; right: 20px; bottom: auto; left: auto;';
                            break;
                        case 'top-left':
                            echo 'top: 20px; left: 20px; bottom: auto; right: auto;';
                            break;
                        default:
                            echo 'bottom: 20px; right: 20px; top: auto; left: auto;';
                    }
                    ?>">
            
            <!-- Chat Header -->
            <div class="psource-chat-header">
                <h3 class="psource-chat-title">
                    <?php echo esc_html($title); ?>
                    <span class="psource-chat-notification-indicator" style="display: none;">●</span>
                </h3>
                <div class="psource-chat-controls">
                    <?php if ($show_user_settings): ?>
                    <button class="psource-chat-btn psource-chat-settings-btn" 
                            title="<?php esc_attr_e('Einstellungen', 'psource-chat'); ?>">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12,15.5A3.5,3.5 0 0,1 8.5,12A3.5,3.5 0 0,1 12,8.5A3.5,3.5 0 0,1 15.5,12A3.5,3.5 0 0,1 12,15.5M19.43,12.97C19.47,12.65 19.5,12.33 19.5,12C19.5,11.67 19.47,11.34 19.43,11.03L21.54,9.37C21.73,9.22 21.78,8.95 21.66,8.73L19.66,5.27C19.54,5.05 19.27,4.96 19.05,5.05L16.56,6.05C16.04,5.66 15.5,5.32 14.87,5.07L14.5,2.42C14.46,2.18 14.25,2 14,2H10C9.75,2 9.54,2.18 9.5,2.42L9.13,5.07C8.5,5.32 7.96,5.66 7.44,6.05L4.95,5.05C4.73,4.96 4.46,5.05 4.34,5.27L2.34,8.73C2.22,8.95 2.27,9.22 2.46,9.37L4.57,11.03C4.53,11.34 4.5,11.67 4.5,12C4.5,12.33 4.53,12.65 4.57,12.97L2.46,14.63C2.27,14.78 2.22,15.05 2.34,15.27L4.34,18.73C4.46,18.95 4.73,19.03 4.95,18.95L7.44,17.94C7.96,18.34 8.5,18.68 9.13,18.93L9.5,21.58C9.54,21.82 9.75,22 10,22H14C14.25,22 14.46,21.82 14.5,21.58L14.87,18.93C15.5,18.68 16.04,18.34 16.56,17.94L19.05,18.95C19.27,19.03 19.54,18.95 19.66,18.73L21.66,15.27C21.78,15.05 21.73,14.78 21.54,14.63L19.43,12.97Z"/>
                        </svg>
                    </button>
                    <?php endif; ?>
                    
                    <?php if ($can_moderate): ?>
                    <button class="psource-chat-btn psource-chat-moderate-btn" 
                            title="<?php esc_attr_e('Moderation', 'psource-chat'); ?>">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12,1L3,5V11C3,16.55 6.84,21.74 12,23C17.16,21.74 21,16.55 21,11V5L12,1M12,7C13.4,7 14.8,8.6 14.8,10V11H16V16H8V11H9.2V10C9.2,8.6 10.6,7 12,7M12,8.2C11.2,8.2 10.4,8.8 10.4,10V11H13.6V10C13.6,8.8 12.8,8.2 12,8.2Z"/>
                        </svg>
                    </button>
                    <?php endif; ?>
                    
                    <button class="psource-chat-btn psource-chat-minimize" 
                            title="<?php esc_attr_e('Minimieren', 'psource-chat'); ?>">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="display: block;">
                            <path d="M19 13H5v-2h14v2z" fill="currentColor"/>
                        </svg>
                    </button>
                    <button class="psource-chat-btn psource-chat-close" 
                            title="<?php esc_attr_e('Schließen', 'psource-chat'); ?>">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="display: block;">
                            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" fill="currentColor"/>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Online Users List -->
            <div class="psource-chat-users">
                <div class="psource-chat-users-title">
                    <?php _e('Online Benutzer', 'psource-chat'); ?>
                    <span class="psource-chat-user-count">(0)</span>
                </div>
                <div class="psource-chat-user-list">
                    <span class="no-users"><?php _e('Keine Benutzer online', 'psource-chat'); ?></span>
                </div>
            </div>
            
            <!-- Messages Area -->
            <div class="psource-chat-messages" style="height: <?php echo esc_attr($height - 250); ?>px;">
                <div class="psource-chat-welcome">
                    <h4><?php _e('Willkommen im PS Chat!', 'psource-chat'); ?></h4>
                    <p><?php _e('Starte eine Unterhaltung, indem Du eine Nachricht unten eingibst.', 'psource-chat'); ?></p>
                </div>
            </div>
            
            <!-- Typing Indicator -->
            <div class="psource-chat-typing"></div>
            
            <!-- Input Area -->
            <div class="psource-chat-input-area">
                <?php if (!is_user_logged_in() && $allow_guest_chat): ?>
                    <div class="psource-chat-guest-info">
                        <input type="text" 
                               class="psource-chat-guest-name" 
                               placeholder="<?php esc_attr_e('Dein Name', 'psource-chat'); ?>" 
                               maxlength="50" 
                               value="<?php echo esc_attr($_COOKIE['psource_chat_guest_name'] ?? ''); ?>" />
                    </div>
                <?php endif; ?>
                
                <div class="psource-chat-input-container">
                    <textarea class="psource-chat-input" 
                              placeholder="<?php esc_attr_e('Nachricht eingeben...', 'psource-chat'); ?>" 
                              rows="1"
                              maxlength="<?php echo esc_attr($max_message_length); ?>"></textarea>
                    
                    <?php if ($enable_emoji): ?>
                    <button class="psource-chat-btn psource-chat-emoji-btn" 
                            type="button"
                            title="<?php esc_attr_e('Emoji hinzufügen', 'psource-chat'); ?>">
                        😊
                    </button>
                    <?php endif; ?>
                    
                    <button class="psource-chat-send-btn" 
                            type="button"
                            title="<?php esc_attr_e('Nachricht senden', 'psource-chat'); ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                        </svg>
                    </button>
                </div>
                
                <div class="psource-chat-message-counter">
                    <span class="current-length">0</span>/<span class="max-length"><?php echo esc_html($max_message_length); ?></span>
                </div>
            </div>
        </div>
        
        <?php if ($enable_emoji): ?>
        <!-- Emoji Picker -->
        <div class="psource-chat-emoji-picker" style="display: none;">
            <?php $this->render_emoji_picker(); ?>
        </div>
        <?php endif; ?>
        
        <!-- Chat Configuration for JavaScript -->
        <script type="text/javascript">
        window.psourceChatConfig = {
            container_id: '<?php echo esc_js($chat_id); ?>',
            position: '<?php echo esc_js($position); ?>',
            width: <?php echo esc_js($width); ?>,
            height: <?php echo esc_js($height); ?>,
            title: '<?php echo esc_js($title); ?>',
            initial_state: '<?php echo esc_js($initial_state); ?>',
            enable_emoji: <?php echo $enable_emoji ? 'true' : 'false'; ?>,
            enable_sound: <?php echo $enable_sound ? 'true' : 'false'; ?>,
            visual_notifications: <?php echo $visual_notifications ? 'true' : 'false'; ?>,
            auto_open_on_message: <?php echo $auto_open_on_message ? 'true' : 'false'; ?>,
            show_user_settings: <?php echo $show_user_settings ? 'true' : 'false'; ?>,
            show_moderation_tools: <?php echo $can_moderate ? 'true' : 'false'; ?>,
            allow_guest_chat: <?php echo $allow_guest_chat ? 'true' : 'false'; ?>,
            max_message_length: <?php echo esc_js($max_message_length); ?>,
            ajax_url: '<?php echo esc_js(admin_url('admin-ajax.php')); ?>',
            nonce: '<?php echo esc_js(wp_create_nonce('psource_chat_nonce')); ?>',
            user_id: <?php echo esc_js(get_current_user_id()); ?>,
            update_interval: <?php echo esc_js($frontend_options['update_interval'] ?? 3000); ?>
        };
        
        // Global settings for backward compatibility
        window.psource_chat_settings = {
            plugin_url: '<?php echo esc_js(plugin_dir_url(dirname(dirname(__DIR__)))); ?>',
            ajax_url: '<?php echo esc_js(admin_url('admin-ajax.php')); ?>',
            nonce: '<?php echo esc_js(wp_create_nonce('psource_chat_nonce')); ?>',
            user_id: <?php echo esc_js(get_current_user_id()); ?>
        };
        </script>
        <?php
        
        // Get the buffered content and output it with a JavaScript relocator
        $chat_html = ob_get_clean();
        
        // Use inline JavaScript to ensure the chat is positioned correctly
        // This approach bypasses theme interference by using DOM manipulation
        ?>
        <script type="text/javascript">
        // PS Chat Emergency Positioning Script
        (function() {
            'use strict';
            
            // Create a temporary container for our HTML
            var tempDiv = document.createElement('div');
            tempDiv.innerHTML = <?php echo json_encode($chat_html); ?>;
            
            var chatElement = tempDiv.firstElementChild;
            
            if (chatElement) {
                // Ensure it's positioned correctly before adding to DOM
                chatElement.style.position = 'fixed';
                chatElement.style.zIndex = '2147483647';
                chatElement.style.bottom = '20px';
                chatElement.style.right = '20px';
                
                // Add directly to body to bypass theme container issues
                document.body.appendChild(chatElement);
                
                console.log('PS Chat: Emergency positioning successful - chat added directly to body');
            } else {
                console.error('PS Chat: Failed to create chat element');
            }
        })();
        </script>
        <?php
    }
    
    /**
     * Render admin chat - Full featured admin chat
     */
    public function render_admin_chat() {
        // Get extension options
        $extension_options = get_option('psource_chat_extensions', []);
        $frontend_options = $extension_options['frontend'] ?? [];
        
        // Chat configuration for admin
        $chat_id = 'psource-chat-admin';
        $position = $frontend_options['admin_position'] ?? 'bottom-right';
        $width = 350; // Slightly smaller for admin
        $height = 450;
        $title = $frontend_options['admin_title'] ?? __('PS Chat (Admin)', 'psource-chat');
        $initial_state = $frontend_options['admin_initial_state'] ?? 'minimized';
        $enable_emoji = ($frontend_options['enable_emoji'] ?? 'yes') === 'yes';
        $enable_sound = ($frontend_options['enable_sound'] ?? 'yes') === 'yes';
        $max_message_length = (int)($frontend_options['max_message_length'] ?? 500);
        
        // CSS classes
        $container_classes = [
            'psource-chat-container',
            'psource-chat-admin',
            'psource-chat-' . $position
        ];
        
        if ($initial_state === 'minimized') {
            $container_classes[] = 'minimized';
        }
        
        ?>
        <div id="<?php echo esc_attr($chat_id); ?>" 
             class="<?php echo esc_attr(implode(' ', $container_classes)); ?>" 
             style="width: <?php echo esc_attr($width); ?>px; max-height: <?php echo esc_attr($height); ?>px;">
            
            <!-- Chat Header -->
            <div class="psource-chat-header">
                <h3 class="psource-chat-title">
                    <?php echo esc_html($title); ?>
                    <span class="admin-badge"><?php _e('Admin', 'psource-chat'); ?></span>
                </h3>
                <div class="psource-chat-controls">
                    <button class="psource-chat-btn psource-chat-minimize" 
                            title="<?php esc_attr_e('Minimieren', 'psource-chat'); ?>">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 13H5v-2h14v2z"/>
                        </svg>
                    </button>
                    <button class="psource-chat-btn psource-chat-close" 
                            title="<?php esc_attr_e('Schließen', 'psource-chat'); ?>">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Online Users List -->
            <div class="psource-chat-users">
                <div class="psource-chat-users-title">
                    <?php _e('Online Benutzer', 'psource-chat'); ?>
                    <span class="psource-chat-user-count">(0)</span>
                </div>
                <div class="psource-chat-user-list">
                    <span class="no-users"><?php _e('Keine Benutzer online', 'psource-chat'); ?></span>
                </div>
            </div>
            
            <!-- Messages Area -->
            <div class="psource-chat-messages" style="height: <?php echo esc_attr($height - 250); ?>px;">
                <div class="psource-chat-welcome">
                    <h4><?php _e('Admin PS Chat', 'psource-chat'); ?></h4>
                    <p><?php _e('Hier können Sie mit Benutzern und anderen Administratoren chatten.', 'psource-chat'); ?></p>
                </div>
            </div>
            
            <!-- Typing Indicator -->
            <div class="psource-chat-typing"></div>
            
            <!-- Input Area -->
            <div class="psource-chat-input-area">
                <div class="psource-chat-input-container">
                    <textarea class="psource-chat-input" 
                              placeholder="<?php esc_attr_e('Admin-Nachricht eingeben...', 'psource-chat'); ?>" 
                              rows="1"
                              maxlength="<?php echo esc_attr($max_message_length); ?>"></textarea>
                    
                    <?php if ($enable_emoji): ?>
                    <button class="psource-chat-btn psource-chat-emoji-btn" 
                            type="button"
                            title="<?php esc_attr_e('Emoji hinzufügen', 'psource-chat'); ?>">
                        😊
                    </button>
                    <?php endif; ?>
                    
                    <button class="psource-chat-send-btn" 
                            type="button"
                            title="<?php esc_attr_e('Nachricht senden', 'psource-chat'); ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                        </svg>
                    </button>
                </div>
                
                <div class="psource-chat-message-counter">
                    <span class="current-length">0</span>/<span class="max-length"><?php echo esc_html($max_message_length); ?></span>
                </div>
            </div>
        </div>
        
        <?php if ($enable_emoji): ?>
        <!-- Emoji Picker -->
        <div class="psource-chat-emoji-picker" style="display: none;">
            <?php $this->render_emoji_picker(); ?>
        </div>
        <?php endif; ?>
        
        <!-- Admin Chat Configuration for JavaScript -->
        <script type="text/javascript">
        window.psourceChatConfig = {
            container_id: '<?php echo esc_js($chat_id); ?>',
            position: '<?php echo esc_js($position); ?>',
            width: <?php echo esc_js($width); ?>,
            height: <?php echo esc_js($height); ?>,
            title: '<?php echo esc_js($title); ?>',
            initial_state: '<?php echo esc_js($initial_state); ?>',
            enable_emoji: <?php echo $enable_emoji ? 'true' : 'false'; ?>,
            enable_sound: <?php echo $enable_sound ? 'true' : 'false'; ?>,
            allow_guest_chat: false,
            max_message_length: <?php echo esc_js($max_message_length); ?>,
            ajax_url: '<?php echo esc_js(admin_url('admin-ajax.php')); ?>',
            nonce: '<?php echo esc_js(wp_create_nonce('psource_chat_nonce')); ?>',
            user_id: <?php echo esc_js(get_current_user_id()); ?>,
            is_admin: true,
            update_interval: <?php echo esc_js($frontend_options['update_interval'] ?? 3000); ?>
        };
        
        // Global settings for backward compatibility
        window.psource_chat_settings = {
            plugin_url: '<?php echo esc_js(plugin_dir_url(dirname(dirname(__DIR__)))); ?>',
            ajax_url: '<?php echo esc_js(admin_url('admin-ajax.php')); ?>',
            nonce: '<?php echo esc_js(wp_create_nonce('psource_chat_nonce')); ?>',
            user_id: <?php echo esc_js(get_current_user_id()); ?>
        };
        </script>
        
        <style>
        /* Admin-specific styles */
        .psource-chat-container.psource-chat-admin .psource-chat-header {
            background: linear-gradient(135deg, #0073aa 0%, #005177 100%);
        }
        
        .psource-chat-container.psource-chat-admin .admin-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: normal;
            margin-left: 8px;
        }
        
        /* Make sure admin chat appears above WordPress admin elements */
        .psource-chat-container.psource-chat-admin {
            z-index: 999999 !important;
        }
        </style>
        <?php
    }
    
    /**
     * Render emoji picker
     */
    private function render_emoji_picker() {
        $emoji_categories = [
            'smileys' => [
                'label' => __('Smileys & Emotion', 'psource-chat'),
                'emojis' => ['😀', '😃', '😄', '😁', '😆', '😅', '😂', '🤣', '😊', '😇', '🙂', '🙃', '😉', '😌', '😍', '🥰', '😘', '😗', '😙', '😚', '😋', '😛', '😝', '😜', '🤪', '🤨', '🧐', '🤓', '😎', '🤩']
            ],
            'gestures' => [
                'label' => __('People & Body', 'psource-chat'),
                'emojis' => ['�', '�', '�', '🤏', '✌️', '🤞', '🤟', '🤘', '🤙', '�', '�', '�', '�', '☝️', '�', '🤚', '🖐️', '✋', '🖖', '👏', '🤲', '🙏', '💪', '🦵', '🦶']
            ],
            'nature' => [
                'label' => __('Animals & Nature', 'psource-chat'),
                'emojis' => ['�', '�', '�', '🐹', '�', '🦊', '�', '�', '�', '�', '�', '🐮', '🐷', '🐽', '🐸', '🐵', '🙈', '�', '🙊', '🐒', '�', '�', '�', '�', '�']
            ],
            'food' => [
                'label' => __('Food & Drink', 'psource-chat'),
                'emojis' => ['🍎', '🍊', '🍋', '🍌', '🍉', '🍇', '🍓', '�', '🍈', '🍒', '🍑', '�', '🍍', '�', '�', '🍅', '🍆', '🥑', '🥦', '🥬', '🥒', '🌶️', '🫑', '🌽', '🥕']
            ],
            'activity' => [
                'label' => __('Activities', 'psource-chat'),
                'emojis' => ['⚽', '🏀', '🏈', '⚾', '�', '🎾', '🏐', '🏉', '�', '🎱', '🪀', '🏓', '🏸', '🏒', '🏑', '🥍', '🏏', '🪃', '🥅', '⛳', '�', '🏹', '🎣', '�', '�']
            ],
            'objects' => [
                'label' => __('Objects', 'psource-chat'),
                'emojis' => ['�', '�', '🎁', '🎉', '🎊', '🎈', '🎀', '🎂', '🎯', '🎮', '�️', '🎰', '🎲', '�', '🃏', '🎴', '🎭', '�️', '🎨', '🧵', '🪡', '🧶', '🪢', '�', '🕶️']
            ]
        ];
        
        echo '<div class="emoji-picker-tabs">';
        foreach ($emoji_categories as $category_id => $category) {
            echo '<button class="emoji-tab" data-category="' . esc_attr($category_id) . '">' . 
                 esc_html($category['label']) . '</button>';
        }
        echo '<button class="emoji-tab gif-tab" data-category="gifs">' . __('GIFs', 'psource-chat') . '</button>';
        echo '</div>';
        
        echo '<div class="emoji-picker-content">';
        
        foreach ($emoji_categories as $category_id => $category) {
            echo '<div class="emoji-category" data-category="' . esc_attr($category_id) . '">';
            foreach ($category['emojis'] as $emoji) {
                echo '<button class="emoji-btn" data-emoji="' . esc_attr($emoji) . '">' . esc_html($emoji) . '</button>';
            }
            echo '</div>';
        }
        
        // GIF section
        echo '<div class="emoji-category gif-category" data-category="gifs">';
        echo '<div class="gif-search">';
        echo '<input type="text" class="gif-search-input" placeholder="' . esc_attr__('GIF suchen...', 'psource-chat') . '">';
        echo '<button class="gif-search-btn">' . __('Suchen', 'psource-chat') . '</button>';
        echo '</div>';
        echo '<div class="gif-results">';
        echo '<div class="gif-loading" style="display: none;">' . __('GIFs werden geladen...', 'psource-chat') . '</div>';
        echo '<div class="gif-grid"></div>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
    }
    
    /**
     * Get JavaScript settings
     */
    private function get_js_settings() {
        $plugin = Plugin::get_instance();
        $options = $plugin->get_options();
        $current_user = wp_get_current_user();
        
        return [
            'update_interval' => 3000,
            'typing_timeout' => 3000,
            'max_message_length' => $options['max_message_length'] ?? 500,
            'enable_sound' => $options['enable_sound'] ?? true,
            'enable_emoji' => $options['enable_emoji'] ?? true,
            'allow_guest_chat' => $options['allow_guest_chat'] ?? false,
            'user_id' => get_current_user_id(),
            'user_name' => $current_user->display_name ?: $current_user->user_login,
            'user_avatar' => get_avatar_url(get_current_user_id(), ['size' => 32]),
            'is_admin' => current_user_can('moderate_chat'),
            'strings' => [
                'send_message' => __('Send Message', 'psource-chat'),
                'typing' => __('is typing...', 'psource-chat'),
                'online' => __('Online', 'psource-chat'),
                'offline' => __('Offline', 'psource-chat'),
                'loading' => __('Loading...', 'psource-chat'),
                'error_connection' => __('Connection error. Please try again.', 'psource-chat'),
                'error_message_too_long' => sprintf(__('Message too long. Maximum %d characters allowed.', 'psource-chat'), $options['max_message_length'] ?? 500),
                'confirm_leave' => __('Are you sure you want to leave the chat?', 'psource-chat'),
                'guest_name_required' => __('Please enter your name to join the chat.', 'psource-chat')
            ]
        ];
    }
    
    /**
     * Render shortcode chat
     */
    public function render_shortcode_chat($atts) {
        $atts = shortcode_atts([
            'session_type' => 'page',
            'session_id' => '',
            'width' => '100%',
            'height' => '400px',
            'theme' => 'default'
        ], $atts);
        
        $chat_id = 'psource-chat-shortcode-' . uniqid();
        $session_id = $atts['session_id'] ?: 'page-' . get_the_ID();
        
        ob_start();
        ?>
        <div id="<?php echo esc_attr($chat_id); ?>" 
             class="psource-chat-shortcode psource-chat-theme-<?php echo esc_attr($atts['theme']); ?>"
             style="width: <?php echo esc_attr($atts['width']); ?>; height: <?php echo esc_attr($atts['height']); ?>;"
             data-session-type="<?php echo esc_attr($atts['session_type']); ?>"
             data-session-id="<?php echo esc_attr($session_id); ?>">
            
            <div class="psource-chat-shortcode-header">
                <h4><?php _e('Chat', 'psource-chat'); ?></h4>
                <div class="psource-chat-user-count">
                    <span class="count">0</span> <?php _e('online', 'psource-chat'); ?>
                </div>
            </div>
            
            <div class="psource-chat-shortcode-messages">
                <div class="psource-chat-loading">
                    <div class="psource-chat-spinner"></div>
                    <p><?php _e('Loading chat...', 'psource-chat'); ?></p>
                </div>
            </div>
            
            <div class="psource-chat-shortcode-input">
                <?php if (!is_user_logged_in()): ?>
                    <div class="psource-chat-login-prompt">
                        <p><?php _e('Please log in to participate in the chat.', 'psource-chat'); ?></p>
                        <a href="<?php echo wp_login_url(get_permalink()); ?>" class="button button-primary">
                            <?php _e('Log In', 'psource-chat'); ?>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="psource-chat-input-container">
                        <textarea class="psource-chat-input" 
                                  placeholder="<?php esc_attr_e('Type your message...', 'psource-chat'); ?>"
                                  rows="2"></textarea>
                        <button class="psource-chat-send-btn">
                            <?php _e('Send', 'psource-chat'); ?>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <script>
        // Initialize shortcode chat
        if (typeof psource_chat !== 'undefined') {
            psource_chat.shortcode_chats = psource_chat.shortcode_chats || [];
            psource_chat.shortcode_chats.push({
                container_id: '<?php echo esc_js($chat_id); ?>',
                session_type: '<?php echo esc_js($atts['session_type']); ?>',
                session_id: '<?php echo esc_js($session_id); ?>'
            });
        }
        </script>
        
        <style>
        .psource-chat-shortcode {
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
            display: flex;
            flex-direction: column;
        }
        
        .psource-chat-shortcode-header {
            background: #f8f9fa;
            padding: 12px 16px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .psource-chat-shortcode-header h4 {
            margin: 0;
            font-size: 16px;
        }
        
        .psource-chat-user-count {
            font-size: 14px;
            color: #666;
        }
        
        .psource-chat-shortcode-messages {
            flex: 1;
            padding: 16px;
            overflow-y: auto;
            min-height: 200px;
        }
        
        .psource-chat-shortcode-input {
            border-top: 1px solid #ddd;
            padding: 12px;
        }
        
        .psource-chat-login-prompt {
            text-align: center;
            padding: 20px;
        }
        
        .psource-chat-login-prompt p {
            margin-bottom: 15px;
            color: #666;
        }
        </style>
        <?php
        
        return ob_get_clean();
    }
}
