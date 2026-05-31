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

/**
 * Happy-Path-Suite — Vor-Phase-3-Coverage-Welle.
 *
 * Sichert die acht grünen Smoke-Pfade aus docs/smoke.md als Pest-
 * Tests ab, damit der Major-Upgrade-Sprung (Laravel 8 → 12, PHP
 * 8.1 → 8.4) ein Sicherheitsnetz unter sich hat. Authorization-
 * Bypass-Tests prüfen „darf der User das?", diese Suite prüft
 * „macht die App das, was sie soll, wenn alles richtig läuft?".
 *
 * Test-Helper `makeProject`, `makeChapter`, `makeEntry` liegen
 * in tests/Pest.php.
 */

use App\Http\Controllers\Auth\MyCustomWelcomeNotification;
use App\Models\Audiovisual;
use App\Models\Gallery;
use App\Models\Image;
use App\Models\MediaContent;
use App\Models\Project;
use App\Models\Text;
use App\Models\User;
use App\Support\PermissionName;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (PermissionName::all() as $name) {
        Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web'])
        ->syncPermissions(Permission::all());

    // Editor-Rolle für die Stakeholder-Workflows (Invitation,
    // Permission-Cascade in den späteren Tests).
    Role::firstOrCreate(['name' => 'Editor', 'guard_name' => 'web']);
});

// ----------------------------------------------------------------------
// 1. Project-Create-Happy-Path.
// Admin mit add-Permission postet ein neues Project. Erwartet:
// Project ist in DB, user_id ist der Caller, Status ist Default.
// ----------------------------------------------------------------------

test('Happy-Path: Admin kann ein Project anlegen', function () {
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $admin->givePermissionTo(PermissionName::ADD);

    $response = $this->actingAs($admin)
        ->from(route('projects.create'))
        ->post(route('projects.store'), [
            'name' => 'Mein erstes Projekt',
            'imprint' => 'Pflicht-Impressum',
            'description' => 'Beschreibungs-Text',
        ]);

    $response->assertRedirect();
    $project = Project::where('user_id', $admin->id)->firstOrFail();
    expect($project->user_id)->toBe($admin->id);
    expect($project->status)->toBe(config('project.status.default'));
});

// ----------------------------------------------------------------------
// 2. Chapter-Create-Happy-Path.
// Owner postet POST /chapters mit gültigen Feldern.
// Erwartet: Chapter ist in DB, position ist 1 (erstes Kapitel).
// ----------------------------------------------------------------------

test('Happy-Path: Owner kann ein Chapter im eigenen Project anlegen', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);

    $response = $this->actingAs($owner)
        ->from(route('chapters.index', ['id' => $project->id]))
        ->post(route('chapters.store'), [
            'projectId' => $project->id,
            'chapterTitle' => 'Kapitel Eins',
            'chapterSubtitle' => 'Unter-Titel',
            'chapterDescription' => 'Beschreibung',
        ]);

    $response->assertRedirect();
    $chapter = $project->chapters()->firstOrFail();
    expect($chapter->position)->toBe(1);
});

// ----------------------------------------------------------------------
// 3. Entry-Create-Happy-Path.
// Owner postet POST /entries an einem Chapter im eigenen Project.
// Erwartet: Entry ist in DB, hängt am richtigen Chapter.
// ----------------------------------------------------------------------

test('Happy-Path: Owner kann ein Entry im eigenen Chapter anlegen', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $chapter = makeChapter($project);

    $response = $this->actingAs($owner)
        ->from(route('chapters.index', ['id' => $project->id]))
        ->post(route('entries.store'), [
            'chapterId' => $chapter->id,
            'entryTitle' => 'Eintrag Eins',
            'entrySubtitle' => 'Unter-Titel',
            'entryDescription' => 'Beschreibung',
        ]);

    $response->assertRedirect();
    $entry = $chapter->entries()->firstOrFail();
    expect($entry->chapter_id)->toBe($chapter->id);
});

// ----------------------------------------------------------------------
// 4. Admin-Invitation-Flow.
// Admin lädt einen neuen User ein. Erwartet: User ist in DB,
// Welcome-Notification ist dispatched (über Spatie's
// WelcomeNotification). Mailpit-Routing muss in CI nicht verfügbar
// sein — Notification::fake() fängt den Dispatch ab.
// ----------------------------------------------------------------------

test('Happy-Path: Admin lädt einen neuen User ein und Welcome-Notification wird dispatched', function () {
    Notification::fake();

    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $response = $this->actingAs($admin)
        ->from(route('register'))
        ->post(route('register.store'), [
            'firstName' => 'Neuer',
            'lastName' => 'Editor',
            'email' => 'neuer.editor@example.com',
            'roles' => 'Editor',
            'policy' => 1,
        ]);

    $response->assertRedirect();
    $newUser = User::where('email', 'neuer.editor@example.com')->firstOrFail();
    expect($newUser->hasRole('Editor'))->toBeTrue();
    Notification::assertSentTo($newUser, MyCustomWelcomeNotification::class);
});

// ----------------------------------------------------------------------
// 5. Audio-Upload-Happy-Path.
// Owner postet POST /save-audiovisual mit einer Audio-Datei. Erwartet:
// Audiovisual-Row in DB, File auf public-Disk in /uploads/audio/.
// ----------------------------------------------------------------------

