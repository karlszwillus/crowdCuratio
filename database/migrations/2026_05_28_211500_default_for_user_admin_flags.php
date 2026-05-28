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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Gibt den NOT-NULL-Boolean-Spalten `users.is_admin` und
 * `users.create_project` einen DB-Default von 0 / false.
 *
 * Beide Spalten kamen aus
 * 2022_01_11_003028_add_fields_to_users_table.php
 * ohne Default-Wert. Unter dem alten strict=false-Setup hat MySQL
 * still 0 eingetragen, wenn das INSERT die Spalten nicht erwähnte.
 * Mit strict=true (ADR-0011) schlägt das mit 1364 "Field doesn't
 * have a default value" fehl — siehe Seeder-Aufruf von
 * CreateAdminUserSeeder.
 *
 * Setzt den Default auf 0/false. Existierende Datensätze behalten
 * ihren aktuellen Wert.
 *
 * SQLite (Test-Connection) wird hier nicht angefasst, weil die
 * Tests mit einer frischen In-Memory-DB starten und das Schema dort
 * via RefreshDatabase neu aufgebaut wird.
 */
class DefaultForUserAdminFlags extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            'ALTER TABLE `users` '
            . 'MODIFY COLUMN `is_admin` TINYINT(1) NOT NULL DEFAULT 0, '
            . 'MODIFY COLUMN `create_project` TINYINT(1) NOT NULL DEFAULT 0'
        );
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        // Defaults wieder entfernen — entspricht dem Original-Stand
        // aus add_fields_to_users_table.php, auch wenn der dort
        // strict-incompatible war.
        DB::statement(
            'ALTER TABLE `users` '
            . 'MODIFY COLUMN `is_admin` TINYINT(1) NOT NULL, '
            . 'MODIFY COLUMN `create_project` TINYINT(1) NOT NULL'
        );
    }
}
