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

use App\Models\Role;
use App\Models\User;
use App\Support\PermissionName;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Admin-Routes-Charakterisierung — Block D / D.1
|--------------------------------------------------------------------------
|
| Fixiert das Authorization-Verhalten der heute mit der
| IsAdmin-Middleware geschützten Routen, bevor in D.3 die
| Middleware durch role:Admin ersetzt wird. UserController und
| RoleController nutzen heute `middleware('admin')->only([...])`
| für index/edit/destroy.
|
| Akzeptierte Stimmen: Admin-Rolle → 200/302; Reader/Editor → 403.
*/

beforeEach(function () {
    foreach (PermissionName::all() as $permissionName) {
        Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
    }

    SpatieRole::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web'])
        ->syncPermissions(Permission::all());
    SpatieRole::firstOrCreate(['name' => 'Reader', 'guard_name' => 'web'])
        ->syncPermissions(['view']);
});

// ---------- UserController ----------

it('Admin darf /users (UserController::index) aufrufen', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $response = $this->get('/users');

    expect($response->status())->toBeIn([200, 302]);
});

it('Reader darf /users (UserController::index) nicht aufrufen — 403', function () {
    /** @var TestCase $this */
    /** @var User $reader */
    $reader = User::factory()->create();
    $reader->assignRole('Reader');
    $this->actingAs($reader);

    $response = $this->get('/users');

    $response->assertStatus(403);
});

it('Admin darf einen User editieren (UserController::edit)', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    /** @var User $target */
    $target = User::factory()->create();
    $target->assignRole('Reader'); // Target braucht eine Rolle — View rendert sonst rot
    $this->actingAs($admin);

    $response = $this->get('/users/'.$target->id.'/edit');

    expect($response->status())->toBeIn([200, 302]);
});

it('Reader darf einen User nicht editieren — 403', function () {
    /** @var TestCase $this */
    /** @var User $reader */
    $reader = User::factory()->create();
    $reader->assignRole('Reader');
    /** @var User $target */
    $target = User::factory()->create();
    $this->actingAs($reader);

    $response = $this->get('/users/'.$target->id.'/edit');

    $response->assertStatus(403);
});

// ---------- RoleController ----------

it('Admin darf /roles (RoleController::index) aufrufen', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $response = $this->get('/roles');

    expect($response->status())->toBeIn([200, 302]);
});

it('Reader darf /roles nicht aufrufen — 403', function () {
    /** @var TestCase $this */
    /** @var User $reader */
    $reader = User::factory()->create();
    $reader->assignRole('Reader');
    $this->actingAs($reader);

    $response = $this->get('/roles');

    $response->assertStatus(403);
});

it('Admin darf eine Rolle editieren (RoleController::edit)', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    // App\Models\Role::create — eigenes Modell extends Spatie's Role
    $role = Role::create(['name' => 'TestRole', 'guard_name' => 'web']);

    $response = $this->get('/roles/'.$role->id.'/edit');

    expect($response->status())->toBeIn([200, 302]);
});

it('Reader darf eine Rolle nicht editieren — 403', function () {
    /** @var TestCase $this */
    /** @var User $reader */
    $reader = User::factory()->create();
    $reader->assignRole('Reader');
    $this->actingAs($reader);

    $role = Role::create(['name' => 'TestRole2', 'guard_name' => 'web']);

    $response = $this->get('/roles/'.$role->id.'/edit');

    $response->assertStatus(403);
});

// ---------- D.4: ProjectController unter Policy (statt Permission-Middleware) ----------
//
// Vorher liefen `permission:add` / `permission:view` / `permission:comment`
// als Route-Middleware. Jetzt geht alles über ProjectPolicy.
// Diese Tests fixieren das umgestellte Verhalten.

it('Admin darf /projects (index) aufrufen', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $response = $this->get('/projects');

    expect($response->status())->toBeIn([200, 302]);
});

it('Reader darf /projects (index) aufrufen — vorher permission:view-Middleware, jetzt Policy::viewAny', function () {
    /** @var TestCase $this */
    /** @var User $reader */
    $reader = User::factory()->create();
    $reader->assignRole('Reader');
    $this->actingAs($reader);

    $response = $this->get('/projects');

    // Vor D.4: `permission:view`-Middleware ließ Reader durch.
    // Nach D.4: `Policy::viewAny` lässt jeden eingeloggten User
    // durch — die Filterung passiert weiterhin im Controller
    // (siehe `getAllProjects()`). Hinweis: das ist heute zu
    // liberal und wird in PR 2 nachgezogen.
    expect($response->status())->toBeIn([200, 302]);
});

it('Reader ohne add-Permission darf /projects/create nicht aufrufen — 403', function () {
    /** @var TestCase $this */
    /** @var User $reader */
    $reader = User::factory()->create();
    $reader->assignRole('Reader');
    $this->actingAs($reader);

    $response = $this->get('/projects/create');

    $response->assertStatus(403);
});

it('Admin darf /projects/create aufrufen', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $response = $this->get('/projects/create');

    expect($response->status())->toBeIn([200, 302]);
});
