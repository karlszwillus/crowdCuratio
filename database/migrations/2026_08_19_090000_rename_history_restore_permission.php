<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 *
 * Phase 5ab.5-Followup: Umbenennung der neuen Permission auf Bindestrich-
 * Konvention. Der Punkt in 'history.restore' bricht Laravels
 * Str::camel()-Aufloesung — die Policy-Methode `historyRestore` wird
 * dann nie gefunden, `@can` gibt false zurueck. Bindestrich klappt.
 *
 * Idempotent: wenn die alte Zeile nicht existiert (frischer Seeder),
 * passiert nichts. Wenn sie existiert, wird sie umbenannt; falls die
 * neue schon exisitiert (doppelte Zuweisung), wird die alte geloescht.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $oldExists = DB::table('permissions')->where('name', 'history.restore')->exists();
        $newExists = DB::table('permissions')->where('name', 'history-restore')->exists();

        if (! $oldExists) {
            return;
        }

        if ($newExists) {
            // Beide Zeilen — Rollen und Pivot-Zuweisungen von alt auf neu
            // uebertragen und die alte Zeile loeschen.
            $old = DB::table('permissions')->where('name', 'history.restore')->first();
            $new = DB::table('permissions')->where('name', 'history-restore')->first();

            DB::table('role_has_permissions')
                ->where('permission_id', $old->id)
                ->update(['permission_id' => $new->id]);
            DB::table('model_has_permissions')
                ->where('permission_id', $old->id)
                ->update(['permission_id' => $new->id]);

            DB::table('permissions')->where('id', $old->id)->delete();
        } else {
            DB::table('permissions')
                ->where('name', 'history.restore')
                ->update(['name' => 'history-restore']);
        }
    }

    public function down(): void
    {
        // Kein Down — die Punkt-Version war kaputt.
    }
};
