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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

/**
 * Inline-Rich-Text-Editor mit Quill-Bridge.
 *
 * Rendert ein Alpine-gemanagtes Quill-Editor-Div direkt am Ort
 * des Content-Elements. HTML-Änderungen werden debounced (1500ms)
 * an die `save()`-Action gepusht, die validiert, autorisiert und
 * speichert. Dispatcht `saved` / `save-failed` wie inline-editor.
 *
 * Props:
 * - `$model`   — Eloquent-Modell mit dem HTML-Feld (z. B. Text, Chapter)
 * - `$field`   — Feld-Name (z. B. `text`, `description`)
 * - `$rules`   — Validation-Rules-String
 * - `$label`   — aria-Label
 *
 * Ausdrückliche Anleihen an inline-editor:
 * - resolveProject()-Logik ist identisch
 * - Gate::authorize('update', $project) läuft vor Validate+Save
 * - `saved`-Event Payload folgt der bekannten Signatur
 *   (field, model, id) — Sidebar-Tree und Auto-Save-Indikator
 *   springen ohne Extra-Verdrahtung an.
 *
 * Der Editor speichert den kompletten Quill-innerHTML. Bewusst
 * keine Sanitizer-Schicht auf View-Ebene: die Freigabe-Kette der
 * Frontend-Ausgabe rendert diesen Wert mit `{!! !!}` seit Jahren,
 * die Whitelist steht im Text-Modell / -Policy (Phase 4).
 */
new class extends Component
{
    public Model $model;

    public string $field;

    public string $rules = 'nullable|string';

    public string $label = '';

    public string $value = '';

    public function mount(Model $model, string $field, string $rules = 'nullable|string', string $label = ''): void
    {
        $this->model = $model;
        $this->field = $field;
        $this->rules = $rules;
        $this->label = $label !== '' ? $label : $field;
        $this->value = (string) $model->getAttribute($field);
    }

    /**
     * Wird von der Alpine-Bridge nach jeder debouncten Text-Change
     * gerufen. Der HTML-String kommt als Argument.
     */
    public function save(string $html): void
    {
        $project = $this->resolveProject();
        Gate::authorize('update', $project);

        $this->value = $html;

        try {
            $this->validate([
                'value' => $this->rules,
            ]);
        } catch (ValidationException $e) {
            $this->dispatch('save-failed', field: $this->field, message: $e->validator->errors()->first('value'));

            throw $e;
        }

        $this->model->setAttribute($this->field, $html);
        $this->model->save();

        $this->dispatch(
            'saved',
            field: $this->field,
            model: class_basename($this->model),
            id: $this->model->getKey(),
        );
    }

    /**
     * Siehe inline-editor::resolveProject — Duplikat, weil beide
     * Komponenten sonst über eine Shared-Trait/Concern verbunden
     * werden müssten, was in Volt-Single-File-Components mehr
     * Reibung als Nutzen bringt.
     */
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
}; ?>

<div
    x-data="{ ...richTextEditor(@js($value)), focused: false }"
    x-init="initQuill($refs.editor); $refs.editor.addEventListener('focusin', () => focused = true); $refs.editor.addEventListener('focusout', (e) => { if (!$el.contains(e.relatedTarget)) focused = false; })"
    wire:ignore
    :class="focused ? 'rich-text-editor is-focused' : 'rich-text-editor'"
    aria-label="{{ $label }}"
>
    {{-- Quill mountet auf $refs.editor. `wire:ignore` verhindert,
         dass Livewire das Editor-DOM beim Re-Render überschreibt —
         sonst würde die Caret-Position beim Auto-Save verloren
         gehen. Die Toolbar (`.ql-toolbar`) wird per CSS nur dann
         sichtbar, wenn `is-focused` am Root sitzt (P1.3 Review-
         Punkt: Toolbar nur am aktiven Block). --}}
    {{-- 5z.4: Kein 6-rem-Mindestbereich mehr — leere Beschreibungen
         sollen keinen Höhen-Loch von 96 px reservieren. Klickfläche
         bleibt über die kleine 2-rem-Mindestbox erhalten; Quill wächst
         mit dem Inhalt. --}}
    <div x-ref="editor" class="min-h-[2rem] bg-transparent text-ink-900"></div>

    @error('value')
        <p class="mt-1 text-sm text-primary">
            {{ $message }}
        </p>
    @enderror
</div>
