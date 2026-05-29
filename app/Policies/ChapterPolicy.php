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
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Chapter-Authorization.
 *
 * Owner-Logik transitiv über Project: ein User darf ein Chapter
 * editieren, wenn er Owner des dazugehörigen Projects ist
 * (`chapter->project->user_id === user->id`). Admin via before().
 *
 * Referenz: .werkbank/ADR/0013-authorization-strategie.md
 */
class ChapterPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('Admin') ? true : null;
    }

    public function view(User $user, Chapter $chapter): bool
    {
        return $user->id === (int) $chapter->project->user_id;
    }

    public function create(User $user): bool
    {
        return $user->can('add');
    }

    /**
     * Darf $user im konkreten $project ein Chapter anlegen?
     *
     * Owner-Check zusätzlich zur Permission. Schließt NF-LAR-003:
     * Permission 'add' allein reichte nicht, weil sie projekt­übergreifend
     * gilt — der Owner-Check verhindert das Anlegen in fremden Projekten.
     */
    public function createIn(User $user, Project $project): bool
    {
        return $user->id === (int) $project->user_id;
    }

    public function update(User $user, Chapter $chapter): bool
    {
        return $user->id === (int) $chapter->project->user_id;
    }

    public function delete(User $user, Chapter $chapter): bool
    {
        return $user->id === (int) $chapter->project->user_id;
    }
}
