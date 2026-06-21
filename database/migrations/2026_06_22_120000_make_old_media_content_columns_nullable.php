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
 * Phase 4 / Block E.7b Sub-Welle 4d-Followup — siehe ADR-0022.
 *
 * In Welle 4d wurde die Service-Doppelschreibung in die alten
 * Pivot-Spalten (`media_content_id`, `media_contentable_id`,
 * `media_contentable_type`) beendet. Solange diese Spalten noch
 * mit `NOT NULL`-Constraint stehen, schlägt jeder Insert von einer
 * Pivot-Row mit `Integrity constraint violation: NOT NULL` fehl.
 *
 * Diese Migration entfernt den NOT-NULL-Constraint auf den drei
 * Altspalten, damit Welle-4d-Inserts laufen. Der vollständige
 * Spalten-Drop kommt in Welle 4e nach Backfill-Verifikation.
 */
return new class extends Migration
{
    public function up(): void
    {
        // doctrine/dbal ist verfügbar (Phase 2 / B-Hotfix), deshalb
        // kann `->nullable()->change()` auf bestehende Spalten
        // angewendet werden.
        Schema::table('media_content', function (Blueprint $table) {
            $table->unsignedBigInteger('media_content_id')->nullable()->change();
            $table->unsignedBigInteger('media_contentable_id')->nullable()->change();
            $table->string('media_contentable_type')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Rollback: NOT NULL wieder herstellen. Nur sicher, wenn alle
        // Rows in den drei Spalten Werte haben. Sonst wirft die
        // Migration eine Integrity-Violation — Operator muss die
        // Rows entweder backfillen oder die Migration manuell
        // zurücknehmen.
        Schema::table('media_content', function (Blueprint $table) {
            $table->unsignedBigInteger('media_content_id')->nullable(false)->change();
            $table->unsignedBigInteger('media_contentable_id')->nullable(false)->change();
            $table->string('media_contentable_type')->nullable(false)->change();
        });
    }
};
