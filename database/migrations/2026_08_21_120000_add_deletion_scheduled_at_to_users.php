<?php

/**
crowdCuratio - Curating together virtually
Copyright (C)2026 - berlinHistory e.V.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

See LICENSE.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * B2 (2026-08-21) — DSGVO-Baseline: Konto-Loeschung mit 30-Tage-Frist.
 *
 * `deletion_scheduled_at` markiert Konten, die zur Loeschung angemeldet
 * sind. Ab dem Zeitstempel laeuft eine 30-Tage-Frist. Login innerhalb
 * dieser Frist bietet dem Nutzer die Reaktivierung an; nach Ablauf
 * schiebt ein Cron das Konto in den SoftDeletes-Scope (das ist die
 * eigentliche Loeschung). Der spaetere Force-Delete (endgueltiges
 * Loeschen der Zeile) laeuft ueber die bestehende SoftDeletes-
 * Retention.
 *
 * `deletion_reason` ist bewusst optional — DSGVO fordert keinen Grund,
 * aber fuer interne Reflexion (haeufigste Loesch-Gruende) hilfreich.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('deletion_scheduled_at')->nullable()->after('welcome_valid_until');
            $table->string('deletion_reason', 255)->nullable()->after('deletion_scheduled_at');
            $table->index('deletion_scheduled_at', 'users_deletion_scheduled_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_deletion_scheduled_at_idx');
            $table->dropColumn(['deletion_scheduled_at', 'deletion_reason']);
        });
    }
};
