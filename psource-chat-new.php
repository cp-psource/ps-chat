<?php
/**
 * Plugin Name: PS Chat (Modernized)
 * Plugin URI: https://cp-psource.github.io/ps-chat/
 * Description: Bietet Dir einen voll ausgestatteten Chat-Bereich entweder in einem Beitrag, einer Seite, einem Widget oder in der unteren Ecke Ihrer Website. Unterstützt BuddyPress Group-Chats und private Chats zwischen angemeldeten Benutzern. KEINE EXTERNEN SERVER/DIENSTE!
 * Author: PSOURCE
 * Version: 3.0.0
 * Author URI: https://github.com/cp-psource
 * Text Domain: psource-chat
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.5
 * Requires PHP: 7.4
 * 
 * @package PSSource\Chat
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PSOURCE_CHAT_VERSION', '3.0.0');
define('PSOURCE_CHAT_PLUGIN_FILE', __FILE__);
define('PSOURCE_CHAT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PSOURCE_CHAT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PSOURCE_CHAT_INCLUDES_DIR', PSOURCE_CHAT_PLUGIN_DIR . 'includes/');

// Autoloader
spl_autoload_register(function ($class) {
    $namespace = 'PSSource\\Chat\\';
    
    if (strpos($class, $namespace) !== 0) {
        return;
    }
    
    $class = str_replace($namespace, '', $class);
    $class = str_replace('\\', '/', $class);
    
    // Convert class name to file path
    $parts = explode('/', $class);
    $filename = array_pop($parts);
    $path = implode('/', array_map('strtolower', $parts));
    
    // Convert CamelCase/Snake_Case to kebab-case for filename
    $filename = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $filename));
    $filename = str_replace('_', '-', $filename);
    
    $file = PSOURCE_CHAT_INCLUDES_DIR . ($path ? $path . '/' : '') . $filename . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
});

// Initialize the plugin
add_action('plugins_loaded', function() {
    // Load text domain
    load_plugin_textdomain(
        'psource-chat',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages/'
    );
    
    // Load Extensions API for third-party plugins
    require_once PSOURCE_CHAT_PLUGIN_DIR . 'includes/api/extensions-api.php';
    
    // Initialize the main plugin class
    PSSource\Chat\Core\Plugin::get_instance();
});

// Activation hook
register_activation_hook(__FILE__, function() {
    PSSource\Chat\Core\Installer::activate();
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    PSSource\Chat\Core\Installer::deactivate();
});
