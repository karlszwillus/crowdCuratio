<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2022, 2026 - berlinHistory e.V.

See LICENSE.
 */

use App\Models\Project;
use App\Services\SourceMigrationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

/**
 * Q4-Etappe 3 / C0-8b · GUI-Wrapper fuer den Migrations-Assistenten.
 *
 * Aufruf: `/admin/sources/migration` (Admin-only via Route-Middleware).
 * Flow: Projekt auswaehlen → Dry-Run laeuft automatisch → Report
 * anzeigen → optional Commit mit Bestaetigung. Rollback-Historie
 * unten aus `sources_backup`.
 */
new class extends Component
{
    public ?int $projectId = null;

    /** @var array<string, mixed>|null */
    public ?array $report = null;

    public bool $showCommitModal = false;

    public string $flash = '';

    public function mount(): void
    {
        // Defense-in-depth zur Route-Middleware — Command laeuft
        // pro Projekt einzeln und ist eine kritische Datenumstellung.
        abort_unless(auth()->user()?->hasRole('Admin'), 403);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Project>
     */
    #[Computed]
    public function projects(): \Illuminate\Support\Collection
    {
        return Project::query()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    #[Computed]
    public function history(): \Illuminate\Support\Collection
    {
        // `rows` ist bei MySQL 8 ein reserviertes Wort (fenster-
        // funktions-Clause), deshalb Alias `row_count`.
        return DB::table('sources_backup')
            ->select('run_key', 'project_id', DB::raw('MIN(backup_run_at) as run_at'), DB::raw('COUNT(*) as row_count'))
            ->groupBy('run_key', 'project_id')
            ->orderByDesc('run_at')
            ->limit(20)
            ->get();
    }

    /**
     * Selektiert ein Projekt und triggert automatisch einen Dry-Run —
     * damit die Admin-Sicht ohne extra Klick anzeigt, was ein Commit
     * tun wuerde.
     */
    public function selectProject(int $projectId): void
    {
        $this->projectId = $projectId;
        $this->report = app(SourceMigrationService::class)->runFor($projectId, dryRun: true);
        $this->flash = '';
    }

    public function refreshDryRun(): void
    {
        if ($this->projectId !== null) {
            $this->report = app(SourceMigrationService::class)->runFor($this->projectId, dryRun: true);
            $this->flash = '';
        }
    }

    public function openCommitModal(): void
    {
        if ($this->projectId === null) {
            return;
        }
        // Q4-Etappe 3 / C0-8a Erweiterung: Commit ist auch dann
        // sinnvoll, wenn nur AV-Rows backgefillt werden (candidates=0,
        // av_pending>0). Erst wenn nichts zu tun ist, blocken.
        $hasWork = ($this->report['candidates'] ?? 0) > 0
            || ($this->report['av_pending'] ?? 0) > 0;
        if (! $hasWork) {
            return;
        }
        $this->showCommitModal = true;
    }

    public function cancelCommit(): void
    {
        $this->showCommitModal = false;
    }

    public function confirmCommit(): void
    {
        if ($this->projectId === null) {
            return;
        }

        $result = app(SourceMigrationService::class)->runFor($this->projectId, dryRun: false);
        $this->showCommitModal = false;
        $this->flash = __('sources_migration_commit_flash', [
            'migrated' => $result['migrated'],
            'run_key' => $result['run_key'],
        ]);

        // Report als Dry-Run neu bauen — sollte danach 0 Kandidaten
        // ausweisen (Idempotenz-Signal).
        $this->report = app(SourceMigrationService::class)->runFor($this->projectId, dryRun: true);
    }
};
?>

<div class="mx-auto max-w-4xl px-6 py-6">
    <header class="mb-6">
        <h1 class="text-title font-semibold text-ink-900">{{ __('sources_migration_title') }}</h1>
        <p class="mt-1 text-body text-ink-500">{{ __('sources_migration_intro') }}</p>
    </header>

    @if ($flash !== '')
        <div class="mb-4 rounded-md border border-success-bg bg-success-bg/40 px-4 py-3 text-body text-ink-900">
            {{ $flash }}
        </div>
    @endif

    <section class="rounded-lg border border-line-200 bg-paper-0 p-6"
             aria-label="{{ __('sources_migration_projects_heading') }}">
        <h2 class="mb-3 text-heading font-semibold text-ink-900">
            {{ __('sources_migration_projects_heading') }}
        </h2>
        <ul class="divide-y divide-line-100" role="list">
            @foreach ($this->projects as $project)
                <li class="flex items-center justify-between py-2">
                    <span class="text-body text-ink-900">{{ $project->name }}</span>
                    <button
                        type="button"
                        wire:click="selectProject({{ $project->id }})"
                        aria-pressed="{{ $projectId === $project->id ? 'true' : 'false' }}"
                        class="inline-flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-caption font-medium
                               {{ $projectId === $project->id
                                   ? 'border-ink-900 bg-ink-900 text-paper-0'
                                   : 'border-line-200 bg-paper-0 text-ink-900 hover:border-ink-400' }}
                               focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                    >
                        <x-icon name="search" size="4"/>
                        {{ __('sources_migration_project_analyze') }}
                    </button>
                </li>
            @endforeach
        </ul>
    </section>

    @if ($report !== null)
        <section class="mt-6 rounded-lg border border-line-200 bg-paper-50 p-6"
                 aria-label="{{ __('sources_migration_report_heading') }}">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-heading font-semibold text-ink-900">
                        {{ __('sources_migration_report_heading') }}
                    </h2>
                    <p class="text-caption text-ink-500">
                        {{ __('sources_migration_report_dryrun_hint') }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        wire:click="refreshDryRun"
                        class="inline-flex items-center gap-1.5 rounded-md border border-line-200 bg-paper-0 px-3 py-1.5 text-caption font-medium text-ink-900 hover:border-ink-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                    >
                        <x-icon name="refresh-cw" size="4"/>
                        {{ __('sources_migration_refresh') }}
                    </button>
                    <button
                        type="button"
                        wire:click="openCommitModal"
                        @disabledIf((($report['candidates'] ?? 0) === 0 && ($report['av_pending'] ?? 0) === 0), __('sources_migration_no_candidates_hint'))
                        class="inline-flex items-center gap-1.5 rounded-md bg-primary px-3 py-1.5 text-caption font-medium text-primary-on hover:opacity-90
                               focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                    >
                        <x-icon name="play" size="4"/>
                        {{ __('sources_migration_commit') }}
                    </button>
                </div>
            </div>

            <dl class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                <div>
                    <dt class="text-caption text-ink-500">{{ __('sources_migration_report_candidates') }}</dt>
                    <dd class="text-heading font-semibold text-ink-900">{{ $report['candidates'] }}</dd>
                </div>
                <div>
                    <dt class="text-caption text-ink-500">{{ __('sources_migration_report_groups') }}</dt>
                    <dd class="text-heading font-semibold text-ink-900">{{ $report['groups'] }}</dd>
                </div>
                <div>
                    <dt class="text-caption text-ink-500">{{ __('sources_migration_report_freetext') }}</dt>
                    <dd class="text-heading font-semibold text-ink-900">{{ $report['freetext_candidates'] }}</dd>
                </div>
                <div>
                    <dt class="text-caption text-ink-500">{{ __('sources_migration_report_av_pending') }}</dt>
                    <dd class="text-heading font-semibold text-ink-900">{{ $report['av_pending'] ?? 0 }}</dd>
                </div>
                <div>
                    <dt class="text-caption text-ink-500">{{ __('sources_migration_report_run_key') }}</dt>
                    <dd class="font-mono text-caption text-ink-700">{{ $report['run_key'] }}</dd>
                </div>
            </dl>

            <h3 class="mt-6 text-body font-semibold text-ink-900">
                {{ __('sources_migration_kind_suggestions') }}
            </h3>
            <ul class="mt-2 grid grid-cols-2 gap-2 text-caption text-ink-700 md:grid-cols-5" role="list">
                @foreach ($report['kind_suggestions'] as $kind => $count)
                    <li class="rounded-md border border-line-200 bg-paper-0 px-3 py-2">
                        <span class="block text-ink-500">{{ __('sources_kind_'.$kind) }}</span>
                        <span class="text-body font-semibold text-ink-900">{{ $count }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="mt-6 rounded-lg border border-line-200 bg-paper-0 p-6"
             aria-label="{{ __('sources_migration_history_heading') }}">
        <h2 class="mb-3 text-heading font-semibold text-ink-900">
            {{ __('sources_migration_history_heading') }}
        </h2>
        @if ($this->history->isEmpty())
            <p class="text-caption text-ink-500">{{ __('sources_migration_history_empty') }}</p>
        @else
            <ul class="divide-y divide-line-100" role="list">
                @foreach ($this->history as $entry)
                    <li class="py-2 text-caption text-ink-700">
                        <span class="font-mono">{{ $entry->run_key }}</span> ·
                        {{ __('project') }} {{ $entry->project_id ?? '—' }} ·
                        {{ $entry->row_count }} {{ __('sources_migration_rows_backed_up') }} ·
                        {{ $entry->run_at }}
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- Commit-Confirmation-Modal, Muster analog remove-confirm-modal. --}}
    @if ($showCommitModal)
        <div
            class="fixed inset-0 z-40 flex items-center justify-center bg-ink-900/40 px-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="sources-commit-title"
            wire:click.self="cancelCommit"
            x-data
            x-init="$nextTick(() => document.getElementById('sourcesCommitCancel')?.focus())"
            @keydown.escape.window="$wire.cancelCommit()"
        >
            <div class="w-full max-w-md rounded-lg border border-line-200 bg-paper-0 shadow-lg">
                <header class="flex items-center justify-between border-b border-line-200 px-5 py-3">
                    <h3 id="sources-commit-title" class="text-heading font-semibold text-ink-900">
                        {{ __('sources_migration_commit_title') }}
                    </h3>
                    <button
                        type="button"
                        wire:click="cancelCommit"
                        aria-label="{{ __('close') }}"
                        class="rounded-md p-1 text-ink-500 hover:bg-ink-900/5 hover:text-ink-900
                               focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink-900"
                    >
                        <x-icon name="x" size="4"/>
                    </button>
                </header>
                <div class="p-5">
                    <p class="text-body text-ink-900">
                        {{ __('sources_migration_commit_confirm', [
                            'candidates' => $report['candidates'] ?? 0,
                            'groups' => $report['groups'] ?? 0,
                        ]) }}
                    </p>
                    <p class="mt-2 text-caption text-ink-500">
                        {{ __('sources_migration_commit_hint') }}
                    </p>
                    <div class="mt-5 flex items-center justify-end gap-2">
                        <button
                            id="sourcesCommitCancel"
                            type="button"
                            wire:click="cancelCommit"
                            class="inline-flex items-center rounded-md bg-transparent px-4 py-2 text-body font-medium text-ink-700 hover:bg-ink-900/5
                                   focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink-900"
                        >
                            {{ __('cancel') }}
                        </button>
                        <button
                            type="button"
                            wire:click="confirmCommit"
                            class="inline-flex items-center gap-1.5 rounded-md bg-primary px-4 py-2 text-body font-medium text-primary-on hover:opacity-90
                                   focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                        >
                            <x-icon name="play" size="4"/>
                            {{ __('sources_migration_commit_button') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
