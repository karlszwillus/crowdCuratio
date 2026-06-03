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
use App\Models\ModelHasRole;
use App\Models\Project;
use App\Models\ProjectUserPermission;
use App\Models\User;
use App\Support\PermissionName;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;

/**
 * Kapselt die project-scoped Permission-Operationen, die vorher in
 * zehn Methoden über den ProjectController verteilt waren.
 *
 * Phase 4 / Block C — Pilot-Extraktion. Block D / ADR-0005 wird die
 * Permission-Welt insgesamt harmonisieren (Spatie + UserHasPermission
 * + PermissionName auf einen Pfad); bis dahin hält dieser Service
 * die existierende Drei-Welten-Logik gekapselt.
 */
class ProjectPermissionService
{
    /**
     * Liste der für ein Project berechtigten User mit ihren
     * project-scoped Permissions.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getUsersForThisProject(int $projectId): array
    {
        $users = User::all();

        $userList = [];
        foreach ($users as $user) {
            $userList[$user->id] = ['name' => $user->name, 'lastName' => $user->last_name];
        }

        $pivots = ProjectUserPermission::where('project_id', $projectId)->get();

        $listGrantedUsers = [];

        foreach ($pivots as $pivot) {
            if (! array_key_exists($pivot->user_id, $listGrantedUsers)
                && array_key_exists($pivot->user_id, $userList)
            ) {
                $listGrantedUsers[$pivot->user_id]['name'] = $userList[$pivot->user_id]['name'].' '.$userList[$pivot->user_id]['lastName'];
                $listGrantedUsers[$pivot->user_id]['permission'] = $this->getSelectedPermissionUser($pivot->user_id, $projectId);
            }
        }

        return $listGrantedUsers;
    }

    /**
     * Globale Spatie-Permissions eines Users (über Rolle vererbt).
     *
     * @return array<int, string>
     */
    public function getCurrentUsersPermissions(int $userId): array
    {
        return User::query()
            ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->join('role_has_permissions', 'role_has_permissions.role_id', '=', 'roles.id')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('users.id', '=', $userId)
            ->pluck('permissions.name', 'permissions.id')->toArray();
    }

    /**
     * Project-scoped Permissions eines Users als Name-Array,
     * gekeyed by Permission-ID.
     *
     * @return array<int, string>
     */
    public function getSelectedPermissionUser(int $userId, int $projectId): array
    {
        return Permission::query()
            ->join('project_user_permissions', 'project_user_permissions.permission_id', '=', 'permissions.id')
            ->where('project_user_permissions.user_id', $userId)
            ->where('project_user_permissions.project_id', $projectId)
            ->pluck('permissions.name', 'permissions.id')->toArray();
    }

    /**
     * Project-scoped Permissions als Collection (für pluck-/keyBy-
     * Pfade in Views).
     */
    public function getSelectedPermissionUserPluck(int $userId, int $projectId): Collection
    {
        return Permission::query()
            ->join('project_user_permissions', 'project_user_permissions.permission_id', '=', 'permissions.id')
            ->where('project_user_permissions.user_id', $userId)
            ->where('project_user_permissions.project_id', $projectId)
            ->pluck('permissions.name', 'permissions.id');
    }

    /**
     * Spatie-Rollen-Namen eines Users.
     */
    public function getRoleSelectedUser(int $userId): Collection
    {
        return ModelHasRole::query()
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_id', $userId)
            ->pluck('roles.name');
    }

    /**
     * Setzt für einen User auf einem Project die übergebenen
     * Permissions. Bestehende Einträge werden vorher gelöscht
     * (Set-Semantik), Invitations zwischen den beteiligten Usern
     * werden ebenfalls aufgeräumt und neu aufgesetzt.
     *
     * @param  array<int>  $permissionIds
     */
    public function setForUserOnProject(int $userId, int $projectId, array $permissionIds, int $invitedByUserId): void
    {
        ProjectUserPermission::where('project_id', $projectId)
            ->where('user_id', $userId)
            ->delete();

        Invitation::where('project_id', $projectId)
            ->where('guest_id', $userId)
            ->delete();

        foreach ($permissionIds as $permissionId) {
            ProjectUserPermission::firstOrCreate([
                'project_id' => $projectId,
                'permission_id' => $permissionId,
                'user_id' => $userId,
            ]);
        }

        Invitation::firstOrCreate([
            'user_id' => $invitedByUserId,
            'guest_id' => $userId,
            'project_id' => $projectId,
        ]);
    }

