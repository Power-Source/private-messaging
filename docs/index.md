---
layout: psource-theme
title: "PS Pretty Plugins"
---

<h2 align="center" style="color:#38c2bb;">PS Pretty Plugins</h2>

<div class="menu">
  <a href="https://github.com/Power-Source/ps-pretty-plugins/releases" style="color:#38c2bb;">Download</a>
  <a href="https://power-source.github.io/ps-update-manager/" style="color:#38c2bb;">PSOURCE MANAGER</a>
</div>


Verleihe Deinen Plugin-Seiten in Multisite-Netzwerken das Aussehen eines App Stores mit ausgewählten Bildern, Kategorien und einer erstaunlichen Suche.

## Mit Pretty Plugins kannst Du Plugins in Kategorien gruppieren, ihnen auffällige Funktionsbilder geben und sie in einem benutzerfreundlichen Raster anzeigen.

Mache das Finden und Installieren von Plugins zum Kinderspiel. PS-Pretty Plugins ist ein unverzichtbares Tool für jeden Netzwerkbesitzer. Es bietet eine zentrale Steuerung und Konfiguration der Plugins-Seite jeder Webseite. Kombiniere es mit [PS Bloghosting](https://power-source.github.io/ps-bloghosting/), um einen fantastischen kostenpflichtigen Plugin-Shop zu erstellen.

Große Symbole und ein vertrautes Rasterlayout sorgen für ein optisch weitaus ansprechenderes Layout im Vergleich zum standardmäßigen Basiseintrag.

![Screenshot der Plugins-Seite mit dem Rasterlayout](assets/images/prettyplugins-ss12.png)

  Große Symbole und ein Rasterlayout machen das Entdecken von Plugins zum Kinderspiel

  Plugins können in mehrere Kategorien gruppiert werden, die vom Netzwerkadministrator definiert werden, was es Webseitenbesitzern erleichtert, neue Funktionen zu entdecken und zu installieren.

![Screenshot der Plugin-Kategorien](assets/images/prettyplugins-ss2.png)

  Gruppiere Plugins in Kategorien, um sie leichter finden zu können

  Eine umfassende Einstellungsseite ermöglicht es einem Netzwerkbesitzer, alle Aspekte der Plugins-Seite zu verwalten, einschließlich Titel, Untertitel, Bilder und die Sichtbarkeit von Beschreibungen. Die Einstellungen können sogar exportiert und importiert werden!

![Screenshot der Einstellungsseite](assets/images/prettyplugins-ss11.png)

  Konfiguriere alle Aspekte des Verhaltens und Aussehens der Plugins-Seite

  Für jedes Plugin wird eine genauere Steuerung bereitgestellt. Als Netzwerkadministrator kannst Du den Plugin-Namen, den Link und die Beschreibung überschreiben – Du kannst das Plugin beliebig vielen vorhandenen Kategorien zuweisen oder neue erstellen – und Du kannst ein vorgestelltes Bild laden.

![Screenshot der Plugin-Details-Bearbeitungsfunktion](assets/images/prettyplugins-ss41.png)

  Überschreibe alle Plugin-Attribute

  PS Pretty Plugins gibt Dir eine beispiellose Kontrolle über das Aussehen und Verhalten der Plugins-Seite und macht es für Webseitenbesitzer einfacher denn je, ihre Plugins selbst zu verwalten und neue Funktionen zu finden und zu ihrer Webseite hinzuzufügen.

## Verwendung

### Für den Anfang

Wenn Du neu bei Word/ClassicPress bist, Beachte dass dieses Plugin nur für Multisite-Installationen konzipiert ist und nicht auf einzelnen Webseiten funktioniert.

### Konfigurieren der Einstellungen

Nach der Installation und Aktivierung siehst Du in Deinem Menü „Netzwerkeinstellungen“ einen neuen Menüpunkt: PS Pretty Plugins. Klicke jetzt auf den Menüpunkt PS Pretty Plugins.

![pretty-plugins-1000-menu](assets/images/pretty-plugins-1000-menu.png)

  Die Einstellungen sind recht einfach. Aber lasse uns sie trotzdem durchgehen, ja? 

![1\. Setup-Modus aktivieren/deaktivieren. 2\. Wähle Dein bevorzugtes Design. 3\. Wähle das Ziel des Plugin-Links aus. 4\. Wähle Deine Screenshot-Einstellungen aus. 5\. Plugin-Beschreibungen ein-/ausblenden. 6\. Passe die Beschriftungen an.](assets/images/pretty-plugins-1000-settings.png)

  1\. Setup-Modus aktivieren/deaktivieren.
2\. Wähle Dein bevorzugtes Design.
3\. Wähle das Ziel des Plugin-Links aus.
4\. Wähle Deine Screenshot-Einstellungen aus.
5\. Plugin-Beschreibungen ein-/ausblenden.
6\. Passe die Beschriftungen an.

  1\. Wahrscheinlich möchtest Du _Setup-Modus aktivieren_ auf „WAHR“ gesetzt lassen, während Du die Dinge konfigurierst.

* Wenn der Setup-Modus aktiviert ist, kannst Du beim Aufrufen der Hauptseite nur eine Vorschau anzeigen. Die Unterseiten sind davon nicht betroffen.
* Sobald Du fertig und mit Deiner Konfiguration zufrieden bist, setze sie auf „FFALSCH“, um Deine neue Pretty Plugins-Seite auf allen Unterseiten in Deinem Netzwerk zu aktivieren.

2\. _Theme für Plugin-Seite auswählen_ ist genau das: Du kannst das Theme auswählen, das zur Anzeige der verfügbaren Plugins auf allen Unterseiten verwendet wird.

* Derzeit sind in diesem Plugin folgende Themes enthalten: QuickSand / 3Eight / Material.
* Um Dein eigenes benutzerdefiniertes Theme zu erstellen, kopiere einfach das QuickSand-Theme in /pretty-plugins/themes/ und benenne es um. Anschließend kannst Du das Layout und das Stylesheet bearbeiten und Dein benutzerdefiniertes Design in Deinen Ordner /wp-content/uploads/prettyplugins/themes/ hochladen. Es steht dann zur Auswahl zur Verfügung.

3\. Um auszuwählen, wohin der Plugin-Link verweist, hast Du vier Möglichkeiten:

* _Plugin-Original-URL_ verweist auf die URL, die der Plugin-Autor in das Plugin eingefügt hat.
* _Benutzerdefinierte Plugin-URL_ verweist auf die URLs, die Du für jedes Plugin festgelegt hast (wir werden weiter unten darauf eingehen).
* _Info URL des Plugins oder, falls keine Info URL vorhanden ist, Original-URL_ bietet Dir das Beste aus beidem: Wenn Du keine Info URL für ein Plugin eingibst, wird als Link standardmäßig die URL verwendet, die der Autor des Plugins eingefügt hat.
* _Disable_ deaktiviert effektiv alle Plugin-Links.

4\. Hier wählen wir aus, welche Screenshots für Plugins verwendet und angezeigt werden sollen.

* Wenn „WAHR" eingestellt ist, verwendet _Ersten Screenshot automatisch laden_ automatisch Screenshots aus dem Plugin-Root-Verzeichnis. Dadurch wird Pretty Plugins angewiesen, im Hauptverzeichnis des Plugins nach Bildern zu suchen - zuerst _screenshot-1.png_, dann _logo.png_ und zuletzt _logo.jpg_. Wenn keine dieser Dateien vorhanden ist, wird für dieses Plugin nichts angezeigt.
* Wenn auf WAHR gesetzt, weist _Screenshot automatisch mit korrektem Namen laden_ Pretty Plugins an, in Deinem Ordner /wp-content/upload/prettyplugins/screenshots/ nach Dateien zu suchen, die _Du_ dort mit den richtigen Namen hochgeladen hast.
     * Wenn sich das Plugin beispielsweise unter _wp-content/plugins/akismet/akismet.php_ befindet, sollte der Screenshot-Name _akismet-akismet.png_ lauten. Beachte, dass mit dieser Methode nur PNG-Bilder funktionieren.

5\. Du kannst _Beschreibungen minimieren_ auf WAHR setzen, um nur Screenshots auf Unterseiten-Plugin-Seiten anzuzeigen. Dadurch wird die Anzeige eines Links aktiviert, auf den Deine Benutzer klicken können, um die Beschreibung umzuschalten, wenn sie weitere Informationen über das Plugin wünschen. 6\. Mit den Einstellungen „Beschriftungen für Plugin-Seite verwalten“ kannst Du anpassen, wie Plugins den Benutzern Deiner Unterseiten angezeigt werden.

* _Plugin-Seitentitel_ ändert den Namen des Menüelements und den Seitenanfangstitel auf der Plugin-Seite. Möglicherweise bevorzugst Du beispielsweise das Wort „Addons“.
* _Plugin-Seitenbeschreibung_ ist der benutzerdefinierte beschreibende Text, der direkt unter dem Titel der Plugin-Seite angezeigt wird.
* _Benutzerdefiniertes Link-Label_ ist das, worauf Benutzer klicken, um die Plugin-URL zu besuchen, die Du oben unter _WWähle wohin Plugin Links zeigen_ festgelegt hast. Wenn Du dies auf „Deaktiviert“ eingestellt hast, wird dies ausgeblendet.

### Zusätzliche Tools

Es werden einige zusätzliche Tools bereitgestellt, mit denen Du Plugin-Daten und -Einstellungen verwalten kannst.

![1\. Datei config.xml exportieren/importieren. 2\. Alle Daten löschen.](assets/images/pretty-plugins-1000-tools.png)

  1\. Datei config.xml exportieren/importieren.
2\. Alle Daten löschen.

  1\. Du kannst eine config.xml-Datei _exportieren_, um Deine Einstellungen auf Deinem Computer zu sichern, und sie dann in Deinen Ordner /wp-content/uploads/prettyplugins/ _importieren_.
 
  2\. Die _Reset_-Funktion ist praktisch, wenn Du von vorne beginnen möchtest. :) :)

