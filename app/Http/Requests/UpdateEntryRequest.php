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
 * FormRequest für `PATCH /entries/{entry}`.
 *
 * Authorization über EntryPolicy::update. Phase 2 / D.5, ADR-0017.
 * Felder analog zu UpdateChapterRequest.
 */
class UpdateEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('entry'));
    }

    public function rules(): array
    {
        return [
            'entryTitle' => 'required|string|max:255',
            'entrySubtitle' => 'nullable|string|max:255',
            'entryDescription' => 'nullable|string',
            'translationEntry' => 'sometimes|boolean',
            'isTranslated' => 'sometimes|boolean',
        ];
    }
}
