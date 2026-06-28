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
use App\Models\Comment;
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
| Pinnt die sauberen morphTo-Beziehungen content() und parent() auf
| MediaContent. Sie lesen aus den in Sub-Welle 2a angelegten Spalten
| content_id/content_type bzw. parent_id/parent_type und liefern das
| konkrete Content- bzw. Parent-Modell zurück. Die alten media_*-
| Spalten und tote Beziehungen sind in Welle 4e gedroppt bzw.
| in Welle 4c entfernt.
*/

it('content() liefert den Text-Datensatz, wenn content_type = Text::class', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $entry = makeEntry(makeChapter(makeProject($owner)));
    $text = Text::factory()->create();

    $row = MediaContent::create([
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

    // E.7b 4e (ADR-0022): alte Tag-Spalte gedroppt; content_type
    // führt sauber Gallery::class.
    $row = MediaContent::create([
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

it('image() liefert das verknüpfte Image-Modell über content_id', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $entry = makeEntry(makeChapter(makeProject($owner)));
    $image = makeImage();

    /** @var MediaContent $row */
    $row = MediaContent::create([
        'content_id' => $image->id,
        'content_type' => Image::class,
        'parent_id' => $entry->id,
        'parent_type' => Entry::class,
        'position' => 0,
    ]);

    expect($row->image)->toBeInstanceOf(Image::class);
    expect($row->image->id)->toBe($image->id);
});

it('text() liefert das verknüpfte Text-Modell über content_id', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $entry = makeEntry(makeChapter(makeProject($owner)));
    $text = Text::factory()->create();

    /** @var MediaContent $row */
    $row = MediaContent::create([
        'content_id' => $text->id,
        'content_type' => Text::class,
        'parent_id' => $entry->id,
        'parent_type' => Entry::class,
        'position' => 0,
    ]);

    expect($row->text)->toBeInstanceOf(Text::class);
    expect($row->text->id)->toBe($text->id);
});

it('gallery() liefert das verknüpfte Gallery-Modell über content_id', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $entry = makeEntry(makeChapter(makeProject($owner)));
    $gallery = makeGallery();

    /** @var MediaContent $row */
    $row = MediaContent::create([
        'content_id' => $gallery->id,
        'content_type' => Gallery::class,
        'parent_id' => $entry->id,
        'parent_type' => Entry::class,
        'position' => 0,
    ]);

    expect($row->gallery)->toBeInstanceOf(Gallery::class);
    expect($row->gallery->id)->toBe($gallery->id);
});

it('audiovisual() liefert das verknüpfte Audiovisual-Modell über content_id', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $entry = makeEntry(makeChapter(makeProject($owner)));
    $av = makeAudiovisual();

    /** @var MediaContent $row */
    $row = MediaContent::create([
        'content_id' => $av->id,
        'content_type' => Audiovisual::class,
        'parent_id' => $entry->id,
        'parent_type' => Entry::class,
        'position' => 0,
    ]);

    expect($row->audiovisual)->toBeInstanceOf(Audiovisual::class);
    expect($row->audiovisual->id)->toBe($av->id);
});

it('entry() liefert via belongsToMany alle Entries, deren parent_id auf diesen MediaContent zeigt', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $entry = makeEntry(makeChapter(makeProject($owner)));
    $text = Text::factory()->create();

    /** @var MediaContent $row */
    $row = MediaContent::create([
        'content_id' => $text->id,
        'content_type' => Text::class,
        'parent_id' => $entry->id,
        'parent_type' => Entry::class,
        'position' => 0,
    ]);

    expect($row->entry()->get())->toHaveCount(1);
    expect($row->entry()->first()->id)->toBe($entry->id);
});

it('löscht beim Delete kaskadierend Text-/Gallery-/Comment-Kinder mit', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $entry = makeEntry(makeChapter(makeProject($owner)));
    $text = Text::factory()->create();

    /** @var MediaContent $row */
    $row = MediaContent::create([
        'content_id' => $text->id,
        'content_type' => Text::class,
        'parent_id' => $entry->id,
        'parent_type' => Entry::class,
        'position' => 0,
    ]);

    // Ein Top-Level-Comment auf das MediaContent
    $comment = new Comment;
    $comment->project_id = $entry->chapter->project_id;
    $comment->user_id = $owner->id;
    $comment->setTranslation('comment', 'de', 'Auf MediaContent');
    $comment->commentable_type = MediaContent::class;
    $comment->commentable_id = $row->id;
    $comment->parent_id = null;
    $comment->save();

    expect(Text::find($text->id))->not->toBeNull();
    expect(Comment::find($comment->id))->not->toBeNull();

    $row->delete();

    expect(Text::find($text->id))->toBeNull();
    expect(Comment::find($comment->id))->toBeNull();
});
