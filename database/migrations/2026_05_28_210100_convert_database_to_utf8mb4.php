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
 * Konvertiert alle Anwendungs-Tabellen auf utf8mb4 / utf8mb4_unicode_ci.
 * Siehe ADR-0011 und Finding F-DB-002.
 *
 * Idempotent: jede Tabelle wird einzeln gegen
 * information_schema.tables geprüft und nur konvertiert, wenn sie
 * nicht schon utf8mb4 fährt.
 *
 * Wichtig: läuft NICHT auf SQLite (Tests). Wenn der DB-Driver kein
 * MySQL ist, ist die Migration ein No-Op.
 */
class ConvertDatabaseToUtf8mb4 extends Migration
{
    private string $targetCharset = 'utf8mb4';
    private string $targetCollation = 'utf8mb4_unicode_ci';

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        // 1) Datenbank-Default-Charset/Collation umstellen.
        $database = DB::connection()->getDatabaseName();
        DB::statement(
            "ALTER DATABASE `{$database}` "
            . "CHARACTER SET {$this->targetCharset} "
            . "COLLATE {$this->targetCollation}"
        );

        // 2) Jede Tabelle einzeln, falls sie noch nicht utf8mb4 fährt.
        $tables = DB::select(
            'SELECT table_name AS name FROM information_schema.tables
              WHERE table_schema = ? AND table_type = ?',
            [$database, 'BASE TABLE']
        );

        foreach ($tables as $row) {
            $table = $row->name;
            $needsConversion = $this->tableNeedsConversion($database, $table);

            if (! $needsConversion) {
                continue;
            }

            DB::statement(
                "ALTER TABLE `{$table}` "
                . "CONVERT TO CHARACTER SET {$this->targetCharset} "
                . "COLLATE {$this->targetCollation}"
            );
        }
    }

    public function down(): void
    {
        // Bewusst keine echte Rück-Migration auf utf8mb3. Würde aktiv
        // Datenverlust erzeugen (gespeicherte 4-Byte-Sequenzen werden
        // gestripped). Wer wirklich zurück will, baut sich seine
        // eigene Migration und akzeptiert den Verlust.
    }

    /**
     * Prüft, ob mindestens eine Spalte oder die Tabelle selbst noch
     * nicht im Ziel-Charset liegt.
     */
    private function tableNeedsConversion(string $database, string $table): bool
    {
        $row = DB::selectOne(
            'SELECT ccsa.character_set_name AS charset
               FROM information_schema.tables t
               JOIN information_schema.collation_character_set_applicability ccsa
                 ON ccsa.collation_name = t.table_collation
              WHERE t.table_schema = ?
                AND t.table_name = ?',
            [$database, $table]
        );

        if ($row && $row->charset !== $this->targetCharset) {
            return true;
        }

        // Auch wenn die Tabelle als utf8mb4 markiert ist, kann eine
        // einzelne Spalte noch utf8mb3 sein. Lieber konvertieren als
        // false-negative riskieren.
        $columnRow = DB::selectOne(
            'SELECT COUNT(*) AS cnt
               FROM information_schema.columns
              WHERE table_schema = ?
                AND table_name = ?
                AND character_set_name IS NOT NULL
                AND character_set_name <> ?',
            [$database, $table, $this->targetCharset]
        );

        return ((int) ($columnRow->cnt ?? 0)) > 0;
    }
}
