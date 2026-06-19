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
use App\Models\ProjectUserPermission;
use App\Models\User;
use App\Services\ProjectInvitationService;
use App\Support\PermissionName;
use App\Support\RoleName;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/*
|--------------------------------------------------------------------------
| ProjectInvitationService
|--------------------------------------------------------------------------
|
| Block E / Welle E.5. Vorher der `if (isset($request->projectId))`-
| Zweig in RegisteredUserController::store: Permission-Lookup über
| Spatie-Relation, ProjectUserPermission-Pivot-Inserts pro
| Permission, plus Invitation-Eintrag. Service kapselt das.
*/

beforeEach(function () {
    foreach (PermissionName::all() as $permissionName) {
        Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
    }

    Role::firstOrCreate(['name' => RoleName::ADMIN->value, 'guard_name' => 'web'])
        ->syncPermissions(Permission::all());
    Role::firstOrCreate(['name' => RoleName::READER->value, 'guard_name' => 'web'])
        ->syncPermissions(['view']);
});

it('attachInviteeToProject: schreibt eine ProjectUserPermission pro Permission der Rolle', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::ADMIN->value);
    /** @var User $invitee */
    $invitee = User::factory()->create();

    $project = makeProject($owner);
    $readerRole = Role::where('name', RoleName::READER->value)->first();

    $service = new ProjectInvitationService;
    $service->attachInviteeToProject($invitee, $owner, $project->id, [$readerRole]);

    $count = ProjectUserPermission::query()
        ->where('user_id', $invitee->id)
        ->where('project_id', $project->id)
        ->count();

    // Reader hat eine Permission (view).
    expect($count)->toBe(1);
});

it('attachInviteeToProject: legt eine Invitation an', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::ADMIN->value);
    /** @var User $invitee */
    $invitee = User::factory()->create();

    $project = makeProject($owner);
    $readerRole = Role::where('name', RoleName::READER->value)->first();

    $service = new ProjectInvitationService;
    $service->attachInviteeToProject($invitee, $owner, $project->id, [$readerRole]);

    $invitation = Invitation::query()
        ->where('user_id', $owner->id)
        ->where('guest_id', $invitee->id)
        ->where('project_id', $project->id)
        ->first();

    expect($invitation)->not->toBeNull();
});

it('attachInviteeToProject: mehrere Rollen → Union der Permissions ohne Duplikate', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::ADMIN->value);
    /** @var User $invitee */
    $invitee = User::factory()->create();

    $project = makeProject($owner);
    $readerRole = Role::where('name', RoleName::READER->value)->first();
    $adminRole = Role::where('name', RoleName::ADMIN->value)->first();

    $service = new ProjectInvitationService;
    $service->attachInviteeToProject($invitee, $owner, $project->id, [$readerRole, $adminRole]);

    // Admin hat alle 7 Permissions, Reader hat view → Union = 7.
    $count = ProjectUserPermission::query()
        ->where('user_id', $invitee->id)
        ->where('project_id', $project->id)
        ->count();

    expect($count)->toBe(count(PermissionName::all()));
});

it('attachInviteeToProject: leeres Roles-Array → keine Permissions, aber Invitation trotzdem', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::ADMIN->value);
    /** @var User $invitee */
    $invitee = User::factory()->create();

    $project = makeProject($owner);

    $service = new ProjectInvitationService;
    $service->attachInviteeToProject($invitee, $owner, $project->id, []);

    $permissionCount = ProjectUserPermission::query()
        ->where('user_id', $invitee->id)
        ->where('project_id', $project->id)
        ->count();
    expect($permissionCount)->toBe(0);

    $invitation = Invitation::query()
        ->where('guest_id', $invitee->id)
        ->where('project_id', $project->id)
        ->first();
    expect($invitation)->not->toBeNull();
});
