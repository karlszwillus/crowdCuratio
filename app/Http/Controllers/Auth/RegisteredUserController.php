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
use App\Models\Invitation;
use App\Models\MailSetting;
use App\Models\RoleHasPermission;
use App\Models\User;
use App\Models\UserHasPermission;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    /**
     * Instantiate a new ProjectController instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the registration view.
     *
     * @return View
     */
    public function create()
    {
        // F-DB-013: vorher Role::where('id', 'not like', '1') —
        // LIKE auf INT-Spalte mit hartkodierter Admin-ID.
        $roles = Role::where('name', '!=', 'Admin')->pluck('name', 'name')->all();

        return view('auth.register', compact('roles'));
    }

    /**
     * Handle an incoming registration request.
     *
     * @return RedirectResponse
     */
    public function store(RegisterRequest $request)
    {
        $mail = ! empty(MailSetting::first()) ? MailSetting::first() : null;

        $ifUserExists = DB::table('users')->where('email', $request->email)->first();

        if ($ifUserExists != '') {
            $affected = DB::table('users')
                ->where('email', $request->email)
                ->update(['deleted_at' => null]);

            return redirect()->route('users.index')->with(
                'success',
                'Dieser Nutzer war inaktivert und wurde soeben wieder reaktiviert. Über die Login Seite kann mit den bestehenden Zugangsdaten wieder auf das CMS zugegriffen werden.'
            );
        } else {

            // NF-SEC-202: `is_admin` und `create_project` (plus die
            // Admin-Rolle) sind privilegiert. Sie werden ausschließlich
            // gesetzt, wenn der aktuell eingeloggte User selbst Admin
            // ist. Erste Schicht ist die Route-Middleware `role:Admin`
            // (web.php), dieser Check ist die zweite Schicht — falls
            // die Route-Konfiguration jemals fällt oder ein neuer
            // Einstiegspunkt entsteht, bleibt der Pfad zu.
            //
            // Quelle der Wahrheit ist die Spatie-Rolle, nicht das
            // `is_admin`-Feld auf users. Der CreateAdminUserSeeder
            // setzt nur die Rolle. Das `is_admin`-Feld ist ein
            // bestehender Daten-Attribut-Doppel-Begriff, der in
            // Phase 4 mit ADR-0005 (Permission-Mehrgleisigkeit)
            // aufgelöst wird.
            $caller = Auth::user();
            $callerIsAdmin = $caller?->hasRole('Admin') === true;
            $isAdminInvite = $callerIsAdmin && $request->boolean('adminUser');
            $grantCreateProject = $callerIsAdmin && $request->boolean('createProject');

            // Felder einzeln per Property-Setter, nicht via
            // `User::create($array)`: `is_admin` und `create_project`
            // sind nicht mehr in `$fillable`, würden bei Mass-Assignment
            // verworfen und der Insert liefe ohne sie. Auf SQLite
            // (Pest-Pfad) gibt es keinen Spalten-Default (die
            // default_for_user_admin_flags-Migration ist dort No-Op,
            // siehe NF-DB-103) — Insert würde mit NOT-NULL-Verstoß
            // brechen. Direkt-Assignment umgeht `$fillable` und setzt
            // die Werte explizit, ohne den Mass-Assignment-Schutz
            // aufzuweichen (keine Request-Daten landen ungefiltert
            // im Modell).
            $user = new User;
            $user->name = $request->firstName;
            $user->last_name = $request->lastName;
            $user->email = $request->email;
            $user->password = Hash::make(Str::random(8));
            $user->is_admin = $isAdminInvite;
            $user->create_project = $grantCreateProject;
            $user->save();

            event(new Registered($user));

            // Block D PR 2 / Smoke-Fix: das `roles`-Feld kann
            // verschieden ankommen — als Array (Multi-Select-Form),
            // als Single-String, als Role-Name oder als Role-ID
            // (numerisch). Spatie v6 interpretiert Strings strikt
            // als Namen, was bei einem ID-Submit zu
            // `RoleDoesNotExist: no role named '20'` führt. Der
            // Resolver löst beide Pfade in echte `Role`-Instanzen
            // auf, bevor sie an `assignRole` und an die nachgelagerte
            // `RoleHasPermission`-Query gehen.
            $resolvedRoles = $isAdminInvite
                ? [Role::findByName('Admin', 'web')]
                : $this->resolveRoles($request->input('roles'));

            $user->assignRole($resolvedRoles);

            $expiresAt = now()->addDay(3);
            $invitation = (isset($mail['invitation']) && ! empty(strip_tags($mail['invitation']))) ? strip_tags(
                $mail['invitation']
            ) : config('project.mail.default');

            $user->sendWelcomeNotification($expiresAt, $request->firstName, $invitation);

            if (isset($request->projectId)) {
                $resolvedRoleIds = collect($resolvedRoles)->pluck('id')->all();
                $permissions = RoleHasPermission::whereIn('role_id', $resolvedRoleIds)->pluck('permission_id');
                foreach ($permissions as $permission) {
                    UserHasPermission::create([
                        'project_id' => $request->projectId,
                        'permission_id' => $permission,
                        'user_id' => $user->id,
                    ]);
                }

                Invitation::create([
                    'user_id' => Auth::user()->id,
                    'guest_id' => $user->id,
                    'project_id' => $request->projectId,
                ]);

                return Redirect()->back()->with('success', 'User added successful');
            } else {
                return redirect()->route('users.index')->with('success', 'User added successful');
            }
        }
    }

    /**
     * Block D PR 2 / Smoke-Fix: Eingabe für `roles` zu konkreten
     * `Role`-Instanzen auflösen. Akzeptiert Single-String, Array
     * und mischt numerische Strings (ID-Lookup) mit Text-Strings
     * (Name-Lookup). Wirft die Spatie-üblichen
     * `RoleDoesNotExist`-Exceptions, wenn der referenzierte Wert
     * nicht auflösbar ist — das bleibt bewusst hart, weil ein
     * ungültiger Role-Submit ein Form-Bug ist, kein Use-Case.
     *
     * @param  array<int|string, mixed>|string|int|null  $input
     * @return array<int, Role>
     */
    private function resolveRoles(array|string|int|null $input): array
    {
        if ($input === null || $input === '') {
            return [];
        }

        $values = is_array($input) ? $input : [$input];

        return collect($values)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(function ($value): Role {
                if (is_int($value) || (is_string($value) && ctype_digit($value))) {
                    return Role::findById((int) $value, 'web');
                }

                return Role::findByName((string) $value, 'web');
            })
            ->values()
            ->all();
    }
}
