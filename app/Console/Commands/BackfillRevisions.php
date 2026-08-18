<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 */

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Revision;
use App\Support\RevisionKind;
use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;

/**
 * Phase 5ab.1b: Historischen Verlauf aus Activitylog nachziehen.
 *
 * Vor dem Deploy einmal laufen lassen (`sail artisan revisions:backfill`),
 * damit der Verlauf-Panel am Cut-over-Tag nicht ueberall behauptet, ein
 * Block sei seit dem Anlegen nicht geaendert worden.
 *
 * Idempotent: prueft pro Activity-Zeile, ob schon eine Revision mit
 * gleichem Subject und exakt gleichem `created_at` existiert. Wer den
 * Command zweimal laufen laesst, bekommt keine Duplikate.
 *
 * Kein Coalescing rueckwirkend: Aenderungen im 5-Min-Fenster wurden in
 * Activitylog als Einzeleintraege gespeichert; die uebernehmen wir 1:1.
 * Verdichten waere ein eigener Command (Backlog).
 */
class BackfillRevisions extends Command
{
    protected $signature = 'revisions:backfill
        {--dry-run : Zeige an, was geschrieben wuerde, ohne DB-Writes}
        {--fresh : Vor dem Schreiben alle backfilled_from=activity_log-Zeilen loeschen}';

    protected $description = 'Backfill der revisions-Tabelle aus dem Activitylog';

    /**
     * Nur diese sechs Subject-Typen werden ins Verlauf-Modell uebertragen.
     * Andere Activitylog-Eintraege (User, Project, Comment, …) haben mit
     * dem Content-Verlauf des Editors nichts zu tun.
     *
     * @var array<int, string>
     */
    private const SUBJECT_TYPES = [
        \App\Models\Chapter::class,
        \App\Models\Entry::class,
        \App\Models\Text::class,
        \App\Models\Gallery::class,
        \App\Models\Image::class,
        \App\Models\Audiovisual::class,
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $fresh = (bool) $this->option('fresh');
        if ($dryRun) {
            $this->warn('Dry-Run — kein DB-Write.');
        }
        if ($fresh && ! $dryRun) {
            $deleted = Revision::query()
                ->whereJsonContains('snapshot->meta->backfilled_from', 'activity_log')
                ->delete();
            $this->warn("Fresh-Modus: {$deleted} bestehende Backfill-Zeilen entfernt.");
        }

        $total = 0;
        $skipped = 0;
        $written = 0;

        // Version-Counter je Subject, damit wir aufsteigend nummerieren
        // und die aktuelle Fassung tatsaechlich die hoechste Version
        // traegt (§ 6: Chip „v9 · Aktuell").
        /** @var array<string, int> $versionSeq */
        $versionSeq = [];

        Activity::query()
            ->whereIn('subject_type', self::SUBJECT_TYPES)
            ->whereIn('event', ['created', 'updated'])
            ->orderBy('subject_type')
            ->orderBy('subject_id')
            ->orderBy('created_at')
            ->chunkById(500, function ($activities) use (&$total, &$skipped, &$written, &$versionSeq, $dryRun) {
                foreach ($activities as $activity) {
                    $total++;

                    $subjectType = (string) $activity->subject_type;
                    $subjectId = (int) $activity->subject_id;
                    if ($subjectId === 0) {
                        $skipped++;

                        continue;
                    }

                    // Idempotenz: gleiche Kombination aus Subject +
                    // created_at wurde schon uebernommen.
                    $exists = Revision::query()
                        ->where('subject_type', $subjectType)
                        ->where('subject_id', $subjectId)
                        ->where('created_at', $activity->created_at)
                        ->exists();
                    if ($exists) {
                        $skipped++;

                        continue;
                    }

                    $props = $activity->properties?->toArray() ?? [];
                    $old = (array) ($props['old'] ?? []);
                    $new = (array) ($props['attributes'] ?? []);

                    // Changes-Delta bauen — nur Felder, wo new gesetzt
                    // ist. Old faellt auf null, wenn Activitylog nur den
                    // Neuwert kennt (created-Event).
                    $changes = [];
                    foreach ($new as $field => $value) {
                        if (in_array($field, ['updated_at', 'created_at'], true)) {
                            continue;
                        }
                        $oldValue = $old[$field] ?? null;
                        // No-op-Filter: Activitylog schreibt gelegentlich
                        // Zeilen, in denen ein Feld nur von null auf
                        // Leerstring wandert (Formular reicht das leere
                        // Feld mit). Fuer den Verlauf ist das Rauschen —
                        // wir werfen es raus.
                        if (self::isEffectivelyEqual($oldValue, $value)) {
                            continue;
                        }
                        $changes[$field] = [
                            'old' => $oldValue,
                            'new' => $value,
                        ];
                    }
                    if ($changes === [] && $activity->event !== 'created') {
                        $skipped++;

                        continue;
                    }

                    $kind = $this->deriveKind($subjectType, $changes, $activity->event === 'created');
                    $summary = $this->buildSummary($changes, $activity->event === 'created');

                    $key = $subjectType.'#'.$subjectId;
                    $versionSeq[$key] = ($versionSeq[$key] ?? 0) + 1;

                    if (! $dryRun) {
                        // created_at/updated_at duerfen nicht ins fill(),
                        // weil das Model unter shouldBeStrict() sonst
                        // eine MassAssignmentException wirft. Wir setzen
                        // die Timestamps nach dem fill() explizit und
                        // schalten die automatischen Timestamps ab,
                        // damit save() nicht drueberschreibt.
                        $revision = new Revision;
                        $revision->fill([
                            'subject_type' => $subjectType,
                            'subject_id' => $subjectId,
                            'actor_id' => $activity->causer_id ? (int) $activity->causer_id : null,
                            'kind' => $kind,
                            'summary' => $summary,
                            'snapshot' => [
                                'changes' => $changes,
                                'meta' => [
                                    'backfilled_from' => 'activity_log',
                                    'activity_id' => $activity->id,
                                    'event' => $activity->event,
                                ],
                            ],
                            'version' => $versionSeq[$key],
                        ]);
                        // Activity kann in Grenzfaellen (verwaiste Alt-Zeilen)
                        // keinen Timestamp haben — wir ueberspringen die, weil
                        // ein Verlauf ohne Zeit nutzlos ist. PHPStan-Grund:
                        // Activity::$created_at ist Carbon|null, Revision::
                        // $created_at ist non-null.
                        if ($activity->created_at === null) {
                            $skipped++;

                            continue;
                        }
                        $revision->created_at = \Illuminate\Support\Carbon::instance($activity->created_at);
                        $revision->updated_at = \Illuminate\Support\Carbon::instance($activity->updated_at ?? $activity->created_at);
                        $revision->timestamps = false;
                        $revision->save();
                    }
                    $written++;
                }
            });

        $this->info(sprintf(
            'Activity-Zeilen gesichtet: %d · geschrieben: %d · uebersprungen: %d%s',
            $total,
            $written,
            $skipped,
            $dryRun ? ' (dry-run)' : ''
        ));

        return self::SUCCESS;
    }

