# PS Chat Hooks & Actions Reference

Complete reference of all available hooks, actions, and filters in PS Chat.

## Filters (apply_filters)

### Message Processing

#### `psource_chat_display_message`
**File**: `includes/class-psource-chat.php:4844`
**Description**: Filters message content before displaying in chat
**Parameters**: 
- `$message` (string) - The message content
- `$row` (array) - Complete message row data from database

```php
$message = apply_filters( 'psource_chat_display_message', $message, $row );
```

**Use Cases**:
- Add custom message formatting
- Parse custom shortcodes
- Add emoji support
- Filter inappropriate content
- Add user mentions (@username)

**Example**:
```php
function add_mention_support( $message, $row ) {
    // Convert @username to links
    $message = preg_replace(
        '/@([a-zA-Z0-9_]+)/',
        '<span class="mention">@$1</span>',
        $message
    );
    return $message;
}
add_filter( 'psource_chat_display_message', 'add_mention_support', 10, 2 );
```

#### `psource_chat_before_save_message`
**File**: `includes/class-psource-chat.php:5129`
**Description**: Filters message content before saving to database
**Parameters**:
- `$chat_message` (string) - Message content
- `$chat_session` (array) - Chat session data

```php
$chat_message = apply_filters( 'psource_chat_before_save_message', $chat_message, $chat_session );
```

**Use Cases**:
- Sanitize user input
- Convert shortcodes before storage
- Add timestamps
- Log messages to external systems
- Implement message encryption

**Example**:
```php
function encrypt_sensitive_messages( $message, $session ) {
    if ( $session['session_type'] === 'private' ) {
        // Simple encryption example (use proper encryption in production)
        $message = base64_encode( $message );
    }
    return $message;
}
add_filter( 'psource_chat_before_save_message', 'encrypt_sensitive_messages', 10, 2 );
```

### User Management

#### `psource-chat-user-statuses`
**File**: `includes/class-psource-chat.php:1011`
**Description**: Filters available user statuses
**Parameters**:
- `$user_statuses` (array) - Array of user status definitions

```php
$this->_chat_options_defaults['user-statuses'] = apply_filters( 'psource-chat-user-statuses', $this->_chat_options_defaults['user-statuses'] );
```

**Default Statuses**:
- `available` - User is available
- `away` - User is away
- `busy` - User is busy

**Example**:
```php
function add_custom_user_statuses( $statuses ) {
    $statuses['lunch'] = array(
        'label' => __( 'At Lunch', 'textdomain' ),
        'class' => 'psource-chat-status-lunch',
        'icon'  => '🍽️'
    );
    
    $statuses['meeting'] = array(
        'label' => __( 'In Meeting', 'textdomain' ),
        'class' => 'psource-chat-status-meeting',
        'icon'  => '📞'
    );
    
    return $statuses;
}
add_filter( 'psource-chat-user-statuses', 'add_custom_user_statuses' );
```

#### `psource-chat-options-defaults`
**File**: `includes/class-psource-chat.php:1087`
**Description**: Filters default user meta options
**Parameters**:
- `$option_type` (string) - Type of option (always 'user_meta')
- `$user_meta` (array) - User meta defaults

```php
$this->_chat_options_defaults['user_meta'] = apply_filters( 'psource-chat-options-defaults', 'user_meta', $this->_chat_options_defaults['user_meta'] );
```

**Example**:
```php
function customize_user_meta_defaults( $option_type, $user_meta ) {
    if ( $option_type === 'user_meta' ) {
        $user_meta['custom_field'] = 'default_value';
        $user_meta['notification_sound'] = 'enabled';
    }
    return $user_meta;
}
add_filter( 'psource-chat-options-defaults', 'customize_user_meta_defaults', 10, 2 );
```

### Emoji System

#### `psource_chat_emoji_categories`
**File**: `includes/class-psource-chat-emoji.php:203`
**Description**: Filters emoji categories and emoji definitions
**Parameters**:
- `$emoji_categories` (array) - Array of emoji categories

