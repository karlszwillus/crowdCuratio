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

use App\Http\Controllers\Auth\MyCustomWelcomeNotification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;
use Spatie\WelcomeNotification\ReceivesWelcomeNotification;

class User extends Authenticatable
{
    use HasFactory, HasRoles, LogsActivity, Notifiable, ReceivesWelcomeNotification, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * NF-SEC-202 / Phase-2.5-Hotfix: `is_admin` und `create_project`
     * sind hier bewusst raus. Beide Felder steuern Berechtigungen
     * im System und dürfen NIEMALS über Mass-Assignment aus einem
     * Request gesetzt werden. Wer sie schreiben will, ruft
     * `setAttribute()` bzw. weist direkt zu (`$user->is_admin = …`)
     * und hat damit die Verantwortung, vorher die Aufrufer-Identität
     * zu prüfen — siehe `RegisteredUserController::store()`. Das
     * `created_at` gehört ebenfalls nicht ins `$fillable`; Laravel
     * pflegt es als Timestamp automatisch.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'last_name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        // F-LAR-005 / Phase-1-Reviewer: welcome_valid_until ist seit
        // 2021_05_07 in der users-Tabelle als DATETIME, wurde aber bisher
        // nicht in $casts geführt — Controller-Code hat manuell parse()
        // gerufen. Mit dem Cast bekommt jeder Property-Zugriff direkt
        // ein Carbon-Objekt.
        'welcome_valid_until' => 'datetime',
        'is_admin' => 'boolean',
        'create_project' => 'boolean',
    ];

    /**
     * Get role of user
     *
     * @return BelongsTo
     */
    public function role()
    {
        return $this->belongsTo(ModelHasRole::class, 'id', 'model_id');
    }

    public function isAdmin()
    {
        return $this->roles()->where('name', 'Admin')->exists();
    }

    public function projects()
    {

        return $this->hasMany(Project::class);
    }

    public function sendWelcomeNotification(Carbon $validUntil, $firstName, $settingsContent)
    {
        $this->notify(new MyCustomWelcomeNotification($validUntil, $firstName, $settingsContent));
    }

    public function currentRole()
    {
        return $this->belongsToMany(Role::class, 'model_has_roles', 'model_id', 'role_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('User')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
