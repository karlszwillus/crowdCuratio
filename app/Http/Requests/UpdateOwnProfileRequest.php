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
use Illuminate\Support\Facades\Hash;

/**
 * FormRequest für `PATCH /profile` — Self-Edit-Pfad.
 *
 * Block E / Welle E.3: vorher Mischung mit Admin-Edit in
 * `UserController::update`. Hier nur das Profil des eingeloggten
 * Users — Name plus optionaler Passwort-Wechsel mit Verifikation
 * des alten Passworts.
 *
 * Bewusst KEIN `roles`-Feld: Rollen-Zuweisung ist Admin-Pfad. Auch
 * kein `adminUser` / `createProject` — beide sind privilegierte
 * Felder. Würde ein Self-Edit-Caller eines dieser Felder schicken,
 * wandert es nicht in die `validated`-Daten und wird vom Controller
 * stillschweigend ignoriert.
 *
 * Authorize: eingeloggt reicht. Target ist implizit
 * `auth()->user()`, daher keine Self-Check-Klausel nötig.
 */
class UpdateOwnProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $rules = [
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'old_password' => 'sometimes|nullable|string',
            'new_password' => 'sometimes|nullable|string|min:8',
            'confirm_password' => 'sometimes|nullable|string|same:new_password',
        ];

        // Wenn old_password gesetzt ist, muss new_password + confirm_password
        // auch da sein UND old_password muss zum aktuellen Passwort passen.
        if (filled($this->input('old_password'))) {
            $rules['new_password'] = 'required|string|min:8';
            $rules['confirm_password'] = 'required|string|same:new_password';
            $rules['old_password'] = [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (! Hash::check($value, $this->user()->password)) {
                        $fail(__('message_old_password_incorrect'));
                    }
                },
            ];
        }

        return $rules;
    }
}
