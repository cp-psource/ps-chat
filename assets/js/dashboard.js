/**
 * Dashboard Chat JavaScript
 * Handles chat functionality in WordPress Dashboard
 */

(function($) {
    'use strict';

    // Global variables
    let chatWidget = null;
    let messagesContainer = null;
    let messageInput = null;
    let sendButton = null;
    let lastMessageId = 0;
    let pollingInterval = null;
    let heartbeatInterval = null;
    let isConnected = false;
    let soundEnabled = true;

    // Initialize when DOM is ready
    $(document).ready(function() {
        initializeDashboardChat();
    });

    /**
     * Initialize dashboard chat
     */
    function initializeDashboardChat() {
        chatWidget = $('#psource-chat-dashboard');
        if (chatWidget.length === 0) return;

        messagesContainer = $('#chat-messages');
        messageInput = $('#chat-message-input');
        sendButton = $('#chat-send-button');

        setupEventHandlers();
        startChat();
    }

    /**
     * Setup event handlers
     */
    function setupEventHandlers() {
        // Send message on button click
        sendButton.on('click', sendMessage);

        // Send message on Enter (Shift+Enter for new line)
        messageInput.on('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Character counter
        messageInput.on('input', updateCharCounter);

        // Toggle sound
        $('#chat-toggle-sound').on('click', toggleSound);

        // Refresh chat
        $('#chat-refresh').on('click', refreshChat);

        // Status widget handlers
        setupStatusWidget();

        // Friends widget handlers  
        setupFriendsWidget();
    }

    /**
     * Setup status widget
     */
    function setupStatusWidget() {
        $('#user-status-select').on('change', function() {
            const status = $(this).val();
            changeUserStatus(status);
        });

        $('#save-status').on('click', function() {
            const message = $('#status-message').val();
            saveStatusMessage(message);
        });
    }

    /**
     * Setup friends widget
     */
    function setupFriendsWidget() {
        $('#refresh-friends').on('click', loadFriends);
        $('#start-group-chat').on('click', startGroupChat);
        $('#friends-search').on('input', filterFriends);

        // Load friends initially
        loadFriends();
    }

    /**
     * Start chat functionality
     */
    function startChat() {
        connectToChat();
        loadMessages();
        startPolling();
        startHeartbeat();
    }

    /**
     * Connect to chat
     */
    function connectToChat() {
        updateConnectionStatus('connecting');
        
        // Simulate connection (in real implementation, this might involve WebSocket)
        setTimeout(function() {
            isConnected = true;
            updateConnectionStatus('connected');
        }, 1000);
    }

    /**
     * Update connection status
     */
    function updateConnectionStatus(status) {
        const statusElement = $('#connection-status');
        const statusDot = $('.status-dot');
        
        statusDot.removeClass('status-online status-offline status-connecting');
        
        switch (status) {
            case 'connecting':
                statusDot.addClass('status-connecting');
                statusElement.text(psourceChatDashboard.strings.connecting || 'Verbinde...');
                break;
            case 'connected':
                statusDot.addClass('status-online');
                statusElement.text(psourceChatDashboard.strings.connected || 'Verbunden');
                break;
            case 'disconnected':
                statusDot.addClass('status-offline');
                statusElement.text(psourceChatDashboard.strings.disconnected || 'Verbindung unterbrochen');
                break;
        }
    }

    /**
     * Load messages
     */
    function loadMessages() {
        // Check if required variables are available
        if (typeof psourceChatDashboard === 'undefined') {
            showError('Chat-Konfiguration nicht geladen.');
            return;
        }
        
        if (!psourceChatDashboard.ajaxUrl) {
            showError('AJAX-URL nicht verfügbar.');
            return;
        }
        
        if (!psourceChatDashboard.nonce) {
            showError('Nonce nicht verfügbar.');
            return;
        }
        
        $.ajax({
            url: psourceChatDashboard.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'psource_chat_get_messages',
                nonce: psourceChatDashboard.nonce,
                last_message_id: lastMessageId,
                room_id: 0,
                type: 'public'
            },
            success: function(response) {
                try {
                    if (response.success) {
                        displayMessages(response.messages || []);
                        hideLoading();
                    } else {
                        showError(response.error || psourceChatDashboard.strings.connectionError);
                    }
                } catch (e) {
                    showError('Fehler beim Verarbeiten der Antwort: ' + e.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr, status, error);
                showError('Verbindungsfehler: ' + error);
                updateConnectionStatus('disconnected');
            }
        });
    }

    /**
     * Display messages
     */
    function displayMessages(messages) {
        if (messages.length === 0) {
            if (lastMessageId === 0) {
                showEmptyState();
            }
            return;
        }

        let newMessagesAdded = false;
        messages.forEach(function(message) {
            // Check if this is a new message
            if (messagesContainer.find(`[data-message-id="${message.id}"]`).length === 0) {
                displayMessage(message);
                newMessagesAdded = true;
            }
            lastMessageId = Math.max(lastMessageId, message.id);
        });

        // Only scroll if new messages were actually added
        if (newMessagesAdded) {
            scrollToBottom();
        }
    }

    /**
     * Display single message
     */
    function displayMessage(message) {
        // Check if message already exists to prevent duplicates
        if (messagesContainer.find(`[data-message-id="${message.id}"]`).length > 0) {
            return; // Message already displayed
        }
        
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

        messagesContainer.append(messageHtml);
        
        // Update lastMessageId to track the latest message
        lastMessageId = Math.max(lastMessageId, message.id);

        // Play sound for new messages (not our own)
        if (soundEnabled && message.user_id !== parseInt(psourceChatDashboard.userId || 0)) {
            playNotificationSound();
        }
    }

    /**
     * Send message
     */
    function sendMessage() {
        const message = messageInput.val().trim();
        
        if (message === '') {
            showError(psourceChatDashboard.strings.messageEmpty || 'Nachricht darf nicht leer sein.');
            return;
        }

        if (message.length > (psourceChatDashboard.maxMessageLength || 500)) {
            showError(psourceChatDashboard.strings.messageTooLong || 'Nachricht zu lang.');
            return;
        }

        // Disable send button and show loading
        sendButton.prop('disabled', true).text('Sende...');
        
        // Store original message for error handling
        const originalMessage = message;

        $.ajax({
            url: psourceChatDashboard.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'psource_chat_send_message',
                nonce: psourceChatDashboard.nonce,
                message: message,
                room_id: 0,
                type: 'public'
            },
            success: function(response) {
                try {
                    if (response && response.success) {
                        // Clear input only on successful send
                        messageInput.val('');
                        updateCharCounter();
                        
                        // Display the sent message immediately instead of reloading all
                        if (response.message) {
                            displayMessage(response.message);
                            scrollToBottom(); // Ensure scroll after sending own message
                        } else {
                            // Fallback: load only new messages since last known message
                            loadNewMessagesOnly();
                        }
                        
                        hideError();
                    } else {
                        // Error from server - restore message to input
                        messageInput.val(originalMessage);
                        showError(response.error || 'Fehler beim Senden der Nachricht.');
                    }
                } catch (e) {
                    console.error('Error processing response:', e);
                    messageInput.val(originalMessage);
                    showError('Fehler beim Verarbeiten der Antwort.');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Send Error:', xhr, status, error);
                // Restore message to input on connection error
                messageInput.val(originalMessage);
                showError('Verbindungsfehler: ' + error);
                updateConnectionStatus('disconnected');
            },
            complete: function() {
                // Re-enable send button
                sendButton.prop('disabled', false).text('Senden');
                messageInput.focus();
            }
        });
    }

    /**
     * Load only new messages since last known message
     */
    function loadNewMessagesOnly() {
        $.ajax({
            url: psourceChatDashboard.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'psource_chat_get_messages',
                nonce: psourceChatDashboard.nonce,
                last_message_id: lastMessageId,
                room_id: 0,
                type: 'public'
            },
            success: function(response) {
                if (response && response.success && response.messages) {
                    displayMessages(response.messages);
                }
            },
            error: function() {
                // Silent fail for new message loading
                console.log('Failed to load new messages');
            }
        });
    }

    /**
     * Update character counter
     */
    function updateCharCounter() {
        const length = messageInput.val().length;
        const maxLength = psourceChatDashboard.maxMessageLength || 500;
        const counter = $('#char-counter');
        
        counter.text(length + '/' + maxLength);
        
        if (length > maxLength * 0.9) {
            counter.addClass('warning');
        } else {
            counter.removeClass('warning');
        }
    }

    /**
     * Start polling for new messages
     */
    function startPolling() {
        pollingInterval = setInterval(loadMessages, psourceChatDashboard.pollingInterval || 3000);
    }

    /**
     * Start heartbeat to keep connection alive
     */
    function startHeartbeat() {
        heartbeatInterval = setInterval(function() {
            $.ajax({
                url: psourceChatDashboard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'psource_chat_heartbeat',
                    nonce: psourceChatDashboard.nonce
                },
                success: function() {
                    if (!isConnected) {
                        isConnected = true;
                        updateConnectionStatus('connected');
                    }
                },
                error: function() {
                    isConnected = false;
                    updateConnectionStatus('disconnected');
                }
            });
        }, psourceChatDashboard.heartbeatInterval || 30000);
    }

    /**
     * Change user status
     */
    function changeUserStatus(status) {
        $.ajax({
            url: psourceChatDashboard.ajaxUrl,
            type: 'POST',
            data: {
                action: 'psource_chat_set_status',
                nonce: psourceChatDashboard.nonce,
                status: status
            },
            success: function(response) {
                if (response.success) {
                    showSuccess('Status wurde geändert');
                    updateMyStats();
                } else {
                    showError(response.error);
                }
            }
        });
    }

    /**
     * Save status message
     */
    function saveStatusMessage(message) {
        // Implementation for saving status message
        showSuccess('Status-Nachricht gespeichert');
    }

    /**
     * Load friends
     */
    function loadFriends() {
        const friendsList = $('#friends-list');
        friendsList.html('<div class="loading-friends"><span class="dashicons dashicons-update spin"></span> Lade Freunde...</div>');

        $.ajax({
            url: psourceChatDashboard.ajaxUrl,
            type: 'POST',
            data: {
                action: 'psource_chat_get_friends',
                nonce: psourceChatDashboard.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayFriends(response.friends);
                } else {
                    friendsList.html('<div class="no-friends">Keine Freunde online</div>');
                }
            }
        });
    }

    /**
     * Display friends list
     */
    function displayFriends(friends) {
        const friendsList = $('#friends-list');
        
        if (friends.length === 0) {
            friendsList.html('<div class="no-friends">Keine Freunde online</div>');
            return;
        }

        let html = '';
        friends.forEach(function(friend) {
            html += `
                <div class="friend-item" data-user-id="${friend.id}">
                    <img src="${friend.avatar}" alt="${friend.name}" class="friend-avatar" />
                    <div class="friend-info">
                        <span class="friend-name">${friend.name}</span>
                        <span class="friend-status status-${friend.status}">${friend.status}</span>
                    </div>
                    <div class="friend-actions">
                        <button type="button" class="button button-small start-private-chat" data-user-id="${friend.id}">
                            Chat
                        </button>
                    </div>
                </div>
            `;
        });

        friendsList.html(html);

        // Add click handlers for private chat
        $('.start-private-chat').on('click', function() {
            const userId = $(this).data('user-id');
            startPrivateChat(userId);
        });
    }

    /**
     * Start private chat
     */
    function startPrivateChat(userId) {
        // Implementation for starting private chat
        showSuccess('Privaten Chat gestartet');
    }

    /**
     * Start group chat
     */
    function startGroupChat() {
        // Implementation for starting group chat
        showSuccess('Gruppenchat gestartet');
    }

    /**
     * Filter friends
     */
    function filterFriends() {
        const query = $('#friends-search').val().toLowerCase();
        $('.friend-item').each(function() {
            const name = $(this).find('.friend-name').text().toLowerCase();
            if (name.includes(query)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }

    /**
     * Utility functions
     */
    function hideLoading() {
        $('.chat-loading').remove();
    }

    function showEmptyState() {
        messagesContainer.html('<div class="empty-state"><p>' + 
            (psourceChatDashboard.strings.noMessages || 'Noch keine Nachrichten.') + '</p></div>');
    }

    function scrollToBottom() {
        messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
    }

    function toggleSound() {
        soundEnabled = !soundEnabled;
        const button = $('#chat-toggle-sound');
        const icon = button.find('.dashicons');
        
        if (soundEnabled) {
            icon.removeClass('dashicons-controls-volumeoff').addClass('dashicons-controls-volumeon');
        } else {
            icon.removeClass('dashicons-controls-volumeon').addClass('dashicons-controls-volumeoff');
        }
    }

    function refreshChat() {
        lastMessageId = 0;
        loadMessages();
        updateMyStats();
    }

    function updateMyStats() {
        // Update message count and other stats
        $('#online-friends-count').text('...');
        // Implementation for updating stats
    }

    function playNotificationSound() {
        if (soundEnabled) {
            // Play notification sound
            const audio = new Audio(psourceChatDashboard.soundUrl || '');
            audio.play().catch(function() {
                // Ignore audio play errors
            });
        }
    }

    function showError(message) {
        // Show error notification
        console.error('Chat Error:', message);
        
        // Show in chat widget if available
        const chatWidget = $('#psource-chat-dashboard');
        if (chatWidget.length) {
            let errorContainer = chatWidget.find('.chat-error');
            if (errorContainer.length === 0) {
                errorContainer = $('<div class="chat-error" style="background: #dc3232; color: white; padding: 8px; margin: 5px 0; border-radius: 3px; font-size: 12px;"></div>');
                chatWidget.prepend(errorContainer);
            }
            errorContainer.text(message).show();
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                errorContainer.fadeOut();
            }, 5000);
        }
    }

    function showSuccess(message) {
        // Show success notification
        if (typeof window.wp !== 'undefined' && window.wp.notices) {
            window.wp.notices.create(message, 'success');
        }
    }

    function hideError() {
        // Remove error notices if any
        $('.notice-error').fadeOut();
    }

    // Cleanup on page unload
    $(window).on('beforeunload', function() {
        if (pollingInterval) clearInterval(pollingInterval);
        if (heartbeatInterval) clearInterval(heartbeatInterval);
    });

})(jQuery);
