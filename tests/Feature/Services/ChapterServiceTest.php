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

use App\Data\ChapterData;
use App\Models\User;
use App\Services\ChapterService;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| ChapterService
|--------------------------------------------------------------------------
|
| Deckt die zwei Schreibpfade des Service ab:
|   - create() mit Position-Calculation (max+1)
|   - update() mit Translation-Verzweigung
*/

it('create legt das erste Chapter mit position 1 an', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);

    $service = new ChapterService;

    $chapter = $service->create(
        new ChapterData(name: 'Erstes Kapitel'),
        $project->id,
    );

    expect($chapter->name)->toBe('Erstes Kapitel');
    expect($chapter->position)->toBe(1);
    expect($chapter->project_id)->toBe($project->id);
});

it('create setzt position auf max+1, wenn das Project schon Kapitel hat', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    makeChapter($project, ['position' => 5]);

    $service = new ChapterService;

    $next = $service->create(
        new ChapterData(name: 'Sechstes Kapitel'),
        $project->id,
    );

    expect($next->position)->toBe(6);
});

it('update schreibt name/subtitle/description direkt, wenn isTranslation=false', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $chapter = makeChapter($project, ['name' => 'Original']);

    $service = new ChapterService;

    $service->update($chapter, new ChapterData(
        name: 'Neuer Titel',
        subtitle: 'Neuer Untertitel',
        description: 'Neue Beschreibung',
        isTranslation: false,
        isTranslated: false,
    ));

    $chapter->refresh();

    expect($chapter->name)->toBe('Neuer Titel');
    expect($chapter->subtitle)->toBe('Neuer Untertitel');
    expect($chapter->description)->toBe('Neue Beschreibung');
    expect($chapter->is_translated)->toBeFalse();
});

it('update schreibt en-Übersetzungen, wenn isTranslation=true, ohne DE zu überschreiben', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $chapter = makeChapter($project, ['name' => 'DE-Original']);

    $service = new ChapterService;

    $service->update($chapter, new ChapterData(
        name: 'EN-Translation',
        subtitle: 'EN-Subtitle',
        description: 'EN-Description',
        isTranslation: true,
        isTranslated: true,
    ));

    $chapter->refresh();

    expect($chapter->getTranslation('name', 'en'))->toBe('EN-Translation');
    expect($chapter->getTranslation('subtitle', 'en'))->toBe('EN-Subtitle');
    expect($chapter->getTranslation('description', 'en'))->toBe('EN-Description');
    expect($chapter->getTranslation('name', 'de'))->toBe('DE-Original');
    expect($chapter->is_translated)->toBeTrue();
});

it('update überspringt die description-Übersetzung beim Sentinel "undefined"', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $chapter = makeChapter($project, ['description' => 'Original-Beschreibung']);
    // Setze eine vorhandene EN-Description, die nicht überschrieben werden darf.
    $chapter->setTranslation('description', 'en', 'EN-Description bereits da');
    $chapter->save();

    $service = new ChapterService;

    $service->update($chapter, new ChapterData(
        name: 'EN-Name',
        subtitle: 'EN-Subtitle',
        description: 'undefined',
        isTranslation: true,
        isTranslated: true,
    ));

    $chapter->refresh();

    expect($chapter->getTranslation('description', 'en'))->toBe('EN-Description bereits da');
});
