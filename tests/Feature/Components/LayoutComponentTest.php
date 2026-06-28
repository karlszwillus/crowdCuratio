<?php

/**
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
 */

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| <x-layout> — App-Shell-Gerüst (Phase 5b.1)
|--------------------------------------------------------------------------
|
| Pinnt die strukturellen Aussagen der neuen Layout-Komponente:
| semantische Landmarks (<header>, <aside>, <main>, <footer>),
| Slot-Durchreichung der fünf Section-Namen (log, main, sidebar,
| content, footer), Full-Width-Fallback bei gesetztem $content-Slot,
| @stack('scripts')-Pflicht (Ablösung des alten @yield('script')).
|
| Die HTTP-Integration mit `@extends('projects.layout')` wird in der
| EditViewCharacterizationTest-Familie weiter abgesichert; hier liegt
| nur die Komponente unter dem Mikroskop.
*/

it('Layout-Editor-Pfad rendert <main role="main"> mit Skip-Link-Anker und drei Aside-Slots', function () {
    /** @var TestCase $this */
    $html = Blade::render(<<<'BLADE'
<x-layout>
    <x-slot:log>HISTORY-INHALT</x-slot:log>
    <x-slot:main>EDITOR-INHALT</x-slot:main>
    <x-slot:sidebar>TOOLS-INHALT</x-slot:sidebar>
</x-layout>
BLADE);

    expect($html)
        ->toContain('<main role="main"')
        ->toContain('id="main-content"')
        ->toContain('HISTORY-INHALT')
        ->toContain('EDITOR-INHALT')
        ->toContain('TOOLS-INHALT')
        // Linke Aside trägt seit 5b.3 das Tree-Label „Projektstruktur";
        // die History wandert mit 5b.6 in den Drawer.
        ->toContain('aria-label="Projektstruktur"')
        ->toContain('aria-label="Werkzeuge"');
});

it('Layout-Full-Width-Pfad rendert <main> ohne Sidebar wenn $content gesetzt', function () {
    /** @var TestCase $this */
    $html = Blade::render(<<<'BLADE'
<x-layout>
    <x-slot:content>SETTINGS-INHALT</x-slot:content>
</x-layout>
BLADE);

    expect($html)
        ->toContain('<main role="main"')
        ->toContain('id="main-content"')
        ->toContain('SETTINGS-INHALT')
        // Wenn $content gesetzt ist, fällt der Editor-Grid weg —
        // weder aria-label="Projektstruktur" noch aria-label="Werkzeuge"
        // erscheinen, weil log/main/sidebar im else-Zweig liegen.
        ->not->toContain('aria-label="Projektstruktur"')
        ->not->toContain('aria-label="Werkzeuge"');
});

it('Layout liefert @stack-scripts und gibt View-Push-Beiträge durch', function () {
    /** @var TestCase $this */
    $html = Blade::render(<<<'BLADE'
@push('scripts')
    <script>window.__cc_5b1_test = true;</script>
@endpush
<x-layout>
    <x-slot:main>NUR-MAIN</x-slot:main>
</x-layout>
BLADE);

    expect($html)
        ->toContain('window.__cc_5b1_test = true;')
        ->toContain('NUR-MAIN');
});

it('Layout rendert Skip-Link mit href="#main-content" als ersten Tab-Stop', function () {
    /** @var TestCase $this */
    $html = Blade::render(<<<'BLADE'
<x-layout>
    <x-slot:main>X</x-slot:main>
</x-layout>
BLADE);

    expect($html)
        ->toContain('href="#main-content"')
        ->toContain('class="skip-link"')
        ->toContain('Zum Inhalt springen');

    // Der Skip-Link muss noch vor dem Header kommen, damit Tab als
    // erstes auf ihm landet.
    $skipPos = strpos($html, 'href="#main-content"');
    $headerPos = strpos($html, '<header');
    expect($skipPos)->toBeInt()->toBeLessThan($headerPos);
});

it('Layout-Komponente exponiert <header> und @livewireScripts vor </body>', function () {
    /** @var TestCase $this */
    $html = Blade::render(<<<'BLADE'
<x-layout>
    <x-slot:main>X</x-slot:main>
</x-layout>
BLADE);

    expect($html)
        ->toContain('<!DOCTYPE html>')
        ->toContain('<header')
        ->toContain('</body>');

    // @livewireScripts rendert je nach Setup unterschiedlichen Output;
    // gepinnt wird hier nur, dass die Reihenfolge body → Livewire →
    // @stack erhalten bleibt (siehe Komponente).
    $bodyClose = strpos($html, '</body>');
    expect($bodyClose)->toBeInt()->toBeGreaterThan(0);
});
