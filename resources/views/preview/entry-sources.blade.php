{{--
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

Q4-Etappe 5 / G-Fund-5 (2026-09-09): Quellenblock am Eintragsende.
Sammelt alle referenzierten Sources aus den Content-Blöcken des
Eintrags (Text.copyright/origin, Gallery-Image.copyright/origin,
Audiovisual.copyright_id/origin_id, QuoteBlock.source_id) und
rendert sie als deduplizierte Liste im Handoff-v4-Stil.

Erwartet: $entry (Entry) im Kontext.
--}}
@php
    /** @var \App\Models\Entry $entry */
    $collected = collect();
    foreach ($entry->mediaContent ?? [] as $mc) {
        if ($mc->content_type === 'App\\Models\\Text' && isset($mc->text)) {
            $collected->push($mc->text->copyrightText);
            $collected->push($mc->text->originText);
        } elseif ($mc->content_type === 'App\\Models\\Gallery' && isset($mc->gallery)) {
            foreach ($mc->gallery->images ?? [] as $img) {
                $collected->push($img->copyrightImage ?? null);
                $collected->push($img->originImage ?? null);
            }
        } elseif ($mc->content_type === 'App\\Models\\Audiovisual' && isset($mc->audiovisual)) {
            $collected->push($mc->audiovisual->copyrightSource ?? null);
            $collected->push($mc->audiovisual->originSource ?? null);
        } elseif ($mc->content_type === 'App\\Models\\QuoteBlock' && isset($mc->quoteBlock)) {
            $collected->push($mc->quoteBlock->source ?? null);
        }
    }
    $entrySources = $collected
        ->filter()
        ->unique(fn ($s) => $s->id)
        ->sortBy(fn ($s) => (string) $s->name)
        ->values();
@endphp
@if($entrySources->isNotEmpty())
    <aside class="cc-entry-sources" aria-labelledby="entry-sources-{{ $entry->id }}">
        <h4 id="entry-sources-{{ $entry->id }}">{{ __('reader_entry_sources_heading') }}</h4>
        <ul>
            @foreach($entrySources as $src)
                <li>
                    <span class="cc-entry-sources__name">{{ $src->name }}</span>
                    @if(! empty($src->title))
                        <span class="cc-entry-sources__title">{{ $src->title }}</span>
                    @endif
                    @if(! empty($src->holding) || ! empty($src->signature))
                        <span class="cc-entry-sources__meta">
                            @if(! empty($src->holding)){{ $src->holding }}@endif
                            @if(! empty($src->holding) && ! empty($src->signature)) · @endif
                            @if(! empty($src->signature)){{ $src->signature }}@endif
                        </span>
                    @endif
                </li>
            @endforeach
        </ul>
    </aside>
@endif
