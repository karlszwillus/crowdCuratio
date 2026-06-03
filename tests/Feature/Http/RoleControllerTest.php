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

use App\Models\PermissionDescription;
use App\Models\User;
use App\Support\PermissionName;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| RoleController — Coverage-Push Block D PR 2 / Welle 2c
|--------------------------------------------------------------------------
|
| Heutige Authorization: `role:Admin` auf index/edit/destroy. Die
| Resource-Routes create/store/show/update gehen über die
| `auth`-Mittelschicht — Reader können das nicht aufrufen, weil
| der Admin-Bereich frontend-seitig nicht verlinkt ist; eine
| Backend-Härtung dieser Mid-Routes steht für eine spätere Welle
| (Inkonsistenz im heutigen Permission-Modell).
*/

beforeEach(function () {
    foreach (PermissionName::all() as $position => $permissionName) {
        $perm = Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);

        // PermissionDescription: `permission_id` und `position` sind
        // bewusst nicht in `$fillable` (verhindert Mass-Assignment
        // auf Lifecycle-Feldern). Property-Setter umgeht den
        // Schutz, ohne ihn aufzuweichen — gleiche Variante, die der
        // PermissionTableSeeder via updateOrCreate-Pfad fährt.
        if (! PermissionDescription::where('permission_id', $perm->id)->exists()) {
            $desc = new PermissionDescription;
            $desc->permission_id = $perm->id;
            // `description` ist Spatie-Translatable (JSON-Spalte mit
            // Locale-Container). Direktes Array-Assignment widerspricht
            // dem deklarierten `string`-Property-Type. setTranslation()
            // ist der API-konforme Weg.
            $desc->setTranslation('description', 'de', $permissionName);
            $desc->setTranslation('description', 'en', $permissionName);
            $desc->position = $position;
            $desc->save();
        }
    }

    Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web'])
        ->syncPermissions(Permission::all());
    Role::firstOrCreate(['name' => 'Reader', 'guard_name' => 'web'])
        ->syncPermissions(['view']);
});

it('index: Admin sieht die Rollen-Liste', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $response = $this->get('/roles');

    expect($response->status())->toBeIn([200, 302]);
});

it('index: Reader bekommt 403', function () {
    /** @var TestCase $this */
    /** @var User $reader */
    $reader = User::factory()->create();
    $reader->assignRole('Reader');
    $this->actingAs($reader);

    $response = $this->get('/roles');

    $response->assertStatus(403);
});

it('create: Admin sieht das Anlege-Formular', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $response = $this->get('/roles/create');

    expect($response->status())->toBeIn([200, 302]);
});

it('store: Admin legt eine neue Rolle mit Permissions an', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $viewPermission = Permission::where('name', 'view')->first();
    $addPermission = Permission::where('name', 'add')->first();

    $response = $this->post('/roles', [
        'name' => 'NeueRolle',
        'permission' => [$viewPermission->id, $addPermission->id],
    ]);

    expect($response->status())->toBeIn([200, 302]);

    $role = Role::where('name', 'NeueRolle')->first();
    expect($role)->not->toBeNull();
    expect($role->permissions->pluck('name')->toArray())->toContain('view', 'add');
});

it('update: Admin ändert Rollen-Name und Permissions', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $role = Role::create(['name' => 'Bisher', 'guard_name' => 'web']);
    $role->syncPermissions(['view']);

    $editPermission = Permission::where('name', 'edit')->first();

    $response = $this->patch('/roles/'.$role->id, [
        'name' => 'Umbenannt',
        'permission' => [$editPermission->id],
    ]);

    expect($response->status())->toBeIn([200, 302]);

    $role->refresh();
    expect($role->name)->toBe('Umbenannt');
    expect($role->permissions->pluck('name')->toArray())->toBe(['edit']);
});

it('destroy: Admin löscht eine Rolle', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $role = Role::create(['name' => 'ZuLoeschen', 'guard_name' => 'web']);

    $response = $this->delete('/roles/'.$role->id);

    expect($response->status())->toBeIn([200, 302]);
    expect(Role::where('name', 'ZuLoeschen')->exists())->toBeFalse();
});

it('show: Admin sieht eine Rolle mit ihren Permissions', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $readerRoleId = Role::where('name', 'Reader')->value('id');

    $response = $this->get('/roles/'.$readerRoleId);

    expect($response->status())->toBeIn([200, 302]);
});

it('customizedDelete: löscht Rolle und weist User auf eine andere Rolle um', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    /** @var User $existing */
    $existing = User::factory()->create();
    $this->actingAs($admin);

    $oldRole = Role::create(['name' => 'AltzuLoeschen', 'guard_name' => 'web']);
    $newRole = Role::firstOrCreate(['name' => 'Reader', 'guard_name' => 'web']);
    $existing->assignRole('AltzuLoeschen');

    $response = $this->post('/role/'.$oldRole->id.'/alt/'.$newRole->id.'/');

    expect($response->status())->toBeIn([200, 302]);

    // Alte Rolle ist weg.
    expect(Role::where('name', 'AltzuLoeschen')->exists())->toBeFalse();

    // User hat jetzt die Reader-Rolle.
    $existing->refresh();
    expect($existing->hasRole('Reader'))->toBeTrue();
});

it('customizedDelete: nicht-numerischer alt-Parameter wird abgewiesen, Rolle bleibt', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $role = Role::create(['name' => 'BleibtBestehen', 'guard_name' => 'web']);

    // Nicht-numerischer alt → Controller-Guard greift, kein Löschen.
    $response = $this->post('/role/'.$role->id.'/alt/abc/');

    // Controller gibt $this zurück (kein Redirect, kein Crash —
    // beobachtbares Verhalten ist: Rolle bleibt da).
    expect(Role::where('name', 'BleibtBestehen')->exists())->toBeTrue();
});

it('roleHasUsers: liefert true wenn die Rolle Zuweisungen hat', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    /** @var User $other */
    $other = User::factory()->create();
    $other->assignRole('Reader');
    $this->actingAs($admin);

    $readerRoleId = Role::where('name', 'Reader')->value('id');

    $response = $this->get('/role/check/'.$readerRoleId.'/');

    // Hinweis: $response->json() wirft "Invalid JSON" bei Top-Level
    // false/null — Laravel's TestResponse-Decoder kann das nicht von
    // Parse-Fehlern unterscheiden. Direkt auf den Content-String
    // assertieren.
    $response->assertStatus(200);
    expect($response->getContent())->toBe('true');
});

it('roleHasUsers: liefert false wenn die Rolle keine Zuweisungen hat', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $emptyRole = Role::create(['name' => 'Ungenutzt', 'guard_name' => 'web']);

    $response = $this->get('/role/check/'.$emptyRole->id.'/');

    $response->assertStatus(200);
    expect($response->getContent())->toBe('false');
});
