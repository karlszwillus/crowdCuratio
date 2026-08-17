<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 */

declare(strict_types=1);

namespace App\Support;

use Jfcherng\Diff\DiffHelper;

/**
 * Phase 5ab.4 (Design v6 § 6): Wort-Level-Diff fuer den Verlauf-Block.
 *
 * php-diff (jfcherng/php-diff) rendert einen Inline-Diff auf Wortebene:
 * hinzugefuegte Woerter in `--success-bg`, entfernte in `--danger-bg`
 * mit `<del>`-Wrap fuer die Durchstreichung (WCAG 1.4.1 — Farbe ist
 * nicht der einzige Kanal).
 */
final class RevisionDiff
{
    /**
     * @return array{html: string, added: int, removed: int}
     */
    public static function renderWordDiff(string $old, string $new): array
    {
        // php-diff bekommt Zeilen, wir wollen Woerter — deshalb der
        // Trick: die zwei Strings zeichenweise in eine „Zeile pro Wort"
        // umbrechen (Split an Whitespace, gebrochen zurueckreichen), dann
        // Inline-Renderer. Fuer die weiche HTML-Ausgabe reicht das.
        $oldWords = preg_split('/(\s+)/u', $old, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
        $newWords = preg_split('/(\s+)/u', $new, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];

        $rendererOptions = [
            'detailLevel' => 'word',
            'showHeader' => false,
            'wrapperClasses' => ['diff-wrapper', 'diff-html'],
        ];
        $diffOptions = [
            'context' => 3,
            'ignoreCase' => false,
            'ignoreWhitespace' => false,
        ];

        $rawHtml = DiffHelper::calculate(
            implode("\n", $oldWords),
            implode("\n", $newWords),
            'Inline',
            $diffOptions,
            $rendererOptions
        );

        // Zaehl-Metriken fuer die Fusszeile "N Aenderungen · N Woerter
        // hinzugefuegt, N entfernt" (§ 6): einfach Klassen-Count im HTML.
        $added = substr_count($rawHtml, 'class="ins"');
        $removed = substr_count($rawHtml, 'class="del"');

        // Klassen-Namen des Renderers auf unsere Token-Klassen mappen
        // (bg-success-bg / bg-danger-bg). Der Renderer setzt <ins> und
        // <del> mit `class="ins"`/`class="del"`.
        $html = str_replace(
            ['class="ins"', 'class="del"'],
            [
                'class="rounded-sm bg-success-bg px-0.5 text-success"',
                'class="rounded-sm bg-danger-bg px-0.5 text-danger line-through"',
            ],
            $rawHtml
        );

        return [
            'html' => $html,
            'added' => $added,
            'removed' => $removed,
        ];
    }
}
