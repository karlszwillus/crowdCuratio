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
|   - `chapters/index`-View wird (per @include aus `projects.edit`)
|     gerendert für edit
|   - Project-Tree-Daten (chapters → entries → contents) sind im View
|     verfügbar — das ist die Grundlage für die neue Sidebar
|   - show liefert die Reader-Sicht für ein eigenes Project
|
| `getCurrentLog`/`log.text` ist heute toter Code (Controller ruft
| `new LogService;` ohne das erforderliche Model-Argument — Frontend
| zieht die Route nicht). Kein Pinning hier, eigenes Backlog-Item.
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
    // Reader-Rolle wird gebraucht, damit die Test-Owner eine assigned
    // Role haben — `layouts/navi.blade.php` (Z. 31, Z. 80) liest heute
    // `Auth::user()->currentRole[0]->name`, was bei leerer Collection
    // mit „Undefined array key 0" crasht. Das ist der Realfall:
    // jeder per Registrierung angelegte User bekommt eine Rolle. Der
    // navi-Smell selbst ist eigenes Backlog-Item (TODO.md / Pre-Phase-6).
    Role::firstOrCreate(['name' => RoleName::READER->value, 'guard_name' => 'web'])
        ->syncPermissions([PermissionName::VIEW->value]);
});

it('edit rendert die chapters/index-View für den Owner', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    $project = makeProject($owner);
    makeChapter($project, ['name' => 'Erstes Kapitel']);

    $response = $this->actingAs($owner)
        ->get(route('projects.edit', $project));

    $response->assertOk();
    // ProjectController::edit returnt `view('projects.edit', ...)`.
    // Die `projects.edit`-Blade includet `chapters.index`. Das Pinning
    // sitzt deshalb auf `projects.edit` plus dem sichtbaren
    // Chapter-Namen als Indiz, dass das Tree-Markup gerendert wurde.
    $response->assertViewIs('projects.edit');
    $response->assertViewHas('project');
    $response->assertSee('Erstes Kapitel', false);
});

it('edit-View enthält die Chapter-Tree-Struktur für den geplanten Sidebar-Baum', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
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
    $owner->assignRole(RoleName::READER->value);
    /** @var User $stranger */
    $stranger = User::factory()->create();
    $stranger->assignRole(RoleName::READER->value);
    $project = makeProject($owner);

    $response = $this->actingAs($stranger)
        ->get(route('projects.edit', $project));

    $response->assertForbidden();
});

it('edit lässt Admin auf fremde Projects', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole(RoleName::ADMIN->value);
    $project = makeProject($owner);

    $response = $this->actingAs($admin)
        ->get(route('projects.edit', $project));

    $response->assertOk();
});

it('show liefert die Reader-Sicht für ein eigenes Project', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    $project = makeProject($owner);

    $response = $this->actingAs($owner)
        ->get(route('projects.show', $project));

    // 5b ändert vermutlich den Reader nicht direkt, aber falls
    // doch — fällt der Test rot.
    $response->assertOk();
});
