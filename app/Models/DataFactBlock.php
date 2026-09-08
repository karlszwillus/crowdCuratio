<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

See LICENSE.
 */

declare(strict_types=1);

namespace App\Models;

use App\Contracts\HasComments;
use App\Support\HasRevisions;
use App\Support\TouchesEntryViaMediaContent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Translatable\HasTranslations;

/**
 * Q4-Etappe 4 / G1 (2026-09-08): Daten-und-Fakten-Block.
 *
 * `title` und `subtitle` sind HasTranslations (JSON pro Locale).
 * `rows` ist ein Array von Objekten `[{label, value}, …]` — jedes
 * `label` und `value` selbst ein Locale-Map (`{"de": "...",
 * "en": "..."}`), damit die Zeilen mit dem Rest des Editors
 * übersetzt werden können. Kein separater Row-Model + Migration:
 * die Zeilen sind schlank, sortier-nur-per-Reihenfolge im Array,
 * ohne eigene Kommentare oder Revisions.
 *
 * @property int $id
 * @property string $layout
 * @property string|null $title
 * @property string|null $subtitle
 * @property array<int, array{header: array<string, string>}>|null $columns
 * @property array<int, array<string, mixed>>|null $rows
 */
class DataFactBlock extends Model implements HasComments
{
    use HasFactory, HasRevisions, HasTranslations, LogsActivity, SoftDeletes, TouchesEntryViaMediaContent;

    protected $table = 'data_fact_blocks';

    protected $fillable = ['layout', 'title', 'subtitle', 'columns', 'rows'];

    /** @var list<string> */
    public $translatable = ['title', 'subtitle'];

    protected function casts(): array
    {
        return [
            'columns' => 'array',
            'rows' => 'array',
        ];
    }

    public const LAYOUT_STECKBRIEF = 'steckbrief';

    public const LAYOUT_TABELLE = 'tabelle';

    public function isTabellenLayout(): bool
    {
        return ($this->layout ?? self::LAYOUT_STECKBRIEF) === self::LAYOUT_TABELLE;
    }

    public function mediaContents(): MorphMany
    {
        return $this->morphMany(MediaContent::class, 'content');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')->whereNull('parent_id');
    }

    /**
     * Navigiert vom Fakten-Block über MediaContent → Entry →
     * Chapter → Project. Analog zu Text::project() / QuoteBlock.
     */
    public function project(): ?Project
    {
        /** @var MediaContent|null $pivot */
        $pivot = $this->mediaContents()->first();
        if ($pivot === null) {
            return null;
        }

        /** @var Entry|null $parent */
        $parent = $pivot->parent;

        return $parent?->chapter?->project;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('DataFactBlock')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
