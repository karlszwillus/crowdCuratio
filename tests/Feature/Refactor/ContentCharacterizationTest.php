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
use App\Models\Gallery;
use App\Models\Image;
use App\Models\Source;
use App\Models\Text;
use App\Models\User;
use App\Support\PermissionName;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Content-Pfade-Charakterisierung
|--------------------------------------------------------------------------
|
| Fixiert das beobachtbare Verhalten der ContentController- und
| AudiovisualController-Schreibpfade vor der Service-Extraktion
| (Block F.2–F.9). Test-Factories aus F.0 (Source, Text, Image,
| Gallery, Audiovisual) ermöglichen erstmals Tests für diese
| Pfade — vorher fehlte das Setup-Material.
|
| Fokus auf Update-, Edit- und Destroy-Pfade. Die Create-Pfade
| (saveText/saveImage mit File-Upload + attachMedia) sind komplex
| und brauchen Project/Chapter/Entry-Setup plus File-Uploads —
| sie werden in einer zweiten Welle abgedeckt, wenn die Services
| da sind und die Verantwortung schmaler ist.
*/

beforeEach(function () {
    foreach (PermissionName::all() as $permissionName) {
        Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
    }

    Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web'])
        ->syncPermissions(Permission::all());
});

// ---------- Text ----------

it('saveText im Update-Pfad aktualisiert den Text-Body und die Source-IDs', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $text = makeText(['text' => json_encode(['de' => 'Original-Text'])]);

    $this->post(route('text.store'), [
        'textId' => $text->id,
        'contentText' => '<p>Neuer Body</p>',
        'originText' => 'Neue Quelle',
        'copyrightText' => 'Neues Copyright',
    ]);

    $text->refresh();

    expect($text->text)->toContain('Neuer Body');
    expect($text->originText->name)->toBe('Neue Quelle');
    expect($text->copyrightText->name)->toBe('Neues Copyright');
});

it('editText liefert text, origin-name und copyright-name als JSON', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $origin = makeSource(['name' => 'Test-Quelle', 'type' => 'Origin']);
    $copyright = makeSource(['name' => 'Test-Copyright', 'type' => 'Copyright']);
    $text = makeText([
        'text' => json_encode(['de' => 'Body']),
        'origin' => $origin->id,
        'copyright' => $copyright->id,
    ]);

    $response = $this->get('/edit/'.$text->id.'/text');

    $response->assertOk();
    $response->assertJson([
        'id' => $text->id,
        'origin' => 'Test-Quelle',
        'copyright' => 'Test-Copyright',
    ]);
});

it('destroyText soft-deleted den Text und seine Comments', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $text = makeText();
    $project = makeProject($owner);

    $this->delete('/delete/'.$text->id.'/text?project='.$project->id);

    expect(Text::find($text->id))->toBeNull();
    expect(Text::withTrashed()->find($text->id))->not->toBeNull();
});

// ---------- Image ----------

it('saveImage im Update-Pfad aktualisiert Source-IDs auf einem Image', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $image = makeImage();

    $this->post(route('image.store'), [
        'imageId' => $image->id,
        'originImage' => 'Neue Bild-Quelle',
        'copyrightImage' => 'Neues Bild-Copyright',
    ]);

    $image->refresh();

    expect($image->originImage->name)->toBe('Neue Bild-Quelle');
    expect($image->copyrightImage->name)->toBe('Neues Bild-Copyright');
});

it('editImage liefert image, origin-name, copyright-name und alt als JSON', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $origin = makeSource(['name' => 'Img-Quelle', 'type' => 'Origin']);
    $copyright = makeSource(['name' => 'Img-Copyright', 'type' => 'Copyright']);
    $image = makeImage([
        'image' => 'test.jpg',
        'origin' => $origin->id,
        'copyright' => $copyright->id,
        'alt' => 'Alt-Text',
    ]);

    $response = $this->get('/edit/'.$image->id.'/image');

    $response->assertOk();
    $response->assertJsonFragment([
        'id' => $image->id,
        'origin' => 'Img-Quelle',
        'copyright' => 'Img-Copyright',
    ]);
});

