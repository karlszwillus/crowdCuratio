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
use App\Models\Gallery;
use App\Models\Image;
use App\Models\MediaContent;
use App\Models\Project;
use App\Models\ProjectUserPermission;
use App\Models\User;
use App\Policies\ImagePolicy;
use App\Support\PermissionName;
use App\Support\RoleName;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| ImagePolicy (Block E.7b Sub-Welle 3, ADR-0022)
|--------------------------------------------------------------------------
|
| Sonderfall: Image hängt nicht direkt am Entry, sondern indirekt
| über die Gallery (gallery_id-FK). Project-Auflösung passiert via
| Image::project() → Gallery::project() → Pivot → Entry → Chapter
| → Project.
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
 * @return array{image: Image, project: Project}
 */
function imageAttachedTo(User $owner): array
{
    $project = makeProject($owner);
    $entry = makeEntry(makeChapter($project));
    $gallery = Gallery::factory()->create();
    /** @var Image $image */
    $image = Image::factory()->create(['gallery_id' => $gallery->id]);

    MediaContent::create([
        'content_id' => $gallery->id,
        'content_type' => Gallery::class,
        'parent_id' => $entry->id,
        'parent_type' => Entry::class,
        'position' => 0,
    ]);

    return ['image' => $image->refresh(), 'project' => $project];
}

it('view: Owner darf sein Image sehen (via Gallery)', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    ['image' => $image] = imageAttachedTo($owner);

    $policy = app(ImagePolicy::class);

    expect($policy->view($owner, $image))->toBeTrue();
});

it('view: Admin darf jedes Image sehen (before)', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole(RoleName::ADMIN->value);
    /** @var User $other */
    $other = User::factory()->create();
    $other->assignRole(RoleName::READER->value);
    ['image' => $image] = imageAttachedTo($other);

    expect($admin->can('view', $image))->toBeTrue();
});

it('update: Eingeladener mit edit-Permission darf editieren', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    /** @var User $invitee */
    $invitee = User::factory()->create();
    $invitee->assignRole(RoleName::READER->value);
    ['image' => $image, 'project' => $project] = imageAttachedTo($owner);

    $editPermission = Permission::where('name', PermissionName::EDIT->value)->first();
    ProjectUserPermission::create([
        'user_id' => $invitee->id,
        'project_id' => $project->id,
        'permission_id' => $editPermission->id,
    ]);

    $policy = app(ImagePolicy::class);

    expect($policy->update($invitee, $image))->toBeTrue();
});

it('delete: Fremder darf NICHT löschen', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    /** @var User $stranger */
    $stranger = User::factory()->create();
    $stranger->assignRole(RoleName::READER->value);
    ['image' => $image] = imageAttachedTo($owner);

    $policy = app(ImagePolicy::class);

    expect($policy->delete($stranger, $image))->toBeFalse();
});

it('view: Image ohne gallery_id liefert false', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    /** @var Image $image */
    $image = Image::factory()->create(['gallery_id' => null]);

    $policy = app(ImagePolicy::class);

    expect($policy->view($owner, $image))->toBeFalse();
});
