/**
 * PSource Chat File Upload Handler - JavaScript
 * 
 * Handles file upload functionality in the chat interface
 * 
 * @package PSource_Chat
 * @subpackage Upload
 * @since 2.5.1
 */

(function($) {
    'use strict';

    // Upload Handler Object
    window.PSChatUpload = {
        
        /**
         * Aktuelle Upload-Queue
         */
        uploadQueue: [],
        
        /**
         * Upload-Status
         */
        isUploading: false,

        /**
         * Initialisiert Upload-Handler
         */
        init: function() {
            this.addUploadButton();
            this.bindEvents();
            this.createUploadStyles();
        },

        /**
         * Fügt Upload-Button zur Chat-Eingabe hinzu
         */
        addUploadButton: function() {
            // Upload-Button zu allen Chat-Boxen hinzufügen
            $('.psource-chat-module-message-area').each(function() {
                var $messageArea = $(this);
                var $textarea = $messageArea.find('textarea.psource-chat-send');
                
                if ($textarea.length && !$messageArea.find('.psource-chat-upload-button').length) {
                    var $uploadContainer = $('<div class="psource-chat-upload-container">');
                    
                    // File Input (versteckt)
                    var $fileInput = $('<input type="file" class="psource-chat-file-input" multiple accept="' + PSChatUpload.getAcceptString() + '">');
                    
                    // Upload Button
                    var $uploadButton = $('<button type="button" class="psource-chat-upload-button" title="Datei anhängen">');
                    $uploadButton.html('<span class="dashicons dashicons-paperclip"></span>');
                    
                    $uploadContainer.append($fileInput).append($uploadButton);
                    $textarea.after($uploadContainer);
                }
            });
        },

        /**
         * Bindet Event-Handler
         */
        bindEvents: function() {
            // Upload-Button Click
            $(document).on('click', '.psource-chat-upload-button', this.handleUploadButtonClick);
            
            // File Input Change
            $(document).on('change', '.psource-chat-file-input', this.handleFileSelect);
            
            // Drag & Drop
            $(document).on('dragover', '.psource-chat-module-message-area', this.handleDragOver);
            $(document).on('dragleave', '.psource-chat-module-message-area', this.handleDragLeave);
            $(document).on('drop', '.psource-chat-module-message-area', this.handleDrop);
            
            // Upload-Preview Aktionen
            $(document).on('click', '.psource-chat-upload-preview-remove', this.handleRemoveUpload);
            $(document).on('click', '.psource-chat-upload-preview-retry', this.handleRetryUpload);
        },

        /**
         * Upload-Button Click
         */
        handleUploadButtonClick: function(e) {
            e.preventDefault();
            
            // Suche nach dem versteckten File-Input
            var $chatBox = $(this).closest('.psource-chat-box');
            var $fileInput = $chatBox.find('.psource-chat-file-input');
            
            // Falls kein Input gefunden wurde, erstelle einen
            if (!$fileInput.length) {
                var sessionId = $chatBox.find('textarea.psource-chat-send').attr('id');
                if (sessionId) {
                    sessionId = sessionId.replace('psource-chat-send-', '');
                    $fileInput = $('#psource-chat-file-input-' + sessionId);
                }
            }
            
            if ($fileInput.length) {
                $fileInput.trigger('click');
            } else {
                console.error('File input not found');
            }
        },

        /**
         * Datei-Auswahl Handler
         */
        handleFileSelect: function(e) {
            var files = e.target.files;
            var $chatBox = $(this).closest('.psource-chat-box');
            
            PSChatUpload.processFiles(files, $chatBox);
            
            // Input zurücksetzen
            $(this).val('');
        },

        /**
         * Drag Over Handler
         */
        handleDragOver: function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            $(this).addClass('psource-chat-drag-over');
        },

        /**
         * Drag Leave Handler
         */
        handleDragLeave: function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            $(this).removeClass('psource-chat-drag-over');
        },

        /**
         * Drop Handler
         */
        handleDrop: function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            $(this).removeClass('psource-chat-drag-over');
            
            var files = e.originalEvent.dataTransfer.files;
            var $chatBox = $(this).closest('.psource-chat-box');
            
            PSChatUpload.processFiles(files, $chatBox);
        },

        /**
         * Verarbeitet ausgewählte Dateien
         */
        processFiles: function(files, $chatBox) {
            if (!files || files.length === 0) return;
            
            var sessionId = this.getSessionId($chatBox);
            if (!sessionId) {
                this.showError($chatBox, 'Keine gültige Chat-Session gefunden');
                return;
            }

            // Validierung und zur Queue hinzufügen (OHNE Upload)
            for (var i = 0; i < files.length; i++) {
                var file = files[i];
                var validation = this.validateFile(file);
                
                if (validation.valid) {
                    this.addToUploadQueue(file, sessionId, $chatBox);
                } else {
                    this.showError($chatBox, 'Datei "' + file.name + '": ' + validation.error);
                }
            }
            
            // KEIN automatischer Upload mehr - nur Vorschau zeigen
        },

        /**
         * Validiert eine Datei
         */
        validateFile: function(file) {
            var allowedTypes = this.getAllowedTypes();
            var maxSize = this.getMaxSize();
            
            // Größe prüfen
            if (file.size > maxSize) {
                return {
                    valid: false,
                    error: 'Datei zu groß. Maximum: ' + this.formatFileSize(maxSize)
                };
            }
            
            // Typ prüfen
            var extension = file.name.split('.').pop().toLowerCase();
            if (allowedTypes.indexOf(extension) === -1) {
                return {
                    valid: false,
                    error: 'Dateityp nicht erlaubt. Erlaubt: ' + allowedTypes.join(', ')
                };
            }
            
            return { valid: true };
        },

        /**
         * Fügt Datei zur Upload-Queue hinzu und zeigt Dateiname im Textfeld
         */
        addToUploadQueue: function(file, sessionId, $chatBox) {
            var uploadId = this.generateUploadId();
            
            var uploadItem = {
                id: uploadId,
                file: file,
                sessionId: sessionId,
                chatBox: $chatBox,
                status: 'queued', // Status: queued = bereit zum Upload
                progress: 0,
                result: null
            };
            
            this.uploadQueue.push(uploadItem);
            
            // KEINE Upload-Vorschau mehr anzeigen
            // this.showUploadPreview(uploadItem);
            
            // Dateiname ins Textfeld einfügen als Visualisierung
            var $textarea = $chatBox.find('textarea.psource-chat-send');
            var currentText = $textarea.val();
            var fileName = '📎 ' + file.name;
            
            if (currentText.trim()) {
                $textarea.val(currentText + '\n' + fileName);
            } else {
                $textarea.val(fileName);
            }
            
            // Fokus aufs Textfeld
            $textarea.focus();
        },

        /**
         * Zeigt Upload-Vorschau
         */
        showUploadPreview: function(uploadItem) {
            var $chatBox = uploadItem.chatBox;
            var $previewContainer = $chatBox.find('.psource-chat-upload-previews');
            
            if ($previewContainer.length === 0) {
                $previewContainer = $('<div class="psource-chat-upload-previews">');
                $chatBox.find('.psource-chat-module-message-area').append($previewContainer);
            }
            
            var $preview = $('<div class="psource-chat-upload-preview" data-upload-id="' + uploadItem.id + '">');
            $preview.html(this.renderUploadPreview(uploadItem));
            
            $previewContainer.append($preview);
        },

        /**
         * Rendert Upload-Vorschau HTML
         */
        renderUploadPreview: function(uploadItem) {
            var file = uploadItem.file;
            var fileType = this.getFileTypeCategory(file.type);
            var fileSize = this.formatFileSize(file.size);
            var icon = this.getFileTypeIcon(fileType);
            
            var html = '<div class="psource-chat-upload-preview-content">';
            html += '<div class="psource-chat-upload-preview-icon">' + icon + '</div>';
            html += '<div class="psource-chat-upload-preview-info">';
            html += '<div class="psource-chat-upload-preview-name">' + this.escapeHtml(file.name) + '</div>';
            html += '<div class="psource-chat-upload-preview-size">' + fileSize + '</div>';
            html += '<div class="psource-chat-upload-preview-status">Bereit zum Senden</div>';
            html += '</div>';
            html += '<div class="psource-chat-upload-preview-progress">';
            html += '<div class="psource-chat-upload-preview-progress-bar" style="width: 0%"></div>';
            html += '</div>';
            html += '<div class="psource-chat-upload-preview-actions">';
            html += '<button class="psource-chat-upload-preview-remove" title="Entfernen">&times;</button>';
            html += '</div>';
            html += '</div>';
            
            return html;
        },

        /**
         * Diese Methoden werden nicht mehr benötigt - Upload erfolgt beim Senden
         */
        processUploadQueue: function() {
            // Wird nicht mehr verwendet
        },

        uploadNext: function() {
            // Wird nicht mehr verwendet  
        },

        uploadFile: function(uploadItem) {
            // Diese Methode wird durch uploadSingleFile ersetzt
        },

        /**
         * Upload-Queue beim Senden verarbeiten
         */
        processQueueOnSend: function($chatBox, callback) {
            
            var queuedUploads = this.uploadQueue.filter(function(item) {
                return item.status === 'queued' && item.chatBox.is($chatBox);
            });
            
            if (queuedUploads.length === 0) {
                // Keine Uploads -> normale Nachricht senden
                callback(null);
                return;
            }
            
            // Upload-Status auf "uploading" setzen
            queuedUploads.forEach(function(item) {
                item.status = 'uploading';
                // Keine Vorschau-Updates mehr
                // PSChatUpload.updateUploadPreview(item);
            });
            
            // Alle Uploads parallel starten
            var uploadPromises = queuedUploads.map(function(item) {
                return PSChatUpload.uploadSingleFile(item);
            });
            
            // Warten bis alle Uploads fertig sind
            Promise.all(uploadPromises).then(function(results) {
                // Upload-Referenzen sammeln
                var uploadReferences = results
                    .filter(function(result) { return result && result.id; })
                    .map(function(result) { return '[upload:' + result.id + ']'; });
                
                callback(uploadReferences);
                
                // Keine Upload-Previews mehr zu entfernen
                // queuedUploads.forEach(function(item) {
                //     PSChatUpload.removeUploadPreview(item);
                // });
                
            }).catch(function(error) {
                console.error('Upload error:', error);
                PSChatUpload.showError($chatBox, 'Upload-Fehler: ' + error.message);
                
                // Upload-Status zurücksetzen
                queuedUploads.forEach(function(item) {
                    item.status = 'queued';
                    // Keine Vorschau-Updates mehr
                    // PSChatUpload.updateUploadPreview(item);
                });
            });
        },
        
        /**
         * Einzelne Datei hochladen
         */
        uploadSingleFile: function(uploadItem) {
            return new Promise(function(resolve, reject) {
                var formData = new FormData();
                formData.append('action', 'psource_chat_upload_file');
                formData.append('file', uploadItem.file);
                formData.append('session_id', uploadItem.sessionId);
                formData.append('nonce', psource_chat_localized.nonce);
                
                $.ajax({
                    url: psource_chat_localized.ajax_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    xhr: function() {
                        var xhr = new window.XMLHttpRequest();
                        xhr.upload.addEventListener('progress', function(e) {
                            if (e.lengthComputable) {
                                var percentComplete = (e.loaded / e.total) * 100;
                                uploadItem.progress = percentComplete;
                                PSChatUpload.updateUploadProgress(uploadItem);
                            }
                        }, false);
                        return xhr;
                    },
                    success: function(response) {
                        if (response.success) {
                            uploadItem.result = response.data;
                            uploadItem.status = 'completed';
                            resolve(response.data);
                        } else {
                            uploadItem.status = 'error';
                            reject(new Error(response.data || 'Upload-Fehler'));
                        }
                    },
                    error: function(xhr, status, error) {
                        uploadItem.status = 'error';
                        reject(new Error('Netzwerk-Fehler: ' + error));
                    }
                });
            });
        },
        
        /**
         * Upload-Vorschau aktualisieren
         */
        updateUploadPreview: function(uploadItem) {
            var $preview = $('[data-upload-id="' + uploadItem.id + '"]');
            if ($preview.length === 0) return;
            
            var $status = $preview.find('.psource-chat-upload-preview-status');
            var $progress = $preview.find('.psource-chat-upload-preview-progress-bar');
            
            switch (uploadItem.status) {
                case 'queued':
                    $status.text('Bereit zum Senden');
                    $progress.css('width', '0%');
                    break;
                case 'uploading':
                    $status.text('Wird hochgeladen...');
                    break;
                case 'completed':
                    $status.text('✓ Hochgeladen');
                    $progress.css('width', '100%');
                    break;
                case 'error':
                    $status.text('✗ Fehler').css('color', '#e74c3c');
                    break;
            }
        },
        
        /**
         * Upload-Progress aktualisieren
         */
        updateUploadProgress: function(uploadItem) {
            var $preview = $('[data-upload-id="' + uploadItem.id + '"]');
            if ($preview.length === 0) return;
            
            var $progress = $preview.find('.psource-chat-upload-preview-progress-bar');
            $progress.css('width', uploadItem.progress + '%');
        },
        
        /**
         * Upload-Vorschau entfernen
         */
        removeUploadPreview: function(uploadItem) {
            var $preview = $('[data-upload-id="' + uploadItem.id + '"]');
            $preview.fadeOut(300, function() {
                $(this).remove();
            });
            
            // Aus Queue entfernen
            this.uploadQueue = this.uploadQueue.filter(function(item) {
                return item.id !== uploadItem.id;
            });
        },

        /**
         * Fügt Datei-Referenz zur Nachricht hinzu
         */
        /**
         * Wird nicht mehr benötigt - Upload erfolgt beim Senden
         */
        addFileToMessage: function(uploadItem) {
            // Diese Methode wird nicht mehr verwendet
            // Upload-Referenzen werden erst beim Senden hinzugefügt
        },

        /**
         * Generiert HTML für Datei basierend auf Typ
         */
        generateFileHtml: function(fileData) {
            var downloadUrl = psource_chat_localized.ajax_url + '?action=psource_chat_download_file&file_id=' + encodeURIComponent(fileData.stored_name);
            var fileName = fileData.original_name;
            var fileExt = fileName.split('.').pop().toLowerCase();
            
            // Medien-Dateien
            if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExt)) {
                return '<img src="' + downloadUrl + '" alt="' + fileName + '" style="max-width: 100%; height: auto; border-radius: 8px; margin: 8px 0;">';
            }
            
            if (['mp4', 'webm', 'ogg'].includes(fileExt)) {
                return '<video controls style="max-width: 100%; height: auto; border-radius: 8px; margin: 8px 0;"><source src="' + downloadUrl + '" type="' + (fileData.mime_type || 'video/mp4') + '">Dein Browser unterstützt das Video-Element nicht.</video>';
            }
            
            if (['mp3', 'wav', 'ogg'].includes(fileExt)) {
                return '<audio controls style="width: 100%; margin: 8px 0;"><source src="' + downloadUrl + '" type="' + (fileData.mime_type || 'audio/mpeg') + '">Dein Browser unterstützt das Audio-Element nicht.</audio>';
            }
            
            // Fallback für andere Dateien (vorerst einfacher Link)
            return '<a href="' + downloadUrl + '" target="_blank">' + fileName + '</a>';
        },

        /**
         * Upload entfernen
         */
        handleRemoveUpload: function(e) {
            e.preventDefault();
            
            var $preview = $(this).closest('.psource-chat-upload-preview');
            var uploadId = $preview.data('upload-id');
            
            // Aus Queue entfernen
            PSChatUpload.uploadQueue = PSChatUpload.uploadQueue.filter(function(item) {
                return item.id !== uploadId;
            });
            
            $preview.fadeOut(200, function() {
                $(this).remove();
            });
        },

        /**
         * Upload wiederholen
         */
        handleRetryUpload: function(e) {
            e.preventDefault();
            
            var $preview = $(this).closest('.psource-chat-upload-preview');
            var uploadId = $preview.data('upload-id');
            
            var uploadItem = PSChatUpload.uploadQueue.find(function(item) {
                return item.id === uploadId;
            });
            
            if (uploadItem) {
                uploadItem.status = 'pending';
                uploadItem.progress = 0;
                
                $preview.removeClass('psource-chat-upload-preview-error');
                $preview.find('.psource-chat-upload-preview-retry').remove();
                $preview.find('.psource-chat-upload-preview-progress-bar').css('width', '0%');
                
                PSChatUpload.processUploadQueue();
            }
        },

        /**
         * Holt Session-ID aus Chat-Box
         */
        getSessionId: function($chatBox) {
            var id = $chatBox.attr('id');
            if (id && id.startsWith('psource-chat-box-')) {
                return id.replace('psource-chat-box-', '');
            }
            return null;
        },

        /**
         * Holt erlaubte Dateitypen
         */
        getAllowedTypes: function() {
            if (psource_chat_localized.upload_settings && psource_chat_localized.upload_settings.allowed_types) {
                return psource_chat_localized.upload_settings.allowed_types.split(',');
            }
            return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'pdf', 'doc', 'docx', 'txt', 'zip'];
        },

        /**
         * Holt maximale Dateigröße
         */
        getMaxSize: function() {
            if (psource_chat_localized.upload_settings && psource_chat_localized.upload_settings.max_size) {
                return parseInt(psource_chat_localized.upload_settings.max_size) * 1024 * 1024;
            }
            return 5 * 1024 * 1024; // 5MB Standard
        },

        /**
         * Generiert Accept-String für File-Input
         */
        getAcceptString: function() {
            var types = this.getAllowedTypes();
            return types.map(function(type) {
                return '.' + type;
            }).join(',');
        },

        /**
         * Holt Dateityp-Kategorie
         */
        getFileTypeCategory: function(mimeType) {
            if (mimeType.startsWith('image/')) return 'image';
            if (mimeType.startsWith('video/')) return 'video';
            if (mimeType.startsWith('audio/')) return 'audio';
            if (mimeType === 'application/pdf') return 'pdf';
            if (mimeType.includes('zip') || mimeType.includes('rar')) return 'archive';
            if (mimeType.startsWith('text/')) return 'text';
            return 'document';
        },

        /**
         * Holt Icon für Dateityp
         */
        getFileTypeIcon: function(fileType) {
            var icons = {
                'image': '<span class="dashicons dashicons-format-image"></span>',
                'video': '<span class="dashicons dashicons-format-video"></span>',
                'audio': '<span class="dashicons dashicons-format-audio"></span>',
                'pdf': '<span class="dashicons dashicons-pdf"></span>',
                'archive': '<span class="dashicons dashicons-archive"></span>',
                'text': '<span class="dashicons dashicons-text"></span>',
                'document': '<span class="dashicons dashicons-media-document"></span>'
            };
            
            return icons[fileType] || icons['document'];
        },

        /**
         * Formatiert Dateigröße
         */
        formatFileSize: function(bytes) {
            if (bytes === 0) return '0 Bytes';
            
            var k = 1024;
            var sizes = ['Bytes', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        /**
         * Generiert eindeutige Upload-ID
         */
        generateUploadId: function() {
            return 'upload_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        },

        /**
         * Escaped HTML
         */
        escapeHtml: function(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        /**
         * Zeigt Fehler-Nachricht
         */
        showError: function($chatBox, message) {
            // Temporäre Fehler-Anzeige
            var $error = $('<div class="psource-chat-upload-error">' + this.escapeHtml(message) + '</div>');
            $chatBox.find('.psource-chat-module-message-area').append($error);
            
            setTimeout(function() {
                $error.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * Erstellt Upload-Styles
         */
        createUploadStyles: function() {
            if ($('#psource-chat-upload-styles').length) return;
            
            var styles = `
            <style id="psource-chat-upload-styles">
            .psource-chat-upload-container {
                position: relative;
                display: inline-block;
                margin-left: 5px;
            }
            
            .psource-chat-file-input {
                display: none;
            }
            
            .psource-chat-upload-button {
                background: #0073aa;
                color: white;
                border: none;
                border-radius: 3px;
                padding: 5px 8px;
                cursor: pointer;
                font-size: 16px;
                line-height: 1;
                transition: background-color 0.2s;
            }
            
            .psource-chat-upload-button:hover {
                background: #005a87;
            }
            
            .psource-chat-upload-button .dashicons {
                width: 16px;
                height: 16px;
                font-size: 16px;
            }
            
            .psource-chat-drag-over {
                background-color: rgba(0, 115, 170, 0.1);
                border: 2px dashed #0073aa;
                border-radius: 5px;
            }
            
            .psource-chat-upload-previews {
                margin: 10px 0;
                max-height: 200px;
                overflow-y: auto;
            }
            
            .psource-chat-upload-preview {
                background: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 4px;
                margin-bottom: 5px;
                padding: 8px;
                position: relative;
            }
            
            .psource-chat-upload-preview-content {
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            .psource-chat-upload-preview-icon {
                flex-shrink: 0;
                color: #666;
            }
            
            .psource-chat-upload-preview-info {
                flex: 1;
                min-width: 0;
            }
            
            .psource-chat-upload-preview-name {
                font-weight: 600;
                font-size: 12px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            
            .psource-chat-upload-preview-size,
            .psource-chat-upload-preview-status {
                font-size: 11px;
                color: #666;
            }
            
            .psource-chat-upload-preview-progress {
                height: 3px;
                background: #e1e1e1;
                border-radius: 1px;
                margin-top: 5px;
                overflow: hidden;
            }
            
            .psource-chat-upload-preview-progress-bar {
                height: 100%;
                background: #0073aa;
                border-radius: 1px;
                transition: width 0.3s ease;
            }
            
            .psource-chat-upload-preview-actions {
                position: absolute;
                top: 5px;
                right: 5px;
            }
            
            .psource-chat-upload-preview-remove,
            .psource-chat-upload-preview-retry {
                background: #dc3232;
                color: white;
                border: none;
                border-radius: 50%;
                width: 20px;
                height: 20px;
                font-size: 12px;
                line-height: 1;
                cursor: pointer;
                margin-left: 2px;
            }
            
            .psource-chat-upload-preview-retry {
                background: #0073aa;
            }
            
            .psource-chat-upload-preview-remove:hover {
                background: #a00;
            }
            
            .psource-chat-upload-preview-retry:hover {
                background: #005a87;
            }
            
            .psource-chat-upload-preview-success {
                background: #f0f8ff;
                border-color: #0073aa;
            }
            
            .psource-chat-upload-preview-success .psource-chat-upload-preview-progress-bar {
                background: #46b450;
            }
            
            .psource-chat-upload-preview-error {
                background: #ffeaea;
                border-color: #dc3232;
            }
            
            .psource-chat-upload-preview-error .psource-chat-upload-preview-progress-bar {
                background: #dc3232;
            }
            
            .psource-chat-upload-error {
                background: #ffeaea;
                color: #dc3232;
                padding: 8px;
                border-radius: 4px;
                margin: 5px 0;
                font-size: 12px;
            }
            </style>`;
            
            $('head').append(styles);
        },

        /**
         * Bereinigt Nachrichtentext von Dateinamen-Platzhaltern
         */
        cleanMessageText: function(messageText) {
            if (!messageText) return '';
            
            // Entferne alle Zeilen die Datei-Platzhalter enthalten
            // Pattern: 📎 gefolgt von beliebigen Zeichen (Dateiname)
            var result = messageText
                .replace(/📎\s+[^\n\r]+/g, '') // Entferne 📎 + Dateiname
                .replace(/\n\s*\n/g, '\n')     // Mehrfache Leerzeilen zu einer
                .trim();                        // Whitespace am Anfang/Ende
            
            return result;
        },

    };

    // DOM Ready
    $(document).ready(function() {
        // Upload-Handler initialisieren falls Uploads aktiviert sind
        if (psource_chat_localized.upload_settings && psource_chat_localized.upload_settings.enabled) {
            PSChatUpload.init();
        }
    });

})(jQuery);
