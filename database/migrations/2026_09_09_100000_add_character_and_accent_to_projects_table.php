<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

See LICENSE.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Q4-Etappe 5 / G-Fund-1 (2026-09-09): Reader-Charakter und
 * optionale Akzent-Farbe pro Projekt.
 *
 * `character` = `dokumentation` (Default, Handoff-Empfehlung),
 *               `archiv`, `erzaehlung`. Setzt die neun Reader-
 *               Tokens (Papier, Tinte, Rule, Akzent) und die
 *               Lesefamilie.
 * `accent_color` = optionale Hex-Farbe, überschreibt den
 *                  Charakter-Primärton (`--accent`). Wenn null,
 *                  gilt der Charakter-Default. Kunden-Wunsch:
 *                  Charakter fest UND optionale Akzentwahl.
 *
 * Löst die alte `?colorAccent`-Query-Param-Kette ab — der Reader
 * liest ab jetzt direkt vom Projekt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('character', 16)->default('dokumentation')->after('reader_layout');
            $table->string('accent_color', 9)->nullable()->after('character');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['character', 'accent_color']);
        });
    }
};
