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

use App\Models\Entry;
use App\Models\Gallery;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Phase 4 / Block E.7b Sub-Welle 4e-prep, ADR-0022.
 *
 * Safety-Net-Backfill für media_content vor dem Spalten-Drop in
 * Welle 4e. Sucht Pivot-Rows, die in den neuen content_- bzw.
 * parent_-Spalten leer sind, aber in den alten media_contentable-
 * oder media_content_id-Spalten Werte haben — und kopiert sie rüber.
 *
 * Hintergrund: Die Schema-Migration in Welle 2a hat einen einmaligen
 * Backfill durchgeführt, und seit Welle 2d schreiben die Services in
 * beide Spalten parallel (bis Welle 4d nur noch in die neuen). Auf
 * einem regulär migrierten Stand sollte dieser Command also nichts
 * zu tun finden. Er ist als Sicherheitsnetz für Drift-Fälle gedacht:
 *
 *   - Welle-2a-Migration teilweise fehlgeschlagen
 *   - Direkt-Inserts via Raw-SQL zwischen 2a und 2d
 *   - Manuelle DB-Bearbeitung
 *
 * Modi:
 *   php artisan db:migrate-media-content
 *     Dry-run. Reportet, was migriert würde, schreibt aber nichts.
 *
 *   php artisan db:migrate-media-content --apply
 *     Backfill ausführen. Idempotent — Re-Runs sind sicher.
 */
class MigrateMediaContent extends Command
{
    protected $signature = 'db:migrate-media-content
                            {--apply : Backfill tatsächlich ausführen (default: dry-run)}';

    protected $description = 'Backfill-Safety-Net für media_content content_*/parent_*-Spalten vor dem Spalten-Drop (ADR-0022).';

    public function handle(): int
    {
        if (! DB::getSchemaBuilder()->hasTable('media_content')) {
            $this->error('Tabelle media_content fehlt — Migrationen nicht durchgelaufen?');

            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');

        if (! $apply) {
            $this->warn('Dry-run-Modus. Re-run mit --apply, um die Korrekturen wirklich zu schreiben.');
        } else {
            $this->info('Apply-Modus. Schreibvorgang läuft.');
        }
        $this->newLine();

        $total = DB::table('media_content')->count();
        $this->line("media_content Total-Rows: {$total}");
        $this->newLine();

        if ($total === 0) {
            $this->info('Keine Rows — nichts zu tun.');

            return self::SUCCESS;
        }

        $stats = $this->analyzeAndMigrate($apply);

        $this->newLine();
        $this->renderReport($stats, $apply);

        return self::SUCCESS;
    }

    /**
     * Iteriert über die Pivot-Rows, identifiziert Backfill-Kandidaten
     * und führt sie aus (im Apply-Modus). Liefert die Statistik für
     * den Report.
     *
     * @return array{
     *   already_filled: int,
     *   fixable: int,
     *   fixed: int,
     *   unrecoverable: int,
     *   gallery_schiefstand: int
     * }
     */
    protected function analyzeAndMigrate(bool $apply): array
    {
        $alreadyFilled = 0;
        $fixable = 0;
        $fixed = 0;
        $unrecoverable = 0;
        $gallerySchiefstand = 0;

        $entryTable = (new Entry)->getTable();
        $galleryTable = (new Gallery)->getTable();

        DB::table('media_content')->orderBy('id')->chunkById(500, function ($rows) use (
            $apply,
            $entryTable,
            $galleryTable,
            &$alreadyFilled,
            &$fixable,
            &$fixed,
            &$unrecoverable,
            &$gallerySchiefstand,
        ) {
            foreach ($rows as $row) {
                $needsBackfill =
                    $row->content_id === null
                    || $row->content_type === null
                    || $row->parent_id === null
                    || $row->parent_type === null;

                if (! $needsBackfill) {
                    $alreadyFilled++;

                    continue;
                }

                // Aus den alten Spalten ableiten.
                $newContentId = $row->content_id ?? $row->media_content_id;
                $newContentType = $row->content_type ?? $row->media_contentable_type;
                $newParentId = $row->parent_id ?? $row->media_contentable_id;
                $newParentType = $row->parent_type ?? Entry::class;

                // Gallery-Schiefstand-Fix: alte media_contentable_type
                // trug für Galleries fälschlich Image::class. Wenn die
                // media_content_id eine bestehende Gallery trifft,
                // setzen wir content_type sauber auf Gallery::class.
                if (
                    $newContentType === \App\Models\Image::class
                    && $newContentId !== null
                    && DB::table($galleryTable)->where('id', $newContentId)->exists()
                ) {
                    $newContentType = Gallery::class;
                    $gallerySchiefstand++;
                }

                // Wenn wir auch aus den alten Spalten nichts ableiten
                // können (alle null), ist die Row nicht recoverable.
                if (
                    $newContentId === null
                    || $newContentType === null
                    || $newParentId === null
                ) {
                    $unrecoverable++;

                    continue;
                }

                $fixable++;

                if ($apply) {
                    DB::table('media_content')
                        ->where('id', $row->id)
                        ->update([
                            'content_id' => $newContentId,
                            'content_type' => $newContentType,
                            'parent_id' => $newParentId,
                            'parent_type' => $newParentType,
                        ]);
                    $fixed++;
                }
            }
        });

        return [
            'already_filled' => $alreadyFilled,
            'fixable' => $fixable,
            'fixed' => $fixed,
            'unrecoverable' => $unrecoverable,
            'gallery_schiefstand' => $gallerySchiefstand,
        ];
    }

    /**
     * @param array{
     *   already_filled: int,
     *   fixable: int,
     *   fixed: int,
     *   unrecoverable: int,
     *   gallery_schiefstand: int
     * } $stats
     */
    protected function renderReport(array $stats, bool $apply): void
    {
        $this->line('## Report');
        $this->newLine();

        $this->line('| Kategorie                         | Anzahl |');
        $this->line('|-----------------------------------|-------:|');
        $this->line(sprintf('| Schon vollständig befüllt         | %6d |', $stats['already_filled']));
        $this->line(sprintf('| Backfill-Kandidaten (fixable)     | %6d |', $stats['fixable']));
        $this->line(sprintf('| Davon im Lauf korrigiert (fixed)  | %6d |', $stats['fixed']));
        $this->line(sprintf('| Unrecoverable (alte Spalten leer) | %6d |', $stats['unrecoverable']));
        $this->line(sprintf('| Gallery-Schiefstand mit-gefixt    | %6d |', $stats['gallery_schiefstand']));

        $this->newLine();

        if ($stats['fixable'] === 0 && $stats['unrecoverable'] === 0) {
            $this->info('Sauber. Alle Rows sind bereits in den neuen Spalten geführt — Welle 4e kann die alten Spalten droppen.');

            return;
        }

        if ($stats['unrecoverable'] > 0) {
            $this->error(sprintf(
                '%d Rows sind weder in den neuen noch in den alten Spalten vollständig. '.
                'Diese müssen vor dem Spalten-Drop manuell geprüft werden.',
                $stats['unrecoverable'],
            ));
        }

        if ($apply) {
            $this->info(sprintf('%d Rows wurden korrigiert.', $stats['fixed']));
        } else {
            $this->warn(sprintf(
                '%d Rows würden korrigiert. Re-run mit --apply, um die Änderungen zu schreiben.',
                $stats['fixable'],
            ));
        }
    }
}
