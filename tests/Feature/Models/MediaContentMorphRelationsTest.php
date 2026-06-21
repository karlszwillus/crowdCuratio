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
use App\Models\Entry;
use App\Models\Gallery;
use App\Models\Image;
use App\Models\MediaContent;
use App\Models\Text;
use App\Models\User;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| MediaContent — Morph-Relations (E.7b Sub-Welle 2b, ADR-0022)
|--------------------------------------------------------------------------
|
| Pinnt die neuen sauberen morphTo-Beziehungen content() und parent()
| auf MediaContent. Sie lesen aus den in Sub-Welle 2a angelegten
| Spalten content_id/content_type bzw. parent_id/parent_type und
| liefern das konkrete Content- bzw. Parent-Modell zurück.
|
| Die alten Beziehungen (text/image/gallery/audiovisual/entry) auf
| die media_content_id-Spalte bleiben während der Übergangswelle
| funktionsfähig — Konsumenten werden in Sub-Welle 2c/2d
| umgestellt, Cleanup in Sub-Welle 4.
*/

it('content() liefert den Text-Datensatz, wenn content_type = Text::class', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $entry = makeEntry(makeChapter(makeProject($owner)));
    $text = Text::factory()->create();

    $row = MediaContent::create([
        'media_content_id' => $text->id,
        'media_contentable_id' => $entry->id,
        'media_contentable_type' => Text::class,
        'content_id' => $text->id,
        'content_type' => Text::class,
        'parent_id' => $entry->id,
        'parent_type' => Entry::class,
        'position' => 0,
    ]);

    expect($row->content)->toBeInstanceOf(Text::class);
    /** @var Text $content */
    $content = $row->content;
    expect($content->id)->toBe($text->id);
});

it('content() liefert die Gallery, wenn content_type = Gallery::class (historischer Schiefstand jetzt sauber)', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $entry = makeEntry(makeChapter(makeProject($owner)));
    $gallery = Gallery::factory()->create();

    // Alte Tag-Spalte hatte historisch Image::class — neue
    // content_type-Spalte hat den korrekten Gallery::class-Wert
    // nach Backfill bzw. Service-Doppelschreibung.
    $row = MediaContent::create([
        'media_content_id' => $gallery->id,
        'media_contentable_id' => $entry->id,
        'media_contentable_type' => Image::class,
        'content_id' => $gallery->id,
        'content_type' => Gallery::class,
        'parent_id' => $entry->id,
        'parent_type' => Entry::class,
        'position' => 0,
    ]);

    expect($row->content)->toBeInstanceOf(Gallery::class);
    /** @var Gallery $content */
    $content = $row->content;
    expect($content->id)->toBe($gallery->id);
});

it('content() liefert den Audiovisual-Datensatz, wenn content_type = Audiovisual::class', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $entry = makeEntry(makeChapter(makeProject($owner)));
    $av = Audiovisual::factory()->create();

    $row = MediaContent::create([
        'media_content_id' => $av->id,
        'media_contentable_id' => $entry->id,
        'media_contentable_type' => Audiovisual::class,
        'content_id' => $av->id,
        'content_type' => Audiovisual::class,
        'parent_id' => $entry->id,
        'parent_type' => Entry::class,
        'position' => 0,
    ]);

    expect($row->content)->toBeInstanceOf(Audiovisual::class);
    /** @var Audiovisual $content */
    $content = $row->content;
    expect($content->id)->toBe($av->id);
});

it('parent() liefert den Entry-Datensatz', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $entry = makeEntry(makeChapter(makeProject($owner)));
    $text = Text::factory()->create();

    $row = MediaContent::create([
        'media_content_id' => $text->id,
        'media_contentable_id' => $entry->id,
        'media_contentable_type' => Text::class,
        'content_id' => $text->id,
        'content_type' => Text::class,
        'parent_id' => $entry->id,
        'parent_type' => Entry::class,
        'position' => 0,
    ]);

    expect($row->parent)->toBeInstanceOf(Entry::class);
    /** @var Entry $parent */
    $parent = $row->parent;
    expect($parent->id)->toBe($entry->id);
});
