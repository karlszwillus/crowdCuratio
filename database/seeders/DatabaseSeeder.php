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

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Reihenfolge ist wichtig: erst Permissions anlegen, dann den
     * Admin, damit das `assignRole`/`syncPermissions` im
     * CreateAdminUserSeeder eine gefüllte Permission-Tabelle vorfindet.
     *
     * PreviewSeeder ist bewusst NICHT Teil des Default-Runs — er
     * erzeugt Demo-Inhalte und ist nur über expliziten Aufruf
     * gewünscht:
     *   php artisan db:seed --class=Database\\Seeders\\PreviewSeeder
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            PermissionTableSeeder::class,
            RoleTableSeeder::class,
            CreateAdminUserSeeder::class,
        ]);
    }
}
