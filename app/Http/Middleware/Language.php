<?php

/**
crowdCuratio - Curating together virtually
Copyright (C)2022 - berlinHistory e.V.

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

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;

class Language
{
    public function handle($request, Closure $next)
    {
        $languages = Config::get('languages');

        // Phase 5ac.1: Konto-Praeferenz hat Vorrang vor der Session,
        // damit die Sprachwahl im Profil sofort und geraeteuebergreifend
        // greift. Session bleibt als Fallback fuer nicht eingeloggte
        // Nutzer und als Uebergang, bis das Feld ueberall gepflegt ist.
        $user = $request->user();
        if ($user && ! empty($user->locale) && array_key_exists($user->locale, $languages)) {
            App::setLocale($user->locale);
        } elseif (Session::has('applocale') and array_key_exists(Session::get('applocale'), $languages)) {
            App::setLocale(Session::get('applocale'));
        } else {
            App::setLocale(Config::get('app.fallback_locale'));
        }

        return $next($request);
    }
}
