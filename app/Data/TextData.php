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
 * DTO für Text-Mutations.
 *
 * Bündelt die Frontend-Feldnamen (contentText / originText /
 * copyrightText) auf den semantischen Vertrag des TextService.
 * `body` wird vor dem Speichern vom Service noch durch einen
 * `<script>`-Filter geschickt (heute im saveText/updateText-Body,
 * mit dem Refactor jetzt im Service).
 *
 * `isTranslated` markiert, ob der Text eine Übersetzung hat —
 * unabhängig vom Body selbst.
 */
final readonly class TextData
{
    public function __construct(
        public string $body,
        public string $originName,
        public string $copyrightName,
        public bool $isTranslated = false,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            body: (string) $request['contentText'],
            originName: (string) $request['originText'],
            copyrightName: (string) $request['copyrightText'],
            isTranslated: isset($request['isTranslatedText']),
        );
    }
}
