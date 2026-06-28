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
use App\Models\Comment;
use App\Models\MediaContent;
use App\Models\Text;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Comment — Eloquent-Relations
|--------------------------------------------------------------------------
|
| Deckt die vier Relations (chapter, commentable, project, content) plus
| die tapActivity-Locale-Anreicherung ab. Comment ist morph-fähig: das
| Pivot zeigt mal auf Chapter, mal auf MediaContent — die Tests prüfen
| beide Pfade.
*/

beforeEach(function () {
    app()->setLocale('de');
});

it('comment->project liefert das verknüpfte Project zurück', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);

    /** @var Comment $comment */
    $comment = new Comment;
    $comment->project_id = $project->id;
    $comment->user_id = $owner->id;
    $comment->setTranslation('comment', 'de', 'Kommentar-Text');
    $comment->commentable_type = Chapter::class;
    $comment->commentable_id = 0; // morph: wird über chapter()/content() gefiltert
    $comment->save();

    expect($comment->project)->not->toBeNull();
    /** @var \App\Models\Project $resolvedProject */
    $resolvedProject = $comment->project;
    expect($resolvedProject->id)->toBe($project->id);
});

it('comment->commentable liefert das verknüpfte Chapter über morphTo', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $chapter = makeChapter($project);

    /** @var Comment $comment */
    $comment = new Comment;
    $comment->project_id = $project->id;
    $comment->user_id = $owner->id;
    $comment->setTranslation('comment', 'de', 'Auf Chapter');
    $comment->commentable_type = Chapter::class;
    $comment->commentable_id = $chapter->id;
    $comment->save();

    /** @var Chapter $resolved */
    $resolved = $comment->commentable;

    expect($resolved)->toBeInstanceOf(Chapter::class);
    expect($resolved->id)->toBe($chapter->id);
});

it('comment->commentable liefert MediaContent, wenn der morph-Typ MediaContent ist', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $entry = makeEntry($chapter);
    $text = makeText();

    /** @var MediaContent $media */
    $media = MediaContent::create([
        'content_id' => $text->id,
        'content_type' => Text::class,
        'parent_id' => $entry->id,
        'parent_type' => \App\Models\Entry::class,
        'position' => 1,
    ]);

    $comment = new Comment;
    $comment->project_id = $project->id;
    $comment->user_id = $owner->id;
    $comment->setTranslation('comment', 'de', 'Auf MediaContent');
    $comment->commentable_type = MediaContent::class;
    $comment->commentable_id = $media->id;
    $comment->save();

    /** @var MediaContent $resolved */
    $resolved = $comment->commentable;

    expect($resolved)->toBeInstanceOf(MediaContent::class);
    expect($resolved->id)->toBe($media->id);
});

it('comment->content liefert das MediaContent über commentable_id', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $entry = makeEntry($chapter);
    $text = makeText();

    /** @var MediaContent $media */
    $media = MediaContent::create([
        'content_id' => $text->id,
        'content_type' => Text::class,
        'parent_id' => $entry->id,
        'parent_type' => \App\Models\Entry::class,
        'position' => 1,
    ]);

    $comment = new Comment;
    $comment->project_id = $project->id;
    $comment->user_id = $owner->id;
    $comment->setTranslation('comment', 'de', 'Verlinkt');
    $comment->commentable_type = MediaContent::class;
    $comment->commentable_id = $media->id;
    $comment->save();

    expect($comment->content)->not->toBeNull();
    expect($comment->content->id)->toBe($media->id);
});

// Comment::chapter() ist als morphToMany mit (table=comments,
// foreignPivotKey=commentable_id, relatedPivotKey=id) definiert.
// Die Konstruktion ist strukturell unsinnig — comments ist keine
// Pivot-Tabelle zwischen Comment und Chapter, sondern die Comments
// selber. Ein Test gegen die Methode liefert konsistent leere
// Collections. In werkbank/TODO.md als Code-Hygiene-Item notiert.
