{{--
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

Q4-Etappe 5 / G2+G5 (2026-09-08): Reader-Content-Partial · Galerie.
Rendert ohne Slick-Slider — Bilder als responsives CSS-Grid
(`.cc-gallery-grid` im Layout definiert). Passt sich sowohl an die
One-Pager- als auch an die schmalere Multi-Page-Content-Spalte an.

Erwartet: $media (MediaContent mit ->gallery + ->gallery->images) im Kontext.
--}}
<div class="einspaltig">
    @isset($media->gallery->title)<h2>@rich($media->gallery->title)</h2>@endisset
    @isset($media->gallery->subtitle)<p class="subtitle">@rich($media->gallery->subtitle)</p>@endisset
    @isset($media->gallery->description)<p>@rich($media->gallery->description)</p>@endisset
</div>
@if(isset($media->gallery->images) && $media->gallery->images->isNotEmpty())
    <div class="einspaltig">
        <div class="cc-gallery-grid">
            @foreach($media->gallery->images as $image)
                @php
                    $imgCredit = trim(collect([
                        optional($image->copyrightImage)->name,
                        optional($image->originImage)->name,
                    ])->filter()->implode(' · '));
                @endphp
                <figure>
                    <img alt="{{ $image->alt }}" src="{{ route('image', $image->image) }}" loading="lazy">
                    {{-- Handoff Regel 3: Bildunterschrift + Signatur/
                         Rechte im Grid nebeneinander. Ohne Credit
                         bleibt es bei der Unterschrift. --}}
                    @if(! empty(trim(strip_tags((string) $image->alt))) || $imgCredit !== '')
                        <figcaption class="cc-figcaption">
                            <span class="cc-figcaption__caption">@rich($image->alt)</span>
                            @if($imgCredit !== '')
                                <span class="cc-figcaption__credit">{{ $imgCredit }}</span>
                            @endif
                        </figcaption>
                    @endif
                </figure>
            @endforeach
        </div>
    </div>
@endif
