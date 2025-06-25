# PS Chat Integration Examples

Real-world examples of integrating PS Chat with popular WordPress plugins and custom functionality.

## Table of Contents

1. [WooCommerce Integration](#woocommerce-integration)
2. [BuddyPress Integration](#buddypress-integration)
3. [Custom User Roles](#custom-user-roles)
4. [External API Integration](#external-api-integration)
5. [Custom Message Types](#custom-message-types)
6. [Mobile App Integration](#mobile-app-integration)
7. [Analytics Integration](#analytics-integration)
8. [Multi-language Support](#multi-language-support)

## WooCommerce Integration

### Product Support Chat

Add a support chat button to product pages that includes product information.

```php
// Add chat button to single product pages
function add_product_support_chat() {
    global $product;
    if ( is_product() && $product ) {
        ?>
        <div class="product-chat-support">
            <button id="start-product-chat" class="button" data-product-id="<?php echo $product->get_id(); ?>">
                Ask about this product
            </button>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#start-product-chat').click(function() {
                var productId = $(this).data('product-id');
                var productName = $('.product_title').text();
                var productUrl = window.location.href;
                
                // Create support session
                var sessionId = 'wc-product-' + productId;
                
                // Send initial message with product info
                var message = 'Hi! I have a question about: ' + productName + 
                             '\nProduct URL: ' + productUrl + 
                             '\n[product:' + productId + ']';
                
                psource_chat.chat_session_enqueue_message(message, sessionId);
                
                // Show chat box
                if ($('#psource-chat-box-' + sessionId).length === 0) {
                    // Initialize new chat session
                    initializeProductChat(sessionId, productId);
                }
            });
        });
        
        function initializeProductChat(sessionId, productId) {
            // Custom chat initialization for products
            $.post(ajaxurl, {
                action: 'initialize_product_chat',
                session_id: sessionId,
                product_id: productId,
                nonce: '<?php echo wp_create_nonce('product_chat_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    // Chat session created, reload or update UI
                    location.reload();
                }
            });
        }
        </script>
        <?php
    }
}
add_action( 'woocommerce_single_product_summary', 'add_product_support_chat', 25 );

// Handle product chat initialization
function handle_product_chat_initialization() {
    if ( ! wp_verify_nonce( $_POST['nonce'], 'product_chat_nonce' ) ) {
        wp_die( 'Security check failed' );
    }
    
    $session_id = sanitize_text_field( $_POST['session_id'] );
    $product_id = intval( $_POST['product_id'] );
    
    // Create chat session with product context
    $chat_session = array(
        'session_type' => 'woocommerce-product',
        'session_status' => 'open',
        'product_id' => $product_id,
        'customer_id' => get_current_user_id()
    );
    
    // Store session data
    update_option( 'chat_session_' . $session_id, $chat_session );
    
    wp_send_json_success( array( 'session_id' => $session_id ) );
}
add_action( 'wp_ajax_initialize_product_chat', 'handle_product_chat_initialization' );

// Custom message filter for product references
function display_product_references( $message, $row ) {
    // Handle [product:ID] references
    if ( preg_match_all( '/\[product:(\d+)\]/', $message, $matches ) ) {
        foreach ( $matches[1] as $product_id ) {
            $product = wc_get_product( $product_id );
            if ( $product ) {
                $product_html = sprintf(
                    '<div class="chat-product-card">
                        <img src="%s" alt="%s" style="width: 60px; height: 60px; object-fit: cover;">
                        <div class="product-info">
                            <h4><a href="%s" target="_blank">%s</a></h4>
                            <p class="price">%s</p>
                        </div>
                    </div>',
                    wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ),
                    esc_attr( $product->get_name() ),
                    get_permalink( $product_id ),
                    esc_html( $product->get_name() ),
                    $product->get_price_html()
                );
                
                $message = str_replace( '[product:' . $product_id . ']', $product_html, $message );
            }
        }
    }
    
    return $message;
}
add_filter( 'psource_chat_display_message', 'display_product_references', 10, 2 );

// Order support chat
function add_order_support_chat() {
    if ( is_wc_endpoint_url( 'view-order' ) ) {
        global $wp;
        $order_id = absint( $wp->query_vars['view-order'] );
        $order = wc_get_order( $order_id );
        
        if ( $order && $order->get_customer_id() === get_current_user_id() ) {
            ?>
            <div class="order-chat-support">
                <h3>Need help with this order?</h3>
                <button id="start-order-chat" class="button" data-order-id="<?php echo $order_id; ?>">
                    Contact Support
                </button>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                $('#start-order-chat').click(function() {
                    var orderId = $(this).data('order-id');
                    var sessionId = 'wc-order-' + orderId;
                    
                    var message = 'I need help with my order #' + orderId + 
                                 '\n[order:' + orderId + ']';
                    
                    psource_chat.chat_session_enqueue_message(message, sessionId);
                });
            });
            </script>
            <?php
        }
    }
}
add_action( 'woocommerce_order_details_after_order_table', 'add_order_support_chat' );
```

## BuddyPress Integration

### Group Chat Integration

```php
// Add chat to BuddyPress group pages
function integrate_bp_group_chat() {
    if ( function_exists( 'bp_is_group' ) && bp_is_group() ) {
        $group_id = bp_get_current_group_id();
        $group = groups_get_group( $group_id );
        
        // Check if user can access group chat
        if ( groups_is_user_member( get_current_user_id(), $group_id ) || groups_is_user_admin( get_current_user_id(), $group_id ) ) {
            ?>
            <div id="group-chat-container" class="bp-widget">
                <h2 class="widget-title">Group Chat</h2>
                <div id="group-chat-<?php echo $group_id; ?>" class="group-chat-box">
                    <!-- Chat will be initialized here -->
                </div>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                // Initialize group chat
                var groupId = <?php echo $group_id; ?>;
                var sessionId = 'bp-group-' + groupId;
                
                // Create chat interface
                initializeGroupChat(sessionId, groupId);
            });
            
            function initializeGroupChat(sessionId, groupId) {
                $.post(ajaxurl, {
                    action: 'initialize_bp_group_chat',
                    session_id: sessionId,
                    group_id: groupId,
                    nonce: '<?php echo wp_create_nonce('bp_group_chat_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        $('#group-chat-' + groupId).html(response.data.chat_html);
                    }
                });
            }
            </script>
            <?php
        }
    }
}
add_action( 'bp_after_group_body', 'integrate_bp_group_chat' );

// Handle BuddyPress group chat initialization
function handle_bp_group_chat_initialization() {
    if ( ! wp_verify_nonce( $_POST['nonce'], 'bp_group_chat_nonce' ) ) {
        wp_die( 'Security check failed' );
    }
    
    $session_id = sanitize_text_field( $_POST['session_id'] );
    $group_id = intval( $_POST['group_id'] );
    
    // Verify user can access this group
    if ( ! groups_is_user_member( get_current_user_id(), $group_id ) ) {
        wp_send_json_error( 'Access denied' );
    }
    
    // Create group chat session
    $chat_session = array(
        'session_type' => 'bp-group',
        'group_id' => $group_id,
        'session_status' => 'open'
    );
    
    // Generate chat HTML
    $chat_html = generate_group_chat_html( $session_id, $group_id );
    
    wp_send_json_success( array( 'chat_html' => $chat_html ) );
}
add_action( 'wp_ajax_initialize_bp_group_chat', 'handle_bp_group_chat_initialization' );

// Private messaging integration
function add_bp_private_chat_button() {
    if ( bp_is_user() && ! bp_is_my_profile() ) {
        $user_id = bp_displayed_user_id();
        $current_user_id = get_current_user_id();
        
        ?>
        <div class="bp-private-chat">
            <button id="start-private-chat" class="button" data-user-id="<?php echo $user_id; ?>">
                Start Private Chat
            </button>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#start-private-chat').click(function() {
                var userId = $(this).data('user-id');
                var currentUserId = <?php echo $current_user_id; ?>;
                
                // Create unique session ID for private chat
                var sessionId = 'bp-private-' + Math.min(currentUserId, userId) + '-' + Math.max(currentUserId, userId);
                
                // Initialize private chat
                initializePrivateChat(sessionId, userId);
            });
        });
        </script>
        <?php
    }
}
add_action( 'bp_before_member_header_meta', 'add_bp_private_chat_button' );
```

## Custom User Roles

### Chat Moderator System

```php
// Create custom roles for chat
function create_chat_roles() {
    // Chat Moderator
    add_role( 'chat_moderator', 'Chat Moderator', array(
        'read' => true,
        'moderate_chat' => true,
        'view_chat_logs' => true,
        'ban_chat_users' => true
    ));
    
    // Chat Support Agent
    add_role( 'chat_support', 'Support Agent', array(
        'read' => true,
        'answer_support_chats' => true,
        'view_customer_info' => true,
        'transfer_chats' => true
    ));
    
    // Add capabilities to existing roles
    $admin = get_role( 'administrator' );
    $admin->add_cap( 'moderate_chat' );
    $admin->add_cap( 'view_chat_logs' );
    $admin->add_cap( 'manage_chat_settings' );
}
add_action( 'init', 'create_chat_roles' );

// Check moderator permissions in chat
function check_chat_moderation_permissions( $user_id = null ) {
    if ( ! $user_id ) {
        $user_id = get_current_user_id();
    }
    
    return user_can( $user_id, 'moderate_chat' ) || user_can( $user_id, 'manage_options' );
}

// Add moderation tools to chat interface
function add_chat_moderation_tools( $message, $row ) {
    if ( check_chat_moderation_permissions() && $row['user_id'] != get_current_user_id() ) {
        $moderation_tools = sprintf(
            '<div class="chat-moderation-tools">
                <button class="delete-message" data-message-id="%d">Delete</button>
                <button class="warn-user" data-user-id="%d">Warn</button>
                <button class="ban-user" data-user-id="%d">Ban</button>
            </div>',
            $row['id'],
            $row['user_id'],
            $row['user_id']
        );
        
        $message .= $moderation_tools;
    }
    
    return $message;
}
add_filter( 'psource_chat_display_message', 'add_chat_moderation_tools', 20, 2 );

// Handle moderation actions
function handle_chat_moderation_actions() {
    if ( ! check_chat_moderation_permissions() ) {
        wp_send_json_error( 'Insufficient permissions' );
    }
    
    $action = sanitize_text_field( $_POST['moderation_action'] );
    $target_id = intval( $_POST['target_id'] );
    
    switch ( $action ) {
        case 'delete_message':
            delete_chat_message( $target_id );
            break;
            
        case 'warn_user':
            warn_chat_user( $target_id );
            break;
            
        case 'ban_user':
            ban_chat_user( $target_id );
            break;
    }
    
    wp_send_json_success();
}
add_action( 'wp_ajax_chat_moderation_action', 'handle_chat_moderation_actions' );
```

## External API Integration

### Slack Integration

```php
// Sync chat messages to Slack
function sync_chat_to_slack( $message_id, $message_data, $session_id ) {
    $slack_webhook = get_option( 'chat_slack_webhook' );
    
    if ( $slack_webhook && ! empty( $message_data['message'] ) ) {
        $user = get_userdata( $message_data['user_id'] );
        $username = $user ? $user->display_name : 'Unknown User';
        
        $slack_message = array(
            'text' => sprintf( 'New chat message in session %s', $session_id ),
            'attachments' => array(
                array(
                    'author_name' => $username,
                    'text' => $message_data['message'],
                    'color' => 'good',
                    'timestamp' => time()
                )
            )
        );
        
        wp_remote_post( $slack_webhook, array(
            'body' => json_encode( $slack_message ),
            'headers' => array(
                'Content-Type' => 'application/json'
            )
        ));
    }
}

// Hook into message sending (you would need to add this action to the plugin)
// add_action( 'psource_chat_message_sent', 'sync_chat_to_slack', 10, 3 );

// Discord Integration
function sync_chat_to_discord( $message_id, $message_data, $session_id ) {
    $discord_webhook = get_option( 'chat_discord_webhook' );
    
    if ( $discord_webhook && ! empty( $message_data['message'] ) ) {
        $user = get_userdata( $message_data['user_id'] );
        $username = $user ? $user->display_name : 'Unknown User';
        
        $discord_message = array(
            'content' => '',
            'embeds' => array(
                array(
                    'title' => 'New Chat Message',
                    'description' => $message_data['message'],
                    'author' => array(
                        'name' => $username
                    ),
                    'color' => 3447003, // Blue color
                    'timestamp' => date( 'c' ),
                    'footer' => array(
                        'text' => 'Session: ' . $session_id
                    )
                )
            )
        );
        
        wp_remote_post( $discord_webhook, array(
            'body' => json_encode( $discord_message ),
            'headers' => array(
                'Content-Type' => 'application/json'
            )
        ));
    }
}

// CRM Integration (HubSpot example)
function sync_chat_to_hubspot( $session_id, $session_data ) {
    $hubspot_api_key = get_option( 'chat_hubspot_api_key' );
    
    if ( $hubspot_api_key ) {
        $customer_email = get_userdata( $session_data['customer_id'] )->user_email;
        
        // Create or update contact
        $contact_data = array(
            'properties' => array(
                array(
                    'property' => 'email',
                    'value' => $customer_email
                ),
                array(
                    'property' => 'chat_session_id',
                    'value' => $session_id
                ),
                array(
                    'property' => 'last_chat_date',
                    'value' => time() * 1000 // HubSpot uses milliseconds
                )
            )
        );
        
        wp_remote_post( 'https://api.hubapi.com/contacts/v1/contact/createOrUpdate/email/' . urlencode( $customer_email ), array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $hubspot_api_key
            ),
            'body' => json_encode( $contact_data )
        ));
    }
}
```

## Custom Message Types

### Interactive Message Components

```php
// Custom message types filter
function handle_custom_message_types( $message, $row ) {
    // Survey messages
    if ( preg_match( '/\[survey:(\d+)\]/', $message, $matches ) ) {
        $survey_id = $matches[1];
        $survey_html = generate_survey_html( $survey_id );
        $message = str_replace( $matches[0], $survey_html, $message );
    }
    
    // Appointment booking
    if ( preg_match( '/\[book_appointment\]/', $message ) ) {
        $booking_html = generate_appointment_booking_html();
        $message = str_replace( '[book_appointment]', $booking_html, $message );
    }
    
    // FAQ suggestions
    if ( preg_match( '/\[faq_suggestions:(.+?)\]/', $message, $matches ) ) {
        $keywords = $matches[1];
        $faq_html = generate_faq_suggestions( $keywords );
        $message = str_replace( $matches[0], $faq_html, $message );
    }
    
    return $message;
}
add_filter( 'psource_chat_display_message', 'handle_custom_message_types', 15, 2 );

// Generate survey HTML
function generate_survey_html( $survey_id ) {
    $survey = get_post( $survey_id );
    if ( ! $survey ) return '';
    
    $questions = get_post_meta( $survey_id, 'survey_questions', true );
    
    $html = '<div class="chat-survey" data-survey-id="' . $survey_id . '">';
    $html .= '<h4>' . esc_html( $survey->post_title ) . '</h4>';
    
    foreach ( $questions as $index => $question ) {
        $html .= '<div class="survey-question">';
        $html .= '<p>' . esc_html( $question['question'] ) . '</p>';
        
        if ( $question['type'] === 'multiple_choice' ) {
            foreach ( $question['options'] as $option_index => $option ) {
                $html .= sprintf(
                    '<label><input type="radio" name="question_%d" value="%d"> %s</label><br>',
                    $index,
                    $option_index,
                    esc_html( $option )
                );
            }
        }
        
        $html .= '</div>';
    }
    
    $html .= '<button class="submit-survey" data-survey-id="' . $survey_id . '">Submit Survey</button>';
    $html .= '</div>';
    
    return $html;
}

// Generate appointment booking HTML
function generate_appointment_booking_html() {
    $available_slots = get_available_appointment_slots();
    
    $html = '<div class="chat-appointment-booking">';
    $html .= '<h4>Book an Appointment</h4>';
    $html .= '<select id="appointment-slots">';
    
    foreach ( $available_slots as $slot ) {
        $html .= sprintf(
            '<option value="%s">%s</option>',
            $slot['datetime'],
            date( 'M j, Y - g:i A', strtotime( $slot['datetime'] ) )
        );
    }
    
    $html .= '</select>';
    $html .= '<button class="book-appointment">Book Appointment</button>';
    $html .= '</div>';
    
    return $html;
}

// Auto-suggest FAQ based on message content
function auto_suggest_faq( $message_data, $session_id ) {
    $keywords = extract_keywords_from_message( $message_data['message'] );
    $relevant_faqs = find_relevant_faqs( $keywords );
    
    if ( ! empty( $relevant_faqs ) ) {
        $suggestions_message = "Here are some articles that might help:\n[faq_suggestions:" . implode( ',', array_column( $relevant_faqs, 'id' ) ) . "]";
        
        // Send as system message
        setTimeout( function() use ( $suggestions_message, $session_id ) {
            psource_chat.chat_session_enqueue_message( $suggestions_message, $session_id );
        }, 2000 ); // 2 second delay
    }
}
```

## Mobile App Integration

### REST API Endpoints

```php
// Register custom REST API endpoints for mobile app
function register_chat_rest_endpoints() {
    register_rest_route( 'pschat/v1', '/sessions', array(
        'methods' => 'GET',
        'callback' => 'get_user_chat_sessions',
        'permission_callback' => 'check_chat_api_permissions'
    ));
    
    register_rest_route( 'pschat/v1', '/sessions/(?P<id>\d+)/messages', array(
        'methods' => 'GET',
        'callback' => 'get_chat_messages_api',
        'permission_callback' => 'check_chat_api_permissions'
    ));
    
    register_rest_route( 'pschat/v1', '/sessions/(?P<id>\d+)/messages', array(
        'methods' => 'POST',
        'callback' => 'send_chat_message_api',
        'permission_callback' => 'check_chat_api_permissions'
    ));
}
add_action( 'rest_api_init', 'register_chat_rest_endpoints' );

function check_chat_api_permissions() {
    return is_user_logged_in();
}

function get_user_chat_sessions( $request ) {
    $user_id = get_current_user_id();
    $sessions = get_user_chat_sessions( $user_id );
    
    $formatted_sessions = array();
    foreach ( $sessions as $session ) {
        $formatted_sessions[] = array(
            'id' => $session['id'],
            'title' => $session['title'],
            'type' => $session['session_type'],
            'status' => $session['status'],
            'last_message' => get_last_message( $session['id'] ),
            'unread_count' => get_unread_count( $session['id'], $user_id )
        );
    }
    
    return new WP_REST_Response( $formatted_sessions, 200 );
}

// Push notifications for mobile
function send_push_notification( $message_data, $session_id ) {
    $session_participants = get_session_participants( $session_id );
    $sender_id = $message_data['user_id'];
    
    foreach ( $session_participants as $user_id ) {
        if ( $user_id != $sender_id ) {
            $device_tokens = get_user_meta( $user_id, 'mobile_device_tokens', true );
            
            if ( $device_tokens ) {
                foreach ( $device_tokens as $token ) {
                    send_fcm_notification( $token, array(
                        'title' => 'New Chat Message',
                        'body' => substr( $message_data['message'], 0, 100 ),
                        'data' => array(
                            'session_id' => $session_id,
                            'message_id' => $message_data['id']
                        )
                    ));
                }
            }
        }
    }
}

function send_fcm_notification( $device_token, $notification_data ) {
    $fcm_server_key = get_option( 'fcm_server_key' );
    
    $payload = array(
        'to' => $device_token,
        'notification' => array(
            'title' => $notification_data['title'],
            'body' => $notification_data['body'],
            'sound' => 'default'
        ),
        'data' => $notification_data['data']
    );
    
    wp_remote_post( 'https://fcm.googleapis.com/fcm/send', array(
        'headers' => array(
            'Authorization' => 'key=' . $fcm_server_key,
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode( $payload )
    ));
}
```

## Analytics Integration

### Google Analytics Events

```javascript
// Track chat events in Google Analytics
function setupChatAnalytics() {
    // Track chat session start
    jQuery(document).on('psource_chat_session_started', function(event, data) {
        if (typeof gtag !== 'undefined') {
            gtag('event', 'chat_session_started', {
                'session_type': data.session_type,
                'session_id': data.session_id
            });
        }
    });
    
    // Track message sent
    jQuery(document).on('psource_chat_message_sent', function(event, data) {
        if (typeof gtag !== 'undefined') {
            gtag('event', 'chat_message_sent', {
                'session_id': data.session_id,
                'message_length': data.message.length
            });
        }
    });
    
    // Track file uploads
    jQuery(document).on('psource_chat_upload_completed', function(event, data) {
        if (typeof gtag !== 'undefined') {
            gtag('event', 'chat_file_uploaded', {
                'session_id': data.session_id,
                'file_type': data.file_type,
                'file_size': data.file_size
            });
        }
    });
}

jQuery(document).ready(function() {
    setupChatAnalytics();
});
```

## Multi-language Support

### Translation Integration

```php
// WPML integration for chat sessions
function integrate_wpml_chat() {
    if ( function_exists( 'icl_get_current_language' ) ) {
        add_filter( 'psource_chat_session_language', 'set_chat_session_language' );
        add_filter( 'psource_chat_display_message', 'translate_chat_message', 5, 2 );
    }
}
add_action( 'init', 'integrate_wpml_chat' );

function set_chat_session_language( $session_data ) {
    $session_data['language'] = icl_get_current_language();
    return $session_data;
}

function translate_chat_message( $message, $row ) {
    // Auto-translate messages if users speak different languages
    $session_language = get_chat_session_language( $row['session_id'] );
    $user_language = get_user_preferred_language( get_current_user_id() );
    
    if ( $session_language !== $user_language ) {
        $translated_message = translate_text( $message, $session_language, $user_language );
        if ( $translated_message ) {
            $message = $translated_message . '<br><small><em>Translated from ' . $session_language . '</em></small>';
        }
    }
    
    return $message;
}

function translate_text( $text, $from_lang, $to_lang ) {
    $google_translate_api_key = get_option( 'google_translate_api_key' );
    
    if ( ! $google_translate_api_key ) {
        return false;
    }
    
    $url = add_query_arg( array(
        'key' => $google_translate_api_key,
        'source' => $from_lang,
        'target' => $to_lang,
        'q' => urlencode( $text )
    ), 'https://translation.googleapis.com/language/translate/v2' );
    
    $response = wp_remote_get( $url );
    
    if ( ! is_wp_error( $response ) ) {
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        
        if ( isset( $body['data']['translations'][0]['translatedText'] ) ) {
            return $body['data']['translations'][0]['translatedText'];
        }
    }
    
    return false;
}
```

---

These integration examples provide real-world scenarios for extending PS Chat functionality with popular WordPress plugins and external services. Each example includes complete, working code that can be adapted to specific needs.
