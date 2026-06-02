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

use App\Data\TextData;
use App\Models\MediaContent;
use App\Models\Source;
use App\Models\Text;
use App\Models\User;
use App\Services\SourceService;
use App\Services\TextService;

/*
|--------------------------------------------------------------------------
| TextService
|--------------------------------------------------------------------------
|
| Deckt die drei Schreibpfade ab: create (Source-Lookup + Text +
| MediaContent-Attach), update (Source-Lookup + Text), destroy
| (Soft-Delete + Comment-/MediaContent-Detach).
*/

beforeEach(function () {
    $this->service = new TextService(new SourceService);
});

it('create legt einen Text mit Source-Refs und MediaContent-Eintrag an', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $entry = makeEntry($chapter);

    $data = new TextData(
        body: '<p>Test-Body</p>',
        originName: 'Test-Origin',
        copyrightName: 'Test-Copyright',
    );

    $text = $this->service->create($data, $entry->id);

    expect($text->id)->toBeInt();
    expect($text->originText->name)->toBe('Test-Origin');
    expect($text->copyrightText->name)->toBe('Test-Copyright');

    $media = MediaContent::where('media_content_id', $text->id)
        ->where('media_contentable_id', $entry->id)
        ->where('media_contentable_type', Text::class)
        ->first();
    expect($media)->not->toBeNull();
});

it('create filtert script-Tags aus dem Body', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $entry = makeEntry($chapter);

    $data = new TextData(
        body: '<p>Hallo</p><script>alert(1)</script>',
        originName: 'O',
        copyrightName: 'C',
    );

    $text = $this->service->create($data, $entry->id);

    expect($text->text)->not->toContain('<script>');
    expect($text->text)->not->toContain('</script>');
    expect($text->text)->toContain('Hallo');
});

it('update aktualisiert Body, Source-IDs und is_translated', function () {
    $text = makeText();
    $originalOriginId = $text->origin;

    $data = new TextData(
        body: '<p>Neuer Body</p>',
        originName: 'Neue Quelle',
        copyrightName: 'Neues Copyright',
        isTranslated: true,
    );

    $updated = $this->service->update($text, $data);

    $updated->refresh();

    expect($updated->text)->toContain('Neuer Body');
    expect($updated->originText->name)->toBe('Neue Quelle');
    expect($updated->copyrightText->name)->toBe('Neues Copyright');
    expect($updated->is_translated)->toBeTrue();
    expect($updated->origin)->not->toBe($originalOriginId);
});

it('update wiederverwendet bestehende Source-Zeilen statt neuer Duplikate', function () {
    $existing = Source::factory()->origin()->create(['name' => 'Bestehende Quelle']);
    $text = makeText();

    $data = new TextData(
        body: '<p>X</p>',
        originName: 'Bestehende Quelle',
        copyrightName: 'C',
    );

    $this->service->update($text, $data);

    $text->refresh();

    expect($text->origin)->toBe($existing->id);
    expect(Source::where('name', 'Bestehende Quelle')->count())->toBe(1);
});

it('destroy soft-deleted Text plus zugehörige MediaContent', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $entry = makeEntry($chapter);

    $data = new TextData(body: '<p>X</p>', originName: 'O', copyrightName: 'C');
    $text = $this->service->create($data, $entry->id);

    $this->service->destroy($text);

    expect(Text::find($text->id))->toBeNull();
    expect(Text::withTrashed()->find($text->id))->not->toBeNull();
});
