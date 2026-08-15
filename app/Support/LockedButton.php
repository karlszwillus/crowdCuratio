<?php

/**
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

declare(strict_types=1);

namespace App\Support;

/**
 * Runtime-Helper fuer die @disabledIf-Blade-Direktive
 * (Phase 5d.2, siehe AppServiceProvider::registerLockedButtonDirective()).
 *
 * Rendert das aria-disabled + title + is-disabled-Klassen-Set fuer
 * rohe <button>-Tags im Bestand. Wenn $condition false ist, schreibt
 * der Helper nichts — der Button bleibt offen.
 *
 * Kein Schloss-Icon in dieser Variante: die volle Locked-Behandlung
 * (Icon + Style + Alpine-Bindings) bleibt der x-ui.button-Komponente
 * mit :locked-Prop vorbehalten. @disabledIf ist der schnellste Weg,
 * ohne Legacy-Templates auf die Komponente umzuziehen.
 */
final class LockedButton
{
    /**
     * Baut den Attribut-String fuer den umgebenden Tag.
     *
     * @param  bool  $condition  Sperr-Bedingung (true = locked).
     * @param  string  $reason  Tooltip-Text; wird HTML-geescaped.
     * @return string aria-disabled + title + class-Fragment oder Leerstring.
     */
    public static function attributes(bool $condition, string $reason): string
    {
        if (! $condition) {
            return '';
        }

        // data-locked="1" statt is-disabled-Klasse: die Direktive kann
        // keinen class-String in einen bestehenden class="..."-Wert des
        // umgebenden Tags mergen (zwei class-Attribute in einem Element
        // ignoriert der HTML-Parser bis auf das erste). data-Attribut
        // ist eigenstaendig, CSS-Regel in app.css matcht beide Anker
        // (.is-disabled fuer die Komponente, [data-locked="1"] fuer
        // die Direktive) gleichwertig.
        return sprintf(
            'aria-disabled="true" title="%s" data-locked="1"',
            e($reason),
        );
    }
}
