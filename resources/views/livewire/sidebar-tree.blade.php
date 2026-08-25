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

use App\Models\Project;
use App\Services\ProjectTreeService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new class extends Component
{
    public Project $project;

    public function mount(Project $project, ProjectTreeService $tree): void
    {
        // Defense-in-Depth: die Volt-Komponente wird heute nur aus
        // dem gegateten ProjectController::edit gerendert, aber ohne
        // eigenen Gate wäre sie bei künftiger Wiederverwendung ein
        // Bypass. Ein direkter Livewire-Update-Roundtrip wählt sonst
        // beliebige Projekte als Prop.
        Gate::authorize('view', $project);

        // Tree-Aufbau geht durch den ProjectTreeService (Single Source
        // of Truth, den auch <x-ui.breadcrumb :tree> nutzt). Die
        // Sidebar iteriert direkt über die eager-geladenen Relations
        // am Project-Modell.
        $this->project = $tree->sidebarTree($project);
    }

    /**
     * Sidebar aktualisiert sich, wenn irgendein Feld eines Chapter,
     * Entry oder Content-Modells über den Inline-Editor gespeichert
     * wurde. Der Editor dispatcht das `saved`-Event global.
     *
     * Wichtig: `load` statt `loadMissing`. Nach `->fresh()` sind die
     * Relations noch nicht geladen, `loadMissing` würde greifen —
     * aber der Livewire-Snapshot bringt manchmal schon `chapters`
     * mit, dann würde `loadMissing` als No-Op durchlaufen und stale
     * Entry-Werte anzeigen. `load` überschreibt konsequent.
     */
    #[On('saved')]
    public function refreshTree(): void
    {
        $fresh = $this->project->fresh();
        $fresh->load(['chapters.entries']);
        $this->project = $fresh;
    }
}; ?>

<nav
    aria-label="{{ __('project_structure') }}"
    class="text-body"
    x-data="{
        active: window.location.hash,
        expanded: {},
        toggle(id) { this.expanded[id] = !this.expanded[id]; },
        isExpanded(id) { return this.expanded[id] !== false; }
    }"
    @hashchange.window="active = window.location.hash"
