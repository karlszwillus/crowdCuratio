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
 * Q4-Etappe 7 · E7-1 (2026-09-11): Lizenz-Feld an der Quelle.
 *
 * Bislang landete eine Angabe wie „CC-BY-SA 3.0 by Wikimedia
 * Commons" in `name` und wurde damit gleichrangig zu Urhebern
 * geführt — im Designer-Nachreview zur Bildergalerie (Befund 06)
 * und im CMS-Review (Befund 15) sichtbar gemacht.
 *
 * `license` ist ein freier Kurztext (z. B. „CC BY-SA 4.0",
 * „Rechte am Original beim Halter", „gemeinfrei") und wird im
 * Nachweis-Menü unter einer eigenen Rubrik geführt. Nullable —
 * Bestandsrows brauchen keine Lizenz.
 *
 * Karl 2026-09-11: keine Migration von Bestandsdaten. Die
 * Test-Datenbank hat aktuell nur `name` gefüllt, alle anderen
 * Detail-Felder (kind/title/holding/signature/license) sind
 * leer und werden erst mit dem Zitier-Modus „voll" gefüllt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sources', function (Blueprint $table) {
            $table->string('license', 255)->nullable()->after('signature');
        });
    }

    public function down(): void
    {
        Schema::table('sources', function (Blueprint $table) {
            $table->dropColumn('license');
        });
    }
};
