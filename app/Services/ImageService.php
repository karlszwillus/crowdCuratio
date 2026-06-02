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

use App\Data\ImageData;
use App\Models\Comment;
use App\Models\Image;
use App\Models\MediaContent;
use App\Traits\UploadTrait;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Kapselt die Schreibpfade auf Image-Modelle (Block F.4).
 *
 * Übernimmt die `saveImage`-/`updateImage`-Logik aus dem
 * `ContentController`: Source-Lookups über den `SourceService`,
 * File-Upload via `UploadTrait`, Gallery-Positionierung.
 *
 * `destroy` nutzt heute den `DB::table()`-basierten
 * Soft-Delete-Bypass (NF-LAR-009). F.7 wird das auf Eloquent +
 * Soft-Delete-Trait umstellen.
 */
class ImageService
{
    use UploadTrait;

    public function __construct(
        private readonly SourceService $sources,
    ) {}

    /**
     * Legt ein neues Image in einer Gallery an. Lädt den File auf
     * die `public`-Disk, generiert einen zeitbasierten Dateinamen
     * und setzt die Position als max+1 innerhalb der Gallery.
     */
    public function create(ImageData $data, UploadedFile $file, int $galleryId): Image
    {
        $name = $this->uploadImageFile($file);

        $position = Image::where('gallery_id', $galleryId)
            ->orderByDesc('position')
            ->value('position');

        $copyright = $this->sources->findOrCreateId($data->copyrightName, 'Copyright');
        $origin = $this->sources->findOrCreateId($data->originName, 'Origin');

        return Image::firstOrCreate([
            'gallery_id' => $galleryId,
            'image' => $name,
            'position' => ($position ?? 0) + 1,
            'origin' => $origin,
            'copyright' => $copyright,
            'url' => Storage::path($name),
            'alt' => $data->altText,
        ]);
    }

    /**
     * Aktualisiert ein bestehendes Image. Wenn ein neuer File
     * übergeben wird, wird er hochgeladen und die `image`-/`url`-
     * Felder werden überschrieben; sonst bleibt das Bild
     * unverändert und nur die Metadaten (Source, Alt) werden
     * aktualisiert.
     */
    public function update(Image $image, ImageData $data, ?UploadedFile $newFile = null): Image
    {
        $copyright = $this->sources->findOrCreateId($data->copyrightName, 'Copyright');
        $origin = $this->sources->findOrCreateId($data->originName, 'Origin');

        if ($newFile !== null) {
            $name = $this->uploadImageFile($newFile);
            $image->image = $name;
            $image->url = Storage::path($name);
        }

        $image->origin = $origin;
        $image->copyright = $copyright;

        if ($data->altText !== null) {
            $image->alt = $data->altText;
        }

        $image->updated_at = now();
        $image->save();

        return $image;
    }

    /**
     * Soft-deleted das Image und seine zugehörigen Comment-/
     * MediaContent-Einträge. Aktuell über `DB::update`-Bypass —
     * F.7 stellt auf Eloquent-SoftDeletes um.
     */
    public function destroy(Image $image): void
    {
        $this->detachFromEntries($image->id);

        $image->delete();
    }

    /**
     * File-Upload nach `/uploads/images/` auf der `public`-Disk.
     * Liefert den generierten Dateinamen zurück (zeitbasiert,
     * konsistent zum alten setImage-Pattern).
     */
    private function uploadImageFile(UploadedFile $file): string
    {
        $name = date('Ymd').'_'.time().'.'.$file->extension();
        $folder = '/uploads/images/';

        $this->uploadOne($file, $folder, 'public', $name);

        return $name;
    }

    /**
     * Soft-deleted Comment- und MediaContent-Einträge eines
     * Images via Eloquent (NF-LAR-009-Fix).
     */
    private function detachFromEntries(int $imageId): void
    {
        Comment::where('commentable_id', $imageId)
            ->where('commentable_type', Image::class)
            ->delete();

        MediaContent::where('media_contentable_id', $imageId)
            ->where('media_contentable_type', Image::class)
            ->delete();
    }
}
