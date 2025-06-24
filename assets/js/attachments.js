/**
 * PS Chat Attachments System
 * Handles emojis, GIFs, and file uploads
 */

(function($) {
    'use strict';
    
    window.PSChatAttachments = {
        settings: {},
        initialized: false,
        emojiPicker: null,
        gifPicker: null,
        
        /**
         * Initialize attachments system
         */
        init: function(settings) {
            if (this.initialized) return;
            
            this.settings = $.extend({
                emojisEnabled: false,
                gifsEnabled: false,
                uploadsEnabled: false,
                emojiSource: 'builtin',
                customEmojis: [],
                maxFileSize: 5,
                allowedTypes: ['jpg', 'png', 'gif'],
                ajaxUrl: '',
                nonce: ''
            }, settings || {});
            
            this.bindEvents();
            this.createComponents();
            
            this.initialized = true;
            console.log('PS Chat Attachments initialized');
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            var self = this;
            
            // Emoji button click
            $(document).on('click', '.psource-chat-emoji-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.toggleEmojiPicker($(this));
            });
            
            // GIF button click
            $(document).on('click', '.psource-chat-gif-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.toggleGifPicker($(this));
            });
            
            // Upload button click
            $(document).on('click', '.psource-chat-upload-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.showUploadDialog($(this));
            });
            
            // Click outside to close pickers
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.psource-chat-attachment-picker').length) {
                    self.closeAllPickers();
                }
            });
        },
        
        /**
         * Create attachment components
         */
        createComponents: function() {
            if (this.settings.emojisEnabled) {
                this.createEmojiPicker();
            }
            
            if (this.settings.gifsEnabled) {
                this.createGifPicker();
            }
            
            if (this.settings.uploadsEnabled) {
                this.createUploadHandler();
            }
        },
        
        /**
         * Toggle emoji picker
         */
        toggleEmojiPicker: function(button) {
            var picker = $('#psource-chat-emoji-picker');
            
            if (picker.is(':visible')) {
                this.closeAllPickers();
                return;
            }
            
            this.closeAllPickers();
            this.positionPicker(picker, button);
            picker.show();
        },
        
        /**
         * Toggle GIF picker
         */
        toggleGifPicker: function(button) {
            var picker = $('#psource-chat-gif-picker');
            
            if (picker.is(':visible')) {
                this.closeAllPickers();
                return;
            }
            
            this.closeAllPickers();
            this.positionPicker(picker, button);
            picker.show();
        },
        
        /**
         * Show upload dialog
         */
        showUploadDialog: function(button) {
            var input = $('<input type="file" style="display: none;">');
            input.attr('accept', this.getAllowedTypesAttribute());
            
            var self = this;
            input.on('change', function() {
                var file = this.files[0];
                if (file) {
                    self.handleFileSelect(file, button);
                }
            });
            
            $('body').append(input);
            input.click();
            
            // Clean up
            setTimeout(function() {
                input.remove();
            }, 1000);
        },
        
        /**
         * Position picker relative to button
         */
        positionPicker: function(picker, button) {
            var buttonOffset = button.offset();
            var buttonHeight = button.outerHeight();
            var pickerHeight = picker.outerHeight();
            var windowHeight = $(window).height();
            
            var top = buttonOffset.top - pickerHeight - 10;
            var left = buttonOffset.left;
            
            // Adjust if picker would go above viewport
            if (top < 10) {
                top = buttonOffset.top + buttonHeight + 10;
            }
            
            // Adjust horizontal position if needed
            var pickerWidth = picker.outerWidth();
            if (left + pickerWidth > $(window).width() - 20) {
                left = $(window).width() - pickerWidth - 20;
            }
            
            picker.css({
                position: 'fixed',
                top: top + 'px',
                left: left + 'px',
                zIndex: 999999
            });
        },
        
        /**
         * Close all pickers
         */
        closeAllPickers: function() {
            $('.psource-chat-attachment-picker').hide();
        },
        
        /**
         * Create emoji picker
         */
        createEmojiPicker: function() {
            if ($('#psource-chat-emoji-picker').length > 0) return;
            
            var emojis = this.getEmojis();
            var categories = this.categorizeEmojis(emojis);
            
            var html = '<div id="psource-chat-emoji-picker" class="psource-chat-attachment-picker psource-chat-emoji-picker" style="display: none;">';
            
            // Tabs
            html += '<div class="emoji-picker-tabs">';
            var isFirst = true;
            for (var category in categories) {
                html += '<button type="button" class="emoji-tab' + (isFirst ? ' active' : '') + '" data-category="' + category + '">';
                html += this.getCategoryIcon(category);
                html += '</button>';
                isFirst = false;
            }
            html += '</div>';
            
            // Content
            html += '<div class="emoji-picker-content">';
            isFirst = true;
            for (var category in categories) {
                html += '<div class="emoji-category" data-category="' + category + '"' + (isFirst ? '' : ' style="display: none;"') + '>';
                categories[category].forEach(function(emoji) {
                    html += '<span class="emoji-item" data-emoji="' + emoji + '">' + emoji + '</span>';
                });
                html += '</div>';
                isFirst = false;
            }
            html += '</div>';
            
            html += '</div>';
            
            $('body').append(html);
            this.bindEmojiPickerEvents();
        },
        
        /**
         * Create GIF picker
         */
        createGifPicker: function() {
            if ($('#psource-chat-gif-picker').length > 0) return;
            
            var html = '<div id="psource-chat-gif-picker" class="psource-chat-attachment-picker psource-chat-gif-picker" style="display: none;">';
            html += '<div class="gif-picker-header">';
            html += '<input type="text" class="gif-search-input" placeholder="' + this.settings.strings.searchGifs + '">';
            html += '</div>';
            html += '<div class="gif-picker-content">';
            html += '<div class="gif-loading" style="display: none;">🔄 Laden...</div>';
            html += '<div class="gif-results"></div>';
            html += '</div>';
            html += '</div>';
            
            $('body').append(html);
            this.bindGifPickerEvents();
        },
        
        /**
         * Create upload handler
         */
        createUploadHandler: function() {
            // Upload functionality would be implemented here
            console.log('Upload handler created');
        },
        
        /**
         * Bind emoji picker events
         */
        bindEmojiPickerEvents: function() {
            var self = this;
            
            // Tab switching
            $(document).on('click', '.emoji-tab', function() {
                var category = $(this).data('category');
                
                $('.emoji-tab').removeClass('active');
                $(this).addClass('active');
                
                $('.emoji-category').hide();
                $('.emoji-category[data-category="' + category + '"]').show();
            });
            
            // Emoji selection
            $(document).on('click', '.emoji-item', function() {
                var emoji = $(this).data('emoji');
                self.insertEmoji(emoji);
                self.closeAllPickers();
            });
        },
        
        /**
         * Bind GIF picker events
         */
        bindGifPickerEvents: function() {
            var self = this;
            var searchTimeout;
            
            // Search input
            $(document).on('input', '.gif-search-input', function() {
                var query = $(this).val();
                
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    if (query.length > 2) {
                        self.searchGifs(query);
                    }
                }, 500);
            });
            
            // GIF selection
            $(document).on('click', '.gif-item', function() {
                var gifUrl = $(this).data('gif-url');
                self.insertGif(gifUrl);
                self.closeAllPickers();
            });
        },
        
        /**
         * Get emojis based on source
         */
        getEmojis: function() {
            if (this.settings.emojiSource === 'custom' && this.settings.customEmojis.length > 0) {
                return this.settings.customEmojis;
            }
            
            // Default emoji set
            return [
                '😀','😃','😄','😁','😆','😅','🤣','😂','😊','😇','😍','🤩','😘','😗','😚','😛','🤪','😜','😝','🤑','🤗','🤭','🤫','🤔','🤐','🤨','😐','😑','😶','😏',
                '👋','🤚','🖐️','✋','🖖','👌','🤏','✌️','🤞','🤘','🤙','👍','👎','✊','👊','🤝','🙏','💪','👂','👃','🧠','👅','👄','❤️','💔','💕','💖','💗','💘','💙','💚','💛','🧡','💜','🖤','💯','💢','💥','💫','💦','💨','🕳️','💬','👁️‍🗨️','🗨️','🗯️','💭','💤'
            ];
        },
        
        /**
         * Categorize emojis
         */
        categorizeEmojis: function(emojis) {
            return {
                'smileys': emojis.slice(0, 30),
                'gestures': emojis.slice(30, 45),
                'symbols': emojis.slice(45)
            };
        },
        
        /**
         * Get category icon
         */
        getCategoryIcon: function(category) {
            var icons = {
                'smileys': '😊',
                'gestures': '👍',
                'symbols': '❤️'
            };
            return icons[category] || '📎';
        },
        
        /**
         * Insert emoji into active message input
         */
        insertEmoji: function(emoji) {
            var input = this.getActiveMessageInput();
            if (input && input.length) {
                var currentValue = input.val();
                var cursorPos = input[0].selectionStart || currentValue.length;
                
                var newValue = currentValue.substring(0, cursorPos) + emoji + currentValue.substring(cursorPos);
                input.val(newValue);
                
                // Set cursor position after emoji
                var newPos = cursorPos + emoji.length;
                input[0].setSelectionRange(newPos, newPos);
                input.focus();
            }
        },
        
        /**
         * Insert GIF into active message input
         */
        insertGif: function(gifUrl) {
            var input = this.getActiveMessageInput();
            if (input && input.length) {
                var gifMarkdown = '[gif]' + gifUrl + '[/gif]';
                var currentValue = input.val();
                var cursorPos = input[0].selectionStart || currentValue.length;
                
                var newValue = currentValue.substring(0, cursorPos) + gifMarkdown + currentValue.substring(cursorPos);
                input.val(newValue);
                
                input.focus();
            }
        },
        
        /**
         * Get active message input
         */
        getActiveMessageInput: function() {
            return $('.psource-chat-message-input:focus, .psource-chat-message-input').first();
        },
        
        /**
         * Search GIFs
         */
        searchGifs: function(query) {
            var results = $('.gif-results');
            var loading = $('.gif-loading');
            
            loading.show();
            results.empty();
            
            // Placeholder for GIF search implementation
            setTimeout(function() {
                loading.hide();
                results.html('<div class="gif-placeholder">GIF-Suche wird implementiert...</div>');
            }, 1000);
        },
        
        /**
         * Handle file selection
         */
        handleFileSelect: function(file, button) {
            if (!this.validateFile(file)) {
                return;
            }
            
            console.log('File selected:', file.name);
            // File upload implementation would go here
        },
        
        /**
         * Validate file
         */
        validateFile: function(file) {
            // Check file size
            var maxSize = this.settings.maxFileSize * 1024 * 1024; // Convert MB to bytes
            if (file.size > maxSize) {
                alert(this.settings.strings.fileTooBig);
                return false;
            }
            
            // Check file type
            var extension = file.name.split('.').pop().toLowerCase();
            if (this.settings.allowedTypes.indexOf(extension) === -1) {
                alert(this.settings.strings.fileTypeNotAllowed);
                return false;
            }
            
            return true;
        },
        
        /**
         * Get allowed file types attribute
         */
        getAllowedTypesAttribute: function() {
            return this.settings.allowedTypes.map(function(type) {
                return '.' + type;
            }).join(',');
        },
        
        /**
         * Render attachment buttons for a chat
         */
        renderButtons: function(chatOptions) {
            var buttons = [];
            
            if (this.settings.emojisEnabled && (chatOptions.enable_emoji !== false)) {
                buttons.push('<button type="button" class="psource-chat-attachment-btn psource-chat-emoji-btn" title="' + this.settings.strings.selectEmoji + '">😊</button>');
            }
            
            if (this.settings.gifsEnabled && (chatOptions.enable_gifs === true)) {
                buttons.push('<button type="button" class="psource-chat-attachment-btn psource-chat-gif-btn" title="GIF">GIF</button>');
            }
            
            if (this.settings.uploadsEnabled && (chatOptions.enable_uploads === true)) {
                buttons.push('<button type="button" class="psource-chat-attachment-btn psource-chat-upload-btn" title="' + this.settings.strings.uploadFile + '">📎</button>');
            }
            
            if (buttons.length === 0) {
                return '';
            }
            
            return '<div class="psource-chat-attachment-buttons">' + buttons.join('') + '</div>';
        }
    };
    
    // Auto-initialize if settings are available
    $(document).ready(function() {
        if (typeof psourceChatAttachments !== 'undefined') {
            PSChatAttachments.init(psourceChatAttachments);
        }
    });
    
})(jQuery);
