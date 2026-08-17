<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 */

declare(strict_types=1);

namespace App\Models;

use App\Support\RevisionKind;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Phase 5ab.1: Eine Fassung eines Content-Objekts.
 *
 * Wird vom HasRevisions-Trait auf den sechs Content-Modellen
 * geschrieben; das Verlauf-Panel liest die Liste polymorphisch aus.
 *
 * @property int $id
 * @property string $subject_type
 * @property int $subject_id
 * @property int|null $actor_id
 * @property string $kind
 * @property string|null $summary
 * @property array{changes: array<string, array{old: mixed, new: mixed}>, meta?: array<string, mixed>} $snapshot
 * @property int $version
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Model $subject
 * @property-read User|null $actor
 */
class Revision extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_type',
        'subject_id',
        'actor_id',
        'kind',
        'summary',
        'snapshot',
        'version',
    ];

    protected $casts = [
        'snapshot' => 'array',
        'version' => 'integer',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Deutsche Chip-Beschriftung aus dem Kind-Enum.
     */
    protected function kindLabel(): Attribute
    {
        return Attribute::get(function (): string {
            $kind = RevisionKind::tryFrom((string) $this->kind);

            return $kind?->label() ?? (string) $this->kind;
        });
    }
}
