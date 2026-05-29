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

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        // Strict-Mode in non-production aktiv (Phase 2 / C.1, F-LAR-013).
        // Wirft Exceptions bei:
        // - Lazy-Loading nach eager-loaded Query (preventLazyLoading)
        // - Zugriff auf nicht gefüllte Attribute (preventAccessingMissingAttributes)
        // - Silently-discarded Mass-Assignment (preventSilentlyDiscardingAttributes)
        //
        // Wir aktivieren das bewusst nur außerhalb von Production —
        // Tests, Sail-Dev, CI-Pest-Pfade — damit das Frühwarnsystem
        // greift, ohne live-User durch eine späte Regression zu treffen.
        // Sobald Phase 4 die N+1-Hotspots aufgeräumt hat, darf das in
        // Production scharf werden.
        Model::shouldBeStrict(! $this->app->isProduction());
    }
}
