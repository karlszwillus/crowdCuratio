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

use App\Models\Chapter;
use App\Models\Entry;
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

function makeChapter(Project $project, array $overrides = []): Chapter
{
    return Chapter::create(array_merge([
        'project_id' => $project->id,
        'name' => 'Original Kapitel-Titel',
        'subtitle' => 'Original Untertitel',
        'description' => 'Original Beschreibung',
        'position' => 0,
    ], $overrides));
}

function makeEntry(Chapter $chapter, array $overrides = []): Entry
{
    return Entry::create(array_merge([
        'chapter_id' => $chapter->id,
        'name' => 'Original Entry-Titel',
        'subtitle' => 'Original Untertitel',
        'description' => 'Original Beschreibung',
        'position' => 0,
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

// ----------------------------------------------------------------------
// Chapter-Suite — Owner-Logik transitiv über Project.
// ----------------------------------------------------------------------

test('Owner darf eigenes Chapter ändern', function () {
    $owner = User::factory()->create();
    $chapter = makeChapter(makeProject($owner));

    $response = $this->actingAs($owner)->put(
        route('chapters.update', $chapter),
        [
            'chapterId' => $chapter->id,
            'chapterTitle' => 'Vom Owner geändert',
            'chapterSubtitle' => 'Untertitel',
            'chapterDescription' => 'Beschreibung',
        ]
    );

    $response->assertSuccessful();
    expect($chapter->fresh()->name)->toBe('Vom Owner geändert');
});

test('Admin darf jedes Chapter ändern', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $owner = User::factory()->create();
    $chapter = makeChapter(makeProject($owner));

    $response = $this->actingAs($admin)->put(
        route('chapters.update', $chapter),
        [
            'chapterId' => $chapter->id,
            'chapterTitle' => 'Vom Admin geändert',
            'chapterSubtitle' => 'Untertitel',
            'chapterDescription' => 'Beschreibung',
        ]
    );

    $response->assertSuccessful();
    expect($chapter->fresh()->name)->toBe('Vom Admin geändert');
});

test('Intruder darf fremdes Chapter NICHT ändern (B-3a)', function () {
    $owner = User::factory()->create();
    $chapter = makeChapter(makeProject($owner));

    $intruder = User::factory()->create();

    $response = $this->actingAs($intruder)->put(
        route('chapters.update', $chapter),
        [
            'chapterId' => $chapter->id,
            'chapterTitle' => 'HACKED',
            'chapterSubtitle' => 'HACKED',
            'chapterDescription' => 'HACKED',
        ]
    );

    $response->assertForbidden();
    expect($chapter->fresh()->name)->toBe('Original Kapitel-Titel');
});

test('Intruder darf fremdes Chapter NICHT löschen (B-4a)', function () {
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $chapter = makeChapter($project);

    $intruder = User::factory()->create();

    $response = $this->actingAs($intruder)->delete(
        route('chapters.destroy', $chapter),
        ['project' => $project->id]
    );

    $response->assertForbidden();
    expect(Chapter::query()->find($chapter->id))->not->toBeNull();
});

// ----------------------------------------------------------------------
// Entry-Suite — Owner-Logik über Chapter → Project.
// ----------------------------------------------------------------------

test('Owner darf eigenen Entry ändern', function () {
    $owner = User::factory()->create();
    $entry = makeEntry(makeChapter(makeProject($owner)));

    $response = $this->actingAs($owner)->put(
        route('entries.update', $entry),
        [
            'entryId' => $entry->id,
            'entryTitle' => 'Vom Owner geändert',
            'entrySubtitle' => 'Untertitel',
            'entryDescription' => 'Beschreibung',
        ]
    );

    $response->assertSuccessful();
    expect($entry->fresh()->name)->toBe('Vom Owner geändert');
});

test('Admin darf jeden Entry ändern', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $owner = User::factory()->create();
    $entry = makeEntry(makeChapter(makeProject($owner)));

    $response = $this->actingAs($admin)->put(
        route('entries.update', $entry),
        [
            'entryId' => $entry->id,
            'entryTitle' => 'Vom Admin geändert',
            'entrySubtitle' => 'Untertitel',
            'entryDescription' => 'Beschreibung',
        ]
    );

    $response->assertSuccessful();
    expect($entry->fresh()->name)->toBe('Vom Admin geändert');
});

test('Intruder darf fremden Entry NICHT ändern (B-3b)', function () {
    $owner = User::factory()->create();
    $entry = makeEntry(makeChapter(makeProject($owner)));

    $intruder = User::factory()->create();

    $response = $this->actingAs($intruder)->put(
        route('entries.update', $entry),
        [
            'entryId' => $entry->id,
            'entryTitle' => 'HACKED',
            'entrySubtitle' => 'HACKED',
            'entryDescription' => 'HACKED',
        ]
    );

    $response->assertForbidden();
    expect($entry->fresh()->name)->toBe('Original Entry-Titel');
});

test('Intruder darf fremden Entry NICHT löschen (B-4b)', function () {
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $entry = makeEntry(makeChapter($project));

    $intruder = User::factory()->create();

    $response = $this->actingAs($intruder)->delete(
        route('entries.destroy', $entry),
        ['project' => $project->id]
    );

    $response->assertForbidden();
    expect(Entry::query()->find($entry->id))->not->toBeNull();
});
