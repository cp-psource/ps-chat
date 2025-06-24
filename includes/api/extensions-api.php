<?php
/**
 * Chat Extensions API
 * 
 * Provides an API for third-party plugins to register chat extensions
 * 
 * @package PSSource\Chat\API
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register a new chat extension
 * 
 * Example usage for third-party plugins:
 * 
 * ```php
 * add_action('psource_chat_register_extensions', function($extensions_manager) {
 *     $extensions_manager->register_extension('my_plugin_chat', [
 *         'title' => 'My Plugin Chat Integration',
 *         'description' => 'Custom chat integration for my plugin',
 *         'icon' => 'dashicons-admin-generic',
 *         'callback' => 'my_plugin_render_chat_settings',
 *         'priority' => 100,
 *         'capability' => 'manage_options'
 *     ]);
 * });
 * ```
 * 
 * @param string $id       Unique extension ID
 * @param array  $args     Extension configuration
 * @return bool            True if registered successfully
 */
function psource_chat_register_extension($id, $args = []) {
    if (!did_action('psource_chat_register_extensions')) {
        // Store for later registration
        global $psource_chat_pending_extensions;
        if (!isset($psource_chat_pending_extensions)) {
            $psource_chat_pending_extensions = [];
        }
        $psource_chat_pending_extensions[$id] = $args;
        return true;
    }
    
    // Get extensions manager
    static $extensions_manager = null;
    if ($extensions_manager === null) {
        $extensions_manager = new \PSSource\Chat\Admin\Chat_Extensions();
    }
    
    return $extensions_manager->register_extension($id, $args);
}

/**
 * Get chat extension options
 * 
 * @param string $extension_id Extension ID
 * @param string $option_key   Option key (optional)
 * @param mixed  $default      Default value
 * @return mixed               Option value or all options for extension
 */
function psource_chat_get_extension_option($extension_id, $option_key = null, $default = null) {
    $all_options = get_option('psource_chat_extensions', []);
    $extension_options = $all_options[$extension_id] ?? [];
    
    if ($option_key === null) {
        return $extension_options;
    }
    
    return $extension_options[$option_key] ?? $default;
}

/**
 * Update chat extension option
 * 
 * @param string $extension_id Extension ID
 * @param string $option_key   Option key
 * @param mixed  $value        Option value
 * @return bool                True if updated successfully
 */
function psource_chat_update_extension_option($extension_id, $option_key, $value) {
    $all_options = get_option('psource_chat_extensions', []);
    
    if (!isset($all_options[$extension_id])) {
        $all_options[$extension_id] = [];
    }
    
    $all_options[$extension_id][$option_key] = $value;
    
    return update_option('psource_chat_extensions', $all_options);
}

/**
 * Check if an extension is enabled
 * 
 * @param string $extension_id Extension ID
 * @return bool                True if enabled
 */
function psource_chat_is_extension_enabled($extension_id) {
    $options = psource_chat_get_extension_option($extension_id);
    return !empty($options) && ($options['enabled'] ?? false);
}

/**
 * Add extension settings section
 * 
 * Helper function to add a settings section within an extension
 * 
 * @param string $title       Section title
 * @param string $description Section description (optional)
 * @param array  $fields      Field definitions
 * @param string $extension_id Extension ID for field names
 */
