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

use App\Models\User;
use App\Support\RoleName;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * User-Authorization.
 *
 * Hotfix Authorization-Bypass: vor diesem Patch war
 * `UserController::update` weder per `role:Admin`-Middleware noch per
 * Policy geschützt — jeder eingeloggte User konnte via
 * `PATCH /users/{anderer}` fremde User editieren (inkl. Rollen-Sync).
 *
 * Logik: Admin darf alles (via before()), sonst nur Self-Edit. Die
 * inhaltliche Beschränkung des `roles`-Feldes (nur Admins dürfen
 * Rollen synchronisieren) macht der Controller, weil die Policy nicht
 * an Request-Felder herankommt.
 */
class UserPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole(RoleName::ADMIN->value) ? true : null;
    }

    /**
     * Self-Edit. Admin via before().
     */
    public function update(User $user, User $target): bool
    {
        return $user->id === $target->id;
    }
}
