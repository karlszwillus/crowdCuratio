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
 * Permission-Namen als Konstanten — Phase 2 / D.8 (NF-CODE-003).
 *
 * Bisher waren die sieben Permission-Strings ('view', 'add', …) als
 * Magic-Strings über Seeder, Policies, Controller und Views verteilt.
 * Diese Klasse zentralisiert sie. Die String-Werte bleiben unverändert,
 * damit Bestandsdaten (Permission-Rows in der DB, Role-Permission-
 * Verknüpfungen) ohne Migration weiterleben.
 *
 * Verwendung in PHP-Code (Seeder, Policies, Controller, FormRequests):
 *
 *     use App\Support\PermissionName;
 *
 *     $user->can(PermissionName::ADD);
 *     Permission::updateOrCreate(['name' => PermissionName::VIEW]);
 *
 * Blade-Views nutzen weiterhin die String-Form ('add', 'edit', …),
 * weil `@can(PermissionName::ADD, $project)` in Templates schlechter
 * lesbar ist als `@can('add', $project)`. Tradeoff bewusst.
 *
 * Phase 3-Bezug: mit dem Laravel-Upgrade kann diese Klasse in ein
 * Backed-Enum umgewandelt werden, sobald PHP 8.1+ in Prod fix ist.
 */
final class PermissionName
{
    public const VIEW = 'view';

    public const ADD = 'add';

    public const EDIT = 'edit';

    public const DELETE = 'delete';

    public const PUBLISH = 'publish';

    public const COMMENT = 'comment';

    public const INVITE = 'invite';

    /**
     * Alle Permissions in der kanonischen Reihenfolge.
     *
     * Wird vom PermissionTableSeeder durchlaufen — die Reihenfolge
     * bestimmt die `permission_descriptions.position`.
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::VIEW,
            self::ADD,
            self::EDIT,
            self::DELETE,
            self::PUBLISH,
            self::COMMENT,
            self::INVITE,
        ];
    }
}
