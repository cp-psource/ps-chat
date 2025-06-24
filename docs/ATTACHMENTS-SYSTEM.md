# PS Chat Attachments System

Das neue modulare Attachments-System ersetzt die vorherigen fest eingebauten Emoji-Funktionen und bietet eine flexible, erweiterbare Lösung für verschiedene Arten von Chat-Anhängen.

## Überblick

Das Attachments-System besteht aus drei Hauptkomponenten:

1. **Globale Attachments-Extension**: Definiert verfügbare Attachment-Typen
2. **Chat-spezifische Einstellungen**: Jeder Chat kann individuell wählen, welche Attachments verwendet werden
3. **Frontend-JavaScript**: Modular aufgebaute Benutzeroberfläche für Attachments

## Funktionen

### Emojis
- **Quelle**: Eingebaute Emojis oder benutzerdefinierte Liste
- **Interface**: Kategorisierter Emoji-Picker mit Tabs
- **Integration**: Direkte Einfügung in Nachrichteneingabe

### GIFs
- **Anbieter**: Giphy oder Tenor API
- **Interface**: Suchbasierter GIF-Picker
- **Format**: Markdown-ähnliche Syntax `[gif]URL[/gif]`

### Datei-Uploads
- **Dateitypen**: Konfigurierbare Liste erlaubter Erweiterungen
- **Größenbegrenzung**: Einstellbare maximale Dateigröße
- **Sicherheit**: Optionale Moderation und Login-Pflicht

## Konfiguration

### Globale Einstellungen (Attachments-Extension)

```php
$options = [
    'enabled' => 'enabled',                    // Extension aktivieren
    'emojis_enabled' => 'yes',                 // Emojis global verfügbar
    'emojis_source' => 'builtin',              // 'builtin' oder 'custom'
    'emojis_custom_set' => '😀,😃,😄,...',     // Benutzerdefinierte Emoji-Liste
    'gifs_enabled' => 'yes',                   // GIFs global verfügbar
    'gifs_source' => 'giphy',                  // 'giphy' oder 'tenor'
    'gifs_api_key' => 'YOUR_API_KEY',          // API-Schlüssel
    'uploads_enabled' => 'yes',                // Uploads global verfügbar
    'uploads_max_size' => '5',                 // Maximale Größe in MB
    'uploads_allowed_types' => 'jpg,png,pdf',  // Erlaubte Dateitypen
    'uploads_require_login' => 'yes',          // Login für Uploads erforderlich
    'moderate_uploads' => 'yes',               // Uploads moderieren
    'attachment_history' => 'yes'              // Attachment-Verlauf speichern
];
```

### Chat-spezifische Einstellungen

Jeder Chat (Frontend, Dashboard, Widgets, etc.) kann individuell konfigurieren, welche der global verfügbaren Attachment-Funktionen verwendet werden sollen:

```php
// Frontend Chat Einstellungen
$frontend_options = [
    'enable_emoji' => 'yes',      // Emojis in diesem Chat aktivieren
    'enable_gifs' => 'no',        // GIFs in diesem Chat deaktivieren
    'enable_uploads' => 'yes'     // Uploads in diesem Chat aktivieren
];
```

## Verwendung für Entwickler

### JavaScript Integration

```javascript
// Attachments-System initialisieren
PSChatAttachments.init({
    emojisEnabled: true,
    gifsEnabled: false,
    uploadsEnabled: true,
    emojiSource: 'builtin',
    customEmojis: ['😀', '😃', '😄'],
    maxFileSize: 5,
    allowedTypes: ['jpg', 'png', 'gif']
});

// Attachment-Buttons für einen Chat rendern
var buttonsHtml = PSChatAttachments.renderButtons({
    enable_emoji: true,
    enable_gifs: false,
    enable_uploads: true
});
```

### PHP Integration

```php
// Attachments-Extension Instanz abrufen
$attachments = new \PSSource\Chat\Extensions\Attachments();

// Attachment-Buttons für Chat-Template generieren
$buttons = $attachments->get_attachment_buttons([
    'enable_emoji' => true,
    'enable_gifs' => false,
    'enable_uploads' => true
]);

echo $buttons; // HTML für Attachment-Buttons
```

## Architektur-Vorteile

### Modularität
- Attachments sind eine separate Extension
- Jeder Attachment-Typ kann unabhängig aktiviert/deaktiviert werden
- Neue Attachment-Typen können einfach hinzugefügt werden

### Flexibilität
- Globale Konfiguration definiert verfügbare Funktionen
- Chat-spezifische Konfiguration ermöglicht individuelle Anpassung
- APIs und externe Dienste können einfach integriert werden

