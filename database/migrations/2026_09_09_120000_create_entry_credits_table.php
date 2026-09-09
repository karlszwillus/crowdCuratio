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
 * Q4-Etappe 5 / G-Fund-5 (2026-09-09): Credits pro Abschnitt.
 *
 * Wer hat an einem Entry gearbeitet? Der Redakteur trägt Namen
 * mit Rolle (`recherche` / `redaktion` / `hinweis`) und optional
 * einem „Stand"-Datum ein. Speist im Reader die Credit-Zeile am
 * Eintragskopf UND das aggregierte „Erarbeitet von" am Projekt.
 *
 * Ohne diese Tabelle bleibt die redaktionelle Leistung unsichtbar
 * (Handoff v4 Blocker 2). `date` als Date-Typ statt Freitext,
 * damit MM/JJJJ konsistent formatierbar bleibt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entry_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')->constrained()->cascadeOnDelete();
            $table->string('role', 16); // recherche | redaktion | hinweis
            $table->string('name', 191);
            $table->date('date')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['entry_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entry_credits');
    }
};
