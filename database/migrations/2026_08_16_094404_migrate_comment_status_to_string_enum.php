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

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5x.4: Kommentar-Status von integer auf string-enum.
 *
 * Vor: `status integer unsigned nullable` mit Werten aus
 * config/project.php (1..5). Nach: `status varchar(20) DEFAULT
 * 'open'` mit den vier Werten aus App\Support\CommentStatus.
 *
 * Mapping:
 *   1 (open)     → 'open'
 *   2 (accepted) → 'in_progress'
 *   3 (decline)  → 'rejected'
 *   4 (cancel)   → 'rejected'   (auch bewusst nicht umgesetzt)
 *   5 (done)     → 'resolved'
 *   NULL / andere → 'open'
 *
 * Reihenfolge:
 *   1. Neue Spalte `status_new` VARCHAR(20) NULL
 *   2. Daten mappen via UPDATE + CASE
 *   3. Alte Spalte droppen, neue umbenennen und Default setzen
 *
 * down() ist symmetrisch, wobei 'in_progress' → 2 (accepted)
 * und 'rejected' → 3 (decline) — der cancel-Nuancenverlust
 * ist nach dem Enum-Sprung nicht mehr rekonstruierbar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table): void {
            $table->string('status_new', 20)->nullable()->after('status');
        });

        // Bestandsdaten mappen. CASE-Ausdruck ist portabel ueber
        // MySQL / MariaDB und laesst sich in einem UPDATE erschlagen.
        DB::statement("
            UPDATE comments SET status_new = CASE status
                WHEN 1 THEN 'open'
                WHEN 2 THEN 'in_progress'
                WHEN 3 THEN 'rejected'
                WHEN 4 THEN 'rejected'
                WHEN 5 THEN 'resolved'
                ELSE 'open'
            END
        ");

        Schema::table('comments', function (Blueprint $table): void {
            $table->dropColumn('status');
        });

        Schema::table('comments', function (Blueprint $table): void {
            $table->renameColumn('status_new', 'status');
        });

        // Default setzen und NOT NULL machen (nach dem Rename,
        // weil renameColumn den Default nicht durchreicht).
        Schema::table('comments', function (Blueprint $table): void {
            $table->string('status', 20)->default('open')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table): void {
            $table->integer('status_old')->unsigned()->nullable()->after('status');
        });

        DB::statement("
            UPDATE comments SET status_old = CASE status
                WHEN 'open'        THEN 1
                WHEN 'in_progress' THEN 2
                WHEN 'resolved'    THEN 5
                WHEN 'rejected'    THEN 3
                ELSE 1
            END
        ");

        Schema::table('comments', function (Blueprint $table): void {
            $table->dropColumn('status');
        });

        Schema::table('comments', function (Blueprint $table): void {
            $table->renameColumn('status_old', 'status');
        });
    }
};
