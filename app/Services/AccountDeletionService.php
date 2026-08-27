<?php

/**
crowdCuratio - Curating together virtually
Copyright (C)2026 - berlinHistory e.V.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

See LICENSE.
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * B2 (2026-08-21) — DSGVO-Baseline: Konto-Loeschung mit 30-Tage-Frist.
 *
 * Drei operative Punkte:
 *
 * 1. Schedule — der User meldet die Loeschung an, ab jetzt laeuft
 *    die Grace-Period (30 Tage). Owner-Konten muessen ihre Projekte
 *    an einen Nachfolger uebertragen; ohne Uebergabe wird geblockt,
 *    damit die Projekte nicht Owner-los werden.
 * 2. Cancel — User meldet sich innerhalb der Frist wieder an und
 *    hebt die geplante Loeschung auf.
 * 3. Purge — Cron schiebt abgelaufene Konten in den SoftDeletes-
 *    Scope. Die endgueltige Zeilen-Loeschung folgt einer eigenen
 *    laengeren Retention-Politik (siehe ADR-0009-Ausklang).
 */
final class AccountDeletionService
{
    /**
     * Meldet die Konto-Loeschung an. Verlangt eine Owner-Uebergabe
     * fuer jedes Projekt, dessen Owner der User ist.
     *
     * @param  array<int, int>  $projectHandovers  Map project_id => new_owner_user_id
     *
     * @throws RuntimeException wenn ein Owner-Projekt keinen Nachfolger hat.
     */
    public function schedule(User $user, ?string $reason, array $projectHandovers): void
    {
        // Idempotenz-Guard: eine bereits angemeldete Loeschung darf nicht
        // durch einen erneuten Schedule verlaengert werden — sonst haetten
        // Nutzer:innen einen Missbrauchs-Vektor, die Grace-Period beliebig
        // zu verschieben. Wer verlaengern will, muss zuerst cancel() rufen.
        if ($user->isScheduledForDeletion()) {
            return;
        }

        DB::transaction(function () use ($user, $reason, $projectHandovers): void {
            $ownedProjects = Project::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->get(['id']);

            foreach ($ownedProjects as $project) {
                $newOwnerId = $projectHandovers[$project->id] ?? null;
                if ($newOwnerId === null) {
                    throw new RuntimeException(
                        "Projekt-Uebergabe fehlt fuer project_id={$project->id}."
                    );
                }
                if ((int) $newOwnerId === (int) $user->id) {
                    throw new RuntimeException(
                        "Neuer Owner darf nicht der loeschende User selbst sein (project_id={$project->id})."
                    );
                }
                Project::query()
                    ->where('id', $project->id)
                    ->update(['user_id' => (int) $newOwnerId]);
            }

            $user->deletion_scheduled_at = now();
            $user->deletion_reason = $reason;
            $user->save();

            Log::channel(config('logging.default'))->info('account.deletion.scheduled', [
                'user_id' => $user->id,
                'scheduled_at' => $user->deletion_scheduled_at?->toIso8601String(),
                'reason' => $reason,
                'handovers' => array_map(static fn ($v) => (int) $v, $projectHandovers),
            ]);
        });
    }

    /**
     * Hebt eine geplante Konto-Loeschung wieder auf. Idempotent —
     * kein Fehler, wenn keine Loeschung angemeldet ist.
     */
    public function cancel(User $user): void
    {
        if (! $user->isScheduledForDeletion()) {
            return;
        }
        $scheduledAt = $user->deletion_scheduled_at?->toIso8601String();
        $user->deletion_scheduled_at = null;
        $user->deletion_reason = null;
        $user->save();

        Log::channel(config('logging.default'))->info('account.deletion.cancelled', [
            'user_id' => $user->id,
            'was_scheduled_at' => $scheduledAt,
        ]);
    }

    /**
     * Cron-Callback: alle Konten, deren Grace-Period abgelaufen ist,
     * werden soft-geloescht. Der Aufrufer bekommt die Anzahl der
     * behandelten Konten fuer das Log.
     */
    public function purgeExpired(): int
    {
        $cutoff = now()->subDays(User::DELETION_GRACE_DAYS);
        $expired = User::query()
            ->whereNotNull('deletion_scheduled_at')
            ->where('deletion_scheduled_at', '<=', $cutoff)
            ->get();

        $purged = 0;

        foreach ($expired as $user) {
            // Owner-Projekte muessen zum Zeitpunkt des Schedule bereits
            // uebertragen worden sein (schedule() erzwingt das). Zwischen
            // Ownership-Check und SoftDelete koennte ein Admin in dem
            // Fensterchen ein Projekt zurueckuebertragen — deshalb pruefen
            // und loeschen wir innerhalb derselben Transaktion mit
            // lockForUpdate auf der User-Row.
            $purged += DB::transaction(function () use ($user): int {
                /** @var User|null $locked */
                $locked = User::query()
                    ->whereKey($user->id)
                    ->lockForUpdate()
                    ->first();
                if (! $locked instanceof User || ! $locked->isScheduledForDeletion()) {
                    return 0;
                }
                $stillOwning = Project::query()
                    ->where('user_id', $locked->id)
                    ->exists();
                if ($stillOwning) {
                    Log::channel(config('logging.default'))->warning('account.deletion.purge_skipped_owner', [
                        'user_id' => $locked->id,
                        'reason' => 'still_owning_projects',
                    ]);

                    return 0;
                }

                $scheduledAt = $locked->deletion_scheduled_at?->toIso8601String();
                $reason = $locked->deletion_reason;
                $locked->delete(); // SoftDelete

                Log::channel(config('logging.default'))->info('account.deletion.purged', [
                    'user_id' => $locked->id,
                    'scheduled_at' => $scheduledAt,
                    'reason' => $reason,
                ]);

                return 1;
            });
        }

        return $purged;
    }

    /**
     * Findet Kandidaten fuer die Owner-Uebergabe: alle Nutzer:innen,
     * die aktiv sind, nicht gerade selbst geloescht werden und nicht
     * das aktuelle User-Konto sind. Wird von der Profil-Sicht fuer
     * die Empfaenger-Auswahl gebraucht.
     *
     * @return Collection<int, User>
     */
    public function candidatesForHandover(User $excluding): Collection
    {
        return User::query()
            ->whereNull('deletion_scheduled_at')
            ->where('id', '!=', $excluding->id)
            ->orderBy('name')
            ->orderBy('last_name')
            ->get(['id', 'name', 'last_name', 'email']);
    }
}