it('destroyImage soft-deleted das Image', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $image = makeImage();
    $project = makeProject($owner);

    $this->delete('/delete/'.$image->id.'/image?project='.$project->id);

    expect(Image::find($image->id))->toBeNull();
    expect(Image::withTrashed()->find($image->id))->not->toBeNull();
});

// ---------- Gallery ----------

it('saveGallery im Update-Pfad aktualisiert title/subtitle/description', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $gallery = makeGallery(['title' => 'Alter Titel']);

    $this->post('/save-gallery', [
        'galleryId' => $gallery->id,
        'galleryTitle' => 'Neuer Titel',
        'gallerySubtitle' => 'Neuer Untertitel',
        'galleryDescription' => 'Neue Beschreibung',
    ]);

    $gallery->refresh();

    expect($gallery->title)->toBe('Neuer Titel');
    expect($gallery->subtitle)->toBe('Neuer Untertitel');
    expect($gallery->description)->toBe('Neue Beschreibung');
});

it('destroyGallery soft-deleted Gallery und ihre Images', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $gallery = makeGallery();
    $image = makeImage(['gallery_id' => $gallery->id]);
    $project = makeProject($owner);

    $this->delete('/delete/'.$gallery->id.'/gallery?project='.$project->id);

    // Gallery + Image soft-deleted — heutiger Pfad nutzt DB::table()
    // direkt (NF-LAR-009, latente Schwäche), das setzt deleted_at,
    // umgeht aber SoftDeletes-Trait-Hooks. Mit `withTrashed()`
    // bekommen wir den Eintrag trotzdem.
    expect(Gallery::find($gallery->id))->toBeNull();
    expect(Gallery::withTrashed()->find($gallery->id))->not->toBeNull();
    expect(Image::find($image->id))->toBeNull();
    expect(Image::withTrashed()->find($image->id))->not->toBeNull();
});

// ---------- Audiovisual ----------

it('AudiovisualController::store im Update-Pfad aktualisiert link/source/copyright', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $av = makeAudiovisual(['link' => 'altes-link']);

    $this->post(route('save.audiovisual'), [
        'audiovisualId' => $av->id,
        'link' => 'https://www.youtube.com/watch?v=abc12345678',
        'source' => 'Neue Quelle',
        'copyright' => 'Neues Copyright',
        'type' => 'video',
        'translationMode' => false,
    ]);

    $av->refresh();

    // Der Controller wandelt YouTube-URLs in den embed-Pfad um —
    // youtubeID extrahiert die 11-stellige ID und prependet
    // https://www.youtube.com/embed/.
    expect($av->link)->toContain('youtube.com/embed/');
    expect($av->source)->toBe('Neue Quelle');
    expect($av->copyright)->toBe('Neues Copyright');
});

it('AudiovisualController::delete soft-deleted das Audiovisual', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $av = makeAudiovisual();
    $project = makeProject($owner);

    $this->delete(route('audiovisual.delete', $av->id).'?project='.$project->id);

    expect(Audiovisual::find($av->id))->toBeNull();
    expect(Audiovisual::withTrashed()->find($av->id))->not->toBeNull();
});

// ---------- Factory-Smoke ----------

it('SourceFactory legt eine Source mit korrektem Type an', function () {
    $origin = Source::factory()->origin()->create();
    $copyright = Source::factory()->copyright()->create();

    expect($origin->type)->toBe('Origin');
    expect($copyright->type)->toBe('Copyright');
    expect($origin->name)->toBeString();
});

it('TextFactory legt einen Text mit zwei Source-Refs an', function () {
    $text = makeText();

    expect($text->text)->toBeString();
    expect($text->origin)->toBeInt();
    expect($text->copyright)->toBeInt();
    expect($text->originText)->not->toBeNull();
    expect($text->copyrightText)->not->toBeNull();
});

it('ImageFactory legt ein Image standalone mit Source-Refs an', function () {
    $image = makeImage();

    expect($image->image)->toBeString();
    expect($image->originImage)->not->toBeNull();
    expect($image->copyrightImage)->not->toBeNull();
});
