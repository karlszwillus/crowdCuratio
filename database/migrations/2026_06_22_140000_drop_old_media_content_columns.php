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
 * Phase 4 / Block E.7b Sub-Welle 4e — siehe ADR-0022.
 *
 * Spalten-Drop. Nach Welle 4b (Controllers), 4c (Models), 4d
 * (Services) liest und schreibt nichts mehr in die alten
 * media_contentable-/media_content_id-Spalten. Welle 4e-prep
 * (`db:migrate-media-content --apply`) ist als Safety-Net auf
 * Live-Daten gelaufen.
 *
 * WICHTIG VOR LIVE-DEPLOY:
 *   1. Backup der media_content-Tabelle (Spaltenwerte gehen verloren).
 *   2. `php artisan db:migrate-media-content` (Dry-run) — Bericht prüfen.
 *   3. `php artisan db:migrate-media-content --apply` — Drift backfillen.
 *   4. Diese Migration laufen lassen.
 */
return new class extends Migration
{
    public function up(): void
    {
        // SQLite schmeißt 'no such column in index'-Fehler, wenn der
        // dropColumn die Spalte unter einem bestehenden Index wegzieht.
        // Indizes laut create-Migration (2021_07_28_162259):
        //   media_content_media_content_id_index
        //   media_content_media_contentable_id_index
        //   media_content_media_contentable_type_index
        Schema::table('media_content', function (Blueprint $table) {
            $table->dropIndex('media_content_media_content_id_index');
            $table->dropIndex('media_content_media_contentable_id_index');
            $table->dropIndex('media_content_media_contentable_type_index');
        });

        Schema::table('media_content', function (Blueprint $table) {
            $table->dropColumn([
                'media_content_id',
                'media_contentable_id',
                'media_contentable_type',
            ]);
        });
    }

    public function down(): void
    {
        // Rollback. Spalten werden ohne NOT-NULL-Constraint
        // wiederhergestellt — die Daten sind weg, ein vollständiger
        // Rollback würde den Restore aus dem Pre-Drop-Backup brauchen.
        // Indizes parallel wieder herstellen (analog up()).
        Schema::table('media_content', function (Blueprint $table) {
            $table->unsignedBigInteger('media_content_id')->nullable();
            $table->unsignedBigInteger('media_contentable_id')->nullable();
            $table->string('media_contentable_type')->nullable();
        });

        Schema::table('media_content', function (Blueprint $table) {
            $table->index('media_content_id');
            $table->index('media_contentable_id');
            $table->index('media_contentable_type');
        });
    }
};
