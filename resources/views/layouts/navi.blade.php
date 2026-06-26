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

<header class="mb-4 border-b border-ink-400 bg-white">
    <nav class="mx-auto flex w-full max-w-screen-2xl items-center justify-between gap-4 px-6 py-3">
        <a href="{{ route('projects.index') }}" class="shrink-0">
            <img
                class="h-10 w-auto"
                src="//app.crowdcurat.io/css/images/crowdCuratio_logo.png"
                alt="crowdCuratio"
            >
        </a>

        <ul class="flex flex-wrap items-center gap-1">
            @if(isset(Auth::user()->currentRole) && Auth::user()->currentRole[0]->name == 'Admin')
                <li>
                    <a class="block rounded-md px-3 py-2 text-body text-ink-900 hover:bg-ink-400/10" href="{{route('settings.index')}}">
                        {{__('setting')}}
                    </a>
                </li>
            @endif

            <li x-data="{ open: false }" class="relative">
                <button
                    type="button"
                    @click="open = !open"
                    @click.outside="open = false"
                    aria-haspopup="true"
                    :aria-expanded="open"
                    class="flex items-center gap-1 rounded-md px-3 py-2 text-body text-ink-900 hover:bg-ink-400/10"
                >
                    {{__('project')}}
                    <x-ui.icon name="chevron-down" :size="14"/>
                </button>
                <div
                    x-show="open"
                    x-transition
                    x-cloak
                    class="absolute left-0 z-10 mt-1 min-w-[12rem] rounded-md border border-ink-400 bg-white py-1 shadow-md"
                >
                    <a class="block px-4 py-2 text-body text-ink-900 hover:bg-ink-400/10" href="{{ route('projects.index') }}">{{__('all_projects')}}</a>
                    <a class="block px-4 py-2 text-body text-ink-900 hover:bg-ink-400/10" href="{{ route('projects.create') }}">{{__('new_project')}}</a>
                </div>
            </li>

            <li x-data="{ open: false }" class="relative">
                <button
                    type="button"
                    @click="open = !open"
                    @click.outside="open = false"
                    aria-haspopup="true"
                    :aria-expanded="open"
                    class="flex items-center gap-1 rounded-md px-3 py-2 text-body text-ink-900 hover:bg-ink-400/10"
                >
                    {{__('users')}}
                    <x-ui.icon name="chevron-down" :size="14"/>
                </button>
                <div
                    x-show="open"
                    x-transition
                    x-cloak
                    class="absolute left-0 z-10 mt-1 min-w-[12rem] rounded-md border border-ink-400 bg-white py-1 shadow-md"
                >
                    @if(isset(Auth::user()->currentRole) && Auth::user()->currentRole[0]->name == 'Admin')
                        <a class="block px-4 py-2 text-body text-ink-900 hover:bg-ink-400/10" href="{{ route('users.index') }}">{{__('all_users')}}</a>
                        <a class="block px-4 py-2 text-body text-ink-900 hover:bg-ink-400/10" href="{{ route('register') }}">{{__('add_new')}}</a>
                        <a class="block px-4 py-2 text-body text-ink-900 hover:bg-ink-400/10" href="{{ route('roles.index') }}">{{__('roles')}}</a>
                    @endif
                    <a class="block px-4 py-2 text-body text-ink-900 hover:bg-ink-400/10" href="{{ route('profile') }}">{{__('profile')}}</a>
                </div>
            </li>

            <li>
                <a class="block rounded-md px-3 py-2 text-body text-ink-900 hover:bg-ink-400/10" href="{{route('all.comments')}}">
                    {{__('comments')}}
                </a>
            </li>
        </ul>

        <div class="flex items-center gap-3">
            @if(!in_array(Route::currentRouteName(), ['translate', 'log.detail']))
                <div x-data="{ open: false }" class="relative">
                    <button
                        type="button"
                        @click="open = !open"
                        @click.outside="open = false"
                        aria-haspopup="true"
                        :aria-expanded="open"
                        class="flex items-center gap-1 rounded-md px-3 py-2 text-caption text-ink-700 hover:bg-ink-400/10"
                    >
                        {{ config('languages')[App::getLocale()] }}
                        <x-ui.icon name="chevron-down" :size="14"/>
                    </button>
                    <div
                        x-show="open"
                        x-transition
                        x-cloak
                        class="absolute right-0 z-10 mt-1 min-w-[8rem] rounded-md border border-ink-400 bg-white py-1 shadow-md"
                    >
                        @foreach (Config::get('languages') as $lang => $language)
                            @if ($lang != App::getLocale())
                                <a class="block px-4 py-2 text-body text-ink-900 hover:bg-ink-400/10" href="{{ route('lang.switch', $lang) }}">{{$language}}</a>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            <div x-data="{ open: false }" class="relative">
                <button
                    type="button"
                    @click="open = !open"
                    @click.outside="open = false"
                    aria-haspopup="true"
                    :aria-expanded="open"
                    class="flex items-center gap-1 rounded-md bg-ink-900 px-3 py-2 text-caption text-white hover:opacity-90"
                >
                    @if(isset(Auth::user()->name)){{ Auth::user()->name }} {{ Auth::user()->last_name }}@endif
                    <x-ui.icon name="chevron-down" :size="14"/>
                </button>
                <div
                    x-show="open"
                    x-transition
                    x-cloak
                    class="absolute right-0 z-10 mt-1 min-w-[8rem] rounded-md border border-ink-400 bg-white py-1 shadow-md"
                >
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="block w-full px-4 py-2 text-left text-body text-ink-900 hover:bg-ink-400/10"
                        >
                            {{ __('log_out') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>
</header>

<div class="row">
    <div class="col-sm-2 leftbar">
        @yield('log')
    </div>
    @if(View::hasSection('content'))
        <div class="col-sm-10">
            @yield('content')
        </div>
    @else
        <div class="col-sm-7 mainbar">
            @yield('main')
        </div>
        <div class="col-sm-3 rightbar">
            @yield('sidebar')
        </div>
    @endif
    <div class="col-sm-12">
        @yield('footer')
    </div>
</div>
