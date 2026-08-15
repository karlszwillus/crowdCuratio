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

namespace App\Http\Controllers;

use App\Models\Audiovisual;
use App\Models\Chapter;
use App\Models\Comment;
use App\Models\Entry;
use App\Models\Gallery;
use App\Models\Image;
use App\Models\Project;
use App\Models\Text;
use App\Models\User;
use App\Support\PermissionName;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Dashboard-Landing-Sicht (Phase 5e.1, Screen 09 aus Handoff v4).
 *
 * Vier Sektionen in fester Reihenfolge, einspaltige Prioritaets-Achse:
 *   1. Wiederaufnahme-Zeile (zuletzt bearbeiteter Block)
 *   2. Meine Projekte (Owner)
 *   3. Mir zugeteilt (per project_user_permissions eingeladen)
 *   4. Letzte Kommentare (30-Tage-Fenster, max 5)
 *
 * Regel aus dem Designer-Briefing:
 *   - Sektionen liefern max. 6 Karten, sortiert `updated_at DESC`
 *   - Rest hinter „Alle anzeigen"
 *   - Empty-States werden nicht ausgeblendet — die Seiten-Struktur
 *     bleibt konstant
 *   - Nur „Meine Projekte" hat einen CTA-Empty-State (Erst-Anlage)
 */
class DashboardController extends Controller
{
    private const SECTION_LIMIT = 6;

    private const COMMENTS_LIMIT = 5;

    private const COMMENTS_WINDOW_DAYS = 30;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function __invoke(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $ownProjects = $this->ownProjects($user);
        $assignedProjects = $this->assignedProjects($user);
        $recentComments = $this->recentComments($user);
        $resumeAt = $this->resumeAt($user, $ownProjects, $assignedProjects);

        return view('dashboard', [
            'ownProjects' => $ownProjects,
            'assignedProjects' => $assignedProjects,
            'recentComments' => $recentComments,
            'resumeAt' => $resumeAt,
        ]);
    }

    /**
     * „Meine Projekte" — Projekte, deren Owner der/die User:in ist.
     * Sortiert nach `updated_at DESC`, max. sechs Karten.
     *
     * @return Collection<int, Project>
     */
    private function ownProjects(User $user): Collection
    {
        return Project::query()
            ->where('user_id', $user->id)
            ->withCount('chapters')
            ->orderByDesc('updated_at')
            ->limit(self::SECTION_LIMIT)
            ->get();
    }

    /**
     * „Mir zugeteilt" — Projekte, in denen der/die User:in via
     * `project_user_permissions` eingeladen ist, aber nicht Owner.
     *
     * @return Collection<int, Project>
     */
    private function assignedProjects(User $user): Collection
    {
        return Project::query()
            ->join('project_user_permissions as pup', 'pup.project_id', '=', 'projects.id')
            ->join('users as owners', 'owners.id', '=', 'projects.user_id')
            ->where('pup.user_id', $user->id)
            ->where('projects.user_id', '!=', $user->id)
            ->select(
                'projects.*',
                'owners.name as owner_name',
                'owners.last_name as owner_last_name',
            )
            ->withCount('chapters')
            ->groupBy('projects.id')
            ->orderByDesc('projects.updated_at')
            ->limit(self::SECTION_LIMIT)
            ->get()
            ->map(function (Project $project) use ($user): Project {
                // Rolle-Badge-Wert: hat der User `edit` auf dem Projekt?
                // Wenn ja → Editor:in, sonst Leserecht. (Owner ist hier
                // ausgeschlossen, siehe where-Klausel.)
                $canEdit = $project->user_has_permission_edit ?? $this->canEditOnProject($user, $project);
                $project->setAttribute('dashboard_role_badge', $canEdit ? 'editor' : 'reader');

                return $project;
            });
    }

