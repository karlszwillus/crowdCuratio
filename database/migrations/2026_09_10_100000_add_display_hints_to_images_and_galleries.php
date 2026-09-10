<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

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
 * Q4-Etappe 6 · G6-1 (2026-09-10): Darstellungs-Hinweise am Bild und
 * am Galerie-Block. Fünf Felder am Image, ein Feld an der Gallery:
 *
 *   images.no_crop            — Dokument/Scan/Karte: nie in eine
 *                                Zelle beschneiden, immer einpassen.
 *   images.focus_x / focus_y  — Beschnitt-Fokus in Prozent 0-100.
 *                                Null = Mitte (Standard-Verhalten).
 *   images.intrinsic_width /
 *   images.intrinsic_height   — Original-Dimensionen in Pixel für
 *                                Layout-Reservierung (kein CLS).
 *   galleries.sequence        — Redaktioneller Schalter: Serie/
 *                                Vorher-Nachher als Pager-Bühne
 *                                statt Bogen zeigen. Nur eine
 *                                Richtung — ist gesetzt oder nicht.
 *
 * Bestandsdaten: no_crop = false, focus_* = null, intrinsic_* = null,
 * sequence = false. Der Reader-Renderer greift zurück auf sein
 * bestehendes Default-Verhalten, wenn intrinsic_* leer sind.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->boolean('no_crop')->default(false)->after('image');
            $table->smallInteger('focus_x')->unsigned()->nullable()->after('no_crop');
            $table->smallInteger('focus_y')->unsigned()->nullable()->after('focus_x');
            $table->integer('intrinsic_width')->unsigned()->nullable()->after('focus_y');
            $table->integer('intrinsic_height')->unsigned()->nullable()->after('intrinsic_width');
        });

        Schema::table('galleries', function (Blueprint $table) {
            $table->boolean('sequence')->default(false)->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('galleries', function (Blueprint $table) {
            $table->dropColumn('sequence');
        });

        Schema::table('images', function (Blueprint $table) {
            $table->dropColumn([
                'no_crop',
                'focus_x',
                'focus_y',
                'intrinsic_width',
                'intrinsic_height',
            ]);
        });
    }
};
