# PS Chat - Modular WordPress Chat Plugin

## Überblick

PS Chat wurde von einer monolithischen Legacy-Struktur zu einer modernen, modularen, objektorientierten Architektur migriert. Das Plugin bietet jetzt:

- **Modular Structure**: Saubere Trennung von Core, Admin, Frontend, API und Integrations
- **Object-Oriented**: Moderne PHP-Klassen mit Namespaces
- **REST API**: RESTful API-Endpunkte für moderne Integrationen
- **AJAX Support**: Asynchrone Chat-Funktionalität
- **Database Optimized**: Effiziente Datenbankstruktur mit Indizierung
- **Logging System**: Umfassendes Logging für Debugging und Monitoring
- **Admin Interface**: Moderne, responsive Admin-Oberfläche
- **Migration Tools**: Automatische Migration von Legacy-Daten

## Struktur

```
psource-chat/
├── psource-chat-new.php         # Neuer Plugin-Bootstrap
├── migration.php                # Migrations-Script
├── uninstall.php               # Saubere Deinstallation
├── includes/
│   ├── core/                   # Kern-Funktionalität
│   │   ├── plugin.php         # Haupt-Plugin-Klasse
│   │   ├── database.php       # Datenbankoperationen
│   │   ├── installer.php      # Installation/Updates
│   │   └── logger.php         # Logging-System
│   ├── admin/                  # Admin-Funktionalität
│   │   ├── admin-menu.php     # Admin-Menü
│   │   ├── settings-page.php  # Einstellungen
│   │   ├── dashboard.php      # Dashboard
│   │   ├── sessions-page.php  # Session-Management
│   │   ├── logs-page.php      # Log-Verwaltung
│   │   ├── users-page.php     # Benutzer-Verwaltung
│   │   └── tools-page.php     # Tools & Migration
│   ├── frontend/               # Frontend-Funktionalität
│   │   ├── chat-handler.php   # Chat-Logic
│   │   ├── chat-renderer.php  # UI-Rendering
│   │   └── shortcode-handler.php # Shortcode-Support
│   ├── api/                    # API-Endpunkte
│   │   ├── ajax-handler.php   # AJAX-Verarbeitung
│   │   └── chat-rest-controller.php # REST API
│   └── integrations/           # Drittanbieter-Integrationen
│       └── buddypress.php     # BuddyPress-Integration
├── assets/
│   ├── css/
│   │   ├── frontend.css       # Frontend-Styles
│   │   └── admin.css          # Admin-Styles
│   └── js/
│       ├── frontend.js        # Frontend-JavaScript
│       └── admin.js           # Admin-JavaScript
└── legacy-files/              # Legacy-Dateien (werden migriert)
```

## Installation & Migration

### Schritt 1: Plugin aktivieren
1. Aktiviere das neue Plugin über `psource-chat-new.php`
2. Das Plugin erstellt automatisch die neuen Datenbankstrukturen

### Schritt 2: Migration ausführen
1. Gehe zu **PS Chat > Tools > Migration**
2. Überprüfe die erkannten Legacy-Daten
3. Wähle Migrations-Optionen:
   - Backup vor Migration erstellen ✓
   - Legacy-Daten nach erfolgreicher Migration löschen (optional)
   - Test-Modus (keine echten Änderungen)
4. Starte die Migration

### Schritt 3: Konfiguration
1. Gehe zu **PS Chat > Einstellungen**
2. Überprüfe und aktualisiere die Einstellungen
3. Teste die Chat-Funktionalität

## Features

### Core Features
- **Session Management**: Effiziente Session-Verwaltung
- **Message Handling**: Optimierte Nachrichten-Verarbeitung
- **User Management**: Benutzer-Verwaltung mit Rollen und Berechtigungen
- **Real-time Updates**: Live-Updates via AJAX
- **Responsive Design**: Mobile-optimierte Benutzeroberfläche

