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
 * Phase 4 / Block E.7b, ADR-0022.
 *
 * Read-only-Audit der `media_content`-Pivot-Tabelle. Vor dem
 * Schema-Refactor brauchen wir Klarheit über die historischen
 * Belegungen der `media_contentable_type`-Spalte — die ist
 * semantisch keine `morphTo`-Parent-Spalte, sondern eine
 * Content-Tag-Spalte mit teils inkonsistenten Werten:
 *
 *   - `Text::class`         (gesetzt von TextService::attachToEntry)
 *   - `Audiovisual::class`  (gesetzt von AudiovisualService::attachToEntry)
 *   - `Image::class`        (gesetzt von GalleryService::attachToEntry,
 *                            historisch — saveGallery hatte
 *                            'App\Models\Image' hartkodiert; matcht
 *                            den detachFromEntries-Zugriff nicht)
 *
 * Der Command zählt die tatsächlichen Werte pro Spalte und prüft,
 * ob `media_content_id` auf eine existierende Row im entsprechenden
 * Content-Modell zeigt. Die Empfehlung für die Migration (Welle 2)
 * basiert auf diesem Ist-Stand.
 *
 * Schritte:
 *   php artisan db:audit-media-content
 *     Read-only. Markdown-Tabellen für: Type-Counts pro `media_contentable_type`,
 *     Orphans pro vermutetem Content-Modell, Cross-Match-Probe gegen Entry.
 *     Exit-Code 0 unabhängig vom Befund — der Command ist Diagnose, nicht Gate.
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
     * Zählt rows pro `media_contentable_type`-Wert. Erwartet konkret:
     *   Text::class         → einer pro saveText-Create
     *   Audiovisual::class  → einer pro save-Audiovisual-Create
     *   Image::class        → in Wahrheit Gallery-Eintrag (historische
     *                         Belegung von GalleryService::attachToEntry)
     */
    protected function renderTypeCounts(): void
    {
        $this->line('## Verteilung media_contentable_type');
        $this->newLine();

        $rows = DB::table('media_content')
            ->select('media_contentable_type', DB::raw('COUNT(*) as count'))
            ->groupBy('media_contentable_type')
            ->orderByDesc('count')
            ->get();

        if ($rows->isEmpty()) {
            $this->line('| (keine Rows) |');

            return;
        }

        $this->line('| media_contentable_type        | count |');
        $this->line('|-------------------------------|------:|');
        foreach ($rows as $row) {
            $type = $row->media_contentable_type ?? '(null)';
            $this->line(sprintf('| %-29s | %5d |', $type, $row->count));
        }
    }

    /**
     * Für jede Content-Klasse: zählt media_content_ids, die NICHT auf
     * eine existierende Row im jeweiligen Content-Modell zeigen. Diese
     * sind Migrationskandidaten für Cleanup oder zeigen Soft-Deletes,
     * die der Pivot nicht mitgeführt hat.
     */
    protected function renderContentOrphans(): void
    {
        $this->line('## Cross-Check: media_content_id gegen vermutetes Content-Modell');
        $this->newLine();

        $contentModels = [
            Text::class => ['table' => 'texts', 'column' => 'id'],
            Audiovisual::class => ['table' => 'audiovisuals', 'column' => 'id'],
            Gallery::class => ['table' => 'galleries', 'column' => 'id'],
            Image::class => ['table' => 'images', 'column' => 'id'],
        ];

        $this->line('| Type-Tag           | mapped to table | matched ids | orphan ids |');
        $this->line('|--------------------|-----------------|------------:|-----------:|');

        foreach ($contentModels as $class => $target) {
            $taggedIds = DB::table('media_content')
                ->where('media_contentable_type', $class)
                ->pluck('media_content_id');

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
            'Hinweis: ein `Image::class`-Tag mit Match in der `galleries`- '.
            'statt `images`-Tabelle wäre der historische Gallery-Schiefstand. '.
            'Wenn die Image-Zeile viele orphans und Gallery viele matches '.
            'auf Image-Tags hat, ist das genau der Befund.'
        );
        $this->newLine();

        // Spezialprobe: Image::class-Tags gegen Galleries (historischer Fall)
        $imageTagsAgainstGalleries = DB::table('media_content')
            ->where('media_contentable_type', Image::class)
            ->whereIn(
                'media_content_id',
                DB::table('galleries')->select('id')
            )
            ->count();

        $this->line('Spezialprobe (historischer Befund):');
        $this->line(sprintf(
            '  Image::class-Tags, deren media_content_id eine bestehende Gallery trifft: %d',
            $imageTagsAgainstGalleries,
        ));
    }

    /**
     * Für jede Row prüfen, ob `media_contentable_id` auf eine
     * existierende Entry-Row zeigt. Heute ist Entry der einzige
     * Parent-Typ in der Praxis (laut Service-Code); diese Probe
     * pinnt diese Annahme empirisch.
     */
    protected function renderParentProbe(): void
    {
        $this->line('## Parent-Probe: media_contentable_id gegen Entry');
        $this->newLine();

        $total = DB::table('media_content')->count();
        if ($total === 0) {
            $this->line('(keine Rows zu prüfen)');

            return;
        }

        $matched = DB::table('media_content')
            ->whereIn(
                'media_contentable_id',
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
            $this->info('Alle media_contentable_id zeigen auf eine Entry-Row. Migrationsplan kann parent_type = Entry::class für alle Rows annehmen.');
        } else {
            $this->warn("{$orphan} Rows zeigen NICHT auf eine Entry-Row. Vor der Migration manuell prüfen — möglicherweise hängt ein Content an einer Gallery oder einem anderen Parent.");
        }
    }

    protected function renderRecommendation(): void
    {
        $this->line('## Empfehlung für die Migration (Sub-Welle 2)');
        $this->newLine();
        $this->line('1. Neue Spalten anlegen: content_id, content_type, parent_id, parent_type.');
        $this->line('2. Daten verteilen:');
        $this->line('     - content_id      ← media_content_id');
        $this->line('     - content_type    ← Mapping aus media_contentable_type:');
        $this->line('         Text::class         → Text::class');
        $this->line('         Audiovisual::class  → Audiovisual::class');
        $this->line('         Image::class (mit Match in galleries) → Gallery::class');
        $this->line('         (sonst content_type = media_contentable_type 1:1 lassen)');
        $this->line('     - parent_id        ← media_contentable_id');
        $this->line('     - parent_type      ← Entry::class (laut Parent-Probe oben)');
        $this->line('3. Modelle umstellen: morphTo(content), morphTo(parent), project()-Methode pro Content-Modell.');
        $this->line('4. Services anpassen: TextService/AudiovisualService/GalleryService/ImageService.');
        $this->line('5. Smoke-Pause vor Cleanup der alten Spalten.');
    }
}
