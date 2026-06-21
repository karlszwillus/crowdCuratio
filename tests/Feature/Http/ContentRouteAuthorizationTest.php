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

use App\Models\Audiovisual;
use App\Models\Chapter;
use App\Models\Comment;
use App\Models\Entry;
use App\Models\Gallery;
use App\Models\Image;
use App\Models\MediaContent;
use App\Models\Project;
use App\Models\ProjectUserPermission;
use App\Models\Text;
use App\Models\User;
use App\Support\PermissionName;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Welle 4a-Hotfix-II.c — Pinning-Tests für vier-Controller-Sweep
|--------------------------------------------------------------------------
|
| Pinning-Tests für die in Welle 4a-Hotfix-II.a + II.b gegateten
| Methoden in ChapterController, EntryController, ContentController
| und AudiovisualController. Fokus auf state-changing Vektoren —
| dort wo ein Reader vorher fremde Daten verändern oder Comment-
| Status auf fremden Projekten manipulieren konnte.
|
| Wichtig: `app(PermissionRegistrar)->forgetCachedPermissions()` ist
| im beforeEach gesetzt, damit die Tests gegen einen Spatie-Cache
| im Hot-State laufen (Konvention aus dem ADR-0023-Setup).
*/

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (PermissionName::all() as $permissionName) {
        Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
    }

    Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web'])
        ->syncPermissions(Permission::all());
    Role::firstOrCreate(['name' => 'Reader', 'guard_name' => 'web'])
        ->syncPermissions(['view']);
    Role::firstOrCreate(['name' => 'Editor', 'guard_name' => 'web'])
        ->syncPermissions(['view', 'add', 'edit', 'delete', 'comment']);
});

/**
 * Hängt ein Content-Modell via MediaContent-Pivot an einen Entry.
 * Beide alte (media_contentable_*) und neue (content_/parent_)
 * Spalten werden befüllt, damit die Tests sowohl gegen die
 * Doppelschreibungs-Welle als auch gegen den späteren Cleanup robust
 * laufen.
 */
function attachToEntry(string $contentClass, int $contentId, Entry $entry): MediaContent
{
    return MediaContent::create([
        'media_content_id' => $contentId,
        'media_contentable_id' => $entry->id,
        'media_contentable_type' => $contentClass,
        'content_id' => $contentId,
        'content_type' => $contentClass,
        'parent_id' => $entry->id,
        'parent_type' => Entry::class,
        'position' => 0,
    ]);
}

/**
 * Setzt eine Reader-User-Kombi mit Project auf, an dem der Stranger
 * NICHT eingeladen ist. Liefert [owner, stranger, project, entry]
 * als assoc-Array.
 *
 * @return array{owner: User, stranger: User, project: Project, entry: Entry}
 */
function readerVsStrangerSetup(): array
{
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Reader');
    /** @var User $stranger */
    $stranger = User::factory()->create();
    $stranger->assignRole('Reader');

    $project = makeProject($owner);
    $entry = makeEntry(makeChapter($project));

    return ['owner' => $owner, 'stranger' => $stranger, 'project' => $project, 'entry' => $entry];
}

/*
|--------------------------------------------------------------------------
| ChapterController — Welle II.a
|--------------------------------------------------------------------------
*/

it('ChapterController::edit — Fremder darf JSON-Daten fremder Chapter NICHT sehen', function () {
    /** @var TestCase $this */
    ['stranger' => $stranger, 'project' => $project] = readerVsStrangerSetup();
    $chapter = makeChapter($project);

    $this->actingAs($stranger);

    $this->get('/chapters/'.$chapter->id.'/edit')->assertStatus(403);
});

it('ChapterController::commentChapter — Fremder kriegt 403, auch wenn Auth durch StoreCommentRequest', function () {
    /** @var TestCase $this */
    ['stranger' => $stranger, 'project' => $project] = readerVsStrangerSetup();
    $chapter = makeChapter($project);

    $this->actingAs($stranger);

    $this->post(route('comment.chapter'), [
        'id' => $chapter->id,
        'comment' => 'Unerlaubter Kommentar',
    ])->assertStatus(403);
});

it('ChapterController::setCommentStatusChapter — Fremder darf fremden Comment-Status NICHT setzen', function () {
    /** @var TestCase $this */
    ['owner' => $owner, 'stranger' => $stranger, 'project' => $project] = readerVsStrangerSetup();
    $chapter = makeChapter($project);
    $comment = Comment::create([
        'user_id' => $owner->id,
        'project_id' => $project->id,
        'comment' => 'Owner-Kommentar',
        'status' => 0,
        'commentable_id' => $chapter->id,
        'commentable_type' => Chapter::class,
    ]);

    $this->actingAs($stranger);

    $this->post(route('comment.chapter.status'), [
        'id' => $comment->id,
        'status' => 1,
    ])->assertStatus(403);
});

