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
use App\Models\User;
use App\Support\PermissionName;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Comment-Pfade-Charakterisierung
|--------------------------------------------------------------------------
|
| Fixiert das beobachtbare Verhalten der Comment-Endpunkte über die
| fünf betroffenen Controller (Project, Chapter, Entry,
| Content [Text/Image/Gallery], Audiovisual) vor der
| CommentService-Extraktion.
|
| Strategie: nicht jeder Controller-Pfad einzeln, sondern jeder
| Pfad-Typ × ein Repräsentanten-Controller. Die Pfade sind
| strukturell identisch (Trait-Aufruf), nach dem Refactor ebenso —
| volle Symmetrie über die fünf Controller.
*/

beforeEach(function () {
    foreach (PermissionName::all() as $permissionName) {
        Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
    }

    Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web'])
        ->syncPermissions(Permission::all());
});

// ---------- ProjectController ----------

it('commentProject legt einen neuen Top-Level-Kommentar auf dem Project an', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);

    $this->post(route('comment.project'), [
        'id' => $project->id,
        'IdProjectComment' => $project->id,
        'comment' => 'Test-Kommentar',
    ]);

    $comment = Comment::where('commentable_type', 'App\Models\Project')
        ->where('commentable_id', $project->id)
        ->first();

    expect($comment)->not->toBeNull();
    expect($comment->comment)->toBe('Test-Kommentar');
});

it('setStatusProject setzt den Status eines bestehenden Comments', function () {
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
        'commentable_type' => 'App\Models\Project',
        'comment' => json_encode(['de' => 'A']),
        'status' => 1,
    ]);

    $this->post(route('comment.project.status'), [
        'id' => $comment->id,
        'status' => 3,
    ]);

    $comment->refresh();

    expect((int) $comment->status)->toBe(3);
});

// ---------- ChapterController ----------

it('commentChapter legt einen neuen Top-Level-Kommentar auf dem Chapter an', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);
    $chapter = makeChapter($project);

    $this->post(route('comment.chapter'), [
        'id' => $chapter->id,
        'IdProjectComment' => $project->id,
        'comment' => 'Chapter-Kommentar',
    ]);

    $comment = Comment::where('commentable_type', 'App\Models\Chapter')
        ->where('commentable_id', $chapter->id)
        ->first();

    expect($comment)->not->toBeNull();
});

it('saveComment löscht einen Comment über den btn_submit=delete-Pfad', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $comment = Comment::create([
        'user_id' => $owner->id,
        'project_id' => $project->id,
        'commentable_id' => $chapter->id,
        'commentable_type' => 'App\Models\Chapter',
        'comment' => json_encode(['de' => 'B']),
        'status' => 1,
    ]);

    $this->post("/comment/chapter/{$chapter->id}/save", [
        'btn_submit' => 'delete',
        'id' => $comment->id,
    ]);

    expect(Comment::find($comment->id))->toBeNull();
});

it('saveComment editiert einen Comment-Body über den btn_submit=Edit-Pfad', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $comment = Comment::create([
        'user_id' => $owner->id,
        'project_id' => $project->id,
        'commentable_id' => $chapter->id,
        'commentable_type' => 'App\Models\Chapter',
        'comment' => json_encode(['de' => 'Original']),
        'status' => 1,
    ]);

    $this->post("/comment/chapter/{$chapter->id}/save", [
        'btn_submit' => 'Edit',
        'pk' => $comment->id,
        'value' => 'Editiert',
    ]);

    $comment->refresh();

    expect($comment->comment)->toBe('Editiert');
});

it('saveComment legt einen Reply mit parent_id an, wenn btn_submit unbekannt ist', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $parent = Comment::create([
        'user_id' => $owner->id,
        'project_id' => $project->id,
        'commentable_id' => $chapter->id,
        'commentable_type' => 'App\Models\Chapter',
        'comment' => json_encode(['de' => 'Parent']),
        'status' => 1,
    ]);

    $this->post("/comment/chapter/{$chapter->id}/save", [
        'btn_submit' => 'Reply',
        'reply' => 'Antwort',
        'commentId' => $parent->id,
        'question' => $chapter->id,
        'projectId' => $project->id,
    ]);

    $reply = Comment::where('parent_id', $parent->id)->first();

    expect($reply)->not->toBeNull();
    expect($reply->comment)->toBe('Antwort');
});

// ---------- EntryController ----------

it('commentEntry legt einen neuen Top-Level-Kommentar auf dem Entry an', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $entry = makeEntry($chapter);

    $this->post(route('comment.entry'), [
        'id' => $entry->id,
        'IdProjectComment' => $project->id,
        'comment' => 'Entry-Kommentar',
    ]);

    $comment = Comment::where('commentable_type', 'App\Models\Entry')
        ->where('commentable_id', $entry->id)
        ->first();

    expect($comment)->not->toBeNull();
});

it('setStatusEntry setzt den Status eines Comments', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $entry = makeEntry($chapter);
    $comment = Comment::create([
        'user_id' => $owner->id,
        'project_id' => $project->id,
        'commentable_id' => $entry->id,
        'commentable_type' => 'App\Models\Entry',
        'comment' => json_encode(['de' => 'C']),
        'status' => 1,
    ]);

    $this->post(route('comment.entry.status', $entry), [
        'id' => $comment->id,
        'status' => 2,
    ]);

    $comment->refresh();

    expect((int) $comment->status)->toBe(2);
});

// ---------- updateStatus (ContentController shared Endpoint) ----------

it('updateStatus per POST-Route setzt einen Comment-Status', function () {
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
        'commentable_type' => 'App\Models\Project',
        'comment' => json_encode(['de' => 'D']),
        'status' => 1,
    ]);

    $this->post("/comment/{$comment->id}/update/4");

    $comment->refresh();

    expect($comment->status)->toBe(4);
});
