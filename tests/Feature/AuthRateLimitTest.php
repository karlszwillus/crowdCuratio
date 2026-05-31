<?php

use Tests\TestCase;

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

/*
|--------------------------------------------------------------------------
| Rate-Limit auf den Guest-Auth-Routen
|--------------------------------------------------------------------------
|
| Login, Forgot-Password und Reset-Password tragen jetzt
| `throttle:6,1` (sechs Requests pro Minute pro IP) als zusätzliche
| Middleware. Konsistent mit den verification.*-Routen, die schon
| seit Breeze gedrosselt sind. Das verhindert Credential-Stuffing
| auf POST /login und Spam auf den beiden Password-Reset-Endpunkten.
|
| Tests senden je sieben Requests aus derselben fingierten Session;
| der siebte Request muss mit HTTP 429 (Too Many Requests)
| abgelehnt werden.
*/

it('drosselt POST /login nach sechs Versuchen pro Minute', function () {
    /** @var TestCase $this */
    for ($i = 0; $i < 6; $i++) {
        $this->post('/login', [
            'email' => 'noone@example.com',
            'password' => 'wrong',
        ]);
    }

    $response = $this->post('/login', [
        'email' => 'noone@example.com',
        'password' => 'wrong',
    ]);

    expect($response->status())->toBe(429);
});

it('drosselt POST /forgot-password nach sechs Versuchen pro Minute', function () {
    /** @var TestCase $this */
    for ($i = 0; $i < 6; $i++) {
        $this->post('/forgot-password', [
            'email' => 'noone@example.com',
        ]);
    }

    $response = $this->post('/forgot-password', [
        'email' => 'noone@example.com',
    ]);

    expect($response->status())->toBe(429);
});

it('drosselt POST /reset-password nach sechs Versuchen pro Minute', function () {
    /** @var TestCase $this */
    for ($i = 0; $i < 6; $i++) {
        $this->post('/reset-password', [
            'token' => 'irrelevant',
            'email' => 'noone@example.com',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);
    }

    $response = $this->post('/reset-password', [
        'token' => 'irrelevant',
        'email' => 'noone@example.com',
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ]);

    expect($response->status())->toBe(429);
});
