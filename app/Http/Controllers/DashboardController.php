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

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

/**
 * Dashboard-Landing-Sicht (Phase 5e.1, Screen 09 aus Handoff v4).
 *
 * Die vier Sektionen (Wiederaufnahme / Meine Projekte / Mir zugeteilt
 * / Letzte Kommentare) leben seit Phase-5-Backlog #70 in der
 * Volt-Component `<livewire:dashboard-sections>` mit #[Lazy] —
 * Skelett-Ladezustand sichtbar bis Livewire hydratisiert.
 *
 * Der Controller rendert nur noch den Chrome-Kontext (Begruessung,
 * Suche, „+ Neues Projekt"). Daten-Feeds sitzen in der Component.
 */
class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function __invoke(): View
    {
        return view('dashboard');
    }
}
