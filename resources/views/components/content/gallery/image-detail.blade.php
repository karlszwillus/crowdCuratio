@props(['image', 'item', 'project', 'listPermissions', 'position', 'total'])

{{--
    Q4-Etappe 2 / I5 (2026-08-27): Image-Detail-Row im Gallery-Block,
    extrahiert aus components/content/gallery-block.blade.php. Wird per
    x-show gefiltert (editingImageId === image.id) sichtbar geschaltet.

    Alpine-State (`editingImageId`, `exitDetail()`) kommt aus dem
    umschliessenden x-data des Gallery-Blocks — die Sub-Component sitzt
    im DOM-Scope davon.

    Props:
    - $image, $item, $project, $listPermissions
    - $position, $total  aus dem umschliessenden $loop
--}}

<div
    x-show="editingImageId === {{ $image->id }}"
    x-cloak
    data-image-id="{{ $image->id }}"
    data-history-subject="Image:{{ $image->id }}"
    @keydown.escape.window="exitDetail()"
    class="gallery-detail-row mt-4 rounded-md border border-line-200 bg-paper-50 p-4"
>
    <header class="mb-4 flex items-center justify-between gap-3">
        <button type="button"
                @click="exitDetail()"
                class="inline-flex items-center gap-1 text-body text-primary hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
            <x-icon name="chevron-left" size="4"/>
            <span>{{ __('gallery_back_to_grid') }}</span>
        </button>
        <span class="text-caption text-ink-500">
            {{ __('gallery_image_n_of_m', ['n' => $position, 'm' => $total]) }}
        </span>
        <div class="flex items-center gap-1">
            {{-- A7-Followup (2026-08-21): Verlauf-Trigger fuer
                 die Bild-Metadaten (alt, description, origin,
                 copyright). Image ist ein eigenes revidiertes
                 Subject; der Kurator kommt hier direkt zur
                 Bild-Historie. --}}
            <x-ui.history-trigger
                subjectType="Image"
                :subjectId="$image->id"
            />
            <button type="button"
                    @click="exitDetail()"
                    title="{{ __('close') }}"
                    class="inline-flex size-11 items-center justify-center rounded-md text-ink-500 hover:bg-line-100 hover:text-ink-900">
                <x-icon name="x" size="4"/>
            </button>
        </div>
    </header>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        {{-- Vorschau --}}
        <div>
            <div class="gallery-detail-preview relative flex aspect-video items-center justify-center overflow-hidden rounded-md bg-line-100" data-image-id="{{ $image->id }}">
                <img src="{{ route('image', $image->image) }}"
                     alt="{{ $image->alt }}"
                     class="max-h-full max-w-full object-contain"/>
            </div>
        </div>

        {{-- Vier Felder: Titel · Bildbeschreibung · Urheberrecht · Quelle. --}}
        <div class="grid grid-cols-1 gap-4">
            <div data-history-field="alt">
                <label class="mb-1 block text-caption font-medium text-ink-700">{{ __('title') }}</label>
                <livewire:inline-editor
                    :model="$image"
                    field="alt"
                    rules="nullable|string|max:255"
                    :key="'image-detail-alt-'.$image->id"
                />
                <p class="mt-1 text-caption text-ink-500">{{ __('gallery_field_title_hint') }}</p>
            </div>
            <div data-history-field="description">
                <label class="mb-1 block text-caption font-medium text-ink-700">
                    {{ __('gallery_image_description') }} <span class="text-danger" aria-hidden="true">*</span>
                </label>
                <livewire:inline-editor
                    :model="$image"
                    field="description"
                    rules="nullable|string|max:2000"
                    :key="'image-detail-description-'.$image->id"
                />
                <p class="mt-1 text-caption text-ink-500">{{ __('gallery_field_description_hint') }}</p>
            </div>
            <div data-history-field="copyright">
                <label class="mb-1 block text-caption font-medium text-ink-700">
                    {{ __('copyright') }} <span class="text-danger" aria-hidden="true">*</span>
                </label>
                <livewire:source-picker
                    :model="$image"
                    field="copyright"
                    relation="copyrightImage"
                    source-type="Copyright"
                    :label="__('copyright')"
                    :key="'image-detail-copyright-'.$image->id" />
            </div>
            <div data-history-field="origin">
                <label class="mb-1 block text-caption font-medium text-ink-700">
                    {{ __('origin') }} <span class="text-danger" aria-hidden="true">*</span>
                </label>
                <livewire:source-picker
                    :model="$image"
                    field="origin"
                    relation="originImage"
                    source-type="Origin"
                    :label="__('origin')"
                    :key="'image-detail-origin-'.$image->id" />
            </div>
        </div>
    </div>
</div>
