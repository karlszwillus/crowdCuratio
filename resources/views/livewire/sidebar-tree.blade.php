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
}; ?>

<nav
    aria-label="{{ __('project_structure') }}"
    class="text-body"
    x-data="{ active: window.location.hash }"
    @hashchange.window="active = window.location.hash"
>
    <ol class="space-y-1">
        <li>
            <a
                href="#main-content"
                class="block rounded-md px-2 py-1 font-medium text-ink-900 hover:bg-chrome-active"
                :aria-current="active === '#main-content' || active === '' ? 'page' : null"
                :class="(active === '#main-content' || active === '') && 'bg-chrome-active'"
            >
                {{ $project->name }}
            </a>

            @if ($project->chapters->isNotEmpty())
                <ol class="ml-3 mt-1 space-y-1 border-l border-ink-400 pl-3">
                    @foreach ($project->chapters as $chapter)
                        <li>
                            <a
                                href="#anchor_Chapter_{{ $chapter->id }}"
                                class="block rounded-md px-2 py-1 text-ink-800 hover:bg-chrome-active"
                                :aria-current="active === '#anchor_Chapter_{{ $chapter->id }}' ? 'true' : null"
                                :class="active === '#anchor_Chapter_{{ $chapter->id }}' && 'bg-chrome-active font-medium'"
                            >
                                {{ $chapter->name }}
                            </a>

                            @if ($chapter->entries->isNotEmpty())
                                <ol class="ml-3 mt-1 space-y-1 border-l border-ink-400 pl-3">
                                    @foreach ($chapter->entries as $entry)
                                        <li>
                                            <a
                                                href="#anchor_Entry_{{ $entry->id }}"
                                                class="block rounded-md px-2 py-1 text-ink-700 hover:bg-chrome-active"
                                                :aria-current="active === '#anchor_Entry_{{ $entry->id }}' ? 'true' : null"
                                                :class="active === '#anchor_Entry_{{ $entry->id }}' && 'bg-chrome-active font-medium'"
                                            >
                                                {{ $entry->name }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ol>
                            @endif
                        </li>
                    @endforeach
                </ol>
            @endif
        </li>
    </ol>
</nav>
