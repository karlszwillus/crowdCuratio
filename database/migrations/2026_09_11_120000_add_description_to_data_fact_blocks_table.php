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
 * Q4-Etappe 7 · E7-3-Followup (2026-09-11): Optionale Einleitung
 * für den Daten-und-Fakten-Block. Analog zu Entry/Gallery: nullable,
 * translatable, Rich-Text — erscheint im Reader zwischen Untertitel
 * und Fakten-Rendering.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_fact_blocks', function (Blueprint $table) {
            $table->json('description')->nullable()->after('subtitle');
        });
    }

    public function down(): void
    {
        Schema::table('data_fact_blocks', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
