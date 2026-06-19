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
 * Basisklasse für project-scoped Policies.
 *
 * Block E / Welle E.7a. Bietet drei wiederverwendbare Bausteine,
 * die alle Content-Policies (Chapter, Entry, Text, Image, Gallery,
 * Audiovisual) heute oder in Zukunft brauchen:
 *
 *  1. **Admin-Shortcut via `before()`** — der Spatie-Admin darf alles,
 *     unabhängig vom Modell.
 *  2. **Service-Injection** für den `ProjectPermissionService`, der
 *     Owner-Shortcut und Pivot-Lookup einheitlich liefert.
 *  3. **`check()`-Helper** — eine Methode, die je Subklasse mit dem
 *     resolved `Project` und der gewünschten `PermissionName` aufgerufen
 *     wird. Die Subklasse trägt die typisierten Policy-Methoden
 *     (`view(User, ConcreteModel)` etc.) und kümmert sich darum, das
 *     Project aus dem Modell zu extrahieren — die `media_content`-
 *     Polymorphie (Text/Image/Gallery/Audiovisual) lässt heute keine
 *     gemeinsame `projectOf()`-Signatur zu.
 *
 * Wenn das Schema später eine einheitliche `project()`-Relation pro
 * Content-Modell bekommt (geplant in Phase 5 mit der `media_content`-
 * Bereinigung), kann diese Klasse um eine abstract `projectOf()`-
 * Methode plus Default-`view`/`update`/`delete`/`comment` erweitert
 * werden.
 */
abstract class OwnerScopedPolicy
{
    use HandlesAuthorization;

    public function __construct(
        protected readonly ProjectPermissionService $permissions,
    ) {}

    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole(RoleName::ADMIN->value) ? true : null;
    }

    /**
     * Project-scoped Permission-Prüfung. Owner-Shortcut und Pivot-
     * Lookup macht der Service intern (siehe
     * `ProjectPermissionService::userHasPermissionOnProject`).
     */
    protected function check(User $user, Project $project, PermissionName $permission): bool
    {
        return $this->permissions->userHasPermissionOnProject($user, $project, $permission);
    }
}
