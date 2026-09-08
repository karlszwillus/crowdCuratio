<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 */

use App\Models\Audiovisual;
use App\Models\DataFactBlock;
use App\Models\Entry;
use App\Models\Gallery;
use App\Models\MediaContent;
use App\Models\QuoteBlock;
use App\Models\Text;
use App\Models\User;
use App\Services\ContentInsertionService;

/**
 * Q4-Etappe 4 / C1c (2026-09-08): Inline-Add-Flow für Content-
 * Blöcke. Service legt leere Blöcke an einer bestimmten Position
 * an und shiftet vorhandene MediaContent-Rows entsprechend.
 */
function insertBlank(int $entryId, string $type, ?int $after = null): int
{
    return app(ContentInsertionService::class)->insertBlank($entryId, $type, $after);
}

it('legt einen leeren Text-Block an Position 1 an, wenn Entry leer ist', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $entry = makeEntry($chapter);

    $mediaId = insertBlank($entry->id, 'text');

    $media = MediaContent::findOrFail($mediaId);
    expect($media->parent_id)->toBe($entry->id);
    expect($media->parent_type)->toBe(Entry::class);
    expect($media->content_type)->toBe(Text::class);
    expect($media->position)->toBe(1);

    $text = Text::findOrFail($media->content_id);
    expect((string) $text->text)->toBe('');
    expect($text->origin)->toBeNull();
    expect($text->copyright)->toBeNull();
});

it('legt einen leeren Gallery-Block an', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $entry = makeEntry(makeChapter($project));

    $mediaId = insertBlank($entry->id, 'gallery');

    $media = MediaContent::findOrFail($mediaId);
    expect($media->content_type)->toBe(Gallery::class);
    expect(Gallery::find($media->content_id))->not->toBeNull();
});

it('legt einen leeren Daten-und-Fakten-Block an', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $entry = makeEntry(makeChapter($project));

    $mediaId = insertBlank($entry->id, 'data-facts');

    $media = MediaContent::findOrFail($mediaId);
    expect($media->content_type)->toBe(DataFactBlock::class);
    $block = DataFactBlock::find($media->content_id);
    expect($block)->not->toBeNull();
    expect($block->title)->toBeNull();
    expect($block->rows)->toBe([]);
});

it('legt einen leeren Zitat-Block an', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $entry = makeEntry(makeChapter($project));

    $mediaId = insertBlank($entry->id, 'quote');

    $media = MediaContent::findOrFail($mediaId);
    expect($media->content_type)->toBe(QuoteBlock::class);
    $quote = QuoteBlock::find($media->content_id);
    expect($quote)->not->toBeNull();
    expect((string) $quote->text)->toBe('');
    expect($quote->speaker)->toBeNull();
    expect($quote->kind)->toBeNull();
    expect($quote->source_id)->toBeNull();
});

it('legt einen leeren Audiovisual-Block an', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $entry = makeEntry(makeChapter($project));

    $mediaId = insertBlank($entry->id, 'audiovisual');

    $media = MediaContent::findOrFail($mediaId);
    expect($media->content_type)->toBe(Audiovisual::class);
    $av = Audiovisual::find($media->content_id);
    expect($av)->not->toBeNull();
    expect($av->type)->toBe('video');
});

it('shiftet bestehende Blöcke, wenn zwischen zwei Positionen eingefügt wird', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $entry = makeEntry(makeChapter($project));

    $first = insertBlank($entry->id, 'text');           // pos 1
    $second = insertBlank($entry->id, 'text', $first);  // pos 2
    $third = insertBlank($entry->id, 'text', $second);  // pos 3

    // Zwischen first und second einfügen → neuer Block auf pos 2,
    // bisherige pos 2 und 3 rutschen auf 3 und 4.
    $between = insertBlank($entry->id, 'text', $first);

    expect(MediaContent::find($first)->position)->toBe(1);
    expect(MediaContent::find($between)->position)->toBe(2);
    expect(MediaContent::find($second)->position)->toBe(3);
    expect(MediaContent::find($third)->position)->toBe(4);
});

it('shiftet alle Blöcke um +1, wenn vor dem ersten eingefügt wird (afterMediaContentId=null)', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $entry = makeEntry(makeChapter($project));

    $a = insertBlank($entry->id, 'text');           // pos 1
    $b = insertBlank($entry->id, 'text', $a);       // pos 2

    // Neuer Block vor dem ersten → Position 1, a→2, b→3.
    $newFirst = insertBlank($entry->id, 'text', null);

    expect(MediaContent::find($newFirst)->position)->toBe(1);
    expect(MediaContent::find($a)->position)->toBe(2);
    expect(MediaContent::find($b)->position)->toBe(3);
});

it('wirft bei unbekanntem Typ', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $entry = makeEntry(makeChapter($project));

    expect(fn () => insertBlank($entry->id, 'unknown'))
        ->toThrow(InvalidArgumentException::class);
});