```php
$this->emoji_categories = apply_filters( 'psource_chat_emoji_categories', $this->emoji_categories );
```

**Example**:
```php
function add_company_emojis( $categories ) {
    $categories['company'] = array(
        'label' => __( 'Company', 'textdomain' ),
        'emojis' => array(
            'logo' => array(
                'code' => ':company_logo:',
                'image' => get_template_directory_uri() . '/images/company-logo.png',
                'alt' => 'Company Logo'
            ),
            'product' => array(
                'code' => ':our_product:',
                'image' => get_template_directory_uri() . '/images/product-icon.png',
                'alt' => 'Our Product'
            )
        )
    );
    return $categories;
}
add_filter( 'psource_chat_emoji_categories', 'add_company_emojis' );
```

### Integration Filters

#### `bp_get_group_name` (BuddyPress)
**File**: `includes/class-psource-chat.php:3436`
**Description**: BuddyPress integration for group names
**Usage**: Automatic integration with BuddyPress groups

#### `wc_chat_decline_message` (WooCommerce)
**File**: `includes/class-psource-chat.php:5283`
**Description**: Filters chat decline message in WooCommerce context
**Parameters**:
- `$error_text` (string) - The decline message

```php
$reply_data['sessions'][ $chat_id ]['errorText'] = apply_filters( 'wc_chat_decline_message', $reply_data['sessions'][ $chat_id ]['errorText'] );
```

### Session Management

#### `chat_logs_show_session`
**File**: `includes/class-psource-chat.php:7138`
**Description**: Filters chat session data for logs display
**Parameters**:
- `$chat_session` (array) - Chat session data

```php
$chat_session_after = apply_filters( 'chat_logs_show_session', $chat_session );
```

**Example**:
```php
function customize_chat_logs( $session ) {
    // Add additional data for logs
    $session['custom_data'] = get_option( 'custom_chat_data_' . $session['id'] );
    
    // Filter sensitive information
    if ( isset( $session['private_notes'] ) && ! current_user_can( 'manage_options' ) ) {
        unset( $session['private_notes'] );
    }
    
    return $session;
}
add_filter( 'chat_logs_show_session', 'customize_chat_logs' );
```

## Custom Actions (Hooks you can add)

While PS Chat doesn't define many custom actions, you can add them to extend functionality:

### Session Events

```php
// Add these to class-psource-chat.php in appropriate methods

// When session is created
do_action( 'psource_chat_session_created', $session_id, $session_data );

// When session status changes
do_action( 'psource_chat_session_status_changed', $session_id, $old_status, $new_status );

// When session is closed
do_action( 'psource_chat_session_closed', $session_id, $session_data );
```

### Message Events

```php
// When message is sent
do_action( 'psource_chat_message_sent', $message_id, $message_data, $session_id );

// When message is received
do_action( 'psource_chat_message_received', $message_id, $message_data, $session_id );

// When message is deleted
do_action( 'psource_chat_message_deleted', $message_id, $session_id );
```

### User Events

```php
// When user joins chat
do_action( 'psource_chat_user_joined', $user_id, $session_id );

// When user leaves chat
do_action( 'psource_chat_user_left', $user_id, $session_id );

// When user status changes
do_action( 'psource_chat_user_status_changed', $user_id, $old_status, $new_status );
```

### Upload Events

```php
// When file is uploaded
do_action( 'psource_chat_file_uploaded', $file_id, $file_data, $session_id );

// When file is deleted
do_action( 'psource_chat_file_deleted', $file_id, $session_id );

// Before file cleanup
do_action( 'psource_chat_before_file_cleanup', $session_id );
```

## WordPress Core Actions Used

### Admin Actions

