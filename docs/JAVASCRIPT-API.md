# PS Chat JavaScript API Reference

Complete reference for the PS Chat JavaScript API and custom integration.

## Global Objects

### `psource_chat`
Main chat functionality object.

### `PSChatUpload`
File upload functionality object.

### `psource_chat_localized`
Localized data from PHP (AJAX URL, nonces, settings).

## Core JavaScript API

### psource_chat Object Methods

#### Message Handling

##### `chat_session_enqueue_message(message, session_id)`
Sends a message to the specified chat session.

**Parameters**:
- `message` (string) - The message content
- `session_id` (string) - The chat session ID

**Example**:
```javascript
// Send a simple message
psource_chat.chat_session_enqueue_message('Hello World!', 'session_123');

// Send a message with media reference
psource_chat.chat_session_enqueue_message('Check this out: [upload:456]', 'session_123');
```

##### `chat_session_get_messages(session_id)`
Retrieves messages for a session (usually called automatically by polling).

**Parameters**:
- `session_id` (string) - The chat session ID

**Returns**: AJAX promise

```javascript
psource_chat.chat_session_get_messages('session_123')
    .done(function(response) {
        console.log('Messages loaded:', response);
    });
```

#### Session Management

##### `chat_session_site_min(chat_id)`
Minimizes a chat window.

**Parameters**:
- `chat_id` (string) - The chat ID

```javascript
psource_chat.chat_session_site_min('session_123');
```

##### `chat_session_site_max(chat_id)`
Maximizes a chat window.

**Parameters**:
- `chat_id` (string) - The chat ID

```javascript
psource_chat.chat_session_site_max('session_123');
```

##### `chat_session_site_change_minmax(chat_id, event)`
Toggles between minimized and maximized state.

**Parameters**:
- `chat_id` (string) - The chat ID
- `event` (Event) - The triggering event

```javascript
jQuery('.minimize-button').click(function(e) {
    psource_chat.chat_session_site_change_minmax('session_123', e);
});
```

#### User Management

##### `chat_session_moderate_user(chat_id, user_id, action)`
Performs moderation actions on users.

**Parameters**:
- `chat_id` (string) - The chat ID
- `user_id` (string) - The user ID to moderate
- `action` (string) - Moderation action (kick, ban, etc.)

```javascript
// Kick a user from chat
psource_chat.chat_session_moderate_user('session_123', '456', 'kick');
```

#### Settings Management

##### `chat_session_site_change_sound(event)`
Toggles sound notifications for a chat session.

**Parameters**:
- `event` (Event) - The triggering event

```javascript
jQuery('.sound-toggle').click(function(e) {
    psource_chat.chat_session_site_change_sound(e);
});
```

### Upload System API

#### PSChatUpload Object Methods

##### `init()`
Initializes the upload system. Called automatically when upload system is enabled.

```javascript
PSChatUpload.init();
```

##### `addToUploadQueue(file, sessionId, $chatBox)`
Adds a file to the upload queue without uploading immediately.

**Parameters**:
- `file` (File) - JavaScript File object
- `sessionId` (string) - Chat session ID
- `$chatBox` (jQuery) - Chat box jQuery element

```javascript
// Add file from file input
document.getElementById('file-input').addEventListener('change', function(e) {
    var files = e.target.files;
    var $chatBox = jQuery('#psource-chat-box-session_123');
    
    for (var i = 0; i < files.length; i++) {
        PSChatUpload.addToUploadQueue(files[i], 'session_123', $chatBox);
    }
});
```

##### `processQueueOnSend($chatBox, callback)`
Processes all queued uploads for a chat box when sending a message.

**Parameters**:
- `$chatBox` (jQuery) - Chat box jQuery element
- `callback` (Function) - Callback function that receives upload references

```javascript
PSChatUpload.processQueueOnSend(jQuery('#psource-chat-box-session_123'), function(uploadReferences) {
    if (uploadReferences && uploadReferences.length > 0) {
        console.log('Files uploaded:', uploadReferences);
        // uploadReferences contains ['[upload:123]', '[upload:124]', ...]
    }
});
```

##### `cleanMessageText(messageText)`
Removes file name placeholders from message text.

**Parameters**:
- `messageText` (string) - Original message text with file placeholders

**Returns**: (string) Clean message text

```javascript
var originalText = "Hello! 📎 document.pdf\nHow are you?";
var cleanText = PSChatUpload.cleanMessageText(originalText);
// Result: "Hello!\nHow are you?"
```

##### `validateFile(file)`
Validates a file against allowed types and size limits.

**Parameters**:
- `file` (File) - JavaScript File object

**Returns**: Object with `valid` (boolean) and `error` (string) properties

