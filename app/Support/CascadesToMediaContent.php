<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 */

declare(strict_types=1);

namespace App\Support;

use App\Models\MediaContent;
use Illuminate\Database\Eloquent\Model;

/**
 * Q4-Etappe 7 · E7-Followup (2026-09-11): Content-Blocks (Text,
 * Audiovisual, Gallery, QuoteBlock, DataFactBlock) haengen ueber
 * einen MediaContent-Pivot am Entry. Beim (Soft-)Delete des Blocks
 * bleibt das Pivot bisher stehen — visuelle Folge: `@isset`-Gates in
 * den Block-Views unterdruecken das Block-Render, aber die Add-Bar
 * dahinter laeuft weiter. Im Canvas standen zwei identische Add-Bars
 * in Folge.
 *
 * Der Trait haengt sich an die `deleted`- und `restored`-Events des
 * jeweiligen Block-Models und propagiert den Zustand auf alle Pivots,
 * die auf diesen Block zeigen. MediaContent nutzt selbst SoftDeletes,
 * damit bleibt die Aktion revertierbar.
 *
 * Reihenfolge: `deleted` feuert nach dem Soft-Delete. Die Query hier
 * arbeitet mit MediaContent's Default-Scope (nicht `withTrashed`),
 * also werden nur noch aktive Pivots angefasst — ein bereits ge-
 * loeschtes Pivot bleibt unbeeinflusst.
 */
trait CascadesToMediaContent
{
    public static function bootCascadesToMediaContent(): void
    {
        static::deleted(function (Model $model): void {
            // Force-Delete (permanent) laeuft ueber ein eigenes Event
            // (`forceDeleted`); hier reicht das Soft-Delete-Cascade.
            MediaContent::query()
                ->where('content_type', static::class)
                ->where('content_id', $model->getKey())
                ->get()
                ->each->delete();
        });

        static::restored(function (Model $model): void {
            MediaContent::query()
                ->withTrashed()
                ->where('content_type', static::class)
                ->where('content_id', $model->getKey())
                ->whereNotNull('deleted_at')
                ->get()
                ->each->restore();
        });
    }
}
