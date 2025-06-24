/**
 * PS Chat Frontend - Erweiterte Implementation
 * Funktioniert mit server-gerenderten Chat HTML und behält alle Features bei
 */

(function($) {
    'use strict';

    let chatContainer = null;
    let isMinimized = false;
    let pollingInterval = null;
    let typingTimer = null;
    let config = {};

    // Initialize when DOM is ready
    $(document).ready(function() {
        // Warte auf Konfiguration
        if (typeof window.psourceChatConfig !== 'undefined') {
            config = window.psourceChatConfig;
            initializeFrontendChat();
        } else {
            // Fallback für alte Konfiguration
            setTimeout(initializeFrontendChat, 1000);
        }
    });

    /**
     * Initialize frontend chat
     */
    function initializeFrontendChat() {
        chatContainer = $('.psource-chat-container');
        if (chatContainer.length === 0) {
            return;
        }

        setupEventHandlers();
        setupEmojiPicker();
        
        // Check initial state from config
        if (config.initial_state === 'minimized') {
            isMinimized = true;
            chatContainer.addClass('minimized');
        } else {
            isMinimized = false;
            chatContainer.removeClass('minimized');
        }
        
        // Stelle sicher, dass die Position korrekt ist
        applyPosition();
        
        // Start polling for messages if not minimized
        if (!isMinimized) {
            startPolling();
        }
    }

    /**
     * Apply correct position
     */
    function applyPosition() {
        if (!config.position) return;
        
        // Entferne alle Positionsklassen
        chatContainer.removeClass('psource-chat-bottom-right psource-chat-bottom-left psource-chat-top-right psource-chat-top-left');
        
        // Füge die korrekte Positionsklasse hinzu
        chatContainer.addClass('psource-chat-' + config.position);
        
        // Zusätzlich per Inline-Style sicherstellen
        const positions = {
            'bottom-right': { bottom: '20px', right: '20px', top: 'auto', left: 'auto' },
            'bottom-left': { bottom: '20px', left: '20px', top: 'auto', right: 'auto' },
            'top-right': { top: '20px', right: '20px', bottom: 'auto', left: 'auto' },
            'top-left': { top: '20px', left: '20px', bottom: 'auto', right: 'auto' }
        };
        
        if (positions[config.position]) {
            chatContainer.css(positions[config.position]);
        }
    }

    /**
     * Setup event handlers
     */
    function setupEventHandlers() {
        // Header click to toggle minimize
        chatContainer.find('.psource-chat-header').on('click', function(e) {
            // Don't toggle if clicking on buttons
            if ($(e.target).closest('.psource-chat-btn').length === 0) {
                toggleMinimize();
            }
        });

        // Minimize button
        chatContainer.find('.psource-chat-minimize').on('click', function(e) {
            e.stopPropagation();
            toggleMinimize();
        });

        // Close button
        chatContainer.find('.psource-chat-close').on('click', function(e) {
            e.stopPropagation();
            closeChat();
        });

        // Send message
        chatContainer.find('.psource-chat-send-btn').on('click', sendMessage);

        // Enter key to send
        chatContainer.find('.psource-chat-input').on('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Input events
        chatContainer.find('.psource-chat-input').on('input', function() {
            autoResizeTextarea(this);
            updateCharCounter();
            handleTyping();
        });

        // Guest name input
        chatContainer.find('.psource-chat-guest-name').on('input', function() {
            // Store guest name for messages
            config.guest_name = $(this).val();
        });
    }

    /**
     * Setup emoji picker
     */
    function setupEmojiPicker() {
        const emojiBtn = chatContainer.find('.psource-chat-emoji-btn');
        const emojiPicker = $('.psource-chat-emoji-picker');

        emojiBtn.on('click', function(e) {
            e.preventDefault();
            emojiPicker.toggle();
        });

        // Emoji selection
        emojiPicker.on('click', '.emoji-btn', function() {
            const emoji = $(this).data('emoji');
            const input = chatContainer.find('.psource-chat-input');
            const currentValue = input.val();
            input.val(currentValue + emoji);
            input.focus();
            emojiPicker.hide();
            updateCharCounter();
        });

        // Close emoji picker when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.psource-chat-emoji-btn, .psource-chat-emoji-picker').length) {
                emojiPicker.hide();
            }
        });
    }

    /**
     * Toggle minimize state
     */
    function toggleMinimize() {
        isMinimized = !isMinimized;
        
        if (isMinimized) {
            chatContainer.addClass('minimized');
            stopPolling();
        } else {
            chatContainer.removeClass('minimized');
            startPolling();
            loadMessages();
        }
        
        // Update minimize button icon
        const minimizeBtn = chatContainer.find('.psource-chat-minimize');
        const icon = minimizeBtn.find('svg path');
        
        if (isMinimized) {
            // Show maximize icon (plus or expand)
            icon.attr('d', 'M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z');
        } else {
            // Show minimize icon (minus)
            icon.attr('d', 'M19 13H5v-2h14v2z');
        }
    }

    /**
     * Close chat completely
     */
    function closeChat() {
        chatContainer.fadeOut(300);
        stopPolling();
    }

    /**
     * Send message
     */
    function sendMessage() {
        const input = chatContainer.find('.psource-chat-input');
        const message = input.val().trim();
        
        if (message === '') {
            return;
        }

        const sendBtn = chatContainer.find('.psource-chat-send-btn');
        sendBtn.prop('disabled', true);

        // Send via AJAX
        $.ajax({
            url: config.ajax_url || psourceChatFrontend.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'psource_chat_send_message',
                nonce: config.nonce || psourceChatFrontend.nonce,
                message: message,
                room_id: 0,
                type: 'public',
                guest_name: config.guest_name || ''
            },
            success: function(response) {
                if (response && response.success) {
                    input.val('');
                    autoResizeTextarea(input[0]);
                    updateCharCounter();
                    loadMessages(); // Reload messages
                    clearTimeout(typingTimer);
                } else {
                    showError(response.error || 'Fehler beim Senden der Nachricht.');
                }
            },
            error: function() {
                showError('Verbindungsfehler');
            },
            complete: function() {
                sendBtn.prop('disabled', false);
            }
        });
    }

    /**
     * Load messages
     */
    function loadMessages() {
        if (isMinimized) {
            return;
        }

        $.ajax({
            url: config.ajax_url || psourceChatFrontend.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'psource_chat_get_messages',
                nonce: config.nonce || psourceChatFrontend.nonce,
                room_id: 0,
                type: 'public'
            },
            success: function(response) {
                if (response && response.success && response.messages) {
                    displayMessages(response.messages);
                }
                if (response && response.users) {
                    updateUsersList(response.users);
                }
            },
            error: function() {
                console.log('Failed to load messages');
            }
        });
    }

    /**
     * Display messages
     */
    function displayMessages(messages) {
        const messagesContainer = chatContainer.find('.psource-chat-messages');
        
        if (messages.length === 0) {
            messagesContainer.html('<div class="psource-chat-welcome"><h4>Willkommen im Chat!</h4><p>Starte eine Unterhaltung, indem Du eine Nachricht unten eingibst.</p></div>');
            return;
        }

        let html = '';
        messages.forEach(function(message) {
            const isOwn = message.user_id == config.user_id && config.user_id > 0;
            const ownClass = isOwn ? ' own' : '';
            
            html += `
                <div class="psource-chat-message${ownClass}" data-message-id="${message.id}">
                    <img src="${message.user_avatar}" alt="${message.user_name}" class="psource-chat-avatar" />
                    <div class="psource-chat-message-content">
                        <div class="psource-chat-message-header">
                            <span class="psource-chat-username">${message.user_name}</span>
                            <span class="psource-chat-timestamp">${message.formatted_time}</span>
                        </div>
                        <div class="psource-chat-message-text">${message.message}</div>
                    </div>
                </div>
            `;
        });

        messagesContainer.html(html);
        scrollToBottom();
    }

    /**
     * Update users list
     */
    function updateUsersList(users) {
        const usersList = chatContainer.find('.psource-chat-user-list');
        
        if (!users || users.length === 0) {
            usersList.html('<div class="no-users">Keine Benutzer online</div>');
            return;
        }

        let html = '';
        users.forEach(function(user) {
            html += `
                <div class="psource-chat-user" data-user-id="${user.id}">
                    <img src="${user.avatar}" alt="${user.name}" class="user-avatar" />
                    <span class="user-name">${user.name}</span>
                    <span class="user-status status-${user.status}"></span>
                </div>
            `;
        });

        usersList.html(html);
    }

    /**
     * Handle typing indicator
     */
    function handleTyping() {
        clearTimeout(typingTimer);
        
        // Send typing indicator
        $.ajax({
            url: config.ajax_url || psourceChatFrontend.ajaxUrl,
            type: 'POST',
            data: {
                action: 'psource_chat_typing',
                nonce: config.nonce || psourceChatFrontend.nonce,
                typing: true
            }
        });

        // Stop typing after 3 seconds
        typingTimer = setTimeout(function() {
            $.ajax({
                url: config.ajax_url || psourceChatFrontend.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'psource_chat_typing',
                    nonce: config.nonce || psourceChatFrontend.nonce,
                    typing: false
                }
            });
        }, 3000);
    }

    /**
     * Update character counter
     */
    function updateCharCounter() {
        const input = chatContainer.find('.psource-chat-input');
        const counter = chatContainer.find('.current-length');
        
        if (counter.length) {
            counter.text(input.val().length);
        }
    }

    /**
     * Scroll to bottom
     */
    function scrollToBottom() {
        const messagesContainer = chatContainer.find('.psource-chat-messages');
        messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
    }

    /**
     * Auto-resize textarea
     */
    function autoResizeTextarea(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
    }

    /**
     * Start polling for new messages
     */
    function startPolling() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
        }
        
        pollingInterval = setInterval(loadMessages, 5000); // Every 5 seconds
    }

    /**
     * Stop polling
     */
    function stopPolling() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }
    }

    /**
     * Show error message
     */
    function showError(message) {
        console.error('Chat Error:', message);
        
        // Show in chat if available
        const errorHtml = `<div class="chat-error" style="background: #dc3232; color: white; padding: 8px; margin: 5px; border-radius: 3px; font-size: 12px;">${message}</div>`;
        chatContainer.find('.psource-chat-messages').prepend(errorHtml);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            chatContainer.find('.chat-error').fadeOut();
        }, 5000);
    }

    // Cleanup on page unload
    $(window).on('beforeunload', function() {
        stopPolling();
        clearTimeout(typingTimer);
    });

})(jQuery);
