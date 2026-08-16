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
 * Kommentar-Status als Backed-Enum (Phase 5x.4).
 *
 * Vor 5x.4 lebte der Status als `integer`-Spalte in `comments` mit
 * fuenf Werten aus `config/project.php`: open(1), accepted(2),
 * decline(3), cancel(4), done(5). Design-Briefing v5 reduziert auf
 * vier semantische Status mit fester Farb-Zuordnung, `veraltet`
 * faellt weg.
 *
 * Farb-Mapping (Chip-bg / Chip-text, siehe BRIEFING-kommentare § 4):
 *   open         — info    (#eaf0fc / #2f5bd4)
 *   in_progress  — warning (#fbf1dd / #a26b16)
 *   resolved     — success (#e7f4ee / #16744a)
 *   rejected     — neutral (#eef0f2 / #4b5563)
 *
 * `--danger` bleibt fuer Loeschen reserviert — `rejected` ist eine
 * Entscheidung, kein Fehler.
 */
enum CommentStatus: string
{
    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case RESOLVED = 'resolved';
    case REJECTED = 'rejected';

    /**
     * Mapping der alten Integer-Werte auf die neuen String-Werte.
     * Wird von der Migration und vom Model-Cast (Rueckwaertskompat
     * fuer ungewanderte Datensaetze) genutzt.
     */
    public static function fromLegacyInt(?int $value): self
    {
        return match ($value) {
            1 => self::OPEN,
            2 => self::IN_PROGRESS,  // vorher accepted — jemand hat angenommen und arbeitet dran
            3 => self::REJECTED,     // vorher decline
            4 => self::REJECTED,     // vorher cancel   — auch bewusst nicht umgesetzt
            5 => self::RESOLVED,     // vorher done
            default => self::OPEN,   // null oder unbekannt — Default
        };
    }

    /**
     * Semantik-Token fuer die Chip-Darstellung.
     *
     * @return 'info' | 'warning' | 'success' | 'neutral'
     */
    public function tokenVariant(): string
    {
        return match ($this) {
            self::OPEN => 'info',
            self::IN_PROGRESS => 'warning',
            self::RESOLVED => 'success',
            self::REJECTED => 'neutral',
        };
    }

    /**
     * `resolved` und `rejected` verhalten sich in der UI gleich:
     * per Default ausgeblendet (BRIEFING-kommentare § 7).
     */
    public function isHiddenByDefault(): bool
    {
        return $this === self::RESOLVED || $this === self::REJECTED;
    }

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
