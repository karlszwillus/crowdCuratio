<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 *
 * Phase 5y.9: Beim optimistischen Drop-Upload wird das Bild ohne
 * Copyright und Quelle angelegt — die Angaben fügt Nutzer:in in der
 * Detailzeile nach. Damit das ohne Umweg über eine leere Placeholder-
 * Source klappt, gehen `origin` und `copyright` in `images` auf
 * nullable. Das existierende Angaben-Status-Chip pro Kachel zeigt die
 * Lücken sofort mit dem etablierten „⚠ Urheberrecht/Quelle fehlt".
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->unsignedBigInteger('origin')->nullable()->change();
            $table->unsignedBigInteger('copyright')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->unsignedBigInteger('origin')->nullable(false)->change();
            $table->unsignedBigInteger('copyright')->nullable(false)->change();
        });
    }
};
