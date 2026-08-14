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

use App\Models\Audiovisual;
use App\Services\AudiovisualService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

/**
 * Inline-Uploader für Audio-Dateien.
 *
 * Bei type=audio ersetzt diese Komponente das reine Link-Textfeld:
 * sie zeigt den aktuellen Dateinamen und einen File-Input, über den
 * eine neue Audiodatei hochgeladen werden kann. Die Datei geht durch
 * `AudiovisualService::resolveLink()` (server-generierter Name,
 * NF-SEC-201, kein Client-Input im Path).
 *
 * Dispatched `saved` mit `model=Audiovisual` und `id`, damit
 * <livewire:audiovisual-player> den frischen Link direkt lädt und
 * das `<audio>`-Element mit der neuen Quelle rendert.
 */
new class extends Component
{
    use WithFileUploads;

    public Audiovisual $audiovisual;

    /**
     * Temporärer Upload aus dem File-Input. Wird nach erfolgreichem
     * Save geleert, damit der nächste Upload nicht die letzte Datei
     * wiedersieht.
     */
    public $file = null;

    public function mount(Audiovisual $audiovisual): void
    {
        $this->audiovisual = $audiovisual;
    }

    /**
     * Wird automatisch getriggert, sobald der User eine Datei
     * ausgewählt hat (Livewire lifecycle hook `updated{Prop}`).
     * Validiert MIME und Größe, ruft den Service zum Speichern
     * und dispatched das `saved`-Event.
     */
    public function updatedFile(): void
    {
        $project = $this->audiovisual->project();
        Gate::authorize('update', $project);

        try {
            $this->validate([
                // Whitelist wie im AudiovisualController::store —
                // audio/mpeg, mp4, wav, ogg, x-m4a; 20 MB.
                'file' => 'required|file|mimetypes:audio/mpeg,audio/mp4,audio/wav,audio/ogg,audio/x-m4a|max:20480',
            ]);
        } catch (ValidationException $e) {
            $this->dispatch('save-failed', field: 'file', message: $e->validator->errors()->first('file'));

            throw $e;
        }

        // Livewire's TemporaryUploadedFile erbt von
        // Illuminate\Http\UploadedFile — der Service kann es direkt
        // verarbeiten, ohne dass wir das Objekt selbst neu wrappen
        // müssten.
        /** @var AudiovisualService $service */
        $service = app(AudiovisualService::class);
        $newName = $service->resolveLink(null, $this->file);

        if ($newName === null) {
            return;
        }

        $this->audiovisual->link = $newName;
        $this->audiovisual->save();

        $this->file = null;

        $this->dispatch(
            'saved',
            field: 'link',
            model: 'Audiovisual',
            id: $this->audiovisual->getKey(),
        );
    }
}; ?>

<div
    x-data
    class="flex items-center gap-3"
    aria-label="{{ __('audio_file') }}"
>
    <span class="text-caption text-chrome-on-dim">
        {{ __('current_file') }}:
        <code class="rounded bg-canvas-dim px-1">{{ $audiovisual->link }}</code>
    </span>

    <label class="cursor-pointer rounded-md border border-ink-300 bg-canvas-bg px-3 py-1 text-body text-ink-900 hover:bg-chrome-active focus-within:outline focus-within:outline-2 focus-within:outline-offset-1 focus-within:outline-primary">
        {{ __('replace_audio') }}
        <input
            type="file"
            wire:model="file"
            accept="audio/mpeg,audio/mp4,audio/wav,audio/ogg,audio/x-m4a"
            class="sr-only"
            @error('file') aria-invalid="true" aria-describedby="audio-uploader-error-{{ $audiovisual->id }}" @enderror
        />
    </label>

    <span
        wire:loading
        wire:target="file"
        class="text-caption text-chrome-on-dim"
    >{{ __('uploading') }}…</span>

    @error('file')
        <p id="audio-uploader-error-{{ $audiovisual->id }}" class="text-caption text-primary">
            {{ $message }}
        </p>
    @enderror
</div>
