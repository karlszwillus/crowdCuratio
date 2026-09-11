{{--
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

Q4-Etappe 5 / G4 (2026-09-08): Multi-Page-Reader — genau ein
Kapitel mit Sidebar-Kapitel-Navigation links. Erbt Head/Header/
Footer aus preview/layout.blade.php und rendert im Content-Slot
das Grid (Sidebar + Chapter-Content).

Erwartet zusätzlich zum Layout: $chapter (Chapter) im Kontext.
--}}
@extends('preview.layout')

@section('title-suffix', $chapter->title ?? $chapter->name)

@section('body-classes', 'cc-reader-multipage')

@section('header-nav')
    <a href="{{ route('preview', ['project' => $project->id] + request()->query()) }}">{{ __('reader_header_chapters') }}</a>
    <a href="#about">{{ __('reader_header_about') }}</a>
@endsection

@section('progress')
    {{-- Handoff E1b: 3-px-Fortschrittslinie unter der Kopfleiste.
         Multi-Page: statischer Balken (aktuelles Kapitel / Gesamt). --}}
    @php
        $chaptersSorted = $project->chapters->sortBy('position')->values();
        $total = max(1, $chaptersSorted->count());
        $currentPos = ($chaptersSorted->search(fn ($c) => $c->id === $chapter->id) ?: 0) + 1;
        $progressPercent = round(($currentPos / $total) * 100);
    @endphp
    <div class="cc-progress" role="progressbar"
         aria-valuenow="{{ $currentPos }}" aria-valuemin="1" aria-valuemax="{{ $total }}"
         aria-label="{{ __('reader_progress_label') }}">
        <div class="cc-progress__fill" style="width: {{ $progressPercent }}%;"></div>
    </div>
@endsection

{{-- Multi-Page-Styles kommen jetzt aus public/css/reader.css
     (G-Fund-2), keine inline-Blöcke mehr. --}}

