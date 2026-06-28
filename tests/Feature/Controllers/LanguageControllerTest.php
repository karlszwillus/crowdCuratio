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
use Illuminate\Support\Facades\Session;

/*
|--------------------------------------------------------------------------
| LanguageController — Sprach-Switcher
|--------------------------------------------------------------------------
|
| Pinning-Tests für /lang/{lang}. Setzt `applocale` in die Session,
| wenn der Schlüssel in `config/languages.php` existiert; ignoriert
| unbekannte Werte. Redirect immer zurück, unabhängig vom Ausgang.
*/

it('switcht in eine konfigurierte Sprache und schreibt sie in die Session', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from('/projects')
        ->get(route('lang.switch', 'en'));

    $response->assertRedirect('/projects');
    expect(Session::get('applocale'))->toBe('en');
});

it('ignoriert eine nicht konfigurierte Sprache, ohne die Session zu schreiben', function () {
    $user = User::factory()->create();
    Session::put('applocale', 'de');

    $response = $this->actingAs($user)
        ->from('/projects')
        ->get(route('lang.switch', 'xx'));

    $response->assertRedirect('/projects');
    expect(Session::get('applocale'))->toBe('de');
});
