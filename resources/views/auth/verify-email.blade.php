{{--
crowdCuratio - Curating together virtually
Copyright (C)2022, 2026 - berlinHistory e.V.

B12 (2026-08-20): Auth-Sicht auf layouts/guest gezogen.
--}}

@extends('layouts.guest')

@section('title', __('verify_email_title'))
@section('subtitle', __('message_thankyou'))

@section('content')

    @if (session('status') === 'verification-link-sent')
        <x-ui.banner type="success" class="mt-6">
            {{ __('message_new_verification') }}
        </x-ui.banner>
    @endif

    <div class="mt-8 flex flex-col gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit"
                    class="w-full rounded-md bg-primary px-4 py-3 text-body font-semibold text-primary-on hover:opacity-90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-bar">
                {{ __('resend_verification') }}
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="w-full rounded-md border border-ink-300 bg-canvas-bg px-4 py-3 text-body text-ink-900 hover:bg-chrome-active">
                {{ __('logout') }}
            </button>
        </form>
    </div>

@endsection
