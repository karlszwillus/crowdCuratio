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
use App\Services\CommentService;
use Illuminate\Http\Request;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| CommentService
|--------------------------------------------------------------------------
|
| Deckt die fünf Schreibpfade + den dispatchSaveAction-Switch ab.
| Modell-Repräsentant: Project (über makeProject), weil das DTO-
| neutrale Verhalten gleichermaßen für Chapter, Entry, Text, Image,
| Gallery, Audiovisual und MediaContent gilt — alle haben die
| `comments()`-MorphMany direkt im Modell.
*/

it('addComment legt einen Comment am commentable an', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);

    $request = new Request;
    $request->setUserResolver(fn () => $owner);
    $request->merge([
        'comment' => 'Test-Body',
        'IdProjectComment' => $project->id,
    ]);

    $service = new CommentService;
    $service->addComment($project, $request);

    $comment = Comment::where('commentable_id', $project->id)
        ->where('commentable_type', 'App\Models\Project')
        ->first();

    expect($comment)->not->toBeNull();
    expect($comment->comment)->toBe('Test-Body');
    expect((int) $comment->status)->toBe(1);
    expect($comment->user_id)->toBe($owner->id);
});

it('replyToComment legt einen Comment mit parent_id an', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $parent = Comment::create([
        'user_id' => $owner->id,
        'project_id' => $project->id,
        'commentable_id' => $project->id,
        'commentable_type' => 'App\Models\Project',
        'comment' => json_encode(['de' => 'Parent']),
        'status' => 1,
    ]);

    $request = new Request;
    $request->setUserResolver(fn () => $owner);
    $request->merge([
        'reply' => 'Antwort',
        'projectId' => $project->id,
        'commentId' => $parent->id,
    ]);

    $service = new CommentService;
    $service->replyToComment($project, $request);

    $reply = Comment::where('parent_id', $parent->id)->first();

    expect($reply)->not->toBeNull();
    expect($reply->comment)->toBe('Antwort');
});

it('editComment aktualisiert den Body als de-Lokalisierung', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $comment = Comment::create([
        'user_id' => $owner->id,
        'project_id' => $project->id,
        'commentable_id' => $project->id,
        'commentable_type' => 'App\Models\Project',
        'comment' => json_encode(['de' => 'Original']),
        'status' => 1,
    ]);

    $service = new CommentService;
    $service->editComment($comment->id, 'Editiert');

    $comment->refresh();

    expect($comment->comment)->toBe('Editiert');
});

it('deleteComment soft-deleted den Comment', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $comment = Comment::create([
        'user_id' => $owner->id,
        'project_id' => $project->id,
        'commentable_id' => $project->id,
        'commentable_type' => 'App\Models\Project',
        'comment' => json_encode(['de' => 'X']),
        'status' => 1,
    ]);

    $service = new CommentService;
    $service->deleteComment($comment->id);

    expect(Comment::find($comment->id))->toBeNull();
});

it('setCommentStatus aktualisiert den status-Wert', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $comment = Comment::create([
        'user_id' => $owner->id,
        'project_id' => $project->id,
        'commentable_id' => $project->id,
        'commentable_type' => 'App\Models\Project',
        'comment' => json_encode(['de' => 'X']),
        'status' => 1,
    ]);

    $service = new CommentService;
    $service->setCommentStatus($comment->id, 3);

    $comment->refresh();

    expect((int) $comment->status)->toBe(3);
});

it('dispatchSaveAction routet btn_submit=Edit an editComment', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $comment = Comment::create([
        'user_id' => $owner->id,
        'project_id' => $project->id,
        'commentable_id' => $project->id,
        'commentable_type' => 'App\Models\Project',
        'comment' => json_encode(['de' => 'Old']),
        'status' => 1,
    ]);

    $request = new Request;
    $request->merge([
        'btn_submit' => 'Edit',
        'pk' => $comment->id,
        'value' => 'Neu',
    ]);

    $service = new CommentService;
    $result = $service->dispatchSaveAction($project, $request);

    expect($result)->toBeTrue();

    $comment->refresh();
    expect($comment->comment)->toBe('Neu');
});

it('dispatchSaveAction routet btn_submit=delete an deleteComment', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $comment = Comment::create([
        'user_id' => $owner->id,
        'project_id' => $project->id,
        'commentable_id' => $project->id,
        'commentable_type' => 'App\Models\Project',
        'comment' => json_encode(['de' => 'X']),
        'status' => 1,
    ]);

    $request = new Request;
    $request->merge([
        'btn_submit' => 'delete',
        'id' => $comment->id,
    ]);

    $service = new CommentService;
    $service->dispatchSaveAction($project, $request);

    expect(Comment::find($comment->id))->toBeNull();
});

it('dispatchSaveAction routet unbekannte btn_submit-Werte an replyToComment', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $parent = Comment::create([
        'user_id' => $owner->id,
        'project_id' => $project->id,
        'commentable_id' => $project->id,
        'commentable_type' => 'App\Models\Project',
        'comment' => json_encode(['de' => 'Parent']),
        'status' => 1,
    ]);

    $request = new Request;
    $request->setUserResolver(fn () => $owner);
    $request->merge([
        'btn_submit' => 'Reply',
        'reply' => 'Antwort',
        'projectId' => $project->id,
        'commentId' => $parent->id,
    ]);

    $service = new CommentService;
    $service->dispatchSaveAction($project, $request);

    $reply = Comment::where('parent_id', $parent->id)->first();

    expect($reply)->not->toBeNull();
    expect($reply->comment)->toBe('Antwort');
});

it('dispatchSaveAction liefert false bei fehlendem btn_submit', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);

    $request = new Request;

    $service = new CommentService;

    expect($service->dispatchSaveAction($project, $request))->toBeFalse();
});
