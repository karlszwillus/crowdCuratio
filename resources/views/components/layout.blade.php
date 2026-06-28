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

{{-- App-Shell-Gerüst.
 - dunkles Chrome (Header + Sidebar-Hintergrund), heller Content-Canvas
 - semantische Landmarks: <header>, <aside>, <main>, <footer>
 - Tailwind-Grid statt Bootstrap-3-Cols
 - Slot-API spiegelt die alten @yield-Namen (`log`, `main`, `sidebar`,
   `content`, `footer`), damit `projects/layout.blade.php` die Slots
   aus dem Outer-Section-Stack füllen kann und die bestehenden
   `@extends('projects.layout')`-Views in Welle 5b.1 unverändert
   weiterlaufen. Saubere Komponenten-Umstellung der Views kommt ab
   5b.3.
 - Wenn `$content` (Full-Width-Sektion) gesetzt ist, entfällt
   die Sidebar-Spalte rechts.
--}}

@props([
    'log' => null,
    'main' => null,
    'sidebar' => null,
    'content' => null,
    'footer' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>crowdCuratio</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
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

    {{-- Skip-Link als erster Tab-Stop (WCAG 2.4.1). Im Default per
         transform: translateY(-150%) versteckt, bei :focus springt
         er nach unten ins Viewport. Style in resources/css/app.css
         (.skip-link). Ziel ist der <main id="main-content">-Anker
         weiter unten. --}}
    <a href="#main-content" class="skip-link">
        {{ __('skip_to_main') }}
    </a>

    @include('layouts.navi-header')

    <div class="mx-auto w-full max-w-screen-2xl px-4">
        @if($content !== null && trim($content) !== '')
            {{-- Full-Width-Sektion (Settings, Index, Auth-Register, Translate). --}}
            <main role="main" id="main-content" class="py-4">
                {{ $content }}
            </main>
        @else
            {{-- Editor-Layout: drei Spalten — History links, Editor mitte, Tools rechts. --}}
            <div class="grid grid-cols-12 gap-4 py-4">
                <aside aria-label="{{ __('project_structure') }}" class="col-span-12 md:col-span-2">
                    {{ $log }}
                </aside>
                <main role="main" id="main-content" class="col-span-12 md:col-span-7">
                    {{ $main }}
                </main>
                <aside aria-label="{{ __('tools') }}" class="col-span-12 md:col-span-3">
                    {{ $sidebar }}
                </aside>
            </div>
        @endif

        @if($footer !== null && trim($footer) !== '')
            <footer class="border-t border-ink-400 py-4">
                {{ $footer }}
            </footer>
        @endif
    </div>

    {{-- Live-Region für ARIA-Announcements (WCAG 4.1.3). Wird heute
         vom Tastatur-Reorder (resources/js/keyboard-reorder.js) und
         später von weiteren Async-Aktionen befüllt. Globale Funktion
         window.ccAnnounce(message) schreibt den Text rein, Screen-
         Reader liest ihn höflich vor. --}}
    <div
        id="cc-live-announcer"
        role="status"
        aria-live="polite"
        aria-atomic="true"
        class="sr-only"
    ></div>

    @livewireScripts

    {{-- View-spezifische Scripts. View-Files publishen via
         @push('scripts') … @endpush. Ersetzt das alte
         @yield('script')-Pattern aus dem Bootstrap-3-Layout. --}}
    @stack('scripts')
</body>
</html>
