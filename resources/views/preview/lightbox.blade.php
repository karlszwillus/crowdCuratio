{{--
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

Q4-Etappe 6 · G6-7 (2026-09-10): Lightbox-Overlay. Ein einziges
Overlay pro Reader-Seite, gesteuert vom globalen Alpine-Store
`lightbox` (siehe preview/layout.blade.php). Galerien öffnen die
Lightbox mit `$store.lightbox.show(items, index)` — items ist ein
Array `[{src, alt, caption, credit, focus_x, focus_y}, ...]`.

Ohne JS bleibt das Overlay komplett versteckt; Bilder in Band und
Sequenz haben ihre Nachweiszeilen ohnehin unter dem Bild, und der
Kontaktbogen zeigt sie in der Lightbox erst mit Alpine.
--}}
<div x-data
     x-show="$store.lightbox.open"
     x-cloak
     @keydown.escape.window="$store.lightbox.close()"
     @keydown.arrow-left.window="$store.lightbox.prev()"
     @keydown.arrow-right.window="$store.lightbox.next()"
     class="cc-lightbox"
     role="dialog"
     aria-modal="true"
     :aria-label="$store.lightbox.current ? ($store.lightbox.current.caption || '{{ __('gallery_form_kontaktbogen') }}') : ''">
    <div class="cc-lightbox__backdrop" @click="$store.lightbox.close()"></div>

    <button type="button"
            class="cc-lightbox__close"
            @click="$store.lightbox.close()"
            aria-label="{{ __('lightbox_close') }}">×</button>

    <button type="button"
            class="cc-lightbox__nav cc-lightbox__nav--prev"
            @click="$store.lightbox.prev()"
            :disabled="$store.lightbox.items.length < 2"
            aria-label="{{ __('gallery_sequence_prev') }}">‹</button>

    <button type="button"
            class="cc-lightbox__nav cc-lightbox__nav--next"
            @click="$store.lightbox.next()"
            :disabled="$store.lightbox.items.length < 2"
            aria-label="{{ __('gallery_sequence_next') }}">›</button>

    <div class="cc-lightbox__body" @click.stop>
        <figure class="cc-lightbox__figure">
            <img x-show="$store.lightbox.current"
                 x-cloak
                 :src="$store.lightbox.current ? $store.lightbox.current.src : ''"
                 :alt="$store.lightbox.current ? $store.lightbox.current.alt : ''"
                 class="cc-lightbox__image">
        </figure>

        <aside class="cc-lightbox__meta" aria-label="{{ __('lightbox_meta_label') }}">
            <div class="cc-lightbox__meta-counter">
                <span x-text="String($store.lightbox.index + 1).padStart(2, '0')"></span>
                <span> / </span>
                <span x-text="String($store.lightbox.items.length).padStart(2, '0')"></span>
            </div>
            <h4 class="cc-lightbox__meta-heading">{{ __('lightbox_meta_caption') }}</h4>
            <p class="cc-lightbox__meta-caption"
               x-text="$store.lightbox.current && $store.lightbox.current.caption ? $store.lightbox.current.caption : '{{ __('gallery_image_untitled') }}'"></p>

            <template x-if="$store.lightbox.current && $store.lightbox.current.credit">
                <div>
                    <h4 class="cc-lightbox__meta-heading">{{ __('lightbox_meta_credit') }}</h4>
                    <p class="cc-lightbox__meta-credit" x-text="$store.lightbox.current.credit"></p>
                </div>
            </template>
        </aside>
    </div>
</div>
