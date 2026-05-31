<?php

/**
crowdCuratio - Curating together virtually
Copyright (C)2022, 2026 - berlinHistory e.V.

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

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreateAdminUserSeeder extends Seeder
{
    /**
     * Seed the initial admin user.
     *
     * Reads admin.email, admin.password and (optional) admin.name from
     * the application config (config/admin.php — gespeist aus
     * ADMIN_EMAIL / ADMIN_PASSWORD / ADMIN_NAME / ADMIN_LAST_NAME in
     * .env). Refuses to run if the required values are missing — das
     * verhindert das historische "leeres Passwort Admin"-Footgun
     * (siehe ADR-0013, Befund F-SEC-009).
     *
     * Der Umweg über config() statt env() ist nötig, damit
     * `php artisan config:cache` den Seeder nicht aushebelt
     * (Larastan-Regel `larastan.noEnvCallsOutsideOfConfig`).
     *
     * The seeder is idempotent: re-running it does not reset an
     * existing admin's password and does not duplicate the Admin role.
     *
     * @return void
     */
    public function run()
    {
        $email = config('admin.email');
        $password = config('admin.password');

        if (empty($email) || empty($password)) {
            throw new \RuntimeException(
                'CreateAdminUserSeeder requires ADMIN_EMAIL and ADMIN_PASSWORD '
                .'to be set in your .env file before running db:seed. '
                .'See README and ADR-0013.'
            );
        }

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => config('admin.name', 'Admin'),
                'last_name' => config('admin.last_name', ''),
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );

        $role = Role::firstOrCreate(
            ['name' => 'Admin', 'guard_name' => 'web']
        );

        $permissions = Permission::pluck('id', 'id')->all();
        $role->syncPermissions($permissions);

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }
    }
}
