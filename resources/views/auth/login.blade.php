<!--
crowdCuratio - Curating together virtually
Copyright (C)2022, 2026 - berlinHistory e.V.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program in the file LICENSE.

If not, see <https://www.gnu.org/licenses/>. -->

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>crowdCuratio · {{ __('login') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-paper-0 text-ink-900 antialiased">

    {{-- Split-Layout nach Handoff v4 § Screen 04.
         Links Marken-Panel (dunkel), rechts Formular-Panel (hell).
         Auf schmalen Viewports stapelt sich das Layout mit dem
         Marken-Panel oben und dem Formular darunter. --}}
    <div class="flex min-h-screen flex-col md:flex-row">

        {{-- Marken-Panel — 520 px auf Desktop, volle Breite mit
             reduzierter Höhe auf Mobile. --}}
        <aside
            class="flex flex-col justify-between bg-chrome-bg px-12 py-12
                   text-on-dark-100 md:w-[520px] md:shrink-0"
            aria-labelledby="brand-panel-heading"
        >
            {{-- Oben: Logo-Kachel + Wortmarke --}}
            <div class="flex items-center gap-3">
                <div class="flex size-11 items-center justify-center rounded-md bg-logo-bg text-logo-on">
                    <span class="text-title font-semibold leading-none">c</span>
                </div>
                <span class="text-heading font-medium tracking-tight">
                    <span class="font-light">crowd</span><span class="font-semibold">Curatio</span>
                </span>
            </div>

            {{-- Mitte: großes „c" + Claim + Fließtext --}}
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

            {{-- Unten: Referenzen --}}
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

        {{-- Formular-Panel --}}
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

            {{-- Formular-Zentrum, max 380px --}}
            <div class="w-full max-w-sm">
                <h2 class="text-title font-semibold tracking-tight text-ink-900">
                    {{ __('login_title') }}
                </h2>
                <p class="mt-2 text-body text-ink-600">
                    {{ __('login_subtitle') }}
                </p>

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

                    {{-- E-Mail --}}
                    <div>
                        <label for="email" class="mb-1 block text-caption font-medium text-ink-700">
                            {{ __('email') }} <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <input
                            id="email" name="email" type="email"
                            value="{{ old('email') }}"
                            required autofocus autocomplete="email" aria-required="true"
                            class="w-full rounded-md border border-form-border bg-paper-0 px-3 py-2.5
                                   text-body text-ink-900
                                   focus:border-primary focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary"
                        >
                    </div>

                    {{-- Passwort — klassisches type="password"-Feld
                         (der 'anzeigen'-Toggle wird bewusst weggelassen,
                         damit Password-Manager wie 1Password sauber
                         greifen und die UX nicht ueberladen wird). --}}
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
                        <input
                            id="password" name="password" type="password"
                            required autocomplete="current-password" aria-required="true"
                            class="w-full rounded-md border border-form-border bg-paper-0 px-3 py-2.5
                                   text-body text-ink-900
                                   focus:border-primary focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary"
                        >
                    </div>

                    {{-- Angemeldet bleiben --}}
                    <label for="remember_me" class="flex items-center gap-2 text-body text-ink-700">
                        <input id="remember_me" name="remember" type="checkbox"
                               class="size-4 rounded border-form-border text-primary
                                      focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-primary">
                        <span>{{ __('stay_logged_in') }}</span>
                    </label>

                    {{-- Primary volle Breite --}}
                    <button type="submit"
                            class="w-full rounded-md bg-primary px-4 py-3
                                   text-body font-semibold text-primary-on hover:opacity-90
                                   focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-bar">
                        {{ __('login') }}
                    </button>
                </form>

                <p class="mt-6 text-center text-caption text-ink-500">
                    {{ __('no_account') }}
                    <span class="font-medium text-ink-700">{{ __('invite_only') }}</span>
                </p>
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
                <a href="#" class="hover:text-ink-900">{{ __('privacy_policy') }}</a>
            </footer>
        </main>
    </div>

</body>
</html>
