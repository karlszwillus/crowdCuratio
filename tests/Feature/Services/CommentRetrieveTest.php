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
    foreach (PermissionName::all() as $permissionName) {
        Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
    }

    Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web'])
        ->syncPermissions(Permission::all());
});

it('liefert für ein Project ohne Kommentare ein leeres comment-Bucket mit pathComment ""', function () {
    /** @var TestCase $this */
    /** @var User $user */
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
    /** @var User $owner */
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
    /** @var User $owner */
    $owner = User::factory()->create();
    /** @var User $other */
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

it('liefert für ein Text den pathComment "comment.text" und Save-Pfad', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);
    $text = makeText();

    $comment = new Comment;
    $comment->fill([
        'comment' => 'Text-Comment',
        'project_id' => $project->id,
        'user_id' => $owner->id,
        'commentable_id' => $text->id,
        'commentable_type' => 'App\\Models\\Text',
        'status' => 1,
    ]);
    $comment->created_at = now();
    $comment->save();

    $data = (new CommentRetrieve)->getComments('App\\Models\\Text', $text->id);

    expect($data)
        ->toHaveKey('pathComment', 'comment.text')
        ->toHaveKey('id', $text->id);
    expect($data['comment'][0]['path'])->toBe('comment.text.save');
});

it('liefert für ein Image den pathComment "comment.image" und Save-Pfad', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);
    $image = makeImage();

    $comment = new Comment;
    $comment->fill([
        'comment' => 'Image-Comment',
        'project_id' => $project->id,
        'user_id' => $owner->id,
        'commentable_id' => $image->id,
        'commentable_type' => 'App\\Models\\Image',
        'status' => 1,
    ]);
    $comment->created_at = now();
    $comment->save();

    $data = (new CommentRetrieve)->getComments('App\\Models\\Image', $image->id);

    expect($data)->toHaveKey('pathComment', 'comment.image');
    expect($data['comment'][0]['path'])->toBe('comment.image.save');
});

it('liefert für ein Gallery den pathComment "comment.gallery" und Save-Pfad', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);
    $gallery = makeGallery();

    $comment = new Comment;
    $comment->fill([
        'comment' => 'Gallery-Comment',
        'project_id' => $project->id,
        'user_id' => $owner->id,
        'commentable_id' => $gallery->id,
        'commentable_type' => 'App\\Models\\Gallery',
        'status' => 1,
    ]);
    $comment->created_at = now();
    $comment->save();

    $data = (new CommentRetrieve)->getComments('App\\Models\\Gallery', $gallery->id);

    expect($data)->toHaveKey('pathComment', 'comment.gallery');
    expect($data['comment'][0]['path'])->toBe('comment.gallery.save');
});

it('liefert für ein Audiovisual den pathComment "comment.audiovisual" und Save-Pfad', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);
    $av = makeAudiovisual();

    $comment = new Comment;
    $comment->fill([
        'comment' => 'AV-Comment',
        'project_id' => $project->id,
        'user_id' => $owner->id,
        'commentable_id' => $av->id,
        'commentable_type' => 'App\\Models\\Audiovisual',
        'status' => 1,
    ]);
    $comment->created_at = now();
    $comment->save();

    $data = (new CommentRetrieve)->getComments('App\\Models\\Audiovisual', $av->id);

    expect($data)->toHaveKey('pathComment', 'comment.audiovisual');
    expect($data['comment'][0]['path'])->toBe('comment.audiovisual.save');
});

it('liefert für unbekannte Class (MediaContent) leeren pathReply ohne Crash', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $entry = makeEntry($chapter);

    // MediaContent als Fallback-Class — kein Switch-Case, pathReply
    // bleibt leer. Wird von ContentController::getTextComment /
    // getImageComment so durchgereicht.
    $mediaContent = \App\Models\MediaContent::create([
        'position' => 1,
        'media_content_id' => 1,
        'media_contentable_id' => $entry->id,
        'media_contentable_type' => 'App\\Models\\Text',
    ]);

    $comment = new Comment;
    $comment->fill([
        'comment' => 'MC-Comment',
        'project_id' => $project->id,
        'user_id' => $owner->id,
        'commentable_id' => $mediaContent->id,
        'commentable_type' => 'App\\Models\\MediaContent',
        'status' => 1,
    ]);
    $comment->created_at = now();
    $comment->save();

    $data = (new CommentRetrieve)->getComments('App\\Models\\MediaContent', $mediaContent->id);

    expect($data['pathComment'])->toBe('');
    expect($data['comment'][0]['path'])->toBe('');
});

it('rollt für ein Entry mit Reply den Reply-Block korrekt aus', function () {
    /** @var TestCase $this */
    /** @var User $owner */
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
