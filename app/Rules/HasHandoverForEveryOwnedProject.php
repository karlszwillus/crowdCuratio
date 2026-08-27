<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

See LICENSE.
 */

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * B2 (2026-08-26) — DSGVO: prueft, dass die Handover-Map jeden
 * owner-gehaltenen Projekt-ID einschliesst. Ohne Nachfolger dropt der
 * Schedule, weil das Projekt sonst orphan werden wuerde.
 *
 * Konstruktion mit der Liste der aktuell owner-gehaltenen Projekt-IDs.
 * Die Rule laeuft gegen den `handovers`-Array-Wert im Request und macht
 * den Cross-Check gegen diese Erwartung.
 */
final class HasHandoverForEveryOwnedProject implements ValidationRule
{
    /**
     * @param  array<int, int>  $ownedProjectIds
     */
    public function __construct(private readonly array $ownedProjectIds) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $handoverKeys = [];
        if (is_array($value)) {
            foreach (array_keys($value) as $projectId) {
                $handoverKeys[] = (int) $projectId;
            }
        }

        $missing = array_values(array_diff($this->ownedProjectIds, $handoverKeys));
        if ($missing === []) {
            return;
        }

        $fail(__('profile_deletion_handover_missing'));
    }
}
