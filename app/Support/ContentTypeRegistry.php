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
use App\Models\Text;

/**
 * I2 (2026-08-21) — ARCH-02 / F-ARCH-009. Register-Pattern fuer den
 * String → Model-Klasse/Tabelle/Property-Mapping, das bisher als
 * switch-Anti-Pattern in LogService, ProjectController::getParentText
 * und diversen anderen Stellen lebte. Konvergiert die
 * Content-Modelle auf einen Dispatcher.
 *
 * Kurz-Slugs (`text`, `image`, `entry`, `chapter`, `gallery`, `audiovisual`)
 * werden vom Frontend uebergeben — die Zuordnung zu Model-FQCN,
 * DB-Tabelle und dem Log-relevanten Feld lebt hier zentral.
 */
final class ContentTypeRegistry
{
    /**
     * @var array<string, array{model: class-string, table: string, property: string}>
     */
    private const MAP = [
        'text' => [
            'model' => Text::class,
            'table' => 'texts',
            'property' => 'text',
        ],
        'image' => [
            'model' => Image::class,
            'table' => 'images',
            'property' => 'name',
        ],
        'entry' => [
            'model' => Entry::class,
            'table' => 'entries',
            'property' => 'name',
        ],
        'chapter' => [
            'model' => Chapter::class,
            'table' => 'chapters',
            'property' => 'name',
        ],
        'gallery' => [
            'model' => Gallery::class,
            'table' => 'galleries',
            'property' => 'name',
        ],
        'audiovisual' => [
            'model' => Audiovisual::class,
            'table' => 'audiovisuals',
            'property' => 'text',
        ],
    ];

    /**
     * @return array{model: class-string, table: string, property: string}|null
     */
    public static function for(?string $slug): ?array
    {
        if ($slug === null) {
            return null;
        }

        return self::MAP[$slug] ?? null;
    }

    public static function model(string $slug): ?string
    {
        return self::for($slug)['model'] ?? null;
    }

    public static function table(string $slug): ?string
    {
        return self::for($slug)['table'] ?? null;
    }

    public static function property(string $slug): ?string
    {
        return self::for($slug)['property'] ?? null;
    }

    /**
     * Alle registrierten Slugs — nuetzlich fuer Whitelist-Checks.
     *
     * @return array<int, string>
     */
    public static function slugs(): array
    {
        return array_keys(self::MAP);
    }
}
