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

use App\Models\PrivacyPolicy;
use App\Models\TermsConditions;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| PublicController — Policy + Terms als JSON
|--------------------------------------------------------------------------
|
| Liefert die aktive Privacy-Policy bzw. Terms-Conditions als JSON-Body
| (für Pre-Auth-Pages im Reader/Editor). „Aktiv" via `where('active', 1)`,
| es darf nur einen aktiven Eintrag geben.
*/

beforeEach(function () {
    app()->setLocale('de');
});

it('liefert die aktive Privacy-Policy als JSON', function () {
    PrivacyPolicy::create([
        'privacy_policy' => ['de' => 'Aktive Datenschutz-Erklärung'],
        'active' => 1,
    ]);

    /** @var TestCase $this */
    $response = $this->get(route('auth.policy'));

    $response->assertOk();
    expect($response->json())->toBe('Aktive Datenschutz-Erklärung');
});

it('ignoriert inaktive Privacy-Einträge', function () {
    PrivacyPolicy::create([
        'privacy_policy' => ['de' => 'Alter inaktiver Eintrag'],
        'active' => 0,
    ]);
    PrivacyPolicy::create([
        'privacy_policy' => ['de' => 'Aktueller Eintrag'],
        'active' => 1,
    ]);

    /** @var TestCase $this */
    $response = $this->get(route('auth.policy'));

    $response->assertOk();
    expect($response->json())->toBe('Aktueller Eintrag');
});

it('liefert die aktiven Terms-Conditions als JSON', function () {
    TermsConditions::create([
        'terms_conditions' => ['de' => 'Aktive AGB'],
        'active' => 1,
    ]);

    /** @var TestCase $this */
    $response = $this->get(route('auth.terms'));

    $response->assertOk();
    expect($response->json())->toBe('Aktive AGB');
});

it('ignoriert inaktive Terms-Einträge', function () {
    TermsConditions::create([
        'terms_conditions' => ['de' => 'Alte AGB'],
        'active' => 0,
    ]);
    TermsConditions::create([
        'terms_conditions' => ['de' => 'Aktuelle AGB'],
        'active' => 1,
    ]);

    /** @var TestCase $this */
    $response = $this->get(route('auth.terms'));

    $response->assertOk();
    expect($response->json())->toBe('Aktuelle AGB');
});
