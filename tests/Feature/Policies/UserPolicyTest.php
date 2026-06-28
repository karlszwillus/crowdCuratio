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
use App\Policies\UserPolicy;
use App\Support\RoleName;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| UserPolicy
|--------------------------------------------------------------------------
|
| Two Pfade:
|   - Admins dürfen alles (before() greift)
|   - Sonst nur Self-Edit (update() vergleicht IDs)
|
| Andere Abilities ohne explizite Method fallen für Nicht-Admins durch
| (Laravel-Default: deny).
*/

beforeEach(function () {
    Role::firstOrCreate(['name' => RoleName::ADMIN->value, 'guard_name' => 'web']);
});

it('lässt einen Admin via before() jede Ability passieren', function () {
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole(RoleName::ADMIN->value);

    /** @var User $target */
    $target = User::factory()->create();
    $policy = new UserPolicy;

    expect($policy->before($admin, 'update'))->toBeTrue();
    expect($policy->before($admin, 'irgendeine-andere-ability'))->toBeTrue();
});

it('returns null via before() für Nicht-Admins (fällt auf method-Auflösung)', function () {
    /** @var User $user */
    $user = User::factory()->create();
    $policy = new UserPolicy;

    expect($policy->before($user, 'update'))->toBeNull();
});

it('erlaubt Self-Edit für Nicht-Admins', function () {
    /** @var User $user */
    $user = User::factory()->create();
    $policy = new UserPolicy;

    expect($policy->update($user, $user))->toBeTrue();
});

it('verbietet Fremd-Edit für Nicht-Admins', function () {
    /** @var User $user */
    $user = User::factory()->create();
    /** @var User $target */
    $target = User::factory()->create();
    $policy = new UserPolicy;

    expect($policy->update($user, $target))->toBeFalse();
});

it('lässt Admins via voller Gate-Auflösung passieren — auch beim Fremd-Edit', function () {
    /** @var TestCase $this */
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole(RoleName::ADMIN->value);

    /** @var User $target */
    $target = User::factory()->create();

    // Volle Gate-Auflösung über $this->can() — schließt before() ein.
    $this->actingAs($admin);
    expect($admin->can('update', $target))->toBeTrue();
});
