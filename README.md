# PS Chat - WordPress Chat Plugin

![PS Chat Logo](images/ps-chat-256x256.png)

Ein vollständig modernisiertes WordPress-Chat-Plugin mit umfassenden Features für Community-Websites, BuddyPress-Integration und responsive Design.

## 🚀 Features

### Core Features
- **🎯 Dashboard Chat**: Integrierter Chat im WordPress-Dashboard
- **📱 Responsive Widget**: Modernes Chat-Widget für Sidebars
- **⚡ Shortcode Support**: Flexible Chat-Integration mit [psource_chat]
- **🎨 Modern UI/UX**: Responsive, mobile-optimierte Benutzeroberfläche
- **🔒 Sichere Architektur**: Moderne OOP-Struktur mit Namespaces

### Chat Features
- **💬 Real-time Messaging**: Live-Chat ohne externe Server
- **👥 Multi-User Support**: Unbegrenzte gleichzeitige Benutzer
- **🎵 Sound Notifications**: Anpassbare Audio-Benachrichtigungen
- **😊 Emoji Support**: Vollständiger Emoji-Picker
- **📝 Message History**: Konfigurierbare Nachrichtenhistorie
- **🔇 Private Messages**: Direkte Nachrichten zwischen Benutzern

### Administration
- **⚙️ Comprehensive Settings**: Tab-basierte Einstellungsseite
- **👨‍💼 User Management**: Rollen-basierte Berechtigungen
- **🛡️ Moderation Tools**: Nachrichten-Moderation und Spam-Schutz
- **📊 Session Logs**: Detaillierte Chat-Protokollierung
- **🔧 Shortcode Builder**: Visueller Shortcode-Generator

### Security & Performance
- **🚫 Spam Protection**: Anti-Flood und Rate-Limiting
- **🤬 Bad Words Filter**: Automatische Schimpfwort-Filterung
- **🚫 IP Blocking**: IP-basierte Benutzer-Sperrung
- **⚡ Optimized Database**: Indexierte Tabellen und Cache-System
- **🔒 CSRF Protection**: Sichere AJAX/REST-Requests

## 📋 Anforderungen

- **WordPress**: 5.0 oder höher
- **PHP**: 7.4 oder höher
- **MySQL**: 5.6 oder höher
- **Optional**: BuddyPress für erweiterte Community-Features

## 🛠️ Installation

### Automatische Installation
1. WordPress Admin → Plugins → Neu hinzufügen
2. Suche nach "PS Chat"
3. Installieren und aktivieren

### Manuelle Installation
1. Download der neuesten Version
2. Upload nach `/wp-content/plugins/ps-chat/`
3. Plugin im WordPress-Admin aktivieren

### Nach der Aktivierung
1. Gehe zu **PS Chat → Einstellungen**
2. Konfiguriere die gewünschten Optionen
3. Füge das Widget oder Shortcodes hinzu

## 🎯 Verwendung

### Widget
1. **Design → Widgets**
2. "PS Chat Widget" zu gewünschtem Bereich hinzufügen
3. Titel und Optionen konfigurieren

### Shortcode
#### Basis-Verwendung
```
[psource_chat]
```

#### Erweiterte Optionen
```
[psource_chat 
    height="400" 
    width="100%" 
    title="Community Chat" 
    allow_guests="true"
    show_users="true"
    enable_sound="true"
    room="general"
]
```

### Dashboard Chat
- Automatisch verfügbar nach Aktivierung
- Admin → Dashboard → PS Chat Widget

## ⚙️ Konfiguration

### Einstellungen-Tabs

#### **General**
- Chat-Timeouts und Limits
- Sound-Einstellungen
- Emoji-Konfiguration

#### **Appearance**
- Design und Farben
- Layout-Optionen
- Custom CSS

#### **Messages**
- Nachrichten-Moderation
- Bad Words Filter
- Message History

#### **Users**
- Benutzer-Berechtigungen
- Rollen-Konfiguration
- Gast-Chat-Optionen

#### **Widget**
- Widget-Standard-Einstellungen
- Dashboard-Chat-Konfiguration

#### **Advanced**
- Performance-Einstellungen
- Debug-Modi
- API-Konfiguration

## 🔧 Shortcode-Attribute

| Attribut | Standard | Beschreibung |
|----------|----------|--------------|
| `height` | `300px` | Chat-Höhe |
| `width` | `100%` | Chat-Breite |
| `title` | `"Chat"` | Chat-Titel |
| `allow_guests` | `false` | Gast-Chat erlauben |
| `show_users` | `true` | Benutzer-Liste anzeigen |
| `enable_sound` | `true` | Sound-Benachrichtigungen |
| `room` | `"general"` | Chat-Raum |
| `login_message` | `""` | Custom Login-Nachricht |

## 🤝 BuddyPress-Integration

Bei aktiviertem BuddyPress:
- **Gruppen-Chats**: Automatische Chat-Räume für BP-Gruppen
- **Private Messages**: Integration in BP-Nachrichtensystem
- **User Profiles**: Chat-Status in Benutzerprofilen
- **Activity Stream**: Chat-Integration in Activity-Feed

