<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 */

declare(strict_types=1);

namespace App\Support;

// jfcherng/php-diff bleibt in composer.json fuer eventuelle spaetere
// Side-by-Side-Ansichten (§ 6 Umschalter). Der Inline-Modus hier laeuft
// ohne Renderer, wir bringen den Wort-Diff selbst — sonst schleift der
// Combined-Renderer immer Zeilennummern und +/- Rahmen mit.

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
        // Der Combined-Renderer aus php-diff schreibt Zeilen — wir
        // wollen aber Fliesstext mit Wort-Diff innerhalb der Zeile.
        // Trick: den Text am Whitespace splitten und die Woerter selbst
        // Sequenz-diffen (LCS light via php-diff), dann per Loop wieder
        // in Fliesstext zurueck. So brauchen wir keinen Renderer, der
        // Zeilennummern oder Rahmen mitbringt.
        $oldTokens = self::tokenize($old);
        $newTokens = self::tokenize($new);

        $opcodes = self::opcodes($oldTokens, $newTokens);

        $out = '';
        $added = 0;
        $removed = 0;
        foreach ($opcodes as [$tag, $i1, $i2, $j1, $j2]) {
            $oldSlice = self::joinTokens(array_slice($oldTokens, $i1, $i2 - $i1));
            $newSlice = self::joinTokens(array_slice($newTokens, $j1, $j2 - $j1));
            if ($tag === 'equal') {
                $out .= htmlspecialchars($newSlice, ENT_QUOTES, 'UTF-8');
            } elseif ($tag === 'insert') {
                $out .= '<ins class="rounded-sm bg-success-bg px-0.5 text-success no-underline">'
                    .htmlspecialchars($newSlice, ENT_QUOTES, 'UTF-8').'</ins>';
                $added++;
            } elseif ($tag === 'delete') {
                $out .= '<del class="rounded-sm bg-danger-bg px-0.5 text-danger line-through">'
                    .htmlspecialchars($oldSlice, ENT_QUOTES, 'UTF-8').'</del>';
                $removed++;
            } else { // replace
                $out .= '<del class="rounded-sm bg-danger-bg px-0.5 text-danger line-through">'
                    .htmlspecialchars($oldSlice, ENT_QUOTES, 'UTF-8').'</del>'
                    .'<ins class="rounded-sm bg-success-bg px-0.5 text-success no-underline">'
                    .htmlspecialchars($newSlice, ENT_QUOTES, 'UTF-8').'</ins>';
                $added++;
                $removed++;
            }
        }

        return [
            'html' => $out,
            'added' => $added,
            'removed' => $removed,
        ];
    }

    /**
     * Text in Woerter + Trennzeichen zerlegen. Trennzeichen bleiben als
     * eigene Tokens erhalten, damit der zusammengesetzte Diff-Text die
     * Original-Whitespace-Struktur behaelt.
     *
     * @return array<int, string>
     */
    private static function tokenize(string $text): array
    {
        $tokens = preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];

        return array_values(array_filter($tokens, fn ($t) => $t !== ''));
    }

    /**
     * @param  array<int, string>  $tokens
     */
    private static function joinTokens(array $tokens): string
    {
        return implode('', $tokens);
    }

    /**
     * Vereinfachtes Sequenz-Diff auf Token-Ebene (LCS + Backtracking).
     * Reicht fuer kurze bis mittelgrosse Textfelder; fuer die
     * seltenen 10-kByte-Description-Felder waere ein spezialisierter
     * Algorithmus schneller, aber die Laufzeit ist praktisch OK.
     *
     * @param  array<int, string>  $a
     * @param  array<int, string>  $b
     * @return list<array{0:string,1:int,2:int,3:int,4:int}>
     */
    private static function opcodes(array $a, array $b): array
    {
        $n = count($a);
        $m = count($b);
        $lcs = array_fill(0, $n + 1, array_fill(0, $m + 1, 0));
        for ($i = $n - 1; $i >= 0; $i--) {
            for ($j = $m - 1; $j >= 0; $j--) {
                $lcs[$i][$j] = $a[$i] === $b[$j]
                    ? $lcs[$i + 1][$j + 1] + 1
                    : max($lcs[$i + 1][$j], $lcs[$i][$j + 1]);
            }
        }

        $ops = [];
        $i = 0;
        $j = 0;
        while ($i < $n && $j < $m) {
            if ($a[$i] === $b[$j]) {
                $i0 = $i;
                $j0 = $j;
                while ($i < $n && $j < $m && $a[$i] === $b[$j]) {
                    $i++;
                    $j++;
                }
                $ops[] = ['equal', $i0, $i, $j0, $j];
            } elseif ($lcs[$i + 1][$j] >= $lcs[$i][$j + 1]) {
                $i0 = $i;
                while ($i < $n && ($j >= $m || $lcs[$i + 1][$j] >= $lcs[$i][$j + 1]) && $a[$i] !== ($b[$j] ?? null)) {
                    $i++;
                }
                $ops[] = ['delete', $i0, $i, $j, $j];
            } else {
                $j0 = $j;
                while ($j < $m && ($i >= $n || $lcs[$i][$j + 1] > $lcs[$i + 1][$j]) && ($a[$i] ?? null) !== $b[$j]) {
                    $j++;
                }
                $ops[] = ['insert', $i, $i, $j0, $j];
            }
        }
        if ($i < $n) {
            $ops[] = ['delete', $i, $n, $j, $j];
        }
        if ($j < $m) {
            $ops[] = ['insert', $i, $i, $j, $m];
        }

        // Aufeinanderfolgende delete+insert zu einem 'replace' verschmelzen
        // — sonst wuerde in einem Wort-Wechsel („Zeit" → „Zeiten") die
        // Ausgabe wie zwei getrennte Aktionen wirken. Optisch besser,
        // wenn beide Marker direkt nebeneinander stehen.
        $merged = [];
        foreach ($ops as $op) {
            if ($merged !== [] && $merged[array_key_last($merged)][0] === 'delete' && $op[0] === 'insert') {
                $last = array_pop($merged);
                $merged[] = ['replace', $last[1], $last[2], $op[3], $op[4]];
            } else {
                $merged[] = $op;
            }
        }

        return $merged;
    }
}
