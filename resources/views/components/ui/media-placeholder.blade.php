{{--
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
--}}

{{-- Medien-Platzhalter (Handoff v4 § Screen 05a, P3.14 aus 5-D.6b).

Für leere Bild-/Audio-/Video-Blöcke: statt einer leeren weißen
Fläche zeigen wir ein Streifenmuster mit passendem Seitenverhältnis
plus Icon + Hint-Text („Bild einfügen …" / „Audio-Datei hochladen").

Nutzung:
  <x-ui.media-placeholder type="image" hint="{{ __('add_image_hint') }}" />
  <x-ui.media-placeholder type="video" aspect="16/9" />

Props:
- `type`   — 'image' | 'audio' | 'video' | 'gallery'. Bestimmt das
             Icon und das Default-Aspect-Ratio.
- `aspect` — Overrider für das Seitenverhältnis (CSS-Wert wie '16/9',
             '4/3', '1/1'). Default folgt dem Typ.
- `hint`   — Hint-Text unter dem Icon. Default folgt dem Typ.
--}}

@props([
    'type' => 'image',
    'aspect' => null,
    'hint' => null,
])

@php
    $typeMeta = [
        'image'   => ['icon' => 'image',       'aspect' => '4/3', 'hint' => __('placeholder_image_hint')],
        'gallery' => ['icon' => 'layout-grid', 'aspect' => '4/3', 'hint' => __('placeholder_gallery_hint')],
        'audio'   => ['icon' => 'audio-lines', 'aspect' => '4/1', 'hint' => __('placeholder_audio_hint')],
        'video'   => ['icon' => 'play',        'aspect' => '16/9', 'hint' => __('placeholder_video_hint')],
    ];

    $meta = $typeMeta[$type] ?? $typeMeta['image'];
    $displayAspect = $aspect ?? $meta['aspect'];
    $displayHint = $hint ?? $meta['hint'];
@endphp

<div
    {{ $attributes->merge(['class' => 'cc-media-placeholder relative flex items-center justify-center rounded-md border border-dashed border-line-200 text-ink-500']) }}
    style="aspect-ratio: {{ $displayAspect }};"
    role="img"
    aria-label="{{ $displayHint }}"
>
    <div class="relative z-10 flex flex-col items-center gap-2 px-6 text-center">
        <x-icon :name="$meta['icon']" size="6" class="text-ink-400"/>
        <p class="text-caption text-ink-500">{{ $displayHint }}</p>
    </div>
</div>
