<?php
/**
crowdCuratio - Curating together virtually
Copyright (C)2022 - berlinHistory e.V.

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

use App\Models\Entry;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Entry-Authorization.
 *
 * Owner-Logik transitiv über Chapter → Project. Admin via before().
 *
 * Referenz: .werkbank/ADR/0013-authorization-strategie.md
 */
class EntryPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('Admin') ? true : null;
    }

    public function view(User $user, Entry $entry): bool
    {
        return $user->id === (int) $entry->chapter->project->user_id;
    }

    public function create(User $user): bool
    {
        return $user->can('add');
    }

    public function update(User $user, Entry $entry): bool
    {
        return $user->id === (int) $entry->chapter->project->user_id;
    }

    public function delete(User $user, Entry $entry): bool
    {
        return $user->id === (int) $entry->chapter->project->user_id;
    }
}
