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

use App\Models\ProjectUserPermission;
use App\Models\User;
use App\Policies\ProjectPolicy;
use App\Support\PermissionName;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| ProjectPolicy — project-scoped Authorization
|--------------------------------------------------------------------------
|
| Block D PR 2 / D.6. Vor PR 2 prüften `view`/`comment` nur Owner
| bzw. globale `comment`-Permission — Eingeladene mit project-scoped
| Permissions waren außen vor. Nach PR 2 ist die Policy
| project-scoped: Owner ODER Eingeladener mit passender Permission.
| Admin via `before()` (unverändert).
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

// ---------- view($user, $project) ----------

it('view: Owner darf sein eigenes Project sehen', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Reader');
    $project = makeProject($owner);

    $policy = app(ProjectPolicy::class);

    expect($policy->view($owner, $project))->toBeTrue();
});

it('view: Admin darf jedes Project sehen (before())', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    /** @var User $other */
    $other = User::factory()->create();
    $other->assignRole('Reader');
    $project = makeProject($other);

    expect($admin->can('view', $project))->toBeTrue();
});

it('view: Eingeladener mit view-Permission darf das Project sehen', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Reader');
    /** @var User $invitee */
    $invitee = User::factory()->create();
    $invitee->assignRole('Reader');
    $project = makeProject($owner);

    $viewPermission = Permission::where('name', 'view')->first();
    ProjectUserPermission::create([
        'user_id' => $invitee->id,
        'project_id' => $project->id,
        'permission_id' => $viewPermission->id,
    ]);

    $policy = app(ProjectPolicy::class);

    expect($policy->view($invitee, $project))->toBeTrue();
});

it('view: Fremder ohne Einladung darf das Project nicht sehen', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Reader');
    /** @var User $stranger */
    $stranger = User::factory()->create();
    $stranger->assignRole('Reader');
    $project = makeProject($owner);

    $policy = app(ProjectPolicy::class);

    expect($policy->view($stranger, $project))->toBeFalse();
});

// ---------- comment($user, $project) ----------

it('comment: Owner darf auf seinem Project kommentieren', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Reader');
    $project = makeProject($owner);

    $policy = app(ProjectPolicy::class);

    expect($policy->comment($owner, $project))->toBeTrue();
});

it('comment: Admin darf auf jedem Project kommentieren (before())', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    /** @var User $other */
    $other = User::factory()->create();
    $other->assignRole('Reader');
    $project = makeProject($other);

    expect($admin->can('comment', $project))->toBeTrue();
});

it('comment: Eingeladener mit comment-Permission darf kommentieren', function () {
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

    $policy = app(ProjectPolicy::class);

    expect($policy->comment($invitee, $project))->toBeTrue();
});

it('comment: Eingeladener nur mit view (ohne comment) darf nicht kommentieren', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Reader');
    /** @var User $invitee */
    $invitee = User::factory()->create();
    $invitee->assignRole('Reader');
    $project = makeProject($owner);

    $viewPermission = Permission::where('name', 'view')->first();
    ProjectUserPermission::create([
        'user_id' => $invitee->id,
        'project_id' => $project->id,
        'permission_id' => $viewPermission->id,
    ]);

    $policy = app(ProjectPolicy::class);

    expect($policy->comment($invitee, $project))->toBeFalse();
});

// ---------- viewAny($user) ----------
//
// viewAny bleibt nach PR 2 wie nach dem D.4-Hotfix: User braucht
// die globale `view`-Permission. Project-scoped Filterung der Liste
// passiert im Service (`listProjectsForUser`).

it('viewAny: User mit view-Permission darf die Project-Liste sehen', function () {
    /** @var TestCase $this */
    /** @var User $reader */
    $reader = User::factory()->create();
    $reader->assignRole('Reader');

    $policy = app(ProjectPolicy::class);

    expect($policy->viewAny($reader))->toBeTrue();
});

it('viewAny: User ohne view-Permission darf die Project-Liste nicht sehen', function () {
    /** @var TestCase $this */
    /** @var User $noRole */
    $noRole = User::factory()->create();

    $policy = app(ProjectPolicy::class);

    expect($policy->viewAny($noRole))->toBeFalse();
});
