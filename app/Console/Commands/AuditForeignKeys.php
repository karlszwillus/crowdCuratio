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

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Phase 2 / E.3, ADR-0018.
 *
 * Listet Orphan-Foreign-Keys auf bekannten Tabellen, die unter der
 * MyISAM-Historie (ADR-0010) entstehen konnten. MyISAM hat
 * FK-Constraints stillschweigend verworfen — nach dem InnoDB-Switch
 * können dort dangling references zurückgeblieben sein, die
 * spätestens beim utf8mb4-Konvertierungs-Lauf (ADR-0018) Probleme
 * machen würden.
 *
 * Schritte:
 * - php artisan db:audit-fk
 *     Read-only-Lauf. Markdown-Tabelle mit gefundenen Orphans.
 *     Exit-Code 1, wenn Orphans gefunden, sonst 0.
 *
 * - php artisan db:audit-fk --fix --confirm
 *     Setzt Orphan-Spalten auf NULL. Schreibt vorher ein
 *     JSON-Protokoll der Vorher-Werte in
 *     storage/logs/fk-audit-fix-YYYYMMDD-HHmmss.json, damit ein
 *     Rollback per Hand möglich ist. --confirm ist Pflicht;
 *     ohne den Schalter werden keine Daten verändert.
 *
 * Erweiterbar: weitere Tabellen können in $checks ergänzt werden.
 * Die Liste hier bildet den Stand der bekannten FK-Schäden ab
 * (texts.origin / texts.copyright, beide → sources.id).
 */
class AuditForeignKeys extends Command
{
    protected $signature = 'db:audit-fk
        {--fix : Setzt Orphan-Spalten auf NULL (zerstörend; braucht --confirm)}
        {--confirm : Pflicht für --fix; verhindert versehentliche Datenmutation}';

    protected $description = 'Listet orphan foreign keys auf bekannten Tabellen und kann sie optional auf NULL setzen.';

    /**
     * @var list<array{table:string,column:string,references_table:string,references_column:string}>
     */
    protected array $checks = [
        [
            'table' => 'texts',
            'column' => 'origin',
            'references_table' => 'sources',
            'references_column' => 'id',
        ],
        [
            'table' => 'texts',
            'column' => 'copyright',
            'references_table' => 'sources',
            'references_column' => 'id',
        ],
    ];

    public function handle(): int
    {
        $fix = (bool) $this->option('fix');
        $confirmed = (bool) $this->option('confirm');

        if ($fix && ! $confirmed) {
            $this->error('--fix erfordert zusätzlich --confirm. Lauf ohne --fix für reinen Audit.');

            return self::FAILURE;
        }

        $findings = $this->collectFindings();

        if ($findings === []) {
            $this->info('Keine Orphan-Foreign-Keys gefunden. Alles sauber.');

            return self::SUCCESS;
        }

        $this->renderMarkdown($findings);

        if (! $fix) {
            $this->warn('Lauf mit --fix --confirm, um Orphans auf NULL zu setzen.');

            return self::FAILURE;
        }

        $this->applyFix($findings);

        return self::SUCCESS;
    }

    /**
     * @return list<array{table:string,column:string,references_table:string,references_column:string,row_id:int,orphan_value:int|string}>
     */
    protected function collectFindings(): array
    {
        $findings = [];

        foreach ($this->checks as $check) {
            $orphans = DB::table($check['table'])
                ->whereNotNull($check['column'])
                ->whereNotIn(
                    $check['column'],
                    DB::table($check['references_table'])->select($check['references_column'])
                )
                ->select(['id', $check['column']])
                ->get();

            foreach ($orphans as $orphan) {
                $findings[] = [
                    'table' => $check['table'],
                    'column' => $check['column'],
                    'references_table' => $check['references_table'],
                    'references_column' => $check['references_column'],
                    'row_id' => (int) $orphan->id,
                    'orphan_value' => $orphan->{$check['column']},
                ];
            }
        }

        return $findings;
    }

    /**
     * @param  list<array<string,mixed>>  $findings
     */
    protected function renderMarkdown(array $findings): void
    {
        $this->line('');
        $this->line('| Tabelle | Spalte | Row ID | Orphan-Wert | Referenz |');
        $this->line('|---------|--------|-------:|------------:|----------|');

        foreach ($findings as $f) {
            $this->line(sprintf(
                '| %s | %s | %d | %s | %s.%s |',
                $f['table'],
                $f['column'],
                $f['row_id'],
                (string) $f['orphan_value'],
                $f['references_table'],
                $f['references_column'],
            ));
        }

        $this->line('');
        $this->line(sprintf('%d Orphan(s) gefunden.', count($findings)));
    }

    /**
     * @param  list<array<string,mixed>>  $findings
     */
    protected function applyFix(array $findings): void
    {
        $protocol = [
            'timestamp' => now()->toIso8601String(),
            'database' => DB::connection()->getDatabaseName(),
            'fixes' => $findings,
        ];

        $logDir = storage_path('logs');
        if (! File::isDirectory($logDir)) {
            File::makeDirectory($logDir, 0o755, true);
        }

        $logFile = sprintf(
            '%s/fk-audit-fix-%s.json',
            $logDir,
            now()->format('Ymd-His')
        );

        File::put(
            $logFile,
            json_encode($protocol, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        $this->line(sprintf('Protokoll geschrieben: %s', $logFile));

        DB::transaction(function () use ($findings): void {
            foreach ($findings as $f) {
                DB::table($f['table'])
                    ->where('id', $f['row_id'])
                    ->update([$f['column'] => null]);
            }
        });

        $this->info(sprintf('%d Orphan(s) auf NULL gesetzt.', count($findings)));
    }
}
