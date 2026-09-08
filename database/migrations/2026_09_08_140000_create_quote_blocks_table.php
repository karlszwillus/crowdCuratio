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
 * Q4-Etappe 4 / F1 (2026-09-08): Zitat-Block als neuer Content-Type.
 *
 * Semantik: `text` ist der (ggf. übersetzte) Zitat-Text — translatable
 * über HasTranslations. `speaker` ist monolingual (Namen werden nicht
 * übersetzt). `date_text` als Freitext, weil Zitate teils exakte Daten
 * („12.03.1943") und teils Umschreibungen („um 1925") tragen. `kind`
 * ist nullable — der Redakteur setzt aktiv, leer heißt „keine
 * Angabe". `text_original` + `lang_original` sind optional und nur
 * gefüllt, wenn `text` eine Übersetzung ist. `source_id` verweist auf
 * eine projekt-scopede Quelle (nullable, weil beim Anlegen leer),
 * `locator` als Freitext für Fundstelle („S. 42", „12:34").
 *
 * Die Nachweiszeile wird im Reader abgeleitet und nicht in der DB
 * gespeichert.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_blocks', function (Blueprint $table) {
            $table->id();
            $table->json('text')->nullable();
            $table->string('speaker')->nullable();
            $table->string('date_text')->nullable();
            $table->string('kind', 32)->nullable();
            $table->string('lang_original', 8)->nullable();
            $table->text('text_original')->nullable();
            $table->foreignId('source_id')->nullable()->constrained('sources')->nullOnDelete();
            $table->string('locator')->nullable();
            $table->boolean('is_translated')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_blocks');
    }
};
