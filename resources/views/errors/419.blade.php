{{--
crowdCuratio - Curating together virtually
Copyright (C)2026 - berlinHistory e.V.

Phase 5e.5-Followup: 419-Fehlerseite (CSRF-Token abgelaufen).
Analog zu 403/404/500, zusaetzlicher „Neu laden"-Sekundaer-CTA,
weil der Nutzer meist noch eingeloggt ist und den letzten Screen
zurueck haben will.
--}}

@extends('projects.layout')

@section('content')
    <div class="mx-auto flex max-w-2xl flex-col items-center justify-center gap-6 px-6 py-16 text-center">
        <div class="flex size-16 items-center justify-center rounded-full bg-warning-bg text-warning">
            <x-icon name="clock" size="6"/>
        </div>

        <div class="font-mono text-mono-caps uppercase tracking-widest text-ink-500">
            {{ __('error') }} · 419
        </div>

        <h1 class="text-title font-semibold text-ink-900">{{ __('error_419_title') }}</h1>

        <p class="max-w-lg text-body text-ink-700">{{ __('error_419_body') }}</p>

        <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
            <button
                type="button"
                onclick="window.location.reload()"
                class="inline-flex items-center gap-1.5 rounded-md bg-primary px-4 py-2 text-body font-medium text-primary-on
                       hover:opacity-90
                       focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
            >
                <x-icon name="rotate-cw" size="4"/>
                {{ __('error_reload') }}
            </button>

            @auth
                <a href="{{ route('dashboard') }}"
                   class="inline-flex items-center gap-1.5 rounded-md bg-transparent px-4 py-2 text-body font-medium text-ink-700
                          hover:bg-ink-900/5
                          focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink-900">
                    {{ __('start') }}
                </a>
            @else
                <a href="{{ route('login') }}"
                   class="inline-flex items-center gap-1.5 rounded-md bg-transparent px-4 py-2 text-body font-medium text-ink-700
                          hover:bg-ink-900/5
                          focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink-900">
                    {{ __('error_back_to_login') }}
                </a>
            @endauth
        </div>
    </div>
@endsection
