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

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\AccountDeletion\HandoverMissingException;
use App\Http\Requests\ScheduleAccountDeletionRequest;
use App\Http\Requests\UpdateOwnPasswordRequest;
use App\Http\Requests\UpdateOwnProfileRequest;
use App\Models\NotificationPreference;
use App\Models\Project;
use App\Models\ProjectUserPermission;
use App\Models\User;
use App\Services\AccountDeletionService;
use App\Services\AvatarService;
use App\Services\ProjectPermissionService;
use App\Support\InitialsBlocklist;
use App\Support\ProfilePalette;
use App\Support\RoleName;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Spatie\Permission\Models\Role;

/**
 * Q4-Etappe 1 / I8 (2026-08-27): Self-Service-Endpunkte des eingeloggten
 * Users. Frueher waren die als Methoden am `UserController` angehangen,
 * neben dem Admin-User-Management — die Klassen-Grenze zwischen Admin
 * (`role:Admin`) und Self-Service (`auth`) war nur ueber Route-Middleware
 * sichtbar.
 *
 * Enthaelt:
 *  - `profile()`                — Karten-Sicht mit Personendaten,
 *                                  Preferences, „Meine Projekte & Rollen",
 *                                  Konto-Loeschung.
 *  - `updateProfile()`          — Personendaten + Avatar + Preferences.
 *  - `updatePassword()`         — Passwort-Wechsel-Endpoint (eigene Karte).
 *  - `updateLocale()`           — Live-Sprache (JSON).
 *  - `updateTheme()`            — Live-Theme (JSON).
 *  - `checkInitials()`          — Live-Blur-Check fuers Kuerzel (JSON).
 *  - `scheduleDeletion()`       — DSGVO Konto-Loeschung anmelden.
 *  - `cancelScheduledDeletion()`— DSGVO Konto-Loeschung zuruecknehmen.
 */
class ProfileController extends Controller
{
    public function __construct(
        // B2 (2026-08-21) / DSGVO: Konto-Loeschung mit 30-Tage-Frist.
        private readonly AccountDeletionService $accountDeletion,
    ) {
        $this->middleware('auth');
    }

    /**
     * @return Application|Factory|View
     */
    public function profile()
    {
        $roles = Role::pluck('name', 'name')->all();

        // Phase 5ac.3: Projekte + Rolle pro Projekt fuer die Lese-
        // Karte „Meine Projekte & Rollen". Aggregation ueber den
        // bestehenden ProjectPermissionService, damit die Sicht mit
        // der Projektliste konsistent bleibt.
        /** @var User $me */
        $me = auth()->user();
        // shouldBeStrict() sperrt Lazy-Loading — Rollen explizit
        // eager-laden, damit die Rollen-Iteration unten nicht crasht.
        $me->loadMissing('roles');
        $service = app(ProjectPermissionService::class);
        $projectsRaw = $service->listProjectsForUser($me);
        $ownRoleNames = $me->roles->pluck('name')->all();

        $profileProjects = $projectsRaw->map(function ($project) use ($me, $ownRoleNames): array {
            /** @var Project $project */
            $isOwner = (int) $project->user_id === (int) $me->id;
            // Q3-Politur G4 (2026-08-20) / UX-06: Rollen-Chip bekommt
            // eine Erklaerung via `role_desc`. Der View haengt die als
            // Tooltip an den Chip.
            if ($isOwner) {
                $roleLabel = __('profile_project_role_owner');
                $roleDesc = __('profile_project_role_owner_desc');
            } elseif (
                ProjectUserPermission::query()
                    ->where('project_id', $project->id)
                    ->where('user_id', $me->id)
                    ->exists()
            ) {
                $roleLabel = __('profile_project_role_member');
                $roleDesc = __('profile_project_role_member_desc');
            } elseif (in_array(RoleName::ADMIN->value, $ownRoleNames, true)) {
                $roleLabel = __('profile_project_role_admin');
                $roleDesc = __('profile_project_role_admin_desc');
            } else {
                $roleLabel = __('profile_project_role_reader');
                $roleDesc = __('profile_project_role_reader_desc');
            }

            // Kontext-Zahl: fuer Runde 1 einheitlich „N Kapitel". Die
            // rollenabhaengige Formulierung (Eintraege / offene
            // Kommentare / eigene Beitraege) folgt in 5ac.3-Followup.
            $chapterCount = (int) ($project->chapters_count ?? 0);
            $contextText = trans_choice('profile_project_context_chapters', $chapterCount, ['count' => $chapterCount]);

            return [
                'id' => $project->id,
                'name' => (string) $project->name,
                'role' => $roleLabel,
                'role_desc' => $roleDesc,
                'context' => $contextText,
                'is_owner' => $isOwner,
            ];
        })->values();

        // Phase 5ac.5: bestehende Benachrichtigungs-Praeferenzen — oder
        // ein frisches Objekt mit den Defaults, damit das Blade in beiden
        // Faellen dieselben Zugriffe hat.
        $prefs = NotificationPreference::firstOrNew(['user_id' => $me->id]);
        if (! $prefs->exists) {
            $prefs->notify_comments = true;
            $prefs->notify_publish = true;
            $prefs->notify_weekly_digest = false;
        }

        // B2 (2026-08-21) / DSGVO: fuer die Konto-Loeschen-Karte
        // brauchen wir die owned Projekte (Uebergabe-Pflicht) und die
        // moeglichen Uebergabe-Empfaenger.
        $ownedProjects = Project::query()
            ->where('user_id', $me->id)
            ->orderBy('name')
            ->get(['id', 'name']);
        $handoverCandidates = $ownedProjects->isEmpty()
            ? collect()
            : $this->accountDeletion->candidatesForHandover($me);

        return view('users.profile', compact('roles', 'profileProjects', 'prefs', 'ownedProjects', 'handoverCandidates'));
    }

