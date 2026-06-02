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

use App\Models\Invitation;
use App\Models\User;
use App\Models\UserHasPermission;
use App\Services\ProjectPermissionService;
use App\Support\PermissionName;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| ProjectPermissionService
|--------------------------------------------------------------------------
|
| Der Service kapselt die zehn Permission-Methoden, die vorher
| direkt im ProjectController lagen. Drei-Welten-Logik
| (Spatie-Rollen, project-scoped UserHasPermission-Pivot,
| Invitations-Tabelle) bleibt unverändert — Block D / ADR-0005
| wird die Welten dann auf einen einheitlichen Pfad zusammenführen.
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

it('setForUserOnProject legt Permission-Einträge an und erstellt die Invitation', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    /** @var User $invitee */
    $invitee = User::factory()->create();
    $invitee->assignRole('Reader');

    $project = makeProject($owner);
    $editPermission = Permission::where('name', 'edit')->first();
    $commentPermission = Permission::where('name', 'comment')->first();

    $service = new ProjectPermissionService;

    $service->setForUserOnProject(
        userId: $invitee->id,
        projectId: $project->id,
        permissionIds: [$editPermission->id, $commentPermission->id],
        invitedByUserId: $owner->id,
    );

    $pivotCount = UserHasPermission::where('user_id', $invitee->id)
        ->where('project_id', $project->id)
        ->count();

    expect($pivotCount)->toBe(2);

    $invitation = Invitation::where('user_id', $owner->id)
        ->where('guest_id', $invitee->id)
        ->where('project_id', $project->id)
        ->first();

    expect($invitation)->not->toBeNull();
});

it('setForUserOnProject ersetzt bestehende Permissions (Set-Semantik)', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    /** @var User $invitee */
    $invitee = User::factory()->create();
    $invitee->assignRole('Reader');

    $project = makeProject($owner);
    $editPermission = Permission::where('name', 'edit')->first();
    $commentPermission = Permission::where('name', 'comment')->first();

    UserHasPermission::create([
        'user_id' => $invitee->id,
        'project_id' => $project->id,
        'permission_id' => $editPermission->id,
    ]);

    $service = new ProjectPermissionService;

    // Zweite Welle: nur noch comment-Permission.
    $service->setForUserOnProject(
        userId: $invitee->id,
        projectId: $project->id,
        permissionIds: [$commentPermission->id],
        invitedByUserId: $owner->id,
    );

    $remaining = UserHasPermission::where('user_id', $invitee->id)
        ->where('project_id', $project->id)
        ->pluck('permission_id')
        ->toArray();

    expect($remaining)->toBe([$commentPermission->id]);
});

it('removeUserFromProject löscht Permissions und Invitation vollständig', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    /** @var User $invitee */
    $invitee = User::factory()->create();
    $invitee->assignRole('Reader');

    $project = makeProject($owner);
    $editPermission = Permission::where('name', 'edit')->first();

    UserHasPermission::create([
        'user_id' => $invitee->id,
        'project_id' => $project->id,
        'permission_id' => $editPermission->id,
    ]);

    Invitation::create([
        'user_id' => $owner->id,
        'guest_id' => $invitee->id,
        'project_id' => $project->id,
    ]);

    $service = new ProjectPermissionService;

    $service->removeUserFromProject($invitee->id, $project->id);

    $permissionCount = UserHasPermission::where('user_id', $invitee->id)
        ->where('project_id', $project->id)
        ->count();

    $invitationCount = Invitation::where('guest_id', $invitee->id)
        ->where('project_id', $project->id)
        ->count();

    expect($permissionCount)->toBe(0);
    expect($invitationCount)->toBe(0);
});

it('getUsersForThisProject liefert Name und Permissions je berechtigtem User', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    /** @var User $invitee */
    $invitee = User::factory()->create(['name' => 'Berta', 'last_name' => 'Beispiel']);
    $invitee->assignRole('Reader');

    $project = makeProject($owner);
    $editPermission = Permission::where('name', 'edit')->first();

    UserHasPermission::create([
        'user_id' => $invitee->id,
        'project_id' => $project->id,
        'permission_id' => $editPermission->id,
    ]);

    $service = new ProjectPermissionService;

    $result = $service->getUsersForThisProject($project->id);

    expect($result)->toHaveKey($invitee->id);
    expect($result[$invitee->id]['name'])->toBe('Berta Beispiel');
    expect($result[$invitee->id]['permission'])->toBe([
        $editPermission->id => 'edit',
    ]);
});

it('getCurrentUsersPermissions liefert die Spatie-Rollen-Permissions als id=>name', function () {
    /** @var TestCase $this */
    /** @var User $reader */
    $reader = User::factory()->create();
    $reader->assignRole('Reader');

    $service = new ProjectPermissionService;

    $result = $service->getCurrentUsersPermissions($reader->id);

    $viewPermission = Permission::where('name', 'view')->first();

    expect($result)->toBe([$viewPermission->id => 'view']);
});

