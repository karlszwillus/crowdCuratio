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

{{-- App-Shell-Gerüst (Phase 5-D.3).

Konsistente Shell: Rail (60 px, dunkles Chrome) + Sidebar-Panel
(280 px, hell) + Canvas (rest). Das ersetzt die Top-Bar aus 5a/5b.

Slot-API:
- `$rail-active`    (Prop) — 'dashboard' | 'projects' | 'users' | 'comments' | 'settings',
                             markiert das aktive Rail-Icon.
- `$panel`          — Inhalt des Sidebar-Panels (Struktur-Baum,
                      Filter, Sekundärnavigation). Ohne Panel-Slot
                      entfällt die Panel-Spalte und die Rail steht
                      direkt neben dem Canvas (z. B. Full-Width-
                      Views wie Settings).
- `$panel-label`,
  `$panel-title`    — optionaler Panel-Kopf (Mono-Caps + Titel).
- `$panel-aria`     — ARIA-Label des Panels (Default: 'Panel').

Legacy-Slots aus 5a/5b (`log`, `main`, `sidebar`, `content`,
`footer`) werden weiter unterstützt, damit `projects/layout.blade.php`
und die `@extends`-Views nicht auf einen Schlag angefasst werden
müssen. `log` mappt auf `panel` (linke Sidebar-Position), `content`
und `main` sind gleichwertige Canvas-Slots.
--}}

@props([
    'railActive' => null,
    'panel' => null,
    'panelLabel' => null,
    'panelTitle' => null,
    'panelAria' => null,

    // Legacy-Slots (5a/5b-Kompat).
    'log' => null,
    'main' => null,
    'sidebar' => null,
    'content' => null,
    'footer' => null,
])

@php
    // Legacy-`log` auf `panel` mappen, wenn dieser nicht explizit
    // gesetzt ist — dann müssen wir die Bestandsviews nicht sofort
    // anfassen.
    $panelContent = $panel ?? $log;
    $panelHasContent = $panelContent !== null && trim((string) $panelContent) !== '';

    // Legacy-`content` (Full-Width) und `main` sind beide als Canvas
    // gültig. `content` ist historisch der Full-Width-Modus (Settings,
    // Auth-Register); `main` der Editor-Center.
    $canvas = null;
    if ($content !== null && trim((string) $content) !== '') {
        $canvas = $content;
    } elseif ($main !== null && trim((string) $main) !== '') {
        $canvas = $main;
    }
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>crowdCuratio</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Bootstrap-Icons-CDN entfaellt seit Phase 5-D.2: Icons kommen
         ueber die `<x-icon>`-Komponente aus dem Lucide-Set (blade-ui-kit
         + mallardduck/blade-lucide-icons). Font-Awesome bleibt fuer
         die Preview-/PDF-Templates (drei Nutzungen in preview/*.blade.php),
         wird spaeter im PDF-Refactor migriert. --}}
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdn.quilljs.com/1.1.6/quill.snow.css">
    <script src="https://cdn.quilljs.com/1.1.6/quill.js"></script>

    @livewireStyles

    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('css/favicon/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('css/favicon/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('css/favicon/favicon-16x16.png') }}">
    <link rel="manifest" href="{{ asset('css/favicon/site.webmanifest') }}">
    <meta name="msapplication-TileColor" content="#da532c">
    <meta name="theme-color" content="#ffffff">

    {{-- jQuery + CSRF-Setup für Legacy-AJAX-Pfade. Wandert mit den
         Editor-Komponenten in den nächsten Wellen schrittweise auf
         Livewire/Alpine; bis dahin bleibt es Pflicht. --}}
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/RubaXa/Sortable/Sortable.min.js"></script>
    <script type="text/javascript">
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>
</head>
<body class="bg-canvas-bg">

    {{-- Skip-Link als erster Tab-Stop (WCAG 2.4.1). --}}
    <a href="#main-content" class="skip-link">
        {{ __('skip_to_main') }}
    </a>

    <div class="flex min-h-screen">
        {{-- Rail: immer sichtbar, sticky an linkem Rand. --}}
        <x-layout.rail :active="$railActive" />

        {{-- Sidebar-Panel: kontextabhängig, entfällt bei Full-Width-
             Views. --}}
        @if ($panelHasContent)
            <x-layout.sidebar-panel
                :label="$panelLabel"
                :title="$panelTitle"
                :aria-label="$panelAria ?? __('project_structure')"
            >
                {{ $panelContent }}
            </x-layout.sidebar-panel>
        @endif

        {{-- Canvas: Content-Bereich rechts. Bei fehlendem Panel füllt
             er den ganzen Rest. `overflow-clip` statt `overflow-x-hidden`
             — Letzteres bricht `position: sticky` in Chrome/Firefox,
             weil es implizit auch `overflow-y: auto` erzeugt. --}}
        <div class="flex-1 min-w-0 overflow-x-clip">
            <div class="mx-auto w-full max-w-screen-2xl px-6 py-6">
                @if ($canvas !== null)
                    <main role="main" id="main-content">
                        {{ $canvas }}
                    </main>
                @endif

                @if ($sidebar !== null && trim((string) $sidebar) !== '')
                    {{-- Rechte Zweit-Aside für Bestandsviews mit
                         Tools-Panel (z. B. History-Drawer). Wird im
                         5-D.5-Editor-Chrome-Refactor sinnvollerweise
                         als Popover/Modal umgezogen. --}}
                    <aside aria-label="{{ __('tools') }}" class="mt-6">
                        {{ $sidebar }}
                    </aside>
                @endif

                @if ($footer !== null && trim((string) $footer) !== '')
                    <footer class="mt-8 border-t border-line-200 pt-4">
                        {{ $footer }}
                    </footer>
                @endif
            </div>
        </div>
    </div>

    {{-- Live-Region für ARIA-Announcements (WCAG 4.1.3). --}}
    <div
        id="cc-live-announcer"
        role="status"
        aria-live="polite"
        aria-atomic="true"
        class="sr-only"
    ></div>

    {{-- Toast-Region (Phase 5c.3). --}}
    <div
        x-data
        aria-live="assertive"
        aria-atomic="false"
        class="pointer-events-none fixed bottom-4 right-4 z-50 flex flex-col gap-2"
    >
        <template x-for="item in $store.toast.items" :key="item.id">
            <div
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                :class="{
                    'bg-primary text-primary-on': item.type === 'error',
                    'bg-ink-900 text-canvas-bg': item.type === 'success' || item.type === 'info',
                }"
                class="pointer-events-auto flex min-w-[16rem] max-w-md items-start gap-3 rounded-md px-4 py-3 shadow-lg"
                role="alert"
            >
                <span x-text="item.text" class="flex-1 text-body"></span>
                <button
                    type="button"
                    @click="$store.toast.dismiss(item.id)"
                    :aria-label="'{{ __('close') }}'"
                    class="shrink-0 rounded p-1 opacity-70 hover:opacity-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current"
                >
                    &times;
                </button>
            </div>
        </template>
    </div>

    @livewireScripts

    {{-- View-spezifische Scripts. --}}
    @stack('scripts')
</body>
</html>