## 🔒 Sicherheit

### Spam-Schutz
- **Flood Protection**: Verhindert Message-Spam
- **Rate Limiting**: Begrenzt Nachrichten pro Zeitraum
- **IP Blocking**: Automatische IP-Sperrung bei Missbrauch

### Content-Filterung
- **Bad Words Filter**: Anpassbare Schimpfwort-Liste
- **Link Moderation**: Automatische Link-Prüfung
- **Manual Moderation**: Moderator-Freigabe für Nachrichten

## 🎨 Anpassung

### CSS-Anpassung
```css
/* Custom Chat Styling */
.psource-chat-container {
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.psource-chat-message {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
```

### JavaScript-Hooks
```javascript
// Custom Event Listeners
jQuery(document).on('psource_chat_message_received', function(e, data) {
    // Custom handling
});
```

## 📱 Mobile Support

- **Responsive Design**: Optimiert für alle Bildschirmgrößen
- **Touch-optimiert**: Mobile-freundliche Bedienung
- **Progressive Web App**: PWA-ready für App-ähnliche Erfahrung

## 🔧 Entwickler-API

### Actions (Hooks)
```php
// Nach Nachricht gesendet
do_action('psource_chat_message_sent', $message_id, $user_id, $content);

// Benutzer tritt Chat bei
do_action('psource_chat_user_joined', $user_id, $session_id);

// Chat-Session gestartet
do_action('psource_chat_session_started', $session_id);
```

### Filters
```php
// Nachricht vor Speicherung
$content = apply_filters('psource_chat_message_content', $content, $user_id);

// Chat-Optionen modifizieren
$options = apply_filters('psource_chat_options', $options);

// Widget-Output anpassen
$output = apply_filters('psource_chat_widget_output', $output, $args);
```

## 📊 Performance

### Optimierungen
- **Database Indexing**: Optimierte Datenbankabfragen
- **AJAX Caching**: Intelligente Request-Optimierung
- **Lazy Loading**: Nachrichten-Historie bei Bedarf laden
- **Asset Minification**: Minimierte CSS/JS-Dateien

### Server-Anforderungen
- **Memory**: Minimum 64MB PHP Memory
- **Database**: InnoDB für bessere Performance
- **Caching**: Kompatibel mit allen WordPress-Cache-Plugins

## 🐛 Debugging

### Debug-Modus aktivieren
```php
// wp-config.php
define('PSOURCE_CHAT_DEBUG', true);
```

### Log-Dateien
- Admin → **PS Chat → Logs**
- Automatische Log-Rotation
- Konfigurierbarer Retention-Zeitraum

## 🆘 Support

### Dokumentation
- [Online-Dokumentation](https://cp-psource.github.io/ps-chat/)
- [GitHub Wiki](https://github.com/cp-psource/ps-chat/wiki)

### Community
- [WordPress.org Forum](https://wordpress.org/support/plugin/ps-chat/)
- [GitHub Issues](https://github.com/cp-psource/ps-chat/issues)

### Professional Support
- [PSOURCE Support](https://github.com/cp-psource)

## 🤝 Mitwirken

Wir freuen uns über Beiträge! Siehe [CONTRIBUTING.md](CONTRIBUTING.md) für Details.

### Entwickler-Setup
```bash
git clone https://github.com/cp-psource/ps-chat.git
cd ps-chat
composer install
npm install
```

## 📄 Lizenz

GPL v2 oder höher. Siehe [LICENSE](LICENSE) für Details.

## 🏆 Credits

### Hauptentwickler
- **PSOURCE Team**: Entwicklung und Wartung
- **Community**: Feedback und Beiträge

### Third-Party Libraries
- **jQuery**: JavaScript-Framework
- **WordPress REST API**: API-Integration
- **Farbtastic**: Color-Picker (Legacy-Support)

## 📈 Roadmap

### Version 3.1
- [ ] WebSocket-Integration für Echtzeit-Chat
- [ ] Voice Messages
- [ ] File Upload Support
- [ ] Advanced Moderation Tools

### Version 3.2
- [ ] React-based Admin Interface
- [ ] GraphQL API
- [ ] Advanced Analytics
- [ ] Multi-language Chat Rooms

## 🔖 Versionen

### Aktuell: 3.0.0
- ✅ Vollständige Modernisierung
- ✅ Neue OOP-Architektur
- ✅ Responsive Design
- ✅ Enhanced Security
- ✅ Performance-Optimierungen

### Legacy: 2.x
- ⚠️ Nicht mehr unterstützt
- Migration zu 3.0 empfohlen

---

<div align="center">

**[Website](https://cp-psource.github.io/ps-chat/) • [Documentation](https://cp-psource.github.io/ps-chat/docs/) • [Support](https://github.com/cp-psource/ps-chat/issues) • [Roadmap](https://github.com/cp-psource/ps-chat/projects)**

Made with ❤️ by the PSOURCE Team

</div>
