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

### Hinzugefügt

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
