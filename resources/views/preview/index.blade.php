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
    <section class="einleitung">
        <div class="container">
            <h1 style="font-size: var(--t-project-title); line-height: 1.1; letter-spacing: -0.8px; margin-bottom: 1rem;">
                {{ $project->name }}
            </h1>
            @if(isset($project->description))
                <p class="cc-lead">@rich($project->description )</p>
            @endif
        </div>
    </section>

    {{-- Chapter-Chips als eigener Streifen, Handoff E2b-Muster.
         Sticky beim Scroll wäre ein Followup. --}}
    <div id="kapitel" class="ankerleiste">
        <div class="ankerpunkte">
            @if(isset($project->chapters))
                @foreach($project->chapters as $keyProject => $value)
                    <a href="#section{{ $keyProject }}" id="anker{{ $keyProject }}" class="anker">{{ $value->name }}</a>
                @endforeach
            @endif
        </div>
    </div>
@endsection

@section('content')
    @if(isset($project) && isset($project->chapters))
        @foreach($project->chapters as $k => $chapter)
            <h4 class="toggledown">{{ $chapter->name }}</h4>
            @if(isset($parameters['collapse']))
                <div class="plus" onclick="addText({{ $k }})"></div>
            @endif
            <section id="section{{ $k }}" class="section einleitung{{ $k }}">
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
