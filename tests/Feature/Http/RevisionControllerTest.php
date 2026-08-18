<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 *
 * Pest-Tests fuer Phase 5ab.2/5ab.5 — Panel-Feed und Restore-Endpoint.
 *
 * Deckt ab:
 *  - index: JSON mit den Revisions eines Subjects, project-scoped Auth
 *  - restore: Owner mit history-restore-Permission darf, schreibt new-Wert
 *  - restore: Fremder ohne Permission bekommt 403
 *  - restore: Translatable Felder werden via setTranslations() geschrieben
 */

use App\Models\Chapter;
use App\Models\Revision;
use App\Models\User;
use App\Support\PermissionName;
use Illuminate\Support\Carbon;
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
        ->syncPermissions(['view', 'add', 'edit', 'delete', 'comment', 'history-restore']);
});

it('index liefert JSON mit Revisions fuer ein Subject', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $project = makeProject($owner);
    $chapter = makeChapter($project);

    $this->actingAs($owner);

    $response = $this->getJson(route('revisions.index', ['subjectType' => 'Chapter', 'subjectId' => $chapter->id]));

    $response->assertStatus(200)
        ->assertJsonPath('scope', 'block')
        ->assertJsonPath('subject.type', 'Chapter')
        ->assertJsonPath('subject.id', $chapter->id);
    expect(count($response->json('revisions')))->toBeGreaterThan(0);
});

it('restore schreibt den new-Wert zurueck und ist idempotent gegen Rollback', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);
    $project = makeProject($owner);
    $chapter = makeChapter($project, ['name' => 'Original']);

    Revision::query()->update(['created_at' => Carbon::now()->subMinutes(6)]);
    $chapter->name = 'Zwischenstand';
    $chapter->save();
    Revision::query()->update(['created_at' => Carbon::now()->subMinutes(6)]);
    $chapter->name = 'Aktuell';
    $chapter->save();

    // Zweite Revision zurueckholen — die mit new = 'Zwischenstand'
    $target = Revision::query()
        ->where('subject_type', Chapter::class)
        ->where('subject_id', $chapter->id)
        ->where('version', 2)
        ->first();

    $response = $this->postJson(route('revisions.restore', $target));

    $response->assertStatus(200)->assertJsonPath('ok', true);
    expect($chapter->fresh()->name)->toBe('Zwischenstand');
});

it('restore verweigert User ohne history-restore-Permission', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);
    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $target = Revision::query()->where('subject_id', $chapter->id)->first();

    // Reader-Rolle hat nur view — kein history-restore.
    /** @var User $reader */
    $reader = User::factory()->create();
    $reader->assignRole('Reader');
    $this->actingAs($reader);

    $this->postJson(route('revisions.restore', $target))->assertStatus(403);
});

it('restore behaelt beide Sprachen bei einem translatable Feld', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);
    $project = makeProject($owner);
    $chapter = makeChapter($project);

    // Zwei-Locale-Zustand aufbauen, jede Aenderung ausserhalb des
    // Coalesce-Fensters, damit wir drei getrennte Revisions bekommen.
    $chapter->setTranslation('name', 'de', 'Deutsch A');
    $chapter->setTranslation('name', 'en', 'English A');
    $chapter->save();
    Revision::query()->update(['created_at' => Carbon::now()->subMinutes(6)]);

    $chapter->setTranslation('name', 'de', 'Deutsch B');
    $chapter->setTranslation('name', 'en', 'English B');
    $chapter->save();
    Revision::query()->update(['created_at' => Carbon::now()->subMinutes(6)]);

    $chapter->setTranslation('name', 'de', 'Deutsch C');
    $chapter->setTranslation('name', 'en', 'English C');
    $chapter->save();

    // Zurueck zu Version 2 (Deutsch B / English B).
    $target = Revision::query()
        ->where('subject_id', $chapter->id)
        ->where('version', 2)
        ->first();

    $this->postJson(route('revisions.restore', $target))->assertStatus(200);

    $fresh = $chapter->fresh();
    expect($fresh->getTranslation('name', 'de'))->toBe('Deutsch B')
        ->and($fresh->getTranslation('name', 'en'))->toBe('English B');
});
