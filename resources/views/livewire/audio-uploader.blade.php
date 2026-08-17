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
use Illuminate\Support\Facades\Storage;
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

    /**
     * 5z.7: Audio entfernen. Leert `link` — dann greift der leere-Zustand
     * im audiovisual-player (media-placeholder). Die Datei im Storage
     * bleibt liegen (kein Zugriff auf Cleanup-Runner an der Stelle),
     * das folgt in einem Backlog-Ticket.
     */
    public function removeAudio(): void
    {
        $project = $this->audiovisual->project();
        Gate::authorize('update', $project);

        $this->audiovisual->link = '';
        $this->audiovisual->save();

        $this->dispatch(
            'saved',
            field: 'link',
            model: 'Audiovisual',
            id: $this->audiovisual->getKey(),
        );
    }

    /**
     * Meta-Zeile für die aktuelle Audiodatei: Format (Extension in Caps),
     * Größe (KB/MB) und Upload-Datum aus dem Storage. Alles null-safe;
     * wenn die Datei aus irgendwelchen Gründen nicht mehr im Storage
     * liegt, bleiben die Felder leer.
     *
     * @return array{format: ?string, size: ?string, uploaded: ?string}
     */
    public function fileMeta(): array
    {
        $link = (string) $this->audiovisual->link;
        if ($link === '') {
            return ['format' => null, 'size' => null, 'uploaded' => null];
        }

        $format = strtoupper(pathinfo($link, PATHINFO_EXTENSION) ?: '');

        $disk = Storage::disk('public');
        $path = 'uploads/audios/'.$link;
        $size = null;
        $uploaded = null;
        if ($disk->exists($path)) {
            $bytes = $disk->size($path);
            $size = $bytes >= 1024 * 1024
                ? number_format($bytes / 1024 / 1024, 1, ',', '.').' MB'
                : max(1, (int) round($bytes / 1024)).' KB';
            $uploaded = date('d.m.Y', $disk->lastModified($path));
        }

        return ['format' => $format ?: null, 'size' => $size, 'uploaded' => $uploaded];
    }
}; ?>

@php
    $meta = $this->fileMeta();
    $hasFile = ! empty(trim((string) $audiovisual->link));
    $metaParts = array_filter([
        $meta['format'],
        $meta['size'],
        $meta['uploaded'] ? __('audio_meta_uploaded').' '.$meta['uploaded'] : null,
    ]);
@endphp
<div
    x-data
    class="flex flex-wrap items-center justify-between gap-3"
    aria-label="{{ __('audio_file') }}"
>
    <div class="flex min-w-0 flex-1 flex-col gap-0.5">
        @if ($hasFile)
            {{-- 5z.7: Statt Storage-Key eine Meta-Zeile aus Format,
                 Groesse und Datum. Der Key erscheint nirgends mehr
                 sichtbar. --}}
            <span class="text-body text-ink-900">{{ __('audio_file') }}</span>
            <span class="font-mono text-caption text-ink-500">
                {{ implode(' · ', $metaParts) ?: __('audio_meta_no_file') }}
            </span>
        @else
            <span class="text-body text-ink-500">{{ __('audio_meta_no_file') }}</span>
        @endif
    </div>

    <div class="flex items-center gap-2">
        <label class="cursor-pointer rounded-md border border-ink-300 bg-canvas-bg px-3 py-1.5 text-caption text-ink-900 hover:bg-chrome-active focus-within:outline focus-within:outline-2 focus-within:outline-offset-1 focus-within:outline-primary">
            {{ $hasFile ? __('replace_audio') : __('upload_file') }}
            <input
                type="file"
                wire:model="file"
                accept="audio/mpeg,audio/mp4,audio/wav,audio/ogg,audio/x-m4a"
                class="sr-only"
                @error('file') aria-invalid="true" aria-describedby="audio-uploader-error-{{ $audiovisual->id }}" @enderror
            />
        </label>

        @if ($hasFile)
            <button
                type="button"
                wire:click="removeAudio"
                wire:confirm="{{ __('audio_remove_confirm') }}"
                class="inline-flex items-center gap-1 rounded-md px-2 py-1.5 text-caption text-danger hover:bg-danger-bg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger"
            >
                <x-icon name="trash-2" size="3"/>
                <span>{{ __('audio_remove') }}</span>
            </button>
        @endif
    </div>

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
