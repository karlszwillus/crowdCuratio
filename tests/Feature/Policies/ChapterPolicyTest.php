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
use App\Models\ProjectUserPermission;
use App\Models\User;
use App\Policies\ChapterPolicy;
use App\Support\PermissionName;
use App\Support\RoleName;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| ChapterPolicy
|--------------------------------------------------------------------------
|
| Block E / Welle E.7a — project-scoped Authorization analog
| ProjectPolicy. Owner ODER Eingeladener mit passender Permission;
| Admin via before(). Vorher: nur Owner via
| `$user->id === $chapter->project->user_id`, Eingeladene mit
| edit-Permission fielen durch.
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

function chapterIn(User $owner): Chapter
{
    $project = makeProject($owner);
    $chapter = makeChapter($project);

    return $chapter->load('project');
}

it('view: Owner darf sein Chapter sehen', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    $chapter = chapterIn($owner);

    $policy = app(ChapterPolicy::class);

    expect($policy->view($owner, $chapter))->toBeTrue();
});

it('view: Admin darf jedes Chapter sehen (before)', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole(RoleName::ADMIN->value);
    /** @var User $other */
    $other = User::factory()->create();
    $other->assignRole(RoleName::READER->value);
    $chapter = chapterIn($other);

    expect($admin->can('view', $chapter))->toBeTrue();
});

it('view: Eingeladener mit view-Permission darf das Chapter sehen', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    /** @var User $invitee */
    $invitee = User::factory()->create();
    $invitee->assignRole(RoleName::READER->value);
    $chapter = chapterIn($owner);

    $viewPermission = Permission::where('name', PermissionName::VIEW->value)->first();
    ProjectUserPermission::create([
        'user_id' => $invitee->id,
        'project_id' => $chapter->project->id,
        'permission_id' => $viewPermission->id,
    ]);

    $policy = app(ChapterPolicy::class);

    expect($policy->view($invitee, $chapter))->toBeTrue();
});

it('view: Fremder darf das Chapter NICHT sehen', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    /** @var User $stranger */
    $stranger = User::factory()->create();
    $stranger->assignRole(RoleName::READER->value);
    $chapter = chapterIn($owner);

    $policy = app(ChapterPolicy::class);

    expect($policy->view($stranger, $chapter))->toBeFalse();
});

it('update: Eingeladener mit edit-Permission darf das Chapter editieren', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    /** @var User $invitee */
    $invitee = User::factory()->create();
    $invitee->assignRole(RoleName::READER->value);
    $chapter = chapterIn($owner);

    $editPermission = Permission::where('name', PermissionName::EDIT->value)->first();
    ProjectUserPermission::create([
        'user_id' => $invitee->id,
        'project_id' => $chapter->project->id,
        'permission_id' => $editPermission->id,
    ]);

    $policy = app(ChapterPolicy::class);

    expect($policy->update($invitee, $chapter))->toBeTrue();
});

it('update: Eingeladener nur mit view-Permission darf NICHT editieren', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    /** @var User $invitee */
    $invitee = User::factory()->create();
    $invitee->assignRole(RoleName::READER->value);
    $chapter = chapterIn($owner);

    $viewPermission = Permission::where('name', PermissionName::VIEW->value)->first();
    ProjectUserPermission::create([
        'user_id' => $invitee->id,
        'project_id' => $chapter->project->id,
        'permission_id' => $viewPermission->id,
    ]);

    $policy = app(ChapterPolicy::class);

    expect($policy->update($invitee, $chapter))->toBeFalse();
});

it('delete: Fremder darf das Chapter NICHT löschen', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    /** @var User $stranger */
    $stranger = User::factory()->create();
    $stranger->assignRole(RoleName::READER->value);
    $chapter = chapterIn($owner);

    $policy = app(ChapterPolicy::class);

    expect($policy->delete($stranger, $chapter))->toBeFalse();
});
