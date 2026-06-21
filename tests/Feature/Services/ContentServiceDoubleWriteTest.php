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
use App\Models\Image;
use App\Models\MediaContent;
use App\Models\Text;
use App\Models\User;
use App\Services\AudiovisualService;
use App\Services\GalleryService;
use App\Services\TextService;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Content-Services — Doppelschreibung (E.7b Sub-Welle 2d, ADR-0022)
|--------------------------------------------------------------------------
|
| Pinnt, dass die drei attachToEntry-Aufrufer
| (TextService, GalleryService, AudiovisualService) während der
| Übergangswelle sowohl die alten media_contentable-Spalten als
| auch die neuen content- und parent-Spalten korrekt befüllen.
|
| Der Gallery-Test pinnt explizit den historischen Schiefstand-
| Fix: alte media_contentable_type bleibt Image::class
| (Konsumenten, die nach Image-Tags suchen, sehen das Pivot
| weiter), neue content_type ist Gallery::class.
*/

it('TextService::create schreibt alte UND neue Pivot-Spalten', function () {
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
    $pivot = MediaContent::where('media_content_id', $text->id)->firstOrFail();
    expect($pivot->media_contentable_type)->toBe(Text::class);
    expect((int) $pivot->media_contentable_id)->toBe($entry->id);
    expect($pivot->content_type)->toBe(Text::class);
    expect((int) $pivot->content_id)->toBe($text->id);
    expect($pivot->parent_type)->toBe(Entry::class);
    expect((int) $pivot->parent_id)->toBe($entry->id);
});

it('AudiovisualService::create schreibt alte UND neue Pivot-Spalten', function () {
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
    $pivot = MediaContent::where('media_content_id', $av->id)->firstOrFail();
    expect($pivot->media_contentable_type)->toBe(Audiovisual::class);
    expect($pivot->content_type)->toBe(Audiovisual::class);
    expect((int) $pivot->content_id)->toBe($av->id);
    expect($pivot->parent_type)->toBe(Entry::class);
    expect((int) $pivot->parent_id)->toBe($entry->id);
});

it('GalleryService::create schreibt alte media_contentable_type=Image::class UND neue content_type=Gallery::class', function () {
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
    $pivot = MediaContent::where('media_content_id', $gallery->id)->firstOrFail();

    // Historischer Schiefstand bleibt für Übergangswelle in alter Spalte:
    expect($pivot->media_contentable_type)->toBe(Image::class);

    // Neue Spalte trägt jetzt den korrekten Wert:
    expect($pivot->content_type)->toBe(Gallery::class);
    expect((int) $pivot->content_id)->toBe($gallery->id);
    expect($pivot->parent_type)->toBe(Entry::class);
    expect((int) $pivot->parent_id)->toBe($entry->id);
});
