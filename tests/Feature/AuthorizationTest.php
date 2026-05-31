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
use App\Support\PermissionName;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Permissions und die Admin-Rolle anlegen — kopiert die Wirkung von
 * PermissionTableSeeder + dem Admin-Stück aus CreateAdminUserSeeder,
 * ohne den Admin-User selbst zu erzeugen.
 */
beforeEach(function () {
    foreach (PermissionName::all() as $name) {
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
    $data = array_merge([
        'name' => 'Original Name',
        'imprint' => 'Original Impressum',
        'terms' => 'Original AGB',
        'status' => 'draft',
        'description' => 'Original Beschreibung',
    ], $overrides);

    // F-SEC-010: user_id ist nicht in Project::$fillable. Beim Test-
    // Helper setzen wir die Spalte explizit per Property-Setter,
    // analog zum Controller-Store-Pfad. Tests können den Owner via
    // $overrides['user_id'] umschreiben (für Stranger-Szenarien).
    $userId = $overrides['user_id'] ?? $owner->id;
    unset($data['user_id']);

    $project = new Project;
    $project->fill($data);
    $project->user_id = $userId;
    $project->save();

    return $project;
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

    $response->assertRedirect();
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

    $response->assertRedirect();
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

    $response->assertRedirect();
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

    $response->assertRedirect();
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

// ----------------------------------------------------------------------
// NF-LAR-003 — Create-Pfad-Bypass.
//
// In Phase 1 hatten die Update-/Destroy-Pfade Owner-Checks, die store()-
// Methoden von ChapterController und EntryController aber nicht. Wer
// `add`-Permission in irgendeinem Projekt hatte, konnte in jedem
// anderen Projekt Chapter/Entries anlegen. Diese Tests sichern, dass
// der Create-Pfad jetzt ebenfalls über Owner+Admin gehärtet ist.
// ----------------------------------------------------------------------

test('Intruder darf in fremdem Project KEIN Chapter anlegen (NF-LAR-003a)', function () {
    $owner = User::factory()->create();
    $project = makeProject($owner);

    $intruder = User::factory()->create();

    $response = $this->actingAs($intruder)->post(
        route('chapters.store'),
        [
            'projectId' => $project->id,
            'chapterTitle' => 'HACKED',
            'chapterSubtitle' => 'HACKED',
            'chapterDescription' => 'HACKED',
        ]
    );

    $response->assertForbidden();
    expect(Chapter::where('project_id', $project->id)->count())->toBe(0);
});

test('Admin darf in fremdem Project ein Chapter anlegen (NF-LAR-003a)', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $owner = User::factory()->create();
    $project = makeProject($owner);

    $response = $this->actingAs($admin)->post(
        route('chapters.store'),
        [
            'projectId' => $project->id,
            'chapterTitle' => 'Vom Admin angelegt',
            'chapterSubtitle' => 'Untertitel',
            'chapterDescription' => 'Beschreibung',
        ]
    );

    $response->assertRedirect();
    expect(Chapter::where('project_id', $project->id)->count())->toBe(1);
});

test('Intruder darf in fremdem Chapter KEINEN Entry anlegen (NF-LAR-003b)', function () {
    $owner = User::factory()->create();
    $chapter = makeChapter(makeProject($owner));

    $intruder = User::factory()->create();

    $response = $this->actingAs($intruder)->post(
        route('entries.store'),
        [
            'chapterId' => $chapter->id,
            'entryTitle' => 'HACKED',
            'entrySubtitle' => 'HACKED',
            'entryDescription' => 'HACKED',
        ]
    );

    $response->assertForbidden();
    expect(Entry::where('chapter_id', $chapter->id)->count())->toBe(0);
});

test('Admin darf in fremdem Chapter einen Entry anlegen (NF-LAR-003b)', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $owner = User::factory()->create();
    $chapter = makeChapter(makeProject($owner));

    $response = $this->actingAs($admin)->post(
        route('entries.store'),
        [
            'chapterId' => $chapter->id,
            'entryTitle' => 'Vom Admin angelegt',
            'entrySubtitle' => 'Untertitel',
            'entryDescription' => 'Beschreibung',
        ]
    );

    $response->assertRedirect();
    expect(Entry::where('chapter_id', $chapter->id)->count())->toBe(1);
});

// ----------------------------------------------------------------------
// D.10 — Validation-Pflichttests pro FormRequest (ADR-0017).
//
// Pro FormRequest ein 422-Test, der dokumentiert, dass die rules()-
// Schicht greift. Owner-Pfad, fehlendes Pflichtfeld; Erwartung:
// assertInvalid().
// ----------------------------------------------------------------------

test('StoreChapterRequest: chapterTitle ist Pflicht', function () {
    $owner = User::factory()->create();
    $project = makeProject($owner);

    $response = $this->actingAs($owner)->post(
        route('chapters.store'),
        [
            'projectId' => $project->id,
            // chapterTitle absichtlich weggelassen
            'chapterSubtitle' => 'Sub',
        ]
    );

    $response->assertInvalid(['chapterTitle']);
    expect(Chapter::where('project_id', $project->id)->count())->toBe(0);
});

test('UpdateChapterRequest: chapterTitle ist Pflicht', function () {
    $owner = User::factory()->create();
    $chapter = makeChapter(makeProject($owner));

    $response = $this->actingAs($owner)->patch(
        route('chapters.update', $chapter),
        [
            // chapterTitle absichtlich weggelassen
            'chapterSubtitle' => 'Sub',
        ]
    );

    $response->assertInvalid(['chapterTitle']);
    expect($chapter->fresh()->name)->toBe('Original Kapitel-Titel');
});

test('StoreEntryRequest: entryTitle ist Pflicht', function () {
    $owner = User::factory()->create();
    $chapter = makeChapter(makeProject($owner));

    $response = $this->actingAs($owner)->post(
        route('entries.store'),
        [
            'chapterId' => $chapter->id,
            // entryTitle absichtlich weggelassen
        ]
    );

    $response->assertInvalid(['entryTitle']);
    expect(Entry::where('chapter_id', $chapter->id)->count())->toBe(0);
});

test('UpdateEntryRequest: entryTitle ist Pflicht', function () {
    $owner = User::factory()->create();
    $entry = makeEntry(makeChapter(makeProject($owner)));

    $response = $this->actingAs($owner)->patch(
        route('entries.update', $entry),
        [
            // entryTitle absichtlich weggelassen
            'entrySubtitle' => 'Sub',
        ]
    );

    $response->assertInvalid(['entryTitle']);
    expect($entry->fresh()->name)->toBe('Original Entry-Titel');
});

// ----------------------------------------------------------------------
// D.12/D.13 — Sanity-Tests, dass die PATCH-Variante greift.
//
// Die ursprünglichen Update-Tests senden PUT, was bei Resource-Routes
// gleichbedeutend ist. Diese zwei Tests dokumentieren explizit den
// PATCH-Pfad, den das Frontend nach D.12/D.13 nutzt.
// ----------------------------------------------------------------------

test('Owner darf Chapter via PATCH ändern (D.12)', function () {
    $owner = User::factory()->create();
    $chapter = makeChapter(makeProject($owner));

    $response = $this->actingAs($owner)->patch(
        route('chapters.update', $chapter),
        [
            'chapterTitle' => 'Via PATCH geändert',
        ]
    );

    $response->assertRedirect();
    expect($chapter->fresh()->name)->toBe('Via PATCH geändert');
});

test('Owner darf Entry via PATCH ändern (D.13)', function () {
    $owner = User::factory()->create();
    $entry = makeEntry(makeChapter(makeProject($owner)));

    $response = $this->actingAs($owner)->patch(
        route('entries.update', $entry),
        [
            'entryTitle' => 'Via PATCH geändert',
        ]
    );

    $response->assertRedirect();
    expect($entry->fresh()->name)->toBe('Via PATCH geändert');
});

// ----------------------------------------------------------------------
// D.3 + D.6 — Validation pro StoreProjectRequest / UpdateProjectRequest
// inkl. der NF-SEC-001 / NF-SEC-007-Härtung beim Bild-Upload.
// ----------------------------------------------------------------------

test('StoreProjectRequest: name ist Pflicht', function () {
    $owner = User::factory()->create();
    // ProjectController hat middleware('permission:add', […create, store]).
    // Ohne add-Permission wirft die Spatie-Middleware vor dem FormRequest,
    // und der Test sieht 302 ohne errors-Bag statt 422 von der Validation.
    $owner->givePermissionTo(PermissionName::ADD);

    $response = $this->actingAs($owner)
        ->from(route('projects.create'))
        ->post(
            route('projects.store'),
            [
                // name absichtlich weggelassen
                'imprint' => 'Pflicht-Impressum',
            ]
        );

    $response->assertInvalid(['name']);
    expect(Project::count())->toBe(0);
});

test('UpdateProjectRequest: name ist Pflicht', function () {
    $owner = User::factory()->create();
    $project = makeProject($owner);

    $response = $this->actingAs($owner)
        ->from(route('projects.edit', $project))
        ->put(
            route('projects.update', $project),
            [
                // name absichtlich weggelassen
                'imprint' => 'Geänderter Imprint',
            ]
        );

    $response->assertInvalid(['name']);
    expect($project->fresh()->name)->toBe('Original Name');
});

test('UpdateProjectRequest: project_image akzeptiert nur Bild-MIME-Typen (NF-SEC-001)', function () {
    $owner = User::factory()->create();
    $project = makeProject($owner);

    $response = $this->actingAs($owner)
        ->from(route('projects.edit', $project))
        ->put(
            route('projects.update', $project),
            [
                'name' => 'Trotzdem geänderter Name',
                'imprint' => 'Imprint',
                'project_image' => UploadedFile::fake()->create('exploit.php', 50, 'application/x-php'),
            ]
        );

    $response->assertInvalid(['project_image']);
    expect($project->fresh()->name)->toBe('Original Name');
});

// ----------------------------------------------------------------------
// D.7 — RegisterRequest Validation + Gast-Pfad (ADR-0017).
//
// Der Gast-Pfad-Test prüft, dass die Route für nicht-eingeloggte
// User erreichbar ist (kein 302-Redirect zu Login). Ohne explizites
// actingAs() sendet Pest als Gast. Das Validation-Fail-422 statt
// 401/302 belegt: die Route ist offen, der FormRequest greift.
// ----------------------------------------------------------------------

test('RegisterRequest: firstName ist Pflicht (Admin-Einladung)', function () {
    // Seit NF-SEC-202-Hotfix (Phase 2.5) ist /register ein reiner
    // Admin-Endpoint unter `auth + role:Admin`. AM-D-4 (die alte
    // Doppel-Route guest/auth) ist mit beseitigt; ein
    // Self-Service-Gast-Pfad existiert nicht mehr und wird auch
    // nicht getestet.
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $response = $this->actingAs($admin)
        ->from(route('register'))
        ->post(
            route('register'),
            [
                // firstName absichtlich weggelassen
                'lastName' => 'Mustermann',
                'email' => 'max@example.com',
                'roles' => 2,
                'policy' => 1,
            ]
        );

    $response->assertInvalid(['firstName']);
    expect(User::count())->toBe(1); // nur der Admin selbst, kein neuer User
});

// ----------------------------------------------------------------------
// NF-SEC-202 / Phase-2.5-Hotfix — Privilege-Escalation über /register.
//
// Vor dem Hotfix: `User::$fillable` enthielt `is_admin`, der
// RegisterRequest::authorize() war `true`, die Route hatte keinen
// Rollenfilter. Jeder eingeloggte User (Reader, Reviewer, Editor)
// konnte sich über `POST /register` mit `adminUser=1` ein Admin-
// Konto anlegen.
//
// Nach dem Hotfix: zwei Schichten. Route hängt an `role:Admin`,
// Controller setzt `is_admin`/`create_project` nur dann, wenn der
// Caller selbst die Spatie-Rolle „Admin" hat. AM-D-4 (Doppel-
// Route in auth.php) ist mit beseitigt.
// ----------------------------------------------------------------------

test('NF-SEC-202: non-Admin kann keinen User über /register anlegen', function () {
    $intruder = User::factory()->create();
    // keine Rolle — `role:Admin`-Route-Middleware muss 403 liefern.

    $response = $this->actingAs($intruder)
        ->from(route('register'))
        ->post(route('register'), [
            'firstName' => 'Mallory',
            'lastName' => 'Intruder',
            'email' => 'mallory@example.com',
            'roles' => 'Editor',
            'policy' => 1,
            'adminUser' => 1,
        ]);

    $response->assertForbidden();
    expect(User::where('email', 'mallory@example.com')->exists())->toBeFalse();
});

test('NF-SEC-202: Admin kann Admin-Einladung anlegen, is_admin wird gesetzt', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $response = $this->actingAs($admin)
        ->from(route('register'))
        ->post(route('register'), [
            'firstName' => 'Alice',
            'lastName' => 'Admin',
            'email' => 'alice.admin@example.com',
            'roles' => 'Admin',
            'policy' => 1,
            'adminUser' => 1,
        ]);

    $response->assertRedirect();
    $newUser = User::where('email', 'alice.admin@example.com')->firstOrFail();
    expect((bool) $newUser->is_admin)->toBeTrue();
    expect($newUser->hasRole('Admin'))->toBeTrue();
});

