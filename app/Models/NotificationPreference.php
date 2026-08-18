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
 * Phase 5ac.5: Benachrichtigungs-Praeferenzen pro User (Profil § 5).
 *
 * @property int $user_id
 * @property bool $notify_comments
 * @property bool $notify_publish
 * @property bool $notify_weekly_digest
 */
class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'notify_comments',
        'notify_publish',
        'notify_weekly_digest',
    ];

    protected $casts = [
        'notify_comments' => 'boolean',
        'notify_publish' => 'boolean',
        'notify_weekly_digest' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
