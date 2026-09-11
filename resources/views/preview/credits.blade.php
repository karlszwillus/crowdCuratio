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
    <div class="container">
        <header class="mb-6">
            <p class="mb-1 text-mono-caps font-mono uppercase tracking-widest text-ink-500">
                {{ $project->name }}
            </p>
            <h1 class="text-title font-semibold text-ink-900">
                {{ __('reader_credits_title') }}
            </h1>
            <p class="mt-2 text-body text-ink-700">{{ __('reader_credits_intro') }}</p>
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
            <p class="text-body text-ink-500">{{ __('reader_credits_empty') }}</p>
        @else
            <table class="w-full text-caption">
                <thead>
                    <tr class="border-b border-line-200 text-left text-ink-500">
                        <th class="py-2 pr-2 font-medium">{{ __('title') }}</th>
                        <th class="py-2 pr-2 font-medium">{{ __('chapter') }} · {{ __('entry') }}</th>
                        <th class="py-2 pr-2 font-medium">{{ __('copyright') }}</th>
                        <th class="py-2 font-medium">{{ __('origin') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $r)
                        <tr class="border-b border-line-100 align-top">
                            <td class="py-2 pr-2 text-ink-900">{{ $r['title'] }}</td>
                            <td class="py-2 pr-2 text-ink-500">{{ $r['chapter'] }} · {{ $r['entry'] }}</td>
                            <td class="py-2 pr-2 text-ink-700">{{ $r['copyright'] ?: '—' }}</td>
                            <td class="py-2 text-ink-700">{{ $r['origin'] ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
