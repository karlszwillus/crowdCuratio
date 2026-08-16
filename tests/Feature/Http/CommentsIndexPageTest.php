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
use App\Models\Invitation;
use App\Models\User;
use App\Support\CommentStatus;
use App\Support\PermissionName;
use App\Support\RoleName;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Comments-Uebersicht (Screen 11, Phase 5x.9)
|--------------------------------------------------------------------------
|
| /allComments rendert die neue Blade-View `comments/index`, analog zu
| users/index und projects/index. Deckt Zugriffslogik und Rendering ab.
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

it('Admin sieht alle Kommentare in der Uebersicht', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole(RoleName::ADMIN->value);
    /** @var User $author */
    $author = User::factory()->create();
    $author->assignRole(RoleName::READER->value);

    $project = makeProject($author);
    $chapter = makeChapter($project);

    $comment = new Comment;
    $comment->comment = 'Admin-sichtbarer Text';
    $comment->project_id = $project->id;
    $comment->status = CommentStatus::OPEN;
    $comment->commentable_id = $chapter->id;
    $comment->commentable_type = Chapter::class;
    $comment->user()->associate($author);
    $comment->save();

    $this->actingAs($admin)
        ->get('/allComments')
        ->assertOk()
        ->assertSee('Admin-sichtbarer Text');
});

it('Reader sieht nur Kommentare aus zugaenglichen Projekten', function () {
    /** @var TestCase $this */
    /** @var User $reader */
    $reader = User::factory()->create();
    $reader->assignRole(RoleName::READER->value);
    /** @var User $strangerOwner */
    $strangerOwner = User::factory()->create();
    $strangerOwner->assignRole(RoleName::READER->value);
    /** @var User $inviteHost */
    $inviteHost = User::factory()->create();
    $inviteHost->assignRole(RoleName::READER->value);

    // Fremdes Projekt mit Kommentar, auf das Reader keinen Zugriff hat.
    $strangerProject = makeProject($strangerOwner);
    $strangerChapter = makeChapter($strangerProject);
    $forbidden = new Comment;
    $forbidden->comment = 'geheimer Text';
    $forbidden->project_id = $strangerProject->id;
    $forbidden->status = CommentStatus::OPEN;
    $forbidden->commentable_id = $strangerChapter->id;
    $forbidden->commentable_type = Chapter::class;
    $forbidden->user()->associate($strangerOwner);
    $forbidden->save();

    // Zugaengliches Projekt via Invitation — das ist der Legacy-
    // Sichtbarkeits-Pfad des ContentController::allComment (Bestand,
    // JOIN mit invitations). Fuer den Test wird der Reader als Guest
    // ins Projekt geladen.
    $ownProject = makeProject($inviteHost);
    Invitation::create([
        'project_id' => $ownProject->id,
        'guest_id' => $reader->id,
        'user_id' => $inviteHost->id,
    ]);
    $ownChapter = makeChapter($ownProject);
    $visible = new Comment;
    $visible->comment = 'eigener Text';
    $visible->project_id = $ownProject->id;
    $visible->status = CommentStatus::OPEN;
    $visible->commentable_id = $ownChapter->id;
    $visible->commentable_type = Chapter::class;
    $visible->user()->associate($reader);
    $visible->save();

    $this->actingAs($reader)
        ->get('/allComments')
        ->assertOk()
        ->assertSee('eigener Text')
        ->assertDontSee('geheimer Text');
});

it('Deep-Link-Ziel eines Comment-Row traegt model+comment Query', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole(RoleName::ADMIN->value);
    /** @var User $author */
    $author = User::factory()->create();
    $author->assignRole(RoleName::READER->value);

    $project = makeProject($author);
    $chapter = makeChapter($project);
    $comment = new Comment;
    $comment->comment = 'Ping';
    $comment->project_id = $project->id;
    $comment->status = CommentStatus::OPEN;
    $comment->commentable_id = $chapter->id;
    $comment->commentable_type = Chapter::class;
    $comment->user()->associate($author);
    $comment->save();

    $response = $this->actingAs($admin)->get('/allComments')->assertOk();

    // Aktion-Icon (Deep-Link) fuehrt zur Editor-URL mit model+comment.
    $response->assertSee('projects/'.$project->id.'/edit', false)
        ->assertSee('model=App%5CModels%5CChapter', false)
        ->assertSee('comment='.$chapter->id, false);
});
