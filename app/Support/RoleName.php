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

/**
 * Backed-Enum für die vier kanonischen Rollen der App.
 *
 * Block E / Welle E.1 (analog `PermissionName`). Vor diesem Enum
 * verteilten sich `'Admin'`/`'Reader'`/`'Editor'`/`'Reviewer'` als
 * harte Strings über Controller, Policies und RoleTableSeeder —
 * eine Umbenennung oder Typ-Verschiebung wäre nur über grep
 * machbar gewesen.
 *
 * Die String-Werte entsprechen exakt den Spatie-Rollen-Namen in der
 * `roles`-Tabelle. Änderungen würden Bestandsdaten brechen; daher
 * ist der Test in `tests/Unit/Support/RoleNameTest.php` die
 * Charakterisierung der Case-Sensitivität.
 *
 * Blade-Views und Spatie-Methoden akzeptieren weiterhin den
 * String-Pfad (`$user->hasRole('Admin')`), aber im App-Code wird
 * der Enum bevorzugt.
 */
enum RoleName: string
{
    case ADMIN = 'Admin';
    case READER = 'Reader';
    case EDITOR = 'Editor';
    case REVIEWER = 'Reviewer';

    /**
     * Alle Rollen als String-Array — analog zu `PermissionName::all()`.
     * Wird im `RoleTableSeeder` und in Test-Setups durchlaufen.
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
