{{--
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

If not, see <https://www.gnu.org/licenses/>.
--}}

{{-- Brückenkopf zwischen den alten `@extends('projects.layout')`-Views
     und der neuen `<x-layout>`-Komponente. Bestehende Views ändern
     sich in dieser Welle nicht — sie befüllen weiterhin
     `@section('log/main/sidebar/content/footer')` und werden hier
     in die Slot-API der Komponente übersetzt. Saubere Komponenten-
     Umstellung der Views erfolgt ab den späteren Sub-Wellen, dann
     entfällt diese Hülle. --}}

<x-layout>
    <x-slot:log>
        @yield('log')
    </x-slot:log>

    <x-slot:main>
        @yield('main')
    </x-slot:main>

    <x-slot:sidebar>
        @yield('sidebar')
    </x-slot:sidebar>

    <x-slot:content>
        @yield('content')
    </x-slot:content>

    <x-slot:footer>
        @yield('footer')
    </x-slot:footer>
</x-layout>
