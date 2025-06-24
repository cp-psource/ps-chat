<?php
/**
 * Extension Base Class
 * 
 * @package PSSource\Chat\Core
 */

namespace PSSource\Chat\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Base class for chat extensions
 */
abstract class Extension_Base {
    
    /**
     * Extension ID
     */
    protected $id = '';
    
    /**
     * Extension title
     */
    protected $title = '';
    
    /**
     * Extension description
     */
    protected $description = '';
    
    /**
     * Extension icon
     */
    protected $icon = 'dashicons-admin-generic';
    
    /**
     * Required capability
     */
    protected $capability = 'manage_options';
    
    /**
     * Extension options
     */
    protected $options = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->load_options();
        
        if ($this->is_enabled()) {
            $this->init();
        }
    }
    
    /**
     * Initialize extension (to be implemented by child classes)
     */
    abstract public function init();
    
    /**
     * Get default options (to be implemented by child classes)
     */
    abstract public function get_default_options();
    
    /**
     * Render extension options (to be implemented by child classes)
     */
    abstract public function render_options();
    
    /**
     * Load extension options
     */
    protected function load_options() {
        $all_options = get_option('psource_chat_extensions', []);
        $this->options = array_merge(
            $this->get_default_options(),
            $all_options[$this->id] ?? []
        );
    }
    
    /**
     * Get all extension options
     */
    public function get_options() {
        return $this->options;
    }
    
    /**
     * Get a specific option
     */
    public function get_option($key, $default = null) {
        return $this->options[$key] ?? $default;
    }
    
    /**
     * Set an option
     */
    public function set_option($key, $value) {
        $this->options[$key] = $value;
        $this->save_options();
    }
    
    /**
     * Save options to database
     */
    protected function save_options() {
        $all_options = get_option('psource_chat_extensions', []);
        $all_options[$this->id] = $this->options;
        update_option('psource_chat_extensions', $all_options);
    }
    
    /**
     * Check if extension is enabled
     */
    public function is_enabled() {
        return ($this->get_option('enabled') === 'enabled');
    }
    
    /**
     * Get extension ID
     */
    public function get_id() {
        return $this->id;
    }
    
    /**
     * Get extension title
     */
    public function get_title() {
        return $this->title;
    }
    
    /**
     * Get extension description
     */
    public function get_description() {
        return $this->description;
    }
    
    /**
     * Get extension icon
     */
    public function get_icon() {
        return $this->icon;
    }
    
    /**
     * Get required capability
     */
    public function get_capability() {
        return $this->capability;
    }
    
    /**
     * Validate option value
     */
    protected function validate_option($key, $value) {
        // Basic validation - can be overridden by child classes
        return $value;
    }
    
    /**
     * Sanitize option value
     */
    protected function sanitize_option($key, $value) {
        // Basic sanitization - can be overridden by child classes
        if (is_string($value)) {
            return sanitize_text_field($value);
        }
        return $value;
    }
    
    /**
     * Get localized strings for this extension
     */
    protected function get_strings() {
        return [];
    }
    
    /**
     * Enqueue extension-specific assets
     */
    protected function enqueue_assets() {
        // Override in child classes if needed
    }
    
    /**
     * Add hooks for this extension
     */
    protected function add_hooks() {
        // Override in child classes if needed
    }
    
    /**
     * Log extension activity
     */
    protected function log($message, $level = 'info') {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('[PS Chat %s] %s', ucfirst($this->id), $message));
        }
    }
    
    /**
     * Check if current user can manage this extension
     */
    public function current_user_can_manage() {
        return current_user_can($this->capability);
    }
    
    /**
     * Get extension status
     */
    public function get_status() {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'enabled' => $this->is_enabled(),
            'version' => $this->get_version(),
            'last_updated' => $this->get_option('last_updated'),
        ];
    }
    
    /**
     * Get extension version
     */
    protected function get_version() {
        return '1.0.0';
    }
    
    /**
     * Handle extension activation
     */
    public function activate() {
        $this->set_option('enabled', 'enabled');
        $this->set_option('activated_at', current_time('mysql'));
        $this->on_activation();
    }
    
    /**
     * Handle extension deactivation
     */
    public function deactivate() {
        $this->set_option('enabled', 'disabled');
        $this->set_option('deactivated_at', current_time('mysql'));
        $this->on_deactivation();
    }
    
    /**
     * Called when extension is activated
     */
    protected function on_activation() {
        // Override in child classes if needed
    }
    
    /**
     * Called when extension is deactivated
     */
    protected function on_deactivation() {
        // Override in child classes if needed
    }
}