### Erweiterbarkeit
- Plugin-Entwickler können eigene Attachment-Typen hinzufügen
- JavaScript-Events ermöglichen Integration in bestehende Chats
- Hook-System für serverseitige Verarbeitung

## Migration von altem System

Das alte fest eingebaute Emoji-System wurde durch das modulare System ersetzt:

### Alte Implementierung (entfernt)
```javascript
// DEPRECATED: Alte Emoji-Funktionen
createEmojiPicker()
bindEmojiPickerEvents()
insertEmoji()
```

### Neue Implementierung
```javascript
// Modular: Attachment-System
initAttachments()
PSChatAttachments.renderButtons()
```

## CSS-Klassen

### Attachment-Buttons
- `.psource-chat-attachment-buttons` - Container für alle Attachment-Buttons
- `.psource-chat-attachment-btn` - Basis-Klasse für Attachment-Buttons
- `.psource-chat-emoji-btn` - Emoji-Button
- `.psource-chat-gif-btn` - GIF-Button
- `.psource-chat-upload-btn` - Upload-Button

### Picker-Interface
- `.psource-chat-attachment-picker` - Basis-Klasse für alle Picker
- `.psource-chat-emoji-picker` - Emoji-Picker
- `.psource-chat-gif-picker` - GIF-Picker

### Attachment-Anzeige
- `.psource-chat-attachment-preview` - Container für Attachment-Vorschau
- `.psource-chat-gif` - GIF-Anzeige in Nachrichten
- `.attachment-preview-image` - Bild-Vorschau
- `.attachment-preview-file` - Datei-Vorschau

## API-Hooks

### PHP-Hooks
```php
// Attachment-Verarbeitung in Nachrichten
add_filter('psource_chat_message_content', function($content, $message_data) {
    // Verarbeite Attachments im Nachrichteninhalt
    return $content;
}, 10, 2);

// Custom Attachment-Typen registrieren
add_action('psource_chat_register_attachment_types', function($attachments) {
    $attachments->register_type('stickers', [
        'title' => 'Sticker',
        'icon' => '🎭',
        'handler' => 'my_sticker_handler'
    ]);
});
```

### JavaScript-Events
```javascript
// Attachment hinzugefügt
$(document).on('psource_chat_attachment_added', function(e, attachment) {
    console.log('Attachment hinzugefügt:', attachment);
});

// Attachment-Picker geöffnet
$(document).on('psource_chat_picker_opened', function(e, type) {
    console.log('Picker geöffnet:', type);
});
```

## Sicherheit

- **Upload-Validierung**: Dateigröße und -typ werden serverseitig validiert
- **Sanitization**: Alle Benutzereingaben werden bereinigt
- **Moderation**: Optionale Moderation für hochgeladene Inhalte
- **Nonce-Verifikation**: CSRF-Schutz für alle AJAX-Anfragen
- **Capability-Checks**: Berechtigungsprüfung für alle Aktionen

## Performance

- **Lazy Loading**: Attachment-Komponenten werden nur bei Bedarf geladen
- **Caching**: API-Anfragen und Attachment-Listen werden gecacht
- **Optimierte Assets**: Minimierte CSS/JS-Dateien
- **CDN-Unterstützung**: Externe Ressourcen können über CDN geladen werden

## Debugging

### Debug-Modus aktivieren
```php
// In wp-config.php
define('WP_DEBUG', true);

// Oder URL-Parameter
?ps_chat_debug=1
```

### Console-Logs
```javascript
// JavaScript-Debugging
PSChatAttachments.debug = true;
```

### PHP-Logging
```php
// Extension-Logging
$attachments->log('Debug-Nachricht', 'debug');
```

## Erweiterung für Drittanbieter

### Neue Attachment-Typen hinzufügen

```php
class My_Custom_Attachments {
    public function __construct() {
        add_action('psource_chat_load_extensions', [$this, 'register_custom_types']);
    }
    
    public function register_custom_types() {
        // Registriere benutzerdefinierte Attachment-Typen
        add_filter('psource_chat_attachment_types', [$this, 'add_sticker_type']);
    }
    
    public function add_sticker_type($types) {
        $types['stickers'] = [
            'title' => 'Sticker',
            'icon' => '🎭',
            'enabled' => true,
            'handler' => [$this, 'handle_sticker']
        ];
        return $types;
    }
    
    public function handle_sticker($data) {
        // Verarbeite Sticker-Attachment
        return $this->process_sticker($data);
    }
}

new My_Custom_Attachments();
```

Das neue Attachments-System bietet eine solide Grundlage für alle aktuellen und zukünftigen Attachment-Anforderungen im PS Chat Plugin.
