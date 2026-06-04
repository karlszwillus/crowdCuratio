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

use App\Http\Requests\UpdateOwnProfileRequest;
use App\Models\User;
use App\Support\PermissionName;
use App\Support\RoleName;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| UpdateOwnProfileRequest
|--------------------------------------------------------------------------
|
| Block E / Welle E.3 — Self-Edit-Pfad auf `PATCH /profile`.
| Authorize: eingeloggter User. Target ist implizit der Caller.
| Felder: firstName / lastName / optional old_password +
| new_password + confirm_password.
| Bewusst KEIN roles-Feld — Rollen-Sync ist Admin-Pfad.
*/

beforeEach(function () {
    foreach (PermissionName::all() as $permissionName) {
        Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
    }

    Role::firstOrCreate(['name' => RoleName::READER->value, 'guard_name' => 'web']);
});

it('authorize: eingeloggter User darf', function () {
    /** @var TestCase $this */
    /** @var User $user */
    $user = User::factory()->create();
    $user->assignRole(RoleName::READER->value);

    $request = UpdateOwnProfileRequest::create('/profile', 'PATCH');
    $request->setUserResolver(fn () => $user);

    expect($request->authorize())->toBeTrue();
});

it('authorize: nicht-eingeloggter User darf NICHT', function () {
    /** @var TestCase $this */
    $request = UpdateOwnProfileRequest::create('/profile', 'PATCH');
    $request->setUserResolver(fn () => null);

    expect($request->authorize())->toBeFalse();
});

it('rules: firstName und lastName sind pflicht', function () {
    /** @var TestCase $this */
    $request = new UpdateOwnProfileRequest;

    expect($request->rules())->toHaveKey('firstName');
    expect($request->rules())->toHaveKey('lastName');
    expect($request->rules()['firstName'])->toContain('required');
    expect($request->rules()['lastName'])->toContain('required');
});

it('rules: old_password / new_password / confirm_password sind optional, hängen aber zusammen', function () {
    /** @var TestCase $this */
    $request = new UpdateOwnProfileRequest;

    expect($request->rules())->toHaveKey('old_password');
    expect($request->rules())->toHaveKey('new_password');
    expect($request->rules())->toHaveKey('confirm_password');
});

it('rules: kein roles-Feld zugelassen — Self-Edit darf keine Rollen setzen', function () {
    /** @var TestCase $this */
    $request = new UpdateOwnProfileRequest;

    expect($request->rules())->not->toHaveKey('roles');
});
