<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 */

use App\Models\Source;
use App\Models\User;
use App\Services\SourceService;
use App\Support\PermissionName;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Q4-Etappe 3 / C0-8a · Projekt-Scope fuer Source-Rows
|--------------------------------------------------------------------------
|
| Sichert die neue Signatur `SourceService::findOrCreateId($value,
| $type, ?int $projectId)`:
|   - Legt einen neuen Source-Row mit gesetztem `project_id` an.
|   - Wiederholte Aufrufe im selben Projekt finden den bestehenden Row.
|   - Derselbe Name in zwei Projekten fuehrt zu zwei getrennten Rows.
|   - Ohne `projectId` liefert der Service Alt-Verhalten und matcht nur
|     projektlose Rows (Bestand aus der Vor-C0-Zeit).
*/

beforeEach(function () {
    foreach (PermissionName::all() as $permissionName) {
        Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
    }
    Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web'])
        ->syncPermissions(Permission::all());
});

it('legt einen Source-Row mit project_id an', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);

    $service = app(SourceService::class);
    $sourceId = $service->findOrCreateId('Landesarchiv Berlin', 'Origin', $project->id);

    $source = Source::findOrFail($sourceId);
    expect($source->project_id)->toBe($project->id);
    expect($source->type)->toBe('Origin');
    expect($source->getTranslation('name', app()->getLocale()))->toBe('Landesarchiv Berlin');
});

it('findet einen bestehenden Source-Row im selben Projekt wieder', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);

    $service = app(SourceService::class);
    $firstId = $service->findOrCreateId('Landesarchiv Berlin', 'Origin', $project->id);
    $secondId = $service->findOrCreateId('Landesarchiv Berlin', 'Origin', $project->id);

    expect($firstId)->toBe($secondId);
    expect(Source::where('project_id', $project->id)->where('type', 'Origin')->count())->toBe(1);
});

it('legt bei gleichem Namen in zwei Projekten zwei Rows an', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $projectA = makeProject($owner);
    $projectB = makeProject($owner);

    $service = app(SourceService::class);
    $idA = $service->findOrCreateId('Landesarchiv Berlin', 'Origin', $projectA->id);
    $idB = $service->findOrCreateId('Landesarchiv Berlin', 'Origin', $projectB->id);

    expect($idA)->not->toBe($idB);
    expect(Source::findOrFail($idA)->project_id)->toBe($projectA->id);
    expect(Source::findOrFail($idB)->project_id)->toBe($projectB->id);
});

it('scopet ohne projectId nur gegen projektlose Rows', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);

    $service = app(SourceService::class);
    // Zuerst einen projekt-gescopten Row anlegen, dann projektlos suchen:
    // Match darf NICHT auf den projekt-gescopten Row springen.
    $scopedId = $service->findOrCreateId('Landesarchiv Berlin', 'Origin', $project->id);
    $unscopedId = $service->findOrCreateId('Landesarchiv Berlin', 'Origin', null);

    expect($scopedId)->not->toBe($unscopedId);
    expect(Source::findOrFail($unscopedId)->project_id)->toBeNull();
});