test('NF-SEC-202: Admin lädt regulären User ein — is_admin bleibt false', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    Role::firstOrCreate(['name' => 'Editor', 'guard_name' => 'web']);

    $response = $this->actingAs($admin)
        ->from(route('register'))
        ->post(route('register'), [
            'firstName' => 'Bob',
            'lastName' => 'Editor',
            'email' => 'bob.editor@example.com',
            'roles' => 'Editor',
            'policy' => 1,
            // adminUser absichtlich nicht gesetzt
        ]);

    $response->assertRedirect();
    $newUser = User::where('email', 'bob.editor@example.com')->firstOrFail();
    expect((bool) $newUser->is_admin)->toBeFalse();
    expect($newUser->hasRole('Admin'))->toBeFalse();
});

test('NF-SEC-202: Gast wird auf Login umgeleitet (auth-Middleware vor role:Admin)', function () {
    $response = $this->post(route('register'), [
        'firstName' => 'Eve',
        'lastName' => 'Guest',
        'email' => 'eve@example.com',
        'roles' => 'Editor',
        'policy' => 1,
        'adminUser' => 1,
    ]);

    $response->assertRedirect(route('login'));
    expect(User::where('email', 'eve@example.com')->exists())->toBeFalse();
});

// ----------------------------------------------------------------------
// NF-SEC-201 / Vor-Phase-3-Härtung — Upload-Pfade in ContentController
// und AudiovisualController bekommen MIME-Whitelist und Size-Limit
// über StoreImageBlockRequest und StoreAudiovisualRequest. Vorher
// liefen beide Routen mit `$request->validate(['image' => 'required'])`
// bzw. ganz ohne File-Validation.
// ----------------------------------------------------------------------

