{{--
crowdCuratio - Curating together virtually
Copyright (C)2022, 2026 - berlinHistory e.V.

B12 (2026-08-20): Auth-Sicht auf layouts/guest gezogen.
--}}

@extends('layouts.guest')

@section('title', __('confirm_password_title'))
@section('subtitle', __('message_confirm_password'))

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

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-8 space-y-4">
        @csrf

        <div>
            <label for="password" class="mb-1 block text-caption font-medium text-ink-700">
                {{ __('password') }} <span class="text-danger" aria-hidden="true">*</span>
            </label>
            <input id="password" name="password" type="password"
                   required autofocus autocomplete="current-password"
                   class="w-full rounded-md border border-form-border bg-paper-0 px-3 py-2.5 text-body text-ink-900 focus:border-primary focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary">
        </div>

        <button type="submit"
                class="w-full rounded-md bg-primary px-4 py-3 text-body font-semibold text-primary-on hover:opacity-90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-bar">
            {{ __('confirm') }}
        </button>
    </form>

@endsection
