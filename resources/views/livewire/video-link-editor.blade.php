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
use App\Support\VideoLink;
use Illuminate\Support\Facades\Gate;
use Livewire\Volt\Component;

/**
 * Phase 5z.8: Watch-URL-Feld für den Video-Block.
 *
 * Der Nutzer klebt die Adresse aus dem Browser ein (Watch, youtu.be,
 * embed, shorts) — der Save-Handler normalisiert das über
 * `VideoLink::toEmbedUrl()` auf die kanonische Embed-URL und
 * speichert. Nicht-YouTube-URLs bleiben stehen und werden vom Player
 * als Fehlerzustand gerendert (§ 5 Zustand 4 im Briefing).
 *
 * Dispatched `saved` mit `field=link, model=Audiovisual, id=…`, damit
 * der audiovisual-player denselben Refresh-Pfad wie beim Inline-Editor
 * benutzt.
 */
new class extends Component
{
    public Audiovisual $audiovisual;

    /** Eingabe im Textfeld — kann Watch-URL, youtu.be-URL, Embed sein. */
    public string $draft = '';

    public string $error = '';

    public function mount(Audiovisual $audiovisual): void
    {
        $this->audiovisual = $audiovisual;
        $this->draft = (string) $audiovisual->link;
    }

    public function save(): void
    {
        $project = $this->audiovisual->project();
        Gate::authorize('update', $project);

        $trimmed = trim($this->draft);
        if ($trimmed === '') {
            $this->error = __('video_link_error_reason_empty');
            $this->audiovisual->link = '';
            $this->audiovisual->save();
            $this->dispatchSaved();

            return;
        }

        $embed = VideoLink::toEmbedUrl($trimmed);
        if ($embed === null) {
            // Nicht-YouTube: rohen Wert speichern, damit der Nutzer
            // ihn im Fehlerpanel weiter bearbeiten kann.
            $this->error = __('video_link_error_reason_invalid');
            $this->audiovisual->link = $trimmed;
            $this->audiovisual->save();
            $this->dispatchSaved();

            return;
        }

        $this->error = '';
        $this->audiovisual->link = $embed;
        $this->audiovisual->save();
        $this->draft = $embed;
        $this->dispatchSaved();
    }

    private function dispatchSaved(): void
    {
        $this->dispatch(
            'saved',
            field: 'link',
            model: 'Audiovisual',
            id: $this->audiovisual->getKey(),
        );
    }
}; ?>

<div class="flex flex-col gap-1">
    <label class="text-caption font-medium text-ink-700" for="video-link-{{ $audiovisual->id }}">
        {{ __('video_link_label') }}
    </label>
    <div class="flex gap-2">
        <input
            id="video-link-{{ $audiovisual->id }}"
            type="url"
            wire:model="draft"
            wire:keydown.enter.prevent="save"
            placeholder="{{ __('video_link_placeholder') }}"
            class="w-full rounded-md border border-line-200 bg-canvas-bg px-3 py-2 text-body text-ink-900 focus:border-primary focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary"
            aria-describedby="video-link-hint-{{ $audiovisual->id }}"
        />
        <button
            type="button"
            wire:click="save"
            class="inline-flex items-center gap-1 rounded-md bg-primary px-3 py-2 text-caption font-medium text-primary-on hover:opacity-90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
        >
            {{ __('video_link_check') }}
        </button>
    </div>
    <p id="video-link-hint-{{ $audiovisual->id }}" class="text-caption text-ink-500">
        {{ __('video_link_hint') }}
    </p>
    @if ($error !== '')
        <p class="text-caption text-warning">{{ $error }}</p>
    @endif
</div>