### Plugin-Details bearbeiten

Schauen wir uns nun an, wie Du anpassen kannst, wie einzelne Plugins in der Plugin-Liste auf allen Webseiten angezeigt werden. Gehe in Deinem Netzwerkadministrator zu Plugins > Installierte Plugins und klicke auf den Link Details bearbeiten für jedes Plugin, das Du bearbeiten möchtest.

![pretty-plugins-1000-edit](assets/images/pretty-plugins-1000-edit.png)

  Dadurch wird ein vertrauter Bereich geöffnet, der dem Schnellbearbeitungsbereich beim Bearbeiten von Beiträgen ähnelt. Schauen wir uns nun den ersten Teil genauer an.

![pretty-plugins-1000-edit-details](assets/images/pretty-plugins-1000-edit-details.png)

  Gib einen benutzerdefinierten _Namen_ ein, um den Anzeigenamen des Plugins zu ersetzen. Wenn nichts eingegeben wird, wird wie immer der tatsächliche Name des Plugins angezeigt. Du kannst eine _Info URL_ eingeben, um die vom Plugin-Autor angegebene URL zu ersetzen. Beachte, dass diese URL nur verwendet wird, wenn Du oben unter _Wähle wohin Plugin Links zeigen_ eine der benutzerdefinierten URL-Anzeigeoptionen ausgewählt hast. Du kannst auch eine benutzerdefinierte _Bild-URL_ hochladen oder darauf verlinken. Dadurch wird das Bild festgelegt, das für dieses Plugin angezeigt wird.

