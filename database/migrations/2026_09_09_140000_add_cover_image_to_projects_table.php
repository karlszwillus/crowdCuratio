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
 * Q4-Etappe 5 / E1a (2026-09-09): Leitbild pro Projekt.
 *
 * `logo` ist die kleine Marken-Kachel (26 px rechts oben in der
 * Kopfleiste). Das Leitbild ist ein separates, vollbreites Bild
 * über der Startseite (430 px hoch, überlappendes Titelpanel).
 * Feld ist nullable — ohne Leitbild entfällt das Bildfeld ganz
 * (Handoff-Regel 2: kein leerer Container).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('cover_image')->nullable()->after('logo');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('cover_image');
        });
    }
};