/*
|--------------------------------------------------------------------------
| EntryController — Welle II.a
|--------------------------------------------------------------------------
*/

it('EntryController::edit — Fremder darf JSON-Daten fremder Entries NICHT sehen', function () {
    /** @var TestCase $this */
    ['stranger' => $stranger, 'entry' => $entry] = readerVsStrangerSetup();

    $this->actingAs($stranger);

    $this->get('/entries/'.$entry->id.'/edit')->assertStatus(403);
});

it('EntryController::commentEntry — Fremder kriegt 403', function () {
    /** @var TestCase $this */
    ['stranger' => $stranger, 'entry' => $entry] = readerVsStrangerSetup();

    $this->actingAs($stranger);

    $this->post(route('comment.entry'), [
        'id' => $entry->id,
        'comment' => 'Unerlaubter Kommentar',
    ])->assertStatus(403);
});

it('EntryController::setCommentStatusEntry — Fremder darf fremden Comment-Status NICHT setzen', function () {
    /** @var TestCase $this */
    ['owner' => $owner, 'stranger' => $stranger, 'project' => $project, 'entry' => $entry] = readerVsStrangerSetup();
    $comment = Comment::create([
        'user_id' => $owner->id,
        'project_id' => $project->id,
        'comment' => 'Owner-Kommentar',
        'status' => 0,
        'commentable_id' => $entry->id,
        'commentable_type' => Entry::class,
    ]);

    $this->actingAs($stranger);

    $this->post(route('comment.entry.status'), [
        'id' => $comment->id,
        'status' => 1,
    ])->assertStatus(403);
});

/*
|--------------------------------------------------------------------------
| ContentController::saveText — project-scoped Gate
|--------------------------------------------------------------------------
|
| saveText gate'd project-scoped:
|   - textId-Pfad: authorize('update', $text)
|   - Create-Pfad (entryId): authorize('update', $entry)
|   - translationMode mit nur originId/copyrightId: hasPermissionTo('edit')
|     als Defense-in-Depth (Sources sind global geteilt, kein
|     project-Bezug).
|
| Owner-Shortcut in OwnerScopedPolicy lässt Project-Owner ohne
| globale 'edit'-Permission durch.
*/

it('ContentController::saveText — Reader auf fremdem Entry kommt nicht durch (project-scoped Gate)', function () {
    /** @var TestCase $this */
    ['stranger' => $stranger, 'entry' => $entry] = readerVsStrangerSetup();

    $this->actingAs($stranger);

    $this->post(route('text.store'), [
        'entryId' => $entry->id,
        'contentText' => 'Schmuggel',
        'copyrightText' => 'C',
        'originText' => 'O',
    ])->assertStatus(403);
});

it('ContentController::saveText — Editor mit globaler edit aber ohne Project-Einladung kriegt 403 auf fremdem Entry', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Reader');
    /** @var User $editor */
    $editor = User::factory()->create();
    $editor->assignRole('Editor');

    $project = makeProject($owner);
    $entry = makeEntry(makeChapter($project));

    $this->actingAs($editor);

    $this->post(route('text.store'), [
        'entryId' => $entry->id,
        'contentText' => 'Schmuggel',
        'copyrightText' => 'C',
        'originText' => 'O',
    ])->assertStatus(403);
});

/*
|--------------------------------------------------------------------------
| ContentController::editText / editImage / editGallery — Read-Pfade
|--------------------------------------------------------------------------
*/

it('ContentController::editText — Fremder darf JSON-Daten fremder Texts NICHT sehen', function () {
    /** @var TestCase $this */
    ['stranger' => $stranger, 'entry' => $entry] = readerVsStrangerSetup();
    $text = Text::factory()->create();
    attachToEntry(Text::class, $text->id, $entry);

    $this->actingAs($stranger);

    $this->get('/edit/'.$text->id.'/text')->assertStatus(403);
});

it('ContentController::editImage — Fremder darf JSON-Daten fremder Images NICHT sehen', function () {
    /** @var TestCase $this */
    ['stranger' => $stranger, 'entry' => $entry] = readerVsStrangerSetup();
    $gallery = Gallery::factory()->create();
    attachToEntry(Gallery::class, $gallery->id, $entry);
    $image = Image::factory()->create(['gallery_id' => $gallery->id]);

    $this->actingAs($stranger);

    $this->get('/edit/'.$image->id.'/image')->assertStatus(403);
});

it('ContentController::editGallery — Fremder darf JSON-Daten fremder Galleries NICHT sehen', function () {
    /** @var TestCase $this */
    ['stranger' => $stranger, 'entry' => $entry] = readerVsStrangerSetup();
    $gallery = Gallery::factory()->create();
    attachToEntry(Gallery::class, $gallery->id, $entry);

    $this->actingAs($stranger);

    $this->get(route('gallery.edit', ['id' => $gallery->id]))->assertStatus(403);
});

