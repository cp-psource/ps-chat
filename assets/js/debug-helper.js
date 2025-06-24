/**
 * PS Chat Debug Helper
 * 
 * This script helps debug chat positioning issues by providing comprehensive
 * information about the chat container and its environment.
 */

(function($) {
    'use strict';
    
    var PSChatDebug = {
        
        init: function() {
            this.setupDebugPanel();
            this.monitorChatContainer();
            this.logInitialState();
        },
        
        setupDebugPanel: function() {
            if (!window.location.search.includes('ps_chat_debug=1')) {
                return;
            }
            
            var debugPanel = $('<div id="ps-chat-debug-panel"></div>').css({
                position: 'fixed',
                top: '10px',
                left: '10px',
                width: '400px',
                maxHeight: '300px',
                backgroundColor: '#fff',
                border: '2px solid #007cba',
                borderRadius: '5px',
                padding: '10px',
                fontSize: '12px',
                fontFamily: 'monospace',
                zIndex: '999999',
                overflow: 'auto',
                boxShadow: '0 4px 20px rgba(0,0,0,0.3)'
            });
            
            debugPanel.html('<strong>PS Chat Debug Panel</strong><br><div id="ps-debug-content">Initialisiere...</div>');
            $('body').append(debugPanel);
            
            // Add close button
            var closeBtn = $('<button>×</button>').css({
                position: 'absolute',
                top: '5px',
                right: '5px',
                background: '#dc3232',
                color: 'white',
                border: 'none',
                borderRadius: '3px',
                cursor: 'pointer'
            }).click(function() {
                debugPanel.hide();
            });
            
            debugPanel.append(closeBtn);
        },
        
        updateDebugPanel: function(info) {
            var content = $('#ps-debug-content');
            if (content.length) {
                content.html(info);
            }
        },
        
        monitorChatContainer: function() {
            var self = this;
            var checkInterval = setInterval(function() {
                var container = $('#psource-chat-seitenkanten');
                if (container.length) {
                    self.analyzeChatContainer(container);
                    clearInterval(checkInterval);
                    
                    // Continue monitoring for changes
                    setInterval(function() {
                        self.analyzeChatContainer(container);
                    }, 2000);
                }
            }, 500);
            
            // Stop checking after 10 seconds
            setTimeout(function() {
                clearInterval(checkInterval);
            }, 10000);
        },
        
        analyzeChatContainer: function(container) {
            if (!container || !container.length) {
                this.updateDebugPanel('❌ Chat Container nicht gefunden');
                return;
            }
            
            var rect = container[0].getBoundingClientRect();
            var computedStyle = window.getComputedStyle(container[0]);
            var parent = container.parent();
            
            var info = [
                '<strong>Container Status:</strong>',
                '✅ Container gefunden: ' + container.attr('id'),
                'Sichtbar: ' + (container.is(':visible') ? 'Ja' : 'Nein'),
                'Dimension: ' + rect.width + 'x' + rect.height,
                '',
                '<strong>Position:</strong>',
                'CSS Position: ' + computedStyle.position,
                'Top: ' + computedStyle.top + ' (computed: ' + rect.top + 'px)',
                'Right: ' + computedStyle.right + ' (computed: ' + rect.right + 'px)',
                'Bottom: ' + computedStyle.bottom + ' (computed: ' + rect.bottom + 'px)',
                'Left: ' + computedStyle.left + ' (computed: ' + rect.left + 'px)',
                'Z-Index: ' + computedStyle.zIndex,
                '',
                '<strong>Parent:</strong>',
                'Tag: ' + parent[0].tagName,
                'ID: ' + (parent.attr('id') || 'keine'),
                'Classes: ' + (parent.attr('class') || 'keine'),
                'Parent Position: ' + window.getComputedStyle(parent[0]).position,
                '',
                '<strong>Viewport:</strong>',
                'Window: ' + window.innerWidth + 'x' + window.innerHeight,
                'Scroll: ' + window.pageXOffset + ',' + window.pageYOffset,
                '',
                '<strong>Probleme:</strong>'
            ];
            
            // Detect problems
            var problems = [];
            
            if (computedStyle.position !== 'fixed') {
                problems.push('❌ Position ist nicht "fixed"');
            }
            
            if (rect.bottom > window.innerHeight || rect.right > window.innerWidth) {
                problems.push('❌ Container außerhalb des Viewports');
            }
            
            if (rect.width === 0 || rect.height === 0) {
                problems.push('❌ Container hat keine Dimensionen');
            }
            
            if (parent[0].tagName !== 'BODY') {
                problems.push('⚠️ Container ist nicht direkt in <body>');
            }
            
            if (parseInt(computedStyle.zIndex) < 999999) {
                problems.push('⚠️ Z-Index könnte zu niedrig sein');
            }
            
            if (problems.length === 0) {
                problems.push('✅ Keine offensichtlichen Probleme');
            }
            
            info = info.concat(problems);
            
            this.updateDebugPanel(info.join('<br>'));
            
            // Console logging for developers
            console.group('PS Chat Debug');
            console.log('Container:', container[0]);
            console.log('Computed Style:', computedStyle);
            console.log('Bounding Rect:', rect);
            console.log('Parent:', parent[0]);
            console.log('Problems:', problems);
            console.groupEnd();
        },
        
        logInitialState: function() {
            console.group('PS Chat Initialization Debug');
            console.log('jQuery Version:', $.fn.jquery);
            console.log('WordPress Ajax URL:', typeof psourceChatFrontend !== 'undefined' ? psourceChatFrontend.ajaxUrl : 'nicht verfügbar');
            console.log('User ID:', typeof psourceChatFrontend !== 'undefined' ? psourceChatFrontend.userId : 'nicht verfügbar');
            console.log('Document Ready State:', document.readyState);
            console.log('Window Size:', window.innerWidth + 'x' + window.innerHeight);
            console.groupEnd();
        },
        
        // Force chat positioning - emergency function
        forceFixPosition: function() {
            var container = $('#psource-chat-seitenkanten');
            if (!container.length) {
                console.warn('Chat container not found for force positioning');
                return;
            }
            
            console.log('Force positioning chat container...');
            
            // Move to body if not already there
            if (container.parent()[0].tagName !== 'BODY') {
                container.appendTo('body');
                console.log('Moved chat to body');
            }
            
            // Apply emergency styles
            container.css({
                'position': 'fixed !important',
                'bottom': '20px !important',
                'right': '20px !important',
                'z-index': '999999 !important',
                'display': 'block !important',
                'visibility': 'visible !important'
            });
            
            console.log('Applied emergency positioning styles');
        }
    };
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        PSChatDebug.init();
        
        // Make debug functions globally available
        window.PSChatDebug = PSChatDebug;
    });
    
    // Auto-fix if major problems detected
    setTimeout(function() {
        var container = $('#psource-chat-seitenkanten');
        if (container.length) {
            var rect = container[0].getBoundingClientRect();
            var computedStyle = window.getComputedStyle(container[0]);
            
            // Check if chat is completely off-screen or has wrong positioning
            if (computedStyle.position !== 'fixed' || 
                rect.bottom > window.innerHeight + 100 || 
                rect.right > window.innerWidth + 100) {
                
                console.warn('Chat positioning problem detected, applying auto-fix...');
                PSChatDebug.forceFixPosition();
            }
        }
    }, 3000);
    
})(jQuery);
