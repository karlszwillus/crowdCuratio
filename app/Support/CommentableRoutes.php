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
use App\Models\Entry;
use App\Models\Gallery;
use App\Models\Image;
use App\Models\Project;
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
            'save' => 'comment.project.save',
            'base' => '',
        ],
        Chapter::class => [
            'save' => 'comment.save',
            'base' => 'comment.chapter',
        ],
        Entry::class => [
            'save' => 'comment.entry.save',
            'base' => 'comment.entry',
        ],
        Gallery::class => [
            'save' => 'comment.gallery.save',
            'base' => 'comment.gallery',
        ],
        Audiovisual::class => [
            'save' => 'comment.audiovisual.save',
            'base' => 'comment.audiovisual',
        ],
        Image::class => [
            'save' => 'comment.image.save',
            'base' => 'comment.image',
        ],
        Text::class => [
            'save' => 'comment.text.save',
            'base' => 'comment.text',
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
