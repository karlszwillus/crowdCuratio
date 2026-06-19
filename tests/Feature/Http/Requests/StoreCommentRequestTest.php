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

use App\Http\Requests\StoreCommentRequest;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| StoreCommentRequest
|--------------------------------------------------------------------------
|
| Block E / Welle E.6 — vorher hatten sieben Comment-Endpunkte
| jeweils ein inline `$request->validate(['comment' => 'required'])`.
| Ein einziger FormRequest deckt alle ab.
|
| authorize() = eingeloggt. Die project-scoped Autorisierung (darf
| dieser User auf diesem Modell kommentieren?) macht jeder Controller
| weiterhin via `$this->authorize('comment', $model)` — die Policy
| ist die richtige Schicht dafür, kein FormRequest.
*/

it('authorize: eingeloggter User darf', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $request = StoreCommentRequest::create('/comment/project', 'POST');
    $request->setUserResolver(fn () => $user);

    expect($request->authorize())->toBeTrue();
});

it('authorize: nicht-eingeloggter User darf NICHT', function () {
    $request = StoreCommentRequest::create('/comment/project', 'POST');
    $request->setUserResolver(fn () => null);

    expect($request->authorize())->toBeFalse();
});

it('rules: comment ist pflicht', function () {
    $request = new StoreCommentRequest;

    expect($request->rules())->toHaveKey('comment');
    expect($request->rules()['comment'])->toContain('required');
});

it('rules: id ist pflicht (Polymorphes Parent-Lookup im Controller)', function () {
    $request = new StoreCommentRequest;

    expect($request->rules())->toHaveKey('id');
    expect($request->rules()['id'])->toContain('required');
});
