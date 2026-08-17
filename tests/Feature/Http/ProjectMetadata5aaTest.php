<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 *
 * Pest-Tests fuer Phase 5aa.2 (Systemtext-Uebernahme in Projekt-Metadaten)
 * und 5aa.3 (Bulk-Save-Endpoint der Uebersetzen-Sicht).
 *
 * Adopt-System-Text:
 *  - Owner darf: leeres Projekt-Impressum wird mit Systemtext ueberschrieben.
 *  - Field-Whitelist: nur `imprint`/`terms` erlaubt, sonst 422.
 *  - Fremder ohne Rechte: 403.
 *
 * Save-Translations:
 *  - Owner speichert englische Uebersetzungen (Chapter, Entry).
 *  - Cross-Project-Guard: Chapter aus fremdem Projekt wird ignoriert,
 *    nicht ins Zielprojekt geschrieben.
 *  - AJAX-Request bekommt JSON zurueck, klassisches Formular Redirect.
 */

use App\Models\Chapter;
use App\Models\Entry;
use App\Models\Imprint;
use App\Models\User;
use App\Support\PermissionName;
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

    Imprint::create([
        'name' => ['firstname' => 'System', 'lastname' => 'Betreiber'],
        'address' => ['address' => 'Musterstr. 1', 'postcode' => '10115'],
        'contact' => ['phone' => '030 123', 'email' => 'kontakt@example.test'],
    ]);
});

/*
|--------------------------------------------------------------------------
| ProjectController::adoptSystemLegalText — 5aa.2
|--------------------------------------------------------------------------
*/

it('adoptSystemLegalText — Owner uebernimmt Systemtext in leeres Impressum', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Editor');
    $project = makeProject($owner, ['imprint' => '']);

    $this->actingAs($owner);

    $response = $this->post(route('projects.metadata.adopt_system_text', $project), [
        'field' => 'imprint',
    ]);

    $response->assertStatus(302);
    expect($project->fresh()->imprint)->toContain('System Betreiber');
});

it('adoptSystemLegalText — unbekanntes Feld wird mit 422 abgewiesen', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Editor');
    $project = makeProject($owner);

    $this->actingAs($owner);

    $response = $this->post(route('projects.metadata.adopt_system_text', $project), [
        'field' => 'description',
    ]);

    $response->assertStatus(422);
});

it('adoptSystemLegalText — Fremder ohne update-Recht bekommt 403', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Editor');
    /** @var User $stranger */
    $stranger = User::factory()->create();
    $stranger->assignRole('Reader');
    $project = makeProject($owner);

    $this->actingAs($stranger);

    $response = $this->post(route('projects.metadata.adopt_system_text', $project), [
        'field' => 'imprint',
    ]);

    $response->assertStatus(403);
});

/*
|--------------------------------------------------------------------------
| ProjectController::saveTranslations — 5aa.3
|--------------------------------------------------------------------------
*/

it('saveTranslations — Owner speichert englische Kapitel- und Abschnitt-Titel', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Editor');
    $project = makeProject($owner);
    $chapter = makeChapter($project, ['name' => 'Deutscher Titel']);
    $entry = makeEntry($chapter, ['name' => 'Deutscher Abschnitt']);

    $this->actingAs($owner);

    $response = $this->post(route('translate.save', $project->id), [
        'translations' => [
            "Chapter.{$chapter->id}.name" => 'English chapter title',
            "Entry.{$entry->id}.name" => 'English section title',
        ],
    ]);

    $response->assertStatus(302);

    expect($chapter->fresh()->getTranslation('name', 'en'))->toBe('English chapter title')
        ->and($entry->fresh()->getTranslation('name', 'en'))->toBe('English section title');
});

it('saveTranslations — Chapter aus fremdem Projekt wird ignoriert (Cross-Project-Guard)', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Editor');
    /** @var User $otherOwner */
    $otherOwner = User::factory()->create();
    $otherOwner->assignRole('Editor');

    $ownProject = makeProject($owner);
    $otherProject = makeProject($otherOwner);
    $foreignChapter = makeChapter($otherProject, ['name' => 'Original']);

    $this->actingAs($owner);

    $this->post(route('translate.save', $ownProject->id), [
        'translations' => [
            "Chapter.{$foreignChapter->id}.name" => 'Hijack attempt',
        ],
    ]);

    // Fremdes Kapitel darf nicht ueber ein Save auf $ownProject uebersetzt werden.
    expect($foreignChapter->fresh()->getTranslation('name', 'en'))->not->toBe('Hijack attempt');
});

it('saveTranslations — AJAX-Request bekommt JSON-Antwort', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Editor');
    $project = makeProject($owner);
    $chapter = makeChapter($project);

    $this->actingAs($owner);

    $response = $this->postJson(route('translate.save', $project->id), [
        'translations' => [
            "Chapter.{$chapter->id}.name" => 'Fresh EN title',
        ],
    ]);

    $response->assertStatus(200)->assertJson(['ok' => true]);
});
