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
{{-- Q4-Etappe 6 · G6-4-Nachreview: figcaption immer rendern —
     auch wenn Caption und Credit fehlen. Die feste Mindesthöhe
     (siehe .cc-gal-band__item .cc-figcaption min-height) sorgt
     dafür, dass eine leere Zeile sichtbar reserviert bleibt und
     die Unterschrift der einen Kachel nicht wie eine Beschreibung
     der ganzen Reihe wirkt (Design-Review Blocker 4). --}}
<figcaption class="cc-figcaption">
    <span class="cc-figcaption__caption">
        @if($hasCaption)@rich($image->alt)@endif
    </span>
    @if($hasCredit)
        <span class="cc-figcaption__credit">{{ $creditParts }}</span>
    @endif
</figcaption>
