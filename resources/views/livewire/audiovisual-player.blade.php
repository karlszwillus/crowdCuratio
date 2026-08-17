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
use Livewire\Attributes\On;
use Livewire\Volt\Component;

/**
 * Audiovisual-Player als eigenständige Volt-Komponente.
 *
 * Rendert je nach `type` einen `<audio controls>`- oder einen
 * YouTube-`<iframe>`-Block und aktualisiert sich, sobald der
 * Inline-Editor auf dem Audiovisual `link` oder `type` verändert.
 *
 * Der Player horcht auf `saved`-Events (dispatched vom
 * `inline-editor`) und lädt sein Audiovisual frisch aus der
 * Datenbank, sobald ein Feld dieses Modells gespeichert wurde.
 * So kann Karl z. B. den Type von „audio" auf „video" umschalten,
 * und der iframe erscheint sofort.
 */
new class extends Component
{
    public Audiovisual $audiovisual;

    public function mount(Audiovisual $audiovisual): void
    {
        $this->audiovisual = $audiovisual;
    }

    /**
     * Aktualisiert den Player, wenn irgendein Feld des Audiovisuals
     * über den Inline-Editor gespeichert wurde. Wir prüfen die
     * dispatched Event-Payload (`model`, `id`) — nur unser eigenes
     * Audiovisual triggert das Refresh, andere Modelle werden
     * ignoriert, damit nicht jede Editor-Interaktion irgendwo
     * einen SQL-Reload verursacht.
     */
    #[On('saved')]
    public function refreshFromSave(string $field, string $model, $id): void
    {
        if ($model !== 'Audiovisual' || (int) $id !== (int) $this->audiovisual->getKey()) {
            return;
        }

        $this->audiovisual = $this->audiovisual->fresh();
    }
}; ?>

<div>
    {{-- Wrapper enthält jetzt Player UND das type-abhängige
         Editier-Feld (Link vs. Audio-Uploader), damit der komplette
         Media-Block bei einem Type-Wechsel als Einheit refresht.
         Vorher standen Player und Feld getrennt in chapters/index —
         der äußere Blade-@if reagierte nicht auf DB-Updates und der
         UI-Zustand fiel um einen Save hinterher. --}}
    @if ($audiovisual->type === 'audio')
        @if (empty(trim((string) $audiovisual->link)))
            <x-ui.media-placeholder type="audio"/>
        @else
            {{-- 5z.7: Plyr enhanced das native <audio> — kein Browser-⋮-
                 Menue mit „Herunterladen" mehr, einheitlicher Chrome. --}}
            <audio
                controls
                class="cc-plyr w-full"
                id="audio_{{ $audiovisual->id }}"
                src="{{ route('audio', $audiovisual->link) }}"
                wire:key="av-audio-{{ $audiovisual->id }}-{{ md5((string) $audiovisual->link) }}"
                wire:ignore
            ></audio>
        @endif

        @can('update', $audiovisual->project())
            <div class="mt-3">
                <livewire:audio-uploader
                    :audiovisual="$audiovisual"
                    :key="'av-audio-uploader-'.$audiovisual->id" />
            </div>
        @endcan
    @else
        @if (empty(trim((string) $audiovisual->link)))
            <x-ui.media-placeholder type="video"/>
        @else
            @php
                // 5z.7: YouTube-Embed-ID aus der bestehenden Embed-URL ziehen.
                // Plyr rendert dann seinen eigenen Player-Chrome mit
                // Zwei-Klick-Einbettung; noCookie ist im Plyr-Init aktiv,
                // damit vor dem Play kein Drittanbieter-Request rausgeht.
                $ytEmbedId = null;
                if (preg_match('#(?:youtube\.com/embed/|youtu\.be/|youtube\.com/watch\?v=)([A-Za-z0-9_-]{6,})#', (string) $audiovisual->link, $m)) {
                    $ytEmbedId = $m[1];
                }
            @endphp
            @if ($ytEmbedId)
                <div
                    class="cc-plyr-video"
                    data-plyr-provider="youtube"
                    data-plyr-embed-id="{{ $ytEmbedId }}"
                    wire:key="av-video-{{ $audiovisual->id }}-{{ md5((string) $audiovisual->link) }}"
                    wire:ignore
                    aria-label="{{ __('audiovisual_player') }}"
                ></div>
            @else
                {{-- Nicht-YouTube-Fallback: rohes iframe rendern (bleibt
                     bis 5z.8, das andere Quellen sauber abfängt). --}}
                <iframe
                    wire:key="av-iframe-{{ $audiovisual->id }}-{{ md5((string) $audiovisual->link) }}"
                    width="100%"
                    height="315"
                    src="{!! $audiovisual->link !!}"
                    frameborder="0"
                    allowfullscreen
                    title="{{ __('audiovisual_player') }}"
                ></iframe>
            @endif
        @endif

        @can('update', $audiovisual->project())
            <div class="mt-3">
                <livewire:inline-editor
                    :model="$audiovisual"
                    field="link"
                    rules="required|string"
                    :label="__('link')"
                    :key="'av-link-'.$audiovisual->id" />
            </div>
        @endcan
    @endif
</div>
