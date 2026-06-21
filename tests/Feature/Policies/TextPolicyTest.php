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

use App\Models\Entry;
use App\Models\MediaContent;
use App\Models\Project;
use App\Models\ProjectUserPermission;
use App\Models\Text;
use App\Models\User;
use App\Policies\TextPolicy;
use App\Support\PermissionName;
use App\Support\RoleName;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| TextPolicy (Block E.7b Sub-Welle 3, ADR-0022)
|--------------------------------------------------------------------------
|
| Project-scoped via OwnerScopedPolicy. Owner ODER Eingeladener mit
| passender Permission; Admin via before(). Das Project wird aus
| dem Text via project()-Methode aufgelöst, die über den
| MediaContent-Pivot zum Entry → Chapter → Project navigiert.
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
 * @return array{text: Text, project: Project}
 */
function textAttachedTo(User $owner): array
{
    $project = makeProject($owner);
    $entry = makeEntry(makeChapter($project));
    $text = Text::factory()->create();

    MediaContent::create([
        'content_id' => $text->id,
        'content_type' => Text::class,
        'parent_id' => $entry->id,
        'parent_type' => Entry::class,
        'position' => 0,
    ]);

    return ['text' => $text->refresh(), 'project' => $project];
}

it('view: Owner darf seinen Text sehen', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    ['text' => $text] = textAttachedTo($owner);

    $policy = app(TextPolicy::class);

    expect($policy->view($owner, $text))->toBeTrue();
});

it('view: Admin darf jeden Text sehen (before)', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole(RoleName::ADMIN->value);
    /** @var User $other */
    $other = User::factory()->create();
    $other->assignRole(RoleName::READER->value);
    ['text' => $text] = textAttachedTo($other);

    expect($admin->can('view', $text))->toBeTrue();
});

it('update: Eingeladener mit edit-Permission darf den Text editieren', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    /** @var User $invitee */
    $invitee = User::factory()->create();
    $invitee->assignRole(RoleName::READER->value);
    ['text' => $text, 'project' => $project] = textAttachedTo($owner);

    $editPermission = Permission::where('name', PermissionName::EDIT->value)->first();
    ProjectUserPermission::create([
        'user_id' => $invitee->id,
        'project_id' => $project->id,
        'permission_id' => $editPermission->id,
    ]);

    $policy = app(TextPolicy::class);

    expect($policy->update($invitee, $text))->toBeTrue();
});

it('update: Eingeladener nur mit view-Permission darf NICHT editieren', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    /** @var User $invitee */
    $invitee = User::factory()->create();
    $invitee->assignRole(RoleName::READER->value);
    ['text' => $text, 'project' => $project] = textAttachedTo($owner);

    $viewPermission = Permission::where('name', PermissionName::VIEW->value)->first();
    ProjectUserPermission::create([
        'user_id' => $invitee->id,
        'project_id' => $project->id,
        'permission_id' => $viewPermission->id,
    ]);

    $policy = app(TextPolicy::class);

    expect($policy->update($invitee, $text))->toBeFalse();
});

it('delete: Fremder darf den Text NICHT löschen', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    /** @var User $stranger */
    $stranger = User::factory()->create();
    $stranger->assignRole(RoleName::READER->value);
    ['text' => $text] = textAttachedTo($owner);

    $policy = app(TextPolicy::class);

    expect($policy->delete($stranger, $text))->toBeFalse();
});

it('view: ohne Entry-Verknüpfung (project()=null) liefert false', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    $text = Text::factory()->create();

    $policy = app(TextPolicy::class);

    expect($policy->view($owner, $text))->toBeFalse();
});
