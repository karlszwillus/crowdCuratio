<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 *
 * Phase 5ac.1 (Profil-Redesign Screen 17A): Neue Felder auf `users`
 * fuer Avatar, Kuerzel-Fallback und die Praeferenzen (Sprache, Theme).
 * Notification-Preferences kommen in 5ac.5 als eigenes Modell.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('avatar_path', 255)->nullable()->after('email');
            // Kuerzel-Fallback: 2-3 Zeichen, ueberschreibbar, sonst leer
            // (Controller/Livewire leitet vorbelegten Wert aus Vor+Nachname
            // beim Rendern ab — kein persistiertes Default).
            $table->string('initials', 3)->nullable()->after('avatar_path');
            // Sechs Token-Werte gemaess Briefing § 2. String reicht — die
            // Whitelist steht im Livewire.
            $table->string('initials_color', 24)->nullable()->after('initials');
            // Sprach- und Theme-Praeferenz, Sofort-Wirkung ohne Save.
            $table->string('locale', 8)->default('de')->after('initials_color');
            $table->string('theme', 24)->default('default')->after('locale');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['avatar_path', 'initials', 'initials_color', 'locale', 'theme']);
        });
    }
};
