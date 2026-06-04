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

        if (filled($validated['new_password'] ?? null)) {
            $user->password = Hash::make($validated['new_password']);
        }

        $user->save();

        return redirect()->back()->with('success', __('message_edit_profile_success'));
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

        return view('users.profile', compact('roles'));
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
