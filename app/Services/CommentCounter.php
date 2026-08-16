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

declare(strict_types=1);

namespace App\Services;

use App\Models\Comment;
use App\Models\Project;
use App\Models\ProjectUserPermission;
use App\Models\User;
use App\Support\CommentStatus;
use App\Support\PermissionName;
use App\Support\RoleName;
use Spatie\Permission\Models\Permission;

/**
 * Phase 5x.10: Zaehler fuer offene Kommentare, verwendet vom Rail-Badge
 * (linke Navigation). „Offen" bedeutet Status open ODER in_progress —
 * die beiden Zustaende, die noch Handlung erwarten. Erledigte und
 * abgelehnte zaehlen nicht.
 *
 * Der Query laeuft einmal pro Page-Render und wird intern statisch
 * gecacht (memoization pro Request), weil die Rail auf jeder Editor-
 * Route mitgerendert wird und mehrfach denselben Wert braeuchte.
 */
class CommentCounter
{
    /** @var array<int,int> */
    private static array $cache = [];

    public static function openCountForUser(?User $user): int
    {
        if ($user === null) {
            return 0;
        }

        if (array_key_exists($user->id, self::$cache)) {
            return self::$cache[$user->id];
        }

        // Phase 5x-Followup nach Karls Feedback: der Badge zaehlt nur
        // Kommentare in Projekten, in denen der User selbst
        // kommentieren darf — Leserechte-Projekte bleiben aus dem
        // Zaehler raus. Admin sieht wie gewohnt projektuebergreifend.
        $projectIds = $user->hasRole(RoleName::ADMIN->value)
            ? Project::query()->pluck('id')->all()
            : self::projectIdsWithCommentRight($user);

        if ($projectIds === []) {
            return self::$cache[$user->id] = 0;
        }

        return self::$cache[$user->id] = Comment::query()
            ->whereIn('project_id', $projectIds)
            ->whereIn('status', [
                CommentStatus::OPEN->value,
                CommentStatus::IN_PROGRESS->value,
            ])
            ->whereNull('parent_id')
            ->count();
    }

    /**
     * Offene Root-Kommentare (open + in_progress) pro commentable-Modell
     * — vom Sidebar-Baum-Zaehler pro Chapter/Entry verwendet.
     *
     * Antworten (parent_id != null) zaehlen nicht mit, weil der Root-
     * Status massgeblich ist (siehe CommentService::setCommentStatus
     * und Briefing § 5).
     */
    public static function openCountForCommentable(string $commentableType, int $commentableId): int
    {
        $key = $commentableType.':'.$commentableId;
        if (isset(self::$commentableCache[$key])) {
            return self::$commentableCache[$key];
        }

        return self::$commentableCache[$key] = Comment::query()
            ->where('commentable_type', $commentableType)
            ->where('commentable_id', $commentableId)
            ->whereIn('status', [
                CommentStatus::OPEN->value,
                CommentStatus::IN_PROGRESS->value,
            ])
            ->whereNull('parent_id')
            ->count();
    }

    /** @var array<string,int> */
    private static array $commentableCache = [];

    /**
     * Projekte, in denen der User `comment`-Recht hat:
     *  - eigene Projekte (Owner darf implizit alles)
     *  - fremde Projekte mit `comment`-Permission im Pivot
     *    `project_user_permissions`.
     *
     * @return list<int>
     */
    private static function projectIdsWithCommentRight(User $user): array
    {
        $own = Project::query()
            ->where('user_id', $user->id)
            ->pluck('id')
            ->all();

        $commentPermissionId = Permission::query()
            ->where('name', PermissionName::COMMENT->value)
            ->value('id');

        $invitedWithComment = $commentPermissionId
            ? ProjectUserPermission::query()
                ->where('user_id', $user->id)
                ->where('permission_id', $commentPermissionId)
                ->pluck('project_id')
                ->all()
            : [];

        return array_values(array_unique(array_merge($own, $invitedWithComment)));
    }
}
