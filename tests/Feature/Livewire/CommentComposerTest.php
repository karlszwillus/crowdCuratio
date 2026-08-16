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

declare(strict_types=1);

use App\Models\Chapter;
use App\Models\Comment;
use App\Models\ProjectUserPermission;
use App\Models\User;
use App\Support\CommentStatus;
use App\Support\PermissionName;
use App\Support\RoleName;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Volt-Komponente: comment-composer (Phase 5x.8)
|--------------------------------------------------------------------------
|
| Deckt die drei kritischen Pfade ab:
|   1. Owner mit comment-Permission speichert einen Top-Level-Kommentar.
|   2. Reader ohne comment-Permission bekommt die Leser-Hinweiszeile,
|      kein Textfeld — save() muss 403 werfen (Defense-in-Depth).
|   3. Reply-Variante speichert an den Parent-Comment.
*/

beforeEach(function () {
    foreach (PermissionName::all() as $permissionName) {
        Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
    }
    Role::firstOrCreate(['name' => RoleName::ADMIN->value, 'guard_name' => 'web'])
        ->syncPermissions(Permission::all());
    Role::firstOrCreate(['name' => RoleName::READER->value, 'guard_name' => 'web'])
        ->syncPermissions(Permission::whereIn('name', ['view'])->get());
});

it('Owner speichert Top-Level-Kommentar am Chapter', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    $this->actingAs($owner);
    $project = makeProject($owner);
    $chapter = makeChapter($project);

    Livewire::test('comment-composer', [
        'commentableType' => Chapter::class,
        'commentableId' => $chapter->id,
        'projectId' => $project->id,
        'variant' => 'full',
    ])
        ->set('body', 'Ein neuer Gedanke.')
        ->call('save');

    expect(Comment::where('commentable_id', $chapter->id)->count())->toBe(1);

    $created = Comment::where('commentable_id', $chapter->id)->first();
    expect($created->comment)->toContain('Ein neuer Gedanke.');
    expect($created->parent_id)->toBeNull();
});

it('Reader ohne comment-Permission bekommt canComment=false', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    /** @var User $reader */
    $reader = User::factory()->create();
    $reader->assignRole(RoleName::READER->value);
    $this->actingAs($reader);
    $project = makeProject($owner);
    $chapter = makeChapter($project);
    // Reader bekommt nur view-Recht auf dem Projekt, kein comment.
    ProjectUserPermission::create([
        'project_id' => $project->id,
        'user_id' => $reader->id,
        'permission_id' => Permission::where('name', PermissionName::VIEW->value)->value('id'),
    ]);

    Livewire::test('comment-composer', [
        'commentableType' => Chapter::class,
        'commentableId' => $chapter->id,
        'projectId' => $project->id,
        'variant' => 'full',
    ])->assertSet('canComment', false);
});

it('Reader-save() wirft 403 und speichert keinen Kommentar', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    /** @var User $reader */
    $reader = User::factory()->create();
    $reader->assignRole(RoleName::READER->value);
    $this->actingAs($reader);
    $project = makeProject($owner);
    $chapter = makeChapter($project);

    Livewire::test('comment-composer', [
        'commentableType' => Chapter::class,
        'commentableId' => $chapter->id,
        'projectId' => $project->id,
        'variant' => 'full',
    ])
        ->set('body', 'Verbotene Eingabe.')
        ->call('save')
        ->assertStatus(403);

    expect(Comment::where('commentable_id', $chapter->id)->count())->toBe(0);
});

it('Reply-Variante speichert an den Parent-Comment', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    $this->actingAs($owner);
    $project = makeProject($owner);
    $chapter = makeChapter($project);

    // Bestehender Root-Kommentar.
    $root = new Comment;
    $root->comment = 'Wurzel';
    $root->project_id = $project->id;
    $root->status = CommentStatus::OPEN;
    $root->commentable_id = $chapter->id;
    $root->commentable_type = Chapter::class;
    $root->user()->associate($owner);
    $root->save();

    Livewire::test('comment-composer', [
        'commentableType' => Chapter::class,
        'commentableId' => $chapter->id,
        'projectId' => $project->id,
        'parentId' => $root->id,
        'variant' => 'reply',
    ])
        ->call('toggle') // Reply-Variante ist initial zu; erst oeffnen.
        ->set('body', 'Antwort auf die Wurzel.')
        ->call('save');

    expect(Comment::where('parent_id', $root->id)->count())->toBe(1);
});

it('Leerer Body wird via required-Validation abgefangen', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    $this->actingAs($owner);
    $project = makeProject($owner);
    $chapter = makeChapter($project);

    Livewire::test('comment-composer', [
        'commentableType' => Chapter::class,
        'commentableId' => $chapter->id,
        'projectId' => $project->id,
        'variant' => 'full',
    ])
        ->set('body', '')
        ->call('save')
        ->assertHasErrors(['body' => 'required']);

    expect(Comment::where('commentable_id', $chapter->id)->count())->toBe(0);
});
