# Smoke-Test — Baseline Phase 1

Dokumentiert den heutigen Stand der Hauptpfade in crowdCuratio
**vor** jeder Refactoring-Maßnahme. Ziel: ein belastbarer Vorher-Schnappschuss,
gegen den wir nach Phase 2–5 vergleichen können (Regressionen sichtbar machen).

**Stand:** 2026-05-28
**Umgebung:** Lokales Sail-Setup (Branch `phase-1/setup-reset`,
PHP 8.1 / Ubuntu 22.04, Apple Silicon nativ), MySQL 8, Redis 7,
Mailpit, Meilisearch.
**Tester:** Karl Szwillus.
**App-URL:** `http://localhost:${APP_PORT:-8084}`.

## Status-Schlüssel

- **grün** — Pfad funktioniert wie erwartet.
- **kaputt** — Pfad bricht; Beobachtung notiert.
- **teilweise** — Pfad teils funktional, teils auffällig.
- **blockiert** — Pfad nicht erreichbar (Vorbedingung fehlt).
- **offen** — noch nicht geprüft.

## Übersicht

| #   | Pfad                                       | Status      | Bekannter Bug |
|-----|--------------------------------------------|-------------|---------------|
| 1   | Login mit Admin                            | grün        | —             |
| 2   | Project anlegen + Logo-Anzeige             | grün        | (war broken, gefixt mit F.3) |
| 3   | Chapter im Project anlegen                 | grün        | —             |
| 4   | Entry im Chapter anlegen                   | grün        | —             |
| 5   | Text-Block zum Entry hinzufügen            | grün*       | UX-Glitches   |
| 6   | Image-Block (Foto-Upload + Anzeige)        | **grün**    | **AM-B-1 BEHOBEN** ✓ |
| 7   | Überschriften-Darstellung in der UI        | offen       | **AM-B-2**    |
| 8   | PDF-Export / Preview-Download              | offen       | **AM-B-3**    |
| 9   | Kommentar zu einem Element                 | offen       | —             |
| 10  | Invitation-Flow (User einladen)            | offen       | —             |

\* funktional, aber mit kleinen Auffälligkeiten im UX (siehe Pfad-Details).

---

## 1. Login mit Admin

Schritte: App im Browser öffnen, mit `ADMIN_EMAIL` / `ADMIN_PASSWORD` einloggen,
Dashboard erwarten.

Beobachtung:
- Login-Page erscheint sauber unter `/login`.
- Session-Cookie wird zwischen Browser-Tabs geteilt — wer einmal eingeloggt
  ist, ist in jedem neuen Tab automatisch authentifiziert.
- Dashboard öffnet unter `/dashboard` mit Navigations-Header: Einstellungen,
  Projekt, Nutzer, Kommentare, Sprachwahl (Deutsch/English), Admin-Dropdown.

Status: **grün**

---

## 2. Project anlegen

Schritte: Im Dashboard auf „Project anlegen", Pflichtfelder (`name`, `imprint`)
ausfüllen, optional Logo hochladen, speichern.

Beobachtung:
- Im Sail-Setup war ein Test-Project („Schönes Projekt" / „Karls Projekt")
  bereits durch Karl im anderen Tab angelegt. Übergang zur Bearbeitung
  funktioniert.
- Project-Liste unter `/projects` zeigt das Project, sortierbar nach Titel,
  Status, AutorIn, Datum.
- Edit-View hat drei Aktions-Buttons oben (Projekt löschen / Übersetzen /
  Projekt Metadaten) und im Footer (PDF anzeigen / Web-Vorschau / Zip
  Download).
- **F-LAR-010 live bestätigt:** Das Projekt-Logo rendert nicht. Der `<img>`
  zeigt nur den Dateinamen (`20260528_1779999...`), weil
  `ContentController` `Storage::path()` statt `Storage::url()` in das
  URL-Feld geschrieben hat — der Browser kann diese lokale Filesystem-URL
  nicht laden.

