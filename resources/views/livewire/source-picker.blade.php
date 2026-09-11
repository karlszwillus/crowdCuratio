<?php

/**
crowdCuratio - Curating together virtually
Copyright (C)2026 - berlinHistory e.V.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program in the file LICENSE.

If not, see <https://www.gnu.org/licenses/>.
 */

use App\Models\Source;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Livewire\Volt\Component;

/**
 * Inline-Picker für Source-Referenzen (Copyright, Quelle) an Text-
 * und Image-Modellen. Ersetzt den Bootstrap-3-Typeahead im alten
 * Modify-Modal und macht Modals damit für Text/Image überflüssig
 * (Phase 5c.6.c.4-Followup).
 *
 * Verhalten:
 * - Anzeigemodus: Chip mit dem aktuellen Source-Namen plus Klick-
 *   Fläche zum Öffnen des Editors.
 * - Editmodus: Text-Input mit Live-Suche (debounced 250ms) gegen
 *   Source-Tabelle, gefiltert nach `type`. Vorschläge als Liste
 *   drunter, Tastatur- und Klick-Bedienbar.
 * - „Neu anlegen": wenn die eingegebene Query keinen exakten Match
 *   hat, gibt es eine Aktion, die eine neue Source-Row mit dem Wert
 *   erzeugt und direkt zuweist. Das ist das established Verhalten
 *   aus dem alten Modal.
 *
 * Props:
 * - `$model`      — Eloquent-Modell mit dem FK-Feld (Text, Image)
 * - `$field`      — Name der FK-Spalte („copyright" oder „origin")
 * - `$relation`   — Name der belongsTo-Method für die Anzeige
 *                   (z. B. „copyrightText")
 * - `$sourceType` — Filter auf `sources.type` („Copyright" oder
 *                   „Origin")
 * - `$label`      — aria-Label
 *
 * Dispatched wie inline-editor:
 * - `saved` (field, model, id)
 * - `save-failed` (field, message)
 */
