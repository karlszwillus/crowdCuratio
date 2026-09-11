{{--
crowdCuratio - Curating together virtually
Copyright (C)2026 - berlinHistory e.V.

I1.4 (2026-08-21): Editor-Chrome als eigene Blade-Komponente.
Zieht die Sticky-Bar aus chapters/index, projects/permissions und
(schlichter) projects/create zu einem gemeinsamen Bauteil zusammen.

Q4-Etappe 7 · E7-4 (2026-09-11): Brotkrumenpfad raus (ueberlappte die
Reiterleiste bei langen Titeln, sagte nichts, was nicht links in der
Struktur-Rail steht). Layout jetzt: Reiterleiste links, Aktions-Zone
rechts, `flex-nowrap` haelt beides in einer Zeile — bei Viewport-Enge
scrollt der Container horizontal statt umzubrechen.

Props:
- `project`  — das Projekt (Modell, fuer Tabs)
- `active`   — welcher Tab aktiv ist ("edit" | "meta" | "permissions")
- `sticky`   — bool, default true (Sticky-Positionierung)

Slot `actions` — optional; Editor-Sicht haengt Publish + ⋮ hier ein,
Metadaten die Autosave-State-Buttons.
--}}

@props([
    'project',
    'active',
    'sticky' => true,
])

<div data-testid="editor-chrome"
     @class([
    'sticky top-0 z-20' => $sticky,
    '-mx-6 -mt-6 mb-6 flex flex-nowrap items-center justify-between gap-4',
    // Karl 2026-09-11: overflow-x-auto clippt in vielen Browsern
    // auch vertikal — das Popover des ⋮-Menues wurde dadurch unten
    // abgeschnitten. Kein overflow mehr; die inneren Flex-Kinder
    // haben min-w-0/flex-none und regeln Enge selbst.
    'border-b border-line-200 bg-canvas-bg/95 px-6 py-3',
    'backdrop-blur supports-[backdrop-filter]:bg-canvas-bg/80',
])>
    <div class="flex min-w-0 flex-1 items-center">
        @can('update', $project)
            <x-projects.tabs :project="$project" :active="$active"/>
        @endcan
    </div>

    <div class="flex flex-none items-center gap-3">
        {{ $actions ?? '' }}
    </div>
</div>
