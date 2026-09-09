{{--
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

Q4-Etappe 5 / E1b-Polish (2026-09-09): Reader-Content-Partial · Galerie.
Handoff-Regel 2 „Bild führt":
  - 1 Bild  → Vollbreit (470 px hoch, bündig zum Randabstand).
  - 2 Bilder → 1:1 zweispaltig, Unterschrift je Bild.
  - 3+ Bilder → Grid mit Lightbox-Pille (später).

Bildunterschrift steht immer unter dem Bild (nie darin), Signatur/
Rechte im Grid daneben.

Erwartet: $media (MediaContent mit ->gallery + ->gallery->images) im Kontext.
--}}
@if(isset($media->gallery))
    @php
        $imgs = $media->gallery->images ?? collect();
        $imgCount = $imgs->count();
    @endphp

    @if(! empty($media->gallery->title) || ! empty($media->gallery->subtitle) || ! empty($media->gallery->description))
        <div class="einspaltig">
            @isset($media->gallery->title)<h3>@rich($media->gallery->title)</h3>@endisset
            @isset($media->gallery->subtitle)<p class="subtitle">@rich($media->gallery->subtitle)</p>@endisset
            @isset($media->gallery->description)<p>@rich($media->gallery->description)</p>@endisset
        </div>
    @endif

    @if($imgCount === 1)
        @php $img = $imgs->first(); @endphp
        <figure class="cc-fig cc-fig--full">
            <img alt="{{ $img->alt }}" src="{{ route('image', $img->image) }}" loading="lazy">
            @include('preview.content.gallery-caption', ['image' => $img])
        </figure>
    @elseif($imgCount === 2)
        <div class="cc-fig-pair">
            @foreach($imgs as $img)
                <figure class="cc-fig">
                    <img alt="{{ $img->alt }}" src="{{ route('image', $img->image) }}" loading="lazy">
                    @include('preview.content.gallery-caption', ['image' => $img])
                </figure>
            @endforeach
        </div>
    @elseif($imgCount > 2)
        <div class="cc-gallery-grid">
            @foreach($imgs as $img)
                <figure>
                    <img alt="{{ $img->alt }}" src="{{ route('image', $img->image) }}" loading="lazy">
                    @include('preview.content.gallery-caption', ['image' => $img])
                </figure>
            @endforeach
        </div>
    @endif
@endif
