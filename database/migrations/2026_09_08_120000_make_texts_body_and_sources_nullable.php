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
 * Q4-Etappe 4 / C1c (2026-09-08): `texts.text`, `texts.origin` und
 * `texts.copyright` werden nullable, damit der Inline-Add-Flow einen
 * leeren Text-Block anlegen kann. Der User füllt Body und Quellen
 * anschließend im In-Place-Editor. Vollständigkeit prüfen weiterhin
 * die UI-Angaben-Status-Chips und (bei `source_required=true`) der
 * Publish-Gate — nicht mehr die DB.
 *
 * Galerie- und AV-Blöcke sind bereits nullable-freundlich; nur die
 * Texts-Tabelle war historisch strikt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('texts', function (Blueprint $table) {
            $table->text('text')->nullable()->change();
            $table->unsignedBigInteger('origin')->nullable()->change();
            $table->unsignedBigInteger('copyright')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('texts', function (Blueprint $table) {
            $table->text('text')->nullable(false)->change();
            $table->unsignedBigInteger('origin')->nullable(false)->change();
            $table->unsignedBigInteger('copyright')->nullable(false)->change();
        });
    }
};
