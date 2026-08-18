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

use App\Http\Requests\UpdateOwnProfileRequest;
use App\Http\Requests\UpdateUserAsAdminRequest;
use App\Models\MailSetting;
use App\Models\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // Block E / Welle E.3: `update` jetzt auch role:Admin-gated.
        // Self-Edit lebt auf einer eigenen Route (`PATCH /profile`)
        // mit eigenem FormRequest — siehe `updateProfile` unten.
        $this->middleware('role:Admin')->only(['index', 'edit', 'update', 'destroy']);
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
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        //
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
            $user->initials_color = in_array($color, \App\Support\ProfilePalette::TOKENS, true) ? $color : null;
        }

        // Phase 5ac.2: Avatar-Upload. remove_avatar=1 wins gegen ein
        // hochgeladenes File — der Nutzer will dann sein Bild los.
        if (! empty($validated['remove_avatar'])) {
            app(\App\Services\AvatarService::class)->remove($user->avatar_path);
            $user->avatar_path = null;
        } elseif ($request->hasFile('avatar')) {
            $newFile = app(\App\Services\AvatarService::class)->store($request->file('avatar'));
            if ($newFile !== null) {
                app(\App\Services\AvatarService::class)->remove($user->avatar_path);
                $user->avatar_path = $newFile;
            }
        }

        if (filled($validated['new_password'] ?? null)) {
            $user->password = Hash::make($validated['new_password']);
        }

        $user->save();

        return redirect()->back()->with('success', __('message_edit_profile_success'));
    }

    /**
     * Phase 5ac.1: Sofort-Wirkung fuer Sprache und Theme — kein
     * Save-Kandidat, aendert sich mit dem Klick. Beide Endpoints
     * nehmen einen JSON-Payload und antworten mit 204, damit der
     * Client nur reloaden muss.
     */
    public function updateLocale(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(401);
        }
        $locale = (string) $request->input('locale');
        $allowed = array_keys((array) \Illuminate\Support\Facades\Config::get('languages'));
        if (! in_array($locale, $allowed, true)) {
            abort(422, 'Unknown locale.');
        }
        $user->locale = $locale;
        $user->save();
        \Illuminate\Support\Facades\Session::put('applocale', $locale);

        return response()->json(['ok' => true]);
    }

    /**
     * Phase 5ac.4: Eigener Endpoint fuer den Passwort-Wechsel — die
     * Karte hat ihren eigenen Save-Button, damit ein Passwort-Wechsel
     * nicht am Vornamen haengt. Bestehende UpdateOwnProfileRequest-
     * Logik bleibt fuer Rueckwaertskompat.
     */
    public function updatePassword(\App\Http\Requests\UpdateOwnPasswordRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->password = Hash::make($request->validated()['new_password']);
        $user->save();

        return redirect()->route('profile')->with('success', __('profile_password_updated'));
    }

    public function updateTheme(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(401);
        }
        $theme = (string) $request->input('theme');
        if (! in_array($theme, ['default', 'aktives-museum'], true)) {
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
        /** @var \App\Models\User $me */
        $me = auth()->user();
        $service = app(\App\Services\ProjectPermissionService::class);
        $projectsRaw = $service->listProjectsForUser($me);
        $ownRoleNames = $me->roles->pluck('name')->all();

        $profileProjects = $projectsRaw->map(function ($project) use ($me, $ownRoleNames) {
            $isOwner = (int) $project->user_id === (int) $me->id;
            $roleLabel = $isOwner
                ? __('profile_project_role_owner')
                : (\App\Models\ProjectUserPermission::query()
                    ->where('project_id', $project->id)
                    ->where('user_id', $me->id)
                    ->exists()
                    ? __('profile_project_role_member')
                    : (in_array(\App\Support\RoleName::ADMIN->value, $ownRoleNames, true)
                        ? __('profile_project_role_admin')
                        : __('profile_project_role_reader')));

            // Kontext-Zahl: fuer Runde 1 einheitlich „N Kapitel". Die
            // rollenabhaengige Formulierung (Eintraege / offene
            // Kommentare / eigene Beitraege) folgt in 5ac.3-Followup.
            $chapterCount = (int) ($project->chapters_count ?? 0);
            $contextText = trans_choice('profile_project_context_chapters', $chapterCount, ['count' => $chapterCount]);

            return [
                'id' => $project->id,
                'name' => (string) $project->name,
                'role' => $roleLabel,
                'context' => $contextText,
                'is_owner' => $isOwner,
            ];
        })->values();

        return view('users.profile', compact('roles', 'profileProjects'));
    }

    /**
     * Resend invitation
     *
     * @return $this
     */
    public function resendInvitation($id)
    {

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
