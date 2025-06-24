# PS Chat Extensions System - Vollständige Dokumentation

Das PS Chat Plugin verfügt jetzt über ein vollständig funktionales Extensions-System, das alle Grundfunktionen des Originalplugigns als konfigurierbare Module bereitstellt.

## Verfügbare Extensions

### 1. Dashboard Chat Extension
**Datei:** `includes/admin/dashboard-widgets.php`  
**Features:**
- Haupt-Chat-Widget im WordPress Dashboard
- Status-Widget für schnelle Status-Änderung  
- Freunde-Widget mit Online-Anzeige
- Vollständige AJAX-Integration
- Responsive Design

**Konfiguration:**
- Widget aktivieren/deaktivieren
- Widget-Titel anpassen
- Höhe konfigurieren
- Benutzer-Kontrolle aktivieren
- Netzwerk-Modus für Multisite

### 2. Frontend Chat Extension
**Datei:** `includes/frontend/frontend-chat.php`  
**Features:**
- Auto-Embed in Posts/Seiten
- Floating Chat Window
- Shortcode-Support (`[ps_chat]`, `[ps_chat_status]`, etc.)
- Responsive Chat-Interface
- Gast-Chat-Unterstützung

**Konfiguration:**
- Automatisches Einbetten konfigurieren
- Chat-Position festlegen (vor/nach Inhalt, floating)
- Shortcode-Parameter anpassen

### 3. Chat Widgets Extension
**Datei:** `includes/frontend/enhanced-widgets.php`  
**Features:**
- Haupt-Chat Widget (moderne Version)
- Status Widget für Sidebars
- Freunde Widget mit Online-Status
- Räume Widget mit aktiven Chats
- Vollständig WordPress Widget API kompatibel

**Konfiguration:**
- Einzelne Widgets aktivieren/deaktivieren
- Widget-spezifische Einstellungen

### 4. Admin Bar Chat Extension
**Datei:** `includes/frontend/admin-bar-chat.php`  
**Features:**
- Chat-Menü in WordPress Admin Bar
- Status-Umschaltung
- Online-Freunde-Anzeige
- Schnell-Chat-Popup
- Benachrichtigungs-Badges

**Konfiguration:**
- Admin Bar Integration aktivieren/deaktivieren
- Popup-Verhalten konfigurieren
- Benachrichtigungen einstellen

### 5. Performance Extension
**Features:**
- Polling-Intervalle konfigurieren
- Heartbeat-Einstellungen
- Cache-Optionen
- Datenbankoptimierung

### 6. Security Extension  
**Features:**
- IP-Blockierung
- Wort-Filter
- URL-Blockierung
- Spam-Schutz

### 7. BuddyPress Extension
**Datei:** `includes/integrations/buddypress.php`  
**Features:**
- Gruppen-Chat Integration
- Private Chats zwischen Freunden
- Activity Stream Integration
- BuddyPress Benachrichtigungen

## Core Engine
**Datei:** `includes/core/chat-engine.php`  
**Features:**
- AJAX-Handlers für alle Chat-Funktionen
- Nachrichten senden/empfangen
- Benutzer-Status management
- Chat-Räume verwalten
- Heartbeat-System

## JavaScript Assets

### Dashboard (`assets/js/dashboard.js`)
- Dashboard-Widget-Funktionalität
- Echtzeit-Nachrichten
- Status-Management
- Freunde-Integration

### Admin Bar (`assets/js/admin-bar.js`)
- Admin Bar Popup-Chat
- Status-Umschaltung
- Benachrichtigungs-System

### Frontend (`assets/js/frontend.js`)
- Frontend-Chat-Interface
- Shortcode-Handler
- Floating Chat Window

## CSS Assets

### Dashboard (`assets/css/dashboard.css`)
- Dashboard-Widget Styling
- Responsive Design
- Dark Mode Support

### Admin Bar (`assets/css/admin-bar.css`)
- Admin Bar Integration
- Popup-Styling
- Mobile Responsive

### Frontend (`assets/css/enhanced-frontend.css`)
- Frontend Chat Styling
- Floating Window Design
- Cross-Browser-Kompatibilität

## Datenbank-Struktur

Das Plugin erstellt folgende Tabellen:
- `wp_psource_chat_messages` - Chat-Nachrichten
- `wp_psource_chat_users` - Benutzer-Status und Aktivität
- `wp_psource_chat_rooms` - Chat-Räume
- `wp_psource_chat_room_users` - Raum-Mitgliedschaften

## Extensions API

**Datei:** `includes/api/extensions-api.php`

Drittanbieter-Plugins können eigene Extensions registrieren:

