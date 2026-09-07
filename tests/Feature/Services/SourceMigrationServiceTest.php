<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 */

use App\Models\Project;
use App\Models\Source;
use App\Models\Text;
use App\Models\User;
use App\Services\SourceMigrationService;
use App\Support\PermissionName;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Q4-Etappe 3 / C0-8b · Migrations-Assistent
|--------------------------------------------------------------------------
|
| Deckt die drei operativen Punkte ab:
|   - candidatesFor: findet Alt-Sources, die von einem Projekt referenziert werden
|   - runFor(dryRun): meldet nur, veraendert keine DB
|   - runFor(commit): legt project-scoped Zeilen an, biegt Referenzen um,
|                     schreibt Backup und ist idempotent
*/

beforeEach(function () {
    foreach (PermissionName::all() as $permissionName) {
        Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
    }
    Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web'])
        ->syncPermissions(Permission::all());
});

/**
 * Legt einen Text-Block im gegebenen Projekt an, dessen Origin/
 * Copyright auf die uebergebenen projektlosen Alt-Sources zeigt.
 */
function attachTextWithSources(Project $project, Source $origin, Source $copyright): Text
{
    $text = makeText(['origin' => $origin->id, 'copyright' => $copyright->id]);
    attachToProject($project, $text);

    return $text;
}

it('candidatesFor findet Alt-Sources, die vom Projekt referenziert sind', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);

    // Zwei projektlose Alt-Sources vorbereiten.
    $origin = Source::create([
        'type' => 'Origin',
        'name' => json_encode(['de' => 'Landesarchiv Berlin']),
    ]);
    $copyright = Source::create([
        'type' => 'Copyright',
        'name' => json_encode(['de' => 'CC-BY-SA 4.0']),
    ]);

    attachTextWithSources($project, $origin, $copyright);

    $service = app(SourceMigrationService::class);
    $candidates = $service->candidatesFor($project->id);

    expect($candidates->pluck('id')->all())
        ->toContain($origin->id)
        ->toContain($copyright->id);
});

it('runFor mit dry-run meldet Kandidaten, ohne DB-Writes', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);

    $origin = Source::create([
        'type' => 'Origin',
        'name' => json_encode(['de' => 'Landesarchiv Berlin']),
    ]);
    $copyright = Source::create([
        'type' => 'Copyright',
        'name' => json_encode(['de' => 'CC-BY-SA 4.0']),
    ]);

    attachTextWithSources($project, $origin, $copyright);

    $before = Source::count();

    $service = app(SourceMigrationService::class);
    $report = $service->runFor($project->id, dryRun: true);

    expect($report['dry_run'])->toBeTrue();
    expect($report['candidates'])->toBe(2);
    expect($report['groups'])->toBe(2);
    expect($report['migrated'])->toBe(0);
    // Keine neuen Rows angelegt.
    expect(Source::count())->toBe($before);
    // Alt-Sources bleiben projektlos.
    expect(Source::findOrFail($origin->id)->project_id)->toBeNull();
});

it('runFor commit legt project-scoped Rows an und biegt Referenzen um', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);

    $origin = Source::create([
        'type' => 'Origin',
        'name' => json_encode(['de' => 'Landesarchiv Berlin']),
    ]);
    $copyright = Source::create([
        'type' => 'Copyright',
        'name' => json_encode(['de' => 'CC-BY-SA 4.0']),
    ]);

    $text = attachTextWithSources($project, $origin, $copyright);

    $service = app(SourceMigrationService::class);
    $report = $service->runFor($project->id, dryRun: false);

    expect($report['migrated'])->toBe(2);

    // Zwei neue project-scoped Rows existieren.
    $projectSources = Source::where('project_id', $project->id)->get();
    expect($projectSources)->toHaveCount(2);
    $projectSources->each(function (Source $source) use ($origin, $copyright) {
        expect($source->original_id)->toBeIn([$origin->id, $copyright->id]);
    });

    // Referenzen im Text zeigen jetzt auf die neuen Rows.
    $text->refresh();
    $newOrigin = $projectSources->firstWhere('type', 'Origin');
    $newCopyright = $projectSources->firstWhere('type', 'Copyright');
    expect($text->origin)->toBe($newOrigin->id);
    expect($text->copyright)->toBe($newCopyright->id);

    // Backup-Zeilen existieren.
    $backups = DB::table('sources_backup')->where('run_key', $report['run_key'])->count();
    expect($backups)->toBe(2);
});

it('runFor commit ist idempotent — zweiter Lauf findet keine Kandidaten mehr', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);

    $origin = Source::create([
        'type' => 'Origin',
        'name' => json_encode(['de' => 'Landesarchiv Berlin']),
    ]);
    $copyright = Source::create([
        'type' => 'Copyright',
        'name' => json_encode(['de' => 'CC-BY-SA 4.0']),
    ]);

    attachTextWithSources($project, $origin, $copyright);

    $service = app(SourceMigrationService::class);
    $service->runFor($project->id, dryRun: false);

    // Zweiter Lauf.
    $secondReport = $service->runFor($project->id, dryRun: false);

    expect($secondReport['candidates'])->toBe(0);
    expect($secondReport['migrated'])->toBe(0);
});

it('classifyKind schlaegt kind auf Basis der Keyword-Regel vor', function () {
    $service = app(SourceMigrationService::class);

    expect($service->classifyKind('Landesarchiv Berlin'))->toBe('archivalie');
    expect($service->classifyKind('Suhrkamp Verlag'))->toBe('publikation');
    expect($service->classifyKind('Interview Frau Meier'))->toBe('interview');
    expect($service->classifyKind('BPK Bildarchiv'))->toBe('abbildung');
    expect($service->classifyKind('Unbekannt'))->toBeNull();
});

it('looksLikeFreetext erkennt kurze Namen als Freitext-Kandidaten', function () {
    $service = app(SourceMigrationService::class);

    expect($service->looksLikeFreetext('KLM'))->toBeTrue();      // < 4 Zeichen
    expect($service->looksLikeFreetext('Meier'))->toBeTrue();     // 1 Wort, kurz
    expect($service->looksLikeFreetext('Landesarchiv Berlin'))->toBeFalse();
    expect($service->looksLikeFreetext('A Rep. 042'))->toBeFalse(); // enthaelt Zahl
});
