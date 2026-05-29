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

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleTableSeeder extends Seeder
{
    /**
     * Seed the default project roles used in the registration UI.
     *
     * Without these roles the "Default Rolle" combobox in the
     * registration form (/register) is effectively empty in a fresh
     * install — the only role created elsewhere is "Admin" via
     * CreateAdminUserSeeder. That makes user invitations impossible
     * until somebody manually adds a role through the UI (smoke-test
     * finding AM-D-3).
     *
     * The seeder is idempotent:
     *   - Role::firstOrCreate keeps existing roles in place.
     *   - syncPermissions re-aligns the role to the declared
     *     permission set on every run, so removing a permission here
     *     and re-running the seeder revokes it cleanly.
     *
     * Run standalone:
     *   php artisan db:seed --class=Database\\Seeders\\RoleTableSeeder
     *
     * @return void
     */
    public function run()
    {
        $roles = [
            // Vollwertige Mitarbeit am Inhalt, ohne Nutzerverwaltung.
            'Editor' => ['view', 'add', 'edit', 'delete', 'publish', 'comment'],
            // Begutachter — darf sehen und kommentieren.
            'Reviewer' => ['view', 'comment'],
            // Reine Leserolle.
            'Reader' => ['view'],
        ];

        foreach ($roles as $name => $permissionNames) {
            $role = Role::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web']
            );
            $permissions = Permission::whereIn('name', $permissionNames)->get();
            $role->syncPermissions($permissions);
        }
    }
}
