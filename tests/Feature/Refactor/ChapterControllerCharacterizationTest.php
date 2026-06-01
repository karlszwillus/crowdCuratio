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

use App\Models\Chapter;
use App\Models\Entry;
use App\Models\User;
use App\Support\PermissionName;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| ChapterController-Charakterisierung
|--------------------------------------------------------------------------
|
| Fixiert das beobachtbare Verhalten des ChapterController vor der
| Service-Layer-Extraktion (ChapterService, ContentReorderService,
| ChapterData DTO). Im Gegensatz zum ProjectController-Pilot ist der
| ChapterController bereits deutlich aufgeräumt — FormRequests
| arbeiten, kein mapData(), kein UploadTrait. Was bleibt:
|
|   - store():            Position-Calculation (max+1) im Controller
|   - update():           Verzweigung translationChapter ja/nein
|   - saveDragAndDrop():  switch-case über chapter/entry/content
|                         plus Auth-Gate per ProjectPolicy::update
|
| Comment-Pfade (commentChapter, getChapterComment, saveComment) und
| setStatus (latenter Bug — ruft CommentTrait::status() statt
| Chapter-Status) wandern in einen späteren Block.
*/

beforeEach(function () {
    foreach (PermissionName::all() as $permissionName) {
        Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
    }

    Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web'])
        ->syncPermissions(Permission::all());

    Role::firstOrCreate(['name' => 'Reader', 'guard_name' => 'web'])
        ->syncPermissions(['view']);
});

it('store legt das erste Chapter mit position 1 an', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);

    $response = $this->post(route('chapters.store'), [
        'projectId' => $project->id,
        'chapterTitle' => 'Erstes Kapitel',
        'chapterSubtitle' => null,
        'chapterDescription' => null,
    ]);

    $response->assertRedirect(route('projects.edit', $project->id));

    $chapter = Chapter::where('project_id', $project->id)->first();
    expect($chapter)->not->toBeNull();
    expect($chapter->name)->toBe('Erstes Kapitel');
    expect($chapter->position)->toBe(1);
});

it('store erhöht die position auf max+1, wenn das Project schon Kapitel hat', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);
    makeChapter($project, ['position' => 7]);

    $this->post(route('chapters.store'), [
        'projectId' => $project->id,
        'chapterTitle' => 'Nächstes Kapitel',
    ]);

    $latest = Chapter::where('project_id', $project->id)
        ->orderByDesc('position')
        ->first();

    expect($latest->position)->toBe(8);
});

it('update setzt name/subtitle/description direkt, wenn translationChapter nicht gesetzt', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);
    $chapter = makeChapter($project, ['name' => 'Original']);

    $this->patch(route('chapters.update', $chapter), [
        'chapterTitle' => 'Neuer Titel',
        'chapterSubtitle' => 'Neuer Untertitel',
        'chapterDescription' => 'Neue Beschreibung',
    ]);

    $chapter->refresh();

    expect($chapter->name)->toBe('Neuer Titel');
    expect($chapter->subtitle)->toBe('Neuer Untertitel');
    expect($chapter->description)->toBe('Neue Beschreibung');
});

it('update schreibt en-Übersetzungen, wenn translationChapter=true', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);
    $chapter = makeChapter($project, ['name' => 'Original DE']);

    $this->patch(route('chapters.update', $chapter), [
        'chapterTitle' => 'Original EN',
        'chapterSubtitle' => 'Subtitle EN',
        'chapterDescription' => 'Description EN',
        'translationChapter' => true,
        'isTranslated' => true,
    ]);

    $chapter->refresh();

    expect($chapter->getTranslation('name', 'en'))->toBe('Original EN');
    expect($chapter->getTranslation('subtitle', 'en'))->toBe('Subtitle EN');
    expect($chapter->is_translated)->toBeTrue();
    // DE-Werte bleiben unverändert
    expect($chapter->getTranslation('name', 'de'))->toBe('Original DE');
});

it('saveDragAndDrop ordnet Chapter-Positionen neu, wenn element=chapter', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);
    $first = makeChapter($project, ['name' => 'Erstes', 'position' => 1]);
    $second = makeChapter($project, ['name' => 'Zweites', 'position' => 2]);

    $this->post(route('chapter.drag'), [
        'data' => [
            'element' => 'chapter',
            'data' => [$second->id, $first->id], // Reihenfolge umgedreht
        ],
    ]);

    $first->refresh();
    $second->refresh();

    expect($second->position)->toBe(1);
    expect($first->position)->toBe(2);
});

it('saveDragAndDrop verschiebt Entries zwischen Kapiteln, wenn element=entry', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);
    $sourceChapter = makeChapter($project, ['position' => 1]);
    $targetChapter = makeChapter($project, ['position' => 2]);
    $entry = makeEntry($sourceChapter, ['position' => 1]);

    $this->post(route('chapter.drag'), [
        'data' => [
            'element' => 'entry',
            'chapter' => $targetChapter->id,
            'data' => [$entry->id],
        ],
    ]);

    $entry->refresh();

    expect($entry->chapter_id)->toBe($targetChapter->id);
    expect($entry->position)->toBe(1);
});

it('saveDragAndDrop lehnt drag auf fremdes Project mit 403 ab', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    /** @var User $intruder */
    $intruder = User::factory()->create();
    $intruder->assignRole('Reader');

    $project = makeProject($owner);
    $chapter = makeChapter($project, ['position' => 1]);

    $this->actingAs($intruder);

    $response = $this->post(route('chapter.drag'), [
        'data' => [
            'element' => 'chapter',
            'data' => [$chapter->id],
        ],
    ]);

    $response->assertStatus(403);
});

it('destroy löscht das Chapter und redirected auf projects.edit', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);
    $chapter = makeChapter($project);

    $response = $this->delete(route('chapters.destroy', $chapter).'?project='.$project->id);

    $response->assertRedirect('projects/'.$project->id.'/edit');
    expect(Chapter::find($chapter->id))->toBeNull();
});
