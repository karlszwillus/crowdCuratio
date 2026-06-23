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

use App\Models\Comment;
use App\Models\Project;
use App\Models\User;
use App\Support\PermissionName;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Volt-Komponente: comment-status-switcher
|--------------------------------------------------------------------------
|
| Pilot-Test für die erste Livewire-4-Komponente nach Frontend-Stack-
| Reset. Deckt drei Pfade ab:
|   1. Owner ändert den Status -> DB-Wert + currentStatus passen.
|   2. Fremder User ohne `comment`-Permission -> 403.
|   3. Ungültiger Status (außerhalb config('project.comment')) ->
|      kein Schreibvorgang.
*/

beforeEach(function () {
    foreach (PermissionName::all() as $permissionName) {
        Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
    }

    Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web'])
        ->syncPermissions(Permission::all());

    Role::firstOrCreate(['name' => 'Reader', 'guard_name' => 'web'])
        ->syncPermissions(Permission::where('name', 'view')->get());
});

it('Owner kann den Comment-Status ueber die Volt-Komponente aendern', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = Project::create([
        'user_id' => $owner->id,
        'name' => json_encode(['de' => 'Pilot-Projekt']),
        'status' => 'open',
    ]);

    $comment = Comment::create([
        'user_id' => $owner->id,
        'project_id' => $project->id,
        'commentable_id' => $project->id,
        'commentable_type' => Project::class,
        'comment' => json_encode(['de' => 'Kommentar']),
        'status' => 1,
    ]);

    Livewire::test('comment-status-switcher', [
        'comment' => $comment,
        'project' => $project,
    ])
        ->assertSet('currentStatus', 1)
        ->call('updateStatus', 4)
        ->assertSet('currentStatus', 4);

    expect((int) $comment->fresh()->status)->toBe(4);
});

it('Fremder User ohne Permission bekommt 403', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');

    $project = Project::create([
        'user_id' => $owner->id,
        'name' => json_encode(['de' => 'Pilot-Projekt']),
        'status' => 'open',
    ]);

    $comment = Comment::create([
        'user_id' => $owner->id,
        'project_id' => $project->id,
        'commentable_id' => $project->id,
        'commentable_type' => Project::class,
        'comment' => json_encode(['de' => 'Kommentar']),
        'status' => 1,
    ]);

    /** @var User $stranger */
    $stranger = User::factory()->create();
    $stranger->assignRole('Reader');
    $this->actingAs($stranger);

    Livewire::test('comment-status-switcher', [
        'comment' => $comment,
        'project' => $project,
    ])->call('updateStatus', 4);
})->throws(AuthorizationException::class);

it('Ungueltiger Status wird stillschweigend verworfen', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = Project::create([
        'user_id' => $owner->id,
        'name' => json_encode(['de' => 'Pilot-Projekt']),
        'status' => 'open',
    ]);

    $comment = Comment::create([
        'user_id' => $owner->id,
        'project_id' => $project->id,
        'commentable_id' => $project->id,
        'commentable_type' => Project::class,
        'comment' => json_encode(['de' => 'Kommentar']),
        'status' => 1,
    ]);

    Livewire::test('comment-status-switcher', [
        'comment' => $comment,
        'project' => $project,
    ])
        ->call('updateStatus', 999)
        ->assertSet('currentStatus', 1);

    expect((int) $comment->fresh()->status)->toBe(1);
});
