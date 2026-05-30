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
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWelcomeValidUntilFieldToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('welcome_valid_until')->nullable();
        });
    }

    /**
     * F-DB-019 / Phase 2 / E.7: vorher fehlte die down() komplett. Ein
     * `migrate:rollback` lief still ohne Effekt und liess die Spalte
     * im Schema stehen — Konvention der Codebasis ist aber, dass jede
     * Migration eine echte Reverse-Operation hat. hasColumn-Guard,
     * damit der Rollback auch auf einer DB sicher ist, die up() nie
     * durchlaufen hatte.
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'welcome_valid_until')) {
                $table->dropColumn('welcome_valid_until');
            }
        });
    }
}
