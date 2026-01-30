# Installation und Einrichtung

## Installation

Legen Sie den Plugin-Ordner (`eledia_coursesearch/`) in das Moodle-Verzeichnis `blocks/` und starten Sie den Installationsprozess.  
(Gehen Sie zur Seite **Website-Administration** oder starten Sie die Installation über die CLI)

## Einrichtung

### Block verfügbar machen

Da es sich bei dem Plugin um einen *Block* handelt, wird empfohlen, ihn nur im **Dashboard** anzuzeigen.  

Wenn Sie den Block an anderen Stellen benötigen (z. B. auf der Startseite), muss Ihr Theme dies unterstützen.  

So machen Sie den Block für alle Nutzer im Dashboard sichtbar:

- Gehen Sie zu **Website-Administration** **→** **Darstellung** **→** **Standard-Dashboard-Seite**  
- Schalten Sie den **Bearbeitungsmodus** ein  
- Klicken Sie auf **Block hinzufügen**  
- Fügen Sie das Plugin hinzu  
- Schalten Sie den **Bearbeitungsmodus** wieder aus  
- Klicken Sie auf **Dashboard für alle Nutzer zurücksetzen**  

Jetzt ist die Kurssuche für alle Nutzer verfügbar.

### Benutzerdefinierte Felder hinzufügen

Das Plugin zeigt nur benutzerdefinierte Felder an, die für **Jeden** sichtbar sind.  

- Gehen Sie zu **Website-Administration** **→** **Kurse**  
- Im Abschnitt **Standardeinstellungen** gehen Sie zu **Benutzerdefinierte Kursfelder**  

<img src="../assets/adminsettings_de.png" alt="Website-Administration" width="70%">

- Wenn keine Kategorie vorhanden ist, klicken Sie auf **Neue Kategorie hinzufügen**  

![Menü für benutzerdefinierte Felder](../assets/admin_customfields_de.png)

- Im Abschnitt **Allgemein** klicken Sie auf **Neues benutzerdefiniertes Feld hinzufügen** und wählen Sie einen Feldtyp  

![Neues benutzerdefiniertes Feld hinzufügen](../assets/admin_customfieldsdd_de.png)

- Fügen Sie **Name**, **Kurzname** und **Beschreibung** hinzu  
  - Die Beschreibung wird dem Nutzer im Plugin angezeigt, Formatierungen werden unterstützt.  

<img src="../assets/create_customfield_de.png" alt="Details zum benutzerdefinierten Feld hinzufügen" width="60%">

- **Übersetzung:** Das Plugin unterstützt deutsche und englische Übersetzungen für das Feld **Name**:  
  
  - Syntax: `Deutscher Name;English name`  
  - Wenn die Benutzersprache nicht Deutsch ist (jegliche Form von Deutsch), wird der englische Name angezeigt.

- Im Abschnitt **Allgemeine Einstellungen für benutzerdefinierte Kursfelder** setzen Sie **Sichtbar für** auf **Jeden**  

<img src="../assets/customfield_visibility_de.png" alt="Sichtbarkeit auf Jeden setzen" width="50%">

- Verwenden Sie das benutzerdefinierte Feld in mindestens einem Kurs, der für alle Nutzer sichtbar ist:  
  - Gehen Sie zu den Kurseinstellungen. Im Abschnitt **Zusätzliche Felder** finden Sie das benutzerdefinierte Feld.  
  - Treffen Sie eine Auswahl.  

Die Reihenfolge der benutzerdefinierten Felder im Plugin entspricht der Reihenfolge in den Einstellungen.  

Um die Reihenfolge zu ändern, ziehen Sie die Felder an die gewünschte Position.  

Nicht verwendete benutzerdefinierte Felder oder Felder ohne Sichtbarkeit **Jeden** werden im Plugin nicht angezeigt.

## Einstellungen

Die meisten Einstellungen sollten nicht geändert werden.  
Im Folgenden finden Sie eine Liste der verfügbaren Einstellungen und deren Status.

### Darstellung

#### Kategorien anzeigen

Status: funktionsfähig  
Zeigt Kategorien in der Kursliste oder auf den Kurs-Infokacheln an.  

#### Verfügbare Layouts (Kontrollkästchen)

Status: **NICHT ÄNDERN**  
Wenn geändert, funktioniert das Frontend des Plugins nicht mehr korrekt.  

- Die Schaltfläche zum Layoutwechsel verschwindet.  
- Wählen Sie beide Optionen aus, dann ist alles wie erwartet.  

### Verfügbare Filter

#### Alle (einschließlich aus Ansicht entfernt)

Status: nicht funktionsfähig  
Einige Teile des Codes erwarten diese Option, sie hat jedoch keine funktionale Auswirkung.

#### Position der ausgewählten Optionen

Status: funktionsfähig  
Legt fest, ob die ausgewählten Optionen oben oder unten im Dropdown angezeigt werden.
Standard ist **Aus**.

#### Alle

Status: funktionsfähig  
Wenn deaktiviert, ist die Option **Alle** im Fortschritts-Dropdown für Kurse nicht verfügbar.  
Es gibt keinen erkennbaren Grund, sie zu deaktivieren.

#### In Bearbeitung

Status: funktionsfähig  
Wenn deaktiviert, ist die Option **In Bearbeitung** im Fortschritts-Dropdown für Kurse nicht verfügbar.  
Es gibt keinen erkennbaren Grund, sie zu deaktivieren.

#### Vergangen

Status: funktionsfähig  
Wenn deaktiviert, ist die Option **Vergangen** im Fortschritts-Dropdown für Kurse nicht verfügbar.  
Es gibt keinen erkennbaren Grund, sie zu deaktivieren.

#### Zukünftig

Status: funktionsfähig  
Wenn deaktiviert, ist die Option **Zukünftig** im Fortschritts-Dropdown für Kurse nicht verfügbar.  
Es gibt keinen erkennbaren Grund, sie zu deaktivieren.

#### Benutzerdefiniertes Feld

Status: nicht funktionsfähig  
Wenn angeklickt, erscheint ein Dropdown unter dem Kontrollkästchen. Diese Option wird von Teilen des Codes erwartet, hat aber keine Wirkung mehr.

#### Favorisiert

Status: nicht funktionsfähig  
Diese Option wird von Teilen des Codes erwartet, hat aber keine Wirkung mehr.

#### Aus Ansicht entfernt

Status: nicht funktionsfähig  
Diese Option wird von Teilen des Codes erwartet, hat aber keine Wirkung mehr.