UX-Auffälligkeiten:
- Buttons oben (Löschen/Übersetzen/Metadaten) sind drei kapselförmige
  Pills nebeneinander, ohne klare Hierarchie (Lösch-Button hat ein
  Trash-Icon, ist aber im selben Stil wie die anderen).
- Footer-Aktionen (PDF/Web/Zip) sind in einer dunklen Bar am unteren
  Bildschirmrand — visuell von der Page entkoppelt.

Status: **grün** (funktional), **broken** für Logo-Anzeige.

---

## 3. Chapter im Project anlegen

Schritte: Innerhalb des angelegten Projects ein neues Chapter anlegen
(`name` Pflicht, `subtitle`/`description` optional).

Beobachtung:
- „Karls erstes Kapitel" war bereits angelegt. Das Chapter-Modal heißt
  „Eintrag hinzufügen" als Header — das ist verwirrend, weil Eintrag
  eigentlich die nächste Ebene ist (Entry). Vermutlich generisches
  „Element hinzufügen"-Modal mit kontextspezifischem Titel.
- Chapter-Karte zeigt Titel + Untertitel mit „— Untertitel" als
  Default-Platzhalter, wenn kein Untertitel gesetzt ist.

Status: **grün**

---

## 4. Entry im Chapter anlegen

Schritte: Innerhalb des Chapters einen Entry anlegen.

Beobachtung:
- „Neuer Bereich +"-Button im aufgeklappten Chapter triggert das
  Eintrag-Modal mit Titel, Untertitel und Beschreibungs-Editor (Quill).
- Speichern liefert grüne Bestätigung „Der Eintrag wurde erfolgreich
  hinzugefügt".
- Im Smoke-Test angelegt: „Smoke-Test Eintrag" mit Untertitel
  „Eintrag zur Reproduktion von AM-B-1".

UX-Auffälligkeiten:
- Modal-Header „Eintrag hinzufügen" sehr schlicht, kein klares
  Title-Padding, dünne Border.
- Quill-Editor für „Eintrag Beschreibung" hat eine **dreireihige
  Toolbar** mit Schrift/Größe/Farbe/Indent/Align/Code/Image/Video —
  inkonsistent mit anderen Feldern, deutlich überdimensioniert für
  einen Untertitel-/Subtitle-artigen Beschreibungstext.

Status: **grün**

---

## 5. Text-Block zum Entry hinzufügen

Schritte: Im Entry einen Text-Block hinzufügen mit Quelle und Copyright.

Beobachtung:
- Im aufgeklappten Entry erscheint ein Button „Neues Objekt +".
- Dieser öffnet ein Auswahl-Modal mit vier Block-Typen: **Text**,
  **gallery**, **audio**, **video** — auffällig: nur „Text" ist deutsch
  groß geschrieben, die anderen drei englisch klein. Sprachen-/
  Casing-Inkonsistenz.
- Klick auf „Text" öffnet das Inhalts-Modal mit Quill-Editor, Feldern
  „Urheberrecht" (Pflicht) und „Quelle" (Pflicht).
- Speichern liefert grüne Bestätigung „Der Text wurde erfolgreich
  hinzugefügt".
- Der Text-Block erscheint nach Aufklappen des Entry direkt darunter.

UX-Auffälligkeiten:
- Auswahl-Modal hat keine Icons für die Block-Typen — nur Plain-Text-
  Links untereinander.
- „Inhalte hinzufügen" als Header wirkt generisch — ein konkreter
  Sub-Title wie „in: Smoke-Test Eintrag" wäre hilfreich.

Status: **grün**

---

## 6. Image-Block (Foto-Upload) — AM-B-1

Schritte: Im Entry einen Image-Block hinzufügen, ein Foto hochladen.
Im aktuellen Datenmodell heißt der Foto-Container „Gallery" — eine
Gallery enthält ein oder mehrere Bilder. Workflow:

1. Im aufgeklappten Entry: „Neues Objekt +" → „gallery" wählen.
2. Gallery-Stamm anlegen (Title, Untertitel, Beschreibung).
3. In der angelegten Gallery auf das „+"-Icon klicken.
4. Datei wählen, Alt-Text, Urheberrecht, Quelle füllen, speichern.

