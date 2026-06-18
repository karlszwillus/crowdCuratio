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

use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\UserOnboardingService;
use App\Support\PermissionName;
use App\Support\RoleName;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/*
|--------------------------------------------------------------------------
| UserOnboardingService
|--------------------------------------------------------------------------
|
| Block E / Welle E.5. Vorher 30+ Zeilen inline in
| RegisteredUserController::store: User-Erzeugung via Property-
| Setter (NF-SEC-202 Privilege-Check), Rollen-Sync, Welcome-Mail.
| Service kapselt das mit explizitem $caller-Parameter — die
| Privilege-Check-Logik (adminUser/createProject nur durch Admin)
| ist damit isoliert testbar.
*/

beforeEach(function () {
    foreach (PermissionName::all() as $permissionName) {
        Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
    }

    Role::firstOrCreate(['name' => RoleName::ADMIN->value, 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => RoleName::READER->value, 'guard_name' => 'web']);
});

function buildRequest(array $payload): RegisterRequest
{
    $request = RegisterRequest::create('/register', 'POST', $payload);
    $request->setContainer(app());
    $request->setRedirector(app('redirect'));

    return $request;
}

it('createInvitedUser: legt User mit Name, Email und Reader-Rolle an', function () {
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole(RoleName::ADMIN->value);

    $request = buildRequest([
        'firstName' => 'Anna',
        'lastName' => 'Beispiel',
        'email' => 'anna@example.test',
        'policy' => 'on',
    ]);

    $readerRole = Role::where('name', RoleName::READER->value)->first();

    $service = new UserOnboardingService;
    $user = $service->createInvitedUser($admin, $request, [$readerRole]);

    expect($user->email)->toBe('anna@example.test');
    expect($user->name)->toBe('Anna');
    expect($user->last_name)->toBe('Beispiel');
    expect($user->hasRole(RoleName::READER->value))->toBeTrue();
});

it('createInvitedUser: Admin-Caller mit adminUser=true setzt Admin-Flag + Rolle', function () {
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole(RoleName::ADMIN->value);

    $request = buildRequest([
        'firstName' => 'Bea',
        'lastName' => 'Admin',
        'email' => 'bea@example.test',
        'policy' => 'on',
        'adminUser' => '1',
    ]);

    $adminRole = Role::where('name', RoleName::ADMIN->value)->first();

    $service = new UserOnboardingService;
    $user = $service->createInvitedUser($admin, $request, [$adminRole]);

    expect((bool) $user->is_admin)->toBeTrue();
    expect($user->hasRole(RoleName::ADMIN->value))->toBeTrue();
});

it('createInvitedUser: Nicht-Admin-Caller mit adminUser=true wird ignoriert (Privilege-Check)', function () {
    /** @var User $reader */
    $reader = User::factory()->create();
    $reader->assignRole(RoleName::READER->value);

    $request = buildRequest([
        'firstName' => 'Versuch',
        'lastName' => 'Eskalation',
        'email' => 'eskalation@example.test',
        'policy' => 'on',
        'adminUser' => '1',
    ]);

    $readerRole = Role::where('name', RoleName::READER->value)->first();

    $service = new UserOnboardingService;
    $user = $service->createInvitedUser($reader, $request, [$readerRole]);

    expect((bool) $user->is_admin)->toBeFalse();
});

it('createInvitedUser: Admin-Caller mit createProject=true setzt create_project=true', function () {
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole(RoleName::ADMIN->value);

    $request = buildRequest([
        'firstName' => 'Cora',
        'lastName' => 'Projekt',
        'email' => 'cora@example.test',
        'policy' => 'on',
        'createProject' => '1',
    ]);

    $readerRole = Role::where('name', RoleName::READER->value)->first();

    $service = new UserOnboardingService;
    $user = $service->createInvitedUser($admin, $request, [$readerRole]);

    expect((bool) $user->create_project)->toBeTrue();
});

it('createInvitedUser: Nicht-Admin-Caller mit createProject=true wird ignoriert', function () {
    /** @var User $reader */
    $reader = User::factory()->create();
    $reader->assignRole(RoleName::READER->value);

    $request = buildRequest([
        'firstName' => 'Dora',
        'lastName' => 'Projekt',
        'email' => 'dora@example.test',
        'policy' => 'on',
        'createProject' => '1',
    ]);

    $readerRole = Role::where('name', RoleName::READER->value)->first();

    $service = new UserOnboardingService;
    $user = $service->createInvitedUser($reader, $request, [$readerRole]);

    expect((bool) $user->create_project)->toBeFalse();
});

it('createInvitedUser: ohne Caller-Authentifizierung kein Admin-Flag', function () {
    $request = buildRequest([
        'firstName' => 'Egon',
        'lastName' => 'Anonym',
        'email' => 'egon@example.test',
        'policy' => 'on',
        'adminUser' => '1',
    ]);

    $readerRole = Role::where('name', RoleName::READER->value)->first();

    $service = new UserOnboardingService;
    $user = $service->createInvitedUser(null, $request, [$readerRole]);

    expect((bool) $user->is_admin)->toBeFalse();
    expect((bool) $user->create_project)->toBeFalse();
});