test('NF-SEC-201: image.store weist ungültigen MIME ab (.php-Datei)', function () {
    $owner = User::factory()->create();

    $response = $this->actingAs($owner)
        ->from(route('chapters.index', ['id' => 1]))
        ->post(route('image.store'), [
            'galleryId' => 1,
            'altText' => 'irrelevant',
            'copyrightImage' => 'Quelle X',
            'originImage' => 'Quelle Y',
            'image' => UploadedFile::fake()->create('exploit.php', 50, 'application/x-php'),
        ]);

    $response->assertInvalid(['image']);
});

test('NF-SEC-201: image.store weist zu großes File ab (>4 MB)', function () {
    $owner = User::factory()->create();

    $response = $this->actingAs($owner)
        ->from(route('chapters.index', ['id' => 1]))
        ->post(route('image.store'), [
            'galleryId' => 1,
            'altText' => 'irrelevant',
            'copyrightImage' => 'Quelle X',
            'originImage' => 'Quelle Y',
            // 5 MB JPEG — über dem 4 MB Limit aus StoreImageBlockRequest.
            'image' => UploadedFile::fake()->image('huge.jpg')->size(5 * 1024),
        ]);

    $response->assertInvalid(['image']);
});

test('NF-SEC-201: save.audiovisual weist nicht-Audio-MIME ab (.exe)', function () {
    $owner = User::factory()->create();

    $response = $this->actingAs($owner)
        ->from(route('dashboard'))
        ->post(route('save.audiovisual'), [
            'audio' => UploadedFile::fake()->create('payload.exe', 50, 'application/x-msdownload'),
        ]);

    $response->assertInvalid(['audio']);
});