```php
// Admin menu setup
add_action( 'admin_menu', array( $this, 'chat_admin_menu_setup' ) );

// Admin scripts and styles
add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

// Admin init
add_action( 'admin_init', array( $this, 'admin_init' ) );
```

### Frontend Actions

```php
// Frontend scripts and styles
add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

// Footer scripts
add_action( 'wp_footer', array( $this, 'footer_scripts' ) );

// Init hook
add_action( 'init', array( $this, 'init' ) );
```

### AJAX Actions

```php
// Chat AJAX endpoints
add_action( 'wp_ajax_psource_chat_session_get_messages', array( $this, 'ajax_session_get_messages' ) );
add_action( 'wp_ajax_psource_chat_session_enqueue_message', array( $this, 'ajax_session_enqueue_message' ) );
add_action( 'wp_ajax_psource_chat_upload_file', array( $upload_handler, 'handle_upload' ) );
add_action( 'wp_ajax_psource_chat_download_file', array( $upload_handler, 'handle_download' ) );

// Non-logged in users (if enabled)
add_action( 'wp_ajax_nopriv_psource_chat_session_get_messages', array( $this, 'ajax_session_get_messages' ) );
```

## Custom Hooks Usage Examples

### Logging System

```php
function setup_chat_logging() {
    // Log all messages
    add_action( 'psource_chat_message_sent', 'log_chat_message', 10, 3 );
    add_action( 'psource_chat_message_received', 'log_chat_message', 10, 3 );
    
    // Log session events
    add_action( 'psource_chat_session_created', 'log_session_event', 10, 2 );
    add_action( 'psource_chat_session_closed', 'log_session_event', 10, 2 );
}

function log_chat_message( $message_id, $message_data, $session_id ) {
    error_log( sprintf(
        'Chat Message: Session %s, User %s, Message: %s',
        $session_id,
        $message_data['user_id'],
        substr( $message_data['message'], 0, 100 )
    ));
}

function log_session_event( $session_id, $session_data ) {
    $action = current_action();
    error_log( sprintf(
        'Chat Session Event: %s for session %s',
        $action,
        $session_id
    ));
}

add_action( 'init', 'setup_chat_logging' );
```

### Notification System

```php
function setup_chat_notifications() {
    add_action( 'psource_chat_message_sent', 'send_message_notification', 10, 3 );
}

function send_message_notification( $message_id, $message_data, $session_id ) {
    // Get session participants
    $participants = get_chat_session_participants( $session_id );
    
    foreach ( $participants as $user_id ) {
        // Skip sender
        if ( $user_id == $message_data['user_id'] ) {
            continue;
        }
        
        // Check if user wants notifications
        $wants_notifications = get_user_meta( $user_id, 'chat_notifications', true );
        if ( $wants_notifications !== 'disabled' ) {
            // Send email notification
            wp_mail(
                get_userdata( $user_id )->user_email,
                'New Chat Message',
                'You have a new message in chat session ' . $session_id
            );
        }
    }
}

add_action( 'init', 'setup_chat_notifications' );
```

### Integration with External APIs

```php
function setup_external_chat_sync() {
    add_action( 'psource_chat_message_sent', 'sync_to_external_api', 10, 3 );
}

function sync_to_external_api( $message_id, $message_data, $session_id ) {
    // Send to Slack, Discord, or other chat platforms
    $webhook_url = get_option( 'external_chat_webhook' );
    
    if ( $webhook_url ) {
        wp_remote_post( $webhook_url, array(
            'body' => json_encode( array(
                'session_id' => $session_id,
                'user' => get_userdata( $message_data['user_id'] )->display_name,
                'message' => $message_data['message'],
                'timestamp' => current_time( 'mysql' )
            )),
            'headers' => array(
                'Content-Type' => 'application/json'
            )
        ));
    }
}

add_action( 'init', 'setup_external_chat_sync' );
```

---

This reference provides comprehensive coverage of all hooks and filters available in PS Chat, along with practical examples for extending functionality.
