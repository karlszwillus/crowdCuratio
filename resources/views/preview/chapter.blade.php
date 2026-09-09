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

{{-- Multi-Page-Styles kommen jetzt aus public/css/reader.css
     (G-Fund-2), keine inline-Blöcke mehr. --}}

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
            </section>
        </div>
    </div>
@endsection
