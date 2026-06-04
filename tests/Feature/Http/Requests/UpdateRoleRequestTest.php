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

use App\Http\Requests\UpdateRoleRequest;
use App\Models\User;
use App\Support\PermissionName;
use App\Support\RoleName;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| UpdateRoleRequest
|--------------------------------------------------------------------------
|
| Block E / Welle E.4 — Admin ändert eine bestehende Rolle.
| Authorize: nur Admin. Felder: name (required, kein unique-Check
| weil Update auf bestehender Rolle) + permission-Array.
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

    $request = UpdateRoleRequest::create('/roles/1', 'PATCH');
    $request->setUserResolver(fn () => $admin);

    expect($request->authorize())->toBeTrue();
});

it('authorize: Reader darf NICHT', function () {
    /** @var TestCase $this */
    /** @var User $reader */
    $reader = User::factory()->create();
    $reader->assignRole(RoleName::READER->value);

    $request = UpdateRoleRequest::create('/roles/1', 'PATCH');
    $request->setUserResolver(fn () => $reader);

    expect($request->authorize())->toBeFalse();
});

it('authorize: nicht-eingeloggter User darf NICHT', function () {
    /** @var TestCase $this */
    $request = UpdateRoleRequest::create('/roles/1', 'PATCH');
    $request->setUserResolver(fn () => null);

    expect($request->authorize())->toBeFalse();
});

it('rules: name und permission sind pflicht', function () {
    /** @var TestCase $this */
    $request = new UpdateRoleRequest;

    expect($request->rules())->toHaveKey('name');
    expect($request->rules())->toHaveKey('permission');
    expect($request->rules()['name'])->toContain('required');
    expect($request->rules()['permission'])->toContain('required');
});
