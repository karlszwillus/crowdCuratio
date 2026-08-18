{{--
crowdCuratio - Curating together virtually
Copyright (C)2026 - berlinHistory e.V.

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

If not, see <https://www.gnu.org/licenses/>.
--}}

{{-- App-Shell-Rail (60 px, dunkles Chrome).

Konsistente linke Navigations-Rail über alle Editor-Routen. Logo
oben, primäre Bereichs-Icons in der Mitte, Utility-Aktionen
(Theme-Toggle, Sprach-Switch, User-Menü) unten. Aktive Route ist
per `--brand-bar`-Left-Border markiert (Handoff v4 § Screen 02).

Konventionen:
- Trefferfläche pro Item ≥ 44 × 44 px (WCAG 2.5.8, `--target-min`).
- Alle Icons via `<x-icon>` mit `size="5"` (20 px, Handoff-Konvention
  für Icon-Buttons).
- ARIA-Labels an Icon-only-Buttons — decorative=false und label
  setzen.
- Focus-Ring via `--focus-outline`.

Nutzung: `<x-layout.rail :active="'projects'" />` im äußeren
`<x-layout>`.
--}}

@props([
    'active' => null, // 'projects' | 'users' | 'comments' | 'media'
])

@php
    // Persona-Smoke 2026-08-15: currentRole[0] crashte bei rollenlosen
    // Usern (Undefined array key 0). Spatie's hasRole() ist robust
    // gegen leere Rollen-Collection.
    $isAdmin = Auth::check() && Auth::user()->hasRole(\App\Support\RoleName::ADMIN->value);

    $initials = Auth::check()
        ? mb_strtoupper(mb_substr(Auth::user()->name ?? '', 0, 1)
            . mb_substr(Auth::user()->last_name ?? '', 0, 1))
        : '';

    // Phase 5x.10: offener Kommentar-Zaehler fuer den Rail-Badge.
    // Beruecksichtigt eigene + eingeladene Projekte, Status open + in_progress.
    $openCommentCount = \App\Services\CommentCounter::openCountForUser(Auth::user());

    $itemBase = 'group relative flex h-11 w-11 items-center justify-center rounded-md '
              . 'text-chrome-on-dim hover:bg-chrome-active hover:text-chrome-on '
              . 'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 '
              . 'focus-visible:outline-brand-bar';

    // Aktive Zeile: Fläche + linker 3-px-Border in brand-bar. Wir
    // implementieren den Border als ::before-Pseudo per Utility, um
    // ohne extra CSS auszukommen (`before:*`).
    $itemActive = 'bg-chrome-active text-chrome-on '
                . 'before:absolute before:left-0 before:top-1 before:bottom-1 before:w-[3px] '
                . 'before:rounded-r before:bg-brand-bar';
@endphp

<aside
    aria-label="{{ __('main_navigation') }}"
    class="sticky top-0 z-40 flex h-screen w-[60px] shrink-0 flex-col items-center gap-2
           border-r border-chrome-line bg-chrome-bg py-3"
