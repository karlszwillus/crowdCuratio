{{--
crowdCuratio - Curating together virtually
Copyright (C)2022, 2026 - berlinHistory e.V.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Q3-Politur B1 (2026-08-20 / 5ae): Admin-User-Edit auf Design-v7.
Nach dem Profil-Muster: Header mit sr-only H1, Karten mit
<section>+<h2>, Sticky-Save-Footer mit Pending-Label und
submitting-State, Alpine statt jQuery, Fehler als Banner.
--}}

@extends('projects.layout')

@section('main')

    {{-- Q3-Politur B1 / LIVE-UX-02: sr-only H1 als Screenreader-
         Ausgangspunkt. --}}
    <h1 class="sr-only">
        {{ __('users_edit_h1', ['name' => trim(($user->name ?? '').' '.($user->last_name ?? ''))]) }}
    </h1>

    @if ($message = Session::get('success'))
        <x-ui.banner type="success" class="mb-4" dismissible>
            {{ $message }}
        </x-ui.banner>
    @endif

    @if ($errors->any())
        <x-ui.banner type="danger" class="mb-4" :title="__('whoops')">
            {{ __('message_problem_input') }}
            <ul class="mt-2 list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-ui.banner>
    @endif

    @php
        $currentRole = $user->getRoleNames()->first() ?? '';
    @endphp

    <form method="POST" action="{{ route('users.update', $user->id) }}"
          x-data="{
              isAdmin: @js((bool) $user->is_admin),
              submitting: false,
              _dirty: false,
          }"
          @input.capture="_dirty = true"
          @change.capture="_dirty = true"
          @submit="submitting = true">
        @csrf
        @method('PUT')

        {{-- Karte 1 · Person --}}
        <section class="mb-4 rounded-md border border-line-200 bg-paper-0 p-5">
            <header class="mb-4">
                <h2 class="text-heading font-semibold text-ink-900">
                    {{ __('users_edit_card_person_title') }}
                </h2>
            </header>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="firstName" class="mb-1 block text-caption font-semibold text-ink-700">
                        {{ __('first_name') }} <span class="text-danger" aria-hidden="true">*</span>
                    </label>
                    <input id="firstName" name="firstName" type="text" required autofocus
                           value="{{ old('firstName', $user->name) }}"
                           @class([
                               'block w-full rounded-md border bg-paper-0 px-3 py-2 text-body text-ink-900 focus:border-primary focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary',
                               'border-danger' => $errors->has('firstName'),
                               'border-line-200' => ! $errors->has('firstName'),
                           ])/>
                    @error('firstName')
                        <p class="mt-1 text-caption text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="lastName" class="mb-1 block text-caption font-semibold text-ink-700">
                        {{ __('last_name') }} <span class="text-danger" aria-hidden="true">*</span>
                    </label>
                    <input id="lastName" name="lastName" type="text" required
                           value="{{ old('lastName', $user->last_name) }}"
                           @class([
                               'block w-full rounded-md border bg-paper-0 px-3 py-2 text-body text-ink-900 focus:border-primary focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary',
                               'border-danger' => $errors->has('lastName'),
                               'border-line-200' => ! $errors->has('lastName'),
                           ])/>
                    @error('lastName')
                        <p class="mt-1 text-caption text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <span class="mb-1 block text-caption font-semibold text-ink-700">
                        {{ __('email') }}
                    </span>
                    <div class="rounded-md border border-dashed border-line-300 bg-canvas-bg px-3 py-2">
                        <span class="block truncate text-body text-ink-900">{{ $user->email }}</span>
                    </div>
                    <p class="mt-1 text-caption text-ink-500">{{ __('users_edit_email_hint') }}</p>
                </div>
            </div>
        </section>

        {{-- Karte 2 · Rolle & Rechte --}}
        <section class="mb-4 rounded-md border border-line-200 bg-paper-0 p-5">
            <header class="mb-4">
                <h2 class="text-heading font-semibold text-ink-900">
                    {{ __('role_right') }}
                </h2>
                <p class="mt-1 text-body text-ink-500">
                    {{ __('users_edit_role_desc') }}
                </p>
            </header>

            <label for="hasAdminRight" class="inline-flex items-center gap-2">
                <input type="checkbox" id="hasAdminRight" name="adminUser" value="1"
                       x-model="isAdmin"
                       class="rounded border-line-300 text-primary shadow-sm focus:border-primary focus:ring focus:ring-primary/30"/>
                <span class="text-body text-ink-900">{{ __('is_admin') }}</span>
            </label>

            <div x-show="! isAdmin" x-cloak class="mt-6 space-y-4 border-t border-line-100 pt-4">
                <p class="text-caption text-ink-500">{{ __('when_not') }}</p>

                <div>
                    <label for="lblRole" class="mb-1 block text-caption font-semibold text-ink-700">
                        {{ __('role') }}
                    </label>
                    @php
                        $roleLabels = [
                            App\Support\RoleName::EDITOR->value   => __('role_editor'),
                            App\Support\RoleName::REVIEWER->value => __('role_reviewer'),
                            App\Support\RoleName::READER->value   => __('role_reader'),
                        ];
                    @endphp
                    <select name="roles[]" id="lblRole"
                            class="block w-full rounded-md border border-line-200 bg-paper-0 px-3 py-2 text-body text-ink-900 focus:border-primary focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary">
                        @foreach ($roleLabels as $key => $label)
                            <option value="{{ $key }}" @selected($currentRole === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="createProject" value="1"
                           @checked($user->create_project)
                           class="rounded border-line-300 text-primary shadow-sm focus:border-primary focus:ring focus:ring-primary/30"/>
                    <span class="text-body text-ink-900">{{ __('create_project') }}</span>
                </label>
            </div>
        </section>

        {{-- Sticky-Save-Footer analog Profil-Muster. --}}
        <div class="fixed inset-x-0 bottom-0 z-20 border-t border-line-200 bg-paper-0/95 shadow-medium backdrop-blur"
             role="region"
             aria-label="{{ __('users_edit_sticky_region_label') }}">
            <div class="mx-auto flex max-w-4xl items-center justify-between gap-4 px-6 py-3">
                <p class="text-caption text-ink-500">
                    <span x-show="!_dirty">{{ __('users_edit_no_pending') }}</span>
                    <span x-show="_dirty" x-cloak>{{ __('users_edit_pending') }}</span>
                </p>
                <div class="flex gap-2">
                    <a href="{{ route('users.index') }}"
                       class="rounded-md border border-ink-300 bg-canvas-bg px-3 py-1.5 text-caption text-ink-900 hover:bg-chrome-active">
                        {{ __('cancel') }}
                    </a>
                    <button type="submit"
                            :disabled="submitting"
                            class="rounded-md bg-primary px-4 py-1.5 text-caption font-semibold text-paper-0 hover:opacity-90 disabled:opacity-40">
                        <span x-show="!submitting">{{ __('save') }}</span>
                        <span x-show="submitting" x-cloak>{{ __('save') }} …</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Reservat unter dem Sticky-Footer, damit der letzte Inhalt
             nicht dahinter verschwindet. --}}
        <div class="h-16" aria-hidden="true"></div>
    </form>

@endsection
