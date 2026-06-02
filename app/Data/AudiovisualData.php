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

namespace App\Data;

use Illuminate\Http\Request;

/**
 * DTO für Audiovisual-Mutations.
 *
 * `link` ist bereits durch den Service vor-normalisiert — also
 * YouTube-URLs sind in die `/embed/`-Form überführt, Audio-Uploads
 * haben einen generierten Dateinamen. Der DTO trägt nur die
 * finalen DB-Werte.
 *
 * `isTranslation` schaltet die Schreibstrategie um (direkt vs.
 * setTranslation('en', ...)); `isTranslated` markiert davon
 * unabhängig, ob eine Übersetzung existiert.
 */
final readonly class AudiovisualData
{
    public function __construct(
        public ?string $link = null,
        public ?string $source = null,
        public ?string $copyright = null,
        public ?string $type = null,
        public bool $isTranslation = false,
        public bool $isTranslated = false,
    ) {}

    /**
     * Bauen aus einer Request plus dem schon normalisierten Link
     * (entweder YouTube-embed-URL oder generierter Audio-File-Name).
     */
    public static function fromRequest(Request $request, ?string $normalizedLink = null): self
    {
        return new self(
            link: $normalizedLink ?? ($request['link'] ?? null),
            source: $request['source'] ?? null,
            copyright: $request['copyright'] ?? null,
            type: $request['type'] ?? null,
            isTranslation: (bool) ($request['translationMode'] ?? false),
            isTranslated: isset($request['isTranslated']),
        );
    }
}
