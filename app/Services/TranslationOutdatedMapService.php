<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2022, 2026 - berlinHistory e.V.

See LICENSE.
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Chapter;
use App\Models\Entry;
use App\Models\Project;
use App\Models\Revision;
use App\Models\TranslationSourceReference;
use App\Support\RevisionSubject;
use Illuminate\Database\Eloquent\Model;

/**
 * Q4-Etappe 2 / I6 (2026-08-27): Sammelt fuer die Uebersetzen-Sicht
 * pro uebersetztem Feld, ob das Original nach der Uebersetzung
 * geaendert wurde. Vorher lebte die Logik als private
 * `buildOutdatedTranslationMap` im `ProjectController` (~85 LoC).
 *
 * Phase 5ab.5 (Design v6 § 4): Sync-Warnung „Original nach
 * Uebersetzung geaendert".
 */
final class TranslationOutdatedMapService
{
    /**
     * Map aller uebersetzten Felder auf einen Boolean, ob das
     * Original nach der Uebersetzung geaendert wurde.
     *
     * Key-Schema: „Model.id.field" (dieselben Payload-Keys wie in
     * `saveTranslations()`). Wert: true = veraltet, false = frisch.
     *
     * @return array<string, bool>
     */
    public function mapFor(Project $tree): array
    {
        // Alle Subjects sammeln, die im Tree vorkommen.
        /** @var array<string, array<int, int>> $subjects */
        $subjects = [];
        foreach ($tree->chapters as $chapter) {
            $subjects[Chapter::class][] = $chapter->id;
            foreach ($chapter->entries as $entry) {
                $subjects[Entry::class][] = $entry->id;
                foreach ($entry->mediaContent as $mc) {
                    foreach (['text', 'gallery', 'audiovisual'] as $rel) {
                        /** @var Model|null $obj */
                        $obj = $mc->{$rel} ?? null;
                        if ($obj) {
                            $subjects[$obj::class][] = (int) $obj->getKey();
                        }
                    }
                }
            }
        }

        // Bulk-Query: Referenzen fuer die gesammelten Subjects.
        $refs = collect();
        foreach ($subjects as $type => $ids) {
            if ($ids === []) {
                continue;
            }
            $refs = $refs->concat(
                TranslationSourceReference::query()
                    ->where('subject_type', $type)
                    ->whereIn('subject_id', $ids)
                    ->get(['subject_type', 'subject_id', 'field', 'source_revision_id'])
            );
        }
        if ($refs->isEmpty()) {
            return [];
        }

        // Aktuelle Revisions-ID je Subject einmal aufloesen — verglichen
        // wird die hoechste ID pro (type, id).
        $latestByKey = [];
        foreach ($subjects as $type => $ids) {
            if ($ids === []) {
                continue;
            }
            Revision::query()
                ->where('subject_type', $type)
                ->whereIn('subject_id', $ids)
                ->selectRaw('subject_id, MAX(id) as latest_id')
                ->groupBy('subject_id')
                ->get()
                ->each(function ($row) use (&$latestByKey, $type): void {
                    // selectRaw fuegt latest_id/subject_id an, ohne dass die
                    // Model-Klasse sie kennt — Attribute-Getter statt Property.
                    $subjectId = (int) $row->getAttribute('subject_id');
                    $latestByKey[$type.'|'.$subjectId] = (int) $row->getAttribute('latest_id');
                });
        }

        $map = [];
        foreach ($refs as $ref) {
            $latest = $latestByKey[$ref->subject_type.'|'.$ref->subject_id] ?? null;
            $short = RevisionSubject::shortName($ref->subject_type);
            if ($short === null || $latest === null) {
                continue;
            }
            $key = $short.'.'.$ref->subject_id.'.'.$ref->field;
            $map[$key] = $latest > $ref->source_revision_id;
        }

        return $map;
    }
}
