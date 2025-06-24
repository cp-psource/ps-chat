<?php
/**
 * Shortcode Handler
 * 
 * @package PSSource\Chat\Frontend
 */

namespace PSSource\Chat\Frontend;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles chat shortcodes
 */
class Shortcode_Handler {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_shortcode('psource_chat', [$this, 'render_chat']);
        add_shortcode('chat', [$this, 'render_chat']); // Legacy support
    }
    
    /**
     * Render chat shortcode
     */
    public function render_chat($atts, $content = null) {
        // Don't render in admin or feeds
        if (is_admin() || is_feed()) {
            return '';
        }
        
        $atts = shortcode_atts([
            'session_type' => 'page',
            'session_id' => '',
            'width' => '100%',
            'height' => '400px',
            'theme' => 'default',
            'show_users' => 'true',
            'allow_private' => 'false',
            'moderator_only' => 'false'
        ], $atts, 'psource_chat');
        
        // Check permissions
        if ($atts['moderator_only'] === 'true' && !current_user_can('moderate_chat')) {
            return '<p>' . __('This chat is only available for moderators.', 'psource-chat') . '</p>';
        }
        
        // Check if user can access chat
        if (!is_user_logged_in()) {
            $plugin = \PSSource\Chat\Core\Plugin::get_instance();
            if (!$plugin->get_option('allow_guest_chat', false)) {
                return $this->render_login_prompt();
            }
        }
        
        // Generate unique session ID if not provided
        if (empty($atts['session_id'])) {
            switch ($atts['session_type']) {
                case 'page':
                    $atts['session_id'] = 'page-' . get_the_ID();
                    break;
                case 'post':
                    $atts['session_id'] = 'post-' . get_the_ID();
                    break;
                case 'category':
                    $category = get_queried_object();
                    $atts['session_id'] = 'category-' . ($category ? $category->term_id : 'unknown');
                    break;
                case 'global':
                    $atts['session_id'] = 'global-site';
                    break;
                default:
                    $atts['session_id'] = 'custom-' . uniqid();
            }
        }
        
        $renderer = new Chat_Renderer();
        return $renderer->render_shortcode_chat($atts);
    }
    
    /**
     * Render login prompt
     */
    private function render_login_prompt() {
        ob_start();
        ?>
        <div class="psource-chat-login-required">
            <div class="psource-chat-login-box">
                <h4><?php _e('Login Required', 'psource-chat'); ?></h4>
                <p><?php _e('You need to be logged in to participate in this chat.', 'psource-chat'); ?></p>
                <div class="psource-chat-login-actions">
                    <a href="<?php echo wp_login_url(get_permalink()); ?>" class="button button-primary">
                        <?php _e('Log In', 'psource-chat'); ?>
                    </a>
                    <?php if (get_option('users_can_register')): ?>
                        <a href="<?php echo wp_registration_url(); ?>" class="button button-secondary">
                            <?php _e('Register', 'psource-chat'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <style>
        .psource-chat-login-required {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            background: #f9f9f9;
            margin: 20px 0;
        }
        
        .psource-chat-login-box h4 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #333;
        }
        
        .psource-chat-login-box p {
            margin-bottom: 20px;
            color: #666;
        }
        
        .psource-chat-login-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .psource-chat-login-actions .button {
            text-decoration: none;
        }
        </style>
        <?php
        
        return ob_get_clean();
    }
}
