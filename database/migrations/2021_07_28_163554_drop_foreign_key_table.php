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

class DropForeignKeyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * Idempotent on fresh databases: the create_texts_table /
     * create_image_table migrations in this repo do not actually
     * create an entry_id column (despite this migration's original
     * assumption), so we guard every drop with Schema::hasColumn.
     * See finding F-DB-008.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('texts', function (Blueprint $table) {
            if (Schema::hasColumn('texts', 'entry_id')) {
                $table->dropForeign(['entry_id']);
                $table->dropColumn('entry_id');
            }
            if (Schema::hasColumn('texts', 'position')) {
                $table->dropColumn('position');
            }
        });

        Schema::table('images', function (Blueprint $table) {
            if (Schema::hasColumn('images', 'entry_id')) {
                $table->dropForeign(['entry_id']);
                $table->dropColumn('entry_id');
            }
            if (Schema::hasColumn('images', 'position')) {
                $table->dropColumn('position');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * Guarded with Schema::hasColumn so that re-applying the
     * migration on a database that already has the columns (e.g. a
     * partially rolled-back state) does not blow up.
     *
     * Note: original onDelete was RESTRICT in the create migrations,
     * not CASCADE. Kept the existing CASCADE here because changing it
     * silently would alter long-standing production behavior. The
     * proper decision belongs in ADR-0012 (media_content vs. direct
     * entry binding, phase 4).
     *
     * @return void
     */
    public function down()
    {
        Schema::table('texts', function (Blueprint $table) {
            if (! Schema::hasColumn('texts', 'entry_id')) {
                $table->unsignedInteger('entry_id');
                $table->foreign('entry_id')
                    ->references('id')
                    ->on('entries')
                    ->onDelete('cascade');
            }
            if (! Schema::hasColumn('texts', 'position')) {
                $table->integer('position')->default(0)->after('copyright');
            }
        });

        Schema::table('images', function (Blueprint $table) {
            if (! Schema::hasColumn('images', 'entry_id')) {
                $table->unsignedInteger('entry_id');
                $table->foreign('entry_id')
                    ->references('id')
                    ->on('entries')
                    ->onDelete('cascade');
            }
            if (! Schema::hasColumn('images', 'position')) {
                $table->integer('position')->default(0)->after('alt');
            }
        });
    }
}
