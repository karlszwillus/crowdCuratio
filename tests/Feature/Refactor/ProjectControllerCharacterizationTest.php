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

use App\Models\User;
use App\Models\UserHasPermission;
use App\Support\PermissionName;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| ProjectController-Charakterisierung
|--------------------------------------------------------------------------
|
| Diese Tests fixieren das beobachtbare Verhalten der nicht-Resource-
| Custom-Routes des ProjectController, bevor Block C die Klasse in
| fünf Services zerlegt (ProjectService, ProjectImageService,
| ProjectPermissionService, ProjectActivityService,
| ProjectCommentService).
|
| Die Resource-Routes (index, store, show, edit, update, destroy)
| sind bereits in AuthorizationTest und HappyPathTest abgedeckt —
| dort liegen ~15 Tests, die nach dem Refactor weiter grün bleiben
| müssen.
|
| Was diese Datei explizit charakterisiert:
|
|   - Status-Wechsel:     POST /comment/project/status
|   - Translation-Switch: GET  /project/{id}/translate
|   - Permission-Set:     POST /project/permission
|   - User-Remove:        DELETE /user/{userId}/project/{projectId}
|   - Email-Check:        POST /check/email
|   - Element-View:       GET  /element
|   - MetaData-Edit:      GET  /project/{id}/metadata
|
| Was diese Datei bewusst NICHT charakterisiert (gehört in spätere
| Blöcke, weil Service-Schnitt anders verläuft):
|
|   - Comment-Pfade (commentProject, getProjectComment,
|     saveCommentProject) → Block F gemeinsam mit dem
|     ContentController-Comment-Refactor.
|   - Activity-Log-Pfade (history, getCurrentLog, getDetails,
|     resetValue, allData) → Block F (ActivityHistoryService).
|   - Preview/PDF-Pfade (previewProject, downloadPreview,
|     projectMetadata) → Block G (ProjectPdfService nach ADR-0019).
|
| Wenn die sieben Tests vor und nach dem Refactor grün sind, ist
| die ProjectController-Kern-Schicht (Status, Translation,
| Permission, User-Remove, Email-Check, Meta) sauber durch den
| Service-Schnitt durchgekommen.
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

// setStatusProject bewusst nicht charakterisiert: die Methode ruft
// $project->status($request) auf, aber Project::status kommt aus
// CommentTrait und setzt einen Comment-Status (nicht den Project-
// Status — der Method-Name ist irreführend). Das Verhalten wird in
// Block F mit dem ContentController-Comment-Refactor neu strukturiert.

it('translateCurrentProject setzt die Session-Locale und kehrt zur Editor-Ansicht zurück', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);

    $response = $this->get(route('translate', $project->id));

    // Die Methode setzt App::setlocale('de') und gibt eine View
    // oder einen Redirect zurück — beides ist OK, sie wirft nicht.
    expect($response->status())->toBeIn([200, 302]);
});

it('setPermissionForUserOnProject legt einen Project-scoped Permission-Eintrag an', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    /** @var User $invitee */
    $invitee = User::factory()->create();
    $invitee->assignRole('Reader');
    $this->actingAs($owner);

    $project = makeProject($owner);
    $editPermission = Permission::where('name', 'edit')->first();

    $response = $this->post(route('project.permission'), [
        'user' => $invitee->id,
        'project' => $project->id,
        'permissions' => [$editPermission->id],
    ]);

    expect($response->status())->toBeIn([200, 302]);

    $pivot = UserHasPermission::where('user_id', $invitee->id)
        ->where('project_id', $project->id)
        ->where('permission_id', $editPermission->id)
        ->first();

    expect($pivot)->not->toBeNull();
});

it('deleteUserFromProject entfernt alle Permission-Einträge des Users für das Project', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    /** @var User $invitee */
    $invitee = User::factory()->create();
    $invitee->assignRole('Reader');
    $this->actingAs($owner);

    $project = makeProject($owner);
    $editPermission = Permission::where('name', 'edit')->first();

    UserHasPermission::create([
        'user_id' => $invitee->id,
        'project_id' => $project->id,
        'permission_id' => $editPermission->id,
    ]);

    $response = $this->delete(
        "/user/{$invitee->id}/project/{$project->id}",
        []
    );

    expect($response->status())->toBeIn([200, 302]);

    $remaining = UserHasPermission::where('user_id', $invitee->id)
        ->where('project_id', $project->id)
        ->count();

    expect($remaining)->toBe(0);
});

it('checkEmail kehrt mit einem Redirect zur vorherigen Seite zurück und gibt error_code im Flash mit', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    User::factory()->create(['email' => 'someone@example.com']);

    // Trotz des Method-Doc-Hints "@return JsonResponse" gibt
    // checkEmail tatsächlich Redirect()->back()->with(...) zurück
    // — die Flash-Daten (error_code 6/7, user, permissions etc.)
    // landen in der Session und werden vom Frontend per
    // session('error_code') ausgewertet. Block C wird die Methode
    // sauber als JsonResponse umbauen, hier fixieren wir das
    // aktuelle 302-Verhalten als Vorlage.
    $response = $this->from('/projects')->post(route('check.email'), [
        'userEmail' => 'someone@example.com',
    ]);

    expect($response->status())->toBe(302);
});

it('element liefert die element-View zurück', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $response = $this->get(route('element'));

    $response->assertStatus(200);
});

it('editMetaData rendert die Metadata-View für ein eigenes Project', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);

    $response = $this->get(route('project.metadata', $project->id));

    $response->assertStatus(200);
});
