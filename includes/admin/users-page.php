<?php
/**
 * Admin Users Page
 * 
 * @package PSSource\Chat\Admin
 */

namespace PSSource\Chat\Admin;

use PSSource\Chat\Core\Database;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Users Page Class
 */
class Users_Page {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_psource_chat_ban_user', [$this, 'ban_user']);
        add_action('wp_ajax_psource_chat_unban_user', [$this, 'unban_user']);
        add_action('wp_ajax_psource_chat_update_user_permissions', [$this, 'update_user_permissions']);
    }
    
    /**
     * Render users page
     */
    public function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'psource-chat'));
        }
        
        $current_tab = sanitize_text_field($_GET['tab'] ?? 'active-users');
        
        ?>
        <div class="wrap psource-chat-admin">
            <h1><?php _e('Chat Users', 'psource-chat'); ?></h1>
            
            <nav class="psource-chat-nav-tabs">
                <ul>
                    <li class="<?php echo $current_tab === 'active-users' ? 'active' : ''; ?>">
                        <a href="#active-users"><?php _e('Active Users', 'psource-chat'); ?></a>
                    </li>
                    <li class="<?php echo $current_tab === 'banned-users' ? 'active' : ''; ?>">
                        <a href="#banned-users"><?php _e('Banned Users', 'psource-chat'); ?></a>
                    </li>
                    <li class="<?php echo $current_tab === 'moderators' ? 'active' : ''; ?>">
                        <a href="#moderators"><?php _e('Moderators', 'psource-chat'); ?></a>
                    </li>
                    <li class="<?php echo $current_tab === 'permissions' ? 'active' : ''; ?>">
                        <a href="#permissions"><?php _e('Permissions', 'psource-chat'); ?></a>
                    </li>
                </ul>
            </nav>
            
            <div id="active-users" class="psource-chat-tab-content" style="<?php echo $current_tab === 'active-users' ? '' : 'display:none;'; ?>">
                <?php $this->render_active_users(); ?>
            </div>
            
            <div id="banned-users" class="psource-chat-tab-content" style="<?php echo $current_tab === 'banned-users' ? '' : 'display:none;'; ?>">
                <?php $this->render_banned_users(); ?>
            </div>
            
            <div id="moderators" class="psource-chat-tab-content" style="<?php echo $current_tab === 'moderators' ? '' : 'display:none;'; ?>">
                <?php $this->render_moderators(); ?>
            </div>
            
            <div id="permissions" class="psource-chat-tab-content" style="<?php echo $current_tab === 'permissions' ? '' : 'display:none;'; ?>">
                <?php $this->render_permissions(); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render active users
     */
    private function render_active_users() {
        $users = $this->get_active_users();
        
        ?>
        <div class="psource-chat-users-header">
            <h3><?php _e('Active Chat Users', 'psource-chat'); ?></h3>
            <div class="psource-chat-stats">
                <span class="psource-chat-stat">
                    <strong><?php echo count($users); ?></strong> <?php _e('total active users', 'psource-chat'); ?>
                </span>
            </div>
        </div>
        
        <?php if (empty($users)): ?>
            <div class="psource-chat-notice psource-chat-notice-info">
                <p><?php _e('No active users found.', 'psource-chat'); ?></p>
            </div>
        <?php else: ?>
            <table class="psource-chat-sessions-table wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('User', 'psource-chat'); ?></th>
                        <th><?php _e('Status', 'psource-chat'); ?></th>
                        <th><?php _e('Last Activity', 'psource-chat'); ?></th>
                        <th><?php _e('Messages', 'psource-chat'); ?></th>
                        <th><?php _e('Sessions', 'psource-chat'); ?></th>
                        <th><?php _e('Actions', 'psource-chat'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr data-user-id="<?php echo esc_attr($user->user_id); ?>">
                            <td>
                                <div class="psource-chat-user-info">
                                    <?php echo get_avatar($user->user_id, 32); ?>
                                    <div class="psource-chat-user-details">
                                        <strong><?php echo esc_html($user->display_name); ?></strong>
                                        <br>
                                        <small><?php echo esc_html($user->user_email); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="psource-chat-status psource-chat-status-<?php echo $user->is_online ? 'active' : 'inactive'; ?>">
                                    <?php echo $user->is_online ? __('Online', 'psource-chat') : __('Offline', 'psource-chat'); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html(human_time_diff(strtotime($user->last_activity), current_time('timestamp')) . ' ' . __('ago', 'psource-chat')); ?></td>
                            <td><?php echo esc_html($user->message_count); ?></td>
                            <td><?php echo esc_html($user->session_count); ?></td>
                            <td>
                                <div class="psource-chat-actions">
                                    <button type="button" class="psource-chat-action-btn psource-chat-action-view" data-modal="user-details-modal-<?php echo esc_attr($user->user_id); ?>">
                                        <?php _e('View', 'psource-chat'); ?>
                                    </button>
                                    <?php if (!$this->is_user_banned($user->user_id)): ?>
                                        <button type="button" class="psource-chat-action-btn psource-chat-action-delete" onclick="banChatUser(<?php echo esc_attr($user->user_id); ?>)">
                                            <?php _e('Ban', 'psource-chat'); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php foreach ($users as $user): ?>
                <div id="user-details-modal-<?php echo esc_attr($user->user_id); ?>" class="psource-chat-modal" style="display:none;">
                    <div class="psource-chat-modal-header">
                        <h3 class="psource-chat-modal-title"><?php printf(__('User Details: %s', 'psource-chat'), esc_html($user->display_name)); ?></h3>
                        <button type="button" class="psource-chat-modal-close">&times;</button>
                    </div>
                    <div class="psource-chat-modal-content">
                        <?php $this->render_user_details($user->user_id); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Render banned users
     */
    private function render_banned_users() {
        $users = $this->get_banned_users();
        
        ?>
        <div class="psource-chat-users-header">
            <h3><?php _e('Banned Users', 'psource-chat'); ?></h3>
            <div class="psource-chat-stats">
                <span class="psource-chat-stat">
                    <strong><?php echo count($users); ?></strong> <?php _e('banned users', 'psource-chat'); ?>
                </span>
            </div>
        </div>
        
        <?php if (empty($users)): ?>
            <div class="psource-chat-notice psource-chat-notice-info">
                <p><?php _e('No banned users found.', 'psource-chat'); ?></p>
            </div>
        <?php else: ?>
            <table class="psource-chat-sessions-table wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('User', 'psource-chat'); ?></th>
                        <th><?php _e('Banned Date', 'psource-chat'); ?></th>
                        <th><?php _e('Banned By', 'psource-chat'); ?></th>
                        <th><?php _e('Reason', 'psource-chat'); ?></th>
                        <th><?php _e('Actions', 'psource-chat'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr data-user-id="<?php echo esc_attr($user->user_id); ?>">
                            <td>
                                <div class="psource-chat-user-info">
                                    <?php echo get_avatar($user->user_id, 32); ?>
                                    <div class="psource-chat-user-details">
                                        <strong><?php echo esc_html($user->display_name); ?></strong>
                                        <br>
                                        <small><?php echo esc_html($user->user_email); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo esc_html(wp_date(get_option('date_format'), strtotime($user->banned_date))); ?></td>
                            <td>
                                <?php 
                                $banned_by = get_userdata($user->banned_by);
                                echo $banned_by ? esc_html($banned_by->display_name) : __('System', 'psource-chat');
                                ?>
                            </td>
                            <td><?php echo esc_html($user->ban_reason ?: __('No reason provided', 'psource-chat')); ?></td>
                            <td>
                                <div class="psource-chat-actions">
                                    <button type="button" class="psource-chat-action-btn psource-chat-action-view" onclick="unbanChatUser(<?php echo esc_attr($user->user_id); ?>)">
                                        <?php _e('Unban', 'psource-chat'); ?>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Render moderators
     */
    private function render_moderators() {
        $moderators = $this->get_moderators();
        
        ?>
        <div class="psource-chat-users-header">
            <h3><?php _e('Chat Moderators', 'psource-chat'); ?></h3>
            <div class="psource-chat-actions">
                <button type="button" class="button button-primary" data-modal="add-moderator-modal">
                    <?php _e('Add Moderator', 'psource-chat'); ?>
                </button>
            </div>
        </div>
        
        <?php if (empty($moderators)): ?>
            <div class="psource-chat-notice psource-chat-notice-info">
                <p><?php _e('No moderators assigned.', 'psource-chat'); ?></p>
            </div>
        <?php else: ?>
            <table class="psource-chat-sessions-table wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('User', 'psource-chat'); ?></th>
                        <th><?php _e('Assigned Date', 'psource-chat'); ?></th>
                        <th><?php _e('Permissions', 'psource-chat'); ?></th>
                        <th><?php _e('Actions', 'psource-chat'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($moderators as $moderator): ?>
                        <tr data-user-id="<?php echo esc_attr($moderator->user_id); ?>">
                            <td>
                                <div class="psource-chat-user-info">
                                    <?php echo get_avatar($moderator->user_id, 32); ?>
                                    <div class="psource-chat-user-details">
                                        <strong><?php echo esc_html($moderator->display_name); ?></strong>
                                        <br>
                                        <small><?php echo esc_html($moderator->user_email); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo esc_html(wp_date(get_option('date_format'), strtotime($moderator->assigned_date))); ?></td>
                            <td>
                                <?php 
                                $permissions = json_decode($moderator->permissions, true);
                                if (is_array($permissions)) {
                                    echo esc_html(implode(', ', array_keys(array_filter($permissions))));
                                } else {
                                    echo __('Standard Permissions', 'psource-chat');
                                }
                                ?>
                            </td>
                            <td>
                                <div class="psource-chat-actions">
                                    <button type="button" class="psource-chat-action-btn psource-chat-action-view" data-modal="edit-moderator-modal-<?php echo esc_attr($moderator->user_id); ?>">
                                        <?php _e('Edit', 'psource-chat'); ?>
                                    </button>
                                    <button type="button" class="psource-chat-action-btn psource-chat-action-delete" onclick="removeModerator(<?php echo esc_attr($moderator->user_id); ?>)">
                                        <?php _e('Remove', 'psource-chat'); ?>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <!-- Add Moderator Modal -->
        <div id="add-moderator-modal" class="psource-chat-modal" style="display:none;">
            <div class="psource-chat-modal-header">
                <h3 class="psource-chat-modal-title"><?php _e('Add Moderator', 'psource-chat'); ?></h3>
                <button type="button" class="psource-chat-modal-close">&times;</button>
            </div>
            <div class="psource-chat-modal-content">
                <form class="psource-chat-ajax-form" data-action="add_moderator">
                    <div class="psource-chat-form-field">
                        <label for="moderator-user-search"><?php _e('Select User', 'psource-chat'); ?></label>
                        <select id="moderator-user-search" name="user_id" required>
                            <option value=""><?php _e('Select a user...', 'psource-chat'); ?></option>
                            <?php 
                            $users = get_users(['fields' => ['ID', 'display_name']]);
                            foreach ($users as $user) {
                                echo '<option value="' . esc_attr($user->ID) . '">' . esc_html($user->display_name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="psource-chat-form-field">
                        <label><?php _e('Permissions', 'psource-chat'); ?></label>
                        <?php $this->render_permission_checkboxes(); ?>
                    </div>
                    
                    <div class="psource-chat-form-field">
                        <input type="submit" class="button button-primary" value="<?php _e('Add Moderator', 'psource-chat'); ?>">
                    </div>
                </form>
            </div>
        </div>
        
        <div class="psource-chat-modal-overlay"></div>
        <?php
    }
    
    /**
     * Render permissions tab
     */
    private function render_permissions() {
        ?>
        <div class="psource-chat-permissions-header">
            <h3><?php _e('Permission Settings', 'psource-chat'); ?></h3>
            <p class="description"><?php _e('Configure default permissions for different user roles.', 'psource-chat'); ?></p>
        </div>
        
        <form method="post" action="" class="psource-chat-settings-form">
            <?php wp_nonce_field('psource_chat_permissions', 'psource_chat_permissions_nonce'); ?>
            
            <?php 
            $roles = wp_roles()->get_names();
            foreach ($roles as $role_key => $role_name): 
                $permissions = $this->get_role_permissions($role_key);
            ?>
                <div class="psource-chat-settings-section">
                    <h4><?php echo esc_html($role_name); ?></h4>
                    
                    <?php 
                    $available_permissions = $this->get_available_permissions();
                    foreach ($available_permissions as $perm_key => $perm_label): 
                    ?>
                        <div class="psource-chat-checkbox-field">
                            <input type="checkbox" 
                                   id="<?php echo esc_attr($role_key . '_' . $perm_key); ?>" 
                                   name="permissions[<?php echo esc_attr($role_key); ?>][<?php echo esc_attr($perm_key); ?>]" 
                                   value="1" 
                                   <?php checked(!empty($permissions[$perm_key])); ?>>
                            <label for="<?php echo esc_attr($role_key . '_' . $perm_key); ?>">
                                <?php echo esc_html($perm_label); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
            
            <div class="submit">
                <input type="submit" class="button button-primary" value="<?php _e('Save Permissions', 'psource-chat'); ?>">
            </div>
        </form>
        <?php
        
        // Handle form submission
        if (isset($_POST['psource_chat_permissions_nonce']) && wp_verify_nonce($_POST['psource_chat_permissions_nonce'], 'psource_chat_permissions')) {
            $this->save_permissions($_POST['permissions'] ?? []);
            echo '<div class="psource-chat-notice psource-chat-notice-success"><p>' . __('Permissions saved successfully.', 'psource-chat') . '</p></div>';
        }
    }
    
    /**
     * Get active users
     */
    private function get_active_users() {
        global $wpdb;
        
        $sessions_table = Database::get_table_name('sessions');
        $messages_table = Database::get_table_name('messages');
        
        return $wpdb->get_results("
            SELECT DISTINCT u.ID as user_id, u.display_name, u.user_email,
                   MAX(s.last_activity) as last_activity,
                   (MAX(s.last_activity) > DATE_SUB(NOW(), INTERVAL 5 MINUTE)) as is_online,
                   COUNT(DISTINCT m.id) as message_count,
                   COUNT(DISTINCT s.id) as session_count
            FROM {$wpdb->users} u
            LEFT JOIN {$sessions_table} s ON u.ID = s.user_id
            LEFT JOIN {$messages_table} m ON u.ID = m.user_id
            WHERE s.id IS NOT NULL
            GROUP BY u.ID
            ORDER BY last_activity DESC
        ");
    }
    
    /**
     * Get banned users
     */
    private function get_banned_users() {
        global $wpdb;
        
        return $wpdb->get_results("
            SELECT u.ID as user_id, u.display_name, u.user_email,
                   um.meta_value as ban_data
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
            WHERE um.meta_key = 'psource_chat_banned'
            AND um.meta_value != ''
            ORDER BY u.display_name
        ");
    }
    
    /**
     * Get moderators
     */
    private function get_moderators() {
        global $wpdb;
        
        return $wpdb->get_results("
            SELECT u.ID as user_id, u.display_name, u.user_email,
                   um.meta_value as moderator_data
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
            WHERE um.meta_key = 'psource_chat_moderator'
            AND um.meta_value != ''
            ORDER BY u.display_name
        ");
    }
    
    /**
     * Check if user is banned
     */
    private function is_user_banned($user_id) {
        $ban_data = get_user_meta($user_id, 'psource_chat_banned', true);
        return !empty($ban_data);
    }
    
    /**
     * Render user details
     */
    private function render_user_details($user_id) {
        $user = get_userdata($user_id);
        if (!$user) return;
        
        // Get user stats
        global $wpdb;
        $sessions_table = Database::get_table_name('sessions');
        $messages_table = Database::get_table_name('messages');
        
        $stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(DISTINCT s.id) as total_sessions,
                COUNT(DISTINCT m.id) as total_messages,
                MAX(s.last_activity) as last_activity,
                MIN(s.created_at) as first_session
            FROM {$sessions_table} s
            LEFT JOIN {$messages_table} m ON s.id = m.session_id
            WHERE s.user_id = %d
        ", $user_id));
        
        ?>
        <div class="psource-chat-user-details-full">
            <div class="psource-chat-user-avatar">
                <?php echo get_avatar($user_id, 64); ?>
            </div>
            
            <table class="psource-chat-details-table">
                <tr>
                    <th><?php _e('Display Name', 'psource-chat'); ?></th>
                    <td><?php echo esc_html($user->display_name); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Email', 'psource-chat'); ?></th>
                    <td><?php echo esc_html($user->user_email); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Role', 'psource-chat'); ?></th>
                    <td><?php echo esc_html(implode(', ', $user->roles)); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Total Sessions', 'psource-chat'); ?></th>
                    <td><?php echo esc_html($stats->total_sessions ?? 0); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Total Messages', 'psource-chat'); ?></th>
                    <td><?php echo esc_html($stats->total_messages ?? 0); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Last Activity', 'psource-chat'); ?></th>
                    <td>
                        <?php 
                        echo $stats->last_activity 
                            ? esc_html(human_time_diff(strtotime($stats->last_activity), current_time('timestamp')) . ' ' . __('ago', 'psource-chat'))
                            : __('Never', 'psource-chat');
                        ?>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('First Session', 'psource-chat'); ?></th>
                    <td>
                        <?php 
                        echo $stats->first_session 
                            ? esc_html(wp_date(get_option('date_format'), strtotime($stats->first_session)))
                            : __('Never', 'psource-chat');
                        ?>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    /**
     * Render permission checkboxes
     */
    private function render_permission_checkboxes($selected_permissions = []) {
        $permissions = $this->get_available_permissions();
        
        foreach ($permissions as $key => $label) {
            $checked = !empty($selected_permissions[$key]);
            ?>
            <div class="psource-chat-checkbox-field">
                <input type="checkbox" 
                       id="perm_<?php echo esc_attr($key); ?>" 
                       name="permissions[<?php echo esc_attr($key); ?>]" 
                       value="1" 
                       <?php checked($checked); ?>>
                <label for="perm_<?php echo esc_attr($key); ?>">
                    <?php echo esc_html($label); ?>
                </label>
            </div>
            <?php
        }
    }
    
    /**
     * Get available permissions
     */
    private function get_available_permissions() {
        return [
            'delete_messages' => __('Delete Messages', 'psource-chat'),
            'ban_users' => __('Ban Users', 'psource-chat'),
            'moderate_chat' => __('Moderate Chat', 'psource-chat'),
            'view_logs' => __('View Logs', 'psource-chat'),
            'manage_sessions' => __('Manage Sessions', 'psource-chat'),
            'send_private_messages' => __('Send Private Messages', 'psource-chat'),
            'bypass_filters' => __('Bypass Word Filters', 'psource-chat')
        ];
    }
    
    /**
     * Get role permissions
     */
    private function get_role_permissions($role) {
        return get_option('psource_chat_role_permissions_' . $role, []);
    }
    
    /**
     * Save permissions
     */
    private function save_permissions($permissions) {
        foreach ($permissions as $role => $role_permissions) {
            update_option('psource_chat_role_permissions_' . $role, $role_permissions);
        }
    }
    
    /**
     * Ban user via AJAX
     */
    public function ban_user() {
        check_ajax_referer('psource_chat_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'psource-chat'));
        }
        
        $user_id = intval($_POST['user_id'] ?? 0);
        $reason = sanitize_text_field($_POST['reason'] ?? '');
        
        if (!$user_id) {
            wp_send_json_error(__('Invalid user ID.', 'psource-chat'));
        }
        
        $ban_data = [
            'banned_by' => get_current_user_id(),
            'banned_date' => current_time('mysql'),
            'reason' => $reason
        ];
        
        update_user_meta($user_id, 'psource_chat_banned', $ban_data);
        
        wp_send_json_success(__('User banned successfully.', 'psource-chat'));
    }
    
    /**
     * Unban user via AJAX
     */
    public function unban_user() {
        check_ajax_referer('psource_chat_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'psource-chat'));
        }
        
        $user_id = intval($_POST['user_id'] ?? 0);
        
        if (!$user_id) {
            wp_send_json_error(__('Invalid user ID.', 'psource-chat'));
        }
        
        delete_user_meta($user_id, 'psource_chat_banned');
        
        wp_send_json_success(__('User unbanned successfully.', 'psource-chat'));
    }
    
    /**
     * Update user permissions via AJAX
     */
    public function update_user_permissions() {
        check_ajax_referer('psource_chat_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'psource-chat'));
        }
        
        $user_id = intval($_POST['user_id'] ?? 0);
        $permissions = $_POST['permissions'] ?? [];
        
        if (!$user_id) {
            wp_send_json_error(__('Invalid user ID.', 'psource-chat'));
        }
        
        $moderator_data = [
            'assigned_by' => get_current_user_id(),
            'assigned_date' => current_time('mysql'),
            'permissions' => $permissions
        ];
        
        update_user_meta($user_id, 'psource_chat_moderator', $moderator_data);
        
        wp_send_json_success(__('User permissions updated successfully.', 'psource-chat'));
    }
}
