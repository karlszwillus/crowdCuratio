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

use App\Data\ChapterData;
use App\Models\Chapter;

/**
 * Kapselt die Schreibpfade auf Chapter — Create mit Position-
 * Calculation und Update mit Translation-Verzweigung.
 *
 * Die Position-Calculation ist heute eine simple max+1-Logik, die
 * unter Race-Conditions theoretisch kollidieren kann. Eine sauberere
 * Lösung (DB-side Trigger oder Position via Constraint) ist
 * Refactoring-Material; hier wird nur das bestehende Verhalten 1:1
 * aus dem ChapterController gezogen.
 */
class ChapterService
{
    /**
     * Legt ein neues Chapter im Project an. Die Position wird auf
     * `max(position) + 1` gesetzt, leere Projects bekommen Position 1.
     */
    public function create(ChapterData $data, int $projectId): Chapter
    {
        $latest = Chapter::where('project_id', $projectId)
            ->orderByDesc('position')
            ->first();

        return Chapter::create([
            'project_id' => $projectId,
            'name' => $data->name,
            'subtitle' => $data->subtitle,
            'description' => $data->description,
            'position' => ($latest->position ?? 0) + 1,
        ]);
    }

    /**
     * Aktualisiert ein Chapter. Je nach `isTranslation`-Flag im DTO
     * werden die Felder direkt gesetzt oder über
     * setTranslation('en', ...) als englische Übersetzung
     * geschrieben.
     *
     * `is_translated` wird unabhängig davon aus dem DTO übernommen
     * (Frontend-Flag: "Übersetzung existiert").
     *
     * Das `'undefined'`-Sentinel für `description` im Translation-
     * Pfad spiegelt das alte Controller-Verhalten — das Frontend
     * schickt diesen Literal-String, wenn das Feld bei der
     * Übersetzung nicht gefüllt war. Wird in einem späteren Pass
     * sauber durch null-Handling im Frontend ersetzt.
     */
    public function update(Chapter $chapter, ChapterData $data): Chapter
    {
        if ($data->isTranslation) {
            $chapter->setTranslation('name', 'en', $data->name);
            $chapter->setTranslation('subtitle', 'en', $data->subtitle ?? '');

            if (($data->description ?? '') !== 'undefined') {
                $chapter->setTranslation('description', 'en', $data->description ?? '');
            }
        } else {
            $chapter->name = $data->name;
            $chapter->subtitle = $data->subtitle;
            $chapter->description = $data->description;
        }

        $chapter->is_translated = $data->isTranslated;

        $chapter->save();

        return $chapter;
    }
}