    private function canEditOnProject(User $user, Project $project): bool
    {
        return \DB::table('project_user_permissions')
            ->join('permissions', 'permissions.id', '=', 'project_user_permissions.permission_id')
            ->where('project_user_permissions.user_id', $user->id)
            ->where('project_user_permissions.project_id', $project->id)
            ->where('permissions.name', PermissionName::EDIT->value)
            ->exists();
    }

    /**
     * „Letzte Kommentare" — 30-Tage-Fenster, max. fünf Einträge.
     * Berücksichtigt Kommentare auf Content in Projekten, in denen
     * die aktuelle User:in Owner oder eingeladen ist (inkl. Leserecht).
     *
     * @return Collection<int, Comment>
     */
    private function recentComments(User $user): Collection
    {
        $projectIds = $this->accessibleProjectIds($user);
        if ($projectIds === []) {
            return collect();
        }

        return Comment::query()
            ->where('created_at', '>=', Carbon::now()->subDays(self::COMMENTS_WINDOW_DAYS))
            ->whereIn('commentable_type', [
                Project::class,
                Chapter::class,
                Entry::class,
                Text::class,
                Image::class,
                Gallery::class,
                Audiovisual::class,
            ])
            // Eager-Load polymorph plus dessen project-Kette, damit
            // commentBelongsToAccessibleProject() nicht lazy-loadet
            // (AppServiceProvider hat Model::shouldBeStrict aktiv).
            ->with([
                'user',
                'commentable' => function ($morphTo) {
                    $morphTo->morphWith([
                        Chapter::class => ['project'],
                        Entry::class => ['chapter.project'],
                    ]);
                },
            ])
            ->orderByDesc('created_at')
            ->limit(self::COMMENTS_LIMIT * 3) // Buffer wg. Filterung
            ->get()
            ->filter(fn (Comment $c) => $this->commentBelongsToAccessibleProject($c, $projectIds))
            ->take(self::COMMENTS_LIMIT)
            ->values();
    }

    /**
     * @return array<int, int>
     */
    private function accessibleProjectIds(User $user): array
    {
        $own = Project::query()
            ->where('user_id', $user->id)
            ->pluck('id')
            ->all();

        $assigned = \DB::table('project_user_permissions')
            ->where('user_id', $user->id)
            ->distinct()
            ->pluck('project_id')
            ->all();

        return array_values(array_unique(array_merge($own, $assigned)));
    }

    /**
     * @param  array<int, int>  $projectIds
     */
    private function commentBelongsToAccessibleProject(Comment $comment, array $projectIds): bool
    {
        $target = $comment->commentable;
        if ($target === null) {
            return false;
        }

        $projectId = match (true) {
            $target instanceof Project => $target->id,
            $target instanceof Chapter => $target->project_id ?? null,
            $target instanceof Entry => optional($target->chapter)->project_id,
            default => optional(method_exists($target, 'project') ? $target->project() : null)?->id,
        };

        return $projectId !== null && in_array((int) $projectId, $projectIds, true);
    }

    /**
     * Wiederaufnahme-Zeile: zuletzt bearbeiteter Block der User:in
     * über eigene und zugeteilte Projekte hinweg. Wenn nichts
     * bearbeitet wurde, gibt der Helper `null` zurück — die Zeile
     * entfällt dann komplett (kein Platzhalter, Briefing-Regel).
     *
     * MVP-Umsetzung (2026-08-15): wir nehmen das Projekt mit dem
     * spätesten `updated_at`, das die User:in bearbeiten darf.
     * Block-Level-Auflösung (Text/Bild/Content mit updated_at)
     * folgt in einem Followup — dafür braucht es eine
     * ActivityLog-Auswertung, die zusätzliche Abhängigkeiten
     * einführt und den Scope sprengt.
     *
     * @param  Collection<int, Project>  $own
     * @param  Collection<int, Project>  $assigned
     */
    private function resumeAt(User $user, Collection $own, Collection $assigned): ?Project
    {
        $candidate = $own->merge($assigned)->sortByDesc('updated_at')->first();

        return $candidate;
    }
}
