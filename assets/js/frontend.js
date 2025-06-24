/**
 * PS Chat Frontend JavaScript
 * Modern ES6+ chat functionality
 */

class PSChat {
    constructor(options = {}) {
        this.options = {
            container: '.psource-chat-container',
            updateInterval: 3000,
            typingTimeout: 3000,
            maxRetries: 3,
            ...options
        };
        
        this.sessionId = null;
        this.userId = psource_chat.user_id || 0;
        this.isMinimized = false;
        this.isTyping = false;
        this.typingTimer = null;
        this.updateTimer = null;
        this.retryCount = 0;
        this.lastMessageTime = null;
        
        this.init();
    }
    
    /**
     * Initialize the chat
     */
    init() {
        this.createChatInterface();
        this.bindEvents();
        this.joinSession();
        this.startUpdateLoop();
    }
    
    /**
     * Create the chat interface HTML
     */
    createChatInterface() {
        const container = document.querySelector(this.options.container);
        if (!container) {
            this.createChatContainer();
        }
        
        this.elements = {
            container: document.querySelector(this.options.container),
            header: document.querySelector('.psource-chat-header'),
            messages: document.querySelector('.psource-chat-messages'),
            input: document.querySelector('.psource-chat-input'),
            sendBtn: document.querySelector('.psource-chat-send-btn'),
            usersList: document.querySelector('.psource-chat-user-list'),
            typingIndicator: document.querySelector('.psource-chat-typing'),
            minimizeBtn: document.querySelector('.psource-chat-minimize'),
            closeBtn: document.querySelector('.psource-chat-close')
        };
    }
    
