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

namespace App\Policies;

use App\Models\Comment;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectPermissionService;
use App\Support\PermissionName;
use App\Support\RoleName;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Comment-Authorization nach BRIEFING-kommentare § 3 (Phase 5x.7).
 *
 * Regeln aus dem Briefing, alle serverseitig zu erzwingen — nicht
 * nur im Blade auszublenden:
 *
 *   - **Bearbeiten** ist strikt auf den Autor beschraenkt. Auch
 *     Projekt-Owner bearbeiten keine fremden Texte.
 *   - **Loeschen** hat zwei Wege:
 *       Owner    → alle Kommentare (Hard-Delete + Kaskade auf Antworten)
 *       Autor    → nur eigene, und nur solange sie keine Antworten haben
 *       Reader   → nie
 *       Sonst    → nie
 *   - **Status-Aenderung** braucht die `comment`-Permission auf dem
 *     Projekt. Antworten haben keinen eigenen Status; ein Setzen auf
 *     einer Antwort ist stumm zu ignorieren (kein 403).
 *   - **Antworten** brauchen die `comment`-Permission auf dem Projekt
 *     — genau wie das Anlegen eines Top-Level-Kommentars.
 *
 * Der Owner-Sonderweg im Delete deckt den fachlichen Fall „Ich lasse
 * den Kommentar nicht stehen". Autor-only fuers Bearbeiten spiegelt
 * die Konvention aus dem Slack-/Github-Muster.
 */
class CommentPolicy
{
    use HandlesAuthorization;

    public function __construct(
        private readonly ProjectPermissionService $permissions,
    ) {}

    /**
     * Admin darf alles (analog OwnerScopedPolicy).
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole(RoleName::ADMIN->value) ? true : null;
    }

    /**
     * Bearbeiten: strikt Autor-only. Der Owner des Projekts darf
     * fremde Kommentare NICHT bearbeiten — er darf sie loeschen.
     */
    public function update(User $user, Comment $comment): bool
    {
        return $user->id === (int) $comment->user_id;
    }

    /**
     * Loeschen: Owner des Projekts darf alle Kommentare (Hard-Delete
     * inkl. Antworten). Autor darf den eigenen Kommentar loeschen,
     * aber nur solange er noch keine Antwort hat (sonst bleibt der
     * Text als Thread-Kontext stehen).
     */
    public function delete(User $user, Comment $comment): bool
    {
        $project = $this->resolveProject($comment);
        if ($project === null) {
            return false;
        }

        // Owner-Weg: alle Kommentare.
        if ($user->id === (int) $project->user_id) {
            return true;
        }

        // Autor-Weg: nur eigen, nur ohne Antworten.
        if ($user->id === (int) $comment->user_id) {
            return $comment->replies()->count() === 0;
        }

        return false;
    }

    /**
     * Status-Aenderung: Projekt-Owner darf immer, Autor:in darf den
     * eigenen Kommentar setzen. Alle anderen — auch Reviewer:innen mit
     * `comment`-Recht — koennen keinen fremden Status umlegen
     * (Phase 5x-Followup nach Karls Feedback).
     *
     * Zusaetzlich blockt der Service Aenderungen auf Antwort-Kommentaren
     * (Status haengt am Wurzel-Kommentar) — die Policy laesst den
     * Aufruf zu, der Service ignoriert ihn stumm.
     */
    public function changeStatus(User $user, Comment $comment): bool
    {
        $project = $this->resolveProject($comment);
        if ($project === null) {
            return false;
        }

        // Owner-Weg.
        if ($user->id === (int) $project->user_id) {
            return true;
        }

        // Autor:in-Weg.
        return $user->id === (int) $comment->user_id;
    }

    /**
     * Antworten: analog Top-Level-Kommentar-Anlage — `comment`-
     * Permission auf dem Projekt.
     */
    public function reply(User $user, Comment $comment): bool
    {
        $project = $this->resolveProject($comment);
        if ($project === null) {
            return false;
        }

        return $this->permissions->userHasPermissionOnProject(
            $user,
            $project,
            PermissionName::COMMENT,
        );
    }

    /**
     * Loest zu einem Comment das zugehoerige Project auf. Nutzt
     * einerseits `project_id` (schnell, wenn gesetzt), andererseits
     * die polymorphe commentable-Kette (Fallback).
     */
    private function resolveProject(Comment $comment): ?Project
    {
        if ($comment->project_id !== null) {
            return Project::find($comment->project_id);
        }

        $target = $comment->commentable;
        if ($target instanceof Project) {
            return $target;
        }

        if (method_exists($target, 'project')) {
            $result = $target->project();

            return $result instanceof Project ? $result : null;
        }

        return null;
    }
}