    /**
     * Entfernt einen User vollständig aus einem Project — sowohl
     * dessen project-scoped Permissions als auch eine offene
     * Invitation.
     */
    public function removeUserFromProject(int $userId, int $projectId): void
    {
        ProjectUserPermission::where('project_id', $projectId)
            ->where('user_id', $userId)
            ->delete();

        Invitation::where('project_id', $projectId)
            ->where('guest_id', $userId)
            ->delete();
    }

    /**
     * Liefert die aktuell gesetzten Permission-IDs für einen User
     * auf einem Project. Wird vom `givePermissionToUser`-Endpoint
     * als JSON für das Frontend gebraucht.
     */
    public function getPermissionIdsForUserOnProject(int $userId, int $projectId): Collection
    {
        return ProjectUserPermission::where('user_id', $userId)
            ->where('project_id', $projectId)
            ->pluck('permission_id');
    }

    /**
     * Block D PR 2 / D.5: prüft, ob ein User auf einem konkreten
     * Project die übergebene Permission hat. Owner-Shortcut zuerst —
     * der Project-Owner darf alles auf seinem Project (Admin wird
     * eine Ebene höher über die Policy::before() abgefangen).
     *
     * Sonst Lookup in `project_user_permissions` (project-scoped Pivot).
     * Die Tabelle wird in Welle 2b auf `project_user_permissions`
     * umbenannt; diese Methode bleibt davon unberührt, weil das
     * Modell `UserHasPermission` (bzw. dessen Nachfolger) die
     * Tabellen-Bindung kapselt.
     */
    public function userHasPermissionOnProject(User $user, Project $project, PermissionName $permission): bool
    {
        if ($user->id === (int) $project->user_id) {
            return true;
        }

        $permissionId = Permission::query()
            ->where('name', $permission->value)
            ->value('id');

        if ($permissionId === null) {
            return false;
        }

        return ProjectUserPermission::query()
            ->where('user_id', $user->id)
            ->where('project_id', $project->id)
            ->where('permission_id', $permissionId)
            ->exists();
    }

    /**
     * Block D PR 2 / D.5: Liefert die Projects, die ein User in
     * seiner Liste sehen darf. Admin sieht alles (nicht
     * soft-gelöschte Projects bzw. User); Nicht-Admin sieht
     * eigene Projects plus solche, in die er eingeladen ist
     * (über `project_user_permissions`).
     *
     * Vor PR 2 lebte diese Query als private `getAllProjects()` im
     * ProjectController — über `invitations.guest_id` statt
     * `project_user_permissions.user_id`. Funktional äquivalent, weil
     * `setForUserOnProject` immer beides anlegt; mit PR 2 läuft
     * die Sicht einheitlich über die Permission-Welt.
     */
    public function listProjectsForUser(User $user): EloquentCollection
    {
        if ($user->hasRole('Admin')) {
            return Project::query()
                ->join('users', 'users.id', '=', 'projects.user_id')
                ->select('projects.*', 'users.name as user_name')
                ->whereNull('projects.deleted_at')
                ->whereNull('users.deleted_at')
                ->get();
        }

        return Project::query()
            ->join('users', 'users.id', '=', 'projects.user_id')
            ->leftJoin('project_user_permissions', 'project_user_permissions.project_id', '=', 'projects.id')
            ->select('projects.*', 'users.name as user_name')
            ->distinct()
            ->where(function ($query) use ($user) {
                $query->where('projects.user_id', $user->id)
                    ->orWhere('project_user_permissions.user_id', $user->id);
            })
            ->whereNull('projects.deleted_at')
            ->whereNull('users.deleted_at')
            ->get();
    }
}
