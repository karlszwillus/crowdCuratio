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
 * FormRequest für `PUT /projects/{project}`.
 *
 * Authorize() delegiert an ProjectPolicy::update. Validation analog
 * zu StoreProjectRequest, plus die NF-SEC-007-Härtung: project_image
 * darf nur als File-Upload mit MIME-Whitelist reinkommen, kein
 * String-Wert für `logo`.
 *
 * Phase 2 / D.6, ADR-0017.
 */
class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('project'));
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'imprint' => 'required|string',
            'terms' => 'nullable|string',
            'description' => 'nullable|string',
            'project_image' => 'sometimes|nullable|file|mimes:jpeg,jpg,png,gif,webp|max:4096',
            // Q4-Etappe 5 / E1a (2026-09-09): Leitbild-Upload.
            'cover_image' => 'sometimes|nullable|file|mimes:jpeg,jpg,png,gif,webp|max:8192',
            // Q4-Etappe 3 / C0b (2026-09-07): Zitier-Settings.
            'citation_depth' => 'sometimes|in:simple,full',
            'source_required' => 'sometimes|boolean',
            // Q4-Etappe 5 / G1 (2026-09-08): Reader-Layout.
            'reader_layout' => 'sometimes|in:one-page,multi-page',
            // Q4-Etappe 5 / G-Fund-1 (2026-09-09): Reader-Charakter + Akzent.
            'character' => 'sometimes|in:dokumentation,archiv,erzaehlung',
            'accent_color' => 'sometimes|nullable|regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/',
        ];
    }

    /**
     * Q4-Etappe 3 / C0b: Checkboxen kommen bei nicht-gesetzt gar nicht
     * im Request an — vor der Validation als false injizieren, damit
     * das Setting explizit abgeschaltet werden kann.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('citation_depth')) {
            $this->merge([
                'source_required' => $this->boolean('source_required'),
            ]);
        }
    }
}
