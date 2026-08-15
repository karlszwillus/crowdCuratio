{{--
crowdCuratio - Curating together virtually
Copyright (C)2022, 2026 - berlinHistory e.V.

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

Phase 5e.1 (2026-08-15): Dashboard-Landing (Screen 09 aus Handoff v4).
Einspaltige Prioritaets-Achse, vier Sektionen in fester Reihenfolge.
Empty-States werden nicht ausgeblendet — nur „Meine Projekte" traegt
im Erstlogin-Fall einen CTA.
--}}

<x-layout rail-active="dashboard">
    <x-slot:content>
        @php
            $user = auth()->user();
            $firstName = $user?->name ?? '';
            $hour = now()->hour;
            $greeting = match (true) {
                $hour < 11             => __('good_morning'),
                $hour >= 18            => __('good_evening'),
                default                => __('good_day'),
            };

            $statusMeta = [
                'Published' => ['label' => __('status_published'), 'variant' => 'success'],
                'Draft'     => ['label' => __('status_draft'),     'variant' => 'warning'],
                'In Review' => ['label' => __('status_in_review'), 'variant' => 'info'],
            ];
        @endphp

        <div class="mx-auto max-w-6xl px-6 py-6">

            {{-- Topbar: Begruessung + Suche + Sprache + CTA. --}}
            <header class="mb-8 flex flex-wrap items-center justify-between gap-4">
                <h1 class="text-title font-semibold text-ink-900">
                    {{ $greeting }}, {{ $firstName }}
                </h1>

                <div class="flex items-center gap-3">
                    <div class="relative w-72">
                        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-ink-500">
                            <x-icon name="search" size="4"/>
                        </span>
                        <form action="{{ route('projects.index') }}" method="get">
                            <label for="dashboardSearch" class="sr-only">{{ __('search_projects') }}</label>
                            <input
                                id="dashboardSearch"
                                type="search"
                                name="q"
                                placeholder="{{ __('search_projects') }}"
                                class="block w-full rounded-md border border-line-200 bg-paper-50 py-2 pl-9 pr-3
                                       text-body text-ink-900 placeholder:text-ink-500
                                       focus:border-primary focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary"
                            />
                        </form>
                    </div>

                    @can('create', App\Models\Project::class)
                        <a
                            href="{{ route('projects.create') }}"
                            class="inline-flex items-center gap-1.5 rounded-md bg-primary px-3.5 py-2
                                   text-body font-medium text-primary-on hover:opacity-90
                                   focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-bar"
                        >
                            <x-icon name="plus" size="4"/>
                            {{ __('new_project') }}
                        </a>
                    @endcan
                </div>
            </header>

            {{-- ==================================================
                 Wiederaufnahme-Zeile (Sektion 1).
                 Regel: kein letzter Bearbeitungsort → Zeile entfällt.
                 ================================================== --}}
            @if ($resumeAt !== null)
                <a href="{{ route('projects.edit', $resumeAt->id) }}"
                   class="mb-8 flex items-center gap-4 rounded-lg border border-line-200 border-l-[3px] border-l-brand-bar
                          bg-paper-0 px-4 py-3
                          hover:border-ink-400
                          focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-bar">
                    {{-- Thumbnail 44×34 --}}
                    <span
                        class="h-[34px] w-[44px] shrink-0 rounded bg-line-100 bg-cover bg-center"
                        @if ($resumeAt->logo)
                            style="background-image: url('{{ route('image', $resumeAt->logo) }}')"
                        @endif
                        aria-hidden="true"
                    ></span>

                    <div class="min-w-0 flex-1">
                        <div class="font-mono text-mono-caps uppercase tracking-widest text-ink-500">
                            {{ __('recently_edited') }}
                        </div>
                        <div class="truncate text-body font-medium text-ink-900">
                            {{ $resumeAt->name }}
                        </div>
                    </div>

                    <span class="hidden text-caption text-ink-500 sm:inline">
                        {{ $resumeAt->updated_at?->diffForHumans() }}
                    </span>

                    <span class="inline-flex items-center rounded-md border border-line-200 px-3 py-2 text-body font-medium text-ink-900
                                 hover:bg-line-100/40">
                        {{ __('continue_editing') }}
                    </span>
                </a>
            @endif

            {{-- ==================================================
                 Meine Projekte (Sektion 2).
                 ================================================== --}}
            <section class="mb-10" aria-labelledby="section-own-projects">
                <div class="mb-3 flex items-center justify-between">
                    <div class="flex items-baseline gap-3">
                        <h2 id="section-own-projects" class="text-heading font-semibold text-ink-900">
                            {{ __('my_projects') }}
                        </h2>
                        <span class="font-mono text-mono-caps uppercase tracking-widest text-ink-500">
                            {{ $ownProjects->count() }}
                        </span>
                    </div>
                    @if ($ownProjects->isNotEmpty())
                        <a href="{{ route('projects.index') }}"
                           class="inline-flex items-center gap-1 text-body text-primary hover:opacity-80">
                            {{ __('show_all') }} <span aria-hidden="true">›</span>
                        </a>
                    @endif
                </div>

                @if ($ownProjects->isEmpty())
                    {{-- Empty-State mit CTA (einziger auf dem Dashboard). --}}
                    <div class="flex flex-col items-center justify-center gap-3 rounded-lg border-2 border-dashed border-line-200
                                bg-paper-0 px-6 py-10 text-center">
                        <div class="text-body font-medium text-ink-900">
                            {{ __('empty_own_projects_title') }}
                        </div>
                        <p class="max-w-md text-caption text-ink-500">
                            {{ __('empty_own_projects_body') }}
                        </p>
                        @can('create', App\Models\Project::class)
                            <a
                                href="{{ route('projects.create') }}"
                                class="mt-2 inline-flex items-center gap-1.5 rounded-md bg-primary px-4 py-2
                                       text-body font-medium text-primary-on hover:opacity-90
                                       focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                            >
                                <x-icon name="plus" size="4"/>
                                {{ __('create_first_project') }}
                            </a>
                        @endcan
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-[14px] sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($ownProjects as $project)
                            @include('dashboard._project-card', [
                                'project'    => $project,
                                'statusMeta' => $statusMeta,
                                'roleBadge'  => null,
                                'ownerName'  => null,
                            ])
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- ==================================================
                 Mir zugeteilt (Sektion 3).
                 ================================================== --}}
            <section class="mb-10" aria-labelledby="section-assigned-projects">
                <div class="mb-3 flex items-center justify-between">
                    <div class="flex items-baseline gap-3">
                        <h2 id="section-assigned-projects" class="text-heading font-semibold text-ink-900">
                            {{ __('assigned_to_me') }}
                        </h2>
                        <span class="font-mono text-mono-caps uppercase tracking-widest text-ink-500">
                            {{ $assignedProjects->count() }}
                        </span>
                    </div>
                    @if ($assignedProjects->isNotEmpty())
                        <a href="{{ route('projects.index') }}"
                           class="inline-flex items-center gap-1 text-body text-primary hover:opacity-80">
                            {{ __('show_all') }} <span aria-hidden="true">›</span>
                        </a>
                    @endif
                </div>

                @if ($assignedProjects->isEmpty())
                    {{-- Solo-Fall (nie zugeteilt worden, aber selbst
                         Projekte) vs. Erst-Login (weder eigene noch
                         zugeteilte) — Solo hat den ausfuehrlicheren
                         Text, Erst-Login den kuerzeren, damit die
                         Seite bei „ganz neu" nicht doppelt schreit. --}}
                    <p class="text-body text-ink-500">
                        {{ $ownProjects->isEmpty()
                            ? __('empty_assigned')
                            : __('empty_assigned_solo') }}
                    </p>
                @else
                    <div class="grid grid-cols-1 gap-[14px] sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($assignedProjects as $project)
                            @php
                                $ownerName = trim(($project->owner_name ?? '').' '.($project->owner_last_name ?? ''));
                                $badge = $project->dashboard_role_badge ?? 'reader';
                            @endphp
                            @include('dashboard._project-card', [
                                'project'    => $project,
                                'statusMeta' => $statusMeta,
                                'roleBadge'  => $badge,
                                'ownerName'  => $ownerName,
                            ])
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- ==================================================
                 Letzte Kommentare (Sektion 4).
                 ================================================== --}}
            <section aria-labelledby="section-recent-comments">
                <div class="mb-3 flex items-center justify-between">
                    <div class="flex items-baseline gap-3">
                        <h2 id="section-recent-comments" class="text-heading font-semibold text-ink-900">
                            {{ __('recent_comments') }}
                        </h2>
                        @if ($recentComments->isNotEmpty())
                            <span class="font-mono text-mono-caps uppercase tracking-widest text-ink-500">
                                {{ $recentComments->count() }} {{ __('new') ?? 'neu' }}
                            </span>
                        @endif
                    </div>
                    @if ($recentComments->isNotEmpty())
                        <a href="{{ route('all.comments') }}"
                           class="inline-flex items-center gap-1 text-body text-primary hover:opacity-80">
                            {{ __('show_all') }} <span aria-hidden="true">›</span>
                        </a>
                    @endif
                </div>

                @if ($recentComments->isEmpty())
                    <p class="text-body text-ink-500">
                        {{ ($ownProjects->isEmpty() && $assignedProjects->isEmpty())
                            ? __('empty_no_comments_yet')
                            : __('empty_recent_comments') }}
                    </p>
                @else
                    <div class="overflow-hidden rounded-lg border border-line-200 bg-paper-0">
                        @foreach ($recentComments as $comment)
                            @include('dashboard._comment-row', ['comment' => $comment])
                        @endforeach
                    </div>
                @endif
            </section>

        </div>
    </x-slot:content>
</x-layout>
