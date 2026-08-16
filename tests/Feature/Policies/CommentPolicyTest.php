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

use App\Models\Comment;
use App\Models\Project;
use App\Models\ProjectUserPermission;
use App\Models\User;
use App\Policies\CommentPolicy;
use App\Support\CommentStatus;
use App\Support\PermissionName;
use App\Support\RoleName;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| CommentPolicy (Phase 5x.7, BRIEFING-kommentare § 3)
|--------------------------------------------------------------------------
|
| Pinnt die Regeln aus dem Briefing: Bearbeiten strikt Autor-only,
| Loeschen Owner-Kaskade ODER Autor-ohne-Antworten, Status-Aenderung
| an comment-Permission gebunden.
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

function makeCommentOnProject(User $author, Project $project, ?int $parentId = null): Comment
{
    $comment = new Comment;
    $comment->comment = 'Text';
    $comment->project_id = $project->id;
    $comment->parent_id = $parentId;
    $comment->status = CommentStatus::OPEN;
    $comment->commentable_id = $project->id;
    $comment->commentable_type = Project::class;
    $comment->user()->associate($author);
    $comment->save();

    return $comment;
}

// ---------- update: Autor-only ----------

it('update: Autor darf den eigenen Kommentar bearbeiten', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    /** @var User $author */
    $author = User::factory()->create();
    $author->assignRole(RoleName::READER->value);
    $project = makeProject($owner);
    $comment = makeCommentOnProject($author, $project);

    expect(app(CommentPolicy::class)->update($author, $comment))->toBeTrue();
});

it('update: Owner darf einen FREMDEN Kommentar NICHT bearbeiten', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    /** @var User $reviewer */
    $reviewer = User::factory()->create();
    $reviewer->assignRole(RoleName::READER->value);
    $project = makeProject($owner);
    $comment = makeCommentOnProject($reviewer, $project);

    // Kernregel aus dem Briefing: „Bearbeiten strikt eigene".
    expect(app(CommentPolicy::class)->update($owner, $comment))->toBeFalse();
});

// ---------- delete: Owner-Kaskade ODER Autor-ohne-Antworten ----------

it('delete: Owner darf jeden Kommentar loeschen', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    /** @var User $stranger */
    $stranger = User::factory()->create();
    $stranger->assignRole(RoleName::READER->value);
    $project = makeProject($owner);
    $comment = makeCommentOnProject($stranger, $project);

    expect(app(CommentPolicy::class)->delete($owner, $comment))->toBeTrue();
});

it('delete: Autor darf den eigenen Kommentar NUR ohne Antworten loeschen', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    /** @var User $author */
    $author = User::factory()->create();
    $author->assignRole(RoleName::READER->value);
    $project = makeProject($owner);
    $comment = makeCommentOnProject($author, $project);

    // Ohne Antwort: erlaubt.
    expect(app(CommentPolicy::class)->delete($author, $comment))->toBeTrue();

    // Mit Antwort: gesperrt.
    makeCommentOnProject($owner, $project, parentId: $comment->id);
    expect(app(CommentPolicy::class)->delete($author, $comment->refresh()))->toBeFalse();
});

it('delete: Fremder ohne Autor-/Owner-Rolle darf NICHT loeschen', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    /** @var User $author */
    $author = User::factory()->create();
    $author->assignRole(RoleName::READER->value);
    /** @var User $stranger */
    $stranger = User::factory()->create();
    $stranger->assignRole(RoleName::READER->value);
    $project = makeProject($owner);
    $comment = makeCommentOnProject($author, $project);

    expect(app(CommentPolicy::class)->delete($stranger, $comment))->toBeFalse();
});

// ---------- changeStatus: comment-Permission ----------

it('changeStatus: User mit comment-Permission auf dem Projekt darf', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    /** @var User $invitee */
    $invitee = User::factory()->create();
    $invitee->assignRole(RoleName::READER->value);
    $project = makeProject($owner);
    ProjectUserPermission::create([
        'project_id' => $project->id,
        'user_id' => $invitee->id,
        'permission_id' => Permission::where('name', PermissionName::COMMENT->value)->value('id'),
    ]);

    $comment = makeCommentOnProject($invitee, $project);

    expect(app(CommentPolicy::class)->changeStatus($invitee, $comment))->toBeTrue();
});

it('changeStatus: User OHNE comment-Permission darf NICHT', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    /** @var User $reader */
    $reader = User::factory()->create();
    $reader->assignRole(RoleName::READER->value);
    $project = makeProject($owner);
    // Reader hat nur view im Pivot, kein comment.
    ProjectUserPermission::create([
        'project_id' => $project->id,
        'user_id' => $reader->id,
        'permission_id' => Permission::where('name', PermissionName::VIEW->value)->value('id'),
    ]);
    $comment = makeCommentOnProject($owner, $project);

    expect(app(CommentPolicy::class)->changeStatus($reader, $comment))->toBeFalse();
});

// ---------- Admin-Shortcut ----------

it('before: Admin darf alles', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole(RoleName::ADMIN->value);
    /** @var User $author */
    $author = User::factory()->create();
    $author->assignRole(RoleName::READER->value);
    $project = makeProject($author);
    $comment = makeCommentOnProject($author, $project);

    expect(app(CommentPolicy::class)->before($admin, 'update'))->toBeTrue();
    expect(app(CommentPolicy::class)->before($admin, 'delete'))->toBeTrue();
});
