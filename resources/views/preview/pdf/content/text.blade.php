{{--
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

Q4-Etappe 6 · G7-1 (2026-09-10): PDF-Text-Partial. Reiner
Fließtext ohne Rahmen. Rich-Text-Auszeichnungen werden per
strip_tags + nl2br in eine für dompdf sichere Fassung gebracht —
komplexes Markup übernimmt der Web-Reader.

Erwartet: $media (MediaContent mit ->text) im Kontext.
--}}
@if(isset($media->text) && ! empty(strip_tags((string) $media->text->text)))
    @php
        $textCredit = trim(collect([
            optional($media->text->copyrightText)->name,
            optional($media->text->originText)->name,
        ])->filter()->implode(' · '));
        $body = strip_tags((string) $media->text->text, '<p><br><strong><em><b><i>');
    @endphp
    <div class="cc-text">
        <div>{!! nl2br($body) !!}</div>
        @if($textCredit !== '')
            <div class="cc-text__credit">{{ $textCredit }}</div>
        @endif
    </div>
@endif
