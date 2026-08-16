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
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Dashboard-Chrome (Phase 5e.1, Backlog #70)
|--------------------------------------------------------------------------
|
| Seit Backlog #70 leben die vier Sektionen in einer Livewire-Volt-
| Component mit #[Lazy]. Der Controller rendert nur noch den
| Chrome-Kontext (Greeting, Suche, „+ Neues Projekt") — dieser Test
| pinnt genau das. Die Sektions-Feeds pinnt DashboardSectionsTest.
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
    $response->assertStatus(302);
});

it('Dashboard-Chrome rendert Greeting + Suche + Neues-Projekt-CTA', function () {
    /** @var TestCase $this */
    /** @var User $user */
    $user = User::factory()->create(['name' => 'Rolfo']);
    $user->assignRole(RoleName::READER->value);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
    $response->assertSee('Rolfo');
    $response->assertSee(__('search_projects'), false);
});

it('Dashboard-Sections: rendert vier Sektionen fuer Erstlogin-User', function () {
    /** @var TestCase $this */
    /** @var User $user */
    $user = User::factory()->create();
    $user->assignRole(RoleName::READER->value);

    // `#[Lazy]` an der Komponente bewirkt, dass der erste
    // Render-Zyklus nur den placeholder() liefert (Skelett-Grid).
    // Der `$refresh`-Call zwingt den zweiten Roundtrip mit dem
    // echten Content — sonst matcht der Test gegen das Skelett.
    Livewire::actingAs($user)
        ->test('dashboard-sections')
        ->call('$refresh')
        ->assertOk()
        ->assertSeeText(__('my_projects'))
        ->assertSeeText(__('assigned_to_me'))
        ->assertSeeText(__('recent_comments'))
        // Erstlogin: Empty-State-CTA fuer Meine Projekte.
        ->assertSeeText(__('empty_own_projects_title'));
});

it('Dashboard-Sections: Owner sieht sein Projekt in „Meine Projekte"', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    $project = makeProject($owner, ['name' => 'Frauenbewegung Berlin']);

    Livewire::actingAs($owner)
        ->test('dashboard-sections')
        ->call('$refresh')
        ->assertOk()
        ->assertSeeText('Frauenbewegung Berlin');
});
