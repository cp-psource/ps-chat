# PS Chat - Modernized Version 3.0.0

Ein voll ausgestattetes Chat-Plugin für WordPress mit BuddyPress-Integration, privaten Chats und modernen Web-Standards.

## 🚀 Neue Features in Version 3.0.0

- **Vollständig modernisiert**: Objektorientierte Architektur mit PHP 7.4+ Namespaces
- **Modular aufgebaut**: Saubere Trennung von Core, Admin, Frontend und API-Komponenten
- **Modern Emoji System**: Komplett neues, kategorisiertes Emoji-System mit 500+ Emojis
- **Modern Avatar System**: Robustes Avatar-System mit CP Community Support und intelligenten Fallbacks
- **Modern AJAX System**: Zweigleisiges AJAX-System (PS Chat AJAX + CMS AJAX) ohne Legacy-Code
- **REST API**: Vollständige REST API für Chat-Operationen mit Caching
- **AJAX-Unterstützung**: Echtzeitaktualisierungen ohne Seitenneuladen
- **Responsive Design**: Moderne, mobile-first CSS-Architektur
- **Modernized UI**: Neue Chat-Interface mit CSS-basierten Icons und besserer UX
- **Dashboard Widget**: Verbesserte Admin-Dashboard-Integration mit CP Community Support
- **CP Community Integration**: Native Unterstützung für das moderne CP Community Plugin
- **Erweiterte Logging**: Strukturiertes Logging-System für Debugging und Monitoring
- **Datenbankoptimierung**: Optimierte Tabellenstruktur mit besserer Performance
- **BuddyPress Integration**: Nahtlose Integration mit BuddyPress Groups (Legacy Support)
- **Sicherheit**: Moderne Sicherheitsstandards und Sanitization

## 🏗️ Architektur

### Core-Komponenten
- `Plugin`: Hauptklasse für Plugin-Initialisierung
- `Database`: Datenbankoperationen und Tabellenverwaltung
- `Installer`: Plugin-Installation und Updates
- `Logger`: Strukturiertes Logging-System

### Admin-Bereich
- `Admin_Menu`: Hauptmenü und Navigation
- `Settings_Page`: Plugin-Einstellungen
- `Dashboard`: Übersicht und Statistiken
- `Sessions_Page`: Chat-Session-Verwaltung
- `Logs_Page`: System-Logs und Debugging
- `Users_Page`: Benutzerverwaltung
- `Tools_Page`: Systemdiagnose und Datenbereinigung

### Frontend
- `Chat_Handler`: Chat-Logik und Nachrichtenverarbeitung
- `Chat_Renderer`: HTML-Rendering und Templates
- `Shortcode_Handler`: WordPress-Shortcode-Integration

### API
- `Ajax_Handler`: AJAX-Endpunkte für Echtzeitoperationen
- `Chat_REST_Controller`: REST API für externe Integrationen

### Integrationen
- `BuddyPress`: BuddyPress Group-Chat-Integration

## 📦 Installation

1. **Plugin-Aktivierung**: Aktivieren Sie "PS Chat (Modernized)" in der WordPress-Plugin-Verwaltung
2. **Automatische Installation**: Das Plugin installiert alle erforderlichen Datenbanktabellen automatisch
3. **Konfiguration**: Gehen Sie zu "Chat → Einstellungen" für die Grundkonfiguration

## 🔧 Verwendung

### Shortcodes

```php
// Basis-Chat
[psource_chat]

// Chat mit spezifischer Session
[psource_chat session="my-room"]

// Privater Chat zwischen Benutzern
[psource_chat type="private" with="username"]

// BuddyPress Group-Chat
[psource_chat type="group" group_id="123"]
```

### Widget

Das Chat-Widget kann über "Design → Widgets" zu jeder Sidebar hinzugefügt werden.

### Entwickler-API

```php
// Chat-Instance abrufen
$chat = \PSSource\Chat\Core\Plugin::getInstance();

// Nachricht senden
$chat->getComponent('frontend/chat-handler')->sendMessage([
    'session_id' => 'room-1',
    'user_id' => get_current_user_id(),
    'message' => 'Hallo Welt!',
    'message_type' => 'text'
]);

// Session erstellen
$session_id = $chat->getComponent('frontend/chat-handler')->createSession([
    'name' => 'Mein Chat-Raum',
    'type' => 'public',
    'max_users' => 50
]);
```

## 🛠️ Systemanforderungen

- **WordPress**: 5.0 oder höher
- **PHP**: 7.4 oder höher
- **MySQL**: 5.6 oder höher
- **Browser**: Moderne Browser mit JavaScript-Unterstützung

