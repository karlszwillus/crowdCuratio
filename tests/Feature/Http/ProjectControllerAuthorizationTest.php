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

use App\Models\Chapter;
use App\Models\Comment;
use App\Models\Project;
use App\Models\ProjectUserPermission;
use App\Models\User;
use App\Support\PermissionName;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| ProjectController — Authorization-Pfade unter project-scoped Policy
|--------------------------------------------------------------------------
|
| Block D PR 2 / D.6. Charakterisiert die Controller-Pfade, die nach
| der Policy-Verschärfung project-scoped laufen:
|   - GET /projects (Liste) — Owner sieht eigene + eingeladene
|   - POST /comment-project — Owner/Eingeladener-mit-comment darf,
|     Fremder kriegt 403
|
| Index-Filter geht heute über getAllProjects() im Controller; mit
| D.5/D.6 wandert die Logik in den ProjectPermissionService und die
| Policy spielt project-scoped.
*/

beforeEach(function () {
    foreach (PermissionName::all() as $permissionName) {
        Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
    }

    Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web'])
        ->syncPermissions(Permission::all());
    Role::firstOrCreate(['name' => 'Reader', 'guard_name' => 'web'])
        ->syncPermissions(['view']);
});

// ---------- /projects (Index-Filter) ----------

it('Reader sieht in /projects sein eigenes Project und das, in das er eingeladen ist — nicht aber fremde', function () {
    /** @var TestCase $this */
    /** @var User $reader */
    $reader = User::factory()->create();
    $reader->assignRole('Reader');
    /** @var User $otherOwner */
    $otherOwner = User::factory()->create();
    $otherOwner->assignRole('Reader');

    $ownProject = makeProject($reader, ['name' => 'Eigenes Reader-Projekt']);
    $sharedProject = makeProject($otherOwner, ['name' => 'Geteiltes Projekt']);
    $strangerProject = makeProject($otherOwner, ['name' => 'Fremdes Projekt']);

    $viewPermission = Permission::where('name', 'view')->first();
    ProjectUserPermission::create([
        'user_id' => $reader->id,
        'project_id' => $sharedProject->id,
        'permission_id' => $viewPermission->id,
    ]);

    $this->actingAs($reader);

    $response = $this->get('/projects');

    $response->assertStatus(200);
    $response->assertSee('Eigenes Reader-Projekt');
    $response->assertSee('Geteiltes Projekt');
    $response->assertDontSee('Fremdes Projekt');
});

it('Admin sieht in /projects alle Projects', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    /** @var User $other */
    $other = User::factory()->create();
    $other->assignRole('Reader');

    $projectA = makeProject($other, ['name' => 'Projekt-Alpha']);
    $projectB = makeProject($other, ['name' => 'Projekt-Beta']);

    $this->actingAs($admin);

    $response = $this->get('/projects');

    $response->assertStatus(200);
    $response->assertSee('Projekt-Alpha');
    $response->assertSee('Projekt-Beta');
});

// ---------- /comment-project (Comment-Authorize) ----------

it('Comment: Owner darf auf seinem Project kommentieren', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Reader');
    $project = makeProject($owner);

    $this->actingAs($owner);

    $response = $this->post(route('comment.project'), [
        'id' => $project->id,
        'comment' => 'Erster Kommentar des Owners',
    ]);

    expect($response->status())->toBeIn([200, 302]);
});

it('Comment: Eingeladener mit comment-Permission darf kommentieren', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Reader');
    /** @var User $invitee */
    $invitee = User::factory()->create();
    $invitee->assignRole('Reader');
    $project = makeProject($owner);

    $commentPermission = Permission::where('name', 'comment')->first();
    ProjectUserPermission::create([
        'user_id' => $invitee->id,
        'project_id' => $project->id,
        'permission_id' => $commentPermission->id,
    ]);

    $this->actingAs($invitee);

    $response = $this->post(route('comment.project'), [
        'id' => $project->id,
        'comment' => 'Kommentar des Eingeladenen',
    ]);

    expect($response->status())->toBeIn([200, 302]);
});

