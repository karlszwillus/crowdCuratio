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

{{-- Sidebar-Panel (280 px, `bg-paper-0`).

Neben der Rail (60 px) gerendert, füllt sich kontextabhängig:
- Editor: Struktur-Baum (Kapitel/Eintrag/Block-Hierarchie)
- Projektliste: Filter-/Nav-Menü
- Andere Routen: leer oder Route-spezifische Sekundärnavigation

Das Panel trägt den Kopf-Slot (Mono-Caps-Label + Titel) und den
Body-Slot (der eigentliche Inhalt). Wenn nur `$slot` gesetzt wird,
läuft der ganze Panel-Body durch — für Fälle ohne Standard-Kopf.

Handoff v4 Screen 02: „STRUKTUR"-Mono-Caps + Projektname 15 px/600,
padding 16px 18px 12px, rechte Kante 1px chrome-line.
--}}

@props([
    /** Kopf-Label (Mono-Caps, uppercase). */
    'label' => null,
    /** Kopf-Titel (Sub-Headline unterhalb des Labels). */
    'title' => null,
    /** ARIA-Label für die <aside> — Pflicht. */
    'ariaLabel' => 'Panel',
])

<aside
    aria-label="{{ $ariaLabel }}"
    class="sticky top-0 z-30 flex h-screen w-[280px] shrink-0 flex-col
           overflow-y-auto border-r border-line-200 bg-paper-0"
>
    @if ($label || $title)
        <header class="border-b border-line-100 px-[18px] pb-3 pt-4">
            @if ($label)
                <p class="text-mono-caps font-mono uppercase tracking-widest text-ink-500">
                    {{ $label }}
                </p>
            @endif
            @if ($title)
                <h2 class="mt-1 text-[15px] font-semibold text-ink-900">
                    {{ $title }}
                </h2>
            @endif
        </header>
    @endif

    <div class="flex-1 px-3 py-3">
        {{ $slot }}
    </div>
</aside>
