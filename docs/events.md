# Event-Katalog

Kanäle, über die crowdCuratio zwischen Server (Livewire), Client-State
(Alpine) und Browser-DOM kommuniziert. Neue Events werden hier
dokumentiert, bevor sie in Blades landen (siehe ADR-0027 „Alpine-/
Livewire-Event-Vertrag").

Vier Kanäle mit klarer Zuständigkeit:

1. **Livewire-Component-Events** — `$this->dispatch(...)` oder
   `Livewire.dispatch(...)`. Server-getriggerte Kommunikation
   zwischen Livewire-Components; Hörer über `#[On('name')]` oder
   `wire:on:name`.
2. **Alpine-Stores** — `Alpine.store('name')`. Globaler Client-
   State ohne Server-Roundtrip.
3. **Native `window`-Events** — `window.dispatchEvent(new CustomEvent(...))`.
   Cross-cutting Aktionen zwischen Panels, Alpine-Komponenten und
   Editor-DOM.
4. **Alpine-Component-lokale Events** — `x-on:name`, `$dispatch`
   innerhalb einer Alpine-Insel. Nur für Kind-Eltern-Kommunikation.

Konvention: `namespace:aktion` in Kleinschreibung, Payload immer als
`{ detail: {...} }` (bei nativen Events). Namespace = Panel/Feature-
Zone, Aktion = Verb oder Zustand.

---

## Livewire-Events

### `saved`

Nach jedem erfolgreichen `inline-editor`- oder `rich-text-editor`-
Save. Verwendet vom Auto-Save-Indikator, vom `data-history-field`-
Diff-Overlay und vom Chip in inline-editor.

- **Sender:** `livewire/inline-editor.blade.php`,
  `livewire/rich-text-editor.blade.php`
- **Payload:** `{ field: string, model: string, id: int }`
  - `field` — Feld-Name des Volt-Components (z.B. `name`, `alt`,
    `description`)
  - `model` — `class_basename` des Eloquent-Modells (z.B. `Chapter`,
    `Image`)
  - `id` — Primary-Key des Modells
- **Hörer:** Alpine-Bridge zum `saveStatus`-Store, Chip-Handler im
  `inline-editor`-Blade selbst

### `save-failed`

Bei Validierungs- oder Autorisierungs-Fehler im Volt-Save.

- **Sender:** `livewire/inline-editor.blade.php`,
  `livewire/rich-text-editor.blade.php`
- **Payload:** `{ field: string, message: string }`
- **Hörer:** Alpine-Toast-Store (`Alpine.store('toast')` via
  `resources/js/toast.js`)

### `history-panel:load`

Öffentlicher Livewire-Trigger fürs Verlauf-Panel. Lädt Revisions
für das übergebene Subject in `history-panel-list`.

- **Sender:** `x-layout.history-panel` (Alpine-Bridge), gefüttert vom
  gleichnamigen nativen Event (siehe unten)
- **Payload:** `{ subjectType: string, subjectId: int, scope?: 'block'|'entry'|'project' }`
- **Hörer:** `livewire/history-panel-list.blade.php` (`#[On]`)

### `comment-panel:load`

Analoges Pendant fürs Kommentar-Panel.

- **Sender:** `x-layout.comment-panel` (Alpine-Bridge), gefüttert vom
  `panel:load-and-open`-Event
- **Payload:** `{ commentableType: string, commentableId: int }`
- **Hörer:** `livewire/comment-panel-list.blade.php` (`#[On]`)

### `revision-selected`

Wenn der Kurator im Verlauf-Panel eine Fassungs-Karte klickt.
Startet den Diff-Modus im Editor.

- **Sender:** `livewire/history-panel-list.blade.php`
  (`$this->dispatch('revision-selected', ...)`)
- **Payload:**
  ```json
  {
    "revisionId": 42,
    "revisionVersion": 9,
    "revisionAuthor": "Karl Szwillus",
    "subjectType": "Chapter",
    "subjectId": 3,
    "fields": {
      "name": { "html": "...", "added": 3, "removed": 1, "old": "...", "new": "..." }
    }
  }
  ```
- **Hörer:** `resources/js/history-diff.js` (window-Event-Listener)

---

## Native `window`-Events

### `history-panel:load-and-open`

Öffnet das Verlauf-Panel und lädt gleichzeitig die Revisions. Ein
Kombi-Event, damit Consumer nicht zwei Aktionen sequenziell dispatchen
müssen.

- **Sender:** `<x-ui.history-trigger>`,
  `livewire/history-panel-list.blade.php` (bei Scope-Wechsel)
- **Payload:** `{ subjectType: string, subjectId: int, scope?: string }`
- **Effekt:** dispatcht intern `Livewire.dispatch('history-panel:load', ...)`
  plus `panel:open` mit `{name: 'history'}`

### `panel:open`

Öffnet ein benanntes Slide-Panel. Namens-Guard: schließt gleichzeitig
das jeweils andere Panel (§ Design v6 § 6).

- **Sender:** `history-panel:load-and-open`,
  `comment-panel:load-and-open`, direkter Aufruf
- **Payload:** `{ name: 'history'|'comments' }`
- **Hörer:** `x-layout.history-panel`, `x-layout.comment-panel`

### `panel:load-and-open`

Deprecated-Legacy für das Kommentar-Panel. Neuer Code sollte
`panel:open` mit `{name: 'comments'}` + `comment-panel:load` nutzen.

- **Sender:** `x-comment.trigger`,
  `resources/views/dashboard/_comment-row.blade.php`
- **Payload:** `{ commentableType: string, commentableId: int }`
- **Effekt:** öffnet Kommentar-Panel und lädt gleichzeitig

### `entry-modal:open`

Öffnet das Alpine-Entry-Add-Modal. Ersetzt seit I1.2 den früher
jQuery-verkabelten `.addEntry`-Handler.

- **Sender:** Chapter-/Entry-Add-Buttons im Editor
  (`chapters/_canvas.blade.php`, `livewire/sidebar-tree.blade.php`)
- **Payload:** `{ chapterId: int, chapterName: string }`
- **Hörer:** `entries/_add-modal.blade.php` (Alpine `@entry-modal:open.window`)

### `history:restore-request`

Ein Restore-Klick im Verlauf-Panel öffnet den Bestätigungs-Dialog.

- **Sender:** `livewire/history-panel-list.blade.php` (Restore-Button)
- **Payload:** `{ revisionId: int, version: int, hasTranslations: bool }`
- **Hörer:** `<x-layout.history-restore-dialog>` (Alpine `@history:restore-request.window`)

### `history:diff-close`

Beendet den Diff-Modus im Editor.

- **Sender:** Banner-Close-Button im Diff-Modus,
  Escape-Handler auf `document`
- **Payload:** kein Detail nötig
- **Hörer:** `resources/js/history-diff.js`

---

## Alpine-Stores

### `saveStatus`

Autosave-Zustandsübersicht — pro Sicht ein Store, gefüttert vom
`saved`/`save-failed`-Livewire-Event. Wird von Block-Cards für den
per-Feld-Chip verwendet.

- **Datei:** `resources/js/save-status.js`
- **API:**
  - `markSaved(slot)` — Feld auf „gespeichert" setzen, Chip aufblitzen
  - `markSaveFailed(slot, message)` — Feld auf „fehlgeschlagen"
  - `markSaving()` — global „Auto-Save läuft"
  - `blocks[slot]` — pro Feld `{ state, at }` mit 10-s-Timeout
- **Bridge:** `resources/js/toast.js` (Fehler → Toast),
  Block-Card-Alpine (Chip-Rendering)

### `theme`

Aktives Theme (`crowdCuratio` oder `am` für Aktives Museum).
Setzt das `data-theme`-Attribut am `<html>`-Element mit 180-ms-
Color-Transition.

- **Datei:** `resources/js/theme.js`
- **API:**
  - `set(theme)` — Theme wechseln
  - `current` — aktueller Wert (getter)
- **Persistierung:** `localStorage` unter `theme`

### `toast`

Ein Message-Bus für Toast-Nachrichten am Bildschirm-Rand.

- **Datei:** `resources/js/toast.js`
- **API:**
  - `push(message, kind = 'info')` — Toast anzeigen (`info` | `error` |
    `success`)
  - `messages` — aktuelle Queue
- **Helper:** `window.ccToast(message, kind)` (globales Alias)

---

## Alpine-Data-Components

### `richTextEditor(initialHtml)`

Alpine-`x-data`-Definition für den Quill-basierten Rich-Text-Editor.

- **Datei:** `resources/js/rich-text-editor.js`
- **Verwendung:** `<div x-data="{ ...richTextEditor(@js($value)) }" x-init="initQuill($refs.editor)">`
- **API:**
  - `initQuill(container)` — mountet Quill in den Container, syncet
    Initial-HTML in den Editor-DOM
  - `chipVisible` — pro-Feld-Chip nach erfolgreichem Save
- **Save:** `$wire.call('save', html)` mit 1500-ms-Debounce

### `ccBreadcrumb(tree)`

Live-Breadcrumb-Registrierung für `<x-ui.breadcrumb :tree="...">`.
Reagiert auf `hashchange` und leitet den aktuellen Pfad (Projekt →
Kapitel → Abschnitt) aus dem Tree-Objekt ab.

- **Datei:** `resources/js/breadcrumb.js`

---

## Pflege

Neue Events werden in einem der obigen Blöcke ergänzt, bevor sie
in einer Blade committet werden. PR-Template hat eine Check-Box
„Event-Katalog aktualisiert?" (ADR-0027 § Folgeaufgaben).