    /**
     * Create chat container if it doesn't exist
     */
    createChatContainer() {
        const container = document.createElement('div');
        container.className = 'psource-chat-container';
        container.innerHTML = `
            <div class="psource-chat-header">
                <h3 class="psource-chat-title">${psource_chat.strings.chat_title || 'Chat'}</h3>
                <div class="psource-chat-controls">
                    <button class="psource-chat-btn psource-chat-minimize" title="Minimieren">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 13H5v-2h14v2z"/>
                        </svg>
                    </button>
                    <button class="psource-chat-btn psource-chat-close" title="Schließen">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="psource-chat-users">
                <div class="psource-chat-users-title">${psource_chat.strings.online || 'Online'}</div>
                <div class="psource-chat-user-list"></div>
            </div>
            <div class="psource-chat-messages"></div>
            <div class="psource-chat-typing"></div>
            <div class="psource-chat-input-area">
                <div class="psource-chat-input-container">
                    <textarea class="psource-chat-input" placeholder="${psource_chat.strings.type_message || 'Nachricht eingeben...'}" rows="1"></textarea>
                    <button class="psource-chat-send-btn" title="Senden">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                        </svg>
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(container);
    }
    
    /**
     * Bind event listeners
     */
    bindEvents() {
        // Header click to toggle minimize
        this.elements.header.addEventListener('click', () => this.toggleMinimize());
        
        // Control buttons
        this.elements.minimizeBtn?.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleMinimize();
        });
        
        this.elements.closeBtn?.addEventListener('click', (e) => {
            e.stopPropagation();
            this.closeChat();
        });
        
        // Send message
        this.elements.sendBtn.addEventListener('click', () => this.sendMessage());
        
        // Input events
        this.elements.input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });
        
        this.elements.input.addEventListener('input', () => {
            this.handleTyping();
            this.autoResize();
        });
        
        // Auto-scroll to bottom on new messages
        this.elements.messages.addEventListener('DOMNodeInserted', () => {
            this.scrollToBottom();
        });
        
        // Window visibility change
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.getMessages();
            }
        });
    }
    
    /**
     * Join chat session
     */
    async joinSession(sessionType = 'site') {
        try {
            const response = await this.apiRequest('join_session', {
                session_type: sessionType,
                session_id: this.sessionId
            });
            
            if (response.success) {
                this.sessionId = response.data.session_id;
                this.displayMessages(response.data.messages);
                this.updateUsersList(response.data.active_users);
                this.retryCount = 0;
            } else {
                this.showError(response.data.message);
            }
        } catch (error) {
            console.error('Failed to join session:', error);
            this.showError('Verbindung zum Chat fehlgeschlagen');
        }
    }
    
    /**
     * Send message
     */
    async sendMessage() {
        const message = this.elements.input.value.trim();
        if (!message || !this.sessionId) return;
        
        // Disable send button
        this.elements.sendBtn.disabled = true;
        this.elements.input.value = '';
        this.autoResize();
        
        try {
            const response = await this.apiRequest('send_message', {
                session_id: this.sessionId,
                message: message
            });
            
            if (response.success) {
                this.addMessage(response.data.message);
                this.stopTyping();
            } else {
                this.showError(response.data.message);
                this.elements.input.value = message; // Restore message
            }
        } catch (error) {
            console.error('Failed to send message:', error);
            this.showError('Nachricht konnte nicht gesendet werden');
            this.elements.input.value = message; // Restore message
        } finally {
            this.elements.sendBtn.disabled = false;
            this.elements.input.focus();
        }
    }
    
    /**
     * Get new messages
     */
    async getMessages() {
        if (!this.sessionId) return;
        
        try {
            const response = await this.apiRequest('get_messages', {
                session_id: this.sessionId,
                since: this.lastMessageTime
            });
            
            if (response.success) {
                this.displayMessages(response.data.messages);
                this.updateUsersList(response.data.active_users);
                this.lastMessageTime = response.data.timestamp;
                this.retryCount = 0;
            }
        } catch (error) {
            console.error('Failed to get messages:', error);
            this.retryCount++;
            
            if (this.retryCount >= this.options.maxRetries) {
                this.showError('Verbindung zum Chat verloren');
                this.stopUpdateLoop();
            }
        }
    }
    
    /**
     * Handle typing indicator
     */
    async handleTyping() {
        if (!this.sessionId) return;
        
        if (!this.isTyping) {
            this.isTyping = true;
            this.sendTypingStatus(true);
        }
        
        clearTimeout(this.typingTimer);
        this.typingTimer = setTimeout(() => {
            this.stopTyping();
        }, this.options.typingTimeout);
    }
    
    /**
     * Stop typing
     */
    stopTyping() {
        if (this.isTyping) {
            this.isTyping = false;
            this.sendTypingStatus(false);
        }
        clearTimeout(this.typingTimer);
    }
    
    /**
     * Send typing status
     */
    async sendTypingStatus(isTyping) {
        try {
            await this.apiRequest('user_typing', {
                session_id: this.sessionId,
                is_typing: isTyping
            });
        } catch (error) {
            console.error('Failed to send typing status:', error);
        }
    }
    
    /**
     * Get typing users
     */
    async getTypingUsers() {
        if (!this.sessionId) return;
        
        try {
            const response = await this.apiRequest('get_typing_users', {
                session_id: this.sessionId
            });
            
            if (response.success) {
                this.displayTypingIndicator(response.data.typing_users);
            }
        } catch (error) {
            console.error('Failed to get typing users:', error);
        }
    }
    
    /**
     * Display messages
     */
    displayMessages(messages) {
        if (!Array.isArray(messages) || messages.length === 0) return;
        
        messages.forEach(message => this.addMessage(message));
    }
    
    /**
     * Add single message
     */
    addMessage(message) {
        const messageElement = this.createMessageElement(message);
        this.elements.messages.appendChild(messageElement);
        this.scrollToBottom();
        
        // Play sound if enabled
        if (psource_chat.options.enable_sound && message.user_id !== this.userId) {
            this.playNotificationSound();
        }
    }
    
    /**
     * Create message element
     */
    createMessageElement(message) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `psource-chat-message ${message.user_id == this.userId ? 'own' : ''} ${message.is_private ? 'private' : ''}`;
        messageDiv.dataset.messageId = message.id;
        
        messageDiv.innerHTML = `
            <img src="${message.avatar}" alt="${message.user_name}" class="psource-chat-avatar">
            <div class="psource-chat-message-content">
                <div class="psource-chat-message-header">
                    <span class="psource-chat-username">${this.escapeHtml(message.user_name)}</span>
                    <span class="psource-chat-timestamp" title="${message.message_time}">${message.time_ago}</span>
                </div>
                <div class="psource-chat-message-text">${message.message_text}</div>
            </div>
        `;
        
        return messageDiv;
    }
    
    /**
     * Update users list
     */
    updateUsersList(users) {
        if (!Array.isArray(users)) return;
        
        this.elements.usersList.innerHTML = '';
        
        users.forEach(user => {
            const userElement = document.createElement('div');
            userElement.className = 'psource-chat-user';
            userElement.innerHTML = `
                <img src="${user.avatar}" alt="${user.user_name}" class="psource-chat-user-avatar">
                <span>${this.escapeHtml(user.user_name)}</span>
                <div class="psource-chat-user-status"></div>
            `;
            
            this.elements.usersList.appendChild(userElement);
        });
    }
    
    /**
     * Display typing indicator
     */
    displayTypingIndicator(typingUsers) {
        if (!Array.isArray(typingUsers) || typingUsers.length === 0) {
            this.elements.typingIndicator.innerHTML = '';
            return;
        }
        
        const filteredUsers = typingUsers.filter(user => user !== psource_chat.current_user_name);
        
        if (filteredUsers.length === 0) {
            this.elements.typingIndicator.innerHTML = '';
            return;
        }
        
        let text;
        if (filteredUsers.length === 1) {
            text = `${filteredUsers[0]} ${psource_chat.strings.typing || 'tippt...'}`;
        } else {
            text = `${filteredUsers.join(', ')} ${psource_chat.strings.typing || 'tippen...'}`;
        }
        
        this.elements.typingIndicator.innerHTML = `
            ${text}
            <span class="psource-chat-typing-dots">
                <span class="psource-chat-typing-dot"></span>
                <span class="psource-chat-typing-dot"></span>
                <span class="psource-chat-typing-dot"></span>
            </span>
        `;
    }
    
    /**
     * Toggle minimize
     */
    toggleMinimize() {
        this.isMinimized = !this.isMinimized;
        this.elements.container.classList.toggle('minimized', this.isMinimized);
        
        if (!this.isMinimized) {
            this.scrollToBottom();
            this.elements.input.focus();
        }
    }
    
    /**
     * Close chat
     */
    closeChat() {
        this.leaveSession();
        this.elements.container.style.display = 'none';
    }
    
    /**
     * Leave session
     */
    async leaveSession() {
        if (!this.sessionId) return;
        
        try {
            await this.apiRequest('leave_session', {
                session_id: this.sessionId
            });
        } catch (error) {
            console.error('Failed to leave session:', error);
        }
        
        this.stopUpdateLoop();
        this.sessionId = null;
    }
    
    /**
     * Start update loop
     */
    startUpdateLoop() {
        this.updateTimer = setInterval(() => {
            this.getMessages();
            this.getTypingUsers();
        }, this.options.updateInterval);
    }
    
    /**
     * Stop update loop
     */
    stopUpdateLoop() {
        if (this.updateTimer) {
            clearInterval(this.updateTimer);
            this.updateTimer = null;
        }
    }
    
    /**
     * Auto-resize textarea
     */
    autoResize() {
        const input = this.elements.input;
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 100) + 'px';
    }
    
    /**
     * Scroll to bottom
     */
    scrollToBottom() {
        this.elements.messages.scrollTop = this.elements.messages.scrollHeight;
    }
    
    /**
     * Play notification sound
     */
    playNotificationSound() {
        try {
            const audio = new Audio(`${psource_chat.plugin_url}/audio/chime.mp3`);
            audio.volume = 0.3;
            audio.play().catch(() => {
                // Ignore errors if autoplay is blocked
            });
        } catch (error) {
            console.log('Could not play notification sound:', error);
        }
    }
    
    /**
     * Show error message
     */
    showError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'psource-chat-error';
        errorDiv.textContent = message;
        
        this.elements.messages.appendChild(errorDiv);
        this.scrollToBottom();
        
        // Remove error after 5 seconds
        setTimeout(() => {
            if (errorDiv.parentNode) {
                errorDiv.parentNode.removeChild(errorDiv);
            }
        }, 5000);
    }
    
    /**
     * Show success message
     */
    showSuccess(message) {
        const successDiv = document.createElement('div');
        successDiv.className = 'psource-chat-success';
        successDiv.textContent = message;
        
        this.elements.messages.appendChild(successDiv);
        this.scrollToBottom();
        
        // Remove success after 3 seconds
        setTimeout(() => {
            if (successDiv.parentNode) {
                successDiv.parentNode.removeChild(successDiv);
            }
        }, 3000);
    }
    
    /**
     * Make API request
     */
    async apiRequest(action, data = {}) {
        const formData = new FormData();
        formData.append('action', 'psource_chat_action');
        formData.append('chat_action', action);
        formData.append('nonce', psource_chat.nonce);
        
        Object.keys(data).forEach(key => {
            formData.append(key, data[key]);
        });
        
        const response = await fetch(psource_chat.ajax_url, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        return result;
    }
    
    /**
     * Escape HTML
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    /**
     * Destroy chat instance
     */
    destroy() {
        this.leaveSession();
        this.stopUpdateLoop();
        clearTimeout(this.typingTimer);
        
        if (this.elements.container) {
            this.elements.container.remove();
        }
    }
}

// Initialize chat when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if chat should be shown
    if (typeof psource_chat !== 'undefined' && psource_chat.show_chat) {
        window.psChat = new PSChat();
    }
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (window.psChat) {
        window.psChat.destroy();
    }
});
