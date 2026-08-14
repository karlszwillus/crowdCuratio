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

{{-- Segmented Control (Handoff v4 Screen 02).

Container mit hellem Hintergrund und dünnem Rahmen; das aktive
Segment sitzt in einer weißen Kachel mit subtle-Shadow. Ersetzt
mehrfache freistehende Buttons für exklusive Modus-Wechsel
(z. B. „Bearbeiten · Übersetzen · Metadaten").

Nutzung:
```
<x-ui.segmented :items="[
    ['label' => __('edit'), 'href' => route('chapters.index', $project->id), 'active' => true],
    ['label' => __('translate'), 'href' => route('translate', $project->id)],
    ['label' => __('meta_data'), 'href' => route('project.metadata', $project->id)],
]" aria-label="{{ __('editor_mode') }}" />
```

Items-Signatur pro Eintrag:
- `label`  (string, Pflicht)
- `href`   (string, Pflicht — Link-Ziel)
- `active` (bool, optional — Default false)
- `icon`   (string, optional — Lucide-Name für Icon vor dem Label)
--}}

@props([
    'items' => [],
    'ariaLabel' => 'Modus',
])

<div
    role="tablist"
    aria-label="{{ $ariaLabel }}"
    {{ $attributes->merge(['class' => 'inline-flex rounded-md border border-line-200 bg-paper-50 p-0.5']) }}
>
    @foreach ($items as $item)
        @php
            $active = (bool) ($item['active'] ?? false);
            $tabClasses = collect([
                'inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-body transition-colors',
                'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-bar',
                $active ? 'bg-paper-0 text-ink-900 shadow-subtle font-medium' : 'text-ink-500 hover:text-ink-900',
            ])->implode(' ');
        @endphp

        <a
            href="{{ $item['href'] }}"
            role="tab"
            aria-selected="{{ $active ? 'true' : 'false' }}"
            @if ($active) aria-current="page" @endif
            class="{{ $tabClasses }}"
        >
            @if (! empty($item['icon']))
                <x-icon :name="$item['icon']" size="4"/>
            @endif
            <span>{{ $item['label'] }}</span>
        </a>
    @endforeach
</div>
