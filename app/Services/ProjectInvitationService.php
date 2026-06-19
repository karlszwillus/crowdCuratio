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

namespace App\Services;

use App\Models\Invitation;
use App\Models\ProjectUserPermission;
use App\Models\User;
use Spatie\Permission\Contracts\Role as RoleContract;
use Spatie\Permission\Models\Role;

/**
 * Block E / Welle E.5 — Project-Invitation-Pfad aus
 * `RegisteredUserController::store` herausgezogen.
 *
 * Hängt einen neu angelegten User an ein konkretes Project: für
 * jede Permission der übergebenen Rollen einen
 * `ProjectUserPermission`-Pivot-Eintrag, plus eine Invitation,
 * die den Einlader zum Eingeladenen verknüpft.
 *
 * Permissions kommen aus Spatie's `permissions()`-Relation am
 * Role-Modell; die Übergabe von `RoleContract`-Instanzen aus dem
 * Aufrufer-Code wird intern zu Spatie's `Role`-Models eager-loaded,
 * weil das Contract die Relation nicht typisiert garantiert.
 */
class ProjectInvitationService
{
    /**
     * @param  array<int, RoleContract>  $resolvedRoles
     */
    public function attachInviteeToProject(
        User $invitee,
        User $inviter,
        int $projectId,
        array $resolvedRoles,
    ): void {
        $permissionIds = $this->collectPermissionIds($resolvedRoles);

        foreach ($permissionIds as $permissionId) {
            ProjectUserPermission::create([
                'project_id' => $projectId,
                'permission_id' => $permissionId,
                'user_id' => $invitee->id,
            ]);
        }

        Invitation::create([
            'user_id' => $inviter->id,
            'guest_id' => $invitee->id,
            'project_id' => $projectId,
        ]);
    }

    /**
     * @param  array<int, RoleContract>  $resolvedRoles
     * @return array<int, int>
     */
    private function collectPermissionIds(array $resolvedRoles): array
    {
        $roleIds = collect($resolvedRoles)->pluck('id')->all();

        if ($roleIds === []) {
            return [];
        }

        return Role::query()
            ->whereIn('id', $roleIds)
            ->with('permissions')
            ->get()
            ->flatMap(fn (Role $role) => $role->permissions)
            ->pluck('id')
            ->unique()
            ->values()
            ->all();
    }
}
