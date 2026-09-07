<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2022, 2026 - berlinHistory e.V.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Q4-Etappe 3 / C0-8a (2026-09-07): Erste Etappe der Quellen-
     * Umstellung auf projektweite Verzeichnisse.
     *
     * Vier neue Felder — alle nullable, damit Bestandsrows nicht
     * brechen:
     *  - `project_id`   FK auf `projects`. Wird von neuen Anlagen
     *                   sofort gesetzt; der Migrations-Assistent
     *                   (8b) zieht Alt-Rows pro Projekt nach. Erst
     *                   nach 8b wird `NOT NULL` erzwungen.
     *  - `kind`         `archivalie` / `publikation` / `interview` /
     *                   `abbildung` / `sonstige`. Verzeichnis-
     *                   Klassifikation (Briefing § 1.1).
     *  - `holding`      Archiv / Institution / Verlag.
     *  - `signature`    Signatur / Bestellnummer.
     *  - `title`        Kurztitel.
     *
     * Der bisherige `type`-Wert (`Copyright` / `Origin`) beschreibt
     * die *Rolle* am Content-Block, nicht die Art der Quelle —
     * beide Achsen leben ab jetzt getrennt. `type` bleibt als
     * Legacy-Spalte bis zur naechsten Umstellung (Zitat-Block-
     * Roll-out).
     */
    public function up(): void
    {
        Schema::table('sources', function (Blueprint $table) {
            $table->foreignId('project_id')
                ->nullable()
                ->after('id')
                ->constrained('projects')
                ->nullOnDelete();

            // MySQL: enum-artig; SQLite (Testpfad) ignoriert die
            // enum-Definition und legt VARCHAR an — beides fein.
            $table->string('kind', 32)
                ->nullable()
                ->after('type');

            $table->string('title', 255)->nullable()->after('name');
            $table->string('holding', 255)->nullable()->after('title');
            $table->string('signature', 255)->nullable()->after('holding');
        });
    }

    public function down(): void
    {
        Schema::table('sources', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_id');
            $table->dropColumn(['kind', 'holding', 'signature', 'title']);
        });
    }
};