// ----------------------------------------------------------------------
// F-SEC-010 / Vor-Phase-3-Härtung — Project.user_id ist nicht mehr
// in $fillable. Ein User darf keine fremde user_id über das
// Project-Store-Form injizieren.
// ----------------------------------------------------------------------

test('F-SEC-010: user_id aus Request wird beim Project-Store ignoriert', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    /** @var User $stranger */
    $stranger = User::factory()->create();
    $owner->givePermissionTo(PermissionName::ADD);

    $response = $this->actingAs($owner)
        ->from(route('projects.create'))
        ->post(route('projects.store'), [
            'name' => 'Eigenes Projekt',
            'imprint' => 'Pflicht-Impressum',
            // Mass-Assignment-Versuch: User-Owner umlenken.
            'user_id' => $stranger->id,
        ]);

    $response->assertRedirect();
    // Project::name ist translatable und landet als JSON in der DB —
    // deshalb über user_id suchen, nicht über den Klartext-Namen.
    $project = Project::where('user_id', $owner->id)->firstOrFail();
    expect($project->user_id)->toBe($owner->id);
    expect($project->user_id)->not->toBe($stranger->id);
});

// ----------------------------------------------------------------------
// F-API-009 / Vor-Phase-3-Härtung — POST /drag hat jetzt einen
// Authorization-Gate über ProjectPolicy::update. Vorher konnte jeder
// eingeloggte User fremde Chapter, Entries und MediaContent
// umsortieren.
// ----------------------------------------------------------------------

test('F-API-009: Intruder kann kein chapter-reorder in fremdem Project', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $chapter = makeChapter($project, ['name' => 'Kapitel A', 'position' => 0]);

    $intruder = User::factory()->create();

    $response = $this->actingAs($intruder)
        ->post(route('chapter.drag'), [
            'data' => [
                'element' => 'chapter',
                'data' => [$chapter->id],
            ],
        ]);

    $response->assertForbidden();
    expect($chapter->fresh()->position)->toBe(0);
});

test('F-API-009: Owner darf chapter-reorder in eigenem Project', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $a = makeChapter($project, ['name' => 'Kapitel A', 'position' => 0]);
    $b = makeChapter($project, ['name' => 'Kapitel B', 'position' => 1]);

    $response = $this->actingAs($owner)
        ->post(route('chapter.drag'), [
            'data' => [
                'element' => 'chapter',
                'data' => [$b->id, $a->id],
            ],
        ]);

    $response->assertOk();
    // Reihenfolge gedreht.
    expect($b->fresh()->position)->toBe(1);
    expect($a->fresh()->position)->toBe(2);
});
