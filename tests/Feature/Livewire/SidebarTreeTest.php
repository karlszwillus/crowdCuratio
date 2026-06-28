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
use Livewire\Livewire;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| <livewire:sidebar-tree> — Projekt-Struktur-Baum (Phase 5b.3)
|--------------------------------------------------------------------------
|
| Pinnt das Render-Verhalten der Sidebar-Tree-Komponente:
| Mount lädt eager via loadMissing, Tree zeigt Projekt-Name plus
| drei Ebenen (Projekt → Kapitel → Abschnitt) mit href-Ankern auf
| die bestehenden `anchor_Chapter_{id}`/`anchor_Entry_{id}`-Stellen
| in chapters/index. Inhalts-Ebene (4) ist NICHT in der Sidebar
| (Entscheidung 2.4).
*/

it('SidebarTree rendert Projekt-Name und alle Kapitel als Anker-Links', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner, ['name' => 'Mein Test-Projekt']);
    $chapter1 = makeChapter($project, ['name' => 'Kapitel Eins']);
    $chapter2 = makeChapter($project, ['name' => 'Kapitel Zwei']);

    Livewire::actingAs($owner)
        ->test('sidebar-tree', ['project' => $project])
        ->assertSee('Mein Test-Projekt', false)
        ->assertSee('Kapitel Eins', false)
        ->assertSee('Kapitel Zwei', false)
        ->assertSee('href="#anchor_Chapter_'.$chapter1->id.'"', false)
        ->assertSee('href="#anchor_Chapter_'.$chapter2->id.'"', false);
});

it('SidebarTree rendert Abschnitte als dritte Ebene mit anchor_Entry-Links', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $chapter = makeChapter($project, ['name' => 'Kapitel mit Abschnitten']);
    $entryA = makeEntry($chapter, ['name' => 'Abschnitt A']);
    $entryB = makeEntry($chapter, ['name' => 'Abschnitt B']);

    Livewire::actingAs($owner)
        ->test('sidebar-tree', ['project' => $project])
        ->assertSee('Abschnitt A', false)
        ->assertSee('Abschnitt B', false)
        ->assertSee('href="#anchor_Entry_'.$entryA->id.'"', false)
        ->assertSee('href="#anchor_Entry_'.$entryB->id.'"', false);
});

it('SidebarTree liefert leeren Tree-Stamm für ein Projekt ohne Kapitel', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner, ['name' => 'Leeres Projekt']);

    Livewire::actingAs($owner)
        ->test('sidebar-tree', ['project' => $project])
        ->assertSee('Leeres Projekt', false)
        ->assertSee('aria-label="Projektstruktur"', false)
        ->assertDontSee('href="#anchor_Chapter_', false);
});
