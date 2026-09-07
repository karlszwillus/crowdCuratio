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
     * Q4-Etappe 3 / C0-8b (2026-09-07): Rollback-Anker fuer den
     * Migrations-Assistenten. Vor dem Commit einer Projekt-Migration
     * wird der Zustand der betroffenen Source-Zeilen 1:1 in diese
     * Tabelle kopiert. `run_key` gruppiert die Zeilen pro Command-Lauf
     * (`sources:migrate` timestamps sich selbst); `source_id` haelt
     * die Referenz auf die reale Zeile in `sources`.
     */
    public function up(): void
    {
        Schema::create('sources_backup', function (Blueprint $table) {
            $table->id();
            $table->string('run_key', 64)->index();
            $table->unsignedBigInteger('source_id')->index();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->text('name');
            $table->string('title', 255)->nullable();
            $table->string('holding', 255)->nullable();
            $table->string('signature', 255)->nullable();
            $table->text('type');
            $table->string('kind', 32)->nullable();
            $table->boolean('is_translated')->default(false);
            $table->timestamp('backup_run_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sources_backup');
    }
};