// ---------- editMetaData (E.7a-Hotfix) ----------
//
// Vor dem Hotfix war /project/{id}/metadata nur durch auth-Middleware
// gegated — Reader konnten fremde Project-Metadaten und die
// Permissions-Verwaltung sehen. Plus die View crashte mit
// Undefined Variable $listPermissions, weil der Controller die
// nicht übergab.

it('editMetaData: Fremder darf fremde Project-Metadata NICHT öffnen — 403', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Reader');
    /** @var User $stranger */
    $stranger = User::factory()->create();
    $stranger->assignRole('Reader');
    $project = makeProject($owner);

    $this->actingAs($stranger);

    $response = $this->get('/project/'.$project->id.'/metadata');

    $response->assertStatus(403);
});

it('editMetaData: Owner darf seine Project-Metadata öffnen', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Reader');
    $project = makeProject($owner);

    $this->actingAs($owner);

    $response = $this->get('/project/'.$project->id.'/metadata');

    expect($response->status())->toBeIn([200, 302]);
});

it('editMetaData: Admin darf fremde Project-Metadata öffnen', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    /** @var User $other */
    $other = User::factory()->create();
    $other->assignRole('Reader');
    $project = makeProject($other);

    $this->actingAs($admin);

    $response = $this->get('/project/'.$project->id.'/metadata');

    expect($response->status())->toBeIn([200, 302]);
});

/*
|--------------------------------------------------------------------------
| translateCurrentProject — Reader-Frontend-Härtung Juni 2026
|--------------------------------------------------------------------------
|
| Smoke-Findings nach E.7a-Hotfix: der Translate-Pfad
| (translateCurrentProject) hatte vorher nur `auth`-Middleware. Reader
| konnten fremde Project-Inhalte in der Übersetzungs-Maske sehen.
| Inline-Authorize via `update`-Policy schließt das analog zu
| editMetaData.
*/

it('translateCurrentProject: Fremder ohne Einladung kriegt 403', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Reader');
    /** @var User $stranger */
    $stranger = User::factory()->create();
    $stranger->assignRole('Reader');
    $project = makeProject($owner);

    $this->actingAs($stranger);

    $response = $this->get(route('translate', $project->id));

    $response->assertStatus(403);
});

it('translateCurrentProject: Owner darf', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Reader');
    $project = makeProject($owner);

    $this->actingAs($owner);

    $response = $this->get(route('translate', $project->id));

    expect($response->status())->toBeIn([200, 302]);
});

it('translateCurrentProject: Admin darf fremde Übersetzungs-Maske öffnen', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    /** @var User $other */
    $other = User::factory()->create();
    $other->assignRole('Reader');
    $project = makeProject($other);

    $this->actingAs($admin);

    $response = $this->get(route('translate', $project->id));

    expect($response->status())->toBeIn([200, 302]);
});

/*
|--------------------------------------------------------------------------
| Reader-via-URL-Hotfix nach Welle-3-Smoke (2026-06-21)
|--------------------------------------------------------------------------
|
| Karl-Befund: über URL-Manipulation kam ein eingeloggter User in
| fremde Projekte. Sweep über ProjectController fand acht ungegated
| Pfade — sechs view-Pfade plus zwei kritische Permission-Verteil-
| Pfade. Diese Tests pinnen jetzt die Authorize-Gates.
*/

it('show: Fremder darf fremdes Project NICHT öffnen', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Reader');
    /** @var User $stranger */
    $stranger = User::factory()->create();
    $stranger->assignRole('Reader');
    $project = makeProject($owner);

    $this->actingAs($stranger);

    $response = $this->get('/projects/'.$project->id);

    $response->assertStatus(403);
});

it('edit: Fremder darf fremdes Project NICHT in der Edit-Maske sehen', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Reader');
    /** @var User $stranger */
    $stranger = User::factory()->create();
    $stranger->assignRole('Reader');
    $project = makeProject($owner);

    $this->actingAs($stranger);

    $response = $this->get('/projects/'.$project->id.'/edit');

    $response->assertStatus(403);
});

