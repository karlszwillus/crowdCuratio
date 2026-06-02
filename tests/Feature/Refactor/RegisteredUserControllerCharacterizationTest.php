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

use App\Models\ProjectUserPermission;
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

it('create: Admin sieht das Register-Formular mit verfügbaren Rollen', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $response = $this->get('/register');

    expect($response->status())->toBeIn([200, 302]);
});

it('create: Reader bekommt 403 (role:Admin-Middleware)', function () {
    /** @var TestCase $this */
    /** @var User $reader */
    $reader = User::factory()->create();
    $reader->assignRole('Reader');
    $this->actingAs($reader);

    $response = $this->get('/register');

    $response->assertStatus(403);
});

it('store: bei gesetzter projectId werden project-scoped Permissions geschrieben', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $project = makeProject($admin);
    $readerRoleId = Role::where('name', 'Reader')->value('id');

    $response = $this->post('/register', [
        'firstName' => 'Erna',
        'lastName' => 'Beispiel',
        'email' => 'erna@example.test',
        'roles' => ['Reader'],
        'policy' => 'on',
        'projectId' => $project->id,
    ]);

    expect($response->status())->toBeIn([200, 302]);

    /** @var User $created */
    $created = User::query()->where('email', 'erna@example.test')->firstOrFail();

    $viewPermissionId = Permission::where('name', 'view')->value('id');
    $pivot = ProjectUserPermission::query()
        ->where('user_id', $created->id)
        ->where('project_id', $project->id)
        ->where('permission_id', $viewPermissionId)
        ->first();

    expect($pivot)->not->toBeNull();
});

it('store: ein soft-deletetes User wird über den Reaktivierungs-Pfad wieder aktiviert', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    // Vorbedingung: User existiert, ist soft-deleted.
    /** @var User $existing */
    $existing = User::factory()->create([
        'email' => 'wieder@example.test',
        'deleted_at' => now()->subDay(),
    ]);

    $response = $this->post('/register', [
        'firstName' => 'Egal',
        'lastName' => 'Egal',
        'email' => 'wieder@example.test',
        'roles' => ['Reader'],
        'policy' => 'on',
    ]);

    expect($response->status())->toBeIn([200, 302]);

    // Reaktivierungs-Pfad räumt deleted_at via DB::table-update —
    // ohne SoftDeletes-Scope. Direkt-Query liest den frischen Stand.
    $reactivated = DB::table('users')->where('email', 'wieder@example.test')->first();
    expect($reactivated->deleted_at)->toBeNull();
});

it('store: Admin lädt einen Admin-User ein (adminUser=true), Spatie-Rolle Admin wird gesetzt', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $response = $this->post('/register', [
        'firstName' => 'Frieda',
        'lastName' => 'Admin',
        'email' => 'frieda@example.test',
        'roles' => ['Reader'], // wird ignoriert, weil adminUser=true den Admin-Pfad nimmt
        'policy' => 'on',
        'adminUser' => '1',
    ]);

    expect($response->status())->toBeIn([200, 302]);

    /** @var User $created */
    $created = User::query()->where('email', 'frieda@example.test')->firstOrFail();
    expect($created->hasRole('Admin'))->toBeTrue();
    expect((bool) $created->is_admin)->toBeTrue();
});

it('store: Reader kann adminUser=true NICHT setzen — kein Privilege-Escalation', function () {
    /** @var TestCase $this */
    // Reader hat keinen Zugriff auf POST /register (role:Admin-Middleware).
    // Der Test fixiert NF-SEC-202: selbst wenn die Middleware fiele,
    // würde der Controller das adminUser-Flag ignorieren, weil der
    // Caller kein Admin ist. Hier prüfen wir den Middleware-Schutz.
    /** @var User $reader */
    $reader = User::factory()->create();
    $reader->assignRole('Reader');
    $this->actingAs($reader);

    $response = $this->post('/register', [
        'firstName' => 'Versuch',
        'lastName' => 'Eskalation',
        'email' => 'eskalation@example.test',
        'roles' => ['Reader'],
        'policy' => 'on',
        'adminUser' => '1',
    ]);

    $response->assertStatus(403);

    // User wurde nicht angelegt.
    expect(User::query()->where('email', 'eskalation@example.test')->exists())->toBeFalse();
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
