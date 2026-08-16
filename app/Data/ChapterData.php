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
 * DTO für Chapter-Mutations (Store + Update).
 *
 * Normalisiert die Frontend-Feldnamen (chapterTitle / chapterSubtitle
 * / chapterDescription) auf die Modell-Feldnamen (name / subtitle /
 * description) und kapselt die beiden Translation-Flags, die der
 * UpdateChapterRequest zusätzlich tragen kann.
 *
 * `isTranslation` schaltet im ChapterService die Schreibstrategie um:
 * - false: name / subtitle / description direkt setzen
 * - true:  via setTranslation('en', ...) als englische Übersetzung
 *          schreiben
 *
 * `isTranslated` ist davon unabhängig — es markiert, ob das Chapter
 * eine Übersetzung HAT, nicht wie geschrieben wird.
 */
final readonly class ChapterData
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

        // Phase-5-Backlog-Sammler (2026-08-16): das Translate-Blade
        // schickt <input type="hidden" name="translationChapter"> OHNE
        // value-Attribut. `(bool) ""` ist false, wir landen dann
        // faelschlich im Direktschreib-Pfad und ueberschreiben die
        // deutsche Variante. `$request->has()` prueft nur, ob der
        // Key im Payload liegt.
        return new self(
            name: $validated['chapterTitle'],
            subtitle: $validated['chapterSubtitle'] ?? null,
            description: $validated['chapterDescription'] ?? null,
            isTranslation: $request->has('translationChapter'),
            isTranslated: $request->has('isTranslated'),
        );
    }
}