it('edit: Owner darf eigenes Project öffnen', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Reader');
    $project = makeProject($owner);

    $this->actingAs($owner);

    $response = $this->get('/projects/'.$project->id.'/edit');

    expect($response->status())->toBeIn([200, 302]);
});

it('setPermissionForUserOnProject: Fremder kriegt 403 (Privilege Escalation geschlossen)', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Reader');
    /** @var User $stranger */
    $stranger = User::factory()->create();
    $stranger->assignRole('Reader');
    $project = makeProject($owner);

    $this->actingAs($stranger);

    $response = $this
        ->from('/projects/'.$project->id.'/edit')
        ->post(route('project.permission'), [
            'user' => $stranger->id,
            'project' => $project->id,
            'permissions' => [1, 2, 3, 4, 5, 6, 7],
        ]);

    $response->assertStatus(403);
});

it('Comment: Fremder ohne Einladung kriegt 403', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Reader');
    /** @var User $stranger */
    $stranger = User::factory()->create();
    $stranger->assignRole('Reader');
    $project = makeProject($owner);

    $this->actingAs($stranger);

    $response = $this->post(route('comment.project'), [
        'id' => $project->id,
        'comment' => 'Unerlaubter Kommentar',
    ]);

    $response->assertStatus(403);
});

/*
|--------------------------------------------------------------------------
| Welle-4a-Hotfix (2026-06-21) — Spatie Gate::before Reader-Bypass
|--------------------------------------------------------------------------
|
| Karl-Befund: Reader Rolf (Zugriff nur Projekt 18/19) konnte
| /projects/20/edit aufrufen. Diagnose via Tinker:
|   policy.view direct = 0    (Policy sagt korrekt nein)
|   rolf can view project = 1 (Gate sagt fälschlich ja)
|
| Ursache: Spatie's PermissionRegistrar registriert per Default
| ein `Gate::before`, das bei jedem `can()`/`authorize()` zuerst
| `checkPermissionTo($ability)` prüft — ohne Modell-Argument.
| Reader hat globale Permission `view`, also gibt das `Gate::before`
| true zurück, bevor ProjectPolicy::view überhaupt aufgerufen wird.
|
| Fix: `register_permission_check_method => false` in
| config/permission.php, plus Umstellung der globalen Permission-
| Checks auf `hasPermissionTo()` / `@hasPermissionTo`.
|
| Dieser Test pinnt explizit, dass der Bypass jetzt zu ist —
| Reader mit globaler view-Permission kommt NICHT auf fremde
| Project-Edit-Maske durch.
|
| Der davor stehende `edit: Fremder darf...`-Test war
| fälschlich grün — vermutlich weil im Test-Setup Spatie's
| Permission-Cache nicht initialisiert ist und checkPermissionTo
| throw'd, was Laravel als false interpretiert. In Live-Umgebung
| mit hot Cache liefert checkPermissionTo true.
*/

it('Spatie-Bypass: Reader mit globaler view-Permission darf NICHT auf fremdes Project edit', function () {
    /** @var TestCase $this */

    // Spatie's Cache primen, damit checkPermissionTo nicht throw't
    // sondern den realen Live-Pfad nimmt.
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Reader');
    /** @var User $stranger */
    $stranger = User::factory()->create();
    $stranger->assignRole('Reader');

    // Explizit: Stranger hat die globale Spatie-Permission `view`
    // direkt zugewiesen, simuliert volle Cache-Hit-Bedingungen.
    $stranger->givePermissionTo(PermissionName::VIEW->value);

    $project = makeProject($owner);

    $this->actingAs($stranger);

    $response = $this->get('/projects/'.$project->id.'/edit');

    $response->assertStatus(403);
});

