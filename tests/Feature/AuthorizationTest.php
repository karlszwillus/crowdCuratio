<?php
/**
 * Authorization-Bypass-Suite — Phase 1 / D.4.
 *
 * Diese Tests bilden die in der Tiefenanalyse identifizierten Blocker
 * B-3 (F-SEC-007) und B-4 (F-LAR-001) als Pest-Tests ab. Im Zustand
 * vor den Fixes (D.6–D.10) sind die als "MUSS 403" markierten Tests
 * rot — sie demonstrieren, dass jeder eingeloggte User fremde
 * Projekte und Chapter ändern kann. Nach den Fixes werden sie grün.
 *
 * Vier-User-Matrix für die Tests:
 *  - $owner    — Eigentümer des Projects (projects.user_id)
 *  - $admin    — globale Admin-Rolle, darf alles
 *  - $intruder — eingeloggter User ohne Bezug zum Project, MUSS 403 bekommen
 *
 * Stand 2026-05-28: die Tests gehen davon aus, dass die
 * Update-/Destroy-Routen unter `projects.update` und
 * `projects.destroy` erreichbar sind und Route-Model-Binding
 * verwenden — ein Blick in routes/web.php bestätigt das.
 *
 * Referenzen: .werkbank/REVIEW/04-security.md (F-SEC-007),
 * .werkbank/REVIEW/07-laravel.md (F-LAR-001), .werkbank/ADR/0013.
 */

use App\Models\Project;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Permissions und die Admin-Rolle anlegen — kopiert die Wirkung von
 * PermissionTableSeeder + dem Admin-Stück aus CreateAdminUserSeeder,
 * ohne den Admin-User selbst zu erzeugen.
 */
beforeEach(function () {
    foreach (['view', 'add', 'edit', 'delete', 'publish', 'comment', 'invite'] as $name) {
        Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web'])
        ->syncPermissions(Permission::all());
});

/**
 * Hilfs-Funktion zum Anlegen eines Test-Projects mit den
 * Pflichtfeldern aus dem Schema.
 */
function makeProject(User $owner, array $overrides = []): Project
{
    return Project::create(array_merge([
        'user_id' => $owner->id,
        'name' => 'Original Name',
        'imprint' => 'Original Impressum',
        'terms' => 'Original AGB',
        'status' => 'draft',
        'description' => 'Original Beschreibung',
    ], $overrides));
}

test('Owner darf sein eigenes Project ändern', function () {
    $owner = User::factory()->create();
    $project = makeProject($owner);

    $response = $this->actingAs($owner)->put(
        route('projects.update', $project),
        [
            'name' => 'Vom Owner geändert',
            'imprint' => 'Owner-Impressum',
        ]
    );

    $response->assertRedirect();
    expect($project->fresh()->name)->toBe('Vom Owner geändert');
});

test('Admin darf jedes Project ändern', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $owner = User::factory()->create();
    $project = makeProject($owner);

    $response = $this->actingAs($admin)->put(
        route('projects.update', $project),
        [
            'name' => 'Vom Admin geändert',
            'imprint' => 'Admin-Impressum',
        ]
    );

    $response->assertRedirect();
    expect($project->fresh()->name)->toBe('Vom Admin geändert');
});

/**
 * BYPASS-TEST — B-3 (F-SEC-007).
 *
 * Erwartet 403. Pre-Fix-Stand: läuft als 200/302 durch, Project wird
 * tatsächlich verändert. Genau das demonstriert der Test.
 */
test('Intruder darf fremdes Project NICHT ändern (B-3)', function () {
    $owner = User::factory()->create();
    $project = makeProject($owner);

    $intruder = User::factory()->create();

    $response = $this->actingAs($intruder)->put(
        route('projects.update', $project),
        [
            'name' => 'HACKED',
            'imprint' => 'HACKED',
        ]
    );

    $response->assertForbidden();
    expect($project->fresh()->name)->toBe('Original Name')
        ->and($project->fresh()->imprint)->toBe('Original Impressum');
});

/**
 * BYPASS-TEST — B-4 (F-LAR-001).
 *
 * Erwartet 403 beim DELETE. Pre-Fix-Stand: die auskommentierte
 * Permission-Middleware auf `destroy` lässt jeden eingeloggten User
 * fremde Projects löschen.
 */
test('Intruder darf fremdes Project NICHT löschen (B-4)', function () {
    $owner = User::factory()->create();
    $project = makeProject($owner);

    $intruder = User::factory()->create();

    $response = $this->actingAs($intruder)->delete(
        route('projects.destroy', $project)
    );

    $response->assertForbidden();
    expect(Project::query()->find($project->id))->not->toBeNull();
});

/**
 * Bonus-Test — Owner darf sein eigenes Project löschen.
 * Dient als Sanity-Check, dass die Authorization nicht überschießt
 * (Owner muss seine Rechte behalten).
 */
test('Owner darf sein eigenes Project löschen', function () {
    $owner = User::factory()->create();
    $project = makeProject($owner);

    $response = $this->actingAs($owner)->delete(
        route('projects.destroy', $project)
    );

    $response->assertRedirect();
    expect(Project::query()->withTrashed()->find($project->id)?->trashed())->toBeTrue();
});
