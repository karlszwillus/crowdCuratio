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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Lang;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class MediaContent extends Model implements HasComments
{
    use HasFactory,LogsActivity,SoftDeletes;

    public $timestamps = false;

    protected $table = 'media_content';

    /**
     * The attributes that are mass assignable.
     *
     * Phase 4 / Block E.7b Sub-Welle 4e (ADR-0022): Cleanup. Die alten
     * media_content_id- und media_contentable-Spalten sind in der
     * Spalten-Drop-Migration entfernt; das Modell führt nur noch die
     * neuen content_- und parent_-Spalten.
     *
     * @var list<string>
     */
    protected $fillable = [
        'content_id',
        'content_type',
        'parent_id',
        'parent_type',
        'position',
    ];

    /**
     * Override parent boot and Call deleting event
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(
            function ($content) {
                foreach ($content->gallery()->get() as $gallery) {
                    $gallery->delete();
                }
                foreach ($content->text()->get() as $text) {
                    $text->delete();
                }
                foreach ($content->comments()->get() as $comment) {
                    $comment->delete();
                }

            }

        );
    }

    /**
     * Sauberer morphTo auf das Content-Modell (Text/Image/Gallery/
     * Audiovisual). Phase 4 / Block E.7b Sub-Welle 2b (ADR-0022).
     *
     * Liest aus den neuen Spalten `content_id` + `content_type` —
     * im Gegensatz zur Tag-Spalten-Semantik der alten
     * media_contentable_*-Spalten. Cleanup der alten in Sub-Welle 4.
     */
    public function content(): MorphTo
    {
        return $this->morphTo('content');
    }

    /**
     * Sauberer morphTo auf den Parent (heute durchgehend Entry).
     * Phase 4 / Block E.7b Sub-Welle 2b (ADR-0022).
     *
     * Liest aus den neuen Spalten `parent_id` + `parent_type`.
     * Die alten `media_contentable_*`-Spalten haben hier zwar die
     * Parent-ID gespeichert, aber den Type des Contents (nicht
     * des Parents) — siehe ADR-0022 für die historische Erklärung.
     */
    public function parent(): MorphTo
    {
        return $this->morphTo('parent');
    }

    /**
     * Get image
     *
     * Phase 4 / Block E.7b Sub-Welle 4a (ADR-0022): Foreign-Key
     * wechselt von `media_content_id` auf `content_id`. Während der
     * Doppelschreibungs-Welle 2d sind beide Spalten gleichwertig
     * befüllt; in Welle 4e wird die alte Spalte gedroppt. Der
     * Diskriminator-Check (Gallery vs. Image vs. Text) bleibt in
     * den Aufrufern (Views, Controllers).
     *
     * @return BelongsTo
     */
    public function image()
    {
        return $this->belongsTo(Image::class, 'content_id', 'id');
    }

    /**
     * Get text
     *
     * Phase 4 / Block E.7b Sub-Welle 4a (ADR-0022): siehe image().
     *
     * @return BelongsTo
     */
    public function text()
    {
        return $this->belongsTo(Text::class, 'content_id', 'id');
    }

    /**
     * Get entry
     *
     * Phase 4 / Block E.7b Sub-Welle 4a (ADR-0022): Foreign-Key
     * wechselt von `media_contentable_id` auf `parent_id`. Inhalt
     * bleibt identisch — die Beziehung zeigt auf den Entry-Parent
     * des Pivot.
     *
     * @return BelongsToMany
     */
    public function entry()
    {
        return $this->belongsToMany(Entry::class, 'media_content', 'id', 'parent_id');
    }

    /**
     * Get all comments
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')->whereNull('parent_id');
    }

    /**
     * Get gallery
     *
     * Phase 4 / Block E.7b Sub-Welle 4a (ADR-0022): Foreign-Key
     * wechselt von `media_content_id` auf `content_id`. Siehe image().
     *
     * @return BelongsTo
     */
    public function gallery()
    {
        return $this->belongsTo(Gallery::class, 'content_id', 'id');
    }

    /**
     * Get Audiovisual
     *
     * Phase 4 / Block E.7b Sub-Welle 4a (ADR-0022): Foreign-Key
     * wechselt von `media_content_id` auf `content_id`. Siehe image().
     *
     * @return BelongsTo
     */
    public function audiovisual()
    {
        return $this->belongsTo(Audiovisual::class, 'content_id', 'id');
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
