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
 * Permission-Namen als Backed-Enum.
 *
 * Hervorgegangen aus der Phase-2 / D.8-Konstanten-Klasse
 * (NF-CODE-003). Seit Block D / ADR-0005 ist die Klasse zum
 * Enum gehoben — Laravel-Gate und Spatie-Permission v6
 * akzeptieren `BackedEnum`-Instanzen direkt
 * (`$user->can(PermissionName::ADD)`, `$user->givePermissionTo
 * (PermissionName::EDIT)`).
 *
 * Die String-Werte bleiben unverändert, damit Bestandsdaten
 * (Permission-Rows in der DB, Role-Permission-Verknüpfungen)
 * ohne Migration weiterleben.
 *
 * Blade-Views nutzen weiterhin die String-Form (`@can('add',
 * $project)`), weil `@can(PermissionName::ADD, $project)` in
 * Templates schlechter lesbar ist. Tradeoff bewusst.
 */
enum PermissionName: string
{
    case VIEW = 'view';
    case ADD = 'add';
    case EDIT = 'edit';
    case DELETE = 'delete';
    case PUBLISH = 'publish';
    case COMMENT = 'comment';
    case INVITE = 'invite';

    /**
     * Alle Permissions als String-Array — kompatibel zur alten
     * Final-Class-Signatur. Wird vom `PermissionTableSeeder`
     * durchlaufen, plus in den Test-Setups (beforeEach).
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
