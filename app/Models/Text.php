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
class Text extends Model implements HasComments
{
    use HasFactory, HasTranslations, LogsActivity,SoftDeletes;

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
     */
    public function comments(): MorphMany
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
     * Phase 4 / Block E.7b Sub-Welle 2c (ADR-0022).
     *
     * `morphMany` über die neuen Pivot-Spalten `content_id` +
     * `content_type`. Liefert alle MediaContent-Pivot-Einträge,
     * die diesen Text an Entries hängen. Ersetzt mittelfristig die
     * `medias()`-Methode oben, die auf die alten
     * media_contentable_*-Spalten geht — Konsumenten werden in
     * Welle 4 umgestellt.
     */
    public function mediaContents(): MorphMany
    {
        return $this->morphMany(MediaContent::class, 'content');
    }

    /**
     * Navigiert vom Text über den Pivot zum Entry, von dort zum
     * Chapter und zum Project. Vorbereitung für Block E.7b
     * Welle 3 (TextPolicy auf OwnerScopedPolicy).
     *
     * Liefert `null`, wenn der Text noch nicht an einen Entry
     * gehängt ist (Race-Case zwischen Service::create und
     * attachToEntry).
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
            ->useLogName('Text')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