>
    {{-- Kopfzeile 'STRUKTUR / Projektname' (Handoff v4 § Screen 02).
         Sitzt in <x-layout.sidebar-panel> — der Panel-Kopf wird
         dort gerendert; wir liefern hier nur den Content. --}}

    <p class="mb-3 text-mono-caps font-mono uppercase tracking-widest text-ink-500">
        {{ __('structure') }}
    </p>
    <h2 class="mb-4 text-body font-semibold text-ink-900">
        {{ $project->name }}
    </h2>

    <ol class="list-none space-y-0.5">
        @foreach ($project->chapters as $chapterIndex => $chapter)
            @php $chapterExpandedKey = 'chapter-'.$chapter->id; @endphp
            <li>
                <div class="flex items-center gap-1">
                    {{-- Klapp-Chevron. --}}
                    @if ($chapter->entries->isNotEmpty())
                        <button
                            type="button"
                            @click="toggle('{{ $chapterExpandedKey }}')"
                            :aria-expanded="isExpanded('{{ $chapterExpandedKey }}')"
                            :aria-label="isExpanded('{{ $chapterExpandedKey }}') ? '{{ __('collapse') }}' : '{{ __('expand') }}'"
                            class="flex size-5 shrink-0 items-center justify-center rounded text-ink-500 hover:bg-line-100"
                        >
                            <x-icon x-show="isExpanded('{{ $chapterExpandedKey }}')" name="chevron-down" size="4"/>
                            <x-icon x-show="!isExpanded('{{ $chapterExpandedKey }}')" name="chevron-right" size="4"/>
                        </button>
                    @else
                        <span class="inline-block size-5 shrink-0" aria-hidden="true"></span>
                    @endif

                    {{-- Kapitel-Link mit Nummer.
                         Der farbige Punkt aus Handoff v4 Screen 02
                         entfaellt: er wirkte visuell wie ein
                         Aufzaehlungszeichen und war doppelt gemoppelt
                         mit der Nummer + der aktiven Left-Kante. --}}
                    <a
                        href="#anchor_Chapter_{{ $chapter->id }}"
                        class="relative flex flex-1 items-center justify-between gap-2 rounded-md px-2 py-1 text-body font-semibold text-ink-900 hover:bg-line-100"
                        :aria-current="active === '#anchor_Chapter_{{ $chapter->id }}' ? 'true' : null"
                        :class="active === '#anchor_Chapter_{{ $chapter->id }}' && 'bg-tint-bg text-tint-text before:absolute before:left-[-6px] before:top-1 before:bottom-1 before:w-[3px] before:rounded before:bg-brand-bar'"
                    >
                        <span class="min-w-0 truncate">
                            <span class="text-ink-500">{{ $chapterIndex + 1 }} ·</span>
                            {{ $chapter->name }}
                        </span>
                        @php
                            // Zaehler nur fuer User mit comment-Recht — Reader ohne
                            // Kommentar-Berechtigung koennen mit der Zahl nichts anfangen
                            // und der Rail-Badge signalisiert ihnen ohnehin nichts
                            // (Phase 5x-Followup nach Karls Feedback).
                            $chapterOpen = Auth::user()?->can('comment', $project)
                                ? \App\Services\CommentCounter::openCountForCommentable(
                                    \App\Models\Chapter::class, $chapter->id,
                                )
                                : 0;
                        @endphp
                        @if ($chapterOpen > 0)
                            <span
                                class="inline-flex min-w-[1.15rem] shrink-0 items-center justify-center rounded-full
                                       bg-info-bg px-1 text-[10px] font-semibold leading-none text-info"
                                aria-label="{{ $chapterOpen }} {{ __('comment') }}"
                                title="{{ $chapterOpen }} {{ __('comment') }}"
                            >{{ $chapterOpen }}</span>
                        @endif
                    </a>
                </div>

                @if ($chapter->entries->isNotEmpty())
                    <ol
                        x-show="isExpanded('{{ $chapterExpandedKey }}')"
                        x-collapse
                        class="ml-6 mt-0.5 list-none space-y-0.5"
                    >
                        @foreach ($chapter->entries as $entry)
                            <li>
                                <a
                                    href="#anchor_Entry_{{ $entry->id }}"
                                    class="relative flex items-center justify-between gap-2 rounded-md px-2 py-1 text-body text-ink-700 hover:bg-line-100"
                                    :aria-current="active === '#anchor_Entry_{{ $entry->id }}' ? 'true' : null"
                                    :class="active === '#anchor_Entry_{{ $entry->id }}' && 'bg-tint-bg text-tint-text font-medium before:absolute before:left-[-6px] before:top-1 before:bottom-1 before:w-[3px] before:rounded before:bg-brand-bar'"
                                >
                                    <span class="min-w-0 truncate">{{ $entry->name }}</span>
                                    @php
                                        // Sichtbarkeits-Regel wie fuer den Chapter-Zaehler.
                                        $entryOpen = Auth::user()?->can('comment', $project)
                                            ? \App\Services\CommentCounter::openCountForCommentable(
                                                \App\Models\Entry::class, $entry->id,
                                            )
                                            : 0;
                                    @endphp
                                    @if ($entryOpen > 0)
                                        <span
                                            class="inline-flex min-w-[1.15rem] shrink-0 items-center justify-center rounded-full
                                                   bg-info-bg px-1 text-[10px] font-semibold leading-none text-info"
                                            aria-label="{{ $entryOpen }} {{ __('comment') }}"
                                            title="{{ $entryOpen }} {{ __('comment') }}"
                                        >{{ $entryOpen }}</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach

                        @can('update', $project)
                            <li>
                                <button
                                    type="button"
                                    onclick="window.dispatchEvent(new CustomEvent('entry-modal:open', { detail: { chapterId: {{ (int) $chapter->id }}, chapterName: @js((string) $chapter->name) } }))"
                                    class="add_entry ml-2 mt-1 inline-flex items-center gap-1 text-caption text-ink-500 hover:text-ink-900"
                                >
                                    <x-icon name="plus" size="4"/>
                                    <span>{{ __('add_entry') }}</span>
                                </button>
                            </li>
                        @endcan
                    </ol>
                @endif
            </li>
        @endforeach

        @can('update', $project)
            <li class="mt-3">
                <a
                    data-toggle="modal" data-target="#myModal"
                    class="add_chapter inline-flex cursor-pointer items-center gap-1 text-caption text-ink-500 hover:text-ink-900"
                >
                    <x-icon name="plus" size="4"/>
                    <span>{{ __('add_chapter') }}</span>
                </a>
            </li>
        @endcan
    </ol>
</nav>