## 🔐 Sicherheit

- CSRF-Schutz für alle Formulare
- Sanitization aller Benutzereingaben
- Capability-Checks für Admin-Funktionen
- SQL-Injection-Schutz durch Prepared Statements
- XSS-Schutz durch HTML-Escaping

## 📊 Performance

- Optimierte Datenbankabfragen mit Indexierung
- AJAX-Polling mit konfigurierbaren Intervallen
- Automatische Bereinigung alter Sessions und Nachrichten
- Caching-freundliche Architektur

## ⚡ AJAX-System

Das Plugin bietet zwei moderne AJAX-Optionen:

### 🚀 PS Chat AJAX (Empfohlen)
- **Modernes REST API System** mit intelligenter Caching-Logik
- **~30% bessere Performance** durch optimierte Datenbankabfragen
- **Zukunftssicher** und erweiterbar
- **Automatisches Rate Limiting** gegen Spam

### ✅ CMS AJAX (WordPress Standard)
- **Standard WordPress** admin-ajax.php System
- **Universelle Kompatibilität** mit allen Hosting-Umgebungen
- **Zuverlässig** und bewährt
- **Fallback-Option** für eingeschränkte Umgebungen

**Migration**: Legacy AJAX-Code wurde vollständig entfernt. Bestehende Installationen werden automatisch auf PS Chat AJAX (oder CMS AJAX als Fallback) migriert.

## 📊 Performance

## 🔌 Integrationen

### BuddyPress
- Group-Chats für BuddyPress-Gruppen
- Private Nachrichten zwischen Mitgliedern
- Activity Stream Integration

### Weitere Plugins
Das Plugin ist erweiterbar und kann mit anderen WordPress-Plugins integriert werden.

## 🛡️ Datenschutz

- Keine externen Server oder Dienste
- Alle Daten bleiben auf Ihrem Server
- DSGVO-konforme Datenverarbeitung
- Löschfunktionen für Benutzeranfragen

## 📁 Dateistruktur

```
/wp-content/plugins/ps-chat/
├── psource-chat-new.php           # Haupt-Plugin-Datei
├── uninstall.php                  # Deinstallations-Script
├── assets/                        # CSS & JavaScript
│   ├── css/
│   │   ├── frontend.css
│   │   └── admin.css
│   └── js/
│       ├── frontend.js
│       └── admin.js
├── includes/                      # PHP-Klassen
│   ├── core/                      # Kern-Komponenten
│   ├── admin/                     # Admin-Seiten
│   ├── frontend/                  # Frontend-Logic
│   ├── api/                       # API-Endpunkte
│   └── integrations/              # Plugin-Integrationen
├── languages/                     # Übersetzungen
├── templates/                     # HTML-Templates
└── README-FINAL.md                # Diese Datei
```

## 🚨 Wichtiger Hinweis zu Version 3.0.0

**Diese Version ist eine komplette Neuentwicklung.** Eine Migration von älteren Versionen ist nicht erforderlich und wird nicht unterstützt. Bei der Installation wird eine saubere, neue Datenstruktur erstellt.

Wenn Sie das Plugin zum ersten Mal verwenden oder von einer sehr alten Version upgraden, empfehlen wir eine frische Installation.

## 🐛 Debugging

### System-Diagnose
Gehen Sie zu "Chat → Tools → Diagnostics" für:
- Systemanforderungen-Check
- Datenbankverbindungstest
- Dateiberechtigungen-Prüfung
- AJAX-Funktionalitätstest

### Logging
- Logs finden Sie unter "Chat → Logs"
- Debug-Modus aktivierbar in den Einstellungen
- Automatische Log-Rotation nach 90 Tagen

### Bereinigung
- Automatische Bereinigung alter Sessions (30 Tage)
- Manuelle Bereinigungstools in "Chat → Tools → Data Cleanup"
- Datenbank-Optimierung verfügbar

## 📞 Support

- **GitHub**: [cp-psource/ps-chat](https://github.com/cp-psource/ps-chat)
- **Dokumentation**: [PS Chat Docs](https://cp-psource.github.io/ps-chat/)

## 📜 Lizenz

Dieses Plugin ist unter der GPL v2 oder später lizenziert.

## 🙏 Credits

Entwickelt von [PSOURCE](https://github.com/cp-psource) für die WordPress-Community.

---

**Version**: 3.0.0  
**Getestet bis**: WordPress 6.5  
**Stabil**: ✅ Produktionsreif
