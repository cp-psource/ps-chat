# PS Chat Developer Documentation

Welcome to the PS Chat developer documentation! This section provides comprehensive guides for developers who want to extend, customize, or integrate with PS Chat.

## 📚 Documentation Overview

### [🚀 Developer Guide](DEVELOPER-GUIDE.md)
**Complete developer guide covering:**
- Plugin architecture overview
- Main classes and their purposes
- Database schema documentation
- Upload system details
- Security considerations
- Performance optimization tips

### [🔗 Hooks & Actions Reference](HOOKS-REFERENCE.md) 
**Comprehensive reference for all available hooks:**
- Complete list of filters (`apply_filters`)
- Available actions (`do_action`)
- Custom hook examples
- Integration patterns
- Real-world usage scenarios

### [⚡ JavaScript API Reference](JAVASCRIPT-API.md)
**Frontend development guide:**
- Global objects (`psource_chat`, `PSChatUpload`)
- Core JavaScript API methods
- Custom events and event handling
- AJAX integration examples
- Error handling patterns

### [🔧 Integration Examples](INTEGRATION-EXAMPLES.md)
**Real-world integration examples:**
- WooCommerce integration (product support, order help)
- BuddyPress integration (group chats, private messaging)
- Custom user roles and permissions
- External API integration (Slack, Discord, CRM)
- Custom message types and components
- Mobile app integration
- Analytics tracking
- Multi-language support

## 🎯 Quick Start for Developers

### Basic Plugin Extension

```php
<?php
// Basic PS Chat extension plugin
/*
Plugin Name: My PS Chat Extension
Description: Custom extensions for PS Chat
Version: 1.0
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class My_PSChat_Extension {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // Add custom message filter
        add_filter('psource_chat_display_message', array($this, 'custom_message_filter'), 10, 2);
        
        // Add custom user status
        add_filter('psource-chat-user-statuses', array($this, 'add_custom_status'));
        
        // Enqueue custom scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    public function custom_message_filter($message, $row) {
        // Add custom message processing
        $message = str_replace('[custom]', '<strong>Custom</strong>', $message);
        return $message;
    }
    
    public function add_custom_status($statuses) {
        $statuses['lunch'] = array(
            'label' => 'At Lunch',
            'class' => 'status-lunch'
        );
        return $statuses;
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script(
            'my-chat-extension',
            plugin_dir_url(__FILE__) . 'js/extension.js',
            array('jquery'),
            '1.0',
            true
        );
    }
}

new My_PSChat_Extension();
?>
```

### Basic JavaScript Extension

```javascript
// Custom chat functionality
jQuery(document).ready(function($) {
    
    // Listen for new messages
    $(document).on('psource_chat_message_received', function(event, data) {
        console.log('New message received:', data);
        
        // Custom processing
        if (data.message.includes('urgent')) {
            // Highlight urgent messages
            $('.chat-message[data-message-id="' + data.message_id + '"]')
                .addClass('urgent-message');
        }
    });
    
    // Add custom button to chat interface
    $('.psource-chat-box').each(function() {
        var $chatBox = $(this);
        var customButton = '<button class="custom-chat-action">Custom Action</button>';
        $chatBox.find('.psource-chat-actions').append(customButton);
    });
    
    // Handle custom button click
    $(document).on('click', '.custom-chat-action', function() {
        var $chatBox = $(this).closest('.psource-chat-box');
        var sessionId = $chatBox.attr('id').replace('psource-chat-box-', '');
        
        // Send custom message
        psource_chat.chat_session_enqueue_message('Custom action triggered!', sessionId);
    });
});
```

## 🛠️ Development Tools

### Debugging

Enable debug mode in `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('PSOURCE_CHAT_DEBUG', true);
```

### Database Queries

Useful queries for development:
```sql
-- View all chat sessions
SELECT * FROM wp_psource_chat_sessions;

-- View messages for a specific session
SELECT * FROM wp_psource_chat_messages WHERE session_id = 123;

-- View upload files
SELECT * FROM wp_psource_chat_uploads;
```

### Testing Hooks

```php
// Test all available filters
function debug_chat_filters() {
    $test_message = 'Test message [upload:123] with content';
    $test_row = array('id' => 1, 'user_id' => 1, 'session_id' => 'test');
    
    $filtered_message = apply_filters('psource_chat_display_message', $test_message, $test_row);
    error_log('Filtered message: ' . $filtered_message);
}
add_action('init', 'debug_chat_filters');
```

## 📋 Common Use Cases

### 1. **Custom Message Types**
- Product references in WooCommerce
- Appointment booking widgets
- Survey integration
- FAQ suggestions

### 2. **User Management**
- Custom user roles (moderators, support agents)
- Permission-based features
- User status extensions

### 3. **External Integrations**
- CRM synchronization (HubSpot, Salesforce)
- Team communication (Slack, Discord)
- Analytics tracking (Google Analytics)
- Email notifications

### 4. **Mobile Support**
- REST API endpoints
- Push notifications
- Progressive Web App features

### 5. **Multi-language**
- WPML integration
- Auto-translation features
- Language-specific chat rooms

## 🔒 Security Best Practices

1. **Always sanitize input:**
   ```php
   $message = sanitize_text_field($_POST['message']);
   ```

2. **Verify nonces:**
   ```php
   if (!wp_verify_nonce($_POST['nonce'], 'chat_action')) {
       wp_die('Security check failed');
   }
   ```

3. **Check permissions:**
   ```php
   if (!current_user_can('participate_in_chat')) {
       wp_send_json_error('Insufficient permissions');
   }
   ```

4. **Validate file uploads:**
   ```php
   $allowed_types = array('jpg', 'png', 'pdf');
   $file_ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
   if (!in_array($file_ext, $allowed_types)) {
       wp_die('File type not allowed');
   }
   ```

## 📞 Support & Contributing

- **Issues**: Report bugs and feature requests in the plugin repository
- **Documentation**: Contribute to documentation improvements
- **Code**: Submit pull requests for bug fixes and enhancements
- **Testing**: Help test new features and report compatibility issues

## 🗂️ File Structure Reference

```
ps-chat/
├── includes/
│   ├── class-psource-chat.php          # Main plugin class
│   ├── class-psource-chat-upload.php   # Upload handling
│   ├── class-psource-chat-avatar.php   # Avatar management
│   └── class-psource-chat-emoji.php    # Emoji system
├── js/
│   ├── psource-chat.js                 # Main JavaScript
│   └── psource-chat-upload.js          # Upload JavaScript
├── css/
│   └── psource-chat-style.css          # Main styles
├── lib/
│   ├── psource_chat_admin_panels.php   # Admin interface
│   └── psource_chat_utilities.php      # Utility functions
└── docs/                               # This documentation
```

---

**Happy coding!** 🚀

For specific questions or advanced integration needs, refer to the detailed guides above or examine the plugin source code for implementation examples.