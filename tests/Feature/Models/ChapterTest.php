<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

See LICENSE.
 */

declare(strict_types=1);

use App\Models\Chapter;
use App\Models\Image;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Chapter-Model · Kapitel-Titelbild
|--------------------------------------------------------------------------
|
| Q4-Etappe 6 · G6-5: Chapter trägt einen FK auf Image für das
| Kapitel-Titelbild. Bei Soft-Delete des Bildes filtert die
| coverImage-Relation stumm heraus (SoftDeletes-Filter greift auf
| BelongsTo); bei Hard-Delete setzt der FK dank ON DELETE SET NULL
| die Spalte zurück.
*/

it('kann ein Kapitelbild via coverImage-Relation lesen', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $image = makeImage();

    $chapter->cover_image_id = $image->id;
    $chapter->save();

    $chapter->refresh();

    expect($chapter->cover_image_id)->toBe($image->id);
    expect($chapter->coverImage)->not->toBeNull();
    expect($chapter->coverImage->id)->toBe($image->id);
});

it('coverImage liefert null, wenn das Bild soft-deleted wurde', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $image = makeImage();

    $chapter->cover_image_id = $image->id;
    $chapter->save();

    $image->delete();

    // cover_image_id bleibt technisch stehen — der SoftDeletes-Filter
    // in der Relation blendet das Bild aber aus, sodass der Reader
    // die Karte wieder ohne Bildflaeche rendert.
    $chapter->refresh();
    expect($chapter->coverImage)->toBeNull();
});

it('cover_image_id wird via ON DELETE SET NULL geleert, wenn das Bild hart gelöscht wird', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $image = makeImage();

    $chapter->cover_image_id = $image->id;
    $chapter->save();

    // Hard-Delete umgeht den SoftDeletes-Trait und triggert die
    // FK-Constraint. Wir schreiben direkt in die DB, damit der
    // Trait nicht dazwischenfunkt.
    Image::where('id', $image->id)->forceDelete();

    $chapter->refresh();
    expect($chapter->cover_image_id)->toBeNull();
    expect($chapter->coverImage)->toBeNull();
});
