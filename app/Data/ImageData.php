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
 * DTO für Image-Mutations.
 *
 * Normalisiert die Frontend-Feldnamen (copyrightImage /
 * originImage / altText) auf den semantischen Vertrag des
 * ImageService. Der UploadedFile selbst wird nicht im DTO geführt
 * — er wird als separater Parameter an den Service durchgereicht,
 * weil File-Handles nicht zu einem readonly-DTO passen.
 */
final readonly class ImageData
{
    public function __construct(
        public string $originName,
        public string $copyrightName,
        public ?string $altText = null,
        public bool $isTranslated = false,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            originName: (string) $request['originImage'],
            copyrightName: (string) $request['copyrightImage'],
            altText: $request['altText'] ?? null,
            isTranslated: isset($request['isTranslated']),
        );
    }
}
