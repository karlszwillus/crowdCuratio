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
 * Setzt einen DB-Default '' für users.last_name.
 *
 * Die Spalte wurde von
 * 2021_05_07_105723_add_last_name_to_user_table.php als NOT NULL ohne
 * Default angelegt. Unter strict=false hat MySQL still einen leeren
 * String eingetragen, wenn ein Insert die Spalte nicht erwähnte
 * (z. B. User::factory()->create() aus dem Stock-Breeze-Test, das
 * Admin-Form bei Self-Registrierung, etc.). Mit strict=true (ADR-0011)
 * bricht das mit 1364.
 *
 * Empty-String als Default passt zur Semantik: ein Nachname ist nicht
 * fachlich erzwungen, viele User füllen ihn nur halb aus. Wer einen
 * required-Last-Name möchte, baut das per Validation auf Form-Ebene
 * statt per DB-Constraint.
 */
class DefaultForUsersLastName extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "ALTER TABLE `users` "
            . "MODIFY COLUMN `last_name` VARCHAR(255) NOT NULL DEFAULT ''"
        );
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            'ALTER TABLE `users` '
            . 'MODIFY COLUMN `last_name` VARCHAR(255) NOT NULL'
        );
    }
}
