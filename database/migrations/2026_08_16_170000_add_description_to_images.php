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
 * Phase 5y.7: neue Spalte `description` an Bildern fuer den
 * Alt-/Screenreader-Text. Bisher wurde `alt` als Titel benutzt
 * (Feldbreite auf der Kachel), was auf Screen 12A doppelt belegt
 * war — der Handoff-Briefing v5 § 5 trennt jetzt sauber:
 *   - `alt` bleibt als Titel/Bildunterschrift
 *   - `description` als Bildbeschreibung fuer Screenreader
 *
 * Weiches Pflichtfeld: Bestandsbilder haben keine Beschreibung,
 * ein hartes NOT-NULL wuerde jede Bearbeitung eines Altbestands
 * blockieren. Deshalb nullable — die Warnung faellt in den
 * Kachel-Angaben-Status und die Veroeffentlichen-Pruefung.
 *
 * Speicherung translatable (Spatie), analog zu `alt`. TEXT statt
 * VARCHAR, weil Alternativtexte gerne mehrzeilig werden.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->text('description')->nullable()->after('alt');
        });
    }

    public function down(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
