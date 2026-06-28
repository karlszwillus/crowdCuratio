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
use App\Models\MediaContent;
use App\Models\Text;
use App\Models\User;
use App\Services\ContentReorderService;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| ContentReorderService
|--------------------------------------------------------------------------
|
| Deckt die drei Reorder-Methoden plus die Project-Auflösung ab.
| MediaContent-Tests werden bei der EntryController-Extraktion
| nachgezogen — die Modell-Setup-Hilfen dafür leben noch nicht in
| Pest.php.
*/

it('reorderChapters schreibt position aufsteigend ab 1', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $first = makeChapter($project, ['position' => 1]);
    $second = makeChapter($project, ['position' => 2]);
    $third = makeChapter($project, ['position' => 3]);

    $service = new ContentReorderService;

    // Reihenfolge umkehren
    $service->reorderChapters([$third->id, $second->id, $first->id]);

    $first->refresh();
    $second->refresh();
    $third->refresh();

    expect($third->position)->toBe(1);
    expect($second->position)->toBe(2);
    expect($first->position)->toBe(3);
});

it('reorderEntries verschiebt einen Entry in ein anderes Chapter', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $sourceChapter = makeChapter($project);
    $targetChapter = makeChapter($project);
    $entry = makeEntry($sourceChapter, ['position' => 1]);

    $service = new ContentReorderService;

    $service->reorderEntries($targetChapter->id, [$entry->id]);

    $entry->refresh();

    expect($entry->chapter_id)->toBe($targetChapter->id);
    expect($entry->position)->toBe(1);
});

it('reorderEntries überspringt null-Einträge in der ID-Liste', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $entry = makeEntry($chapter, ['position' => 5]);

    $service = new ContentReorderService;

    $service->reorderEntries($chapter->id, [null, $entry->id]);

    $entry->refresh();

    expect($entry->position)->toBe(2); // Index 1 + 1
});

it('resolveProject liefert für element=chapter das Project des ersten Chapters', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $chapter = makeChapter($project);

    $service = new ContentReorderService;

    $resolved = $service->resolveProject('chapter', [], [$chapter->id]);

    expect($resolved)->not->toBeNull();
    expect($resolved->id)->toBe($project->id);
});

it('resolveProject liefert für element=entry das Project über payload.chapter', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $chapter = makeChapter($project);

    $service = new ContentReorderService;

    $resolved = $service->resolveProject(
        'entry',
        ['chapter' => $chapter->id],
        [],
    );

    expect($resolved)->not->toBeNull();
    expect($resolved->id)->toBe($project->id);
});

it('resolveProject liefert null bei unbekanntem Element-Typ', function () {
    /** @var TestCase $this */
    $service = new ContentReorderService;

    expect($service->resolveProject('unknown', [], []))->toBeNull();
    expect($service->resolveProject(null, [], []))->toBeNull();
});

it('resolveProject liefert null bei nicht auflösbarer ID', function () {
    /** @var TestCase $this */
    $service = new ContentReorderService;

    expect($service->resolveProject('chapter', [], [999999]))->toBeNull();
    expect($service->resolveProject('entry', ['chapter' => 999999], []))->toBeNull();
});

it('reorderChapters überspringt null-Einträge in der ID-Liste', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $chapter = makeChapter($project, ['position' => 5]);

    $service = new ContentReorderService;

    $service->reorderChapters([null, $chapter->id]);

    $chapter->refresh();
    expect($chapter->position)->toBe(2); // Index 1 + 1
});

it('reorderContent setzt nur die Position, wenn kein Ziel-Entry übergeben wird', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $entry = makeEntry($chapter);
    $text = makeText();

    $first = MediaContent::create([
        'content_id' => $text->id,
        'content_type' => Text::class,
        'parent_id' => $entry->id,
        'parent_type' => Entry::class,
        'position' => 1,
    ]);
    $second = MediaContent::create([
        'content_id' => $text->id,
        'content_type' => Text::class,
        'parent_id' => $entry->id,
        'parent_type' => Entry::class,
        'position' => 2,
    ]);

    $service = new ContentReorderService;

    // Reihenfolge umkehren, gleicher Entry
    $service->reorderContent(null, [$second->id, $first->id]);

    $first->refresh();
    $second->refresh();

    expect($second->position)->toBe(1);
    expect($first->position)->toBe(2);
    expect($first->parent_id)->toBe($entry->id);
    expect($second->parent_id)->toBe($entry->id);
});

it('reorderContent verschiebt Content in einen anderen Entry und schreibt die Position', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $sourceEntry = makeEntry($chapter);
    $targetEntry = makeEntry($chapter);
    $text = makeText();

    $content = MediaContent::create([
        'content_id' => $text->id,
        'content_type' => Text::class,
        'parent_id' => $sourceEntry->id,
        'parent_type' => Entry::class,
        'position' => 1,
    ]);

    $service = new ContentReorderService;

    $service->reorderContent($targetEntry->id, [$content->id]);

    $content->refresh();

    expect($content->parent_id)->toBe($targetEntry->id);
    expect($content->position)->toBe(1);
});

it('reorderContent überspringt null-Einträge in der ID-Liste', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $entry = makeEntry($chapter);
    $text = makeText();

    $content = MediaContent::create([
        'content_id' => $text->id,
        'content_type' => Text::class,
        'parent_id' => $entry->id,
        'parent_type' => Entry::class,
        'position' => 7,
    ]);

    $service = new ContentReorderService;

    $service->reorderContent(null, [null, $content->id]);

    $content->refresh();
    expect($content->position)->toBe(2); // Index 1 + 1
});

it('resolveProject liefert für element=content das Project über payload.entry', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $entry = makeEntry($chapter);

    $service = new ContentReorderService;

    $resolved = $service->resolveProject(
        'content',
        ['entry' => $entry->id],
        [],
    );

    expect($resolved)->not->toBeNull();
    expect($resolved->id)->toBe($project->id);
});

it('resolveProject liefert für element=content null bei unauflösbarer Entry-ID', function () {
    /** @var TestCase $this */
    $service = new ContentReorderService;

    expect($service->resolveProject('content', ['entry' => 999999], []))->toBeNull();
});
