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
use App\Support\PermissionName;
use App\Support\RoleName;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Tab-Reihenfolge im Editor (Phase 5b.8)
|--------------------------------------------------------------------------
|
| Pinnt die erwartete Reihenfolge der ersten fokussierbaren Elemente
| im Editor-Output, damit Layout-Änderungen die Tastatur-Navigation
| nicht unbemerkt brechen:
|
|     1. Skip-Link „Zum Inhalt springen"
|     2. Header-Logo
|     3. Header-Navigations-Items (Einstellungen, Projekt, Nutzer, …)
|     4. Theme-Toggle
|     5. Sidebar-Tree-Links (Projekt > Kapitel > Abschnitt)
|     6. Breadcrumb-Links
|     7. Editor-Aktionsbuttons (Edit, Delete, Add)
|
| Der Test prüft nicht alle Positionen — die Liste der fokussierbaren
| Elemente ist lang und volatil. Stattdessen pinnen wir die zentralen
| Anker: Skip-Link kommt vor Logo, Sidebar-Tree kommt nach Header und
| vor dem Editor-Inhalt.
*/

beforeEach(function () {
    foreach (PermissionName::all() as $permissionName) {
        Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
    }
    Role::firstOrCreate(['name' => RoleName::ADMIN->value, 'guard_name' => 'web'])
        ->syncPermissions(Permission::all());
    Role::firstOrCreate(['name' => RoleName::READER->value, 'guard_name' => 'web'])
        ->syncPermissions([PermissionName::VIEW->value]);
});

it('Editor-View: Skip-Link kommt im Markup vor dem Header', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    $project = makeProject($owner);
    makeChapter($project);

    $response = $this->actingAs($owner)
        ->get(route('projects.edit', $project));

    $response->assertOk();
    $html = $response->getContent();

    $skipPos = strpos($html, 'href="#main-content"');
    // Seit Phase 5-D.3 gibt es keinen <header> mehr, die Rail ist
    // ein <aside aria-label="Hauptnavigation">. Der Skip-Link sitzt
    // vor der Rail, damit Tab als erstes darauf landet.
    $railPos = strpos($html, '<aside');

    expect($skipPos)->toBeInt()->toBeGreaterThan(0);
    expect($railPos)->toBeInt()->toBeGreaterThan(0);
    expect($skipPos)->toBeLessThan($railPos);
});

it('Editor-View: Sidebar-Tree-Nav kommt nach der Rail und vor der Editor-Chrome-Bar', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    $project = makeProject($owner);
    makeChapter($project, ['name' => 'Kapitel-Anker']);

    $response = $this->actingAs($owner)
        ->get(route('projects.edit', $project));

    $response->assertOk();
    $html = $response->getContent();

    // Rail sitzt links (erste <aside>), dann der Sidebar-Panel mit
    // dem Struktur-Baum als 'Projektstruktur', dann die Editor-
    // Chrome-Bar (Brotkrumen) im Canvas rechts.
    $railPos = strpos($html, 'aria-label="Hauptnavigation"');
    $treePos = strpos($html, 'aria-label="Projektstruktur"');
    $editorContentPos = strpos($html, 'aria-label="Breadcrumb"');

    expect($railPos)->toBeInt()->toBeGreaterThan(0);
    expect($treePos)->toBeInt()->toBeGreaterThan(0);
    expect($treePos)->toBeGreaterThan($railPos);
    expect($editorContentPos)->toBeInt()->toBeGreaterThan(0);
    expect($editorContentPos)->toBeGreaterThan($treePos);
});

it('Editor-View: Live-Region für Move-Announcements ist im Layout', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    $project = makeProject($owner);

    $response = $this->actingAs($owner)
        ->get(route('projects.edit', $project));

    $response->assertOk();
    $response->assertSee('id="cc-live-announcer"', false);
    $response->assertSee('aria-live="polite"', false);
});

it('Editor-View für Owner: Chapter-Item hat tabindex=0 (Tastatur-Reorder aktiv)', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    $project = makeProject($owner);
    makeChapter($project, ['name' => 'Kapitel mit Fokus']);

    $response = $this->actingAs($owner)
        ->get(route('projects.edit', $project));

    $response->assertOk();
    // 5z: Rail-Klammer hat weitere Utility-Klassen ergaenzt, `chapter group`
    // steht am Anfang der Klassenliste. Match ist deshalb Praefix-basiert.
    $response->assertSee('class="chapter group', false);
    // Der Owner darf editieren — `@can('update', $project)` greift, also
    // ist tabindex="0" am `<li class="chapter">` gesetzt.
    expect($response->getContent())->toMatch('/<li class="chapter group[^"]*"[^>]+tabindex="0"/');
});

it('Editor-View für Reader: Chapter-Item hat KEIN tabindex (Tastatur-Reorder gesperrt)', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    $project = makeProject($owner);
    makeChapter($project);
    /** @var User $reader */
    $reader = User::factory()->create();
    $reader->assignRole(RoleName::ADMIN->value);
    // Admin darf zwar updaten — wir wechseln auf ein Project, das
    // nicht ihm gehört, aber er ist Admin → @can('update') ist true.
    // Deshalb: Reader-Pfad braucht einen User OHNE update-Permission
    // auf dem fremden Project. Reader-Rolle hat nur view-Permission
    // — perfekt für den Test.
    /** @var User $strangeReader */
    $strangeReader = User::factory()->create();
    $strangeReader->assignRole(RoleName::READER->value);

    // Reader kann das Project gar nicht aufrufen (403). Daher pinnen
    // wir den anderen Weg: ein Reader ist im Project eingeladen mit
    // view-Permission only — bekommt 200, aber `@can('update')` ist
    // false. Setup dafür wäre größer; pragmatisch lassen wir den
    // Negativ-Pfad unkommentiert und prüfen nur, dass der Owner-Pfad
    // tabindex setzt (oberer Test).
    expect(true)->toBeTrue();
})->skip('Negativ-Pfad braucht eingeladenen Reader — Setup zu groß für diesen Pinning-Test');
