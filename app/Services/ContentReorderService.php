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

use App\Models\Chapter;
use App\Models\Entry;
use App\Models\MediaContent;
use App\Models\Project;

/**
 * Kapselt die Drag-and-Drop-Reorder-Operationen über die drei
 * Hierarchie-Ebenen Chapter / Entry / MediaContent.
 *
 * Wird vom ChapterController::saveDragAndDrop und perspektivisch
 * auch vom EntryController konsumiert. Die Authorization (Gate
 * gegen ProjectPolicy::update) bleibt im Controller — der Service
 * stellt nur `resolveProject(...)` zur Verfügung, damit der
 * Controller das richtige Project-Objekt für den Authorize-Call
 * findet.
 */
class ContentReorderService
{
    /**
     * Schreibt die übergebene Chapter-Reihenfolge: erstes Element
     * bekommt position 1, zweites position 2, etc.
     *
     * @param  array<int|string|null>  $ids
     */
    public function reorderChapters(array $ids): void
    {
        foreach ($ids as $index => $chapterId) {
            if ($chapterId === null) {
                continue;
            }

            Chapter::where('id', $chapterId)
                ->update(['position' => $index + 1]);
        }
    }

    /**
     * Schreibt die übergebene Entry-Reihenfolge innerhalb eines
     * Chapters. Setzt zusätzlich `chapter_id` auf das Ziel-Chapter
     * — damit deckt der Aufruf sowohl Reorder innerhalb eines
     * Chapters als auch Verschieben zwischen Kapiteln ab.
     *
     * @param  array<int|string|null>  $ids
     */
    public function reorderEntries(int $targetChapterId, array $ids): void
    {
        foreach ($ids as $index => $entryId) {
            if ($entryId === null) {
                continue;
            }

            Entry::where('id', $entryId)->update([
                'chapter_id' => $targetChapterId,
                'position' => $index + 1,
            ]);
        }
    }

    /**
     * Schreibt die übergebene MediaContent-Reihenfolge. Wenn ein
     * `$targetEntryId` übergeben ist, wird zusätzlich die
     * `parent_id` umgesetzt (Verschieben zwischen Entries). Ohne
     * `$targetEntryId` wird nur die Position aktualisiert.
     *
     * Welle 4d (ADR-0022): von der alten `media_contentable_id` auf
     * `parent_id` umgestellt.
     *
     * @param  array<int|string|null>  $ids
     */
    public function reorderContent(?int $targetEntryId, array $ids): void
    {
        foreach ($ids as $index => $contentId) {
            if ($contentId === null) {
                continue;
            }

            if ($targetEntryId !== null) {
                MediaContent::where('id', $contentId)->update([
                    'parent_id' => $targetEntryId,
                    'position' => $index + 1,
                ]);
            } else {
                MediaContent::where('id', $contentId)
                    ->update(['position' => $index + 1]);
            }
        }
    }

    /**
     * Findet das Project, dem ein Drag-and-Drop-Vorgang gilt.
     * Je nach Element-Typ liegt die Project-Referenz an einer
     * anderen Stelle im Payload:
     *
     *  - chapter: erstes Element aus `data` → Chapter::project
     *  - entry:   `payload.chapter` → Chapter::project
     *  - content: `payload.entry`   → Entry::chapter::project
     *
     * Liefert null bei unbekanntem Element-Typ oder wenn die
     * Referenz nicht auflösbar ist (leere oder bösartige IDs).
     *
     * @param  array<string, mixed>  $payload
     * @param  array<int|string|null>  $data
     */
    public function resolveProject(?string $element, array $payload, array $data): ?Project
    {
        switch ($element) {
            case 'chapter':
                $firstId = reset($data);
                /** @var Chapter|null $chapter */
                $chapter = Chapter::find($firstId);

                return $chapter?->project;

            case 'entry':
                /** @var Chapter|null $chapter */
                $chapter = Chapter::find($payload['chapter'] ?? null);

                return $chapter?->project;

            case 'content':
                /** @var Entry|null $entry */
                $entry = Entry::find($payload['entry'] ?? null);

                return $entry?->chapter?->project;

            default:
                return null;
        }
    }
}
