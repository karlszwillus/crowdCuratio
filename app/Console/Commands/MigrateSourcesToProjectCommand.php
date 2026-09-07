<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2022, 2026 - berlinHistory e.V.

See LICENSE.
 */

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\SourceMigrationService;
use Illuminate\Console\Command;

/**
 * Q4-Etappe 3 / C0-8b (2026-09-07): Migrations-Assistent fuer die
 * projekt-scoped Source-Umstellung. Ruft `SourceMigrationService`
 * mit einem einzelnen Projekt und meldet den Report an die Console.
 *
 * Aufruf (immer pro Projekt einzeln):
 *   php artisan sources:migrate --project=42 --dry-run
 *   php artisan sources:migrate --project=42
 *
 * Ohne `--project` bricht der Command ab — die Umstellung ist zu
 * heikel fuer Bulk-Runs, jeder Projekt-Zug will Kontroll-Blick.
 */
class MigrateSourcesToProjectCommand extends Command
{
    protected $signature = 'sources:migrate
        {--project= : ID des Projekts, dessen Alt-Sources projekt-scoped werden sollen. Pflicht.}
        {--dry-run : Nur analysieren, keine DB-Writes.}';

    protected $description = 'C0-8b · Fuehrt Alt-Sources (project_id=NULL) eines Projekts in projekt-scoped Zeilen ueber.';

    public function handle(SourceMigrationService $service): int
    {
        $projectId = $this->option('project');
        if ($projectId === null || $projectId === '') {
            $this->error('--project ist Pflicht. Aufruf pro Projekt einzeln.');

            return self::FAILURE;
        }

        $projectId = (int) $projectId;
        $project = Project::find($projectId);
        if ($project === null) {
            $this->error("Projekt {$projectId} nicht gefunden.");

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        $report = $service->runFor($projectId, $dryRun);

        $this->newLine();
        $this->info('=== sources:migrate Report ===');
        $this->line("Projekt:          {$project->id} ({$project->name})");
        $this->line('Modus:            '.($dryRun ? 'DRY-RUN (keine DB-Writes)' : 'COMMIT'));
        $this->line("Run-Key:          {$report['run_key']}");
        $this->line("Kandidaten:       {$report['candidates']} Alt-Sources referenziert");
        $this->line("Dedup-Gruppen:    {$report['groups']} nach Normalisierung");
        $this->line("Freitext-Hinweise: {$report['freetext_candidates']}");
        $this->line("AV-Backfill:      {$report['av_pending']} Audiovisual-Rows offen"
            .($dryRun ? '' : ' · '.$report['av_backfilled'].' erledigt'));
        $this->newLine();
        $this->line('kind-Vorschlaege:');
        foreach ($report['kind_suggestions'] as $kind => $count) {
            $this->line("  {$kind}: {$count}");
        }

        if (! $dryRun) {
            $this->newLine();
            $this->info("Migriert:         {$report['migrated']} neue project-scoped Rows");
            $this->line('Backup in `sources_backup`, Rollback ueber run_key: '.$report['run_key']);
        } else {
            $this->newLine();
            $this->comment('Naechster Schritt: gleicher Command ohne --dry-run fuer den Commit.');
        }

        return self::SUCCESS;
    }
}
