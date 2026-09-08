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

@push('preview-head')
<style type="text/css">
    /* Q4-Etappe 5 / G4: Multi-Page-Reader-Grid. Sidebar links,
       Content rechts. Optik ist erste Iteration — Feinschliff mit
       Design-Rücksprache. */
    body.cc-reader-multipage .cc-multipage { display: grid; grid-template-columns: 260px 1fr; }
    body.cc-reader-multipage .cc-multipage__nav {
        position: sticky; top: 0; align-self: start;
        padding: 1.5rem 1.25rem;
        max-height: 100vh; overflow-y: auto;
        border-right: 1px solid rgba(0, 0, 0, 0.08);
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: saturate(140%) blur(8px);
    }
    body.cc-reader-multipage .cc-multipage__nav h2 {
        font-size: 0.75rem; letter-spacing: 0.08em; text-transform: uppercase;
        color: #6b7280; margin: 0 0 0.75rem;
    }
    body.cc-reader-multipage .cc-multipage__nav ol { list-style: none; padding: 0; margin: 0; counter-reset: chapter; }
    body.cc-reader-multipage .cc-multipage__nav li { counter-increment: chapter; margin-bottom: 0.25rem; }
    body.cc-reader-multipage .cc-multipage__nav a {
        display: block; padding: 0.5rem 0.75rem; border-radius: 0.375rem;
        color: inherit; text-decoration: none; line-height: 1.35;
    }
    body.cc-reader-multipage .cc-multipage__nav a::before {
        content: counter(chapter) '. '; color: #9ca3af; margin-right: 0.25rem;
    }
    body.cc-reader-multipage .cc-multipage__nav a:hover { background: rgba(0, 0, 0, 0.04); }
    body.cc-reader-multipage .cc-multipage__nav a.is-active {
        background: rgba(0, 0, 0, 0.06); font-weight: 600; color: #111;
    }
    body.cc-reader-multipage .cc-multipage__content { min-width: 0; }

    @media (max-width: 720px) {
        body.cc-reader-multipage .cc-multipage { grid-template-columns: 1fr; }
        body.cc-reader-multipage .cc-multipage__nav {
            position: static; max-height: none;
            border-right: 0; border-bottom: 1px solid rgba(0, 0, 0, 0.08);
        }
    }
</style>
@endpush

@section('content')
    <div class="cc-multipage">
        <nav class="cc-multipage__nav" aria-label="{{ __('reader_chapter_nav_label') }}">
            <h2>{{ __('reader_chapter_nav_label') }}</h2>
            <ol>
                @foreach($project->chapters->sortBy('position') as $navChapter)
                    <li>
                        <a href="{{ route('preview.chapter', ['project' => $project->id, 'chapter' => $navChapter->id]) }}"
                           @class(['is-active' => $navChapter->id === $chapter->id])
                           @if($navChapter->id === $chapter->id) aria-current="page" @endif>
                            {{ $navChapter->name }}
                        </a>
                    </li>
                @endforeach
            </ol>
        </nav>

        <div class="cc-multipage__content">
            <section class="section">
                <div class="hintergrundweiss">
                    <div class="container">
                        <div class="zweispaltig" id="text">
                            @isset($chapter->title)<h2>@rich($chapter->title )</h2>@endisset
                            @isset($chapter->subtitle)<h3>{{ $chapter->subtitle }}</h3>@endisset
                            @isset($chapter->description)<p>@rich($chapter->description )</p>@endisset
                        </div>
                    </div>
                </div>

                @if(isset($chapter->entries))
                    @foreach($chapter->entries as $key => $entry)
                        <div class="@if($key == 0) hintergrundweiss @elseif($key % 2 == 0) hintergrundweiss @else {{ $parameters['backgroundSecond'] }} @endif">
                            <div class="container">
                                <div class="zweispaltig">
                                    @isset($entry->name)<h2>{{ $entry->name }}</h2>@endisset
                                    @isset($entry->subtitle)<p class="subtitle">{{ $entry->subtitle }}</p>@endisset
                                    @isset($entry->description)<p>@rich($entry->description )</p>@endisset
                                </div>

                                @if(isset($entry->mediaContent))
                                    @foreach($entry->mediaContent as $media)
                                        @include('preview.content.dispatcher', ['media' => $media])
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    @endforeach
                @endif
            </section>
        </div>
    </div>
@endsection
