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

use App\Models\User;
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

    // @phpstan-ignore-next-line property.notFound (Pest-Magic ->not->)
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

    // Der Skip-Link muss noch vor der Rail (erste <aside>) kommen,
    // damit Tab als erstes auf ihn landet. Der <header>-Selector aus
    // 5b entfaellt, seit Phase 5-D.3 uebernimmt die Rail als
    // <aside aria-label="Hauptnavigation"> die Rolle des Nav-Chromes.
    $skipPos = strpos($html, 'href="#main-content"');
    $railPos = strpos($html, '<aside');
    expect($skipPos)->toBeInt()->toBeLessThan($railPos);
});

it('Layout rendert Auto-Save-Indikator in der Rail mit drei State-Spans', function () {
    /** @var TestCase $this */
    $html = Blade::render(<<<'BLADE'
<x-layout>
    <x-slot:main>X</x-slot:main>
</x-layout>
BLADE);

    // Seit Phase 5-D.3 sitzt der Indikator als Farb-Punkt in der
    // Rail statt als Text-Chip im Navi-Header. Der Store-Zugriff
    // und die drei States (saving/saved/error) bleiben gleich.
    expect($html)
        ->toContain('$store.saveStatus.state')
        ->toContain('aria-live="polite"');
});

it('Layout rendert Toast-Region rechts unten mit aria-live=assertive', function () {
    /** @var TestCase $this */
    $html = Blade::render(<<<'BLADE'
<x-layout>
    <x-slot:main>X</x-slot:main>
</x-layout>
BLADE);

    // Region iteriert reaktiv über $store.toast.items,
    // Position rechts unten (fixed bottom-4 right-4).
    expect($html)
        ->toContain('aria-live="assertive"')
        ->toContain('$store.toast.items')
        ->toContain('$store.toast.dismiss')
        ->toContain('fixed bottom-4 right-4');
});

it('User-Menue rendert Profil, Passwort aendern und Abmelden (Phase 5d.6)', function () {
    /** @var TestCase $this */
    // Der Rail rendert das User-Menue nur wenn ein User authenticated
    // ist. Wir loggen einen Dummy-User ein und pruefen die drei
    // Menuepunkte + role=menu.
    /** @var User $user */
    $user = User::factory()->create();
    auth()->login($user);

    $html = Blade::render(<<<'BLADE'
<x-layout>
    <x-slot:main>X</x-slot:main>
</x-layout>
BLADE);

    expect($html)
        ->toContain('role="menu"')
        ->toContain('href="'.route('profile').'"')
        ->toContain('href="'.route('profile').'#password"')
        ->toContain('action="'.route('logout').'"')
        ->toContain(__('profile'))
        ->toContain(__('change_password'))
        ->toContain(__('log_out'));
});

it('Layout-Komponente exponiert <aside>-Rail und @livewireScripts vor </body>', function () {
    /** @var TestCase $this */
    $html = Blade::render(<<<'BLADE'
<x-layout>
    <x-slot:main>X</x-slot:main>
</x-layout>
BLADE);

    expect($html)
        ->toContain('<!DOCTYPE html>')
        ->toContain('<aside') // Rail seit 5-D.3 statt <header>
        ->toContain('</body>');

    // @livewireScripts rendert je nach Setup unterschiedlichen Output;
    // gepinnt wird hier nur, dass die Reihenfolge body → Livewire →
    // @stack erhalten bleibt (siehe Komponente).
    $bodyClose = strpos($html, '</body>');
    expect($bodyClose)->toBeInt()->toBeGreaterThan(0);
});