it('Spatie-Bypass: hasPermissionTo VIEW true, aber Gate::view auf fremdem Project false', function () {
    /** @var TestCase $this */
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Reader');
    /** @var User $stranger */
    $stranger = User::factory()->create();
    $stranger->assignRole('Reader');
    $stranger->givePermissionTo(PermissionName::VIEW->value);

    $project = makeProject($owner);

    // Globale Permission ja
    expect($stranger->hasPermissionTo(PermissionName::VIEW->value))->toBeTrue();
    // Project-scoped via Gate nein
    expect($stranger->can('view', $project))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Security-Sweep-III (2026-06-22) — Phase-4-Review-Findings
|--------------------------------------------------------------------------
|
| Drei HIGH + zwei MEDIUM-Lücken aus dem Phase-4-Reviewer-Befund.
| Verifikation und Pinning analog zum Welle-3- und 4a-Hotfix-II-Block.
*/

it('Sweep-III: resetValue verlangt eine whitelisted subjectType', function () {
    /** @var TestCase $this */
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    /** @var User $user */
    $user = User::factory()->create();
    $user->assignRole('Reader');

    $this->actingAs($user);

    $response = $this->post(route('log.reset'), [
        'subjectType' => 'App\\Models\\User',
        'subjectId' => 1,
        'nameReset' => 'Hijacked',
    ]);

    $response->assertStatus(403);
});

it('Sweep-III: resetValue blockt Fremde auch bei korrekter subjectType', function () {
    /** @var TestCase $this */
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Reader');
    /** @var User $stranger */
    $stranger = User::factory()->create();
    $stranger->assignRole('Reader');

    $project = makeProject($owner);
    $chapter = makeChapter($project);

    $this->actingAs($stranger);

    $response = $this->post(route('log.reset'), [
        'subjectType' => Chapter::class,
        'subjectId' => $chapter->id,
        'nameReset' => 'Hijacked',
    ]);

    $response->assertStatus(403);
});

it('Sweep-III: ChapterController::index blockt Fremden via GET ?id=', function () {
    /** @var TestCase $this */
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Reader');
    /** @var User $stranger */
    $stranger = User::factory()->create();
    $stranger->assignRole('Reader');

    $project = makeProject($owner);

    $this->actingAs($stranger);

    $response = $this->get('/chapters?id='.$project->id);
    $response->assertStatus(403);
});

it('Sweep-III: inviteUserForProject blockt Fremde', function () {
    /** @var TestCase $this */
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Reader');
    /** @var User $stranger */
    $stranger = User::factory()->create();
    $stranger->assignRole('Reader');
    /** @var User $target */
    $target = User::factory()->create();
    $target->assignRole('Reader');

    $project = makeProject($owner);

    $this->actingAs($stranger);

    $response = $this->get(route('user.info', ['id' => $target->id, 'projectId' => $project->id]));
    $response->assertStatus(403);
});

it('Sweep-III: saveCommentProject blockt Fremde', function () {
    /** @var TestCase $this */
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Reader');
    /** @var User $stranger */
    $stranger = User::factory()->create();
    $stranger->assignRole('Reader');

    $project = makeProject($owner);

    $this->actingAs($stranger);

    $response = $this->post(route('comment.project.save', ['id' => $project->id]), [
        'btn_submit' => 'Edit',
        'pk' => 1,
        'value' => 'Hijacked',
    ]);

    $response->assertStatus(403);
});

it('Sweep-III: setCommentStatusProject blockt Fremde', function () {
    /** @var TestCase $this */
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Reader');
    /** @var User $stranger */
    $stranger = User::factory()->create();
    $stranger->assignRole('Reader');

    $project = makeProject($owner);
    $comment = Comment::create([
        'user_id' => $owner->id,
        'project_id' => $project->id,
        'comment' => 'Owner-Kommentar',
        'status' => 0,
        'commentable_id' => $project->id,
        'commentable_type' => Project::class,
    ]);

    $this->actingAs($stranger);

    $response = $this->post(route('comment.project.status'), [
        'id' => $comment->id,
        'status' => 1,
    ]);

    $response->assertStatus(403);
});
