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
 * FormRequest für `POST /projects`.
 *
 * Phase 2 / D.3 + D.6, ADR-0017. Image-Upload ist hier integriert,
 * weil project_image als File-Field zusammen mit den Project-
 * Feldern abgeschickt wird — kein separater Endpoint.
 *
 * Authorize() ist hier offen: jeder eingeloggte User darf ein
 * Project anlegen. Sobald wir das ändern wollen (z. B. nur User
 * mit `create_project=true`), wandert die Policy-Logik hier her.
 */
class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'imprint' => 'required|string',
            'terms' => 'nullable|string',
            'description' => 'nullable|string',
            // NF-SEC-001 + NF-SEC-007: Bild-Upload mit MIME-Whitelist
            // und 4-MB-Limit. Phase 1.5 hatte das schon in
            // ProjectController::update inline; hier jetzt systematisch
            // in beiden Pfaden.
            'project_image' => 'sometimes|nullable|file|mimes:jpeg,jpg,png,gif,webp|max:4096',
        ];
    }
}
