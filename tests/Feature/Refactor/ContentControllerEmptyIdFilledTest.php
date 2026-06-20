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
use App\Models\Text;
use App\Models\User;
use App\Support\PermissionName;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Filled-Pattern-Charakterisierung (Stakeholder-Bug Juni 2026)
|--------------------------------------------------------------------------
|
| Pinnt das Verhalten der Schreibpfade bei Neuanlage. Vorher liefen
| `saveGallery`/`saveText`/`saveImage`/`AudiovisualController::store`
| mit dem Pattern `isset($req['xId']) && $req['xId'] !== ''`. Seit dem
| Laravel-11-Sprung (Phase 3 / Block F) gehört
| `ConvertEmptyStringsToNull` zur Default-Web-Middleware. Sie schreibt
| leere Hidden-Inputs (`galleryId=""` bei Neuanlage) zu `null` um. Die
| alte Bedingung war dafür blind (`null !== ''` ist `true`) und führte
| in den Update-Pfad → `findOrFail(null)` → `ModelNotFoundException`
| → HTTP 404. Stakeholder konnten weder neue Galerien noch Texte noch
| Bilder noch Audio/Video anlegen.
|
| Diese Tests stellen sicher, dass `xId=""` (Neuanlage-Pfad) NICHT in
| `findOrFail(null)` läuft und nicht 404 zurückgibt. Sie sind die
| Regression-Barriere — wer die alten `isset/!== ''`-Pattern aus
| Versehen zurückbringt, soll hier scheitern.
*/

beforeEach(function () {
    foreach (PermissionName::all() as $permissionName) {
        Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
    }

    Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web'])
        ->syncPermissions(Permission::all());
});

// ---------- saveGallery ----------

it('save.gallery: leerer galleryId-String läuft in den Create-Pfad, nicht in 404', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $entry = makeEntry($chapter);

    $countBefore = Gallery::count();

    $response = $this
        ->from(route('chapters.index', ['id' => $project->id]))
        ->post(route('save.gallery'), [
            'galleryId' => '', // simuliert Neuanlage — Browser sendet leeren Hidden-Input
            'entryId' => $entry->id,
            'title' => 'Frische Galerie',
            'subtitle' => 'Untertitel',
            'description' => 'Beschreibung',
        ]);

    $response->assertRedirect();
    expect(Gallery::count())->toBe($countBefore + 1);
});

it('save.gallery: ohne galleryId-Key im Payload läuft in den Create-Pfad, nicht in 404', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $entry = makeEntry($chapter);

    $countBefore = Gallery::count();

    $response = $this
        ->from(route('chapters.index', ['id' => $project->id]))
        ->post(route('save.gallery'), [
            // Kein galleryId im Payload überhaupt — Robustheits-Pinning.
            'entryId' => $entry->id,
            'title' => 'Frische Galerie',
            'subtitle' => 'Untertitel',
            'description' => 'Beschreibung',
        ]);

    $response->assertRedirect();
    expect(Gallery::count())->toBe($countBefore + 1);
});

// ---------- saveText ----------

it('text.store: leerer textId-String läuft in den Create-Pfad, nicht in 404', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $entry = makeEntry($chapter);

    $countBefore = Text::count();

    $response = $this
        ->from(route('chapters.index', ['id' => $project->id]))
        ->post(route('text.store'), [
            'textId' => '', // simuliert Neuanlage
            'entryId' => $entry->id,
            'contentText' => 'Ein neuer Body.',
            'originText' => 'Quelle',
            'copyrightText' => 'Copyright',
        ]);

    $response->assertRedirect();
    expect(Text::count())->toBe($countBefore + 1);
});

// ---------- saveImage ----------

it('image.store: leerer imageId-String läuft in den Create-Pfad, nicht in 404', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $entry = makeEntry($chapter);
    $gallery = makeGallery();

    $response = $this
        ->from(route('chapters.index', ['id' => $project->id]))
        ->post(route('image.store'), [
            'imageId' => '', // simuliert Neuanlage
            'galleryId' => $gallery->id,
            'entryId' => $entry->id,
            'image' => UploadedFile::fake()->image('foto.jpg', 800, 600),
            'altText' => 'Alt-Text',
            'copyrightImage' => 'CC-BY',
            'originImage' => 'Eigene Aufnahme',
        ]);

    // Hier reicht ein assertRedirect statt assertStatus(404) —
    // entscheidend ist: kein 404, der Image-Create-Pfad wurde
    // erreicht.
    $response->assertRedirect();
});

// ---------- save.audiovisual ----------

it('save.audiovisual: leerer audiovisualId-String läuft in den Create-Pfad, nicht in 404', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $entry = makeEntry($chapter);

    $countBefore = Audiovisual::count();

    $response = $this
        ->from(route('chapters.index', ['id' => $project->id]))
        ->post(route('save.audiovisual'), [
            'audiovisualId' => '', // simuliert Neuanlage
            'entryId' => $entry->id,
            'type' => 'video',
            'link' => 'https://www.youtube.com/watch?v=abc12345678',
            'source' => 'Quelle',
            'copyright' => 'CC-BY',
        ]);

    $response->assertRedirect();
    expect(Audiovisual::count())->toBe($countBefore + 1);
});

// ---------- Regression-Absicherung des Update-Pfads ----------
//
// Damit der Fix den Update-Pfad nicht aus Versehen zerschneidet:
// Wenn galleryId/textId/audiovisualId gefüllt ist, muss WEITERHIN
// der Update-Zweig laufen, KEIN zweiter Create.

it('save.gallery: gefüllter galleryId aktualisiert die bestehende Gallery (kein zweiter Insert)', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $entry = makeEntry($chapter);
    $gallery = makeGallery(['title' => json_encode(['de' => 'Alt'])]);

    $countBefore = Gallery::count();

    $this
        ->from(route('chapters.index', ['id' => $project->id]))
        ->post(route('save.gallery'), [
            'galleryId' => $gallery->id,
            'entryId' => $entry->id,
            'title' => 'Neu',
            'subtitle' => 'Untertitel',
            'description' => 'Beschreibung',
        ]);

    $gallery->refresh();
    expect(Gallery::count())->toBe($countBefore);
    expect($gallery->title)->toBe('Neu');
});

it('save.audiovisual: gefüllter audiovisualId aktualisiert (kein zweiter Insert)', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $entry = makeEntry($chapter);
    $av = makeAudiovisual();

    $countBefore = Audiovisual::count();

    $this
        ->from(route('chapters.index', ['id' => $project->id]))
        ->post(route('save.audiovisual'), [
            'audiovisualId' => $av->id,
            'entryId' => $entry->id,
            'type' => 'video',
            'link' => 'https://www.youtube.com/watch?v=xyz87654321',
            'source' => 'Quelle',
            'copyright' => 'CC-BY',
        ]);

    expect(Audiovisual::count())->toBe($countBefore);
});
