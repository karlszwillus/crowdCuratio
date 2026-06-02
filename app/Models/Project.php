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
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property int $user_id
 * @property string $logo
 * @property Collection<int, Chapter> $chapters
 * @property User|null $user
 */
class Project extends Model implements HasComments
{
    use HasFactory, HasPermissions, HasTranslations,LogsActivity, SoftDeletes;

    protected $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     *
     * F-SEC-010: `user_id` ist hier bewusst raus. Die Spalte trägt
     * die Eigentümerschaft eines Projects und darf NIEMALS über
     * Mass-Assignment aus einem Request gesetzt werden — sonst
     * könnte ein Angreifer beim Anlegen ein Project an einen anderen
     * User hängen. `ProjectController::store()` setzt `user_id`
     * explizit über den Property-Setter aus `Auth::user()->id`.
     *
     * @var list<string>
     */
    protected $fillable = ['name', 'logo', 'imprint', 'terms', 'status', 'description'];

    public $translatable = ['name', 'imprint', 'terms', 'description'];
    /*
     * Get all of the chapters for the project
     */

    public function chapters()
    {
        return $this->hasMany(Chapter::class)->orderBy('position', 'asc');
    }

    /*
     * Get user from project
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /*
     * Get single chapter from project
     */
    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }

    /**
     * Get comments
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')->whereNull('parent_id');
    }

    /**
     * Granted users
     *
     * @return HasMany
     */
    public function permittedUsers()
    {
        return $this->hasMany(ModelHasPermission::class, 'project_id');
    }

    /**
     * Grant user's right
     *
     * @return HasMany
     */
    public function grantUserRights()
    {
        return $this->hasMany(ProjectUserPermission::class);
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

    /**
     * Eager-Loading-Baum für die projects/edit-Hierarchie.
     *
     * Inkludiert Chapter/Entry/MediaContent inkl. Source-Relations
     * (copyright/origin auf Text und Image) und Comments auf jeder
     * Ebene. Wird in ProjectController::edit und in
     * ChapterController::index genutzt; ohne den Baum wirft
     * Model::preventLazyLoading (Phase 2 / C.1) Exceptions auf den
     * tiefen Property-Zugriffen in chapters/index.blade.php.
     *
     * Phase-4-Migration: sobald ein ProjectService existiert, kann
     * dieser Scope nach dort wandern.
     */
    public function scopeWithEditTree($query)
    {
        return $query->with([
            'chapters.comments',
            'chapters.entries.comments',
            'chapters.entries.mediaContent.comments',
            'chapters.entries.mediaContent.text.comments',
            'chapters.entries.mediaContent.text.copyrightText',
            'chapters.entries.mediaContent.text.originText',
            'chapters.entries.mediaContent.audiovisual.comments',
            'chapters.entries.mediaContent.gallery.comments',
            'chapters.entries.mediaContent.gallery.images.comments',
            'chapters.entries.mediaContent.gallery.images.copyrightImage',
            'chapters.entries.mediaContent.gallery.images.originImage',
        ]);
    }

    /**
     * Eager-Loading-Baum für die preview-Hierarchie (HTML und PDF).
     *
     * Schmaler als withEditTree — die Preview-Views rendern weder
     * Source-Relations noch Comments, sondern nur die Inhalts-
     * Hierarchie. Wird in ProjectController::previewProject und
     * ::downloadPreview genutzt.
     */
    public function scopeWithPreviewTree($query)
    {
        return $query->with([
            'chapters.entries.mediaContent.text',
            'chapters.entries.mediaContent.gallery.images',
            'chapters.entries.mediaContent.audiovisual',
        ]);
    }

    /**
     * Eager-Loading-Baum für die Translate-Ansicht.
     *
     * `ProjectController::allData` iteriert über chapters/entries/
     * mediaContent und greift dabei auf `$entry->mediaContent` zu —
     * unter Strict-Mode wirft das ohne Eager-Loading eine
     * LazyLoadingViolation. Die einzelnen Text/Audiovisual/Gallery-
     * Modelle werden im Controller anschließend per `Model::find()`
     * (mit ihren eigenen Eager-Loads) nachgeladen, die brauchen
     * deshalb nicht zum Scope.
     */
    public function scopeWithTranslateTree($query)
    {
        return $query->with([
            'chapters.entries.mediaContent',
        ]);
    }

    /**
     * Eager-Loading-Baum für die copyright-/Impressums-View.
     *
     * Flach — preview/copyright.blade.php rendert nur eine
     * Kapitelliste mit Namen. Wird in
     * ProjectController::projectMetadata genutzt.
     */
    public function scopeWithCopyrightTree($query)
    {
        return $query->with('chapters');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Project')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
