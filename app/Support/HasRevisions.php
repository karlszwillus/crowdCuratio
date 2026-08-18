<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 */

declare(strict_types=1);

namespace App\Support;

use App\Models\Revision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Phase 5ab.1: Trait fuer alle Content-Modelle, die Fassungen fuehren.
 *
 * Klemmt sich auf die Eloquent-Events `updated` und `created` und
 * schreibt eine Zeile in `revisions`. Coalescing (§ 8.2): eine
 * Aenderung derselben Person am selben Subject innerhalb der letzten
 * 5 Minuten wird nicht als neue Fassung angelegt, sondern in die
 * bestehende Fassung eingerechnet — sonst rauscht der Verlauf durch
 * das Auto-Save-on-Blur aus 5aa voll.
 *
 * Der Kind wird aus den geaenderten Feldern abgeleitet:
 * - REORDER: nur `position`-Feld geaendert
 * - TRANSLATION: nur JSON-translatable Felder mit non-de Locale
 *   geaendert (Marker vom saveTranslations-Endpoint)
 * - CONTENT: Text-Felder (name, subtitle, description, transcript, …)
 * - FACTS: alles andere (Herkunft, Copyright, Datei, Angaben)
 *
 * Die Reihenfolge oben ist die Fallback-Kette: der spezifischere
 * Kind gewinnt (Reorder → Translation → Content → Facts).
 */
trait HasRevisions
{
    /**
     * Marker, den saveTranslations() setzt, damit der Observer die
     * Aenderung als TRANSLATION klassifizieren kann. Ohne den Marker
     * kann der Trait nicht wissen, dass eine JSON-Aenderung an `name`
     * eine Uebersetzungs-Aenderung war und keine deutsche Text-
     * Anpassung.
     */
    public ?string $revisionKindHint = null;

    public static function bootHasRevisions(): void
    {
        static::created(function (Model $model): void {
            /** @var Model&self $model */
            $model->recordRevision([], created: true);
        });

        static::updated(function (Model $model): void {
            /** @var Model&self $model */
            $changes = [];
            /** @var array<string, mixed> $original */
            $original = $model->getOriginal();
            /** @var array<string, mixed> $dirty */
            $dirty = $model->getChanges();
            foreach ($dirty as $field => $newValue) {
                if (in_array($field, ['updated_at', 'created_at'], true)) {
                    continue;
                }
                $changes[$field] = [
                    'old' => $original[$field] ?? null,
                    'new' => $newValue,
                ];
            }
            if ($changes === []) {
                return;
            }
            $model->recordRevision($changes);
        });
    }

    public function revisions(): MorphMany
    {
        return $this->morphMany(Revision::class, 'subject');
    }

    /**
     * Legt eine Revision an oder aktualisiert die letzte (Coalescing).
     *
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     */
    public function recordRevision(array $changes, bool $created = false): void
    {
        $actorId = Auth::id();
        $kind = $this->deriveRevisionKind($changes, $created);
        $summary = $this->buildRevisionSummary($changes, $created);

        // Coalescing-Fenster: 5 Minuten, derselbe Actor, dasselbe Subject,
        // derselbe Kind. Wenn eine passende Revision existiert, mergen wir
        // die changes hinein — old bleibt, new wird ueberschrieben.
        $window = Carbon::now()->subMinutes(5);
        /** @var Revision|null $existing */
        $existing = null;
        if ($actorId !== null && ! $created) {
            $existing = Revision::query()
                ->where('subject_type', static::class)
                ->where('subject_id', $this->getKey())
                ->where('actor_id', $actorId)
                ->where('kind', $kind)
                ->where('created_at', '>=', $window)
                ->latest('created_at')
                ->first();
        }

        if ($existing !== null) {
            $merged = $existing->snapshot['changes'] ?? [];
            foreach ($changes as $field => $delta) {
                if (isset($merged[$field])) {
                    // old bleibt (der Ur-Ausgangspunkt), new wird auf
                    // den neuesten Stand gebracht.
                    $merged[$field]['new'] = $delta['new'];
                } else {
                    $merged[$field] = $delta;
                }
            }
            $existing->snapshot = [
                'changes' => $merged,
                'meta' => $existing->snapshot['meta'] ?? [],
            ];
            $existing->summary = $summary;
            $existing->touch();

            return;
        }

        Revision::create([
            'subject_type' => static::class,
            'subject_id' => $this->getKey(),
            'actor_id' => $actorId,
            'kind' => $kind,
            'summary' => $summary,
            'snapshot' => [
                'changes' => $changes,
                'meta' => $created ? ['created' => true] : [],
            ],
            'version' => $this->nextRevisionVersion(),
        ]);
    }

    /**
     * Naechste Fassungs-Nummer fuer dieses Subject.
     */
    protected function nextRevisionVersion(): int
    {
        $max = (int) Revision::query()
            ->where('subject_type', static::class)
            ->where('subject_id', $this->getKey())
            ->max('version');

        return $max + 1;
    }

    /**
     * Ableitung des Kind aus den geaenderten Feldern.
     *
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     */
    protected function deriveRevisionKind(array $changes, bool $created): string
    {
        if ($created) {
            return RevisionKind::CONTENT->value;
        }

        // Marker vom saveTranslations-Endpoint hat Vorrang: wenn die
        // Aenderung ausdruecklich als Uebersetzung eingehen soll,
        // vertrauen wir dem Aufrufer.
        if ($this->revisionKindHint !== null) {
            return $this->revisionKindHint;
        }

        $fields = array_keys($changes);

        if ($fields === ['position']) {
            return RevisionKind::REORDER->value;
        }

        // Alle sechs Modelle mit dem Trait tragen HasTranslations und
        // definieren `$translatable` — der property_exists-Check waere
        // damit tautologisch. Sollte spaeter ein Trait-User ohne Spatie
        // dazukommen, faellt der Zugriff hier auf.
        /** @var array<int, string> $translatable */
        $translatable = (array) $this->translatable;

        // Wenn ausschliesslich translatable Felder betroffen sind, aber
        // die Aenderung nicht ueber den Translation-Marker kam, ist es
        // trotzdem eine Aenderung am deutschen Original — CONTENT.
        $textFields = array_merge($translatable, ['transcript', 'description']);
        $isOnlyText = collect($fields)->every(fn ($f) => in_array($f, $textFields, true));
        if ($isOnlyText && $fields !== []) {
            return RevisionKind::CONTENT->value;
        }

        return RevisionKind::FACTS->value;
    }

    /**
     * Kurzfassung fuer die Fassungs-Karte. Deutsche Beschriftung, eine
     * Zeile — mehr ist im Diff, nicht in der Kurzfassung.
     *
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     */
    protected function buildRevisionSummary(array $changes, bool $created): string
    {
        if ($created) {
            return __('revision_summary_created');
        }

        $fieldCount = count($changes);
        if ($fieldCount === 1) {
            $field = array_key_first($changes);

            return __('revision_summary_single_field', ['field' => $field]);
        }

        return __('revision_summary_multi_field', ['count' => $fieldCount]);
    }
}
