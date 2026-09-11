<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

See LICENSE.
 */

declare(strict_types=1);

namespace App\Models;

use App\Contracts\HasComments;
use App\Support\CascadesToMediaContent;
use App\Support\HasRevisions;
use App\Support\TouchesEntryViaMediaContent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Translatable\HasTranslations;

/**
 * Q4-Etappe 4 / F1 (2026-09-08): Zitat-Block als eigener Content-Type
 * neben Text / Gallery / Audiovisual. Wird polymorph über
 * `media_content.content_type = App\Models\QuoteBlock` an den Entry
 * gehängt.
 *
 * @property int $id
 * @property string|null $text
 * @property string|null $speaker
 * @property string|null $date_text
 * @property string|null $kind
 * @property string|null $lang_original
 * @property string|null $text_original
 * @property int|null $source_id
 * @property string|null $locator
 * @property bool $is_translated
 * @property-read Source|null $source
 */
class QuoteBlock extends Model implements HasComments
{
    use CascadesToMediaContent, HasFactory, HasRevisions, HasTranslations, LogsActivity, SoftDeletes, TouchesEntryViaMediaContent;

    protected $fillable = [
        'text',
        'speaker',
        'date_text',
        'kind',
        'lang_original',
        'text_original',
        'source_id',
        'locator',
        'is_translated',
    ];

    /**
     * Nur `text` ist übersetzbar. `speaker` bleibt monolingual (Namen
     * werden nicht übersetzt); `text_original` ist per Definition in
     * der Original-Sprache und braucht daher keinen Translations-Cast.
     *
     * @var list<string>
     */
    public $translatable = ['text'];

    protected function casts(): array
    {
        return [
            'is_translated' => 'boolean',
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    public function mediaContents(): MorphMany
    {
        return $this->morphMany(MediaContent::class, 'content');
    }

    /**
     * Comments am Zitat-Block — analog zu Text/Gallery/Audiovisual
     * über polymorphe `commentable`-Beziehung. Nur Top-Level, Threads
     * hängen an ihrer Root.
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')->whereNull('parent_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('QuoteBlock')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Navigiert vom Zitat-Block über MediaContent → Entry → Chapter
     * → Project. Analog zu Text::project(). Wird von der Policy
     * für die project-scoped Auth-Kette verwendet.
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
}
