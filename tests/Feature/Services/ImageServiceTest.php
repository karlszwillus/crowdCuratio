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

use App\Data\ImageData;
use App\Models\Image;
use App\Services\ImageService;
use App\Services\SourceService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| ImageService
|--------------------------------------------------------------------------
|
| Deckt die drei Schreibpfade ab: create (Upload + Source-Lookup
| + Positionierung in der Gallery), update (mit und ohne neuen
| File), destroy (Soft-Delete).
*/

beforeEach(function () {
    Storage::fake('public');
});

function imageService(): ImageService
{
    return new ImageService(new SourceService);
}

it('create lädt das File hoch und legt ein Image in der Gallery an', function () {
    $gallery = makeGallery();
    $file = UploadedFile::fake()->image('test.jpg');

    $data = new ImageData(
        originName: 'Test-Origin',
        copyrightName: 'Test-Copyright',
        altText: 'Mein Alt-Text',
    );

    $image = imageService()->create($data, $file, $gallery->id);

    expect($image->id)->toBeInt();
    expect($image->gallery_id)->toBe($gallery->id);
    expect($image->image)->toEndWith('.jpg');
    expect($image->alt)->toBe('Mein Alt-Text');
    expect($image->originImage->name)->toBe('Test-Origin');
    expect($image->copyrightImage->name)->toBe('Test-Copyright');

    Storage::disk('public')->assertExists('/uploads/images/'.$image->image);
});

it('create setzt position als max+1 innerhalb der Gallery', function () {
    $gallery = makeGallery();
    makeImage(['gallery_id' => $gallery->id, 'position' => 4]);

    $file = UploadedFile::fake()->image('next.jpg');
    $data = new ImageData(originName: 'O', copyrightName: 'C');

    $next = imageService()->create($data, $file, $gallery->id);

    expect($next->position)->toBe(5);
});

it('update aktualisiert nur Source-Refs ohne neuen File', function () {
    $image = makeImage(['image' => 'original.jpg', 'alt' => 'Alt-Alt']);
    $originalImageName = $image->image;

    $data = new ImageData(
        originName: 'Neue Quelle',
        copyrightName: 'Neues Copyright',
        altText: 'Neuer Alt',
    );

    $updated = imageService()->update($image, $data);
    $updated->refresh();

    expect($updated->image)->toBe($originalImageName);
    expect($updated->alt)->toBe('Neuer Alt');
    expect($updated->originImage->name)->toBe('Neue Quelle');
    expect($updated->copyrightImage->name)->toBe('Neues Copyright');
});

it('update überschreibt image und url, wenn ein neuer File übergeben wird', function () {
    $image = makeImage(['image' => 'original.jpg']);

    $newFile = UploadedFile::fake()->image('new.png');
    $data = new ImageData(originName: 'O', copyrightName: 'C');

    $updated = imageService()->update($image, $data, $newFile);
    $updated->refresh();

    expect($updated->image)->not->toBe('original.jpg');
    expect($updated->image)->toEndWith('.png');
});

it('destroy soft-deleted das Image', function () {
    $image = makeImage();

    imageService()->destroy($image);

    expect(Image::find($image->id))->toBeNull();
    expect(Image::withTrashed()->find($image->id))->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Q4-Etappe 6 · G6-1: intrinsic_width / intrinsic_height
|--------------------------------------------------------------------------
|
| Die neuen Darstellungs-Hinweise werden beim Upload aus dem Bild
| gelesen und persistiert. `UploadedFile::fake()->image($name, $w, $h)`
| erzeugt ein synthetisches Bild mit exakten Dimensionen; damit können
| wir prüfen, dass der Reader hinterher die Fläche vor dem Laden
| reservieren kann.
*/

it('create persistiert intrinsic_width und intrinsic_height aus dem Upload', function () {
    $gallery = makeGallery();
    $file = UploadedFile::fake()->image('test.jpg', 800, 600);

    $data = new ImageData(originName: 'O', copyrightName: 'C');

    $image = imageService()->create($data, $file, $gallery->id);

    expect($image->intrinsic_width)->toBe(800);
    expect($image->intrinsic_height)->toBe(600);
});

it('createFromDrop persistiert intrinsic_width und intrinsic_height', function () {
    $gallery = makeGallery();
    $file = UploadedFile::fake()->image('drop.jpg', 1200, 900);

    $image = imageService()->createFromDrop($file, $gallery->id);

    expect($image->intrinsic_width)->toBe(1200);
    expect($image->intrinsic_height)->toBe(900);
});

it('update aktualisiert intrinsic_width und intrinsic_height, wenn ein neuer File übergeben wird', function () {
    $image = makeImage([
        'image' => 'original.jpg',
        'intrinsic_width' => 100,
        'intrinsic_height' => 100,
    ]);

    $newFile = UploadedFile::fake()->image('new.png', 640, 480);
    $data = new ImageData(originName: 'O', copyrightName: 'C');

    $updated = imageService()->update($image, $data, $newFile);
    $updated->refresh();

    expect($updated->intrinsic_width)->toBe(640);
    expect($updated->intrinsic_height)->toBe(480);
});

it('update lässt intrinsic_width und intrinsic_height unverändert ohne neuen File', function () {
    $image = makeImage([
        'intrinsic_width' => 800,
        'intrinsic_height' => 600,
    ]);

    $data = new ImageData(originName: 'X', copyrightName: 'Y');

    $updated = imageService()->update($image, $data);
    $updated->refresh();

    expect($updated->intrinsic_width)->toBe(800);
    expect($updated->intrinsic_height)->toBe(600);
});

it('Image castet no_crop als bool, focus_x/y und intrinsic_* als int', function () {
    $image = makeImage([
        'no_crop' => 1,
        'focus_x' => 42,
        'focus_y' => 58,
        'intrinsic_width' => 1024,
        'intrinsic_height' => 768,
    ]);
    $image->refresh();

    expect($image->no_crop)->toBeBool()->toBeTrue();
    expect($image->focus_x)->toBeInt()->toBe(42);
    expect($image->focus_y)->toBeInt()->toBe(58);
    expect($image->intrinsic_width)->toBeInt()->toBe(1024);
    expect($image->intrinsic_height)->toBeInt()->toBe(768);
});

it('Image no_crop ist per Default false', function () {
    $image = makeImage();
    $image->refresh();

    expect($image->no_crop)->toBeFalse();
    expect($image->focus_x)->toBeNull();
    expect($image->focus_y)->toBeNull();
});
