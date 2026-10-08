<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 */

use App\Models\DataFactBlock;
use App\Models\Gallery;
use App\Models\MediaContent;
use App\Models\QuoteBlock;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/*
|--------------------------------------------------------------------------
| CascadesToMediaContent — Soft-, Restore- und Force-Delete
|--------------------------------------------------------------------------
|
| Zusätzlich die Diskriminator-Falle der MediaContent-Relationen: Text
| und Galerie mit derselben ID.
*/

function pivotsFor(Model $block)
{
    return MediaContent::withTrashed()
        ->where('content_type', $block::class)
        ->where('content_id', $block->getKey());
}

it('Soft-Delete und Restore ziehen den Pivot mit', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $text = attachToProject($project, makeText());

    $text->delete();
    expect(pivotsFor($text)->whereNull('deleted_at')->count())->toBe(0);

    $text->restore();
    expect(pivotsFor($text)->whereNull('deleted_at')->count())->toBe(1);
});

dataset('content-blocks', [
    'Text' => [fn () => makeText()],
    'Galerie' => [fn () => makeGallery()],
    'Audio/Video' => [fn () => makeAudiovisual()],
    'Zitat' => [fn () => QuoteBlock::create(['text' => 'Zitat', 'kind' => 'archivalie'])],
    'Daten/Fakten' => [fn () => DataFactBlock::create(['layout' => 'list', 'columns' => [], 'rows' => []])],
]);

it('Force-Delete entfernt den Pivot endgültig', function (Closure $makeBlock) {
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $block = attachToProject($project, $makeBlock());

    $block->forceDelete();

    expect(pivotsFor($block)->count())->toBe(0);
})->with('content-blocks');

it('Force-Delete eines bereits soft-gelöschten Blocks räumt auch den soft-gelöschten Pivot', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $text = attachToProject($project, makeText());

    $text->delete();
    $text->forceDelete();

    expect(pivotsFor($text)->count())->toBe(0);
});

it('Löschen eines Texts lässt eine Galerie mit gleicher ID unberührt', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $text = attachToProject($project, makeText());
    $gallery = attachToProject($project, makeGallery());

    // Frische DB: beide Tabellen starten bei 1 — genau die Kollision.
    expect($gallery->getKey())->toBe($text->getKey());

    $text->delete();

    expect(Gallery::find($gallery->getKey()))->not->toBeNull()
        ->and(pivotsFor($gallery)->whereNull('deleted_at')->count())->toBe(1);
});
