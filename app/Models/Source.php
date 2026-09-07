<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2022, 2026 - berlinHistory e.V.

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
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property int|null $project_id
 * @property string $name
 * @property string|null $title
 * @property string|null $holding
 * @property string|null $signature
 * @property string $type
 * @property string|null $kind
 * @property bool $is_translated
 * @property-read Project|null $project
 */
class Source extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    /**
     * Q4-Etappe 3 / C0-8a (2026-09-07): `content_id` aus $fillable
     * entfernt — die Spalte existiert nicht in der DB und war ein
     * toter Legacy-Eintrag. Neue Felder aus dem Projekt-Scope-
     * Schema (project_id, kind, title, holding, signature) ergaenzt.
     *
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'name',
        'title',
        'holding',
        'signature',
        'type',
        'kind',
        'is_translated',
    ];

    protected $dates = ['deleted_at'];

    public $translatable = ['name'];

    /**
     * Projekt-Scope. Nullable, weil Alt-Rows aus der Vor-C0-Zeit
     * noch keinen Scope haben — der Migrations-Assistent (C0-8b)
     * zieht sie pro Projekt nach.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
