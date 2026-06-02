<?php

/**
crowdCuratio - Curating together virtually
Copyright (C)2026 - berlinHistory e.V.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program in the file LICENSE.

If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Services;

use App\Data\GalleryData;
use App\Models\Comment;
use App\Models\Gallery;
use App\Models\Image;
use App\Models\MediaContent;

/**
 * Kapselt die Schreibpfade auf Gallery-Modelle (Block F.5).
 *
 * Übernimmt die `saveGallery`-Logik aus dem `ContentController`
 * und fixt den latenten Inkonsistenz-Bug im Update-Pfad: vorher
 * las der Direct-Pfad `$request['title']` etc., das Frontend
 * schickt aber nur `galleryTitle` etc. Im DTO normalisiert,
 * Service nutzt nur noch das DTO.
 *
 * `destroy` nutzt heute den `DB::table()`-basierten
 * Soft-Delete-Bypass (NF-LAR-009). F.7 stellt das auf Eloquent +
 * Soft-Delete-Trait um.
 */
class GalleryService
{
    /**
     * Legt eine neue Gallery an und hängt sie per MediaContent an
     * einen Entry. attachMedia setzt die Position als max+1
     * innerhalb des Entry.
     */
    public function create(GalleryData $data, int $entryId): Gallery
    {
        $gallery = Gallery::create([
            'title' => $data->title,
            'subtitle' => $data->subtitle,
            'description' => $data->description,
        ]);

        $this->attachToEntry($gallery->id, $entryId);

        return $gallery;
    }

    /**
     * Aktualisiert eine bestehende Gallery. Je nach
     * `isTranslation`-Flag werden die Felder direkt gesetzt oder
     * via setTranslation('en', ...) als englische Übersetzung.
     */
    public function update(Gallery $gallery, GalleryData $data): Gallery
    {
        if ($data->isTranslation) {
            $gallery->setTranslation('title', 'en', $data->title ?? '');
            $gallery->setTranslation('subtitle', 'en', $data->subtitle ?? '');
            $gallery->setTranslation('description', 'en', $data->description ?? '');
        } else {
            $gallery->title = $data->title;
            $gallery->subtitle = $data->subtitle;
            $gallery->description = $data->description;
        }

        $gallery->is_translated = $data->isTranslated;
        $gallery->save();

        return $gallery;
    }

    /**
     * Soft-deleted Gallery + zugehörige Images +
     * Comment-/MediaContent-Einträge. Aktuell über
     * `DB::update`-Bypass — F.7 stellt auf Eloquent-SoftDeletes
     * um.
     */
    public function destroy(Gallery $gallery): void
    {
        $this->detachFromEntries($gallery->id);

        Image::where('gallery_id', $gallery->id)
            ->update(['deleted_at' => now()]);

        $gallery->delete();
    }

    /**
     * MediaContent-Eintrag für die Gallery auf einen Entry, mit
     * `Image` als content-Type — historisch (saveGallery hatte
     * `'App\Models\Image'` hartkodiert).
     */
    private function attachToEntry(int $galleryId, int $entryId): void
    {
        $lastPosition = MediaContent::where('media_contentable_id', $entryId)
            ->orderByDesc('position')
            ->value('position');

        MediaContent::create([
            'position' => ($lastPosition ?? 0) + 1,
            'media_content_id' => $galleryId,
            'media_contentable_id' => $entryId,
            'media_contentable_type' => Image::class,
        ]);
    }

    /**
     * Soft-deleted Comment- und MediaContent-Einträge einer
     * Gallery. F.7 stellt auf Eloquent-SoftDeletes um.
     */
    private function detachFromEntries(int $galleryId): void
    {
        Comment::where('commentable_id', $galleryId)
            ->where('commentable_type', Gallery::class)
            ->update(['deleted_at' => now()]);

        MediaContent::where('media_contentable_id', $galleryId)
            ->where('media_contentable_type', Gallery::class)
            ->update(['deleted_at' => now()]);
    }
}
