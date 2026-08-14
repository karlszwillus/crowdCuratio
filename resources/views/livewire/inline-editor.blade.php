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
 * Inline-Editor für einzelne Modell-Felder.
 *
 * Ersetzt in Phase 5c die klassischen Edit-Modale: Titel, Subtitel,
 * Beschreibungen und ähnliche Felder werden direkt im Content-
 * Canvas editiert. Save läuft on-blur oder nach 1500 ms Debounce
 * on-input.
 *
 * Props:
 * - `$model`       — das Eloquent-Modell (Chapter, Entry, Text …)
 * - `$field`       — Feld-Name im Modell (name, subtitle, description …)
 * - `$rules`       — Laravel-Validation-Rules-String (default 'nullable|string')
 * - `$multiline`   — bool, ob <textarea> statt <input> genutzt wird
 * - `$label`       — barrierefreies Label (aria-label)
 *
 * Ereignisse:
 * - `saved`        — nach erfolgreichem Update, für Auto-Save-Indikator (5c.2)
 * - `save-failed`  — bei Validation-Fail oder Server-Fehler, für Toast (5c.3)
 *
 * Autorisierung: das übergebene Modell muss zu einem `Project`
 * gehören (`$model->project` oder `$model` selbst). Wir gate'n gegen
 * `update` auf diesem Projekt — das ist die established Konvention
 * aus Phase 1/4, alle Content-Modelle nutzen sie.
 */
new class extends Component
{
    public Model $model;

    public string $field;

    public string $rules = 'nullable|string';

    public bool $multiline = false;

    public string $label = '';

    public string $value = '';

    public function mount(Model $model, string $field, string $rules = 'nullable|string', bool $multiline = false, string $label = ''): void
    {
        $this->model = $model;
        $this->field = $field;
        $this->rules = $rules;
        $this->multiline = $multiline;
        $this->label = $label !== '' ? $label : $field;

        // Aktueller Wert des Feldes für die Anzeige. HasTranslations
        // liefert den Locale-Wert automatisch beim String-Cast.
        $this->value = (string) $model->getAttribute($field);
    }

    /**
     * Wird von Livewire nach jedem `wire:model`-Update gerufen (blur
     * oder 1500ms-Debounce). Prüft Autorisierung, validiert und
     * schreibt die Änderung.
     */
    public function updatedValue(string $newValue): void
    {
        $project = $this->resolveProject();
        Gate::authorize('update', $project);

        try {
            $this->validate([
                'value' => $this->rules,
            ]);
        } catch (ValidationException $e) {
            $this->dispatch('save-failed', field: $this->field, message: $e->validator->errors()->first('value'));

            throw $e;
        }

        $this->model->setAttribute($this->field, $newValue);
        $this->model->save();

        $this->dispatch('saved', field: $this->field, model: class_basename($this->model), id: $this->model->getKey());
    }

    /**
     * Löst das Project für den Authorize-Gate auf. Chapter, Entry,
     * Text usw. haben eigene Konventionen, wie sie ihr Project
     * erreichen — hier zentral aufgelöst.
     */
    private function resolveProject()
    {
        if ($this->model instanceof \App\Models\Project) {
            return $this->model;
        }

        if (method_exists($this->model, 'project')) {
            $project = $this->model->project;

            if ($project === null && method_exists($this->model, 'project')) {
                // Content-Modelle (Text, Image, …) haben project()
                // als Methode, die den Tree hochwandert.
                $project = $this->model->project();
            }

            return $project;
        }

        return null;
    }
}; ?>

<div>
    @if ($multiline)
        <textarea
            wire:model.blur="value"
            wire:model.live.debounce.1500ms="value"
            aria-label="{{ $label }}"
            @error('value') aria-invalid="true" aria-describedby="inline-editor-error-{{ $field }}" @enderror
            class="w-full rounded-md border border-ink-300 bg-canvas-bg px-3 py-2 text-body text-ink-900 focus:border-primary focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary"
            rows="3"
        >{{ $value }}</textarea>
    @else
        <input
            type="text"
            value="{{ $value }}"
            wire:model.blur="value"
            wire:model.live.debounce.1500ms="value"
            aria-label="{{ $label }}"
            @error('value') aria-invalid="true" aria-describedby="inline-editor-error-{{ $field }}" @enderror
            class="w-full rounded-md border border-ink-300 bg-canvas-bg px-3 py-2 text-body text-ink-900 focus:border-primary focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary"
        />
    @endif

    @error('value')
        <p id="inline-editor-error-{{ $field }}" class="mt-1 text-sm text-primary">
            {{ $message }}
        </p>
    @enderror
</div>
