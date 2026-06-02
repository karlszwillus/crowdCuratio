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

use App\Http\Middleware\Language;
use App\Http\Middleware\RedirectIfAuthenticated;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Web-Group: Language hinten anhängen. EncryptCookies,
        // StartSession, VerifyCsrfToken, SubstituteBindings,
        // ShareErrorsFromSession kommen aus dem Framework-Default.
        $middleware->web(append: [
            Language::class,
        ]);

        // Route-Middleware-Aliase: Spatie + projekt-eigener
        // `admin`-Alias (Phase 4 / Block D löst den durch
        // role:Admin ab).
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'guest' => RedirectIfAuthenticated::class,
        ]);

        // TrimStrings-Ausnahmen für Passwort-Felder — Framework
        // trimmt sonst und macht z. B. ein Passwort mit führendem
        // Leerzeichen still kürzer.
        $middleware->trimStrings(except: [
            'current_password',
            'password',
            'password_confirmation',
        ]);

        // Authenticate-Redirect: zur named Route `login` umleiten,
        // wenn der Request keinen JSON erwartet. Ersetzt die alte
        // App\Http\Middleware\Authenticate-Subklasse.
        $middleware->redirectGuestsTo(fn () => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Inputs, die bei Validation-Exceptions nicht in die Session
        // geflasht werden (sonst stünde z. B. ein falsches Passwort
        // im old()-Helper im Login-Formular).
        $exceptions->dontFlash([
            'current_password',
            'password',
            'password_confirmation',
        ]);
    })
    ->create();
