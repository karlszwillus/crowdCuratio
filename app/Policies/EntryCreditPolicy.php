<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

See LICENSE.
 */

declare(strict_types=1);

namespace App\Policies;

use App\Models\Entry;
use App\Models\EntryCredit;
use App\Models\Project;
use App\Models\User;
use App\Support\PermissionName;

/**
 * Q4-Etappe 5 / G-Fund-5 (2026-09-09): Authorization für Credits.
 * Läuft project-scoped über das Entry — analog zu QuoteBlockPolicy.
 */
class EntryCreditPolicy extends OwnerScopedPolicy
{
    public function view(User $user, EntryCredit $credit): bool
    {
        return $this->checkViaProject($user, $this->projectOf($credit), PermissionName::VIEW);
    }

    public function update(User $user, EntryCredit $credit): bool
    {
        return $this->checkViaProject($user, $this->projectOf($credit), PermissionName::EDIT);
    }

    public function delete(User $user, EntryCredit $credit): bool
    {
        return $this->checkViaProject($user, $this->projectOf($credit), PermissionName::DELETE);
    }

    /**
     * Löst das Projekt über den Entry auf. PHPStan sieht am
     * BelongsTo nur `Model`, deshalb hier ein expliziter Cast auf
     * Entry, um den `project()`-Method-Call type-sauber zu halten.
     */
    private function projectOf(EntryCredit $credit): ?Project
    {
        /** @var Entry|null $entry */
        $entry = $credit->entry;

        return $entry?->project();
    }
}
