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

use App\Models\Project;
use App\Models\ProjectUserPermission;
use App\Models\User;
use App\Services\UserService;
use App\Support\PermissionName;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| UserService::getAllUsers
|--------------------------------------------------------------------------
|
| Die Methode liefert die effektive Permission-Liste des authenti-
| fizierten Users für ein konkretes Project. Logik:
|
| - Wenn der User in `project_user_permissions` Einträge für das
|   Project hat, gewinnen diese (project-scoped override).
| - Sonst fallen wir auf die globalen Spatie-Permissions des Users
|   zurück (über Rolle vererbt).
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

it('liefert die globalen Permissions, wenn keine project-scoped Overrides vorhanden sind', function () {
    /** @var TestCase $this */
    /** @var User $user */
    $user = User::factory()->create();
    $user->assignRole('Admin');

    $project = makeProject($user);

    $this->actingAs($user);

    $service = new UserService;
    $permissions = $service->getAllUsers($project->id);

    expect($permissions)
        ->toBeArray()
        ->toContain('view')
        ->toContain('add')
        ->toContain('edit')
        ->toContain('delete');
});

it('liefert die project-scoped Overrides, sobald welche vorhanden sind', function () {
    /** @var TestCase $this */
    /** @var User $user */
    $user = User::factory()->create();
    $user->assignRole('Admin');

    $project = makeProject($user);

    $viewPermission = Permission::where('name', 'view')->first();
    ProjectUserPermission::create([
        'user_id' => $user->id,
        'project_id' => $project->id,
        'permission_id' => $viewPermission->id,
    ]);

    $this->actingAs($user);

    $service = new UserService;
    $permissions = $service->getAllUsers($project->id);

    // Override gewinnt: nur 'view' zurück, nicht die volle Admin-Liste.
    expect($permissions)->toBeArray()->toContain('view');
    expect($permissions)->not->toContain('delete');
    expect($permissions)->not->toContain('edit');
});

it('liefert eine leere Liste zurück, wenn weder Overrides noch globale Permissions vorliegen', function () {
    /** @var TestCase $this */
    /** @var User $user */
    $user = User::factory()->create();
    // Keine Rolle, keine project-scoped Permissions.

    $project = makeProject($user);

    $this->actingAs($user);

    $service = new UserService;
    $permissions = $service->getAllUsers($project->id);

    expect($permissions)
        ->toBeArray()
        ->toBeEmpty();
});

it('unterscheidet Overrides verschiedener Projects strikt voneinander', function () {
    /** @var TestCase $this */
    /** @var User $user */
    $user = User::factory()->create();
    $user->assignRole('Reader');

    $projectA = makeProject($user, ['name' => 'Project A']);
    $projectB = makeProject($user, ['name' => 'Project B']);

    $editPermission = Permission::where('name', 'edit')->first();
    ProjectUserPermission::create([
        'user_id' => $user->id,
        'project_id' => $projectA->id,
        'permission_id' => $editPermission->id,
    ]);

    $this->actingAs($user);

    $service = new UserService;

    $a = $service->getAllUsers($projectA->id);
    $b = $service->getAllUsers($projectB->id);

    // Project A: Override greift, Reader-Default ('view') taucht nicht auf.
    expect($a)->toContain('edit');
    expect($a)->not->toContain('view');
    // Project B: kein Override, Reader-Default ('view') gewinnt.
    expect($b)->toContain('view');
    expect($b)->not->toContain('edit');
});
