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
use App\Services\CommentRetrieve;
use App\Support\PermissionName;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| CommentRetrieve::getComments
|--------------------------------------------------------------------------
|
| Lädt Comments für ein commentable Model und liefert ein Datenpaket
| für die View: pro Comment der Sender (User-Name oder
| 'gelöschte Benutzer'-Fallback), das Status-Mapping aus
| config('project.comment'), Replies und der Owner-Flag (eingeloggter
| User == Comment-User). Plus pathReply / pathComment je nach
| commentable_type.
*/

beforeEach(function () {
    /** @var TestCase $this */
    foreach (PermissionName::all() as $name) {
        Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web'])
        ->syncPermissions(Permission::all());
});

it('liefert für ein Project ohne Kommentare ein leeres comment-Bucket mit pathComment ""', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $user->assignRole('Admin');
    $this->actingAs($user);

    $project = makeProject($user);

    $service = new CommentRetrieve;
    $data = $service->getComments('App\\Models\\Project', $project->id);

    expect($data)
        ->toBeArray()
        ->toHaveKey('id', $project->id)
        ->toHaveKey('pathComment', '');

    // Kein Comment angelegt → kein 'comment'-Key.
    expect($data)->not->toHaveKey('comment');
});

it('liefert für ein Chapter mit einem Kommentar den richtigen pathComment und Owner-Flag', function () {
    /** @var TestCase $this */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);
    $chapter = makeChapter($project);

    $comment = new Comment;
    $comment->fill([
        'comment' => 'Erster Kommentar',
        'project_id' => $project->id,
        'user_id' => $owner->id,
        'commentable_id' => $chapter->id,
        'commentable_type' => 'App\\Models\\Chapter',
        'status' => 1,
    ]);
    $comment->created_at = now();
    $comment->save();

    $service = new CommentRetrieve;
    $data = $service->getComments('App\\Models\\Chapter', $chapter->id);

    expect($data)
        ->toBeArray()
        ->toHaveKey('pathComment', 'comment.chapter')
        ->toHaveKey('id', $chapter->id);

    expect($data['comment'])
        ->toBeArray()
        ->toHaveCount(1)
        ->and($data['comment'][0])
        ->toMatchArray([
            'id' => $comment->id,
            'comment' => 'Erster Kommentar',
            'owner' => true,
            'path' => 'comment.save',
        ]);
});

it('markiert ein Comment von einem anderen User als nicht-Owner', function () {
    /** @var TestCase $this */
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $owner->assignRole('Admin');

    $project = makeProject($owner);

    $comment = new Comment;
    $comment->fill([
        'comment' => 'Fremd-Kommentar',
        'project_id' => $project->id,
        'user_id' => $other->id,
        'commentable_id' => $project->id,
        'commentable_type' => 'App\\Models\\Project',
        'status' => 1,
    ]);
    $comment->created_at = now();
    $comment->save();

    $this->actingAs($owner);

    $service = new CommentRetrieve;
    $data = $service->getComments('App\\Models\\Project', $project->id);

    expect($data['comment'][0]['owner'])->toBeFalse();
    expect($data['comment'][0]['user'])->toContain($other->name);
});

it('rollt für ein Entry mit Reply den Reply-Block korrekt aus', function () {
    /** @var TestCase $this */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $entry = makeEntry($chapter);

    $parent = new Comment;
    $parent->fill([
        'comment' => 'Parent-Kommentar',
        'project_id' => $project->id,
        'user_id' => $owner->id,
        'commentable_id' => $entry->id,
        'commentable_type' => 'App\\Models\\Entry',
        'status' => 1,
    ]);
    $parent->created_at = now();
    $parent->save();

    $reply = new Comment;
    $reply->fill([
        'comment' => 'Reply darauf',
        'project_id' => $project->id,
        'user_id' => $owner->id,
        'commentable_id' => $entry->id,
        'commentable_type' => 'App\\Models\\Entry',
        'parent_id' => $parent->id,
        'status' => 1,
    ]);
    $reply->created_at = now();
    $reply->save();

    $service = new CommentRetrieve;
    $data = $service->getComments('App\\Models\\Entry', $entry->id);

    expect($data['comment'])->toHaveCount(1);
    expect($data['comment'][0]['replies'])
        ->toBeArray()
        ->toHaveCount(1)
        ->and($data['comment'][0]['replies'][0])
        ->toMatchArray([
            'comment' => 'Reply darauf',
            'ownerReply' => true,
        ]);
});
