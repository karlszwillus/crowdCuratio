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
    /**
     * Q4-Etappe 3 / C0b (2026-09-07): `citation_depth` und
     * `source_required` als projektweite Zitier-Settings ergaenzt.
     */
    protected $fillable = ['name', 'logo', 'imprint', 'terms', 'status', 'description', 'citation_depth', 'source_required', 'reader_layout', 'character', 'accent_color'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source_required' => 'boolean',
        ];
    }

    public $translatable = ['name', 'imprint', 'terms', 'description'];

    /**
     * Q4-Etappe 3 / C0b: Convenience-Helpers fuer die Zitier-Settings
     * — werden von source-picker und Content-Bloecken gebraucht, damit
     * die UI entscheiden kann, ob die Zitier-Tiefe-Felder sichtbar und
     * die Copyright/Origin-Felder Pflicht sind.
     */
    public function usesFullCitationDepth(): bool
    {
        return ($this->citation_depth ?? 'simple') === 'full';
    }

    public function requiresSources(): bool
    {
        return (bool) ($this->source_required ?? true);
    }

    public const READER_LAYOUT_ONE_PAGE = 'one-page';

    public const READER_LAYOUT_MULTI_PAGE = 'multi-page';

    /**
     * Q4-Etappe 5 / G1 (2026-09-08): Reader-Layout pro Projekt.
     * `one-page` (Default) — Long-Scroll für kleine Projekte;
     * `multi-page` — Sidebar links, Deeplink pro Kapitel.
     */
    public function usesMultiPageReader(): bool
    {
        return ($this->reader_layout ?? self::READER_LAYOUT_ONE_PAGE) === self::READER_LAYOUT_MULTI_PAGE;
    }

    public function readerLayout(): string
    {
        return in_array($this->reader_layout ?? null, [self::READER_LAYOUT_ONE_PAGE, self::READER_LAYOUT_MULTI_PAGE], true)
            ? (string) $this->reader_layout
            : self::READER_LAYOUT_ONE_PAGE;
    }

    public const CHARACTER_DOKUMENTATION = 'dokumentation';

    public const CHARACTER_ARCHIV = 'archiv';

    public const CHARACTER_ERZAEHLUNG = 'erzaehlung';

    /**
     * Q4-Etappe 5 / G-Fund-1 (2026-09-09): Reader-Charakter (Handoff
     * v4-Export). Default `dokumentation` (Handoff-Empfehlung).
     */
    public function character(): string
    {
        return in_array($this->character ?? null, [
            self::CHARACTER_DOKUMENTATION,
            self::CHARACTER_ARCHIV,
            self::CHARACTER_ERZAEHLUNG,
        ], true)
            ? (string) $this->character
            : self::CHARACTER_DOKUMENTATION;
    }
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
            // Q4-Etappe 4 / F1 (2026-09-08): Zitat-Block inkl.
            // Comments für den Kommentar-Badge in der Blockkopf-Zeile.
            'chapters.entries.mediaContent.quoteBlock.source',
            'chapters.entries.mediaContent.quoteBlock.comments',
            // Q4-Etappe 4 / G1 (2026-09-08): Daten-und-Fakten-Block.
            'chapters.entries.mediaContent.dataFactBlock.comments',
            // Q4-Etappe 5 / G-Fund-5 (2026-09-09): Credits pro Abschnitt.
            'chapters.entries.credits',
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
            // Q4-Etappe 4 / F1 (2026-09-08): Zitat-Block.
            'chapters.entries.mediaContent.quoteBlock.source',
            // Q4-Etappe 4 / G1 (2026-09-08): Daten-und-Fakten-Block.
            'chapters.entries.mediaContent.dataFactBlock',
            // Q4-Etappe 5 / G-Fund-5 (2026-09-09): Credits pro Abschnitt.
            'chapters.entries.credits',
            // Bild- und Text-Sources fürs Nachweiszeilen-/Quellenblock-
            // Rendering im Reader (G-Fund-5).
            'chapters.entries.mediaContent.text.copyrightText',
            'chapters.entries.mediaContent.text.originText',
            'chapters.entries.mediaContent.gallery.images.copyrightImage',
            'chapters.entries.mediaContent.gallery.images.originImage',
            'chapters.entries.mediaContent.audiovisual.copyrightSource',
            'chapters.entries.mediaContent.audiovisual.originSource',
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
