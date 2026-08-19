<!--
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

If not, see <https://www.gnu.org/licenses/>. -->

@extends('projects.layout')

@section('content')

    @php
        // Rollen-Filter-Chips (Phase 5d.3, analog Projektliste 5-D.4).
        // Wir zaehlen pro Rolle, damit die Persona auf einen Blick
        // sieht, wieviele Editor:innen vs. Leser:innen im System sind.
        $data ??= collect();
        $counts = [
            'all'      => $data->count(),
            'admin'    => $data->where('role', \App\Support\RoleName::ADMIN->value)->count(),
            'editor'   => $data->where('role', \App\Support\RoleName::EDITOR->value)->count(),
            'reviewer' => $data->where('role', \App\Support\RoleName::REVIEWER->value)->count(),
            'reader'   => $data->where('role', \App\Support\RoleName::READER->value)->count(),
        ];

        // Rollen-Chip-Meta: Farbe pro Rolle (Design-Token-basiert).
        // Admin nutzt danger-Ton fuer visuellen Anker („hoechste
        // Rechte"), die anderen die neutralen info/success/warning.
        $roleMeta = [
            \App\Support\RoleName::ADMIN->value    => ['label' => __('role_admin'),    'variant' => 'danger'],
            \App\Support\RoleName::EDITOR->value   => ['label' => __('role_editor'),   'variant' => 'success'],
            \App\Support\RoleName::REVIEWER->value => ['label' => __('role_reviewer'), 'variant' => 'info'],
            \App\Support\RoleName::READER->value   => ['label' => __('role_reader'),   'variant' => 'warning'],
        ];

        // Rollen → Filter-Key-Map fuer die x-show-Ausdruecke unten.
        $roleFilterKey = [
            \App\Support\RoleName::ADMIN->value    => 'admin',
            \App\Support\RoleName::EDITOR->value   => 'editor',
            \App\Support\RoleName::REVIEWER->value => 'reviewer',
            \App\Support\RoleName::READER->value   => 'reader',
        ];
    @endphp

    <div x-data="{ filter: 'all', search: '' }">

        @if ($message = Session::get('success'))
            <div class="mb-4 rounded-md border border-success-bg bg-success-bg/50 px-4 py-2 text-body text-success"
                 role="status" aria-live="polite">
                {{ $message }}
            </div>
        @endif

        {{-- Screen-Kopf: Titel + Suche + „Neue:r Nutzer:in". --}}
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-title font-semibold text-ink-900">
                {{ __('users') }}
            </h1>

            <div class="flex items-center gap-3">
                <div class="relative w-64">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-ink-500">
                        <x-icon name="search" size="4"/>
                    </span>
                    <label for="userSearch" class="sr-only">{{ __('search_users') }}</label>
                    <input
                        id="userSearch"
                        type="search"
                        x-model="search"
                        placeholder="{{ __('search_users') }}"
                        class="block w-full rounded-md border border-line-200 bg-paper-50 py-2 pl-9 pr-3
                               text-body text-ink-900 placeholder:text-ink-500
                               focus:border-primary focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary"
                    />
                </div>

                <a
                    href="{{ route('register') }}"
                    class="inline-flex items-center gap-1.5 rounded-md bg-primary px-3.5 py-2
                           text-body font-medium text-primary-on hover:opacity-90
                           focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-bar"
                >
                    <x-icon name="plus" size="4"/>
                    {{ __('new_user') }}
                </a>
            </div>
        </div>

        {{-- Filter-Chips mit Zaehlern nach Rolle. --}}
        <div class="mb-4 flex flex-wrap items-center gap-2" role="tablist" aria-label="{{ __('role') }}">
            @foreach ([
                'all'      => __('filter_all'),
                'admin'    => __('role_admin'),
                'editor'   => __('role_editor'),
                'reviewer' => __('role_reviewer'),
                'reader'   => __('role_reader'),
            ] as $key => $label)
                <button
                    type="button"
                    role="tab"
                    @click="filter = '{{ $key }}'"
                    :aria-selected="filter === '{{ $key }}'"
                    :class="filter === '{{ $key }}'
                        ? 'bg-ink-900 text-paper-0 border-ink-900'
                        : 'bg-paper-0 text-ink-500 border-line-200 hover:border-ink-400'"
                    class="inline-flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-body transition-colors
                           focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-bar"
                >
                    <span>{{ $label }}</span>
                    <span class="text-ink-500" :class="filter === '{{ $key }}' ? 'text-paper-0/70' : ''">
                        · {{ $counts[$key] }}
                    </span>
                </button>
            @endforeach
        </div>

        {{-- Nutzer:innen-Tabelle als CSS-Grid.
             Spalten: Nutzer:in (2.2fr) · E-Mail (2fr) · Rolle (1.1fr)
             · Status (1fr) · Aktionen (0.7fr). --}}
        <div class="overflow-hidden rounded-lg border border-line-200 bg-paper-0">
            {{-- Kopf --}}
            <div class="grid grid-cols-[2.2fr_2fr_1.1fr_1fr_0.7fr] gap-4 border-b border-line-200 bg-paper-50 px-5 py-3
                        text-mono-caps font-mono uppercase tracking-widest text-ink-500"
                 role="row">
                <div>{{ __('name') }}</div>
                <div>{{ __('email') }}</div>
                <div>{{ __('role') }}</div>
                <div>{{ __('status') }}</div>
                <div class="sr-only">{{ __('actions') }}</div>
            </div>

            {{-- Zeilen --}}
            @if ($data->isEmpty())
                <div class="px-5 py-8 text-center text-body text-ink-500">
                    {{ __('no_users_yet') }}
                </div>
            @else
                <ul>
                    @foreach ($data as $user)
                        @php
                            $roleValue = $user->role ?? '';
                            $meta = $roleMeta[$roleValue] ?? ['label' => $roleValue, 'variant' => 'info'];
                            $badgeClass = [
                                'success' => 'bg-success-bg text-success',
                                'warning' => 'bg-warning-bg text-warning',
                                'info'    => 'bg-info-bg text-info',
                                'danger'  => 'bg-danger-bg text-danger',
                            ][$meta['variant']] ?? 'bg-info-bg text-info';

                            $fullName = trim(($user->name ?? '').' '.($user->last_name ?? ''));

                            $dataRole = $roleFilterKey[$roleValue] ?? 'other';
                            $isPending = ! is_null($user->welcome_valid_until);
                            $isSelf = auth()->user()->id === $user->id;
                        @endphp

                        <li
                            x-show="(filter === 'all' || filter === '{{ $dataRole }}')
                                    && (search === ''
                                        || '{{ addslashes($fullName) }}'.toLowerCase().includes(search.toLowerCase())
                                        || '{{ addslashes($user->email ?? '') }}'.toLowerCase().includes(search.toLowerCase()))"
                            class="grid grid-cols-[2.2fr_2fr_1.1fr_1fr_0.7fr] items-center gap-4 border-b border-line-100 px-5 py-3.5
                                   last:border-b-0 hover:bg-paper-50"
                            role="row"
                        >
                            {{-- Nutzer:in (Avatar-Initialen + Name) --}}
                            <a href="{{ route('users.edit', $user->id) }}"
                               class="flex items-center gap-3 text-ink-900 rounded-md
                                      focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-bar">
                                <x-ui.user-avatar :user="$user" size="8" text="text-caption font-semibold"/>
                                <span class="min-w-0 truncate text-body font-medium text-ink-900">
                                    {{ $fullName }}
                                </span>
                            </a>

                            {{-- E-Mail --}}
                            <div class="truncate text-body text-ink-700">
                                {{ $user->email }}
                            </div>

                            {{-- Rolle als Chip --}}
                            <div>
                                <span class="{{ $badgeClass }} inline-flex items-center gap-1.5 rounded-pill px-2.5 py-0.5 text-caption font-medium">
                                    <span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>
                                    {{ $meta['label'] }}
                                </span>
                            </div>

                            {{-- Status: entweder aktiv oder Einladung ausstehend --}}
                            <div class="text-caption text-ink-500">
                                @if ($isPending)
                                    <span class="inline-flex items-center gap-1.5">
                                        <x-icon name="clock" size="4"/>
                                        {{ __('pending_invitation') }}
                                    </span>
                                @else
                                    <span class="text-ink-500">—</span>
                                @endif
                            </div>

                            {{-- Aktionen: Edit, Resend-Invitation (falls pending), Delete --}}
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('users.edit', $user->id) }}"
                                   title="{{ __('edit_user') }}"
                                   class="inline-flex size-11 items-center justify-center rounded-md text-ink-500
                                          hover:bg-line-100/40 hover:text-ink-700
                                          focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-bar">
                                    <x-icon name="pencil" size="4"/>
                                </a>

                                @if ($isPending)
                                    <a href="{{ route('resend.invitation', $user->id) }}"
                                       title="{{ __('resend_invitation') }}"
                                       class="inline-flex size-11 items-center justify-center rounded-md text-ink-500
                                              hover:bg-line-100/40 hover:text-ink-700
                                              focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-bar">
                                        <x-icon name="mail" size="4"/>
                                    </a>
                                @endif

                                @if (! $isSelf)
                                    <form action="{{ route('users.destroy', $user->id) }}" method="POST"
                                          onsubmit="return confirm('{{ __('message_delete_confirm') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                title="{{ __('delete_user') }}"
                                                class="inline-flex size-11 items-center justify-center rounded-md text-ink-500
                                                       hover:bg-danger-bg hover:text-danger
                                                       focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger">
                                            <x-icon name="trash-2" size="4"/>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

    </div>
@endsection
