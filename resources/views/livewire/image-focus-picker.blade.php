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
 * Q4-Etappe 6 · G6-3 (2026-09-10): Beschnitt-Fokus per Klick auf
 * das Vorschaubild. Speichert `focus_x` / `focus_y` in Prozent
 * (0–100) — der Reader nutzt sie im Kontaktbogen als
 * `object-position`, damit z. B. Portraits nicht mittig
 * beschnitten werden, wenn das Gesicht am oberen Bildrand sitzt.
 *
 * Alpine trägt die UI-Logik (Klick, Marker-Position), Livewire
 * persistiert. Bei `no_crop=true` wird der Picker deaktiviert —
 * dann wird das Bild ohnehin komplett gezeigt, ein Fokus ist
 * bedeutungslos.
 */
new class extends Component
{
    #[Locked]
    public int $imageId;

    public ?int $focusX = null;

    public ?int $focusY = null;

    public bool $noCrop = false;

    public string $imageUrl = '';

    public string $imageAlt = '';

    public function mount(int $imageId, ?int $focusX, ?int $focusY, bool $noCrop, string $imageUrl, string $imageAlt = ''): void
    {
        $this->imageId = $imageId;
        $this->focusX = $focusX;
        $this->focusY = $focusY;
        $this->noCrop = $noCrop;
        $this->imageUrl = $imageUrl;
        $this->imageAlt = $imageAlt;
    }

    public function saveFocus(int $x, int $y): void
    {
        $image = Image::findOrFail($this->imageId);
        Gate::authorize('update', $image);

        // Clamp auf 0–100 — verteidigt gegen Zahlen ausserhalb des
        // Bildbereichs (Alpine liefert Prozent, aber Sicherheitsnetz
        // fuer manuelle Aufrufe).
        $image->focus_x = max(0, min(100, $x));
        $image->focus_y = max(0, min(100, $y));
        $image->save();

        $this->focusX = $image->focus_x;
        $this->focusY = $image->focus_y;
    }

    public function clearFocus(): void
    {
        $image = Image::findOrFail($this->imageId);
        Gate::authorize('update', $image);

        $image->focus_x = null;
        $image->focus_y = null;
        $image->save();

        $this->focusX = null;
        $this->focusY = null;
    }
};
?>

<div x-data="{
        focusX: @js($focusX ?? 50),
        focusY: @js($focusY ?? 50),
        hasFocus: @js($focusX !== null && $focusY !== null),
        active: @js(! $noCrop),
        setFocus($event) {
            if (! this.active) return;
            const rect = $event.currentTarget.getBoundingClientRect();
            const x = Math.round(($event.clientX - rect.left) / rect.width * 100);
            const y = Math.round(($event.clientY - rect.top) / rect.height * 100);
            this.focusX = Math.max(0, Math.min(100, x));
            this.focusY = Math.max(0, Math.min(100, y));
            this.hasFocus = true;
            $wire.saveFocus(this.focusX, this.focusY);
        },
        clearFocus() {
            this.hasFocus = false;
            $wire.clearFocus();
        },
    }"
    x-on:image-no-crop-changed.window="
        if ($event.detail.imageId === {{ $imageId }}) {
            active = ! $event.detail.noCrop;
        }
    "
    class="space-y-2">
    <div class="gallery-detail-preview relative flex aspect-video items-center justify-center overflow-hidden rounded-md bg-line-100"
         :class="active ? 'cursor-crosshair' : ''"
         data-image-id="{{ $imageId }}"
         @click="setFocus($event)"
         role="button"
         tabindex="0"
         :aria-label="active ? '{{ __('image_focus_picker_aria_active') }}' : '{{ __('image_focus_picker_aria_inactive') }}'">
        <img src="{{ $imageUrl }}"
             alt="{{ $imageAlt }}"
             class="pointer-events-none max-h-full max-w-full object-contain"/>
        <div x-show="hasFocus && active"
             x-cloak
             class="pointer-events-none absolute size-4 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white bg-primary shadow"
             :style="'left: ' + focusX + '%; top: ' + focusY + '%'"></div>
    </div>
    <div class="flex items-center justify-between gap-2 text-caption text-ink-500">
        <span x-show="active && hasFocus" x-cloak>
            {{ __('image_focus_picker_hint_set') }}
            (<span x-text="focusX"></span>%, <span x-text="focusY"></span>%)
        </span>
        <span x-show="active && ! hasFocus" x-cloak>
            {{ __('image_focus_picker_hint_click') }}
        </span>
        <span x-show="! active" x-cloak class="text-ink-400">
            {{ __('image_focus_picker_hint_inactive') }}
        </span>
        <button type="button"
                x-show="active && hasFocus"
                x-cloak
                @click="clearFocus()"
                class="text-caption text-primary underline decoration-dotted underline-offset-2 hover:no-underline">
            {{ __('image_focus_picker_clear') }}
        </button>
    </div>
</div>
