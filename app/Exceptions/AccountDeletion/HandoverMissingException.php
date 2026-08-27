<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

See LICENSE.
 */

declare(strict_types=1);

namespace App\Exceptions\AccountDeletion;

use RuntimeException;

/**
 * B2 (2026-08-27) — Fach-Signal: waehrend der Konto-Loeschung wurde
 * ein Owner-Projekt ohne Nachfolger entdeckt (typischerweise weil in
 * der Zwischenzeit ein neues Projekt angelegt wurde). Der Controller
 * mappt die Exception auf einen Validierungs-Fehler am `handovers`-
 * Feld.
 *
 * Bewusst als eigene Klasse und nicht als generischer RuntimeException —
 * Log-Filter, Sentry-Grouping und Test-Assertions bleiben so
 * spezifisch.
 */
final class HandoverMissingException extends RuntimeException
{
    public function __construct(
        public readonly int $projectId,
        public readonly ?int $attemptedNewOwnerId = null,
        string $message = '',
    ) {
        parent::__construct(
            $message !== '' ? $message : "Projekt-Uebergabe fehlt fuer project_id={$projectId}.",
        );
    }
}
