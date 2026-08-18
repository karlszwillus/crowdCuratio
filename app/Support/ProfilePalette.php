<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 */

declare(strict_types=1);

namespace App\Support;

/**
 * Phase 5ac.1: Sechs Token-Werte fuer die Kuerzel-Farbe im Profil
 * (Briefing § 2). Whitelist, damit der Controller nichts anderes
 * annimmt und das Rendering deterministisch bleibt.
 */
final class ProfilePalette
{
    /** @var array<int, string> */
    public const TOKENS = [
        'primary',
        'ink-900',
        'success',
        'info',
        'warning',
        'ink-500',
    ];

    /**
     * Deterministischer Default aus dem Namen: hashe Vor+Nachname zu
     * einem Index in die Whitelist. Gibt jedem Kuerzel eine stabile
     * Farbe ohne dass wir sie persistieren muessen.
     */
    public static function defaultFor(string $firstName, string $lastName): string
    {
        $seed = mb_strtolower(trim($firstName.$lastName));
        if ($seed === '') {
            return self::TOKENS[0];
        }

        return self::TOKENS[crc32($seed) % count(self::TOKENS)];
    }
}
