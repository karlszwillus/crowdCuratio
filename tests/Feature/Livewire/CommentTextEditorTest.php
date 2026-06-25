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
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Volt-Komponente: comment-text-editor
|--------------------------------------------------------------------------
|
| Loest x-editable fuer Inline-Edit der Kommentar-Texte ab. Drei Pfade:
|   1. Owner kann den Text bearbeiten -> DB-Wert + sichtbarer Text passen.
|   2. Fremder Reader -> 403 beim startEdit (Policy-Gate).
|   3. Leerer Text wird stillschweigend verworfen, Edit-Modus schliesst.
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

it('Owner kann den Comment-Text ueber die Volt-Komponente bearbeiten', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);

    $comment = Comment::create([
        'user_id' => $owner->id,
        'project_id' => $project->id,
        'commentable_id' => $project->id,
        'commentable_type' => Project::class,
        'comment' => json_encode(['de' => 'Original']),
        'status' => 1,
    ]);

    Livewire::test('comment-text-editor', [
        'comment' => $comment,
        'project' => $project,
    ])
        ->assertSet('editing', false)
        ->assertSet('text', 'Original')
        ->call('startEdit')
        ->assertSet('editing', true)
        ->set('text', 'Aktualisiert')
        ->call('save')
        ->assertSet('editing', false)
        ->assertSet('text', 'Aktualisiert');

    $decoded = json_decode($comment->fresh()->comment, true);
    expect($decoded['de'])->toBe('Aktualisiert');
});

it('Fremder User ohne Permission bekommt 403 beim startEdit', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');

    $project = makeProject($owner);

    $comment = Comment::create([
        'user_id' => $owner->id,
        'project_id' => $project->id,
        'commentable_id' => $project->id,
        'commentable_type' => Project::class,
        'comment' => json_encode(['de' => 'Original']),
        'status' => 1,
    ]);

    /** @var User $stranger */
    $stranger = User::factory()->create();
    $stranger->assignRole('Reader');
    $this->actingAs($stranger);

    Livewire::test('comment-text-editor', [
        'comment' => $comment,
        'project' => $project,
    ])
        ->call('startEdit')
        ->assertStatus(403);

    $decoded = json_decode($comment->fresh()->comment, true);
    expect($decoded['de'])->toBe('Original');
});

it('Leerer Text wird stillschweigend verworfen', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);

    $comment = Comment::create([
        'user_id' => $owner->id,
        'project_id' => $project->id,
        'commentable_id' => $project->id,
        'commentable_type' => Project::class,
        'comment' => json_encode(['de' => 'Original']),
        'status' => 1,
    ]);

    Livewire::test('comment-text-editor', [
        'comment' => $comment,
        'project' => $project,
    ])
        ->call('startEdit')
        ->set('text', '   ')
        ->call('save')
        ->assertSet('editing', false);

    $decoded = json_decode($comment->fresh()->comment, true);
    expect($decoded['de'])->toBe('Original');
});