// ---------- Block D PR 2 / D.5 — Policy-relevante Helper ----------
//
// Diese Tests beschreiben die neuen Service-Methoden, die die
// ProjectPolicy in D.6 für die project-scoped Sicht braucht.
// Stand: noch nicht implementiert → rot, bis D.5 fertig ist.

it('userHasPermissionOnProject erkennt den Project-Owner', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Reader');

    $project = makeProject($owner);

    $service = new ProjectPermissionService;

    expect($service->userHasPermissionOnProject($owner, $project, PermissionName::VIEW))->toBeTrue();
    expect($service->userHasPermissionOnProject($owner, $project, PermissionName::EDIT))->toBeTrue();
    expect($service->userHasPermissionOnProject($owner, $project, PermissionName::COMMENT))->toBeTrue();
});

it('userHasPermissionOnProject erkennt einen Eingeladenen mit comment-Permission', function () {
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

    $service = new ProjectPermissionService;

    expect($service->userHasPermissionOnProject($invitee, $project, PermissionName::COMMENT))->toBeTrue();
});

it('userHasPermissionOnProject lehnt einen Eingeladenen ohne die geforderte Permission ab', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Reader');
    /** @var User $invitee */
    $invitee = User::factory()->create();
    $invitee->assignRole('Reader');

    $project = makeProject($owner);
    // Invitee hat NUR view-Permission, kein comment/edit.
    $viewPermission = Permission::where('name', 'view')->first();

    UserHasPermission::create([
        'user_id' => $invitee->id,
        'project_id' => $project->id,
        'permission_id' => $viewPermission->id,
    ]);

    $service = new ProjectPermissionService;

    expect($service->userHasPermissionOnProject($invitee, $project, PermissionName::VIEW))->toBeTrue();
    expect($service->userHasPermissionOnProject($invitee, $project, PermissionName::COMMENT))->toBeFalse();
    expect($service->userHasPermissionOnProject($invitee, $project, PermissionName::EDIT))->toBeFalse();
});

it('userHasPermissionOnProject lehnt einen Fremden ohne Einladung ab', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Reader');
    /** @var User $stranger */
    $stranger = User::factory()->create();
    $stranger->assignRole('Reader');

    $project = makeProject($owner);

    $service = new ProjectPermissionService;

    expect($service->userHasPermissionOnProject($stranger, $project, PermissionName::VIEW))->toBeFalse();
    expect($service->userHasPermissionOnProject($stranger, $project, PermissionName::COMMENT))->toBeFalse();
    expect($service->userHasPermissionOnProject($stranger, $project, PermissionName::EDIT))->toBeFalse();
});

it('listProjectsForUser liefert eigene Projects und solche, in die der User eingeladen ist', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Reader');
    /** @var User $invitee */
    $invitee = User::factory()->create();
    $invitee->assignRole('Reader');
    /** @var User $other */
    $other = User::factory()->create();
    $other->assignRole('Reader');

    $ownProject = makeProject($invitee, ['name' => 'Eigenes Projekt']);
    $sharedProject = makeProject($owner, ['name' => 'Geteiltes Projekt']);
    $strangerProject = makeProject($other, ['name' => 'Fremdes Projekt']);

    $viewPermission = Permission::where('name', 'view')->first();
    UserHasPermission::create([
        'user_id' => $invitee->id,
        'project_id' => $sharedProject->id,
        'permission_id' => $viewPermission->id,
    ]);

    $service = new ProjectPermissionService;

    $projects = $service->listProjectsForUser($invitee);

    $ids = collect($projects)->pluck('id')->all();
    expect($ids)->toContain($ownProject->id);
    expect($ids)->toContain($sharedProject->id);
    expect($ids)->not->toContain($strangerProject->id);
});

it('listProjectsForUser liefert für einen Admin alle nicht-gelöschten Projects', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    /** @var User $other */
    $other = User::factory()->create();
    $other->assignRole('Reader');

    $projectA = makeProject($other, ['name' => 'A']);
    $projectB = makeProject($other, ['name' => 'B']);

    $service = new ProjectPermissionService;

    $projects = $service->listProjectsForUser($admin);

    $ids = collect($projects)->pluck('id')->all();
    expect($ids)->toContain($projectA->id);
    expect($ids)->toContain($projectB->id);
});

// ---------- Bestand ----------

it('getPermissionIdsForUserOnProject liefert die Pivot-IDs als Collection', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    /** @var User $invitee */
    $invitee = User::factory()->create();
    $invitee->assignRole('Reader');

    $project = makeProject($owner);
    $editPermission = Permission::where('name', 'edit')->first();
    $commentPermission = Permission::where('name', 'comment')->first();

    UserHasPermission::create([
        'user_id' => $invitee->id,
        'project_id' => $project->id,
        'permission_id' => $editPermission->id,
    ]);
    UserHasPermission::create([
        'user_id' => $invitee->id,
        'project_id' => $project->id,
        'permission_id' => $commentPermission->id,
    ]);

    $service = new ProjectPermissionService;

    $result = $service->getPermissionIdsForUserOnProject($invitee->id, $project->id);

    expect($result->toArray())->toBe([$editPermission->id, $commentPermission->id]);
});
