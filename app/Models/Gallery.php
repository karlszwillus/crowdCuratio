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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
 * @property Collection<int, Image> $images
 * @property Collection<int, Image>|null $image_list Runtime-Snapshot der images-Relation für den Preview-Render.
 */
class Gallery extends Model implements HasComments
{
    use HasFactory, HasTranslations, LogsActivity, SoftDeletes;

    /**
     * Override parent boot and Call deleting event
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(
            function ($gallery) {
                foreach ($gallery->images()->get() as $image) {
                    $image->delete();
                }
            }
        );
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['title', 'subtitle', 'description'];

    public $translatable = ['title', 'subtitle', 'description'];

    /**
     * Get all images
     *
     * @return HasMany
     */
    public function images()
    {
        return $this->hasMany(Image::class, 'gallery_id', 'id')->orderBy('position', 'asc');
    }

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
     * Navigiert von der Gallery über den Pivot zum Entry → Chapter
     * → Project. Vorbereitung für GalleryPolicy in E.7b Welle 3.
     *
     * Bei Galerien historisch besonders relevant, weil
     * `GalleryService::attachToEntry` `Image::class` als
     * media_contentable_type setzte — die neue `content_type`-
     * Spalte hat den korrekten Gallery::class-Wert nach Backfill
     * (Sub-Welle 2a).
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
            ->useLogName('Gallery')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
