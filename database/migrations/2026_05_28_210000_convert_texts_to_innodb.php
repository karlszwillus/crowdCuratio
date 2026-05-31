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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Konvertiert die `texts`-Tabelle von MyISAM auf InnoDB und setzt
 * die FK-Constraints auf `sources` aktiv. Siehe ADR-0010 und
 * Finding F-DB-001.
 *
 * Idempotent: prüft die aktuelle Engine vor dem Rebuild.
 */
class ConvertTextsToInnodb extends Migration
{
    public function up(): void
    {
        // information_schema und MyISAM/InnoDB sind MySQL-Konzepte.
        // Auf SQLite (CI-Test-Pfad) ist die Migration ein No-Op —
        // siehe NF-DB-103 im Phase-1-Review.
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $currentEngine = $this->getEngine('texts');

        if ($currentEngine === null) {
            // Tabelle nicht da — Setup ist noch nicht gelaufen, dann ist
            // hier auch nichts zu tun. Spätere create_texts_table-
            // Migration kann ohnehin direkt InnoDB nehmen, sobald sie
            // sauber überarbeitet ist.
            return;
        }

        if (strtoupper($currentEngine) !== 'INNODB') {
            DB::statement('ALTER TABLE `texts` ENGINE = InnoDB');
        }

        // FK-Constraints unter MyISAM wurden vom DBMS still verworfen.
        // Nach der Engine-Konvertierung müssen wir sie explizit setzen.
        // Vorher die alten Constraint-Namen (falls noch da) wegräumen,
        // damit ein erneuter Lauf nicht stolpert.
        $this->dropForeignKeyIfExists('texts', 'texts_origin_foreign');
        $this->dropForeignKeyIfExists('texts', 'texts_copyright_foreign');

        Schema::table('texts', function (Blueprint $table): void {
            $table->foreign('origin', 'texts_origin_foreign')
                ->references('id')->on('sources')
                ->onUpdate('restrict')
                ->onDelete('restrict');
            $table->foreign('copyright', 'texts_copyright_foreign')
                ->references('id')->on('sources')
                ->onUpdate('restrict')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        // NF-DB-104 / Phase 2 / E.6: bewusst keine echte Rück-Migration
        // auf MyISAM — die ist destruktiv (FKs würden still verworfen,
        // siehe ADR-0010). Ein No-Op-down() würde aber den Eindruck
        // erwecken, der Roll-back habe funktioniert. RuntimeException
        // ist die ehrlichste Antwort: wer wirklich zurück will, muss
        // einen eigenen Schritt schreiben und das Risiko bewusst tragen.
        throw new RuntimeException(
            'Rollback to MyISAM is not supported — siehe ADR-0010 '
            .'für die Begründung.'
        );
    }

    private function getEngine(string $table): ?string
    {
        $database = DB::connection()->getDatabaseName();
        $row = DB::selectOne(
            'SELECT ENGINE FROM information_schema.tables
              WHERE table_schema = ? AND table_name = ?',
            [$database, $table]
        );

        return $row?->ENGINE;
    }

    private function dropForeignKeyIfExists(string $table, string $constraint): void
    {
        $database = DB::connection()->getDatabaseName();
        $exists = DB::selectOne(
            'SELECT 1 FROM information_schema.table_constraints
              WHERE constraint_schema = ?
                AND table_name = ?
                AND constraint_name = ?
                AND constraint_type = ?',
            [$database, $table, $constraint, 'FOREIGN KEY']
        );

        if ($exists) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
        }
    }
}
