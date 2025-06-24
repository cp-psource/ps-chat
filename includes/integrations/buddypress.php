<?php
/**
 * BuddyPress Integration
 * 
 * @package PSSource\Chat\Integrations
 */

namespace PSSource\Chat\Integrations;

use PSSource\Chat\Core\Database;
use PSSource\Chat\Frontend\Chat_Renderer;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles BuddyPress integration
 */
class BuddyPress {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Only initialize if BuddyPress is active
        if (!class_exists('BuddyPress')) {
            return;
        }
        
        add_action('bp_init', [$this, 'init']);
    }
    
    /**
     * Initialize BuddyPress integration
     */
    public function init() {
        // Group chat integration
        add_action('bp_after_group_header', [$this, 'maybe_add_group_chat']);
        add_action('bp_group_activity_tabs', [$this, 'add_group_chat_tab']);
        add_action('bp_group_plugin_options_nav', [$this, 'add_group_chat_nav']);
        
        // Profile chat integration
        add_action('bp_member_plugin_options_nav', [$this, 'add_member_chat_nav']);
        add_action('bp_template_content', [$this, 'display_member_chat_content']);
        
        // Activity stream integration
        add_action('bp_activity_posted_update', [$this, 'maybe_create_chat_from_activity'], 10, 3);
        
        // Notifications
        add_action('psource_chat_message_sent', [$this, 'maybe_send_bp_notification'], 10, 3);
        
        // CSS/JS for BuddyPress
        add_action('bp_enqueue_scripts', [$this, 'enqueue_bp_assets']);
    }
    
    /**
     * Maybe add group chat
     */
    public function maybe_add_group_chat() {
        if (!bp_is_group() || !$this->is_group_chat_enabled()) {
            return;
        }
        
        $group_id = bp_get_current_group_id();
        $group = groups_get_group($group_id);
        
        // Check if user can access group chat
        if (!$this->can_user_access_group_chat($group)) {
            return;
        }
        
        echo '<div class="psource-chat-bp-group-widget">';
        echo '<h4>' . __('Group Chat', 'psource-chat') . '</h4>';
        
        $renderer = new Chat_Renderer();
        echo $renderer->render_shortcode_chat([
            'session_type' => 'buddypress-group',
            'session_id' => 'bp-group-' . $group_id,
            'width' => '100%',
            'height' => '300px',
            'theme' => 'buddypress'
        ]);
        
        echo '</div>';
    }
    
    /**
     * Add group chat tab
     */
    public function add_group_chat_tab() {
        if (!bp_is_group() || !$this->is_group_chat_enabled()) {
            return;
        }
        
        $group_id = bp_get_current_group_id();
        $group = groups_get_group($group_id);
        
        if (!$this->can_user_access_group_chat($group)) {
            return;
        }
        
        ?>
        <li id="group-chat-tab" class="current">
            <a href="#group-chat-content">
                <?php _e('Chat', 'psource-chat'); ?>
                <span class="psource-chat-unread-count" style="display: none;">0</span>
            </a>
        </li>
        <?php
    }
    
    /**
     * Add group chat navigation
     */
    public function add_group_chat_nav() {
        if (!bp_is_group() || !$this->is_group_chat_enabled()) {
            return;
        }
        
        $group = groups_get_current_group();
        if (!$this->can_user_access_group_chat($group)) {
            return;
        }
        
        bp_core_new_subnav_item([
            'name' => __('Chat', 'psource-chat'),
            'slug' => 'chat',
            'parent_url' => bp_get_group_permalink($group),
            'parent_slug' => bp_get_current_group_slug(),
            'screen_function' => [$this, 'group_chat_screen'],
            'position' => 30,
            'user_has_access' => $this->can_user_access_group_chat($group),
            'item_css_id' => 'group-chat'
        ]);
    }
    
    /**
     * Group chat screen
     */
    public function group_chat_screen() {
        add_action('bp_template_title', [$this, 'group_chat_title']);
        add_action('bp_template_content', [$this, 'group_chat_content']);
        bp_core_load_template('buddypress/groups/single/plugins');
    }
    
    /**
     * Group chat title
     */
    public function group_chat_title() {
        echo __('Group Chat', 'psource-chat');
    }
    
    /**
     * Group chat content
     */
    public function group_chat_content() {
        $group_id = bp_get_current_group_id();
        
        echo '<div class="psource-chat-bp-group-page">';
        
        // Chat guidelines
        echo '<div class="psource-chat-guidelines">';
        echo '<h4>' . __('Chat Guidelines', 'psource-chat') . '</h4>';
        echo '<ul>';
        echo '<li>' . __('Be respectful to all group members', 'psource-chat') . '</li>';
        echo '<li>' . __('Stay on topic related to the group', 'psource-chat') . '</li>';
        echo '<li>' . __('No spam or promotional content', 'psource-chat') . '</li>';
        echo '</ul>';
        echo '</div>';
        
        // Chat interface
        $renderer = new Chat_Renderer();
        echo $renderer->render_shortcode_chat([
            'session_type' => 'buddypress-group',
            'session_id' => 'bp-group-' . $group_id,
            'width' => '100%',
            'height' => '500px',
            'theme' => 'buddypress'
        ]);
        
        echo '</div>';
    }
    
    /**
     * Add member chat navigation
     */
    public function add_member_chat_nav() {
        if (!bp_is_user() || !is_user_logged_in()) {
            return;
        }
        
        // Don't show for own profile
        if (bp_is_my_profile()) {
            return;
        }
        
        // Check if private chat is enabled
        $plugin = \PSSource\Chat\Core\Plugin::get_instance();
        if (!$plugin->get_option('enable_private_chat', true)) {
            return;
        }
        
        bp_core_new_subnav_item([
            'name' => __('Private Chat', 'psource-chat'),
            'slug' => 'private-chat',
            'parent_url' => bp_displayed_user_domain(),
            'parent_slug' => 'profile',
            'screen_function' => [$this, 'member_chat_screen'],
            'position' => 100,
            'user_has_access' => true,
            'item_css_id' => 'member-private-chat'
        ]);
    }
    
    /**
     * Member chat screen
     */
    public function member_chat_screen() {
        add_action('bp_template_title', [$this, 'member_chat_title']);
        add_action('bp_template_content', [$this, 'member_chat_content']);
        bp_core_load_template('buddypress/members/single/plugins');
    }
    
    /**
     * Member chat title
     */
    public function member_chat_title() {
        echo sprintf(__('Private Chat with %s', 'psource-chat'), bp_get_displayed_user_fullname());
    }
    
    /**
     * Display member chat content
     */
    public function display_member_chat_content() {
        if (!bp_is_current_component('profile') || !bp_is_current_action('private-chat')) {
            return;
        }
        
        $this->member_chat_content();
    }
    
    /**
     * Member chat content
     */
    public function member_chat_content() {
        $current_user_id = get_current_user_id();
        $displayed_user_id = bp_displayed_user_id();
        
        // Create unique session ID for private chat
        $user_ids = [$current_user_id, $displayed_user_id];
        sort($user_ids);
        $session_id = 'bp-private-' . implode('-', $user_ids);
        
        echo '<div class="psource-chat-bp-private-page">';
        
        // Privacy notice
        echo '<div class="psource-chat-privacy-notice">';
        echo '<p>' . __('This is a private conversation. Only you and the other person can see these messages.', 'psource-chat') . '</p>';
        echo '</div>';
        
        // Chat interface
        $renderer = new Chat_Renderer();
        echo $renderer->render_shortcode_chat([
            'session_type' => 'buddypress-private',
            'session_id' => $session_id,
            'width' => '100%',
            'height' => '400px',
            'theme' => 'buddypress',
            'allow_private' => 'true'
        ]);
        
        echo '</div>';
    }
    
    /**
     * Maybe create chat from activity
     */
    public function maybe_create_chat_from_activity($content, $user_id, $activity_id) {
        // Check if activity chat is enabled
        if (!apply_filters('psource_chat_enable_activity_chat', false)) {
            return;
        }
        
        // Create a chat session for this activity
        $session_id = 'bp-activity-' . $activity_id;
        Database::create_session('buddypress-activity');
        
        // Add initial message
        $user = get_user_by('ID', $user_id);
        Database::add_message($session_id, [
            'message_text' => sprintf(__('%s started a discussion about this activity.', 'psource-chat'), $user->display_name),
            'user_id' => $user_id,
            'user_name' => $user->display_name,
            'user_email' => $user->user_email,
            'session_type' => 'buddypress-activity'
        ]);
    }
    
    /**
     * Maybe send BuddyPress notification
     */
    public function maybe_send_bp_notification($message_id, $session_id, $user_id) {
        if (!function_exists('bp_notifications_add_notification')) {
            return;
        }
        
        // Only for group chats
        if (strpos($session_id, 'bp-group-') !== 0) {
            return;
        }
        
        $group_id = str_replace('bp-group-', '', $session_id);
        $group = groups_get_group($group_id);
        
        if (!$group) {
            return;
        }
        
        // Get group members (exclude sender)
        $members = groups_get_group_members($group_id);
        $sender = get_user_by('ID', $user_id);
        
        foreach ($members['members'] as $member) {
            if ($member->ID == $user_id) {
                continue; // Skip sender
            }
            
            bp_notifications_add_notification([
                'user_id' => $member->ID,
                'item_id' => $group_id,
                'secondary_item_id' => $message_id,
                'component_name' => 'psource_chat',
                'component_action' => 'group_chat_message',
                'date_notified' => bp_core_current_time(),
                'is_new' => 1
            ]);
        }
    }
    
    /**
     * Enqueue BuddyPress assets
     */
    public function enqueue_bp_assets() {
        if (!bp_is_group() && !bp_is_user()) {
            return;
        }
        
        wp_enqueue_style('psource-chat-buddypress', 
            PSOURCE_CHAT_PLUGIN_URL . 'assets/css/buddypress.css', 
            ['psource-chat-frontend'], 
            PSOURCE_CHAT_VERSION
        );
        
        wp_enqueue_script('psource-chat-buddypress',
            PSOURCE_CHAT_PLUGIN_URL . 'assets/js/buddypress.js',
            ['psource-chat-frontend'],
            PSOURCE_CHAT_VERSION,
            true
        );
    }
    
    /**
     * Check if group chat is enabled
     */
    private function is_group_chat_enabled() {
        $group_id = bp_get_current_group_id();
        
        // Check group meta
        $enabled = groups_get_groupmeta($group_id, 'psource_chat_enabled');
        
        // Default to enabled if not set
        if ($enabled === '') {
            $enabled = true;
        }
        
        return apply_filters('psource_chat_group_enabled', $enabled, $group_id);
    }
    
    /**
     * Check if user can access group chat
     */
    private function can_user_access_group_chat($group) {
        if (!is_user_logged_in()) {
            return false;
        }
        
        // Check if user is a member of the group
        return groups_is_user_member(get_current_user_id(), $group->id);
    }
}