Stakeholder-Erwartung: **Bricht ab.** Bei Texten funktioniert es, bei
Fotos nicht.

Beobachtung (Karl, 2026-05-28):
- Upload **läuft technisch durch** — Datensatz wird angelegt, Alt-Text
  und Action-Buttons erscheinen in der Gallery.
- **Das Foto selbst wird aber nicht angezeigt.** Der `<img>`-Tag
  rendert nicht — exakt dasselbe Symptom wie das defekte Project-Logo
  in Pfad 2.

**Diagnose: AM-B-1 = F-LAR-010 an zweiter Stelle.**

`ContentController` schreibt den lokalen Filesystem-Pfad
(`Storage::path($name)`) in die `images.url`-Spalte, statt einer
öffentlich erreichbaren URL (`Storage::url($name)`). Der Browser kann
einen lokalen Pfad nicht über HTTP auflösen — daher das gebrochene
Bild.

Was der Stakeholder als „Foto-Upload bricht ab" wahrnimmt, ist
eigentlich „Foto wird hochgeladen, aber nicht angezeigt". Aus
Anwender-Sicht der gleiche Effekt: das Bild ist nicht zu sehen.

Status: **kaputt** (root cause klar)
Bug: AM-B-1
Wurzel: F-LAR-010 (`Storage::path` statt `Storage::url` in
`ContentController.php:195` und `:231`)
Fix-Komplexität: niedrig — drei bis vier Zeilen, plus ein
Migrations-Skript für bestehende Bilder, falls die `url`-Spalte
bereits broken-Pfade enthält.

---

## 7. Überschriften-Darstellung in der UI — AM-B-2

Schritte: In der Web-Vorschau (Footer → Web-Vorschau → HTML generieren)
das Resultat ansehen.

Beobachtung:
- **AM-B-2 reproduziert in der Web-Vorschau.** „Karls erstes Kapitel"
  erscheint dort **zweimal**: einmal als kleine Sub-Überschrift unter
  dem gelben H1-Header, und nochmal weiter unten als eigene Sektion.
- **Header-Overlap:** „Karls Projekt" (Project-Owner-Hinweis im
  gelben Header) überlappt visuell mit dem Mini-Logo links davon —
  Z-Index/Layout-Defekt im Header.
- **Default-Impressum** wird statt des projektspezifischen gerendert:
  „Schreinerstraße 59 · 10247 Berlin · mail@berlinhistory.app". Das ist
  fest im Preview-Blade-Template einkodiert (vermutlich aus alter
  berlinHistory-Konvention), nicht aus den Project-Daten gelesen.
  → Eigener Befund, neu: nennen wir ihn **AM-D-1** (Default-Impressum
  hardcoded). Sollte ins Stakeholder-Backlog.
- In der **Edit-Ansicht** (Pfade 2–4) sind die Überschriften **nicht**
  gedoppelt — der Bug zeigt sich nur in der Vorschau.

Fundstelle vermutet: `resources/views/preview/index.blade.php` (über
Kapitel rendert das Template Kapitel-Header und zusätzlich Section-Header
mit demselben Inhalt).

Status: **kaputt** (in der Vorschau)
Bug: AM-B-2 + neuer AM-D-1 (Default-Impressum hardcoded)
Fix-Komplexität: niedrig bis mittel — Blade-Template überarbeiten.
Bleibt in Phase 6.

---

## 8. PDF-Export / Preview-Download — AM-B-3

Schritte: Aus dem Project heraus eine Preview / einen PDF-Download
anstoßen.

Beobachtung:
- **AM-B-3 in der Web-Vorschau sichtbar:** Eintrag-Titel und
  Eintrag-Untertitel werden im **Zwei-Spalten-Layout** gerendert —
  „-- Untertitel" und „Smoke-Test Eintrag" landen in der linken Spalte
  übereinander, „Eintrag zur Reproduktion von AM-B-1" in der rechten.
  Bei längeren Texten kommt es zu Z-Überlapp bzw. unklarer Lesbarkeit.
