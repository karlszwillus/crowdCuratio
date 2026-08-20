{{--
crowdCuratio - Curating together virtually
Copyright (C)2022, 2026 - berlinHistory e.V.

B12 (2026-08-20): Guest-Layout fuer die unauthenticated Auth-Sichten.
Aus dem Login-Standalone-HTML extrahiert und als eigenes Layout
verfuegbar gemacht. Split-Layout nach Handoff v4 § Screen 04: links
Marken-Panel, rechts Formular-Slot.

Slots:
- @yield('title')   — Titel fuer <title> und den Formular-Kopf
- @yield('subtitle') — optionaler Untertitel unter dem Titel
- @yield('content') — Formular-Inhalt selbst (Banner, Form, Hinweise)
--}}

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>crowdCuratio · @yield('title', __('login'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-paper-0 text-ink-900 antialiased">

    <div class="flex min-h-screen flex-col md:flex-row">

        {{-- Marken-Panel (dark, links) --}}
        <aside
            class="flex flex-col justify-between bg-ink-900 px-12 py-12
                   text-on-dark-100 md:w-[520px] md:shrink-0"
            aria-labelledby="brand-panel-heading"
        >
            <div class="flex items-center gap-3">
                <div class="flex size-11 items-center justify-center rounded-md bg-logo-bg text-logo-on">
                    <span class="text-title font-semibold leading-none">c</span>
                </div>
                <span class="text-heading font-medium tracking-tight">
                    <span class="font-light">crowd</span><span class="font-semibold">Curatio</span>
                </span>
            </div>

            <div class="max-w-md">
                <div class="mb-6 flex size-24 items-center justify-center rounded-lg bg-logo-bg text-logo-on">
                    <span class="text-[80px] font-semibold leading-none">c</span>
                </div>
                <h1 id="brand-panel-heading" class="text-title font-semibold leading-tight tracking-tight text-on-dark-100">
                    {{ __('brand_claim') }}
                </h1>
                <p class="mt-4 text-body leading-relaxed text-on-dark-300">
                    {{ __('brand_body') }}
                </p>
            </div>

            <div>
                <p class="text-mono-caps font-mono uppercase tracking-widest text-on-dark-300">
                    {{ __('used_by') }}
                </p>
                <p class="mt-1 text-body text-on-dark-100">
                    berlinHistory e.&nbsp;V.
                    <span class="mx-2 text-on-dark-300">·</span>
                    Aktives Museum
                </p>
            </div>
        </aside>

        {{-- Formular-Panel (hell, rechts) --}}
        <main
            role="main"
            class="relative flex flex-1 flex-col items-center justify-center px-6 py-12 md:px-12"
        >
            {{-- Sprach-Select oben rechts --}}
            <div x-data="{ open: false }" class="absolute right-6 top-6">
                <button
                    type="button"
                    @click="open = !open"
                    @click.outside="open = false"
                    aria-haspopup="true"
                    :aria-expanded="open"
                    class="inline-flex items-center gap-1 rounded-md px-3 py-2 text-caption font-medium
                           text-ink-600 hover:bg-line-100/60"
                >
                    {{ mb_strtoupper(App::getLocale()) }}
                    <x-icon name="chevron-down" size="4"/>
                </button>
                <div
                    x-show="open" x-transition x-cloak
                    class="absolute right-0 z-10 mt-1 min-w-[8rem] rounded-md border border-line-200 bg-paper-0 py-1 shadow-popover"
                >
                    @foreach (Config::get('languages') as $lang => $language)
                        @if ($lang !== App::getLocale())
                            <a class="block px-4 py-2 text-body text-ink-900 hover:bg-line-100/60"
                               href="{{ route('lang.switch', $lang) }}">
                                {{ $language }}
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- Formular-Zentrum --}}
            <div class="w-full max-w-sm">
                <h2 class="text-title font-semibold tracking-tight text-ink-900">
                    @yield('title')
                </h2>
                @hasSection('subtitle')
                    <p class="mt-2 text-body text-ink-600">
                        @yield('subtitle')
                    </p>
                @endif

                @yield('content')
            </div>

            {{-- Footer --}}
            <footer class="absolute inset-x-0 bottom-6 flex justify-center gap-3 text-mono-caps
                           font-mono uppercase tracking-widest text-ink-500">
                <span>crowdCuratio</span>
                <span aria-hidden="true">·</span>
                <span>Open Source</span>
                <span aria-hidden="true">·</span>
                <span>GPLv3</span>
                <span aria-hidden="true">·</span>
                <a href="{{ route('auth.policy') }}" class="hover:text-ink-900">{{ __('privacy_policy') }}</a>
            </footer>
        </main>
    </div>

</body>
</html>