    public function updateProfile(UpdateOwnProfileRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        $user->name = $validated['firstName'];
        $user->last_name = $validated['lastName'];

        // Phase 5ac.1: Kuerzel + Farbe (optional).
        if (array_key_exists('initials', $validated)) {
            $trimmed = trim((string) $validated['initials']);
            $user->initials = $trimmed === '' ? null : mb_strtoupper($trimmed);
        }
        if (array_key_exists('initials_color', $validated)) {
            $color = (string) $validated['initials_color'];
            $user->initials_color = in_array($color, ProfilePalette::TOKENS, true) ? $color : null;
        }

        // Phase 5ac.2: Avatar-Upload. remove_avatar=1 wins gegen ein
        // hochgeladenes File — der Nutzer will dann sein Bild los.
        // getAttribute() umgeht die MissingAttributeException, falls
        // der Factory-User die 5ac.1-Spalten noch nicht in seinem
        // attributes-Array hat (kommt im Test-Pfad ohne Reload vor).
        $existingAvatar = null;
        try {
            $existingAvatar = $user->getAttribute('avatar_path');
        } catch (\Throwable $e) {
            // Feld fehlt im Attribute-Set — Alt-Datei existiert nicht.
        }

        if (! empty($validated['remove_avatar'])) {
            app(AvatarService::class)->remove($existingAvatar);
            $user->avatar_path = null;
        } elseif ($request->hasFile('avatar')) {
            $newFile = app(AvatarService::class)->store($request->file('avatar'));
            if ($newFile !== null) {
                app(AvatarService::class)->remove($existingAvatar);
                $user->avatar_path = $newFile;
            }
        }

        if (filled($validated['new_password'] ?? null)) {
            $user->password = Hash::make($validated['new_password']);
        }

        $user->save();

        // Phase 5ac.5: Benachrichtigungs-Praeferenzen als eigene Zeile
        // (updateOrCreate, damit auch neue User keine leere DB-Zeile
        // vorher brauchen). Toggles sind Checkboxen — nicht gesendet
        // heisst false.
        NotificationPreference::updateOrCreate(
            ['user_id' => $user->id],
            [
                'notify_comments' => (bool) ($validated['notify_comments'] ?? false),
                'notify_publish' => (bool) ($validated['notify_publish'] ?? false),
                'notify_weekly_digest' => (bool) ($validated['notify_weekly_digest'] ?? false),
            ]
        );

        return redirect()->back()->with('success', __('message_edit_profile_success'));
    }