### Admin Features
- **Dashboard**: Übersicht über Chat-Aktivitäten
- **Session Management**: Aktive Sessions verwalten
- **User Management**: Benutzer sperren/entsperren, Moderatoren zuweisen
- **Logs**: Umfassende Log-Verwaltung (Error, Activity, System)
- **Tools**: Migration, Backup/Restore, Cleanup, Diagnostics

### API Features
- **REST Endpoints**: `/wp-json/pschat/v1/`
  - `GET /sessions` - Sessions abrufen
  - `POST /sessions` - Session erstellen
  - `GET /messages` - Nachrichten abrufen
  - `POST /messages` - Nachricht senden
- **AJAX Actions**:
  - `psource_chat_send_message`
  - `psource_chat_get_messages`
  - `psource_chat_join_session`

### Security Features
- **Nonce Verification**: CSRF-Schutz
- **User Permissions**: Rollenbasierte Zugriffskontrolle
- **Input Sanitization**: Schutz vor XSS und SQL-Injection
- **Rate Limiting**: Schutz vor Spam
- **Bad Word Filter**: Inhaltsmäßige Filterung

## Datenbankstruktur

### Tabellen
- `wp_psource_chat_sessions` - Chat-Sessions
- `wp_psource_chat_messages` - Chat-Nachrichten
- `wp_psource_chat_user_sessions` - Benutzer-Session-Zuordnungen
- `wp_psource_chat_logs` - System-/Error-/Activity-Logs

### Migrierte Daten
- Legacy-Optionen → Neue strukturierte Einstellungen
- Legacy-Session-Daten → Neue Session-Tabelle
- Legacy-Nachrichten → Neue Messages-Tabelle
- Legacy-Benutzer-Meta → Neue User-Sessions

## Verwendung

### Shortcode
```php
[psource_chat type="site" room="general"]
```

### PHP Integration
```php
// Chat in Theme einbetten
do_action('psource_chat_render');

// Programmatisch Nachricht senden
PSSource\Chat\Core\Database::add_message($session_id, [
    'message_text' => 'Hello World',
    'user_name' => 'Admin'
]);
```

### REST API Beispiele
```javascript
// Session erstellen
fetch('/wp-json/pschat/v1/sessions', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': pschat_nonce
    },
    body: JSON.stringify({
        type: 'site',
        host: window.location.host
    })
});

// Nachricht senden
fetch('/wp-json/pschat/v1/messages', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': pschat_nonce
    },
    body: JSON.stringify({
        session_id: 'xxx-xxx-xxx',
        message: 'Hello World!'
    })
});
```

## Hooks & Filter

### Actions
- `psource_chat_message_sent` - Nach dem Senden einer Nachricht
- `psource_chat_session_created` - Nach Session-Erstellung
- `psource_chat_user_joined` - Benutzer tritt Session bei
- `psource_chat_user_left` - Benutzer verlässt Session

### Filters
- `psource_chat_message_content` - Nachrichten-Inhalt filtern
- `psource_chat_user_permissions` - Benutzer-Berechtigungen anpassen
- `psource_chat_session_data` - Session-Daten modifizieren

## Wartung

### Backup
- Automatische Backups vor Migration
- Manuelle Backups über Admin-Interface
- Export als JSON oder ZIP

### Cleanup
- Alte Sessions automatisch bereinigen
- Log-Rotation
- Datenbankoptimierung

### Monitoring
- Error-Logs für Debugging
- Activity-Logs für Überwachung
- System-Logs für Performance-Monitoring

## Support

Bei Fragen oder Problemen:
1. Überprüfe die Logs unter **PS Chat > Logs**
2. Führe Diagnostics unter **PS Chat > Tools > Diagnostics** aus
3. Exportiere System-Informationen für Support-Anfragen

## Migrationshinweise

⚠️ **Wichtig**: 
- Erstelle immer ein vollständiges Backup vor der Migration
- Teste die Migration zuerst im Test-Modus
- Überprüfe nach der Migration alle Chat-Funktionalitäten
- Die Legacy-Dateien können nach erfolgreicher Migration entfernt werden

Die Migration ist darauf ausgelegt, alle bestehenden Daten zu erhalten und nahtlos in die neue Struktur zu übertragen.
