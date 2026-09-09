{{--
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

Q4-Etappe 5 / G4 (2026-09-08): One-Pager-Reader (Long-Scroll).
Erbt Head/Header/Footer aus preview/layout.blade.php und füllt
nur die view-spezifischen Slots: Chapter-Chips oben in der
Header-Nav, Long-Scroll-Sections im Content.
--}}
@extends('preview.layout')

@section('header-nav')
    {{-- Handoff E1a: Header-Slots „Kapitel" und „Über das Projekt".
         Kapitel klappt via Anker im One-Pager weiter, „Über" ist
         Platzhalter für die Info-Seite (E2a-Zulieferung). --}}
    <a href="#kapitel">{{ __('reader_header_chapters') }}</a>
    <a href="#about">{{ __('reader_header_about') }}</a>
@endsection

@section('intro')
    {{-- Handoff E1a: Leitbild 430 px vollbreit, Titelpanel in --paper
         überlappt die Unterkante (linker Rand 34 px, Breite 640 px).
         Ohne cover_image entfällt das Bildfeld — Titelpanel auf
         --paper-2, Höhe entfällt (Handoff-Regel 2: kein leerer
         Container). --}}
    <section class="cc-leitbild @if(empty($project->cover_image)) cc-leitbild--no-image @endif">
        @if(! empty($project->cover_image))
            <div class="cc-leitbild__image">
                <img src="{{ route('image', $project->cover_image) }}" alt="" width="1600" height="430">
            </div>
        @endif
        <div class="cc-leitbild__panel">
            <h1>{{ $project->name }}</h1>
            @if(! empty(trim(strip_tags((string) $project->description))))
                <p class="cc-lead">@rich($project->description )</p>
            @endif
        </div>
    </section>

    {{-- Handoff E1a: Einleitung im Lesemaß + rechte Umfangsspalte
         (vier Zahlen · zuletzt ergänzt). Nur wenn eine Beschreibung
         gepflegt ist oder mindestens ein Kapitel existiert. --}}
    @php
        $projSummary = [
            'chapters' => isset($project->chapters) ? $project->chapters->count() : 0,
            'entries' => 0,
            'images' => 0,
            'sources' => 0,
        ];
        $sourceIdsProject = [];
        $lastEdit = $project->updated_at;
        foreach ($project->chapters ?? [] as $ch) {
            foreach ($ch->entries ?? [] as $en) {
                $projSummary['entries']++;
                if ($en->updated_at && $en->updated_at->gt($lastEdit)) { $lastEdit = $en->updated_at; }
                foreach ($en->mediaContent ?? [] as $mc) {
                    if ($mc->content_type === 'App\\Models\\Gallery' && isset($mc->gallery)) {
                        $projSummary['images'] += ($mc->gallery->images ?? collect())->count();
                        foreach ($mc->gallery->images ?? [] as $img) {
                            foreach ([$img->copyrightImage ?? null, $img->originImage ?? null] as $s) { if ($s) $sourceIdsProject[] = $s->id; }
                        }
                    } elseif ($mc->content_type === 'App\\Models\\Text' && isset($mc->text)) {
                        foreach ([$mc->text->copyrightText, $mc->text->originText] as $s) { if ($s) $sourceIdsProject[] = $s->id; }
                    } elseif ($mc->content_type === 'App\\Models\\Audiovisual' && isset($mc->audiovisual)) {
                        foreach ([$mc->audiovisual->copyrightSource ?? null, $mc->audiovisual->originSource ?? null] as $s) { if ($s) $sourceIdsProject[] = $s->id; }
                    } elseif ($mc->content_type === 'App\\Models\\QuoteBlock' && isset($mc->quoteBlock)) {
                        if ($mc->quoteBlock->source) $sourceIdsProject[] = $mc->quoteBlock->source->id;
                    }
                }
            }
        }
        $projSummary['sources'] = count(array_unique($sourceIdsProject));
    @endphp

    @if($projSummary['chapters'] > 0)
        <section class="cc-start-summary">
            <div class="cc-start-summary__grid">
                <div class="cc-start-summary__text">
                    @if(! empty(trim(strip_tags((string) $project->description))))
                        <p class="cc-lead">@rich($project->description )</p>
                    @endif
                </div>
                <aside class="cc-start-summary__scope" aria-label="{{ __('reader_project_scope') }}">
                    <dl>
                        <div><dt>{{ trans_choice('reader_scope_chapters', $projSummary['chapters']) }}</dt><dd>{{ $projSummary['chapters'] }}</dd></div>
                        <div><dt>{{ trans_choice('reader_scope_entries', $projSummary['entries']) }}</dt><dd>{{ $projSummary['entries'] }}</dd></div>
                        <div><dt>{{ trans_choice('reader_scope_images', $projSummary['images']) }}</dt><dd>{{ $projSummary['images'] }}</dd></div>
                        <div><dt>{{ trans_choice('reader_scope_sources', $projSummary['sources']) }}</dt><dd>{{ $projSummary['sources'] }}</dd></div>
                    </dl>
                    @if($lastEdit)
                        <p class="cc-start-summary__updated">{{ __('reader_scope_last_edit') }} {{ $lastEdit->format('d.m.Y') }}</p>
                    @endif
                </aside>
            </div>
        </section>

        {{-- Handoff E1a: Kapitel-Karten 2×2. Bildband 180 px mit
             großer Kapitelnummer in Mono, darunter Titel, Lead,
             „n Abschnitte · n Abbildungen". Rahmen 1px, keine Radien.
             Ohne Kapitel-Cover-Image: Akzent-Fläche mit Nummer. --}}
        <section id="kapitel" class="cc-start-chapters" aria-label="{{ __('reader_chapter_cards_label') }}">
            @foreach($project->chapters as $keyProject => $chapter)
                @php
                    $chEntries = isset($chapter->entries) ? $chapter->entries->count() : 0;
                    $chImages = 0;
                    foreach ($chapter->entries ?? [] as $en) {
                        foreach ($en->mediaContent ?? [] as $mc) {
                            if ($mc->content_type === 'App\\Models\\Gallery' && isset($mc->gallery)) {
                                $chImages += ($mc->gallery->images ?? collect())->count();
                            }
                        }
                    }
                @endphp
                <a href="#section{{ $keyProject }}" class="cc-chapter-card">
                    <div class="cc-chapter-card__band">
                        <span class="cc-chapter-card__number">{{ str_pad((string) ($keyProject + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <div class="cc-chapter-card__body">
                        <h3>{{ $chapter->name }}</h3>
                        @if(! empty(trim(strip_tags((string) $chapter->description))))
                            <p class="cc-chapter-card__lead">{{ Str::limit(strip_tags((string) $chapter->description), 180) }}</p>
                        @endif
                        <p class="cc-chapter-card__meta">
                            {{ trans_choice('reader_chapter_card_entries', $chEntries, ['count' => $chEntries]) }}
                            @if($chImages > 0)
                                · {{ trans_choice('reader_chapter_card_images', $chImages, ['count' => $chImages]) }}
                            @endif
                        </p>
                    </div>
                </a>
            @endforeach
        </section>
    @endif
@endsection

@section('content')
    @if(isset($project) && isset($project->chapters))
        @foreach($project->chapters as $k => $chapter)
            @if(isset($parameters['collapse']))
                <div class="plus" onclick="addText({{ $k }})"></div>
            @endif
            <section id="section{{ $k }}" class="section einleitung{{ $k }}">
                {{-- Handoff E1b: Kapitel-Opener statt nackter H2. --}}
                @include('preview.chapter-opener', ['chapter' => $chapter, 'chapterIndex' => $k])

                @if(isset($chapter->entries))
                    @foreach($chapter->entries as $key => $entry)
                        <div class="@if($key == 0) hintergrundweiss @elseif($key % 2 == 0) hintergrundweiss @else {{ $parameters['backgroundSecond'] }} @endif">
                            <div class="container">
                                <div class="zweispaltig">
                                    @isset($entry->name)<h2>{{ $entry->name }}</h2>@endisset
                                    @isset($entry->subtitle)<p class="subtitle">{{ $entry->subtitle }}</p>@endisset
                                </div>
                                @include('preview.entry-credits', ['entry' => $entry])
                                @isset($entry->description)
                                    <div class="zweispaltig"><p>@rich($entry->description )</p></div>
                                @endisset

                                {{-- Q4-Etappe 5 / G2 (2026-09-08): Content-Loop
                                     via Type-Dispatcher-Include. --}}
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
            </section>
        @endforeach
        @include('preview.project-credits', ['project' => $project])
    @endif
@endsection
