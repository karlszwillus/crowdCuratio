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

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest für die Registrierungs-/Einladungs-Form.
 *
 * Phase 2 / D.7, ADR-0017. Löst F-LAR-008 (Validator::make im
 * Controller).
 *
 * authorize() ist hier offen — Routing-Middleware (guest/auth)
 * regelt, wer die Route erreicht. Bei /register läuft die
 * Breeze-Standard-`guest`-Middleware; das Pendant unter
 * `Route::resource('/register', …)` im web.php-Auth-Block ist Admin-
 * Tool und mit `auth` middlewared. AM-D-4 (Phase-1-Reviewer) zum
 * Aufräumen dieses Doppel-Routings bleibt für Phase 4 vorgemerkt.
 *
 * Felder kommen aus dem Stakeholder-Workflow `auth.register`:
 *  - firstName / lastName  → User-Name
 *  - email                 → User-Email
 *  - roles                 → Spatie-Permission-Rolle. Beim
 *                            Admin-Invite-Pfad (`adminUser=true`)
 *                            wird die Eingabe ignoriert (der
 *                            Controller setzt die Admin-Rolle
 *                            unabhängig), deshalb ist die Regel
 *                            dort nicht mehr `required` —
 *                            Stakeholder mussten sonst trotz
 *                            Admin-Haken eine Default-Rolle
 *                            auswählen (Stakeholder-Bug
 *                            AM-D-3-Rest).
 *  - policy                → Datenschutz-Bestätigung (Checkbox).
 *  - adminUser / createProject / projectId → optional, je nach
 *    Einladungs-Kontext.
 */
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Beim Admin-Invite ist die Rollen-Wahl egal — der Controller
        // setzt die Admin-Rolle direkt. Daher ist `roles` dort
        // optional.
        $rolesRule = $this->boolean('adminUser') ? 'sometimes' : 'required';

        return [
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'roles' => $rolesRule,
            'policy' => 'required',
            'adminUser' => 'sometimes|boolean',
            'createProject' => 'sometimes|boolean',
            'projectId' => 'sometimes|integer|exists:projects,id',
        ];
    }
}
