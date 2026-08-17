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

    /** @var array<int, array{id: int, name: string}> */
    public array $results = [];

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
        $needle = mb_strtolower($name);
        $existing = Source::where('type', $this->sourceType)
            ->get()
            ->first(fn (Source $source) => mb_strtolower((string) $source->name) === $needle);

        $source = $existing ?? Source::create([
            'name' => $name,
            'type' => $this->sourceType,
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

        if ($q !== '') {
            $builder->where('name', 'like', '%'.$q.'%');
        }

        return $builder
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name'])
            ->map(fn (Source $s) => ['id' => $s->id, 'name' => $s->name])
            ->all();
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
>
    @if (! $editing)
        @php
            // Design v6 § 4 (in 5e-Vokabular): eine Beschriftung genügt — sie steht
            // im umgebenden <label>, hier zeigen wir nur den Wert im Feldrahmen.
            // Fehlt der Wert, wechselt der Rahmen auf warning (namentliche
            // Publish-Prüfung).
            $sourceIsEmpty = $query === '';
        @endphp
        <button
            type="button"
            wire:click="startEdit"
            class="{{ $sourceIsEmpty ? 'border-warning bg-warning-bg/40 text-warning' : 'border-line-200 bg-canvas-bg text-ink-900 hover:border-ink-300' }} inline-flex w-full items-center justify-between gap-2 rounded-md border px-3 py-2 text-body focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
        >
            <span class="font-medium">
                {{ $query !== '' ? $query : __('add') }}
            </span>
            @if ($sourceIsEmpty)
                <span class="text-caption">{{ __('source_missing_hint') }}</span>
            @endif
        </button>
    @else
        <div class="relative flex flex-col gap-1">
            <div class="flex items-center gap-2">
                <input
                    type="text"
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

            <ul
                id="source-picker-list-{{ $field }}-{{ $model->getKey() }}"
                role="listbox"
                class="z-20 mt-1 max-h-56 overflow-auto rounded-md border border-ink-300 bg-canvas-bg py-1 shadow-md"
                @if (empty($results) && ! $this->hasExactMatch() && trim($query) === '') style="display:none" @endif
            >
                @foreach ($results as $result)
                    <li>
                        <button
                            type="button"
                            role="option"
                            wire:click="selectSource({{ $result['id'] }})"
                            class="block w-full px-3 py-1 text-left text-body text-ink-900 hover:bg-chrome-active focus-visible:bg-chrome-active focus-visible:outline-none"
                        >
                            {{ $result['name'] }}
                        </button>
                    </li>
                @endforeach

                @if (trim($query) !== '' && ! $this->hasExactMatch())
                    <li class="border-t border-ink-300/60">
                        <button
                            type="button"
                            wire:click="createAndSelect"
                            class="block w-full px-3 py-1 text-left text-caption text-primary hover:bg-chrome-active focus-visible:bg-chrome-active focus-visible:outline-none"
                        >
                            + {{ __('create_new') }}: „{{ trim($query) }}"
                        </button>
                    </li>
                @endif
            </ul>
        </div>
    @endif
</div>
