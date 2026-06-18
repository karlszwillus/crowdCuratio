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
use App\Models\Entry;
use App\Models\ProjectUserPermission;
use App\Models\User;
use App\Policies\EntryPolicy;
use App\Support\PermissionName;
use App\Support\RoleName;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| EntryPolicy
|--------------------------------------------------------------------------
|
| Block E / Welle E.7a — project-scoped Authorization transitiv
| über Chapter → Project. Owner ODER Eingeladener mit Permission;
| Admin via before().
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

function entryIn(User $owner): Entry
{
    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $entry = makeEntry($chapter);

    return $entry->load('chapter.project');
}

it('view: Owner darf sein Entry sehen', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    $entry = entryIn($owner);

    $policy = app(EntryPolicy::class);

    expect($policy->view($owner, $entry))->toBeTrue();
});

it('view: Admin darf jedes Entry sehen', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole(RoleName::ADMIN->value);
    /** @var User $other */
    $other = User::factory()->create();
    $other->assignRole(RoleName::READER->value);
    $entry = entryIn($other);

    expect($admin->can('view', $entry))->toBeTrue();
});

it('view: Eingeladener mit view-Permission darf das Entry sehen', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    /** @var User $invitee */
    $invitee = User::factory()->create();
    $invitee->assignRole(RoleName::READER->value);
    $entry = entryIn($owner);

    $viewPermission = Permission::where('name', PermissionName::VIEW->value)->first();
    ProjectUserPermission::create([
        'user_id' => $invitee->id,
        'project_id' => $entry->chapter->project->id,
        'permission_id' => $viewPermission->id,
    ]);

    $policy = app(EntryPolicy::class);

    expect($policy->view($invitee, $entry))->toBeTrue();
});

it('update: Eingeladener mit edit-Permission darf das Entry editieren', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    /** @var User $invitee */
    $invitee = User::factory()->create();
    $invitee->assignRole(RoleName::READER->value);
    $entry = entryIn($owner);

    $editPermission = Permission::where('name', PermissionName::EDIT->value)->first();
    ProjectUserPermission::create([
        'user_id' => $invitee->id,
        'project_id' => $entry->chapter->project->id,
        'permission_id' => $editPermission->id,
    ]);

    $policy = app(EntryPolicy::class);

    expect($policy->update($invitee, $entry))->toBeTrue();
});

it('delete: Fremder darf das Entry NICHT löschen', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    /** @var User $stranger */
    $stranger = User::factory()->create();
    $stranger->assignRole(RoleName::READER->value);
    $entry = entryIn($owner);

    $policy = app(EntryPolicy::class);

    expect($policy->delete($stranger, $entry))->toBeFalse();
});

it('delete: Eingeladener mit delete-Permission darf das Entry löschen', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    /** @var User $invitee */
    $invitee = User::factory()->create();
    $invitee->assignRole(RoleName::READER->value);
    $entry = entryIn($owner);

    $deletePermission = Permission::where('name', PermissionName::DELETE->value)->first();
    ProjectUserPermission::create([
        'user_id' => $invitee->id,
        'project_id' => $entry->chapter->project->id,
        'permission_id' => $deletePermission->id,
    ]);

    $policy = app(EntryPolicy::class);

    expect($policy->delete($invitee, $entry))->toBeTrue();
});
