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

namespace App\Console\Commands;

use App\Services\AccountDeletionService;
use Illuminate\Console\Command;

/**
 * B2 (2026-08-21) / DSGVO: Cron-Command fuer die Konto-Loeschung nach
 * Ablauf der 30-Tage-Grace-Period. Empfohlener Aufruf: taeglich
 * `php artisan users:purge-scheduled` — deployt als Deploy-Hook oder
 * ueber den Container-Scheduler.
 *
 * Fehler-Handling: Konten mit noch nicht uebertragenen Owner-Projekten
 * werden vom Service uebersprungen (siehe AccountDeletionService::purgeExpired)
 * und im Log vermerkt, damit ein Admin nachziehen kann.
 */
class PurgeScheduledUsersCommand extends Command
{
    protected $signature = 'users:purge-scheduled {--dry-run : Nur zaehlen, nicht loeschen}';

    protected $description = 'Loescht Konten, deren 30-Tage-DSGVO-Frist abgelaufen ist (SoftDelete).';

    public function handle(AccountDeletionService $service): int
    {
        if ($this->option('dry-run')) {
            $count = \App\Models\User::query()
                ->whereNotNull('deletion_scheduled_at')
                ->where('deletion_scheduled_at', '<=', now()->subDays(\App\Models\User::DELETION_GRACE_DAYS))
                ->count();
            $this->info("Dry-Run: {$count} Konto(s) waeren jetzt gepurgt worden.");

            return self::SUCCESS;
        }

        $purged = $service->purgeExpired();
        $this->info("{$purged} Konto(s) gepurgt (SoftDelete).");

        return self::SUCCESS;
    }
}
