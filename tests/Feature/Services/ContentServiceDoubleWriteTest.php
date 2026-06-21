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

use App\Data\AudiovisualData;
use App\Data\GalleryData;
use App\Data\TextData;
use App\Models\Audiovisual;
use App\Models\Entry;
use App\Models\Gallery;
use App\Models\MediaContent;
use App\Models\Text;
use App\Models\User;
use App\Services\AudiovisualService;
use App\Services\GalleryService;
use App\Services\TextService;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Content-Services — Pivot-Schreibpfade (E.7b Sub-Welle 2d → 4d, ADR-0022)
|--------------------------------------------------------------------------
|
| Diese Datei hieß ursprünglich „DoubleWriteTest", weil die
| attachToEntry-Aufrufer in Welle 2d alte media_contentable-Spalten
| und neue content-/parent-Spalten parallel befüllt haben. Mit
| Welle 4d ist die Doppelschreibung beendet; die Tests pinnen jetzt
| nur noch die neuen Spalten. Datei-Umbenennung folgt im nächsten
| Aufräumblock.
|
| Der Gallery-Test bestätigt zusätzlich, dass content_type sauber
| Gallery::class trägt — der historische Image::class-Schiefstand
| ist mit der alten Spalte verschwunden.
*/

it('TextService::create schreibt Pivot-Row mit content_*/parent_*', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $entry = makeEntry(makeChapter(makeProject($owner)));

    $service = app(TextService::class);
    $text = $service->create(
        new TextData(
            body: 'Hallo Welt',
            originName: 'Karls Quelle',
            copyrightName: 'CC-BY',
        ),
        $entry->id,
    );

    /** @var MediaContent $pivot */
    $pivot = MediaContent::where('content_id', $text->id)
        ->where('content_type', Text::class)
        ->firstOrFail();
    expect($pivot->content_type)->toBe(Text::class);
    expect((int) $pivot->content_id)->toBe($text->id);
    expect($pivot->parent_type)->toBe(Entry::class);
    expect((int) $pivot->parent_id)->toBe($entry->id);
});

it('AudiovisualService::create schreibt Pivot-Row mit content_*/parent_*', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $entry = makeEntry(makeChapter(makeProject($owner)));

    $service = app(AudiovisualService::class);
    $av = $service->create(
        new AudiovisualData(
            link: 'https://example.test/audio.mp3',
            source: null,
            copyright: null,
            type: 'audio',
            isTranslated: false,
        ),
        $entry->id,
    );

    /** @var MediaContent $pivot */
    $pivot = MediaContent::where('content_id', $av->id)
        ->where('content_type', Audiovisual::class)
        ->firstOrFail();
    expect($pivot->content_type)->toBe(Audiovisual::class);
    expect((int) $pivot->content_id)->toBe($av->id);
    expect($pivot->parent_type)->toBe(Entry::class);
    expect((int) $pivot->parent_id)->toBe($entry->id);
});

it('GalleryService::create schreibt Pivot-Row mit content_type=Gallery::class', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $entry = makeEntry(makeChapter(makeProject($owner)));

    $service = app(GalleryService::class);
    $gallery = $service->create(
        new GalleryData(
            title: 'Karls Galerie',
            subtitle: null,
            description: null,
            isTranslation: false,
            isTranslated: false,
        ),
        $entry->id,
    );

    /** @var MediaContent $pivot */
    $pivot = MediaContent::where('content_id', $gallery->id)
        ->where('content_type', Gallery::class)
        ->firstOrFail();

    // E.7b 4d (ADR-0022): historischer Schiefstand (alte Tag-Spalte
    // hatte Image::class für Galleries) verschwindet mit der alten
    // Spalte. Neue content_type-Spalte ist sauber.
    expect($pivot->content_type)->toBe(Gallery::class);
    expect((int) $pivot->content_id)->toBe($gallery->id);
    expect($pivot->parent_type)->toBe(Entry::class);
    expect((int) $pivot->parent_id)->toBe($entry->id);
});
