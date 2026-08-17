<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C)2026 - berlinHistory e.V.
 *
 * Phase 5ab.3: Livewire-Container fuer das Verlauf-Panel.
 *
 * Analog zu comment-panel-list. Empfaengt `history-panel:load` mit
 * `{subjectType, subjectId, scope?}`, laedt die Revisions ueber das
 * bestehende Model (kein Fetch auf den Endpoint noetig — wir sind auf
 * dem Server), rendert Fassungs-Karten mit Chip, Summary, Actor und
 * Zeitstempel.
 */

use App\Models\Revision;
use App\Support\RevisionSubject;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new class extends Component
{
    public int $projectId;

    public ?string $subjectType = null;

    public ?int $subjectId = null;

    public string $scope = 'block';

    public ?int $selectedRevisionId = null;

    public function mount(int $projectId): void
    {
        $this->projectId = $projectId;
    }

    #[On('history-panel:load')]
    public function load(string $subjectType, int $subjectId, string $scope = 'block'): void
    {
        $this->subjectType = $subjectType;
        $this->subjectId = $subjectId;
        $this->scope = in_array($scope, ['block', 'entry', 'project'], true) ? $scope : 'block';
        $this->selectedRevisionId = null;
    }

    public function setScope(string $scope): void
    {
        $this->scope = in_array($scope, ['block', 'entry', 'project'], true) ? $scope : 'block';
    }

    public function select(int $revisionId): void
    {
        $this->selectedRevisionId = $revisionId;
        // Diff im Block einschalten (5ab.4). Wir rendern pro Feld ein
        // Wort-Level-Diff und schicken das komplette Objekt als Payload;
        // der Editor haengt die HTML-Fragmente per Alpine an die
        // passenden Block-Felder.
        /** @var Revision|null $revision */
        $revision = Revision::query()->find($revisionId);
        if ($revision === null) {
            return;
        }

        /** @var array<string, array{old: mixed, new: mixed}> $changes */
        $changes = $revision->snapshot['changes'] ?? [];
        $fields = [];
        foreach ($changes as $field => $delta) {
            $old = self::stringifyDelta($delta['old'] ?? null);
            $new = self::stringifyDelta($delta['new'] ?? null);
            if ($old === '' && $new === '') {
                continue;
            }
            $diff = \App\Support\RevisionDiff::renderWordDiff($old, $new);
            $fields[$field] = [
                'html' => $diff['html'],
                'added' => $diff['added'],
                'removed' => $diff['removed'],
                'old' => $old,
                'new' => $new,
            ];
        }

        $this->dispatch(
            'revision-selected',
            revisionId: $revisionId,
            subjectType: RevisionSubject::shortName($revision->subject_type),
            subjectId: (int) $revision->subject_id,
            fields: $fields,
        );
    }

    /**
     * Snapshot-Werte fuer den Diff auf reinen Text bringen.
     *
     * Translatable Felder liegen intern als JSON (Spatie HasTranslations)
     * — beim Backfill aus dem Activitylog landen sie als JSON-String im
     * Snapshot, bei Live-Writes als PHP-Array. Beide Faelle in einen
     * String fuer die aktuelle Locale (Fallback: die, die drinsteht).
     */
    private static function stringifyDelta(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[')) {
                $decoded = json_decode($trimmed, true);
                if (is_array($decoded)) {
                    return self::stringifyDelta($decoded);
                }
            }
            // Rich-Text-Beschreibungen haben HTML — fuer den Wort-Diff
            // reicht der reine Text ohne Tags, sonst diffen wir <p>-
            // Marker mit.
            return trim(strip_tags($value));
        }
        if (is_array($value)) {
            $locale = app()->getLocale();
            if (isset($value[$locale])) {
                return self::stringifyDelta($value[$locale]);
            }
            if (isset($value['de'])) {
                return self::stringifyDelta($value['de']);
            }
            $first = reset($value);

            return $first === false ? '' : self::stringifyDelta($first);
        }
        if (is_scalar($value)) {
            return (string) $value;
        }

        return '';
    }

    public function with(): array
    {
        if ($this->subjectType === null || $this->subjectId === null) {
            return ['revisions' => collect(), 'grouped' => collect(), 'restoreProject' => null];
        }

        $fqcn = RevisionSubject::TYPES[$this->subjectType] ?? null;
        if ($fqcn === null) {
            return ['revisions' => collect(), 'grouped' => collect(), 'restoreProject' => null];
        }

        // Projekt einmal aufloesen und in `with()` durchreichen. Blade
        // nutzt es fuer @can(history.restore), damit wir nicht pro
        // Revision-Karte $revision->subject lazy-loaden — shouldBeStrict()
        // sperrt das sonst.
        $anchorSubject = RevisionSubject::resolve($this->subjectType, $this->subjectId);
        $restoreProject = $anchorSubject ? RevisionSubject::projectFor($anchorSubject) : null;
        // § 7: bei Uebersetzung vorhanden warnt der Restore-Dialog, dass
        // sie erhalten bleibt aber als „Original nach Uebersetzung
        // geaendert" markiert wird. Wir prueflegen das anhand des
        // getranslations-Zustands des Anker-Subjects.
        $anchorHasTranslations = false;
        if ($anchorSubject !== null && method_exists($anchorSubject, 'getTranslations')) {
            $translatable = property_exists($anchorSubject, 'translatable') ? (array) $anchorSubject->translatable : [];
            foreach ($translatable as $field) {
                $translations = $anchorSubject->getTranslations($field);
                foreach ($translations as $locale => $value) {
                    if ($locale !== 'de' && trim((string) $value) !== '') {
                        $anchorHasTranslations = true;
                        break 2;
                    }
                }
            }
        }

        $query = Revision::query()
            ->with('actor:id,name')
            ->orderByDesc('created_at')
            ->limit(50);

        if ($this->scope === 'block') {
            $query->where('subject_type', $fqcn)
                ->where('subject_id', $this->subjectId);
        } else {
            // Fuer Entry-/Project-Umfang delegieren wir an den
            // RevisionController-Helper — der bleibt der eine Ort, an
            // dem die Umfangs-Logik liegt. Wir instanziieren ihn hier
            // NICHT, sondern replizieren die einfache Variante: alle
            // Revisions im Projekt (fuer scope=project). Fuer scope=
            // entry braeuchten wir das Subject als Entry-Anker — das
            // machen wir, sobald der Editor die Entry-Id mitgibt.
            // Fuer 5ab.3 reicht scope=project als Naeherung.
            $subject = RevisionSubject::resolve($this->subjectType, $this->subjectId);
            $project = $subject ? RevisionSubject::projectFor($subject) : null;
            if ($project === null) {
                return ['revisions' => collect(), 'grouped' => collect()];
            }
            $chapterIds = $project->chapters()->pluck('id')->all();
            $entryIds = \App\Models\Entry::query()->whereIn('chapter_id', $chapterIds)->pluck('id')->all();
            $mediaContent = \App\Models\MediaContent::query()
                ->whereIn('parent_id', $entryIds)
                ->where('parent_type', \App\Models\Entry::class)
                ->get(['content_id', 'content_type']);

            $query->where(function ($outer) use ($chapterIds, $entryIds, $mediaContent): void {
                if ($chapterIds !== []) {
                    $outer->orWhere(function ($q) use ($chapterIds): void {
                        $q->where('subject_type', \App\Models\Chapter::class)->whereIn('subject_id', $chapterIds);
                    });
                }
                if ($entryIds !== []) {
                    $outer->orWhere(function ($q) use ($entryIds): void {
                        $q->where('subject_type', \App\Models\Entry::class)->whereIn('subject_id', $entryIds);
                    });
                }
                foreach ($mediaContent->groupBy('content_type') as $type => $rows) {
                    $ids = $rows->pluck('content_id')->map(fn ($id) => (int) $id)->all();
                    if ($ids === []) {
                        continue;
                    }
                    $outer->orWhere(function ($q) use ($type, $ids): void {
                        $q->where('subject_type', $type)->whereIn('subject_id', $ids);
                    });
                }
            });
        }

        $revisions = $query->get();

        // Gruppierung nach Tag fuer die Karten-Ueberschrift (§ 6:
        // „Heute · 16.08.2026").
        $grouped = $revisions->groupBy(fn (Revision $r) => $r->created_at?->format('Y-m-d'));

        return [
            'revisions' => $revisions,
            'grouped' => $grouped,
            'restoreProject' => $restoreProject,
            'anchorHasTranslations' => $anchorHasTranslations,
        ];
    }
}; ?>

