<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

See LICENSE.
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Audiovisual;
use App\Models\Entry;
use App\Models\Gallery;
use App\Models\MediaContent;
use App\Models\QuoteBlock;
use App\Models\Text;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Q4-Etappe 4 / C1c (2026-09-08): Legt einen leeren Content-Block
 * (Text / Galerie / Audiovisual) an einer bestimmten Position im
 * Entry an. Ersetzt die Modal-basierten Store-Endpoints für den
 * Add-Flow — der User füllt den neuen Block danach direkt im
 * In-Place-Editor.
 *
 * Position-Semantik:
 *  - `afterMediaContentId === null` → neuer Block wird zum ersten
 *    Block des Entries (`position = 1`), alle bestehenden Blöcke
 *    verschieben sich um +1.
 *  - `afterMediaContentId = X`      → neuer Block liegt direkt
 *    hinter X (`position = X.position + 1`), alle Blöcke mit
 *    `position > X.position` verschieben sich um +1.
 */
final class ContentInsertionService
{
    /** @var array<int, string> */
    private const ALLOWED_TYPES = ['text', 'gallery', 'audiovisual', 'quote'];

    /**
     * Legt einen leeren Block an und hängt ihn per MediaContent an
     * den Entry. Return: die neue MediaContent-ID.
     */
    public function insertBlank(int $entryId, string $type, ?int $afterMediaContentId = null): int
    {
        if (! in_array($type, self::ALLOWED_TYPES, true)) {
            throw new InvalidArgumentException("Unknown content type: {$type}");
        }

        // Entry muss existieren — Authorisierung liegt beim Aufrufer.
        Entry::findOrFail($entryId);

        return DB::transaction(function () use ($entryId, $type, $afterMediaContentId): int {
            $newPosition = $this->computeNewPosition($entryId, $afterMediaContentId);

            // Alle bestehenden MediaContent-Rows im gleichen Entry mit
            // position >= newPosition um +1 verschieben. SoftDeleted-
            // Rows bleiben unberührt (Default-Query filtert sie).
            MediaContent::where('parent_id', $entryId)
                ->where('parent_type', Entry::class)
                ->where('position', '>=', $newPosition)
                ->increment('position');

            $content = $this->createBlankContent($type);

            $mediaContent = MediaContent::create([
                'position' => $newPosition,
                'content_id' => $content->id,
                'content_type' => get_class($content),
                'parent_id' => $entryId,
                'parent_type' => Entry::class,
            ]);

            return (int) $mediaContent->id;
        });
    }

    /**
     * Berechnet die Position der neuen Row aus dem Insert-Anker.
     */
    private function computeNewPosition(int $entryId, ?int $afterMediaContentId): int
    {
        if ($afterMediaContentId === null) {
            // Vor den ersten Block: neuer erster Slot.
            return 1;
        }

        $anchor = MediaContent::where('parent_id', $entryId)
            ->where('parent_type', Entry::class)
            ->find($afterMediaContentId);

        // Fallback (Anker existiert nicht mehr, z. B. Race Condition):
        // ans Ende hängen.
        if ($anchor === null) {
            $max = MediaContent::where('parent_id', $entryId)
                ->where('parent_type', Entry::class)
                ->max('position');

            return ((int) $max) + 1;
        }

        return ((int) $anchor->position) + 1;
    }

    /**
     * Erzeugt einen leeren Content-Row je nach Typ. Text- und
     * Source-Felder bleiben null — die C1c-Migration hat sie
     * nullable gemacht, damit dieser Insert-Weg sauber funktioniert.
     */
    private function createBlankContent(string $type): Text|Gallery|Audiovisual|QuoteBlock
    {
        return match ($type) {
            'text' => tap(new Text, function (Text $t): void {
                // HasTranslations: leerer Locale-Wert statt null, damit
                // Spatie sauber serialisiert und der In-Place-Editor
                // beim ersten Öffnen keinen null-Zugriff hat.
                // `texts.position` wurde in 2021_07_28_163554 entfernt —
                // Sortierung liegt seither ausschließlich an
                // media_content.position.
                $t->setTranslation('text', app()->getLocale(), '');
                $t->is_translated = false;
                $t->save();
            }),
            'gallery' => Gallery::create([]),
            'audiovisual' => tap(new Audiovisual, function (Audiovisual $a): void {
                // Default-Typ auf `video` — wird nach dem Speichern im
                // Editor durch Link-Erkennung überschrieben.
                $a->type = 'video';
                $a->save();
            }),
            'quote' => tap(new QuoteBlock, function (QuoteBlock $q): void {
                // Zitat-Text ist HasTranslations — leerer Locale-Wert
                // statt null, damit Spatie beim ersten Editor-Zugriff
                // sauber serialisiert.
                $q->setTranslation('text', app()->getLocale(), '');
                $q->is_translated = false;
                $q->save();
            }),
            // Unerreichbar wegen ALLOWED_TYPES-Prüfung in insertBlank();
            // PHPStan sieht das aber nicht ohne expliziten default-Arm.
            default => throw new InvalidArgumentException("Unknown content type: {$type}"),
        };
    }
}
