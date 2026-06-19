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

use App\Models\Chapter;
use App\Models\Entry;
use App\Models\User;
use App\Support\PermissionName;

/**
 * Entry-Authorization.
 *
 * Block E / Welle E.7a: project-scoped via `OwnerScopedPolicy`,
 * transitiv über Chapter → Project. Owner-Shortcut und Pivot-Lookup
 * macht der Service. Vorher nur reiner Owner-Check ohne Service.
 *
 * Referenz: .werkbank/ADR/0013-authorization-strategie.md
 */
class EntryPolicy extends OwnerScopedPolicy
{
    public function view(User $user, Entry $entry): bool
    {
        return $this->check($user, $entry->chapter->project, PermissionName::VIEW);
    }

    /**
     * Globale Permission, kein Project-Kontext — bleibt wie vorher.
     */
    public function create(User $user): bool
    {
        return $user->can(PermissionName::ADD);
    }

    /**
     * Darf $user im konkreten $chapter einen Entry anlegen?
     */
    public function createIn(User $user, Chapter $chapter): bool
    {
        return $this->check($user, $chapter->project, PermissionName::ADD);
    }

    public function update(User $user, Entry $entry): bool
    {
        return $this->check($user, $entry->chapter->project, PermissionName::EDIT);
    }

    public function delete(User $user, Entry $entry): bool
    {
        return $this->check($user, $entry->chapter->project, PermissionName::DELETE);
    }
}
