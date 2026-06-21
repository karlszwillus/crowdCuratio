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

use App\Models\Image;
use App\Models\User;
use App\Support\PermissionName;

/**
 * Image-Authorization. Block E.7b Sub-Welle 3 (ADR-0022).
 *
 * Image hängt nicht direkt am Entry, sondern über die Gallery
 * (gallery_id-FK). `Image::project()` delegiert deshalb an
 * `Gallery::project()`. Wenn das Image noch keiner Gallery
 * zugeordnet ist, liefert project() null — Zugriff verweigert.
 */
class ImagePolicy extends OwnerScopedPolicy
{
    public function view(User $user, Image $image): bool
    {
        return $this->checkViaProject($user, $image->project(), PermissionName::VIEW);
    }

    public function update(User $user, Image $image): bool
    {
        return $this->checkViaProject($user, $image->project(), PermissionName::EDIT);
    }

    public function delete(User $user, Image $image): bool
    {
        return $this->checkViaProject($user, $image->project(), PermissionName::DELETE);
    }

    public function comment(User $user, Image $image): bool
    {
        return $this->checkViaProject($user, $image->project(), PermissionName::COMMENT);
    }
}
