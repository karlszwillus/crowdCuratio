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
use App\Http\Requests\UpdateUserAsAdminRequest;
use App\Models\MailSetting;
use App\Models\User;
use App\Services\ProjectInvitationService;
use App\Services\UserOnboardingService;
use App\Services\UserReactivationService;
use App\Support\RoleName;
use App\Support\RoleResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
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
    ) {
        $this->middleware('auth');
        // Q4-Etappe 1 / I8 (2026-08-27): Der `UserController` ist ab
        // jetzt rein Admin-User-Management. Self-Service-Endpunkte
        // (`profile`, `updateProfile`, `updatePassword`, `updateLocale`,
        // `updateTheme`, `checkInitials`, `scheduleDeletion`,
        // `cancelScheduledDeletion`) leben in `ProfileController` unter
        // reiner `auth`-Middleware. `resendInvitation` bleibt hier —
        // Admin-only via `abort_unless`-Guard in der Methode.
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

    // Q4-Etappe 1 / I8 (2026-08-27): Self-Service-Endpunkte
    // (`profile`, `updateProfile`, `updatePassword`, `updateLocale`,
    // `updateTheme`, `checkInitials`, `scheduleDeletion`,
    // `cancelScheduledDeletion`) leben ab jetzt in
    // `App\Http\Controllers\ProfileController`. Der `UserController`
    // ist rein Admin-User-Management.

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