```php
// Extension registrieren
add_action('psource_chat_register_extensions', function($extensions_manager) {
    $extensions_manager->register_extension('my_extension', [
        'title' => 'Meine Extension',
        'description' => 'Beschreibung der Extension',
        'icon' => 'dashicons-admin-generic',
        'callback' => 'my_extension_render_function',
        'priority' => 100
    ]);
});

// Render-Funktion
function my_extension_render_function($ext_id) {
    // Extension-Einstellungen hier rendern
    echo '<p>Meine Extension Einstellungen</p>';
}
```

## Admin-Interface

### Erweiterungen-Seite
**Zugriff:** WordPress Admin → PS Chat → Erweiterungen

Die Extensions-Seite bietet:
- Grid-Layout für alle verfügbaren Extensions
- Tab-basierte Navigation zwischen Extensions
- Einstellungsformular mit automatischem Speichern
- Responsive Design für alle Bildschirmgrößen

### Legacy Settings
Die ursprünglichen Plugin-Einstellungen sind weiterhin unter "Chat Einstellungen" verfügbar und vollständig kompatibel.

## Installation und Aktivierung

1. Plugin installieren/aktivieren
2. Automatische Datenbank-Erstellung
3. Extensions-System wird automatisch initialisiert
4. Admin-Menü wird erweitert

## Kompatibilität

### WordPress
- Mindestversion: 5.0
- Getestet bis: 6.5
- PHP: 7.4+

### Plugins
- **BuddyPress:** Vollständige Integration verfügbar
- **PS Freunde:** Unterstützt (falls verfügbar)
- **Multisite:** Vollständig unterstützt

### Browser
- Chrome, Firefox, Safari, Edge
- Mobile Browser unterstützt
- IE11+ (eingeschränkte Unterstützung)

## Migration von Legacy

Das Plugin erkennt automatisch bestehende PS Chat Installationen und:
- Migriert alle Einstellungen
- Behält Datenbankstruktur bei
- Konvertiert alte Widget-Einstellungen
- Aktiviert entsprechende Extensions

## Performance

### Optimierungen
- AJAX-basierte Kommunikation
- Configurable Polling-Intervalle
- Lazy Loading für Widgets
- Minimierte Asset-Dateien

### Caching
- Browser-Caching für Nachrichten
- Transient-basierte Server-Caches
- Datenbankabfrage-Optimierung

## Sicherheit

### Implementierte Maßnahmen
- Nonce-Verifizierung für alle AJAX-Calls
- Sanitization aller Eingaben
- SQL-Injection Schutz
- XSS-Schutz für Nachrichten

### Konfigurierbare Sicherheit
- IP-Blockierung
- Wort-Filter mit Bad-Words-Liste
- Rate-Limiting für Nachrichten
- URL-Blockierung

## Debugging und Testing

### Test-Dateien
- `test-extensions.php` - Vollständiger Extensions-Test
- `test-activation.php` - Plugin-Aktivierung testen
- `test-plugin-features.php` - Feature-Tests

### Debug-Modi
- WordPress Debug-Modus unterstützt
- Console-Logging in JavaScript
- PHP Error-Logging

## Erweiterte Konfiguration

### Shortcodes
```php
// Haupt-Chat
[ps_chat height="400px" room="general" title="Chat"]

// Status-Widget
[ps_chat_status show_avatar="true" show_message="true"]

// Online-Benutzer
[ps_chat_users limit="10" show_avatars="true"]

// Aktive Räume
[ps_chat_rooms limit="5" show_description="true"]
```

### PHP-Hooks
```php
// Extension-Optionen abrufen
$options = psource_chat_get_extension_option('dashboard', 'widget_enabled', 'disabled');

// Extension registrieren
add_action('psource_chat_register_extensions', 'my_extension_register');

// Chat-Nachricht-Filter
add_filter('psource_chat_message_content', 'my_message_filter');
```

### JavaScript-Events
```javascript
// Chat-Events
document.addEventListener('psource_chat_message_sent', function(e) {
    console.log('Nachricht gesendet:', e.detail);
});

document.addEventListener('psource_chat_user_status_changed', function(e) {
    console.log('Status geändert:', e.detail);
});
```

## Support und Dokumentation

### Offizielle Ressourcen
- GitHub Repository: https://github.com/cp-psource/ps-chat
- Dokumentation: https://cp-psource.github.io/ps-chat/
- Support-Forum: WordPress.org

### Community
- Extensions können von Drittanbietern entwickelt werden
- API-Dokumentation für Entwickler verfügbar
- Beispiel-Extensions im Repository

## Roadmap

### Geplante Features
- WebSocket-Unterstützung für Echtzeit-Chat
- Mobile App Integration
- Erweiterte Moderation-Tools
- Video/Voice Chat Integration
- Multi-Sprachen-Übersetzungen

Das PS Chat Extensions-System ist jetzt vollständig implementiert und bietet eine moderne, erweiterbare Chat-Lösung für WordPress mit vollständiger Rückwärtskompatibilität zu den ursprünglichen Plugin-Features.
