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

declare(strict_types=1);

use App\Models\ProjectUserPermission;
use App\Models\User;
use App\Support\PermissionName;
use App\Support\RoleName;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| <livewire:project-permissions> — Screen 3B (Phase 5d.4)
|--------------------------------------------------------------------------
|
| Pinnt Verhalten der Volt-Komponente: User-Auswahl, Preset-Belegung
| aus RoleTableSeeder, Save-Persistenz, Owner-Sonderbehandlung, Invite-
| Modal, isDirty-Computed. Ergaenzt tests/Feature/Components/
| UiComponentsTest fuer Save-Bar + Locked-Pattern.
*/

beforeEach(function () {
    foreach (PermissionName::all() as $permissionName) {
        Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
    }
    Role::firstOrCreate(['name' => RoleName::ADMIN->value, 'guard_name' => 'web'])
        ->syncPermissions(Permission::all());
    Role::firstOrCreate(['name' => RoleName::EDITOR->value, 'guard_name' => 'web'])
        ->syncPermissions(Permission::whereIn('name', ['view', 'add', 'edit', 'delete', 'publish', 'comment'])->get());
    Role::firstOrCreate(['name' => RoleName::REVIEWER->value, 'guard_name' => 'web'])
        ->syncPermissions(Permission::whereIn('name', ['view', 'comment'])->get());
    Role::firstOrCreate(['name' => RoleName::READER->value, 'guard_name' => 'web'])
        ->syncPermissions(Permission::whereIn('name', ['view'])->get());
});

it('rendert die Sicht fuer den Project-Owner und liefert isDirty=false initial', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::ADMIN->value);
    $project = makeProject($owner);

    // Chain-Bruch: assertOk() liefert bei Livewire 3 zwar wieder ein
    // Testable, aber PHPStan bindet die Signatur an den `TestResponse`-
    // Alias und findet dort assertSet() nicht. Zwischenvariable haelt
    // den Testable-Typ.
    $component = Livewire::actingAs($owner)
        ->test('project-permissions', ['projectId' => $project->id]);

    $component->assertOk();
    $component
        ->assertSet('projectId', $project->id)
        ->assertSet('selectedUserId', $owner->id)
        ->assertSet('isDirty', false);
});

it('setzt Editor-Preset auf alle Editorial-Rechte (edit/add/delete/publish/comment)', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::ADMIN->value);
    $project = makeProject($owner);
    /** @var User $editor */
    $editor = User::factory()->create();
    $editor->assignRole(RoleName::EDITOR->value);

    // Editor ins Projekt einladen — mit view-only-Ist als Startpunkt.
    $viewId = Permission::where('name', 'view')->value('id');
    ProjectUserPermission::create([
        'project_id' => $project->id,
        'user_id' => $editor->id,
        'permission_id' => $viewId,
    ]);

    Livewire::actingAs($owner)
        ->test('project-permissions', ['projectId' => $project->id])
        ->call('selectUser', $editor->id)
        ->call('applyPreset', RoleName::EDITOR->value)
        ->assertSet('permissions.edit', true)
        ->assertSet('permissions.add', true)
        ->assertSet('permissions.delete', true)
        ->assertSet('permissions.publish', true)
        ->assertSet('permissions.comment', true)
        // invite ist Owner-Delegation und im Editor-Preset bewusst aus.
        ->assertSet('permissions.invite', false)
        ->assertSet('isDirty', true);
});

it('setzt Reviewer-Preset nur auf comment', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::ADMIN->value);
    $project = makeProject($owner);
    /** @var User $reviewer */
    $reviewer = User::factory()->create();
    $reviewer->assignRole(RoleName::REVIEWER->value);

    $viewId = Permission::where('name', 'view')->value('id');
    ProjectUserPermission::create([
        'project_id' => $project->id,
        'user_id' => $reviewer->id,
        'permission_id' => $viewId,
    ]);

    Livewire::actingAs($owner)
        ->test('project-permissions', ['projectId' => $project->id])
        ->call('selectUser', $reviewer->id)
        ->call('applyPreset', RoleName::REVIEWER->value)
        ->assertSet('permissions.edit', false)
        ->assertSet('permissions.add', false)
        ->assertSet('permissions.delete', false)
        ->assertSet('permissions.publish', false)
        ->assertSet('permissions.comment', true)
        ->assertSet('permissions.invite', false);
});

