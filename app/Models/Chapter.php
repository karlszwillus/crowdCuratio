<?php

/**
crowdCuratio - Curating together virtually
Copyright (C)2022, 2026 - berlinHistory e.V.

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
 * @property Project|null $project
 * @property Collection<int, Entry> $entries
 * @property mixed $entry Runtime-Zuweisung in ProjectController::allData (Entry-Snapshot je Chapter), nicht DB-Spalte.
 */
class Chapter extends Model implements HasComments
{
    use HasFactory, HasTranslations, LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['project_id', 'name', 'subtitle', 'description', 'is_translated', 'position'];

    /**
     * Attribute-Casts.
     *
     * NF-LAR-005 (Phase-1-Reviewer): `is_translated` ist in der DB
     * ein TINYINT(1), das Eloquent ohne Cast als string/int liefert.
     * Damit `$chapter->is_translated === true|false` zuverlässig
     * funktioniert, casten wir auf boolean.
     */
    protected $casts = [
        'is_translated' => 'boolean',
    ];

    protected $dates = ['deleted_at'];

    public $translatable = ['name', 'subtitle', 'description'];

    /**
     * Override parent boot and Call deleting event
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(
            function ($chapter) {
                foreach ($chapter->entries()->get() as $entry) {
                    $entry->delete();
                }

                foreach ($chapter->comments()->get() as $comment) {
                    $comment->delete();
                }
            }
        );

    }

    /*
     * Get the chapter that belongs to the project
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get all entries
     *
     * @return HasMany
     */
    public function entries()
    {
        return $this->hasMany(Entry::class)->orderBy('position', 'asc');
    }

    /**
     * Get all comments
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')->whereNull('parent_id');
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
            ->useLogName('Chapter')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
