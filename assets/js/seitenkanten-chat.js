/**
 * Seitenkanten Chat - Complete Legacy Compatible Version
 * Based on original PS Chat functionality with full feature set
 */

(function($) {
    'use strict';
    
    // Global chat object
    window.PSSourceChat = {
        settings: {},
        initialized: false,
        minimized: false,
        userList: [],
        messages: [],
        updateTimer: null,
        typingTimer: null,
        soundEnabled: true,
        container: null,
        
        /**
         * Initialize the chat
         */
        init: function(config) {
            if (this.initialized) return;
            
            this.settings = $.extend({
                container_id: 'psource-chat-seitenkanten',
                position: 'bottom-right',
                width: 400,
                height: 500,
                title: 'PS Chat',
                initial_state: 'minimized',
                update_interval: 3000,
                user_id: 0,
                allow_guest_chat: false,
                ajax_url: ''
            }, config || {});
            
            // Find and prepare container
            this.container = this.findAndPrepareContainer();
            if (!this.container || !this.container.length) {
                console.error('PS Chat: Could not find or create container');
                return;
            }
            
            console.log('PS Chat: Container ready, initializing...');
            
            // CRITICAL: Ensure proper positioning BEFORE anything else
            this.ensureProperPositioning();
            
            // Load saved state from localStorage (highest priority)
            var savedState = this.loadState();
            console.log('PS Chat: Loaded saved state:', savedState);
            
            // Determine initial minimized state
            if (savedState !== null) {
                this.minimized = savedState;
                console.log('PS Chat: Using saved state:', this.minimized);
            } else if (this.container.hasClass('minimized')) {
                this.minimized = true;
                console.log('PS Chat: Using PHP minimized class');
            } else {
                this.minimized = (this.settings.initial_state === 'minimized');
                console.log('PS Chat: Using settings initial_state:', this.settings.initial_state);
            }
            
            // Apply the determined state
            this.container.toggleClass('minimized', this.minimized);
            console.log('PS Chat: Applied initial state - minimized:', this.minimized);
            
            this.bindEvents();
            this.setupChat();
            this.startUpdateLoop();
            this.monitorMinimizeIcon();
            
            // Set initial icon after everything is set up
            setTimeout(function() {
                this.updateMinimizeIcon();
            }.bind(this), 100);
            
            // Ensure positioning is maintained
            this.startPositionMonitoring();
            
            this.initialized = true;
            console.log('PSSource Chat initialized with state:', this.minimized ? 'minimized' : 'maximized');
        },
        
        /**
         * Find existing container or create one, ensuring it's properly positioned
         */
        findAndPrepareContainer: function() {
            var container = $('#' + this.settings.container_id);
            
            if (!container.length) {
                container = $('.psource-chat-container');
            }
            
            if (!container.length) {
                console.warn('PS Chat: No container found in DOM');
                return null;
            }
            
            console.log('PS Chat: Found container:', container.attr('id') || container.attr('class'));
            
            // CRITICAL: Move container to body if it's not already there
            if (container.parent()[0].tagName !== 'BODY') {
                console.log('PS Chat: Moving container to body from:', container.parent()[0].tagName);
                container.detach().appendTo('body');
            }
            
            return container;
        },
        
        /**
         * Ensure the chat container has proper fixed positioning
         */
        ensureProperPositioning: function() {
            if (!this.container || !this.container.length) return;
            
            console.log('PS Chat: Ensuring proper positioning...');
            
            // Remove any existing positioning that might interfere
            this.container.removeClass('psource-chat-bottom-right psource-chat-bottom-left psource-chat-top-right psource-chat-top-left');
            
            // Apply base positioning class
            this.container.addClass('psource-chat-' + this.settings.position);
            
            // Force critical CSS properties
            var positionStyles = {
                'position': 'fixed',
                'z-index': '999999',
                'display': 'block'
            };
            
            // Set position-specific coordinates
            switch(this.settings.position) {
                case 'bottom-right':
                    positionStyles.bottom = '20px';
                    positionStyles.right = '20px';
                    positionStyles.top = 'auto';
                    positionStyles.left = 'auto';
                    break;
                case 'bottom-left':
                    positionStyles.bottom = '20px';
                    positionStyles.left = '20px';
                    positionStyles.top = 'auto';
                    positionStyles.right = 'auto';
                    break;
                case 'top-right':
                    positionStyles.top = '20px';
                    positionStyles.right = '20px';
                    positionStyles.bottom = 'auto';
                    positionStyles.left = 'auto';
                    break;
                case 'top-left':
                    positionStyles.top = '20px';
                    positionStyles.left = '20px';
                    positionStyles.bottom = 'auto';
                    positionStyles.right = 'auto';
                    break;
            }
            
            // Apply styles with !important for maximum override power
            this.container.css(positionStyles);
            
            // Additional DOM attributes to prevent interference
            this.container.attr('data-ps-chat-positioned', 'true');
            
            console.log('PS Chat: Applied positioning styles:', positionStyles);
            
            // Verify positioning worked
            setTimeout(function() {
                this.verifyPositioning();
            }.bind(this), 100);
        },
        
        /**
         * Verify that positioning was applied correctly
         */
        verifyPositioning: function() {
            if (!this.container || !this.container.length) return;
            
            var computedStyle = window.getComputedStyle(this.container[0]);
            var rect = this.container[0].getBoundingClientRect();
            
            console.log('PS Chat: Position verification:');
            console.log('- CSS Position:', computedStyle.position);
            console.log('- Z-Index:', computedStyle.zIndex);
            console.log('- Bounding Rect:', rect);
            console.log('- Parent:', this.container.parent()[0].tagName);
            
            // Check for problems and fix them
            var problems = [];
            
            if (computedStyle.position !== 'fixed') {
                problems.push('Position not fixed');
                this.container.css('position', 'fixed');
            }
            
            if (parseInt(computedStyle.zIndex) < 999999) {
                problems.push('Z-index too low');
                this.container.css('z-index', '999999');
            }
            
            if (this.container.parent()[0].tagName !== 'BODY') {
                problems.push('Not in body');
                this.container.detach().appendTo('body');
            }
            
            // Check if chat is off-screen
            if (rect.bottom > window.innerHeight + 50 || rect.right > window.innerWidth + 50) {
                problems.push('Off-screen positioning');
                this.ensureProperPositioning();
            }
            
            if (problems.length > 0) {
                console.warn('PS Chat: Fixed positioning problems:', problems);
            } else {
                console.log('PS Chat: Positioning verification passed');
            }
        },
        
        /**
         * Start monitoring position to ensure it stays correct
         */
        startPositionMonitoring: function() {
            var self = this;
            
            // Monitor every 5 seconds
            setInterval(function() {
                if (self.container && self.container.length) {
                    var computedStyle = window.getComputedStyle(self.container[0]);
                    
                    if (computedStyle.position !== 'fixed' || 
                        parseInt(computedStyle.zIndex) < 999999 ||
                        self.container.parent()[0].tagName !== 'BODY') {
                        
                        console.log('PS Chat: Position drift detected, correcting...');
                        self.ensureProperPositioning();
                    }
                }
            }, 5000);
        },
        
        /**
         * Bind all event handlers
         */
        bindEvents: function() {
            var self = this;
            
            console.log('PS Chat: Binding events to container:', this.container);
            
            // Header click to toggle
            this.container.find('.psource-chat-header').on('click', function(e) {
                console.log('PS Chat: Header clicked');
                if (!$(e.target).hasClass('psource-chat-btn') && !$(e.target).closest('.psource-chat-btn').length) {
                    self.toggleMinimize();
                }
            });
            
            // Minimize button - Use event delegation for better reliability
            $(document).on('click', '.psource-chat-minimize', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('PS Chat: Minimize button clicked');
                setTimeout(function() {
                    self.toggleMinimize();
                }, 50);
            });
            
            // Settings button
            $(document).on('click', '.psource-chat-settings-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.showUserSettings();
            });
            
            // Moderation button
            $(document).on('click', '.psource-chat-moderate-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.showModerationTools();
            });
            
            // Emoji button
            $(document).on('click', '.psource-chat-emoji-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.toggleEmojiPicker();
            });
            
            // Message form submission
            this.container.on('submit', '.psource-chat-message-form', function(e) {
                e.preventDefault();
                self.sendMessage();
            });
            
            // Message input keypress
            this.container.on('keypress', '.psource-chat-message-input', function(e) {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    self.sendMessage();
                }
            });
            
            // Window resize - reposition chat
            $(window).off('resize.psourceChat').on('resize.psourceChat', function() {
                self.forcePositioning();
            });
            
            console.log('PS Chat: All event handlers bound');
        },
        
        /**
         * Setup chat interface
         */
        setupChat: function() {
            console.log('PS Chat: Setting up chat interface...');
            
            // IMMEDIATE FORCE POSITIONING - Override any theme interference
            this.forceFixedPositioning();
            
            // Apply initial minimized state
            this.container.toggleClass('minimized', this.minimized);
            console.log('PS Chat: Applied minimized state in setupChat:', this.minimized);
            
            // Apply position class AGAIN for certainty
            this.container.removeClass('psource-chat-bottom-right psource-chat-bottom-left psource-chat-top-right psource-chat-top-left');
            this.container.addClass('psource-chat-' + this.settings.position);
            console.log('PS Chat: Applied position class: psource-chat-' + this.settings.position);
            
            // Set dimensions
            this.container.css({
                'width': this.settings.width + 'px',
                'max-width': '90vw'
            });
            
            // Update title
            this.container.find('.psource-chat-title').text(this.settings.title);
            
            // Set correct initial icon
            this.updateMinimizeIcon();
            
            // AGGRESSIVE: Force positioning multiple times with delays
            setTimeout(function() {
                this.forceFixedPositioning();
                console.log('PS Chat: Re-forced positioning after 100ms');
            }.bind(this), 100);
            
            setTimeout(function() {
                this.forceFixedPositioning();
                console.log('PS Chat: Re-forced positioning after 500ms');
            }.bind(this), 500);
            
            setTimeout(function() {
                this.forceFixedPositioning();
                console.log('PS Chat: Re-forced positioning after 1000ms');
            }.bind(this), 1000);
            
            setTimeout(function() {
                this.forceFixedPositioning();
                console.log('PS Chat: Re-forced positioning after 2000ms');
            }.bind(this), 2000);
            
            // Load initial data
            this.loadMessages();
            this.loadActiveUsers();
            
            console.log('PS Chat: Setup completed');
        },
        
        /**
         * Force fixed positioning to override theme interference
         */
        forceFixedPositioning: function() {
            if (!this.container || !this.container.length) return;
            
            console.log('PS Chat: Force positioning starting...');
            
            // AGGRESSIVE positioning override - remove any conflicting styles
            this.container.css({
                'position': 'fixed !important',
                'z-index': '2147483647', // Maximum possible z-index
                'display': 'block !important',
                'float': 'none !important',
                'clear': 'none !important',
                'margin': '0 !important',
                'transform': 'none !important',
                'translate': 'none !important'
            });
            
            // Force position based on setting with !important
            var position = this.settings.position || 'bottom-right';
            console.log('PS Chat: Applying position:', position);
            
            // Remove ALL position classes first
            this.container.removeClass('psource-chat-bottom-right psource-chat-bottom-left psource-chat-top-right psource-chat-top-left');
            
            // Apply current position class
            this.container.addClass('psource-chat-' + position);
            
            var positions = {
                'bottom-right': { 
                    bottom: '20px !important', 
                    right: '20px !important', 
                    top: 'auto !important', 
                    left: 'auto !important' 
                },
                'bottom-left': { 
                    bottom: '20px !important', 
                    left: '20px !important', 
                    top: 'auto !important', 
                    right: 'auto !important' 
                },
                'top-right': { 
                    top: '20px !important', 
                    right: '20px !important', 
                    bottom: 'auto !important', 
                    left: 'auto !important' 
                },
                'top-left': { 
                    top: '20px !important', 
                    left: '20px !important', 
                    bottom: 'auto !important', 
                    right: 'auto !important' 
                }
            };
            
            if (positions[position]) {
                this.container.css(positions[position]);
                console.log('PS Chat: Applied position CSS:', positions[position]);
            }
            
            // Additional aggressive overrides
            this.container.attr('style', this.container.attr('style') + '; position: fixed !important; z-index: 2147483647 !important;');
            
            // Check if positioning worked
            var computedStyle = this.container.css(['position', 'top', 'right', 'bottom', 'left', 'z-index']);
            console.log('PS Chat: Final computed styles:', computedStyle);
            
            console.log('PS Chat: Force positioning completed for', position);
        },
        
        /**
         * Force proper positioning based on settings
         */
        forcePositioning: function() {
            this.forceFixedPositioning();
        },
        
        /**
         * Update minimize button icon
         */
        updateMinimizeIcon: function() {
            var minimizeBtn = this.container.find('.psource-chat-minimize');
            
            if (!minimizeBtn.length) {
                console.log('PS Chat: No minimize button found');
                return;
            }
            
            var svg = minimizeBtn.find('svg path');
            
            if (!svg.length) {
                console.log('PS Chat: No SVG path found in minimize button');
                return;
            }
            
            if (this.minimized) {
                // Show maximize icon (plus/expand)
                svg.attr('d', 'M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z');
                minimizeBtn.attr('title', 'Maximieren');
                console.log('PS Chat: Set icon to EXPAND (minimized state)');
            } else {
                // Show minimize icon (minus)
                svg.attr('d', 'M19 13H5v-2h14v2z');
                minimizeBtn.attr('title', 'Minimieren');
                console.log('PS Chat: Set icon to MINIMIZE (maximized state)');
            }
            
            // Force visibility
            minimizeBtn.css({
                'display': 'inline-block',
                'opacity': '1'
            });
        },
        
        /**
         * Toggle minimize state
         */
        toggleMinimize: function() {
            console.log('PS Chat: toggleMinimize called, current state:', this.minimized);
            
            // Toggle state
            this.minimized = !this.minimized;
            
            // Update container class
            this.container.toggleClass('minimized', this.minimized);
            
            console.log('PS Chat: New minimized state:', this.minimized);
            
            // Update the icon
            setTimeout(function() {
                this.updateMinimizeIcon();
            }.bind(this), 10);
            
            // Save state
            this.saveState();
            
            // Force positioning
            this.forceFixedPositioning();
            
            // Load messages if maximizing
            if (!this.minimized) {
                this.loadMessages();
            }
        },
        
        /**
         * Start update loop
         */
        startUpdateLoop: function() {
            if (this.updateTimer) {
                clearInterval(this.updateTimer);
            }
            
            var self = this;
            this.updateTimer = setInterval(function() {
                self.loadMessages();
                self.loadActiveUsers();
            }, this.settings.update_interval);
            
            console.log('PS Chat: Update loop started with interval:', this.settings.update_interval);
        },
        
        /**
         * Send message
         */
        sendMessage: function() {
            var input = this.container.find('.psource-chat-message-input');
            var message = input.val().trim();
            
            if (!message) return;
            
            console.log('PS Chat: Sending message:', message);
            
            // Clear input
            input.val('');
            
            // Send via AJAX
            this.ajaxRequest('psource_chat_send_message', {
                message: message,
                guest_name: this.container.find('.psource-chat-guest-name').val() || ''
            }, function(response) {
                if (response.success) {
                    console.log('PS Chat: Message sent successfully');
                    this.loadMessages();
                } else {
                    console.error('PS Chat: Error sending message:', response.data);
                }
            }.bind(this));
        },
        
        /**
         * Load messages
         */
        loadMessages: function() {
            this.ajaxRequest('psource_chat_get_messages', {
                last_id: this.getLastMessageId()
            }, function(response) {
                if (response.success && response.data) {
                    this.displayMessages(response.data);
                }
            }.bind(this));
        },
        
        /**
         * Load active users
         */
        loadActiveUsers: function() {
            this.ajaxRequest('psource_chat_get_users', {}, function(response) {
                if (response.success && response.data && response.data.users) {
                    this.displayUsers(response.data.users);
                }
            }.bind(this));
        },
        
        /**
         * Display messages
         */
        displayMessages: function(data) {
            if (!data.messages || !data.messages.length) return;
            
            console.log('PS Chat: Displaying', data.messages.length, 'messages');
            
            var messagesContainer = this.container.find('.psource-chat-messages');
            var hasNewMessages = false;
            
            data.messages.forEach(function(message) {
                if (this.addMessageToDisplay(message)) {
                    hasNewMessages = true;
                }
            }.bind(this));
            
            if (hasNewMessages) {
                this.scrollToBottom();
                if (this.soundEnabled && this.minimized) {
                    this.playSound();
                }
            }
        },
        
        /**
         * Add single message to display
         */
        addMessageToDisplay: function(message) {
            var messagesContainer = this.container.find('.psource-chat-messages');
            
            // Check if message already exists
            var existingMessage = messagesContainer.find('[data-message-id="' + message.id + '"]');
            if (existingMessage.length > 0) {
                return false;
            }
            
            var messageHtml = this.formatMessage(message);
            messagesContainer.append(messageHtml);
            
            return true; // New message added
        },
        
        /**
         * Format message HTML
         */
        formatMessage: function(message) {
            var timestamp = this.formatTime(message.timestamp);
            var avatar = message.avatar || this.getDefaultAvatar();
            
            return '<div class="psource-chat-message" data-message-id="' + message.id + '">' +
                '<div class="message-avatar">' +
                '<img src="' + avatar + '" alt="' + this.escapeHtml(message.user_name) + '" />' +
                '</div>' +
                '<div class="message-content">' +
                '<div class="message-header">' +
                '<span class="message-user">' + this.escapeHtml(message.user_name) + '</span>' +
                '<span class="message-time">' + timestamp + '</span>' +
                '</div>' +
                '<div class="message-text">' + this.escapeHtml(message.message) + '</div>' +
                '</div>' +
                '</div>';
        },
        
        /**
         * Display users
         */
        displayUsers: function(users) {
            var usersList = this.container.find('.psource-chat-user-list-content');
            if (!usersList.length) return;
            
            usersList.empty();
            
            if (!users || !users.length) {
                usersList.html('<div class="no-users">Keine Benutzer online</div>');
                return;
            }
            
            users.forEach(function(user) {
                var userHtml = '<div class="user-item" data-user-id="' + user.id + '">' +
                    '<img src="' + user.avatar + '" alt="' + user.name + '" class="user-avatar" />' +
                    '<span class="user-name">' + user.name + '</span>' +
                    '<span class="user-status ' + user.status + '">' + user.status + '</span>' +
                    '</div>';
                usersList.append(userHtml);
            });
            
            this.userList = users;
        },
        
        /**
         * Toggle emoji picker
         */
        toggleEmojiPicker: function() {
            console.log('PS Chat: Toggle emoji picker called');
            
            // Toggle behavior: if picker exists, remove it
            var existingPicker = $('.psource-chat-emoji-picker');
            if (existingPicker.length) {
                existingPicker.fadeOut(200, function() {
                    $(this).remove();
                });
                return;
            }
            
            // Remove any other menus first
            $('.psource-chat-settings-menu, .psource-chat-moderation-menu').remove();
            
            // Create picker if it doesn't exist
            this.createEmojiPicker();
            
            var picker = $('.psource-chat-emoji-picker');
            if (picker.length === 0) {
                console.log('PS Chat: Failed to create emoji picker');
                return;
            }
            
            // Position picker relative to emoji button
            var emojiBtn = this.container.find('.psource-chat-emoji-btn');
            if (emojiBtn.length > 0) {
                var btnOffset = emojiBtn.offset();
                var windowWidth = $(window).width();
                var windowHeight = $(window).height();
                
                var pickerWidth = 300;
                var pickerHeight = 250;
                
                var left = btnOffset.left;
                var bottom = windowHeight - btnOffset.top + 10;
                
                // Adjust if picker would go off-screen
                if (left + pickerWidth > windowWidth) {
                    left = windowWidth - pickerWidth - 20;
                }
                if (left < 20) {
                    left = 20;
                }
                
                picker.css({
                    'position': 'fixed',
                    'left': left + 'px',
                    'bottom': bottom + 'px',
                    'z-index': '1000001'
                });
            }
            
            picker.fadeIn(200);
            this.switchEmojiCategory('smileys');
            
            // Hide when clicking outside
            setTimeout(function() {
                $(document).on('click.emojiOutside', function(e) {
                    if (!$(e.target).closest('.psource-chat-emoji-picker, .psource-chat-emoji-btn').length) {
                        $('.psource-chat-emoji-picker').fadeOut(200, function() {
                            $(this).remove();
                        });
                        $(document).off('click.emojiOutside');
                    }
                });
            }, 100);
        },
        
        /**
         * Create emoji picker
         */
        createEmojiPicker: function() {
            if ($('.psource-chat-emoji-picker').length > 0) return;
            
            // Original emoji string from legacy code
            var emoji_string = '😀,😃,😄,😁,😆,😅,🤣,😂,😊,😇,😍,🤩,😘,😗,😚,😛,🤪,😜,😝,🤑,🤗,🤭,🤫,🤔,🤐,🤨,😐,😑,😶,😏,😒,🙄,😬,🤥,😔,😪,🤤,😴,😷,🤒,🤕,🤢,🤮,🤧,🥵,🥶,🥴,😵,🤯,🤠,🥳,😎,🤓,🧐,😕,😟,😮,😳,😨,😢,😭,😱,😣,😓,😫,😤,🥱,😠,🤬,😈,👿,💩,🤡,👽,👻,💋,👋,🤚,🖐️,✋,🖖,👌,🤏,✌️,🤞,🤘,🤙,👍,👎,✊,👊,🤝,🙏,💪,👂,👃,🧠,👅,👄';
            
            var all_emojis = emoji_string.split(',');
            var smileys = all_emojis.slice(0, 30);
            var gestures = all_emojis.slice(30, 50);
            var misc = all_emojis.slice(50);
            
            var pickerHtml = '<div class="psource-chat-emoji-picker" style="display: none;">' +
                '<div class="emoji-picker-tabs">' +
                '<button class="emoji-tab active" data-category="smileys">😊</button>' +
                '<button class="emoji-tab" data-category="gestures">👍</button>' +
                '<button class="emoji-tab" data-category="misc">💪</button>' +
                '</div>' +
                '<div class="emoji-picker-content">' +
                '<div class="emoji-category" data-category="smileys">' +
                this.createEmojiButtons(smileys) +
                '</div>' +
                '<div class="emoji-category" data-category="gestures" style="display:none;">' +
                this.createEmojiButtons(gestures) +
                '</div>' +
                '<div class="emoji-category" data-category="misc" style="display:none;">' +
                this.createEmojiButtons(misc) +
                '</div>' +
                '</div>' +
                '</div>';
            
            $('body').append(pickerHtml);
            this.bindEmojiPickerEvents();
        },
        
        /**
         * Create emoji buttons HTML
         */
        createEmojiButtons: function(emojis) {
            var html = '';
            emojis.forEach(function(emoji) {
                html += '<span class="emoji-btn" data-emoji="' + emoji + '">' + emoji + '</span>';
            });
            return html;
        },
        
        /**
         * Bind emoji picker events
         */
        bindEmojiPickerEvents: function() {
            var self = this;
            
            // Emoji clicks
            $(document).off('click.emojiPicker').on('click.emojiPicker', '.psource-chat-emoji-picker .emoji-btn', function(e) {
                e.preventDefault();
                var emoji = $(this).data('emoji');
                self.insertEmoji(emoji);
                $('.psource-chat-emoji-picker').fadeOut(200, function() {
                    $(this).remove();
                });
            });
            
            // Category tabs
            $(document).off('click.emojiTabs').on('click.emojiTabs', '.psource-chat-emoji-picker .emoji-tab', function(e) {
                e.preventDefault();
                var category = $(this).data('category');
                self.switchEmojiCategory(category);
            });
        },
        
        /**
         * Switch emoji category
         */
        switchEmojiCategory: function(category) {
            $('.psource-chat-emoji-picker .emoji-tab').removeClass('active');
            $('.psource-chat-emoji-picker .emoji-tab[data-category="' + category + '"]').addClass('active');
            
            $('.psource-chat-emoji-picker .emoji-category').hide();
            $('.psource-chat-emoji-picker .emoji-category[data-category="' + category + '"]').show();
        },
        
        /**
         * Insert emoji into input
         */
        insertEmoji: function(emoji) {
            var input = this.container.find('.psource-chat-message-input')[0];
            if (!input) return;
            
            var start = input.selectionStart;
            var end = input.selectionEnd;
            var text = input.value;
            
            input.value = text.substring(0, start) + emoji + text.substring(end);
            input.selectionStart = input.selectionEnd = start + emoji.length;
            input.focus();
        },
        
        /**
         * Show user settings
         */
        showUserSettings: function() {
            console.log('PS Chat: Showing user settings');
            
            // Toggle behavior
            var existingMenu = $('.psource-chat-settings-menu');
            if (existingMenu.length) {
                existingMenu.remove();
                return;
            }
            
            $('.psource-chat-moderation-menu, .psource-chat-emoji-picker').remove();
            
            var menuHtml = '<div class="psource-chat-settings-menu">' +
                '<div class="settings-menu-header">Einstellungen</div>' +
                '<div class="settings-option">' +
                '<label>Sound:</label>' +
                '<select name="enable_sound" class="settings-select">' +
                '<option value="yes">An</option>' +
                '<option value="no">Aus</option>' +
                '</select>' +
                '</div>' +
                '<div class="settings-option">' +
                '<label>Benachrichtigungen:</label>' +
                '<select name="enable_notifications" class="settings-select">' +
                '<option value="yes">An</option>' +
                '<option value="no">Aus</option>' +
                '</select>' +
                '</div>' +
                '</div>';
            
            $('body').append(menuHtml);
            this.positionMenu('.psource-chat-settings-menu', '.psource-chat-settings-btn');
            this.loadUserSettingsInMenu();
        },
        
        /**
         * Show moderation tools
         */
        showModerationTools: function() {
            console.log('PS Chat: Showing moderation tools');
            
            var existingMenu = $('.psource-chat-moderation-menu');
            if (existingMenu.length) {
                existingMenu.remove();
                return;
            }
            
            $('.psource-chat-settings-menu, .psource-chat-emoji-picker').remove();
            
            var menuHtml = '<div class="psource-chat-moderation-menu">' +
                '<div class="moderation-menu-header">Moderation</div>' +
                '<div class="moderation-menu-item" data-action="clear_messages">Nachrichten löschen</div>' +
                '<div class="moderation-menu-item" data-action="ban_user">Benutzer bannen</div>' +
                '<div class="moderation-menu-item" data-action="kick_user">Benutzer kicken</div>' +
                '</div>';
            
            $('body').append(menuHtml);
            this.positionMenu('.psource-chat-moderation-menu', '.psource-chat-moderate-btn');
        },
        
        /**
         * Position menu relative to button
         */
        positionMenu: function(menuSelector, buttonSelector) {
            var btn = this.container.find(buttonSelector);
            var menu = $(menuSelector);
            
            if (btn.length && menu.length) {
                var btnOffset = btn.offset();
                var btnHeight = btn.outerHeight();
                var btnWidth = btn.outerWidth();
                
                menu.css({
                    'position': 'fixed',
                    'top': (btnOffset.top + btnHeight + 5) + 'px',
                    'left': (btnOffset.left - menu.outerWidth() + btnWidth) + 'px',
                    'z-index': '1000001'
                });
            }
        },
        
        /**
         * Load user settings into menu
         */
        loadUserSettingsInMenu: function() {
            var settings = this.getUserSettings();
            $('.psource-chat-settings-menu select[name="enable_sound"]').val(settings.enable_sound);
            $('.psource-chat-settings-menu select[name="enable_notifications"]').val(settings.enable_notifications);
        },
        
        /**
         * Get user settings
         */
        getUserSettings: function() {
            return {
                enable_sound: localStorage.getItem('psource_chat_user_sound') || 'yes',
                enable_notifications: localStorage.getItem('psource_chat_user_notifications') || 'yes'
            };
        },
        
        /**
         * Save user setting
         */
        saveUserSetting: function(name, value) {
            localStorage.setItem('psource_chat_user_' + name, value);
            
            if (name === 'enable_sound') {
                this.soundEnabled = (value === 'yes');
            }
        },
        
        /**
         * Play sound
         */
        playSound: function() {
            if (!this.soundEnabled) return;
            
            try {
                var audio = new Audio(psource_chat_ajax.sound_url || '');
                audio.volume = 0.5;
                audio.play().catch(function() {
                    console.log('Could not play notification sound');
                });
            } catch (e) {
                console.log('Sound error:', e);
            }
        },
        
        /**
         * Scroll to bottom
         */
        scrollToBottom: function() {
            var messages = this.container.find('.psource-chat-messages');
            if (messages.length) {
                messages.scrollTop(messages[0].scrollHeight);
            }
        },
        
        /**
         * Save state
         */
        saveState: function() {
            if (typeof Storage !== 'undefined') {
                localStorage.setItem('psource_chat_minimized', this.minimized ? '1' : '0');
                console.log('PS Chat: State saved:', this.minimized);
            }
        },
        
        /**
         * Load state
         */
        loadState: function() {
            if (typeof Storage !== 'undefined') {
                var saved = localStorage.getItem('psource_chat_minimized');
                if (saved !== null) {
                    return saved === '1';
                }
            }
            return null;
        },
        
        /**
         * Monitor minimize icon
         */
        monitorMinimizeIcon: function() {
            var self = this;
            
            // Monitor both icon AND positioning
            setInterval(function() {
                // Check minimize icon
                var btn = self.container.find('.psource-chat-minimize');
                if (btn.length && btn.find('svg path').length === 0) {
                    console.log('PS Chat: Minimize icon disappeared, restoring...');
                    self.updateMinimizeIcon();
                }
                
                // Check positioning - if position is not fixed, re-apply
                var currentPosition = self.container.css('position');
                if (currentPosition !== 'fixed') {
                    console.log('PS Chat: Position changed from fixed to', currentPosition, '- re-applying!');
                    self.forceFixedPositioning();
                }
                
                // Check if chat is in wrong location (e.g., in footer)
                var containerOffset = self.container.offset();
                var windowHeight = $(window).height();
                var expectedPosition = self.settings.position || 'bottom-right';
                
                if (expectedPosition.includes('bottom')) {
                    // For bottom positions, chat should be near bottom of viewport
                    var expectedBottom = windowHeight - 100; // Should be within 100px of bottom
                    if (containerOffset && containerOffset.top > expectedBottom + 200) {
                        console.log('PS Chat: Chat appears to be below viewport (in footer?) - re-positioning!');
                        console.log('PS Chat: Container top:', containerOffset.top, 'Expected max:', expectedBottom + 200);
                        self.forceFixedPositioning();
                    }
                }
                
            }, 3000); // Check every 3 seconds
        },
        
        /**
         * Helper functions
         */
        escapeHtml: function(text) {
            return $('<div>').text(text).html();
        },
        
        formatTime: function(timestamp) {
            var date = new Date(timestamp);
            return date.toLocaleTimeString('de-DE', {
                hour: '2-digit',
                minute: '2-digit'
            });
        },
        
        getDefaultAvatar: function() {
            return psource_chat_ajax.default_avatar || '';
        },
        
        getLastMessageId: function() {
            var lastMessage = this.container.find('.psource-chat-message').last();
            return lastMessage.data('message-id') || 0;
        },
        
        /**
         * AJAX request helper
         */
        ajaxRequest: function(action, data, callback) {
            console.log('PS Chat: AJAX Request', action, data);
            
            $.ajax({
                url: psource_chat_ajax.ajax_url,
                type: 'POST',
                data: $.extend({
                    action: action,
                    nonce: psource_chat_ajax.nonce
                }, data),
                success: function(response) {
                    console.log('PS Chat: AJAX Success', action, response);
                    if (typeof callback === 'function') {
                        callback(response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('PS Chat: AJAX Error', {
                        action: action,
                        status: status,
                        error: error
                    });
                    if (typeof callback === 'function') {
                        callback({success: false, error: error});
                    }
                }
            });
        }
    };
    
    // Auto-initialize when DOM ready
    $(document).ready(function() {
        console.log('PS Chat: DOM Ready - starting emergency positioning protocol');
        
        // PHASE 1: Immediate emergency positioning for any existing chat containers
        $('.psource-chat-container, #psource-chat-seitenkanten').each(function() {
            var $chat = $(this);
            console.log('PS Chat: Found chat container:', $chat.attr('id') || $chat.attr('class'));
            
            // Emergency relocation to body
            if (!$chat.parent().is('body')) {
                console.log('PS Chat: EMERGENCY - Moving chat from', $chat.parent().get(0).tagName, 'to body');
                $chat.detach().appendTo('body');
            }
            
            // Apply emergency fixed positioning immediately with maximum priority
            $chat.css({
                'position': 'fixed',
                'bottom': '20px',
                'right': '20px',
                'top': 'auto',
                'left': 'auto',
                'z-index': '2147483647',
                'display': 'block'
            }).attr('style', $chat.attr('style') + '; position: fixed !important; bottom: 20px !important; right: 20px !important; z-index: 2147483647 !important;');
            
            console.log('PS Chat: Emergency positioning applied to:', $chat.attr('id'));
        });
        
        // PHASE 2: Initialize chat functionality if conditions are met
        if (typeof psourceChatFrontend !== 'undefined' && $('.psource-chat-container').length > 0) {
            console.log('PS Chat: Conditions met, initializing...');
            
            // Get configuration from WordPress localization
            var config = {
                ajax_url: psourceChatFrontend.ajaxUrl,
                user_id: psourceChatFrontend.userId || 0,
                nonce: psourceChatFrontend.nonce
            };
            
            // Merge with extension options if available
            if (psourceChatFrontend.extensionOptions && psourceChatFrontend.extensionOptions.frontend) {
                var frontendOptions = psourceChatFrontend.extensionOptions.frontend;
                config.position = frontendOptions.position || 'bottom-right';
                config.width = frontendOptions.width || 400;
                config.height = frontendOptions.height || 500;
                config.title = frontendOptions.title || 'PS Chat';
                config.initial_state = frontendOptions.initial_state || 'minimized';
                config.allow_guest_chat = frontendOptions.allow_guest_chat === 'yes';
            }
            
            console.log('PS Chat: Configuration:', config);
            
            // Initialize with configuration
            PSSourceChat.init(config);
        } else {
            console.log('PS Chat: Required objects not found');
            console.log('- psourceChatFrontend:', typeof psourceChatFrontend);
            console.log('- chat containers:', $('.psource-chat-container').length);
        }
    });
    
    // PHASE 3: Continuous monitoring to prevent theme interference
    $(window).on('load', function() {
        console.log('PS Chat: Window loaded - starting continuous monitoring');
        
        // Monitor every 2 seconds for theme interference
        setInterval(function() {
            $('.psource-chat-container, #psource-chat-seitenkanten').each(function() {
                var $chat = $(this);
                var currentParent = $chat.parent().get(0);
                var currentPosition = $chat.css('position');
                
                // Check if chat was moved out of body
                if (currentParent.tagName !== 'BODY') {
                    console.log('PS Chat: ALERT - Chat moved to', currentParent.tagName, '- relocating to body!');
                    $chat.detach().appendTo('body');
                }
                
                // Check if position was changed from fixed
                if (currentPosition !== 'fixed') {
                    console.log('PS Chat: ALERT - Position changed to', currentPosition, '- restoring fixed!');
                    $chat.css('position', 'fixed');
                }
                
                // Check if chat is below viewport (in footer area)
                var rect = $chat.get(0).getBoundingClientRect();
                if (rect.top > window.innerHeight + 100) {
                    console.log('PS Chat: ALERT - Chat below viewport (possibly in footer) - emergency repositioning!');
                    $chat.css({
                        'position': 'fixed',
                        'bottom': '20px',
                        'right': '20px',
                        'top': 'auto',
                        'z-index': '2147483647'
                    });
                }
            });
        }, 2000);
    });
    
    // PHASE 4: MutationObserver to catch dynamic DOM changes
    if (window.MutationObserver) {
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) { // Element node
                            var $node = $(node);
                            
                            // Check if a chat container was added
                            if ($node.hasClass('psource-chat-container') || $node.attr('id') === 'psource-chat-seitenkanten') {
                                console.log('PS Chat: New chat container detected in DOM - applying emergency positioning');
                                
                                // Immediate emergency positioning
                                if (!$node.parent().is('body')) {
                                    $node.appendTo('body');
                                }
                                
                                $node.css({
                                    'position': 'fixed',
                                    'bottom': '20px',
                                    'right': '20px',
                                    'z-index': '2147483647'
                                });
                            }
                            
                            // Check if chat container was added inside this node
                            $node.find('.psource-chat-container, #psource-chat-seitenkanten').each(function() {
                                var $chat = $(this);
                                console.log('PS Chat: Chat container found inside new node - relocating');
                                $chat.detach().appendTo('body').css({
                                    'position': 'fixed',
                                    'bottom': '20px',
                                    'right': '20px',
                                    'z-index': '2147483647'
                                });
                            });
                        }
                    });
                }
            });
        });
        
        // Start observing
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
})(jQuery);