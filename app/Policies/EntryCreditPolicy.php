<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

See LICENSE.
 */

declare(strict_types=1);

namespace App\Policies;

use App\Models\EntryCredit;
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
        return $this->checkViaProject($user, $credit->entry?->project(), PermissionName::VIEW);
    }

    public function update(User $user, EntryCredit $credit): bool
    {
        return $this->checkViaProject($user, $credit->entry?->project(), PermissionName::EDIT);
    }

    public function delete(User $user, EntryCredit $credit): bool
    {
        return $this->checkViaProject($user, $credit->entry?->project(), PermissionName::DELETE);
    }
}
