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
 * Q4-Etappe 6 · G6-5 (2026-09-10): Kapitel-Titelbild.
 *
 * Statt eines eigenen Upload-Feldes am Kapitel (das Bilder ohne
 * Nachweis produzieren würde — der ganze Punkt dieses Projekts ist
 * aber der Nachweis) trägt jedes Bild in einer Galerie eines
 * Eintrags ein Häkchen „Als Titelbild für dieses Kapitel". Setzt
 * der Redakteur das Häkchen, füllt die Chapter-Row ihren neuen FK
 * `cover_image_id`. Urheber, Rechte und Lizenz wandern mit — die
 * Kapitelkarte trägt dann auch eine Nachweiszeile.
 *
 * ON DELETE SET NULL: wird das Bild oder sein Eintrag gelöscht,
 * verliert das Kapitel sein Titelbild automatisch. Das Fach im
 * Editor zeigt den Verlust an (Design-Briefing „Wenn es
 * verschwindet"), statt es stumm zu übernehmen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            $table->foreignId('cover_image_id')
                ->nullable()
                ->after('description')
                ->constrained('images')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cover_image_id');
        });
    }
};
