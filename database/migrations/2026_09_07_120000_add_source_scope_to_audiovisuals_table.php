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
     * Q4-Etappe 3 / C0-8a Erweiterung (2026-09-07): Audiovisual haelt
     * `copyright` und `source` heute als einfache Text-Felder (bzw.
     * translatable JSON). Fuer die Vereinheitlichung mit Text/Image
     * ziehen wir sie auf FK-Spalten `copyright_id`/`origin_id` gegen
     * `sources` um — analog zu `texts.origin`/`texts.copyright` und
     * `images.origin`/`images.copyright`.
     *
     * Die alten Spalten (`copyright`, `source`) bleiben bewusst
     * stehen, weil sie aus dem `translatable`-JSON gelesen werden
     * und der Backfill aus ihnen speist. Ein Cleanup-Ticket kann
     * sie in einer spaeteren Welle droppen, wenn alle AV-Rows sauber
     * uebergeben sind.
     */
    public function up(): void
    {
        Schema::table('audiovisuals', function (Blueprint $table) {
            $table->foreignId('copyright_id')
                ->nullable()
                ->after('copyright')
                ->constrained('sources')
                ->nullOnDelete();

            $table->foreignId('origin_id')
                ->nullable()
                ->after('source')
                ->constrained('sources')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('audiovisuals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('copyright_id');
            $table->dropConstrainedForeignId('origin_id');
        });
    }
};
