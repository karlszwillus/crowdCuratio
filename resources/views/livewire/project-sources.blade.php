<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2022, 2026 - berlinHistory e.V.

See LICENSE.
 */

use App\Models\Audiovisual;
use App\Models\Image;
use App\Models\Project;
use App\Models\Source;
use App\Models\Text;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

/**
 * Q4-Etappe 3 / C0d (2026-09-07): Projekt-weite Quellenverwaltung.
 * Vollpage unter /projects/{project}/sources, gated auf `update`.
 *
 * Bietet Liste + Suche + Filter (Type Copyright/Origin), Detail-
 * Editor (Name, kind, title, holding, signature — die letzten vier
 * nur bei `citation_depth=full` sichtbar), Merge zweier Sources mit
 * Referenz-Umbiegung an Text/Image/AV, SoftDelete mit Referenz-
 * Warnung. Referenz-Zähler pro Row.
 */
new class extends Component
{
    #[Locked]
    public int $projectId;

    public string $search = '';

    /** all|copyright|origin */
    public string $typeFilter = 'all';

    public ?int $selectedId = null;

    // Detail-Formular
    public string $name = '';

    public ?string $kind = null;

    public ?string $title = null;

    public ?string $holding = null;

    public ?string $signature = null;

    // Merge-Modal
    public bool $showMergeModal = false;

    public ?int $mergeTargetId = null;

    // Delete-Modal
    public bool $showDeleteModal = false;

    public string $flash = '';

    public function mount(int $projectId): void
    {
        $this->projectId = $projectId;
        Gate::authorize('update', Project::findOrFail($projectId));
    }

    #[Computed]
    public function project(): Project
    {
        return Project::findOrFail($this->projectId);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Source>
     */
    #[Computed]
    public function sources(): \Illuminate\Support\Collection
    {
        return Source::query()
            ->where('project_id', $this->projectId)
            ->when($this->typeFilter === 'copyright', fn (Builder $q) => $q->where('type', 'Copyright'))
            ->when($this->typeFilter === 'origin', fn (Builder $q) => $q->where('type', 'Origin'))
            ->when($this->search !== '', fn (Builder $q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->orderBy('type')
            ->orderBy('name')
            ->get();
    }

    /**
     * Zaehlt die aktiven Referenzen (Text+Image+AV) fuer EINE Source.
     *
     * C0-8b Fix v2 (2026-09-07): Vorherige Version nutzte ein Cache-
     * Array ueber alle Projekt-Sources — das lieferte inkonsistente
     * Werte zwischen Liste und Detail-Bereich (vermutlich Livewire-
     * Computed-Cache-Timing in Volt). Diese Version zaehlt direkt
     * per Source, damit beide Zugriffe garantiert dieselbe Query-
     * Semantik verwenden.
     */
    public function referenceCountFor(int $sourceId): int
    {
        return Text::query()
            ->where(fn ($q) => $q->where('origin', $sourceId)->orWhere('copyright', $sourceId))
            ->count()
            + Image::query()
                ->where(fn ($q) => $q->where('origin', $sourceId)->orWhere('copyright', $sourceId))
                ->count()
            + Audiovisual::query()
                ->where(fn ($q) => $q->where('origin_id', $sourceId)->orWhere('copyright_id', $sourceId))
                ->count();
    }

    public function selectSource(int $sourceId): void
    {
        $source = Source::where('project_id', $this->projectId)->findOrFail($sourceId);
        $this->selectedId = $source->id;
        $this->name = (string) $source->name;
        $this->kind = $source->kind;
        $this->title = $source->title;
        $this->holding = $source->holding;
        $this->signature = $source->signature;
        $this->flash = '';
    }

    public function clearSelection(): void
    {
        $this->selectedId = null;
        $this->name = '';
        $this->kind = null;
        $this->title = null;
        $this->holding = null;
        $this->signature = null;
    }

    public function save(): void
    {
        if ($this->selectedId === null) {
            return;
        }

        $source = Source::where('project_id', $this->projectId)->findOrFail($this->selectedId);
        $source->setTranslation('name', app()->getLocale(), trim($this->name));
        if ($this->project->usesFullCitationDepth()) {
            $source->kind = $this->kind ?: null;
            $source->title = $this->title ?: null;
            $source->holding = $this->holding ?: null;
            $source->signature = $this->signature ?: null;
        }
        $source->save();

        $this->flash = __('sources_admin_saved_flash');
    }

    // ------------------------------------------------------------------
    // Merge
    // ------------------------------------------------------------------

    public function openMergeModal(): void
    {
        if ($this->selectedId === null) {
            return;
        }
        $this->mergeTargetId = null;
        $this->showMergeModal = true;
    }

    public function cancelMerge(): void
    {
        $this->showMergeModal = false;
        $this->mergeTargetId = null;
    }

    /**
     * Mögliche Merge-Ziele: gleicher `type` wie die aktive Source,
     * aber nicht die aktive Source selbst.
     *
     * @return \Illuminate\Support\Collection<int, Source>
     */
    #[Computed]
    public function mergeCandidates(): \Illuminate\Support\Collection
    {
        if ($this->selectedId === null) {
            return collect();
        }
        $active = Source::find($this->selectedId);
        if ($active === null) {
            return collect();
        }

        return Source::query()
            ->where('project_id', $this->projectId)
            ->where('type', $active->type)
            ->where('id', '!=', $active->id)
            ->orderBy('name')
            ->get();
    }

    public function confirmMerge(): void
    {
        if ($this->selectedId === null || $this->mergeTargetId === null) {
            return;
        }

        $source = Source::where('project_id', $this->projectId)->findOrFail($this->selectedId);
        $target = Source::where('project_id', $this->projectId)->findOrFail($this->mergeTargetId);

        // In der Transaktion: Referenzen umbiegen, dann Source
        // soft-deleten. Text/Image/AV.
        DB::transaction(function () use ($source, $target) {
            Text::query()->where('origin', $source->id)->update(['origin' => $target->id]);
            Text::query()->where('copyright', $source->id)->update(['copyright' => $target->id]);
            Image::query()->where('origin', $source->id)->update(['origin' => $target->id]);
            Image::query()->where('copyright', $source->id)->update(['copyright' => $target->id]);
            Audiovisual::query()->where('origin_id', $source->id)->update(['origin_id' => $target->id]);
            Audiovisual::query()->where('copyright_id', $source->id)->update(['copyright_id' => $target->id]);
            $source->delete();

            \Illuminate\Support\Facades\Log::channel(config('logging.default'))
                ->info('sources.admin.merged', [
                    'project_id' => $this->projectId,
                    'merged_source_id' => $source->id,
                    'target_source_id' => $target->id,
                ]);
        });

        $this->showMergeModal = false;
        $this->mergeTargetId = null;
        $this->selectedId = $target->id;
        $this->flash = __('sources_admin_merged_flash', ['target' => (string) $target->name]);
        $this->selectSource($target->id);
    }

    // ------------------------------------------------------------------
    // Delete
    // ------------------------------------------------------------------

    public function openDeleteModal(): void
    {
        if ($this->selectedId === null) {
            return;
        }
        $this->showDeleteModal = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
    }

    public function confirmDelete(): void
    {
        if ($this->selectedId === null) {
            return;
        }

        $source = Source::where('project_id', $this->projectId)->findOrFail($this->selectedId);
        $source->delete();

        \Illuminate\Support\Facades\Log::channel(config('logging.default'))
            ->info('sources.admin.deleted', [
                'project_id' => $this->projectId,
                'source_id' => $source->id,
            ]);

        $this->flash = __('sources_admin_deleted_flash');
        $this->showDeleteModal = false;
        $this->clearSelection();
    }
};
?>

<div class="mx-auto max-w-6xl px-6 py-6">
    <header class="mb-6">
        <h1 class="text-title font-semibold text-ink-900">
            {{ __('sources_admin_title') }}
        </h1>
        <p class="mt-1 text-body text-ink-500">
            {{ __('sources_admin_intro') }}
        </p>
        @if (! $this->project->usesFullCitationDepth())
            <p class="mt-2 rounded-md border border-line-200 bg-canvas-bg px-3 py-2 text-caption text-ink-700">
                {{ __('sources_admin_simple_hint') }}
            </p>
        @endif
    </header>

    @if ($flash !== '')
        <div class="mb-4 rounded-md border border-success-bg bg-success-bg/40 px-4 py-3 text-body text-ink-900">
            {{ $flash }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 md:grid-cols-[320px_1fr]">
        {{-- Linke Spalte: Filter + Liste --}}
        <section class="rounded-lg border border-line-200 bg-paper-0 p-4"
                 aria-label="{{ __('sources_admin_list_heading') }}">
            <div class="mb-3 flex items-center gap-2">
                <select wire:model.live="typeFilter"
                        class="rounded-md border border-line-200 bg-paper-0 px-2 py-1 text-caption">
                    <option value="all">{{ __('sources_admin_filter_all') }}</option>
                    <option value="copyright">{{ __('copyright') }}</option>
                    <option value="origin">{{ __('origin') }}</option>
                </select>
                <input type="search"
                       wire:model.live.debounce.250ms="search"
                       placeholder="{{ __('sources_admin_search_placeholder') }}"
                       class="w-full rounded-md border border-line-200 bg-paper-0 px-2 py-1 text-caption"/>
            </div>
            @if ($this->sources->isEmpty())
                <p class="py-4 text-center text-caption text-ink-500">
                    {{ __('sources_admin_empty') }}
                </p>
            @else
                <ul class="divide-y divide-line-100" role="list">
                    @foreach ($this->sources as $source)
                        @php
                            $count = $this->referenceCountFor((int) $source->id);
                        @endphp
                        <li>
                            <button
                                type="button"
                                wire:click="selectSource({{ $source->id }})"
                                aria-pressed="{{ $selectedId === $source->id ? 'true' : 'false' }}"
                                class="flex w-full items-start justify-between gap-3 rounded-md px-2 py-2 text-left
                                       {{ $selectedId === $source->id ? 'bg-danger-bg' : 'hover:bg-canvas-bg' }}"
                            >
                                <span class="min-w-0 flex-1">
                                    <span class="block text-body text-ink-900">{{ $source->name }}</span>
                                    <span class="block text-caption text-ink-500">{{ $source->type }}</span>
                                </span>
                                <span class="mt-0.5 shrink-0 rounded-full bg-line-100 px-2 py-0.5 text-caption text-ink-700"
                                      title="{{ __('sources_admin_reference_count') }}">
                                    {{ $count }}×
                                </span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Rechte Spalte: Detail-Editor --}}
        <section class="rounded-lg border border-line-200 bg-paper-50 p-6"
                 aria-label="{{ __('sources_admin_detail_heading') }}">
            @if ($selectedId === null)
                <p class="py-12 text-center text-body text-ink-500">
                    {{ __('sources_admin_no_selection') }}
                </p>
            @else
                @php $refCount = $this->referenceCountFor((int) $selectedId); @endphp
                <form wire:submit.prevent="save" class="space-y-4">
                    <div>
                        <label for="src-name" class="mb-1 block text-caption font-medium text-ink-700">
                            {{ __('sources_admin_field_name') }}
                        </label>
                        <input id="src-name" type="text" wire:model="name"
                               class="block w-full rounded-md border border-line-200 bg-paper-0 px-3 py-2 text-body text-ink-900"/>
                    </div>

                    @if ($this->project->usesFullCitationDepth())
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <div>
                                <label for="src-kind" class="mb-1 block text-caption font-medium text-ink-700">
                                    {{ __('sources_admin_field_kind') }}
                                </label>
                                <select id="src-kind" wire:model="kind"
                                        class="block w-full rounded-md border border-line-200 bg-paper-0 px-3 py-2 text-body">
                                    <option value="">—</option>
                                    <option value="archivalie">{{ __('sources_kind_archivalie') }}</option>
                                    <option value="publikation">{{ __('sources_kind_publikation') }}</option>
                                    <option value="interview">{{ __('sources_kind_interview') }}</option>
                                    <option value="abbildung">{{ __('sources_kind_abbildung') }}</option>
                                    <option value="sonstige">{{ __('sources_kind_sonstige') }}</option>
                                </select>
                            </div>
                            <div>
                                <label for="src-title" class="mb-1 block text-caption font-medium text-ink-700">
                                    {{ __('sources_admin_field_title') }}
                                </label>
                                <input id="src-title" type="text" wire:model="title"
                                       class="block w-full rounded-md border border-line-200 bg-paper-0 px-3 py-2 text-body"/>
                            </div>
                            <div>
                                <label for="src-holding" class="mb-1 block text-caption font-medium text-ink-700">
                                    {{ __('sources_admin_field_holding') }}
                                </label>
                                <input id="src-holding" type="text" wire:model="holding"
                                       class="block w-full rounded-md border border-line-200 bg-paper-0 px-3 py-2 text-body"/>
                            </div>
                            <div>
                                <label for="src-signature" class="mb-1 block text-caption font-medium text-ink-700">
                                    {{ __('sources_admin_field_signature') }}
                                </label>
                                <input id="src-signature" type="text" wire:model="signature"
                                       class="block w-full rounded-md border border-line-200 bg-paper-0 px-3 py-2 text-body"/>
                            </div>
                        </div>
                    @endif

                    <p class="text-caption text-ink-500">
                        {{ trans_choice('sources_admin_referenced_n_times', $refCount, ['count' => $refCount]) }}
                    </p>

                    <div class="flex items-center justify-between gap-2 pt-2">
                        <div class="flex items-center gap-2">
                            @if ($this->mergeCandidates->isNotEmpty())
                                <button type="button"
                                        wire:click="openMergeModal"
                                        class="inline-flex items-center gap-1.5 rounded-md border border-line-200 bg-paper-0 px-3 py-1.5 text-caption text-ink-900 hover:border-ink-400">
                                    <x-icon name="merge" size="4"/>
                                    {{ __('sources_admin_merge') }}
                                </button>
                            @endif
                            <button type="button"
                                    wire:click="openDeleteModal"
                                    class="inline-flex items-center gap-1.5 rounded-md border border-line-200 bg-paper-0 px-3 py-1.5 text-caption text-danger hover:bg-danger-bg">
                                <x-icon name="trash-2" size="4"/>
                                {{ __('sources_admin_delete') }}
                            </button>
                        </div>
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 rounded-md bg-primary px-4 py-2 text-body font-medium text-primary-on hover:opacity-90">
                            <x-icon name="save" size="4"/>
                            {{ __('save') }}
                        </button>
                    </div>
                </form>
            @endif
        </section>
    </div>

    {{-- Merge-Modal --}}
    @if ($showMergeModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-ink-900/40 px-4"
             role="dialog" aria-modal="true"
             wire:click.self="cancelMerge"
             x-data @keydown.escape.window="$wire.cancelMerge()">
            <div class="w-full max-w-md rounded-lg border border-line-200 bg-paper-0 shadow-lg">
                <header class="flex items-center justify-between border-b border-line-200 px-5 py-3">
                    <h3 class="text-heading font-semibold text-ink-900">{{ __('sources_admin_merge_title') }}</h3>
                    <button type="button" wire:click="cancelMerge" aria-label="{{ __('close') }}"
                            class="rounded-md p-1 text-ink-500 hover:bg-ink-900/5 hover:text-ink-900">
                        <x-icon name="x" size="4"/>
                    </button>
                </header>
                <div class="p-5 space-y-3">
                    <p class="text-body text-ink-900">{{ __('sources_admin_merge_intro') }}</p>
                    <select wire:model.live="mergeTargetId"
                            class="block w-full rounded-md border border-line-200 bg-paper-0 px-3 py-2 text-body">
                        <option value="">— {{ __('sources_admin_merge_target_placeholder') }} —</option>
                        @foreach ($this->mergeCandidates as $candidate)
                            <option value="{{ $candidate->id }}">{{ $candidate->name }}</option>
                        @endforeach
                    </select>
                    <p class="text-caption text-ink-500">{{ __('sources_admin_merge_hint') }}</p>
                    <div class="flex items-center justify-end gap-2 pt-3">
                        <button type="button" wire:click="cancelMerge"
                                class="rounded-md px-3 py-1.5 text-body text-ink-700 hover:bg-ink-900/5">
                            {{ __('cancel') }}
                        </button>
                        <button type="button" wire:click="confirmMerge"
                                @disabledIf($mergeTargetId === null, __('sources_admin_merge_pick_target'))
                                class="inline-flex items-center gap-1.5 rounded-md bg-primary px-4 py-2 text-body font-medium text-primary-on hover:opacity-90">
                            <x-icon name="merge" size="4"/>
                            {{ __('sources_admin_merge_confirm') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete-Modal --}}
    @if ($showDeleteModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-ink-900/40 px-4"
             role="dialog" aria-modal="true"
             wire:click.self="cancelDelete"
             x-data @keydown.escape.window="$wire.cancelDelete()">
            <div class="w-full max-w-md rounded-lg border border-line-200 bg-paper-0 shadow-lg">
                <header class="flex items-center justify-between border-b border-line-200 px-5 py-3">
                    <h3 class="text-heading font-semibold text-ink-900">{{ __('sources_admin_delete_title') }}</h3>
                    <button type="button" wire:click="cancelDelete" aria-label="{{ __('close') }}"
                            class="rounded-md p-1 text-ink-500 hover:bg-ink-900/5 hover:text-ink-900">
                        <x-icon name="x" size="4"/>
                    </button>
                </header>
                <div class="p-5 space-y-3">
                    <p class="text-body text-ink-900">{{ __('sources_admin_delete_confirm', ['name' => $name]) }}</p>
                    @php $refCount = $this->referenceCountFor((int) $selectedId); @endphp
                    @if ($refCount > 0)
                        <p class="rounded-md border border-warning-bg bg-warning-bg/40 px-3 py-2 text-caption text-ink-900">
                            {{ trans_choice('sources_admin_delete_reference_warning', $refCount, ['count' => $refCount]) }}
                        </p>
                    @endif
                    <div class="flex items-center justify-end gap-2 pt-3">
                        <button type="button" wire:click="cancelDelete"
                                class="rounded-md px-3 py-1.5 text-body text-ink-700 hover:bg-ink-900/5">
                            {{ __('cancel') }}
                        </button>
                        <button type="button" wire:click="confirmDelete"
                                class="inline-flex items-center gap-1.5 rounded-md bg-danger px-4 py-2 text-body font-medium text-danger-on hover:opacity-90">
                            <x-icon name="trash-2" size="4"/>
                            {{ __('sources_admin_delete_button') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
