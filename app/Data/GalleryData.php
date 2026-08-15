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
 * DTO für Gallery-Mutations.
 *
 * Frontend lieferte historisch zwei verschiedene Feld-Sets — einmal
 * `galleryTitle`/`gallerySubtitle`/`galleryDescription` (im
 * Translation-Modal) und einmal `title`/`subtitle`/`description`
 * (im normalen Update). Der direkte Update-Pfad in
 * ContentController::saveGallery war damit latent kaputt, weil er
 * `$request['title']` las — das Frontend schickt aber nur die
 * `gallery*`-Variante. Der DTO normalisiert beide auf einen
 * gemeinsamen Vertrag und priorisiert die `gallery*`-Felder.
 *
 * `isTranslation` schaltet im GalleryService die Schreibstrategie
 * um (direkt vs. setTranslation('en', ...)). `isTranslated` ist
 * davon unabhängig — es markiert, ob die Gallery eine Übersetzung
 * hat.
 */
final readonly class GalleryData
{
    public function __construct(
        public ?string $title = null,
        public ?string $subtitle = null,
        public ?string $description = null,
        public bool $isTranslation = false,
        public bool $isTranslated = false,
    ) {}

    public static function fromRequest(Request $request): self
    {
        // Phase-5-Backlog-Sammler (2026-08-16): Hidden-Inputs im
        // Translate-Blade ohne value-Attribut werden von der
        // ConvertEmptyStringsToNull-Middleware zu null. `isset()`
        // liefert bei Key-mit-null-Wert `false` — wir landen dann
        // faelschlich im Direktschreib-Pfad und ueberschreiben die
        // deutsche Variante. `$request->has()` prueft nur die
        // Key-Anwesenheit.
        return new self(
            title: $request['galleryTitle'] ?? $request['title'] ?? null,
            subtitle: $request['gallerySubtitle'] ?? $request['subtitle'] ?? null,
            description: $request['galleryDescription'] ?? $request['description'] ?? null,
            isTranslation: $request->has('translationGallery'),
            isTranslated: $request->has('isTranslated'),
        );
    }
}
