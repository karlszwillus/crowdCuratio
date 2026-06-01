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
 * DTO für Project-Mutations.
 *
 * Ersetzt das alte mapData()-Cargo in ProjectController, das die
 * FormRequest-Validation ignorierte und stattdessen mit
 * isset($request[...]) wieder selbst gelesen hat.
 *
 * Erste konkrete Anwendung der DTO-Konvention aus ADR-0020.
 */
final readonly class ProjectData
{
    public function __construct(
        public string $name,
        public ?string $imprint = null,
        public ?string $terms = null,
        public ?string $description = null,
        public ?string $logo = null,
    ) {}

    /**
     * Bauen aus einer validierten FormRequest plus einem optional
     * von einem vorgelagerten Service zurückgelieferten Logo-Namen.
     *
     * Das Logo kommt nicht aus der Request, weil
     * ProjectImageService->store(...) es vorher auf der public-Disk
     * abgelegt und einen Dateinamen geliefert hat.
     */
    public static function fromRequest(FormRequest $request, ?string $logo = null): self
    {
        $validated = $request->validated();

        return new self(
            name: $validated['name'],
            imprint: $validated['imprint'] ?? null,
            terms: $validated['terms'] ?? null,
            description: $validated['description'] ?? null,
            logo: $logo,
        );
    }

    /**
     * Array für Eloquent::fill() oder Project::update().
     *
     * Filtert null-Felder raus, damit Eloquent sie nicht
     * fälschlich überschreibt (z. B. beim Update darf logo=null
     * den bestehenden Logo-Filename nicht löschen, wenn das
     * Frontend kein neues Bild geschickt hat).
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'imprint' => $this->imprint,
            'terms' => $this->terms,
            'description' => $this->description,
            'logo' => $this->logo,
        ], fn ($value) => $value !== null);
    }
}