```javascript
var file = document.getElementById('file-input').files[0];
var validation = PSChatUpload.validateFile(file);

if (validation.valid) {
    console.log('File is valid');
} else {
    console.log('File validation error:', validation.error);
}
```

## Custom Events

You can listen for custom events to extend functionality:

### Chat Events

```javascript
// Listen for new messages
jQuery(document).on('psource_chat_message_received', function(event, data) {
    console.log('New message:', data);
    // data contains: message, session_id, user_id, timestamp
});

// Listen for session state changes
jQuery(document).on('psource_chat_session_changed', function(event, data) {
    console.log('Session changed:', data);
    // data contains: session_id, old_state, new_state
});

// Listen for user status changes
jQuery(document).on('psource_chat_user_status_changed', function(event, data) {
    console.log('User status changed:', data);
    // data contains: user_id, old_status, new_status
});
```

### Upload Events

```javascript
// Listen for upload start
jQuery(document).on('psource_chat_upload_started', function(event, data) {
    console.log('Upload started:', data);
    // data contains: file_name, session_id, upload_id
});

// Listen for upload progress
jQuery(document).on('psource_chat_upload_progress', function(event, data) {
    console.log('Upload progress:', data);
    // data contains: upload_id, progress_percent
});

// Listen for upload completion
jQuery(document).on('psource_chat_upload_completed', function(event, data) {
    console.log('Upload completed:', data);
    // data contains: upload_id, file_data, session_id
});

// Listen for upload errors
jQuery(document).on('psource_chat_upload_error', function(event, data) {
    console.log('Upload error:', data);
    // data contains: upload_id, error_message
});
```

## AJAX Integration

### Direct AJAX Calls

You can make direct AJAX calls to chat endpoints:

```javascript
// Send message via direct AJAX
jQuery.ajax({
    url: psource_chat_localized.ajax_url,
    type: 'POST',
    data: {
        action: 'psource_chat_session_enqueue_message',
        message: 'Hello from AJAX!',
        session_id: 'session_123',
        nonce: psource_chat_localized.nonce
    },
    success: function(response) {
        if (response.success) {
            console.log('Message sent successfully');
        }
    }
});

// Get messages via direct AJAX
jQuery.ajax({
    url: psource_chat_localized.ajax_url,
    type: 'POST',
    data: {
        action: 'psource_chat_session_get_messages',
        session_id: 'session_123',
        nonce: psource_chat_localized.nonce
    },
    success: function(response) {
        if (response.success) {
            console.log('Messages:', response.data);
        }
    }
});
```

### Upload AJAX

```javascript
// Upload file via direct AJAX
var formData = new FormData();
formData.append('action', 'psource_chat_upload_file');
formData.append('file', fileObject);
formData.append('session_id', 'session_123');
formData.append('nonce', psource_chat_localized.nonce);

jQuery.ajax({
    url: psource_chat_localized.ajax_url,
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    xhr: function() {
        var xhr = new window.XMLHttpRequest();
        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                var percentComplete = (e.loaded / e.total) * 100;
                console.log('Upload progress:', percentComplete + '%');
            }
        }, false);
        return xhr;
    },
    success: function(response) {
        if (response.success) {
            console.log('File uploaded:', response.data);
        }
    }
});
```

## Custom Integrations

### Adding Custom Message Types

```javascript
// Custom message type handler
function handleCustomMessage(messageData) {
    if (messageData.message.startsWith('[product:')) {
        // Extract product ID
        var productId = messageData.message.match(/\[product:(\d+)\]/)[1];
        
        // Create custom display
        var productHtml = '<div class="chat-product-card" data-product="' + productId + '">' +
                         '<img src="/wp-content/uploads/product-' + productId + '.jpg" alt="Product">' +
                         '<div class="product-info">' +
                         '<h4>Product Name</h4>' +
                         '<p>Product description...</p>' +
                         '<button class="add-to-cart" data-product="' + productId + '">Add to Cart</button>' +
                         '</div></div>';
        
        return productHtml;
    }
    
    return messageData.message; // Return original if not a product message
}

// Hook into message display
jQuery(document).on('psource_chat_message_received', function(event, data) {
    var customMessage = handleCustomMessage(data);
    if (customMessage !== data.message) {
        // Replace message content in the chat
        jQuery('.chat-message[data-message-id="' + data.message_id + '"] .message-content')
            .html(customMessage);
    }
});
```

### Custom Chat Widget

