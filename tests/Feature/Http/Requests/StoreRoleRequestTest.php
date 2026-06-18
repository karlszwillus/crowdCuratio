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

use App\Http\Requests\StoreRoleRequest;
use App\Models\User;
use App\Support\PermissionName;
use App\Support\RoleName;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| StoreRoleRequest
|--------------------------------------------------------------------------
|
| Block E / Welle E.4 — Admin legt eine neue Rolle an.
| Authorize: nur Admin. Felder: name (unique) + permission-Array.
*/

beforeEach(function () {
    foreach (PermissionName::all() as $permissionName) {
        Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
    }

    Role::firstOrCreate(['name' => RoleName::ADMIN->value, 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => RoleName::READER->value, 'guard_name' => 'web']);
});

it('authorize: Admin darf', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole(RoleName::ADMIN->value);

    $request = StoreRoleRequest::create('/roles', 'POST');
    $request->setUserResolver(fn () => $admin);

    expect($request->authorize())->toBeTrue();
});

it('authorize: Reader darf NICHT', function () {
    /** @var TestCase $this */
    /** @var User $reader */
    $reader = User::factory()->create();
    $reader->assignRole(RoleName::READER->value);

    $request = StoreRoleRequest::create('/roles', 'POST');
    $request->setUserResolver(fn () => $reader);

    expect($request->authorize())->toBeFalse();
});

it('authorize: nicht-eingeloggter User darf NICHT', function () {
    /** @var TestCase $this */
    $request = StoreRoleRequest::create('/roles', 'POST');
    $request->setUserResolver(fn () => null);

    expect($request->authorize())->toBeFalse();
});

it('rules: name ist pflicht und unique', function () {
    /** @var TestCase $this */
    $request = new StoreRoleRequest;

    expect($request->rules())->toHaveKey('name');
    expect($request->rules()['name'])->toContain('required');
    expect($request->rules()['name'])->toContain('unique:roles,name');
});

it('rules: permission ist pflicht', function () {
    /** @var TestCase $this */
    $request = new StoreRoleRequest;

    expect($request->rules())->toHaveKey('permission');
    expect($request->rules()['permission'])->toContain('required');
});
