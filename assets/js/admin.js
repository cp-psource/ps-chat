/**
 * PSSource Chat Admin JavaScript
 * 
 * @package PSSource\Chat
 */

(function($) {
    'use strict';

    // Initialize admin functionality when document is ready
    $(document).ready(function() {
        PSChatAdmin.init();
    });

    /**
     * Main Admin Object
     */
    window.PSChatAdmin = {
        
        // Initialize all admin functions
        init: function() {
            this.initTabs();
            this.initColorPickers();
            this.initDataTables();
            this.initModals();
            this.initAjaxForms();
            this.initTooltips();
            this.initRealTimeStats();
        },

        // Tab Navigation
        initTabs: function() {
            $('.psource-chat-nav-tabs a').on('click', function(e) {
                e.preventDefault();
                
                var target = $(this).attr('href');
                
                // Update tab states
                $('.psource-chat-nav-tabs .active').removeClass('active');
                $(this).parent().addClass('active');
                
                // Show/hide content
                $('.psource-chat-tab-content').hide();
                $(target).show();
                
                // Save active tab
                localStorage.setItem('psource_chat_active_tab', target);
            });
            
            // Restore active tab
            var activeTab = localStorage.getItem('psource_chat_active_tab');
            if (activeTab) {
                $('.psource-chat-nav-tabs a[href="' + activeTab + '"]').click();
            }
        },

        // Color Picker Integration
        initColorPickers: function() {
            if ($.fn.wpColorPicker) {
                $('.psource-chat-color-picker input').wpColorPicker({
                    change: function(event, ui) {
                        var color = ui.color.toString();
                        $(this).val(color);
                        $(this).trigger('change');
                    }
                });
            }
        },

        // Data Tables for Sessions/Messages
        initDataTables: function() {
            if ($.fn.DataTable && $('.psource-chat-sessions-table').length) {
                $('.psource-chat-sessions-table').DataTable({
                    responsive: true,
                    pageLength: 25,
                    order: [[3, 'desc']], // Order by date descending
                    columnDefs: [
                        { orderable: false, targets: -1 } // Disable ordering on actions column
                    ],
                    language: {
                        search: 'Suchen:',
                        lengthMenu: '_MENU_ Einträge anzeigen',
                        info: '_START_ bis _END_ von _TOTAL_ Einträgen',
                        infoEmpty: 'Keine Einträge vorhanden',
                        infoFiltered: '(gefiltert von _MAX_ Einträgen)',
                        paginate: {
                            first: 'Erste',
                            last: 'Letzte',
                            next: 'Nächste',
                            previous: 'Vorherige'
                        }
                    }
                });
            }
        },

        // Modal Dialogs
        initModals: function() {
            // Open modal
            $(document).on('click', '[data-modal]', function(e) {
                e.preventDefault();
                var modalId = $(this).data('modal');
                PSChatAdmin.openModal(modalId);
            });
            
            // Close modal
            $(document).on('click', '.psource-chat-modal-close, .psource-chat-modal-overlay', function(e) {
                if (e.target === this) {
                    PSChatAdmin.closeModal();
                }
            });
            
            // Close modal on escape key
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27) { // Escape key
                    PSChatAdmin.closeModal();
                }
            });
        },

        // Open modal
        openModal: function(modalId) {
            var modal = $('#' + modalId);
            if (modal.length) {
                $('.psource-chat-modal-overlay').show();
                modal.show();
                $('body').addClass('modal-open');
            }
        },

        // Close modal
        closeModal: function() {
            $('.psource-chat-modal-overlay').hide();
            $('.psource-chat-modal').hide();
            $('body').removeClass('modal-open');
        },

        // AJAX Form Handling
        initAjaxForms: function() {
            $(document).on('submit', '.psource-chat-ajax-form', function(e) {
                e.preventDefault();
                
                var form = $(this);
                var submitBtn = form.find('input[type="submit"], button[type="submit"]');
                var originalText = submitBtn.val() || submitBtn.text();
                
                // Show loading state
                submitBtn.prop('disabled', true);
                submitBtn.val('Wird gespeichert...').text('Wird gespeichert...');
                
                // Serialize form data
                var formData = form.serialize();
                formData += '&action=psource_chat_admin_action';
                formData += '&nonce=' + psource_chat_admin.nonce;
                
                // Send AJAX request
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    dataType: 'json'
                }).done(function(response) {
                    if (response.success) {
                        PSChatAdmin.showNotice('success', response.data.message || 'Erfolgreich gespeichert!');
                        
                        // Close modal if form is in modal
                        if (form.closest('.psource-chat-modal').length) {
                            PSChatAdmin.closeModal();
                        }
                        
                        // Refresh page if needed
                        if (response.data.reload) {
                            location.reload();
                        }
                    } else {
                        PSChatAdmin.showNotice('error', response.data || 'Ein Fehler ist aufgetreten.');
                    }
                }).fail(function() {
                    PSChatAdmin.showNotice('error', 'Verbindungsfehler. Bitte versuchen Sie es erneut.');
                }).always(function() {
                    // Restore button state
                    submitBtn.prop('disabled', false);
                    submitBtn.val(originalText).text(originalText);
                });
            });
        },

        // Show notification
        showNotice: function(type, message) {
            var notice = $('<div class="psource-chat-notice psource-chat-notice-' + type + '">' + message + '</div>');
            
            // Find the best place to insert notice
            var target = $('.psource-chat-admin').first();
            if (target.length) {
                target.prepend(notice);
            } else {
                $('body').prepend(notice);
            }
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                notice.fadeOut(function() {
                    notice.remove();
                });
            }, 5000);
        },

        // Initialize tooltips
        initTooltips: function() {
            if ($.fn.tooltip) {
                $('[data-tooltip]').tooltip({
                    content: function() {
                        return $(this).data('tooltip');
                    },
                    position: {
                        my: "center bottom-20",
                        at: "center top"
                    }
                });
            }
        },

        // Real-time statistics updates
        initRealTimeStats: function() {
            if ($('.psource-chat-dashboard').length) {
                this.updateStats();
                
                // Update every 30 seconds
                setInterval(function() {
                    PSChatAdmin.updateStats();
                }, 30000);
            }
        },

        // Update dashboard statistics
        updateStats: function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'psource_chat_get_stats',
                    nonce: psource_chat_admin.nonce
                },
                dataType: 'json'
            }).done(function(response) {
                if (response.success && response.data) {
                    // Update each stat value
                    $.each(response.data, function(key, value) {
                        $('.psource-chat-stat-value[data-stat="' + key + '"]').text(value);
                    });
                }
            });
        },

        // Session management
        deleteSession: function(sessionId) {
            if (!confirm('Sind Sie sicher, dass Sie diese Sitzung löschen möchten?')) {
                return;
            }
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'psource_chat_delete_session',
                    session_id: sessionId,
                    nonce: psource_chat_admin.nonce
                },
                dataType: 'json'
            }).done(function(response) {
                if (response.success) {
                    // Remove row from table
                    $('tr[data-session-id="' + sessionId + '"]').fadeOut(function() {
                        $(this).remove();
                    });
                    PSChatAdmin.showNotice('success', 'Sitzung erfolgreich gelöscht.');
                } else {
                    PSChatAdmin.showNotice('error', response.data || 'Fehler beim Löschen der Sitzung.');
                }
            }).fail(function() {
                PSChatAdmin.showNotice('error', 'Verbindungsfehler beim Löschen der Sitzung.');
            });
        },

        // Message management
        deleteMessage: function(messageId) {
            if (!confirm('Sind Sie sicher, dass Sie diese Nachricht löschen möchten?')) {
                return;
            }
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'psource_chat_delete_message',
                    message_id: messageId,
                    nonce: psource_chat_admin.nonce
                },
                dataType: 'json'
            }).done(function(response) {
                if (response.success) {
                    // Remove message from display
                    $('.psource-chat-message[data-message-id="' + messageId + '"]').fadeOut(function() {
                        $(this).remove();
                    });
                    PSChatAdmin.showNotice('success', 'Nachricht erfolgreich gelöscht.');
                } else {
                    PSChatAdmin.showNotice('error', response.data || 'Fehler beim Löschen der Nachricht.');
                }
            }).fail(function() {
                PSChatAdmin.showNotice('error', 'Verbindungsfehler beim Löschen der Nachricht.');
            });
        },

        // Export data
        exportData: function(type, format) {
            var params = new URLSearchParams({
                action: 'psource_chat_export_data',
                type: type,
                format: format,
                nonce: psource_chat_admin.nonce
            });
            
            // Create download link
            var downloadUrl = ajaxurl + '?' + params.toString();
            
            // Trigger download
            var link = document.createElement('a');
            link.href = downloadUrl;
            link.download = 'psource-chat-' + type + '.' + format;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            PSChatAdmin.showNotice('info', 'Export wird vorbereitet...');
        },

        // Clear chat data
        clearChatData: function(type) {
            var confirmMessage = 'Sind Sie sicher, dass Sie alle ' + type + ' löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.';
            
            if (!confirm(confirmMessage)) {
                return;
            }
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'psource_chat_clear_data',
                    type: type,
                    nonce: psource_chat_admin.nonce
                },
                dataType: 'json'
            }).done(function(response) {
                if (response.success) {
                    PSChatAdmin.showNotice('success', response.data || 'Daten erfolgreich gelöscht.');
                    location.reload(); // Refresh to show updated data
                } else {
                    PSChatAdmin.showNotice('error', response.data || 'Fehler beim Löschen der Daten.');
                }
            }).fail(function() {
                PSChatAdmin.showNotice('error', 'Verbindungsfehler beim Löschen der Daten.');
            });
        }
    };

    // Global functions for button clicks
    window.deleteChatSession = function(sessionId) {
        PSChatAdmin.deleteSession(sessionId);
    };

    window.deleteChatMessage = function(messageId) {
        PSChatAdmin.deleteMessage(messageId);
    };

    window.exportChatData = function(type, format) {
        PSChatAdmin.exportData(type, format);
    };

    window.clearChatData = function(type) {
        PSChatAdmin.clearChatData(type);
    };

})(jQuery);