new class extends Component
{
    public Model $model;

    public string $field;

    public string $relation;

    public string $sourceType;

    public string $label = '';

    public string $query = '';

    public bool $editing = false;

    /**
     * Q4-Etappe 7 · E7-1 (2026-09-11): Typ-Wahl beim Neu-Anlegen.
     * Nach Designer-Nachreview darf ein Nachweis eine Sorte tragen
     * (Person, Institution, Plattform, Lizenz) — die Vorschlags-
     * liste gruppiert nach Sorte, ein neuer Nachweis bekommt sie
     * beim Anlegen optional mit. Leer bleibt zulässig.
     */
    public ?string $newKind = null;

    /** @var array<int, array{id: int, name: string, kind: string|null}> */
    public array $results = [];

    /*
     * Fest verankerte Sorten leben am Model (Source::kinds()),
     * damit Quellenverwaltung und Nachweis-Menue dieselbe Menge
     * teilen.
     */

    public function mount(Model $model, string $field, string $relation, string $sourceType, string $label = ''): void
    {
        $this->model = $model;
        $this->field = $field;
        $this->relation = $relation;
        $this->sourceType = $sourceType;
        $this->label = $label !== '' ? $label : $field;
        $this->query = $this->currentName();
    }

    public function updatedQuery(): void
    {
        $this->results = $this->searchSources($this->query);
    }

    public function startEdit(): void
    {
        $this->editing = true;
        $this->query = $this->currentName();
        $this->results = $this->searchSources($this->query);
    }

    public function cancelEdit(): void
    {
        $this->editing = false;
        $this->query = $this->currentName();
        $this->results = [];
    }

    /**
     * Wählt eine existierende Source aus. Setzt den FK am Parent-
     * Modell, dispatched `saved`.
     */
    public function selectSource(int $sourceId): void
    {
        $this->authorizeUpdate();

        $source = Source::where('type', $this->sourceType)->find($sourceId);
        if ($source === null) {
            return;
        }

        $this->model->setAttribute($this->field, $source->id);
        $this->model->save();

        $this->query = $source->name;
        $this->editing = false;
        $this->results = [];

        $this->dispatch(
            'saved',
            field: $this->field,
            model: class_basename($this->model),
            id: $this->model->getKey(),
        );
    }

    /**
     * Legt eine neue Source mit dem aktuellen Query-Text an und
     * verknüpft sie. Wird gerufen, wenn der User eine Bezeichnung
     * eintippt, die es noch nicht gibt. Trimmt und dedupliziert
     * gegen bestehende Werte (auch case-insensitive), damit der
     * User nicht versehentlich Duplikate erzeugt.
     */
    public function createAndSelect(): void
    {
        $this->authorizeUpdate();

        $name = trim($this->query);
        if ($name === '') {
            return;
        }

        // `sources.name` ist HasTranslations (JSON-Spalte), deshalb
        // greift ein SQL-`LOWER(name)`-Vergleich am JSON-String an
        // (er sieht `{"de":"Bundesarchiv"}`, nicht den Klartext).
        // Für kleine Source-Tabellen ist ein PHP-Roundtrip auf der
        // Locale-aufgelösten Name-Property sauberer und Dialekt-
        // unabhängig.
        // Q4-Etappe 3 / C0-8a (2026-09-07): Dedup und Neu-Anlage
        // scopen auf das Parent-Project, damit „Landesarchiv Berlin"
        // in Projekt A und in Projekt B zwei separate Zeilen ergibt
        // und der Alt-Bestand (project_id=NULL) nicht mit-matched wird.
        $projectId = $this->resolveProject()?->id;

        $needle = mb_strtolower($name);
        $builder = Source::where('type', $this->sourceType);
        if ($projectId !== null) {
            $builder->where('project_id', $projectId);
        }
        $existing = $builder->get()
            ->first(fn (Source $source) => mb_strtolower((string) $source->name) === $needle);

        $source = $existing ?? Source::create([
            'project_id' => $projectId,
            'name' => $name,
            'type' => $this->sourceType,
            'kind' => in_array($this->newKind, Source::kinds(), true) ? $this->newKind : null,
            'is_translated' => false,
        ]);

        $this->model->setAttribute($this->field, $source->id);
        $this->model->save();

        $this->query = $source->name;
        $this->editing = false;
        $this->results = [];

        $this->dispatch(
            'saved',
            field: $this->field,
            model: class_basename($this->model),
            id: $this->model->getKey(),
        );
    }

    /**
     * Aktueller Anzeige-Name für die zugeordnete Source. Kommt aus
     * der übergebenen Relation (z. B. `copyrightText`); leer, wenn
     * (noch) nichts zugewiesen ist.
     */
    private function currentName(): string
    {
        // Q4-Etappe 3 / C0-8a Erweiterung (2026-09-07):
        // Model::shouldBeStrict() verbietet Lazy-Loading — vor dem
        // Zugriff auf die BelongsTo-Relation deshalb explizit
        // eager-loaden. Wenn die Relation gar nicht existiert
        // (Randfall in Volt-Tests), gibt loadMissing ohne Fehler
        // durch und der Fallback greift.
        $this->model->loadMissing($this->relation);
        $related = $this->model->{$this->relation};

        return $related?->name ?? '';
    }

    /**
     * Sucht Source-Rows, die zum aktuellen Type passen und im Namen
     * die Query-Substring enthalten. Limit 8 — reicht für Autocomplete-
     * UX, hält die Antwort schmal.
     *
     * @return array<int, array{id: int, name: string}>
     */
    private function searchSources(string $query): array
    {
        $q = trim($query);
        $builder = Source::where('type', $this->sourceType);

        // Q4-Etappe 3 / C0-8a (2026-09-07): Nach der Projekt-Scope-
        // Umstellung filtern wir hier auf `project_id = <projectId>`,
        // damit die Vorschlagsliste nicht Alt-Rows anderer Projekte
        // (bzw. den ungescopeten Vor-C0-Bestand) mit anzeigt. Wenn das
        // Parent-Modell keine Project-Bindung hat (Randfall — Volt-
        // Test-Setup), zeigen wir wie bisher alles.
        $projectId = $this->resolveProject()?->id;
        if ($projectId !== null) {
            $builder->where('project_id', $projectId);
        }

        if ($q !== '') {
            $builder->where('name', 'like', '%'.$q.'%');
        }

        return $builder
            ->orderBy('name')
            ->limit(12)
            ->get(['id', 'name', 'kind'])
            ->map(fn (Source $s) => [
                'id' => $s->id,
                'name' => (string) $s->name,
                'kind' => $s->kind,
            ])
            ->all();
    }

    /**
     * Q4-Etappe 7 · E7-1: Vorschläge nach Sorte gruppieren, ohne
     * Sorte („null") als eigene Gruppe unten. Reihenfolge fest,
     * damit die Rangfolge Person → Institution → Plattform →
     * Lizenz → ohne Sorte im Editor konsistent ist.
     *
     * Karl 2026-09-11: Im Zitier-Modus „einfach" tragen alle
     * Quellen `kind = null` — dann wäre die Gruppen-Überschrift
     * „Ohne Sorte" redundant. Der Picker fällt in diesem Fall auf
     * eine einzige unbenannte Gruppe zurück.
     *
     * @return array<string, array<int, array{id: int, name: string, kind: string|null}>>
     */
    public function groupedResults(): array
    {
        if (! $this->kindsVisible()) {
            return ['' => $this->results];
        }

        $order = [...Source::kinds(), ''];
        $grouped = array_fill_keys($order, []);

        foreach ($this->results as $result) {
            $key = in_array($result['kind'], Source::kinds(), true) ? $result['kind'] : '';
            $grouped[$key][] = $result;
        }

        return array_filter($grouped, fn (array $items) => $items !== []);
    }

    /**
     * Sorten-UI (Gruppen-Überschriften, Chip pro Vorschlag,
     * Sorten-Wahl beim Neu-Anlegen) zeigt sich nur, wenn das
     * Projekt im Zitier-Modus „voll" arbeitet. Sonst tragen alle
     * Quellen `kind = null` und die Sorten-Anzeige wäre nutzlos.
     */
    public function kindsVisible(): bool
    {
        $project = $this->resolveProject();

        return $project instanceof \App\Models\Project && $project->usesFullCitationDepth();
    }

    /**
     * Gate wie inline-editor: authorisiert gegen das Parent-Project.
     * Text und Image bringen die Methode `project()` über den
     * MediaContent-Pivot mit; Signatur identisch zu resolveProject
     * dort — Duplikat aus demselben Grund (kein Trait in Volt).
     */
    private function authorizeUpdate(): void
    {
        $project = $this->resolveProject();
        Gate::authorize('update', $project);
    }

    private function resolveProject()
    {
        if ($this->model instanceof \App\Models\Project) {
            return $this->model;
        }

        if (! method_exists($this->model, 'project')) {
            return null;
        }

        $result = $this->model->project();

        if ($result instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
            return $result->getResults();
        }

        return $result;
    }

    /**
     * Genau ein Ergebnis, das exakt dem Query-String entspricht?
     * Dann bieten wir „Neu anlegen" nicht an — sonst wäre der
     * Klick verwirrend (der bestehende Match wird sowieso passend
     * angezeigt).
     */
    public function hasExactMatch(): bool
    {
        $q = mb_strtolower(trim($this->query));
        if ($q === '') {
            return false;
        }

        foreach ($this->results as $result) {
            if (mb_strtolower($result['name']) === $q) {
                return true;
            }
        }

        return false;
    }
}; ?>

