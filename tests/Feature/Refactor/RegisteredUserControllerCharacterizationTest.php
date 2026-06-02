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
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| RegisteredUserController::store — Role-Resolver
|--------------------------------------------------------------------------
|
| Block D PR 2 / Smoke-Fix. Spatie v6 interpretiert Strings, die an
| `assignRole` übergeben werden, strikt als Rollen-Namen — auch
| numerische Strings wie "20". Die /register-Form hat sich über die
| Zeit unterschiedlich verhalten (mal Name-Submit, mal ID-Submit);
| im Smoke schlug ID-Submit als RoleDoesNotExist auf.
|
| Diese Charakterisierungs-Tests fixieren beide Eingabewege:
| Name-Submit (heutiges Blade) und ID-Submit (alter View / API).
*/

beforeEach(function () {
    foreach (PermissionName::all() as $permissionName) {
        Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
    }

    Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web'])
        ->syncPermissions(Permission::all());
    Role::firstOrCreate(['name' => 'Reader', 'guard_name' => 'web'])
        ->syncPermissions(['view']);
    Role::firstOrCreate(['name' => 'Editor', 'guard_name' => 'web'])
        ->syncPermissions(['view', 'add', 'edit']);
});

it('store: Admin lädt neuen User mit Role-Namen ein (heutiges Blade)', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $response = $this->post('/register', [
        'firstName' => 'Berta',
        'lastName' => 'Beispiel',
        'email' => 'berta@example.test',
        'roles' => ['Editor'],
        'policy' => 'on',
    ]);

    expect($response->status())->toBeIn([200, 302]);

    /** @var User $created */
    $created = User::query()->where('email', 'berta@example.test')->firstOrFail();
    expect($created->hasRole('Editor'))->toBeTrue();
});

it('store: Admin lädt neuen User mit Role-ID ein (alter View / API-Client)', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $editorRoleId = Role::where('name', 'Editor')->value('id');

    $response = $this->post('/register', [
        'firstName' => 'Carlo',
        'lastName' => 'Beispiel',
        'email' => 'carlo@example.test',
        // Numerischer String, simuliert ein Form/Client mit `value="{$role->id}"`.
        'roles' => [(string) $editorRoleId],
        'policy' => 'on',
    ]);

    expect($response->status())->toBeIn([200, 302]);

    /** @var User $created */
    $created = User::query()->where('email', 'carlo@example.test')->firstOrFail();
    expect($created->hasRole('Editor'))->toBeTrue();
});

it('store: Admin lädt neuen User mit Role-Name als Single-String ein (kein Array)', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $response = $this->post('/register', [
        'firstName' => 'Dora',
        'lastName' => 'Beispiel',
        'email' => 'dora@example.test',
        // Bewusst Single-String statt Array — Spatie und Blade
        // sollten beides verkraften.
        'roles' => 'Reader',
        'policy' => 'on',
    ]);

    expect($response->status())->toBeIn([200, 302]);

    /** @var User $created */
    $created = User::query()->where('email', 'dora@example.test')->firstOrFail();
    expect($created->hasRole('Reader'))->toBeTrue();
});
