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
| Volt-Komponente: comment-panel-list (Phase 5x.9)
|--------------------------------------------------------------------------
|
| Deckt den Live-Load-Pfad ab:
|   1. Initial ohne Selection zeigt Empty-Hint.
|   2. `comment-panel:load`-Event setzt State, lädt Kommentare.
|   3. `comment-added`-Event triggert Re-Render (Neu-Query).
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

it('Initial ohne Selection: kein Kommentar geladen, Empty-Hint sichtbar', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    $this->actingAs($owner);
    $project = makeProject($owner);

    Livewire::test('comment-panel-list', ['projectId' => $project->id])
        ->assertSet('commentableType', null)
        ->assertSet('commentableId', null)
        ->assertSee(__('comment_panel_empty_hint'));
});

it('load-Event setzt State und laedt Kommentare fuer das commentable', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    $this->actingAs($owner);
    $project = makeProject($owner);
    $chapter = makeChapter($project);

    // Kommentar am Chapter anlegen.
    $comment = new Comment;
    $comment->comment = 'Hallo Welt';
    $comment->project_id = $project->id;
    $comment->status = CommentStatus::OPEN;
    $comment->commentable_id = $chapter->id;
    $comment->commentable_type = Chapter::class;
    $comment->user()->associate($owner);
    $comment->save();

    Livewire::test('comment-panel-list', ['projectId' => $project->id])
        ->call('load', Chapter::class, $chapter->id)
        ->assertSet('commentableType', Chapter::class)
        ->assertSet('commentableId', $chapter->id)
        ->assertSee('Hallo Welt');
});

it('comment-added-Event triggert einen Re-Render', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    $this->actingAs($owner);
    $project = makeProject($owner);
    $chapter = makeChapter($project);

    $component = Livewire::test('comment-panel-list', ['projectId' => $project->id])
        ->call('load', Chapter::class, $chapter->id);

    // Direkter DB-Insert simuliert das Speichern durch den Composer.
    $comment = new Comment;
    $comment->comment = 'Frisch dazu';
    $comment->project_id = $project->id;
    $comment->status = CommentStatus::OPEN;
    $comment->commentable_id = $chapter->id;
    $comment->commentable_type = Chapter::class;
    $comment->user()->associate($owner);
    $comment->save();

    // Vor dem Refresh ist der neue Kommentar nicht sichtbar (das Blade
    // rendert erst beim naechsten Roundtrip neu).
    $component->call('reloadOnAdded')
        ->assertSee('Frisch dazu');
});
