@props(['gallery', 'project'])

{{--
    Q4-Etappe 6 · G6-2 (2026-09-10): Kopfpanel des Galerie-Blocks
    im Editor. Zeigt drei Sachen:
      - Status-Pille „Erscheint als …" (nicht klickbar; Status,
        kein Knopf — die Form ergibt sich aus der Bildanzahl).
      - Formwechsel-Hint an der Kante („Ein weiteres Bild würde
        auf Kontaktbogen wechseln — Einzelnachweise wandern in
        die Großansicht").
      - Sequenz-Toggle (Livewire) — die EINE redaktionelle Aussage.

    Der Partial lauscht live auf `gallery-sequence-changed` (aus
    dem Sequenz-Toggle) und aktualisiert Pille und Hint clientseitig,
    damit die Anzeige direkt reagiert, ohne den ganzen Block zu
    re-rendern.

    Erwartet: $gallery (Gallery) und $project (Project) im Kontext.
--}}
@php
    /** @var \App\Models\Gallery $gallery */
    $imgCount = $gallery->images->count();
    $sequence = (bool) $gallery->sequence;
    $currentForm = \App\Support\GalleryForm::resolve($imgCount, $sequence);
    $nextIfIncrement = $currentForm->nextFormIfIncrement($imgCount, $sequence);
    $nextIfDecrement = $currentForm->nextFormIfDecrement($imgCount, $sequence);

    // Hint-Text: was passiert beim nächsten Upload/Löschen?
    $hintKey = null;
    if ($nextIfIncrement === \App\Support\GalleryForm::KONTAKTBOGEN) {
        $hintKey = 'gallery_form_hint_next_kontaktbogen';
    } elseif ($nextIfDecrement === \App\Support\GalleryForm::BAND) {
        $hintKey = 'gallery_form_hint_next_band';
    } elseif ($nextIfIncrement === null && $nextIfDecrement === null) {
        $hintKey = 'gallery_form_hint_stable';
    }
@endphp

<div class="mt-4 rounded-md border border-line-200 bg-paper-50 p-4"
     x-data="{
        imgCount: {{ $imgCount }},
        sequence: @js($sequence),
    }"
    x-on:gallery-sequence-changed.window="
        if ($event.detail.galleryId === {{ $gallery->id }}) {
            sequence = $event.detail.sequence;
        }
    ">
    <div class="flex flex-wrap items-start gap-3">
        <div class="flex-1 min-w-0">
            <div class="text-caption font-mono uppercase tracking-wider text-ink-500">
                {{ __('gallery_form_panel_label') }}
            </div>
            <div class="mt-1 flex flex-wrap items-baseline gap-x-3 gap-y-1">
                <span class="inline-flex items-center gap-2 rounded-full bg-primary-bg px-3 py-1 text-caption font-medium text-primary">
                    <span class="size-2 rounded-full bg-primary"></span>
                    <span>{{ $currentForm->label() }}</span>
                </span>
                <span class="text-caption text-ink-500">{{ $currentForm->scopeHint() }}</span>
            </div>
            @if($hintKey)
                <p class="mt-2 text-caption text-ink-600">{{ __($hintKey) }}</p>
            @endif
        </div>

        @can('update', $project)
            <div class="w-full sm:w-auto sm:max-w-[320px] sm:border-l sm:border-line-200 sm:pl-4">
                @livewire('gallery-sequence-toggle', [
                    'galleryId' => $gallery->id,
                    'value' => $sequence,
                ], key('gallery-sequence-toggle-'.$gallery->id))
            </div>
        @endcan
    </div>
</div>
