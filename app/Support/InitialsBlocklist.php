<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 */

declare(strict_types=1);

namespace App\Support;

/**
 * Phase 5ac.2 (Profil § 2): Pruefung eines Kuerzels gegen die
 * Sperrliste aus `config/kuerzel_blocklist.php`.
 *
 * Normalisierung: Uppercase, Umlaute → Grundbuchstaben, Whitespace/
 * Punkte raus. So schlaegt „ss" genauso wie „SS." oder „Sß" an.
 */
final class InitialsBlocklist
{
    /**
     * Ist das Kuerzel verboten? Leere/kurze Eingaben schlagen NICHT
     * an — die pflegt der Controller mit einer eigenen Regel ab.
     */
    public static function isBlocked(?string $candidate): bool
    {
        if ($candidate === null) {
            return false;
        }
        $norm = self::normalize($candidate);
        if ($norm === '') {
            return false;
        }
        foreach (self::allEntries() as $entry) {
            if (self::normalize($entry) === $norm) {
                return true;
            }
        }

        return false;
    }

    /**
     * Drei Alternativ-Vorschlaege, wenn ein Kuerzel gesperrt ist
     * (Briefing § 2, Zustand-Karte 17B). Ableitung aus Vor+Nachname:
     *   1. Erste drei Buchstaben des Vornamens
     *   2. Vorname komplett auf max. 3 Zeichen (redundant zu 1 nur
     *      bei kurzen Vornamen, dann wird 3 punktierte Initialen)
     *   3. Punktierte Initialen (T.B.)
     * Doppelte werden entfernt, gesperrte uebersprungen — bleibt eine
     * Liste von 1–3 sauberen Vorschlaegen zurueck.
     *
     * @return array<int, string>
     */
    public static function suggestFor(string $firstName, string $lastName): array
    {
        $first = self::normalize($firstName);
        $last = self::normalize($lastName);

        $candidates = array_filter([
            mb_substr($first, 0, 3),
            mb_substr($first, 0, 2),
            trim(mb_substr($first, 0, 1).'.'.mb_substr($last, 0, 1).'.'),
        ]);

        $seen = [];
        $out = [];
        foreach ($candidates as $c) {
            $c = self::normalize($c);
            if ($c === '' || isset($seen[$c]) || self::isBlocked($c)) {
                continue;
            }
            $seen[$c] = true;
            $out[] = $c;
        }

        return array_values($out);
    }

    /**
     * Alle geladenen Sperr-Kuerzel (unnormalisiert).
     *
     * @return array<int, string>
     */
    private static function allEntries(): array
    {
        $config = (array) config('kuerzel_blocklist', []);
        $all = [];
        foreach ($config as $group) {
            foreach ((array) $group as $entry) {
                $all[] = (string) $entry;
            }
        }

        return $all;
    }

    private static function normalize(string $value): string
    {
        $value = mb_strtoupper($value);
        $value = strtr($value, [
            'Ä' => 'A', 'Ö' => 'O', 'Ü' => 'U', 'ß' => 'S', 'ẞ' => 'S',
        ]);
        // Nur Buchstaben und Ziffern behalten — Punkte, Whitespace und
        // Sonderzeichen zaehlen fuer die Sperre nicht.
        $value = preg_replace('/[^A-Z0-9]/u', '', $value) ?? '';

        return (string) $value;
    }
}
