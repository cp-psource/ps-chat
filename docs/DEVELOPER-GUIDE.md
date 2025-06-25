# PS Chat Developer Guide

Comprehensive guide for developers who want to extend or integrate with PS Chat.

## Table of Contents

1. [Plugin Architecture](#plugin-architecture)
2. [Hooks & Filters](#hooks--filters)
3. [JavaScript API](#javascript-api)
4. [Database Schema](#database-schema)
5. [Upload System](#upload-system)
6. [Integration Examples](#integration-examples)
7. [Security Considerations](#security-considerations)
8. [Performance Tips](#performance-tips)

## Plugin Architecture

### Main Classes

#### `PSource_Chat`
The main plugin class handling all core functionality.

**Location**: `includes/class-psource-chat.php`

**Key Methods**:
- `chat_session_add()` - Creates new chat sessions
- `chat_session_enqueue_message()` - Adds messages to chat
- `chat_session_get_messages()` - Retrieves messages
- `chat_session_clear()` - Clears chat messages

#### `PSource_Chat_Upload`
Handles file uploads and media management.

**Location**: `includes/class-psource-chat-upload.php`

**Key Methods**:
- `handle_upload()` - Processes file uploads
- `cleanup_session_files()` - Removes session files
- `render_upload_preview()` - Generates media previews

#### `PSource_Chat_Avatar`
Manages user avatars and profile images.

**Location**: `includes/class-psource-chat-avatar.php`

#### `PSource_Chat_Emoji`
Handles emoji support and emoji picker.

**Location**: `includes/class-psource-chat-emoji.php`

## Hooks & Filters

### Filters

#### Message Processing

##### `psource_chat_display_message`
Filters the message content before display.

```php
apply_filters( 'psource_chat_display_message', $message, $row );
```

**Parameters**:
- `$message` (string) - The message content
- `$row` (array) - Complete message row data

**Example**:
```php
function my_custom_message_filter( $message, $row ) {
    // Add custom formatting or content filtering
    $message = str_replace( '[custom]', '<strong>Custom</strong>', $message );
    return $message;
}
add_filter( 'psource_chat_display_message', 'my_custom_message_filter', 10, 2 );
```

##### `psource_chat_before_save_message`
Filters message content before saving to database.

```php
apply_filters( 'psource_chat_before_save_message', $chat_message, $chat_session );
```

**Parameters**:
- `$chat_message` (string) - Message content
- `$chat_session` (array) - Chat session data

**Example**:
```php
function filter_message_before_save( $message, $session ) {
    // Remove unwanted content or add preprocessing
    $message = strip_tags( $message, '<b><i><em><strong>' );
    return $message;
}
add_filter( 'psource_chat_before_save_message', 'filter_message_before_save', 10, 2 );
```

#### User Management

##### `psource-chat-user-statuses`
Filters available user statuses.

```php
apply_filters( 'psource-chat-user-statuses', $user_statuses );
```

**Example**:
```php
function add_custom_user_status( $statuses ) {
    $statuses['busy'] = array(
        'label' => 'Busy',
        'class' => 'psource-chat-status-busy'
    );
    return $statuses;
}
add_filter( 'psource-chat-user-statuses', 'add_custom_user_status' );
```

##### `psource-chat-options-defaults`
Filters default user meta options.

```php
apply_filters( 'psource-chat-options-defaults', 'user_meta', $user_meta );
```

#### Emoji System

##### `psource_chat_emoji_categories`
Filters emoji categories and emojis.

```php
apply_filters( 'psource_chat_emoji_categories', $emoji_categories );
```

**Example**:
```php
function add_custom_emoji_category( $categories ) {
    $categories['custom'] = array(
        'label' => 'Custom',
        'emojis' => array(
            'company_logo' => array(
                'code' => ':company:',
                'image' => 'path/to/company-logo.png'
            )
        )
    );
    return $categories;
}
add_filter( 'psource_chat_emoji_categories', 'add_custom_emoji_category' );
```

#### Session Management

##### `chat_logs_show_session`
Filters chat session data for logs display.

```php
apply_filters( 'chat_logs_show_session', $chat_session );
```

#### Error Messages

##### `wc_chat_decline_message`
Filters chat decline message (WooCommerce integration).

```php
apply_filters( 'wc_chat_decline_message', $error_text );
```

### Actions

While PS Chat primarily uses filters, you can hook into WordPress core actions to extend functionality:

#### Session Events

```php
// Hook into chat session creation
function on_chat_session_created( $session_id ) {
    // Your custom logic
    error_log( "New chat session created: " . $session_id );
}

// You would need to add this action call to the plugin
// do_action( 'psource_chat_session_created', $session_id );
```

## JavaScript API

### Global Object: `psource_chat`

The main JavaScript object providing chat functionality.

#### Key Methods

##### `chat_session_enqueue_message(message, session_id)`
Sends a message to the chat.

```javascript
psource_chat.chat_session_enqueue_message('Hello World!', 'session_123');
```

##### `chat_session_get_messages(session_id)`
Retrieves messages for a session.

```javascript
psource_chat.chat_session_get_messages('session_123');
```

##### `chat_session_site_min(chat_id)`
Minimizes a chat window.

```javascript
psource_chat.chat_session_site_min('session_123');
```

##### `chat_session_site_max(chat_id)`
Maximizes a chat window.

```javascript
psource_chat.chat_session_site_max('session_123');
```

### Upload System API

#### Global Object: `PSChatUpload`

##### `addToUploadQueue(file, sessionId, $chatBox)`
Adds a file to the upload queue.

```javascript
PSChatUpload.addToUploadQueue(fileObject, 'session_123', jQuery('#chat-box'));
```

##### `processQueueOnSend($chatBox, callback)`
Processes upload queue when sending messages.

```javascript
PSChatUpload.processQueueOnSend(jQuery('#chat-box'), function(uploadReferences) {
    console.log('Uploads completed:', uploadReferences);
});
```

### Custom JavaScript Events

You can listen for custom events:

```javascript
// Listen for new messages
jQuery(document).on('psource_chat_new_message', function(event, data) {
    console.log('New message received:', data);
});

// Listen for upload completion
jQuery(document).on('psource_chat_upload_complete', function(event, data) {
    console.log('Upload completed:', data);
});
```

## Database Schema

### Main Tables

#### `wp_psource_chat_sessions`
Stores chat session information.

```sql
CREATE TABLE wp_psource_chat_sessions (
    id int(11) NOT NULL AUTO_INCREMENT,
    blog_id int(11) NOT NULL,
    session_type varchar(255) NOT NULL,
    session_status varchar(255) DEFAULT 'open',
    session_created datetime NOT NULL,
    session_updated datetime NOT NULL,
    PRIMARY KEY (id)
);
```

#### `wp_psource_chat_messages`
Stores chat messages.

```sql
CREATE TABLE wp_psource_chat_messages (
    id int(11) NOT NULL AUTO_INCREMENT,
    session_id int(11) NOT NULL,
    user_id int(11) NOT NULL,
    message text NOT NULL,
    timestamp datetime NOT NULL,
    message_type varchar(50) DEFAULT 'user',
    PRIMARY KEY (id),
    KEY session_id (session_id),
    KEY user_id (user_id)
);
```

#### `wp_psource_chat_uploads`
Stores file upload information.

```sql
CREATE TABLE wp_psource_chat_uploads (
    id int(11) NOT NULL AUTO_INCREMENT,
    session_id int(11) NOT NULL,
    user_id int(11) NOT NULL,
    original_name varchar(255) NOT NULL,
    stored_name varchar(255) NOT NULL,
    file_path varchar(500) NOT NULL,
    mime_type varchar(100) NOT NULL,
    file_size int(11) NOT NULL,
    upload_time datetime NOT NULL,
    PRIMARY KEY (id),
    KEY session_id (session_id)
);
```

## Upload System

### Configuration

Upload settings are configured in the admin panel and accessible via:

```php
$upload_settings = get_option( 'psource_chat_upload_settings', array() );
```

### File Processing

#### Allowed File Types
Configured via admin settings, default includes:
- Images: jpg, jpeg, png, gif, webp
- Videos: mp4, webm, ogg
- Documents: pdf, doc, docx, txt
- Archives: zip

#### Storage Location
Files are stored in: `wp-content/uploads/psource-chat/uploads/`

#### Security Features
- File type validation
- Size limit enforcement
- Unique file naming
- Orphaned file cleanup

### Custom Upload Handling

```php
function handle_custom_file_type( $file_path, $session_id ) {
    // Custom processing for specific file types
    if ( pathinfo( $file_path, PATHINFO_EXTENSION ) === 'custom' ) {
        // Your custom logic here
        return process_custom_file( $file_path );
    }
    return false;
}

// Hook into upload process (you would need to add this hook to the plugin)
add_action( 'psource_chat_before_file_save', 'handle_custom_file_type', 10, 2 );
```

## Integration Examples

### WooCommerce Integration

```php
// Add product info to chat messages
function add_product_to_chat() {
    global $product;
    if ( is_product() && $product ) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Add product shortcut to chat
            $('.psource-chat-send').before(
                '<button class="add-product-to-chat" data-product="<?php echo $product->get_id(); ?>">Add Product</button>'
            );
            
            $('.add-product-to-chat').click(function() {
                var productId = $(this).data('product');
                var message = '[product:' + productId + ']';
                var sessionId = 'woocommerce_support';
                psource_chat.chat_session_enqueue_message(message, sessionId);
            });
        });
        </script>
        <?php
    }
}
add_action( 'wp_footer', 'add_product_to_chat' );
```

### BuddyPress Integration

```php
// Integrate with BuddyPress groups
function integrate_bp_groups_with_chat() {
    if ( function_exists( 'bp_is_group' ) && bp_is_group() ) {
        $group_id = bp_get_current_group_id();
        $chat_session_id = 'bp-group-' . $group_id;
        
        // Initialize group chat
        echo '<div id="group-chat-' . $group_id . '" class="group-chat-widget">';
        // Chat initialization code here
        echo '</div>';
    }
}
add_action( 'bp_after_group_header', 'integrate_bp_groups_with_chat' );
```

### Custom User Roles

```php
// Add moderator capabilities
function add_chat_moderator_caps() {
    $role = get_role( 'chat_moderator' );
    if ( ! $role ) {
        add_role( 'chat_moderator', 'Chat Moderator', array(
            'read' => true,
            'manage_chat_sessions' => true,
            'moderate_chat_messages' => true,
            'view_chat_logs' => true
        ));
    }
}
add_action( 'init', 'add_chat_moderator_caps' );

// Check moderator permissions
function user_can_moderate_chat( $user_id = null ) {
    if ( ! $user_id ) {
        $user_id = get_current_user_id();
    }
    return user_can( $user_id, 'moderate_chat_messages' );
}
```

## Security Considerations

### Input Sanitization

Always sanitize user input:

```php
function sanitize_chat_message( $message ) {
    // Remove dangerous HTML
    $message = wp_kses( $message, array(
        'b' => array(),
        'i' => array(),
        'em' => array(),
        'strong' => array(),
        'a' => array( 'href' => array() )
    ));
    
    // Escape output
    return esc_html( $message );
}
```

### File Upload Security

- Validate file types server-side
- Check file headers, not just extensions
- Scan for malicious content
- Limit file sizes
- Use unique file names

### AJAX Security

Always verify nonces in AJAX requests:

```php
function secure_chat_ajax_handler() {
    // Verify nonce
    if ( ! wp_verify_nonce( $_POST['nonce'], 'psource_chat_nonce' ) ) {
        wp_die( 'Security check failed' );
    }
    
    // Verify user permissions
    if ( ! current_user_can( 'participate_in_chat' ) ) {
        wp_die( 'Insufficient permissions' );
    }
    
    // Process request
}
```

## Performance Tips

### Database Optimization

1. **Index Important Columns**:
   ```sql
   ALTER TABLE wp_psource_chat_messages ADD INDEX idx_session_timestamp (session_id, timestamp);
   ```

2. **Limit Message History**:
   ```php
   // Only load recent messages
   $messages = $chat->get_messages( $session_id, array(
       'limit' => 50,
       'order' => 'DESC'
   ));
   ```

3. **Clean Old Data**:
   ```php
   // Remove old messages periodically
   function cleanup_old_chat_messages() {
       global $wpdb;
       $wpdb->query( $wpdb->prepare(
           "DELETE FROM wp_psource_chat_messages WHERE timestamp < %s",
           date( 'Y-m-d H:i:s', strtotime( '-30 days' ) )
       ));
   }
   add_action( 'wp_scheduled_delete', 'cleanup_old_chat_messages' );
   ```

### JavaScript Optimization

1. **Throttle AJAX Requests**:
   ```javascript
   var messageCheckThrottle = false;
   function checkForNewMessages() {
       if ( messageCheckThrottle ) return;
       messageCheckThrottle = true;
       
       // Your AJAX call here
       
       setTimeout(function() {
           messageCheckThrottle = false;
       }, 1000);
   }
   ```

2. **Use Event Delegation**:
   ```javascript
   // Instead of binding to each element
   jQuery(document).on('click', '.chat-message-button', function() {
       // Handle click
   });
   ```

### Caching

```php
// Cache chat sessions
function get_cached_chat_session( $session_id ) {
    $cache_key = 'chat_session_' . $session_id;
    $session = wp_cache_get( $cache_key, 'psource_chat' );
    
    if ( false === $session ) {
        $session = load_chat_session_from_db( $session_id );
        wp_cache_set( $cache_key, $session, 'psource_chat', 300 ); // 5 minutes
    }
    
    return $session;
}
```

---

**Need Help?**

If you need assistance with PS Chat development:

1. Check the existing code examples in the plugin files
2. Review the admin settings for configuration options  
3. Test changes in a development environment first
4. Follow WordPress coding standards
5. Consider performance implications of custom modifications

Happy coding! 🚀
