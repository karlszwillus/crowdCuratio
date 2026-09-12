# Changelog

Alle nennenswerten Änderungen an crowdCuratio werden in dieser Datei dokumentiert.

Das Format orientiert sich an [Keep a Changelog 1.1.0](https://keepachangelog.com/de/1.1.0/),
die Versionierung folgt [Semantic Versioning 2.0.0](https://semver.org/lang/de/).

Sektionen je Release: `Hinzugefügt`, `Geändert`, `Veraltet`, `Entfernt`,
`Behoben`, `Sicherheit`.

## [Unreleased]

Strukturelles Refactoring-Release. Schwerpunkte: Service-Layer-Extraktion
aus den Fat Controllern (Project, Chapter, Entry, Content, Audiovisual)
auf Basis von DTOs, Konsolidierung des Permission-Modells auf Spatie
mit project-scoped Policies, Authorization-Härtung über alle
Content-Controller, sauber polymorpher Refactor der `media_content`-
Pivot-Tabelle und Anlage einer Architektur-Dokumentation für
Entwickler. Begleitet von einem vorgezogenen Coverage-Push (von
~27 % auf 55 %), einer kompletten Major-Upgrade-Welle (PHP 8.1 → 8.4,
Laravel 8 → 12, Spatie- und Test-Tooling auf jeweils aktuellem Major)
und mehreren Sicherheits-Sweeps zur Schließung von Authorization-
Bypässen, Privilege-Escalation-Pfaden und Audit-Befunden in
Drittabhängigkeiten.

**Phase-5a — Frontend-Modernisierung abgeschlossen.** Build-Stack auf
Vite + Tailwind 4 zurückgesetzt mit token-basierter Design-Schicht
(`tokens.css` als Source of Truth aus dem v3-Briefing). Livewire 4 als
Interaktivitäts-Schicht installiert, Komponenten-Bibliothek von sechs
Blade-Komponenten unter `<x-ui.*>` (Button, Icon-Button, Input, Toggle,
Card, Banner, plus `<x-ui.modal>` als zentrale Modal-Komponente). 16
Modal-Markups in 11 Views von Bootstrap-3-Struktur auf
`<x-ui.modal>` umgezogen. Bootstrap-3-JS-Plugins durchgehend abgelöst
durch schmale Vanilla-Manager (Modal, Tooltip, Typeahead, DataTable,
Sortable-via-SortableJS), jQuery-Shims halten die Bestands-Inits am
Leben. Die ursprüngliche Compat-Schicht wurde nach Reality-Check zu
permanenten Custom-Utilities mit Bootstrap-Klassennamen umetikettiert
(`compat-bootstrap.css` → `bootstrap-utilities.css`); eine 1:1-
Übersetzung der ~560 Klassen-Stellen hätte funktional nichts geändert.
Zweiter Mandant „Aktives Museum" als Theme-Switch über
`<html data-theme>` mit smoother 180-ms-Color-Transition (`prefers-
reduced-motion` respektiert) und Sonne-/Mond-Toggle im Navi-Header.
Coverage-Schwelle im CI auf 65 % gehoben und tatsächlich auf 77,5 %
gepusht (Polster für die Frontend-Wellen 5b–5f). Phase 5a hinterlässt
ein dokumentiertes Backlog aus drei Code-Hygiene-Items (leerer
`PermissionService`-Stub, `SettingController::store`-Imprint-Smell,
falsche `Comment::chapter()`-Definition) und fünf Theme-Vollausbau-
Themen (Logo-Tausch, Title-Tag, Mail-Templates, Card-Tokens, Tenant-
Detection — siehe `werkbank/TODO.md`).

**Phase-5b — Layout-Neuschnitt mit Sidebar-Struktur-Baum abgeschlossen.**
Editor bekommt einen App-Shell-Refactor: neue `<x-layout>`-Komponente
mit semantischen Landmarks (`<header>`, `<aside>`, `<main role="main">`,
`<footer>`), Tailwind-Grid statt Bootstrap-3-Cols, dunkles Chrome und
heller Content-Canvas. Linke Aside ist jetzt der dreistufige
Sidebar-Tree (Projekt → Kapitel → Abschnitt) als Livewire-Volt-
Komponente; Klick scrollt smooth zum Anker im Canvas. Oberhalb des
Canvas schwebt ein Breadcrumb, der per Hash-Watcher auf den
Tree-Klick reagiert (Deep-Links funktionieren mit). Die A11y-Pflöcke
für die nächsten Wellen sind gesetzt: Skip-Link „Zum Inhalt springen"
als erster Tab-Stop (WCAG 2.4.1), Strg+Pfeil-Reorder als Tastatur-
Alternative zum SortableJS-Drag (WCAG 2.5.7) mit zentraler ARIA-Live-
Region für Move-Announcements, Touch-Target-Mindestgröße 44×44 px
auf allen Icon-only-Buttons (WCAG 2.5.8), 15 Editor-Aktionsbuttons
von `<a href="">` auf semantisches `<button type="button">` migriert.
Bestands-Endpoint `chapter.drag` nimmt sowohl Maus- als auch
Tastatur-Reorders ohne Sicherheits-Refactor. Coverage hält bei 77,5 %.

**Nachtrag Phase-5b-Hotfix (2026-08-14).** Reviewer-Konsolidierung
nach dem 5b-Merge deckte einen funktionalen Bug im Tastatur-Reorder
auf: das POST-Payload-Encoding aus dem `fetch`-Handler war nicht
kompatibel zum bestehenden `chapter.drag`-Endpoint, die neue
Reihenfolge landete nicht in der Datenbank. Persona-Smoke hatte den
Bug nicht gefangen, weil er ohne Page-Reload lief. Parallel dazu
zeigte sich, dass der Shortcut `Strg+Pfeil` auf macOS system-weit
für Mission Control und Space-Switching reserviert ist — das
Feature war für macOS-Nutzer:innen von Anfang an nicht bedienbar.
Beides ist im Hotfix behoben: form-encoded Array-Notation im POST,
Shortcut auf `Alt+↑/↓` umgestellt (plattformübergreifend frei, wie
in VS Code). Zusätzlich vier defensive Härtungen (DOM-Rollback bei
Server-Fail, Rate-Limit auf `chapter.drag`, Autorisierung in der
Sidebar-Tree-Livewire-Komponente, Aktivmarkierung per
`aria-current`) und ein Refactor auf einen `ProjectTreeService` als
Single Source für Sidebar und Breadcrumb.

**Phase-5c — Inline-Editing für Content-Editor abgeschlossen.** Der
Editier-Flow verlässt die Modal-Welt: alle Feldeditoren des Content-
Trees (Kapitel, Abschnitte, Text-Blöcke, Galerien, Bilder,
Audiovisuals) laufen jetzt inline direkt am Content-Element. Basis
ist eine schlanke Volt-Komponente `<livewire:inline-editor>` mit drei
Render-Modi (Text-Input, Textarea, Select-Dropdown), die Update-
Autorisierung project-scoped über `Gate::authorize('update', $project)`
prüft, Validation gegen konfigurierbare Laravel-Rules laufen lässt und
via debouncing (blur oder 1,5-s-Timer) speichert. Fehler laden aria-
invalid/aria-describedby und ein Screenreader-freundliches Feedback.

Für Text-Content und alle Beschreibungs-Felder gibt es einen
`<livewire:rich-text-editor>` mit Quill-Bridge (Alpine-x-data), der
das gleiche Save-Gate nutzt und HTML als Rohtext ins Modell schreibt.
Der Audiovisual-Player rendert als eigene Volt-Komponente, die auf
das `saved`-Event des Inline-Editors hört und sich beim Wechsel von
audio↔video sofort neu lädt. Bei type=audio ersetzt ein Inline-Audio-
Uploader das Link-Textfeld, mit MIME-Whitelist und 20-MB-Limit wie
in der Server-Route.

Copyright und Quelle sind in einem `<details>`-Toggle unterhalb des
Content-Blocks kollabiert und lassen sich über einen neuen
`<livewire:source-picker>` mit Live-Autocomplete gegen die Source-
Tabelle editieren — inklusive „+ Neu anlegen: '…'"-Aktion für nicht
existierende Werte und case-insensitiver Duplikat-Erkennung. Damit
fällt der Bootstrap-3-Typeahead aus dem alten Modify-Modal ersatzlos.

Ein Auto-Save-Indikator im Header (Alpine-Store `saveStatus`) zeigt
„speichert…", „gespeichert" (grüner Puls, Auto-Fade) und
„nicht gespeichert" je nach Livewire-Event; Toast-Notifications
signalisieren Fehler global über einen weiteren Alpine-Store. Header
und Sidebar-Tree sind auf `sticky` gesetzt und der Tree aktualisiert
sich live über den `saved`-Event ohne Full-Reload. Focus-Trap in
Modals nutzt `@alpinejs/focus` und ist WCAG-2.4.11/2.1.2-konform.

Sechs Modify-Modals aus dem Bestand fallen weg (Kapitel, Abschnitt,
Galerie, Text-Content, Bild, Audiovisual); die zugehörigen jQuery-
Handler und Ajax-Roundtrips sind entfernt. Die fünf Add-Modals
(Kapitel/Abschnitt/Text/Bild/Audiovisual/Galerie hinzufügen) bleiben
in dieser Runde — deren Inline-Migration ist als Backlog-Feature
vermerkt und braucht Design-Vorlage.

**Phase-5-Design-Sprint — visuelle Angleichung an Designer-Handoff
v4 abgeschlossen.** Zwischen Phase 5c und Phase 5d wird die
Umsetzung optisch an den Handoff des Designers herangeführt: neun
Sub-Wellen, ein Branch, ein PR. Grundlage sind die 60
Design-Tokens aus `tokens.md`, die als Tailwind-4-`@theme`-Block
alle Chrome-, Neutral-, Semantik- und Typografie-Werte liefern.
IBM Plex Sans wird als projektweiter UI-Default gesetzt, IBM
Plex Mono für Caps-Labels; Font-Files kommen selbst gehostet über
`@fontsource`.

Icons wechseln vollständig auf Lucide über
`blade-ui-kit/blade-icons` plus `mallardduck/blade-lucide-icons`;
die neue semantische `<x-icon name="…">`-Komponente ersetzt 84
Bootstrap-Icons-Aufrufe und die Dependency `bootstrap-icons` fällt.

Die App-Shell besteht aus einer sticky 60-px-Rail links (Logo +
Bereichs-Icons + Utility-Zone mit Auto-Save-Punkt, Marken-Toggle,
Sprache, User-Menü), einem 280-px-Sidebar-Panel dahinter
(kontextabhängig: Projektliste zeigt globale Nav, Editor den
Struktur-Baum mit Kapitel-Nummerierung, Klapp-Chevrons und
aktiver Kante), und dem Canvas rechts. Die alte Top-Bar aus
Phase 5b entfällt.

Die Projektliste rendert als Card mit Filter-Chips
(Alle/Veröffentlicht/Entwurf/In-Review mit Zählern), Suchfeld
oben rechts und einer CSS-Grid-Tabelle mit Thumbnails, Status-
Badges, Autor-Avataren und rechtsbündigen Aktions-Icons. Der
Editor-Kopf ist eine sticky Chrome-Bar mit Brotkrumen (`Projekte
/ Karls Projekt / Kapitel N / Eintragsname`), Segmented-Control
„Bearbeiten · Übersetzen · Metadaten", rotem
„Veröffentlichen"-Primary und einem ⋮-Menü, das Export (PDF,
Web-Vorschau, ZIP-Download) und Projekt-Löschen aufnimmt.

Kapitel sind offene Zonen auf dem Canvas (kein Rahmen), Einträge
sind Karten mit Mono-Caps-Label „Eintrag · Kapitel N", und
Content-Blöcke sind Karten mit Typ-Tag als Pill (Text/Bild/
Galerie/Audio/Video/Zitat). Die neue `<x-ui.block-card>`-
Komponente trägt einen `save-slot`-Prop, über den ein Alpine-
Store pro Block einen „Gespeichert"-Chip in Grün oder eine
Fehler-Meldung in Rot rendert; der globale Punkt in der Rail
zeigt zusätzlich den letzten Save-Status projektweit.

Copyright und Quelle sitzen als Pflichtfelder mit Sternchen und
`aria-required` sichtbar am Fuß jeder Block-Card, nicht mehr im
Details-Toggle. Der Rich-Text-Editor rendert seine Quill-Toolbar
nur beim Fokus (unter 600 px Card-Breite fallen List/Link/Clean
in einen Overflow); der aktive Block bekommt zusätzlich den
2-px-`--brand-bar`-Rand aus dem Handoff-Screen 05a.

Leere Medien-Blöcke (Bild, Audio, Video, Galerie) rendern als
Streifenmuster-Platzhalter mit passendem Seitenverhältnis, Icon
und Hint-Text statt einer weißen Fläche. Add-Trigger wechseln auf
Ghost-Style mit dashed Border, damit sie nicht mit dem
Publish-Primary konkurrieren. Der Login-Screen ist komplett neu:
Split-Layout mit dunklem Marken-Panel links (Logo, Wortmarke,
Claim, „Eingesetzt von berlinHistory e.V. · Aktives Museum") und
Formular-Panel rechts (Duzen-Konvention, Sprach-Select,
„Angemeldet bleiben", roter Primary). Nach erfolgreichem Login
landen User direkt auf ihrer Projektliste.

Beide Marken (crowdCuratio und Aktives Museum) sind an
Projektliste, Editor und Login verifiziert; der Marken-Panel im
Login bleibt bewusst themeunabhängig dunkel — er ist Signature,
nicht Chrome. Coverage hält bei 77,9 % über die Design-Sprint-
Umbauten.

**Phase-5d — Rollen-bewusste UI abgeschlossen.** Die
Berechtigungswelt bekommt eine eigene Sicht: aus einer
dreifachen Modal-Kaskade in `projects/create` wird Screen 3B —
ein Split-Layout mit Mitarbeitenden-Sidebar links und Detail-
Panel rechts. Der Detail-Bereich zeigt oben vier Rollen-Vorlage-
Buttons (Reader/Reviewer/Editor/Owner) und darunter eine
Toggle-Karten-Matrix. Die Matrix wurde gegenüber Handoff v4
bewusst um zwei Toggles erweitert (`comment` und `invite`),
damit „Reviewer:in ohne Kommentar-Recht" und „Co-Owner mit
Einladungs-Recht" ausdrückbar werden; die Design-Abweichung ist
für die nächste Review-Runde in
`.werkbank/BRIEFINGS/redesign/permission-matrix-6-toggles.md`
dokumentiert. Owner-Rechte sind an `project.user_id` gekoppelt
und werden per `@disabledIf` als sichtbar-aber-gesperrt
gerendert.

Als Fundament dafür ist ein neues Locked-Pattern entstanden:
`<x-ui.button :locked lockedReason>` rendert `aria-disabled=
"true"` (ohne native `disabled`), Schloss-Icon links vom Label,
Tooltip mit Grund und die neue Style-Klasse `.is-disabled`; für
rohe `<button>`-Tags im Bestand macht dieselbe Semantik die
neue Blade-Direktive `@disabledIf($condition, $reason)`, die
`aria-disabled`, `title` und `data-locked="1"` in einer Zeile
setzt. Persona-Befund B-K-B-04 („Reader sucht den Add-User-
Button und ist verwirrt") ist damit adressiert: statt Buttons
für Nicht-Berechtigte auszublenden, zeigen wir sie gesperrt mit
erklärendem Tooltip.

Für die Berechtigungssicht neu ist die `<x-ui.save-bar>`-
Komponente — eine Sticky-Bar am unteren Rand, die bei
`@if($this->isDirty)` auftaucht (Livewire-3-`#[Computed]`) und
Verwerfen/Speichern anbietet. Die globale Nutzer:innen-Liste
`/users` folgt der Handschrift der Phase-5-D-Projektliste: CSS-
Grid-Tabelle mit Filter-Chips nach Rolle, Suchfeld,
Rollen-Chips als farbige Pills, Status-Spalte für „Einladung
ausstehend". Das User-Menü im Rail bekommt einen dritten
Menüpunkt „Passwort ändern" und wird A11y-technisch mit
`role="menu"` und Divider aufgeräumt. Alle vier Projekt-Screens
(Editor, Metadaten, Übersetzen, Berechtigungen) haben jetzt
denselben Tab-Balken über die neue Komponente
`<x-projects.tabs :project active>` — der Berechtigungen-Tab
zeigt sich nur für User mit `invite`-Recht.

Als Least-Privilege-Sicherheitsnetz fällt der Register-Flow
ohne mitgeschickte Rolle auf Reader zurück (statt rollenlose
User zu erzeugen, für die `@can`-Gates nicht mehr greifen); die
`RegisterRequest`-Rules geben die alte `required`-Fessel
entsprechend auf. Der Invite-Flow direkt in der Berechtigungs-
sicht legt bestehende Nutzer:innen mit Reader-Default an; die
Neuanlage-Kette folgt in einer späteren Iteration.

**Phase-5e — Vokabular-Sweep und Fehlerseiten abgeschlossen.**
Der komplette UI-Wortschatz wandert nach Glossar: `Eintrag` wird
zum **Abschnitt**, `Block` zum **Inhalt**, `Galerie` zum
**Bildergalerie**, `Item` und `Draft` fliegen raus,
Genderungs-Formen (`AutorIn`, `BenutzerIn`) gehen auf die
Doppelpunkt-Variante. Anlage-Verben werden substantivisch
(„Neuer Abschnitt" statt „Abschnitt hinzufügen"). Alle
Änderungen sitzen in `resources/lang/de.json` — 28 Keys
umgezogen — und in einer DataTable-i18n-Nachjustierung
(Kommentar-Liste). Das Backend-Domain-Modell bleibt englisch
(Entry/Chapter/Text/Image/Gallery), das UI folgt dem Glossar.

Die drei Fehlerseiten (403/404/500) sind persona-freundlich
neu geschrieben: großer Statuscode-Anker, freundlicher Titel,
konkreter Handlungsvorschlag und ein CTA zurück in die App.
Der 500-Fehler hatte im Non-Debug-Modus vorher Exception-
Message plus Datei und Zeile an Endnutzer ausgegeben — ein
Info-Leak, der mit dem Rewrite geschlossen ist. Die 403-Sicht
adressiert außerdem den Backlog-Punkt „Auth-Fehler auf der
Übersetzungs-Route" inhaltlich: statt einer nackten
Exception-Seite sehen betroffene Nutzer:innen die neue
Sicht mit dem Hinweis „Wende dich an die Projekt-Inhaber:in".

Zur Regression-Absicherung neu: ein Vokabular-Snapshot-Test
scannt `de.json` und die Blade-Views nach den aussortierten
Alt-Vokabeln und schlägt fehl, sobald jemand per Copy-Paste
einen Rückfall einbaut. Und das Glossar-Kapitel in
`docs/architecture.md` fasst UI-Vokabel ↔ Backend-Modell in
einer Tabelle zusammen — als Nachschlagewerk für Onboarding.

Das Dashboard (Screen 09) kam abends nach: der Designer
lieferte ein durchgearbeitetes Briefing mit Screen-Datei und
konkreten Design-Entscheidungen (einspaltige Prioritäts-
Achse, vier Sektionen — Wiederaufnahme / Meine Projekte /
Mir zugeteilt / Letzte Kommentare — Empty-States pro Sektion,
CTA nur in einem). Der neue `DashboardController` lädt die
Sektionen (`updated_at DESC`, max 6 Karten je Sektion,
Kommentare 30-Tage-Fenster / max 5), die View sitzt in
`resources/views/dashboard.blade.php` mit zwei Partials
(`_project-card`, `_comment-row`). Rollen-Badge auf
zugeteilten Karten unterscheidet Editor:in vs. Leserecht;
Leserecht-Karten führen zur Leseansicht statt zum Editor
(sonst 403). Sidebar-Rail bekommt „Start" als ersten
Menüpunkt, `RouteServiceProvider::HOME` geht zurück auf
`/dashboard`.

**Phase-5-Sammler.** Sammelbranch für die kleineren Restposten
zwischen den grossen Sub-Wellen der Phase 5: einzelne
Aufraeumarbeiten, die zu klein fuer einen eigenen Feature-
Branch sind, und Vorarbeiten, die spaeteren Sub-Wellen den
Boden bereiten (z. B. gemeinsame Helfer, Vokabular-Nachzuege,
Test-Regressionen). Konkrete Aenderungen listet dieser Absatz
inkrementell auf, je nachdem was in den Branch einlaeuft.
**Phase-5x — Kommentar-System auf Handoff-Design abgeschlossen.**
Das komplette Kommentar-Erlebnis rutscht auf das Design v5.
Statt der alten Sidebar-Section unter dem Beitrag gleitet ein
Slide-out-Panel rechts rein (`<x-layout.comment-panel>`, 26 rem,
`shadow-floating`, weiche 350-ms-Transition mit
`cubic-bezier(0.16, 1, 0.3, 1)`); Trigger sind die Kommentar-
Icons am jeweiligen Block — kein Full-Page-Reload mehr. Der
Panel-Inhalt läuft über die neue Volt-Komponente
`comment-panel-list`, die per Livewire-Event
`comment-panel:load` den aktiven Block bekommt und Kommentare
plus Composer rendert. Der `comment-composer` löst die Legacy-
Reply-Forms ab; Leser:innen sehen dort eine aria-live-
Hinweiszeile statt eines Textfelds.

Datenmodell auf backed enum: `comments.status` wandert von
integer 1..5 auf String-Enum (`open` · `in_progress` ·
`resolved` · `rejected`), mit Migrations-Downpath für den
Wartungsfenster-Rollback. Ein toleranter `CommentStatusCast`
absorbiert Alt-Werte während der Übergangsphase. Berechtigungen
kondensieren in eine neue `CommentPolicy`: Bearbeiten strikt
autor-only (auch Owner dürfen fremden Text nicht editieren),
Löschen als Hard-Delete mit Reply-Kaskade (Owner darf alle,
Autor:in nur ohne Antworten), Status-Wechsel an das
`comment`-Recht gebunden, Antworten ignorieren Status-Setzer
still. `CommentService::deleteComment` schreibt die Regel im
Service-Layer weiter.

Neue globale Sicht unter `/allComments` (Screen 11) folgt dem
Muster von Users/Projects: Titel + Suche, Filter-Chips mit
Status-Zählern, CSS-Grid-Zeilen mit Autor:in-Initialen,
Projekt+Blocktyp, Text-Snippet, Status-Chip in Design-Token-
Farben, Datum und Aktion-Deep-Link mit `?model=…&comment=…` in
den Editor-Panel-Auto-Open. Der Rail bekommt einen roten
Primary-Badge über dem Kommentar-Icon mit der Anzahl offener
Wurzelkommentare pro User (`CommentCounter`-Service, memo-
gecacht pro Request); Struktur-Baum-Zähler pro Chapter und
Entry im Sidebar-Panel zeigen den gleichen Wert pro
commentable-Modell. Dashboard-Rows zeigen jetzt ebenfalls auf
`?model=…&comment=…`, damit ein Klick von der Landing-Seite
direkt das richtige Panel im Editor öffnet.

Karten-System auf Design-Tokens: Chip-Switcher statt Select
für den Status, `bg-info-bg/text-info`-Familie statt
erfundener `-50/-700`-Varianten, Reply-Karten in einem
`border-l-2 border-line-200`-Threading-Container mit sichtbarer
Einrückungslinie, „Erledigte anzeigen"-Toggle im Panel-Header
mit `sessionStorage`-Merker (per Default sind erledigt +
abgelehnt aus). Der Sichtbarkeits-Filter greift auf DOM-Ebene
per Alpine `x-show`, ohne Livewire-Roundtrip.

Nebenbei drei stille Bugfixes, die entlang des Wegs auftauchten:
der globale `*, *::before, *::after`-Reset in `app.css` (aus
dem Theme-Wechsel-Fade) fraß Tailwinds `transition-opacity`
und `transition-transform`-Klassen — Panel-Slide-Animationen
umgehen ihn jetzt per inline-`style`. Die
`grid-cols-[…fr]`-arbitrary-Klasse ist in Tailwind 4 ab sechs
Spalten oder mit Dezimalzahlen instabil, für die Comments-
Übersicht setzen wir das Grid deswegen per inline-Style. Und
Alpine `$dispatch` blieb wirkungslos, wenn die Trigger-Elemente
kein `x-data` erben — `<x-comment.trigger>` bekommt seins
explizit.

**Phase-5y — Galerie-Block-Redesign in Arbeit.** Der Galerie-
Block folgt jetzt dem Muster aus Screen 12/12A–D. Ein
schmaler Pill-Kopf zeigt den Blocktyp, Aktionen wandern
in einen Kopf-Slot, und die überflüssige 250-px-Leerfläche
im Editor-Chrome ist weg. Die Bilder liegen in einem
Auto-Fill-Grid mit 16:9-Kacheln (`object-contain`), Titel
unter dem Bild, eine kleine Positionsnummer plus Griff oben
links und eine Zeile Angaben-Status darunter — grün „Angaben
vollständig" oder gelb „Bildbeschreibung fehlt · Ergänzen".
Eine Bildbeschreibung kommt als eigenes translatables Feld
neu hinzu (Migration `add_description_to_images`,
`Image::$translatable` erweitert). Die Detailzeile öffnet
inline unter der Kachel und bündelt vier Felder (Titel,
Beschreibung, Urheberrecht, Quelle) in einem `<x-block.row>`-
Container. Papierkorb ist strikt einer pro Ebene: der Kopf-
Slot löscht die Galerie, das Kachel-Overlay das Bild — die
Bild-Karte bekommt kein zweites Trash-Icon mehr.

Sortieren geht per Maus (Sortable.js am Griff, POST auf
`gallery.images.reorder`) und per Tastatur: Fokus auf den
Griff, Leertaste hebt das Bild an, Pfeiltasten verschieben
in beide Achsen, zweites Leertaste-Drücken legt ab, Esc
rollt die vor der Aufnahme snapshottete Reihenfolge zurück.
Bewegungen werden in einer `aria-live="polite"`-Region als
„Bild an Position X von N verschoben" angesagt. Beim Ablegen
speichert der bestehende POST-Endpunkt die neue Reihenfolge
über den neuen `ContentReorderService::reorderImages(…)`. Der
Sortable-Kontext bekommt eine benannte Gruppe mit
`pull: false, put: false`, damit sich Bilder nicht aus der
Galerie in andere DnD-Container ziehen lassen.

Der Übergang Raster ⇄ Detailzeile folgt § 6 des Briefings:
Blockhöhe wird vor und nach dem State-Wechsel gemessen und
über 200 ms interpoliert, damit der Kontext darunter nicht
springt. Parallel wandert die geklickte Kachel per FLIP
(First-Last-Invert-Play) 180 ms lang mit
`cubic-bezier(0.2, 0.7, 0.3, 1)` auf die Vorschau-Position
der Detailzeile — und beim Zurückweg zurück auf die Kachel-
Position im Raster. Nach dem Enter setzt ein Timer den Fokus
auf das erste bearbeitbare Feld. `prefers-reduced-motion`
überspringt sowohl FLIP als auch Höhen-Interpolation und
wechselt hart. Ein Save-Event-Listener merkt sich Änderungen
im Detailmodus und lädt den Editor beim Zurückweg voll neu,
sodass die aktualisierten Titel + Angaben-Status wieder auf
der Kachel sichtbar werden.

Über dem Raster sitzt der Kopf aus Briefing § 3: `BILDER`-
Pill, Anzahl-Chip (`trans_choice` mit `count`), Sammel-
Warnung, wenn Angaben fehlen, Hinweis „Reihenfolge =
Ausstellungsreihenfolge" und rechts der Primary-Button
„Bilder hinzufügen". Am Ende des Rasters liegt eine
gestrichelte 16:9-Drop-Zone-Kachel mit demselben Modal-
Trigger — der alte Plus-Icon-Button neben dem Papierkorb
in der Blockkopf-Zeile entfällt. Bei leerer Galerie ersetzt
eine schlanke, ganzflächige Drop-Zone das große
`media-placeholder`-Streifenmuster. Positionsnummern werden
nach jeder Sortier-Aktion (Maus wie Tastatur) im Grid neu
gezeichnet, damit die Zahl auf der Kachel immer die aktuelle
Reihenfolge wiedergibt.

Für Leserechte wird der Galerie-Block ausgedünnt: Kein
Add-Button im Rasterkopf, keine Drop-Zone-Kachel am Ende
des Rasters, kein Ziehgriff (nur die Positionsnummer bleibt
sichtbar), kein Overlay mit „Angaben bearbeiten"/Löschen.
Über dem Raster erscheint eine ruhige Hinweiszeile in
`bg-info-bg` mit dem Satz, dass Bilder in der Großansicht
öffnen und Reihenfolge und Angaben nicht bearbeitbar sind.

Die Drop-Zone lädt Dateien jetzt optimistisch hoch. Beim
Ablegen (oder über den nativen Dateipicker im gleichen Feld)
prüft der Client Format und Größe, hängt für jede gültige
Datei eine Ghost-Kachel mit Vorschau, Fortschrittsbalken
(`bg-info`) und „Abbrechen" ans Raster und schickt einen
XHR-POST auf einen neuen JSON-Endpoint
`gallery.images.drop`. Der Endpoint legt das Bild ohne
Copyright und Quelle an — beide werden im Schema nullable
(`make_image_sources_nullable`-Migration) und lassen sich in
der Detailzeile nachpflegen. Der Angaben-Status-Chip auf
der Kachel meldet die Lücken sofort. Nach dem letzten Upload
lädt die Seite einmal neu, damit Raster, Header-Anzahl,
Sammelwarnung und Publish-Check konsistent sind. Ein
Teilfehler wirft die anderen Dateien nicht zurück — der
Rejected-Banner listet Dateiname und Grund, „Hinweis
schließen" räumt ihn ab.

Der „Veröffentlichen"-Button prüft vor dem HTML-Export den
Bestand an Bildern und listet die Lücken im Vorschau-Modal
namentlich auf — jeder Eintrag mit Bildtitel und fehlendem
Feld (Bildbeschreibung, Urheberrecht oder Quelle). Sind alle
Angaben vollständig, erscheint eine grüne
`bg-success-bg`-Zeile mit „Alle Bilder haben vollständige
Angaben." Der HTML-Export-Button bleibt in beiden Fällen
freigeschaltet — das Ziel ist Bewusstsein, nicht Blockade.

Die Audio- und Video-Blöcke ziehen mit: Versionen, Kommentar
und Löschen wandern aus der Fuß-Zeile in den Blockkopf-
Aktions-Slot, damit die Anatomie gleich der Galerie ist. Die
alte Fuß-Zeile schrumpft auf einen Angaben-Status („✓
Angaben vollständig" oder „⚠ Urheberrecht/Quelle fehlt")
plus Speicherdatum. Die Publish-Prüfung im Vorschau-Modal
listet Audio-/Video-Blöcke mit fehlendem Urheberrecht oder
fehlender Quelle jetzt namentlich neben den Bildern.

Nebenbei: Tab-Label „permissions" in der Editor-Segmented-
Control ist jetzt „Berechtigungen".

**Phase-5z — Gerüst und Blöcke auf Design v6.** Nach der Galerie
wandern jetzt auch Kapitel, Abschnitt, Text-, Video- und Audio-
Block auf die neuen Screens 13A–13D — mit der Konvention aus
5e, dass das Design-Vokabular „Eintrag/Block" auf unser Persona-
Glossar „Abschnitt/Inhalt" übersetzt wird. Kapitel bekommt eine
3-px-Rail-Klammer über die ganze Gruppe (leere Kapitel in
`line-200`, gefüllte in `brand-bar`), einen Mono-Caps-Chip
„KAPITEL n · m Abschnitte" via `trans_choice` und ein ⋯-Menü,
in das Löschen aus der Titelzeile wandert. Duplizieren und
Verschieben stehen als deaktivierte Platzhalter, bis das
Backend nachzieht. Ein leeres Kapitel ist jetzt ein Zustand aus
Info-Banner und primärer „Ersten Abschnitt anlegen"-Aktion,
kein 500-px-Weiß mehr. Die zwei Einfüge-Zonen sind visuell
getrennt: „+ Neuer Abschnitt" schmal und eingerückt innerhalb
der Rail, „+ Neues Kapitel" in voller Breite mit dickerem Rahmen
am Tree-Ende.

Der Eintrag folgt derselben Anatomie: Chip „ABSCHNITT n · IN
„<Kapitelname>"" statt bloßer Elternnummer, Aktionen in
Reihenfolge Versionen · Kommentar · ⋯, Löschen im Menü,
Duplizieren/Verschieben ebenfalls disabled bis das Backend
folgt. Leere Titel, Untertitel und Beschreibungen reservieren
in Reader-Sicht keine Höhe mehr, und der Rich-Text-Editor
verkleinert seine Mindestbox von 6 rem auf 2 rem, damit die
Editor-Sicht nicht mehr um 96 px pro leere Description atmet.
Insgesamt schrumpft die Editor-Seite um über 1.500 px Leerraum.

Der Text-Block heftet Versionen, Kommentar und Löschen in den
Blockkopf-Slot, die alte Fußzeilen-Aktionsreihe entfällt.
Darunter erscheint bei Fokus die Quill-Format-Leiste (schon
seit 5-D.6b), plus eine Absatz-Legende „⏎ neuer Absatz · ⇧⏎
Zeilenumbruch — Abstände erscheinen in der Ausstellung genau
so." Der `source-picker` rendert im Ruhezustand keinen
doppelten „Urheberrecht: M. Heinrich"-Chip mehr, sondern ein
echtes Feld mit dem reinen Wert im Rahmen und dem Label in
der Umgebung. Fehlt der Wert, wechselt der Rahmen auf
`warning` mit Hinweis „⚠ Wird beim Veröffentlichen namentlich
aufgeführt" — dasselbe Muster, das auch die Galerie und der
Publish-Check verwenden.

Video- und Audio-Blöcke bekommen einen einheitlichen Player-
Chrome. Plyr (`^3.8.4`, MIT) ersetzt den nativen `<audio
controls>` und das nackte YouTube-iframe; die Farb-Custom-
Properties liegen auf unseren `ink`- und `paper`-Tokens. Das
Plyr-Init-Modul hakt auf `DOMContentLoaded`, `livewire:init`
und `livewire:navigated`, damit auch dynamisch re-renderte
Blöcke (Type-Wechsel, neu angelegt) einen Player kriegen.
Für YouTube schaltet `noCookie: true` die Zwei-Klick-
Einbettung — vor dem ersten Play geht kein Drittanbieter-
Request raus. Ein neuer `App\Support\VideoLink`-Helper
extrahiert die YouTube-ID aus Watch-, `youtu.be`-, Embed- und
Shorts-URLs und normalisiert auf die kanonische Embed-URL; die
neue `livewire:video-link-editor`-Komponente akzeptiert die
Adresse wie sie im Browser steht und speichert normalisiert.
Nicht-YouTube-Quellen rendern jetzt einen 16:9-Fehler-Panel
in `danger-bg` mit Grund, „Wird beim Veröffentlichen
übersprungen"-Fuß und „Als Link ausgeben"-Fallback. Der
Audio-Uploader zeigt statt des Storage-Schlüssels eine Meta-
Zeile aus Format, Größe und Upload-Datum (`Storage::size`,
`Storage::lastModified`), dazu explizite „Ersetzen"- und
„Entfernen"-Aktionen — Letztere fehlte bislang.

Transkript ist neu: eine translatable `transcript`-Spalte auf
`audiovisuals` (Migration `add_transcript_to_audiovisuals`),
gerendert als mehrzeiliger Inline-Editor über der Herkunft, mit
Erklärungszeile „Für Screenreader, Suchmaschinen und alle, die
nicht abspielen können." Weiche Pflicht: der Angaben-Status
zählt ein fehlendes Transkript ein, die Publish-Prüfung nennt
den Block namentlich neben Bildbeschreibung, Urheberrecht und
Quelle. Der Blocktyp-`<select>` verschwindet dabei aus dem
Body — der Typ steht im Chip, Umwandeln bleibt für einen
späteren ⋯-Menü-Eintrag reserviert. „▸ Metadaten"-Kollaps ist
aufgelöst, Herkunft steht offen wie bei Text und Galerie. Die
neue Fußzeile ist für alle Blocktypen gleich: Vollständigkeits-
Status links, Speicherstand mit Datum UND Uhrzeit rechts.

`add_chapter` fehlte bislang als i18n-Schlüssel — der Sidebar-
Tree-Button zeigte den rohen Key. Neu angelegt als „Neues
Kapitel" (parallel zu `new_chapter`, 5e-Substantiv-Konvention).

**Phase-5aa — Nebenseiten (Einstellungen, Metadaten,
Übersetzen, Export) auf Design v6.** Die vier
Rand-Sichten der Projekt-Werkbank werden aus dem Bestand
gehoben und an das Chrome und die Muster aus 5-D angeglichen.
Einstellungen listet Rechtstexte (Impressum, Datenschutz-
erklärung, Geschäftsbedingungen) und E-Mail-Vorlagen
(Einladungs-Mail, Aktivierungs-Mail, Passwort-Vergessen-Mail)
in zwei Gruppen mit Status-Chip und Auszug pro Zeile;
jede Zeile hat einen echten „Bearbeiten"-Button, der die
bisherigen Bestands-Modale weiterhin öffnet (Backend
unverändert).

Metadaten bekommen eine Sticky-Save-Fußzeile mit primärem
„Speichern" und sekundärem „Änderungen verwerfen", weil ein
Speichern-Punkt in der Rail beim Metadatenfeld weit weg vom
Fokus liegt. Das Vorschau-Bild ist ein 120×120-Alpine-Panel
mit „Bild wählen" und „Entfernen" statt eines nativen
File-Pickers, der Projektname trägt einen Zeichenzähler
(80). Rechte Spalte fasst Publish-Check-Anker, Kennzahlen
(Kapitel, Abschnitte, Übersetzt-Prozent, letzte Änderung)
und Verlaufs-Link zusammen; Projekt löschen steht mit
gebührendem Abstand darunter.

Impressum und AGB dürfen jetzt leer bleiben — dann greift
der systemweite Text aus Einstellungen automatisch. Ein
neuer `App\Support\ProjectLegalText`-Helper hält die Regel
an einer Stelle; sowohl die Metadaten-Sicht als auch die
HTML- und PDF-Preview ziehen ihren Wert von dort. Zusätzlich
gibt es einen „Systemtext übernehmen"-Button pro Feld, der
den strukturierten Systemtext einmalig ins Projektfeld
kopiert (POST `projects.metadata.adopt_system_text`, per
`update`-Policy geschützt).

Übersetzen ist eine Zwei-Spalten-Sicht mit Sprachpaar-Selektor
oben, „Nur unübersetzte Felder"-Filter und Sektions-Chips pro
Kapitel/Abschnitt/Inhalt mit `⚠ n von m Feldern übersetzt`-
Countern. Die Sticky-Fußzeile zeigt einen Fortschrittsbalken
plus einen einzigen Sammel-Speichern-Button. Content-Blöcke
(Text, Galerie, Audiovisuell) sind eingeschlossen — bislang
fehlten sie in der Übersetzen-Sicht komplett. Speichern läuft
über den neuen `translate.save`-POST-Endpoint, der Payload-
Schlüssel im Schema `translations[Model.id.field]` entgegen-
nimmt und pro Modell die Zugehörigkeit zum Zielprojekt
prüft; fremde Modelle werden verworfen. Auf jedem Input hängt
ein Auto-Save-on-Blur: das Feld schickt beim Verlassen genau
einen Wert ans Backend, die Sektions-Counter und der
Gesamt-Fortschritt aktualisieren live ohne Reload. Der große
Sammel-Speichern-Button bleibt als Notfall stehen, falls
Blur-Change verpasst wurde oder der Nutzer offline war.

Der Export-Dialog bekommt eine kuratierte Formatwahl (HTML
oder PDF als zwei Radio-Karten, primärer Button folgt der
Auswahl) statt der freien Format-Liste; die Konsequenz jeder
Toggle-Option steht als eigene Zeile unter dem Schalter.
Publish-Check listet fehlende Angaben mit `Ansehen`-Deep-
Links, die den passenden Abschnitt im Editor per Anchor
öffnen. Vier vorkonfigurierte Akzentfarben (Rot, Anthrazit,
Teal, Braun) mit WCAG-Kontrastwert lösen den freien
Farbwähler ab. Sprache mit Fallback-Konsequenz macht
transparent, was passiert, wenn ein Feld in der Zielsprache
fehlt.

**Phase-5ab — Verlauf als Panel, Wort-Diff im Block,
Wiederherstellen mit Übersetzungs-Warnung.** Der Verlauf
wird aus einer eigenen Editor-Seite in ein Slide-out-Panel
rechts gehoben, das sich das Muster mit dem Kommentar-Panel
aus 5x.1 teilt (§ 6 des Briefings: derselbe Behälter, anderer
Inhalt). Beide Panels schließen sich per Namens-Guard auf
`panel:open` gegenseitig aus. Fassungs-Karten sind gruppiert
nach Tag, tragen Actor, Zeit, `v`-Chip und einen farbigen
Kind-Chip (Inhalt · Angaben · Reihenfolge · Übersetzung),
darunter eine einzeilige Kurzfassung. Ein Segmented Control
oben schaltet zwischen Umfang Block, Abschnitt und Projekt.

Datenmodell: neue Tabelle `revisions` mit polymorphem
Subject, `actor_id`, `kind`, JSON-`snapshot` und
`version`-Zähler; `HasRevisions`-Trait auf allen sechs
Content-Modellen schreibt bei `created`/`updated` eine
Revision und mergt Änderungen derselben Person am selben
Feld innerhalb eines 5-Min-Fensters in die letzte Revision
(§ 8.2 des Briefings) — sonst würde Auto-Save-on-Blur aus
5aa den Verlauf zuschütten. Ein Artisan-Command
`revisions:backfill` zieht die vorhandenen `activity_log`-
Zeilen für Chapter, Abschnitt, Text, Galerie, Bild und
Audio/Video in `revisions` nach, überspringt No-op-Deltas
(null↔"") und hat `--dry-run` und `--fresh`-Flags. Der
Deploy-Runbook-Schritt 5 dokumentiert den einmaligen
Backfill vor dem Cut-over.

Diff-Anzeige im Block, nicht in einem Viewer (§ 6). Ein
Klick auf eine Fassungs-Karte legt pro geändertem Feld ein
Wort-Level-Diff-Overlay auf die betroffene Stelle im
Editor: hinzugefügte Wörter in `success-bg`, entfernte in
`danger-bg` mit `line-through`, benachbarte
delete+insert werden zu einem `replace` verschmolzen
(„Zeit" → „Zeiten" liest sich als eine Änderung, nicht
zwei). Das Overlay ist ein eigenes Kind-Element des
`[data-history-field]`-Wrappers — der Livewire-Rich-Text-
Editor darunter bleibt komplett im DOM, Alpine-State und
`wire:model`-Bindings gehen nicht verloren, weil wir sein
Markup nie anfassen. Ein Info-Banner „Nur zum Ansehen"
oben zeigt den aktiven Vergleichsmodus, `data-history-lock`
setzt die Aktionen im Block auf `pointer-events: none` und
die Eingaben auf read-only. `Esc` oder der Banner-Button
schließt und stellt den Original-DOM wieder her.

Wiederherstellen ist bewusst nicht destruktiv (§ 7).
Ein Bestätigungs-Dialog sagt zuerst, was **nicht** passiert:
die aktuelle Fassung geht nicht verloren, sondern wird als
eigene Fassung im Verlauf abgelegt. Bei vorhandenen
Übersetzungen zeigt der Dialog eine Warnung, dass sie
erhalten bleiben, aber als „Original nach der Übersetzung
geändert" markiert werden. Primärer Button ist `ink-900`,
nicht `danger` — die Aktion ist umkehrbar. Der Restore
schreibt den `new`-Wert der gewählten Fassung zurück
(nicht den `old`-Wert davor — „Wiederherstellen von v13"
aktiviert den Zustand, den v13 darstellt) und geht bei
translatable Feldern über `setTranslations()`, damit alle
Locales erhalten bleiben. Serverseitig ist der Endpoint
über die neue `history-restore`-Permission project-scoped;
Admin greift wie üblich über `before()`.

„Original nach der Übersetzung geändert" (§ 4). Eine neue
Tabelle `translation_source_references` merkt sich pro
(Subject, Feld, Locale), auf welcher Revision des Originals
die Übersetzung basiert. `saveTranslations` schreibt/aktua-
lisiert die Zeile bei jedem EN-Save. Die Übersetzen-Sicht
prüft in einem Bulk-Query, ob die aktuelle Original-Fassung
neuer ist als die Referenz — und rendert einen Warn-Chip
neben dem Feldlabel. Für Bestand-Übersetzungen gibt es
absichtlich keinen Backfill; der Marker greift erst ab
Cut-over.

Aufräumung: der alte Version-Log-Kartenblock unter dem
Editor entfällt, das Panel ersetzt ihn. Alle
`rotate-ccw`-Trigger auf Kapitel, Abschnitt, Text-, Video-
und Audio-Block sowie Galerie wandern auf den neuen
`<x-ui.history-trigger>` — kein Full-Page-Reload auf
`?log={id}` mehr. Ein kleiner Punkt-Indikator am
Verlauf-Icon zeigt, wo bereits Historie liegt.

**Phase-5ac — Profil-Seite auf Design v7 (Screen 17A) plus
Kürzel-Fallback und Avatar-Komponente.** Die alte
`/profile`-Sicht mit ihren fünf Feldern und einem Speichern-
Button war das letzte alte Chrome im Editor-Bereich. Sie
wird durch vier klar abgegrenzte Karten ersetzt: „Person &
Darstellung" oben mit Vor-/Nachname, Kürzel-Fallback,
Farbwahl aus sechs Token-Werten, Sprach- und Theme-
Umschalter (Sofort-Wirkung ohne Save), „Meine Projekte &
Rollen" als Lese-Karte mit Rolle-Chip pro Projekt,
„Passwort ändern" als eigene Karte mit eigenem Save-Button
und Stärke-Meter, und „Benachrichtigungen" mit vier
Toggles (Einladungen bewusst nicht abschaltbar — der
Zugangslink läuft dort). Sticky-Fußzeile nennt, was offen
ist („Zwei Änderungen offen: Kürzel, Benachrichtigungen").

Datenmodell: `users` bekommt `avatar_path`, `initials`,
`initials_color`, `locale`, `theme`; neue Tabelle
`notification_preferences` mit drei Boolean-Spalten (eine
Zeile pro Nutzer via `updateOrCreate`). Kürzel-Sperrliste
liegt in `config/kuerzel_blocklist.php`, der `InitialsBlocklist`-
Helper normalisiert case/Umlaute/Punkte und liefert drei
Vorschläge, wenn ein Kürzel abgelehnt wird — greift auch
für die aus dem Namen automatisch abgeleitete Vorbelegung.

Avatar-Upload läuft über einen schmalen `AvatarService`
(JPG/PNG/WebP, max. 2 MB), Live-Preview per FileReader
ohne Server-Roundtrip, `remove_avatar` löscht die Datei
zusammen mit dem DB-Feld. Der Language-Middleware liest
jetzt zuerst `$user->locale`, dann die Session — die
Sprachwahl greift sofort und geräteübergreifend.

Kaskaden für „zuletzt bearbeitet" im Dashboard: Chapter
touched Project, Entry touched Chapter, ein neuer Trait
`TouchesEntryViaMediaContent` sitzt auf Text, Galerie und
Audiovisuell und toucht den Entry über den MediaContent-
Pivot; Image touched über `$touches = ['gallery']`. Damit
bewegt sich das Projekt-Timestamp auch beim Editieren
einzelner Content-Blöcke, statt statisch an der letzten
Metadaten-Änderung zu hängen.

Wiederverwendbare Blade-Component `<x-ui.user-avatar>`:
rendert das hochgeladene Bild oder das gewählte Kürzel
mit Farbe, akzeptiert einen `size`-Prop. Rail-User-Chip,
Verlauf-Panel-Fassungskarten und Kommentar-Karten nutzen
sie — überall dort erscheint das Profilbild oder das
gewählte Kürzel statt eines uniformen Erstbuchstabens.

### Hinzugefügt

- **Q4-Etappe 8 · Route-Konvention und Kleinkram** (2026-09-12).
  Umsetzung von ADR-0030 (URL-Konvention Plural) plus drei Sanierungs-
  Punkte aus Etappe H. Alle Projekt-Sub-Routen laufen jetzt konsistent
  unter `/projects/{project}/…` mit Route-Model-Binding
  (`projects.metadata`, `projects.translations.edit/.update`,
  `preview.legal`); Kommentar-Endpunkte sind auf `/comments/{type}/…`
  und Route-Namen `comments.{type}[.action]` migriert; Content-Aktionen
  (`/text/*`, `/image/*`, `/gallery/*`, `/audiovisual/*`, `/quote/*`,
  `/data-facts/*`) sowie Nebenrouten (Log, Role-Check, Permission-User,
  Reset-Log, Save-Gallery, Save-Audiovisual) sind auf Plural-URLs
  umgezogen — Route-Namen bleiben im sanften Modus. Alte GET-Pfade
  bekommen 301-Redirects fuer eine Release-Iteration, POST/DELETE sind
  vom Frontend hart mitgezogen. Die JSON-Endpunkte fuer Drag-Reorder und
  Gallery-Bilder-Reorder/Drop wandern unter das bestehende
  `/api/internal/`-Prefix (I11). `resources/views/users/profile.blade.php`
  ist von 760 auf 280 LoC im Wrapper geschrumpft, verteilt auf drei
  Sub-Views `_person` / `_password` / `_deletion` (I9). Die `roles/*`-
  Sichten (Index, Create, Edit, Show) sind von Bootstrap-3 auf
  Tailwind + Alpine umgezogen; das alte jQuery-`.modal('show')` beim
  Rollen-Loeschen laeuft jetzt als Alpine-`x-data`-Overlay (E3e).

- **Q4-Etappe 7 · Editor-Politur nach Design-Review** (2026-09-11).
  Umsetzung der 25 Designer-Befunde aus „REVIEW CMS Oberfläche" plus
  der drei Etappe-6-Reste. Das CMS ist von einer heterogenen
  Bootstrap-3-Restfläche auf ein einheitliches Chrome-Modell umgezogen:
  neue Blade-Komponenten `<x-projects.chrome>`, `<x-projects.editor-actions>`,
  `<x-projects.save-state>`, `<x-projects.edit-save-state>` und
  `<x-projects.export-modal>` tragen jetzt auf jedem Editor-Screen
  (Bearbeiten, Metadaten, Übersetzen, Quellen, Berechtigungen) dieselbe
  Reiterleiste, denselben zentralen Save-Status und denselben Publish-
  Trigger. Der Struktur-Baum ist read-only auf allen Nicht-Bearbeiten-
  Screens sichtbar, damit die Layout-Silhouette konsistent bleibt.
  Metadaten speichern via Alpine-Store `metadataAutosave` mit 1,5-s-
  Debounce (`ProjectController::update` antwortet auf `wantsJson()`
  mit JSON-Response inklusive `savedAtLabel`), Quill-Inhalte werden
  vor jedem Payload synchronisiert; File-Uploads laufen weiter über den
  klassischen Form-Submit-Weg. Die Editor-Farbwahl ist auf drei
  kuratierte Presets pro Charakter reduziert (mit AA-Kontrastwerten),
  daneben bleibt ein freies Hex/Color-Picker-Feld als Reserve; der
  Charakter-Default wird beim Wechsel automatisch neu vorgewählt.
  Neuer `CascadesToMediaContent`-Trait an Text/Audiovisual/Gallery/
  QuoteBlock/DataFactBlock schließt beim SoftDelete/Restore die
  zugehörigen `media_content`-Pivots mit ab; eine begleitende
  Cleanup-Migration soft-löscht bereits verwaiste Pivots (behebt die
  „doppelte Add-Bar"-Sicht). Der Daten-und-Fakten-Block erhält ein
  optionales, translatable Rich-Text-Beschreibungsfeld, der Source-
  Picker bekommt eine „Neue Quelle anlegen"-Aktion + Kopfzeile
  „Bereits im Projekt verwendet"; die Quellenverwaltung listet
  Referenzen als klickbare Sprungliste. Der Multi-Page-Reader bekommt
  Rail-Fußlinks zu neuen Sichten für alle Abbildungen und für
  Kapitel-PDFs (neue Routen `preview.all_images`, `preview.chapter_pdf`,
  `preview.credits`, `preview.a11y`); die Reader-Fußzeile führt jetzt
  auf echte Bildnachweise- und Barrierefreiheitsseiten statt in tote
  Anker. Nebenaufräumen: Verlaufs-Icon nutzt „gefüllt vs. leer" statt
  eines roten Zahl-Badges, Rail-Items zeigen einen sofortigen
  data-tip-Popover, gepunktete Andeutungslinie plus blasses Plus für
  Zwischen-Add-Bars, konsolidierte „Alle Änderungen gespeichert"-
  Anzeige in der Chrome-Bar (per-Block-Timestamps entfallen),
  Bildunterschriften-Grid gedreht (kein buchstabenweiser Umbruch mehr
  bei langen Credit-Namen), `--accent`-Override im Reader mit
  korrekter Selektor-Spezifität und WCAG-Luminance-gestütztem
  `--accent-on`. Der alte „Bild hinzufügen"-Button plus die
  `contents.image`-Modal-Kette sind entfallen — Bild-Upload läuft
  ausschließlich über die Drop-Zone im Gallery-Block. Migrations
  in Reihenfolge: `add_license_to_sources_table`,
  `add_description_to_data_fact_blocks_table`,
  `cleanup_orphan_media_content_pivots` (datenverändernd —
  vor Prod-Deploy Backup und Staging-Verifikation). Details in
  `.werkbank/PLANS/Q4-ETAPPE7-BILANZ.md`.

- **Q4-Etappe 6 · G7 · PDF-Neubau** (2026-09-10). Der PDF-
  Renderer im Preview-Export ist grundlegend reduziert und
  ersetzt den bisherigen ~1.100 LoC starken Monolithen
  (`preview/pdf.blade.php`) durch einen schlanken, thematisch
  aufgeteilten Stack unter `preview/pdf/`:

  - `layout.blade.php` als dompdf-Wrapper mit inline Print-CSS,
    `@page`-Rändern und Charakter-Akzent aus dem Projekt
    (`dokumentation` → Ziegel, `archiv` → Blaugrau,
    `erzaehlung` → Ocker; überschreibbar per Projekt-
    Akzent-Farbe).
  - `content/dispatcher.blade.php` mit fünf reduzierten Content-
    Partials (`text`, `gallery`, `audiovisual`, `quote`,
    `data-facts`) sowie `entry-sources.blade.php` als Quellen-
    Block am Abschnittsende.
  - Audio/Video wird bewusst als Hinweiszeile mit Icon-Character
    und URL gerendert — kein Player-Ersatz-Frame auf Papier
    (Handoff für die Print-Ausgabe).
  - Gallery ohne Anzahl-Regel, Sequenz-Modus oder Lightbox: alle
    Bilder in einem simplen Zwei-Spalten-Table-Grid (dompdf
    versteht Tabellen zuverlässiger als Grid oder Flex),
    Nachweiszeile immer unter jedem Bild.
  - Kapitel-Trennung per `page-break-before: always`.

  Gesamt-LoC nach dem Umbau: 477 statt 1.092.
  `preview/pdf.blade.php` (Legacy) und `mpdf/mpdf` (nie im Code
  benutztes Cargo-Paket aus `composer.json`) sind entfernt — der
  Vendor-Footprint sinkt spürbar. Realisiert damit ADR-0019
  („PDF-Lib-Wahl") vollständig; die dort geplante
  `ProjectPdfService`-Extraktion bleibt bewusst offen, weil der
  dompdf-Aufruf im Controller mit drei Zeilen kurz und
  überschaubar bleibt.

- **Q4-Etappe 6 · G6-7 · Lightbox** (2026-09-10). Klick auf jede
  Kachel im Band, Kontaktbogen und in der Sequenz öffnet dieselbe
  Großansicht — dunkler Overlay, großes Bild links, Metadaten-
  Spalte rechts (Bildunterschrift + Nachweis), Prev/Next per
  Buttons oder Pfeiltasten, Escape zum Schließen. Ein globaler
  Alpine-Store `lightbox` verwaltet Zustand und Bildliste; jedes
  Galerie-Blade übergibt beim Öffnen ein `imagesMeta`-Array mit
  allen Bildern der Gallery in Dokumentreihenfolge. Damit sind
  die Einzelnachweise im Kontaktbogen wieder erreichbar — der
  Designer-Punkt „Bogen darf ohne Einzelunterschriften auskommen,
  wenn die Nachweise einen Klick entfernt sind" ist damit
  bedient. Ohne JavaScript bleibt das Overlay komplett unsichtbar
  (`x-cloak` + `[x-cloak]` in reader.css) — Band und Sequenz
  tragen ihre Nachweise ohnehin unter dem Bild. Neue Locale-
  Keys: `lightbox_close`, `lightbox_meta_label` / `_caption` /
  `_credit`, `gallery_open_lightbox`.

- **Q4-Etappe 6 · G6-5 · Kapitel-Titelbild** (2026-09-10). Statt
  eines eigenen Upload-Feldes am Kapitel — das Bilder ohne Nachweis
  produzieren würde — wählt der Redakteur das Titelbild jetzt am
  Bild selbst per Häkchen. Neue Chapter-Spalte `cover_image_id`
  (Migration `add_cover_image_to_chapters_table`, FK auf `images`
  mit `nullOnDelete`), `Chapter::coverImage()`-Relation.
  Editor-Seite:
  - Bild-Detail bekommt eine dritte Zeile in der Vorschau-Spalte:
    Volt-Toggle „Als Titelbild für <Kapitel> verwenden".
    Setzt sich der Redakteur beim zweiten Bild desselben Kapitels,
    wandert die Zuordnung auf das neue Bild; die Komponente
    dispatched dann `image-chapter-cover-swapped` mit dem Namen
    des vorher gesetzten Bildes.
  - Kapitel-Header trägt ein neues Fach `chapter-cover-slot`:
    Vorschau des aktuellen Titelbilds, Herkunfts-Eintrag,
    Nachweis, „Zum Bild springen" (Anker `#anchor_Image_<id>`).
    Ohne Häkchen: Placeholder-Kachel mit Hinweis, wo das Häkchen
    zu setzen ist.

  Reader-Seite:
  - Kapitelkarten auf der Startseite zeigen das Titelbild als
    volle Band-Kachel, mit `focus_x/y` als `object-position`
    (Portraits werden nicht mittig beschnitten). Kapitelnummer
    weiß als Overlay unten links.
  - **Ohne** Titelbild entfällt das Band **komplett** — die
    Kapitelkarte startet direkt mit der Body-Kachel und trägt die
    Kapitelnummer als Mono-Chip. Damit fällt der leere Beige-
    Kasten aus Design-Review 3 (Punkt 5) endgültig weg.

  Kapitel-Konsistenz: Bei Soft-Delete des Bildes filtert die
  Relation stumm heraus (SoftDeletes greift auf BelongsTo), bei
  Hard-Delete setzt der FK die Chapter-Spalte via
  ON DELETE SET NULL zurück. Neuer Anker `#anchor_Image_<id>` an
  jeder Bild-Kachel im Editor-Galerie-Block, damit das Fach genau
  auf das gemeinte Bild springt. Locale-Keys:
  `image_chapter_cover_label` / `_label_with_chapter` / `_hint`,
  `chapter_cover_slot_label` / `_empty` / `_open` / `_change`,
  `chapter_cover_from_entry`, `reader_chapter_card_number`.

- **Q4-Etappe 6 · G6-4 · Reader-Galerie nach Anzahl-Regel**
  (2026-09-10). Der Reader rendert Galerien jetzt in einer der
  drei Formen, die die Anzahl-Regel aus `App\Support\GalleryForm`
  vorgibt — dieselbe Methode, die auch das Editor-Kopfpanel
  entscheidet, sodass Anzeige und Ausspiel nicht divergieren.

  - **Band** (`.cc-gal-band`) bei 1–4 Bildern: alle Bilder gleich
    hoch (380 px auf Desktop, volle Breite auf Mobile), linksbündig
    auf der Textkante, mit `object-position: left center`. Kein
    Beschnitt, alle Bilder bleiben komplett sichtbar.
  - **Kontaktbogen** (`.cc-gal-bogen`) ab 5 Bildern: 3-Spalten-Grid
    (mobil 2), 4:3-Zellen mit 6 px Fuge, `object-fit: cover` plus
    `object-position` aus `focus_x/y`. `no_crop`-Bilder wechseln
    auf `contain` und liegen auf `--paper`, damit Dokumente und
    Scans nicht beschnitten werden. Ab dem 10. Bild ersetzt eine
    dunkle „+n"-Overflow-Kachel den Rest — Einzelnachweise wandern
    laut Handoff in die Lightbox (E1c), unter dem Bogen bleibt
    Platz für eine Gruppenbeschreibung.
  - **Sequenz-Bühne** (`.cc-gal-stage`) auf redaktionelle Ansage:
    dunkles Papier (`#17140f`) im 3:2-Format, Alpine-Pager mit
    Prev/Next-Buttons, Positions-Counter „02 / 06" oben links,
    Fortschritts-Striche unten (Handoff-Regel: Striche statt
    Punkte, 3 px hoch). Nachweiszeile pro Slide als überlagerte
    Zeile am unteren Bildrand.

  Alte Klassen (`cc-fig--full`, `cc-fig-pair`, `cc-gallery-grid`)
  bleiben in der CSS als Legacy-Reste stehen — aktuell verwendet
  sie niemand mehr, aber der Cleanup wandert zum nächsten
  Reader-Sweep. Neue Locale-Keys: `gallery_sequence_aria` /
  `_prev` / `_next` / `_goto`, `gallery_bogen_more`. Reader-
  Rendering greift auf die G6-1-Felder (`no_crop`, `focus_x/y`)
  und den G6-2-Schalter (`sequence`) zurück.

- **Q4-Etappe 6 · G6-3 · Beschnitt-Steuerung im Bild-Detail**
  (2026-09-10). Die Bild-Detail-Zeile bekommt zwei neue Elemente
  in der Vorschau-Spalte: einen Livewire-Volt-Toggle „Nicht
  beschneiden" für Dokumente, Scans und Karten sowie einen
  Fokus-Picker, der per Klick auf das Vorschaubild `focus_x` /
  `focus_y` in Prozent setzt. Ein kleiner Marker im
  Kontrast-Farbring zeigt den aktuellen Fokus-Punkt auf dem Bild,
  der Cursor wird zum Fadenkreuz, sobald der Picker aktiv ist.
  Setzt der Redakteur „Nicht beschneiden", deaktiviert sich der
  Fokus-Picker automatisch (Alpine hört auf das
  `image-no-crop-changed`-Event des Toggles) — ein Fokus wäre
  bedeutungslos, wenn das Bild ohnehin komplett gezeigt wird.
  Beide Werte werden inline gespeichert (kein Redirect,
  konsistent zu den anderen Content-Volt-Komponenten). Der
  Reader nutzt die Felder im Kontaktbogen-Rendering ab G6-4 als
  `object-fit` und `object-position` — bis dahin sammeln die
  Redakteur:innen die Angaben schon einmal.

  Neue Livewire-Komponenten: `image-no-crop-toggle` und
  `image-focus-picker`. Locale-Keys: `image_no_crop_label` /
  `_hint`, `image_focus_picker_hint_click` / `_set` / `_inactive`,
  `image_focus_picker_clear`, `image_focus_picker_aria_active`
  / `_inactive`. Klick-Handler klemmt den Wert auf 0–100 (Alpine
  vor dem Senden, Backend als Sicherheitsnetz).

- **Q4-Etappe 6 · G6-2 · Galerie-Kopfpanel im Editor** (2026-09-10).
  Der Galerie-Block trägt jetzt sichtbar in seinem Kopf, als was er
  in der Ausstellung erscheinen wird — nicht als wählbare Option,
  sondern als Statusanzeige, damit die Anzahl-Regel jederzeit
  ablesbar ist. Neuer Enum `App\Support\GalleryForm` (`BAND`,
  `KONTAKTBOGEN`, `SEQUENZ`) mit einer `resolve(count, sequence)`-
  Methode, die den Regel-Kern kapselt: 1–4 Bilder → Band, ab 5 →
  Kontaktbogen; `sequence=true` überschreibt beides. Editor-
  Kopfpanel und Reader-Rendering rufen dieselbe Methode, damit
  Anzeige und Ausspiel niemals divergieren. Der Kopf zeigt eine
  Status-Pille „Erscheint als …", einen Scope-Hint („1–4 Bilder"
  bzw. „ab 5 Bildern") und einen Formwechsel-Hinweis, wenn das
  nächste Bild oder das Löschen eines Bildes die Darstellung
  ändern würde („Ein weiteres Bild wechselt den Block auf
  Kontaktbogen — Einzelnachweise wandern in die Großansicht").
  Die Sequenz-Bühne bleibt eine redaktionelle Aussage und
  bekommt einen eigenen Livewire-Volt-Toggle
  („Die Reihenfolge ist die Aussage") mit inline-Save; alle
  anderen Formen sind Status, kein Knopf. `no_crop`-Bilder tragen
  im Editor-Raster ein Mono-Kürzel „NC" oben rechts an der
  Kachel, damit im Kontaktbogen sichtbar bleibt, welche Bilder
  eingepasst statt gefüllt gerendert werden. Locale-Keys:
  `gallery_form_panel_label`, `gallery_form_band`,
  `gallery_form_kontaktbogen`, `gallery_form_sequenz` (jeweils
  mit `_scope`-Kontext), `gallery_form_hint_next_kontaktbogen`,
  `gallery_form_hint_next_band`, `gallery_form_hint_stable`,
  `gallery_sequence_toggle_label`/`_hint`,
  `gallery_no_crop_marker`.

- **Q4-Etappe 6 · G6-1 · Darstellungs-Hinweise am Bild und am
  Galerie-Block** (2026-09-10). Fundament für den nach dem
  Galerie-Briefing gebauten Reader-Umbau (Band / Kontaktbogen /
  Sequenz nach Anzahl-Regel). Fünf neue Felder am `Image`-Model,
  ein Feld am `Gallery`-Model:
  - `images.no_crop` — Dokument, Scan, Karte: nie in eine Zelle
    beschneiden, immer einpassen. Opt-out für den Kontaktbogen.
  - `images.focus_x` / `focus_y` — Beschnitt-Fokus in Prozent
    (0–100), Null = Mitte. Wird im CMS per Klick auf das Vorschau-
    bild gesetzt, kein Zahlenfeld.
  - `images.intrinsic_width` / `intrinsic_height` — Original-
    Dimensionen in Pixel, beim Upload aus dem Bild gelesen. Der
    Reader kann damit die Bildfläche vor dem Laden reservieren
    (kein Cumulative Layout Shift).
  - `galleries.sequence` — redaktionelle Ansage, dass die Bilder
    als Pager-Bühne statt als Kontaktbogen gerendert werden sollen.
    Nur eine Richtung — gesetzt oder nicht.

  Migration `add_display_hints_to_images_and_galleries` mit
  sauberem `down()`. Bestandsdaten laufen mit den Defaults
  (`no_crop` = false, `focus_*` = null, `intrinsic_*` = null,
  `sequence` = false); der Reader-Renderer fällt zurück auf sein
  bestehendes Verhalten, solange die neuen Felder leer sind. Der
  `ImageService` liest die Dimensionen jetzt beim Upload aus
  (`getimagesize()`, mit `[null, null]`-Fallback für defekte oder
  nicht-Bild-Formate) und persistiert sie in allen drei Schreib-
  pfaden (`create`, `createFromDrop`, `update` mit neuem File).

- **Q4-Etappe 5 · Reader-Polish nach Design-Review 3** (2026-09-09).
  Der Multi-Page-Reader bekommt eine rechte Marginalspalte
  (`--side-col`, 330 px, sticky) mit einer Kapitel-TOC über die
  Abschnitte der aktiven Seite; ein IntersectionObserver markiert
  den Abschnitt am oberen Viewport-Drittel und rendert einen
  „HIER"-Marker sowie eine Fortschritts-Rail am linken Rand der
  Liste. Das dreispaltige Grid (Rail · Content · Marginalspalte)
  löst den kippenden Satzspiegel des Zwei-Spalten-Zwischenstands
  auf und aktiviert das im Handoff v4 vorgesehene Layout. Am
  Kapitel-Ende ergänzt ein Weitergang-Block Beitragen-Pille und
  „Nächstes Kapitel"-Card; das letzte Kapitel eines Projekts blendet
  zusätzlich „Erarbeitet von" ein (Aggregation aller
  `entry_credits`, drei Rollen-Spalten), damit der Editor:innen-
  Nachweis auch im Multi-Page dort landet, wo Leser:innen
  aussteigen. Fußzeile um eine vierte Spalte „Weiteres" erweitert
  (Bildnachweise · Barrierefreiheit · PDF-Download); die
  Ziel-Seiten Bildnachweise/Barrierefreiheitserklärung stehen als
  Backlog-Ticket in der Werkbank. Neue Locale-Keys:
  `reader_aside_label`, `reader_entry_toc_label`,
  `reader_next_chapter_label`, `reader_footer_more`,
  `reader_footer_credits_link`, `reader_footer_a11y_link`,
  `reader_footer_pdf_link`, `reader_footer_cite_publisher`. Mobile-
  Fallback: Marginalspalte kollabiert unter 1100 px, Fußzeile
  bricht auf zwei Spalten unter 960 px.

- **Q4-Etappe 5 · G-Fund · Reader-Fundament nach Design-Handoff v4**
  (2026-09-09). Antwort auf den Design-Review vom 09.09.: der Reader
  bekommt das im Handoff v4-Export vorgesehene Vokabular, und die
  redaktionelle Leistung wird sichtbar. Neues Projekt-Setting
  **Charakter** (`dokumentation` / `archiv` / `erzaehlung`, Default
  `dokumentation`) mit UI-Karte im Metadaten-Tab, drei Preview-Tiles
  je Charakter (Papier · Tinte · Akzent). Zusätzlich optionale
  **Akzent-Farbe** pro Projekt, die nur den Charakter-Primärton
  überschreibt (Logo-Kachel, „Etwas beitragen"-Pille, Zitat-Kante,
  aktiver Kapitel-Marker, Fokus-Ring) — Papier, Tinte und Rules
  bleiben vom Charakter. `public/css/reader.css` komplett auf die
  neun Handoff-Tokens pro Charakter umgestellt (via `data-char`),
  Typo-Skala 1:1 aus der Handoff-Tabelle (Source Serif 4 als Lese-
  familie im Default, IBM Plex Sans für Archiv, IBM Plex Mono für
  Meta-Labels), Lesemaß 660 px, kantig (keine Kartenradien, keine
  Schatten). Neue Kopfleiste 62 px nach Handoff E1a: Akzent-Kachel
  26 px + Projektname + Nav-Slots („Kapitel", „Über das Projekt")
  + Sprachwahl DE/EN + dauerhafte Pille „Etwas beitragen" im
  Akzent. Neue Fußzeile auf `--paper-3` mit 2-px-Oberkante: drei
  Spalten (Träger · Ausstellung · Rechtliches), Zitierhinweis für
  das Gesamtwerk mit generierter URL, Abschlusszeile „Erstellt mit
  crowdCuratio · Letzte Änderung". Fokus-Ring `2px solid
  var(--accent)`, Skip-Link. Alter `?colorAccent`-Query-Param-Weg
  entfällt.

  **Vier abgeleitete Ausgaben nachgezogen** (Design-Review Blocker
  2 — die redaktionelle Leistung wird sichtbar):
  - **Nachweiszeile am Bild**: Bildunterschrift links, Signatur/
    Rechte (Copyright · Origin) rechts in Mono. Mobil untereinander.
  - **Textblock-Credit**: schmale Mono-Zeile unter dem Absatz mit
    Copyright · Origin.
  - **Quellenblock am Eintragsende**: sammelt und dedupliziert alle
    Sources aus Text/Gallery/AV/QuoteBlock, mit Name/Titel/Bestand/
    Signatur, auf `--paper-2` gerahmt.
  - **Credit-Zeile am Eintragskopf**: `Recherche: … · Redaktion: … ·
    Stand MM/JJJJ · N Abbildungen · N Quellen`. Die Namen aus dem
    neuen `entry_credits`-Model, die zwei Zähler abgeleitet aus
    MediaContent-Aggregation.
  - **„Erarbeitet von" am Projektende** (nur One-Pager): drei
    Spalten (Recherche · Redaktion · Hinweise beigetragen), Namen
    projektweit aggregiert und dedupliziert.

  Neues Model `App\Models\EntryCredit` (`entry_id`, `role` als
  Enum `recherche`/`redaktion`/`hinweis`, `name`, `date`,
  `position`), Policy, Volt-Editor am Entry-Header (aufklappbare
  Karte mit Rolle-Dropdown, Namensfeld, Month-Picker, Reorder-
  gewichtiges Live-Save). Eager-Load in `Project::scopeWithEditTree`
  und `scopeWithPreviewTree` erweitert.

  Zwei weitere Reader-Umbauten mit dabei: der `<main>`-Wrapper
  bekommt ein `#cc-reader-main` als Skip-Link-Ziel; nested
  `<main>`-Elemente in der Multi-Page-View auf `<div>` bereinigt.
  Die Gallery-Blade rendert jetzt als CSS-Grid (`.cc-gallery-grid`),
  Slick/FA/GSAP-Includes waren im vorigen Zug schon raus.
  Reader-CSS als statisches `public/css/reader.css` mit
  inline-Design-Tokens (kein Vite-Manifest-Timing-Risiko).

  Folgezüge in `.werkbank/PLANS/Q4-ETAPPE-G-READER-UMBAU.md`:
  Detail-Design-Abgleich mit dem Designer, Leitbild + Startseiten-
  Sequenz (E1a), Kapitel-Opener + Rail-Fortschritt (E1b),
  Lightbox mit Metadaten (E1c), Eintrag als eigene Adresse (E1d),
  PDF reduziert (G7).

- **Q4-Etappe 5 · G1–G6 · Reader-Umbau (Multi-Page + Design-Handoff)**
  (2026-09-09). Der öffentliche Reader lernt zwei Modi und übernimmt
  das Design-Vokabular aus dem Handoff v4. Neues Projekt-Setting
  `reader_layout` (`one-page` | `multi-page`, Default `one-page`)
  mit UI-Karte im Metadaten-Tab entscheidet: **One-Pager** rendert
  wie bisher als Long-Scroll mit Chapter-Chips oben; **Multi-Page**
  redirected auf das erste Kapitel (`/preview/chapters/{chapter}`)
  und zeigt links eine sticky Sidebar mit nummerierter Kapitel-
  Liste — pro Kapitel ein eigener Deeplink. Beide Modi teilen ein
  gemeinsames `preview/layout.blade.php` (Head, Header, Sprach-
  Umschalter, Footer, Impressum-Kette einmal statt zweimal). Fünf
  wiederverwendbare Content-Partials unter `preview/content/*`
  (Text, Galerie, Audiovisual, Zitat, Daten-und-Fakten) plus ein
  Type-Dispatcher; der Reader-Body nutzt nur noch einen einzigen
  `@include` statt eines 130-Zeilen-Type-Switches. Die Copyright/
  Policy-Seite erbt jetzt ebenfalls vom gemeinsamen Layout (129 LoC
  Duplikation weg). Slick, Font-Awesome und GSAP-CDN-Includes im
  Web-Reader entfernt — Slick war dort nur toter CSS-Ballast (kein
  JS-Init im Web, nur im PDF), das FA-Sprach-Icon wird durch einen
  schlichten Mono-Kürzel-Button ersetzt, GSAP war unbenutzt. Neues
  responsives Gallery-Grid (`.cc-gallery-grid`, `<figure>` +
  `<figcaption>`, `loading="lazy"`) passt sich sowohl an die
  One-Pager- als auch an die schmalere Multi-Page-Content-Spalte an
  ohne Overflow. Skip-Link „Zum Inhalt springen" (Handoff-A11y).
  Reader-Body auf **Source Serif 4** (Bunny Fonts, DSGVO-freundlich,
  kein npm-Package), Headlines auf IBM Plex Sans, Meta-Labels auf
  IBM Plex Mono — alles über das neue statische
  `public/css/reader.css`-Bundle mit inline-Design-Tokens.
  Projekt-Farbe aus `?colorAccent` überschreibt die zentralen
  CSS-Variablen (`--color-primary`, `--color-brand-bar`, das
  Pastell-Band ableitet sich via `color-mix()`), Multi-Page-Sidebar
  farbet mit. Legacy `public/css/index.css` wird vom Reader nicht
  mehr geladen. PDF-Pipeline bleibt bis zum reduzierten Neubau in
  Etappe G · Schritt 13 auf dem Alt-Stand — im Redirect-Fall bleibt
  daher `?pdf=1` immer auf dem One-Pager. Neue Locale-Keys für
  Reader-Layout-Setting, Skip-Link, Sprach-Umschalter, Chapter-
  Navigation. Als Follow-ups notiert (in
  `.werkbank/PLANS/Q4-ETAPPE-G-READER-UMBAU.md`): Detail-Design-
  Abgleich durch den Designer und die Persistierung der Projekt-
  Farben als Projekt-Setting statt GET-Params.

- **Q4-Etappe 4 · F · Daten-und-Fakten-Block als eigener Content-Type**
  (2026-09-08). Neuer polymorpher Content-Type `App\Models\DataFactBlock`
  (Tabelle `data_fact_blocks`) mit zwei Layouts: **Steckbrief**
  (Default) rendert als semantische Definitionsliste
  (`<dl><dt><dd>`) für Personen-, Ort- oder Ereignis-Karten;
  **Tabelle** rendert als `<table>` mit `<thead>`+`<tbody>` und
  frei definierbaren Spalten, für Aufzählungen wie „Judenhäuser"
  (Jahr · Adresse · Ereignis). Der Redakteur schaltet inline
  zwischen beiden Layouts um; das jeweils nicht sichtbare Layout
  behält seine Daten (kein Datenverlust beim Vergleichen). Umschaltung
  läuft ohne Server-Roundtrip auf UI-Seite (Alpine `x-show` gesteuert
  von einem Livewire-Browser-Event aus dem Layout-Selector).
  `title`, `subtitle` und alle Zellen-Inhalte sind translatable
  (HasTranslations mit Locale-Maps in JSON). Titel und Untertitel
  über den bestehenden inline-editor, die Zeilen über zwei
  dedizierte Volt-Komponenten: `data-facts-rows-editor` für
  Steckbrief (Label · Wert mit Reorder + Delete) und
  `data-facts-table-editor` für Tabelle (Spalten-Definitionen
  mit Add/Remove + N-Cell-Zeilen mit Reorder + Delete). Voll
  angeschlossen: `DataFactBlockPolicy` (view/update/delete/comment),
  `HasComments` mit vier `comment.data-facts[.show|.save|.status]`-
  Routes, `HasRevisions` + `LogsActivity`, RevisionSubject-TYPES-
  Whitelist, RevisionRevertService-Whitelist,
  `MediaContent::dataFactBlock()`, Eager-Load in
  `Project::scopeWithEditTree` und `scopeWithPreviewTree`. Add-Bar
  bekommt eine fünfte Option „Fakten und Daten" mit dediziertem
  Block-Card-Typ (Tabellen-Icon). Reader- und PDF-Rendering pro
  Layout: DL für Steckbrief, HTML-Table für Tabelle; die
  Feinschliff-Optik folgt mit dem Multi-Seiten-Reader-Umbau
  (Etappe G). Test-Erweiterung: `data-facts`-Case in
  `ContentInsertionServiceTest`. 20+ neue Locale-Keys je Sprache.

  **Follow-ups mit dabei** (aus dem Smoke-Test aufgefallen): die
  Bildergalerie-Dropzone für leere Galerien war noch ein
  Legacy-Modal-Trigger — jetzt echte Drop+Klick-Zone mit direktem
  XHR-Upload gegen `gallery.images.drop`, identisch zur letzten
  Kachel im gefüllten Grid. Beide Dropzone-Reloads setzen
  jetzt ein URL-Fragment auf den Galerie-Block, damit der
  Browser nicht an den Seitenanfang scrollt; die Detail-View
  scrollt nach dem Alpine-Umschalten ein zweites Mal exakt auf
  den Block, weil die Y-Position sich zwischen Grid- und Detail-
  Ansicht verschiebt. Der `source-picker` schließt beim
  Außerhalb-Klick jetzt sauber (bisher nur ESC).

- **Q4-Etappe 4 · F · Zitat-Block als eigener Content-Type**
  (2026-09-08). Neuer polymorpher Content-Type `App\Models\QuoteBlock`
  (Tabelle `quote_blocks`) mit Feldern `text` (translatable),
  `speaker`, `date_text`, `kind` (`archivalie`/`interview`/
  `publikation`, nullable), `lang_original`, `text_original` (beide
  nullable, für übersetzte Zitate), `source_id` (nullable FK auf
  `sources`) und `locator` (Freitext-Fundstelle). Hängt polymorph
  über `media_content.content_type` am Entry und lässt sich per
  Inline-Add-Flow (Add-Bar bekommt vierte Option „Zitat") an
  jeder Position eines Entries einfügen — der neu angelegte
  Block ist leer und wird direkt im Editor über die bestehenden
  Volt-Kompomenten (rich-text-editor, inline-editor, source-picker)
  befüllt. Für das `kind`-Dropdown eine dezidierte Livewire-Volt-
  Komponente `quote-kind-selector`, damit der Wechsel inline ohne
  Redirect + Page-Scroll läuft. Aktionen (Verlauf, Kommentar,
  Löschen) sitzen wie bei den anderen Content-Blöcken in der
  Kopfzeile der Block-Card, Angaben-Status-Chip in der Fußzeile.
  Reader- und PDF-Rendering als Blockquote mit abgeleiteter
  Nachweiszeile (`{speaker}, {date_text} · {source.name}{,
  locator}`) — der Redakteur pflegt die Bausteine, die Zeile ist
  kein Eingabefeld. Optional erscheint darunter „Originaltext:
  …" mit `lang`-Attribut, wenn `text_original` gefüllt ist. Voll
  angeschlossen: Policy (`QuoteBlockPolicy` project-scoped über
  `OwnerScopedPolicy`), Comment-Kette (`HasComments`-Interface,
  `comments()`-MorphMany, 4 Routes `comment.quote[.show/.save/
  .status]`, ContentCommentController-Methoden, CommentableRoutes-
  Registry), Verlauf (`HasRevisions`-Trait, `LogsActivity`,
  RevisionSubject-TYPES-Whitelist, RevisionRevertService-
  Whitelist, `source_id` im Diff-Renderer als Source-Referenz
  aufgelöst), Eager-Load in Project::withEditTree und
  withPreviewTree. Neue Migrations: `create_quote_blocks_table`
  plus `data-history-field`-Wrapper an allen Editor-Feldern für
  den Diff-Overlay. Test-Erweiterung: `quote`-Case in
  `ContentInsertionServiceTest`. 15 neue Locale-Keys je Sprache.

- **Q4-Etappe 4 · C1 · Inline-Add-Flow für Content-Blöcke**
  (2026-09-08). Ersetzt die Bootstrap-3-Modal-Kette (contentModal,
  galleryModal, audiovisualModal + jQuery-Verkabelung `.addContent` /
  `.add-Text` / `.add-Image` / `.add-audio` / `.add-video`) durch
  eine inline sichtbare Add-Bar im Editor. Zwischen jedem
  Content-Block liegt ein schmaler Trenner („Hier neuen Inhalt
  einfügen"), am Ende leerer Entries eine dominante Anlege-Bar
  („Neuen Inhalt hinzufügen"); Klick öffnet ein Popover mit den
  drei Optionen **Text / Galerie / Audio-Video** (Bilder bleiben
  Sache der Gallery-Dropzone). Volt-Component
  `<livewire:content-add-bar>` mit Tastatur-Navigation, Focus-Trap,
  ARIA-Menu-Semantik und weicher Slide-Fade-Transition (350 ms
  cubic-bezier), reagiert auf `prefers-reduced-motion`. Persistiert
  über `ContentInsertionService::insertBlank(entryId, type,
  afterMediaContentId)` — legt einen leeren Block an und shiftet
  `media_content.position` transaktional, damit Insert-Between
  sauber möglich ist. Nach dem Anlegen scrollt die Seite direkt zum
  neuen Block (`#anchor_MediaContent_{id}` plus livewire:navigated-
  Listener im Layout, der zwei rAF-Ticks nach Livewires Scroll-
  Restoration greift). Analog dazu redirecten Chapter- und Entry-
  Anlage mit Fragment `#anchor_Chapter_{id}` bzw.
  `#anchor_Entry_{id}` — die zugehörigen Anker sind bereits im DOM.
  Legacy-Aufräumen: `contents/index|gallery|audiovisual.blade.php`,
  `projects/element.blade.php` (verwaister Bootstrap-3-Chapter/Entry-
  Anlege-Screen ohne Save-Backend) plus Route `/element` und
  `ProjectController::element` sowie die zugehörigen jQuery-Handler
  und `resetValues()` entfernt (netto ~430 LoC weniger). Migrations:
  `texts.text/origin/copyright` und `audiovisuals.link` nullable,
  damit Blanko-Blöcke sauber persistiert werden. Neue Tests:
  `ContentInsertionServiceTest` (6 Fälle inkl. Insert-Between und
  Insert-Before-First), `ChapterEntryAnchorRedirectTest`. 5 neue
  Locale-Keys je Sprache. Als Follow-up ausdrücklich offen und in
  einer Werkbank-Notiz dokumentiert: die Gutter-Handle-Affordanz
  aus Design-Briefing v4 Screen 05·5 (drei mögliche Deutungen —
  nur Add, Add+Move-Cluster, Move-Only) wartet auf Klärung mit
  dem Designer.

- **Q4-Etappe 3 · C0d · Quellenverwaltung pro Projekt**
  (2026-09-07). Neue Vollpage unter `/projects/{project}/sources`
  (gated auf `update`, als „Quellen"-Tab in der Projekt-Chrome-Bar
  verlinkt) mit Liste + Filter (Copyright/Origin) + Textsuche,
  Referenz-Zähler pro Row (Text + Image + AV), Detail-Editor
  (Name; plus `kind` / `title` / `holding` / `signature` nur bei
  `citation_depth=full`), Merge zweier Sources (Referenz-Umbiegung
  an Text/Image/AV in einer Transaktion, Alt-Row SoftDelete,
  `Log::info('sources.admin.merged')`) und SoftDelete mit
  pluralisierter Warnung bei bestehenden Referenzen
  (`sources.admin.deleted`-Event). 30 neue Locale-Keys je Sprache.

- **Q4-Etappe 3 · C0b · Zitier-Tiefe und Quellen-Pflicht pro
  Projekt konfigurierbar** (2026-09-07). Zwei neue Projekt-Settings
  aus dem AM-Meeting: `citation_depth` (`simple` — nur Name; `full`
  — plus `kind`/`title`/`holding`/`signature` an Sources) und
  `source_required` (Copyright/Origin an Content-Blöcken Pflicht
  oder nicht). Default nach Karl-Entscheidung: `simple` + `required`
  — die meisten Projekte brauchen keine strenge Zitier-Tiefe,
  Copyright/Origin bleiben aber weiterhin Pflicht.
  `Project::$fillable` + `casts` ergänzt, plus Helpers
  `usesFullCitationDepth()` und `requiresSources()`.
  `UpdateProjectRequest`/`StoreProjectRequest` und `ProjectData`
  reichen die Werte durch. Neue Karte „Zitier-Einstellungen" im
  Projekt-Metadaten-Editor (Radio-Buttons für die Tiefe, Checkbox
  für die Pflicht). Die Copyright-/Origin-Sternchen in `text-block`,
  `audiovisual-block` und `gallery/image-detail` sind jetzt bedingt
  (`@if ($project->requiresSources())`). Die Wirkung von
  `citation_depth=full` (Detail-Feld-Editor) landet in C0d. 9 neue
  Locale-Keys pro Sprache.

- **Q4-Etappe 3 · C0 · Erweiterung: Audiovisual auf Source-Rows +
  GUI-Wrapper + Picker-Scoping** (2026-09-07). Nachlauf zum
  C0-Kern-PR, im gleichen Branch. Drei Ergänzungen:
  * **Audiovisual-Umstellung.** Migration
    `add_source_scope_to_audiovisuals_table` ergänzt `copyright_id`
    und `origin_id` als nullable FKs auf `sources`. `Audiovisual`-
    Model hat neue Relations `copyrightSource()` und
    `originSource()`. `AudiovisualService` nimmt `SourceService`
    als Konstruktor-Dep; `create`/`update` legen Source-Rows
    projekt-scoped an und setzen die FKs. Legacy-Strings
    (`copyright`/`source`) werden bis zum Backfill mitgeschrieben —
    der Reader liest bevorzugt aus der Source-Relation.
    `audiovisual-block.blade` nutzt jetzt den `<livewire:source-
    picker>` statt zweier `<livewire:inline-editor>`. Der Angaben-
    Status-Check akzeptiert beide Wege (FK oder Legacy-String).
  * **AV-Backfill im Migrations-Assistenten.**
    `SourceMigrationService` erweitert um
    `pendingAudiovisualBackfillCount` und
    `backfillAudiovisualsForProject`. Kommandobzw. GUI-Report zeigt
    `av_pending` und `av_backfilled`. `Log::info('sources.migration.
    av_backfilled')` pro Zeile.
  * **GUI-Wrapper `/admin/sources/migration` (C0c).** Livewire-Volt-
    Sicht (Admin-only) mit Projekt-Liste, Dry-Run-Report,
    Confirmation-Dialog, History der letzten 20 Läufe aus
    `sources_backup`. Verlinkt in Rail und Navi-Header. `rows` als
    Column-Alias auf `row_count` umbenannt (MySQL-8-Reservat).
  * **Picker scopet auf Projekt.** Der `source-picker` filtert
    Vorschläge und Dedup jetzt auf `project_id`, damit Alt-Rows
    fremder Projekte nicht mehr im Autocomplete auftauchen.
    `createAndSelect` legt neue Rows project-scoped an.
    `currentName` nutzt `loadMissing`, damit
    `Model::shouldBeStrict()` nicht wegen Lazy-Load meckert.
  * **Migrations-Cleanup.** Migrierte Alt-Rows werden nach der
    Referenz-Umbiegung SoftDeleted, wenn kein anderes Projekt sie
    noch referenziert — verhindert Duplikate in der
    Nach-Migration-Anzeige.
  * **Backlog-Notizen:** C0d (dedizierte Quellenverwaltung pro
    Projekt, Vorarbeit Zitat-Block), UX-BLK-01 (Angaben-Status-
    Chip live aktualisieren — betrifft alle Block-Types).

- **Q4-Etappe 3 · C0 · Quellen-Datenmodell projekt-scopen (8a + 8b)**
  (2026-09-07). Vorarbeit für den Zitat-Block und den Reader-Umbau
  aus Phase 6. Zwei zusammengehörige Änderungen an derselben
  Datenkette:
  * **8a · Schema-Erweiterung.** Migration `add_project_scope_to_
    sources_table` ergänzt `project_id` (FK auf `projects`,
    nullable, `nullOnDelete`), `kind` (32-Zeichen-String für die
    Verzeichnis-Klassifikation `archivalie/publikation/interview/
    abbildung/sonstige`) sowie `title`, `holding`, `signature` als
    nullable Strings. Der bisherige `type`-Wert (`Copyright` /
    `Origin` = Rolle am Content-Block) bleibt unangetastet.
    `SourceService::findOrCreateId($value, $type, ?int $projectId)`
    schreibt Namen jetzt sauber über `HasTranslations::set
    Translation` statt manuellem `json_encode`; die Suche scopet
    auf `project_id`, wenn übergeben, sonst nur auf projektlose
    Alt-Rows. Der tote `sources.content_id`-Eintrag im
    `$fillable` des Source-Models ist raus. Aufrufer in
    `TextService`, `ImageService` und `RevisionRevertService`
    reichen die Projekt-ID via `Text::project()` / `Image::
    project()` / `Entry::project()` bzw. `Gallery::project()`
    durch.
  * **8b · Migrations-Assistent.** Neue Tabellen `sources_backup`
    (Rollback-Anker, gruppiert per `run_key`) und eine
    `original_id`-Selbst-FK auf `sources` (Rückverweis alt→neu).
    Neuer `SourceMigrationService` mit `candidatesFor`,
    `dedupCandidates`, `classifyKind`, `looksLikeFreetext` und
    `runFor($projectId, $dryRun)`. Artisan-Command `sources:migrate
    --project=X [--dry-run]` ruft den Service, gibt Report zum
    Terminal und schreibt bei Commit Backup, neue projekt-scoped
    Zeilen mit `original_id`-Rückverweis, biegt Text- und
    Image-Referenzen um und loggt `sources.migration.merged` pro
    Aktion. Umsetzung ist idempotent (zweiter Lauf findet keine
    Kandidaten mehr).
  * Feature-Tests decken beide Ebenen ab: Projekt-Scope-Semantik
    von `findOrCreateId` (vier Cases) und der volle Migrations-
    Fluss (Kandidaten-Bildung, Dry-Run, Commit mit Backup und
    Referenz-Umbiegung, Idempotenz, kind-Regel, Freitext-
    Heuristik).
  * **Backlog-Notizen** in `TODO.md`: **C0b** (Zitier-Tiefe und
    Quellen-Pflicht pro Projekt konfigurierbar — aus Kunden-
    Feedback 07.09.2026, blockt Zitat-Block) und **C0c** (GUI-
    Wrapper für den Migrations-Assistenten, wenn Redaktion selbst
    durchklicken soll).

- **Q4-Etappe 2 · I5 · chapters/_canvas Sub-Extraktion + I11 · AJAX-
  Prefix** (2026-08-27). Die 1.377-LoC-`chapters/_canvas.blade.php` ist
  auf 414 LoC reduziert; die drei Content-Type-Zweige (Text /
  Audiovisual / Gallery) rufen jetzt Sub-Components unter
  `components/content/*` (`text-block`, `audiovisual-block`,
  `gallery-block`). Der Gallery-Block selbst zerlegt die zwei
  Bild-Iterationen (Kachel-Grid und Detail-Editor) in
  Sub-Sub-Components (`components/content/gallery/image-tile`,
  `image-detail`); Alpine-State und -Methoden fürs Drag&Drop und den
  Detail-Wechsel leben weiter im x-data des Gallery-Blocks — die
  Sub-Components sitzen im DOM-Scope davon, damit `pickedId`,
  `editingImageId`, `pick()`, `drop()`, `enterDetail()`, `moveBy()`,
  `exitDetail()` transparent bleiben. Route-Namen, Livewire-Bindings,
  `data-history-*`-Attribute unverändert. Vier interne AJAX-Endpunkte
  (Locale-Switch, Theme-Switch, Initials-Check, Source-Autocomplete)
  laufen jetzt unter dem Prefix `/api/internal/` als
  Weichenstellung für eine spätere Phase-6-`/api/v1/`-Struktur;
  Route-Namen bleiben identisch, alle Aufrufer nutzen `route()` und
  ziehen automatisch mit. `POST /profile/schedule-deletion` und
  `cancel-deletion` bleiben auf `/profile/*`, weil sie Form-Submits
  mit Redirect-Antwort sind, keine JSON-Endpunkte.

- **Q4-Etappe 2 · I7 · ContentController-Split** (2026-08-27). Der
  `ContentController` (784 LoC, mischte Text/Image/Gallery-CRUD +
  acht praktisch identische Kommentar-Methoden + Autocomplete +
  Translation-Helper) ist auf 85 LoC reduziert und enthält nur noch
  den Source-Autocomplete-AJAX-Endpunkt. Vier neue Controller nach
  Content-Type: `TextBlockController` (save, edit, destroy, reset),
  `ImageBlockController` (save, edit, destroy) und
  `GalleryBlockController` (reorder, drop, save, edit, destroy). Der
  neue polymorphe `ContentCommentController` bündelt die früheren
  Kommentar-Methoden über `App\Support\ContentTypeRegistry` — dünne
  öffentliche Wrapper pro Type (Text/Image/Gallery) halten die
  Route-Namen unverändert, der Body ist zu vier Kern-Methoden
  (`storeComment`, `getComments`, `saveCommentAction`, `setStatus`)
  dedupliziert; `listComments` und `updateCommentStatus` leben
  ebenfalls dort. Die frühere `translateField`-Logik ist als
  `SourceTranslationService` extrahiert und wird von beiden
  Save-Pfaden (Text/Image) genutzt. 25 Routen auf neue Controller
  umgemappt, alle Route-Namen unverändert. Die tote Route
  `save.translation.text` (Ziel war `private`, kein Frontend-
  Aufrufer) ist entfernt. `ContentControllerTranslationTest` auf die
  neuen Aufhänger umgebogen; Text-Body-Translation läuft weiter über
  Reflection auf die private `TextBlockController::saveTranslated
  Text`, Source-Translation direkt über den neuen Service.

- **Q4-Etappe 2 · Controller-Refactoring (I6 · ProjectController-Split)**
  (2026-08-27). Der `ProjectController` (1.165 LoC, 8 Concerns) ist auf
  593 LoC halbiert. Vier neue Controller nach Ressourcen-Schnitt:
  `ProjectPreviewController` (Preview + PDF-Download),
  `ProjectCommentController` (Top-Level-Kommentare, save, status),
  `ProjectPermissionController` (setPermission, givePermission,
  checkEmail, deleteUserFromProject) und `ProjectTranslationController`
  (Übersetzen-Sicht + Bulk-Save). Die ~85-LoC-`buildOutdatedTranslation
  Map`-Logik ist als eigener `TranslationOutdatedMapService`
  extrahiert. Route-Namen bleiben identisch (12 Routen umgemappt), keine
  Blade-`route()`-Anpassungen nötig. Der bisher orphan-Endpunkt
  `deleteUserFromProject` (kein Frontend-Aufrufer, kein Authorize-Gate)
  bekommt eine echte UI-Anbindung: neuer „Aus Projekt entfernen"-Button
  im Detail-Panel der `project-permissions`-Volt-Sicht mit
  Confirmation-Dialog (sichtbar nur bei `!$isOwner` und `update`-Recht),
  Server-side Gate `authorize('update', $project)` in
  `deleteUserFromProject` — dokumentiert als Sicherheits-Nachtrag
  (SEC-Q4-01) im `.werkbank/REVIEW/Q3-abschluss/`-Ordner. Sieben neue
  Locale-Keys (`permissions_remove_user_*`).

- **Q4-Etappe 1 · Sanierungs-Vorlauf** (2026-08-27). Vier kleine
  Splits als geschlossener Zug: Der neue `ProfileController` bündelt
  die Self-Service-Endpunkte (`profile`, `updateProfile`,
  `updatePassword`, `updateLocale`, `updateTheme`, `checkInitials`,
  `scheduleDeletion`, `cancelScheduledDeletion`); der `UserController`
  ist ab jetzt rein Admin-User-Management. Die Route-Namen bleiben
  gleich, daher keine Blade-`route()`-Anpassungen nötig. Die
  Livewire-Volt-Sicht `project-permissions` verliert die drei
  größten Blade-Blöcke (Sidebar, Toggle-List, Invite-Modal) an
  neue Sub-Components unter `components/projects/permissions/*`; die
  drei Preset-Masken (Editor/Reviewer/Reader) leben zentral in der
  neuen Support-Klasse `App\Support\PermissionPresets` statt in zwei
  Duplikaten. Neue `App\Http\Middleware\LogContext` reichert jeden
  Log-Eintrag mit `request_id` und (wenn eingeloggt) `user_id` an —
  Prod-Log-Grouping für die frischen `account.deletion.*`-Events wird
  sofort nutzbar, `X-Request-Id`-Header wird respektiert bzw. neu
  vergeben und im Response gespiegelt. Vorräumung nebenbei: der tote
  `commentModal`-Rumpf in `chapters/index.blade.php` (kein Aufrufer
  seit dem Umzug auf die Livewire-Kommentar-Komponenten) ist raus,
  zusammen mit den zugehörigen JS-Handlern `enableButton()` und
  `#IdProjectComment`-Set.

- **Konto-Löschung — Nachreview + Scheduler** (B2 · 2026-08-27).
  Nachreview auf B2 gehärtet: `AccountDeletionService` loggt jede
  State-Transition strukturiert als `account.deletion.*`, `purgeExpired`
  läuft je User in eigener Transaktion mit `lockForUpdate` und
  Re-Check (schließt eine Race gegen Owner-Rücktransfer),
  `schedule()` ist idempotent — ein zweiter Aufruf verlängert die
  Grace-Period nicht. Die Owner-Handover-Validierung liegt jetzt als
  Rule (`HasHandoverForEveryOwnedProject`) im FormRequest; der
  Controller-Cross-Check ist raus. `users:purge-scheduled` ist als
  täglicher Scheduler-Eintrag in `routes/console.php` verkabelt.
  Ops-Voraussetzung `* * * * * php artisan schedule:run` steht jetzt
  im Deploy-Runbook. ADR-0031 dokumentiert die DSGVO-Löschstrategie.

- **Q3-Abschluss-Nachlauf: Domain-Exception + Verlauf-Reset extrahiert**
  (2026-08-27). Zwei Blocker aus dem Gesamt-Architektur-Nachreview
  im selben PR abgeräumt. `AccountDeletionService` wirft jetzt eine
  fachspezifische `App\Exceptions\AccountDeletion\HandoverMissingException`
  statt generischem `RuntimeException` — Log-Filter, Sentry-Grouping und
  Test-Assertions bleiben spezifisch. Der Verlauf-Wiederherstellen-
  Endpunkt (`ProjectController::resetValue`, vorher ~78 LoC inline) läuft
  jetzt über den neuen `RevisionRevertService`; die Content-Whitelist
  liegt als `REVERTIBLE_MODELS`-Konstante am Service, der Controller
  schrumpft auf Request-Gate + Authorize + Delegate.

- **Konto-Löschung mit 30-Tage-Frist** (B2 · 2026-08-26). Neue Karte
  im Profil, über die eingeloggte User ihre Konto-Löschung selbst
  anmelden. Owner müssen ihre Projekte vorab an einen anderen User
  übergeben — der Request lehnt die Anmeldung ohne Übergabe ab und
  gibt das fehlende Projekt als Validierungsfehler zurück. Innerhalb
  der 30 Tage bleibt der Account voll nutzbar; beim Login zeigt das
  Profil ein Reaktivierungs-Banner mit „Löschung zurücknehmen". Neuer
  `AccountDeletionService` bündelt Schedule, Cancel und die endgültige
  Soft-Löschung; ein Artisan-Command `users:purge-scheduled` (mit
  `--dry-run`) räumt abgelaufene Konten und ist als täglicher Cron
  vorgesehen. Migration ergänzt `deletion_scheduled_at` und
  `deletion_reason` auf `users`. DSGVO-konformer Grace-Zeitraum ist
  als Konstante `User::DELETION_GRACE_DAYS` zentralisiert.

- **Verlauf-Panel für Gallery- und Image-Metadaten voll verkabelt**
  (A7 · 2026-08-21). `chapters/_canvas.blade.php` bekommt an Gallery,
  Image-Detail-Zeile und den vier Bild-Feldern (`alt`, `description`,
  `origin`, `copyright`) die passenden `data-history-subject`- und
  `data-history-field`-Attribute — die Diff-Overlays des Verlauf-
  Panels landen jetzt an der richtigen Stelle. Bild-Detail-Header
  hat einen eigenen Verlauf-Trigger. `origin`/`copyright`-Diffs
  werden im Panel als Source-Namen statt IDs gerendert (neuer
  `resolveSourceLabel`-Helper in `history-panel-list`). Der Marker
  am Verlauf-Icon leuchtet erst ab v2 — die Anlage-Revision (v1)
  zählt nicht als Historie; die Zahl-Badge zeigt die Zahl der Edits.

- **Editor-Chrome als eigene Komponente, Editor-Split** (I1 ·
  2026-08-21). Der Chapter-Loop mit Kapitel-Kopf, Entry-Rendering
  und Content-Block-Karten sitzt jetzt in `chapters/_canvas.blade.php`
  — `chapters/index.blade.php` ist von 2.650 auf ~1.220 Zeilen
  geschrumpft. Der Sticky-Header mit Breadcrumb, Tabs, Publish-
  Button und ⋮-Menü kommt aus der neuen `<x-projects.chrome>`-
  Komponente; die drei Sichten `chapters/index`, `projects/create`
  (Metadaten) und `projects/permissions` teilen sie sich mit
  optionalem `actions`-Slot.

- **Entry-Add-Modal auf Alpine, jQuery-Verkabelung raus** (I1 ·
  2026-08-21). Der Entry-Modal war über ~50 Zeilen jQuery in
  `chapters/index.blade.php` und `resources/js/modal-wire.js` an
  seine Trigger verkabelt — der Persona-Smoke vom 15. August hatte
  den Flow mehrfach reproduzieren müssen wegen `chapterId`-Reset-
  Konflikten zwischen Chapter- und Entry-Modal. Neu in
  `entries/_add-modal.blade.php`: Alpine-Root, hört auf ein
  `entry-modal:open`-CustomEvent, rendert die v7-Modal-Struktur,
  Quill-Content wird im `@submit`-Handler in ein Hidden-Feld
  geschrieben. Der frühere Ordner `Entry/` (Großschreibung) ist
  weg — Case-Kanonisierung auf `entries/` für Windows-Freundlichkeit.

- **Auth- und User-Anlage-Altlasten aufgeräumt** (B12 · 2026-08-20).
  Der `/register`-Endpunkt aus dem alten `RegisteredUserController`
  ist in `UserController::create/store` konsolidiert; die alte
  `auth/register.blade.php` ist weg, die neue `users/create.blade.php`
  ist v7-mustergerecht (analog `users/edit`). `/register` bleibt eine
  Release-Iteration lang als 301-Redirect auf `/users/create`. Ein
  neues `layouts/guest.blade.php` extrahiert das Split-Layout aus der
  Login-Sicht — Login, Passwort-vergessen, Passwort-zurücksetzen,
  E-Mail-Bestätigen und Passwort-bestätigen liegen jetzt gemeinsam
  darauf. Die Spatie-Welcome-Notification-Sicht folgt dem gleichen
  Muster. Die vier Fehler-Sichten (403/404/419/500) wählen ihr Layout
  dynamisch — authenticated bleibt Rail-basiert, unauthenticated
  bekommt ein neues minimales `layouts/error-guest.blade.php` ohne
  Rail und Editor-Chrome. Toter `user.info`-Endpunkt aus dem
  Vor-Livewire-Berechtigungs-Flow (`ProjectController::inviteUserForProject`)
  gelöscht.

- **Admin-User-Edit auf Design v7** (B1 · 2026-08-20). Blade-Rewrite
  von `resources/views/users/edit.blade.php` nach dem Profil-Muster:
  sr-only `<h1>` als Landmark, zwei `<section>`-Karten mit `<h2>`
  (Person, Rolle & Rechte), Sticky-Save-Footer als `role="region"`
  mit „Keine offenen Änderungen"/"Änderungen offen"-Pending-Label
  und `submitting`-State. Der Admin-Toggle steuert per Alpine
  `x-show` die Rollen-Sektion — jQuery-Handler weg. Rollen-Select
  liefert die drei Nicht-Admin-Rollen mit übersetzten Labels statt
  Spatie-Rohwerten. Passwort-Bereich ist raus; Admin setzt kein
  Passwort direkt (läuft über den Profil-Self-Edit-Pfad).

- **Einheitliches Save-Feedback** (Q3-Politur 2026-08-20 / UX-02,
  UX-08, LIVE-UX-01). Nach jedem Blur-Save in der Übersetzen-Sicht
  und in Inline-Editoren (Chapter- und Entry-Titel/Subtitle)
  erscheint 1,5 s ein „✓ Gespeichert"-Chip neben dem Feld. Die
  Sticky-Save-Buttons in Profil, Passwort-Karte und Metadaten
  wechseln während des Submits auf „Speichern …" und sind
  disabled, damit Doppel-Klicks nicht durchrutschen.

- **Screenreader-Landmarks in der Metadaten-Sicht** (Q3-Politur
  2026-08-20 / LIVE-UX-02, LIVE-UI-01). Sr-only `<h1>` als
  Ausgangs-Landmark, jede Feld-Karte ist eine `<section>` mit
  eigenem `<h2>`. Der Save-Footer verzichtet auf den letzten
  Speicherzeitpunkt und zeigt stattdessen live „Keine offenen
  Änderungen" bzw. „Änderungen offen — Speichern nicht vergessen."
  analog zum Profil-Muster.

- **Rollen-Chip-Tooltips und Editor-Hinweis für Nicht-Editoren**
  (Q3-Politur 2026-08-20 / UX-06, B-K-04, P-08). Rollen-Chips im
  Profil und in der Berechtigungs-Sicht bekommen einen `title` mit
  einer einzeiligen Erklärung ("Kann lesen und kommentieren." /
  „Kann Inhalte bearbeiten und hinzufügen." / …). Über dem Editor
  steht für Reader/Reviewer ein einzeiliger Hinweis, was ihre Rolle
  darf — der bisher fehlende Kontext, warum keine Save-Aktion da
  ist.

- **Diff-Banner mit Fassungs-Referenz** (Q3-Politur 2026-08-20 /
  UX-04). Der Vergleichs-Modus im Editor zeigt jetzt „Vergleich mit
  v9 · Autor" im Banner-Kopf — der `revision-selected`-Event trägt
  `revisionVersion` und `revisionAuthor` mit, `history-diff.js`
  rendert den Text daraus.

- **Verlauf-Trigger mit Zahl-Badge** (Q3-Politur 2026-08-20 /
  UX-11). Analog zum Kommentar-Trigger zeigt der Verlauf-Icon-
  Button die tatsächliche Anzahl der Fassungen als Badge. Der
  Legacy-Punkt bleibt als Fallback, wenn die Zahl nicht bekannt
  ist.

- **Live-Blur-Check für das Kürzel-Feld im Profil** (Q3-Politur
  2026-08-20 / UX-01). Neuer POST-Endpunkt
  `/profile/check-initials` liefert `{blocked, message, suggestions}`
  als JSON. Nach dem Blur wird die Kürzel-Sperrliste sofort geprüft
  und Vorschläge angezeigt, ohne dass der Server-Save auf Fehler
  laufen muss. Der Server-Validator läuft parallel weiterhin beim
  Save.

- **Panel-Fokus-Return und Escape-Handling für das Invite-Modal**
  (Q3-Politur 2026-08-20 / A11Y-03, A11Y-04). Verlauf- und
  Kommentar-Panel merken sich beim Öffnen das zuvor fokussierte
  Element und geben den Fokus beim Schließen dorthin zurück; beim
  Öffnen wandert der Fokus auf den Schließen-Button. Das Invite-
  Modal in der Berechtigungs-Sicht schließt auf Escape und setzt den
  Fokus beim Öffnen auf das E-Mail-Feld.

- **`<x-icon name="…">`-Komponente auf Lucide-Basis** (Phase
  5-D.2). Neue anonyme Blade-Component wraps `blade-ui-kit/blade-
  icons` und `mallardduck/blade-lucide-icons` und akzeptiert
  auch Bootstrap-Icons-Altnamen (`bi-pencil`) über ein
  `config/icon-mapping.php`. Größen sind auf Tailwind-Utility-
  Stufen 4/5/6 (16/20/24 px) fest, `aria-hidden` ist Default,
  `decorative=false` schaltet auf `role="img"` und `aria-label`.

- **App-Shell aus Rail + Sidebar-Panel + Canvas** (Phase 5-D.3).
  Zwei neue Layout-Komponenten: `<x-layout.rail>` (60 px, dunkles
  Chrome, aktive Route via `aria-current` und 3-px-brand-bar-
  Left-Border, Utility-Zone unten mit Save-Punkt/Marken-Toggle/
  Sprache/User-Menü) und `<x-layout.sidebar-panel>` (280 px,
  paper-0, Kopf-Slot mit Mono-Caps + Titel, Body-Slot für
  Struktur-Baum oder Sekundär-Nav). Alt-Slot `log` mappt auf
  `panel` für 5a/5b-Rückwärtskompat.

- **Redesignte Projektliste** (Phase 5-D.4). Filter-Chips mit
  Status-Zählern, CSS-Grid-Tabelle (2.4fr 1fr 1.3fr 1.2fr 0.7fr)
  mit Thumbnails, Untertiteln „N Kapitel" (via `withCount` im
  `ProjectPermissionService`), Status-Badges als Pills mit
  Punkt-Glyph, Initialen-Avataren und rechtsbündigen Aktions-
  Icons.

- **Editor-Chrome mit Brotkrumen, Segmented Control und
  Publish-Button** (Phase 5-D.5). Neue `<x-ui.segmented>` für den
  Modus-Wechsel „Bearbeiten · Übersetzen · Metadaten" mit
  ARIA-`role="tablist"` und Kachel-Look. Publish-Primary rechts,
  ⋮-Menü rechts daneben (Export + Löschen). Sticky an der
  Canvas-Oberkante mit `backdrop-blur`, damit Kontext beim
  Scrollen sichtbar bleibt.

- **`<x-ui.block-card>` für Text/Bild/Galerie/Audio/Video**
  (Phase 5-D.6). Typ-Tag oben links als Pill (Icon + Label),
  Aktions-Slot oben rechts, `editing`-Prop schaltet auf 2-px-
  brand-bar-Rand plus „· wird bearbeitet"-Suffix. `save-slot`-
  Prop hängt einen Pro-Block-Status-Chip an, der aus dem
  Alpine-Store `saveStatus.blocks` gerendert wird.

- **Rich-Text-Editor als Progressive-Disclosure** (Phase 5-D.6b
  P1.3). Die Quill-Toolbar ist per Default versteckt und wird nur
  bei Editor-Fokus sichtbar; der aktive Editor bekommt zusätzlich
  einen 2-px-brand-bar-Rand. Unter 600 px Card-Breite fallen
  List/Link/Clean-Gruppen weg (Container-Query).

- **`<x-ui.media-placeholder>` mit Streifenmuster** (Phase 5-D.6b
  P3.14). Leere Bild-, Audio-, Video- und Galerie-Blöcke rendern
  ein diagonales 10-px-Streifenmuster in Line-100/Paper-50 plus
  Icon + Hint-Text in einer paper-0-Karte. Passende Default-
  Seitenverhältnisse pro Typ (4/3, 4/1, 16/9), Overrider via
  `aspect`-Prop.

- **Vollständiger Sidebar-Tree** (Phase 5-D.6b P2.8). Kapitel-
  Nummerierung „1 · Kapitelname", Klapp-Chevrons pro Kapitel mit
  Alpine-`x-collapse`, aktive Kante mit `tint-bg` und 3-px-Left-
  Border via `::before`, „+ Eintrag hinzufügen" pro Kapitel und
  „+ Kapitel hinzufügen" am Tree-Ende als schmaler Ghost-Trigger.

- **Speicher-Feedback pro Block** (Phase 5-D.6b P2.12). Alpine-
  Store `saveStatus` um eine `blocks`-Map erweitert; Slot ist
  `{Model}-{id}`. Save-Payload (`model`, `id`) aus dem Inline-
  Editor wird über die Bridge in den passenden Slot geschrieben,
  Auto-Fade nach 10 s. Globale Rail-Punkt-Anzeige bleibt.

- **Redesignter Login-Screen** (Phase 5-D.7). Split-Layout mit
  520-px-Marken-Panel links (fest `bg-ink-900`, unabhängig vom
  Theme) und Formular-Panel rechts mit Sprach-Select, Titel
  „Anmelden", „Angemeldet bleiben", roter Primary-Button.
  Duzen-Konvention konsequent. Nach Login wird auf `/projects`
  weitergeleitet.

- **Locked-Pattern für rollen-bedingte Sperren** (Phase 5d.1 +
  5d.2). Neue Props `:locked` und `:lockedReason` auf
  `<x-ui.button>` rendern den Button sichtbar-aber-gesperrt mit
  `aria-disabled="true"` (ohne native `disabled`, damit Fokus
  und Tooltip erreichbar bleiben), Schloss-Icon links vom Label
  und `.is-disabled` als CSS-Anker. Für rohe `<button>`-Tags im
  Bestand gibt es die Blade-Direktive `@disabledIf($condition,
  $reason)`, die aria-disabled, `title` und `data-locked="1"`
  in einer Zeile spraegt. Load-Time-Runtime-Helper unter
  `App\Support\LockedButton::attributes`.

- **Berechtigungs-Sicht als Screen 3B** (Phase 5d.4). Volt-
  Komponente `<livewire:project-permissions>` unter
  `/projects/{id}/permissions`. Split-Layout mit Mitarbeitenden-
  Sidebar links (Avatar, Name, Rolle klein) und Detail rechts
  (großer Avatar, Rollen-Vorlage-Buttons Reader/Reviewer/
  Editor/Owner, sechs Permission-Toggle-Karten:
  Bearbeiten, Hinzufügen, Löschen, Veröffentlichen, Kommen-
  tieren, Einladen). Klick auf einen Rollen-Button setzt die
  Toggles auf die Rollen-Standardbelegung (siehe
  `RoleTableSeeder`); danach kann individuell abweichen. Owner
  hat alle sechs implizit an und ist gesperrt (Owner-Rechte
  hängen an `project.user_id`, nicht am Pivot). Löst die alte
  Modal-Kaskade in `projects/create` (3 Modals, Session-
  basierter Zwei-Schritt-Flow) ab.

- **`<x-ui.save-bar>` für Batch-Änderungen** (Phase 5d.5).
  Sticky-Bar am unteren Rand, taucht bei
  `@if($this->isDirty)` auf (Livewire-3-`#[Computed]`) und
  bietet Verwerfen + Speichern. Aktuell in der Berechtigungs-
  Sicht verkabelt, wiederverwendbar für andere Batch-Formulare.
  Karl-Entscheidung 2026-08-15: expliziter Save-Button statt
  Undo-Toast.

- **Redesignte Nutzer:innen-Liste** (Phase 5d.3).
  `resources/views/users/index.blade.php` folgt der 5-D.4-
  Handschrift: Kopfleiste mit Titel + Suche + „Neue:r
  Nutzer:in", Filter-Chips nach Rolle mit Zählern (Admin bekommt
  den Danger-Chip-Ton als visueller Anker), CSS-Grid-Tabelle
  mit Avatar-Initialen, Rollen-Chip, Status-Spalte („Einladung
  ausstehend" mit Clock-Icon), 44-px-Icon-Buttons für Edit /
  Resend-Invitation / Delete. Löst die alte
  Bootstrap-`.table`-Sicht mit DataTables-Init ab.

- **User-Menü mit drittem Menüpunkt** (Phase 5d.6). Der Avatar-
  Dropdown in der Rail bekommt „Passwort ändern" zwischen
  „Mein Profil" und „Abmelden" (Anker auf die bestehende
  Profil-Sicht). Semantik wird auf `role="menu"`, `role="menu-
  item"` und `role="separator"` gehoben, Icons neben den
  Labels.

- **Einheitlicher Projekt-Tab-Balken** (Phase 5d.4-Followup).
  Neue Blade-Komponente `<x-projects.tabs :project active>`
  rendert vier Segmente (Bearbeiten · Metadaten · Übersetzen ·
  Berechtigungen) auf Editor, Metadaten, Übersetzen und
  Berechtigungen-Sicht. Der Berechtigungen-Tab erscheint nur
  für User mit `invite`-Recht auf dem Projekt.

- **`ProjectPolicy::invite`** (Phase 5d.4-Hotfix). Neue Method
  gibt Owner und Eingeladenen mit `invite`-Permission Zugriff
  auf die Berechtigungs-Sicht; Admin greift wie bisher via
  `before()`. Ersetzt die dreifache Inline-Bedingung in
  `projects/create.blade.php` durch eine Policy-Method.

- **Inline-Editor als Volt-Komponente** (Phase 5c). Neue
  `<livewire:inline-editor>` mit drei Modi (Text-Input, Textarea,
  Select-Dropdown), Save via `wire:model.blur` plus 1,5-s-Debounce.
  Feldwerte werden project-scoped autorisiert und gegen übergebene
  Laravel-Rules validiert; Fehler-Zustand rendert `aria-invalid`
  und `aria-describedby` auf das Input.

- **Rich-Text-Editor mit Quill-Bridge** (Phase 5c). Eigene
  `<livewire:rich-text-editor>`-Komponente mit Alpine-Bridge, die
  Quill auf einem `wire:ignore`-Container mountet — Caret-Position
  bleibt beim Auto-Save erhalten. HTML-Änderungen laufen debounced
  über die gleiche Save-/Autorisierungs-Pipeline wie der Inline-
  Editor. Löst den bisherigen Quill-im-Modal-Flow für Text-Content
  und alle Beschreibungs-Felder (Kapitel/Abschnitt/Galerie) ab.

- **Audiovisual-Player und Audio-Uploader inline** (Phase 5c). Der
  `<livewire:audiovisual-player>` rendert audio-Tag oder iframe je
  nach Type und lädt sich beim `saved`-Event neu — Typ-Wechsel
  audio↔video greift sofort. Bei type=audio ersetzt ein
  `<livewire:audio-uploader>` das Link-Feld und nimmt Dateien über
  `WithFileUploads` entgegen. MIME-Whitelist
  (audio/mpeg, mp4, wav, ogg, x-m4a) und 20-MB-Limit spiegeln die
  Store-Route.

- **Source-Picker mit Live-Autocomplete** (Phase 5c). Neue
  `<livewire:source-picker>`-Komponente ersetzt den Bootstrap-3-
  Typeahead im alten Modify-Modal für Copyright und Quelle an
  Text- und Image-Modellen. Klick auf den Chip öffnet einen Text-
  Input mit debouncetem Live-Filter gegen die `sources`-Tabelle
  (nach `type`), Auswahl per Klick oder Enter, „+ Neu anlegen:
  '…'"-Aktion legt eine fehlende Source direkt an und verknüpft
  sie. Case-insensitive Duplikat-Prüfung verhindert Doubletten.

- **Auto-Save-Indikator im Header** (Phase 5c). Alpine-Store
  `saveStatus` hört auf `saved`, `save-failed` und `save-started`
  vom Inline-Editor und rendert einen Status-Text rechts von den
  Header-Menüs („speichert…" / „gespeichert" / „nicht gespeichert").
  „gespeichert" pulst 300 ms grün und faded nach 5 s in Chrome-Dim
  weg. Container mit `min-w-[9rem]`, damit der Rest der Nav nicht
  seitlich springt.

- **Toast-Komponente für Fehler-Feedback** (Phase 5c). Zweiter
  Alpine-Store `toast` mit Region unten rechts (`aria-live="assertive"`),
  angezeigt bei `save-failed`-Events oder manuellem
  `window.ccToast(message, type)`. Auto-Dismiss nach 5 s, mehrere
  Toasts stapeln sich vertikal.

- **Sticky Header und Sticky Sidebar-Tree** (Phase 5c). Header sitzt
  auf `sticky top-0 z-40`, Aside auf `md:sticky md:top-20
  md:h-fit md:self-start`. Der Baum bleibt beim Scrollen des
  Content-Canvas sichtbar und aktualisiert sich live via
  `#[On('saved')]`-Listener in der `<livewire:sidebar-tree>`-
  Komponente — Titel-Änderungen erscheinen ohne Full-Reload im Baum.

- **Focus-Trap-Plugin für Modals** (Phase 5c). Registrierung von
  `@alpinejs/focus` an Livewires Alpine-Instance via `alpine:init`.
  `<x-ui.modal>` nutzt `x-trap.noscroll.inert` und `aria-modal="true"`;
  Tab bleibt im offenen Modal, Escape schließt ihn (WCAG 2.4.11 /
  2.1.2). Zwei zusätzliche kebab-case-Events (`cc-modal-shown`,
  `cc-modal-hidden`) neben den bestehenden Bootstrap-Kompatibilitäts-
  Events.

- **`Entry::project()`-Navigations-Methode**. Kanonische Convenience
  wie bei Chapter/Text/Image/Audiovisual/Gallery — navigiert über
  `chapter?->project` hoch. Ohne diese Methode failte
  `Gate::authorize('update', ...)` bei Entry-Feldern mit 403.

- **`attachToProject`-Test-Helper** in `tests/Pest.php`. Zentraler
  Helper legt Chapter → Entry → MediaContent → Content-Modell in
  einem Aufruf an. Ersetzt drei fast identische Duplikate in den
  Livewire-Component-Tests (5c.7-Konsolidierung).

- **Aktivmarkierung im Sidebar-Tree** (Phase-5b-Hotfix). Alpine-
  `x-data` auf der Sidebar-Nav watcht `window.location.hash` bei
  Init und `@hashchange`, setzt auf dem passenden Tree-Link
  `aria-current` sowie eine visuelle Aktiv-Klasse. Klick im Tree
  oder Deep-Link liefert sofort visuelle und Screen-Reader-
  Rückmeldung, welcher Eintrag gerade fokussiert ist.

- **Tastatur- und Screenreader-Pflöcke für den Editor** (Phase 5b.2,
  5b.5, 5b.6, 5b.7). Skip-Link „Zum Inhalt springen" als erster Tab-
  Stop, slidet bei `:focus` sichtbar in die linke obere Ecke (WCAG
  2.4.1, Bypass Blocks). 15 Editor-Aktionsbuttons von `<a href="">`
  auf semantisches `<button type="button">` migriert — Tab-Tastatur
  überspringt jetzt keine Aktionen mehr und Screenreader nennen sie
  als Buttons statt „leere Links". Listen-Items (Kapitel, Abschnitt,
  Inhalt) sind unter Update-Permission tab-fokussierbar mit Strg+Pfeil-
  hoch/runter als Tastatur-Alternative zum SortableJS-Maus-Drag (WCAG
  2.5.7); persistiert über den bestehenden `chapter.drag`-Endpoint,
  Maus-Drag bleibt unangetastet. Zentrale ARIA-Live-Region im Layout
  meldet jeden Move (WCAG 4.1.3) — Funktion `window.ccAnnounce(text)`
  ist auch für künftige Async-Aktionen verfügbar. Icon-only-Buttons
  haben jetzt 44×44 px Mindest-Klick-Fläche per `:has()`-Selector
  ohne pro-Button-Patch (WCAG 2.5.8, Touch Targets).

- **Sidebar-Struktur-Baum als Livewire-Komponente** (Phase 5b.3). Die
  linke Aside im Editor zeigt jetzt einen dreistufigen Tree-View des
  Projekts (Projekt → Kapitel → Abschnitt) als
  `<livewire:sidebar-tree :project="$project">`-Volt-Komponente. Mount
  lädt eager via `loadMissing(['chapters.entries'])`, Render-Output ist
  reines Markup mit `<a href="#anchor_Chapter_{id}">`-Links auf die
  bestehenden Anker im Content-Canvas. Klick scrollt smooth dank
  `scroll-behavior: smooth` plus `scroll-margin-top` für die Anker.
  Aside trägt `aria-label="Projektstruktur"`. Drei Pest-Tests pinnen
  den Render-Pfad. Inhalts-Ebene (4.) bleibt aus der Sidebar raus
  (Entscheidung 2.4 — sie lebt im Content-Canvas innerhalb der
  Abschnitt-Karte).

- **Live-Breadcrumb oberhalb des Content-Canvas** (Phase 5b.4 + 5b.4b).
  Neue `<x-ui.breadcrumb>`-Komponente mit zwei Modi: statisch via
  `:items` oder live via `:tree` mit Alpine-Hash-Watcher. Im Editor
  liefert `chapters/index` die Tree-Daten an die Komponente; Klick im
  Sidebar-Tree ändert den URL-Hash, Breadcrumb leitet daraus den Pfad
  ab (Projekt > Kapitel > Abschnitt) und folgt automatisch. Deep-
  Links beim Page-Load werden mit verarbeitet. Letzter Eintrag rendert
  ohne `<a>` mit `aria-current="page"`. Sechs Pest-Tests decken
  beide Modi und das Edge-Case-Verhalten (leere Items, Trenner-Anzahl)
  ab.

- **Coverage-Push von 65,3 % auf 77,5 %** (Phase 5a.V, Coverage-
  Welle). Über das Phase-5-Ende-Ziel von 70 % deutlich hinaus, gibt
  den fünf folgenden Frontend-Wellen (5b–5f) Polster gegen die übliche
  Coverage-Drift. Sieben neue/ergänzte Test-Files mit über 40 Cases:
  vier Files für ungetestete Controller (Language, Public, Setting),
  Policies (User) und Setting-Modelle (Imprint/MailSetting/PrivacyPolicy/
  TermsConditions); drei Ergänzungen in bestehenden Service-/Model-Tests
  (`ContentReorderService` reorderContent + resolveProject(content),
  `Comment` Relations, `LogService` highlightTextDifference +
  getParentText für texts, `MediaContent` belongsTo-Relations +
  cascading-delete). Plus Exclude der drei One-Off-Maintenance-Commands
  (`AuditForeignKeys`, `AuditMediaContent`, `MigrateMediaContent`,
  zusammen 562 LOC ungetesteter Code) aus der Coverage-Berechnung —
  Tests dafür wären disproportional teuer und fachlich wenig aussage-
  kräftig. CI-Schwelle bleibt auf 65 %, das echte Polster lebt im
  tatsächlichen Wert.

  Begleitend `active` ins `$fillable` von `PrivacyPolicy` und
  `TermsConditions` aufgenommen (plus `boolean`-Cast) — ohne diese
  Ergänzung würde der Strict-Mode-Eloquent-Schutz das
  Mass-Assignment im Test wegen `preventSilentlyDiscardingAttributes`
  abbrechen.


- **App-Shell-Theme-Switch visuell sichtbar** (Phase 5a.V, T4).
  Bis T3 war das Theme funktional schon vollständig — `data-theme`,
  `$store.theme`, `localStorage`, ARIA-States — aber die App sah in
  beiden Themes identisch aus, weil die Views ausschließlich generische
  Ink-Tokens nutzten und die Chrome-/Canvas-/Brand-Tokens nirgends
  referenziert waren (Tailwind 4 generiert Utilities on-demand, also
  fehlten die Klassen sogar im CSS-Bundle).
  - `layouts/navi.blade.php`: Header auf `bg-chrome-bg` /
    `border-chrome-line`, Top-Level-Menü auf `text-chrome-on` /
    `hover:bg-chrome-active`, sekundäre Buttons (Sprachwahl, Theme-
    Toggle) auf `text-chrome-on-dim`, User-Menu-Button auf `bg-primary`
    / `text-primary-on`. Dropdown-Panels bleiben absichtlich auf
    `bg-canvas-bg` — sie öffnen über dem Chrome und sollen in beiden
    Themes hell sein.
  - `layouts/app.blade.php`, `layouts/guest.blade.php`,
    `projects/layout.blade.php`: `<body>`-Background von `bg-gray-100`/
    `bg-ink-400/5` auf `bg-canvas-bg` — der Editor-Body wechselt damit
    mit dem Theme.
  - Default-Theme zeigt jetzt das in den v3-Briefing-Tokens vorgesehene
    dunkelblaue Pro-Tool-Chrome (`#1b2330`) mit hellem Editor-Body.
    AM-Theme dreht das auf beige Chrome (`#efe9da`) mit warm-hellem
    Editor-Body — geeignet als Markenidentität für den realen zweiten
    Mandanten „Aktives Museum".
  - Globale 180-ms-CSS-Transition auf `background-color` /
    `border-color` / `color` / `fill` / `stroke` in `app.css`. Ohne
    Transition zeigt der Browser beim Klick auf den Toggle für einen
    Frame einen Mischzustand, was im hellen Modus als sichtbares
    Flackern wahrgenommen wird. `prefers-reduced-motion: reduce`
    schaltet die Transition komplett ab (WCAG 2.3.3).
  - Browser-verifiziert: Toggle-Roundtrip auf `/projects/1/edit`,
    `data-theme`-Attribut, `cc-theme`-Persistenz, ARIA-States,
    computed colors für Header (`#1b2330` ↔ `#efe9da`), Border, Menü-
    Text und User-Menu-Button.

  **Nicht in dieser Welle** (Backlog für separate Wellen):
  Logo-Tausch (CDN-PNG muss mandantenfähig werden), Title-Tag- und
  Mail-Template-Branding, Reader-/Editor-Card-Tokens
  (`bg-tint-bg`/`border-brand-line` für aktive Marker, Akzent-Linien
  an Cards). „App-Shell-Switch" ist der bewusste Schnitt; ein
  Voll-Theme-Switch folgt, wenn der erste reale AM-Onboarding-Use-Case
  konkret wird.

- **`<x-ui.modal>` als zentrale Modal-Komponente** (Phase 5a.IV.c).
  Anonyme Blade-Komponente unter `resources/views/components/ui/modal.blade.php`.
  Props: `id` (Pflicht, für JS-Manager), `title`, `size` (sm|md|lg),
  `closable`, `labelledby`, `headingLevel`. Slots: `default` (Body),
  `header` (komplexer Header z. B. mit Icons), `footer` (rechtsbündige
  Aktions-Buttons). Markup-Outer (`<div class="modal fade" id="...">`)
  bleibt wegen des Vanilla-Modal-Managers erhalten; Inner-Markup ist
  rein Tailwind/Token-basiert ohne `.modal-dialog`/`.modal-content`/
  `.modal-header`/`.modal-body`/`.modal-footer`. Sieben Pest-Render-
  Tests in `tests/Feature/Components/UiComponentsTest.php` decken
  `id`-Pflicht, Title→aria-labelledby-Verknüpfung, Dismiss-Button,
  `closable=false`, `size=lg`, Footer-Slot und die `header`-Slot-
  Precedence ab.

- **Theme-Switch.** Zweite Markenidentität „Aktives Museum" als
  alternativer Farbmodus, schaltbar über einen Toggle-Button im
  Editor-Header. `<html data-theme="aktivesMuseum">` aktiviert den
  Hell-Modus mit gelbem Brand-Akzent (`#f5cf11`) und hellem App-Chrome
  (`#efe9da`); ohne Attribut bleibt die crowdCuratio-Default-Marke
  (rot/dunkles Chrome) aktiv. Die Theme-Tokens (`--color-brand-bar`,
  `--color-brand-line`, `--color-tint-bg`, `--color-tint-text`,
  `--color-chrome-*`, `--color-canvas-bg`) sind in `tokens.css` als
  Custom-Properties definiert und werden durch einen
  `[data-theme="aktivesMuseum"]`-Selector überschrieben. Die Wahl
  persistiert in `localStorage` (`cc-theme`) und wird vor Alpine-Init
  angewendet, damit kein Flash sichtbar wird. Sonne-/Mond-Icon aus
  Lucide markiert den aktuellen Modus.
- **Tooltip-Shim** (`resources/js/tooltip-shim.js`) — Bootstrap-3-
  `.tooltip()`-Plugin ist mit dem BS3-JS-Abbau gefallen; ein dünner
  No-op-Shim fängt die noch verbliebenen `$('[data-toggle="tooltip"]')
  .tooltip()`-Aufrufe in `chapters/index` und `roles/index` ab, sodass
  der jQuery-deferred-Chain nicht mehr bricht. Native `title`-
  Browser-Tooltips übernehmen den Hover-Hint.
- **CI-Job `Pest Coverage (≥ 65 %)`** als Hard-Fail. Misst Pest-
  Coverage mit `pcov` und bricht den Build, wenn die Schwelle gerissen
  wird. `composer.json` `test-coverage --min` von 55 % auf 65 %
  hochgezogen — die Schwelle ist damit kein lokales Versprechen mehr,
  sondern der echte Anker im PR-Workflow.
- **`docs/architecture.md`** als Entwickler-orientierte Architektur-
  Übersicht. Beschreibt das Domänenmodell (Project → Chapter → Entry
  → MediaContent → Content), das Authorization-Modell
  (`OwnerScopedPolicy` als Basisklasse, Spatie-`Gate::before`
  abgeschaltet via `register_permission_check_method => false`),
  die Service-Layer-Struktur, die Routing-Schichtung und die
  Test-Pyramide. Inklusive expliziter Abgrenzung dessen, was nicht
  ins Dokument gehört (PDF-Pipeline, Storage-Strategie, Frontend-
  Build, utf8mb4-Migration, Bug-Historie).
- **Service-Layer für die Content-Domäne.** Zehn neue Service-Klassen
  unter `app/Services/` kapseln die Schreib- und Lesepfade, die
  vorher in den Fat Controllern lebten:
  - `ProjectImageService` — Logo-Upload für Projects mit
    `Storage::fake`-tauglicher Schnittstelle und deterministischem
    Dateinamen-Muster.
  - `ProjectPermissionService` — zentralisiert die zehn
    project-scoped Permission-Operationen (Listing berechtigter
    User, Lesen globaler und Pivot-Permissions, Set-Semantik beim
    Setzen, vollständiges Entfernen) plus
    `userHasPermissionOnProject` und `listProjectsForUser` für die
    Policy-Schicht.
  - `ChapterService` und `EntryService` — Position-Calculation und
    Translation-Verzweigung für die zwei Schreibpfade pro Modell.
  - `ContentReorderService` — die drei Drag-and-Drop-Schreibpfade
    über Chapter / Entry / MediaContent plus `resolveProject(...)`
    für den Authorize-Gate.
  - `CommentService` — die fünf Schreibpfade auf Comments
    (`addComment`, `replyToComment`, `editComment`, `deleteComment`,
    `setCommentStatus`) plus `dispatchSaveAction` für die
    `btn_submit`-Switch-Logik und `resolveProjectForComment(int)`
    für die Authorize-Auflösung der Comment-Status-Endpunkte.
  - `SourceService` — `findOrCreateId(value, type): int` ersetzt
    das `getSource`-Method-Duplikat in Project- und
    ContentController.
  - `TextService`, `ImageService`, `GalleryService` und
    `AudiovisualService` (mit `resolveLink(?string, ?UploadedFile)`
    für YouTube-URL-Konversion und Audio-Upload).
  - `UserReactivationService`, `UserOnboardingService` und
    `ProjectInvitationService` extrahiert aus dem ehemaligen
    `RegisteredUserController::store`, dazu der `RoleResolver`-
    Helper unter `app/Support/`.
- **DTO-Schicht** unter `app/Data/` für die Mutations:
  `ProjectData`, `ChapterData`, `EntryData`, `TextData`, `ImageData`,
  `GalleryData` und `AudiovisualData`. Jeweils mit
  `fromRequest(FormRequest, ...)`-Factory; ersetzt die
  `mapData()`-Cargo-Helper in den Controllern.
- **FormRequest-Welle für User-, Role- und Comment-Pfade.** Neue
  Klassen unter `app/Http/Requests/`: `UpdateUserAsAdminRequest`,
  `UpdateOwnProfileRequest`, `StoreRoleRequest`, `UpdateRoleRequest`,
  `StoreCommentRequest` (für sieben Comment-Endpunkte über Project,
  Chapter, Entry, Text, Image, Gallery, Audiovisual),
  `StoreImageBlockRequest` und `StoreAudiovisualRequest` mit
  MIME-Whitelist (jpeg, jpg, png, gif, webp für Bilder; mp3, mp4,
  wav, ogg, m4a für Audio) und Size-Limit (4 MB / 20 MB).
- **`App\Contracts\HasComments`-Interface** für die acht
  commentable Modelle (Project, Chapter, Entry, MediaContent,
  Text, Image, Gallery, Audiovisual). Garantiert den
  `comments(): MorphMany`-Vertrag im Type-System, der vorher nur
  durch den entfernten `CommentTrait` implizit war.
- **`App\Policies\OwnerScopedPolicy`** als abstrakte Basisklasse:
  trägt den `before()`-Admin-Shortcut, Service-Injection und einen
  `check(User, Project, PermissionName)`-Helper sowie einen
  `checkViaProject(?Project)`-Helper, der `false` liefert, wenn
  das Project nicht aufgelöst werden kann. Vier neue
  Content-Policies (`TextPolicy`, `ImagePolicy`, `GalleryPolicy`,
  `AudiovisualPolicy`) leiten daraus ab und resolven das Project
  über die polymorphe `mediaContents()`-Beziehung.
- **`App\Support\PermissionName` und `App\Support\RoleName` als
  Backed-Enums** (PHP 8.1+). Sieben Permission-Cases und vier
  Rollen-Cases mit den Spatie-Namen als Werten. Harte Strings in
  Policies, Services und Controllern durchgängig auf
  Enum-Zugriffe umgestellt.
- **`db:audit-media-content` und `db:migrate-media-content`**
  Artisan-Commands. Der Audit-Command liefert Markdown-Output mit
  Type-Counts, Orphan-Check und Parent-Probe für die `media_content`-
  Pivot-Tabelle. Der Migrations-Command läuft Default als Dry-Run
  mit Drift-Report (matched / fixable / unrecoverable /
  gallery_schiefstand) und schreibt mit `--apply` die Korrekturen.
  Beide sind idempotent.
- **Architektur-Dokument** und **PHPDoc-`@property`-Annotationen**
  an sieben Modellen (Audiovisual, Chapter, Entry, Gallery,
  Project, Source, Text) mit DB-Feldern, Relations und den
  dynamisch gesetzten Runtime-Snapshots. Voraussetzung für den
  Eloquent-Strict-Mode-Switch.
- **Test-Factories für die Content-Modelle** unter
  `database/factories/`: `SourceFactory` (mit `origin()` / `copyright()`-
  States), `TextFactory`, `ImageFactory` (mit Source-Refs, optional
  `forGallery(id)`-State), `GalleryFactory`, `AudiovisualFactory`
  (mit `audio()`-State). Pest-Helper `makeSource`, `makeText`,
  `makeImage`, `makeGallery`, `makeAudiovisual` analog zu den
  bestehenden `makeProject`/`makeChapter`/`makeEntry`-Helpern.
- **Test-Suite von 58 auf knapp 400 grüne Pest-Tests gewachsen.**
  Schwerpunkte:
  - **Charakterisierungs-Tests** vor jeder Service-Extraktion
    (Bootstrap-Migration, Content-Pfade, Comment-Pfade,
    Translation-Pfade).
  - **Service-Tests** für jeden der zehn neuen Services in
    `tests/Feature/Services/`.
  - **Policy-Tests** in `tests/Feature/Policies/` mit
    Owner / Admin / Eingeladener-mit-edit / Eingeladener-nur-mit-view
    / Fremdem als Achsen sowie expliziten Negativtests für die
    bisher nicht negativ-getesteten `update`/`delete`/`restore`/
    `publish`-Methoden.
  - **Authorization-Pinning-Tests** für die Content-Controller
    in `ContentRouteAuthorizationTest` (16 Tests über die
    kritischsten Vektoren).
  - **Comment-Charakterisierungs-Tests** und
    **Content-Charakterisierungs-Tests** für die Refactor-Vorlauf-
    Phasen.
  - **HappyPath-Suite ausgebaut** und auf die neuen Spalten der
    `media_content`-Pivot-Tabelle umgestellt.
  - **Unit-Tests** für `LogService` (`highlightTextDifference`,
    Switch-Cases via Reflection) und für die `RoleName`-/
    `PermissionName`-Enums.
  - **Rate-Limit-Tests** für die Guest-Auth-Routen
    (`AuthRateLimitTest`).
- **Schema- und Migrations-Pinning-Tests**:
  `PermissionTableRenameTest`, `ProjectUserPermissionTest`,
  `PermissionTableSeederStrictModeTest`,
  `MediaContentMorphRelationsTest`, `MediaContentMorphColumnsTest`,
  `ContentProjectNavigationTest`, `ContentServicePivotInsertTest`
  (vormals `ContentServiceDoubleWriteTest`).
- **CI-Coverage-Schwelle gestaffelt angehoben** von 25 % auf
  30 %, dann auf 55 % nach Abschluss der Content-Service-Welle.
  `composer.json` `test-coverage --min` entsprechend
  nachgezogen. Coverage am Phase-Ende effektiv bei 66,9 %.
- **Livewire 4 und Volt** als Komponenten-Stack eingeführt
  (`livewire/livewire ^4.0`, `livewire/volt ^1.10`). Erster
  Pilot: `<livewire:comment-status-switcher>` ersetzt den
  jQuery-`$.ajax`-Handler `.update-status` für Kommentar-Status-
  Wechsel in `projects/description.blade.php`. Die Single-File-
  Volt-Komponente bindet den Policy-Check `comment(Project)` und
  delegiert an `CommentService::setCommentStatus`. Drei Pest-Tests
  decken Happy-Path, 403 für Reader und ungültige Status-Werte ab.
- **UI-Komponenten-Bibliothek** unter `resources/views/components/ui/`
  als anonyme Blade-Komponenten mit eingebauten Accessibility-Defaults:
  `<x-ui.button>` (Varianten primary/secondary/ghost/danger, drei Größen,
  sichtbarer Fokus-Ring), `<x-ui.icon-button>` (44 × 44 Min-Trefferfläche
  nach WCAG 2.2, `aria-label` als Pflicht-Prop mit Laufzeit-Check),
  `<x-ui.input>` (Label/Hint/Error verknüpft via `aria-describedby`,
  sichtbarer `*` plus sr-only-Pflichtfeld-Hinweis, `aria-invalid` bei
  Fehlern, `role="alert"` auf der Fehlermeldung),
  `<x-ui.toggle>` (Alpine-getrieben mit `role="switch"`, `aria-checked`,
  Tastatur-Toggle über Space, optionalem Hidden-Input für Form-Submit),
  `<x-ui.card>` (Varianten chapter/abschnitt/inhalt für die im Glossar
  festgehaltene Hierarchie, konfigurierbares Heading-Level) und
  `<x-ui.banner>` (Typen success/info/warning/danger, automatisches
  `aria-live="assertive"` plus `role="alert"` bei warning/danger,
  optionaler Dismiss-Button). Begleitet von einem schmalen
  `<x-ui.icon>`-Wrapper, der Lucide-SVGs inline und stilkonsistent über
  `currentColor` rendert. 17 Pest-Render-Tests prüfen Variant-Klassen,
  Pflicht-ARIA-Attribute und Slot-Durchreichung. Komponenten sind in
  diesem Schritt noch nicht in produktive Views eingewebt — die
  Bibliothek liegt damit für den folgenden Bootstrap-Abbau bereit.
- **Editor-Header-Navigation (`layouts/navi.blade.php`)** auf Tailwind 4
  + Alpine umgestellt. Die Bootstrap-Dropdowns (`data-toggle="dropdown"`)
  sind durch Alpine-Patterns ersetzt, die Top-Level-Items
  (Einstellungen, Projekt, Nutzer, Kommentare, Sprache, User-Menü)
  haben sichtbare Fokus-Ringe und korrekte
  `aria-haspopup`/`aria-expanded`-Attribute. Der
  `<x-ui.icon name="chevron-down">`-Wrapper liefert die Drop-Caret-Icons
  aus dem Lucide-Set.
- **Volt-Komponente `<livewire:comment-text-editor>`** löst das
  jQuery-Plugin x-editable für Inline-Edit der Kommentar-Texte ab.
  Click-to-Edit mit Textarea, Speichern via `CommentService::editComment`,
  Esc und Cancel-Button schließen ohne Schreibvorgang. Policy-Gate
  `comment(Project)` greift sowohl beim Öffnen als auch beim Speichern.
  Drei Pest-Tests decken Happy-Path, 403 für Reader und das stille
  Verwerfen leerer Eingaben ab.

### Geändert

- **String-Type-Switch auf Registry-Pattern konvergiert** (I2 ·
  2026-08-21 / ARCH-02, F-ARCH-009). Zwei Switch-Kaskaden, die
  Content-Type-Slugs bzw. Model-FQCN auf Modell-Klasse, Tabelle,
  Property und Routen mappten (`LogService`, `CommentRetrieve`),
  laufen jetzt über zwei kleine Registries in `App\Support`:
  `ContentTypeRegistry` (Slug → Model/Table/Property, sechs
  Content-Modelle inkl. Audiovisual) und `CommentableRoutes`
  (FQCN → Save-/Base-Route, sieben commentable Modelle). Neue
  Content-Modelle werden jetzt an einer Stelle registriert.

- **Service-DI-Sweep — Container statt `new Service()`** (I3 ·
  2026-08-21 / ARCH-03, F-ARCH-011). Sechs direkte
  `new CommentRetrieve;` und ein `new UserService;` in
  `ProjectController`, `ChapterController`, `ContentController`
  und `EntryController` sind auf Constructor-Injection umgestellt.
  Services werden aus dem Laravel-Container aufgelöst — künftige
  DI-Abhängigkeiten der Services greifen dann automatisch.

- **Comments-DB-Baseline auf Bigint + Polymorph-Index + FKs**
  (Q3-Politur 2026-08-20 / DB-01, DB-02 / F-DB-009, F-DB-010). Neue
  Migration `2026_08_20_150000_align_comments_fks_and_polymorph_index`
  zieht die FK-Spalten `user_id`, `parent_id`, `commentable_id` von
  `INT UNSIGNED` auf `BIGINT UNSIGNED` nach (Referenzziele sind seit
  Ewigkeiten Bigint), legt einen zusammengesetzten Index auf
  `(commentable_type, commentable_id)` und setzt echte
  FK-Constraints auf `user_id → users.id` (RESTRICT) und
  `parent_id → comments.id` (`nullOnDelete`). Polymorpher Lookup ist
  jetzt indiziert; die Voraussetzung für konsistente Foreign Keys
  ist erfüllt.

- **HTML-Purifier als primärer Rich-Text-Sanitizer** (Q3-Politur
  2026-08-20 / ADR-0029). `App\Support\RichTextSanitizer::sanitize()`
  ruft `Purifier::clean($html, 'rich')` mit der ADR-0029-Whitelist
  (Tags `p, br, strong, em, u, s, ul, ol, li, a, blockquote, h2-h4`,
  href/target/rel auf `a`, nur https+http, `HTML.TargetBlank` mit
  Noopener/Noreferrer). Der strip_tags-basierte Übergangs-Sanitizer
  aus der Q3-Härtung bleibt als Fallback für Bootstrap-Sonderfälle.
  `mews/purifier` ^3.4 als neue composer-Dependency.

- **Preset-Highlight synchron mit den Toggles** (Q3-Politur
  2026-08-20 / P-01). Rollen-Preset-Buttons in der Berechtigungs-
  Sicht leuchten jetzt live mit der aktuellen Toggle-Kombination.
  Neuer `matchedPreset`-Getter vergleicht die aktuellen `permissions`
  gegen die drei Presets (Editor/Reviewer/Reader) statt gegen den
  zuletzt gespeicherten `role`-Wert.

- **Farb-Chip-Ring auf ring-inset** (Q3-Politur 2026-08-20 / UI-04).
  Der aktive Ring in der Initials-Farb-Palette lag außen mit
  `ring-offset` — das bohrte in der Chip-Reihe ein weisses Loch.
  Innen-Ring bleibt innerhalb des Chips und kollidiert nicht mit
  den Nachbarn.

- **Restore-Dialog-Übersetzungshinweis auf info-Farbe** (Q3-Politur
  2026-08-20 / UI-05). Der Hinweis „Es gibt Übersetzungen zu diesem
  Block" war fälschlich als `warning` gefärbt, obwohl die Aktion
  nicht destruktiv ist. Jetzt `info-bg`; warning/danger bleibt für
  echte Warnungen reserviert.

- **Locale-Wechsel im Profil mit Dirty-Guard** (Q3-Politur
  2026-08-20 / UX-10). `switchLocale()` fragt vor dem Reload, wenn
  das Formular ungespeicherte Änderungen hat. Vorher verwarf der
  automatische Reload den Formular-State kommentarlos.

- **Register-Rollen-Select übersetzt** (Q3-Härtung 2026-08-19 /
  Legacy FIND-12). `<option>`-Labels zeigten die Spatie-Rohwerte
  „Reader" / „Editor" / „Reviewer" — der 5e-Vokabular-Sweep hatte
  diese Blade nicht erwischt. Value bleibt Rohwert (kompatibel mit
  `\App\Support\RoleName::READER->value`), Label über
  `__('role_'.strtolower($role))` → „Leser:in" / „Editor:in" /
  „Reviewer:in".

- **Dashboard-Landing (Screen 09)** (Phase 5e.1). Vier
  Sektionen: **Wiederaufnahme-Zeile** mit zuletzt
  bearbeitetem Projekt-Anker (entfällt bei leerem Zustand);
  **Meine Projekte** als 3er-Kartenraster mit CTA-Empty-State
  bei Erstlogin; **Mir zugeteilt** analog, aber Karten
  tragen Rollen-Badge (Editor:in / Leserecht) und die
  Leserecht-Karte führt zur Leseansicht statt zum Editor;
  **Letzte Kommentare** als Listenkarte (30-Tage-Fenster,
  max 5, mit line-clamp-2-Vorschau des Kommentartextes).
  Neuer `DashboardController` mit vier isolierten Feeds
  (max 6 Karten pro Sektion, `updated_at DESC`).
  Sidebar-Rail bekommt „Start" als ersten Menüpunkt,
  `RouteServiceProvider::HOME` wandert von `/projects`
  zurück auf `/dashboard`. Designer-Briefing steckt in
  `.werkbank/BRIEFINGS/redesign/dashboard-5e.md`, Screen
  in `dashboard-screen/09-dashboard.png`.

- **UI-Vokabular auf Glossar** (Phase 5e.2 + 5e.3). 28 Keys in
  `resources/lang/de.json` umgezogen: `Eintrag` → `Abschnitt`,
  `Block` → `Inhalt`, `Galerie` → `Bildergalerie`, `AutorIn`
  → `Autor:in`, `BenutzerIn` → `Nutzer:in`, `Item` und
  `Draft` raus. Anlage-Verben substantivisch — „Neuer
  Abschnitt", „Neuer Inhalt", „Neues Kapitel", „Neuer:e
  Nutzer:in". Backend-Domain-Modell (Entry/Chapter/Text/
  Image/Gallery) bleibt englisch.

- **Fehlerseiten 403/404/500 persona-freundlich neu**
  (Phase 5e.5). Gemeinsame Hülle `_error-shell.blade.php`
  mit großem Statuscode-Anker, Icon-Kreis in `danger-bg`,
  freundlichem Titel und Body sowie CTA-Button („Zu meinen
  Projekten" bzw. „Zur Anmeldung"). Der 500-Rewrite schließt
  außerdem einen Info-Leak, siehe „Sicherheit".

- **DataTable-i18n vollständig deutsch** (Phase 5e.4). Die
  letzten englischen Strings in der Kommentar-Liste
  (`resources/views/contents/comment.blade.php`) — „First",
  „Last", generisches „Zeige _MENU_ Einträge" — auf saubere
  deutsche Formulierungen gehoben.

- **Glossar-Sektion in `docs/architecture.md`** (Phase 5e.6).
  UI-Vokabel ↔ Backend-Modell-Tabelle plus explizite
  „vermieden werden"-Liste als Onboarding-Anker für neue
  Entwickler:innen und Referenz für den
  Vokabular-Snapshot-Test.

- **Design-Tokens vollständig auf Handoff v4** (Phase 5-D.1).
  `tokens.css` um `--color-line-100/200`, `--color-paper-0/50`,
  `--color-on-dark-100/300/400`, `--radius-pill`, vier `--shadow-*`-
  Stufen plus `--shadow-popover`, `--text-mono-caps`, `--color-form-
  border` und die drei A11y-Tokens (Focus-Outline, Focus-Offset,
  Target-Min) erweitert. Font-Tokens `--font-sans` (IBM Plex Sans),
  `--font-mono` (IBM Plex Mono) und `--font-serif` (Source Serif 4)
  im `@theme`-Block, damit Tailwind 4 die Utilities generiert und
  IBM Plex Sans als projektweiter UI-Default rendert.

- **Kapitel/Entry-Layout ohne Bootstrap-Grid** (Phase 5-D.6).
  Die Bestandsstruktur mit `.row .border .border-secondary .p-4`
  und mehrfach ineinander geschachtelten Card-Ebenen ist raus.
  Kapitel-Titel sitzt frei auf dem Canvas als
  `text-title font-semibold`, Entry-Karte trägt Mono-Caps-Label
  „EINTRAG · KAPITEL N", Block-Cards tragen ihren Typ-Tag —
  eine sichtbare Rahmenebene je Bereich, nicht drei ineinander.

- **Add-Buttons als Ghost-Style** (Phase 5-D.6b P2.10). Die drei
  Add-Trigger („+ Block hinzufügen", „+ Eintrag hinzufügen",
  „+ Kapitel hinzufügen") laufen mit `border-2 border-dashed
  border-line-200` auf transparentem Untergrund, damit sie nicht
  mit dem roten Publish-Primary konkurrieren.

- **Post-Login-Redirect auf `/projects`** (Phase 5-D.7). Der
  `RouteServiceProvider::HOME`-Wert ist von `/dashboard` (leer)
  auf `/projects` gehoben.

- **Register-Rules ohne harte `roles`-Pflicht** (Phase 5d.7).
  `RegisterRequest::rules` gibt `roles` als `sometimes|nullable`
  frei; ein POST ohne Rollen-Feld läuft im Controller in den
  Reader-Default (least privilege) statt in eine
  ValidationException. Der Fallback verhindert rollenlose User,
  bei denen `@can`-Gates nicht mehr greifen.

- **`isAdmin`-Check im Rail auf `hasRole()`** (Phase 5d.6-
  Hotfix). Vorher `Auth::user()->currentRole[0]->name === 'Admin'`
  — crashte mit „Undefined array key 0" bei rollenlosen Usern.
  Jetzt `Auth::user()->hasRole(RoleName::ADMIN->value)`, robust
  gegen leere Rollen-Collection.

- **Modal-Wire-Up trennt Trigger-Feldbelegung vom `@push`-Block**
  (Persona-Smoke-Hotfix). Der Modal-Manager gibt bei
  `handleToggleClick` den Trigger als `relatedTarget` mit ins
  `cc-modal-show`-Event; ein neues `resources/js/modal-wire.js`
  verkabelt `#entryModal.chapterId` aus `data-id`/`data-chapter`
  des Triggers. Löst das Problem, dass ein JS-Fehler im
  `@push('scripts')`-Block der `chapters/index.blade.php` die
  Registrierung des delegierten `.addEntry`-Handlers unterbrach
  — Modal öffnete, aber `chapterId` blieb leer → 403.

- **`ProjectPolicy::update/delete/publish` auf project-scoped
  Pivot** (Phase 5d.4-Followup). Vorher prüften diese drei
  Methoden nur `$user->id === $project->user_id` (Owner-only).
  Die project-scoped `edit`/`delete`/`publish`-Toggles aus
  Screen 3B hatten damit keinen Effekt auf den Inline-Editor
  und den Rich-Text-Editor — beide autorisieren via
  `Gate::authorize('update', $project)` und liefen in die
  Owner-only-Falle. Jetzt konsultiert die Policy den Service
  `userHasPermissionOnProject(EDIT/DELETE/PUBLISH)` mit
  Owner-Shortcut. Eingeladene Editor:innen können damit
  tatsächlich das bearbeiten, wofür sie im Screen 3B den Toggle
  gesetzt bekommen haben. Vier neue Positiv-Tests in
  `ProjectPolicyTest`, der Kommentar am Test-Block wurde
  umgeschrieben (pinnte vorher das Owner-only-Verhalten als
  gewolltes Feature).

- **Coverage-Mindestschwelle im CI auf 75 %** (Phase 5c). Der
  `pest --coverage --min`-Wert im `test`-Job der GitHub-Actions-
  Pipeline ist von 65 auf 75 gehoben. Ist-Stand nach 5c: 77,9 %
  Zeilenabdeckung — 2,9 Punkte Polster für kleine Folge-Wellen.

- **Editier-Flow ohne Modals** (Phase 5c). Kapitel-, Abschnitt-,
  Text-Content-, Galerie-, Bild- und Audiovisual-Editieren läuft
  direkt am Element inline statt in einem Modal. Titel/Subtitel/
  Beschreibungen editieren sich per Klick am Card; Bild-Alt-Text
  am Bild; Copyright und Quelle hinter einem `<details>`-Toggle
  mit Autocomplete. Fünf Add-Modals bleiben in dieser Runde für
  „hinzufügen" — inline-Add ist als Feature im Backlog.

- **Sidebar-Tree Live-Update via `saved`-Event** (Phase 5c). Der
  Baum hört auf globales `saved`-Event und lädt sich frisch aus
  der Datenbank neu (`->load()` statt `->loadMissing()`, weil
  Livewires Snapshot Relations mitbringt und `loadMissing` sonst
  als No-Op durchläuft). Änderungen an Titeln erscheinen ohne
  Full-Page-Reload im Baum.

- **Gallery-Bild-Layout auf Utility-Höhe umgestellt** (Phase 5c).
  `.gallery_item .img { height: 300px }` aus dem Legacy-CSS wird
  unter Tailwind-4-Preflight nicht mehr zuverlässig priorisiert;
  die 300 px sind jetzt zusätzlich per `h-[300px]`-Utility direkt
  am Element gesetzt. `grid-auto-rows: minmax(300px, auto)`
  ersetzt das Zwei-Row-Muster im `.gallery_container`, damit auch
  Row 2/3 nicht kollabieren.

- **Reorder-Shortcut auf Alt+↑/↓** (Phase-5b-Hotfix). Der ursprünglich
  in Phase 5b vorgesehene Shortcut `Strg+↑/↓` kollidierte auf macOS
  system-weit mit Mission Control und Space-Switching, das Feature
  war für macOS-Nutzer:innen nicht bedienbar. `Alt+↑/↓` (Option auf
  macOS) ist auf allen drei Desktop-OS frei — dieselbe Konvention
  wie in VS Code für Zeilen-Verschieben. Item bekommt zusätzlich
  `aria-keyshortcuts="Alt+ArrowUp Alt+ArrowDown"` und einen
  Tooltip-Hinweis („Verschieben mit Alt+↑/↓").

- **Sidebar- und Breadcrumb-Tree hinter einem Service** (Phase-5b-
  Hotfix). Neuer `ProjectTreeService` mit zwei Methoden
  (`breadcrumbTree`, `sidebarTree`) ist Single Source für die
  Projekt-Hierarchie. Vor der Konsolidierung bauten Sidebar-Volt-
  Komponente und Breadcrumb-Blade die Hierarchie unabhängig aus zwei
  unterschiedlichen Project-Instanzen auf — Drift-Risiko bei
  Struktur-Änderungen.

- **App-Shell-Layout auf semantische Komponente** (Phase 5b.1). Neue
  `<x-layout>`-Komponente löst die alte `layouts/navi.blade.php` ab —
  Tailwind-Grid statt Bootstrap-3-Cols, vier semantische Landmarks
  (`<header>`, `<aside aria-label>`, `<main role="main" id="main-content">`,
  `<footer>`), zentrale `@stack('scripts')`-Region für View-Beiträge
  und eine zentrale `<div id="cc-live-announcer" role="status" aria-live="polite">`
  für ARIA-Announcements. `projects/layout.blade.php` bleibt als
  schlanker Brückenkopf bestehen: die 12+ `@extends('projects.layout')`-
  Views in den App-Pfaden laufen unverändert weiter. Sub-View
  `layouts/navi-header.blade.php` trägt den reinen Header-Anteil
  (Logo, Navi-Items, Theme-Toggle). Vier Pinning-Tests sichern die
  Section-Slot-Durchreichung.

- **Script-Sections auf `@push('scripts')`** (Phase 5b.1). In den
  zehn Editor-, Settings-, Translate-, Auth- und Index-Views ist
  `@section('script') … @endsection` auf den idiomatischen
  `@push('scripts') … @endpush`-Stack umgestellt. Stack wird in der
  Layout-Komponente vor `</body>` ausgegeben (`@stack('scripts')`).
  Robusterer Mechanismus für mehrere Beiträge pro Page — relevant
  für die kommenden Komponenten-Wellen.

- **`compat-bootstrap.css` → `bootstrap-utilities.css` umbenannt**
  (Phase 5a.IV.c, Pragma-Shift). Die Datei war als temporäre Brücke
  angelegt, deren Ablaufdatum der Name `compat-` suggerierte: alle
  Views sollten Bootstrap-3-Klassen verlieren, bevor die Datei fällt.
  In der Modal-Welle wurde sichtbar, dass eine strikte 1:1-Migration
  der verbliebenen ~560 Klassen-Stellen (Grid `row`/`col-*`, Forms
  `form-control`/`form-group`, Buttons `btn-*`, Alerts, Tables, …)
  funktional nichts ändert — die Klassen leben als
  `@layer components`-Custom-Utilities mit Tailwind-Tokens, geladen
  würde nichts mehr aus einem fremden Bundle. Mit dem Rename ist die
  Datei jetzt explizit als permanenter Teil der App-CSS-Schicht
  markiert; der Header dokumentiert die Entscheidung und grenzt die
  vier Modal-JS-Hook-Klassen (`.modal`, `.modal.in/.show`,
  `.modal-backdrop`, `body.modal-open`) als Sonderfall ab.
  Import-Pfad in `resources/css/app.css` aktualisiert, Kommentar in
  `resources/views/projects/layout.blade.php` nachgezogen.

- **Modal-Markup auf `<x-ui.modal>` migriert** (Phase 5a.IV.c, M3). Alle
  16 sichtbaren Modal-Stellen in den App-Views umgezogen: `audiovisualModal`,
  `galleryModal`, `imageModal`, `contentModal` (`contents/*.blade.php`),
  `entryModal` (`Entry/index.blade.php`), `roleModal` (`roles/index.blade.php`),
  `termsConditionsModal`/`privacyModal`/`imprintModal`/`invitationModal`
  (`settings/index.blade.php`), `myModal`/`previewModal` (`projects/index.blade.php`),
  `userInvitation`/`userModal`/`newUserInvitation`/`newUser`
  (`projects/create.blade.php`), `myModal` (`projects/element.blade.php`)
  sowie `myModal`/`commentModal`/`previewModal` (`chapters/index.blade.php`).
  Markup-Outer (`.modal.fade` plus `id`) bleibt identisch, sodass der
  Vanilla-Modal-Manager weiter greift. Bootstrap-3-Compat-Schicht
  (`resources/css/compat-bootstrap.css`) entsorgt parallel die strukturellen
  Modal-Klassen `.modal-dialog`, `.modal-content`, `.modal-header`,
  `.modal-title`, `.modal-body`, `.modal-footer`, `button.close` sowie
  die `.bd-example-modal-xl > .modal-dialog`-Modifier. Was bleibt: nur die
  JS-Hook-Klassen `.modal`/`.modal.in/.show`, `.modal-backdrop` und der
  `body.modal-open`-Scroll-Lock-Hook — alles, woran der Vanilla-Modal-
  Manager funktional gebunden ist. Das E-Mail-Template
  `vendor/welcomeNotification/welcome.blade.php` ist absichtlich nicht
  migriert (wird nicht im Browser gerendert).

- **Accessibility fixes** `<html lang>`-Attribut auf den vier Layouts ergänzt, die es
  bisher nicht hatten, **Logo-`alt`-Attribut** auf vier Logo-`<img>`-Tags ergänzt, 
  **Pflichtfeld-Markierung** um ein Sternchen ergänzt.
- **Bootstrap-CSS- und Bootstrap-3-JS-CDN-Links aus den Haupt-Layouts
  entfernt.** Weder `layouts/guest.blade.php` noch
  `projects/layout.blade.php` laden Bootstrap-CSS oder Bootstrap-3.3.7-JS
  per CDN nach. Das Modal-Plugin ist durch einen schmalen Vanilla-
  Modal-Manager (`resources/js/modal.js`) ersetzt, der die im Bestand
  etablierten Markup-Trigger (`data-toggle="modal"`, `data-dismiss="modal"`,
  Klick außerhalb, Esc) sowie programmatische jQuery-Aufrufe
  (`$('#xxx').modal('show'|'hide'|'toggle')`) über ein jQuery-Shim
  bedient. **x-editable**, das Bootstrap-3-Form-Plugin für Inline-
  Edit der Kommentar-Texte, ist durch die Volt-Komponente
  `<livewire:comment-text-editor>` abgelöst — bestehende `data-url`-
  Attribute und die `$('.comment-edit').editable({...})`-Init in
  `chapters/index.blade.php` fallen, das x-editable-CSS- und
  JS-Bundle entfällt komplett. **Bootstrap-3-Typeahead** ist ebenfalls
  durch einen schmalen Vanilla-Manager (`resources/js/typeahead.js`)
  ersetzt; die fünf bestehenden `$('#xxx').typeahead({...})`-Aufrufe in
  `chapters/index.blade.php` und `projects/index.blade.php`
  funktionieren ohne View-Edits weiter, Tastatur-Navigation (↑/↓/Enter/
  Esc) und Klick-Outside-Schließen sind eingebaut. **jQuery-DataTables**
  ist ebenfalls durch einen Vanilla-Manager (`resources/js/datatable.js`)
  ersetzt — die drei Tabellen-Aufrufe (`projectList`, `userList`,
  `commentList`) bekommen weiterhin Suche, Sortierung per Header-Klick
  und Pagination, jetzt aber ohne jQuery-DataTables-Bundle. Die
  deutschen UI-Strings aus den bestehenden `language`-Optionen werden
  direkt übernommen. **jQuery-UI Sortable** ist durch einen Shim
  (`resources/js/sortable-shim.js`) auf SortableJS umgebogen — die drei
  `.sortable({...})`-Init-Aufrufe für Kapitel/Bereich/Inhalt in
  `chapters/index.blade.php` laufen ohne Markup-Änderung weiter,
  jQuery-UI fällt damit aus dem CDN-Stack. Für die
  Übergangsphase liefert eine schmale Tailwind-Compat-
  CSS-Schicht (`resources/css/compat-bootstrap.css`) die strukturellen
  Bootstrap-Klassen — `container`, `container-fluid`, `row`,
  `col-{xs|sm|md|lg}-*`, `btn`, `btn-{primary|secondary|danger|success}`,
  `btn-block`, `btn-lg`, `btn-sm`, `form-control`, `form-group`,
  `form-check-label`, `alert` und Varianten, `table`-Familie,
  `nav`/`nav-link`/`nav-pills`, `dropdown-menu`/`dropdown-item` und die
  Bootstrap-Modal-Familie (`.modal`, `.modal-dialog`, `.modal-content`,
  Header/Body/Footer, `.modal-backdrop`, `.fade`). Bootstrap-Spacing-
  Utilities bleiben außerhalb: Tailwind hat eigene Klassen-Namen,
  kleine Differenzen sind akzeptiert. Die Schicht und der Bootstrap-3-
  JS-Bestand fallen mit dem nächsten Schritt, sobald die Inhalts-Views
  einzeln auf die neue Komponenten-Bibliothek umgezogen sind.
- **Frontend-Build von Laravel Mix auf Vite umgestellt.**
  `webpack.mix.js` entfällt, `vite.config.js` übernimmt mit
  `laravel-vite-plugin` und `@tailwindcss/vite`. Layouts
  (`layouts/app`, `layouts/guest`, `projects/layout`) referenzieren
  Assets jetzt über `@vite([...])` statt über `asset('css/app.css')`
  / `asset('js/app.js')`. **Tailwind CSS auf v4** angehoben; die
  Tokens (Brand-Farben, Neutral-Skala, semantische Aliase, Spacing-
  und Radius-Stufen) leben als CSS-Custom-Properties in
  `resources/css/tokens.css` und werden über einen `@theme`-Block in
  `resources/css/app.css` an Tailwind durchgereicht. CI baut die
  Front-End-Assets vor dem Pest-Lauf (`npm ci && npm run build`),
  damit `@vite()`-Direktiven das Manifest in `public/build/` finden;
  `public/build/` ist .gitignored, das Manifest entsteht pro Build.
  Charakterisierungs-Tests in `tests/Feature/Refactor/` halten den
  Pre-Refactor-Stand der Frontend-Stack-relevanten Routen für
  spätere Welle-5-Sub-Wellen fest.
- **Application-Bootstrap auf die Laravel-11+-Closure-API
  umgestellt.** `bootstrap/app.php` ist jetzt
  `Application::configure(basePath: ...)->withRouting(...)
  ->withMiddleware(...)->withExceptions(...)->create()`. Die
  `web`-Group bekommt `Language` per `$middleware->web(append: …)`
  angehängt, Custom-Aliase (`role`, `permission`,
  `role_or_permission`, `guest`) werden im `$middleware->alias(...)`-
  Block registriert, `TrimStrings`-Ausnahmen und der
  Guest-Redirect zur `route('login')` direkt im Bootstrap-Closure.
- **Service-Layer-Refactor der Fat Controller per Constructor-
  Injection.** `ProjectController`, `ChapterController`,
  `EntryController`, `ContentController` und
  `AudiovisualController` konsumieren die neuen Services über
  readonly-Properties. Die Methoden-Bodies reduzieren sich auf
  HTTP-Mapping und Service-Delegation. `ProjectController::store`
  und `update` arbeiten gegen das `ProjectData`-DTO statt gegen
  `$request[...]`-Reads; `saveText` / `saveImage` / `saveGallery` /
  `AudiovisualController::store` delegieren die fachliche Arbeit
  vollständig an die zugehörigen Content-Services.
- **`RegisteredUserController::store` von ~115 Zeilen auf ~30
  Zeilen reduziert** durch Extraktion der drei Verzweigungen
  (Reaktivierung, Onboarding, Project-Invitation) in dedizierte
  Services und durch Auslagerung des Role-Resolvers in einen
  Support-Helper.
- **Comment-Pfade konsolidiert.** Die `setStatus*`-Methoden
  heißen jetzt `setCommentStatus*` (Project, Chapter, Entry, Text,
  Image), `ContentController::updateStatus` heißt
  `updateCommentStatus`. Route-Namen einheitlich auf
  `comment.<model>.status`. Gallery- und Audiovisual-Methoden
  entwirrt: Methoden, die einen neuen Kommentar anlegen, heißen
  jetzt `comment<Model>`, Methoden, die eine Save-Submission routen,
  heißen `saveComment<Model>` — symmetrisch zu den anderen
  Modellen. Sieben Comment-Endpunkte sind auf
  `StoreCommentRequest` umgestellt, die zugehörige
  project-scoped Autorisierung bleibt im Controller, weil sie
  das konkrete Modell braucht.
- **`UserController::update` in Admin-Edit und Self-Edit
  aufgespalten.** `PATCH /users/{user}` ist der reine Admin-Pfad
  (Validation via `UpdateUserAsAdminRequest`, Authorization durch
  `role:Admin`-Middleware). `PATCH /profile` (neu) ist der
  Self-Edit-Pfad — das `roles`-Feld ist hier strukturell nicht
  zugelassen, optionaler Passwort-Wechsel mit Verifikation des
  alten Passworts über eine Closure-Rule. Die Profile-View zeigt
  jetzt auf `profile.update` (vorher `users.update`), HTTP-Methode
  korrigiert auf `PATCH`.
- **`RoleController::store` und `update` nutzen FormRequests** mit
  `authorize()` = `hasRole(Admin)` als Defense-in-Depth zur
  Constructor-Middleware. Vorher inline `$this->validate(...)`.
- **Permission-Modell auf Spatie konsolidiert.** Das frühere
  Drei-Welten-Konstrukt (Spatie + custom `UserHasPermission` +
  globale Gate-Closures) ist auf Spatie zentralisiert:
  - Die Pivot-Tabelle `user_has_permissions` ist umbenannt zu
    `project_user_permissions`. Schema/Indizes/FKs überleben den
    `Schema::rename`-Lauf, eine reversible Migration trägt das
    auf MySQL und SQLite identisch um.
  - Das Custom-Modell `UserHasPermission` ist umbenannt zu
    `ProjectUserPermission` (Datei und Klasse), Tabellen-Bindung
    explizit gesetzt.
  - Das Custom-Modell `App\Models\Role` und der Wrapper
    `App\Models\RoleHasPermission` sind gelöscht; alle Aufrufer
    nutzen jetzt `Spatie\Permission\Models\Role` und Spatie's
    `permissions()`-Relation.
  - `ProjectPolicy::view` und `::comment` sind project-scoped und
    delegieren an `ProjectPermissionService::userHasPermissionOnProject`
    (Owner-Shortcut, Admin via `before()`, sonst Pivot-Lookup).
    `ChapterPolicy` und `EntryPolicy` analog — Eingeladene mit
    `edit`/`delete`/`view`-Permission greifen jetzt überall durch
    statt nur in den Project-Pfaden.
- **`ProjectController::getAllProjects` auf den Service
  verschlankt.** Die 25-Zeilen-Query (Admin-Pfad inline +
  Nicht-Admin-Pfad über `invitations.guest_id`) ist auf einen
  Service-Call zusammengeschmolzen. Eingeladene werden jetzt
  über die Permissions-Tabelle resolved (konsistent mit der
  Permission-Welt) statt über `invitations`.
- **`media_content`-Pivot sauber polymorph.** Die alten Spalten
  `media_content_id`, `media_contentable_id`,
  `media_contentable_type` sind durch `content_id`,
  `content_type`, `parent_id`, `parent_type` ersetzt. Übergang
  in mehreren Schritten:
  - Neue Spalten in einer eigenen Migration angelegt, Daten-
    Backfill in derselben Migration (`content_id` 1:1 aus
    `media_content_id`, `parent_id` aus `media_contentable_id`,
    `parent_type = Entry::class`, `content_type` mit Spezialfall
    für historisch falsch getaggte Gallery-Rows).
  - `MediaContent::content()` und `MediaContent::parent()` als
    saubere `morphTo`-Beziehungen ergänzt;
    `MediaContent::text()/image()/gallery()/audiovisual()` liest
    aus `content_id`, `MediaContent::entry()` aus `parent_id`,
    `Entry::mediaContent()` aus `parent_id`.
  - `mediaContents()` und `project()` auf den vier Content-
    Modellen ergänzt — `project()` navigiert vom Content über
    den Pivot zum Entry → Chapter → Project. `Image::project()`
    delegiert an `Gallery::project()`, weil Images über
    `gallery_id` hängen. `Image::gallery()` als `belongsTo`
    ergänzt — der Rückweg fehlte vorher.
  - Services schreiben in einem Doppelschreibungs-Übergang
    parallel in alte und neue Spalten, danach ausschließlich in
    die neuen.
  - Controller- und View-Reads (`ProjectController::getParentText`,
    `allData`, `chapters/index.blade.php`, `preview/index.blade.php`,
    `preview/pdf.blade.php`, `contents/comment.blade.php`) sind
    auf `content_type` / `content_id` / `parent_id` umgestellt;
    Diskriminator-Vergleiche und URL-Parameter folgen.
  - Eine Followup-Migration nimmt die NOT-NULL-Constraints von
    den alten Spalten, eine weitere droppt sie vollständig.
    `MediaContent::$fillable` führt nur noch die neuen Spalten,
    `AuditMediaContent` ist komplett auf neue Spalten
    umgeschrieben.
  - Beifang: der historische Gallery-Schiefstand
    (`GalleryService::attachToEntry` setzte
    `media_contentable_type=Image::class`) ist beim Backfill auf
    `Gallery::class` korrigiert; ein latenter Bug in
    `GalleryService::detachFromEntries` (suchte unter
    `Gallery::class`, fand Rows aber als `Image::class`) ist
    damit strukturell weg. Gallery-Kommentare zeigen jetzt
    korrekt „Gallery" als Type-Label statt „Image".
- **Major-Upgrade-Welle: Stack auf den aktuellen Stand gehoben.**
  Sieben sequenzielle Sprünge in dedizierten Branches:
  - PHP 8.1 → 8.2 → 8.3 → 8.4 (mit verschränktem PHP-8.4-+-
    Laravel-9-Sprung in einem Branch, weil Larastan v1 an
    PHPStan-BetterReflection-Stubs für PHP 8.4 hängenblieb und
    Larastan v2 Laravel 9 voraussetzt).
  - Laravel 8 → 9 → 10 → 11 → 12 (`laravel/framework ^12`).
  - Spatie-Pakete auf jeweils kompatiblen Major:
    `spatie/laravel-permission ^6` (Middleware-Namespace
    `Middlewares\*` → `Middleware\*` Singular),
    `spatie/laravel-activitylog ^4` (neue API-Konvention mit
    `getActivitylogOptions(): LogOptions`, 18 Modelle entsprechend
    angepasst, zwei neue Schema-Spalten `batch_uuid` und `event`
    per `vendor:publish`), `spatie/laravel-translatable ^6`,
    `spatie/laravel-welcome-notification ^2.5`,
    `spatie/laravel-ignition ^2`.
  - Test-Tooling: Pest 1 → 2 → 3, PHPUnit 9 → 10 → 11,
    `nunomaduro/collision` ^6 → ^7 → ^8, `pestphp/pest-plugin-
    laravel` synchron.
  - Larastan v1 → v2 → v3 (Repo-Move `nunomaduro/larastan` →
    `larastan/larastan ^3`, bringt PHPStan v2 mit), Pint im
    Laravel-Preset, Carbon v2 → v3 (Methodennamen-Wechsel in
    `MyCustomWelcomeNotification`).
  - Container-Stack: Ubuntu 22.04 jammy → 24.04 noble, Node 20
    LTS → 22 LTS, PCOV als Coverage-Driver, idempotenter
    `storage:link`-Auto-Setup im `start-container`.
  - `bootstrap/app.php`-Aliase nachgezogen, `app/Http/Kernel.php`,
    `app/Console/Kernel.php` und `app/Exceptions/Handler.php`
    sind durch den Closure-Bootstrap obsolet.
  - 18 Modelle: `$fillable`-PHPDoc von `@var array<int, string>`
    auf `@var list<string>` (PHPStan-v2-Kovarianz), 51
    `@var \App\Models\User`-Hints in fünf Test-Dateien für die
    Larastan-v2-Inferenz, 45 `@var \Tests\TestCase $this`-Hints
    in zwei Pest-3-Test-Dateien.
  - `CreateAdminUserSeeder` von `env()` auf `config()` umgestellt
    (Larastan-v3-Regel `noEnvCallsOutsideOfConfig`); neue
    `config/admin.php`.
  - `laravelcollective/html` (abandoned) raus, native Blade-Forms
    in `roles/create.blade.php` und `roles/edit.blade.php`
    (`@csrf`, `@method('PATCH')`, `@checked`, `old('field',
    $model->field)`-Fallback).
  - `facade/ignition` → `spatie/laravel-ignition`,
    `swiftmailer/swiftmailer` durch Symfony Mailer ersetzt,
    `fideloper/proxy` durch
    `Illuminate\Http\Middleware\TrustProxies` (Laravel-eigene
    Implementation).
  - Larastan-Baseline regeneriert: vormals 198 v1-Einträge, im
    Verlauf v2-130, dann 15 v3-Einträge nach dem PHPDoc-Sweep
    und vier Smell-Fixes im `ProjectController`.
- **CI auf Hard-Fail für `composer audit`.** Der Soft-Fail-
  Übergang aus dem Sicherheitsnetz-Release ist abgeschlossen — ein
  neuer CVE im Lock bricht ab jetzt den Build. `continue-on-error:
  true` und `|| true` sind raus.
- **`php artisan config:cache` läuft im CI-Pest-Job vor der
  Suite.** Defense-in-Depth gegen `env()`-Calls außerhalb von
  `config/`: Larastan fängt das statisch, der Cache-Step fängt es
  dynamisch.
- **Rate-Limit auf den Guest-Auth-Routen.** `POST /login`,
  `POST /forgot-password` und `POST /reset-password` tragen jetzt
  `throttle:6,1` als zusätzliche Middleware. Verhindert Credential-
  Stuffing auf Login und Spam auf den Password-Reset-Endpunkten.
- **Eloquent-Strict-Mode voll aktiviert.**
  `Model::shouldBeStrict()` im `AppServiceProvider` bündelt
  `preventLazyLoading`, `preventAccessingMissingAttributes` und
  `preventSilentlyDiscardingAttributes` in einem Aufruf. Aktiv
  nur außerhalb von Production.
- **`role:Admin`-Middleware statt `'admin'`-Alias** in User- und
  Role-Controller-Methoden (`index`, `edit`, `destroy` etc.).
  Konsistent mit Spatie-Permission. Custom-Alias-Registrierung
  in `bootstrap/app.php` entfernt; Settings-Route-Group
  nachgezogen.
- **„Übersetzen"- und „Projekt-Metadaten"-Buttons in
  `chapters/index.blade.php`** hinter `@can('update', $project)`.
  Vorher zeigten sie sich auch Readern; das Backend blockte sauber
  via Policy, der Frontend-Klick lief damit in 403/leere Seite —
  UX-mäßig irritierend. Mit dem Frontend-Gate sehen Reader die
  Buttons gar nicht mehr.
- **jQuery-Sortable-Init in `chapters/index.blade.php`** hinter
  `@can('update', $project)`. Reader konnten Chapter/Entries/
  Content via Drag-and-Drop visuell verschieben; das Backend
  lehnte den POST sauber ab, der UI-Zustand blieb aber bis zum
  Refresh verschoben. Sortable wird für Reader gar nicht mehr
  initialisiert.
- **Spacing am Preview-Layout (`public/css/index.css`).** Drei
  Mini-Justierungen am `.hintergrundweiss`/`.zweispaltig`/
  `.einspaltig`-Block fangen den Multicolumn-Kollaps bei
  längeren Subtitle-/Description-Texten ab. Defensiver
  CSS-Patch, kein Multicolumn-Ersatz und keine HTML-Umstellung.
- **`LogService::__construct`: `'App\Models\gallery'` →
  `Gallery::class`.** Der kleine `g` war ein Tippfehler, der auf
  einem case-sensitive Linux-Filesystem zur
  `ClassNotFoundException` geführt hätte.
- **`LogService::highlightTextDifference` und
  `ProjectController::highlightTextDifference`** von PascalCase
  auf camelCase umbenannt (sechs Aufrufer in zwei Dateien).
  Konsistent mit Laravel- und PSR-12-Standard.
- **Redundante `'created_at' => now()`-Zuweisungen entfernt** in
  vier Eloquent-Mass-Assignment-Pfaden. Eloquent setzt Timestamps
  automatisch — das manuelle Setzen war Cargo und wird unter
  `preventSilentlyDiscardingAttributes` als
  `MassAssignmentException` sichtbar. Query-Builder-Pfade
  behalten ihr `'created_at'`, weil der Query Builder keine
  Timestamps automatisch setzt.
- **`Text::$fillable` bereinigt** — `'id'` und `'position'` raus.
  Die `position`-Spalte ist seit einer früheren Migration nicht
  mehr in der DB, die Mass-Assignment-Liste hatte sie aber nie
  verloren; unter Strict-Mode löste das eine
  `MissingAttributeException` aus. Schema-Bereinigung der toten
  Spalte verbleibt für einen separaten Schema-Refactor.
- **Inkonsistenz-Bug in `saveGallery` mitkorrigiert**: der direkte
  Update-Pfad las vorher `$request['title']` / `subtitle` /
  `description`, das Frontend schickt aber nur die
  `galleryTitle`-Variante. `GalleryData::fromRequest` akzeptiert
  beide Varianten und priorisiert die `gallery*`-Prefix-Form.
- **`ChapterController::getChapterComment` konsistent zu den
  anderen `get*Comment`-Methoden.** Project, Entry, Text und
  Image geben das `getComments`-Array direkt zurück; Chapter
  machte `redirect()->back()->with(['comments' => …])`. Jetzt
  symmetrisch.
- **`MyCustomWelcomeNotification`-Konstruktor** auf
  `Carbon $validUntil` statt `CarbonInterface $validUntil`. Die
  Eltern-Klasse typed die Property selbst als `Carbon`; die
  redundante `$this->validUntil = $validUntil`-Zuweisung nach
  `parent::__construct()` ist mit raus.
- **`[Unreleased]`-Block des Changelog konsolidiert.** Die zuvor
  chronologisch protokollierten ~50 Refactor-Schritte sind zu
  thematisch kohärenten Keep-a-Changelog-Sektionen
  zusammengefasst, organisationsinternes Vokabular ist
  entfernt. Inhaltlich verlustfrei, lesbar für externe Reviewer.

### Entfernt

- **`log.detail`-Route und Legacy-Log-Detail-Sicht** (A4 ·
  2026-08-21). Der `GET /project/{projectId}/log/{id}`-Endpunkt
  war seit Phase 5ab.3 durch das Livewire-Verlauf-Panel abgelöst,
  keine Verlinkung mehr im Bestand. Weg: Route, `ProjectController::getDetails`,
  Helper `highlightTextDifference`, `resources/views/logs/log.blade.php`,
  `Source`-Import in `ProjectController`, fünf `in_array`-Checks in
  Rail/Header/Preview-Views — insgesamt ~285 Zeilen.

- **`resources/js/modal-wire.js`** (I1 · 2026-08-21). Das
  Wire-Up-Modul war nur für die `entryModal`-Trigger-Bridge da; mit
  dem Alpine-Umbau des Modals entfällt es ersatzlos.

- **`RegisteredUserController` und `auth/register.blade.php`**
  (B12 · 2026-08-20). Der User-Anlage-Flow ist in `UserController`
  konsolidiert, die Blade-Sicht lebt jetzt als `users/create.blade.php`
  auf Design v7.

- **`user.info`-Endpunkt aus dem Vor-Livewire-Berechtigungs-Flow**
  (B12 · 2026-08-20). `ProjectController::inviteUserForProject` und
  die tote `users/create.blade.php`-Detail-Sicht sind gelöscht —
  ersetzt durch die Livewire-`project-permissions`-Komponente.

- **Breeze-Reste `auth-card`, `auth-session-status`,
  `auth-validation-errors`** (B12 · 2026-08-20). Nur noch von den
  jetzt aufgeräumten Auth-Sichten referenziert; die Sichten nutzten
  ohnehin einen nie existierenden `<x-guest-layout>` — waren daher
  seit Ewigkeiten faktisch kaputt.

- **`auth/verify.blade.php` und `auth/passwords/{confirm,email,reset}.blade.php`**
  (B12 · 2026-08-20). Duplikate ohne Aufrufer.

- **Verwaiste Blade-View `resources/views/contents/comment.blade.php`**
  (Q3-Härtung 2026-08-19 / Legacy FIND-01). Kein `view()`-Aufruf mehr
  im Bestand — nur ein Kommentar-Verweis in `resources/js/datatable.js`.

- **Verwaiste Blade-View `resources/views/projects/metadata.blade.php`**
  (Q3-Härtung 2026-08-19 / Legacy FIND-02). Der aktive Metadaten-Tab
  läuft über `ProjectController::editMetaData` → `projects.create`;
  die alte `projects.metadata`-Blade hatte 0 Referenzen.

- **Verwaister Sprach-Key `message_confrim_password`** (Q3-Härtung
  2026-08-19 / Legacy FIND-10). Der Tippfehler-Key existierte parallel
  zum aktiven `message_confirm_password` in `resources/lang/de.json`
  und `en.json` und wurde nirgends referenziert. Dedupliziert.

- **Bootstrap-Icons als Icon-Set und CDN-Include** (Phase 5-D.2).
  Die `bootstrap-icons`-Dependency ist raus, 84 `bi-*`-Klassen in
  13 Views auf `<x-icon>` gehoben, das CDN-`<link>` aus dem
  Layout entfernt. Font-Awesome bleibt vorerst für die PDF-
  Templates.

- **Alte Top-Bar aus 5a/5b** (Phase 5-D.3). Die horizontale
  `<nav>` mit Projekt-/Nutzer-/Kommentar-Menüs ist raus; Inhalte
  wandern auf die Rail.

- **DataTables-jQuery-Init an der Projektliste** (Phase 5-D.4).
  Der Bestand nutzte `$('#projectList').DataTable(...)` mit
  fest-verdrahteter deutscher Übersetzung. Aktive Filterung läuft
  jetzt Alpine-basiert über die Filter-Chips.

- **Bootstrap-3-Legacy-CSS für `li.chapter`/`li.entry`/`li.item`**
  (Phase 5-D.6b). Die grauen Background-Farben plus Drag-Icon-
  Sprite haben visuell einen Kapitel-Kasten simuliert, obwohl der
  Chapter-Wrapper transparent war. `.list-group` und `.entry.group`
  in `bootstrap-utilities.css` ohne Background/Rahmen.

- **Modal-Kaskade für Add-User in `projects/create`**
  (Phase 5d.4). Die drei `<x-ui.modal>`-Blöcke `userInvitation`
  (E-Mail einladen) und `userModal` (bestehende User bearbeiten)
  sind rausgeworfen; ihre Aufgabe übernimmt jetzt die
  `<livewire:project-permissions>`-Sicht mit Sidebar + Detail-
  Panel + Invite-Modal in der Volt-Component. Der dritte
  `newUserInvitation`-Modal bleibt vorerst — er hängt am
  `check.email`-Session-Flow und wird mit dem sauberen 5d.7-
  Invite-Neubau abgeräumt (Backlog).

- **DataTables-jQuery-Init an der Nutzer:innen-Liste**
  (Phase 5d.3). Analog zur Projektliste läuft die neue
  Nutzer:innen-Sicht ohne DataTables — Filter-Chips nach Rolle
  und Alpine-Suche ersetzen den Bestand-Init.

- **Chevron-Toggle in Chapter- und Entry-Aktionen** (Phase
  5-D.6b). Das Auf-/Zuklappen läuft komplett über den Sidebar-
  Tree; die zwei `<a>`-Trigger und die zugehörigen jQuery-
  Handler `collapseExpand()` / `collapseExpandEntry()` sind raus.

- **Export-Footer am Seitenende** (Phase 5-D.6b P3.15). PDF /
  Preview / Download-ZIP wandern ins ⋮-Menü in der sticky
  Editor-Chrome-Bar; der `@section('footer')`-Block ist raus.

- **Sechs Modify-Modal-Handler und -Trigger** (Phase 5c). Die
  jQuery-Handler `.open-ModifyChapter`, `.open-ModifyEntry`,
  `.open-ModifyGallery`, `.open-ModifyText`, `.open-ModifyImage`
  und `.audiovisual-modify` sind ersatzlos gefallen — samt ihrer
  Ajax-Roundtrips gegen `text.edit`, `image.edit` etc. Die Pencil-
  Icon-Buttons in den Content-Cards ebenfalls raus. Die zugehörigen
  Add-Modals (`myModal`, `entryModal`, `contentModal`, `imageModal`,
  `audiovisualModal`) bleiben bestehen, weil sie in der 5c-Runde
  noch für „hinzufügen" gebraucht werden.

- **Drei toter Bootstrap-Boilerplate-Dateien gelöscht** durch die
  Laravel-11+-Closure-API-Umstellung:
  - `app/Http/Kernel.php` (Middleware-Stack, Middleware-Groups
    und Aliase wandern in `bootstrap/app.php`).
  - `app/Console/Kernel.php` (Custom-Commands unter
    `app/Console/Commands/` werden in Laravel 11+ automatisch
    geladen).
  - `app/Exceptions/Handler.php` (60 LoC, ausschließlich
    Boilerplate; `$dontFlash` für Passwort-Felder wandert in den
    `withExceptions(...)`-Closure).
- **Sechs Stock-Middleware-Subklassen aus `app/Http/Middleware/`
  gelöscht** — alle waren 1:1-Subklassen der Framework-Defaults
  ohne projekt-spezifische Logik: `Authenticate`, `EncryptCookies`,
  `PreventRequestsDuringMaintenance`, `TrimStrings`,
  `TrustProxies`, `VerifyCsrfToken`. Verhalten wandert in die
  Bootstrap-Closures.
- **`App\Http\Middleware\IsAdmin` gelöscht.** Custom-Middleware,
  die `auth()->user()->hasRole('Admin')` prüfte — exakt das macht
  Spatie's `RoleMiddleware` per `role:Admin`-Alias.
- **`App\Models\Role`, `App\Models\RoleHasPermission` und
  `App\Models\UserHasPermission` gelöscht** (vormals umbenannt
  zu `ProjectUserPermission`, dann durch Spatie's eigene Modelle
  und Pivot-Tabelle ersetzt). Custom-Wrapper ohne Mehrwert.
- **`app/Traits/CommentTrait.php` gelöscht.** Die fünf
  Trait-Methoden (`commentAsUser`, `replyAsUser`, `editAsUser`,
  `deleteAsUser`, `status`) wandern in den `CommentService`. Die
  `comments()`-MorphMany-Relation lebte schon direkt in den acht
  Modellen; der Trait war nur noch Methoden-Container.
- **Cargo- und tote Helper aus den Fat Controllern**: `mapData()`
  in `ProjectController`, `ContentController` und
  `AudiovisualController`; fünf `protected`-Permission-Helper aus
  `ProjectController` (`getUsersForThisProject`,
  `getCurrentUsersPermissions`, `getSelectedPermissionUser`,
  `getSelectedPermissionUserPluck`, `getRoleSelectedUser`); die
  Upload-/Translation-/Comment-Helper `setImage`, `attachMedia`,
  `detachMedia`, `updateText`, `updateImage`, `uploadAudio`,
  `youtubeID` aus `ContentController` und `AudiovisualController`;
  der duplizierte `getSource`-Helper. Tote Imports
  (`Storage`-Facade, `UploadTrait`, `MediaContent`, `Str`,
  `App\Models\Image`, `Mpdf\Pdf`, `Invitation`, `ModelHasRole`)
  aufgeräumt.
- **Tote Eloquent-Beziehungen auf den abgelösten Pivot-Spalten**:
  `MediaContent::media()`, `Comment::media()`, `Text::medias()`,
  `Text::entry()`, `Image::medias()`, `Image::entry()`,
  `Image::parentEntry()`. Alle ohne Konsumenten in `app/`,
  `resources/` oder `tests/`.
- **Auskommentierter Switch-Case-Block** (22 Zeilen toter Code)
  in der ehemaligen `CommentTrait::commentAsUser`.

### Behoben

- **Multi-Upload · Upload-Progress war auf Sail nicht sichtbar**
  (2026-09-10). Nach Abschluss aller Uploads lief ein Reload nach
  600 ms — auf Localhost sind Uploads schnell genug durch, dass
  die „Wird hochgeladen …"- und „✓ Fertig"-Meldungen in den
  Ghost-Kacheln kaum wahrnehmbar wurden. Reload-Delay auf 2500 ms
  hochgezogen (beide Upload-Pfade in `gallery-block.blade.php`),
  damit der Zustand vor dem Reload sichtbar bleibt.

- **Multi-Upload überschrieb Dateien im selben Sekundenfenster**
  (2026-09-10). `ImageService::uploadImageFile` (und
  `ProjectImageService::store`) generierten den Filename aus
  `date('Ymd').'_'.time()` — bei paralleler Multi-Upload landeten
  mehrere Dateien in derselben Sekunde und teilten sich denselben
  Namen. `storeAs` überschrieb, alle Image-Rows zeigten am Ende
  auf dasselbe File, und im Editor erschien N-mal dasselbe Bild
  statt der N tatsächlich hochgeladenen. Der Fix ersetzt den
  `time()`-Suffix durch `Str::random(10)` (~5.9·10¹⁷ Kombinationen),
  ohne die datumsbasierte Verzeichnis-Sortierung zu opfern.
  Latenter Prod-Bug, den G6-2 beim Testen aufgedeckt hat.

- **Bild aus Galerie löschen führte auf 404**
  (2026-09-10). Das Delete-Formular am Bild-Overlay in
  `image-tile.blade.php` sendet nur CSRF-Token und Method-Override,
  kein `project`-Feld. `ImageBlockController::destroyImage`
  redirected aber auf `'projects/'.$request->project.'/edit'` —
  bei fehlendem `project`-Payload landete der Redirect auf
  `/projects//edit` und damit im 404. Die Projekt-ID wird jetzt
  vor dem Soft-Delete aus dem Image selbst gelesen
  (`Image::project()` navigiert über Gallery-Chain zum Projekt),
  mit Request-Fallback und Back-Fallback für den seltenen
  Waisen-Fall.

- **Reader-Vokabular konsequent auf Kapitel · Abschnitt · Inhalt**
  (2026-09-09). Ein Zwischenstand hatte die Vokabelachse
  „Kapitel · Abschnitt · Inhalt" auf mehreren Reader-Locale-Keys
  gegen „Kapitel · Eintrag · Inhalt" ausgetauscht — der
  `VocabularySweepTest` hat den Rückfall gefangen. Zurück auf das
  Firmen-Glossar: `reader_entry_sources_heading`,
  `reader_scope_entries`, `reader_chapter_card_entries` und
  `reader_chapter_entries_count` in de.json/en.json sowie Blade-
  und CSS-Kommentare wieder auf „Abschnitt / Sections".

- **Reader-Feinschliff aus dem Design-Review 3** (2026-09-09).
  Kapitel-Karten auf der Startseite standen ungleich hoch, weil
  Beschreibungstexte unterschiedlicher Länge die Kachelhöhe
  aufschlugen; Body jetzt als Flex-Column, Beschreibung per
  `line-clamp: 4` gedeckelt, Meta-Zeile klebt am unteren Rand.
  Portrait-Bilder im Multi-Page-Reader saßen zentriert im breiten
  Container und lösten die vom Handoff geforderte gemeinsame linke
  Satzkante auf; `object-position: left center` bringt sie
  bündig zurück. Der aktive Kapitel-Marker in der Rail brach
  „HIER" in eine eigene Zeile — Link jetzt als
  `flex: space-between`, Marker rechtsbündig auf der Titelzeile.
  Fußzeilen-Adressspalte zerfiel durch die `<p>`-Standardmargins
  des Rich-Text-Renderings in Einzelzeilen; Absatzabstände auf
  Zeilenabstände reduziert. Zitierhinweis nennt jetzt den
  Herausgeber (`reader_footer_cite_publisher`, Default
  „berlinHistory e.V. und Aktives Museum e.V.") und nutzt die
  Route-basierte URL statt einer Handbau-String-Konkatenation.

- **Quellenverwaltung: Zähler-Divergenz, Pluralisierung, Merge-
  Button, Zitier-Settings** (Q4-Etappe 3 · C0-Nachreview ·
  2026-09-07). Fünf Follow-ups aus dem Smoke-Test der neuen
  Quellenverwaltung: (a) Der Referenz-Zähler wurde vorher als
  Cache-Array über die aktuell gefilterte Sources-Liste berechnet
  und lieferte in Detail und Liste unterschiedliche Werte;
  ersetzt durch die dedizierte Methode
  `referenceCountFor(int $sourceId)`, die Text/Image/AV pro Source
  frisch zählt und damit garantiert konsistent bleibt. (b) Der
  Detail-Text zeigte fälschlich „Noch nicht referenziert" auch bei
  count=1, weil der Pluralisierungs-String
  `sources_admin_referenced_n_times` keine expliziten Range-Marker
  hatte und Laravel bei Deutsch die 0-Form als „singular" wählte;
  Fix: `{0}|{1}|[2,*]`-Ranges in de.json und en.json. (c) Der
  Merge-Assistent lief immer in eine neue project-scoped Row, statt
  auf eine bereits vorhandene (z. B. per AV-Backfill entstandene)
  Zieltabelle-Row zu merged; `SourceMigrationService::runFor`
  sucht jetzt vor dem `new Source` nach gleichnamigen scoped Rows.
  (d) AV-Referenzen (`copyright_id`/`origin_id`) werden beim Merge
  mit-umgebogen, `candidatesFor` findet auch AV-referenzierte
  Alt-Sources, `referencedElsewhere`-Check inkludiert AV. (e) Der
  Bestätigen-Button im Merge-Modal wurde nie aktiv, weil
  `wire:model` ohne `.live` das Ziel-Select serverseitig nicht
  aktualisierte. Zusätzlich: `ProjectController::update`
  persistiert jetzt tatsächlich `citation_depth` und
  `source_required` — die Radio-Auswahl im Edit-Screen sprang
  vorher nach dem Speichern immer wieder zurück. Zwei neue
  Feature-Test-Suites: `CitationSettingsUpdateTest` (2 Fälle) und
  `ReferenceCountPluralizationTest` (6 Fälle über de + en).

- **`wire:model.blur` in Livewire 4 feuerte keinen Commit** (A7 ·
  2026-08-21). Das Blur-Sync im `inline-editor.blade.php` synced
  zwar den ephemeral state, schickte aber keinen Server-Roundtrip
  — mit dem Ergebnis, dass Bild-Titel und -Beschreibung nicht
  gespeichert wurden. Diagnose per Chrome-in-Chrome mit Karl.
  Workaround: expliziter `@blur="$wire.$commit()"`-Handler
  ersetzt den `.blur`-Modifier. Verhalten aus Nutzersicht
  identisch, Wert wird beim Verlassen des Feldes gespeichert.

- **Livewire-Parse-Fehler `Unexpected token 'if'`** (A7 ·
  2026-08-21). Der `@saved.window`-Handler im `inline-editor`
  enthielt seit Q3-Politur G1 ein `if`-Statement — Alpine
  wickelt Event-Handler in einen Expression-Kontext, was das
  Statement bricht. Mit A7 wurde der Handler auf viele Gallery-
  und Image-Instanzen dupliziert und der Parse-Fehler sichtbar,
  blockierte die Save-Bridge. Fix: dieselbe Filter-Bedingung
  als Kurzschluss-Expression (`… && showChip()`).

- **Cursor-Bug im Rich-Text-Editor der Content-Blöcke**
  (2026-08-21). Beim Editieren von Content-Text-Blöcken löschte
  Backspace am Anfang ins Leere, am Ende wirkte er erst nach
  Fokus-Wechsel — vereinzelt blieben getippte Zeichen stehen.
  Ursache: `clipboard.dangerouslyPasteHTML(html)` beim Editor-
  Init pastet an die aktuelle Selection (bei frisch instanziiertem
  Quill 1.x = null) und hinterlässt einen unsichtbaren Leer-
  Vorspann im Delta. DOM und Delta divergieren, Insert/Backspace
  treffen falsche Positionen. Jetzt: `root.innerHTML = html` +
  `quill.update('silent')` — Quill parst den DOM und synchronisiert
  Delta + Selection sauber.

- **`LogService`-Konstruktor lief mit `null`-Model** (I3 ·
  2026-08-21). `new LogService;` in `ProjectController::getCurrentLog`
  rief den Konstruktor ohne den erforderlichen `$model`-Slug — die
  Query lief mit `subject_type = null` und lieferte immer eine
  leere Aktivitäts-Liste. Jetzt `new LogService('text')` analog
  zum Route-Namen `log.text`.

- **Fehlerseiten rendern ohne Rail, wenn kein User eingeloggt ist**
  (B12 · 2026-08-20). 403/404/419/500 zogen bisher immer
  `projects.layout` inklusive Rail — auch für unauthenticated
  Aufrufer. Neu: `Auth::check()` schaltet auf ein minimales
  Guest-Error-Layout. Sichtbar geworden ist das an einem
  abgelaufenen Welcome-Notification-Link.

- **`invite_user_not_found`-Text ohne Werkbank-Slug**
  (B12 · 2026-08-20). Der User-facing Text enthielt „Neuanlage
  folgt in 5d.7" — Slug ist raus, neuer Text verweist auf die
  jetzt bestehende Anlage-Sicht. Der Locale-Key fehlte in `en.json`
  komplett und wurde ergänzt.

- **Modal-Body scrollt bei langen Inhalten** (B1-Followup ·
  2026-08-20). `<x-ui.modal>` bekommt `max-h-[calc(100vh-4rem)]`
  und `flex flex-col`; der Body-Slot ist `flex-1 overflow-y-auto`.
  Sichtbar war das am „Ausstellung exportieren"-Modal in
  `chapters/index.blade.php`, das unter den Viewport-Rand lief.
  Kurze Modals sind unverändert.

- **`profile_pw_strength_0`-Leerkey rendert nicht mehr wörtlich**
  (Q3-Politur 2026-08-20). Das Passwort-Stärke-Label für den
  Ausgangs-Zustand war als leerer Locale-Key definiert und lieferte
  je nach Missingness-Handling den Schlüssel-Text
  („profile_pw_strength_0") ins UI. Das `pwStrengthLabels`-Array
  liefert jetzt hart `''` für Stärke 0, der Locale-Key ist aus
  `de.json` und `en.json` raus.

- **Fokus-Ring auf Formular-Feldern wieder sichtbar** (Q3-Härtung
  2026-08-19 / LIVE-01, WCAG 2.4.7). Der Tailwind-Reset hatte
  `outline: none` an `input`/`textarea`/`select` gesetzt — der
  Tastatur-Fokus war nur am Border-Farb-Wechsel erkennbar, unter
  AA nicht ausreichend. Neuer Regel-Block in `app.css` mit
  `focus-visible: outline 2px solid var(--color-primary)` +
  `outline-offset: 2px` — Maus-Klicks bleiben rahmenfrei.

- **Metadaten-Save-Footer verdeckt Quill-Toolbar nicht mehr**
  (Q3-Härtung 2026-08-19 / LIVE-UX-03). Auf Viewports < 800 px
  überlappte der Sticky-Save-Footer die Formatier-Leiste des
  Beschreibungs-Editors. `z-20` am Footer + `mt-16` schaffen den
  Puffer.

- **Quill-Toolbar-Buttons erhalten `aria-label`** (Q3-Härtung
  2026-08-19 / LIVE-02, WCAG 4.1.2). ~85 Formatier-Buttons in
  `.ql-toolbar` (`ql-bold`, `ql-italic`, `ql-list[value=ordered]`, …)
  waren für Screenreader unbenannt. Neuer `resources/js/quill-a11y.js`
  labelt via `MutationObserver` und CSS-Klassen-Mapping — greift für
  beide Quill-Init-Stellen (Volt `rich-text-editor.js` + Inline-
  Script in `chapters/index.blade.php`) ohne die Stellen selbst
  anzufassen. Zusätzlich Fallback-Label für Select-Picker
  (Font/Size/Header).

- **`resendInvitation`-Test auf POST-Route umgestellt** (Q3-Härtung
  2026-08-19, Follow-up zu SEC-02). `UserControllerTest` rief die
  alte GET-URL — schlug nach dem Route-Umbau mit 404 fehl. Umgestellt
  auf `route('resend.invitation', $invitee->id)` per POST.

- **Chapter/Entry-Rahmen aus Bootstrap-Legacy-CSS** (Phase 5-D.6b).
  Der visuell wahrgenommene „Kapitel-Kasten" um Titel + Untertitel
  + Description war der `<ul class="list-group">` der Entries mit
  einem Bootstrap-3-Border und weißem Background aus
  `bootstrap-utilities.css`, plus ein doppelter Border auf
  `.entry.group`. Beide Regeln neutralisiert; die Entry-Karten
  kommen jetzt allein aus dem Blade-Template.

- **Ghost-Input mit User-Agent-Border** (Phase 5-D.6b). Chrome und
  Firefox rendern `<input>` per Default mit einem inset-Border,
  auch bei `border: 0`. `appearance: none` plus `outline: none`
  plus `ring: 0` im Ruhezustand; nur bei `focus-visible` ein
  weicher `ring-2 ring-brand-bar/50`.

- **Scroll-Sprung überdeckt vom sticky Chrome** (Phase 5-D.6b).
  Klick auf einen Sidebar-Tree-Link scrollt jetzt mit
  `scroll-margin-top: 96px` an allen `[id^="anchor_"]`-Elementen
  — die sticky Editor-Chrome-Bar überdeckt kein Sprung-Ziel mehr.

- **Gallery-Grid rendert nicht mehr** (Phase 5-D.6). Die Regel
  `.gallery_container { display: grid }` in
  `public/css/crowdcuratio.css` wurde seit dem Vite-Umbau nicht
  mehr geladen; Bilder kollabierten auf 40 px Höhe. Grid direkt
  am Element mit `grid-cols-[repeat(auto-fill,minmax(180px,1fr))]`
  und `h-[200px]` pro Kachel.

- **`overflow-x-hidden` bricht `position: sticky`** (Phase 5-D.6b).
  Der Canvas-Wrapper hatte `overflow-x-hidden`, was Chrome/Firefox
  implizit als `overflow-y: auto` interpretieren und `position:
  sticky` in Kind-Elementen deaktivieren. Auf `overflow-x-clip`
  umgestellt.

- **IBM Plex Sans wurde nicht gerendert** (Phase 5-D.7). Die
  Webfonts waren in `app.js` importiert, aber Tailwind hatte
  keinen `--font-sans`-Token im `@theme`, sodass Chrome auf
  `ui-sans-serif` (San Francisco) fiel. Font-Token gesetzt.

- **Entry-Feld-Save mit 403** (Phase 5c). `Entry` fehlte als
  einziges Content-Modell eine `project()`-Navigations-Methode —
  `resolveProject()` im Inline-Editor lieferte `null`, Gate
  antwortete `403 Forbidden`. Sichtbar geworden erst beim Rich-Text-
  Editor für Beschreibungs-Felder. Fix: `Entry::project()`-Methode
  ergänzt, die über `chapter?->project` navigiert. Konvention ist
  jetzt konsistent über alle sechs Content-Modelle.

- **Gallery-`::project()`-Kollision mit Attribute-Magic** (Phase 5c).
  `$model->project` (Property-Access) triggerte Laravels Attribute-
  Magic, die eine `Relation`-Instanz vom `project()`-Method-Body
  erwartete. `Gallery::project()` liefert aber direkt ein `?Project`
  (Tree-Traversal), was zu „must return a relationship instance"
  führte. Fix: `resolveProject()` ruft die Methode direkt und
  prüft `instanceof Relation` — Duck-typed statt Attribute-magisch.

- **Layout-Kollaps der Gallery-Bilder** (Phase 5c). Die alte CSS-
  Regel `.gallery_item .img { height: 300px }` griff unter Tailwind-
  4-Preflight nicht mehr durchgängig — Kacheln fielen auf 39 px
  Caption-Höhe zusammen und Bilder wurden unsichtbar. Fix: Höhe
  zusätzlich als Tailwind-Utility (`h-[300px]`) direkt am
  Element, plus `grid-auto-rows: minmax(300px, auto)` am
  Container.

- **Blade-Compiler tokenisiert `<livewire:…>` in Kommentaren**
  (Phase 5c). Ein zweites Auftauchen desselben Musters aus 5a:
  wenn `<livewire:inline-editor>` wörtlich in einem Blade- oder
  JS-Kommentar steht, ersetzt der Compiler den Tag durch das
  fertig gerenderte Component-Markup mitten in einer `<script>`-
  Sektion, HTML- und JS-Parser laufen auseinander („Unexpected
  identifier 'html'"). Fix: den Namen ohne spitze Klammern
  ausschreiben. Kommentare in `chapters/index.blade.php`
  entsprechend gehärtet.

- **Tastatur-Reorder persistierte nicht** (Phase-5b-Hotfix). Der
  `fetch`-Handler in `keyboard-reorder.js` schickte den Payload
  als JSON-String über
  `URLSearchParams({data: JSON.stringify(...)})`, `ChapterController::saveDragAndDrop`
  erwartete aber ein form-encoded Array (identisch zum SortableJS-
  Maus-Pfad). Server antwortete `200 "Nothing to update"`, Frontend
  announced Erfolg, DB-Update fiel aus — sichtbar erst nach Page-
  Reload. Fix: form-encoded Array-Notation über `params.append('data[data][]', id)`.
  Wire-Format-Pinning-Test ergänzt, der den PHP-fpm-Roundtrip via
  `http_build_query`/`parse_str` nachbildet.

- **DOM-Rollback bei fehlgeschlagenem Tastatur-Reorder** (Phase-5b-
  Hotfix). Vorher: bei Server-Fail (403, 419, 500) blieb der
  optimistische DOM-Swap stehen, Frontend zeigte eine falsche
  Reihenfolge bis zum nächsten Reload, keine Rückmeldung. Jetzt:
  Swap wird zurückgerollt, deutsche Fehler-Announcement in die
  Live-Region („Verschieben von … fehlgeschlagen, Reihenfolge
  zurückgesetzt.").

- **Layout-Slot-Trim ohne String-Cast** (Phase-5b-Hotfix). `trim($content)`
  in `<x-layout>` warf potenziell `TypeError`, wenn Blade ein
  `Stringable`-Objekt statt eines Strings als Slot lieferte.
  `trim((string) $content)` härtet den Test.

- **Theme-Toggle-Icon im Navi-Header war unsichtbar** (Phase 5a.V,
  T1 + T2 + T3). Drei Bugs überlagert:
  - **T1 (View-Pattern):** Das ursprüngliche Markup hatte zwei
    `<template x-if>` mit eingebetteter `<x-ui.icon>`-Blade-Komponente.
    Das HTML-Standard-`<template>` hält seinen Inhalt außerhalb des
    regulären DOM-Trees; der Alpine-Clone-Insert war in dieser
    Konstellation unzuverlässig. Umgestellt auf `x-show` mit zwei
    direkt im Button eingebetteten `<span>`-Wrappern (jeweils eine
    Lucide-Variante). Plus globale
    `[x-cloak] { display: none !important }`-Regel in
    `resources/css/app.css` und `x-cloak` an beiden Spans, damit beim
    Page-Load nicht beide Icons gleichzeitig aufflackern, bis der
    Store-State hydriert ist.
  - **T2 (Store-Race-Condition):** Browser-Verifikation zeigte, dass
    `$store.theme` trotz T1-Fix leer war (`{}` statt
    `{current, toggle, set}`). Ursache: Livewire 4 bringt sein eigenes
    Alpine mit und startet es früh — das `alpine:init`-Event war
    bereits gefeuert, als `resources/js/theme.js` als Vite-Module
    geladen wurde und seinen Listener registrierte. Folge: der Store
    wurde nie registriert; beide `x-show`-Bedingungen (gegen
    `$store.theme.current`) evaluierten zu `undefined`, kombiniert mit
    `x-cloak` blieben beide Icons unsichtbar. Robust gefixt: `theme.js`
    prüft beim Module-Load, ob `window.Alpine` schon da ist, und
    registriert dann sofort; sonst nimmt es den Listener wie bisher.
  - **T3 (fehlender x-data-Scope):** Nach T2 war der Store da, aber
    am Button waren `aria-pressed`/`aria-label` weiterhin `null` und
    beide Spans behielten ihr `x-cloak`-Attribut. Ursache: Alpine
    verarbeitet `@click`/`:aria-*`/`x-show` nur innerhalb eines
    `x-data`-Scopes; der Theme-Button stand außerhalb. `x-data` direkt
    am Button-Tag angebracht (leeres Scope reicht, der State lebt im
    globalen `$store.theme`). Verifiziert per Browser-DOM-Check: Sun
    sichtbar bei `aktivesMuseum`, Moon bei Default, `aria-pressed`
    schaltet, `data-theme`-Attribut auf `<html>` wechselt, `cc-theme`
    in localStorage persistiert.

- **Bildupload in Galerien zeigt das hochgeladene Bild nicht mehr
  als nicht-vorhanden.**
- **Neuanlage von Gallery, Text, Image und Audio/Video lieferte
  404.** Direkte Folge des Laravel-11-Sprungs: Die Middleware
  `ConvertEmptyStringsToNull` schreibt seitdem leere
  Hidden-Inputs zu `null` um. Die Update-Weichen in
  `ContentController::saveGallery|saveText|saveImage` und
  `AudiovisualController::store` folgten dem Pattern
  `isset($request['xId']) && $request['xId'] !== ''`. Bei `null`
  ist `isset` über den Request-Bag-Key `true` und `null !== ''`
  ebenfalls — der Code lief in den Update-Pfad, rief
  `Model::findOrFail(null)` auf und Laravel rendert die
  resultierende `ModelNotFoundException` als HTTP 404. Fix in
  vier Controller-Methoden plus zwei `translationMode`-Branches:
  `$request->filled('xId')` ersetzt die alten Pattern. Sieben
  Pest-Tests in `ContentControllerEmptyIdFilledTest` pinnen das
  Verhalten.
- **Hardcoded Default-Impressum aus Project-Preview/PDF raus.**
  `preview/index.blade.php` und `preview/pdf.blade.php` zeigten
  unten immer dieselbe Adresse, unabhängig vom Projekt. Jetzt
  rendert der Footer-Block nur, wenn `$project->imprint` nach
  `strip_tags` nicht leer ist.
- **`RegisterRequest::roles`-Rule für den Admin-Invite-Pfad
  entschärft.** Der Controller-Pfad ignoriert beim Admin-Invite
  die Rolle ohnehin (setzt Admin direkt). Conditional Rule via
  `$rolesRule = $this->boolean('adminUser') ? 'sometimes' :
  'required';`. Drei Pest-Tests in `RegisterRequestTest` fixieren
  das.
- **Welcome-Page-Register-Link für Gäste raus.**
  `resources/views/welcome.blade.php` zeigte einen Register-Link
  für Nicht-Eingeloggte, der durch den Registrierungs-Lockdown in
  einen Login-Redirect lief — UX-mäßig irreführend.
- **Gallery- und Audiovisual-Form-IDs eindeutig.** Drei Form-Tags
  in `Entry/index.blade.php`, `contents/gallery.blade.php` und
  `contents/audiovisual.blade.php` hatten alle
  `id="entry_frm"`. Die `resetEntryForm`/`setEntryFormUpdate`-
  Helper konnten dadurch das `_method=PATCH`-Override aus dem
  Entry-Form auf das Gallery-Form übergreifen lassen. Zusätzlich
  war das Gallery-Modal doppelt im DOM (`contents/index.blade.php`
  + `chapters/index.blade.php`). Form-IDs auf `gallery_frm` und
  `audiovisual_frm` umgestellt, der Doppel-Include ist raus.
- **`addImage`-Click setzt jetzt entryId.** Der Hidden-Input wurde
  beim Öffnen des Image-Upload-Modals nicht befüllt, der Save-
  Pfad lief deshalb in den Create-ohne-Entry-Vektor und scheiterte
  am Authorize-Gate. `.addImage`-Click-Handler setzt entryId
  jetzt analog zu `.addContent` und `.addEntry`.
- **`now()->addDay(3)` korrigiert zu `addDays(3)`** in der
  Welcome-Notification-Logik. Die Carbon-Methode `addDay()` nimmt
  keine Parameter — Welcome-Tokens waren faktisch nur einen Tag
  gültig statt drei. Larastan im Strict-Mode der neuen
  Service-Klasse hat den latenten Bug freigelegt.
- **`CommentRetrieve::getComments` initialisiert `$pathReply`
  defensiv.** Für `App\Models\MediaContent` (was
  `ContentController::getTextComment` / `getImageComment` als
  Class durchreichen) gab es keinen Switch-Case, `$pathReply`
  blieb undefined. Bei leerer Comment-Liste fiel das nicht auf,
  bei einem MediaContent mit Kommentaren wäre der Aufruf
  gecrasht. Defensiver Default `$pathReply = '';` am Methoden-
  Anfang.
- **Lazy-Loading-Verletzungen unter Strict-Mode behoben** in
  `ContentController::listComments` (die View greift auf
  `$comment->project->name`, `$comment->user->name` und
  `$comment->content->media_contentable_type` zu — jetzt eager
  geladen) und in `LogService::history` / `LogService::textLog`
  (`$activity->causer->name` ohne `with('causer')`).
- **Blade-Expressions in HTML-Kommentaren werden jetzt nicht mehr
  ausgewertet.** Vier Stellen in drei Blade-Templates hatten
  auskommentierten HTML-Code, in dem `{{ ... }}`-Expressions
  stehengeblieben sind. Blade interpretiert solche Expressions
  auch innerhalb von HTML-Kommentaren — der Kommentar versteckt
  nur das gerenderte HTML, nicht die PHP-Auswertung. Im
  `chapters/index.blade.php`-Fall löste das eine
  `MissingAttributeException` auf `$item->alt` aus. HTML-
  Kommentare durch Blade-Kommentare `{{-- ... --}}` ersetzt.
- **Soft-Delete-Bypass in den Content-Schreibpfaden beseitigt.**
  `destroyText` / `destroyImage` / `destroyGallery` liefen
  vorher über `DB::table()->update(['deleted_at' => now()])` —
  das umgeht die SoftDeletes-Trait-Hooks (Observer, Activity-
  Log etc.). Alle vier Stellen auf Eloquent-Builder-`delete()`
  umgestellt; Verhalten identisch, Trait-Hook-Chain greift jetzt
  korrekt.
- **`PermissionTableSeeder` Strict-Mode-fest gemacht.** Vorher
  schickte der Seeder `permission_id` und `position` durch ein
  `updateOrCreate`-Array an `PermissionDescription`, dessen
  `$fillable = ['description']` beides nicht zulässt. In
  Production lief das still durch (Strict-Mode dort aus), in
  Dev/CI war es eine latente `MassAssignmentException`. Pfad
  jetzt über expliziten Query plus Property-Setter.
- **Einladung neuer User auf `/register` brach mit
  `RoleDoesNotExist: no role named '20'` ab,** sobald das Form
  eine Role-ID als String schickte. Spatie v6 interpretiert
  Strings, die an `assignRole()` gehen, strikt als Rollen-Namen.
  Neuer Helper `RoleResolver` löst Single-String, Array, Name
  und numerische ID zu konkreten `Role`-Instanzen auf, bevor sie
  an Spatie gehen. Charakterisierungs-Tests fixieren die drei
  Eingabewege.
- **`Entry::getAllMediaAttribute()` aufgeräumt.** Vorher
  iterierte die Methode über einen Relation-Builder und gab den
  Builder dann unverändert zurück — der `foreach`-Loop war toter
  Code. Vereinfacht auf `return $this->mediaContent;`.
- **`MediaContent`-PHPDoc-Returns** korrigiert. Drei Methoden
  (`image()`, `text()`, `audiovisual()`) deklarierten
  `MorphToMany`, gaben aber `BelongsTo` zurück.
- **`MediaContentMorphRelationsTest`, `ContentProjectNavigationTest`,
  `TextPolicyTest`, `ImagePolicyTest`, `GalleryPolicyTest`,
  `AudiovisualPolicyTest`, `ContentRouteAuthorizationTest`,
  `AudiovisualServiceTest`, `CommentRetrieveTest`** — alle Insert-
  Stellen auf die neuen `content_*`/`parent_*`-Spalten umgestellt,
  nachdem die alten Spalten aus dem Schema gefallen sind.

### Sicherheit

- **Stored-XSS in Preview- und Log-Views geschlossen** (Q3-Härtung
  2026-08-19 / SEC-01). 33 Fundstellen mit `{!! !!}` in `preview/pdf`,
  `preview/copyright`, `preview/index`, `logs/log` und `roles/create|edit`
  liefen unescaped auf Content-Feldern, die jede Edit-Rolle beschreiben
  kann (`Chapter.name`, `Text.text`, `Image.alt`, Rich-Text-Descriptions,
  Aktivitäts-Log-Diff-Werte). Plaintext-Felder auf `{{ }}` gehoben,
  Rich-Text-Felder auf eine neue `@rich`-Blade-Directive, die
  `App\Support\RichTextSanitizer::sanitize()` nutzt (Tag-Whitelist
  p/br/strong/em/u/s/ul/ol/li/a/blockquote/h2-h4, entfernt
  `on*`-Handler und `javascript:`/`data:`/`vbscript:`/`file:`-URIs
  sowie `style`-Attribute). ADR-0029 sieht mittelfristig HTML-Purifier
  vor — dann tauscht die Sanitize-Implementierung, die Directive und
  Feld-Typ-Klassifikation bleiben. Zusätzlich `roles/index.blade.php`
  JS-Datenübergabe von `{!! !!}` auf `@json` umgestellt.

- **`resendInvitation` gegen fremd-getriggerten Mail-Versand
  abgesichert** (Q3-Härtung 2026-08-19 / SEC-02). Vorher lief die Route
  als `GET /user/{id}/invitation` ohne Authorization — jeder eingeloggte
  User konnte für beliebige User-IDs eine Welcome-Mail auslösen und
  `welcome_valid_until` um drei Tage verlängern; RFC-9110-widrig (GET
  mit Nebenwirkung) und CSRF-frei. Neu:
  `POST /users/{id}/actions/resend-invitation` mit `throttle:6,1`,
  CSRF-Schutz aus dem Blade-Form und `hasRole('Admin')`-Guard im
  Controller. Blade-Trigger auf `<form method="POST">` umgestellt.

- **500-Fehlerseite: kein Exception-Leak mehr** (Phase 5e.5).
  Die alte `resources/views/errors/500.blade.php` zeigte im
  Non-Debug-Modus `$exception->getMessage()` plus Datei und
  Zeile an Endnutzer — ein Info-Leak, der internen Kontext
  (SQL-Fragmente, Pfade, Klassennamen) an nicht-authentifizierte
  oder nicht-berechtigte Zugriffe preisgab. Der Rewrite zeigt
  nur noch eine generische, persona-freundliche Fehlermeldung;
  Debug-Details bleiben Laravel Ignition im `APP_DEBUG=true`-
  Betrieb vorbehalten.

- **Project-scoped Save-Gate für alle Inline-Editoren** (Phase 5c).
  Jede Volt-Komponente (`inline-editor`, `rich-text-editor`,
  `source-picker`, `audio-uploader`, `audiovisual-player`)
  autorisiert vor jedem Save gegen `Gate::authorize('update',
  $project)`; das Project wird server-seitig über die kanonische
  `Model::project()`-Kette aufgelöst und ist nicht durch Client-
  Input beeinflussbar. Reader-Rolle wird konsequent geblockt,
  Fremd-Update-Tests laufen als `assertForbidden` durch.

- **MIME-Whitelist und Größen-Limit im Audio-Uploader** (Phase 5c).
  Serverseitige Rule
  `file|mimetypes:audio/mpeg,audio/mp4,audio/wav,audio/ogg,audio/x-m4a|max:20480`
  (20 MB), identisch zur bestehenden Store-Route. Server-generierter
  Dateiname aus `Str::random(10)` (NF-SEC-201), kein Client-Input
  im Path. PDF-Upload-Versuche werden mit `save-failed` gestoppt
  und toasten die Fehlermeldung, DB bleibt unverändert.

- **Rate-Limit auf `chapter.drag`** (Phase-5b-Hotfix). Neuer
  `throttle:60,1`-Middleware auf der Reorder-Route. Vorher
  ungebremst: Alt+↑/↓-Spam oder ein defekter Client konnte den
  Endpoint fluten, jeder Move ist im Service N Einzel-Updates.
  60 Requests pro Minute pro User entsprechen dem Laravel-
  Rate-Limiter-Standard für interaktive UI.

- **Autorisierung in der Sidebar-Tree-Livewire-Komponente**
  (Phase-5b-Hotfix). `livewire/sidebar-tree.blade.php` prüft
  jetzt in `mount()` explizit `Gate::authorize('view', $project)`.
  Vorher heute nur indirekt über den gegateten Editor-Controller
  abgedeckt; ein direkter Livewire-Roundtrip mit fremdem
  Project-Modell wäre ein Bypass gewesen. Defense-in-Depth-
  Pinning-Test ergänzt.

- **Authorization-Bypässe in vier Content-Controllern geschlossen.**
  Nach der Permission-Modell-Konsolidierung und Abschaltung von
  Spatie's `Gate::before` (siehe unten) zeigte sich, dass weite
  Teile der `ChapterController`, `EntryController`,
  `ContentController` und `AudiovisualController` ungated waren.
  Project-scoped `authorize`-Gates ergänzt für ~25 Methoden über
  die vier Controller — schwerpunktmäßig die JSON-API-Edit-Pfade
  (`editText`, `editImage`, `editGallery`, `ChapterController::edit`,
  `EntryController::show/edit`), die Save-Pfade (`saveText`,
  `saveImage`, `saveGallery`, `AudiovisualController::store`) und
  die kompletten Comment-Pfade (Add/Get/Save/Status für Chapter,
  Entry, Text, Image, Gallery, Audiovisual). Comment-Status-
  Endpunkte hatten zuvor totes Route-Model-Binding (Route-Param
  hieß `{id}`, Signature erwartete `{chapter}`/`{text}` etc.) —
  Laravel instantiierte ein leeres Modell statt zu authorisieren.
  Resolution läuft jetzt über `CommentService::resolveProjectForComment`,
  das vom Comment via `commentable_type`/`commentable_id` zum
  Project navigiert.
- **Reader-Bypass über Spatie's `Gate::before` strukturell
  geschlossen.** Globale `view`-Permission von Spatie hat alle
  project-scoped Policies umgangen: Spatie's
  `PermissionRegistrar::registerPermissions()` registriert per
  Default einen `Gate::before`-Hook, der `checkPermissionTo('view')`
  ohne Modell-Argument prüft. Ein eingeladener Reader mit
  globaler `view`-Permission gab in dem Hook true zurück, bevor
  die project-scoped Policy überhaupt befragt wurde. Im
  Test-Setup lief das durch Glück (Permission-Cache nicht hot,
  `checkPermissionTo` wirft, Laravel interpretiert das als
  false), live mit hot Cache war es offen. Fix:
  `config/permission.php` setzt
  `register_permission_check_method => false`. Vier Policy-
  Methoden (`ProjectPolicy::viewAny`, `ProjectPolicy::create`,
  `ChapterPolicy::create`, `EntryPolicy::create`) gehen jetzt
  direkt über Spatie's `hasPermissionTo()` ans Trait, ohne
  Gate-Roundtrip. Drei Blade-Stellen in `roles/index.blade.php`
  auf `@hasPermissionTo(...)` umgestellt. Pinning-Tests mit
  primärem Permission-Cache (`forgetCachedPermissions()` im
  beforeEach) sichern den Pfad ab. Die Konvention
  `forgetCachedPermissions()` im beforeEach ist als verbindliche
  Test-Setup-Vorgabe etabliert.
- **Owner-Bypass-Bug im Defense-in-Depth-Layer entschärft.** Beim
  Authorize-Sweep über die vier Content-Controller war
  `hasPermissionTo('edit')` als Top-Level-Defense-in-Depth-Hürde
  vor den `authorize('update', $model)`-Aufrufen eingezogen
  worden. Project-Owner ohne globale Editor-Rolle wurden dadurch
  ausgeschlossen, drei HappyPath-Tests brachen. Top-Level-Hürde
  in den vier `saveText`/`saveImage`/`saveGallery`/Audiovisual-
  `store`-Methoden wieder rausgenommen — der Owner-Shortcut in
  `OwnerScopedPolicy` fängt das ab. Nur dort, wo kein Modell-
  Argument für ein project-scoped `authorize` vorhanden ist
  (Source-Translation auf global geteilten Sources in `saveText`
  und `saveImage`), bleibt `hasPermissionTo('edit')` als
  Reader-Schutz.
- **Authorization-Sweep über `ProjectController`.** Sieben
  ungegated Pfade geschlossen: `show($project)`, `edit($project)`,
  `getDetails`, `previewProject`, `downloadPreview`,
  `projectMetadata`, `givePermissionToUser`. Plus kritisch:
  `setPermissionForUserOnProject` ohne Authorize — jeder
  eingeloggte User konnte einem beliebigen User volle Rechte auf
  jedes Projekt vergeben (Privilege Escalation derselben Klasse
  wie der frühere Register-Hotfix). Alle sechs Read-Pfade jetzt
  mit `authorize('view', $project)`, die zwei Permission-Pfade
  mit `authorize('update', $project)`. `history($model, $id)` auf
  `private` reduziert (kein Route-Caller, einziger Aufrufer ist
  `edit()`, das selbst gegated ist); `getCurrentLog($id)`
  navigiert über `Text::project()` und gated mit `view`.
- **Authorization-Sweep über User-, Role- und Translation-
  Endpunkte.** `UserController::update` ohne Authorization-Gate
  geschlossen: jeder eingeloggte User konnte via
  `PATCH /users/{anderer}` mit `roles=['Admin']` fremde User
  editieren und ihnen die Admin-Rolle zuweisen. Neue
  `App\Policies\UserPolicy` regelt das (Admin via `before()`,
  sonst Self-Edit), `authorize('update', $user)` im Controller,
  Caller-Admin-Guard auf das `roles`-Feld. `RoleController::store/
  show/update` waren vor dem Hotfix nicht per `role:Admin`-
  Middleware geschützt (nur `index/edit/destroy` waren es) — via
  Direkt-POST/PATCH konnte ein Reader neue Rollen anlegen oder
  bestehende ändern. Constructor-Middleware-Liste auf den vollen
  Resource-Pfad erweitert. `ProjectController::editMetaData` und
  `::translateCurrentProject` waren nur durch `auth`-Middleware
  geschützt; Reader konnten fremde Project-Metadaten samt
  Permission-Verwaltung und Übersetzungs-Masken sehen. Inline-
  Authorize via `update`-Policy. Charakterisierungs-Tests pro
  Bypass sichern das geschlossene Verhalten.
- **Security-Sweep über sechs Lücken zweiter Ordnung.** Aus den
  Review-Subagents zum Phase-Abschluss:
  - **`ProjectController::resetValue`** lief mit
    `$request['subjectType']::findOrFail()` ohne Whitelist und
    ohne Authorize — ein RCE-naher Vektor, weil ein Angreifer
    beliebige Klassen-Strings durchschießen konnte. Jetzt:
    Whitelist auf die sechs curating-relevanten Content-Modelle
    (Chapter, Entry, Text, Image, Gallery, Audiovisual) plus
    project-scoped `authorize('update', $model)`.
  - **`ChapterController::index`** Reader-Bypass via
    `GET /chapters?id=42`: rendert die volle Edit-Hierarchie
    fremder Projects. `index` sieht semantisch wie ein Listen-
    Endpunkt aus, lädt aber tatsächlich
    `Project::withEditTree()->findOrFail($request['id'])`. Jetzt:
    `authorize('view', $project)` direkt nach Modell-Auflösung.
  - **`ProjectController::inviteUserForProject`** Info-Leak: zeigte
    Rollen und Permissions fremder User auf fremden Projects.
    Jetzt: `Project::findOrFail($projectId)` plus
    `authorize('update', $project)` — gleicher Gate wie auf der
    Permission-Verwaltung in `setPermissionForUserOnProject`.
  - **`ProjectController::saveCommentProject` und
    `setCommentStatusProject`** hatten `Project $project` als
    totes Route-Model-Binding (Route-Param hieß `{id}` bzw. gar
    nicht). Laravel instantiierte ein leeres Project, kein
    Authorize. Jetzt: `Project::findOrFail($request->route('id'))`
    bzw. `CommentService::resolveProjectForComment($commentId)`
    plus `authorize('comment', $project)`.
  - **`ProjectController::getParentText`** SQLi-Surface über die
    String-Parameter `$table` und `$model`. Whitelist auf
    `entries`/`images`/`texts` und `Entry::class`/`Text::class`/
    `Image::class`.
- **Privilege-Escalation und Owner-Checks aus dem Phase-Vorlauf.**
  Vor dem Major-Sprung geschlossen:
  - **Upload-Härtung in den Image- und Audio-Routen.** Vorher
    liefen `POST /image/store` und `POST /save-audiovisual` ohne
    MIME- oder Size-Validation; ein eingeloggter User konnte
    beliebige Dateitypen hochladen. Dedizierte FormRequests
    decken das jetzt mit MIME-Whitelist und Size-Limit ab.
  - **`AudiovisualController::uploadAudio()`** generiert den
    Dateinamen jetzt durchgängig per `Str::random(10)` — der
    vorherige `getClientOriginalName()`-Zwischenwert war ein
    Path-Traversal-Vektor.
  - **`UploadTrait::uploadOne()`** prüft den `disk`-Parameter
    gegen eine Whitelist (`public`).
  - **Mass-Assignment-Schutz für `Project.user_id`** — die Spalte
    ist nicht mehr in `Project::$fillable`. Der Controller setzt
    `user_id` ausschließlich aus `Auth::user()->id`.
  - **Owner-Check vor Drag-and-Drop-Reorder.** Bis zum Fix konnte
    jeder eingeloggte User Chapter, Entries und MediaContent in
    fremden Projekten umsortieren — die Route war nur durch
    `auth`-Middleware geschützt. Project-Policy greift jetzt.
- **`composer audit` und `npm audit` Hotfixes.**
  - **`laravel/framework` 12.61.0 → 12.62.0** für
    GHSA-crmm-hgp2-wgrp (Temporary Signed URL Path Confusion,
    Severity medium).
  - **`guzzlehttp/guzzle` 7.10.5 → 7.12.1 und `guzzlehttp/psr7`
    2.x → 2.12.1** für drei CVEs aus dem Audit-Lauf:
    `CVE-2026-55767` (medium, Dot-only cookie domains match all
    hosts), `CVE-2026-55568` (medium, Silent HTTPS proxy
    downgrade to cleartext), `CVE-2026-55766` (medium, CRLF
    injection in HTTP start-line serialization). Direkte
    Production-Auswirkung in crowdCuratio gering — Guzzle wird
    nur über transitive Abhängigkeiten genutzt, kein outgoing
    HTTP-Call in der Anwendungslogik. Hard-Fix, weil `composer
    audit` sonst rot bleibt.
  - **`axios` komplett aus dem Frontend-Stack entfernt** — 17
    CVEs (CSRF, SSRF, Prototype-Pollution, mehrere DoS-Pfade).
    Das Paket war Laravel-Default-Setup, wurde aber im App-Code
    nirgends genutzt; alle AJAX-Calls laufen über jQuery.
  - **`lodash` aus den `devDependencies` entfernt.** Die
    transitive Version aus Laravel-Mix (4.17.21, gepatcht)
    bleibt aktiv; vorher hing eine veraltete 4.17.19 mit drei
    Prototype-Pollution-CVEs direkt in der dependency-Liste.
  - **`alpinejs` von 2.7.3 auf 3.15.12 gehoben.** 2.x ist EOL.
    Drei Template-Stellen syntaktisch unverändert übernommen;
    `Alpine.start()` in `resources/js/app.js` explizit gerufen
    (in 3.x Pflicht). Die verbleibenden npm-Vulnerabilities
    liegen im Laravel-Mix-Stack (Webpack/Babel/PostCSS) und
    werden mit der Vite-Migration strukturell aufgelöst.
- **CVE-2025-27515 (Laravel File-Validation-Bypass) strukturell
  zu** durch den Laravel-12-Sprung; das frühere Soft-Fail-
  Konstrukt aus dem Sicherheitsnetz-Release ist abgeschlossen.
- **Frontend-Setter-Folgesweep geprüft.** Nur der
  `.addImage`-Click hatte den entryId-Bug, der bereits im
  Image-Modal-Hotfix gefixt wurde. `.addContent` setzt entryId
  korrekt für Text/Audiovisual/Gallery, `.addEntry` setzt
  chapterId korrekt. Keine analogen Lücken.

## [0.9.0] — 2026-05-30 — Sicherheitsnetz

Erste Modernisierungs-Welle nach der Repo-Übernahme. CI-Schicht,
40 Pest-Tests, Authorization über Laravel-Policies, dedizierte
FormRequests für alle mutierenden Routen, Mass-Assignment-Schutz für
privilegierte Felder, Härtung von Docker-Stack und Datenbank-Layer.
Vier in der initialen Tiefenanalyse identifizierte Sicherheits-Blocker
geschlossen, plus ein Privilege-Escalation-Hotfix gegen die
Registrierungs-Route.

### Hinzugefügt

- `CHANGELOG.md` als verbindliche Änderungsspur.
- `composer.lock` wird ab sofort committet — Reproduzierbarkeit
  und `composer audit`-Baseline möglich.
- **CI-Schicht auf GitHub Actions** (`.github/workflows/ci.yml`):
  sechs parallele Jobs auf jedem PR und Push nach `main` — Pest
  gegen SQLite-in-memory, `composer audit`, `npm audit`, Larastan,
  Pint und ein Changelog-Diff-Check, der erzwingt, dass jeder PR
  den Changelog berührt (mit Opt-out via Label `skip-changelog`).
- **Larastan ^1.0** (Laravel-8-kompatibel) mit `phpstan.neon` auf
  Level 5 und `phpstan-baseline.neon` für die Bestandsbefunde —
  neue Verstöße brechen den Build.
- **Laravel Pint** im Laravel-Preset, Hard-Fail im CI. Baseline-
  Sweep über die gesamte Codebasis als isolierter Style-Commit,
  dessen SHA in `.git-blame-ignore-revs` steht — `git blame`
  springt über die Whitespace-/Brace-/Import-Änderungen hinweg.
- **Dependabot** für Composer, npm und GitHub Actions (wöchentlich).
  Major-Bumps für Laravel, Spatie-Pakete, axios, alpine, tailwind
  und Mix sind bewusst ausgenommen — sie gehören in den
  koordinierten Upgrade-Sweep.
- **Pest-Suite mit 40 Tests** — Authorization-Bypass-Szenarien für
  Project / Chapter / Entry, Create-Pfad-Owner-Checks,
  FormRequest-Pflichtfeld-Tests, MIME-Whitelist-Test für das
  Project-Logo, PATCH-Sanity-Tests für Chapter und Entry,
  Pfad-Schutz für die Registrierung.
- **Laravel-Policy-Schicht** für Project, Chapter und Entry
  (`app/Policies/`), inklusive `createIn`-Methode für den
  Owner-Check beim Anlegen.
- **`App\Support\PermissionName`** zentralisiert die sieben
  Permission-Strings (`view`, `add`, …) als public-Konstanten —
  Seeder, Policies und Tests nutzen die Konstanten.
- **Sieben FormRequest-Klassen** unter `app/Http/Requests/`:
  `StoreChapterRequest`, `UpdateChapterRequest`,
  `StoreEntryRequest`, `UpdateEntryRequest`, `StoreProjectRequest`,
  `UpdateProjectRequest`, `Auth\RegisterRequest`. Jede delegiert
  `authorize()` an die zuständige Policy und definiert `rules()`
  mit Standard-Validation.
- **Console-Command `db:audit-fk`**
  (`app/Console/Commands/AuditForeignKeys.php`): Read-only-Default
  produziert eine Markdown-Tabelle mit Orphan-Foreign-Keys
  (`texts.origin`, `texts.copyright` gegen `sources.id`). Der
  `--fix --confirm`-Pfad schreibt vorher ein JSON-Protokoll nach
  `storage/logs/` und setzt orphan-Werte transaktional auf NULL.
- **`database/seeders/RoleTableSeeder.php`** legt drei
  Default-Rollen an (Editor, Reviewer, Reader) — der
  User-Invitation-Workflow läuft im Standard-Setup wieder durch.
- **`docs/smoke.md`** als belastbares Baseline-Inventar — zehn
  manuell verifizierte Haupt-Pfade von Login bis Invitation-Flow.
- **`doctrine/dbal ^3`** als Require — wird für `Schema::dropColumn`
  benötigt, das in SQLite (CI-Pfad) und in Produktions-Migrations
  durch den Doctrine-Schema-Manager läuft.

### Geändert

- **PHP 8.0 → PHP 8.1**, Ubuntu 20.04 → 22.04 (`jammy`),
  Node 15 → Node 20 LTS, `dompdf/dompdf ^1.2` → `^2.0` (acht
  Security-Advisories in 1.2.x). Container-Build neu unter
  `docker/8.1/`.
- **Datenbank-Layer:** Charset auf `utf8mb4` (vorher `utf8mb3`),
  `strict = true` aktiviert — Zero-Dates, GROUP-BY-Verstöße und
  Inserts ohne Pflichtfelder werfen ab sofort hörbar Fehler statt
  still durchzulaufen.
- **`docker-compose.yml`:** Image-Tags gepinnt (`mysql:8.0`,
  `redis:7-alpine`, `getmeili/meilisearch:v1.6`, `phpmyadmin:5.2`,
  `axllent/mailpit:v1.20`). Mailhog durch Mailpit ersetzt, das
  `selenium`-Image entfernt (kein arm64-Support). Healthchecks
  für meilisearch und mailpit, MySQL-Healthcheck mit Root-
  Credentials (vorher lieferte `mysqladmin ping` unter MySQL 8
  ein `Access denied`, das Docker als „läuft" fehlinterpretierte).
  `restart: unless-stopped` auf mysql, redis, meilisearch,
  mailpit. Forward-Ports von mysql, redis, meilisearch und dem
  mailpit-Dashboard an `127.0.0.1` gebunden (nur der SMTP-Port
  von mailpit bleibt offen, weil der App-Container ihn intern
  erreicht).
- **Dockerfile:** Composer aus dem offiziellen `composer:2`-Image
  übernommen statt `curl http://...`-Pipe — deterministische
  Version, signierte Distribution. `apt-key` durch
  `signed-by`-Keyrings unter `/etc/apt/keyrings` ersetzt
  (`apt-key` ist seit Ubuntu 22.04 deprecated und in 24.04
  entfernt). `EXPOSE 80` statt `EXPOSE 8000` — Compose mappte
  ohnehin auf Container-Port 80.
- **`.env.example`:** Sail-taugliche Defaults (`DB_HOST=mysql`,
  `REDIS_HOST=redis`, `MAIL_HOST=mailpit`, `MAIL_FROM_ADDRESS`
  vorbelegt). `ADMIN_*`-Variablen für den Admin-Seeder
  dokumentiert. `WWWUSER` / `WWWGROUP` als kommentierter Hinweis
  für Linux-Hosts mit abweichender UID/GID. `APP_DEBUG`-
  Warnkommentar — Stacktraces dürfen nicht in Produktion.
- **`CreateAdminUserSeeder`** liest `ADMIN_EMAIL` /
  `ADMIN_PASSWORD` / `ADMIN_NAME` / `ADMIN_LAST_NAME` aus dem
  Environment, bricht beim Fehlen mit `RuntimeException` ab,
  idempotent (`firstOrCreate`).
- **`DatabaseSeeder`** ruft jetzt `PermissionTableSeeder` →
  `RoleTableSeeder` → `CreateAdminUserSeeder` in dieser
  Reihenfolge. `PreviewSeeder` bleibt manuell.
- **Authorization über Policies:** `ProjectController`,
  `ChapterController`, `EntryController` rufen
  `$this->authorize(...)` in allen mutierenden Methoden auf.
  Views nutzen `Auth::user()->can('update', $project)` statt
  Custom-Gates.
- **PATCH-Route-Trennung** für Chapter und Entry: Update läuft
  jetzt über `PATCH /chapters/{chapter}` bzw.
  `PATCH /entries/{entry}` mit Route-Model-Binding, statt über
  POST mit `$request['chapterId']`-Verzweigung im Controller.
  Das zugehörige Frontend-JS in `chapters/index.blade.php`
  zieht per `_method`-Hidden-Field mit.
- **Eloquent-Hygiene:**
  - `Model::preventLazyLoading()` ausserhalb der Produktion —
    N+1-Pattern werfen in Dev, Tests und CI sofort eine
    `LazyLoadingViolationException`. Fünf Controller-Pfade laden
    `Project` jetzt mit explizitem `with(...)`-Baum.
  - Drei Local Scopes auf dem `Project`-Model
    (`withEditTree`, `withPreviewTree`, `withCopyrightTree`)
    konsolidieren die Eager-Loading-Bäume.
  - Explizite `$casts` auf `Chapter`, `Entry`, `User` für
    `is_translated`, `welcome_valid_until`, `is_admin` und
    `create_project`.
  - `Role::where('id', 'not like', '1')` an vier Stellen durch
    `Role::where('name', '!=', 'Admin')` ersetzt — LIKE auf
    INT-Spalte mit hardkodierter Admin-ID war semantisch schief.
- **File-Upload-Validation** in `StoreProjectRequest` und
  `UpdateProjectRequest`: `project_image` als File mit
  MIME-Whitelist (jpeg, jpg, png, gif, webp) und 4 MB Limit.
- **`Validator::make` in `RegisteredUserController`** durch
  `RegisterRequest` ersetzt.
- **Pint-Baseline-Sweep** über die gesamte Codebasis (isolierter
  Style-Commit). Pest-Suite vor und nach dem Sweep identisch
  grün.
- **`.gitignore`:** `composer.lock` ist jetzt eingecheckt,
  `.DS_Store` und Smoke-Artefakte ignoriert.

### Behoben

- **Foto-Upload-Anzeige für Project-Logos und Image-Blöcke:**
  die `image`/`audio`-Routen liefen gegen die Default-Disk
  `local`, während Uploads auf der `public`-Disk landen.
  Wechsel auf `Storage::disk('public')->response(...)` rendert
  hochgeladene Bilder wieder.
- **User-Invitation-Workflow:** Default-Rollen fehlten,
  `MAIL_*`-Defaults waren leer. Mit dem Role-Seeder und
  vernünftigen Mail-Defaults läuft der Einladungs-Flow inkl.
  Welcome-Mail wieder durch.
- **`drop_foreign_key_table`-Migration** lief auf frischer DB
  in MySQL-Fehler 1091, weil sie Spalten droppte, die
  `create_texts_table` / `create_image_table` nie angelegt
  hatten. Jetzt mit `Schema::hasColumn`-Guard.
- **Drei Migrations mit fehlenden oder destruktiven
  `down()`-Operationen** gehärtet:
  - `add_welcome_valid_until_field_to_users` hatte keine
    `down()`, jetzt mit `dropColumn`-Guard.
  - `customize_has_permissions_table::down()` droppte die
    falsche Spalte (`project_id` statt `user_id`).
  - `convert_texts_to_innodb::down()` ist jetzt eine
    `RuntimeException` — eine Rück-Konvertierung auf MyISAM
    würde die Foreign-Key-Constraints still verwerfen und ist
    ehrlich verboten statt scheinbar funktionsfähig.
- **`ChapterController::update` und `EntryController::update`**
  gaben bisher `return $this;` zurück — die Versuche, die
  Controller-Instance als Response zu serialisieren, wären als
  `TypeError` hochgegangen. Korrigiert zu `return back();`.
- **DB-Defaults** für `users.is_admin`, `users.create_project`
  und `users.last_name`, plus explizite `position`-Werte im
  `PermissionTableSeeder` — alles latente Schema-Lücken, die
  der `strict = true`-Modus sichtbar gemacht hat.

### Entfernt

- **Sechs tote Legacy-Auth-Controller** (`LoginController`,
  `RegisterController`, `ConfirmPasswordController`,
  `ForgotPasswordController`, `ResetPasswordController`,
  `VerificationController`). Referenzierten
  `Illuminate\Foundation\Auth\*`-Traits, die in Laravel 8+
  nicht mehr existieren, und waren seit dem Breeze-Umzug
  ohne Caller. Auth läuft jetzt durchgängig über die
  Breeze-Klassen.
- **Tote PHP-7.4-Build-Variante** `docker/7.4/` — nach dem
  Umzug auf 8.1 von keiner Compose-Datei mehr referenziert.
- **`selenium`-Image** aus dem Compose-Stack (kein arm64-
  Support).
- **`app/Traits/SourceTrait.php`** plus seine zwei Aufrufer im
  `ContentController`. Der Trait hatte genau eine Methode
  (`checkMeta`), die nirgends aufgerufen wurde; ihre Signature
  (required-Parameter nach optional) war seit PHP 8.0
  deprecated und ab 8.4 fatal.
- **Tote Image-Preview-Route** `/image/{file}/preview`, die
  den Storage-Disk-Fix nie mitbekommen hatte und ohne Caller
  im Code stand.
- **Fünf Custom-Gate-Closures aus `AuthServiceProvider::boot`**
  (`edit-`, `add-`, `delete-`, `publish-`, `comment-project`).
  Die Owner-Logik war semantisch schief (`$user->id === $project`).
  View-Aufrufe in `chapters/index.blade.php` (zehn Stellen) auf
  die Project-Policy umgehängt.
- **Vier redundante `whereNull('deleted_at')`-Aufrufe** in
  Eloquent-Queries (`ChapterController`, `ProjectController`,
  `CommentRetrieve`). Models nutzen durchgängig `SoftDeletes`,
  der Default-Scope schließt trashed Rows implizit aus.
- **Stock-Breeze-Tests** für das Self-Service-Signup-Modell, das
  crowdCuratio nicht hat (`tests/Feature/RegistrationTest`, drei
  `ExampleTest`-Stubs).
- **Self-Service-Registrierungs-Routen** in `routes/auth.php`
  (`GET` und `POST /register` mit `guest`-Middleware). Lebten
  parallel zur Admin-Registrierung in `routes/web.php` und
  stifteten Verwirrung. crowdCuratio kennt keinen Gast-
  Registrierungs-Pfad — neue User werden nur durch Admins
  eingeladen.
- **`.idea/`-Tracking**: PHPStorm-Workspace-State bleibt lokal,
  wandert nicht mehr ins Repo.

### Sicherheit

- **Authorization-Bypass über direkte HTTP-Aufrufe** geschlossen
  ([`7ce63dc`](https://github.com/berlinHistory/crowdCuratio/commit/7ce63dc),
  [`6a213e2`](https://github.com/berlinHistory/crowdCuratio/commit/6a213e2)).
  Project-, Chapter-, Entry-Mutationen prüfen sowohl in der
  Controller-Action als auch in der View, ob der eingeloggte User
  Eigentümer oder Admin ist. Bisher reichte ein direkter HTTP-
  Aufruf gegen die Update-/Destroy-Routen, um fremde Daten zu
  ändern. Belegt durch die Pest-Suite (13 Authorization-Bypass-
  Szenarien grün).
- **Create-Pfad-Bypass für Chapter und Entry** geschlossen
  ([`f586d56`](https://github.com/berlinHistory/crowdCuratio/commit/f586d56)).
  Die Update-/Destroy-Tests hatten den Bypass beim Anlegen
  übersehen — jeder eingeloggte User konnte Chapter und Entry in
  fremden Projekten erzeugen. Neue `createIn`-Policy-Methode plus
  vier Pest-Tests.
- **Logo-Upload-Validation und Path-Traversal-Pfad** geschlossen
  ([`871f6d0`](https://github.com/berlinHistory/crowdCuratio/commit/871f6d0)).
  `ProjectController::update` las `$request['logo']` blind und
  schrieb den Wert in die DB — ein Path-Traversal-Vektor. Logo
  kommt jetzt ausschließlich aus der validierten Upload-Routine,
  `project_image` wird als File mit MIME-Whitelist und 4 MB Limit
  validiert.
- **Privilege-Escalation über `POST /register` geschlossen**
  ([`81055ac`](https://github.com/berlinHistory/crowdCuratio/commit/81055ac)).
  Bis zum Hotfix konnte jeder eingeloggte User (Reader, Reviewer,
  Editor) `POST /register` mit `adminUser=1` aufrufen und sich
  ein Admin-Konto anlegen — `User::$fillable` enthielt `is_admin`
  und `create_project`, der FormRequest war ohne Authorization,
  die Route hatte keinen Rollenfilter. Defense-in-depth-Fix:
  privilegierte Felder aus `User::$fillable` raus, Route hängt
  an `role:Admin`, der Controller setzt die Felder zusätzlich
  nur, wenn der Caller selbst die Admin-Rolle hat. Vier neue
  Pest-Tests sichern den Pfad ab (non-Admin → 403, Admin mit
  `adminUser=1` → neuer Admin, Admin ohne `adminUser` →
  regulärer User, Gast → Login-Redirect).
- **MyISAM → InnoDB für die `texts`-Tabelle**
  ([`5ae90c2`](https://github.com/berlinHistory/crowdCuratio/commit/5ae90c2)).
  Engine-Konvertierung plus Reinstall der Source-Foreign-Keys,
  die unter MyISAM still verworfen wurden — Datenintegrität für
  Quellenangaben wieder gewährleistet.
- **`facade/ignition`-RCE (CVE-2021-3129)** entschärft: durch
  `composer install` mit Lock zieht der Build die geprüfte
  Version 2.17.7 ein, nicht die anfälligen 2.5.0/.1.
- **Charset auf `utf8mb4`** (vorher `utf8mb3`): 4-Byte-Glyphen
  (Emoji etc.) werden ab sofort gespeichert statt gestrippt.
- **MySQL `strict`-Mode** an: zero-dates, GROUP-BY-Verstöße,
  Inserts ohne Pflichtfelder werfen ab sofort hörbar Fehler
  statt still durchzulaufen.
- **File-Upload-Disk auf `public`** umgestellt — File-URLs
  funktionieren, Lateral-Movement-Fläche im lokalen Dev-Netz
  reduziert. Mailpit-Web-Dashboard und phpMyAdmin nur noch auf
  Loopback erreichbar.
- **CVE-2025-27515 — Laravel File Validation Bypass**
  (Severity moderate, betrifft `laravel/framework < 10.48.29`).
  crowdCuratio läuft auf Laravel 8.12 und ist formal in der
  Range. Konkret nicht ausnutzbar: der Angriffspfad braucht eine
  Wildcard-Validation der Form `files.*|image|mimes:…`, die im
  Code aktuell nirgends auftaucht (grep über `app/` ohne
  Treffer). Endgültig zu mit dem Laravel-9-Sprung im Upgrade-
  Pfad.
- **Bekannte offene Lasten aus `composer audit`:** zwei
  abandoned Pakete. `swiftmailer/swiftmailer` (transitive
  Abhängigkeit aus Laravel 8) fällt mit dem Laravel-9-Sprung
  automatisch raus, weil Symfony Mailer übernimmt.
  `laravelcollective/html` braucht einen aktiven Ersatz und ist
  für die Refactoring-Welle vorgemerkt.

---

## [0.8.0] — 2026-05-28 — Übernahme-Baseline

Ausgangspunkt der Modernisierung. Stand des Initial Commit zum
Repo-Übernahmezeitpunkt.

### Funktionsumfang (Stand des Initial Commit)

- Kuratierungs-Hierarchie: Project → Chapter → Entry → Text/Image/Audiovisual/Gallery.
- Quellen- und Copyright-Verknüpfung via `sources`.
- Mehrsprachigkeit über `spatie/laravel-translatable`.
- Rollen, Permissions und projektbezogene Permissions
  (`spatie/laravel-permission` + Custom-Erweiterung `user_has_permissions`).
- Kommentare (polymorph, Threads über `parent_id`), Status-Feld.
- Activity-Log (`spatie/laravel-activitylog`).
- Einladungs-Flow (`spatie/laravel-welcome-notification` +
  eigene `invitations`-Tabelle).
- PDF-Export (dompdf und mpdf parallel installiert).
- Pro Projekt: Impressum, AGB, Datenschutzerklärung, Mail-Einstellungen.

### Stack (Stand des Initial Commit)

- PHP `^7.3 | ^8.0` (Docker-Build: PHP 8.0).
- Laravel 8.12.
- Tailwind 2, Alpine 2.7, Laravel Mix 6.
- MySQL 8 via Sail, Redis, Meilisearch, Mailhog, Selenium im Compose.
- Tests: Stock-Breeze-Auth-Tests, keine Fachtests.

### Bekannte Lasten

- PHP 8.0 und Laravel 8.12 sind End-of-Life.
- `composer.lock` in `.gitignore`.
- Fat-Controller (`ProjectController` ~1.086 LoC, `ContentController` ~822 LoC).
- Zwei PDF-Libraries parallel.
- Eigene Permission-Modell-Variante weicht vom Spatie-Standard ab.

---

[Unreleased]: https://github.com/berlinHistory/crowdCuratio/compare/v0.9.0...HEAD
[0.9.0]: https://github.com/berlinHistory/crowdCuratio/releases/tag/v0.9.0
[0.8.0]: https://github.com/berlinHistory/crowdCuratio/releases/tag/v0.8.0
