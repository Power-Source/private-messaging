# Test-Anleitung - Modernisierung Phase 1

## 🧪 Was wurde geändert?

### Phase 1 - Teil 1: jQuery-Methoden
- Alle `.click()` → `.on('click')`
- Alle `.load()` → `$.get()`

### Phase 1 - Teil 2: Modal-Bibliothek
- `jquery.leanModal.min.js` → `modern-modal.js`

---

## ✅ Wie testen?

### 1. JavaScript-Console prüfen

**Vor der Modernisierung:**
```
⚠️ JQMIGRATE: jQuery.fn.click() event shorthand is deprecated
⚠️ JQMIGRATE: jQuery.trim is deprecated
⚠️ JQMIGRATE: jQuery.isArray is deprecated
```

**Nach der Modernisierung (eigener Code):**
```
✅ Keine Warnungen mehr für .click() im eigenen Code
⚠️ leanModal is deprecated. Please migrate to modernModal. (Nur Info)
⚠️ Weiterhin Warnungen von Drittanbieter-Bibliotheken (selectize, sceditor, etc.)
```

---

### 2. Funktionen testen

Öffnen Sie die Browser-Konsole (F12) und testen Sie:

#### ✅ Modal-Funktionalität

**Wo testen:**
1. **Backend-Bereich:**
   - WordPress Admin → PS PM-System → Nachrichten
   - Klicken Sie auf "Antworten" oder "Neue Nachricht"
   - Modal sollte sich öffnen ✓

2. **Frontend - Inbox:**
   - Gehen Sie zur Inbox-Seite (Shortcode: `[mm_inbox]`)
   - Klicken Sie auf eine Nachricht zum Antworten
   - Modal sollte sich öffnen ✓

3. **Frontend - Message Me Button:**
   - Seite mit `[mm_message_me]` Shortcode
   - Klicken Sie auf "Nachricht senden"
   - Modal sollte sich öffnen ✓

**Was prüfen:**
- [ ] Modal öffnet sich korrekt
- [ ] Overlay (dunkler Hintergrund) wird angezeigt
- [ ] Modal ist zentriert
- [ ] Klick auf Overlay schließt Modal
- [ ] ESC-Taste schließt Modal
- [ ] Formulare im Modal funktionieren
- [ ] Keine JavaScript-Fehler in der Console

#### ✅ Click-Event-Funktionalität

**Backend - Einstellungen:**
1. WordPress Admin → PS PM-System → Einstellungen
2. Testen Sie:
   - [ ] Plugin-Aktivierungs-Buttons funktionieren
   - [ ] "Seite erstellen"-Buttons funktionieren
   - [ ] Keine JavaScript-Fehler

**Backend - Upgrade:**
1. WordPress Admin → PS PM-System → Upgrade
2. Testen Sie:
   - [ ] "Fix Table"-Buttons funktionieren
   - [ ] AJAX-Requests werden gesendet
   - [ ] Keine JavaScript-Fehler

**Backend - Nachrichten:**
1. WordPress Admin → PS PM-System → Nachrichten
2. Testen Sie:
   - [ ] "Konversation sperren"-Button funktioniert
   - [ ] Status wird korrekt aktualisiert
   - [ ] Keine JavaScript-Fehler

**Addons - Words Filter:**
1. PS PM-System → Einstellungen → Words Filter
2. Testen Sie:
   - [ ] "Wort hinzufügen"-Formular funktioniert
   - [ ] Regex-Option schaltet erweiterte Felder an/aus
   - [ ] "Test Regex"-Button funktioniert
   - [ ] Tabelle wird nach Speichern aktualisiert
   - [ ] "Löschen"-Buttons funktionieren

**Addons - Broadcast:**
1. Neue Nachricht erstellen
2. Testen Sie:
   - [ ] "Broadcast an alle"-Checkbox funktioniert
   - [ ] Empfänger-Feld wird aktiviert/deaktiviert
   - [ ] Keine JavaScript-Fehler

---

### 3. Browser-Kompatibilität testen

Testen Sie in mindestens:
- [ ] Chrome/Edge (neueste Version)
- [ ] Firefox (neueste Version)
- [ ] Safari (wenn verfügbar)
- [ ] Mobile Browser (Chrome Mobile, Safari iOS)

**Mobile-spezifisch testen:**
- [ ] Modal öffnet sich korrekt
- [ ] Modal ist responsive
- [ ] Touch-Gesten funktionieren
- [ ] Keyboard öffnet sich bei Formular-Fokus

---

### 4. Performance prüfen

**Vor/Nach-Vergleich:**

1. Öffnen Sie Chrome DevTools → Network Tab
2. Laden Sie eine Seite mit dem Plugin
3. Prüfen Sie:

**Vorher (mit jquery.leanModal.min.js):**
- jquery.leanModal.min.js: ~3.5 KB

**Nachher (mit modern-modal.js):**
- modern-modal.js: ~4 KB (nicht minifiziert)
- modern-modal.min.js: ~2 KB (minifiziert - erstellen!)

---

### 5. Regressionstests

Prüfen Sie, dass **nichts kaputt** gegangen ist:

#### Frontend:
- [ ] Inbox-Seite lädt korrekt
- [ ] Nachrichten können gelesen werden
- [ ] Nachrichten können gesendet werden
- [ ] Dateianhänge funktionieren
- [ ] Benutzer-Suche (Selectize) funktioniert
- [ ] WYSIWYG-Editor funktioniert

#### Backend:
- [ ] Nachrichten-Übersicht lädt
- [ ] Nachrichten können verwaltet werden
- [ ] Einstellungen können gespeichert werden
- [ ] Addons funktionieren

---

## 🐛 Bekannte Probleme / Warnungen

