<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

See LICENSE.
 */

use App\Models\Gallery;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

/**
 * Q4-Etappe 6 · G6-2 (2026-09-10): Sequenz-Toggle am Galerie-Block.
 *
 * Der Toggle ist die einzige redaktionelle Aussage über die
 * Darstellung — alle anderen Formen (Band, Kontaktbogen) folgen
 * automatisch aus der Bildanzahl. Deshalb keine Formauswahl,
 * sondern genau ein Schalter.
 *
 * Inline speichern ohne Redirect — konsistent zu quote-kind-
 * selector und data-facts-layout-selector.
 *
 * Nur eine Richtung: Sequenz ist eine Ansage; einmal gesetzt
 * bleibt sie, bis der Redakteur sie bewusst zurücknimmt. Der
 * Designer-Briefing empfiehlt ausdrücklich, keine Rückumschaltung
 * auf Bogen zu bauen — deshalb dispatched der Toggle nur ein
 * Event, keine „andere Richtung"-Konfiguration.
 */
new class extends Component
{
    #[Locked]
    public int $galleryId;

    public bool $value = false;

    public function mount(int $galleryId, bool $value = false): void
    {
        $this->galleryId = $galleryId;
        $this->value = $value;
    }

    public function save(): void
    {
        $gallery = Gallery::findOrFail($this->galleryId);
        Gate::authorize('update', $gallery);

        $gallery->sequence = $this->value;
        $gallery->save();

        // Editor-Ansicht: die Kopfzeile (Status-Pille + Scope-Hint)
        // reagiert live. Wir dispatchen ein Alpine-lesbares Event
        // mit galleryId + neuem sequence-Wert; der Kopf-Partial
        // hört darauf und schaltet die Anzeige um, ohne dass der
        // ganze Block re-rendert.
        $this->dispatch(
            'gallery-sequence-changed',
            galleryId: $this->galleryId,
            sequence: $gallery->sequence,
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
        <span class="block text-body font-medium text-ink-900">{{ __('gallery_sequence_toggle_label') }}</span>
        <span class="block text-caption text-ink-500">{{ __('gallery_sequence_toggle_hint') }}</span>
    </span>
</label>
