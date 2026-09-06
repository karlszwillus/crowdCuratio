<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2022, 2026 - berlinHistory e.V.

See LICENSE.
 */

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use App\Services\ProjectPermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Q4-Etappe 2 / I6 (2026-08-27): Rechte-Endpunkte fuer Projekte,
 * extrahiert aus dem `ProjectController`. Enthaelt die vier Endpunkte,
 * die die Livewire-`project-permissions`-Volt-Komponente nicht deckt:
 * die alten AJAX/Form-Routen fuer setPermission, givePermissionToUser,
 * checkEmail und deleteUserFromProject.
 *
 * Alle vier gaten gegen `update` auf dem Projekt (Owner / Admin /
 * Eingeladener-mit-edit) — Block E.7b Sub-Welle 3 hat die frueheren
 * Privilege-Escalation-Vektoren hier geschlossen (analog NF-SEC-202).
 */
class ProjectPermissionController extends Controller
{
    public function __construct(
        private readonly ProjectPermissionService $permissions,
    ) {
        $this->middleware('auth');
    }

    /**
     * Set permission for user on project.
     *
     * Block E.7b Sub-Welle 3-Hotfix (ADR-0022, ADR-0013):
     * KRITISCH — vorher konnte JEDER eingeloggte User via direktem
     * POST `/project/permission` einem beliebigen User volle Rechte
     * auf jedes Projekt vergeben. Privilege Escalation, vergleichbar
     * mit NF-SEC-202. update-Gate: nur Owner/Admin/Eingeladener-mit-
     * edit darf Permissions verteilen.
     */
    public function setPermissionForUserOnProject(Request $request): RedirectResponse
    {
        $userId = (int) $request['user'];
        $projectId = (int) $request['project'];
        $permissionIds = (array) ($request['permissions'] ?? []);

        $project = Project::findOrFail($projectId);
        $this->authorize('update', $project);

        $this->permissions->setForUserOnProject(
            $userId,
            $projectId,
            $permissionIds,
            (int) Auth::user()->id,
        );

        $user = User::findOrFail($userId);
        $permissions = $this->permissions->getCurrentUsersPermissions($userId);

        return redirect()->back()->with([
            'error_code' => 5,
            'user' => $user,
            'permissions' => $permissions,
        ]);
    }

    /**
     * AJAX: die Permission-IDs eines Users auf einem Projekt.
     *
     * Block E.7b Sub-Welle 3-Hotfix (ADR-0022, ADR-0013): Info-Leak
     * — gab Permission-IDs eines beliebigen Users auf ein beliebiges
     * Projekt heraus. Gate analog setPermissionForUserOnProject.
     */
    public function givePermissionToUser(string $id): JsonResponse
    {
        [$userId, $projectId] = array_map('intval', explode('_', $id));

        $project = Project::findOrFail($projectId);
        $this->authorize('update', $project);

        $data = $this->permissions->getPermissionIdsForUserOnProject($userId, $projectId);

        return response()->json($data);
    }

    /**
     * Prueft, ob ein E-Mail-Empfaenger bereits als User existiert und
     * gibt seine Rolle / seine Permissions als Session-Flash zurueck
     * (Legacy-Flow aus dem alten Modify-Modal, deckt den Bootstrap-3-
     * Fallback ab, bis die Livewire-Volt-Sicht auf allen Screens
     * lebt).
     */
    public function checkEmail(Request $request): RedirectResponse
    {
        $user = User::where('email', $request->userEmail)->first();

        if ($user) {
            $role = isset($user->role->userRole->name) ? $user->role->userRole->name : '';
            $permissionForRole = [];
            if (isset($user->role->userRole->id)) {
                $permissionForRole = Role::query()
                    ->join('role_has_permissions', 'role_has_permissions.role_id', '=', 'roles.id')
                    ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                    ->where('roles.id', $user->role->userRole->id)
                    ->pluck('permissions.name');
            }
            $listAllPermissions = Permission::orderBy('id', 'ASC')->pluck('name', 'id');
            $permissionForProject = $user->getAllPermissions()->pluck('name')->toArray();
            $permissionProject = $user->getAllPermissions()->pluck('name')->toArray();

            return redirect()->back()->with(
                [
                    'error_code' => 6,
                    'user' => $user,
                    'role' => $role,
                    'listAllPermissions' => $listAllPermissions,
                    'permissionForProject' => $permissionForProject,
                    'permissionProject' => $permissionProject,
                    'permissionForRole' => $permissionForRole,
                ]
            );
        }

        return redirect()->back()->with(['error_code' => 7, 'email' => $request->userEmail]);
    }

    /**
     * Nimmt einen User aus einem Projekt raus (Berechtigungs-Zeilen
     * werden entfernt, der User bleibt bestehen).
     */
    public function deleteUserFromProject(int $userId, int $projectId): RedirectResponse
    {
        // Symmetrisch zu setPermissionForUserOnProject: nur Owner /
        // Admin / edit-Berechtigte duerfen Mitarbeitende entfernen.
        $project = Project::findOrFail($projectId);
        $this->authorize('update', $project);

        $this->permissions->removeUserFromProject($userId, $projectId);

        return redirect()->back()->with('success', __('message_edit_project_success'));
    }
}
