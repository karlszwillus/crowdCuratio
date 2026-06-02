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
    UserHasPermission::create([
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
    UserHasPermission::create([
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