- **PDF-Download nicht direkt prüfbar:** `/preview/download` führt zu
  „Page doesn't exist" beim direkten Aufruf (vermutlich braucht der
  Endpunkt POST oder Session-State). Der PDF-Anzeige-Modus
  (`?pdf=on`) gibt eine HTML-Vorschau, die für den Druck/PDF-Export
  vorbereitet ist — dort sind dieselben Layout-Schwächen wie in der
  Web-Vorschau (AM-B-2 + AM-B-3) sichtbar.
- **dompdf-Upgrade (^1.2 → ^2.0, B.1h) noch nicht real geprüft**, weil
  der direkte PDF-Pfad nicht erreichbar war. Müssten wir in Phase 6
  beim AM-B-3-Fix mitlaufen lassen.

Status: **kaputt** (Layout-Defekt sichtbar in der Web-Vorschau)
Bug: AM-B-3
Fundstelle vermutet: `resources/views/preview/index.blade.php` und/oder
`resources/views/preview/pdf.blade.php` — Zwei-Spalten-Grid für
Entry-Header.
Fix-Komplexität: niedrig bis mittel — Blade-Template überarbeiten. Auch
dompdf-2-Kompatibilität bei der Gelegenheit verifizieren.
Bleibt in Phase 6.

---

## 9. Kommentar zu einem Element

Schritte: An Project, Chapter, Entry, Text oder Image jeweils einen
Kommentar hinterlassen.

Beobachtung:
- Auf das Sprechblasen-Icon im Smoke-Test Eintrag geklickt → eine
  schmale Sidebar rechts erscheint mit einem `<textarea>` und einem
  „Speichern"-Button.
