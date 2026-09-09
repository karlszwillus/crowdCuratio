{{--
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

Q4-Etappe 5 / G2 (2026-09-08): Reader-Content-Partial · Text.
Extrahiert aus preview/index.blade.php. Wird auch von der neuen
Multi-Page-View wiederverwendet.

Erwartet: $media (MediaContent mit ->text) im Kontext.
--}}
@if(isset($media->text))
    @php
        $textCredit = trim(collect([
            optional($media->text->copyrightText)->name,
            optional($media->text->originText)->name,
        ])->filter()->implode(' · '));
    @endphp
    <div class="einspaltig">
        <div class="cc-text-body">@rich($media->text->text)</div>
        @if($textCredit !== '')
            {{-- Handoff Regel 3: redaktionelle Leistung sichtbar
                 machen — Quelle/Copyright unter jedem Textblock. --}}
            <p class="cc-text-credit">{{ $textCredit }}</p>
        @endif
    </div>
@endif
