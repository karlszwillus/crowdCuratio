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

namespace App\Casts;

use App\Support\CommentStatus;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Cast fuer die comments.status-Spalte.
 *
 * Wrappt CommentStatus toleranter als Laravels Standard-Enum-Cast
 * (der strikt ::from() aufruft und bei jedem Nicht-Backing-Wert
 * eine ValueError wirft):
 *
 *  - Legacy-Integer 1..5 werden ueber CommentStatus::fromLegacyInt()
 *    auf den neuen String-Enum gemappt (Wartungsfenster-Robustheit).
 *  - Numerische Strings ('1','2',...) werden gleich behandelt — MySQL
 *    liefert integer-Werte gelegentlich als string zurueck.
 *  - Unbekannte oder null-Werte fallen still auf OPEN — verhindert
 *    500er auf halbmigrierten Datenstaenden.
 *
 * Schreibpfad normalisiert immer auf den String-Backing-Wert, damit
 * die Datenbank nach dem ersten Save konsolidiert ist.
 */
class CommentStatusCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): CommentStatus
    {
        return $this->normalize($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        return $this->normalize($value)->value;
    }

    private function normalize(mixed $value): CommentStatus
    {
        if ($value instanceof CommentStatus) {
            return $value;
        }

        if ($value === null || $value === '') {
            return CommentStatus::OPEN;
        }

        if (is_int($value)) {
            return CommentStatus::fromLegacyInt($value);
        }

        if (is_string($value)) {
            if (ctype_digit($value)) {
                return CommentStatus::fromLegacyInt((int) $value);
            }

            return CommentStatus::tryFrom($value) ?? CommentStatus::OPEN;
        }

        return CommentStatus::OPEN;
    }
}
