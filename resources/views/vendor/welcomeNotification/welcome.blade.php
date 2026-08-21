{{--
crowdCuratio - Curating together virtually
Copyright (C)2022, 2026 - berlinHistory e.V.

B12 (2026-08-20): Welcome-Notification-Sicht (Spatie-Package) auf
layouts/guest gezogen und auf Design v7 gebracht.
--}}

@extends('layouts.guest')

@section('title', __('welcome_set_password_title'))
@section('subtitle', __('welcome_set_password_subtitle'))

@section('content')

    @if ($errors->any())
        <x-ui.banner type="danger" class="mt-6">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-ui.banner>
    @endif

    <form method="POST" class="mt-8 space-y-4">
        @csrf

        <input type="hidden" name="email" value="{{ $user->email }}"/>

        <div>
            <label for="password" class="mb-1 block text-caption font-medium text-ink-700">
                {{ __('password') }} <span class="text-danger" aria-hidden="true">*</span>
            </label>
            <input id="password" name="password" type="password"
                   required autofocus autocomplete="new-password"
                   class="w-full rounded-md border border-form-border bg-paper-0 px-3 py-2.5 text-body text-ink-900 focus:border-primary focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary">
        </div>

        <div>
            <label for="password_confirmation" class="mb-1 block text-caption font-medium text-ink-700">
                {{ __('confirm_password') }} <span class="text-danger" aria-hidden="true">*</span>
            </label>
            <input id="password_confirmation" name="password_confirmation" type="password"
                   required autocomplete="new-password"
                   class="w-full rounded-md border border-form-border bg-paper-0 px-3 py-2.5 text-body text-ink-900 focus:border-primary focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary">
        </div>

        <label for="policy" class="flex items-start gap-2 text-body text-ink-700">
            <input id="policy" name="policy" type="checkbox" value="1"
                   class="mt-1 size-4 rounded border-form-border text-primary focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-primary"/>
            <span>
                {!! __('welcome_consent_html', [
                    'terms' => '<a href="'.route('auth.terms').'" target="_blank" rel="noopener" class="text-tint-text hover:underline">'.__('terms').'</a>',
                    'policy' => '<a href="'.route('auth.policy').'" target="_blank" rel="noopener" class="text-tint-text hover:underline">'.__('privacy_policy').'</a>',
                ]) !!}
            </span>
        </label>

        <button type="submit"
                class="w-full rounded-md bg-primary px-4 py-3 text-body font-semibold text-primary-on hover:opacity-90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-bar">
            {{ __('save_password_login') }}
        </button>
    </form>

@endsection
