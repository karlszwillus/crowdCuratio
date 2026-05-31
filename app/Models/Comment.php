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

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Lang;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Translatable\HasTranslations;

class Comment extends Model
{
    use HasFactory, HasTranslations, LogsActivity, SoftDeletes;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'project_id',
        'parent_id',
        'comment',
        'status',
        'commentable_id',
        'commentable_type',
    ];

    protected $dates = ['deleted_at'];

    public $translatable = ['comment'];

    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get chapter
     *
     * @return MorphToMany
     */
    public function chapter()
    {
        return $this->morphToMany(Chapter::class, 'commentable', 'comments', 'commentable_id', 'id');
    }

    /**
     * relationship
     *
     * @return MorphTo
     */
    public function commentable()
    {
        return $this->morphTo();
    }

    /**
     * Get media
     *
     * @return MorphToMany
     */
    public function media()
    {
        return $this->morphToMany(
            'App\Models\Entry',
            'media_contentable',
            'media_content',
            'id',
            'media_contentable_id',
            '',
            '',
            ''
        )->withPivot('media_contentable_id');
    }

    /**
     * Get project
     *
     * @return BelongsTo
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    /**
     * Get content
     *
     * @return BelongsTo
     */
    public function content()
    {
        return $this->belongsTo(MediaContent::class, 'commentable_id', 'id');
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
            ->useLogName('Entry')
            ->logFillable()
            ->logOnlyDirty();
    }
}
