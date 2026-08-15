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

{{-- Gemeinsame Huelle fuer 403/404/500 (Phase 5e.5).

Konsumiert:
- $code     — HTTP-Statuscode als grosser Anker (403/404/500)
- $iconName — Lucide-Icon-Name (shield-off / compass / alert-triangle)
- $title    — Uebersetzter Titel-String
- $body     — Uebersetzter Body-String (persona-freundlich)

Die drei Fehlerseiten binden dieses Snippet per @include ein, damit
sich die Handschrift nicht dreifach pflegen muss. Layout bleibt
`projects.layout` — Rail und Sidebar-Panel sind sichtbar, damit die
Persona den Weg zurueck findet.

Falls kein User angemeldet ist (z. B. 404 auf /login), zeigen wir den
„Zur Anmeldung"-Button statt „Zu meinen Projekten".
--}}

<div class="mx-auto flex max-w-2xl flex-col items-center justify-center gap-6 px-6 py-16 text-center">
    <div class="flex size-16 items-center justify-center rounded-full bg-danger-bg text-danger">
        <x-icon :name="$iconName" size="6"/>
    </div>

    <div class="font-mono text-mono-caps uppercase tracking-widest text-ink-500">
        {{ __('error') }} · {{ $code }}
    </div>

    <h1 class="text-title font-semibold text-ink-900">{{ $title }}</h1>

    <p class="max-w-lg text-body text-ink-700">{{ $body }}</p>

    <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
        @auth
            <a href="{{ route('projects.index') }}"
               class="inline-flex items-center gap-1.5 rounded-md bg-primary px-4 py-2 text-body font-medium text-primary-on
                      hover:opacity-90
                      focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                <x-icon name="arrow-left" size="4"/>
                {{ __('error_back_to_projects') }}
            </a>
        @else
            <a href="{{ route('login') }}"
               class="inline-flex items-center gap-1.5 rounded-md bg-primary px-4 py-2 text-body font-medium text-primary-on
                      hover:opacity-90
                      focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                <x-icon name="arrow-left" size="4"/>
                {{ __('error_back_to_login') }}
            </a>
        @endauth
    </div>
</div>
