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

use App\Models\Chapter;
use App\Models\Entry;
use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest für `POST /entries`.
 *
 * Authorization über EntryPolicy::createIn (Phase 1.5 NF-LAR-003).
 * Phase 2 / D.5, ADR-0017.
 */
class StoreEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Translation-Mode (Phase-5-Backlog-Sammler 2026-08-16):
        // Uebersetzungs-Sicht submittet mit `translationEntry` +
        // `entryId`, ohne `chapterId`. Update-Semantik statt Create.
        //
        // has() statt filled(): der Hidden-Input im Translate-Blade
        // hat kein value-Attribut, sendet also einen leeren String —
        // filled() waere in dem Fall false.
        if ($this->has('translationEntry') && $this->filled('entryId')) {
            $entry = Entry::find($this->input('entryId'));

            return $entry !== null
                && $this->user()->can('update', $entry);
        }

        $chapter = Chapter::find($this->input('chapterId'));

        if ($chapter === null) {
            return false;
        }

        return $this->user()->can('createIn', [Entry::class, $chapter]);
    }

    public function rules(): array
    {
        if ($this->has('translationEntry')) {
            return [
                'entryId' => 'required|integer|exists:entries,id',
                'entryTitle' => 'required|string|max:255',
                'entrySubtitle' => 'nullable|string|max:255',
                'entryDescription' => 'nullable|string',
                'isTranslated' => 'sometimes',
                'translationEntry' => 'sometimes',
            ];
        }

        return [
            'chapterId' => 'required|integer|exists:chapters,id',
            'entryTitle' => 'required|string|max:255',
            'entrySubtitle' => 'nullable|string|max:255',
            'entryDescription' => 'nullable|string',
        ];
    }
}