/*
|--------------------------------------------------------------------------
| ContentController — Comment-Pfade
|--------------------------------------------------------------------------
*/

it('ContentController::commentText — Fremder kriegt 403', function () {
    /** @var TestCase $this */
    ['stranger' => $stranger, 'entry' => $entry] = readerVsStrangerSetup();
    $text = Text::factory()->create();
    attachToEntry(Text::class, $text->id, $entry);

    $this->actingAs($stranger);

    $this->post(route('comment.text'), [
        'id' => $text->id,
        'comment' => 'Unerlaubter Kommentar',
    ])->assertStatus(403);
});

it('ContentController::setCommentStatusText — Fremder darf fremden Comment-Status NICHT setzen', function () {
    /** @var TestCase $this */
    ['owner' => $owner, 'stranger' => $stranger, 'project' => $project, 'entry' => $entry] = readerVsStrangerSetup();
    $text = Text::factory()->create();
    $pivot = attachToEntry(Text::class, $text->id, $entry);

    $comment = Comment::create([
        'user_id' => $owner->id,
        'project_id' => $project->id,
        'comment' => 'Owner-Kommentar',
        'status' => 0,
        'commentable_id' => $pivot->id,
        'commentable_type' => MediaContent::class,
    ]);

    $this->actingAs($stranger);

    $this->post(route('comment.text.status'), [
        'id' => $comment->id,
        'status' => 1,
    ])->assertStatus(403);
});

it('ContentController::updateCommentStatus — Fremder darf fremden Comment-Status NICHT via URL-Link setzen', function () {
    /** @var TestCase $this */
    ['owner' => $owner, 'stranger' => $stranger, 'project' => $project, 'entry' => $entry] = readerVsStrangerSetup();
    $text = Text::factory()->create();
    $pivot = attachToEntry(Text::class, $text->id, $entry);

    $comment = Comment::create([
        'user_id' => $owner->id,
        'project_id' => $project->id,
        'comment' => 'Owner-Kommentar',
        'status' => 0,
        'commentable_id' => $pivot->id,
        'commentable_type' => MediaContent::class,
    ]);

    $this->actingAs($stranger);

    $this->post(route('comment.update.status', ['id' => $comment->id, 'status' => 1]))
        ->assertStatus(403);
});

/*
|--------------------------------------------------------------------------
| AudiovisualController — Welle II.b
|--------------------------------------------------------------------------
*/

it('AudiovisualController::store — Reader (ohne globale edit-Permission) kommt nicht durch', function () {
    /** @var TestCase $this */
    ['stranger' => $stranger, 'entry' => $entry] = readerVsStrangerSetup();

    $this->actingAs($stranger);

    $this->post(route('save.audiovisual'), [
        'entryId' => $entry->id,
        'link' => 'https://youtube.com/watch?v=abc',
        'type' => 'video',
    ])->assertStatus(403);
});

it('AudiovisualController::commentAudiovisual — Fremder kriegt 403', function () {
    /** @var TestCase $this */
    ['stranger' => $stranger, 'entry' => $entry] = readerVsStrangerSetup();
    $av = Audiovisual::factory()->create();
    attachToEntry(Audiovisual::class, $av->id, $entry);

    $this->actingAs($stranger);

    $this->post(route('comment.audiovisual'), [
        'id' => $av->id,
        'comment' => 'Unerlaubter Kommentar',
    ])->assertStatus(403);
});

/*
|--------------------------------------------------------------------------
| Owner / Eingeladener Happy-Path (Sanity-Check, dass die Gates nicht
| zu eng sind)
|--------------------------------------------------------------------------
*/

it('ContentController::editText — Owner darf JSON-Daten seines eigenen Texts sehen', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Reader');
    $entry = makeEntry(makeChapter(makeProject($owner)));
    $text = Text::factory()->create();
    attachToEntry(Text::class, $text->id, $entry);

    $this->actingAs($owner);

    $response = $this->get('/edit/'.$text->id.'/text');

    expect($response->status())->toBeIn([200, 302]);
});

it('EntryController::commentEntry — Eingeladener mit comment-Permission darf', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Reader');
    /** @var User $invitee */
    $invitee = User::factory()->create();
    $invitee->assignRole('Reader');
    $entry = makeEntry(makeChapter(makeProject($owner)));

    $commentPermissionId = Permission::where('name', PermissionName::COMMENT->value)->value('id');
    ProjectUserPermission::create([
        'user_id' => $invitee->id,
        'project_id' => $entry->chapter->project->id,
        'permission_id' => $commentPermissionId,
    ]);

    $this->actingAs($invitee);

    $response = $this->post(route('comment.entry'), [
        'id' => $entry->id,
        'comment' => 'Eingeladener Kommentar',
    ]);

    expect($response->status())->toBeIn([200, 302]);
});
