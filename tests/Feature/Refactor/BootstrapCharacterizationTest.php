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
use App\Support\PermissionName;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Bootstrap-Charakterisierung
|--------------------------------------------------------------------------
|
| Diese Tests fixieren das beobachtbare Verhalten des klassischen
| Kernel-/Exception-Handler-Bootstraps, bevor der Block-B-Refactor
| auf den Laravel-11+-Closure-Stil (`bootstrap/app.php` mit
| ->withRouting / ->withMiddleware / ->withExceptions) umstellt.
|
| Die drei Tests treffen die drei Schichten, an denen der Refactor
| greift:
|
|   1. Web-Stack-Middleware (Auth, Session, CSRF) — Authentifizierung
|      und Redirect.
|   2. Route-Middleware-Aliase (`role:Admin` aus Spatie-Permission)
|      — werden im Refactor von $routeMiddleware in
|      $middleware->alias() umgezogen.
|   3. Exception-Rendering auf 404 — wird im Refactor vom
|      app/Exceptions/Handler.php in withExceptions()-Closure
|      umgezogen.
|
| Wenn die drei Tests vor und nach dem Refactor grün sind, hat der
| Skeleton-Wechsel keine User-sichtbare Verhaltens-Änderung erzeugt.
*/

beforeEach(function () {
    foreach (PermissionName::all() as $permissionName) {
        Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
    }

    Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web'])
        ->syncPermissions(Permission::all());

    Role::firstOrCreate(['name' => 'Reader', 'guard_name' => 'web'])
        ->syncPermissions(['view']);
});

it('leitet einen nicht-authentifizierten User auf einer auth-geschützten Web-Route auf den Login um', function () {
    /** @var TestCase $this */
    // /projects ist hinter der Auth-Middleware (Resource-Route in
    // routes/web.php). Web-Stack-Middleware (EncryptCookies →
    // StartSession → ShareErrorsFromSession → VerifyCsrfToken →
    // SubstituteBindings → Language) muss durchlaufen, dann greift
    // Authenticate::redirectTo mit dem Login-Route-Redirect.
    $response = $this->get('/projects');

    $response->assertStatus(302);
    $response->assertRedirect(route('login'));
});

it('lehnt einen authentifizierten User ohne Admin-Rolle auf einer role:Admin-geschützten Route mit 403 ab', function () {
    /** @var TestCase $this */
    /** @var User $reader */
    $reader = User::factory()->create();
    $reader->assignRole('Reader');

    $this->actingAs($reader);

    // GET /register trägt seit Phase 2.5 explizit `role:Admin` als
    // Middleware (routes/web.php Z. 131-133) — Self-Service-Register-
    // Pfad wurde geschlossen, neue User können nur durch Admins
    // angelegt werden. Spatie-RoleMiddleware wirft für einen
    // authentifizierten Reader eine AuthorizationException → 403.
    //
    // Der Test fixiert, dass der `role:`-Alias zur Klasse aufgelöst
    // wird. Im Refactor wandert die Alias-Registrierung von
    // Kernel::$routeMiddleware in $middleware->alias() im Bootstrap.
    $response = $this->get('/register');

    $response->assertStatus(403);
});

it('rendert eine unbekannte Route mit HTTP 404', function () {
    /** @var TestCase $this */
    // /this-route-does-not-exist trifft keinen Routing-Match. Der
    // ExceptionHandler::render-Pfad muss eine 404-Response zurück
    // geben — der Test fixiert das HTTP-Verhalten unabhängig vom
    // konkreten Response-Body (HTML vs. JSON), den Laravel je nach
    // Accept-Header zurückliefert.
    $response = $this->get('/this-route-does-not-exist-'.uniqid());

    $response->assertStatus(404);
});
