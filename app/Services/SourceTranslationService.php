<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2022, 2026 - berlinHistory e.V.

See LICENSE.
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Source;

/**
 * Q4-Etappe 2 / I7 (2026-08-27): Extraktion der frueheren
 * `ContentController::translateField`-Methode. Wurde von saveText und
 * saveImage aufgerufen — mit dem I7-Split (Text/Image/Gallery in eigene
 * Block-Controller) waere die Logik sonst dupliziert.
 *
 * Sources sind global geteilte Origin-/Copyright-Quellen ohne Project-
 * Bezug — Aufrufer muss die globale `edit`-Permission vorab pruefen.
 */
final class SourceTranslationService
{
    /**
     * Schreibt die englische Uebersetzung des Namens einer Source-Zeile
     * und setzt das `is_translated`-Flag entsprechend.
     */
    public function translate(int $sourceId, string $englishName, mixed $isTranslated): void
    {
        $source = Source::findOrFail($sourceId);
        $source->setTranslation('name', 'en', $englishName);
        $source->is_translated = isset($isTranslated) && (bool) $isTranslated;
        $source->save();
    }
}
