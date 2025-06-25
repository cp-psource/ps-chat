# Media Support Feature für PS Chat Plugin

## Überblick

Das PS Chat Plugin wurde um umfassende Media-Unterstützung erweitert! Jetzt erkennt das Plugin automatisch URLs in Chat-Nachrichten und zeigt eine passende Vorschau an:

- **🔗 Link-Previews**: Automatische Metadaten-Extraktion von Webseiten mit Titel, Beschreibung und Vorschaubild
- **🖼️ Bilder**: Direkte Anzeige von Bildern aus URLs (JPG, PNG, GIF, WebP, SVG, BMP)
- **📹 Videos**: Unterstützung für HTML5-Videos (MP4, WebM, OGG, AVI, MOV)
- **▶️ YouTube**: Einbettung und Wiedergabe von YouTube-Videos direkt im Chat

## Neue Funktionen

### 🔗 Intelligente Link-Erkennung
Das Plugin erkennt automatisch URLs in Chat-Nachrichten und generiert Live-Previews:

```
https://example.com/artikel
```
↓ wird zu einer ansprechenden Link-Vorschau mit:
- Titel der Webseite
- Beschreibung/Excerpt
- Vorschaubild (falls vorhanden)
- Name der Website

### 🖼️ Direkte Bild-Anzeige
Bild-URLs werden automatisch als Bilder angezeigt:

```
https://example.com/image.jpg
```
↓ wird direkt als Bild im Chat dargestellt
- Optimiert für verschiedene Bildformate
- Click-to-Zoom Lightbox
- Lazy Loading für Performance

### ▶️ YouTube-Integration
YouTube-Videos werden als elegante Embeds angezeigt:

```
https://www.youtube.com/watch?v=VIDEO_ID
https://youtu.be/VIDEO_ID
```
↓ wird zu einem YouTube-Player mit:
- Video-Thumbnail
- Video-Titel (automatisch geholt)
- Click-to-Play Funktionalität
- Responsive Design

### 📹 Video-Unterstützung
Direkte Video-URLs werden als HTML5-Video-Player angezeigt:

```
https://example.com/video.mp4
```
↓ wird als eingebetteter Video-Player dargestellt

## Technische Details

### Unterstützte Dateiformate

**Bilder:**
- JPG/JPEG
- PNG  
- GIF (inklusive animierte)
- WebP
- SVG
- BMP

**Videos:**
- MP4
- WebM
- OGG
- AVI
- MOV

### Performance-Optimierungen

- **Caching**: Link-Metadaten werden für 1 Stunde gecacht
- **Lazy Loading**: Bilder werden erst geladen, wenn sie sichtbar werden
- **Debouncing**: Live-Previews werden nur nach 1 Sekunde Pause generiert
- **Optimierte AJAX**: Separate Endpoints für Media-Verarbeitung

### Sicherheit

- **Content Security**: Alle URLs werden validiert und sanitisiert
- **XSS-Schutz**: HTML-Output wird ordnungsgemäß escaped
- **Domain-Filtering**: Unterstützung für Blacklisting problematischer Domains
- **Nonce-Verification**: Alle AJAX-Requests sind abgesichert

## Integration

### Neue Hooks/Filter

```php
// Nachricht vor dem Speichern verarbeiten
apply_filters('psource_chat_before_save_message', $message, $chat_session);

// Nachricht für die Anzeige verarbeiten  
apply_filters('psource_chat_display_message', $message, $message_data);
```

### AJAX-Endpoints

```javascript
// Link-Preview abrufen
wp.ajax.post('psource_chat_get_link_preview', {
    url: 'https://example.com',
    nonce: psource_chat_nonce
});
```

## Verwendung

### Für Benutzer
Das Feature funktioniert vollständig automatisch:

1. **Link eingeben**: Einfach eine URL in den Chat schreiben
2. **Live-Preview**: Vorschau erscheint automatisch beim Tippen
3. **Senden**: Nachricht wird mit Media-Content gespeichert
4. **Anzeige**: Andere Benutzer sehen die Rich-Media-Inhalte sofort

### Für Entwickler

**Media-Handler erweitern:**
```php
// Eigenen Media-Typ hinzufügen
add_filter('psource_chat_before_save_message', function($message, $chat_session) {
    // Custom Media-Verarbeitung
    return $message;
}, 10, 2);
```

**Custom CSS für Media-Inhalte:**
```css
.psource-chat-media-item[data-type="custom"] {
    /* Eigene Styles */
}
```

## CSS-Klassen

Das neue Media-System fügt folgende CSS-Klassen hinzu:

- `.psource-chat-media-item` - Container für alle Media-Inhalte
- `.psource-chat-link-preview` - Link-Vorschau Container  
- `.psource-chat-image` - Bild-Container
- `.psource-chat-video` - Video-Container
- `.psource-chat-youtube` - YouTube-Embed Container
- `.psource-chat-lightbox` - Bild-Lightbox
- `.psource-chat-preview-container` - Live-Preview Container

## Browser-Unterstützung

- ✅ Chrome 60+
- ✅ Firefox 55+  
- ✅ Safari 12+
- ✅ Edge 79+
- ✅ Mobile Browser (iOS Safari, Chrome Mobile)

## Dateien

### Neue Dateien:
- `includes/class-psource-chat-media.php` - Media-Handler Hauptklasse
- `js/psource-chat-media.js` - Frontend JavaScript für Media-Funktionalität

### Geänderte Dateien:
- `psource-chat.php` - Integration der Media-Klasse
- `includes/class-psource-chat.php` - Hooks und send_message Funktion
- `css/psource-chat-style.css` - Media-Styles hinzugefügt

## Roadmap

Zukünftige Erweiterungen könnten umfassen:
- 📱 Instagram/Twitter/TikTok Embeds
- 🎵 Audio-Player für MP3/OGG Dateien  
- 📄 PDF-Viewer Integration
- 🗺️ Google Maps Links
- 📊 Rich Cards für andere Dienste

## Support

Bei Fragen oder Problemen:
1. Plugin-Cache leeren
2. Browser-Cache löschen  
3. JavaScript-Konsole auf Fehler prüfen
4. Network-Tab für fehlgeschlagene Requests überprüfen

---

**Version**: 2.5.1  
**Erstellt**: Juni 2025  
**Kompatibilität**: WordPress 5.0+, PHP 7.4+
