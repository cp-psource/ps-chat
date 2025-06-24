/**
 * Support Chat Frontend JavaScript
 * 
 * Handles the support chat widget functionality
 */

(function() {
    'use strict';
    
    // Support Chat Widget Class
    class SupportChatWidget {
        constructor() {
            this.widget = null;
            this.button = null;
            this.messages = [];
            this.sessionId = null;
            this.isOpen = false;
            this.currentCategory = null;
            this.unreadCount = 0;
            this.heartbeatInterval = null;
            this.isTyping = false;
            
            this.init();
        }
        
        /**
         * Initialize the widget
         */
        init() {
            // Wait for DOM to be ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.setup());
            } else {
                this.setup();
            }
        }
        
        /**
         * Setup the widget
         */
        setup() {
            this.createButton();
            this.createWidget();
            this.bindEvents();
            this.startHeartbeat();
        }
        
        /**
         * Create the support chat button
         */
        createButton() {
            const config = window.psource_support_chat_config || {};
            
            this.button = document.createElement('button');
            this.button.className = `psource-support-chat-button position-${config.position || 'bottom-right'}`;
            this.button.innerHTML = `
                <span class="chat-icon"></span>
                <span class="button-text">${config.button_text || 'Support'}</span>
                <span class="psource-support-chat-badge" style="display: none;">0</span>
            `;
            
            document.body.appendChild(this.button);
        }
        
        /**
         * Create the chat widget
         */
        createWidget() {
            const config = window.psource_support_chat_config || {};
            
            this.widget = document.createElement('div');
            this.widget.className = `psource-support-chat-widget position-${config.position || 'bottom-right'}`;
            this.widget.innerHTML = `
                <div class="psource-support-chat-header">
                    <h3>${config.widget_title || 'Support Chat'}</h3>
                    <button class="psource-support-chat-close" type="button">&times;</button>
                </div>
                
                ${this.renderCategorySelection()}
                
                <div class="psource-support-chat-messages" id="support-chat-messages">
                    <div class="psource-support-chat-loading">
                        Verbindung wird hergestellt...
                    </div>
                </div>
                
                <div class="psource-support-chat-input">
                    <textarea 
                        placeholder="${config.input_placeholder || 'Nachricht eingeben...'}" 
                        rows="1"
                        id="support-chat-input"
                        disabled
                    ></textarea>
                    <button class="psource-support-chat-send" type="button" disabled></button>
                </div>
            `;
            
            document.body.appendChild(this.widget);
        }
        
        /**
         * Render category selection
         */
        renderCategorySelection() {
            const config = window.psource_support_chat_config || {};
            
            if (!config.categories || config.categories.length === 0) {
                return '';
            }
            
            const options = config.categories.map(cat => 
                `<option value="${cat.value}">${cat.label}</option>`
            ).join('');
            
            return `
                <div class="psource-support-chat-categories">
                    <h4>Wählen Sie eine Kategorie:</h4>
                    <select id="support-chat-category">
                        <option value="">Bitte wählen...</option>
                        ${options}
                    </select>
                </div>
            `;
        }
        
        /**
         * Bind events
         */
        bindEvents() {
            // Button click
            this.button.addEventListener('click', () => this.toggleWidget());
            
            // Close button
            const closeBtn = this.widget.querySelector('.psource-support-chat-close');
            closeBtn.addEventListener('click', () => this.closeWidget());
            
            // Category selection
            const categorySelect = this.widget.querySelector('#support-chat-category');
            if (categorySelect) {
                categorySelect.addEventListener('change', (e) => this.selectCategory(e.target.value));
            }
            
            // Input handling
            const input = this.widget.querySelector('#support-chat-input');
            const sendBtn = this.widget.querySelector('.psource-support-chat-send');
            
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });
            
            input.addEventListener('input', () => this.handleTyping());
            
            sendBtn.addEventListener('click', () => this.sendMessage());
            
            // Auto-resize textarea
            input.addEventListener('input', this.autoResizeTextarea.bind(this));
            
            // Click outside to close
            document.addEventListener('click', (e) => {
                if (!this.widget.contains(e.target) && !this.button.contains(e.target)) {
                    if (this.isOpen) {
                        this.closeWidget();
                    }
                }
            });
        }
        
        /**
         * Toggle widget visibility
         */
        toggleWidget() {
            if (this.isOpen) {
                this.closeWidget();
            } else {
                this.openWidget();
            }
        }
        
        /**
         * Open widget
         */
        openWidget() {
            this.isOpen = true;
            this.widget.classList.add('visible');
            this.button.classList.add('minimized');
            this.resetUnreadCount();
            
            if (!this.sessionId) {
                this.initializeSession();
            }
            
            // Focus input if available
            const input = this.widget.querySelector('#support-chat-input');
            if (input && !input.disabled) {
                setTimeout(() => input.focus(), 100);
            }
        }
        
        /**
         * Close widget
         */
        closeWidget() {
            this.isOpen = false;
            this.widget.classList.remove('visible');
            this.button.classList.remove('minimized');
        }
        
        /**
         * Select category
         */
        selectCategory(categoryValue) {
            if (!categoryValue) return;
            
            this.currentCategory = categoryValue;
            
            // Enable input
            const input = this.widget.querySelector('#support-chat-input');
            const sendBtn = this.widget.querySelector('.psource-support-chat-send');
            
            input.disabled = false;
            sendBtn.disabled = false;
            
            // Clear loading and add welcome message
            this.clearMessages();
            this.addSystemMessage('Kategorie ausgewählt. Sie können jetzt Ihre Nachricht eingeben.');
            
            // Initialize session with category
            this.initializeSession();
        }
        
        /**
         * Initialize chat session
         */
        async initializeSession() {
            try {
                const response = await this.apiCall('initialize_session', {
                    category: this.currentCategory
                });
                
                if (response.success) {
                    this.sessionId = response.data.session_id;
                    this.clearMessages();
                    
                    if (response.data.welcome_message) {
                        this.addSupportMessage(response.data.welcome_message);
                    }
                } else {
                    this.addSystemMessage('Fehler beim Starten der Chat-Session. Bitte versuchen Sie es erneut.');
                }
            } catch (error) {
                console.error('Session initialization error:', error);
                this.addSystemMessage('Verbindungsfehler. Bitte überprüfen Sie Ihre Internetverbindung.');
            }
        }
        
        /**
         * Send message
         */
        async sendMessage() {
            const input = this.widget.querySelector('#support-chat-input');
            const message = input.value.trim();
            
            if (!message || !this.sessionId) return;
            
            // Add user message to UI
            this.addUserMessage(message);
            input.value = '';
            this.autoResizeTextarea({ target: input });
            
            try {
                const response = await this.apiCall('send_message', {
                    session_id: this.sessionId,
                    message: message,
                    category: this.currentCategory
                });
                
                if (!response.success) {
                    this.addSystemMessage('Nachricht konnte nicht gesendet werden. Bitte versuchen Sie es erneut.');
                }
            } catch (error) {
                console.error('Send message error:', error);
                this.addSystemMessage('Verbindungsfehler beim Senden der Nachricht.');
            }
        }
        
        /**
         * Handle typing indicator
         */
        handleTyping() {
            if (!this.sessionId || this.isTyping) return;
            
            this.isTyping = true;
            
            // Send typing indicator
            this.apiCall('typing_indicator', {
                session_id: this.sessionId,
                typing: true
            });
            
            // Clear typing after 3 seconds
            setTimeout(() => {
                this.isTyping = false;
                this.apiCall('typing_indicator', {
                    session_id: this.sessionId,
                    typing: false
                });
            }, 3000);
        }
        
        /**
         * Add user message to chat
         */
        addUserMessage(message) {
            const messageEl = document.createElement('div');
            messageEl.className = 'psource-support-chat-message user';
            messageEl.innerHTML = `
                ${this.escapeHtml(message)}
                <div class="message-time">${this.formatTime(new Date())}</div>
            `;
            
            this.appendMessage(messageEl);
        }
        
        /**
         * Add support message to chat
         */
        addSupportMessage(message) {
            const messageEl = document.createElement('div');
            messageEl.className = 'psource-support-chat-message support';
            messageEl.innerHTML = `
                ${this.escapeHtml(message)}
                <div class="message-time">${this.formatTime(new Date())}</div>
            `;
            
            this.appendMessage(messageEl);
            
            // Increment unread count if widget is closed
            if (!this.isOpen) {
                this.incrementUnreadCount();
            }
        }
        
        /**
         * Add system message to chat
         */
        addSystemMessage(message) {
            const messageEl = document.createElement('div');
            messageEl.className = 'psource-support-chat-message system';
            messageEl.textContent = message;
            
            this.appendMessage(messageEl);
        }
        
        /**
         * Append message to chat
         */
        appendMessage(messageEl) {
            const messagesContainer = this.widget.querySelector('#support-chat-messages');
            messagesContainer.appendChild(messageEl);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
        
        /**
         * Clear all messages
         */
        clearMessages() {
            const messagesContainer = this.widget.querySelector('#support-chat-messages');
            messagesContainer.innerHTML = '';
        }
        
        /**
         * Start heartbeat to check for new messages
         */
        startHeartbeat() {
            this.heartbeatInterval = setInterval(() => {
                if (this.sessionId) {
                    this.checkNewMessages();
                }
            }, 5000); // Check every 5 seconds
        }
        
        /**
         * Check for new messages
         */
        async checkNewMessages() {
            try {
                const response = await this.apiCall('get_new_messages', {
                    session_id: this.sessionId
                });
                
                if (response.success && response.data.messages) {
                    response.data.messages.forEach(message => {
                        if (message.sender_type === 'support') {
                            this.addSupportMessage(message.message);
                        }
                    });
                }
            } catch (error) {
                console.error('Heartbeat error:', error);
            }
        }
        
        /**
         * Increment unread count
         */
        incrementUnreadCount() {
            this.unreadCount++;
            this.updateUnreadBadge();
        }
        
        /**
         * Reset unread count
         */
        resetUnreadCount() {
            this.unreadCount = 0;
            this.updateUnreadBadge();
        }
        
        /**
         * Update unread badge
         */
        updateUnreadBadge() {
            const badge = this.button.querySelector('.psource-support-chat-badge');
            if (this.unreadCount > 0) {
                badge.textContent = this.unreadCount;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }
        
        /**
         * Auto-resize textarea
         */
        autoResizeTextarea(event) {
            const textarea = event.target;
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 80) + 'px';
        }
        
        /**
         * Make API call
         */
        async apiCall(action, data = {}) {
            const formData = new FormData();
            formData.append('action', 'psource_support_chat_' + action);
            formData.append('nonce', window.psource_support_chat_nonce);
            
            Object.keys(data).forEach(key => {
                formData.append(key, data[key]);
            });
            
            const response = await fetch(window.ajaxurl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });
            
            return await response.json();
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
         * Format time
         */
        formatTime(date) {
            return date.toLocaleTimeString('de-DE', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        /**
         * Destroy widget
         */
        destroy() {
            if (this.heartbeatInterval) {
                clearInterval(this.heartbeatInterval);
            }
            
            if (this.widget) {
                this.widget.remove();
            }
            
            if (this.button) {
                this.button.remove();
            }
        }
    }
    
    // Initialize when config is available
    function initializeSupportChat() {
        if (window.psource_support_chat_config && window.psource_support_chat_config.enabled) {
            new SupportChatWidget();
        }
    }
    
    // Try to initialize immediately or wait for config
    if (window.psource_support_chat_config) {
        initializeSupportChat();
    } else {
        // Wait for config to be loaded
        document.addEventListener('psource_support_chat_ready', initializeSupportChat);
    }
    
})();
