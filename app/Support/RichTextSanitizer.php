<?php

/*
crowdCuratio - Curating together virtually
Copyright (C)2026 - berlinHistory e.V.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program in the file LICENSE.

If not, see <https://www.gnu.org/licenses/>.
*/

namespace App\Support;

use Mews\Purifier\Facades\Purifier;
use Throwable;

/**
 * Rich-Text-Sanitizer fuer Ausgabe in Blade `{!! !!}` (via @rich).
 *
 * Q3-Politur G10 (2026-08-20) / ADR-0029: primaer laeuft alles ueber
 * HTML-Purifier mit dem `rich`-Preset (config/purifier.php). Der
 * frueher hier aktive strip_tags-Sanitizer bleibt als **Fallback**
 * fuer den Fall, dass die Purifier-Facade waehrend eines Test-Setups
 * oder in einer noch nicht `composer install`-ten Umgebung nicht
 * verfuegbar ist. Im Regelbetrieb kommt der Fallback nicht zum Zug.
 *
 * **Whitelist** (ADR-0029): p, br, strong, em, u, s, ul, ol, li, a,
 * blockquote, h2, h3, h4. `a` traegt href/target/rel; alle anderen
 * Attribute und Inline-Styles werden entfernt. `javascript:`-,
 * `data:`- und `vbscript:`-URIs werden neutralisiert.
 *
 * Storage bleibt roh (Quill schreibt HTML in die DB); Bereinigung
 * ausschliesslich beim Render. Ein Save-Sanitizer wuerde Alt-Content
 * unangetastet lassen und die Persistenz undurchsichtig machen —
 * siehe ADR-0029 fuer die Diskussion.
 */
class RichTextSanitizer
{
    private const ALLOWED_TAGS = '<p><br><strong><em><u><s><ul><ol><li><a><blockquote><h2><h3><h4>';

    /**
     * Bereinigt einen HTML-String fuer sichere Ausgabe in Blade
     * `{!! !!}`. Primaerpfad: HTML-Purifier mit dem `rich`-Preset.
     * Fallback: interner strip_tags-basierter Filter, wenn Purifier
     * nicht verfuegbar (nur Bootstrap-Sonderfaelle).
     */
    public static function sanitize(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        // Primaerpfad: Purifier. Bei Facade-/Container-Problemen
        // fallen wir auf den strip_tags-Filter zurueck, damit
        // wenigstens die grobkoernige Bereinigung greift.
        try {
            return (string) Purifier::clean($html, 'rich');
        } catch (Throwable $e) {
            // Absichtlich stumm — der Fallback liefert weiterhin einen
            // sicher gefilterten Output. In Production waere das ein
            // signalfaehiger Zustand; wir loggen ihn hier NICHT, damit
            // der Log nicht bei jedem Render volllaeuft. Wenn Purifier
            // dauerhaft ausfaellt, faellt das in APM (fehlende
            // Reduktion, veraendertes Rendering) auf.
            return self::legacyStripTags($html);
        }
    }

    /**
     * Fallback aus Q3-Haertung F1: strip_tags + Attribute-Regex.
     * Nicht so fein wie Purifier, aber grob sicher gegen Stored-XSS.
     */
    private static function legacyStripTags(string $html): string
    {

        // 1. Whitelist-Filter: alles außer den erlaubten Tags weg.
        $sanitized = strip_tags($html, self::ALLOWED_TAGS);

        // 2. Handler-Attribute (`onclick=`, `onerror=`, …) auf verbleibenden
        //    Tags entfernen. `strip_tags` entfernt nur Tags, keine Attribute
        //    auf erlaubten Tags — deshalb muss dieser Schritt separat.
        $sanitized = (string) preg_replace(
            '/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i',
            '',
            $sanitized
        );

        // 3. Gefährliche URI-Schemes in href und src neutralisieren.
        //    `href="javascript:alert(1)"` → `href="#"`.
        $sanitized = (string) preg_replace_callback(
            '/(href|src)\s*=\s*("|\')(.*?)\2/i',
            function (array $m): string {
                $attr = $m[1];
                $quote = $m[2];
                $value = trim($m[3]);
                if (preg_match('/^(javascript|data|vbscript|file):/i', $value)) {
                    return $attr.'='.$quote.'#'.$quote;
                }

                return $m[0];
            },
            $sanitized
        );

        // 4. Style-Attribute auch entfernen — verhindert
        //    `style="background:url(javascript:…)"`-Angriffe.
        $sanitized = (string) preg_replace(
            '/\s+style\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i',
            '',
            $sanitized
        );

        return $sanitized;
    }
}
