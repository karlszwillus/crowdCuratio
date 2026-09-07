<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2022, 2026 - berlinHistory e.V.

See LICENSE.
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Entry;
use App\Models\Gallery;
use App\Models\Image;
use App\Models\Project;
use App\Models\Source;
use App\Models\Text;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Q4-Etappe 3 / C0-8b (2026-09-07): Migrations-Assistent, der Alt-
 * Sources (project_id = NULL) pro Projekt in projekt-scoped Zeilen
 * ueberfuehrt. Zwei Modi: Dry-Run (nur Analyse) und Commit (mit
 * Backup, Referenz-Umbiegung und Activity-Log-Trail).
 *
 * Was der Service tut, im Ueberblick:
 *  1. Sammelt alle Alt-Sources, die von Text/Image-Bloecken des
 *     angegebenen Projekts referenziert werden.
 *  2. Normalisiert Namen (trim, kollabierende Whitespace, casefold)
 *     und gruppiert Duplikate innerhalb des Projekts.
 *  3. Erkennt Freitext-Kandidaten (sehr kurze Namen, keine
 *     Institutions-Keywords).
 *  4. Schlaegt `kind` (`archivalie/publikation/interview/abbildung/
 *     sonstige`) auf Basis einer schmalen Keyword-Regel vor.
 *  5. Im Commit-Modus: Backup, Neu-Anlage projekt-scoped, Referenz-
 *     Umbiegung an Text und Image, Activity-Log-Events.
 *
 * Die Umbiegung ist idempotent: wer laeuft, wenn schon alle Alt-
 * Sources des Projekts verarbeitet sind, findet keine Kandidaten
 * und macht nichts.
 */
final class SourceMigrationService
{
    /**
     * Regel-Keywords fuer den kind-Vorschlag.
     *
     * @var array<string, array<int, string>>
     */
    // Reihenfolge ist Prioritaet — spezifischere Signale (BPK,
    // Ullstein, Verlag, Interview) matchen vor generischen
    // Archiv-Keywords, damit „BPK Bildarchiv" nicht auf `archivalie`
    // faellt, obwohl es klar eine Abbildungsquelle ist.
    private const KIND_KEYWORDS = [
        'abbildung' => ['bpk', 'ullstein', 'fotografie', 'photograph', 'foto', 'bildagentur', 'abbildung'],
        'publikation' => ['verlag', 'zeitschrift', 'zeitung', 'buch', 'edition', 'jahrbuch'],
        'interview' => ['interview', 'gespräch', 'gespraech', 'zeitzeuge'],
        'archivalie' => ['archiv', 'lab', 'bestand', 'signatur', 'akte', 'nachlass'],
    ];

    /** Mindestlaenge fuer „richtige" Quelle. Kuerzer → Freitext-Kandidat. */
    private const FREETEXT_MIN_LENGTH = 4;

    /**
     * Sammelt alle Alt-Sources (project_id = NULL), die vom
     * angegebenen Projekt referenziert werden.
     *
     * @return Collection<int, Source>
     */
    public function candidatesFor(int $projectId): Collection
    {
        // Query-Weg statt whereHas-Chain, weil MediaContent polymorph
        // ueber `parent_type`/`parent_id` haengt und ein sauberer join
        // sich als lesbarer und effizienter erweist.
        $textSourceIds = DB::table('texts')
            ->join('media_content', function ($join) {
                $join->on('media_content.content_id', '=', 'texts.id')
                    ->where('media_content.content_type', '=', Text::class);
            })
            ->join('entries', function ($join) {
                $join->on('entries.id', '=', 'media_content.parent_id')
                    ->where('media_content.parent_type', '=', Entry::class);
            })
            ->join('chapters', 'chapters.id', '=', 'entries.chapter_id')
            ->where('chapters.project_id', $projectId)
            ->select(['texts.origin', 'texts.copyright'])
            ->get()
            ->flatMap(fn ($row) => [$row->origin, $row->copyright]);

        $imageSourceIds = DB::table('images')
            ->join('galleries', 'galleries.id', '=', 'images.gallery_id')
            ->join('media_content', function ($join) {
                $join->on('media_content.content_id', '=', 'galleries.id')
                    ->where('media_content.content_type', '=', Gallery::class);
            })
            ->join('entries', function ($join) {
                $join->on('entries.id', '=', 'media_content.parent_id')
                    ->where('media_content.parent_type', '=', Entry::class);
            })
            ->join('chapters', 'chapters.id', '=', 'entries.chapter_id')
            ->where('chapters.project_id', $projectId)
            ->select(['images.origin', 'images.copyright'])
            ->get()
            ->flatMap(fn ($row) => [$row->origin, $row->copyright]);

        $ids = $textSourceIds->concat($imageSourceIds)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        return Source::query()
            ->whereIn('id', $ids)
            ->whereNull('project_id')
            ->get();
    }