function psource_chat_extension_settings_section($title, $description = '', $fields = [], $extension_id = '') {
    ?>
    <fieldset>
        <legend><?php echo esc_html($title); ?></legend>
        <?php if ($description): ?>
            <p class="description"><?php echo esc_html($description); ?></p>
        <?php endif; ?>
        
        <table border="0" cellpadding="4" cellspacing="0">
            <?php foreach ($fields as $field_key => $field): ?>
                <tr>
                    <td class="chat-label-column">
                        <label for="<?php echo esc_attr($extension_id . '_' . $field_key); ?>">
                            <?php echo esc_html($field['label']); ?>
                        </label>
                    </td>
                    <td class="chat-value-column">
                        <?php
                        $field_name = "psource_chat_extensions[{$extension_id}][{$field_key}]";
                        $field_id = $extension_id . '_' . $field_key;
                        $current_value = psource_chat_get_extension_option($extension_id, $field_key, $field['default'] ?? '');
                        
                        switch ($field['type']) {
                            case 'text':
                                ?>
                                <input type="text" id="<?php echo esc_attr($field_id); ?>" 
                                       name="<?php echo esc_attr($field_name); ?>" 
                                       value="<?php echo esc_attr($current_value); ?>"
                                       placeholder="<?php echo esc_attr($field['placeholder'] ?? ''); ?>" />
                                <?php
                                break;
                                
                            case 'number':
                                ?>
                                <input type="number" id="<?php echo esc_attr($field_id); ?>" 
                                       name="<?php echo esc_attr($field_name); ?>" 
                                       value="<?php echo esc_attr($current_value); ?>"
                                       min="<?php echo esc_attr($field['min'] ?? ''); ?>"
                                       max="<?php echo esc_attr($field['max'] ?? ''); ?>"
                                       step="<?php echo esc_attr($field['step'] ?? '1'); ?>" />
                                <?php
                                break;
                                
                            case 'select':
                                ?>
                                <select id="<?php echo esc_attr($field_id); ?>" name="<?php echo esc_attr($field_name); ?>">
                                    <?php foreach ($field['options'] as $option_value => $option_label): ?>
                                        <option value="<?php echo esc_attr($option_value); ?>" 
                                                <?php selected($current_value, $option_value); ?>>
                                            <?php echo esc_html($option_label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php
                                break;
                                
                            case 'textarea':
                                ?>
                                <textarea id="<?php echo esc_attr($field_id); ?>" 
                                          name="<?php echo esc_attr($field_name); ?>"
                                          rows="<?php echo esc_attr($field['rows'] ?? '4'); ?>"
                                          cols="<?php echo esc_attr($field['cols'] ?? '50'); ?>"
                                          placeholder="<?php echo esc_attr($field['placeholder'] ?? ''); ?>"><?php echo esc_textarea($current_value); ?></textarea>
                                <?php
                                break;
                                
                            case 'checkbox':
                                ?>
                                <input type="checkbox" id="<?php echo esc_attr($field_id); ?>" 
                                       name="<?php echo esc_attr($field_name); ?>" 
                                       value="1" <?php checked($current_value, 1); ?> />
                                <label for="<?php echo esc_attr($field_id); ?>">
                                    <?php echo esc_html($field['description'] ?? ''); ?>
                                </label>
                                <?php
                                break;
                        }
                        ?>
                        
                        <?php if (isset($field['help'])): ?>
                            <p class="description"><?php echo esc_html($field['help']); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </fieldset>
    <?php
}

/**
 * Register pending extensions during the hook
 */
add_action('psource_chat_register_extensions', function($extensions_manager) {
    global $psource_chat_pending_extensions;
    
    if (!empty($psource_chat_pending_extensions)) {
        foreach ($psource_chat_pending_extensions as $id => $args) {
            $extensions_manager->register_extension($id, $args);
        }
        $psource_chat_pending_extensions = [];
    }
}, 1);

/**
 * Example third-party extension registration
 * 
 * This shows how a third-party plugin would register an extension:
 */
/*
add_action('psource_chat_register_extensions', function($extensions_manager) {
    $extensions_manager->register_extension('woocommerce_chat', [
        'title' => 'WooCommerce Chat Support',
        'description' => 'Live chat support for WooCommerce product pages',
        'icon' => 'dashicons-cart',
        'callback' => 'woocommerce_chat_render_settings',
        'priority' => 60,
        'capability' => 'manage_woocommerce'
    ]);
});

function woocommerce_chat_render_settings($ext_id) {
    psource_chat_extension_settings_section(
        'WooCommerce Integration',
        'Configure chat support for your WooCommerce store',
        [
            'enable_product_chat' => [
                'type' => 'select',
                'label' => 'Enable chat on product pages',
                'options' => [
                    'disabled' => 'Disabled',
                    'enabled' => 'Enabled'
                ],
                'default' => 'disabled'
            ],
            'enable_checkout_chat' => [
                'type' => 'select',
                'label' => 'Enable chat on checkout',
                'options' => [
                    'disabled' => 'Disabled',
                    'enabled' => 'Enabled'
                ],
                'default' => 'disabled'
            ],
            'support_hours' => [
                'type' => 'text',
                'label' => 'Support hours',
                'placeholder' => 'e.g., Mon-Fri 9AM-5PM',
                'help' => 'Display support availability hours'
            ]
        ],
        $ext_id
    );
}
*/
