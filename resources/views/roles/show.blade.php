{{--
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

Q4-Etappe 8 · E3e (2026-09-12): Rollen-Detail von Bootstrap-3
auf Tailwind umgezogen; sichtbare Permissions als Chip-Liste
statt inline-Kommata.
--}}

@extends('projects.layout')

@section('main')
    <div class="mx-auto max-w-2xl">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-title font-semibold text-ink-900">{{ $role->name }}</h2>
            <a href="{{ route('roles.index') }}"
               class="inline-flex items-center rounded-md border border-line-200 bg-canvas-bg px-4 py-2 text-body text-ink-900 hover:bg-line-100">
                {{ __('back') }}
            </a>
        </div>

        <section class="rounded-md border border-line-200 bg-paper-0 p-6 shadow-subtle">
            <h3 class="mb-2 text-caption font-semibold uppercase tracking-wider text-ink-500">
                {{ __('permission') }}
            </h3>
            @if (! empty($rolePermissions))
                <ul class="flex flex-wrap gap-2">
                    @foreach ($rolePermissions as $v)
                        <li class="inline-flex items-center rounded-full bg-success-bg px-3 py-1 text-caption text-success">
                            {{ $v->name }}
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-body text-ink-500">{{ __('role_no_permissions') }}</p>
            @endif
        </section>
    </div>
@endsection
