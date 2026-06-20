<?php

/**
crowdCuratio - Curating together virtually
Copyright (C)2022 - berlinHistory e.V.

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

namespace App\Models;

use App\Contracts\HasComments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Lang;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property bool $is_translated
 * @property int|null $media_id Runtime-Zuweisung im ProjectController, nicht DB-Spalte.
 */
class Audiovisual extends Model implements HasComments
{
    use HasFactory, HasTranslations, LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['link', 'source', 'copyright', 'type', 'is_translated'];

    public $translatable = ['link', 'source', 'copyright'];

    /**
     * Get all comments
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')->whereNull('parent_id');
    }

    /**
     * Phase 4 / Block E.7b Sub-Welle 2c (ADR-0022). Pivot-
     * Beziehung über die neuen Spalten content_id/content_type.
     */
    public function mediaContents(): MorphMany
    {
        return $this->morphMany(MediaContent::class, 'content');
    }

    /**
     * Navigiert vom Audiovisual über den Pivot zum Entry → Chapter
     * → Project. Vorbereitung für AudiovisualPolicy in E.7b Welle 3.
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

    /**
     * Add language to log
     */
    public function tapActivity(Activity $activity)
    {
        $activity->properties = $activity->properties->merge([
            'language' => Lang::getLocale(),
        ]);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('MediaContent')
            ->logFillable()
            ->logOnlyDirty();
    }
}