>
    {{-- Logo oben — führt zur Projekte-Übersicht (App-Home). --}}
    <a
        href="{{ route('projects.index') }}"
        class="mb-2 flex h-10 w-10 items-center justify-center rounded-md
               bg-logo-bg text-logo-on
               focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2
               focus-visible:outline-brand-bar"
        aria-label="crowdCuratio · {{ __('home') }}"
    >
        <span class="text-heading font-semibold leading-none">c</span>
    </a>

    {{-- Primäre Navigation. --}}
    <nav class="flex flex-col items-center gap-1" aria-label="{{ __('sections') }}">
        {{-- Phase 5e.1: Dashboard als erster Menuepunkt (Screen 09).
             Home-Icon, aktive Zeile analog zu Projekte. --}}
        <a
            href="{{ route('dashboard') }}"
            class="{{ $itemBase }} {{ $active === 'dashboard' ? $itemActive : '' }}"
            title="{{ __('start') }}"
            @if ($active === 'dashboard') aria-current="page" @endif
        >
            <x-icon name="home" size="5" :decorative="false" :label="__('start')"/>
        </a>

        <a
            href="{{ route('projects.index') }}"
            class="{{ $itemBase }} {{ $active === 'projects' ? $itemActive : '' }}"
            title="{{ __('projects') }}"
            @if ($active === 'projects') aria-current="page" @endif
        >
            <x-icon name="layout-grid" size="5" :decorative="false" :label="__('projects')"/>
        </a>

        @if ($isAdmin)
            <a
                href="{{ route('users.index') }}"
                class="{{ $itemBase }} {{ $active === 'users' ? $itemActive : '' }}"
                title="{{ __('users') }}"
                @if ($active === 'users') aria-current="page" @endif
            >
                <x-icon name="users" size="5" :decorative="false" :label="__('users')"/>
            </a>
        @endif

        <a
            href="{{ route('all.comments') }}"
            class="{{ $itemBase }} {{ $active === 'comments' ? $itemActive : '' }}"
            title="{{ __('comments') }}{{ $openCommentCount > 0 ? ' · '.$openCommentCount : '' }}"
            @if ($active === 'comments') aria-current="page" @endif
        >
            <span class="relative flex">
                <x-icon name="message-square" size="5" :decorative="false" :label="__('comments')"/>
                @if ($openCommentCount > 0)
                    <span
                        class="absolute -right-2 -top-2 inline-flex min-w-[1.15rem] items-center justify-center
                               rounded-full bg-primary px-1 text-[10px] font-semibold leading-none text-primary-on"
                        aria-label="{{ trans_choice(':count offene Kommentare|:count offene Kommentare', $openCommentCount, ['count' => $openCommentCount]) }}"
                    >{{ $openCommentCount }}</span>
                @endif
            </span>
        </a>

        @if ($isAdmin)
            <a
                href="{{ route('settings.index') }}"
                class="{{ $itemBase }} {{ $active === 'settings' ? $itemActive : '' }}"
                title="{{ __('setting') }}"
                @if ($active === 'settings') aria-current="page" @endif
            >
                <x-icon name="settings" size="5" :decorative="false" :label="__('setting')"/>
            </a>
        @endif
    </nav>

    {{-- Utility-Zone unten (mt-auto schiebt bündig ans Rail-Ende). --}}
    <div class="mt-auto flex flex-col items-center gap-1">
        {{-- Auto-Save-Indikator (Alpine-Store, Phase 5c.2). Kompakt
             als Punkt mit Farbwechsel; Text-Version im Titel-Attribut
             für Hover-Feedback. --}}
        <div
            x-data
            aria-live="polite"
            class="flex h-11 w-11 items-center justify-center"
            :title="{
                'idle': '',
                'saving': '{{ __('save_status_saving') }}',
                'saved': '{{ __('save_status_saved') }}',
                'error': '{{ __('save_status_error') }}'
            }[$store.saveStatus.state] || ''"
        >
            <span
                x-show="$store.saveStatus.state === 'saving'"
                class="size-2 animate-pulse rounded-full bg-warning"
            ></span>
            <span
                x-show="$store.saveStatus.state === 'saved'"
                x-transition:leave="transition-opacity duration-500 ease-in"
                x-transition:leave-end="opacity-0"
                class="size-2 rounded-full bg-success"
            ></span>
            <span
                x-show="$store.saveStatus.state === 'error'"
                class="size-2 rounded-full bg-danger"
            ></span>
        </div>

        {{-- Theme-Toggle (crowdCuratio ↔ Aktives Museum). --}}
        <button
            type="button"
            x-data
            @click="$store.theme.toggle()"
            :aria-pressed="$store.theme.current === 'aktivesMuseum'"
            :aria-label="$store.theme.current === 'aktivesMuseum' ? '{{ __('switch_theme_default') }}' : '{{ __('switch_theme_alt') }}'"
            class="{{ $itemBase }}"
            title="{{ __('switch_theme') }}"
        >
            <span x-show="$store.theme.current === 'aktivesMuseum'" x-cloak class="flex">
                <x-icon name="sun" size="5"/>
            </span>
            <span x-show="$store.theme.current !== 'aktivesMuseum'" x-cloak class="flex">
                <x-icon name="moon" size="5"/>
            </span>
        </button>

        {{-- Sprach-Auswahl (Popover). --}}
        @if (! in_array(Route::currentRouteName(), ['translate', 'log.detail']))
            <div x-data="{ open: false }" class="relative">
                <button
                    type="button"
                    @click="open = !open"
                    @click.outside="open = false"
                    aria-haspopup="true"
                    :aria-expanded="open"
                    :aria-label="'{{ __('language') }}'"
                    class="{{ $itemBase }} text-mono-caps"
                >
                    {{ mb_strtoupper(App::getLocale()) }}
                </button>
                <div
                    x-show="open"
                    x-transition
                    x-cloak
                    class="absolute bottom-0 left-full ml-2 min-w-[10rem]
                           rounded-md border border-ink-300 bg-paper-0 py-1 shadow-popover"
                >
                    @foreach (Config::get('languages') as $lang => $language)
                        @if ($lang !== App::getLocale())
                            <a
                                class="block px-4 py-2 text-body text-ink-900 hover:bg-chrome-active/40"
                                href="{{ route('lang.switch', $lang) }}"
                            >
                                {{ $language }}
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif

        {{-- User-Menü (Avatar + Logout). --}}
        @auth
            <div x-data="{ open: false }" class="relative">
                <button
                    type="button"
                    @click="open = !open"
                    @click.outside="open = false"
                    aria-haspopup="true"
                    :aria-expanded="open"
                    class="flex h-11 w-11 items-center justify-center rounded-full
                           focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2
                           focus-visible:outline-brand-bar"
                    :aria-label="'{{ Auth::user()->name ?? '' }} {{ Auth::user()->last_name ?? '' }}'"
                    title="{{ Auth::user()->name ?? '' }} {{ Auth::user()->last_name ?? '' }}"
                >
                    <x-ui.user-avatar size="11"/>
                </button>
                <div
                    x-show="open"
                    x-transition
                    x-cloak
                    role="menu"
                    aria-label="{{ __('profile') }}"
                    class="absolute bottom-0 left-full ml-2 min-w-[14rem]
                           rounded-md border border-ink-300 bg-paper-0 py-1 shadow-popover"
                >
                    {{-- Phase 5d.6: drei Menuepunkte (Profil, Passwort
                         aendern, Abmelden). Passwort-Aendern nutzt die
                         gleiche Profile-Route mit #password-Anker —
                         eine dedizierte Passwort-Sicht gibt es im
                         Backend heute nicht (UpdateOwnProfileRequest
                         validiert beide Felder in einem Formular). --}}
                    <a
                        role="menuitem"
                        class="flex items-center gap-2 px-4 py-2 text-body text-ink-900 hover:bg-chrome-active/40"
                        href="{{ route('profile') }}"
                    >
                        <x-icon name="user" size="4"/>
                        {{ __('profile') }}
                    </a>
                    <a
                        role="menuitem"
                        class="flex items-center gap-2 px-4 py-2 text-body text-ink-900 hover:bg-chrome-active/40"
                        href="{{ route('profile') }}#password"
                    >
                        <x-icon name="key" size="4"/>
                        {{ __('change_password') }}
                    </a>
                    <div class="my-1 border-t border-line-200" role="separator"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            role="menuitem"
                            type="submit"
                            class="flex w-full items-center gap-2 px-4 py-2 text-left text-body text-ink-900 hover:bg-chrome-active/40"
                        >
                            <x-icon name="log-out" size="4"/>
                            {{ __('log_out') }}
                        </button>
                    </form>
                </div>
            </div>
        @endauth
    </div>
</aside>
