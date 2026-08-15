@php
    /**
     * Projekt-Karte fuer das Dashboard (Screen 09).
     * Konsumiert:
     *   $project    — App\Models\Project mit chapters_count
     *   $statusMeta — Array status → label + variant
     *   $roleBadge  — 'editor' | 'reader' | null (nur „Mir zugeteilt")
     *   $ownerName  — string | null (nur „Mir zugeteilt")
     *
     * Ganze Karte ist EIN Link — Rollen-Badge in Absoluter Position.
     * Bei Leserecht fuehrt der Link auf die Leseansicht statt auf den
     * Editor, damit der User nicht in einem 403 landet.
     */
    $meta = $statusMeta[$project->status] ?? ['label' => $project->status, 'variant' => 'info'];
    $badgeClass = [
        'success' => 'bg-success-bg text-success',
        'warning' => 'bg-warning-bg text-warning',
        'info'    => 'bg-info-bg text-info',
        'danger'  => 'bg-danger-bg text-danger',
    ][$meta['variant']] ?? 'bg-info-bg text-info';

    $chapterCount = $project->chapters_count ?? 0;
    // Ziel-URL: immer der Editor. Reader landen dort im Read-Only-
    // Modus (Edit-/Publish-Buttons werden per @can-Gate ausgeblendet).
    // projects.show ist eine Legacy-View mit „show_product"-Ueberschrift
    // ohne Nutzwert.
    $href = route('projects.edit', $project->id);

    $roleBadgeLabel = $roleBadge === 'editor'
        ? __('role_editor_short')
        : ($roleBadge === 'reader' ? __('role_reader_short') : null);
@endphp

<a href="{{ $href }}"
   class="group relative flex flex-col overflow-hidden rounded-[11px] border border-line-200 bg-paper-0
          hover:border-ink-400
          focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-bar">

    {{-- Thumbnail-Band 66px --}}
    <div class="relative h-[66px] w-full bg-line-100"
         @if ($project->logo)
             style="background-image: url('{{ route('image', $project->logo) }}'); background-size: cover; background-position: center;"
         @endif
         aria-hidden="true">
        @if ($roleBadgeLabel)
            <span class="absolute left-2 top-2 rounded-[5px] bg-ink-900/80 px-2 py-0.5
                         font-mono text-[10px] uppercase tracking-widest text-white">
                {{ $roleBadgeLabel }}
            </span>
        @endif
    </div>

    {{-- Body 12px 14px 13px --}}
    <div class="flex flex-1 flex-col gap-1 px-[14px] py-3">
        <div class="truncate text-body font-medium text-ink-900">
            {{ $project->name }}
        </div>
        <div class="text-caption text-ink-500">
            {{ trans_choice('n_chapters', $chapterCount, ['count' => $chapterCount]) }}
            @if ($ownerName)
                · {{ $ownerName }}
            @endif
        </div>
        <div class="mt-auto flex items-center justify-between pt-2">
            <span class="{{ $badgeClass }} inline-flex items-center gap-1.5 rounded-pill px-2.5 py-0.5 text-caption font-medium">
                <span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>
                {{ $meta['label'] }}
            </span>
            <span class="text-caption text-ink-500">
                {{ $project->updated_at?->diffForHumans() }}
            </span>
        </div>
    </div>
</a>