* Zum Hochladen kannst Du ein Bild aus Deiner Medienbibliothek auswählen oder den Namen der Datei eingibst, die Du in /wp-content/uploads/prettyplugins/screenshots/ hochgeladen hast.
* Wenn Du lieber auf ein anderswo gehostetes Bild verlinken möchtest, gib einfach die URL ein.
* Die empfohlenen Bildabmessungen betragen 600 x 450 Pixel.

Schauen wir uns nun den anderen Teil an.

![pretty-plugins-1000-edit-details-2](assets/images/pretty-plugins-1000-edit-details-2.png)

  Um _Kategorien_ hinzuzufügen, klicke auf den Link _Neue Kategorie_, gib Deinen Kategorienamen ein und klicke auf _Hinzufügen_. Anschließend kannst Du Deine Kategorie aus der Liste auswählen.

* Du kannst für jedes Plugin mehrere Kategorien auswählen, wenn Du möchtest.
* Alle Kategorien, die nicht von Plugins verwendet werden, werden automatisch gelöscht.

Du kannst eine benutzerdefinierte _Beschreibung_ eingeben, die die vom Plugin-Autor hinzugefügte Beschreibung ersetzt. Lasse das Feld leer, um stattdessen diese Beschreibung zu verwenden. Wenn Du mit der Bearbeitung fertig bist und auf die Schaltfläche „Aktualisieren“ geklickt hast, werden Deine Anpassungen direkt in der Plugin-Liste in Deinem Netzwerkadministrator angezeigt.

