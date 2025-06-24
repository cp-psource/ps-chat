/**
 * Admin Bar Chat JavaScript
 * Handles chat functionality in WordPress Admin Bar
 */

(function($) {
    'use strict';

    // Global variables
    let adminBarPopup = null;
    let isPopupOpen = false;
    let pollingInterval = null;
    let lastMessageId = 0;

    // Initialize when DOM is ready
    $(document).ready(function() {
        initializeAdminBarChat();
    });

    /**
     * Initialize admin bar chat
     */
    function initializeAdminBarChat() {
        setupEventHandlers();
        updateNotificationBadge();
        
        // Start polling for notifications
        startNotificationPolling();
    }

    /**
     * Setup event handlers
     */
    function setupEventHandlers() {
        // Main chat menu click
        $('#wp-admin-bar-ps-chat').on('click', function(e) {
            e.preventDefault();
            toggleChatPopup();
        });

        // Status change handlers
        $('.ps-chat-status-option').on('click', function(e) {
            e.preventDefault();
            const status = $(this).data('status');
            changeUserStatus(status);
        });

        // Friend chat handlers
        $('.ps-chat-friend-item').on('click', function(e) {
            e.preventDefault();
            const userId = $(this).data('user-id');
            startPrivateChat(userId);
        });

        // Chat toggle handler
        $('.ps-chat-toggle-menu').on('click', function(e) {
            e.preventDefault();
            toggleChatInterface();
        });

        // Popup specific handlers
        setupPopupHandlers();

        // Close popup when clicking outside
        $(document).on('click', function(e) {
            if (isPopupOpen && !$(e.target).closest('#ps-chat-admin-bar-popup, #wp-admin-bar-ps-chat').length) {
                closeChatPopup();
            }
        });
    }

    /**
     * Setup popup specific handlers
     */
    function setupPopupHandlers() {
        // Popup close button
        $(document).on('click', '.chat-popup-close', function() {
            closeChatPopup();
        });

        // Send message
        $(document).on('click', '#admin-bar-chat-send', function() {
            sendMessage();
        });

        // Enter key to send message
        $(document).on('keydown', '#admin-bar-chat-input', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Character counter
        $(document).on('input', '#admin-bar-chat-input', function() {
            updateCharCounter();
        });
    }

    /**
     * Toggle chat popup
     */
    function toggleChatPopup() {
        if (isPopupOpen) {
            closeChatPopup();
        } else {
            openChatPopup();
        }
    }

    /**
     * Open chat popup
     */
    function openChatPopup() {
        adminBarPopup = $('#ps-chat-admin-bar-popup');
        
        if (adminBarPopup.length === 0) {
            // Create popup if it doesn't exist
            createChatPopup();
            adminBarPopup = $('#ps-chat-admin-bar-popup');
        }

        adminBarPopup.show();
        isPopupOpen = true;

        // Load messages
        loadPopupMessages();

        // Focus input
        $('#admin-bar-chat-input').focus();

        // Start message polling
        startMessagePolling();
    }

    /**
     * Close chat popup
     */
    function closeChatPopup() {
        if (adminBarPopup) {
            adminBarPopup.hide();
        }
        isPopupOpen = false;
        
        // Stop message polling
        stopMessagePolling();
    }

    /**
     * Create chat popup
     */
    function createChatPopup() {
        const popupHtml = `
            <div id="ps-chat-admin-bar-popup" class="ps-chat-admin-bar-popup" style="display: none;">
                <div class="chat-popup-header">
                    <h4>Quick Chat</h4>
                    <button type="button" class="chat-popup-close">
                        <span class="dashicons dashicons-no"></span>
                    </button>
                </div>
                
                <div class="chat-popup-content">
                    <div class="chat-popup-messages" id="admin-bar-chat-messages">
                        <div class="loading-messages">
                            <span class="dashicons dashicons-update spin"></span>
                            Lade Nachrichten...
                        </div>
                    </div>
                    
                    <div class="chat-popup-input">
                        <textarea id="admin-bar-chat-input" 
                                  placeholder="Nachricht eingeben..."
                                  rows="2"></textarea>
                        <button type="button" id="admin-bar-chat-send" class="button button-primary">
                            <span class="dashicons dashicons-arrow-up-alt2"></span>
                        </button>
                    </div>
                    
                    <div class="chat-popup-status">
                        <span class="connection-status" id="admin-bar-connection-status">
                            <span class="status-dot"></span>
                            <span class="status-text">Verbinde...</span>
                        </span>
                        <span class="char-counter" id="admin-bar-char-counter">0/500</span>
                    </div>
                </div>
            </div>
        `;

        $('body').append(popupHtml);
    }

    /**
     * Load popup messages
     */
    function loadPopupMessages() {
        $.ajax({
            url: psourceChatAdminBar.ajaxUrl,
            type: 'POST',
            data: {
                action: 'psource_chat_get_messages',
                nonce: psourceChatAdminBar.nonce,
                last_message_id: lastMessageId,
                room_id: 0,
                type: 'public'
            },
            success: function(response) {
                if (response.success) {
                    displayPopupMessages(response.messages);
                    updateConnectionStatus('connected');
                } else {
                    showPopupError(response.error || 'Verbindungsfehler');
                }
            },
            error: function() {
                showPopupError('Verbindungsfehler');
                updateConnectionStatus('disconnected');
            }
        });
    }

    /**
     * Display messages in popup
     */
    function displayPopupMessages(messages) {
        const messagesContainer = $('#admin-bar-chat-messages');
        
        if (messages.length === 0) {
            if (lastMessageId === 0) {
                messagesContainer.html('<div class="no-messages">Noch keine Nachrichten.</div>');
            }
            return;
        }

        // Clear loading or no messages
        if (lastMessageId === 0) {
            messagesContainer.empty();
        }

        messages.forEach(function(message) {
            displayPopupMessage(message);
            lastMessageId = Math.max(lastMessageId, message.id);
        });

        scrollPopupToBottom();
    }

    /**
     * Display single message in popup
     */
    function displayPopupMessage(message) {
        const messageHtml = `
            <div class="chat-message" data-message-id="${message.id}">
                <div class="message-avatar">
                    <img src="${message.user_avatar}" alt="${message.user_name}" />
                </div>
                <div class="message-content">
                    <div class="message-header">
                        <span class="message-author">${message.user_name}</span>
                        <span class="message-time">${message.formatted_time}</span>
                    </div>
                    <div class="message-text">${message.message}</div>
                </div>
            </div>
        `;

        $('#admin-bar-chat-messages').append(messageHtml);

        // Play notification sound for new messages
        if (message.user_id !== parseInt(psourceChatAdminBar.userId || 0)) {
            playNotificationSound();
        }
    }

    /**
     * Send message from popup
     */
    function sendMessage() {
        const messageInput = $('#admin-bar-chat-input');
        const message = messageInput.val().trim();
        
        if (message === '') {
            return;
        }

        if (message.length > 500) {
            showPopupError('Nachricht zu lang.');
            return;
        }

        // Disable send button
        $('#admin-bar-chat-send').prop('disabled', true);

        $.ajax({
            url: psourceChatAdminBar.ajaxUrl,
            type: 'POST',
            data: {
                action: 'psource_chat_send_message',
                nonce: psourceChatAdminBar.nonce,
                message: message,
                room_id: 0,
                type: 'public'
            },
            success: function(response) {
                if (response.success) {
                    messageInput.val('');
                    updateCharCounter();
                    loadPopupMessages(); // Reload to show new message
                } else {
                    showPopupError(response.error || 'Verbindungsfehler');
                }
            },
            error: function() {
                showPopupError('Verbindungsfehler');
            },
            complete: function() {
                $('#admin-bar-chat-send').prop('disabled', false);
                messageInput.focus();
            }
        });
    }

    /**
     * Change user status
     */
    function changeUserStatus(status) {
        $.ajax({
            url: psourceChatAdminBar.ajaxUrl,
            type: 'POST',
            data: {
                action: 'psource_chat_set_status',
                nonce: psourceChatAdminBar.nonce,
                status: status
            },
            success: function(response) {
                if (response.success) {
                    showNotification(psourceChatAdminBar.strings.statusChanged || 'Status wurde geändert');
                    updateStatusIndicators(status);
                } else {
                    showNotification(response.error || psourceChatAdminBar.strings.statusError, 'error');
                }
            }
        });
    }

    /**
     * Start private chat with user
     */
    function startPrivateChat(userId) {
        // Implementation for starting private chat
        showNotification(psourceChatAdminBar.strings.startPrivateChat || 'Privaten Chat gestartet');
    }

    /**
     * Toggle main chat interface
     */
    function toggleChatInterface() {
        // Implementation for toggling main chat interface
        showNotification('Chat-Interface umgeschaltet');
    }

    /**
     * Update notification badge
     */
    function updateNotificationBadge() {
        $.ajax({
            url: psourceChatAdminBar.ajaxUrl,
            type: 'POST',
            data: {
                action: 'psource_chat_get_unread_count',
                nonce: psourceChatAdminBar.nonce
            },
            success: function(response) {
                if (response.success) {
                    const badge = $('.ps-chat-notification-badge');
                    if (response.count > 0) {
                        if (badge.length > 0) {
                            badge.text(response.count);
                        } else {
                            $('#wp-admin-bar-ps-chat .ab-label').after(
                                '<span class="ps-chat-notification-badge">' + response.count + '</span>'
                            );
                        }
                    } else {
                        badge.remove();
                    }
                }
            }
        });
    }

    /**
     * Start notification polling
     */
    function startNotificationPolling() {
        setInterval(updateNotificationBadge, 30000); // Every 30 seconds
    }

    /**
     * Start message polling for popup
     */
    function startMessagePolling() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
        }
        
        pollingInterval = setInterval(function() {
            if (isPopupOpen) {
                loadPopupMessages();
            }
        }, 3000);
    }

    /**
     * Stop message polling
     */
    function stopMessagePolling() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }
    }

    /**
     * Update character counter
     */
    function updateCharCounter() {
        const input = $('#admin-bar-chat-input');
        const counter = $('#admin-bar-char-counter');
        const length = input.val().length;
        
        counter.text(length + '/500');
        
        if (length > 450) {
            counter.addClass('warning');
        } else {
            counter.removeClass('warning');
        }
    }

    /**
     * Update connection status
     */
    function updateConnectionStatus(status) {
        const statusElement = $('#admin-bar-connection-status .status-text');
        const statusDot = $('#admin-bar-connection-status .status-dot');
        
        statusDot.removeClass('status-online status-offline status-connecting');
        
        switch (status) {
            case 'connecting':
                statusDot.addClass('status-connecting');
                statusElement.text('Verbinde...');
                break;
            case 'connected':
                statusDot.addClass('status-online');
                statusElement.text('Verbunden');
                break;
            case 'disconnected':
                statusDot.addClass('status-offline');
                statusElement.text('Verbindung unterbrochen');
                break;
        }
    }

    /**
     * Update status indicators
     */
    function updateStatusIndicators(status) {
        $('.ps-chat-status-option').removeClass('current');
        $('.ps-chat-status-option[data-status="' + status + '"]').addClass('current');
    }

    /**
     * Utility functions
     */
    function scrollPopupToBottom() {
        const messagesContainer = $('#admin-bar-chat-messages');
        messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
    }

    function showPopupError(message) {
        const messagesContainer = $('#admin-bar-chat-messages');
        messagesContainer.append('<div class="error-message">' + message + '</div>');
        scrollPopupToBottom();
    }

    function showNotification(message, type = 'success') {
        // Show notification (could be enhanced with proper notification system)
        console.log('[PS Chat] ' + message);
    }

    function playNotificationSound() {
        // Play notification sound
        try {
            const audio = new Audio();
            audio.play().catch(function() {
                // Ignore audio play errors
            });
        } catch (e) {
            // Ignore errors
        }
    }

    // Cleanup on page unload
    $(window).on('beforeunload', function() {
        stopMessagePolling();
    });

})(jQuery);