<div>
    @if ($subjectType === null)
        <p class="text-body text-ink-500">{{ __('history_panel_empty_hint') }}</p>
    @else
        {{-- Segmented Control fuer den Umfang (§ 6). --}}
        <div class="mb-3 flex gap-1 rounded-md bg-line-100 p-0.5" role="tablist" aria-label="{{ __('history_scope_label') }}">
            @foreach (['block' => 'history_scope_block', 'entry' => 'history_scope_entry', 'project' => 'history_scope_project'] as $key => $label)
                <button
                    type="button"
                    wire:click="setScope('{{ $key }}')"
                    role="tab"
                    aria-selected="{{ $scope === $key ? 'true' : 'false' }}"
                    @class([
                        'flex-1 rounded-md px-2 py-1 text-caption font-medium transition-colors',
                        'bg-paper-0 text-ink-900 shadow-subtle' => $scope === $key,
                        'text-ink-600 hover:text-ink-900' => $scope !== $key,
                    ])
                >{{ __($label) }}</button>
            @endforeach
        </div>

        @if ($revisions->isEmpty())
            {{-- § 7 Leerzustand: „Dieser Block wurde seit dem Anlegen nicht geaendert". --}}
            <div class="rounded-md border border-dashed border-line-300 bg-canvas-bg p-4 text-center">
                <p class="text-caption text-ink-500">{{ __('history_no_revisions') }}</p>
            </div>
        @else
            <div class="flex flex-col gap-4">
                @foreach ($grouped as $dayKey => $dayRevisions)
                    <section>
                        @php
                            $day = \Illuminate\Support\Carbon::parse($dayKey);
                            $dayLabel = $day->isToday()
                                ? __('history_group_today', ['date' => $day->format('d.m.Y')])
                                : $day->format('d.m.Y');
                        @endphp
                        <h3 class="mb-2 text-caption font-mono uppercase tracking-wider text-ink-500">
                            {{ $dayLabel }}
                        </h3>
                        <ul class="flex flex-col gap-2">
                            @foreach ($dayRevisions as $index => $revision)
                                @php
                                    $isSelected = $revision->id === $selectedRevisionId;
                                    $isCurrent = $loop->parent->first && $index === 0;
                                @endphp
                                <li wire:key="revision-{{ $revision->id }}">
                                    <button
                                        type="button"
                                        wire:click="select({{ $revision->id }})"
                                        @class([
                                            'w-full rounded-md border p-3 text-left transition-colors',
                                            'border-ink-300 bg-paper-50 shadow-subtle' => $isSelected,
                                            'border-line-200 bg-paper-0 hover:bg-canvas-bg' => ! $isSelected,
                                        ])
                                        aria-pressed="{{ $isSelected ? 'true' : 'false' }}"
                                    >
                                        <div class="flex items-baseline justify-between gap-2">
                                            <div class="flex items-center gap-2">
                                                <span class="text-body font-medium text-ink-900">
                                                    {{ $revision->actor?->name ?? __('history_actor_system') }}
                                                </span>
                                                <span class="text-caption text-ink-500">
                                                    {{ $revision->created_at?->format('H:i') }}
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <span class="rounded bg-canvas-bg px-1.5 py-0.5 text-caption font-mono text-ink-700">
                                                    v{{ $revision->version }}
                                                </span>
                                                @if ($isCurrent)
                                                    <span class="rounded bg-success-bg px-1.5 py-0.5 text-caption text-success">
                                                        {{ __('history_current') }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="mt-1 flex items-center gap-2">
                                            <span @class([
                                                'rounded px-1.5 py-0.5 text-caption',
                                                'bg-primary/10 text-primary' => $revision->kind === 'content',
                                                'bg-warning-bg text-warning' => $revision->kind === 'facts',
                                                'bg-canvas-dim text-ink-700' => $revision->kind === 'reorder',
                                                'bg-success-bg text-success' => $revision->kind === 'translation',
                                            ])>
                                                {{ $revision->kind_label }}
                                            </span>
                                            <span class="truncate text-caption text-ink-600">
                                                {{ $revision->summary }}
                                            </span>
                                        </div>
                                        @if ($isSelected)
                                            @php
                                                // TEMP-DEBUG 5ab.5: der @can-Guard griff bei Karl
                                                // nicht — wir loggen hier den Rueckgabewert und
                                                // rendern den Button unabhaengig davon, bis wir
                                                // die Ursache haben. Ruecknahme in 5ab.6.
                                                $debugCan = auth()->check() && $restoreProject
                                                    ? auth()->user()->can(App\Support\PermissionName::HISTORY_RESTORE->value, $restoreProject)
                                                    : null;
                                                $debugRole = auth()->user()?->hasRole('Admin') ?? false;
                                            @endphp
                                            <div class="mt-3 flex flex-wrap gap-2 border-t border-line-200 pt-2">
                                                <button
                                                    type="button"
                                                    onclick="event.stopPropagation(); window.dispatchEvent(new CustomEvent('history:restore-request', { detail: { revisionId: {{ $revision->id }}, version: {{ $revision->version }}, hasTranslations: {{ $anchorHasTranslations ? 'true' : 'false' }} } }))"
                                                    class="rounded-md border border-ink-300 bg-canvas-bg px-2 py-1 text-caption text-ink-900 hover:bg-chrome-active"
                                                >
                                                    {{ __('history_restore_button') }}
                                                </button>
                                                <span class="text-caption text-ink-500">
                                                    can={{ $debugCan === null ? 'null' : ($debugCan ? 'true' : 'false') }}
                                                    · admin={{ $debugRole ? 'yes' : 'no' }}
                                                    · project={{ $restoreProject?->id ?? 'null' }}
                                                </span>
                                            </div>
                                        @endif
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            </div>
        @endif
    @endif
</div>
