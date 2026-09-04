# PS PM-System

[![Version](https://img.shields.io/badge/Version-1.0.6-2271b1?style=flat-square)](readme.txt)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-777bb4?style=flat-square&logo=php&logoColor=white)
![WordPress](https://img.shields.io/badge/WordPress-bis%207.1.0-21759b?style=flat-square&logo=wordpress&logoColor=white)
![ClassicPress](https://img.shields.io/badge/ClassicPress-2.7.2-03768e?style=flat-square)
[![Lizenz](https://img.shields.io/badge/Lizenz-GPL--2.0--or--later-2ea44f?style=flat-square)](https://www.gnu.org/licenses/gpl-2.0.html)

Private Nachrichten fuer ClassicPress. Das PS PM-System gibt Mitgliedern einen geschuetzten Posteingang, direkte Konversationen und private Dateifreigabe direkt im Frontend.

## Funktionsumfang

- Posteingang mit Suche, ungelesenen Nachrichten, gesendeten Nachrichten und Archiv
- Neue Nachrichten aus dem Posteingang, der Admin-Bar oder per "Message Me"-Schaltflaeche schreiben
- Konversationen archivieren oder loeschen
- Lesebestaetigungen je Benutzer aktivieren oder deaktivieren
- Private Anhaenge wie Bilder, Videos und Dokumente sicher innerhalb einer Unterhaltung teilen
- Speicherlimit pro Benutzer fuer Nachrichtenanhaenge verwalten
- Benutzerliste zum Blockieren eingehender Nachrichten pflegen
- Responsive Frontend-Oberflaeche fuer Desktop und Mobilgeraete

## Voraussetzungen

- ClassicPress 2.6 oder neuer
- PHP mit den fuer ClassicPress erforderlichen Erweiterungen
- Ein angemeldeter Benutzer fuer Posteingang, Versand und Dateianhaenge

## Installation

1. Den Ordner `private-messaging` nach `wp-content/plugins/` kopieren und das Plugin in ClassicPress aktivieren.
2. Im Backend unter **Messaging > Settings** eine Posteingangsseite auswaehlen oder erstellen lassen.
3. Die Seite mit dem Posteingang aufrufen und eine Testnachricht zwischen zwei Benutzerkonten senden.
4. Optional Add-ons in der Add-on-Verwaltung von Messaging aktivieren und ihre Einstellungen pruefen.

Der Posteingang kann auch mit dem Shortcode `[message_inbox]` auf einer Seite ausgegeben werden.

## Bedienung

### Nachrichten

Im Posteingang kann eine Unterhaltung geoeffnet, beantwortet, archiviert oder geloescht werden. Die linke Liste zeigt Absender, Betreff, Anhang und Sendezeit; die Unterhaltung selbst erhaelt den groessten Teil der verfuegbaren Flaeche.

### Einstellungen

Benutzer verwalten Lesebestaetigungen direkt im Reiter **Einstellungen**. Bei aktivierter Blockliste wird pro Zeile ein Benutzername eingetragen. Diese Benutzer koennen dem aktuellen Konto keine Nachricht senden.

### Anhaenge

Anhaenge sind an die jeweilige Unterhaltung gebunden. Nur Teilnehmer einer Unterhaltung koennen auf deren Dateien zugreifen. Rollen und Speicherlimits werden in den Messaging-Einstellungen verwaltet.

## Add-ons

| Add-on | Zweck |
| --- | --- |
| bbPress Integration | Fuegt in bbPress-Themen eine Schaltflaeche fuer Direktnachrichten hinzu. |
| Blacklist | Blockiert Nachrichten von eingetragenen Benutzern. |
| Sendeberechtigungen | Beschraenkt den Nachrichtenversand auf festgelegte ClassicPress-Rollen. |
| Benachrichtigungen | Zeigt Benachrichtigungen fuer neue Nachrichten. |
| WYSIWYG | Stellt einen Editor fuer formatierte Nachrichten bereit. |
| Broadcast | Versendet Nachrichten an mehrere bzw. alle Benutzer. |
| Gruppengespraech (Beta) | Ermoeglicht Unterhaltungen mit mehreren Teilnehmern. |
| Bad Words Filter | Filtert konfigurierte Begriffe aus Nachrichteninhalten. |

## PSOURCE-Integration

### PS Community

Wenn PS Community mit dem Medienmodul aktiv ist, erweitert das PM-System die Anhangsansicht:

- Bilder und Videos lassen sich in der Community-Medienansicht oeffnen.
- Eigene, kompatible Anhaenge koennen aus einer Unterhaltung in eine eigene PS-Community-Galerie uebernommen werden.
- Die Galerieauswahl beschraenkt sich auf Galerien des angemeldeten Benutzers; Systemgalerien und fremde Galerien stehen nicht zur Auswahl.

Die Einbindung ist optional. Ohne PS Community bleibt der private Versand und Download von Anhaengen voll funktionsfaehig.

PS Community kann das PM-System ausserdem als Profil-Reiter integrieren. Diese Profilintegration wird von PS Community konfiguriert und setzt voraus, dass beide Plugins aktiv sind.

### ClassicPress-Avatare und PS Community

Das PM-System verwendet die ClassicPress-Funktion `get_avatar()`. Ist der Avatar-Filter von PS Community aktiv, erscheinen Community-Profilbilder deshalb automatisch in Nachrichtenlisten und Unterhaltungen. Gibt es kein verfuegbares Avatarbild, zeigt das PM-System Initialen als Fallback.

### bbPress

Mit aktivem bbPress-Add-on koennen Mitglieder aus Themen heraus eine private Nachricht an andere Teilnehmer starten. Die Berechtigungen des PM-Systems gelten weiterhin.

## Datenschutz und Sicherheit

- Nachrichten und Anhaenge sind fuer die Teilnehmer der jeweiligen Unterhaltung bestimmt.
- E-Mail-Benachrichtigungen und Lesebestaetigungen lassen sich durch Benutzer und Administratoren steuern.
- Die Datenschutzerklaerung des Plugins beschreibt die gespeicherten Nachrichten-, Status- und Anhangsdaten genauer.
- Fuer produktive Seiten sollten Rollen, Anhangsberechtigungen, Speicherlimits und die Datenschutzerklaerung vor der Freischaltung geprueft werden.

## Entwicklung

Die Styles werden aus `assets/main.css` erzeugt:

```bash
npm install
npm run build
```

Der Build aktualisiert `assets/main.min.css`. Lokale Node-Abhaengigkeiten liegen in `node_modules/` und werden nicht versioniert.

## Lizenz

GPL-2.0-or-later. Siehe die Plugin-Kopfzeile in `messaging.php` und die Datei `readme.txt`.