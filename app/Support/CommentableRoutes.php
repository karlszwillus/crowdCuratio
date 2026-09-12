<?php

/**
crowdCuratio - Curating together virtually
Copyright (C)2026 - berlinHistory e.V.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

You should have received a copy of the GNU General Public License
along with this program in the file LICENSE.

If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace App\Support;

use App\Models\Audiovisual;
use App\Models\Chapter;
use App\Models\DataFactBlock;
use App\Models\Entry;
use App\Models\Gallery;
use App\Models\Image;
use App\Models\Project;
use App\Models\QuoteBlock;
use App\Models\Text;

/**
 * I2 (2026-08-21) — ARCH-02. Registry der Route-Namen fuer die
 * Kommentar-Sicht pro commentable Model. Bisher lebte diese Zuordnung
 * als switch-Kaskade in CommentRetrieve; jetzt zentral, damit ein
 * neues commentable Model an einer einzigen Stelle registriert wird.
 *
 * `save` — die Save-Endpoint-Route
 * `base` — der Kanonische Deep-Link-Prefix, wird von der Blade-Sicht
 *          fuer Anker in der Comment-Liste genutzt
 */
final class CommentableRoutes
{
    /**
     * @var array<class-string, array{save: string, base: string}>
     */
    private const MAP = [
        Project::class => [
            'save' => 'comments.project.save',
            'base' => '',
        ],
        Chapter::class => [
            'save' => 'comments.chapter.save',
            'base' => 'comments.chapter',
        ],
        Entry::class => [
            'save' => 'comments.entry.save',
            'base' => 'comments.entry',
        ],
        Gallery::class => [
            'save' => 'comments.gallery.save',
            'base' => 'comments.gallery',
        ],
        Audiovisual::class => [
            'save' => 'comments.audiovisual.save',
            'base' => 'comments.audiovisual',
        ],
        Image::class => [
            'save' => 'comments.image.save',
            'base' => 'comments.image',
        ],
        Text::class => [
            'save' => 'comments.text.save',
            'base' => 'comments.text',
        ],
        // Q4-Etappe 4 / F1 (2026-09-08): Zitat-Block.
        QuoteBlock::class => [
            'save' => 'comments.quote.save',
            'base' => 'comments.quote',
        ],
        // Q4-Etappe 4 / G1 (2026-09-08): Daten-und-Fakten-Block.
        DataFactBlock::class => [
            'save' => 'comments.data_facts.save',
            'base' => 'comments.data_facts',
        ],
    ];

    /**
     * @return array{save: string, base: string}|null
     */
    public static function for(string $fqcn): ?array
    {
        return self::MAP[$fqcn] ?? null;
    }
}
