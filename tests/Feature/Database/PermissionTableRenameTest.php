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

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Tabellen-Rename user_has_permissions → project_user_permissions
|--------------------------------------------------------------------------
|
| Block D PR 2 / D.7. Fixiert das Schema-Ergebnis: nach den
| Migrations heißt die Pivot-Tabelle `project_user_permissions`
| und die alte `user_has_permissions` existiert nicht mehr. Plus
| Roundtrip-Test (down/up) — die neue Migration muss zurückrollbar
| sein, damit ein Rollback auf Production möglich ist.
*/

it('Migrations-Endzustand: project_user_permissions existiert, user_has_permissions nicht', function () {
    /** @var TestCase $this */
    expect(Schema::hasTable('project_user_permissions'))->toBeTrue();
    expect(Schema::hasTable('user_has_permissions'))->toBeFalse();
});

it('Migrations-Roundtrip: down() stellt user_has_permissions wieder her, up() benennt zurück', function () {
    /** @var TestCase $this */
    expect(Schema::hasTable('project_user_permissions'))->toBeTrue();

    // down() der Rename-Migration: aktuelle Tabelle zurückbenennen.
    Artisan::call(
        'migrate:rollback',
        ['--path' => 'database/migrations/2026_06_02_000000_rename_user_has_permissions_to_project_user_permissions.php']
    );

    expect(Schema::hasTable('user_has_permissions'))->toBeTrue();
    expect(Schema::hasTable('project_user_permissions'))->toBeFalse();

    // up() wieder hoch.
    Artisan::call(
        'migrate',
        ['--path' => 'database/migrations/2026_06_02_000000_rename_user_has_permissions_to_project_user_permissions.php']
    );

    expect(Schema::hasTable('project_user_permissions'))->toBeTrue();
    expect(Schema::hasTable('user_has_permissions'))->toBeFalse();
});

it('project_user_permissions hat die erwarteten Spalten', function () {
    /** @var TestCase $this */
    expect(Schema::hasColumns('project_user_permissions', [
        'id',
        'project_id',
        'permission_id',
        'user_id',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
});
