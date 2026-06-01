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

use App\Models\Entry;
use App\Models\User;
use App\Support\PermissionName;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| EntryController-Charakterisierung
|--------------------------------------------------------------------------
|
| Fixiert das beobachtbare Verhalten des EntryController vor der
| Service-Layer-Extraktion. Der Controller ist strukturell parallel
| zum ChapterController — Position-Calculation in store(),
| Translation-Verzweigung in update().
|
| Comment-Pfade (commentEntry, getEntryComment, saveCommentEntry)
| und setStatusEntry (latenter CommentTrait-Bug, derselbe wie bei
| setStatusProject/setStatus) sind bewusst nicht abgedeckt — sie
| wandern in den späteren Comment-Refactor-Block.
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

it('store legt den ersten Entry mit position 1 an', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);
    $chapter = makeChapter($project);

    $response = $this->post(route('entries.store'), [
        'chapterId' => $chapter->id,
        'entryTitle' => 'Erster Entry',
    ]);

    $response->assertStatus(302);

    $entry = Entry::where('chapter_id', $chapter->id)->first();
    expect($entry)->not->toBeNull();
    expect($entry->name)->toBe('Erster Entry');
    expect($entry->position)->toBe(1);
});

it('store erhöht position auf max+1, wenn das Chapter schon Entries hat', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);
    $chapter = makeChapter($project);
    makeEntry($chapter, ['position' => 4]);

    $this->post(route('entries.store'), [
        'chapterId' => $chapter->id,
        'entryTitle' => 'Nächster Entry',
    ]);

    $latest = Entry::where('chapter_id', $chapter->id)
        ->orderByDesc('position')
        ->first();

    expect($latest->position)->toBe(5);
});

it('update setzt name/subtitle/description direkt, wenn translationEntry nicht gesetzt', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $entry = makeEntry($chapter, ['name' => 'Original']);

    $this->patch(route('entries.update', $entry), [
        'entryTitle' => 'Neuer Entry-Titel',
        'entrySubtitle' => 'Neuer Untertitel',
        'entryDescription' => 'Neue Beschreibung',
    ]);

    $entry->refresh();

    expect($entry->name)->toBe('Neuer Entry-Titel');
    expect($entry->subtitle)->toBe('Neuer Untertitel');
    expect($entry->description)->toBe('Neue Beschreibung');
});

it('update schreibt en-Übersetzungen, wenn translationEntry=true', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $entry = makeEntry($chapter, ['name' => 'Entry DE']);

    $this->patch(route('entries.update', $entry), [
        'entryTitle' => 'Entry EN',
        'entrySubtitle' => 'Sub EN',
        'entryDescription' => 'Desc EN',
        'translationEntry' => true,
        'isTranslated' => true,
    ]);

    $entry->refresh();

    expect($entry->getTranslation('name', 'en'))->toBe('Entry EN');
    expect($entry->getTranslation('subtitle', 'en'))->toBe('Sub EN');
    expect($entry->is_translated)->toBeTrue();
    expect($entry->getTranslation('name', 'de'))->toBe('Entry DE');
});

it('destroy löscht den Entry und redirected auf projects.edit', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $entry = makeEntry($chapter);

    $response = $this->delete(route('entries.destroy', $entry).'?project='.$project->id);

    $response->assertRedirect('projects/'.$project->id.'/edit');
    expect(Entry::find($entry->id))->toBeNull();
});
