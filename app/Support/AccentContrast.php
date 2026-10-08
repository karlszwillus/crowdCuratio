<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 */

declare(strict_types=1);

namespace App\Support;

/**
 * Textfarbe auf dem Projekt-Akzent (WCAG 2.x, 1.4.3).
 *
 * Vorher entschied eine Luminance-Schwelle (0.55, ohne sRGB-
 * Linearisierung) ueber weiss vs. dunkel — mittlere Akzente (Ocker,
 * Salbei, Rose) bekamen weissen Text unter 4.5:1. Jetzt: echter
 * WCAG-2.x-Kontrast gegen beide Kandidaten, der bessere gewinnt
 * (Gleichstand → dunkel).
 */
final class AccentContrast
{
    public const LIGHT = '#ffffff';

    public const DARK = '#16140f';

    public const AA_NORMAL = 4.5;

    /**
     * Textfarbe mit dem hoeheren Kontrast zum Akzent.
     */
    public static function onColor(string $accentHex): string
    {
        return self::ratio($accentHex, self::DARK) >= self::ratio($accentHex, self::LIGHT)
            ? self::DARK
            : self::LIGHT;
    }

    /**
     * Bestes erreichbares Kontrastverhaeltnis (mit weiss oder dunkel).
     */
    public static function bestRatio(string $accentHex): float
    {
        return max(self::ratio($accentHex, self::DARK), self::ratio($accentHex, self::LIGHT));
    }

    public static function passesAa(string $accentHex): bool
    {
        return self::bestRatio($accentHex) >= self::AA_NORMAL;
    }

    public static function ratio(string $hexA, string $hexB): float
    {
        $la = self::luminance($hexA);
        $lb = self::luminance($hexB);

        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    /**
     * Relative Luminance nach WCAG 2.x (sRGB linearisiert).
     */
    public static function luminance(string $hex): float
    {
        [$r, $g, $b] = array_map(
            static function (int $channel): float {
                $c = $channel / 255;

                return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
            },
            self::rgb($hex),
        );

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private static function rgb(string $hex): array
    {
        $hex = ltrim(trim($hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (! preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            throw new \InvalidArgumentException("Keine gueltige Hex-Farbe: #{$hex}");
        }

        return [(int) hexdec(substr($hex, 0, 2)), (int) hexdec(substr($hex, 2, 2)), (int) hexdec(substr($hex, 4, 2))];
    }
}
