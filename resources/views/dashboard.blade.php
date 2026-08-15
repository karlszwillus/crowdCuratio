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
Phase-5-Backlog #70 (2026-08-16): Skelett-Ladezustand — die vier
Sektionen laufen jetzt ueber <livewire:dashboard-sections> mit
#[Lazy]. Der Controller lieft nur den Chrome-Kontext (Greeting,
Suche, „+ Neues Projekt"); die Daten-Feeds und Empty-States sitzen
in der Volt-Component.
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

            {{-- Sektionen laden ueber Livewire-Lazy — Skelett-Ladezustand
                 sichtbar bis Livewire hydratisiert (Backlog #70). --}}
            <livewire:dashboard-sections/>

        </div>
    </x-slot:content>
</x-layout>
