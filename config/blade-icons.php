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

/*
|--------------------------------------------------------------------------
| Blade Icons — App-Override (Phase 5-D.2)
|--------------------------------------------------------------------------
|
| Wir betreiben die Icon-Runtime aus blade-ui-kit/blade-icons plus das
| Lucide-Set aus mallardduck/blade-lucide-icons. Unsere App-Component
| `resources/views/components/icon.blade.php` (`<x-icon>`) ruft den
| `svg()`-Helper des Packages direkt und braucht keine generische
| Blade-Component-Bindung — deshalb schalten wir die Package-Default-
| Component `<x-icon>` aus (`'default' => ''`), um den Naming-Konflikt
| mit unserer App-Component zu vermeiden.
|
| Sets werden von den einzelnen Icon-Packages (`mallardduck/blade-lucide-
| icons` unter Prefix `lucide`) über ihren ServiceProvider registriert
| und bleiben verfügbar — der `svg('lucide-…')`-Helper löst den Prefix
| korrekt auf. Die Blade-Components `<x-lucide-…>` funktionieren
| ebenfalls weiterhin, falls direkte Nutzung mal nötig wird.
*/

return [

    'sets' => [
        // Sets kommen von den Icon-Packages selbst.
    ],

    'components' => [
        'default' => '',
        'disabled' => false,
    ],

    'attributes' => [],

];
