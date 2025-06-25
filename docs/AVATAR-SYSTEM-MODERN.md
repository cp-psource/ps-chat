# Modern Avatar System for PS Chat

## Übersicht

Das PS Chat Plugin verfügt jetzt über ein modernes, robustes Avatar-System mit intelligenten Fallback-Mechanismen und voller Unterstützung für CP Community.

## Features

### 1. Intelligente Avatar-Priorisierung
Das System versucht Avatare in folgender Reihenfolge zu laden:

1. **CP Community Avatar** (primär)
   - Nutzt `user_avatar_get_avatar()` wenn verfügbar
   - Unterstützt sowohl Thumb- als auch Full-Größen
   - Automatische Größenerkennung (>100px = full, <=100px = thumb)

2. **WordPress/Gravatar Avatar** (Fallback)
   - Standard `get_avatar()` Funktion
   - Intelligente Erkennung von Standard-Avataren
   - Überspringt "mystery-man" und andere Platzhalter

3. **Lokaler Platzhalter** (finale Fallback)
   - SVG-basierter Benutzer-Icon als data URI
   - Funktioniert ohne externe Abhängigkeiten
   - Konsistentes Design

### 2. Klassen-Struktur

```php
PSource_Chat_Avatar
├── get_avatar()           // Hauptfunktion für Avatar-Abruf
├── get_chat_avatar()      // Legacy-kompatible Funktion
├── get_placeholder_avatar() // Platzhalter-Avatar
├── clear_cache()          // Cache-Management
└── set_placeholder()      // Custom Platzhalter setzen
```

### 3. JavaScript-Integration

- Automatische 404-Erkennung für defekte Avatar-URLs
- Sofortiger Ersatz durch Platzhalter bei Fehlern
- Event-basierte Aktualisierung bei Chat-Updates
- CSS-Unterstützung für smooth Transitions

## Implementierung

### PHP-Nutzung

```php
// HTML Avatar mit Default-Größe (96px)
$avatar_html = PSource_Chat_Avatar::get_avatar($user_id, 96, true);

// URL-only für JavaScript/CSS
$avatar_url = PSource_Chat_Avatar::get_avatar($user_id, 96, false);

// Legacy-kompatible Funktion
$avatar_url = PSource_Chat_Avatar::get_chat_avatar($user_id, $email, $name);
```

### JavaScript-Integration

```javascript
// Wird automatisch initialisiert
psource_chat.init_avatar_fallbacks();

// Nach Content-Updates aufrufen
psource_chat.refresh_avatar_fallbacks();
```

### CSS-Anpassungen

```css
/* Automatische Platzhalter für 404-Avatare */
.avatar-placeholder {
    background-color: #f0f0f0;
    border-radius: 50%;
    opacity: 0.8;
}

/* Hover-Effekte */
.psource-chat-user-avatar:hover img.avatar {
    transform: scale(1.05);
    transition: all 0.2s ease;
}
```

## Cache-System

Das Avatar-System verwendet intelligentes Caching:

- **Cache-Key**: `user_{id}_size_{size}_html_{bool}`
- **Lebensdauer**: Session-basiert (wird bei Seitenreload geleert)
- **Clearing**: Automatisch oder manuell per `clear_cache()`

```php
// Cache für spezifischen User löschen
PSource_Chat_Avatar::clear_cache($user_id);

// Gesamten Cache löschen
PSource_Chat_Avatar::clear_cache();
```

## CP Community Integration

Das System erkennt automatisch verfügbare CP Community Funktionen:

```php
// Prüfungen im System
if (function_exists('user_avatar_fetch_avatar')) {
    // CP Community verfügbar
}

if (function_exists('user_avatar_avatar_exists')) {
    // Avatar-Existenz prüfbar
}
```

### Avatar-Upload-Integration

Wenn CP Community aktiv ist, können Benutzer:
- Avatare über das CP Community Interface hochladen
- Avatare direkt in der Chat-Oberfläche verwalten
- Automatische Synchronisation zwischen Chat und Profil

## Error Handling

### PHP-Ebene
```php
try {
    $avatar = user_avatar_fetch_avatar($args);
} catch (Exception $e) {
    error_log('Avatar Error: ' . $e->getMessage());
    return false; // Fallback aktivieren
}
```

### JavaScript-Ebene
```javascript
img.onerror = function() {
    console.log('Avatar 404:', this.src);
    this.src = placeholder_url;
    this.classList.add('avatar-placeholder');
};
```

## Performance-Optimierungen

1. **Lazy Loading**: Avatare werden nur bei Bedarf geladen
2. **Caching**: Wiederholte Aufrufe nutzen gecachte Ergebnisse
3. **Data URIs**: Platzhalter benötigen keine HTTP-Requests
4. **Batch Processing**: JavaScript behandelt mehrere Avatare effizient

## Debugging

### Debug-Output aktivieren
```php
// In wp-config.php oder Plugin-Einstellungen
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Typische Fehlerquellen
1. **404-Avatare**: Alte URLs von gelöschten Uploads
2. **Permission-Fehler**: Fehlende Rechte für Avatar-Ordner
3. **Plugin-Konflikte**: Andere Avatar-Plugins interferieren
4. **CORS-Issues**: External Avatar-Services blockiert

## Migration

### Von altem System
Das neue System ist vollständig rückwärtskompatibel:

```php
// Alt (funktioniert weiterhin)
$avatar = get_avatar($email, 96);

// Neu (mit Verbesserungen)
$avatar = PSource_Chat_Avatar::get_avatar($user_id, 96, true);
```

### Bestehende Installationen
- Keine Datenbank-Migration erforderlich
- Automatische Erkennung von CP Community
- Graduelle Verbesserung bei Chat-Updates

## Anpassungen

### Custom Platzhalter
```php
// Eigenen Platzhalter setzen
PSource_Chat_Avatar::set_placeholder('https://example.com/custom-avatar.png');

// Oder als Data URI
$svg_data = 'data:image/svg+xml;base64,' . base64_encode($svg_content);
PSource_Chat_Avatar::set_placeholder($svg_data);
```

### Hooks für Entwickler
```php
// Avatar-URL filtern
add_filter('psource_chat_avatar_url', function($url, $user_id, $size) {
    // Custom Logic
    return $url;
}, 10, 3);

// Platzhalter-URL filtern  
add_filter('psource_chat_avatar_placeholder', function($url, $size) {
    // Custom Placeholder
    return $url;
}, 10, 2);
```

## Testing

### Manueller Test
1. User ohne Avatar erstellen
2. Chat-Nachricht senden
3. Platzhalter sollte erscheinen
4. CP Community Avatar hochladen
5. Chat aktualisieren - neuer Avatar sollte erscheinen

### Automated Testing
```javascript
// Browser-Console
psource_chat.init_avatar_fallbacks();
// Alle defekten Avatare sollten ersetzt werden
```

## Support

Bei Problemen:
1. Browser-Console auf JavaScript-Fehler prüfen
2. WordPress Debug-Log aktivieren
3. CP Community Avatar-Funktionen testen
4. Network-Tab auf 404-Requests prüfen

Das Avatar-System ist darauf ausgelegt, graceful degradation zu bieten - auch bei Fehlern sollte immer ein Platzhalter angezeigt werden.