    /**
     * Phase 5ac.4: Eigener Endpoint fuer den Passwort-Wechsel — die
     * Karte hat ihren eigenen Save-Button, damit ein Passwort-Wechsel
     * nicht am Vornamen haengt. Bestehende UpdateOwnProfileRequest-
     * Logik bleibt fuer Rueckwaertskompat.
     */
    public function updatePassword(UpdateOwnPasswordRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->password = Hash::make($request->validated()['new_password']);
        $user->save();

        return redirect()->route('profile')->with('success', __('profile_password_updated'));
    }

    /**
     * Phase 5ac.1: Sofort-Wirkung fuer Sprache und Theme — kein
     * Save-Kandidat, aendert sich mit dem Klick. Beide Endpoints
     * nehmen einen JSON-Payload und antworten mit 204, damit der
     * Client nur reloaden muss.
     */
    public function updateLocale(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(401);
        }
        $locale = (string) $request->input('locale');
        $allowed = array_keys((array) Config::get('languages'));
        if (! in_array($locale, $allowed, true)) {
            abort(422, 'Unknown locale.');
        }
        $user->locale = $locale;
        $user->save();
        Session::put('applocale', $locale);

        return response()->json(['ok' => true]);
    }

    public function updateTheme(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(401);
        }
        $theme = (string) $request->input('theme');
        if (! in_array($theme, ['crowdCuratio', 'aktivesMuseum'], true)) {
            abort(422, 'Unknown theme.');
        }
        $user->theme = $theme;
        $user->save();

        return response()->json(['ok' => true]);
    }

    /**
     * Q3-Politur G9 (2026-08-20) / UX-01: Live-Blur-Check fuers Kuerzel.
     * Der FormRequest prueft weiterhin final beim Save — hier ist der
     * vorlaeufige JSON-Endpoint fuer die Sofort-Rueckmeldung.
     */
    public function checkInitials(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(401);
        }
        $candidate = (string) $request->input('initials', '');
        $blocked = InitialsBlocklist::isBlocked($candidate);
        $suggestions = $blocked
            ? InitialsBlocklist::suggestFor((string) $user->name, (string) $user->last_name)
            : [];

        return response()->json([
            'blocked' => $blocked,
            'message' => $blocked ? __('profile_initials_blocked') : null,
            'suggestions' => $suggestions,
        ]);
    }

    /**
     * B2 (2026-08-21) / DSGVO: Konto-Loeschung anmelden. Ab jetzt
     * laeuft die 30-Tage-Grace-Period, in der der User via Login
     * seine Loeschung wieder ruecknehmen kann.
     */
    public function scheduleDeletion(ScheduleAccountDeletionRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $this->accountDeletion->schedule(
                $user,
                $request->input('reason'),
                $request->handovers(),
            );
        } catch (HandoverMissingException $e) {
            // Der FormRequest deckt die uebliche „Handover fehlt"-Regel
            // (HasHandoverForEveryOwnedProject) ab. Der Service wirft
            // die Exception als letzte Verteidigung — etwa wenn zwischen
            // Validation und Transaktion ein neues Owner-Projekt kommt.
            return redirect()->route('profile')->withErrors([
                'handovers' => $e->getMessage(),
            ]);
        }

        return redirect()->route('profile')->with(
            'success',
            __('profile_deletion_scheduled', ['days' => User::DELETION_GRACE_DAYS])
        );
    }

    /**
     * B2 (2026-08-21) / DSGVO: geplante Konto-Loeschung wieder
     * aufheben. Wird typischerweise aus dem Login-Reaktivierungs-
     * Dialog gerufen, funktioniert aber auch aus der Profil-Sicht.
     */
    public function cancelScheduledDeletion(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->accountDeletion->cancel($user);

        return redirect()->route('profile')->with(
            'success',
            __('profile_deletion_cancelled')
        );
    }
}
