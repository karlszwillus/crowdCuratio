{{--
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

Q4-Etappe 5 / G4 (2026-09-08): Gemeinsames Layout-Chrome für den
Web-Reader — nutzt sowohl der One-Pager (preview/index.blade.php)
als auch die Multi-Page-Ansicht (preview/chapter.blade.php).

Head-Ballast (GSAP/Slick/FA) bleibt vorerst drin — Abbau ist G5.
Body-Slot heißt `body-classes` (optionale Zusatz-Klassen am
<body>) und `content` (das eigentliche Reader-Rendering).

Erwartet: $project (Project), $parameters (array) im Kontext.
--}}
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $project->name ?? 'CrowdCuratio' }}@hasSection('title-suffix') · @yield('title-suffix')@endif</title>

    {{-- Q4-Etappe 5 / G6 (2026-09-09): Reader-CSS als statisches
         Asset unter public/css/reader.css. Bewusst KEIN Vite-Bundle
         (Manifest-Timing-Probleme), Design-Tokens inline nach dem
         Handoff v4. Wenn wir später mit Tokens synchronisieren
         wollen, wandert das in einen Vite-Pfad zurück. --}}
    <link media="all" rel="stylesheet" type="text/css" href="{{ asset('css/reader.css') }}"/>

    {{-- Source Serif 4 aus Bunny Fonts — DSGVO-freundlicher als
         Google Fonts, keine npm-Dependency. IBM Plex kommt bei
         same-origin aus dem Editor-Bundle; für Cross-Origin
         Fallback lädt Bunny sie zusätzlich. --}}
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=source-serif-4:400,400i,600,600i|ibm-plex-sans:400,500,600,700|ibm-plex-mono:400,500&display=swap" rel="stylesheet">

    {{-- Q4-Etappe 5 / G5 (2026-09-08): Slick, Font-Awesome und
         GSAP-CDN-Includes für den Web-Reader entfernt.
         - Slick war im Web-Reader nur CSS-Ballast (kein JS-Init;
           nur im PDF wird tatsächlich .gallery.slick(...) aufgerufen —
           bleibt dort bis G7).
         - Font-Awesome-Icon fa-language wird durch einen schlichten
           Text-Button ersetzt.
         - GSAP war im aktuellen Layout nicht aktiv benutzt. --}}

    {{-- Q4-Etappe 5 / G6 (2026-09-09): Projekt-Farbe als CSS-Var
         Override. Statt punktuell `.accent { background-color: … }`
         zu setzen (was nur Legacy-Selektoren trifft), überschreiben
         wir die zentralen Design-Tokens — reader.css liest daraus
         Header-Band, Sidebar-Aktiv, Blockquote-Border etc. --}}
    @if(isset($parameters['colorAccent']) || isset($parameters['colorChapter']))
        <style type="text/css">
            :root {
                @if(isset($parameters['colorAccent']))
                    --color-primary: {{ $parameters['colorAccent'] }};
                    --color-brand-bar: {{ $parameters['colorAccent'] }};
                    /* Pastell-Band aus dem Akzent abgeleitet — die
                       Header-Fläche zieht damit mit dem Projekt-Rot
                       (oder Gelb bei Aktives Museum) mit. */
                    --color-brand-band: color-mix(in srgb, {{ $parameters['colorAccent'] }} 15%, white);
                @endif
                @if(isset($parameters['colorChapter']))
                    --color-chapter-accent: {{ $parameters['colorChapter'] }};
                @endif
            }
            @if(isset($parameters['colorChapter']))
                h4 { color: {{ $parameters['colorChapter'] }}; }
            @endif
        </style>
    @endif

    @stack('preview-head')
</head>

<body class="@yield('body-classes')">
{{-- Handoff v4 A11y: Skip-Link zum Inhalt, sichtbar nur bei Fokus. --}}
<a class="cc-skip-link" href="#cc-reader-main">{{ __('reader_skip_to_content') }}</a>
<div>
    <div class="top-container accent">
        <div id="hinweis">
            <div class="top-inner">
                <p id="hinweistext">erstellt mit dem OpenSource Projekt </p>
                <img src="{{ asset('image/crowdCuratio.png') }}" id="logo2" width="1174" height="402" alt="crowdCuratio"/>
            </div>
        </div>
    </div>

    <header class="headerleiste accent remove-color" id="myHeader">
        <div class="header-inner mb-4">
            <div>
                <a href="{{ route('preview', ['project' => $project->id]) }}">
                    <img class="logo" src="@if(isset($project->logo)){{ route('image', $project->logo) }}@endif" alt="">
                </a>
            </div>
            <p id="untertitel">@rich($project->description )</p>
            <p id="titel">
                <a href="{{ route('preview', ['project' => $project->id]) }}">
                    @isset($project->name){{ $project->name }}@endisset
                </a>
            </p>
        </div>

        <div id="burgermenu" onClick="toggle('sprachebtn')">
            <span id="burgerbutton" aria-label="{{ __('lang_switch_label') ?? 'Language' }}">
                {{-- G5: Font-Awesome-Icon ersetzt durch Text-Kürzel. --}}
                <abbr title="{{ __('lang_switch_label') ?? 'Language' }}">A/文</abbr>
            </span>
        </div>
        <ul id="" class="accent">
            @if(! in_array(Route::currentRouteName(), ['translate']))
                @foreach (Config::get('languages') as $lang => $language)
                    <a href="{{ route('lang.switch', $lang) }}">
                        <button class="sprache" id="sprachede">{{ $language }}</button>
                    </a>
                @endforeach
            @endif
        </ul>

        {{-- View-spezifische Navigation: One-Pager rendert die
             Chapter-Anchor-Chips hier, Multi-Page lässt den Slot
             leer und stellt die Sidebar-Navigation in @yield('content'). --}}
        @yield('header-nav')
    </header>

    @yield('intro')
</div>

<main id="cc-reader-main">
    @yield('content')
</main>

@if(isset($parameters['pdf']))
    <div class="footer-background p-3 my-3 border">
        <a href="@isset($parameters){{ route('download', $parameters) }}@endisset"
           class="btn m-4" data-toggle="modal" data-target="#previewModal" target="_blank">
            {{ __('pdf') }} <x-icon name="file-earmark-pdf-fill"/>
        </a>
    </div>
@endif

<footer>
    <div class="footerinner">
        @php
            $footerImprint = \App\Support\ProjectLegalText::imprintFor($project);
        @endphp
        @if(! empty(strip_tags((string) $footerImprint)))
            <div id="footeradresse">@rich($footerImprint )</div>
        @endif
        <ul id="verlinkungslistefooter">
            <li class="footerverlinkung">
                <a class="verlinkung" href="{{ route('preview.metadata', ['type' => 'copyright', 'parameters' => $parameters]) }}">{{ __('copyright') }}</a>
            </li>
            <li class="footerverlinkung">
                <a class="verlinkung" href="{{ route('preview.metadata', ['type' => 'policy', 'parameters' => $parameters]) }}">{{ __('policy') }}</a>
            </li>
        </ul>
    </div>
</footer>

@stack('preview-body-end')
</body>
</html>
