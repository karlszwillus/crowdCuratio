{{--
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

Q4-Etappe 5 / G-Fund-3+4 (2026-09-09): Reader-Layout nach dem
Export-Handoff v4. Charakter am <html> per data-char="…"
umgeschaltet, optional Akzent-Farbe als CSS-Var-Override.
Kopfleiste 62 px (Akzentkachel + Projektname + Nav + DE/EN +
„Etwas beitragen"), Fußzeile auf --paper-3 mit drei Spalten
und Zitierhinweis.

Erwartet: $project (Project), $parameters (array) im Kontext.
--}}
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      data-char="{{ $project->characterName() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $project->name ?? 'crowdCuratio' }}@hasSection('title-suffix') · @yield('title-suffix')@endif</title>

    <link media="all" rel="stylesheet" type="text/css" href="{{ asset('css/reader.css') }}"/>

    {{-- Source Serif 4 + IBM Plex Sans + IBM Plex Mono aus Bunny
         Fonts (DSGVO-freundlich, kein npm-Package nötig). --}}
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=source-serif-4:400,400i,600,600i|ibm-plex-sans:400,500,600,700|ibm-plex-mono:400,500&display=swap" rel="stylesheet">

    {{-- Q4-Etappe 5 / G-Fund-1 (2026-09-09): Optionaler Akzent-
         Farbe-Override. Der Charakter setzt die Grund-Palette;
         wenn der Redakteur eine eigene Akzent-Farbe gesetzt hat,
         überschreibt sie nur die --accent-Var. --}}
    @if(! empty($project->accent_color))
        <style>
            :root {
                --accent: {{ $project->accent_color }};
                --accent-soft: color-mix(in srgb, {{ $project->accent_color }} 15%, var(--paper));
            }
        </style>
    @endif

    {{-- Q4-Etappe 6 · G6-4 (2026-09-10): Alpine.js für den Reader.
         Der Reader-Layout lädt bewusst kein Vite-Bundle (siehe
         reader.css-Kommentare), deshalb Alpine hier direkt aus
         public/js/. `defer` sorgt dafür, dass Alpine erst nach dem
         DOM-Parse startet — Reihenfolge zu `x-cloak` bleibt sauber. --}}
    <script defer src="{{ asset('js/alpine.min.js') }}"></script>

    @stack('preview-head')
</head>

<body class="@yield('body-classes')">
{{-- Handoff § A11y: Skip-Link zum Inhalt, sichtbar nur bei Fokus. --}}
<a class="cc-skip-link" href="#cc-reader-main">{{ __('reader_skip_to_content') }}</a>

{{-- Kopfleiste (Handoff E1a). --}}
<header class="cc-header" role="banner">
    <div class="cc-header__row">
        <a href="{{ route('preview', ['project' => $project->id] + request()->query()) }}" class="cc-header__project">
            <span class="cc-header__logo" aria-hidden="true">
                @if(! empty($project->logo))
                    <img src="{{ route('image', $project->logo) }}" alt="">
                @else
                    {{ mb_strtoupper(mb_substr($project->name ?? 'C', 0, 1)) }}
                @endif
            </span>
            <span>
                <span class="cc-header__title">{{ $project->name ?? 'crowdCuratio' }}</span>
                @if(! empty(strip_tags((string) $project->description)))
                    <span class="cc-header__subtitle">{{ Str::limit(strip_tags((string) $project->description), 60) }}</span>
                @endif
            </span>
        </a>

        <nav class="cc-header__nav" aria-label="{{ __('reader_header_nav_label') }}">
            @yield('header-nav')
        </nav>

        <div class="cc-header__nav">
            <span class="cc-header__lang" aria-label="{{ __('lang_switch_label') }}">
                @if(! in_array(Route::currentRouteName(), ['translate']))
                    @foreach(Config::get('languages') as $lang => $language)
                        <a href="{{ route('lang.switch', $lang) }}"
                           @class(['is-active' => app()->getLocale() === $lang]))>{{ mb_strtoupper($lang) }}</a>
                    @endforeach
                @endif
            </span>
            {{-- „Etwas beitragen" — Handoff-Blocker 5. Dauerhafter
                 Einstieg im Seitenkopf, auf jeder Seite gleich. Ziel-
                 URL noch offen (Kontakt-Form / Mitmachen-Anker) —
                 vorerst Anker #mitmachen, den wir mit E1a füllen. --}}
            <a href="#mitmachen" class="cc-header__contribute">{{ __('reader_contribute_cta') }}</a>
        </div>
    </div>
</header>

@hasSection('progress')
    @yield('progress')
@endif

@yield('intro')

<main id="cc-reader-main">
    @yield('content')
</main>

@if(isset($parameters['pdf']))
    <div class="footer-background p-3 my-3 border">
        <a href="@isset($parameters){{ route('download', $parameters) }}@endisset"
           class="btn m-4" target="_blank">
            {{ __('pdf') }}
        </a>
    </div>
@endif

{{-- Fußzeile (Handoff E1a). Vier Spalten + Zitierhinweis + Abschluss.
     Review 3 / P-Fußzeile: Sammelspalte „Weiteres" ergänzt
     (Bildnachweise, Barrierefreiheit, PDF); Adressspalte mit
     Zeilenabständen statt Absatzabständen (siehe reader.css). --}}
<footer class="cc-footer" role="contentinfo">
    <div class="cc-footer__grid">
        <div class="cc-footer__addr">
            <h4>{{ __('reader_footer_carrier') }}</h4>
            @php $footerImprint = \App\Support\ProjectLegalText::imprintFor($project); @endphp
            @if(! empty(strip_tags((string) $footerImprint)))
                <div>@rich($footerImprint)</div>
            @endif
        </div>
        <div>
            <h4>{{ __('reader_footer_exhibition') }}</h4>
            <ul>
                @if(isset($project->chapters))
                    @foreach($project->chapters as $ch)
                        <li><a href="#section{{ $loop->index }}">{{ $ch->name }}</a></li>
                    @endforeach
                @endif
            </ul>
        </div>
        <div>
            <h4>{{ __('reader_footer_legal') }}</h4>
            <ul>
                <li><a href="{{ route('preview.metadata', ['type' => 'copyright', 'parameters' => $parameters]) }}">{{ __('copyright') }}</a></li>
                <li><a href="{{ route('preview.metadata', ['type' => 'policy', 'parameters' => $parameters]) }}">{{ __('policy') }}</a></li>
            </ul>
        </div>
        <div>
            {{-- Review 3 · P-Fußzeile: Sammelspalte. Ziel-Routen für
                 Bildnachweise und Barrierefreiheitserklärung folgen
                 (Backlog); PDF-Größe wird bei On-Demand-Generierung
                 nicht mitgeliefert und bleibt vorerst unbeziffert. --}}
            <h4>{{ __('reader_footer_more') }}</h4>
            <ul>
                <li><a href="#bildnachweise">{{ __('reader_footer_credits_link') }}</a></li>
                <li><a href="#barrierefreiheit">{{ __('reader_footer_a11y_link') }}</a></li>
                @if(isset($parameters))
                    <li><a href="{{ route('download', $parameters) }}" target="_blank" rel="noopener">{{ __('reader_footer_pdf_link') }}</a></li>
                @endif
            </ul>
        </div>
    </div>

    <div class="cc-footer__cite">
        <strong>{{ __('reader_footer_cite_label') }}:</strong>
        {{ __('reader_footer_cite_publisher') }} (Hg.):
        <em>{{ $project->name ?? 'crowdCuratio' }}</em>.
        <code>{{ route('preview', ['project' => $project->id]) }}</code>
        · {{ __('reader_footer_cite_retrieved') }} {{ now()->format('d.m.Y') }}
    </div>

    <div class="cc-footer__closing">
        <span>{{ __('reader_footer_made_with') }} crowdCuratio</span>
        @if(isset($project->updated_at))
            <span>{{ __('reader_footer_last_change') }}: {{ $project->updated_at->format('d.m.Y') }}</span>
        @endif
    </div>
</footer>

@stack('preview-body-end')
</body>
</html>
