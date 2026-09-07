<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2022, 2026 - berlinHistory e.V.

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

namespace App\Services;

use App\Models\Source;

/**
 * Kapselt die "find-or-create"-Logik fuer Source-Zeilen (Origin und
 * Copyright). Hat vorher als `getSource`-Methode dupliziert in
 * `ProjectController` und `ContentController` gelebt — der Service
 * loest dieses Duplikat auf.
 *
 * Q4-Etappe 3 / C0-8a (2026-09-07): Source-Zeilen sind ab jetzt
 * projekt-scoped. Wer den Service ruft, gibt die Projekt-ID mit —
 * die Suche filtert dann nur auf Rows dieses Projekts, ein neu
 * angelegter Row bekommt das Projekt gesetzt. `null` bleibt fuer
 * Rueckwaertskompat (Alt-Aufrufer waehrend der 8a→8b-Uebergangs-
 * phase) erlaubt; sobald der Migrations-Assistent (8b) durch ist,
 * kann die Signatur auf `int $projectId` verscharft werden.
 */
class SourceService
{
    /**
     * Findet eine Source mit passendem Namen + Type (optional
     * project-scoped), oder legt eine neue an. Gibt die Source-ID
     * zurueck.
     *
     * Q4-Etappe 3 / C0-8a: Statt manuellem `json_encode` bei der
     * Anlage geht der Weg jetzt sauber ueber `HasTranslations::
     * setTranslation` — dann greifen die Model-Casts und weitere
     * Locales lassen sich nachtragen, ohne den Container haendisch
     * zu bauen.
     */
    public function findOrCreateId(string $value, string $type, ?int $projectId = null): int
    {
        $query = Source::query()->where('type', $type);
        if ($projectId !== null) {
            $query->where('project_id', $projectId);
        } else {
            // Alte, projektlose Rows sind bis zum Assistenten-
            // Durchlauf (8b) noch der Standard — Match nur gegen
            // sie, damit ein neuer Projekt-Row nicht einen Alt-
            // Row aus einem Nachbarprojekt „adoptiert".
            $query->whereNull('project_id');
        }

        foreach ($query->get() as $source) {
            if ($source->name === $value) {
                return (int) $source->id;
            }
        }

        $source = new Source;
        $source->type = $type;
        $source->project_id = $projectId;
        $source->setTranslation('name', app()->getLocale(), $value);
        $source->save();

        return (int) $source->id;
    }
}
