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

namespace App\Http\Controllers;

use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\ScheduleAccountDeletionRequest;
use App\Http\Requests\UpdateOwnPasswordRequest;
use App\Http\Requests\UpdateOwnProfileRequest;
use App\Http\Requests\UpdateUserAsAdminRequest;
use App\Models\MailSetting;
use App\Models\NotificationPreference;
use App\Models\Project;
use App\Models\ProjectUserPermission;
use App\Models\User;
use App\Services\AccountDeletionService;
use App\Services\AvatarService;
use App\Services\ProjectInvitationService;
use App\Services\ProjectPermissionService;
use App\Services\UserOnboardingService;
use App\Services\UserReactivationService;
use App\Support\InitialsBlocklist;
use App\Support\ProfilePalette;
use App\Support\RoleName;
use App\Support\RoleResolver;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(
        // B12 (2026-08-20): Services fuer den User-Anlage-Flow, aus
        // dem alten RegisteredUserController hierher konsolidiert.
        // Non-nullable, damit Laravel die Services aus dem Container
        // aufloest — mit `?Type = null` waeren die Parameter optional
        // und der Container liefert `null`, was den Store-Flow bricht.
        private readonly RoleResolver $roleResolver,
        private readonly UserReactivationService $userReactivation,
        private readonly UserOnboardingService $userOnboarding,
        private readonly ProjectInvitationService $projectInvitation,
        // B2 (2026-08-21) / DSGVO: Konto-Loeschung mit 30-Tage-Frist.
        private readonly AccountDeletionService $accountDeletion,
    ) {
        $this->middleware('auth');
        // Block E / Welle E.3: `update` jetzt auch role:Admin-gated.
        // B12 (2026-08-20): `create` und `store` neu hinzugekommen —
        // ehemals /register in RegisteredUserController, jetzt
        // /users/create resp. POST /users.
        // Self-Edit lebt auf einer eigenen Route (`PATCH /profile`)
        // mit eigenem FormRequest — siehe `updateProfile` unten.
        $this->middleware('role:Admin')->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        // F-DB-014: hier ist whereNull('deleted_at') bewusst stehen
        // geblieben — DB::table() umgeht den SoftDeletes-Scope, anders
        // als die Eloquent-Queries an den anderen Stellen.
        // Phase-4-TODO (F-LAR-007): Query auf Eloquent umstellen, dann
        // fällt der explizite Filter weg.
        $data = DB::table('users')
            ->join('model_has_roles', 'model_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->select('users.*', 'roles.name as role')
            ->whereNull('deleted_at')
            ->get();

        return view('users.index', compact('data'));
    }

    /**
     * B12 (2026-08-20): Anlage-Formular fuer einen neuen User.
     * Aus dem alten RegisteredUserController hierher konsolidiert.
     */
    public function create(): View
    {
        // F-DB-013: vorher Role::where('id', 'not like', '1') —
        // LIKE auf INT-Spalte mit hartkodierter Admin-ID.
        $roles = Role::where('name', '!=', RoleName::ADMIN->value)->pluck('name', 'name')->all();

        return view('users.create', compact('roles'));
    }

    /**
     * B12 (2026-08-20): Neuen User anlegen. Orchestriert vier Services:
     * Reaktivierung, Admin-Bypass, Standard-Onboarding und optionale
     * Projekt-Zuordnung. Aus dem alten RegisteredUserController::store
     * unveraendert uebernommen.
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        if ($this->userReactivation->existsByEmail($request->email)) {
            $this->userReactivation->reactivateByEmail($request->email);

            return redirect()->route('users.index')->with(
                'success',
                __('users_create_reactivated')
            );
        }

        $caller = $request->user();
        $callerIsAdmin = $caller?->hasRole(RoleName::ADMIN->value) === true;

        // Wenn der Caller Admin ist und `adminUser=true` schickt,
        // landet der Eingeladene als Admin — alle anderen Wege gehen
        // durch den RoleResolver.
        $resolvedRoles = ($callerIsAdmin && $request->boolean('adminUser'))
            ? [Role::findByName(RoleName::ADMIN->value, 'web')]
            : $this->roleResolver->resolve($request->input('roles'));

        // Phase 5d.7: least-privilege-Default. Rollenloser User wuerde
        // im Frontend still stehen (@can-Gates greifen schlicht nicht).
        if ($resolvedRoles === []) {
            $resolvedRoles = [Role::findByName(RoleName::READER->value, 'web')];
        }

        $user = $this->userOnboarding->createInvitedUser($caller, $request, $resolvedRoles);

        if (isset($request->projectId)) {
            // Die Route ist per `role:Admin`-Middleware geschuetzt —
            // `$caller` ist hier nie null. Larastan braucht den
            // expliziten Narrowing-Guard.
            abort_if($caller === null, 403);

            $this->projectInvitation->attachInviteeToProject(
                $user,
                $caller,
                (int) $request->projectId,
                $resolvedRoles,
            );

            return redirect()->back()->with('success', __('users_create_success'));
        }

        return redirect()->route('users.index')->with('success', __('users_create_success'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        $user = User::findOrFail($id);
        $roles = Role::pluck('name', 'name')->all();

        return view('users.edit', compact('user', 'roles'));
    }

    /**
     * Admin-Edit eines beliebigen Users.
     *
     * Block E / Welle E.3: `update` ist jetzt der reine Admin-Pfad
     * — Validation via `UpdateUserAsAdminRequest`, Authorization
     * durch `role:Admin`-Middleware im Constructor. Der frühere
     * Password-Change-Pfad lebt auf `PATCH /profile` mit eigenem
     * FormRequest (siehe `updateProfile`).
     */
    public function update(UpdateUserAsAdminRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        $user->name = $validated['firstName'];
        $user->last_name = $validated['lastName'];
        $user->is_admin = $request->boolean('adminUser');
        $user->create_project = $request->boolean('createProject');
        $user->save();

        if (isset($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        return redirect()->back()->with('success', __('message_edit_user_success'));
    }

    /**
     * Self-Edit des eigenen Profils inkl. optionalem Passwort-Wechsel.
     *
     * Block E / Welle E.3 (neu). Target ist immer `auth()->user()`,
     * daher kein `{user}`-Route-Param. Validation via
     * `UpdateOwnProfileRequest` — der `old_password`-Check lebt
     * dort als Closure-Rule, sodass falsche alte Passwörter über
     * Validation-Fehler zurückkommen.
     */
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
     * B2 (2026-08-21) / DSGVO: Konto-Loeschung anmelden. Ab jetzt
     * laeuft die 30-Tage-Grace-Period, in der der User via Login
     * seine Loeschung wieder ruecknehmen kann.
     */
    public function scheduleDeletion(ScheduleAccountDeletionRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Cross-Check: alle vom User besessenen Projekte muessen einen
        // Nachfolger haben. Ohne Uebergabe wuerden die Projekte
        // Owner-los in die Grace-Period rutschen.
        $ownedIds = $request->ownedProjectIds();
        $handovers = $request->handovers();
        $missing = array_diff($ownedIds, array_keys($handovers));
        if ($missing !== []) {
            return redirect()->route('profile')->withErrors([
                'handovers' => __('profile_deletion_handover_missing'),
            ]);
        }

        try {
            $this->accountDeletion->schedule($user, $request->input('reason'), $handovers);
        } catch (\RuntimeException $e) {
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
     * Remove the specified resource from storage.
     *
     * @return RedirectResponse
     */
    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', __('message_delete_user_success'));
    }

    /**
     * Get own profile
     *
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

    /**
     * Resend invitation
     *
     * Q3-Haertung F2 (2026-08-19) / SEC-02:
     * - Vorher GET ohne Auth-Check — jeder eingeloggte User konnte fuer
     *   beliebige User-IDs Mails ausloesen und welcome_valid_until
     *   verlaengern (RFC 9110 § 9.2.1: GET muss idempotent und ohne
     *   Nebenwirkung sein).
     * - Jetzt POST + throttle:6,1 in der Route + Admin-Guard hier.
     *
     * @return $this
     */
    public function resendInvitation($id)
    {
        // Nur Administrator:innen duerfen fremde Einladungen erneut
        // verschicken. Selbst-Einladung ist konzeptuell kein Feature.
        abort_unless(auth()->user()?->hasRole(RoleName::ADMIN->value), 403);

        $mail = ! empty(MailSetting::first()) ? MailSetting::first() : null;

        $expiresAt = now()->addDay(3);
        $invitation = (isset($mail['invitation']) && ! empty(strip_tags($mail['invitation']))) ? strip_tags(
            $mail['invitation']
        ) : config('project.mail.default');

        User::where('id', $id)
            ->update(['welcome_valid_until' => $expiresAt,
                'updated_at' => now()]);

        $user = User::findOrFail($id);
        $user->sendWelcomeNotification($expiresAt, $user->last_name, $invitation);

        return redirect()->back()->with('success', __('invitation_resent'));
    }
}