@section('content')
    @php
        $chaptersOrdered = $project->chapters->sortBy('position')->values();
        $currentChapterIdx = $chaptersOrdered->search(fn ($c) => $c->id === $chapter->id);
        $nextChapter = $currentChapterIdx !== false ? $chaptersOrdered->get($currentChapterIdx + 1) : null;
        $entriesOrdered = isset($chapter->entries) ? collect($chapter->entries) : collect();
    @endphp

    <div class="cc-multipage">
        <nav class="cc-multipage__nav" aria-label="{{ __('reader_chapter_nav_label') }}">
            <h2>{{ __('reader_chapter_nav_label') }}</h2>
            <ol>
                @foreach($chaptersOrdered as $navChapter)
                    <li>
                        <a href="{{ route('preview.chapter', ['project' => $project->id, 'chapter' => $navChapter->id]) }}"
                           @class(['is-active' => $navChapter->id === $chapter->id])
                           @if($navChapter->id === $chapter->id) aria-current="page" @endif>
                            <span class="cc-multipage__nav-label">{{ $navChapter->name }}</span>
                        </a>
                    </li>
                @endforeach
            </ol>

            {{-- Karl 2026-09-11 (E7-6/Etappe-6-Rest): Rail-Fußlinks
                 im Multi-Page-Reader — „Alle Abbildungen" listet die
                 Bilder projektweit, „Kapitel als PDF" rendert genau
                 das aktuell offene Kapitel als PDF. --}}
            <ul class="cc-multipage__nav-foot">
                <li>
                    <a href="{{ route('preview.all_images', ['project' => $project->id]) }}">
                        {{ __('reader_all_images_link') }}
                    </a>
                </li>
                <li>
                    <a href="{{ route('preview.chapter_pdf', ['project' => $project->id, 'chapter' => $chapter->id]) }}">
                        {{ __('reader_chapter_pdf_link') }}
                    </a>
                </li>
            </ul>
        </nav>

        <div class="cc-multipage__content">
            <section class="section">
                {{-- Handoff E1b: Kapitel-Opener. Der Index kommt aus
                     der Kapitel-Position im Projekt. --}}
                @include('preview.chapter-opener', ['chapter' => $chapter, 'chapterIndex' => $currentChapterIdx ?: 0])

                @if($entriesOrdered->isNotEmpty())
                    @foreach($entriesOrdered as $key => $entry)
                        <div id="entry-{{ $entry->id }}" class="@if($key == 0) hintergrundweiss @elseif($key % 2 == 0) hintergrundweiss @else {{ $parameters['backgroundSecond'] }} @endif">
                            <div class="container">
                                <div class="zweispaltig">
                                    @isset($entry->name)<h2>{{ $entry->name }}</h2>@endisset
                                    @isset($entry->subtitle)<p class="subtitle">{{ $entry->subtitle }}</p>@endisset
                                </div>
                                @include('preview.entry-credits', ['entry' => $entry])
                                @isset($entry->description)
                                    <div class="zweispaltig"><p>@rich($entry->description )</p></div>
                                @endisset

                                @if(isset($entry->mediaContent))
                                    @foreach($entry->mediaContent as $media)
                                        @include('preview.content.dispatcher', ['media' => $media])
                                    @endforeach
                                @endif
                                @include('preview.entry-sources', ['entry' => $entry])
                            </div>
                        </div>
                    @endforeach
                @endif

                {{-- P13 · Weitergang am Kapitel-Ende. Beitragen-Pille + Link zum
                     nächsten Kapitel (falls vorhanden) — verhindert die
                     „fünf Sackgassen"-Wirkung aus dem Review. --}}
                <div class="container cc-chapter-outro">
                    <div class="cc-chapter-outro__inner">
                        <a href="#mitmachen" class="cc-chapter-outro__contribute">
                            {{ __('reader_contribute_cta') }}
                        </a>
                        @if($nextChapter)
                            @php $nextIdx = $currentChapterIdx + 1; @endphp
                            <a href="{{ route('preview.chapter', ['project' => $project->id, 'chapter' => $nextChapter->id]) }}"
                               class="cc-chapter-next">
                                <span class="cc-chapter-next__label">{{ __('reader_next_chapter_label') }}</span>
                                <span class="cc-chapter-next__title">
                                    <span class="cc-chapter-next__number">{{ str_pad((string) ($nextIdx + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                    {{ $nextChapter->name }}
                                </span>
                            </a>
                        @endif
                    </div>
                </div>

                {{-- „Erarbeitet von" am tatsächlichen Projekt-Ende — nur
                     im letzten Kapitel, damit der Editor:innen-Nachweis
                     dort sitzt, wo die Leser:innen aussteigen (Review 3 ·
                     offener Punkt aus Durchgang 1). Der Partial rendert
                     sich selbst weg, wenn keine Credits gepflegt sind. --}}
                @if(! $nextChapter)
                    @include('preview.project-credits', ['project' => $project])
                @endif
            </section>
        </div>

        {{-- P11 · Marginalspalte. Handoff-Regel 3: „redaktionelle Leistung
             ist Inhalt". Iteration 1: Sticky-Kapitel-TOC (Abschnitte des
             aktiven Kapitels als Sprungmarken) mit Fortschritts-Marker.
             Quellen-Marginalien pro Textblock folgen mit dem erweiterten
             Quellenmodell (Backlog). --}}
        <aside class="cc-multipage__aside" aria-label="{{ __('reader_aside_label') }}">
            @if($entriesOrdered->count() > 1)
                <nav class="cc-entry-toc" aria-label="{{ __('reader_entry_toc_label') }}">
                    <h4>{{ __('reader_entry_toc_label') }}</h4>
                    <ol>
                        @foreach($entriesOrdered as $tocEntry)
                            <li>
                                <a href="#entry-{{ $tocEntry->id }}"
                                   data-entry-anchor="entry-{{ $tocEntry->id }}">
                                    {{ $tocEntry->name }}
                                </a>
                            </li>
                        @endforeach
                    </ol>
                </nav>
            @endif
        </aside>
    </div>

    {{-- Scroll-Spy für den Entry-TOC: markiert den Anker des Abschnitts,
         der gerade am oberen Viewport-Drittel steht. Kein Framework —
         IntersectionObserver reicht. --}}
    <script>
        (function () {
            const anchors = Array.from(document.querySelectorAll('[data-entry-anchor]'));
            if (! anchors.length) return;
            const byId = new Map(anchors.map(a => [a.dataset.entryAnchor, a]));
            const targets = anchors
                .map(a => document.getElementById(a.dataset.entryAnchor))
                .filter(Boolean);
            const io = new IntersectionObserver((entries) => {
                entries.forEach(e => {
                    if (e.isIntersecting) {
                        anchors.forEach(a => a.classList.remove('is-active'));
                        const active = byId.get(e.target.id);
                        if (active) active.classList.add('is-active');
                    }
                });
            }, { rootMargin: '-30% 0px -60% 0px', threshold: 0 });
            targets.forEach(t => io.observe(t));
        })();
    </script>
@endsection