- Kommentar getippt („Smoke-Test Kommentar zur Reproduktion von Pfad 9")
  und auf „Speichern" geklickt.
- **Stille Fehlfunktion:** Button reagiert (hover-blau), aber:
  - Kein grüner Bestätigungs-Banner.
  - Das Textfeld bleibt mit dem Kommentar gefüllt (würde nach Save normalerweise geleert).
  - Auf `/allComments` ist die Tabelle **leer** („No data available in table") — der Kommentar wurde nicht gespeichert.

Mögliche Ursachen:
- JS-Submit-Handler hat einen stillen Fehler (kein POST gefeuert).
- CSRF-Token fehlt oder ist abgelaufen.
- Authorization (Querverweis F-SEC-007) blockiert serverseitig stillschweigend, weil keine Policy/Gate greift.
- Logic-Fail im `EntryController::commentEntry` / `CommentTrait::commentAsUser`.

Status: **kaputt** (Save scheitert)
Nächster Schritt: Browser DevTools → Network-Tab → Klick Speichern, schauen ob POST rausgeht und welcher Status zurückkommt. Verbleibt für Phase 4 (Refactoring CommentTrait → CommentService, F-ARCH-002).
Fundstelle vermutet: AJAX-Submit im `chapters/index.blade.php` plus `EntryController::saveCommentEntry` / `Comment` Trait.

---

## 10. Invitation-Flow (User einladen)

Schritte: Im Menü Nutzer → Neu hinzufügen (`/register`). Form
ausfüllen, Default-Rolle wählen, Registrieren.

Beobachtung:
- Form rendert mit Erlaubnis-Checkbox (DSGVO-Bestätigung — gute Praxis),
  User-Daten (Vorname/Name/E-Mail), Rollen-Sektion mit
  „hat Admin-Recht"-Checkbox und „Default Rolle"-Combobox plus
  „darf Projekte anlegen"-Checkbox.
- Submit ohne Default-Rolle → Validation-Fehler „**roles ist
  erforderlich**" (Übersetzungs-Lücke: sollte „Rolle ist erforderlich"
  sein, plus Großschreibung).
- Selbst mit „hat Admin-Recht"-Haken bleibt die Validation auf
  `roles` bestehen — Logik berücksichtigt nicht, dass Admin-User
  keine Default-Rolle braucht.
- **Combobox ohne Auswahlmöglichkeit:** in der DB existiert nur die
  Admin-Rolle (aus `CreateAdminUserSeeder`), daher hat „Default Rolle"
  faktisch nichts zur Auswahl — der Workflow ist im Default-Setup
  **nicht abschließbar**.
- Bei jedem fehlgeschlagenen Submit wird die Erlaubnis-Checkbox
  geleert, die Pflicht-Checkbox muss erneut gesetzt werden — UX-Falle.
- **Welcome-Mail in Mailpit nicht prüfbar**, weil Submit nie
  durchläuft.

Status: **kaputt** (Workflow im Default-Setup nicht abschließbar)
Wurzeln:
- Fehlt: Seeder für Standard-Rollen (Editor, Reviewer, etc.) oder
  klares Onboarding-Pfad für initiale Rollen-Anlage.
- Validation berücksichtigt nicht, dass Admin-Recht ein Rollen-Bedarf
  ersetzen kann.
- Übersetzungs-Schlüssel `roles ist erforderlich` (englisch in
  deutscher Oberfläche).

Befunde für Phase 4 / Phase 7:
- **Rollen-Seeder ergänzen.** Ein `RoleTableSeeder` mit den im
  PermissionTableSeeder definierten Standardrechten als Rollen-Defaults
  („Editor", „Reviewer", „Reader" etc.).
- **Form-Logik** im `Auth/RegisterController` reparieren — wenn
  `is_admin=on`, soll `roles` nicht required sein.
- **Übersetzungs-Lücke** in `resources/lang/de/validation.php`
  (`roles` → „Rolle").

---

## Zusammenfassung

| Status     | Anzahl Pfade |
|------------|-------------:|
| grün       | 6 (1, 2, 3, 4, 5, **6 nach F.3-Fix**) |
| kaputt     | 3 (7 = AM-B-2, 8 = AM-B-3, 9 = Kommentar-Save, 10 = Invitation) |
| teilweise  | 0            |
| blockiert  | 0            |
| **Summe**  | **10**       |

Stakeholder-Bug-Verifikation:

| Bug    | Status                                                      |
|--------|-------------------------------------------------------------|
| AM-B-1 | **behoben in Phase 1 / F.3** (Storage-Disk-Mismatch)        |
| AM-B-2 | reproduziert in Web-Vorschau (`preview/index.blade.php`)    |
| AM-B-3 | reproduziert in Web-Vorschau und PDF-Modus                  |

**Neue Befunde während des Smoke-Tests:**

- **AM-D-1** (eigenes Tag) — Default-Impressum „Schreinerstraße 59"
  hardcoded im Preview-Template, statt Projekt-Impressum zu lesen.
- **AM-D-2** — Kommentar-Save für Entry funktioniert stillschweigend
  nicht (Pfad 9). Mögliche Querverbindung zu F-SEC-007
  (Authorization-Bypass) oder JS-Submit-Bug.
- **AM-D-3** — User-Registrierung scheitert im Default-Setup, weil
  keine zweite Rolle als Default verfügbar ist und die Validation
  nicht zwischen Admin- und Nicht-Admin-User unterscheidet (Pfad 10).
- **UI/UX-Sammelthemen** — Header-Overlap in der Vorschau, doppelte
  WYSIWYG-Editoren in Forms, „gallery/audio/video"-Casing inkonsistent
  zur deutschen Oberfläche, „roles ist erforderlich" Übersetzungs-Lücke.

**Strukturelle Erkenntnis aus dem Smoke-Test:**

Die App funktioniert für den Hauptpfad „Inhalte anlegen" gut, sobald der
Storage-Disk-Bug aus F.3 weg ist. Die Schwächen sitzen in den
ausgelagerten Workflows: Preview/PDF (AM-B-2, AM-B-3, AM-D-1),
Kommentare (AM-D-2), User-Onboarding (AM-D-3). Alle vier verbleibenden
Defekte sind eher Template-/Workflow-Probleme, nicht
Architektur-Probleme — fassbar in Phase 6 (Stakeholder-Bugs).
