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

namespace App\Services;

use App\Models\Source;

/**
 * Kapselt die "find-or-create"-Logik für Source-Zeilen (Origin
 * und Copyright). Hat vorher als `getSource`-Methode dupliziert in
 * `ProjectController` und `ContentController` gelebt — der
 * Service löst dieses Duplikat auf.
 *
 * Eine Source-Zeile wird über den Anzeige-Namen und den Type
 * gesucht; existiert sie bereits, gibt der Service die ID zurück,
 * sonst legt er eine neue Zeile an (mit dem Namen als Translation
 * auf der aktuellen Locale, analog zum alten Verhalten).
 */
class SourceService
{
    /**
     * Findet eine Source mit passendem Namen + Type, oder legt
     * eine neue an. Gibt die Source-ID zurück.
     */
    public function findOrCreateId(string $value, string $type): int
    {
        $sources = Source::where('type', $type)->get();

        foreach ($sources as $source) {
            if ($source->name === $value) {
                return $source->id;
            }
        }

        return Source::insertGetId([
            'name' => json_encode([app()->getLocale() => $value]),
            'type' => $type,
            'created_at' => now(),
        ]);
    }
}
