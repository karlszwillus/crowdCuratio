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

use Illuminate\Foundation\Http\FormRequest;

/**
 * DTO für Entry-Mutations (Store + Update).
 *
 * Strukturell parallel zu ChapterData — beide Modelle teilen die
 * gleichen Felder und die gleiche Translation-Flag-Semantik. Eine
 * gemeinsame Basis-Klasse wäre denkbar, lohnt sich bei zwei
 * Klonen aber noch nicht. Sollte ein drittes curierbares Modell
 * dazukommen, wird der Schnitt in ein gemeinsames AbstractItemData
 * sauberer.
 *
 * `isTranslation` schaltet im EntryService die Schreibstrategie
 * um: false = direkt setzen, true = via setTranslation('en', ...).
 * `isTranslated` markiert davon unabhängig, ob das Entry eine
 * Übersetzung hat.
 */
final readonly class EntryData
{
    public function __construct(
        public string $name,
        public ?string $subtitle = null,
        public ?string $description = null,
        public bool $isTranslation = false,
        public bool $isTranslated = false,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            name: $validated['entryTitle'],
            subtitle: $validated['entrySubtitle'] ?? null,
            description: $validated['entryDescription'] ?? null,
            isTranslation: (bool) ($validated['translationEntry'] ?? false),
            isTranslated: (bool) ($validated['isTranslated'] ?? false),
        );
    }
}
