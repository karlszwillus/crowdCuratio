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

use App\Models\Audiovisual;
use App\Models\Entry;
use App\Models\MediaContent;
use App\Models\Project;
use App\Models\ProjectUserPermission;
use App\Models\User;
use App\Policies\AudiovisualPolicy;
use App\Support\PermissionName;
use App\Support\RoleName;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| AudiovisualPolicy (Block E.7b Sub-Welle 3, ADR-0022)
|--------------------------------------------------------------------------
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

/**
 * @return array{audiovisual: Audiovisual, project: Project}
 */
function audiovisualAttachedTo(User $owner): array
{
    $project = makeProject($owner);
    $entry = makeEntry(makeChapter($project));
    $av = Audiovisual::factory()->create();

    MediaContent::create([
        'content_id' => $av->id,
        'content_type' => Audiovisual::class,
        'parent_id' => $entry->id,
        'parent_type' => Entry::class,
        'position' => 0,
    ]);

    return ['audiovisual' => $av->refresh(), 'project' => $project];
}

it('view: Owner darf sein Audiovisual sehen', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    ['audiovisual' => $av] = audiovisualAttachedTo($owner);

    $policy = app(AudiovisualPolicy::class);

    expect($policy->view($owner, $av))->toBeTrue();
});

it('view: Admin darf jedes Audiovisual sehen (before)', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole(RoleName::ADMIN->value);
    /** @var User $other */
    $other = User::factory()->create();
    $other->assignRole(RoleName::READER->value);
    ['audiovisual' => $av] = audiovisualAttachedTo($other);

    expect($admin->can('view', $av))->toBeTrue();
});

it('update: Eingeladener mit edit-Permission darf editieren', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    /** @var User $invitee */
    $invitee = User::factory()->create();
    $invitee->assignRole(RoleName::READER->value);
    ['audiovisual' => $av, 'project' => $project] = audiovisualAttachedTo($owner);

    $editPermission = Permission::where('name', PermissionName::EDIT->value)->first();
    ProjectUserPermission::create([
        'user_id' => $invitee->id,
        'project_id' => $project->id,
        'permission_id' => $editPermission->id,
    ]);

    $policy = app(AudiovisualPolicy::class);

    expect($policy->update($invitee, $av))->toBeTrue();
});

it('delete: Fremder darf NICHT löschen', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    /** @var User $stranger */
    $stranger = User::factory()->create();
    $stranger->assignRole(RoleName::READER->value);
    ['audiovisual' => $av] = audiovisualAttachedTo($owner);

    $policy = app(AudiovisualPolicy::class);

    expect($policy->delete($stranger, $av))->toBeFalse();
});

it('view: ohne Entry-Verknüpfung liefert false', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    $av = Audiovisual::factory()->create();

    $policy = app(AudiovisualPolicy::class);

    expect($policy->view($owner, $av))->toBeFalse();
});
