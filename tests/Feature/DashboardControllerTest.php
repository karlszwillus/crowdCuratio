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

declare(strict_types=1);

use App\Models\User;
use App\Support\PermissionName;
use App\Support\RoleName;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Dashboard (Phase 5e.1, Screen 09)
|--------------------------------------------------------------------------
|
| Smoke-Tests fuer die neue Landing-Page: Route erreichbar, vier
| Sektionen sichtbar, Empty-States rendern korrekt. Die Details
| (Karten-Layout, Rollen-Badge, line-clamp-Kommentare) werden
| durch die Persona-Smoke abgesichert.
*/

beforeEach(function () {
    foreach (PermissionName::all() as $permissionName) {
        Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
    }
    Role::firstOrCreate(['name' => RoleName::ADMIN->value, 'guard_name' => 'web'])
        ->syncPermissions(Permission::all());
    Role::firstOrCreate(['name' => RoleName::READER->value, 'guard_name' => 'web'])
        ->syncPermissions(Permission::whereIn('name', ['view'])->get());
});

it('Dashboard: Route ist mit auth-Middleware geschuetzt', function () {
    /** @var TestCase $this */
    $response = $this->get('/dashboard');

    // 302 → Login-Redirect (auth-middleware).
    $response->assertStatus(302);
});

it('Dashboard: gerendert fuer einen Erstlogin-User zeigt Empty-States', function () {
    /** @var TestCase $this */
    /** @var User $user */
    $user = User::factory()->create(['name' => 'Rolfo']);
    $user->assignRole(RoleName::READER->value);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
    // Greeting (per Kontext-Zeit variabel, aber der Name muss drin sein).
    $response->assertSee('Rolfo');
    // Alle vier Sektionen-Ueberschriften.
    $response->assertSeeText(__('my_projects'));
    $response->assertSeeText(__('assigned_to_me'));
    $response->assertSeeText(__('recent_comments'));
    // Erstlogin: Empty-State mit CTA fuer Meine Projekte.
    $response->assertSeeText(__('empty_own_projects_title'));
});

it('Dashboard: Owner sieht seine Projekte in „Meine Projekte"', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    $project = makeProject($owner, ['name' => 'Frauenbewegung Berlin']);

    $response = $this->actingAs($owner)->get('/dashboard');

    $response->assertOk();
    $response->assertSeeText('Frauenbewegung Berlin');
});
