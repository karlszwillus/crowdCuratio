<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

See LICENSE.
 */

declare(strict_types=1);

namespace App\Policies;

use App\Models\QuoteBlock;
use App\Models\User;
use App\Support\PermissionName;

/**
 * Q4-Etappe 4 / F1 (2026-09-08): Authorization für Zitat-Blöcke.
 * Analog zu TextPolicy — project-scoped über OwnerScopedPolicy und
 * $quote->project() (via MediaContent → Entry → Chapter → Project).
 */
class QuoteBlockPolicy extends OwnerScopedPolicy
{
    public function view(User $user, QuoteBlock $quote): bool
    {
        return $this->checkViaProject($user, $quote->project(), PermissionName::VIEW);
    }

    public function update(User $user, QuoteBlock $quote): bool
    {
        return $this->checkViaProject($user, $quote->project(), PermissionName::EDIT);
    }

    public function delete(User $user, QuoteBlock $quote): bool
    {
        return $this->checkViaProject($user, $quote->project(), PermissionName::DELETE);
    }

    public function comment(User $user, QuoteBlock $quote): bool
    {
        return $this->checkViaProject($user, $quote->project(), PermissionName::COMMENT);
    }
}
