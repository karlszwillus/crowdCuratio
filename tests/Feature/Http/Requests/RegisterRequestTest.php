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

use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Support\Facades\Validator;

/*
|--------------------------------------------------------------------------
| RegisterRequest — Conditional Roles
|--------------------------------------------------------------------------
|
| Stakeholder-Fix AM-D-3-Rest (Juni 2026): Beim Admin-Invite ist
| `roles` optional, weil der RegisteredUserController-Pfad die
| Admin-Rolle unabhängig vom Form-Input setzt. Diese Tests
| pinnen das Verhalten ein.
|
| Der Standard-Invite (`adminUser` nicht gesetzt) verlangt
| weiterhin `roles`.
*/

it('roles: bei adminUser=true optional', function () {
    $request = new RegisterRequest;
    $request->merge([
        'firstName' => 'Ada',
        'lastName' => 'Lovelace',
        'email' => 'ada@example.test',
        'policy' => '1',
        'adminUser' => true,
        // bewusst kein `roles`
    ]);

    $validator = Validator::make($request->all(), $request->rules());

    expect($validator->errors()->has('roles'))->toBeFalse();
});

it('roles: bei adminUser=false optional (Phase 5d.7 — Controller-Fallback auf Reader)', function () {
    // Vor 5d.7 war `roles` bei adminUser=false hart required — ein
    // Missing-roles-POST scheiterte in der Validation, der User
    // wurde gar nicht angelegt. Seit 5d.7 faellt der Controller auf
    // Reader-Default zurueck, damit rollenlose User nicht entstehen.
    $request = new RegisterRequest;
    $request->merge([
        'firstName' => 'Ada',
        'lastName' => 'Lovelace',
        'email' => 'ada@example.test',
        'policy' => '1',
        // adminUser absent — Rule ist trotzdem sometimes|nullable.
    ]);

    $validator = Validator::make($request->all(), $request->rules());

    expect($validator->errors()->has('roles'))->toBeFalse();
});

it('roles: ohne adminUser-Flag ist roles optional (Phase 5d.7)', function () {
    // Analog zum vorherigen Test — beide Wege enden im gleichen
    // Reader-Default-Pfad, siehe RegisteredUserController::store.
    $request = new RegisterRequest;
    $request->merge([
        'firstName' => 'Ada',
        'lastName' => 'Lovelace',
        'email' => 'ada@example.test',
        'policy' => '1',
        'adminUser' => false,
    ]);

    $validator = Validator::make($request->all(), $request->rules());

    expect($validator->errors()->has('roles'))->toBeFalse();
});
