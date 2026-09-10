<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

See LICENSE.
 */

use App\Models\Image;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

/**
 * Q4-Etappe 6 · G6-3 (2026-09-10): „Nicht beschneiden"-Toggle am
 * Bild. Setzt `image.no_crop` — im Kontaktbogen (ab 5 Bildern)
 * wird das Bild dann eingepasst statt gefüllt, damit Dokumente,
 * Scans und Karten nicht ihre Ränder verlieren.
 *
 * Inline speichern ohne Redirect — konsistent zu quote-kind-
 * selector, data-facts-layout-selector, gallery-sequence-toggle.
 */
new class extends Component
{
    #[Locked]
    public int $imageId;

    public bool $value = false;

    public function mount(int $imageId, bool $value = false): void
    {
        $this->imageId = $imageId;
        $this->value = $value;
    }

    public function save(): void
    {
        $image = Image::findOrFail($this->imageId);
        Gate::authorize('update', $image);

        $image->no_crop = $this->value;
        $image->save();

        // Alpine-Event für den Fokus-Picker: bei no_crop=true wird
        // der Fokus-Klick sinnlos (das Bild wird ohnehin komplett
        // gezeigt) — der Picker deaktiviert sich dann visuell.
        $this->dispatch(
            'image-no-crop-changed',
            imageId: $this->imageId,
            noCrop: $image->no_crop,
        );
    }
};
?>

<label class="inline-flex items-start gap-3">
    <input type="checkbox"
           wire:model.live="value"
           wire:change="save"
           class="mt-1 size-4 rounded border-line-300 text-primary focus:ring-primary"/>
    <span class="min-w-0">
        <span class="block text-body font-medium text-ink-900">{{ __('image_no_crop_label') }}</span>
        <span class="block text-caption text-ink-500">{{ __('image_no_crop_hint') }}</span>
    </span>
</label>
