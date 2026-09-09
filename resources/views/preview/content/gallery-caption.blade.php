{{--
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

Q4-Etappe 5 / E1b-Polish (2026-09-09): Bildunterschrift-Grid.
Handoff Regel 3: Bildunterschrift unter dem Bild, Signatur/
Rechte im Grid daneben.

Erwartet: $image (Image) im Kontext.
--}}
@php
    $creditParts = collect([
        optional($image->copyrightImage)->name,
        optional($image->originImage)->name,
    ])->filter()->implode(' · ');
    $hasCaption = ! empty(trim(strip_tags((string) $image->alt)));
    $hasCredit = $creditParts !== '';
@endphp
@if($hasCaption || $hasCredit)
    <figcaption class="cc-figcaption">
        <span class="cc-figcaption__caption">
            @if($hasCaption)@rich($image->alt)@endif
        </span>
        @if($hasCredit)
            <span class="cc-figcaption__credit">{{ $creditParts }}</span>
        @endif
    </figcaption>
@endif
