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

use App\Data\EntryData;
use App\Models\Entry;

/**
 * Kapselt die Schreibpfade auf Entry — Create mit Position-
 * Calculation (max+1 innerhalb eines Chapters) und Update mit
 * Translation-Verzweigung.
 *
 * Strukturell parallel zu ChapterService. Beide Services lassen
 * sich später in eine gemeinsame AbstractItemService-Basis
 * generalisieren, falls ein drittes curierbares Modell dazukommt;
 * heute bringt die Generalisierung nur Komplexität ohne Gewinn.
 */
class EntryService
{
    /**
     * Legt einen neuen Entry im Chapter an. Position-Calculation
     * 1:1 wie bei ChapterService::create — max(position) + 1, leere
     * Chapter starten bei 1.
     */
    public function create(EntryData $data, int $chapterId): Entry
    {
        $latest = Entry::where('chapter_id', $chapterId)
            ->orderByDesc('position')
            ->first();

        return Entry::create([
            'chapter_id' => $chapterId,
            'name' => $data->name,
            'subtitle' => $data->subtitle,
            'description' => $data->description,
            'position' => ($latest->position ?? 0) + 1,
        ]);
    }

    /**
     * Aktualisiert einen Entry. Translation-Verzweigung identisch
     * zu ChapterService::update — inklusive des
     * `'undefined'`-Sentinels für die Description.
     */
    public function update(Entry $entry, EntryData $data): Entry
    {
        if ($data->isTranslation) {
            $entry->setTranslation('name', 'en', $data->name);
            $entry->setTranslation('subtitle', 'en', $data->subtitle ?? '');

            if (($data->description ?? '') !== 'undefined') {
                $entry->setTranslation('description', 'en', $data->description ?? '');
            }
        } else {
            $entry->name = $data->name;
            $entry->subtitle = $data->subtitle;
            $entry->description = $data->description;
        }

        $entry->is_translated = $data->isTranslated;

        $entry->save();

        return $entry;
    }
}
