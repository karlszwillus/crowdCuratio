{{--
Dashboard-Sektions-Rendering (Phase-5-Backlog #70 / Phase 5e.1).

Erwartet: $ownProjects, $assignedProjects, $recentComments, $resumeAt
sowie aus dem Aussenlayout $statusMeta. Wird sowohl im synchronen
Kontext (falls jemals gebraucht) als auch aus der Volt-Component
`dashboard-sections` heraus per @include gerendert.
--}}

@php
    // Status-Meta-Fallback, falls das Partial ausserhalb der
    // Dashboard-View eingeschleust wird.
    $statusMeta ??= [
        'Published' => ['label' => __('status_published'), 'variant' => 'success'],
        'Draft'     => ['label' => __('status_draft'),     'variant' => 'warning'],
        'In Review' => ['label' => __('status_in_review'), 'variant' => 'info'],
    ];
@endphp

{{-- Wiederaufnahme-Zeile. Kein letzter Bearbeitungsort -> Zeile
     entfaellt komplett, kein Platzhalter (Designer-Regel). --}}
@if ($resumeAt !== null)
    <a href="{{ route('projects.edit', $resumeAt->id) }}"
       class="mb-8 flex items-center gap-4 rounded-lg border border-line-200 border-l-[3px] border-l-brand-bar
              bg-paper-0 px-4 py-3
              hover:border-ink-400
              focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-bar">
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

{{-- Meine Projekte. --}}
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

{{-- Mir zugeteilt. --}}
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

{{-- Letzte Kommentare. --}}
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