it('save() persistiert die Toggles und behaelt view implicit', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::ADMIN->value);
    $project = makeProject($owner);
    /** @var User $target */
    $target = User::factory()->create();
    $target->assignRole(RoleName::READER->value);

    $viewId = Permission::where('name', 'view')->value('id');
    ProjectUserPermission::create([
        'project_id' => $project->id,
        'user_id' => $target->id,
        'permission_id' => $viewId,
    ]);

    Livewire::actingAs($owner)
        ->test('project-permissions', ['projectId' => $project->id])
        ->call('selectUser', $target->id)
        ->call('applyPreset', RoleName::EDITOR->value)
        ->call('save')
        ->assertSet('isDirty', false);

    // view muss weiterhin auf dem Pivot stehen — sonst verliert der
    // Eingeladene seine Sichtbarkeit auf das Projekt.
    $pivotNames = ProjectUserPermission::query()
        ->where('project_id', $project->id)
        ->where('user_id', $target->id)
        ->join('permissions', 'permissions.id', '=', 'project_user_permissions.permission_id')
        ->pluck('permissions.name')
        ->all();

    expect($pivotNames)->toContain('view');
    expect($pivotNames)->toContain('edit');
    expect($pivotNames)->toContain('add');
    expect($pivotNames)->toContain('delete');
    expect($pivotNames)->toContain('publish');
    expect($pivotNames)->toContain('comment');
});

it('discard() setzt die Toggles auf den Initialzustand zurueck', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::ADMIN->value);
    $project = makeProject($owner);
    /** @var User $target */
    $target = User::factory()->create();
    $target->assignRole(RoleName::EDITOR->value);

    $viewId = Permission::where('name', 'view')->value('id');
    ProjectUserPermission::create([
        'project_id' => $project->id,
        'user_id' => $target->id,
        'permission_id' => $viewId,
    ]);

    Livewire::actingAs($owner)
        ->test('project-permissions', ['projectId' => $project->id])
        ->call('selectUser', $target->id)
        ->call('applyPreset', RoleName::EDITOR->value)
        ->assertSet('isDirty', true)
        ->call('discard')
        ->assertSet('isDirty', false)
        ->assertSet('permissions.edit', false);
});

it('Invite-Modal schaltet zwischen offen/zu und leert Fehler', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::ADMIN->value);
    $project = makeProject($owner);

    Livewire::actingAs($owner)
        ->test('project-permissions', ['projectId' => $project->id])
        ->call('invite')
        ->assertSet('showInviteModal', true)
        ->set('inviteEmail', 'unbekannt@example.org')
        ->call('submitInvite')
        ->assertSet('inviteError', __('invite_user_not_found'))
        ->call('closeInvite')
        ->assertSet('showInviteModal', false)
        ->assertSet('inviteEmail', '')
        ->assertSet('inviteError', '');
});

it('submitInvite legt bestehenden User als Reader-Default an und waehlt ihn aus', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::ADMIN->value);
    $project = makeProject($owner);
    /** @var User $invitee */
    $invitee = User::factory()->create(['email' => 'lea@example.org']);
    $invitee->assignRole(RoleName::READER->value);

    Livewire::actingAs($owner)
        ->test('project-permissions', ['projectId' => $project->id])
        ->call('invite')
        ->set('inviteEmail', 'lea@example.org')
        ->call('submitInvite')
        ->assertSet('showInviteModal', false)
        ->assertSet('selectedUserId', $invitee->id);

    $pivotNames = ProjectUserPermission::query()
        ->where('project_id', $project->id)
        ->where('user_id', $invitee->id)
        ->join('permissions', 'permissions.id', '=', 'project_user_permissions.permission_id')
        ->pluck('permissions.name')
        ->all();

    // Reader-Default: view + comment. Keine der vier Editorial-Rechte.
    expect($pivotNames)->toContain('view');
    expect($pivotNames)->toContain('comment');
    expect($pivotNames)->not->toContain('edit');
    expect($pivotNames)->not->toContain('publish');
});
