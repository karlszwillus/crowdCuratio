<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 *
 * Pest-Tests fuer Phase 5ab.1 — HasRevisions-Trait auf den Content-Modellen.
 *
 * Deckt ab:
 *  - Neue Revision beim Create eines Chapter (kind = content, meta.created = true)
 *  - Neue Revision beim Update mit Delta {old, new}
 *  - Coalescing im 5-Min-Fenster: gleicher Actor, gleiches Subject, gleicher Kind
 *    merged Aenderungen in die bestehende Revision anstatt eine neue anzulegen
 *  - Kind-Ableitung: nur `position` → REORDER
 */

use App\Models\Chapter;
use App\Models\Revision;
use App\Models\User;
use App\Support\PermissionName;
use App\Support\RevisionKind;
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
});

it('legt eine Revision an, wenn ein Chapter erstellt wird', function () {
    /** @var TestCase $this */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);
    $chapter = makeChapter($project, ['name' => 'Ursprung']);

    $revisions = Revision::query()
        ->where('subject_type', Chapter::class)
        ->where('subject_id', $chapter->id)
        ->get();

    expect($revisions)->toHaveCount(1)
        ->and($revisions->first()->kind)->toBe(RevisionKind::CONTENT->value)
        ->and($revisions->first()->version)->toBe(1)
        ->and($revisions->first()->actor_id)->toBe($owner->id)
        ->and($revisions->first()->snapshot['meta']['created'] ?? null)->toBe(true);
});

it('legt eine Update-Revision mit old/new-Delta an', function () {
    /** @var TestCase $this */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);
    $project = makeProject($owner);
    $chapter = makeChapter($project, ['name' => 'Original']);

    // Coalescing-Fenster ueberspringen — 6 Minuten zurueck fuer die
    // Create-Revision.
    Revision::query()->update(['created_at' => Carbon::now()->subMinutes(6)]);

    $chapter->name = 'Neuer Titel';
    $chapter->save();

    $updates = Revision::query()
        ->where('subject_type', Chapter::class)
        ->where('subject_id', $chapter->id)
        ->latest()
        ->first();

    // Chapter.name ist translatable — HasTranslations schreibt die Werte
    // als JSON in die DB, getOriginal()/getChanges() liefern das cast-
    // Array zurueck. Der Trait speichert genau das, damit der Restore
    // die Locale-Struktur ueber setTranslations() wiederherstellen kann.
    expect($updates->kind)->toBe(RevisionKind::CONTENT->value)
        ->and($updates->version)->toBe(2)
        ->and($updates->snapshot['changes']['name']['old'])->toBe(['de' => 'Original'])
        ->and($updates->snapshot['changes']['name']['new'])->toBe(['de' => 'Neuer Titel']);
});

it('merged Aenderungen innerhalb 5 Minuten in die bestehende Revision', function () {
    /** @var TestCase $this */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);
    $project = makeProject($owner);
    $chapter = makeChapter($project, ['name' => 'Original']);

    // Create-Revision liegt „ausserhalb" des Fensters (6 Minuten zurueck),
    // damit sie nicht mit dem ersten Update mergt.
    Revision::query()->update(['created_at' => Carbon::now()->subMinutes(6)]);

    $chapter->name = 'Zwischenstand';
    $chapter->save();

    // Direkt danach nochmal — muss ins Coalesce-Fenster.
    $chapter->name = 'Endgueltig';
    $chapter->save();

    $updates = Revision::query()
        ->where('subject_type', Chapter::class)
        ->where('subject_id', $chapter->id)
        ->where('version', 2)
        ->get();

    // Genau EINE Update-Revision mit dem finalen Wert und Ur-Old
    // — Werte als translatable Locale-Arrays (siehe Kommentar oben).
    expect($updates)->toHaveCount(1)
        ->and($updates->first()->snapshot['changes']['name']['old'])->toBe(['de' => 'Original'])
        ->and($updates->first()->snapshot['changes']['name']['new'])->toBe(['de' => 'Endgueltig']);
});

it('markiert reine position-Aenderungen als REORDER', function () {
    /** @var TestCase $this */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);
    $project = makeProject($owner);
    $chapter = makeChapter($project);

    Revision::query()->update(['created_at' => Carbon::now()->subMinutes(6)]);

    $chapter->position = 5;
    $chapter->save();

    $update = Revision::query()
        ->where('subject_type', Chapter::class)
        ->where('subject_id', $chapter->id)
        ->latest()
        ->first();

    expect($update->kind)->toBe(RevisionKind::REORDER->value);
});
