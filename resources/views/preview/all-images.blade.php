{{--
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

Q4-Etappe 7 · E7-6 / Etappe-6-Rest (2026-09-11): Bild-Übersicht des
Projekts — vom Rail-Fußlink „Alle Abbildungen" im Multi-Page-Reader
aufgerufen. Reduzierte Sicht: pro Bild Vorschau, Titel, Beschreibung
und Nachweise. Kapitel/Abschnitt-Struktur bleibt sichtbar, damit sich
die Leser:innen im Projekt orientieren können.
--}}

@extends('preview.layout')

@section('body-classes', 'cc-reader-all-images')

@section('content')
    <div class="container">
        <header class="mb-6">
            <p class="mb-1 text-mono-caps font-mono uppercase tracking-widest text-ink-500">
                {{ $project->name }}
            </p>
            <h1 class="text-title font-semibold text-ink-900">
                {{ __('reader_all_images_title') }}
            </h1>
        </header>

        @foreach ($project->chapters->sortBy('position') as $chapter)
            @php
                $chapterImages = collect();
                foreach ($chapter->entries as $entry) {
                    foreach ($entry->mediaContent as $mc) {
                        if ($mc->content_type === \App\Models\Gallery::class && isset($mc->gallery)) {
                            foreach ($mc->gallery->images as $img) {
                                $chapterImages->push(['entry' => $entry, 'image' => $img]);
                            }
                        }
                    }
                }
            @endphp

            @if ($chapterImages->isNotEmpty())
                <section class="mb-10">
                    <h2 class="mb-4 text-heading font-semibold text-ink-900">
                        {{ $chapter->name }}
                    </h2>
                    <ul class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                        @foreach ($chapterImages as $row)
                            @php $img = $row['image']; $entry = $row['entry']; @endphp
                            <li class="flex flex-col gap-2">
                                <div class="overflow-hidden rounded-md bg-line-100">
                                    <img src="{{ route('image', $img->image) }}"
                                         alt="{{ $img->alt ?? '' }}"
                                         class="h-40 w-full object-cover"/>
                                </div>
                                @if (! empty($img->alt))
                                    <p class="text-body font-semibold text-ink-900">{{ $img->alt }}</p>
                                @endif
                                <p class="text-caption text-ink-500">
                                    {{ $entry->name }}
                                </p>
                                @if (! empty(trim(strip_tags((string) $img->description))))
                                    <div class="text-caption text-ink-700">{!! $img->description !!}</div>
                                @endif
                                <p class="text-caption text-ink-500">
                                    @if ($img->copyrightImage)
                                        <span>© {{ $img->copyrightImage->name }}</span>
                                    @endif
                                    @if ($img->originImage)
                                        <span> · {{ $img->originImage->name }}</span>
                                    @endif
                                </p>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        @endforeach
    </div>
@endsection
