<?php
/**
 * Settings Page Handler
 * 
 * @package PSSource\Chat\Admin
 */

namespace PSSource\Chat\Admin;

use PSSource\Chat\Core\Plugin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles the settings page in admin
 */
class Settings_Page {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('psource_chat_settings', 'psource_chat_options', [
            'sanitize_callback' => [$this, 'sanitize_options']
        ]);
        
        // General Settings Section
        add_settings_section(
            'psource_chat_general',
            __('General Settings', 'psource-chat'),
            [$this, 'general_section_callback'],
            'psource_chat_settings'
        );
        
        // Chat Behavior Section
        add_settings_section(
            'psource_chat_behavior',
            __('Chat Behavior', 'psource-chat'),
            [$this, 'behavior_section_callback'],
            'psource_chat_settings'
        );
        
        // Appearance Section
        add_settings_section(
            'psource_chat_appearance',
            __('Appearance', 'psource-chat'),
            [$this, 'appearance_section_callback'],
            'psource_chat_settings'
        );
        
        // Security Section
        add_settings_section(
            'psource_chat_security',
            __('Security & Moderation', 'psource-chat'),
            [$this, 'security_section_callback'],
            'psource_chat_settings'
        );
        
        $this->add_setting_fields();
    }
    
    /**
     * Add setting fields
     */
    private function add_setting_fields() {
        // General Settings
        add_settings_field(
            'enable_sound',
            __('Enable Sound', 'psource-chat'),
            [$this, 'checkbox_field'],
            'psource_chat_settings',
            'psource_chat_general',
            ['field' => 'enable_sound', 'description' => __('Play notification sounds for new messages', 'psource-chat')]
        );
        
        add_settings_field(
            'enable_emoji',
            __('Enable Emoji', 'psource-chat'),
            [$this, 'checkbox_field'],
            'psource_chat_settings',
            'psource_chat_general',
            ['field' => 'enable_emoji', 'description' => __('Convert emoji codes to actual emoji', 'psource-chat')]
        );
        
        add_settings_field(
            'enable_private_chat',
            __('Enable Private Chat', 'psource-chat'),
            [$this, 'checkbox_field'],
            'psource_chat_settings',
            'psource_chat_general',
            ['field' => 'enable_private_chat', 'description' => __('Allow users to send private messages', 'psource-chat')]
        );
        
        add_settings_field(
            'allow_guest_chat',
            __('Allow Guest Chat', 'psource-chat'),
            [$this, 'checkbox_field'],
            'psource_chat_settings',
            'psource_chat_general',
            ['field' => 'allow_guest_chat', 'description' => __('Allow non-logged-in users to participate in chat', 'psource-chat')]
        );
        
        // Behavior Settings
        add_settings_field(
            'max_message_length',
            __('Max Message Length', 'psource-chat'),
            [$this, 'number_field'],
            'psource_chat_settings',
            'psource_chat_behavior',
            ['field' => 'max_message_length', 'min' => 50, 'max' => 2000, 'description' => __('Maximum characters per message', 'psource-chat')]
        );
        
        add_settings_field(
            'chat_timeout',
            __('Chat Timeout (seconds)', 'psource-chat'),
            [$this, 'number_field'],
            'psource_chat_settings',
            'psource_chat_behavior',
            ['field' => 'chat_timeout', 'min' => 60, 'max' => 3600, 'description' => __('Time before a user is considered inactive', 'psource-chat')]
        );
        
        add_settings_field(
            'max_users_per_session',
            __('Max Users per Session', 'psource-chat'),
            [$this, 'number_field'],
            'psource_chat_settings',
            'psource_chat_behavior',
            ['field' => 'max_users_per_session', 'min' => 5, 'max' => 200, 'description' => __('Maximum number of users in one chat session', 'psource-chat')]
        );
        
        add_settings_field(
            'chat_history_limit',
            __('Chat History Limit', 'psource-chat'),
            [$this, 'number_field'],
            'psource_chat_settings',
            'psource_chat_behavior',
            ['field' => 'chat_history_limit', 'min' => 20, 'max' => 500, 'description' => __('Number of messages to show in chat history', 'psource-chat')]
        );
        
        // Appearance Settings
        add_settings_field(
            'theme',
            __('Chat Theme', 'psource-chat'),
            [$this, 'select_field'],
            'psource_chat_settings',
            'psource_chat_appearance',
            ['field' => 'theme', 'options' => [
                'default' => __('Default', 'psource-chat'),
                'dark' => __('Dark', 'psource-chat'),
                'light' => __('Light', 'psource-chat'),
                'custom' => __('Custom', 'psource-chat')
            ]]
        );
        
        add_settings_field(
            'position',
            __('Chat Position', 'psource-chat'),
            [$this, 'select_field'],
            'psource_chat_settings',
            'psource_chat_appearance',
            ['field' => 'position', 'options' => [
                'bottom-right' => __('Bottom Right', 'psource-chat'),
                'bottom-left' => __('Bottom Left', 'psource-chat'),
                'top-right' => __('Top Right', 'psource-chat'),
                'top-left' => __('Top Left', 'psource-chat')
            ]]
        );
        
        add_settings_field(
            'width',
            __('Chat Width (px)', 'psource-chat'),
            [$this, 'number_field'],
            'psource_chat_settings',
            'psource_chat_appearance',
            ['field' => 'width', 'min' => 300, 'max' => 800, 'description' => __('Width of the chat window in pixels', 'psource-chat')]
        );
        
        add_settings_field(
            'height',
            __('Chat Height (px)', 'psource-chat'),
            [$this, 'number_field'],
            'psource_chat_settings',
            'psource_chat_appearance',
            ['field' => 'height', 'min' => 300, 'max' => 800, 'description' => __('Height of the chat window in pixels', 'psource-chat')]
        );
        
        // Security Settings
        add_settings_field(
            'moderate_messages',
            __('Moderate Messages', 'psource-chat'),
            [$this, 'checkbox_field'],
            'psource_chat_settings',
            'psource_chat_security',
            ['field' => 'moderate_messages', 'description' => __('Hold messages for moderation before displaying', 'psource-chat')]
        );
        
        add_settings_field(
            'bad_words_filter',
            __('Bad Words Filter', 'psource-chat'),
            [$this, 'checkbox_field'],
            'psource_chat_settings',
            'psource_chat_security',
            ['field' => 'bad_words_filter', 'description' => __('Filter inappropriate language', 'psource-chat')]
        );
        
        add_settings_field(
            'cleanup_days',
            __('Cleanup Old Data (days)', 'psource-chat'),
            [$this, 'number_field'],
            'psource_chat_settings',
            'psource_chat_security',
            ['field' => 'cleanup_days', 'min' => 1, 'max' => 365, 'description' => __('Automatically delete chat data older than X days', 'psource-chat')]
        );
        
        add_settings_field(
            'enable_file_uploads',
            __('Enable File Uploads', 'psource-chat'),
            [$this, 'checkbox_field'],
            'psource_chat_settings',
            'psource_chat_security',
            ['field' => 'enable_file_uploads', 'description' => __('Allow users to upload files in chat', 'psource-chat')]
        );
    }
    
    /**
     * Render the settings page
     */
    public function render() {
        if (isset($_GET['settings-updated'])) {
            add_settings_error('psource_chat_messages', 'psource_chat_message', __('Settings saved successfully!', 'psource-chat'), 'updated');
        }
        
        settings_errors('psource_chat_messages');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="psource-chat-admin-container">
                <form action="options.php" method="post">
                    <?php
                    settings_fields('psource_chat_settings');
                    do_settings_sections('psource_chat_settings');
                    submit_button();
                    ?>
                </form>
                
                <div class="psource-chat-sidebar">
                    <div class="psource-chat-info-box">
                        <h3><?php _e('Quick Help', 'psource-chat'); ?></h3>
                        <ul>
                            <li><?php _e('Use the shortcode [psource_chat] to display chat anywhere', 'psource-chat'); ?></li>
                            <li><?php _e('Chat appears automatically in the bottom corner if enabled', 'psource-chat'); ?></li>
                            <li><?php _e('Moderators can view all chat sessions from the admin area', 'psource-chat'); ?></li>
                        </ul>
                    </div>
                    
                    <div class="psource-chat-info-box">
                        <h3><?php _e('Performance Tips', 'psource-chat'); ?></h3>
                        <ul>
                            <li><?php _e('Enable cleanup to keep database size manageable', 'psource-chat'); ?></li>
                            <li><?php _e('Limit chat history for better performance', 'psource-chat'); ?></li>
                            <li><?php _e('Use moderation only if necessary', 'psource-chat'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .psource-chat-admin-container {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 20px;
            margin-top: 20px;
        }
        
        .psource-chat-sidebar {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .psource-chat-info-box {
            background: #fff;
            border: 1px solid #ccd0d4;
            padding: 15px;
            border-radius: 4px;
        }
        
        .psource-chat-info-box h3 {
            margin-top: 0;
            margin-bottom: 10px;
        }
        
        .psource-chat-info-box ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .psource-chat-info-box li {
            margin-bottom: 5px;
        }
        
        @media (max-width: 768px) {
            .psource-chat-admin-container {
                grid-template-columns: 1fr;
            }
        }
        </style>
        <?php
    }
    
    /**
     * Section callbacks
     */
    public function general_section_callback() {
        echo '<p>' . __('Configure basic chat functionality and features.', 'psource-chat') . '</p>';
    }
    
    public function behavior_section_callback() {
        echo '<p>' . __('Control how the chat behaves and user interaction limits.', 'psource-chat') . '</p>';
    }
    
    public function appearance_section_callback() {
        echo '<p>' . __('Customize the look and feel of your chat interface.', 'psource-chat') . '</p>';
    }
    
    public function security_section_callback() {
        echo '<p>' . __('Configure security settings and content moderation.', 'psource-chat') . '</p>';
    }
    
    /**
     * Field rendering methods
     */
    public function checkbox_field($args) {
        $options = Plugin::get_instance()->get_options();
        $value = isset($options[$args['field']]) ? $options[$args['field']] : false;
        ?>
        <label>
            <input type="checkbox" name="psource_chat_options[<?php echo esc_attr($args['field']); ?>]" value="1" <?php checked($value); ?> />
            <?php if (isset($args['description'])): ?>
                <span class="description"><?php echo esc_html($args['description']); ?></span>
            <?php endif; ?>
        </label>
        <?php
    }
    
    public function number_field($args) {
        $options = Plugin::get_instance()->get_options();
        $value = isset($options[$args['field']]) ? $options[$args['field']] : '';
        ?>
        <input type="number" 
               name="psource_chat_options[<?php echo esc_attr($args['field']); ?>]" 
               value="<?php echo esc_attr($value); ?>"
               min="<?php echo esc_attr($args['min'] ?? ''); ?>"
               max="<?php echo esc_attr($args['max'] ?? ''); ?>"
               class="regular-text" />
        <?php if (isset($args['description'])): ?>
            <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php endif; ?>
        <?php
    }
    
    public function text_field($args) {
        $options = Plugin::get_instance()->get_options();
        $value = isset($options[$args['field']]) ? $options[$args['field']] : '';
        ?>
        <input type="text" 
               name="psource_chat_options[<?php echo esc_attr($args['field']); ?>]" 
               value="<?php echo esc_attr($value); ?>"
               class="regular-text" />
        <?php if (isset($args['description'])): ?>
            <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php endif; ?>
        <?php
    }
    
    public function select_field($args) {
        $options = Plugin::get_instance()->get_options();
        $value = isset($options[$args['field']]) ? $options[$args['field']] : '';
        ?>
        <select name="psource_chat_options[<?php echo esc_attr($args['field']); ?>]">
            <?php foreach ($args['options'] as $option_value => $option_label): ?>
                <option value="<?php echo esc_attr($option_value); ?>" <?php selected($value, $option_value); ?>>
                    <?php echo esc_html($option_label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (isset($args['description'])): ?>
            <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Sanitize options
     */
    public function sanitize_options($input) {
        $sanitized = [];
        
        // Boolean fields
        $boolean_fields = ['enable_sound', 'enable_emoji', 'enable_private_chat', 'allow_guest_chat', 'moderate_messages', 'bad_words_filter', 'enable_file_uploads'];
        foreach ($boolean_fields as $field) {
            $sanitized[$field] = isset($input[$field]) ? true : false;
        }
        
        // Number fields
        $number_fields = [
            'max_message_length' => ['min' => 50, 'max' => 2000],
            'chat_timeout' => ['min' => 60, 'max' => 3600],
            'max_users_per_session' => ['min' => 5, 'max' => 200],
            'chat_history_limit' => ['min' => 20, 'max' => 500],
            'width' => ['min' => 300, 'max' => 800],
            'height' => ['min' => 300, 'max' => 800],
            'cleanup_days' => ['min' => 1, 'max' => 365]
        ];
        
        foreach ($number_fields as $field => $constraints) {
            if (isset($input[$field])) {
                $value = absint($input[$field]);
                $sanitized[$field] = max($constraints['min'], min($constraints['max'], $value));
            }
        }
        
        // Select fields
        $select_fields = [
            'theme' => ['default', 'dark', 'light', 'custom'],
            'position' => ['bottom-right', 'bottom-left', 'top-right', 'top-left']
        ];
        
        foreach ($select_fields as $field => $allowed_values) {
            if (isset($input[$field]) && in_array($input[$field], $allowed_values)) {
                $sanitized[$field] = $input[$field];
            }
        }
        
        return $sanitized;
    }
}
