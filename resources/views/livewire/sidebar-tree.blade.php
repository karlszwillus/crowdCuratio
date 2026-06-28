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
use Livewire\Volt\Component;

new class extends Component
{
    public Project $project;

    public function mount(Project $project): void
    {
        // Eager-Load nur was die Sidebar braucht — Kapitel und
        // Abschnitte, keine Inhalte (3 Ebenen laut Phase-5b § 2.4).
        // loadMissing greift nur, wenn die Relation noch nicht
        // geladen ist; ProjectController::edit lädt bereits via
        // withEditTree, dann ist das hier ein No-Op.
        $this->project = $project->loadMissing(['chapters.entries']);
    }
}; ?>

<nav aria-label="{{ __('project_structure') }}" class="text-body">
    <ol class="space-y-1">
        <li>
            <a
                href="#main-content"
                class="block rounded-md px-2 py-1 font-medium text-ink-900 hover:bg-chrome-active"
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
