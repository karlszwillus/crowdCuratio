{{--
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

Q4-Etappe 8 · E3e (2026-09-12): Rollen-Anlage von Bootstrap-3
auf Tailwind umgezogen. Frueher spannte das `<form>` sich ueber zwei
`@section`-Bloecke (main + sidebar); jetzt ein zusammenhaengendes
Formular mit Aktions-Reihe unten.
--}}

@extends('projects.layout')

@section('main')
    <div class="mx-auto max-w-2xl">
        <h2 class="mb-6 text-title font-semibold text-ink-900">{{ __('create_new_role') }}</h2>

        @if (count($errors) > 0)
            <x-ui.banner type="danger" class="mb-4" :title="__('whoops')">
                {{ __('message_problem_input') }}
                <ul class="mt-2 list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-ui.banner>
        @endif

        <form action="{{ route('roles.store') }}" method="POST"
              class="rounded-md border border-line-200 bg-paper-0 p-6 shadow-subtle">
            @csrf

            <div class="mb-5">
                <label for="role-name" class="mb-1 block text-caption font-medium text-ink-700">
                    {{ __('name') }} <span class="text-danger" aria-hidden="true">*</span>
                </label>
                <input id="role-name" name="name" type="text"
                       value="{{ old('name') }}"
                       required
                       class="w-full rounded-md border border-line-200 bg-canvas-bg px-3 py-2 text-body text-ink-900 focus:border-primary focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary"/>
            </div>

            <fieldset class="mb-5">
                <legend class="mb-2 text-caption font-medium text-ink-700">{{ __('permission') }}</legend>
                <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                    @foreach ($permission as $value)
                        <label class="inline-flex items-start gap-2 rounded-md border border-line-200 bg-canvas-bg px-3 py-2 text-body text-ink-900 hover:bg-line-100">
                            <input type="checkbox"
                                   name="permission[]"
                                   value="{{ $value->permission_id }}"
                                   @checked(is_array(old('permission')) && in_array($value->permission_id, old('permission')))
                                   class="mt-1 size-4 rounded border-line-200 text-primary focus:ring-primary"/>
                            <span>{{ $value->description }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div class="flex flex-wrap items-center justify-end gap-2 border-t border-line-200 pt-4">
                <a href="{{ route('roles.index') }}"
                   class="inline-flex items-center rounded-md border border-line-200 bg-canvas-bg px-4 py-2 text-body text-ink-900 hover:bg-line-100">
                    {{ __('back') }}
                </a>
                <button type="submit"
                        class="inline-flex items-center rounded-md bg-primary px-4 py-2 text-body font-medium text-primary-on hover:opacity-90">
                    {{ __('submit') }}
                </button>
            </div>
        </form>
    </div>
@endsection
