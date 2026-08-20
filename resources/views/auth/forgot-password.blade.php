{{--
crowdCuratio - Curating together virtually
Copyright (C)2022, 2026 - berlinHistory e.V.

B12 (2026-08-20): Auth-Sicht auf layouts/guest gezogen und auf
Design v7 gebracht (analog Login).
--}}

@extends('layouts.guest')

@section('title', __('forgot_password'))
@section('subtitle', __('message_forgot_password'))

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

    <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-4">
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

        <button type="submit"
                class="w-full rounded-md bg-primary px-4 py-3 text-body font-semibold text-primary-on hover:opacity-90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-bar">
            {{ __('email_password_reset') }}
        </button>
    </form>

    <p class="mt-6 text-center text-caption text-ink-500">
        <a href="{{ route('login') }}" class="text-tint-text hover:underline">
            {{ __('login_back_to_login') }}
        </a>
    </p>

@endsection
