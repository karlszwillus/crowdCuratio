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

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest für `POST /image/store` (ContentController::saveImage).
 *
 * NF-SEC-201 / Vor-Phase-3-Härtung. Schließt zwei Upload-Pfade in
 * einer Klasse:
 *
 *  - Create-Modus: das Form sendet `image` (neuer Image-Block in
 *    einer Gallery).
 *  - Update-Modus: das Form sendet `newImage` (Bild austauschen am
 *    bestehenden Image-Block).
 *
 * Beide Pfade sind nullable, weil dieselbe Route auch im
 * Translation-Modus erreichbar ist (kein File-Upload, nur
 * Metadaten-Übersetzung).
 *
 * authorize() ist hier offen — Owner-Check über Gallery → Entry →
 * Chapter → Project ist Policy-Material für die FormRequest-Welle 2
 * in Phase 4 (NF-LAR-010, geplant zusammen mit `TextPolicy`,
 * `ImagePolicy`, `GalleryPolicy`, `AudiovisualPolicy`).
 *
 * MIME-Whitelist und 4-MB-Limit sind identisch zum
 * `project_image`-Pattern aus `StoreProjectRequest`.
 */
class StoreImageBlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'image' => 'sometimes|nullable|file|mimes:jpeg,jpg,png,gif,webp|max:4096',
            'newImage' => 'sometimes|nullable|file|mimes:jpeg,jpg,png,gif,webp|max:4096',
        ];
    }
}