```javascript
// Create a custom floating chat widget
function createCustomChatWidget() {
    var widget = jQuery('<div id="custom-chat-widget">' +
                       '<div class="chat-header">' +
                       '<span>Support Chat</span>' +
                       '<button class="close-chat">&times;</button>' +
                       '</div>' +
                       '<div class="chat-messages"></div>' +
                       '<div class="chat-input">' +
                       '<input type="text" placeholder="Type your message...">' +
                       '<button class="send-message">Send</button>' +
                       '</div>' +
                       '</div>');
    
    jQuery('body').append(widget);
    
    // Handle sending messages
    widget.find('.send-message').click(function() {
        var message = widget.find('input').val();
        if (message.trim()) {
            psource_chat.chat_session_enqueue_message(message, 'support_session');
            widget.find('input').val('');
        }
    });
    
    // Handle Enter key
    widget.find('input').keypress(function(e) {
        if (e.which === 13) {
            widget.find('.send-message').click();
        }
    });
}

// Initialize custom widget when page loads
jQuery(document).ready(function() {
    createCustomChatWidget();
});
```

### Real-time Features

```javascript
// Custom real-time typing indicator
var typingTimer;
var isTyping = false;

function handleTypingIndicator(sessionId) {
    jQuery('#psource-chat-box-' + sessionId + ' textarea').on('input', function() {
        if (!isTyping) {
            isTyping = true;
            // Send typing started signal
            jQuery.post(psource_chat_localized.ajax_url, {
                action: 'psource_chat_typing_started',
                session_id: sessionId,
                nonce: psource_chat_localized.nonce
            });
        }
        
        clearTimeout(typingTimer);
        typingTimer = setTimeout(function() {
            isTyping = false;
            // Send typing stopped signal
            jQuery.post(psource_chat_localized.ajax_url, {
                action: 'psource_chat_typing_stopped',
                session_id: sessionId,
                nonce: psource_chat_localized.nonce
            });
        }, 1000);
    });
}
```

### Integration with Other Plugins

```javascript
// WooCommerce integration
function integrateWithWooCommerce() {
    // Add product to chat from product page
    if (jQuery('.single-product').length) {
        var productId = jQuery('input[name="add-to-cart"]').val();
        var addProductButton = '<button id="add-product-to-chat" class="button">Ask about this product</button>';
        jQuery('.summary').append(addProductButton);
        
        jQuery('#add-product-to-chat').click(function() {
            var productName = jQuery('.product_title').text();
            var message = 'I have a question about: ' + productName + ' [product:' + productId + ']';
            psource_chat.chat_session_enqueue_message(message, 'woocommerce_support');
        });
    }
}

// BuddyPress integration
function integrateWithBuddyPress() {
    // Add chat to group pages
    if (jQuery('#buddypress').length && jQuery('.groups').length) {
        var groupId = jQuery('body').attr('class').match(/group-(\d+)/)[1];
        
        // Initialize group chat
        var groupChatHtml = '<div id="group-chat-' + groupId + '" class="group-chat">' +
                           '<h3>Group Chat</h3>' +
                           '<div class="chat-container"></div>' +
                           '</div>';
        
        jQuery('#item-body').append(groupChatHtml);
        
        // Connect to group chat session
        psource_chat.initializeSession('bp-group-' + groupId);
    }
}

// Initialize integrations
jQuery(document).ready(function() {
    integrateWithWooCommerce();
    integrateWithBuddyPress();
});
```

## Localization Support

```javascript
// Access localized strings
var strings = psource_chat_localized.strings || {};

// Use localized strings in custom code
function showLocalizedMessage(type) {
    var messages = {
        'upload_error': strings.upload_error || 'Upload failed',
        'connection_error': strings.connection_error || 'Connection error',
        'message_sent': strings.message_sent || 'Message sent'
    };
    
    alert(messages[type]);
}
```

## Error Handling

```javascript
// Global error handler for chat operations
function setupChatErrorHandling() {
    // Handle AJAX errors
    jQuery(document).ajaxError(function(event, xhr, settings, error) {
        if (settings.url === psource_chat_localized.ajax_url) {
            console.error('Chat AJAX error:', error);
            showChatError('Connection error. Please try again.');
        }
    });
    
    // Handle upload errors
    jQuery(document).on('psource_chat_upload_error', function(event, data) {
        showChatError('Upload failed: ' + data.error_message);
    });
}

function showChatError(message) {
    var errorDiv = jQuery('<div class="chat-error-message">' + message + '</div>');
    jQuery('.psource-chat-box').prepend(errorDiv);
    
    setTimeout(function() {
        errorDiv.fadeOut(function() {
            errorDiv.remove();
        });
    }, 5000);
}

// Initialize error handling
jQuery(document).ready(function() {
    setupChatErrorHandling();
});
```

---

This JavaScript API reference provides comprehensive coverage of all available methods, events, and integration patterns for PS Chat frontend development.
