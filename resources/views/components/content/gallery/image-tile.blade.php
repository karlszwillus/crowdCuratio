@props(['image', 'item', 'project', 'listPermissions', 'position'])

{{--
    Q4-Etappe 2 / I5 (2026-08-27): Image-Kachel im Gallery-Grid,
    extrahiert aus components/content/gallery-block.blade.php.

    Alpine-Vars (pickedId, editingImageId) und Methoden (pick, drop,
    enterDetail, moveBy) leben im umschliessenden x-data des
    Gallery-Blocks — die Sub-Component sitzt im DOM-Scope davon und
    kann sie lesen.

    Props:
    - $image, $item, $project, $listPermissions
    - $position  Sortier-Position (1-basiert), aus $loop->iteration
--}}

<div class="gallery_item group relative" id="gallery_items_{{$item->gallery->id}}" data-image-id="{{ $image->id }}">
    <div id="anchor_MediaContent_{{$item->id}}"
         data-image-id="{{ $image->id }}"
         class="gallery-tile-frame relative flex aspect-video items-center justify-center overflow-hidden rounded-md bg-line-100">
        <img
            src="{{ route('image', $image->image) }}"
            alt="{{ $image->alt }}"
            class="max-h-full max-w-full object-contain"
            loading="lazy"
        />
        <span
            @can('update', $project)
                role="button"
                tabindex="0"
                :aria-pressed="pickedId === {{ $image->id }} ? 'true' : 'false'"
                aria-keyshortcuts="Space ArrowUp ArrowDown ArrowLeft ArrowRight Escape"
                @keydown.space.prevent="pickedId === {{ $image->id }} ? drop() : (pickedId === null && pick({{ $image->id }}))"
                @keydown.enter.prevent="pickedId === {{ $image->id }} ? drop() : (pickedId === null && pick({{ $image->id }}))"
                @keydown.escape.prevent="pickedId === {{ $image->id }} && cancel()"
                @keydown.arrow-right.prevent="pickedId === {{ $image->id }} && moveBy({{ $image->id }}, 1)"
                @keydown.arrow-down.prevent="pickedId === {{ $image->id }} && moveBy({{ $image->id }}, 1)"
                @keydown.arrow-left.prevent="pickedId === {{ $image->id }} && moveBy({{ $image->id }}, -1)"
                @keydown.arrow-up.prevent="pickedId === {{ $image->id }} && moveBy({{ $image->id }}, -1)"
                :class="pickedId === {{ $image->id }} ? 'ring-2 ring-brand-bar ring-offset-1' : ''"
            @endcan
            class="gallery-drag-handle absolute left-1.5 top-1.5 inline-flex cursor-grab items-center gap-1 rounded px-1.5 py-0.5 text-caption font-semibold text-white active:cursor-grabbing focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-bar"
            style="background-color: rgba(27,35,48,.78);"
            aria-label="{{ __('gallery_position') }} {{ $position }}"
        >
            @can('update', $project)
                <x-icon name="grip-vertical" size="3"/>
            @endcan
            <span data-image-position>{{ $position }}</span>
        </span>

        {{-- Q4-Etappe 6 · G6-2: no_crop-Marker. Kleines Mono-Kürzel
             oben rechts an der Kachel, damit im Editor sichtbar ist,
             welche Bilder eingepasst statt gefüllt gerendert werden
             (relevant im Kontaktbogen ab 5 Bildern). --}}
        @if ($image->no_crop)
            <span class="absolute right-1.5 top-1.5 inline-flex items-center rounded px-1.5 py-0.5 font-mono text-white"
                  style="background-color: rgba(27,35,48,.78); font-size: 10px; letter-spacing: 0.06em;"
                  title="{{ __('gallery_no_crop_marker') }}"
                  aria-label="{{ __('gallery_no_crop_marker') }}">
                NC
            </span>
        @endif

        @can('update', $project)
            {{-- Overlay-Aktionen unten: Angaben bearbeiten + Entfernen.
                 Erscheint bei hover ODER focus-within — auf Touch-Geraeten
                 per :focus-within immer, sobald man die Kachel antippt. --}}
            <div class="pointer-events-none absolute inset-x-0 bottom-0 flex items-center justify-between gap-2 bg-gradient-to-t from-ink-900/80 to-transparent px-2 py-1.5 opacity-0 transition-opacity duration-150 group-hover:opacity-100 group-focus-within:opacity-100">
                <button
                    type="button"
                    @click.stop="enterDetail({{ $image->id }})"
                    class="pointer-events-auto inline-flex items-center gap-1 rounded bg-white/90 px-2 py-1 text-caption font-medium text-ink-900 hover:bg-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                    title="{{ __('gallery_actions_edit') }}"
                >
                    <x-icon name="pencil" size="3"/>
                    <span>{{ __('gallery_actions_edit') }}</span>
                </button>
                @if(in_array('delete', $listPermissions) || Auth::user()->can('delete', $project))
                    <form action="{{ route('image.delete', $image->id) }}" method="POST" class="pointer-events-auto">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                onclick="return confirm('{{ __('message_delete_confirm') }}')"
                                title="{{ __('delete_image') }}"
                                class="inline-flex size-8 items-center justify-center rounded bg-white/90 text-danger hover:bg-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                            <x-icon name="trash-2" size="3"/>
                        </button>
                    </form>
                @endif
            </div>
        @endcan
    </div>

    <div class="mt-1.5 truncate text-body">
        @if (! empty(trim($image->alt ?? '')))
            <span class="text-ink-900">{{ $image->alt }}</span>
        @else
            <span class="italic text-ink-500">{{ __('gallery_image_untitled') }}</span>
        @endif
    </div>

    @can('update', $project)
        {{-- Angaben-Status: weiches Pflichtfeld pro Bild (Briefing § 5).
             Zeigt „Angaben vollstaendig" oder eine namentliche Warnung. --}}
        @php
            $missing = collect([
                ! empty(trim(strip_tags((string) $image->description))) ? null : __('gallery_image_description'),
                $image->copyrightImage ? null : __('copyright'),
                $image->originImage ? null : __('origin'),
            ])->filter()->values();
        @endphp
        @if ($missing->isEmpty())
            <p class="mt-1 text-caption text-success">✓ {{ __('gallery_status_complete') }}</p>
        @else
            <p class="mt-1 text-caption text-warning">⚠ {{ __('gallery_status_missing', ['fields' => $missing->implode(', ')]) }}</p>
        @endif
    @endcan
</div>
