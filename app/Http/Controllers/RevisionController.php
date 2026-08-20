<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 */

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Audiovisual;
use App\Models\Chapter;
use App\Models\Entry;
use App\Models\Gallery;
use App\Models\Image;
use App\Models\MediaContent;
use App\Models\Project;
use App\Models\Revision;
use App\Models\Text;
use App\Support\PermissionName;
use App\Support\RevisionSubject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Phase 5ab.2 (Design v6 § 6): Feed und Wiederherstellen des Verlaufs.
 *
 * Zwei Endpunkte:
 *
 * - `index()` liefert die Fassungs-Liste fuer das Verlauf-Panel. Der
 *   `scope` bestimmt den Umfang: `block` (das Subject selbst), `entry`
 *   (alle Content-Modelle im Eintrag) oder `project` (alle sechs
 *   Content-Modelle im Projekt). Das Panel im Frontend wechselt per
 *   Segmented Control zwischen den drei Sichten.
 *
 * - `restore()` legt eine neue Revision an, deren Snapshot den Zustand
 *   der gewaehlten Fassung 1:1 wieder herstellt. Die aktuelle Fassung
 *   geht nicht verloren — sie wird als eigene Revision der Kette
 *   angehaengt (§ 7 des Briefings, „Aktuelle Fassung geht nicht
 *   verloren"). Nur User mit `history.restore`-Permission auf dem
 *   Projekt duerfen restoren.
 */
class RevisionController extends Controller
{
    public function index(Request $request, string $subjectType, int $subjectId): JsonResponse
    {
        $subject = RevisionSubject::resolve($subjectType, $subjectId);
        if ($subject === null) {
            abort(404);
        }
        $project = RevisionSubject::projectFor($subject);
        if ($project === null) {
            abort(404);
        }
        $this->authorize('view', $project);

        $scope = $request->query('scope', 'block');
        if (! in_array($scope, ['block', 'entry', 'project'], true)) {
            $scope = 'block';
        }

        $query = Revision::query()
            ->with('actor:id,name,last_name,avatar_path,initials,initials_color')
            ->orderByDesc('created_at');

        // Umfang: Block = nur dieses Subject; Entry = alle Content-
        // Modelle im Eintrag; Project = alle sechs Modelle im Projekt.
        if ($scope === 'block') {
            $query->where('subject_type', $subject::class)
                ->where('subject_id', $subject->getKey());
        } elseif ($scope === 'entry') {
            $entryScope = $this->buildEntryScope($subject);
            if ($entryScope === null) {
                abort(422, 'Entry-Umfang fuer dieses Subject nicht bestimmbar.');
            }
            $this->applySubjectListFilter($query, $entryScope);
        } else { // project
            $this->applySubjectListFilter($query, $this->buildProjectScope($project));
        }

        $revisions = $query->limit(50)->get();

        return response()->json([
            'scope' => $scope,
            'subject' => [
                'type' => RevisionSubject::shortName($subject::class),
                'id' => $subject->getKey(),
            ],
            'revisions' => $revisions->map(function (Revision $r): array {
                // kind_label ist ein Attribute-Accessor (Attribute::get), PHPStan
                // sieht es nicht als deklarierte Property — Zugriff ueber getAttribute.
                return [
                    'id' => $r->id,
                    'version' => $r->version,
                    'kind' => $r->kind,
                    'kindLabel' => (string) $r->getAttribute('kindLabel'),
                    'summary' => $r->summary,
                    'actor' => $r->actor?->name,
                    'createdAt' => $r->created_at->toIso8601String(),
                    'subjectType' => RevisionSubject::shortName($r->subject_type),
                    'subjectId' => $r->subject_id,
                ];
            })->all(),
        ]);
    }

    public function restore(Request $request, Revision $revision): JsonResponse
    {
        $subject = RevisionSubject::resolve(
            RevisionSubject::shortName($revision->subject_type) ?? '',
            $revision->subject_id
        );
        if ($subject === null) {
            abort(404);
        }
        $project = RevisionSubject::projectFor($subject);
        if ($project === null) {
            abort(404);
        }
        // history.restore-Permission ist project-scoped (§ 7 Leserecht:
        // Verlauf sichtbar, Wiederherstellen deaktiviert).
        $user = Auth::user();
        if ($user === null || ! $user->can(PermissionName::HISTORY_RESTORE->value, $project)) {
            abort(403);
        }

        // „Wiederherstellen von v13" heisst fuer den Nutzer: den Zustand
        // aktivieren, den v13 REPRAESENTIERT. Das ist der `new`-Wert der
        // Fassung, nicht der `old`-Wert (der waere der Zustand VOR v13).
        // Fallback auf old, wenn new leer ist (bei alten Backfill-Zeilen,
        // die nur die Aenderung protokolliert haben, ohne den Ziel-Wert
        // sauber zu speichern).
        /** @var array<string, array{old: mixed, new: mixed}> $changes */
        $changes = $revision->snapshot['changes'] ?? [];
        // Whitelist in RevisionSubject::TYPES beschraenkt $subject auf die
        // sechs Content-Modelle, die alle HasTranslations tragen — der
        // Union-Type hilft PHPStan, setTranslation()/setTranslations()
        // aufzuloesen.
        /** @var Chapter|Entry|Text|Gallery|Image|Audiovisual $subject */
        /** @var array<int, string> $translatable */
        $translatable = property_exists($subject, 'translatable') ? (array) $subject->translatable : [];
        foreach ($changes as $field => $delta) {
            $target = $delta['new'] ?? $delta['old'] ?? null;
            if ($target === null) {
                continue;
            }
            // Translatable Felder (Chapter->name, Entry->description, ...)
            // liegen als JSON in der DB. Old-Werte im Snapshot koennen als
            // Array (Live-Trait) oder JSON-String (Activitylog-Backfill)
            // ankommen — beide auf setTranslations() umlenken, damit die
            // Locale-Struktur nicht in einen einzelnen String verkocht wird.
            // Alle sechs Subjects im Union oben tragen HasTranslations —
            // method_exists() waere tautologisch. Reine translatable-Weiche.
            if (in_array($field, $translatable, true)) {
                $translations = null;
                if (is_array($target)) {
                    $translations = $target;
                } elseif (is_string($target)) {
                    $trimmed = trim($target);
                    if ($trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[')) {
                        $decoded = json_decode($trimmed, true);
                        if (is_array($decoded)) {
                            $translations = $decoded;
                        }
                    }
                }
                if ($translations !== null) {
                    $subject->setTranslations($field, $translations);

                    continue;
                }
                // Fallback: nur Deutsch, wenn der Snapshot einen reinen
                // Text-String enthaelt (alte Backfill-Zeilen aus der
                // Zeit vor der translatable-Migration).
                $subject->setTranslation($field, 'de', (string) $target);

                continue;
            }
            $subject->{$field} = $target;
        }
        $subject->save();

        // Der updated-Hook des Traits hat inzwischen selbst eine neue
        // Revision angelegt. Wir markieren sie nachtraeglich mit einer
        // meta-Zeile "wiederhergestellt aus vN", damit die Karte im
        // Panel entsprechend beschriftet werden kann.
        // Q3-Politur G7 (2026-08-20) / CD-05: sortieren nach id, nicht
        // created_at. Sekundenaufloesung reicht bei parallelen Writes
        // nicht; RevisionControllerTest musste bisher subMinutes(6)
        // zwischen zwei Saves einbauen, um die Reihenfolge zu erzwingen.
        /** @var Revision|null $newRevision */
        $newRevision = Revision::query()
            ->where('subject_type', $subject::class)
            ->where('subject_id', $subject->getKey())
            ->latest('id')
            ->first();
        if ($newRevision !== null && $newRevision->id !== $revision->id) {
            $snapshot = $newRevision->snapshot;
            $meta = $snapshot['meta'] ?? [];
            $meta['restored_from_version'] = $revision->version;
            $meta['restored_from_revision_id'] = $revision->id;
            $snapshot['meta'] = $meta;
            $newRevision->snapshot = $snapshot;
            $newRevision->summary = (string) __('revision_summary_restored_from', ['version' => $revision->version]);
            $newRevision->save();
        }

        return response()->json([
            'ok' => true,
            'newRevisionId' => $newRevision?->id,
            'newVersion' => $newRevision?->version,
        ]);
    }

    /**
     * Entry-Umfang aus einem beliebigen Subject ableiten.
     *
     * @return array<int, array{type: class-string, ids: array<int, int>}>|null
     */
    private function buildEntryScope(Model $subject): ?array
    {
        // Wenn das Subject direkt eine Entry ist, ist der Umfang alle
        // Blocks unter dem Entry plus die Entry selbst.
        if ($subject instanceof Entry) {
            $entry = $subject;
        } elseif (method_exists($subject, 'entry')) {
            $entryResult = $subject->entry();
            $entry = $entryResult instanceof Relation
                ? $entryResult->getResults()
                : $entryResult;
            if (! $entry instanceof Entry) {
                return null;
            }
        } else {
            return null;
        }

        $mediaContentIds = $entry->mediaContent()->pluck('id')->all();
        // Content-Modelle sind ueber media_content-Pivot angebunden;
        // hier reichen wir die konkreten Sub-Model-IDs aus dem Pivot
        // heraus. Vereinfacht bauen wir eine Union-Query pro Klasse.

        return [
            ['type' => Entry::class, 'ids' => [$entry->id]],
            ['type' => Text::class, 'ids' => $this->contentIdsForEntry($entry, 'App\\Models\\Text')],
            ['type' => Gallery::class, 'ids' => $this->contentIdsForEntry($entry, 'App\\Models\\Gallery')],
            ['type' => Audiovisual::class, 'ids' => $this->contentIdsForEntry($entry, 'App\\Models\\Audiovisual')],
        ];
    }

    /**
     * Projekt-Umfang: alle Chapter, Entry und Content-Modelle im Projekt.
     *
     * @return array<int, array{type: class-string, ids: array<int, int>}>
     */
    private function buildProjectScope(Project $project): array
    {
        $chapterIds = $project->chapters()->pluck('id')->all();
        $entryIds = Entry::query()->whereIn('chapter_id', $chapterIds)->pluck('id')->all();

        return [
            ['type' => Chapter::class, 'ids' => $chapterIds],
            ['type' => Entry::class, 'ids' => $entryIds],
            ['type' => Text::class, 'ids' => $this->contentIdsForEntries($entryIds, 'App\\Models\\Text')],
            ['type' => Gallery::class, 'ids' => $this->contentIdsForEntries($entryIds, 'App\\Models\\Gallery')],
            ['type' => Audiovisual::class, 'ids' => $this->contentIdsForEntries($entryIds, 'App\\Models\\Audiovisual')],
        ];
    }

    /**
     * @param  array<int, array{type: class-string, ids: array<int, int>}>  $scope
     */
    private function applySubjectListFilter(Builder $query, array $scope): void
    {
        $query->where(function ($outer) use ($scope): void {
            foreach ($scope as $entry) {
                if ($entry['ids'] === []) {
                    continue;
                }
                $outer->orWhere(function ($inner) use ($entry): void {
                    $inner->where('subject_type', $entry['type'])
                        ->whereIn('subject_id', $entry['ids']);
                });
            }
        });
    }

    /**
     * @return array<int, int>
     */
    private function contentIdsForEntry(Entry $entry, string $mediaType): array
    {
        return $entry->mediaContent()
            ->where('content_type', $mediaType)
            ->pluck('content_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @param  array<int, int>  $entryIds
     * @return array<int, int>
     */
    private function contentIdsForEntries(array $entryIds, string $mediaType): array
    {
        if ($entryIds === []) {
            return [];
        }

        return MediaContent::query()
            ->whereIn('parent_id', $entryIds)
            ->where('parent_type', Entry::class)
            ->where('content_type', $mediaType)
            ->pluck('content_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
