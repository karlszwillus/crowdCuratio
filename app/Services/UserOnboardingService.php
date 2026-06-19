<?php

/**
crowdCuratio - Curating together virtually
Copyright (C)2026 - berlinHistory e.V.

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

namespace App\Services;

use App\Http\Requests\Auth\RegisterRequest;
use App\Models\MailSetting;
use App\Models\User;
use App\Support\RoleName;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Contracts\Role as RoleContract;

/**
 * Block E / Welle E.5 — User-Onboarding aus
 * `RegisteredUserController::store` herausgezogen.
 *
 * Drei Verantwortungen:
 *  1. User-Datensatz per Property-Setter erzeugen (Mass-Assignment-
 *     Schutz auf `is_admin` und `create_project` bleibt aktiv;
 *     beide Felder werden nur gesetzt, wenn der `$caller` selbst
 *     Admin ist — NF-SEC-202).
 *  2. Spatie-Rollen synchronisieren (aufgelöste `Role`-Instanzen
 *     vom Aufrufer übergeben, weil der Resolver kontrollierten
 *     Pfad braucht).
 *  3. Welcome-Notification mit Setting-basierter Einladungs-Text
 *     verschicken.
 *
 * Der `is_admin`-Drift zur Spatie-Rolle ist in Phase 5/6 als
 * Schulden vorgemerkt — dieser Service hält den Pfad sauber, bis
 * die Spalte fällt.
 */
class UserOnboardingService
{
    /**
     * @param  array<int, RoleContract>  $resolvedRoles
     */
    public function createInvitedUser(
        ?User $caller,
        RegisterRequest $request,
        array $resolvedRoles,
    ): User {
        $callerIsAdmin = $caller?->hasRole(RoleName::ADMIN->value) === true;
        $isAdminInvite = $callerIsAdmin && $request->boolean('adminUser');
        $grantCreateProject = $callerIsAdmin && $request->boolean('createProject');

        // Property-Setter umgeht $fillable — bewusst: is_admin und
        // create_project sind privilegierte Felder, der Schutz vor
        // Mass-Assignment bleibt aktiv für Request-basierte Pfade.
        $user = new User;
        $user->name = $request->firstName;
        $user->last_name = $request->lastName;
        $user->email = $request->email;
        $user->password = Hash::make(Str::random(8));
        $user->is_admin = $isAdminInvite;
        $user->create_project = $grantCreateProject;
        $user->save();

        event(new Registered($user));

        $user->assignRole($resolvedRoles);

        $this->sendWelcome($user, $request->firstName);

        return $user;
    }

    private function sendWelcome(User $user, string $firstName): void
    {
        $mail = MailSetting::first();
        $expiresAt = now()->addDays(3);
        $invitation = ($mail !== null && ! empty(strip_tags($mail->invitation ?? '')))
            ? strip_tags($mail->invitation)
            : config('project.mail.default');

        $user->sendWelcomeNotification($expiresAt, $firstName, $invitation);
    }
}
