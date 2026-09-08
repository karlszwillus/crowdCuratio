<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

See LICENSE.
 */

declare(strict_types=1);

namespace App\Policies;

use App\Models\DataFactBlock;
use App\Models\User;
use App\Support\PermissionName;

/**
 * Q4-Etappe 4 / G1 (2026-09-08): Authorization für Fakten-Blöcke.
 * Analog zu QuoteBlockPolicy — project-scoped über
 * OwnerScopedPolicy und $block->project() (via MediaContent →
 * Entry → Chapter → Project).
 */
class DataFactBlockPolicy extends OwnerScopedPolicy
{
    public function view(User $user, DataFactBlock $block): bool
    {
        return $this->checkViaProject($user, $block->project(), PermissionName::VIEW);
    }

    public function update(User $user, DataFactBlock $block): bool
    {
        return $this->checkViaProject($user, $block->project(), PermissionName::EDIT);
    }

    public function delete(User $user, DataFactBlock $block): bool
    {
        return $this->checkViaProject($user, $block->project(), PermissionName::DELETE);
    }

    public function comment(User $user, DataFactBlock $block): bool
    {
        return $this->checkViaProject($user, $block->project(), PermissionName::COMMENT);
    }
}
