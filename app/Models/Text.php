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

use App\Traits\CommentTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
 * @property Source|null $originText
 * @property Source|null $copyrightText
 */
class Text extends Model
{
    use CommentTrait, HasFactory, HasTranslations, LogsActivity,SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * `position` ist in der DB als Spalte vorhanden, wird in der
     * Anwendung aber nicht ausgewertet — die Reihenfolge eines Texts
     * im Eintrag steckt in `media_content.position`. Beim Strict-Mode-
     * Save iteriert Spatie-Activitylog über `$fillable` und würde auf
     * `position` zugreifen; deshalb raus aus der Liste. Die Spalte
     * selbst wandert mit dem Phase-4-Schema-Refactor weg.
     *
     * `id` gehört nicht in $fillable — Primary Key wird von Eloquent
     * verwaltet, nie mass-assigned.
     *
     * @var list<string>
     */
    protected $fillable = ['text', 'origin', 'copyright'];

    public $translatable = ['text'];

    /**
     * Get text origin
     *
     * @return BelongsTo
     */
    public function originText()
    {
        return $this->belongsTo(Source::class, 'origin');
    }

    /**
     * Get text copyright
     *
     * @return BelongsTo
     */
    public function copyrightText()
    {
        return $this->belongsTo(Source::class, 'copyright');
    }

    /**
     * Get all comments
     *
     * @return MorphMany
     */
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable')->whereNull('parent_id');
    }

    /**
     * Get media
     *
     * @return MorphMany
     */
    public function medias()
    {
        return $this->morphMany(MediaContent::class, 'media');
    }

    public function entry()
    {
        return $this->morphToMany('App\Models\Text', 'media_contentable', 'media_content', 'media_contentable_id', 'id');
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
            ->useLogName('Text')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
