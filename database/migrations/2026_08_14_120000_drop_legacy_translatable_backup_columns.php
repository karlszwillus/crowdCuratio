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
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Legacy-Backup-Spalten der Translatable-Umstellung entfernen.
 *
 * Vor der Umstellung auf spatie/laravel-translatable waren die
 * mehrsprachigen Felder VARCHAR-/TEXT-Spalten (`name`, `subtitle`, …).
 * Der Migrations-Pfad damals hat die Alt-Werte in `*_old`-Spalten
 * gesichert, bevor die eigentliche Spalte auf JSON umgestellt wurde.
 * Frische Setups (Docker/Sail) laufen direkt in die neue Struktur
 * und haben die `_old`-Spalten nie angelegt; lang lebende Datenbanken
 * (Staging, Produktion mit alten Migrations) tragen die Backup-
 * Spalten aber weiter mit.
 *
 * Unter Strict-Mode-MySQL werden diese NOT-NULL-Spalten ohne Default
 * beim Insert zum Blocker: `SQLSTATE[HY000]: 1364 Field 'name_old'
 * doesn't have a default value` — sichtbar geworden beim ersten
 * Staging-Deploy nach Phase 5.
 *
 * Diese Migration ist idempotent: sie prüft pro Spalte, ob sie
 * existiert, und dropped sie nur dann. Lokal ist das ein No-Op.
 * Rollback ist bewusst leer, weil die Backup-Daten irrelevant sind.
 */
return new class extends Migration
{
    /**
     * @var array<string, array<int, string>>
     */
    private array $backupColumnsPerTable = [
        'chapters' => ['name_old', 'subtitle_old', 'description_old'],
        'entries' => ['name_old', 'subtitle_old', 'description_old'],
        'projects' => ['name_old', 'imprint_old', 'terms_old', 'description_old'],
        'galleries' => ['title_old', 'subtitle_old', 'description_old'],
        'audiovisuals' => ['link_old', 'source_old', 'copyright_old'],
        'texts' => ['text_old'],
        'images' => ['alt_old'],
        'sources' => ['name_old'],
        'comments' => ['comment_old'],
        'privacy_policies' => ['privacy_policy_old'],
        'terms_conditions' => ['terms_conditions_old'],
        'mail_settings' => ['invitation_old'],
        'permission_descriptions' => ['description_old'],
    ];

    public function up(): void
    {
        foreach ($this->backupColumnsPerTable as $tableName => $columns) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName, $columns) {
                foreach ($columns as $columnName) {
                    if (Schema::hasColumn($tableName, $columnName)) {
                        $table->dropColumn($columnName);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        // Bewusst leer. Die Backup-Spalten enthielten Werte aus dem
        // Vor-Translatable-Zustand, die durch die Umstellung auf JSON
        // längst irrelevant sind. Ein Rollback würde die Spalten neu
        // anlegen, aber ohne die alten Werte, und wäre damit keine
        // echte Wiederherstellung.
    }
};
