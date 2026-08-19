<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 *
 * Phase 5ac.5 (Profil § 5): Benachrichtigungs-Praeferenzen pro User.
 * Vier Zeilen, Kanal E-Mail. Einladungen sind bewusst nicht abschaltbar
 * — der Toggle rendert readonly-off im Frontend, hier steht keine Zeile
 * dafuer.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Toggles (Default: alle an ausser 'weekly_digest').
            $table->boolean('notify_comments')->default(true);
            $table->boolean('notify_publish')->default(true);
            $table->boolean('notify_weekly_digest')->default(false);
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
