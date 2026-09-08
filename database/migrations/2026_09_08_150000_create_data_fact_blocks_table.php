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
 * Q4-Etappe 4 / G1 (2026-09-08): Daten-und-Fakten-Block als neuer
 * Content-Type. Zwei Layouts:
 *  - `steckbrief` (Default): 2-Spalten-DL Label · Wert. Für Person-,
 *    Ort- und Ereignis-Karten.
 *  - `tabelle`: N-Spalten-Tabelle mit Header pro Spalte. Für
 *    Aufzählungen wie „Judenhäuser" mit Spalten Jahr / Adresse /
 *    Ereignis.
 *
 * `title` und `subtitle` sind translatable. `columns` hält die
 * Spalten-Definitionen (nur relevant für `tabelle`), `rows` die
 * Zeilen. Die Row-Struktur unterscheidet sich je nach Layout:
 *  - steckbrief: `[{label: {de: "..."}, value: {de: "..."}}, …]`
 *  - tabelle:    `[{cells: [{de: "..."}, {de: "..."}, …]}, …]`
 * Beide werden im gleichen JSON-Feld gespeichert; der Editor
 * verzweigt anhand von `layout`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_fact_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('layout', 16)->default('steckbrief');
            $table->json('title')->nullable();
            $table->json('subtitle')->nullable();
            $table->json('columns')->nullable();
            $table->json('rows')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_fact_blocks');
    }
};