test('Happy-Path: Owner kann ein Audio-File hochladen', function () {
    Storage::fake('public');

    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $entry = makeEntry($chapter);

    $response = $this->actingAs($owner)
        ->from(route('dashboard'))
        ->post(route('save.audiovisual'), [
            'audio' => UploadedFile::fake()->create('jingle.mp3', 200, 'audio/mpeg'),
            'type' => 'audio',
            'source' => 'Test-Quelle',
            'copyright' => 'CC-BY',
            'entryId' => $entry->id,
        ]);

    $response->assertRedirect();
    $audiovisual = Audiovisual::firstOrFail();
    expect($audiovisual->type)->toBe('audio');
    // attachMedia-Pfad: MediaContent-Row für die Polymorphic-Bindung.
    expect(MediaContent::where('media_contentable_id', $entry->id)
        ->where('media_contentable_type', Audiovisual::class)
        ->exists())->toBeTrue();
});

// ----------------------------------------------------------------------
// 6. Text-Block-Create-Happy-Path.
// Owner postet POST /text/store mit den drei Pflichtfeldern. Erwartet:
// Text-Row in DB (JSON-encoded), zwei Source-Rows (Origin + Copyright),
// MediaContent-Row für die Entry-Bindung.
// ----------------------------------------------------------------------

test('Happy-Path: Owner kann einen Text-Block anlegen', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $entry = makeEntry($chapter);

    $response = $this->actingAs($owner)
        ->from(route('chapters.index', ['id' => $project->id]))
        ->post(route('text.store'), [
            'contentText' => 'Ein erster Text-Block für den Curating-Workflow.',
            'copyrightText' => 'CC-BY 4.0',
            'originText' => 'Eigene Erhebung',
            'entryId' => $entry->id,
        ]);

    $response->assertRedirect();
    $text = Text::firstOrFail();
    expect($text->origin)->not->toBeNull();
    expect($text->copyright)->not->toBeNull();
    expect(MediaContent::where('media_contentable_id', $entry->id)
        ->where('media_contentable_type', Text::class)
        ->exists())->toBeTrue();
});

// ----------------------------------------------------------------------
// 7. Image-Block-Create-Happy-Path.
// Owner postet POST /image/store mit einer Bild-Datei und einer
// vorhandenen Gallery. Erwartet: Image-Row in DB mit Server-generiertem
// Dateinamen, MediaContent-Row für die Gallery-Verknüpfung.
// ----------------------------------------------------------------------

test('Happy-Path: Owner kann ein Bild in eine Gallery hochladen', function () {
    Storage::fake('public');

    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $entry = makeEntry($chapter);
    $gallery = Gallery::create([
        'title' => 'Test-Gallery',
        'subtitle' => 'Unter-Titel',
        'description' => 'Beschreibung',
    ]);

    $response = $this->actingAs($owner)
        ->from(route('chapters.index', ['id' => $project->id]))
        ->post(route('image.store'), [
            'image' => UploadedFile::fake()->image('foto.jpg', 800, 600),
            'galleryId' => $gallery->id,
            'entryId' => $entry->id,
            'altText' => 'Bild-Alt-Text',
            'copyrightImage' => 'CC-BY',
            'originImage' => 'Eigene Aufnahme',
        ]);

    $response->assertRedirect();
    $image = Image::firstOrFail();
    expect($image->gallery_id)->toBe($gallery->id);
    expect($image->image)->toMatch('/^[0-9]{8}_[0-9]+\.(jpg|jpeg)$/');
});

// ----------------------------------------------------------------------
// 8. Permission-Cascade-Happy-Path.
// User mit Editor-Rolle hat über die Rolle die add-Permission. Damit
// kommt er an POST /projects (Route ist `permission:add`-geschützt)
// und kann ein Project anlegen. Verifiziert die Kette
// User → Spatie-Rolle → Permissions → Route-Middleware.
// ----------------------------------------------------------------------

test('Happy-Path: Editor-Rolle bringt add-Permission und Project-Anlage über Route durch', function () {
    /** @var User $editor */
    $editor = User::factory()->create();
    // Editor-Rolle hat add/view/edit/delete/publish/comment (siehe
    // RoleTableSeeder). Wir spiegeln den Seeder im beforeEach-Setup
    // und syncen die Permissions auf die Editor-Rolle.
    Role::findByName('Editor', 'web')->syncPermissions(
        Permission::whereIn('name', [
            PermissionName::VIEW,
            PermissionName::ADD,
            PermissionName::EDIT,
            PermissionName::DELETE,
            PermissionName::PUBLISH,
            PermissionName::COMMENT,
        ])->get()
    );
    $editor->assignRole('Editor');

    expect($editor->can(PermissionName::ADD))->toBeTrue();
    expect($editor->can(PermissionName::DELETE))->toBeTrue();

    $response = $this->actingAs($editor)
        ->from(route('projects.create'))
        ->post(route('projects.store'), [
            'name' => 'Editor-Projekt',
            'imprint' => 'Pflicht-Impressum',
        ]);

    $response->assertRedirect();
    expect(Project::where('user_id', $editor->id)->exists())->toBeTrue();
});
