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

{{-- Block-Karte (Handoff v4 § Screen 02, Phase 5-D.6).

Konsistenter Rahmen für Content-Blöcke im Editor: Text, Bild,
Galerie, Audio, Video. Kopf mit Typ-Tag (Icon + Label), rechts
optional Aktions-Icon-Zeile, dann der Content-Slot.

Nutzung:
```
<x-ui.block-card type="text" :editing="false">
    <x-slot:actions>
        <button>...</button>
    </x-slot:actions>

    <p>Content</p>
</x-ui.block-card>
```

Props:
- `type`    (string, Pflicht) — 'text' | 'image' | 'gallery' | 'audio' |
              'video' | 'quote' | 'document' | 'map'. Bestimmt das
              Type-Tag-Icon und -Label.
- `editing` (bool, Default false) — wenn true: 2-px-brand-bar-Rahmen
              und Typ-Tag-Suffix „· wird bearbeitet".
- `label`   (string, optional) — überschreibt den Default-Label des
              Typs (z. B. für Custom-Block-Typen).

Slots:
- `actions` — Icon-Aktionen oben rechts. Erwartet einzelne
              `<button>`- oder `<a>`-Elemente; die Komponente
              spendiert Flex-Container und Gap.
- default   — der eigentliche Block-Inhalt.
--}}

@props([
    'type',
    'editing' => false,
    'label' => null,
    'actions' => null,
])

@php
    // Typ → Icon + Default-Label.
    $typeMeta = [
        'text'     => ['icon' => 'type',            'label' => __('block_type_text')],
        'image'    => ['icon' => 'image',           'label' => __('block_type_image')],
        'gallery'  => ['icon' => 'layout-grid',     'label' => __('block_type_gallery')],
        'audio'    => ['icon' => 'audio-lines',     'label' => __('block_type_audio')],
        'video'    => ['icon' => 'play',            'label' => __('block_type_video')],
        'quote'    => ['icon' => 'quote',           'label' => __('block_type_quote')],
        'document' => ['icon' => 'file-text',       'label' => __('block_type_document')],
        'map'      => ['icon' => 'map-pin',         'label' => __('block_type_map')],
    ];

    $meta = $typeMeta[$type] ?? ['icon' => 'file-text', 'label' => $type];
    $displayLabel = $label ?? $meta['label'];

    $cardClasses = collect([
        'group relative rounded-lg bg-paper-0 p-4 shadow-subtle',
        $editing
            ? 'border-2 border-brand-bar'
            : 'border border-line-200',
    ])->implode(' ');

    $tagClasses = $editing
        ? 'bg-tint-bg text-tint-text'
        : 'bg-paper-50 text-ink-500';
@endphp

<article {{ $attributes->merge(['class' => $cardClasses]) }}>
    {{-- Kopf: Typ-Tag links, optionale Aktionen rechts. --}}
    <header class="mb-3 flex items-start justify-between gap-3">
        <span class="{{ $tagClasses }} inline-flex items-center gap-1.5 rounded-full px-2.5 py-1
                      text-mono-caps font-mono uppercase tracking-widest">
            <x-icon :name="$meta['icon']" size="4"/>
            <span>{{ $displayLabel }}</span>
            @if ($editing)
                <span class="ml-1 normal-case tracking-normal">· {{ __('is_editing') }}</span>
            @endif
        </span>

        @if ($actions !== null && trim((string) $actions) !== '')
            <div class="flex items-center gap-2 text-ink-500">
                {{ $actions }}
            </div>
        @endif
    </header>

    {{-- Content-Slot: der Editor rendert seinen Inhalt direkt hier. --}}
    <div>
        {{ $slot }}
    </div>
</article>
