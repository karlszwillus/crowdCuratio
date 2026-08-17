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

use App\Models\Audiovisual;
use App\Models\Chapter;
use App\Models\Comment;
use App\Models\Entry;
use App\Models\Gallery;
use App\Models\Image;
use App\Models\Project;
use App\Models\ProjectUserPermission;
use App\Models\Text;
use App\Models\User;
use App\Support\PermissionName;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Lazy;
use Livewire\Volt\Component;

/**
 * Volt-Component fuer die vier Dashboard-Sektionen (Phase-5-Backlog
 * #70). Mit #[Lazy] rendert Livewire beim ersten Request nur die
 * placeholder()-Ausgabe (Skelett-Grid), lauedt die Sektionen im
 * zweiten Round-Trip nach.
 *
 * Der DashboardController delegiert die Daten-Feeds vollstaendig
 * an diese Component; er selbst rendert nur den Chrome-Kontext
 * (Begruessung, Suche, „+ Neues Projekt").
 */
new #[Lazy] class extends Component
{
    private const SECTION_LIMIT = 6;

    private const COMMENTS_LIMIT = 5;

    private const COMMENTS_WINDOW_DAYS = 30;

    public function placeholder(): string
    {
        return view('dashboard._skeleton')->render();
    }

    public function with(): array
    {
        /** @var User $user */
        $user = auth()->user();

        $ownProjects = $this->ownProjects($user);
        $assignedProjects = $this->assignedProjects($user);
        $recentComments = $this->recentComments($user);
        $resumeAt = $this->resumeAt($ownProjects, $assignedProjects);

        return compact('ownProjects', 'assignedProjects', 'recentComments', 'resumeAt');
    }

    private function ownProjects(User $user): \Illuminate\Support\Collection
    {
        return Project::query()
            ->where('user_id', $user->id)
            ->withCount('chapters')
            ->orderByDesc('updated_at')
            ->limit(self::SECTION_LIMIT)
            ->get();
    }

    private function assignedProjects(User $user): \Illuminate\Support\Collection
    {
        // Bug 2026-08-17: der frühere `join(project_user_permissions)` + `groupBy('projects.id')`
        // brach auf MySQL mit `ONLY_FULL_GROUP_BY` (Staging-Default), weil `projects.*`
        // und `owners.name` nicht in der GROUP-BY-Klausel standen. Wir entkoppeln die
        // Duplikat-Vermeidung: eine Subquery zieht distinct Project-IDs aus dem Pivot,
        // die Hauptquery joint dann nur noch 1:1 auf `users` als Owner.
        $projectIds = ProjectUserPermission::query()
            ->where('user_id', $user->id)
            ->distinct()
            ->pluck('project_id');

        return Project::query()
            ->join('users as owners', 'owners.id', '=', 'projects.user_id')
            ->whereIn('projects.id', $projectIds)
            ->where('projects.user_id', '!=', $user->id)
            ->select(
                'projects.*',
                'owners.name as owner_name',
                'owners.last_name as owner_last_name',
            )
            ->withCount('chapters')
            ->orderByDesc('projects.updated_at')
            ->limit(self::SECTION_LIMIT)
            ->get()
            ->map(function (Project $project) use ($user): Project {
                $canEdit = $this->canEditOnProject($user, $project);
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

    private function recentComments(User $user): \Illuminate\Support\Collection
    {
        $projectIds = $this->accessibleProjectIds($user);
        if ($projectIds === []) {
            return collect();
        }

        return Comment::query()
            ->where('created_at', '>=', Carbon::now()->subDays(self::COMMENTS_WINDOW_DAYS))
            ->whereIn('commentable_type', [
                Project::class, Chapter::class, Entry::class,
                Text::class, Image::class, Gallery::class, Audiovisual::class,
            ])
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
            ->limit(self::COMMENTS_LIMIT * 3)
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
        $own = Project::query()->where('user_id', $user->id)->pluck('id')->all();
        $assigned = \DB::table('project_user_permissions')
            ->where('user_id', $user->id)->distinct()->pluck('project_id')->all();

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

    private function resumeAt(\Illuminate\Support\Collection $own, \Illuminate\Support\Collection $assigned): ?Project
    {
        return $own->merge($assigned)->sortByDesc('updated_at')->first();
    }
};
?>
<div>
    @include('dashboard._sections', [
        'ownProjects' => $ownProjects,
        'assignedProjects' => $assignedProjects,
        'recentComments' => $recentComments,
        'resumeAt' => $resumeAt,
    ])
</div>
