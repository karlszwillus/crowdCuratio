{{--
crowdCuratio - Curating together virtually
Copyright (C)2022, 2026 - berlinHistory e.V.

B12 (2026-08-20): Auth-Sicht auf layouts/guest gezogen.
--}}

@extends('layouts.guest')

@section('title', __('reset_password_title'))

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

    <form method="POST" action="{{ route('password.update') }}" class="mt-8 space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label for="email" class="mb-1 block text-caption font-medium text-ink-700">
                {{ __('email') }} <span class="text-danger" aria-hidden="true">*</span>
            </label>
            <input id="email" name="email" type="email"
                   value="{{ old('email', $request->email) }}"
                   required autofocus autocomplete="email"
                   class="w-full rounded-md border border-form-border bg-paper-0 px-3 py-2.5 text-body text-ink-900 focus:border-primary focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary">
        </div>

        <div>
            <label for="password" class="mb-1 block text-caption font-medium text-ink-700">
                {{ __('password') }} <span class="text-danger" aria-hidden="true">*</span>
            </label>
            <input id="password" name="password" type="password"
                   required autocomplete="new-password"
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

        <button type="submit"
                class="w-full rounded-md bg-primary px-4 py-3 text-body font-semibold text-primary-on hover:opacity-90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-bar">
            {{ __('reset_password') }}
        </button>
    </form>

@endsection
