{{--
crowdCuratio - Curating together virtually
Copyright (C)2026 - berlinHistory e.V.

I1.4 (2026-08-21): Editor-Chrome als eigene Blade-Komponente.
Zieht die Sticky-Bar aus chapters/index, projects/permissions und
(schlichter) projects/create zu einem gemeinsamen Bauteil zusammen —
Breadcrumb links, Segmented Control mittig, optionale Aktions-Zone
rechts. Publish-Button und ⋮-Menü kommen als Slot rein, weil sie
Editor-spezifisch sind.

Props:
- `project`  — das Projekt (Modell, fuer Breadcrumb-Tree und Tabs)
- `active`   — welcher Tab aktiv ist ("edit" | "meta" | "permissions")
- `sticky`   — bool, default true (Sticky-Positionierung)

Slot `actions` — optional; Editor-Sicht haengt Publish + ⋮ hier ein.
Metadaten und Berechtigungen bleiben leer.
--}}

@props([
    'project',
    'active',
    'sticky' => true,
])

<div @class([
    'sticky top-0 z-20' => $sticky,
    '-mx-6 -mt-6 mb-6 flex flex-wrap items-center justify-between gap-4',
    'border-b border-line-200 bg-canvas-bg/95 px-6 py-3',
    'backdrop-blur supports-[backdrop-filter]:bg-canvas-bg/80',
])>
    <div class="min-w-0 flex-1">
        <x-ui.breadcrumb :tree="app(App\Services\ProjectTreeService::class)->breadcrumbTree($project)"/>
    </div>

    <div class="flex items-center gap-3">
        @can('update', $project)
            <x-projects.tabs :project="$project" :active="$active"/>
        @endcan

        {{ $actions ?? '' }}
    </div>
</div>
