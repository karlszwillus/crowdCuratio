<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 *
 * Phase 5z.9: Transkript-Feld für Audio- und Video-Blöcke. Weiche Pflicht
 * (Bestandsmedien haben keins), Publish-Check nennt fehlende Transkripte
 * namentlich — analog Bildbeschreibung in der Galerie.
 *
 * Translatable (JSON) wie link/source/copyright.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audiovisuals', function (Blueprint $table) {
            $table->text('transcript')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('audiovisuals', function (Blueprint $table) {
            $table->dropColumn('transcript');
        });
    }
};
