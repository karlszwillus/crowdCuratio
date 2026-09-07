<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2022, 2026 - berlinHistory e.V.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Q4-Etappe 3 / C0b (2026-09-07): Zwei Projekt-Settings fuer die
     * Zitier-Semantik.
     *
     *  - `citation_depth`: `simple` = nur `name` an Quellen pflegen;
     *    `full` = zusaetzlich `kind`, `title`, `holding`, `signature`
     *    im Detail-Editor sichtbar.
     *  - `source_required`: wenn `false`, sind Copyright/Origin an
     *    Content-Bloecken optional (kein Sternchen, kein Validator-
     *    Fehler beim Save).
     *
     * Karl-Entscheidung 2026-09-07: `simple` als Default. Die
     * meisten Projekte brauchen keine strenge Zitier-Tiefe;
     * Ausstellungen mit strengem Zitier-Anspruch schalten explizit
     * auf `full`. `source_required` bleibt bei `true` — Copyright
     * und Herkunft sind weiterhin Pflicht, ausser der Kunde
     * schaltet es explizit ab.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('citation_depth', 16)
                ->default('simple')
                ->after('status');
            $table->boolean('source_required')
                ->default(true)
                ->after('citation_depth');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['citation_depth', 'source_required']);
        });
    }
};
