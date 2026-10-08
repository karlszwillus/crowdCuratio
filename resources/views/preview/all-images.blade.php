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
    <div class="container cc-page">
        <header class="cc-page__header">
            <span class="cc-mono-caps cc-page__kicker">{{ $project->name }}</span>
            <h1 class="cc-page__title">{{ __('reader_all_images_title') }}</h1>
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
                <section class="cc-page__section">
                    <h2>{{ $chapter->name }}</h2>
                    <ul class="cc-image-grid">
                        @foreach ($chapterImages as $row)
                            @php $img = $row['image']; $entry = $row['entry']; @endphp
                            <li class="cc-image-grid__item">
                                <div class="cc-image-grid__media">
                                    <img src="{{ route('image', $img->image) }}"
                                         alt="{{ $img->alt ?? '' }}"
                                         loading="lazy"/>
                                </div>
                                @if (! empty($img->alt))
                                    <p class="cc-image-grid__title">{{ $img->alt }}</p>
                                @endif
                                <p class="cc-image-grid__meta">
                                    {{ $entry->name }}
                                </p>
                                @if (! empty(trim(strip_tags((string) $img->description))))
                                    <div class="cc-image-grid__desc">{!! $img->description !!}</div>
                                @endif
                                <p class="cc-image-grid__credit">
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
