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

use App\Support\RoleName;

/*
|--------------------------------------------------------------------------
| RoleName-Enum
|--------------------------------------------------------------------------
|
| Block E / Welle E.1 — Typ-sichere Konstanten für die vier Rollen,
| die in der App fest verankert sind (Admin, Reader, Editor,
| Reviewer). Analog zur PermissionName-Enum.
*/

it('hat die vier kanonischen Rollen als Cases', function () {
    $values = array_map(fn (RoleName $case) => $case->value, RoleName::cases());

    expect($values)->toBe(['Admin', 'Reader', 'Editor', 'Reviewer']);
});

it('all() liefert die Rollen als String-Array', function () {
    expect(RoleName::all())->toBe(['Admin', 'Reader', 'Editor', 'Reviewer']);
});

it('value entspricht dem Spatie-Rollen-Namen exakt — Case-Sensitivity wichtig', function () {
    // Spatie sucht Rollen per `name`-Strict-Match. Wenn wir den
    // Enum-Wert auf lowercase oder anders verändern, brechen die
    // Bestandsdaten. Dieser Test fixiert die exakte Schreibweise.
    expect(RoleName::ADMIN->value)->toBe('Admin');
    expect(RoleName::READER->value)->toBe('Reader');
    expect(RoleName::EDITOR->value)->toBe('Editor');
    expect(RoleName::REVIEWER->value)->toBe('Reviewer');
});
