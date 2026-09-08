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
 * Q4-Etappe 4 / C1c (2026-09-08): `audiovisuals.link` nullable
 * machen, damit der Inline-Add-Flow einen leeren AV-Block anlegen
 * kann. Der User trägt die URL anschließend im In-Place-Editor ein.
 *
 * Nachzügler-Migration — die vorherige (make_texts_body_and_sources_
 * _nullable) war bereits eingespielt, als der AV-Fall auffiel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audiovisuals', function (Blueprint $table) {
            $table->text('link')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('audiovisuals', function (Blueprint $table) {
            $table->text('link')->nullable(false)->change();
        });
    }
};