### Noch vorhandene jQuery-Migrate-Warnungen:

Diese Warnungen kommen von **Drittanbieter-Bibliotheken** und werden in Phase 2 behoben:

1. **Selectize.js:**
   - `jQuery.trim is deprecated`
   - `jQuery.isArray is deprecated`
   - `jQuery.type is deprecated`
   - Benötigt jQuery-UI sortable

2. **Perfect Scrollbar (alte Version):**
   - `.bind()` und `.unbind()` deprecated

3. **SCEditor:**
   - Diverse deprecated Methoden

4. **Noty:**
   - `jQuery.isFunction` deprecated
   - `jQuery.type` deprecated

**→ Diese werden in Phase 2 durch Bibliotheken-Updates behoben**

---

## 📊 Erwartete Ergebnisse

### Console-Ausgabe:

```javascript
// ✅ Gut - Info-Meldung (kann ignoriert werden bis Migration abgeschlossen):
"leanModal is deprecated. Please migrate to modernModal."

// ✅ Gut - Keine dieser Fehler:
✗ "Uncaught TypeError"
✗ "Uncaught ReferenceError"
✗ "jQuery is not defined"

// ⚠️ Akzeptabel (Drittanbieter, wird in Phase 2 behoben):
"JQMIGRATE: jQuery.trim is deprecated" (von selectize.js)
"JQMIGRATE: jQuery.isArray is deprecated" (von selectize.js)
```

### Funktionalität:

- ✅ **Alle Modals** öffnen/schließen korrekt
- ✅ **Alle Click-Events** funktionieren
- ✅ **Alle AJAX-Requests** funktionieren
- ✅ **Formulare** können abgesendet werden
- ✅ **Keine Funktionalität** ging verloren

---

## 🔧 Troubleshooting

### Problem: Modal öffnet sich nicht

**Checken:**
1. JavaScript-Console auf Fehler prüfen
2. Ist `modern-modal.js` geladen? (Network Tab)
3. Sind jQuery-Abhängigkeiten vorhanden?

**Fehlersuche:**
```javascript
// In Browser-Console ausführen:
console.log(typeof jQuery); // sollte "function" sein
console.log(typeof ModernModal); // sollte "function" sein
console.log(jQuery.fn.leanModal); // sollte "function" sein
console.log(jQuery.fn.modernModal); // sollte "function" sein
```

**Fix:**
- Cache leeren (Strg+F5)
- WordPress-Cache leeren
- Plugin neu aktivieren

### Problem: "jQuery is not defined"

**Ursache:** jQuery wird nicht geladen oder zu spät geladen

**Fix:**
1. Prüfen Sie, ob jQuery in WordPress registriert ist
2. Prüfen Sie die Script-Reihenfolge in `messaging.php`
3. Stellen Sie sicher, dass `modern-modal.js` NACH jQuery geladen wird

### Problem: Alte leanModal-Fehler bleiben

**Ursache:** Browser-Cache oder alte Datei wird noch geladen

**Fix:**
```bash
# 1. Hard Refresh im Browser
Strg + F5 (Windows/Linux)
Cmd + Shift + R (Mac)

# 2. WordPress-Cache leeren
# Falls Caching-Plugin aktiv:
- WP Super Cache: Cache löschen
- W3 Total Cache: Cache leeren

# 3. Überprüfen ob richtige Datei geladen wird:
# DevTools → Network → modern-modal.js → Preview Tab
# Sollte "class ModernModal" enthalten
```

---

## ✅ Test-Checkliste (Schnellübersicht)

Kopieren und abhaken beim Testen:

```markdown
## Frontend Tests:
- [ ] Inbox-Seite lädt
- [ ] Nachricht öffnen/schließen funktioniert
- [ ] Nachricht senden funktioniert
- [ ] Modal öffnet sich
- [ ] ESC schließt Modal
- [ ] Overlay-Click schließt Modal

## Backend Tests:
- [ ] Admin-Bereich lädt
- [ ] Nachrichten-Verwaltung funktioniert
- [ ] Einstellungen speichern funktioniert
- [ ] Plugin-Buttons funktionieren
- [ ] Upgrade-Buttons funktionieren
- [ ] Words-Filter funktioniert
- [ ] Broadcast-Funktion funktioniert

## Browser-Tests:
- [ ] Chrome getestet
- [ ] Firefox getestet
- [ ] Safari getestet (optional)
- [ ] Mobile getestet

## Console-Check:
- [ ] Keine Uncaught Errors
- [ ] Nur erwartete Warnungen (s.o.)
- [ ] leanModal-Deprecation-Warnung OK

## Performance:
- [ ] Seitenlade-Zeit unverändert/besser
- [ ] Modals öffnen schnell
- [ ] Keine Verzögerungen
```

---

## 📝 Bug-Report Template

Falls Sie Probleme finden:

```markdown
**Problem:**
[Beschreibung des Problems]

**Schritte zur Reproduktion:**
1. Gehe zu...
2. Klicke auf...
3. Erwartung vs. Realität

**Browser:**
- Browser: [Chrome/Firefox/Safari]
- Version: [z.B. Chrome 121]
- Betriebssystem: [Windows/Mac/Linux]

**Console-Fehler:**
```
[Kopie der Fehlermeldung aus der Console]
```

**Screenshots:**
[Falls hilfreich]

**Zusätzliche Infos:**
- PHP-Version: [z.B. 8.1]
- WordPress-Version: [z.B. 6.4]
- Plugin-Version: [z.B. 1.0.0]
```

---

**Test-Datum:** ____________________  
**Getestet von:** ____________________  
**Status:** ⬜ Bestanden ⬜ Fehlgeschlagen ⬜ Mit Anmerkungen
