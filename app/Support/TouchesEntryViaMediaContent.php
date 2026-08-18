<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 */

declare(strict_types=1);

namespace App\Support;

use App\Models\Entry;
use App\Models\MediaContent;
use Illuminate\Database\Eloquent\Model;

/**
 * Phase 5ac.1-Followup: Content-Blocks (Text, Gallery, Audiovisual)
 * haengen ueber den MediaContent-Pivot am Eintrag, nicht per direktem
 * belongsTo. Damit „zuletzt bearbeitet" im Dashboard auch fuer diese
 * Modelle greift, sucht der Trait beim saved-Event den passenden
 * Pivot-Eintrag und toucht den Entry — der wiederum touched Chapter,
 * das wiederum Project (Kette aus $touches).
 *
 * Ein zusaetzlicher SELECT pro Save; bei Auto-Save-on-Blur der
 * Uebersetzen-Sicht sind das 20-30 im Editor-Alltag. Falls es zum
 * Hotspot wird, koennen wir die parent_id in einem Cache halten,
 * aber fuer 5ac.1 reicht der schlichte Weg.
 */
trait TouchesEntryViaMediaContent
{
    public static function bootTouchesEntryViaMediaContent(): void
    {
        static::saved(function (Model $model): void {
            $mc = MediaContent::query()
                ->where('content_type', static::class)
                ->where('content_id', $model->getKey())
                ->first(['parent_id', 'parent_type']);
            if ($mc === null || $mc->parent_type !== Entry::class) {
                return;
            }
            $entry = Entry::query()->find($mc->parent_id);
            $entry?->touch();
        });
    }
}
