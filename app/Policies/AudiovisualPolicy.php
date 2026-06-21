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

use App\Models\Audiovisual;
use App\Models\User;
use App\Support\PermissionName;

/**
 * Audiovisual-Authorization. Block E.7b Sub-Welle 3 (ADR-0022).
 *
 * Audiovisual hängt über den MediaContent-Pivot direkt am Entry —
 * `Audiovisual::project()` navigiert Entry → Chapter → Project.
 */
class AudiovisualPolicy extends OwnerScopedPolicy
{
    public function view(User $user, Audiovisual $audiovisual): bool
    {
        return $this->checkViaProject($user, $audiovisual->project(), PermissionName::VIEW);
    }

    public function update(User $user, Audiovisual $audiovisual): bool
    {
        return $this->checkViaProject($user, $audiovisual->project(), PermissionName::EDIT);
    }

    public function delete(User $user, Audiovisual $audiovisual): bool
    {
        return $this->checkViaProject($user, $audiovisual->project(), PermissionName::DELETE);
    }

    public function comment(User $user, Audiovisual $audiovisual): bool
    {
        return $this->checkViaProject($user, $audiovisual->project(), PermissionName::COMMENT);
    }
}
