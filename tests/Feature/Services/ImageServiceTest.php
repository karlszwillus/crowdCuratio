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
    $this->service = new ImageService(new SourceService);
});

it('create lädt das File hoch und legt ein Image in der Gallery an', function () {
    $gallery = makeGallery();
    $file = UploadedFile::fake()->image('test.jpg');

    $data = new ImageData(
        originName: 'Test-Origin',
        copyrightName: 'Test-Copyright',
        altText: 'Mein Alt-Text',
    );

    $image = $this->service->create($data, $file, $gallery->id);

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

    $next = $this->service->create($data, $file, $gallery->id);

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

    $updated = $this->service->update($image, $data);
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

    $updated = $this->service->update($image, $data, $newFile);
    $updated->refresh();

    expect($updated->image)->not->toBe('original.jpg');
    expect($updated->image)->toEndWith('.png');
});

it('destroy soft-deleted das Image', function () {
    $image = makeImage();

    $this->service->destroy($image);

    expect(Image::find($image->id))->toBeNull();
    expect(Image::withTrashed()->find($image->id))->not->toBeNull();
});
