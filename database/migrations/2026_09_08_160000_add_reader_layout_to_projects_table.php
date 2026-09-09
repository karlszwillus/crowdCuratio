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
 * Q4-Etappe 5 / G1 (2026-09-08): Reader-Layout pro Projekt.
 *
 * `one-page` (Default) — Long-Scroll mit Chapter-Anchor-Navi oben,
 *                        für kleine Projekte.
 * `multi-page`         — Sidebar links, eine Seite pro Kapitel,
 *                        Deeplink pro Kapitel, für größere Projekte.
 *
 * Default `one-page` für Bestandsprojekte, damit die heutige
 * Reader-Semantik ohne Umstellung sichtbar bleibt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('reader_layout', 16)->default('one-page')->after('source_required');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('reader_layout');
        });
    }
};
