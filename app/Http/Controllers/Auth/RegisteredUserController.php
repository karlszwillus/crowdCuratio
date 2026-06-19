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

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\ProjectInvitationService;
use App\Services\UserOnboardingService;
use App\Services\UserReactivationService;
use App\Support\RoleName;
use App\Support\RoleResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    public function __construct(
        private readonly RoleResolver $roleResolver,
        private readonly UserReactivationService $userReactivation,
        private readonly UserOnboardingService $userOnboarding,
        private readonly ProjectInvitationService $projectInvitation,
    ) {
        $this->middleware('auth');
    }

    /**
     * Display the registration view.
     */
    public function create(): View
    {
        // F-DB-013: vorher Role::where('id', 'not like', '1') —
        // LIKE auf INT-Spalte mit hartkodierter Admin-ID.
        $roles = Role::where('name', '!=', RoleName::ADMIN->value)->pluck('name', 'name')->all();

        return view('auth.register', compact('roles'));
    }

    /**
     * Handle an incoming registration request.
     *
     * Block E / Welle E.5: orchestriert vier Services. Drei
     * Verzweigungen — Reaktivierung, Admin-Invite, Project-Invite —
     * leben jeweils in einem eigenen Service. Privilege-Check für
     * `adminUser` / `createProject` liegt im `UserOnboardingService`,
     * Role-Auflösung im `RoleResolver`.
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        if ($this->userReactivation->existsByEmail($request->email)) {
            $this->userReactivation->reactivateByEmail($request->email);

            return redirect()->route('users.index')->with(
                'success',
                'Dieser Nutzer war inaktivert und wurde soeben wieder reaktiviert. Über die Login Seite kann mit den bestehenden Zugangsdaten wieder auf das CMS zugegriffen werden.'
            );
        }

        $caller = $request->user();
        $callerIsAdmin = $caller?->hasRole(RoleName::ADMIN->value) === true;

        // Wenn der Caller Admin ist und `adminUser=true` schickt,
        // landet der Eingeladene als Admin — alle anderen Wege gehen
        // durch den RoleResolver. Privilege-Check ist Defense-in-Depth;
        // der UserOnboardingService prüft die Flags nochmal.
        $resolvedRoles = ($callerIsAdmin && $request->boolean('adminUser'))
            ? [Role::findByName(RoleName::ADMIN->value, 'web')]
            : $this->roleResolver->resolve($request->input('roles'));

        $user = $this->userOnboarding->createInvitedUser($caller, $request, $resolvedRoles);

        if (isset($request->projectId)) {
            // Die `register`-Route ist per `role:Admin`-Middleware
            // geschützt — `$caller` ist hier nie null. Larastan
            // braucht den expliziten Narrowing-Guard.
            abort_if($caller === null, 403);

            $this->projectInvitation->attachInviteeToProject(
                $user,
                $caller,
                (int) $request->projectId,
                $resolvedRoles,
            );

            return redirect()->back()->with('success', 'User added successful');
        }

        return redirect()->route('users.index')->with('success', 'User added successful');
    }
}
