# Private Messaging Plugin - Modernisierungsplan

**Erstellt am:** 23. Februar 2026  
**Ziel:** jQuery-UI entfernen, veraltete jQuery-Methoden aktualisieren, PHP 8+ Kompatibilität sicherstellen

---

## 📋 Zusammenfassung der Probleme

### 1. jQuery-Migrate Warnungen (aktuelle Fehler)

Die folgenden Warnungen werden derzeit angezeigt:

- ✅ **jQuery.fn.click()** ist deprecated → Verwendet in: [jquery.leanModal.min.js](assets/jquery.leanModal.min.js#L12-L17)
- ✅ **jQuery.trim** ist deprecated → Verwendet in: [selectize.js](assets/selectivejs/js/selectize.js#L3106)
- ✅ **jQuery.isArray** ist deprecated → Verwendet in: [selectize.js](assets/selectivejs/js/selectize.js#L805)

### 2. jQuery-UI Abhängigkeiten

**Status:** Das Plugin deaktiviert bewusst jQuery-UI (siehe [messaging.php](messaging.php#L318-L333))

```php
// Aktive Deaktivierung von jQuery-UI Komponenten
wp_dequeue_script('jquery-ui-sortable');
wp_dequeue_script('jquery-ui-draggable');
// ... etc.
```

**Probleme:**
- Selectize.js benötigt `jquery-ui-sortable` für das "drag_drop" Plugin
- Aktuell keine direkte jQuery-UI Nutzung im eigenen Code

### 3. Veraltete JavaScript-Bibliotheken

| Bibliothek | Status | Probleme | Verwendung |
|------------|--------|----------|------------|
| **jquery.leanModal.min.js** | ⚠️ Veraltet | `.click()` deprecated | Modal-Funktionalität |
| **perfect-scrollbar.min.js** | ⚠️ Veraltet | `.bind()`, `.unbind()` deprecated | Scrollbar-Styling |
| **selectize.js** | ⚠️ Veraltet | `jQuery.trim`, `jQuery.isArray`, `jQuery.type`, benötigt jQuery-UI | Mehrfachauswahl |
| **sceditor** | ⚠️ Veraltet | `.bind()`, `.unbind()`, `.each()`, `.split()` | WYSIWYG-Editor |
| **Noty** | ⚠️ Veraltet | `jQuery.isArray`, `jQuery.isFunction`, `jQuery.type` | Benachrichtigungen |

### 4. Eigener Code mit veralteten jQuery-Methoden

**Betroffene Dateien:**

1. [app/addons/mm-broadcast-messages.php](app/addons/mm-broadcast-messages.php#L39) - `.click()`
2. [app/addons/words-filter.php](app/addons/words-filter.php#L314-L355) - `.click()`, `.load()`
3. [app/views/backend/setting/general.php](app/views/backend/setting/general.php#L167-L188) - `.click()`
4. [app/views/backend/upgrade.php](app/views/backend/upgrade.php#L132) - `.click()`
5. [app/views/backend/main.php](app/views/backend/main.php#L27) - `.click()`
6. [app/addons/wysiwyg.php](app/addons/wysiwyg.php#L80) - `.each()`

### 5. PHP 8+ Kompatibilität

✅ **Status: Gut**
- Keine veralteten PHP-Funktionen gefunden (`create_function`, `each()`, `split()`, etc.)
- Type Hints bereits teilweise implementiert ([template-loader-trait.php](app/traits/template-loader-trait.php))
- Keine `$HTTP_RAW_POST_DATA` Verwendung

**Verbesserungspotenzial:**
- Mehr Type Hints in allen PHP-Klassen
- Strikte Typisierung aktivieren (`declare(strict_types=1)`)
- Property Type Hints für PHP 7.4+

---

## 🎯 Modernisierungsplan

### Phase 1: Sofortige Fixes (Kritisch) 🔴

**Priorität:** Hoch  
**Zeitaufwand:** 2-3 Tage

#### 1.1 jQuery.leanModal ersetzen

**Problem:** Veraltete Modal-Bibliothek mit deprecated `.click()`

**Lösung:** Migration zu **Bootstrap Modal** oder **native HTML5 Dialog**

```javascript
// Aktuell (jquery.leanModal):
$('#trigger').leanModal({ top: 100, overlay: 0.5 });

// Neue Lösung (HTML5 Dialog):
document.querySelector('#trigger').addEventListener('click', () => {
    document.querySelector('#modal').showModal();
});
```

**Schritte:**
1. ✅ Alle Verwendungen von `.leanModal()` identifizieren
2. ✅ HTML-Struktur zu `<dialog>` Tags konvertieren
3. ✅ CSS für Browser-Kompatibilität anpassen
4. ✅ Polyfill für ältere Browser hinzufügen (falls erforderlich)

#### 1.2 jQuery `.click()` durch `.on('click')` ersetzen

**Betroffene Dateien:**
- [mm-broadcast-messages.php](app/addons/mm-broadcast-messages.php#L39)
- [words-filter.php](app/addons/words-filter.php#L320-L328)
- [setting/general.php](app/views/backend/setting/general.php#L167-L188)
- [backend/upgrade.php](app/views/backend/upgrade.php#L132)
- [backend/main.php](app/views/backend/main.php#L27)

**Beispiel-Migration:**
```javascript
// Vorher:
$('.mm-plugin').click(function (e) {
    // Code...
});

// Nachher:
$('.mm-plugin').on('click', function (e) {
    // Code...
});

// Noch besser (Event-Delegation):
$(document).on('click', '.mm-plugin', function (e) {
    // Code...
});
```

#### 1.3 jQuery `.load()` ersetzen

**Betroffene Datei:** [words-filter.php](app/addons/words-filter.php#L314)

```javascript
// Vorher:
$('#badword-list-table').load("<?php echo $_SERVER['REQUEST_URI'] ?> #badword-list-table");

// Nachher (mit $.get):
$.get("<?php echo $_SERVER['REQUEST_URI'] ?>", function(data) {
    $('#badword-list-table').html($(data).find('#badword-list-table').html());
});

// Oder mit Fetch API:
fetch("<?php echo $_SERVER['REQUEST_URI'] ?>")
    .then(response => response.text())
    .then(html => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        document.querySelector('#badword-list-table').innerHTML = 
            doc.querySelector('#badword-list-table').innerHTML;
    });
```

---

### Phase 2: Bibliotheken-Upgrades (Wichtig) 🟡

**Priorität:** Mittel-Hoch  
**Zeitaufwand:** 3-5 Tage

#### 2.1 Selectize.js aktualisieren oder ersetzen

**Problem:** 
- Verwendet deprecated jQuery-Methoden
- Benötigt jQuery-UI sortable (das Plugin deaktiviert jQuery-UI)

**Empfohlene Lösungen:**

**Option A: Tom-Select** (Empfohlen ⭐)
- Fork von Selectize.js ohne jQuery-Abhängigkeit
- Vanilla JavaScript
- Aktiv maintained
- Keine jQuery-UI Abhängigkeit

```html
<!-- Installation -->
<link href="https://cdn.jsdelivr.net/npm/tom-select@2/dist/css/tom-select.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2/dist/js/tom-select.complete.min.js"></script>

<!-- Verwendung -->
<script>
new TomSelect('#select-element', {
    plugins: ['remove_button'],
    // Konfiguration...
});
</script>
```

**Option B: Choices.js**
- Vanilla JavaScript
- Keine jQuery-Abhängigkeit
- Sehr leichtgewichtig

**Migrationsschritte:**
1. Dependencies prüfen und neue Bibliothek lokal hinzufügen
2. Alle Selectize-Initialisierungen identifizieren
3. Schrittweise migrieren und testen
4. Alte Selectize-Dateien entfernen

#### 2.2 Perfect Scrollbar aktualisieren

**Problem:** Alte Version mit `.bind()`, `.unbind()`

**Lösung:** Migration zu **Perfect Scrollbar 1.x** oder **OverlayScrollbars**

**Perfect Scrollbar 1.5.5 (neueste Version):**
```javascript
// Alte Version:
$('.scroll-container').perfectScrollbar();

// Neue Version (Vanilla JS):
const ps = new PerfectScrollbar('.scroll-container', {
    wheelSpeed: 2,
    wheelPropagation: true
});
```

**Alternative: OverlayScrollbars**
- Moderne Alternative
- Bessere Performance
- Mehr Features

```javascript
import OverlayScrollbars from 'overlayscrollbars';

OverlayScrollbars(document.querySelector('.scroll-container'), {
    scrollbars: {
        autoHide: 'move'
    }
});
```

#### 2.3 Noty Notification Library aktualisieren

**Problem:** Verwendet deprecated jQuery-Methoden

**Lösung:** Zu **Noty 3.x** oder **Toastify** migrieren

**Noty 3.2.0-beta.0:**
```javascript
// Installation via npm oder CDN
import Noty from 'noty';

new Noty({
    type: 'success',
    text: 'Nachricht gespeichert!',
    timeout: 3000
}).show();
```

**Alternative: Toastify-js** (Leichter, kein jQuery)
```javascript
import Toastify from 'toastify-js';

Toastify({
    text: "Nachricht gespeichert!",
    duration: 3000,
    gravity: "top",
    position: "right",
    backgroundColor: "linear-gradient(to right, #00b09b, #96c93d)"
}).showToast();
```

#### 2.4 SCEditor modernisieren

**Problem:** Alte Version mit vielen deprecated jQuery-Methoden

**Empfohlene Lösungen:**

**Option A: SCEditor aktualisieren**
- Neueste Version: 3.1.1
- Bessere Browser-Kompatibilität
- Weniger jQuery-Abhängigkeiten

**Option B: TinyMCE** (Falls bereits in wysiwyg.php verwendet)
- Scheint bereits teilweise im Code ([wysiwyg.php](app/addons/wysiwyg.php#L108))
- Moderne, aktiv gewartete Lösung
- Keine jQuery-Abhängigkeit

**Option C: Quill**
- Moderner WYSIWYG-Editor
- Sehr leichtgewichtig
- Keine jQuery-Abhängigkeit

---

### Phase 3: jQuery-Abhängigkeit reduzieren (Optional) 🟢

**Priorität:** Niedrig-Mittel  
**Zeitaufwand:** 5-10 Tage

#### 3.1 jQuery durch vanilla JavaScript ersetzen

**Ziel:** Moderne Browser-APIs verwenden statt jQuery

**Beispiele:**

```javascript
// 1. Selektoren
// jQuery:
$('.class-name')
// Vanilla:
document.querySelectorAll('.class-name')

// 2. Event-Listener
// jQuery:
$(element).on('click', handler)
// Vanilla:
element.addEventListener('click', handler)

// 3. AJAX
// jQuery:
$.ajax({ url: '/api/data', method: 'GET' })
// Vanilla (Fetch):
fetch('/api/data')
    .then(response => response.json())

// 4. DOM-Manipulation
// jQuery:
$(element).addClass('active')
// Vanilla:
element.classList.add('active')

// 5. Iteration
// jQuery:
$.each(array, function(index, item) { })
// Vanilla:
array.forEach((item, index) => { })
```

#### 3.2 Build-System einrichten

**Empfehlung:** Für bessere Code-Verwaltung

**Optionen:**
- **Vite** (Empfohlen für moderne Projekte)
- **Webpack** (Etabliert, aber komplexer)
- **Parcel** (Einfach, Zero-Config)

**Vorteile:**
- Module bundling
- Tree shaking (ungenutzten Code entfernen)
- Kleinere Dateigrößen
- TypeScript-Support (optional)

---

### Phase 4: PHP-Modernisierung 🔵

**Priorität:** Mittel  
**Zeitaufwand:** 3-4 Tage

#### 4.1 Type Hints konsequent einsetzen

**Beispiel-Migration:**

```php
// Vorher:
class MM_Message_Model {
    public function get_message($message_id) {
        // ...
    }
}

// Nachher:
class MM_Message_Model {
    public function get_message(int $message_id): ?array {
        // ...
    }
}
```

#### 4.2 Strict Types aktivieren

**In jeder PHP-Datei:**
```php
<?php
declare(strict_types=1);

// Rest des Codes...
```

#### 4.3 Property Type Hints hinzufügen (PHP 7.4+)

```php
// Vorher:
class MM_Conversation_Model {
    private $db;
    private $table_name;
}

// Nachher:
class MM_Conversation_Model {
    private wpdb $db;
    private string $table_name;
}
```

#### 4.4 Constructor Property Promotion (PHP 8.0+)

```php
// Vorher:
class MyClass {
    private string $property;
    
    public function __construct(string $property) {
        $this->property = $property;
    }
}

// Nachher (PHP 8.0+):
class MyClass {
    public function __construct(
        private string $property
    ) {}
}
```

---

## 📅 Zeitplan & Priorisierung

### Woche 1-2: Kritische Fixes
- [ ] jQuery `.click()` zu `.on('click')` migrieren
- [ ] jQuery `.load()` ersetzen
- [ ] jquery.leanModal zu HTML5 Dialog migrieren

### Woche 3-4: Bibliotheken-Updates
- [ ] Selectize.js zu Tom-Select migrieren
- [ ] Perfect Scrollbar aktualisieren
- [ ] Noty aktualisieren
- [ ] SCEditor evaluieren/aktualisieren

### Woche 5-6: Code-Optimierung
- [ ] PHP Type Hints hinzufügen
- [ ] Strict Types aktivieren
- [ ] Tests durchführen

### Woche 7-8: Optional
- [ ] Weitere jQuery durch Vanilla JS ersetzen
- [ ] Build-System einrichten
- [ ] Performance-Optimierung

---

## 🧪 Testing-Strategie

### 1. Browser-Kompatibilität testen
- ✅ Chrome/Edge (neueste 2 Versionen)
- ✅ Firefox (neueste 2 Versionen)
- ✅ Safari (neueste Version)
- ⚠️ IE11 (falls noch erforderlich - Polyfills nötig)

### 2. Funktionale Tests
- [ ] Modal-Funktionalität
- [ ] Formular-Interaktionen
- [ ] AJAX-Requests
- [ ] Benachrichtigungen
- [ ] WYSIWYG-Editor
- [ ] Multi-Select (Selectize-Ersatz)

### 3. PHP-Tests
- [ ] PHP 7.4 Kompatibilität
- [ ] PHP 8.0 Kompatibilität
- [ ] PHP 8.1 Kompatibilität
- [ ] PHP 8.2+ Kompatibilität

---

## 📦 Empfohlene Bibliotheken-Übersicht

| Aktuell | Problem | Ersatz | Begründung |
|---------|---------|--------|------------|
| jquery.leanModal | `.click()` deprecated | HTML5 `<dialog>` | Native Lösung, keine Abhängigkeiten |
| Selectize.js | jQuery-UI Abhängigkeit | **Tom-Select** | Vanilla JS, kein jQuery-UI |
| Perfect Scrollbar (alt) | `.bind()` deprecated | **Perfect Scrollbar 1.5.5** | Moderne Version |
| Noty (alt) | jQuery-Methoden deprecated | **Noty 3.x** oder **Toastify-js** | Aktualisierte Version |
| SCEditor | Viele deprecated Methoden | **TinyMCE** oder **Quill** | Modern, aktiv supported |

---

## 🎨 CSS-Modernisierung (Bonus)

### Flexbox/Grid statt Floats
```css
/* Vorher: */
.container {
    float: left;
    width: 50%;
}

/* Nachher: */
.container {
    display: grid;
    grid-template-columns: 1fr 1fr;
}
```

### CSS Custom Properties
```css
/* Definieren: */
:root {
    --primary-color: #007bff;
    --border-radius: 4px;
}

/* Verwenden: */
.button {
    background-color: var(--primary-color);
    border-radius: var(--border-radius);
}
```

---

## ✅ Checkliste

### Sofort (Kritisch)
- [ ] `.click()` → `.on('click')` in allen eigenen Dateien
- [ ] `.load()` → `$.get()` oder `fetch()`
- [ ] jquery.leanModal ersetzen

### Kurzfristig (1-2 Monate)
- [ ] Selectize.js migrieren
- [ ] Perfect Scrollbar aktualisieren
- [ ] Noty aktualisieren
- [ ] PHP Type Hints hinzufügen

### Mittelfristig (3-6 Monate)
- [ ] SCEditor evaluieren/ersetzen
- [ ] jQuery-Abhängigkeit reduzieren
- [ ] Build-System einrichten
- [ ] Umfassende Tests

### Langfristig (Optional)
- [ ] Vollständige Vanilla JS Migration
- [ ] TypeScript einführen
- [ ] Performance-Audit
- [ ] A11y (Accessibility) Audit

---

## 📚 Ressourcen & Links

### JavaScript
- [You Might Not Need jQuery](https://youmightnotneedjquery.com/)
- [Tom-Select Dokumentation](https://tom-select.js.org/)
- [HTML5 Dialog Element](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/dialog)
- [Fetch API](https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API)

### PHP
- [PHP 8 Migration Guide](https://www.php.net/manual/en/migration80.php)
- [PHP Type Declarations](https://www.php.net/manual/en/language.types.declarations.php)

### Testing
- [BrowserStack](https://www.browserstack.com/) - Cross-Browser Testing
- [PHPUnit](https://phpunit.de/) - PHP Testing Framework

---

## 💡 Hinweise

1. **Backward Compatibility:** Achten Sie darauf, dass ältere WordPress/ClassicPress-Versionen weiterhin unterstützt werden
2. **Incremental Migration:** Führen Sie Änderungen schrittweise durch, nicht alles auf einmal
3. **Feature Branches:** Nutzen Sie Git-Branches für jede größere Änderung
4. **Dokumentation:** Aktualisieren Sie die Dokumentation nach jeder Phase
5. **User Testing:** Lassen Sie Benutzer jede Phase testen, bevor Sie weitermachen

---

**Erstellt:** 23. Februar 2026  
**Zuletzt aktualisiert:** 23. Februar 2026  
**Status:** 📝 Entwurf
