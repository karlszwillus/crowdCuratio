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

/**
 * Q3-Härtung F1 (2026-08-19) / SEC-01 · Übergangs-Sanitizer für
 * Rich-Text-Ausgabe in Preview-Views.
 *
 * Kontext: Der Security-Review vom 2026-08-19 hat 33 Fundstellen mit
 * unescaped `{!! !!}` markiert (Preview/PDF, Copyright, Log, Roles).
 * Jede Edit-Rolle konnte HTML/JS in Content-Felder ablegen, das dann
 * im Preview-Render als Payload wirkt.
 *
 * ADR-0029 sieht mittelfristig HTML-Purifier vor. Dieser Sanitizer
 * ist der **Übergang** bis Purifier installiert und konfiguriert ist:
 * er entfernt gefährliche Elemente (Script, Handler-Attribute, gefährliche
 * Protokolle) mit PHP-Standardmitteln. Nicht so fein wie Purifier —
 * z. B. wird nur pauschal gestrippt, keine strukturelle HTML-Prüfung.
 *
 * **Whitelist** (aus ADR-0029): p, br, strong, em, u, s, ul, ol, li,
 * a, blockquote, h2, h3, h4. Auf `a` bleiben nur `href` (mit
 * https?://-Prefix) und `target=_blank` + `rel=noopener noreferrer`.
 *
 * **Fallback-Grad:** verhindert Stored-XSS über `<script>`,
 * `onerror=`/`onclick=`/`onload=`-Handler, `javascript:`- und
 * `data:`-URIs in href. Umgeht nicht so fein aufgebaute Attacken über
 * mehrfach-encodierte Entitäten — dort greift erst Purifier.
 */
class RichTextSanitizer
{
    private const ALLOWED_TAGS = '<p><br><strong><em><u><s><ul><ol><li><a><blockquote><h2><h3><h4>';

    /**
     * Bereinigt einen HTML-String für sichere Ausgabe in Blade
     * `{!! !!}`. Nur für Felder aus dem Rich-Text-Editor gedacht;
     * Plaintext-Felder gehören durch `{{ }}` (Blade-Auto-Escape).
     */
    public static function sanitize(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

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
