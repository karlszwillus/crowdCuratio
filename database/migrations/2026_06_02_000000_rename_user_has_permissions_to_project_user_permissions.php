<?php

/**
crowdCuratio - Curating together virtually
Copyright (C)2026 - berlinHistory e.V.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program in the file LICENSE.

If not, see <https://www.gnu.org/licenses/>.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Block D PR 2 / D.7: Pivot-Tabelle für project-scoped Permissions
 * umbenennen — `user_has_permissions` → `project_user_permissions`.
 *
 * Die alte Bezeichnung kollidierte semantisch mit Spatie's
 * `user_has_permissions` aus dem Standard-Schema, das eine ganz
 * andere Bedeutung hat (globale Per-User-Permissions). Mit ADR-0005
 * (Permission-Modell harmonisieren) wird der projekt-bezogene
 * Pivot eindeutig benannt.
 *
 * `Schema::rename` funktioniert auf MySQL und SQLite identisch und
 * bewahrt Spalten, Indizes und Foreign Keys. Die Daten überleben
 * die Migration; ein `down()`-Lauf rollt zurück.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_has_permissions') && ! Schema::hasTable('project_user_permissions')) {
            Schema::rename('user_has_permissions', 'project_user_permissions');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('project_user_permissions') && ! Schema::hasTable('user_has_permissions')) {
            Schema::rename('project_user_permissions', 'user_has_permissions');
        }
    }
};