![pretty-plugins-1000-edit-details-3](assets/images/pretty-plugins-1000-edit-details-3.png)

### Erfahrung als Webseiten-Administrator

Sobald alles konfiguriert ist und Du „Setup-Modus aktivieren“ in den allgemeinen Einstellungen auf „Falsch“ setzt, sehen Deine Webseiten-Administratoren etwas, das diesem Bild sehr ähnlich ist.

![pretty-plugins-1000-site-view](assets/images/pretty-plugins-1000-site-view.png)

  Aktive Plugins werden mit einem grünen Band im Bild angezeigt. In unserem Beispiel haben wir die Beschreibungen so eingestellt, dass sie nicht angezeigt werden, sodass Webseiten-Administratoren sie anzeigen können, indem sie auf den Detaillink klicken. Wenn Du lieber Deine eigenen Bilder für die Bänder verwenden möchtest, ersetze einfach die Standardbilder in /pretty-plugins/themes/quick-sand/images/. Wenn Du Dein eigenes Design erstellt hast, ersetze natürlich stattdessen die Designs im Ordner /images/. Oben (und im Plugins-Menü) befinden sich die Filteroptionen. Das sind alle Kategorien, die wir für unsere Plugins eingerichtet haben. Wenn Du auf einen dieser Links klickst, werden nur Plugins angezeigt, die dieser Kategorie zugeordnet sind.

### PS Bloghosting-Integration

Oh ja, das haben wir! Alle Plugins, die Du den Ebenen [PS Bloghosting](https://power-source.github.io/ps-bloghosting//) im Premium-Plugin-Modul hinzugefügt hast, werden auf allen „Pro“-Webseiten mit einem Menüband in der Ecke des Plugin-Bildes angezeigt. Bewege Deinen Mauszeiger über ein beliebiges Plugin, das Du im Premium-Plugin-Modul für PS Bloghosting-Stufen festgelegt hast, und Du erhältst die Meldung „Upgrade auf [Stufe]“. Wenn die Webseite nicht auf die PS Bloghosting-Ebene aktualisiert wurde und der Webseiten-Administrator auf eines dieser Plugins klickt, wird er zu Deiner PS Bloghosting-Upgrade-Seite weitergeleitet. 
Wie cool ist das? Möglicherweise hast Du im obigen Screenshot auch bemerkt, dass wir Pretty Plugins-Kategorien erstellt haben, die unsere PS Bloghosting-Stufen widerspiegeln: Free, Premium und Super. Das macht es für Deine Webseiten-Administratoren einfach zu einfach, auf einen Blick die Vorteile eines Upgrades ihrer Webseite auf eine kostenpflichtige PS Bloghosting-Stufe in Deinem Netzwerk zu erkennen. Gern geschehen. :) Wir hoffen, dass Du und Deine Benutzer Pretty Plugins genießen und das Beste daraus machen!