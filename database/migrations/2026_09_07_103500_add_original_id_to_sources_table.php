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
     * Q4-Etappe 3 / C0-8b (2026-09-07): Rueckverweis fuer den Migrations-
     * Assistenten. Wenn eine projektlose Alt-Source in eine neue projekt-
     * scoped Source uebergefuehrt wird, haelt `original_id` die Verbindung
     * zur alten Zeile — nachvollziehbar in 6 Monaten, wichtig fuer den
     * Rollback und fuer Audit-Trail.
     */
    public function up(): void
    {
        Schema::table('sources', function (Blueprint $table) {
            $table->foreignId('original_id')
                ->nullable()
                ->after('project_id')
                ->constrained('sources')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sources', function (Blueprint $table) {
            $table->dropConstrainedForeignId('original_id');
        });
    }
};
