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

use App\Models\Audiovisual;
use App\Models\Entry;
use App\Models\Gallery;
use App\Models\Image;
use App\Models\MediaContent;
use App\Models\Project;
use App\Models\Text;
use App\Models\User;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Content-Modelle — project()-Navigation (E.7b Sub-Welle 2c, ADR-0022)
|--------------------------------------------------------------------------
|
| Pinnt das Verhalten der `project()`-Methode auf den vier Content-
| Modellen. Vorbereitung für die Policies in E.7b Welle 3
| (TextPolicy/ImagePolicy/GalleryPolicy/AudiovisualPolicy auf
| OwnerScopedPolicy). Die Methode navigiert vom Content über den
| MediaContent-Pivot zum Entry → Chapter → Project — bei Image
| über die Gallery (gallery_id-FK).
*/

function attachContentToEntry(string $contentClass, int $contentId, Entry $entry): MediaContent
{
    // E.7b 4e (ADR-0022): alte media_contentable_*-Spalten gedroppt.
    return MediaContent::create([
        'content_id' => $contentId,
        'content_type' => $contentClass,
        'parent_id' => $entry->id,
        'parent_type' => Entry::class,
        'position' => 0,
    ]);
}

it('Text::project() liefert das Project über Pivot → Entry → Chapter → Project', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $entry = makeEntry(makeChapter($project));
    $text = Text::factory()->create();

    attachContentToEntry(Text::class, $text->id, $entry);

    /** @var Project $resolved */
    $resolved = $text->project();
    expect($resolved)->toBeInstanceOf(Project::class);
    expect($resolved->id)->toBe($project->id);
});

it('Text::project() liefert null, wenn der Text noch nicht an einen Entry gehängt ist', function () {
    /** @var TestCase $this */
    $text = Text::factory()->create();

    expect($text->project())->toBeNull();
});

it('Audiovisual::project() liefert das Project über Pivot → Entry → Chapter → Project', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $entry = makeEntry(makeChapter($project));
    $av = Audiovisual::factory()->create();

    attachContentToEntry(Audiovisual::class, $av->id, $entry);

    /** @var Project $resolved */
    $resolved = $av->project();
    expect($resolved)->toBeInstanceOf(Project::class);
    expect($resolved->id)->toBe($project->id);
});

it('Gallery::project() liefert das Project über Pivot → Entry → Chapter → Project', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $entry = makeEntry(makeChapter($project));
    $gallery = Gallery::factory()->create();

    attachContentToEntry(Gallery::class, $gallery->id, $entry);

    /** @var Project $resolved */
    $resolved = $gallery->project();
    expect($resolved)->toBeInstanceOf(Project::class);
    expect($resolved->id)->toBe($project->id);
});

it('Image::project() navigiert indirekt über die Gallery (gallery_id-FK)', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $entry = makeEntry(makeChapter($project));
    $gallery = Gallery::factory()->create();
    /** @var Image $image */
    $image = Image::factory()->create(['gallery_id' => $gallery->id]);

    // Die Gallery hängt am Entry, das Image hängt an der Gallery.
    attachContentToEntry(Gallery::class, $gallery->id, $entry);

    /** @var Project $resolved */
    $resolved = $image->project();
    expect($resolved)->toBeInstanceOf(Project::class);
    expect($resolved->id)->toBe($project->id);
});

it('Image::project() liefert null, wenn keine Gallery verknüpft ist', function () {
    /** @var TestCase $this */
    /** @var Image $image */
    $image = Image::factory()->create(['gallery_id' => null]);

    expect($image->project())->toBeNull();
});
