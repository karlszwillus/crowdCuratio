{{--
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

Q4-Etappe 6 · G7-1 (2026-09-10): PDF-Layout, schlanke sw/w-
Ausgabe mit Charakter-Akzent. Ersetzt das ~1.100 LoC starke
preview/pdf.blade.php.

Bewusste Reduktion (Roadmap G7):
  - Keine Kopfleiste, keine mehrspaltige Fußzeile, keine
    Navigation, keine Marginalspalte, keine Lightbox, kein JS.
  - Text-Blöcke als reiner Fließtext ohne Rahmen.
  - Audio/Video als Hinweiszeile, kein Player-Ersatz.
  - Bilder in einem einfachen Grid, ohne Anzahl-Regel oder
    Sequenz-Modus — der Leser blättert am Papier.
  - Kapitel-Trennung per `page-break-before: always`.

Der Charakter (dokumentation/archiv/erzaehlung) steuert nur die
Akzent-Farbe (Kapitelnummer, Zitat-Kante, Chip-Rahmen). Fließtext
bleibt Ink-Schwarz.
--}}
@php
    /** @var \App\Models\Project $project */
    // Charakter → Akzent-Farbe (Handoff-Palette). Fallback dokumentation.
    $accent = match ($project->characterName()) {
        \App\Models\Project::CHARACTER_ARCHIV => '#2f4a63',
        \App\Models\Project::CHARACTER_ERZAEHLUNG => '#e0b04a',
        default => '#a8392f',
    };
    if (! empty($project->accent_color)) {
        $accent = $project->accent_color;
    }
    $chapters = $project->chapters ?? collect();
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $project->name ?? 'crowdCuratio' }}</title>
    <style>
        @page { margin: 20mm 18mm 22mm 18mm; }

        html { font-family: 'DejaVu Serif', 'Times', serif; font-size: 10.5pt; color: #23201c; }
        body { margin: 0; padding: 0; line-height: 1.55; }

        h1, h2, h3, h4 { font-family: 'DejaVu Serif', 'Times', serif; margin: 0 0 6pt; color: #23201c; }
        h1 { font-size: 22pt; font-weight: bold; line-height: 1.15; }
        h2 { font-size: 16pt; font-weight: bold; line-height: 1.2; }
        h3 { font-size: 13pt; font-weight: bold; line-height: 1.3; }
        h4 { font-size: 8.5pt; font-family: 'DejaVu Sans', 'Helvetica', sans-serif;
             text-transform: uppercase; letter-spacing: 1pt; color: #736c62; }
        p  { margin: 0 0 8pt; }

        .cc-mono   { font-family: 'DejaVu Sans Mono', 'Courier', monospace; font-size: 8pt;
                     letter-spacing: 0.5pt; color: #736c62; }
        .cc-caps   { font-family: 'DejaVu Sans', 'Helvetica', sans-serif; font-size: 8pt;
                     text-transform: uppercase; letter-spacing: 1pt; color: #736c62; }
        .cc-accent { color: {{ $accent }}; }

        /* Titelseite */
        .cc-cover { text-align: left; padding-top: 18mm; }
        .cc-cover__eyebrow { color: {{ $accent }}; font-family: 'DejaVu Sans', 'Helvetica', sans-serif;
                             font-size: 9pt; text-transform: uppercase; letter-spacing: 1.5pt; }
        .cc-cover__title { font-size: 28pt; font-weight: bold; margin: 8mm 0 4mm; line-height: 1.1; }
        .cc-cover__subtitle { font-size: 13pt; color: #55504a; margin: 0 0 8mm; }
        .cc-cover__meta { font-family: 'DejaVu Sans Mono', 'Courier', monospace; font-size: 9pt;
                          color: #736c62; }

        /* Kapitel-Opener und -Trennung */
        .cc-chapter { page-break-before: always; }
        .cc-chapter:first-of-type { page-break-before: auto; }
        .cc-chapter__eyebrow { color: {{ $accent }}; font-family: 'DejaVu Sans', 'Helvetica', sans-serif;
                               font-size: 9pt; text-transform: uppercase; letter-spacing: 1.5pt;
                               margin-bottom: 4mm; }
        .cc-chapter__title { font-size: 22pt; font-weight: bold; margin: 0 0 4mm; }
        .cc-chapter__lead { color: #55504a; font-size: 12pt; margin: 0 0 8mm; }
        .cc-chapter__rule { border-top: 1pt solid {{ $accent }}; margin: 0 0 6mm; width: 40mm; }

        /* Abschnitt-Header (Entry) */
        .cc-entry { margin-bottom: 8mm; }
        .cc-entry__title { font-size: 15pt; font-weight: bold; margin: 4mm 0 2mm; }
        .cc-entry__subtitle { color: #55504a; margin: 0 0 3mm; }
        .cc-entry__meta { font-family: 'DejaVu Sans Mono', 'Courier', monospace; font-size: 8pt;
                          color: #736c62; margin: 0 0 4mm; }

        /* Content-Blöcke */
        .cc-text { margin: 0 0 6mm; }
        .cc-text__credit { font-family: 'DejaVu Sans Mono', 'Courier', monospace; font-size: 8pt;
                           color: #736c62; margin-top: 2mm; }

        /* Quellenblock */
        .cc-sources { margin-top: 8mm; padding-top: 4mm; border-top: 0.5pt solid #ddd6c9; }
        .cc-sources__heading { color: #736c62; font-family: 'DejaVu Sans', 'Helvetica', sans-serif;
                               font-size: 8pt; text-transform: uppercase; letter-spacing: 1pt;
                               margin-bottom: 3mm; }
        .cc-sources__item { margin-bottom: 2mm; font-size: 9pt; line-height: 1.4; }
        .cc-sources__name { font-weight: bold; }
        .cc-sources__title { font-style: italic; color: #55504a; }
    </style>
</head>
<body>

{{-- Titelseite --}}
<section class="cc-cover">
    <div class="cc-cover__eyebrow">{{ __('reader_footer_cite_label') }}</div>
    <h1 class="cc-cover__title">{{ $project->name ?? 'crowdCuratio' }}</h1>
    @if(! empty(strip_tags((string) $project->description)))
        <p class="cc-cover__subtitle">{{ Str::limit(strip_tags((string) $project->description), 220) }}</p>
    @endif
    <p class="cc-cover__meta">
        {{ __('reader_footer_cite_publisher') }}
        · {{ now()->format('d.m.Y') }}
    </p>
</section>

{{-- Kapitel --}}
@foreach($chapters as $chapterKey => $chapter)
    <section class="cc-chapter">
        <div class="cc-chapter__eyebrow">
            {{ __('reader_chapter_card_number', ['n' => str_pad((string) ($chapterKey + 1), 2, '0', STR_PAD_LEFT)]) }}
        </div>
        <div class="cc-chapter__rule"></div>
        <h2 class="cc-chapter__title">{{ $chapter->name }}</h2>
        @if(! empty(strip_tags((string) $chapter->description)))
            <p class="cc-chapter__lead">{{ Str::limit(strip_tags((string) $chapter->description), 320) }}</p>
        @endif

        @foreach($chapter->entries ?? [] as $entry)
            <article class="cc-entry">
                @if(! empty(trim((string) $entry->name)))
                    <h3 class="cc-entry__title">{{ $entry->name }}</h3>
                @endif
                @if(! empty(trim((string) $entry->subtitle)))
                    <p class="cc-entry__subtitle">{{ $entry->subtitle }}</p>
                @endif
                @if(! empty(strip_tags((string) $entry->description)))
                    <p>{!! nl2br(e(strip_tags((string) $entry->description))) !!}</p>
                @endif

                @foreach($entry->mediaContent ?? [] as $media)
                    @include('preview.pdf.content.dispatcher', ['media' => $media])
                @endforeach

                @include('preview.pdf.entry-sources', ['entry' => $entry])
            </article>
        @endforeach
    </section>
@endforeach

</body>
</html>