    /**
     * Simplifiziertes Kind-Mapping fuer Backfill: dieselbe Logik wie im
     * Trait, aber ohne den `revisionKindHint` (Uebersetzungen werden im
     * Backfill als CONTENT gefuehrt — die alte Uebersetzen-Route hat
     * keinen Marker gesetzt, den wir hier nachtraeglich raten koennten).
     *
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     */
    private function deriveKind(string $subjectType, array $changes, bool $created): string
    {
        if ($created) {
            return RevisionKind::CONTENT->value;
        }

        $fields = array_keys($changes);
        if ($fields === ['position']) {
            return RevisionKind::REORDER->value;
        }

        // Instanzieren um an $translatable zu kommen, ohne die Modell-
        // Instanz frisch aus der DB zu ziehen (Subject kann geloescht
        // sein — sein Verlauf soll trotzdem sichtbar bleiben).
        $translatable = [];
        if (class_exists($subjectType)) {
            $instance = new $subjectType;
            if (property_exists($instance, 'translatable')) {
                /** @var array<int, string> $translatable */
                $translatable = (array) $instance->translatable;
            }
        }
        $textFields = array_merge($translatable, ['transcript', 'description']);
        $isOnlyText = collect($fields)->every(fn ($f) => in_array($f, $textFields, true));
        if ($isOnlyText && $fields !== []) {
            return RevisionKind::CONTENT->value;
        }

        return RevisionKind::FACTS->value;
    }

    /**
     * Zwei Werte semantisch vergleichen: null, ""  und leere Arrays
     * gelten als gleich, weil das der haeufigste No-op-Fall im Log ist.
     * Strings mit Whitespace-Only werden getrimmed verglichen. Alles
     * andere per Strict-Equality.
     */
    private static function isEffectivelyEqual(mixed $a, mixed $b): bool
    {
        $norm = static function (mixed $v): mixed {
            if ($v === null) {
                return '';
            }
            if (is_string($v)) {
                return trim($v);
            }
            if (is_array($v) && $v === []) {
                return '';
            }

            return $v;
        };

        return $norm($a) === $norm($b);
    }

    /**
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     */
    private function buildSummary(array $changes, bool $created): string
    {
        if ($created) {
            return (string) __('revision_summary_created');
        }
        $count = count($changes);
        if ($count === 1) {
            return (string) __('revision_summary_single_field', ['field' => array_key_first($changes)]);
        }

        return (string) __('revision_summary_multi_field', ['count' => $count]);
    }
}
