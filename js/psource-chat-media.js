/**
 * PSource Chat Media Handler - JavaScript
 * 
 * Handles media content interaction and YouTube embedding
 * 
 * @package PSource_Chat
 * @subpackage Media
 * @since 2.5.1
 */

(function($) {
    'use strict';

    // Media Handler Object
    window.PSChatMedia = {
        
        /**
         * Initialisiert Media-Handler
         */
        init: function() {
            this.bindEvents();
            this.setupImageLightbox();
            this.setupYouTubePlayer();
        },

        /**
         * Bindet Event-Handler
         */
        bindEvents: function() {
            // YouTube-Thumbnail-Click
            $(document).on('click', '.psource-chat-youtube-thumbnail', this.handleYouTubeClick);
            
            // Bild-Click für Lightbox
            $(document).on('click', '.psource-chat-image img', this.handleImageClick);
            
            // Link-Preview-Click (externe Links)
            $(document).on('click', '.psource-chat-link-preview a', this.handleLinkClick);
            
            // Message-Input für Auto-Preview
            $(document).on('input', 'textarea.psource-chat-send', this.handleMessageInput);
        },

        /**
         * YouTube-Video abspielen
         */
        handleYouTubeClick: function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var $thumbnail = $(this);
            var videoId = $thumbnail.data('video-id');
            
            if (!videoId) return;
            
            // YouTube-Iframe erstellen
            var iframe = $('<iframe>', {
                src: 'https://www.youtube.com/embed/' + videoId + '?autoplay=1&rel=0',
                width: '100%',
                height: '225',
                frameborder: '0',
                allowfullscreen: true,
                allow: 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture'
            });
            
            // Thumbnail durch Iframe ersetzen
            $thumbnail.fadeOut(200, function() {
                $(this).parent().html(iframe);
                iframe.fadeIn(200);
            });
        },

        /**
         * Bild-Lightbox öffnen
         */
        handleImageClick: function(e) {
            e.preventDefault();
            
            var imgSrc = $(this).attr('src');
            var imgAlt = $(this).attr('alt') || 'Bild';
            
            // Lightbox erstellen
            var lightbox = $('<div class="psource-chat-lightbox">');
            var img = $('<img>', {
                src: imgSrc,
                alt: imgAlt,
                class: 'psource-chat-lightbox-image'
            });
            var closeBtn = $('<button class="psource-chat-lightbox-close">&times;</button>');
            
            lightbox.append(img).append(closeBtn);
            $('body').append(lightbox);
            
            // Lightbox anzeigen
            lightbox.fadeIn(200);
            
            // Close-Handler
            lightbox.on('click', function(e) {
                if (e.target === this || $(e.target).hasClass('psource-chat-lightbox-close')) {
                    lightbox.fadeOut(200, function() {
                        lightbox.remove();
                    });
                }
            });
            
            // ESC-Taste
            $(document).on('keydown.lightbox', function(e) {
                if (e.keyCode === 27) {
                    lightbox.trigger('click');
                    $(document).off('keydown.lightbox');
                }
            });
        },

        /**
         * Externe Links öffnen
         */
        handleLinkClick: function(e) {
            // Link in neuem Tab öffnen
            var href = $(this).attr('href');
            if (href) {
                window.open(href, '_blank', 'noopener,noreferrer');
                e.preventDefault();
            }
        },

        /**
         * Message-Input für Auto-Preview
         */
        handleMessageInput: function() {
            var $textarea = $(this);
            var message = $textarea.val();
            
            // URL-Pattern
            var urlPattern = /(https?:\/\/[^\s<>"{}|\\^`\[\]]+)/gi;
            var urls = message.match(urlPattern);
            
            if (urls && urls.length > 0) {
                // Debounce für Performance
                clearTimeout($textarea.data('preview-timeout'));
                
                $textarea.data('preview-timeout', setTimeout(function() {
                    PSChatMedia.generatePreview(urls[0], $textarea);
                }, 1000));
            } else {
                // Preview entfernen wenn keine URL mehr vorhanden
                PSChatMedia.removePreview($textarea);
            }
        },

        /**
         * Live-Preview generieren
         */
        generatePreview: function(url, $textarea) {
            var $chatBox = $textarea.closest('.psource-chat-box');
            var $previewContainer = $chatBox.find('.psource-chat-preview-container');
            
            // Preview-Container erstellen falls nicht vorhanden
            if ($previewContainer.length === 0) {
                $previewContainer = $('<div class="psource-chat-preview-container">');
                $textarea.closest('.psource-chat-module-message-area').append($previewContainer);
            }
            
            // Loading-State
            $previewContainer.html('<div class="psource-chat-media-loading">Lade Vorschau...</div>');
            
            // AJAX-Request für Preview-Daten
            $.ajax({
                url: psource_chat_localized.ajax_url,
                type: 'POST',
                data: {
                    action: 'psource_chat_get_link_preview',
                    url: url,
                    nonce: psource_chat_localized.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        var previewHtml = PSChatMedia.renderPreview(response.data);
                        $previewContainer.html(previewHtml);
                    } else {
                        $previewContainer.empty();
                    }
                },
                error: function() {
                    $previewContainer.empty();
                }
            });
        },

        /**
         * Preview entfernen
         */
        removePreview: function($textarea) {
            var $chatBox = $textarea.closest('.psource-chat-box');
            var $previewContainer = $chatBox.find('.psource-chat-preview-container');
            $previewContainer.empty();
        },

        /**
         * Preview-HTML rendern
         */
        renderPreview: function(mediaData) {
            var html = '<div class="psource-chat-media-preview">';
            
            switch (mediaData.type) {
                case 'youtube':
                    html += this.renderYouTubePreview(mediaData);
                    break;
                case 'image':
                    html += this.renderImagePreview(mediaData);
                    break;
                case 'link':
                default:
                    html += this.renderLinkPreview(mediaData);
                    break;
            }
            
            html += '<button class="psource-chat-preview-remove" title="Vorschau entfernen">&times;</button>';
            html += '</div>';
            
            return html;
        },

        /**
         * YouTube-Preview rendern
         */
        renderYouTubePreview: function(media) {
            var title = media.title || 'YouTube Video';
            return '<div class="psource-chat-preview-youtube">' +
                   '<img src="' + media.thumbnail + '" alt="' + title + '">' +
                   '<div class="psource-chat-preview-info">' +
                   '<div class="psource-chat-preview-title">' + title + '</div>' +
                   '<div class="psource-chat-preview-type">YouTube Video</div>' +
                   '</div></div>';
        },

        /**
         * Bild-Preview rendern
         */
        renderImagePreview: function(media) {
            return '<div class="psource-chat-preview-image">' +
                   '<img src="' + media.image + '" alt="Bild-Vorschau">' +
                   '<div class="psource-chat-preview-type">Bild</div>' +
                   '</div>';
        },

        /**
         * Link-Preview rendern
         */
        renderLinkPreview: function(media) {
            var title = media.title || media.url;
            var description = media.description || '';
            var image = media.image || '';
            
            var html = '<div class="psource-chat-preview-link">';
            
            if (image) {
                html += '<img src="' + image + '" alt="' + title + '">';
            }
            
            html += '<div class="psource-chat-preview-info">';
            html += '<div class="psource-chat-preview-title">' + title + '</div>';
            
            if (description) {
                html += '<div class="psource-chat-preview-description">' + description + '</div>';
            }
            
            html += '<div class="psource-chat-preview-type">' + (media.site_name || 'Website') + '</div>';
            html += '</div></div>';
            
            return html;
        },

        /**
         * Image-Lightbox Setup
         */
        setupImageLightbox: function() {
            // Lightbox-Styles zum Head hinzufügen
            if (!$('#psource-chat-lightbox-styles').length) {
                var styles = `
                <style id="psource-chat-lightbox-styles">
                .psource-chat-lightbox {
                    display: none;
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.9);
                    z-index: 10000;
                    cursor: pointer;
                }
                .psource-chat-lightbox-image {
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    max-width: 90%;
                    max-height: 90%;
                    object-fit: contain;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
                }
                .psource-chat-lightbox-close {
                    position: absolute;
                    top: 20px;
                    right: 30px;
                    background: none;
                    border: none;
                    color: white;
                    font-size: 40px;
                    font-weight: bold;
                    cursor: pointer;
                    z-index: 10001;
                    line-height: 1;
                    padding: 0;
                    width: 50px;
                    height: 50px;
                }
                .psource-chat-lightbox-close:hover {
                    opacity: 0.7;
                }
                </style>`;
                $('head').append(styles);
            }
        },

        /**
         * YouTube-Player Setup
         */
        setupYouTubePlayer: function() {
            // YouTube-Iframe-API laden falls noch nicht geladen
            if (!window.YT && !window.youTubeApiLoading) {
                window.youTubeApiLoading = true;
                var tag = document.createElement('script');
                tag.src = 'https://www.youtube.com/iframe_api';
                var firstScriptTag = document.getElementsByTagName('script')[0];
                firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
            }
        }
    };

    // CSS für Preview-Container
    var previewStyles = `
    <style id="psource-chat-preview-styles">
    .psource-chat-preview-container {
        margin: 8px 0;
        position: relative;
    }
    .psource-chat-media-preview {
        position: relative;
        border: 1px solid #e1e5e9;
        border-radius: 8px;
        padding: 8px;
        background: #f8f9fa;
        max-width: 300px;
    }
    .psource-chat-preview-youtube,
    .psource-chat-preview-image,
    .psource-chat-preview-link {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .psource-chat-preview-youtube img,
    .psource-chat-preview-image img,
    .psource-chat-preview-link img {
        width: 60px;
        height: 45px;
        object-fit: cover;
        border-radius: 4px;
        flex-shrink: 0;
    }
    .psource-chat-preview-info {
        flex: 1;
        min-width: 0;
    }
    .psource-chat-preview-title {
        font-weight: 600;
        font-size: 12px;
        line-height: 1.3;
        color: #1d2129;
        margin-bottom: 2px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .psource-chat-preview-description {
        font-size: 11px;
        color: #606770;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        margin-bottom: 2px;
    }
    .psource-chat-preview-type {
        font-size: 10px;
        color: #8a8d91;
        text-transform: uppercase;
        font-weight: 500;
    }
    .psource-chat-preview-remove {
        position: absolute;
        top: 4px;
        right: 4px;
        background: #e74c3c;
        color: white;
        border: none;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        font-size: 12px;
        line-height: 1;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .psource-chat-preview-remove:hover {
        background: #c0392b;
    }
    </style>`;

    // DOM Ready
    $(document).ready(function() {
        // Preview-Styles hinzufügen
        if (!$('#psource-chat-preview-styles').length) {
            $('head').append(previewStyles);
        }
        
        // Media-Handler initialisieren
        PSChatMedia.init();
        
        // Preview-Remove-Handler
        $(document).on('click', '.psource-chat-preview-remove', function(e) {
            e.preventDefault();
            $(this).closest('.psource-chat-preview-container').empty();
        });
    });

})(jQuery);
