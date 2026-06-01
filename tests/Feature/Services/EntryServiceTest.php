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

use App\Data\EntryData;
use App\Models\User;
use App\Services\EntryService;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| EntryService
|--------------------------------------------------------------------------
|
| Deckt die zwei Schreibpfade des Service ab — strukturell parallel
| zum ChapterServiceTest. create() mit Position-Calculation und
| update() mit Translation-Verzweigung inklusive
| 'undefined'-Sentinel.
*/

it('create legt den ersten Entry mit position 1 an', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $chapter = makeChapter($project);

    $service = new EntryService;

    $entry = $service->create(
        new EntryData(name: 'Erster Entry'),
        $chapter->id,
    );

    expect($entry->name)->toBe('Erster Entry');
    expect($entry->position)->toBe(1);
    expect($entry->chapter_id)->toBe($chapter->id);
});

it('create setzt position auf max+1, wenn das Chapter schon Entries hat', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $chapter = makeChapter($project);
    makeEntry($chapter, ['position' => 9]);

    $service = new EntryService;

    $next = $service->create(
        new EntryData(name: 'Zehnter Entry'),
        $chapter->id,
    );

    expect($next->position)->toBe(10);
});

it('update schreibt name/subtitle/description direkt, wenn isTranslation=false', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $entry = makeEntry($chapter, ['name' => 'Original']);

    $service = new EntryService;

    $service->update($entry, new EntryData(
        name: 'Neuer Entry',
        subtitle: 'Neuer Untertitel',
        description: 'Neue Beschreibung',
        isTranslation: false,
        isTranslated: false,
    ));

    $entry->refresh();

    expect($entry->name)->toBe('Neuer Entry');
    expect($entry->subtitle)->toBe('Neuer Untertitel');
    expect($entry->description)->toBe('Neue Beschreibung');
    expect($entry->is_translated)->toBeFalse();
});

it('update schreibt en-Übersetzungen, wenn isTranslation=true, ohne DE zu überschreiben', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $entry = makeEntry($chapter, ['name' => 'DE-Original']);

    $service = new EntryService;

    $service->update($entry, new EntryData(
        name: 'EN-Translation',
        subtitle: 'EN-Subtitle',
        description: 'EN-Description',
        isTranslation: true,
        isTranslated: true,
    ));

    $entry->refresh();

    expect($entry->getTranslation('name', 'en'))->toBe('EN-Translation');
    expect($entry->getTranslation('subtitle', 'en'))->toBe('EN-Subtitle');
    expect($entry->getTranslation('description', 'en'))->toBe('EN-Description');
    expect($entry->getTranslation('name', 'de'))->toBe('DE-Original');
    expect($entry->is_translated)->toBeTrue();
});

it('update überspringt die description-Übersetzung beim Sentinel "undefined"', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $entry = makeEntry($chapter);
    $entry->setTranslation('description', 'en', 'EN-Description bereits da');
    $entry->save();

    $service = new EntryService;

    $service->update($entry, new EntryData(
        name: 'EN-Name',
        subtitle: 'EN-Subtitle',
        description: 'undefined',
        isTranslation: true,
        isTranslated: true,
    ));

    $entry->refresh();

    expect($entry->getTranslation('description', 'en'))->toBe('EN-Description bereits da');
});
