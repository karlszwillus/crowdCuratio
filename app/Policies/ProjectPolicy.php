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

use App\Models\Project;
use App\Models\User;
use App\Services\ProjectPermissionService;
use App\Support\PermissionName;
use App\Support\RoleName;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Project-Authorization.
 *
 * Block D PR 2 / D.6: project-scoped via `ProjectPermissionService`.
 * Owner darf weiterhin alles (über Service), Admin via `before()`,
 * Eingeladene mit konkreter Permission auf dem Project ebenfalls.
 * Vor PR 2 prüften `view`/`comment` nur Owner bzw. globale
 * `can(COMMENT)`-Permission — Eingeladene fielen durch.
 *
 * Referenz: .werkbank/ADR/0005-permission-modell-harmonisieren.md
 */
class ProjectPolicy
{
    use HandlesAuthorization;

    public function __construct(
        private readonly ProjectPermissionService $permissions,
    ) {}

    /**
     * Admin-Shortcut: ein User mit der Rolle "Admin" darf alles.
     * Wird vor jeder anderen Methode dieser Policy ausgewertet.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole(RoleName::ADMIN->value) ? true : null;
    }

    /**
     * Block D / D.4-Hotfix: User braucht die `view`-Permission, um
     * die Project-Liste aufzurufen. Reproduziert die Semantik der
     * früheren `permission:view`-Route-Middleware exakt — vor dem
     * Hotfix ließ die Methode jeden Auth-User durch, was eine
     * Regression war (`ProjectController::index` läuft anschließend
     * in `getAllProjects()`, das Annahmen über die User-Rolle
     * trifft und sonst 500 läuft).
     *
     * Die feinere, project-scoped Sicht (User darf nur Projects in
     * der Liste sehen, in denen er Owner oder eingeladen ist) macht
     * heute weiterhin `getAllProjects()` per Query. Das wandert in
     * Block D / PR 2 in einen `ProjectPermissionService` — siehe
     * `.werkbank/04-plan/phase-4-block-d.md` und ADR-0005.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::VIEW);
    }

    /**
     * Block D PR 2 / D.6: project-scoped. Owner ODER Eingeladener
     * mit `view`-Permission auf dem konkreten Project. Admin via
     * `before()`. Service kapselt die Owner-Check-und-Pivot-Lookup-
     * Logik.
     */
    public function view(User $user, Project $project): bool
    {
        return $this->permissions->userHasPermissionOnProject($user, $project, PermissionName::VIEW);
    }

    /**
     * Jeder eingeloggte User mit der "add"-Permission darf neue
     * Projects anlegen. Die Permission-Middleware auf der Route
     * (`permission:add`) prüft das bereits — hier nur als
     * Defense-in-Depth.
     */
    public function create(User $user): bool
    {
        return $user->can(PermissionName::ADD);
    }

    /**
     * Nur der Project-Owner darf updaten. Admin-Shortcut über before().
     */
    public function update(User $user, Project $project): bool
    {
        return $user->id === (int) $project->user_id;
    }

    /**
     * Nur der Project-Owner darf löschen. Admin-Shortcut über before().
     */
    public function delete(User $user, Project $project): bool
    {
        return $user->id === (int) $project->user_id;
    }

    /**
     * Restore eines soft-deleted Project. Nur Owner (Admin via before()).
     */
    public function restore(User $user, Project $project): bool
    {
        return $user->id === (int) $project->user_id;
    }

    /**
     * Endgültiges Löschen ist heute nur Admin-Aufgabe — Owner kann
     * über `delete()` soft-deleten. Diese Methode existiert für
     * Konsistenz, gibt aber false zurück (Admin greift über before()).
     */
    public function forceDelete(User $user, Project $project): bool
    {
        return false;
    }

    /**
     * Veröffentlichen eines Projects. Owner und Admin.
     */
    public function publish(User $user, Project $project): bool
    {
        return $user->id === (int) $project->user_id;
    }

    /**
     * Block D PR 2 / D.6: project-scoped. Owner ODER Eingeladener
     * mit `comment`-Permission auf dem konkreten Project. Admin
     * via `before()`. Vor PR 2 war das global (`can(COMMENT)`),
     * was jeden User mit globaler comment-Permission durch jedes
     * fremde Project kommentieren ließ.
     */
    public function comment(User $user, Project $project): bool
    {
        return $this->permissions->userHasPermissionOnProject($user, $project, PermissionName::COMMENT);
    }
}
