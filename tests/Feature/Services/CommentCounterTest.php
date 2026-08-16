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
use App\Models\Project;
use App\Models\ProjectUserPermission;
use App\Models\User;
use App\Services\CommentCounter;
use App\Support\CommentStatus;
use App\Support\PermissionName;
use App\Support\RoleName;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| CommentCounter (Phase 5x.10)
|--------------------------------------------------------------------------
|
| Deckt zwei Public-APIs ab:
|  - openCountForUser(User): pro User summiert, nur Wurzel-Comments,
|    nur Status open + in_progress, nur zugaengliche Projekte.
|  - openCountForCommentable($type, $id): pro Modell, gleiche Status-
|    Filter.
*/

beforeEach(function () {
    foreach (PermissionName::all() as $permissionName) {
        Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
    }
    Role::firstOrCreate(['name' => RoleName::ADMIN->value, 'guard_name' => 'web'])
        ->syncPermissions(Permission::all());
    Role::firstOrCreate(['name' => RoleName::READER->value, 'guard_name' => 'web'])
        ->syncPermissions(Permission::whereIn('name', ['view'])->get());

    // Interner Request-Cache im Service leeren, damit Tests unabhaengig
    // voneinander messen koennen.
    $refl = new ReflectionClass(CommentCounter::class);
    foreach (['cache', 'commentableCache'] as $prop) {
        $p = $refl->getProperty($prop);
        $p->setAccessible(true);
        $p->setValue(null, []);
    }
});

function makeCommentOn(Project $project, string $status, ?int $parentId = null, ?User $author = null): Comment
{
    $comment = new Comment;
    $comment->comment = 'body';
    $comment->project_id = $project->id;
    $comment->parent_id = $parentId;
    $comment->status = CommentStatus::from($status);
    $comment->commentable_id = $project->id;
    $comment->commentable_type = Project::class;
    $comment->user()->associate($author ?? User::factory()->create());
    $comment->save();

    return $comment;
}

// ---------- openCountForUser ----------

it('openCountForUser: zaehlt nur open + in_progress Root-Comments in eigenen Projekten', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    $project = makeProject($owner);

    makeCommentOn($project, CommentStatus::OPEN->value);
    makeCommentOn($project, CommentStatus::IN_PROGRESS->value);
    makeCommentOn($project, CommentStatus::RESOLVED->value);   // ignoriert
    makeCommentOn($project, CommentStatus::REJECTED->value);   // ignoriert
    $root = makeCommentOn($project, CommentStatus::OPEN->value);
    makeCommentOn($project, CommentStatus::OPEN->value, parentId: $root->id); // Reply, ignoriert

    expect(CommentCounter::openCountForUser($owner))->toBe(3);
});

it('openCountForUser: zaehlt fremde Projekte mit comment-Recht, ignoriert reine Leserechte', function () {
    /** @var TestCase $this */
    /** @var User $reader */
    $reader = User::factory()->create();
    $reader->assignRole(RoleName::READER->value);
    /** @var User $ownerB */
    $ownerB = User::factory()->create();
    $ownerB->assignRole(RoleName::READER->value);
    /** @var User $ownerC */
    $ownerC = User::factory()->create();
    $ownerC->assignRole(RoleName::READER->value);

    $projectB = makeProject($ownerB);
    $projectC = makeProject($ownerC);

    // Auf Projekt B hat reader `comment`-Recht → zaehlt mit.
    ProjectUserPermission::create([
        'project_id' => $projectB->id,
        'user_id' => $reader->id,
        'permission_id' => Permission::where('name', PermissionName::COMMENT->value)->value('id'),
    ]);
    // Auf Projekt C hat reader nur `view`-Recht → zaehlt NICHT mit.
    ProjectUserPermission::create([
        'project_id' => $projectC->id,
        'user_id' => $reader->id,
        'permission_id' => Permission::where('name', PermissionName::VIEW->value)->value('id'),
    ]);

    makeCommentOn($projectB, CommentStatus::OPEN->value);
    makeCommentOn($projectB, CommentStatus::IN_PROGRESS->value);
    makeCommentOn($projectC, CommentStatus::OPEN->value); // Unsichtbar fuer reader.

    expect(CommentCounter::openCountForUser($reader))->toBe(2);
});

it('openCountForUser: null-User → 0, ohne Query', function () {
    /** @var TestCase $this */
    expect(CommentCounter::openCountForUser(null))->toBe(0);
});

it('openCountForUser: Admin sieht projektuebergreifend alle offenen', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole(RoleName::ADMIN->value);

    /** @var User $strangerOwner */
    $strangerOwner = User::factory()->create();
    $strangerOwner->assignRole(RoleName::READER->value);
    $project = makeProject($strangerOwner);

    makeCommentOn($project, CommentStatus::OPEN->value);
    makeCommentOn($project, CommentStatus::IN_PROGRESS->value);
    makeCommentOn($project, CommentStatus::RESOLVED->value);

    expect(CommentCounter::openCountForUser($admin))->toBe(2);
});

// ---------- openCountForCommentable ----------

it('openCountForCommentable: zaehlt nur Root-Comments am jeweiligen Modell', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    $project = makeProject($owner);

    // Ein Kapitel mit zwei offenen Comments und einer Antwort.
    $chapter = makeChapter($project);

    $root = new Comment;
    $root->comment = 'X';
    $root->project_id = $project->id;
    $root->status = CommentStatus::OPEN;
    $root->commentable_id = $chapter->id;
    $root->commentable_type = Chapter::class;
    $root->user()->associate($owner);
    $root->save();

    $second = new Comment;
    $second->comment = 'Y';
    $second->project_id = $project->id;
    $second->status = CommentStatus::IN_PROGRESS;
    $second->commentable_id = $chapter->id;
    $second->commentable_type = Chapter::class;
    $second->user()->associate($owner);
    $second->save();

    $reply = new Comment;
    $reply->comment = 'Z';
    $reply->project_id = $project->id;
    $reply->status = CommentStatus::OPEN;
    $reply->parent_id = $root->id; // Reply
    $reply->commentable_id = $chapter->id;
    $reply->commentable_type = Chapter::class;
    $reply->user()->associate($owner);
    $reply->save();

    expect(CommentCounter::openCountForCommentable(Chapter::class, $chapter->id))->toBe(2);
});
