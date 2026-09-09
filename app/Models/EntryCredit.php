<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

See LICENSE.
 */

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Q4-Etappe 5 / G-Fund-5 (2026-09-09): Credit pro Abschnitt.
 *
 * @property int $id
 * @property int $entry_id
 * @property string $role
 * @property string $name
 * @property Carbon|null $date
 * @property int $position
 */
class EntryCredit extends Model
{
    use HasFactory, SoftDeletes;

    public const ROLE_RECHERCHE = 'recherche';

    public const ROLE_REDAKTION = 'redaktion';

    public const ROLE_HINWEIS = 'hinweis';

    /** @var list<string> */
    public const ROLES = [
        self::ROLE_RECHERCHE,
        self::ROLE_REDAKTION,
        self::ROLE_HINWEIS,
    ];

    protected $fillable = ['entry_id', 'role', 'name', 'date', 'position'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class);
    }
}
