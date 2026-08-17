<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 */

declare(strict_types=1);

namespace App\Support;

use App\Models\Audiovisual;
use App\Models\Chapter;
use App\Models\Entry;
use App\Models\Gallery;
use App\Models\Image;
use App\Models\Project;
use App\Models\Text;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Phase 5ab.2: Whitelist und Aufloesung der Verlaufs-Subjects.
 *
 * Der Verlauf-Feed und der Restore-Endpoint kriegen `subject_type` und
 * `subject_id` aus dem Panel-Frontend. Wir muessen zweierlei absichern:
 *
 * 1. Nur die sechs Content-Modelle sind erlaubt. Sonst koennte jemand
 *    per Query beliebige Klassen laden.
 * 2. Zu jedem Subject muss ein Projekt gefunden werden, damit die
 *    Policy (`view` / `history.restore`) project-scoped greifen kann.
 *    `Model::project()` liefert bei Chapter/Entry eine Relation, bei
 *    Text/Gallery/Image/Audiovisual das Project direkt — beide Zweige
 *    normalisieren wir hier.
 */
final class RevisionSubject
{
    /**
     * Kurzname → FQCN. Der Kurzname landet im Frontend (URL, Alpine-
     * State); FQCN bleibt im DB-Feld `subject_type`.
     *
     * @var array<string, class-string<Model>>
     */
    public const TYPES = [
        'Chapter' => Chapter::class,
        'Entry' => Entry::class,
        'Text' => Text::class,
        'Gallery' => Gallery::class,
        'Image' => Image::class,
        'Audiovisual' => Audiovisual::class,
    ];

    /**
     * Aufloesung des Subjects. Gibt `null` zurueck, wenn der Typ nicht
     * whitelistet oder das Modell nicht gefunden wurde — der Caller
     * antwortet dann mit 404.
     */
    public static function resolve(string $shortName, int $id): ?Model
    {
        $class = self::TYPES[$shortName] ?? null;
        if ($class === null) {
            return null;
        }

        return $class::find($id);
    }

    /**
     * Projekt, zu dem das Subject gehoert. Wird fuer die Policy-Checks
     * gebraucht (`view` / `update` gegen das Projekt statt gegen das
     * Content-Model, weil die Content-Policies pro Klasse variieren).
     */
    public static function projectFor(Model $subject): ?Project
    {
        if (! method_exists($subject, 'project')) {
            return null;
        }
        $result = $subject->project();
        if ($result instanceof Relation) {
            /** @var Model|null $model */
            $model = $result->getResults();

            return $model instanceof Project ? $model : null;
        }
        if ($result instanceof Project) {
            return $result;
        }

        return null;
    }

    /**
     * Rueckwaerts-Aufloesung: FQCN → Kurzname (fuer die Panel-JSON).
     */
    public static function shortName(string $fqcn): ?string
    {
        $flipped = array_flip(self::TYPES);

        return $flipped[$fqcn] ?? null;
    }
}
