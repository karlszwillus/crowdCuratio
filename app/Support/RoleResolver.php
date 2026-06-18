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

namespace App\Support;

use Spatie\Permission\Contracts\Role as RoleContract;
use Spatie\Permission\Models\Role;

/**
 * Block E / Welle E.5: Eingabe für `roles` zu konkreten
 * `Role`-Instanzen auflösen. Vorher Private-Methode in
 * `RegisteredUserController::store` — herausgezogen, weil isoliert
 * testbar und unabhängig vom Controller-Lifecycle.
 *
 * Akzeptiert Single-String, Array und mischt numerische Strings
 * (ID-Lookup) mit Text-Strings (Name-Lookup). Wirft die
 * Spatie-üblichen `RoleDoesNotExist`-Exceptions, wenn ein
 * referenzierter Wert nicht auflösbar ist — bleibt bewusst hart,
 * weil ein ungültiger Role-Submit ein Form-Bug ist.
 */
class RoleResolver
{
    /**
     * @param  array<int|string, mixed>|string|int|null  $input
     * @return array<int, RoleContract>
     */
    public function resolve(array|string|int|null $input): array
    {
        if ($input === null || $input === '') {
            return [];
        }

        $values = is_array($input) ? $input : [$input];

        return collect($values)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(function ($value): RoleContract {
                if (is_int($value) || (is_string($value) && ctype_digit($value))) {
                    return Role::findById((int) $value, 'web');
                }

                return Role::findByName((string) $value, 'web');
            })
            ->values()
            ->all();
    }
}