    /**
     * Normalisiert und gruppiert Duplikate.
     *
     * @param  Collection<int, Source>  $candidates
     * @return array<string, array{normalized: string, sources: array<int, Source>}>
     */
    public function dedupCandidates(Collection $candidates): array
    {
        $groups = [];
        foreach ($candidates as $source) {
            $key = $this->normalize((string) $source->name);
            $groups[$key] ??= ['normalized' => $key, 'sources' => []];
            $groups[$key]['sources'][] = $source;
        }

        return $groups;
    }

    /**
     * Regel-basierter Erstvorschlag fuer `kind`. Nur ein Vorschlag —
     * Redaktion bestaetigt.
     */
    public function classifyKind(string $name): ?string
    {
        $needle = mb_strtolower($name);
        foreach (self::KIND_KEYWORDS as $kind => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($needle, $keyword)) {
                    return $kind;
                }
            }
        }

        return null;
    }

    /**
     * Freitext-Heuristik: sehr kurze Namen ODER einzelne kurze
     * Woerter ohne Kennung als „vermutlich Freitext" markieren.
     */
    public function looksLikeFreetext(string $name): bool
    {
        $trimmed = trim($name);
        if (mb_strlen($trimmed) < self::FREETEXT_MIN_LENGTH) {
            return true;
        }
        // Ein einzelnes Wort ohne Zahl / Sonderzeichen und kuerzer als 8:
        // Kandidat fuer Freitext (z. B. „Meier", „Foto").
        if (! preg_match('/\s/', $trimmed)
            && mb_strlen($trimmed) < 8
            && ! preg_match('/[0-9-\/]/', $trimmed)) {
            return true;
        }

        return false;
    }

    /**
     * Fuehrt den Migrations-Zug fuer ein Projekt durch. `dryRun`
     * liefert nur den Report; ohne `dryRun` werden Backup +
     * Referenz-Umbiegung committet.
     *
     * @return array{
     *     project_id: int,
     *     run_key: string,
     *     dry_run: bool,
     *     candidates: int,
     *     groups: int,
     *     freetext_candidates: int,
     *     kind_suggestions: array<string, int>,
     *     migrated: int,
     * }
     */
    public function runFor(int $projectId, bool $dryRun): array
    {
        $project = Project::findOrFail($projectId);
        $candidates = $this->candidatesFor($projectId);

        $groups = $this->dedupCandidates($candidates);
        $runKey = 'run-'.now()->format('Y-m-d-His').'-p'.$projectId;

        $report = [
            'project_id' => $projectId,
            'run_key' => $runKey,
            'dry_run' => $dryRun,
            'candidates' => $candidates->count(),
            'groups' => count($groups),
            'freetext_candidates' => 0,
            'kind_suggestions' => ['archivalie' => 0, 'publikation' => 0, 'interview' => 0, 'abbildung' => 0, 'sonstige' => 0],
            'migrated' => 0,
        ];

        foreach ($groups as $group) {
            /** @var Source $canonical */
            $canonical = $group['sources'][0];
            if ($this->looksLikeFreetext((string) $canonical->name)) {
                $report['freetext_candidates']++;
            }
            $kind = $this->classifyKind((string) $canonical->name) ?? 'sonstige';
            $report['kind_suggestions'][$kind]++;
        }

        if ($dryRun) {
            return $report;
        }

        // Commit: Backup + Neu-Anlage + Referenz-Umbiegung in einer
        // Transaktion pro Gruppe. Wenn irgendwo etwas kippt, rollback
        // fuer die Gruppe und weiter mit der naechsten.
        foreach ($groups as $group) {
            /** @var array<int, Source> $sources */
            $sources = $group['sources'];
            /** @var Source $canonical */
            $canonical = $sources[0];

            DB::transaction(function () use ($sources, $canonical, $projectId, $runKey, &$report) {
                // Alle Backups fuer diese Gruppe schreiben.
                foreach ($sources as $source) {
                    $this->backup($source, $runKey);
                }

                // Neuen projekt-scoped Row aus der kanonischen
                // Quelle anlegen. Kind kommt aus dem Regel-Vorschlag
                // (null wenn keiner passt — Redaktion muss nachziehen).
                $projectSource = new Source;
                $projectSource->project_id = $projectId;
                $projectSource->original_id = (int) $canonical->id;
                $projectSource->type = (string) $canonical->type;
                $projectSource->kind = $this->classifyKind((string) $canonical->name);
                $projectSource->is_translated = (bool) $canonical->is_translated;
                foreach ($canonical->getTranslations('name') as $locale => $value) {
                    $projectSource->setTranslation('name', $locale, $value);
                }
                $projectSource->save();

                // Alle Text- und Image-Referenzen auf die alten IDs
                // dieser Gruppe auf die neue ID umbiegen — aber nur
                // fuer Bloecke DIESES Projekts. Andere Projekte
                // bleiben unberuehrt (die duplizieren ihre eigene
                // Zeile im naechsten Assistenten-Lauf).
                $oldIds = collect($sources)->pluck('id')->all();

                // Referenzen umbiegen: sub-query filtert die Text/
                // Image-Rows dieses Projekts, das eigentliche Update
                // laeuft dann per whereIn auf der Primary-ID.
                $textIds = $this->textIdsForProject($projectId);
                $imageIds = $this->imageIdsForProject($projectId);

                if ($textIds !== []) {
                    Text::query()->whereIn('id', $textIds)->whereIn('origin', $oldIds)->update(['origin' => $projectSource->id]);
                    Text::query()->whereIn('id', $textIds)->whereIn('copyright', $oldIds)->update(['copyright' => $projectSource->id]);
                }
                if ($imageIds !== []) {
                    Image::query()->whereIn('id', $imageIds)->whereIn('origin', $oldIds)->update(['origin' => $projectSource->id]);
                    Image::query()->whereIn('id', $imageIds)->whereIn('copyright', $oldIds)->update(['copyright' => $projectSource->id]);
                }

                Log::channel(config('logging.default'))->info('sources.migration.merged', [
                    'run_key' => $runKey,
                    'project_id' => $projectId,
                    'new_source_id' => $projectSource->id,
                    'merged_from' => $oldIds,
                    'kind_suggested' => $projectSource->kind,
                ]);

                $report['migrated']++;
            });
        }

        return $report;
    }

    /**
     * Sichert eine Alt-Zeile in `sources_backup`. Idempotent gegen
     * denselben `run_key` — wer den gleichen Command zweimal ruft,
     * kriegt keine Doppel-Backups.
     */
    private function backup(Source $source, string $runKey): void
    {
        DB::table('sources_backup')->updateOrInsert(
            ['run_key' => $runKey, 'source_id' => $source->id],
            [
                'project_id' => $source->project_id,
                'name' => (string) $source->getRawOriginal('name'),
                'title' => $source->title,
                'holding' => $source->holding,
                'signature' => $source->signature,
                'type' => (string) $source->type,
                'kind' => $source->kind,
                'is_translated' => (bool) $source->is_translated,
                'backup_run_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * @return array<int, int>
     */
    private function textIdsForProject(int $projectId): array
    {
        return DB::table('texts')
            ->join('media_content', function ($join) {
                $join->on('media_content.content_id', '=', 'texts.id')
                    ->where('media_content.content_type', '=', Text::class);
            })
            ->join('entries', function ($join) {
                $join->on('entries.id', '=', 'media_content.parent_id')
                    ->where('media_content.parent_type', '=', Entry::class);
            })
            ->join('chapters', 'chapters.id', '=', 'entries.chapter_id')
            ->where('chapters.project_id', $projectId)
            ->pluck('texts.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function imageIdsForProject(int $projectId): array
    {
        return DB::table('images')
            ->join('galleries', 'galleries.id', '=', 'images.gallery_id')
            ->join('media_content', function ($join) {
                $join->on('media_content.content_id', '=', 'galleries.id')
                    ->where('media_content.content_type', '=', Gallery::class);
            })
            ->join('entries', function ($join) {
                $join->on('entries.id', '=', 'media_content.parent_id')
                    ->where('media_content.parent_type', '=', Entry::class);
            })
            ->join('chapters', 'chapters.id', '=', 'entries.chapter_id')
            ->where('chapters.project_id', $projectId)
            ->pluck('images.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Namens-Normalisierung: trim + Whitespace kollabieren + casefold.
     * Bewusst konservativ — keine Levenshtein-Fuzz-Matches in der
     * ersten Version, damit die Redaktion keine falschen Merges
     * revidieren muss.
     */
    private function normalize(string $name): string
    {
        $stripped = trim($name);
        $stripped = preg_replace('/\s+/u', ' ', $stripped) ?? $stripped;

        return Str::lower($stripped);
    }
}
