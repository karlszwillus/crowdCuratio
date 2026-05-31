<?php

/**
crowdCuratio - Curating together virtually
Copyright (C)2022, 2026 - berlinHistory e.V.

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
| Admin-Seed (CreateAdminUserSeeder)
|--------------------------------------------------------------------------
|
| Werte für den initialen Admin-User. Werden ausschließlich von
| `database/seeders/CreateAdminUserSeeder.php` gelesen.
|
| `email` und `password` MÜSSEN in `.env` gesetzt sein, sonst bricht
| der Seeder bewusst ab (siehe ADR-0013, Befund F-SEC-009 — kein
| "leeres Passwort"-Footgun mehr).
|
| Sicherheitskonvention: `ADMIN_PASSWORD` wird nach erfolgreichem
| Seed aus `.env` wieder entfernt.
|
| Der Indirektionsschritt über `config()` statt `env()` ist nötig,
| damit `php artisan config:cache` den Seeder nicht aushebelt
| (Larastan-Regel `larastan.noEnvCallsOutsideOfConfig`).
|
*/

return [

    'name' => env('ADMIN_NAME', 'Admin'),

    'last_name' => env('ADMIN_LAST_NAME', ''),

    'email' => env('ADMIN_EMAIL'),

    'password' => env('ADMIN_PASSWORD'),

];
