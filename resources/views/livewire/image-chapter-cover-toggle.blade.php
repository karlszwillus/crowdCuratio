<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

See LICENSE.
 */

use App\Models\Chapter;
use App\Models\Entry;
use App\Models\Image;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

/**
 * Q4-Etappe 6 · G6-5 (2026-09-10): Kapitel-Titelbild-Toggle.
 *
 * Häkchen am Bild („Als Titelbild für Kapitel X verwenden"),
 * inline speichern. Der Toggle löst die Chapter-Navigation über
 * gallery → mediaContent → entry → chapter selbst auf — das Bild
 * kennt sein Kapitel nicht direkt.
 *
 * Genau eins pro Kapitel: setzt der Redakteur das Häkchen an
 * einem zweiten Bild desselben Kapitels, wandert das
 * `cover_image_id` auf das neue Bild und die Volt-Komponente
 * dispatched `image-chapter-cover-swapped` mit dem Namen des
 * vorher gesetzten Bildes, damit der Editor die Änderung benannt
 * anzeigen kann („Titelbild von Straßenansicht X übernommen").
 *
 * Ohne Häkchen: Kapitel hat kein Titelbild, Kapitelkarte
 * erscheint ohne Bildfläche (kein Automatikbild aus dem ersten
 * Eintrag — genau der leere Beige-Kasten aus Review 3 fällt
 * damit weg).
 */
new class extends Component
{
    #[Locked]
    public int $imageId;

    public bool $value = false;

    public ?string $chapterName = null;

    public function mount(int $imageId, bool $value = false): void
    {
        $this->imageId = $imageId;
        $this->value = $value;

        // Kapitel-Name für die Toggle-Beschriftung („Als Titelbild
        // für X verwenden") — einmalig beim Mount, damit die Zeile
        // nicht durch das Livewire-Update flackert.
        $this->chapterName = $this->resolveChapter($imageId)?->name;
    }

    public function save(): void
    {
        $image = Image::findOrFail($this->imageId);
        Gate::authorize('update', $image);

        $chapter = $this->resolveChapter($this->imageId);
        if ($chapter === null) {
            // Bild hängt an keinem Kapitel — Toggle ohne Effekt,
            // Zustand zurücksetzen, damit UI sauber bleibt.
            $this->value = false;

            return;
        }

        Gate::authorize('update', $chapter);

        if ($this->value) {
            $previousImageId = $chapter->cover_image_id;
            $previousImageName = null;
            if ($previousImageId !== null && $previousImageId !== $image->id) {
                $previousImageName = Image::withTrashed()
                    ->where('id', $previousImageId)
                    ->value('alt');
            }

            $chapter->cover_image_id = $image->id;
            $chapter->save();

            if ($previousImageName !== null) {
                $this->dispatch(
                    'image-chapter-cover-swapped',
                    chapterId: $chapter->id,
                    previousImageName: $previousImageName,
                );
            }
        } else {
            // Häkchen entfernen: nur zurücksetzen, wenn wir wirklich
            // das aktuelle Titelbild sind — sonst würden wir das
            // Titelbild eines anderen Bildes im selben Kapitel
            // fälschlich löschen.
            if ($chapter->cover_image_id === $image->id) {
                $chapter->cover_image_id = null;
                $chapter->save();
            }
        }

        $this->dispatch(
            'image-chapter-cover-changed',
            chapterId: $chapter->id,
            imageId: $chapter->cover_image_id,
        );
    }

    private function resolveChapter(int $imageId): ?Chapter
    {
        /** @var Image|null $image */
        $image = Image::find($imageId);
        if ($image === null) {
            return null;
        }
        $gallery = $image->gallery;
        if ($gallery === null) {
            return null;
        }
        /** @var \App\Models\MediaContent|null $mediaContent */
        $mediaContent = $gallery->mediaContents()->first();
        if ($mediaContent === null) {
            return null;
        }
        $parent = $mediaContent->parent()->first();
        if (! $parent instanceof Entry) {
            return null;
        }

        return $parent->chapter;
    }
};
?>

<label class="inline-flex items-start gap-3">
    <input type="checkbox"
           wire:model.live="value"
           wire:change="save"
           class="mt-1 size-4 rounded border-line-300 text-primary focus:ring-primary"/>
    <span class="min-w-0">
        <span class="block text-body font-medium text-ink-900">
            @if($chapterName)
                {{ __('image_chapter_cover_label_with_chapter', ['chapter' => $chapterName]) }}
            @else
                {{ __('image_chapter_cover_label') }}
            @endif
        </span>
        <span class="block text-caption text-ink-500">{{ __('image_chapter_cover_hint') }}</span>
    </span>
</label>
