<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 */

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase 5ab.5: Sync-Marker fuer eine einzelne Uebersetzung.
 *
 * @property int $id
 * @property string $subject_type
 * @property int $subject_id
 * @property string $field
 * @property string $locale
 * @property int $source_revision_id
 * @property-read Revision $sourceRevision
 */
class TranslationSourceReference extends Model
{
    protected $fillable = [
        'subject_type',
        'subject_id',
        'field',
        'locale',
        'source_revision_id',
    ];

    public function sourceRevision(): BelongsTo
    {
        return $this->belongsTo(Revision::class, 'source_revision_id');
    }
}
