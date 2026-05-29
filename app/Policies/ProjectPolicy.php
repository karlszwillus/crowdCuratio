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
use App\Support\PermissionName;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Project-Authorization.
 *
 * Logik: Admin darf alles. Sonst muss der User der Eigentümer des
 * Projects sein (`projects.user_id`). Project-scoped Permissions aus
 * der `user_has_permissions`-Tabelle werden hier noch nicht
 * berücksichtigt — das gehört zu ADR-0005
 * (Spatie-Permission-Modell auf Standard versöhnen) und wird in
 * Phase 3/4 nachgezogen.
 *
 * Referenz: .werkbank/ADR/0013-authorization-strategie.md
 */
class ProjectPolicy
{
    use HandlesAuthorization;

    /**
     * Admin-Shortcut: ein User mit der Rolle "Admin" darf alles.
     * Wird vor jeder anderen Methode dieser Policy ausgewertet.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('Admin') ? true : null;
    }

    /**
     * Jeder eingeloggte User darf die Project-Liste sehen.
     * Die Liste selbst wird über Spatie-Permission-Middleware bzw.
     * im ProjectController gefiltert (Owner / Eingeladene).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Owner darf sein Project sehen.
     * Eingeladene User (user_has_permissions) wird die Logik
     * mit ADR-0005 nachziehen.
     */
    public function view(User $user, Project $project): bool
    {
        return $user->id === (int) $project->user_id;
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
}
