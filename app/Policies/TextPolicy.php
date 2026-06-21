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

namespace App\Policies;

use App\Models\Text;
use App\Models\User;
use App\Support\PermissionName;

/**
 * Text-Authorization. Block E.7b Sub-Welle 3 (ADR-0022).
 *
 * Project-scoped via `OwnerScopedPolicy` — Owner-Shortcut und
 * Pivot-Lookup im ProjectPermissionService. Admin via `before()`.
 *
 * Das Project wird via `$text->project()` aufgelöst, das über
 * den MediaContent-Pivot zum Entry → Chapter → Project navigiert.
 * Wenn der Text noch nicht an einen Entry gehängt ist (Race vor
 * attachToEntry), liefert `project()` null — der Zugriff wird
 * dann verweigert.
 */
class TextPolicy extends OwnerScopedPolicy
{
    public function view(User $user, Text $text): bool
    {
        return $this->checkViaProject($user, $text->project(), PermissionName::VIEW);
    }

    public function update(User $user, Text $text): bool
    {
        return $this->checkViaProject($user, $text->project(), PermissionName::EDIT);
    }

    public function delete(User $user, Text $text): bool
    {
        return $this->checkViaProject($user, $text->project(), PermissionName::DELETE);
    }

    public function comment(User $user, Text $text): bool
    {
        return $this->checkViaProject($user, $text->project(), PermissionName::COMMENT);
    }
}
