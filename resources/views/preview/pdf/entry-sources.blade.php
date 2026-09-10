{{--
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

Q4-Etappe 6 · G7-1 (2026-09-10): PDF-Quellenblock am Abschnitts-
ende. Sammelt alle referenzierten Sources aus den Content-Blöcken
des Abschnitts (Text/Gallery/Audiovisual/QuoteBlock) und rendert
sie als deduplizierte Liste — schlanker als der Web-Quellenblock.

Erwartet: $entry (Entry) im Kontext.
--}}
@php
    /** @var \App\Models\Entry $entry */
    $sources = collect();
    foreach ($entry->mediaContent ?? [] as $mc) {
        if ($mc->content_type === 'App\\Models\\Text' && isset($mc->text)) {
            foreach ([$mc->text->copyrightText, $mc->text->originText] as $s) { if ($s) $sources->push($s); }
        } elseif ($mc->content_type === 'App\\Models\\Gallery' && isset($mc->gallery)) {
            foreach ($mc->gallery->images ?? [] as $img) {
                foreach ([$img->copyrightImage ?? null, $img->originImage ?? null] as $s) { if ($s) $sources->push($s); }
            }
        } elseif ($mc->content_type === 'App\\Models\\Audiovisual' && isset($mc->audiovisual)) {
            foreach ([$mc->audiovisual->copyrightSource ?? null, $mc->audiovisual->originSource ?? null] as $s) { if ($s) $sources->push($s); }
        } elseif ($mc->content_type === 'App\\Models\\QuoteBlock' && isset($mc->quoteBlock)) {
            if ($mc->quoteBlock->source) $sources->push($mc->quoteBlock->source);
        }
    }
    $unique = $sources->unique('id')->values();
@endphp
@if($unique->isNotEmpty())
    <div class="cc-sources">
        <h4 class="cc-sources__heading">{{ __('reader_entry_sources_heading') }}</h4>
        @foreach($unique as $source)
            <div class="cc-sources__item">
                <span class="cc-sources__name">{{ $source->name }}</span>
                @if(! empty(trim((string) $source->title)))
                    · <span class="cc-sources__title">{{ $source->title }}</span>
                @endif
                @if(! empty(trim((string) $source->holding)))
                    · {{ $source->holding }}
                @endif
                @if(! empty(trim((string) $source->signature)))
                    · {{ $source->signature }}
                @endif
            </div>
        @endforeach
    </div>
@endif
