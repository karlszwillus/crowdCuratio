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
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| UserController — Coverage-Push Block D PR 2 / Welle 2c
|--------------------------------------------------------------------------
|
| Heutige Authorization-Schicht: `role:Admin` auf index/edit/destroy.
| update() ist auf Eigentümerschaft beschränkt (kein Middleware, der
| User editiert sich selbst). resendInvitation läuft auf jedem
| eingeloggten User.
*/

beforeEach(function () {
    foreach (PermissionName::all() as $permissionName) {
        Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
    }

    Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web'])
        ->syncPermissions(Permission::all());
    Role::firstOrCreate(['name' => 'Reader', 'guard_name' => 'web'])
        ->syncPermissions(['view']);
});

it('index: Admin sieht die User-Liste', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $response = $this->get('/users');

    expect($response->status())->toBeIn([200, 302]);
});

it('index: Reader bekommt 403', function () {
    /** @var TestCase $this */
    /** @var User $reader */
    $reader = User::factory()->create();
    $reader->assignRole('Reader');
    $this->actingAs($reader);

    $response = $this->get('/users');

    $response->assertStatus(403);
});

it('update: Admin ändert Name und Rolle eines anderen Users', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    /** @var User $target */
    $target = User::factory()->create(['name' => 'Alt', 'last_name' => 'Vorgestern']);
    $target->assignRole('Reader');
    $this->actingAs($admin);

    $response = $this->patch('/users/'.$target->id, [
        'firstName' => 'Neu',
        'lastName' => 'Heute',
        'roles' => ['Reader'],
    ]);

    expect($response->status())->toBeIn([200, 302]);

    $target->refresh();
    expect($target->name)->toBe('Neu');
    expect($target->last_name)->toBe('Heute');
    expect($target->hasRole('Reader'))->toBeTrue();
});

// ---------- Authorization-Bypass-Charakterisierung (Hotfix) ----------
//
// Vor dem Hotfix war `UserController::update` weder per Middleware noch
// per inline-Authorize geschützt. Reader konnten via
// `PATCH /users/{anderer}` `roles=['Admin']` schicken — Target wurde Admin.
// Diese Tests fixieren das geschlossene Verhalten.

it('update: Reader darf fremden User NICHT updaten — 403', function () {
    /** @var TestCase $this */
    /** @var User $reader */
    $reader = User::factory()->create();
    $reader->assignRole('Reader');
    /** @var User $target */
    $target = User::factory()->create();
    $target->assignRole('Reader');
    $this->actingAs($reader);

    $response = $this->patch('/users/'.$target->id, [
        'firstName' => 'Eskalation',
        'lastName' => 'Versuch',
        'roles' => ['Admin'],
    ]);

    $response->assertStatus(403);

    // Target hat KEINE Admin-Rolle bekommen.
    $target->refresh();
    expect($target->hasRole('Admin'))->toBeFalse();
});

it('updateProfile: Reader updatet sein eigenes Profil über PATCH /profile', function () {
    /** @var TestCase $this */
    /** @var User $reader */
    $reader = User::factory()->create(['name' => 'Alt', 'last_name' => 'Vorher']);
    $reader->assignRole('Reader');
    $this->actingAs($reader);

    $response = $this->patch('/profile', [
        'firstName' => 'Neu',
        'lastName' => 'Heute',
    ]);

    expect($response->status())->toBeIn([200, 302]);

    $reader->refresh();
    expect($reader->name)->toBe('Neu');
    expect($reader->last_name)->toBe('Heute');
});

it('updateProfile: das roles-Feld wird ignoriert (Self-Edit kann keine Rollen setzen)', function () {
    /** @var TestCase $this */
    /** @var User $reader */
    $reader = User::factory()->create();
    $reader->assignRole('Reader');
    $this->actingAs($reader);

    $response = $this->patch('/profile', [
        'firstName' => $reader->name,
        'lastName' => $reader->last_name,
        'roles' => ['Admin'],
    ]);

    expect($response->status())->toBeIn([200, 302]);

    $reader->refresh();
    expect($reader->hasRole('Admin'))->toBeFalse();
    expect($reader->hasRole('Reader'))->toBeTrue();
});

it('destroy: Admin soft-deleted einen User', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    /** @var User $target */
    $target = User::factory()->create();
    $target->assignRole('Reader');
    $this->actingAs($admin);

    $response = $this->delete('/users/'.$target->id);

    expect($response->status())->toBeIn([200, 302]);

    $target->refresh();
    expect($target->deleted_at)->not->toBeNull();
});

it('destroy: Reader bekommt 403', function () {
    /** @var TestCase $this */
    /** @var User $reader */
    $reader = User::factory()->create();
    $reader->assignRole('Reader');
    /** @var User $target */
    $target = User::factory()->create();
    $target->assignRole('Reader');
    $this->actingAs($reader);

    $response = $this->delete('/users/'.$target->id);

    $response->assertStatus(403);

    $target->refresh();
    expect($target->deleted_at)->toBeNull();
});

it('resendInvitation: setzt welcome_valid_until neu', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    /** @var User $invitee */
    $invitee = User::factory()->create(['welcome_valid_until' => now()->subDay()]);
    $invitee->assignRole('Reader');
    $this->actingAs($admin);

    $response = $this->get('/user/'.$invitee->id.'/invitation');

    expect($response->status())->toBeIn([200, 302]);

    $invitee->refresh();
    expect($invitee->welcome_valid_until)->not->toBeNull();
    expect($invitee->welcome_valid_until->greaterThan(now()))->toBeTrue();
});

it('updateProfile: User ändert sein eigenes Passwort über den old_password-Pfad', function () {
    /** @var TestCase $this */
    /** @var User $user */
    $user = User::factory()->create(['password' => Hash::make('alt-passwort-123')]);
    $user->assignRole('Reader');
    $this->actingAs($user);

    $response = $this->patch('/profile', [
        'firstName' => $user->name,
        'lastName' => $user->last_name,
        'old_password' => 'alt-passwort-123',
        'new_password' => 'neues-passwort-456',
        'confirm_password' => 'neues-passwort-456',
    ]);

    expect($response->status())->toBeIn([200, 302]);

    $user->refresh();
    expect(Hash::check('neues-passwort-456', $user->password))->toBeTrue();
});

it('updateProfile: falsches old_password schlägt fehl, Passwort bleibt unverändert', function () {
    /** @var TestCase $this */
    /** @var User $user */
    $user = User::factory()->create(['password' => Hash::make('alt-passwort-123')]);
    $user->assignRole('Reader');
    $this->actingAs($user);

    $response = $this->from('/profile')->patch('/profile', [
        'firstName' => $user->name,
        'lastName' => $user->last_name,
        'old_password' => 'falsches-passwort',
        'new_password' => 'neues-passwort-456',
        'confirm_password' => 'neues-passwort-456',
    ]);

    // Validation-Fail → Redirect zurück (302) mit Fehlern.
    expect($response->status())->toBe(302);

    $user->refresh();
    expect(Hash::check('alt-passwort-123', $user->password))->toBeTrue();
});

it('profile: eingeloggter User sieht sein Profil', function () {
    /** @var TestCase $this */
    /** @var User $user */
    $user = User::factory()->create();
    $user->assignRole('Reader');
    $this->actingAs($user);

    $response = $this->get('/profile');

    expect($response->status())->toBeIn([200, 302]);
});
