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
 * FormRequest für alle Comment-Pfade.
 *
 * Block E / Welle E.6. Vorher hatten sieben Controller-Methoden
 * (Project, Chapter, Entry, Text, Image, Gallery, Audiovisual)
 * jeweils ein inline `$request->validate(['comment' => 'required'])`.
 * Ein gemeinsamer FormRequest deckt alle ab.
 *
 * Die Frage „darf dieser User auf diesem Modell kommentieren?"
 * gehört nicht hierher — sie ist project-scoped und braucht die
 * konkrete Modell-Instanz. Der jeweilige Controller ruft daher
 * weiterhin `$this->authorize('comment', $model)` nach dem
 * `findOrFail`-Lookup.
 */
class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'id' => 'required|integer',
            'comment' => 'required|string',
        ];
    }
}
