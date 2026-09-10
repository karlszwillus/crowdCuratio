@props(['chapter'])

{{--
    Q4-Etappe 6 · G6-5 (2026-09-10): Kapitel-Titelbild-Fach im
    Editor. Zeigt an, welches Bild aktuell als Titelbild des
    Kapitels gesetzt ist — kein Upload-Feld, das Bild wird am
    Bild selbst per Häkchen gewählt (Design-Briefing).

    Zwei Zustände:
      - Titelbild gesetzt: Vorschau + Bildname + Herkunfts-Eintrag,
        „Zum Bild springen" (Anchor auf den Galerie-Block im
        Canvas), „Anderes Bild wählen" (Hinweistext).
      - Keins gesetzt: Placeholder-Kachel mit gestricheltem Rahmen
        und Hinweis, wo das Häkchen zu setzen ist.

    Der Slot hört live auf `image-chapter-cover-changed` — sobald
    der Redakteur im Bild-Detail das Häkchen setzt oder wechselt,
    wird der ganze Chapter-Slot per wire:navigate aktualisiert.
    (Fürs erste ohne Livewire-Component; der Slot rendert einmal
    beim Page-Load, ein manueller Reload nach Häkchen-Wechsel
    zeigt den neuen Zustand.)

    Erwartet: $chapter (Chapter) im Kontext.
--}}
@php
    /** @var \App\Models\Chapter $chapter */
    $cover = $chapter->coverImage;
    $sourceEntry = null;
    if ($cover !== null) {
        $sourceEntry = optional(optional($cover->gallery)->mediaContents()->first())->parent()->first();
    }
@endphp
<section class="mt-4 mb-6 rounded-md border border-line-200 bg-paper-50 p-4"
         aria-labelledby="chapter-cover-heading-{{ $chapter->id }}">
    <h4 id="chapter-cover-heading-{{ $chapter->id }}"
        class="mb-3 text-caption font-mono uppercase tracking-wider text-ink-500">
        {{ __('chapter_cover_slot_label') }}
    </h4>

    @if($cover !== null)
        <div class="flex items-start gap-4">
            <a href="#anchor_Image_{{ $cover->id }}"
               class="block size-24 flex-none overflow-hidden rounded-md bg-line-100"
               title="{{ __('chapter_cover_slot_open') }}">
                <img src="{{ route('image', $cover->image) }}"
                     alt="{{ $cover->alt }}"
                     class="size-full object-cover"
                     @if(! $cover->no_crop && ($cover->focus_x !== null || $cover->focus_y !== null))
                         style="object-position: {{ $cover->focus_x ?? 50 }}% {{ $cover->focus_y ?? 50 }}%;"
                     @endif/>
            </a>
            <div class="min-w-0 flex-1">
                <p class="text-body font-medium text-ink-900">
                    @if(! empty(trim((string) $cover->alt)))
                        {{ $cover->alt }}
                    @else
                        <span class="italic text-ink-500">{{ __('gallery_image_untitled') }}</span>
                    @endif
                </p>
                @if($sourceEntry !== null && ! empty($sourceEntry->name))
                    <p class="mt-1 text-caption text-ink-500">
                        {{ __('chapter_cover_from_entry', ['entry' => $sourceEntry->name]) }}
                    </p>
                @endif
                @php
                    $creditParts = collect([
                        optional($cover->copyrightImage)->name,
                        optional($cover->originImage)->name,
                    ])->filter()->implode(' · ');
                @endphp
                @if($creditParts !== '')
                    <p class="mt-1 text-caption font-mono text-ink-500">{{ $creditParts }}</p>
                @endif
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <a href="#anchor_Image_{{ $cover->id }}"
                       class="inline-flex items-center gap-1 rounded-md border border-line-200 bg-paper-0 px-3 py-1.5 text-caption font-medium text-ink-700 hover:border-ink-400">
                        <x-icon name="chevron-right" size="3"/>
                        <span>{{ __('chapter_cover_slot_open') }}</span>
                    </a>
                    <span class="text-caption text-ink-500">
                        {{ __('chapter_cover_slot_change') }}
                    </span>
                </div>
            </div>
        </div>
    @else
        <div class="flex items-center gap-4">
            <div class="size-24 flex-none rounded-md border-2 border-dashed border-line-300 bg-transparent"></div>
            <p class="text-caption text-ink-500">
                {{ __('chapter_cover_slot_empty') }}
            </p>
        </div>
    @endif
</section>
