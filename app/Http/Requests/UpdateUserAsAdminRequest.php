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

use App\Support\RoleName;
use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest für `PATCH /users/{user}` — Admin-Pfad.
 *
 * Block E / Welle E.3: vorher hatte `UserController::update`
 * Self-Edit und Admin-Edit in einer Methode vereint mit
 * if-Verzweigung über `old_password`. Mit dem Split bekommt jeder
 * Pfad einen eigenen FormRequest. Hier: Admin-Edit eines beliebigen
 * Users — Name, Rolle, plus die heute noch existierenden Flags
 * `adminUser` / `createProject` (siehe Phase-5-TODO: `is_admin`-
 * Drift-Auflösung).
 *
 * Authorize: Caller muss Admin-Rolle haben. Defense-in-Depth zur
 * `role:Admin`-Middleware in `UserController::__construct`.
 */
class UpdateUserAsAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(RoleName::ADMIN->value) === true;
    }

    public function rules(): array
    {
        return [
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'roles' => 'sometimes|array',
            'roles.*' => 'string',
            'adminUser' => 'sometimes|boolean',
            'createProject' => 'sometimes|boolean',
        ];
    }
}
