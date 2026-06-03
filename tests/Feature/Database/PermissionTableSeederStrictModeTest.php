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

use App\Models\PermissionDescription;
use App\Support\PermissionName;
use Database\Seeders\PermissionTableSeeder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| PermissionTableSeeder unter Strict-Mode
|--------------------------------------------------------------------------
|
| Block E / Welle E.2. Vor diesem Test war der Seeder eine latente
| Mass-Assignment-Bombe: PermissionDescription::$fillable enthält
| nur `['description']`, der Seeder schickte aber `permission_id`
| und `position` durch updateOrCreate-Arrays an `fill()`. In
| Production lief das still durch, weil `Model::shouldBeStrict()`
| dort aus ist (`!isProduction()`); in Dev/CI würde es brechen.
|
| Dieser Test aktiviert Strict-Mode explizit und ruft den Seeder
| direkt — fixiert das geschlossene Verhalten nach Property-Setter-
| Umstellung.
*/

it('PermissionTableSeeder läuft unter strict-Mode ohne MassAssignmentException', function () {
    /** @var TestCase $this */
    Model::shouldBeStrict(true);

    try {
        (new PermissionTableSeeder)->run();
    } finally {
        // Strict-Mode für nachfolgende Tests zurücksetzen — der
        // AppServiceProvider setzt das per !isProduction() default,
        // aber wir lassen die Kontrolle nicht offen.
        Model::shouldBeStrict(false);
    }

    // Sieben Permissions plus sieben Descriptions sollten da sein.
    expect(Permission::count())->toBe(count(PermissionName::all()));
    expect(PermissionDescription::count())->toBe(count(PermissionName::all()));

    // Stichprobe: VIEW-Description trägt die erwartete deutsche Übersetzung.
    $viewPermission = Permission::where('name', PermissionName::VIEW->value)->first();
    $viewDescription = PermissionDescription::where('permission_id', $viewPermission->id)->first();
    expect($viewDescription)->not->toBeNull();
    expect($viewDescription->getTranslation('description', 'de'))->toContain('Lesen');
});

it('PermissionTableSeeder ist idempotent — zweimal aufrufen ohne Bruch', function () {
    /** @var TestCase $this */
    Model::shouldBeStrict(true);

    try {
        (new PermissionTableSeeder)->run();
        (new PermissionTableSeeder)->run();
    } finally {
        Model::shouldBeStrict(false);
    }

    expect(Permission::count())->toBe(count(PermissionName::all()));
    expect(PermissionDescription::count())->toBe(count(PermissionName::all()));
});
