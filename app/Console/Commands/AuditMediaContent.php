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

use App\Models\Audiovisual;
use App\Models\Entry;
use App\Models\Gallery;
use App\Models\Image;
use App\Models\Text;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Phase 4 / Block E.7b, ADR-0022, in Welle 4e umgeschrieben.
 *
 * Read-only-Audit der `media_content`-Pivot-Tabelle. Nach dem
 * Spalten-Drop läuft der Audit auf den neuen content_*/parent_*-
 * Spalten: Type-Counts pro `content_type`, Orphan-Check der
 * `content_id` gegen das jeweilige Content-Modell, Parent-Probe
 * der `parent_id` gegen Entry.
 *
 * Schritte:
 *   php artisan db:audit-media-content
 *     Read-only. Markdown-Tabellen für: Type-Counts, Orphans pro
 *     Content-Modell, Cross-Match-Probe gegen Entry.
 *     Exit-Code 0 unabhängig vom Befund — der Command ist Diagnose,
 *     nicht Gate.
 */
class AuditMediaContent extends Command
{
    protected $signature = 'db:audit-media-content';

    protected $description = 'Read-only-Audit der media_content-Pivot-Tabelle vor dem Schema-Refactor (ADR-0022).';

    public function handle(): int
    {
        if (! DB::getSchemaBuilder()->hasTable('media_content')) {
            $this->error('Tabelle media_content fehlt — Migrationen nicht durchgelaufen?');

            return self::FAILURE;
        }

        $total = DB::table('media_content')->count();
        $this->info("media_content Total-Rows: {$total}");
        $this->newLine();

        $this->renderTypeCounts();
        $this->newLine();

        $this->renderContentOrphans();
        $this->newLine();

        $this->renderParentProbe();
        $this->newLine();

        $this->renderRecommendation();

        return self::SUCCESS;
    }

    /**
     * Zählt rows pro `content_type`-Wert. Erwartet:
     *   Text::class         → einer pro saveText-Create
     *   Audiovisual::class  → einer pro save-Audiovisual-Create
     *   Gallery::class      → einer pro saveGallery-Create (seit Welle 4d
     *                         sauber; vor 4d hieß die alte Tag-Spalte
     *                         für Galleries fälschlich Image::class).
     */
    protected function renderTypeCounts(): void
    {
        $this->line('## Verteilung content_type');
        $this->newLine();

        $rows = DB::table('media_content')
            ->select('content_type', DB::raw('COUNT(*) as count'))
            ->groupBy('content_type')
            ->orderByDesc('count')
            ->get();

        if ($rows->isEmpty()) {
            $this->line('| (keine Rows) |');

            return;
        }

        $this->line('| content_type                  | count |');
        $this->line('|-------------------------------|------:|');
        foreach ($rows as $row) {
            $type = $row->content_type ?? '(null)';
            $this->line(sprintf('| %-29s | %5d |', $type, $row->count));
        }
    }

    /**
     * Für jede Content-Klasse: zählt content_ids, die NICHT auf
     * eine existierende Row im jeweiligen Content-Modell zeigen. Diese
     * sind Migrationskandidaten für Cleanup oder zeigen Soft-Deletes,
     * die der Pivot nicht mitgeführt hat.
     */
    protected function renderContentOrphans(): void
    {
        $this->line('## Cross-Check: content_id gegen Content-Modell');
        $this->newLine();

        $contentModels = [
            Text::class => ['table' => 'texts', 'column' => 'id'],
            Audiovisual::class => ['table' => 'audiovisuals', 'column' => 'id'],
            Gallery::class => ['table' => 'galleries', 'column' => 'id'],
            Image::class => ['table' => 'images', 'column' => 'id'],
        ];

        $this->line('| content_type       | mapped to table | matched ids | orphan ids |');
        $this->line('|--------------------|-----------------|------------:|-----------:|');

        foreach ($contentModels as $class => $target) {
            $taggedIds = DB::table('media_content')
                ->where('content_type', $class)
                ->pluck('content_id');

            $matched = 0;
            $orphan = 0;
            if ($taggedIds->isNotEmpty()) {
                $existingIds = DB::table($target['table'])
                    ->whereIn($target['column'], $taggedIds)
                    ->pluck($target['column'])
                    ->all();

                foreach ($taggedIds as $id) {
                    if (in_array($id, $existingIds, true)) {
                        $matched++;
                    } else {
                        $orphan++;
                    }
                }
            }

            $this->line(sprintf(
                '| %-18s | %-15s | %11d | %10d |',
                class_basename($class),
                $target['table'],
                $matched,
                $orphan,
            ));
        }

        $this->newLine();
        $this->comment(
            'Hinweis: nach Welle 4e ist der Pivot konsistent. '.
            'Orphans in einer Spalte deuten auf Soft-Deletes des Content-Modells, '.
            'die der Pivot nicht mitgeführt hat, oder auf manuelle DB-Drift.'
        );
    }

    /**
     * Für jede Row prüfen, ob `parent_id` auf eine existierende
     * Entry-Row zeigt. Heute ist Entry der einzige Parent-Typ in
     * der Praxis (laut Service-Code); diese Probe pinnt die Annahme.
     */
    protected function renderParentProbe(): void
    {
        $this->line('## Parent-Probe: parent_id gegen Entry');
        $this->newLine();

        $total = DB::table('media_content')->count();
        if ($total === 0) {
            $this->line('(keine Rows zu prüfen)');

            return;
        }

        $matched = DB::table('media_content')
            ->whereIn(
                'parent_id',
                DB::table((new Entry)->getTable())->select('id')
            )
            ->count();

        // $total ist hier per Early-Return oben garantiert > 0.
        $orphan = $total - $matched;
        $rate = round($matched / $total * 100, 1);

        $this->line('| total | matched gegen Entry | orphan | match-rate |');
        $this->line('|------:|--------------------:|-------:|-----------:|');
        $this->line(sprintf('| %5d | %19d | %6d | %9.1f%% |', $total, $matched, $orphan, $rate));

        $this->newLine();
        if ($orphan === 0) {
            $this->info('Alle parent_id zeigen auf eine Entry-Row.');
        } else {
            $this->warn("{$orphan} Rows zeigen NICHT auf eine Entry-Row. Drift im Pivot — manuell prüfen.");
        }
    }

    protected function renderRecommendation(): void
    {
        $this->line('## Status');
        $this->newLine();
        $this->line('media_content läuft seit Welle 4e auf den neuen content_*/parent_*-Spalten.');
        $this->line('Bei Drift in den obigen Tabellen entweder über `db:migrate-media-content --apply`');
        $this->line('rüber-mappen (wenn alte Spalten noch im Backup vorhanden) oder die einzelnen');
        $this->line('Rows manuell prüfen.');
    }
}
