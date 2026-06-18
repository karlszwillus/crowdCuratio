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

use App\Models\User;
use App\Services\UserReactivationService;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| UserReactivationService
|--------------------------------------------------------------------------
|
| Block E / Welle E.5. Vorher inline-Pfad in
| RegisteredUserController::store mit DB::table-Update. Service
| kapselt die zwei Operationen, die ein Reaktivierungs-Workflow
| heute hat: prüfen, ob ein User mit dieser E-Mail (soft-deleted
| oder aktiv) existiert, plus das `deleted_at`-Feld zurücksetzen.
|
| DB::table-Pfad ist absichtlich beibehalten, weil das den
| SoftDeletes-Scope umgeht — der frühere Inline-Code tat das auch
| so, und Block E ändert keine Semantik.
*/

it('existsByEmail: liefert true für aktiven User', function () {
    /** @var User $user */
    $user = User::factory()->create(['email' => 'aktiv@example.test']);

    $service = new UserReactivationService;

    expect($service->existsByEmail('aktiv@example.test'))->toBeTrue();
});

it('existsByEmail: liefert true für soft-deleted User', function () {
    /** @var User $user */
    $user = User::factory()->create([
        'email' => 'inaktiv@example.test',
        'deleted_at' => now()->subDay(),
    ]);

    $service = new UserReactivationService;

    expect($service->existsByEmail('inaktiv@example.test'))->toBeTrue();
});

it('existsByEmail: liefert false wenn kein User mit der E-Mail existiert', function () {
    $service = new UserReactivationService;

    expect($service->existsByEmail('niemand@example.test'))->toBeFalse();
});

it('reactivateByEmail: setzt deleted_at auf null', function () {
    /** @var User $user */
    $user = User::factory()->create([
        'email' => 'wieder@example.test',
        'deleted_at' => now()->subDay(),
    ]);

    $service = new UserReactivationService;
    $service->reactivateByEmail('wieder@example.test');

    $row = DB::table('users')->where('email', 'wieder@example.test')->first();
    expect($row->deleted_at)->toBeNull();
});
