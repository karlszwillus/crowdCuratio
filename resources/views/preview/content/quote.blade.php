{{--
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

Q4-Etappe 5 / G2 (2026-09-08): Reader-Content-Partial · Zitat.
Extrahiert aus preview/index.blade.php.

Nachweiszeile ist ABGELEITET (kein Eingabefeld):
    {speaker}, {date_text} · {source.name}{, locator}
Optional darunter „Originaltext: …" mit lang-Attribut, wenn
text_original gefüllt ist.

Erwartet: $media (MediaContent mit ->quoteBlock + ->quoteBlock->source) im Kontext.
--}}
@if(isset($media->quoteBlock))
    @php
        $q = $media->quoteBlock;
        $attribution = collect([$q->speaker, $q->date_text])->filter()->implode(', ');
        $sourceLine = collect([$q->source?->name, $q->locator])->filter()->implode(', ');
    @endphp
    <div class="einspaltig zitat">
        <blockquote>
            @if(! empty(trim(strip_tags((string) $q->text))))
                @rich($q->text)
            @endif
            @if($attribution !== '' || $sourceLine !== '')
                <cite>
                    @if($attribution !== ''){{ $attribution }}@endif
                    @if($attribution !== '' && $sourceLine !== '') · @endif
                    @if($sourceLine !== ''){{ $sourceLine }}@endif
                </cite>
            @endif
        </blockquote>
        @if(! empty(trim((string) $q->text_original)))
            <p class="zitat-original">
                <strong>{{ __('quote_original_label') }}:</strong>
                <span @if($q->lang_original) lang="{{ $q->lang_original }}" @endif>
                    {{ $q->text_original }}
                </span>
            </p>
        @endif
    </div>
@endif
