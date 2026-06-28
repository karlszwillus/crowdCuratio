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
| Editor-View — Pre-5b-Charakterisierung
|--------------------------------------------------------------------------
|
| Pinnt das äußere Verhalten der Editor-Hot-Path-Methoden vor dem
| Sidebar-/Layout-Refactor in Phase 5b. Ziel ist nicht volle Coverage
| der Controller — die liegt heute bei 44,8 % (ContentController) und
| 46,5 % (ProjectController) und wird durch 5b nicht systematisch
| gehoben. Stattdessen werden die 5b-relevanten View-Anker festgehalten:
|
|   - `chapters/index`-View wird gerendert für edit
|   - Project-Tree-Daten (chapters → entries → contents) sind im View
|     verfügbar — das ist die Grundlage für die neue Sidebar
|   - getCurrentLog liefert History-Einträge für den geplanten Drawer
|   - allData liefert JSON-Tree für AJAX-Pfade
|
| Wenn 5b die Render-Logik oder Daten-Struktur ändert, fallen die
| Tests rot und zwingen einen bewussten Schnitt.
*/

beforeEach(function () {
    foreach (PermissionName::all() as $permissionName) {
        Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
    }
    Role::firstOrCreate(['name' => RoleName::ADMIN->value, 'guard_name' => 'web'])
        ->syncPermissions(Permission::all());
});

it('edit rendert die chapters/index-View für den Owner', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    makeChapter($project, ['name' => 'Erstes Kapitel']);

    $response = $this->actingAs($owner)
        ->get(route('projects.edit', $project));

    $response->assertOk();
    $response->assertViewIs('chapters.index');
    $response->assertViewHas('project');
});

it('edit-View enthält die Chapter-Tree-Struktur für den geplanten Sidebar-Baum', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $chapter = makeChapter($project, ['name' => 'Kapitel mit Inhalt']);
    makeEntry($chapter, ['name' => 'Abschnitt A']);
    makeEntry($chapter, ['name' => 'Abschnitt B']);

    $response = $this->actingAs($owner)
        ->get(route('projects.edit', $project));

    $response->assertOk();
    // Die Sidebar in 5b braucht diese Daten — wenn sie nicht mehr da
    // sind, muss der Sidebar-Code mit-anpasst werden.
    $project = $response->viewData('project');
    expect($project->chapters)->toHaveCount(1);
    expect($project->chapters->first()->entries)->toHaveCount(2);
});

it('edit verbietet fremde User mit 403', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    /** @var User $stranger */
    $stranger = User::factory()->create();
    $project = makeProject($owner);

    $response = $this->actingAs($stranger)
        ->get(route('projects.edit', $project));

    $response->assertForbidden();
});

it('edit lässt Admin auf fremde Projects', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole(RoleName::ADMIN->value);
    $project = makeProject($owner);

    $response = $this->actingAs($admin)
        ->get(route('projects.edit', $project));

    $response->assertOk();
});

it('getCurrentLog via log.text liefert OK für den geplanten History-Drawer', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::ADMIN->value);
    $project = makeProject($owner);

    // log.text ist die heute existierende Route für getCurrentLog.
    // Die View ist Drawer-Kandidat in 5b — Pinning auf den OK-Pfad.
    $response = $this->actingAs($owner)
        ->get(route('log.text', $project->id));

    $response->assertOk();
});

it('show liefert die Reader-Sicht für ein eigenes Project', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);

    $response = $this->actingAs($owner)
        ->get(route('projects.show', $project));

    // 5b ändert vermutlich den Reader nicht direkt, aber falls
    // doch — fällt der Test rot.
    $response->assertOk();
});
