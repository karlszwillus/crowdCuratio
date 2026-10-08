{{--
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

Q4-Etappe 7 · Etappe-6-Rest (2026-09-11): Bildnachweise-Sammelseite.
Loest den Fußzeile-Anker `#bildnachweise` ab, der ins Leere zeigte.
Listet die Copyright/Origin-Nachweise projektweit, damit Leser:innen
die redaktionelle Herkunft der Materialien nachvollziehen können.
--}}

@extends('preview.layout')

@section('content')
    <div class="container cc-page">
        <header class="cc-page__header">
            <span class="cc-mono-caps cc-page__kicker">{{ $project->name }}</span>
            <h1 class="cc-page__title">{{ __('reader_credits_title') }}</h1>
            <p class="cc-page__intro">{{ __('reader_credits_intro') }}</p>
        </header>

        @php
            // Sammeln: Bilder mit Copyright/Origin.
            $rows = collect();
            foreach ($project->chapters as $chapter) {
                foreach ($chapter->entries as $entry) {
                    foreach ($entry->mediaContent as $mc) {
                        if ($mc->content_type === \App\Models\Gallery::class && isset($mc->gallery)) {
                            foreach ($mc->gallery->images as $img) {
                                $rows->push([
                                    'title' => $img->alt ?: '—',
                                    'chapter' => $chapter->name,
                                    'entry' => $entry->name,
                                    'copyright' => optional($img->copyrightImage)->name,
                                    'origin' => optional($img->originImage)->name,
                                ]);
                            }
                        }
                    }
                }
            }
        @endphp

        @if ($rows->isEmpty())
            <p class="cc-page__empty">{{ __('reader_credits_empty') }}</p>
        @else
            <div class="cc-table-wrap">
                <table class="cc-credits-table">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('title') }}</th>
                            <th scope="col">{{ __('chapter') }} · {{ __('entry') }}</th>
                            <th scope="col">{{ __('copyright') }}</th>
                            <th scope="col">{{ __('origin') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $r)
                            <tr>
                                <td>{{ $r['title'] }}</td>
                                <td class="cc-credits-table__context">{{ $r['chapter'] }} · {{ $r['entry'] }}</td>
                                <td>{{ $r['copyright'] ?: '—' }}</td>
                                <td>{{ $r['origin'] ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
