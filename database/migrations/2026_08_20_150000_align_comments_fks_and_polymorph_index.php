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
use Illuminate\Support\Facades\Schema;

/**
 * Q3-Politur G5 (2026-08-20) — DB-Baseline nachziehen.
 *
 * Zwei Themen aus dem DB-Review (DB-01 / F-DB-009 und DB-02 / F-DB-010):
 *
 *  1. Polymorph-Lookups auf `comments` brauchen einen zusammengesetzten
 *     Index auf `(commentable_type, commentable_id)`. Ohne den scannt jede
 *     Query mit `WHERE commentable_type = ? AND commentable_id = ?` die
 *     ganze Tabelle. Bei ~10k Zeilen heute unauffaellig, ab ~100k wird
 *     das der Standard-Bottleneck der Kommentar-Sicht.
 *
 *  2. Die FK-artigen Spalten (`user_id`, `parent_id`, `commentable_id`)
 *     sind noch `INT UNSIGNED` aus dem 2021er-Schema — die referenzierten
 *     Spalten (`users.id`, `comments.id`) sind laengst `BIGINT UNSIGNED`.
 *     Wir ziehen den Typ nach UND setzen im gleichen Schritt echte
 *     FOREIGN KEY-Constraints auf `user_id → users.id` und
 *     `parent_id → comments.id`. `commentable_id` bleibt ohne FK,
 *     weil polymorph.
 *
 *     Semantik: `parent_id` ist nullable und wird bei Delete des
 *     Parent-Comments auf NULL gesetzt (Thread-Kinder ueberleben
 *     als Top-Level). `user_id` ist NOT NULL und RESTRICT — User-
 *     Hard-Delete wuerde scheitern, solange Comments existieren.
 *     Beide Modelle nutzen SoftDeletes, das reale FK-Feuer ist
 *     also selten.
 *
 * Prod-Kontext (Karl, 2026-08-20): comments < 10k Zeilen, Staging-DB
 * ~500 Zeilen, Downtime-Fenster verfuegbar. Kein Locking-Risiko.
 *
 * SQLite (CI, in-memory): `->change()` ist ein No-op fuer INTEGER →
 * BIGINT auf SQLite, weil SQLite kein starkes Typing kennt. Der Index
 * wird trotzdem angelegt. Migration laeuft auf beiden Backends sauber.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Typ-Anpassung der FK-Spalten. Auf MySQL wird ein ALTER
        //    TABLE ausgeloest — bei ~10k Zeilen unterhalb einer Sekunde.
        Schema::table('comments', function (Blueprint $table): void {
            $table->bigInteger('user_id')->unsigned()->change();
            $table->bigInteger('parent_id')->unsigned()->nullable()->change();
            $table->bigInteger('commentable_id')->unsigned()->change();
        });

        // 2. Polymorph-Index. Explizit benannt, damit der down()-Pfad
        //    ihn eindeutig droppen kann — die Laravel-Default-Namen
        //    (comments_commentable_type_commentable_id_index) sind
        //    kompatibel, aber der explizite Name reduziert Rueckfragen
        //    im DB-Debug.
        Schema::table('comments', function (Blueprint $table): void {
            $table->index(
                ['commentable_type', 'commentable_id'],
                'comments_commentable_lookup_idx'
            );
        });

        // 3. Echte FK-Constraints. SQLite (CI) kann FKs mit
        //    `->foreign()`-Syntax anlegen, aktiviert sie aber nur bei
        //    `PRAGMA foreign_keys=ON`. In Tests laeuft es transparent.
        Schema::table('comments', function (Blueprint $table): void {
            $table->foreign('user_id', 'comments_user_id_fk')
                ->references('id')->on('users');

            $table->foreign('parent_id', 'comments_parent_id_fk')
                ->references('id')->on('comments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table): void {
            $table->dropForeign('comments_parent_id_fk');
            $table->dropForeign('comments_user_id_fk');
        });

        Schema::table('comments', function (Blueprint $table): void {
            $table->dropIndex('comments_commentable_lookup_idx');
        });

        Schema::table('comments', function (Blueprint $table): void {
            $table->integer('user_id')->unsigned()->change();
            $table->integer('parent_id')->unsigned()->nullable()->change();
            $table->integer('commentable_id')->unsigned()->change();
        });
    }
};
