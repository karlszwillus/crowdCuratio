{{--
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

Q4-Etappe 6 · G6-4 (2026-09-10): Reader-Content-Partial · Galerie
nach Anzahl-Regel aus dem Design-Briefing Arbeitspaket 5.

  1 Bild        → Band einzeln (Vollbreit oder 380 px)
  2–4 Bilder    → Band gestapelt (jeweils 380 px hoch)
  ab 5 Bildern  → Kontaktbogen (3-Spalten-Grid, 4:3, ab 10. Bild +n)
  Sequenz       → Bühne mit Pager (redaktionelle Ansage)

Die Form kommt aus `GalleryForm::resolve()` — dieselbe Regel wie
im Editor-Kopfpanel, damit Anzeige und Ausspiel niemals divergieren.

`no_crop` respektieren: die Zelle im Kontaktbogen wechselt von
`cover` auf `contain`, damit Dokumente/Scans/Karten nicht ihre
Ränder verlieren. `focus_x/y` (Prozent) werden als
`object-position` angewendet, damit Porträts nicht mittig
beschnitten werden.

Erwartet: $media (MediaContent mit ->gallery + ->gallery->images) im Kontext.
--}}
@php
    use App\Support\GalleryForm;
@endphp

@if(isset($media->gallery))
    @php
        $gallery = $media->gallery;
        $imgs = $gallery->images ?? collect();
        $imgCount = $imgs->count();
        $form = GalleryForm::resolve($imgCount, (bool) $gallery->sequence);

        // Q4-Etappe 6 · G6-7 (2026-09-10): Meta-Array für die
        // Lightbox — pro Bild {src, alt, caption, credit}. Der
        // Alpine-Store liest daraus, egal aus welcher Form die
        // Lightbox geöffnet wurde (Band, Bogen oder Sequenz).
        $lightboxItems = $imgs->map(function ($img) {
            $creditParts = collect([
                optional($img->copyrightImage)->name,
                optional($img->originImage)->name,
            ])->filter()->implode(' · ');

            return [
                'src' => route('image', $img->image),
                'alt' => (string) ($img->alt ?? ''),
                'caption' => trim(strip_tags((string) $img->alt)),
                'credit' => $creditParts,
            ];
        })->values()->all();
    @endphp

    @if(! empty($gallery->title) || ! empty($gallery->subtitle) || ! empty($gallery->description))
        <div class="einspaltig">
            @isset($gallery->title)<h3>@rich($gallery->title)</h3>@endisset
            @isset($gallery->subtitle)<p class="subtitle">@rich($gallery->subtitle)</p>@endisset
            @isset($gallery->description)<p>@rich($gallery->description)</p>@endisset
        </div>
    @endif

    @if($imgCount === 0)
        {{-- Keine Bilder — nichts rendern. --}}
    @elseif($form === GalleryForm::SEQUENZ)
        {{-- Sequenz: Bühne mit Pager. Alpine-basiert; ohne JS fällt
             der Fallback auf gestapeltes Band (siehe unten). Der
             Pager zeigt Position („02 / 06") und Striche als
             Fortschritt (nicht Punkte, per Handoff). --}}
        <div class="cc-gal-stage"
             x-data="{ i: 0, total: {{ $imgCount }} }"
             role="region"
             aria-roledescription="{{ __('gallery_sequence_aria') }}"
             aria-label="{{ $gallery->title ?? __('gallery_form_sequenz') }}">
            <div class="cc-gal-stage__frame">
                @foreach($imgs as $img)
                    <div class="cc-gal-stage__slide" x-show="i === {{ $loop->index }}" x-cloak
                         @click="$store.lightbox.show(@js($lightboxItems), i)"
                         role="button"
                         tabindex="0"
                         aria-label="{{ __('gallery_open_lightbox') }}"
                         @keydown.enter.prevent="$store.lightbox.show(@js($lightboxItems), i)"
                         @keydown.space.prevent="$store.lightbox.show(@js($lightboxItems), i)">
                        <img alt="{{ $img->alt }}"
                             src="{{ route('image', $img->image) }}"
                             loading="lazy">
                    </div>
                @endforeach
                <button type="button"
                        class="cc-gal-stage__nav cc-gal-stage__nav--prev"
                        @click="i = (i - 1 + total) % total"
                        aria-label="{{ __('gallery_sequence_prev') }}">‹</button>
                <button type="button"
                        class="cc-gal-stage__nav cc-gal-stage__nav--next"
                        @click="i = (i + 1) % total"
                        aria-label="{{ __('gallery_sequence_next') }}">›</button>
                <div class="cc-gal-stage__counter" aria-live="polite">
                    <span x-text="String(i + 1).padStart(2, '0')"></span> / {{ str_pad((string) $imgCount, 2, '0', STR_PAD_LEFT) }}
                </div>
            </div>
            <div class="cc-gal-stage__ticks" role="tablist">
                @foreach($imgs as $img)
                    <button type="button"
                            class="cc-gal-stage__tick"
                            :class="i === {{ $loop->index }} ? 'is-active' : ''"
                            @click="i = {{ $loop->index }}"
                            role="tab"
                            :aria-selected="i === {{ $loop->index }} ? 'true' : 'false'"
                            aria-label="{{ __('gallery_sequence_goto', ['n' => $loop->iteration]) }}"></button>
                @endforeach
            </div>
            {{-- Design-Review-Nachreview 2026-09-10 · Blocker 4:
                 Nachweiszeile pro aktivem Slide, unter der Bühne
                 auf ruhigem Papier. Alpine schaltet sichtbar. --}}
            <div class="cc-gal-stage__caption">
                @foreach($imgs as $img)
                    <div x-show="i === {{ $loop->index }}" x-cloak>
                        @include('preview.content.gallery-caption', ['image' => $img])
                    </div>
                @endforeach
            </div>
        </div>
    @elseif($form === GalleryForm::KONTAKTBOGEN)
        {{-- Kontaktbogen: 3-Spalten-Grid. Ab 10. Bild eine „+n"-Kachel
             als letzte Zelle (in G6-4 vorbereitet, Klick wird mit
             der Lightbox in Etappe 7 verdrahtet). --}}
        @php
            $bogenCap = 9;
            $shown = $imgs->take($bogenCap);
            $overflow = max(0, $imgCount - $bogenCap);
        @endphp
        <div class="cc-gal-bogen" x-data>
            @foreach($shown as $img)
                <figure class="cc-gal-bogen__cell{{ $img->no_crop ? ' cc-gal-bogen__cell--fit' : '' }}"
                        @click="$store.lightbox.show(@js($lightboxItems), {{ $loop->index }})"
                        role="button"
                        tabindex="0"
                        aria-label="{{ __('gallery_open_lightbox') }}"
                        @keydown.enter.prevent="$store.lightbox.show(@js($lightboxItems), {{ $loop->index }})"
                        @keydown.space.prevent="$store.lightbox.show(@js($lightboxItems), {{ $loop->index }})">
                    <img alt="{{ $img->alt }}"
                         src="{{ route('image', $img->image) }}"
                         loading="lazy"
                         @if(! $img->no_crop && ($img->focus_x !== null || $img->focus_y !== null))
                             style="object-position: {{ $img->focus_x ?? 50 }}% {{ $img->focus_y ?? 50 }}%;"
                         @endif>
                    @include('preview.content.gallery-caption', ['image' => $img])
                </figure>
            @endforeach
            @if($overflow > 0)
                <button type="button"
                        class="cc-gal-bogen__more"
                        @click="$store.lightbox.show(@js($lightboxItems), {{ $bogenCap }})"
                        aria-label="{{ __('gallery_bogen_more', ['count' => $overflow]) }}">
                    + {{ $overflow }}
                </button>
            @endif
        </div>
    @else
        {{-- Band: 1 Bild einzeln, 2–4 nebeneinander in 1 Reihe
             (2 → 2 Spalten, 3 → 3 Spalten, 4 → 2×2). Alle Kacheln
             gleich hoch, `object-fit: cover` mit optionalem
             `focus_x/y`; `no_crop`-Bilder wechseln auf `contain`,
             damit Dokumente/Scans nicht beschnitten werden. --}}
        <div class="cc-gal-band cc-gal-band--n{{ min($imgCount, 4) }}" x-data>
            @foreach($imgs as $img)
                <figure class="cc-gal-band__item{{ $img->no_crop ? ' cc-gal-band__item--fit' : '' }}">
                    <div class="cc-gal-band__frame"
                         @click="$store.lightbox.show(@js($lightboxItems), {{ $loop->index }})"
                         role="button"
                         tabindex="0"
                         aria-label="{{ __('gallery_open_lightbox') }}"
                         @keydown.enter.prevent="$store.lightbox.show(@js($lightboxItems), {{ $loop->index }})"
                         @keydown.space.prevent="$store.lightbox.show(@js($lightboxItems), {{ $loop->index }})">
                        <img alt="{{ $img->alt }}"
                             src="{{ route('image', $img->image) }}"
                             loading="lazy"
                             @if(! $img->no_crop && ($img->focus_x !== null || $img->focus_y !== null))
                                 style="object-position: {{ $img->focus_x ?? 50 }}% {{ $img->focus_y ?? 50 }}%;"
                             @endif>
                    </div>
                    @include('preview.content.gallery-caption', ['image' => $img])
                </figure>
            @endforeach
        </div>
    @endif
@endif
