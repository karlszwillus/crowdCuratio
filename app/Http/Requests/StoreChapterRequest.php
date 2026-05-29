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
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest für `POST /chapters`.
 *
 * Validiert Eingaben des Add-Chapter-Modals und delegiert
 * Authorization an ChapterPolicy::createIn (Phase 1.5 NF-LAR-003).
 * Phase 2 / D.4, ADR-0017.
 */
class StoreChapterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = Project::find($this->input('projectId'));

        if ($project === null) {
            return false;
        }

        return $this->user()->can('createIn', [Chapter::class, $project]);
    }

    public function rules(): array
    {
        return [
            'projectId' => 'required|integer|exists:projects,id',
            'chapterTitle' => 'required|string|max:255',
            'chapterSubtitle' => 'nullable|string|max:255',
            'chapterDescription' => 'nullable|string',
        ];
    }
}
