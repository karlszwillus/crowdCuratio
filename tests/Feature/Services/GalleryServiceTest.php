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

use App\Data\GalleryData;
use App\Models\Gallery;
use App\Models\Image;
use App\Models\MediaContent;
use App\Models\User;
use App\Services\GalleryService;

/*
|--------------------------------------------------------------------------
| GalleryService
|--------------------------------------------------------------------------
|
| Deckt die drei Schreibpfade ab: create (Gallery + MediaContent-
| Attach an Entry), update (direkter Pfad und Translation-Pfad),
| destroy (Soft-Delete von Gallery + Images + Comment-/MediaContent).
|
| Zusätzlich: Test fixiert den F.5-Bug-Fix — der direkte
| Update-Pfad nutzt jetzt galleryTitle aus dem DTO, nicht mehr
| das nicht-existente $request['title'].
*/

function galleryService(): GalleryService
{
    return new GalleryService;
}

it('create legt Gallery mit MediaContent-Attach an Entry an', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $entry = makeEntry($chapter);

    $data = new GalleryData(
        title: 'Test-Gallery',
        subtitle: 'Test-Untertitel',
        description: 'Test-Beschreibung',
    );

    $gallery = galleryService()->create($data, $entry->id);

    expect($gallery->id)->toBeInt();
    expect($gallery->title)->toBe('Test-Gallery');

    $media = MediaContent::where('media_content_id', $gallery->id)
        ->where('media_contentable_id', $entry->id)
        ->where('media_contentable_type', Image::class)
        ->first();
    expect($media)->not->toBeNull();
});

it('update im direkten Pfad schreibt title/subtitle/description aus dem DTO', function () {
    $gallery = makeGallery(['title' => 'Original']);

    $data = new GalleryData(
        title: 'Neuer Titel',
        subtitle: 'Neuer Untertitel',
        description: 'Neue Beschreibung',
        isTranslation: false,
    );

    galleryService()->update($gallery, $data);
    $gallery->refresh();

    expect($gallery->title)->toBe('Neuer Titel');
    expect($gallery->subtitle)->toBe('Neuer Untertitel');
    expect($gallery->description)->toBe('Neue Beschreibung');
});

it('update im Translation-Pfad schreibt setTranslation(en, ...) ohne DE zu überschreiben', function () {
    $gallery = makeGallery(['title' => 'DE-Original']);

    $data = new GalleryData(
        title: 'EN-Title',
        subtitle: 'EN-Subtitle',
        description: 'EN-Description',
        isTranslation: true,
        isTranslated: true,
    );

    galleryService()->update($gallery, $data);
    $gallery->refresh();

    expect($gallery->getTranslation('title', 'en'))->toBe('EN-Title');
    expect($gallery->getTranslation('subtitle', 'en'))->toBe('EN-Subtitle');
    expect($gallery->getTranslation('description', 'en'))->toBe('EN-Description');
    expect($gallery->getTranslation('title', 'de'))->toBe('DE-Original');
    expect((bool) $gallery->is_translated)->toBeTrue();
});

it('destroy soft-deleted Gallery und ihre Images', function () {
    $gallery = makeGallery();
    $image = makeImage(['gallery_id' => $gallery->id]);

    galleryService()->destroy($gallery);

    expect(Gallery::find($gallery->id))->toBeNull();
    expect(Gallery::withTrashed()->find($gallery->id))->not->toBeNull();
    expect(Image::find($image->id))->toBeNull();
    expect(Image::withTrashed()->find($image->id))->not->toBeNull();
});
