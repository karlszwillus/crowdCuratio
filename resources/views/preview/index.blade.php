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
    <nav class="ankerleiste">
        <div class="ankerpunkte">
            @if(isset($project->chapters))
                @foreach($project->chapters as $keyProject => $value)
                    <a href="#section{{ $keyProject }}" id="anker{{ $keyProject }}" class="anker">{{ $value->name }}</a>
                @endforeach
            @endif
        </div>
    </nav>
@endsection

@section('intro')
    <section class="einleitung">
        <div class="container">
            <div class="zweispaltig">
                @if(isset($project->description))
                    <p>@rich($project->description )</p>
                @endif
            </div>
        </div>
    </section>
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
                                    @isset($entry->description)<p>@rich($entry->description )</p>@endisset
                                </div>

                                {{-- Q4-Etappe 5 / G2 (2026-09-08): Content-Loop
                                     via Type-Dispatcher-Include. --}}
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
        @endforeach
    @endif
@endsection
