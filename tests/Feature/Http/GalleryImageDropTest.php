<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 *
 * Pest-Tests fuer Phase 5y.9 — optimistischer Drop-Upload und
 * Reorder-Endpoint der Galerie.
 *
 * Deckt ab:
 *  - `POST /gallery/{gallery}/images/drop` (dropImage): Owner-only,
 *    Bild-Anlage ohne Copyright/Quelle, MIME-Whitelist, Groessen-Limit.
 *  - `POST /gallery/{gallery}/images/reorder` (reorderImages):
 *    Owner-only, Position wird von 1 hochgezaehlt.
 *
 * Reader-Vektor: Ein User ohne update-Recht bekommt 403, egal ob er
 * auf dem Projekt eingeladen ist oder nicht.
 */

use App\Models\Gallery;
use App\Models\Image;
use App\Models\User;
use App\Support\PermissionName;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (PermissionName::all() as $permissionName) {
        Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
    }

    Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web'])
        ->syncPermissions(Permission::all());
    Role::firstOrCreate(['name' => 'Reader', 'guard_name' => 'web'])
        ->syncPermissions(['view']);
    Role::firstOrCreate(['name' => 'Editor', 'guard_name' => 'web'])
        ->syncPermissions(['view', 'add', 'edit', 'delete', 'comment']);

    Storage::fake('public');
});

/**
 * Setup: Owner-Projekt mit Gallery, Reader ohne Rechte.
 *
 * @return array{owner: User, reader: User, gallery: Gallery}
 */
function galleryDropSetup(): array
{
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Editor');
    /** @var User $reader */
    $reader = User::factory()->create();
    $reader->assignRole('Reader');

    $project = makeProject($owner);
    $gallery = makeGallery();
    attachToProject($project, $gallery);

    return ['owner' => $owner, 'reader' => $reader, 'gallery' => $gallery];
}

/*
|--------------------------------------------------------------------------
| ContentController::dropImage
|--------------------------------------------------------------------------
*/

it('dropImage — Owner darf hochladen, Bild wird ohne Copyright/Quelle angelegt', function () {
    /** @var TestCase $this */
    ['owner' => $owner, 'gallery' => $gallery] = galleryDropSetup();
    $this->actingAs($owner);

    $file = UploadedFile::fake()->image('foto.jpg', 800, 600);

    $response = $this->post(route('gallery.images.drop', $gallery), ['file' => $file]);

    $response->assertStatus(200);
    $response->assertJson(['ok' => true]);

    $image = Image::where('gallery_id', $gallery->id)->latest('id')->first();
    expect($image)->not->toBeNull()
        ->and($image->copyright)->toBeNull()
        ->and($image->origin)->toBeNull()
        ->and($image->position)->toBe(1);
});

it('dropImage — Reader ohne Update-Recht bekommt 403', function () {
    /** @var TestCase $this */
    ['reader' => $reader, 'gallery' => $gallery] = galleryDropSetup();
    $this->actingAs($reader);

    $file = UploadedFile::fake()->image('foto.jpg');

    $this->post(route('gallery.images.drop', $gallery), ['file' => $file])
        ->assertStatus(403);
});

it('dropImage — nicht unterstuetztes Format wird abgelehnt', function () {
    /** @var TestCase $this */
    ['owner' => $owner, 'gallery' => $gallery] = galleryDropSetup();
    $this->actingAs($owner);

    $file = UploadedFile::fake()->create('script.js', 10, 'application/javascript');

    $this->post(route('gallery.images.drop', $gallery), ['file' => $file])
        ->assertStatus(302); // Laravel Validation-Redirect

    expect(Image::where('gallery_id', $gallery->id)->count())->toBe(0);
});

it('dropImage — Datei ueber 4 MB wird abgelehnt', function () {
    /** @var TestCase $this */
    ['owner' => $owner, 'gallery' => $gallery] = galleryDropSetup();
    $this->actingAs($owner);

    // 5 MB Fake-Bild
    $file = UploadedFile::fake()->image('gross.jpg')->size(5 * 1024);

    $this->post(route('gallery.images.drop', $gallery), ['file' => $file])
        ->assertStatus(302);

    expect(Image::where('gallery_id', $gallery->id)->count())->toBe(0);
});

it('dropImage — Position wird pro Gallery hochgezaehlt', function () {
    /** @var TestCase $this */
    ['owner' => $owner, 'gallery' => $gallery] = galleryDropSetup();
    $this->actingAs($owner);

    $first = UploadedFile::fake()->image('a.jpg');
    $second = UploadedFile::fake()->image('b.jpg');

    $this->post(route('gallery.images.drop', $gallery), ['file' => $first])->assertStatus(200);
    $this->post(route('gallery.images.drop', $gallery), ['file' => $second])->assertStatus(200);

    $positions = Image::where('gallery_id', $gallery->id)
        ->orderBy('id')
        ->pluck('position')
        ->all();

    expect($positions)->toBe([1, 2]);
});

/*
|--------------------------------------------------------------------------
| ContentController::reorderImages
|--------------------------------------------------------------------------
*/

it('reorderImages — Owner darf Reihenfolge speichern', function () {
    /** @var TestCase $this */
    ['owner' => $owner, 'gallery' => $gallery] = galleryDropSetup();
    $this->actingAs($owner);

    $a = makeImage(['gallery_id' => $gallery->id, 'position' => 1]);
    $b = makeImage(['gallery_id' => $gallery->id, 'position' => 2]);
    $c = makeImage(['gallery_id' => $gallery->id, 'position' => 3]);

    $this->post(route('gallery.images.reorder', $gallery), [
        'ids' => [$c->id, $a->id, $b->id],
    ])->assertStatus(200);

    expect(Image::find($c->id)->position)->toBe(1)
        ->and(Image::find($a->id)->position)->toBe(2)
        ->and(Image::find($b->id)->position)->toBe(3);
});

it('reorderImages — Reader bekommt 403', function () {
    /** @var TestCase $this */
    ['reader' => $reader, 'gallery' => $gallery] = galleryDropSetup();
    $this->actingAs($reader);

    $img = makeImage(['gallery_id' => $gallery->id, 'position' => 1]);

    $this->post(route('gallery.images.reorder', $gallery), ['ids' => [$img->id]])
        ->assertStatus(403);
});
