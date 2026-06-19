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

use App\Support\PermissionName;
use App\Support\RoleName;
use App\Support\RoleResolver;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/*
|--------------------------------------------------------------------------
| RoleResolver
|--------------------------------------------------------------------------
|
| Block E / Welle E.5 — vor diesem Refactor war der Resolver eine
| private Methode in RegisteredUserController. Jetzt eigene
| Helper-Klasse in App\Support, analog PermissionName und RoleName.
|
| Akzeptiert: Single-String, Array, Role-Name, numerische Role-ID.
| Wirft RoleDoesNotExist bei ungültigen Werten — bewusste Härte,
| weil ein ungültiger Role-Submit ein Form-Bug ist.
*/

beforeEach(function () {
    foreach (PermissionName::all() as $permissionName) {
        Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
    }

    Role::firstOrCreate(['name' => RoleName::ADMIN->value, 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => RoleName::READER->value, 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => RoleName::EDITOR->value, 'guard_name' => 'web']);
});

it('resolve: leerer Input gibt leeres Array', function () {
    $resolver = new RoleResolver;

    expect($resolver->resolve(null))->toBe([]);
    expect($resolver->resolve(''))->toBe([]);
    expect($resolver->resolve([]))->toBe([]);
});

it('resolve: Single-String mit Role-Name → ein Role-Element', function () {
    $resolver = new RoleResolver;

    $result = $resolver->resolve(RoleName::READER->value);

    expect($result)->toHaveCount(1);
    expect($result[0]->name)->toBe(RoleName::READER->value);
});

it('resolve: Array mit Role-Namen → mehrere Role-Elemente', function () {
    $resolver = new RoleResolver;

    $result = $resolver->resolve([RoleName::READER->value, RoleName::EDITOR->value]);

    expect($result)->toHaveCount(2);
    expect($result[0]->name)->toBe(RoleName::READER->value);
    expect($result[1]->name)->toBe(RoleName::EDITOR->value);
});

it('resolve: numerischer String → Lookup by ID', function () {
    $resolver = new RoleResolver;

    $editorId = Role::where('name', RoleName::EDITOR->value)->value('id');

    $result = $resolver->resolve((string) $editorId);

    expect($result)->toHaveCount(1);
    expect($result[0]->id)->toBe($editorId);
});

it('resolve: Array mit numerischen Strings → ID-Lookup', function () {
    $resolver = new RoleResolver;

    $editorId = Role::where('name', RoleName::EDITOR->value)->value('id');
    $readerId = Role::where('name', RoleName::READER->value)->value('id');

    $result = $resolver->resolve([(string) $editorId, (string) $readerId]);

    expect($result)->toHaveCount(2);
});
