<?php
/**
 * Custom CSS Variables Generator
 * Generates minimal CSS variables based on user settings
 */

/**
 * Generate CSS variables for user customizations
 */
function psource_chat_generate_custom_css() {
    $extension_options = get_option('psource_chat_extensions', []);
    $frontend_options = $extension_options['frontend'] ?? [];
    
    if (empty($frontend_options)) {
        return '';
    }
    
    $css_vars = [];
    
    // Color customizations
    if (!empty($frontend_options['header_bg_color'])) {
        $css_vars[] = '--psource-chat-header-bg: ' . esc_attr($frontend_options['header_bg_color']);
    }
    
    if (!empty($frontend_options['header_text_color'])) {
        $css_vars[] = '--psource-chat-header-text: ' . esc_attr($frontend_options['header_text_color']);
    }
    
    if (!empty($frontend_options['chat_bg_color'])) {
        $css_vars[] = '--psource-chat-bg: ' . esc_attr($frontend_options['chat_bg_color']);
    }
    
    if (!empty($frontend_options['input_bg_color'])) {
        $css_vars[] = '--psource-chat-input-bg: ' . esc_attr($frontend_options['input_bg_color']);
    }
    
    if (!empty($frontend_options['button_color'])) {
        $css_vars[] = '--psource-chat-button-color: ' . esc_attr($frontend_options['button_color']);
        $css_vars[] = '--psource-chat-button-hover-color: ' . psource_chat_darken_color($frontend_options['button_color'], 20);
    }
    
    // Size customizations
    if (!empty($frontend_options['font_size'])) {
        $css_vars[] = '--psource-chat-font-size: ' . intval($frontend_options['font_size']) . 'px';
    }
    
    if (!empty($frontend_options['border_radius'])) {
        $css_vars[] = '--psource-chat-border-radius: ' . intval($frontend_options['border_radius']) . 'px';
    }
    
    // Build CSS
    $css = '';
    
    if (!empty($css_vars)) {
        $css .= '.psource-chat-widget {' . "\n";
        $css .= '  ' . implode(";\n  ", $css_vars) . ";\n";
        $css .= '}' . "\n";
        
        // Apply variables to specific elements
        if (!empty($frontend_options['header_bg_color'])) {
            $css .= '.psource-chat-widget .psource-chat-header { background: var(--psource-chat-header-bg) !important; }' . "\n";
        }
        
        if (!empty($frontend_options['header_text_color'])) {
            $css .= '.psource-chat-widget .psource-chat-header { color: var(--psource-chat-header-text) !important; }' . "\n";
        }
        
        if (!empty($frontend_options['chat_bg_color'])) {
            $css .= '.psource-chat-widget .psource-chat-messages { background: var(--psource-chat-bg) !important; }' . "\n";
        }
        
        if (!empty($frontend_options['input_bg_color'])) {
            $css .= '.psource-chat-widget .psource-chat-input { background: var(--psource-chat-input-bg) !important; }' . "\n";
        }
        
        if (!empty($frontend_options['button_color'])) {
            $css .= '.psource-chat-widget .psource-chat-send-btn { background: var(--psource-chat-button-color) !important; border-color: var(--psource-chat-button-color) !important; }' . "\n";
            $css .= '.psource-chat-widget .psource-chat-send-btn:hover { background: var(--psource-chat-button-hover-color) !important; border-color: var(--psource-chat-button-hover-color) !important; }' . "\n";
        }
        
        if (!empty($frontend_options['font_size'])) {
            $css .= '.psource-chat-widget .psource-chat-messages { font-size: var(--psource-chat-font-size) !important; }' . "\n";
            $css .= '.psource-chat-widget .psource-chat-input { font-size: var(--psource-chat-font-size) !important; }' . "\n";
        }
        
        if (!empty($frontend_options['border_radius'])) {
            $css .= '.psource-chat-widget { border-radius: var(--psource-chat-border-radius) !important; }' . "\n";
            $css .= '.psource-chat-widget .psource-chat-input { border-radius: calc(var(--psource-chat-border-radius) - 2px) !important; }' . "\n";
        }
    }
    
    // Add theme classes
    $theme = $frontend_options['theme'] ?? 'default';
    if ($theme !== 'default') {
        $css .= '.psource-chat-widget { /* Theme: ' . $theme . ' - handled via CSS classes */ }' . "\n";
    }
    
    // Add custom CSS
    if (!empty($frontend_options['custom_css'])) {
        $css .= "\n/* Custom CSS */\n";
        $css .= wp_strip_all_tags($frontend_options['custom_css']) . "\n";
    }
    
    return $css;
}

/**
 * Helper function to darken a hex color
 */
function psource_chat_darken_color($hex, $percent) {
    $hex = ltrim($hex, '#');
    
    if (strlen($hex) == 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    
    $r = hexdec($hex[0] . $hex[1]);
    $g = hexdec($hex[2] . $hex[3]);
    $b = hexdec($hex[4] . $hex[5]);
    
    $r = max(0, min(255, $r - ($r * $percent / 100)));
    $g = max(0, min(255, $g - ($g * $percent / 100)));
    $b = max(0, min(255, $b - ($b * $percent / 100)));
    
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}
