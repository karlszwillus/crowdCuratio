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

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Block E / Welle E.5 — Reaktivierungs-Workflow aus
 * `RegisteredUserController::store` herausgezogen.
 *
 * Wenn ein User mit gleicher E-Mail bereits existiert (auch
 * soft-deleted), wird ihm `deleted_at = null` gesetzt statt einen
 * neuen Datensatz anzulegen. Vorher inline-DB::table-Update; der
 * direkte Pfad umgeht bewusst den SoftDeletes-Scope.
 */
class UserReactivationService
{
    public function existsByEmail(string $email): bool
    {
        return DB::table('users')->where('email', $email)->exists();
    }

    public function reactivateByEmail(string $email): void
    {
        DB::table('users')
            ->where('email', $email)
            ->update(['deleted_at' => null]);
    }
}