<div
    class="source-picker"
    aria-label="{{ $label }}"
    x-data="{ open: @entangle('editing') }"
    @keydown.escape.window="if (open) $wire.call('cancelEdit')"
    @click.outside="if (open) $wire.call('cancelEdit')"
>
    @if (! $editing)
        @php
            // Design v6 § 4 (in 5e-Vokabular): eine Beschriftung genügt — sie steht
            // im umgebenden <label>, hier zeigen wir nur den Wert im Feldrahmen.
            // Fehlt der Wert, wechselt der Rahmen auf warning (namentliche
            // Publish-Prüfung).
            $sourceIsEmpty = $query === '';
        @endphp
        {{-- Q4-Etappe 7 · E7-3 (2026-09-11): der pro-Feld-Hint
             „Wird beim Veröffentlichen namentlich aufgeführt" war
             redundant — Designer-Befund 03. Der leere Zustand
             wird jetzt nur noch durch den Placeholder + subtilen
             ink-500-Rahmen markiert. Die Aggregat-Warnung im
             Block-Footer („Bildbeschreibung, Urheber:in fehlt")
             sagt konkret, was fehlt. Warnfarbe (gelb) wandert
             in einer späteren Iteration in den Veröffentlichen-
             Check. --}}
        <button
            type="button"
            wire:click="startEdit"
            class="{{ $sourceIsEmpty ? 'border-line-300 border-dashed bg-canvas-bg/60 text-ink-500' : 'border-line-200 bg-canvas-bg text-ink-900 hover:border-ink-300' }} inline-flex w-full items-center justify-between gap-2 rounded-md border px-3 py-2 text-body focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
        >
            <span class="font-medium">
                {{ $query !== '' ? $query : __('add') }}
            </span>
        </button>
    @else
        {{-- Karl 2026-09-11 (E7-6/Feedback): Bestaetigen-Button entfaellt
             (Klick auf einen Vorschlag oder Enter reichen). Statt eines
             gefuehlten „Auswahl-Zwangs" fuehrt der Menu-Eintrag „Neue
             Quelle anlegen" oben in die Neu-Anlage — er leert die
             Eingabe und fokussiert sie wieder. --}}
        <div class="relative flex flex-col gap-1"
             x-data
             x-ref="pickerRoot">
            <div class="flex items-center gap-2">
                <input
                    type="text"
                    x-ref="pickerInput"
                    wire:model.live.debounce.250ms="query"
                    wire:keydown.enter.prevent="createAndSelect"
                    autofocus
                    aria-label="{{ $label }}"
                    aria-autocomplete="list"
                    aria-controls="source-picker-list-{{ $field }}-{{ $model->getKey() }}"
                    class="w-full rounded-md border border-ink-300 bg-canvas-bg px-2 py-1 text-body text-ink-900 focus:border-primary focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary"
                />
                <button
                    type="button"
                    wire:click="cancelEdit"
                    class="text-caption text-chrome-on-dim hover:text-ink-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                >
                    {{ __('cancel') }}
                </button>
            </div>

            <div
                id="source-picker-list-{{ $field }}-{{ $model->getKey() }}"
                role="listbox"
                class="z-20 mt-1 max-h-72 overflow-auto rounded-md border border-ink-300 bg-canvas-bg py-1 shadow-md"
            >
                {{-- „Neue Quelle anlegen" — erste Zeile im Menu, damit
                     der Nutzer sieht, dass die Vorschlagsliste kein
                     Zwang ist. Klick leert die Query und setzt den
                     Fokus zurueck ins Eingabefeld. --}}
                <button
                    type="button"
                    @click="$wire.set('query', ''); $nextTick(() => $refs.pickerInput?.focus())"
                    class="flex w-full items-center gap-2 border-b border-line-200 px-3 py-2 text-left text-body text-primary hover:bg-primary/5 focus-visible:bg-primary/5 focus-visible:outline-none"
                >
                    <x-icon name="plus" size="4"/>
                    <span>{{ __('source_picker_create_new') }}</span>
                </button>

                @if (! empty($results))
                    <p class="border-b border-line-200 px-3 py-1.5 text-caption font-medium uppercase tracking-wider text-ink-500">
                        {{ __('source_picker_existing_header') }}
                    </p>
                @endif

                {{-- Q4-Etappe 7 · E7-1: Vorschläge nach Sorte
                     gruppiert (Person, Institution, Plattform,
                     Lizenz, ohne Sorte). Sorte als kleiner Chip
                     hinter jedem Namen, Überschriften trennen die
                     Gruppen. Der Designer-Report bemängelte die
                     flache Liste, in der eine Lizenz wie ein
                     Urheber aussah. --}}
                @foreach ($this->groupedResults() as $kind => $items)
                    <div class="border-t border-ink-300/40 first:border-t-0 py-1">
                        @if ($this->kindsVisible())
                            <div class="px-3 pt-1 pb-0.5 text-caption font-mono uppercase tracking-wider text-ink-500">
                                {{ $kind === '' ? __('source_picker_group_uncategorised') : __('source_kind_'.$kind) }}
                            </div>
                        @endif
                        <ul>
                            @foreach ($items as $result)
                                <li>
                                    <button
                                        type="button"
                                        role="option"
                                        wire:click="selectSource({{ $result['id'] }})"
                                        class="flex w-full items-center justify-between gap-3 px-3 py-1 text-left text-body text-ink-900 hover:bg-line-100 focus-visible:bg-line-100 focus-visible:outline-none"
                                    >
                                        <span class="min-w-0 truncate">{{ $result['name'] }}</span>
                                        @if (! empty($result['kind']))
                                            <span class="shrink-0 rounded-sm border border-line-200 px-1.5 py-0.5 text-caption font-mono text-ink-500">
                                                {{ __('source_kind_'.$result['kind']) }}
                                            </span>
                                        @endif
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach

                @if (trim($query) !== '' && ! $this->hasExactMatch())
                    <div class="border-t border-ink-300/60 p-2">
                        <div class="text-caption text-ink-600 mb-1.5">
                            + {{ __('create_new') }}: <span class="font-medium">„{{ trim($query) }}"</span>
                        </div>
                        @if ($this->kindsVisible())
                            <div class="flex flex-wrap items-center gap-1 mb-2">
                                <span class="text-caption font-mono text-ink-500 mr-1">{{ __('source_picker_new_kind_label') }}:</span>
                                <button type="button"
                                        wire:click="$set('newKind', null)"
                                        class="{{ $newKind === null ? 'border-ink-800 text-ink-900' : 'border-line-200 text-ink-600' }} rounded-sm border px-2 py-0.5 text-caption font-mono">
                                    —
                                </button>
                                @foreach (\App\Models\Source::kinds() as $k)
                                    <button type="button"
                                            wire:click="$set('newKind', '{{ $k }}')"
                                            class="{{ $newKind === $k ? 'border-ink-800 text-ink-900' : 'border-line-200 text-ink-600' }} rounded-sm border px-2 py-0.5 text-caption font-mono">
                                        {{ __('source_kind_'.$k) }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                        <button
                            type="button"
                            wire:click="createAndSelect"
                            class="block w-full rounded-md bg-primary px-3 py-1.5 text-caption font-medium text-primary-on hover:opacity-90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                        >
                            {{ __('source_picker_create_and_select') }}
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
