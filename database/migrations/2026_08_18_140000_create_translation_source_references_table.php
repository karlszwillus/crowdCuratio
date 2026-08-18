<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 *
 * Phase 5ab.5 (Design v6 § 4): Sync-Marker fuer „Original nach der
 * Uebersetzung geaendert". Pro (Subject, Feld, Locale) merkt sich die
 * Zeile, auf welcher Fassung des Originals die aktuelle Uebersetzung
 * beruht. Ist die aktuelle Original-Fassung neuer, ist die Uebersetzung
 * veraltet — der Warn-Chip in translate/index nutzt diesen Zustand.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translation_source_references', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->string('field', 64);
            $table->string('locale', 8);
            $table->foreignId('source_revision_id')
                ->constrained('revisions')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['subject_type', 'subject_id', 'field', 'locale'], 'tsr_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_source_references');
    }
};
