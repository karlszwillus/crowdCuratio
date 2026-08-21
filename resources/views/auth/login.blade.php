{{--
crowdCuratio - Curating together virtually
Copyright (C)2022, 2026 - berlinHistory e.V.

B12 (2026-08-20): Login-Sicht auf layouts/guest gezogen. Der frueher
inline im Blade lebende Split-Layout-Rahmen ist jetzt im Guest-Layout,
diese Sicht liefert nur noch den Formular-Content.
--}}

@extends('layouts.guest')

@section('title', __('login_title'))
@section('subtitle', __('login_subtitle'))

@section('content')

    @if (session('status'))
        <x-ui.banner type="success" class="mt-6">
            {{ session('status') }}
        </x-ui.banner>
    @endif

    @if ($errors->any())
        <x-ui.banner type="danger" class="mt-6">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-ui.banner>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-4">
        @csrf

        <div>
            <label for="email" class="mb-1 block text-caption font-medium text-ink-700">
                {{ __('email') }} <span class="text-danger" aria-hidden="true">*</span>
            </label>
            <input id="email" name="email" type="email"
                   value="{{ old('email') }}"
                   required autofocus autocomplete="email" aria-required="true"
                   class="w-full rounded-md border border-form-border bg-paper-0 px-3 py-2.5 text-body text-ink-900 focus:border-primary focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary">
        </div>

        {{-- Passwort ohne „anzeigen"-Toggle, damit Password-Manager
             sauber greifen. --}}
        <div>
            <div class="mb-1 flex items-center justify-between">
                <label for="password" class="text-caption font-medium text-ink-700">
                    {{ __('password') }} <span class="text-danger" aria-hidden="true">*</span>
                </label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}"
                       class="text-caption text-tint-text hover:underline">
                        {{ __('forgot_password') }}
                    </a>
                @endif
            </div>
            <input id="password" name="password" type="password"
                   required autocomplete="current-password" aria-required="true"
                   class="w-full rounded-md border border-form-border bg-paper-0 px-3 py-2.5 text-body text-ink-900 focus:border-primary focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary">
        </div>

        <label for="remember_me" class="flex items-center gap-2 text-body text-ink-700">
            <input id="remember_me" name="remember" type="checkbox"
                   class="size-4 rounded border-form-border text-primary focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-primary">
            <span>{{ __('stay_logged_in') }}</span>
        </label>

        <button type="submit"
                class="w-full rounded-md bg-primary px-4 py-3 text-body font-semibold text-primary-on hover:opacity-90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-bar">
            {{ __('login') }}
        </button>
    </form>

    <p class="mt-6 text-center text-caption text-ink-500">
        {{ __('no_account') }}
        <span class="font-medium text-ink-700">{{ __('invite_only') }}</span>
    </p>

@endsection
