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

use App\Models\Project;
use App\Models\User;
use App\Services\ProjectPermissionService;
use App\Support\PermissionName;
use App\Support\RoleName;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;
use Spatie\Permission\Models\Permission;

/**
 * Volt-Component fuer Screen 3B (Handoff v4): Projekt-Berechtigungen
 * (Phase 5d.4). Loest die alte Modal-Kaskade in
 * resources/views/projects/create.blade.php ab.
 *
 * Datenmodell: Owner-Chip aus project.user_id, alle anderen User aus
 * project_user_permissions. Sichtbare Toggles auf die 4 Editorial-
 * Permissions add / edit / delete / publish beschraenkt — view und
 * comment sind implizit fuer jeden Eingeladenen, invite ist an anderer
 * Stelle geregelt.
 *
 * Preset-Buttons setzen die 4 Toggles auf die Rollen-Standardbelegung
 * (siehe RoleTableSeeder). Danach kann individuell abweichen; der
 * abweichende Zustand persistiert in project_user_permissions.
 */
new class extends Component
{
    /**
     * Sechs sichtbare Permissions (Karl-Entscheidung 2026-08-15).
     * Ergaenzung zur Handoff-v4-Vorlage: Screen 3B zeigt nur 4
     * Toggles (edit/add/delete/publish); wir nehmen zusaetzlich
     * comment (Reviewer-Nuance: kann jemand sehen ohne kommentieren?)
     * und invite (Owner-Delegation: Co-Owner mit Einladungsrecht).
     * Design-Abweichung ist im Designer-Briefing
     * .werkbank/BRIEFINGS/redesign/permission-matrix-6-toggles.md
     * dokumentiert und wartet auf die naechste Review-Runde.
     */
    private const VISIBLE_PERMISSIONS = [
        PermissionName::EDIT->value,
        PermissionName::ADD->value,
        PermissionName::DELETE->value,
        PermissionName::PUBLISH->value,
        PermissionName::COMMENT->value,
        PermissionName::INVITE->value,
    ];

    #[Locked]
    public int $projectId;

    /** ID des aktiven Users im Detail-Panel; 0 = kein User ausgewaehlt. */
    public int $selectedUserId = 0;

    /**
     * Sicht-Ist der 4 Toggles fuer den aktiven User.
     *
     * @var array<string, bool>
     */
    public array $permissions = [];

    /**
     * Sicht-Soll der 4 Toggles beim Laden (Dirty-Check-Vergleich).
     *
     * @var array<string, bool>
     */
    public array $initialPermissions = [];

    public string $inviteEmail = '';

    /** Sichtbarkeit des Invite-Modals. */
    public bool $showInviteModal = false;

    /** Fehler-Text unterm E-Mail-Feld, wenn User nicht gefunden. */
    public string $inviteError = '';

    public function mount(int $projectId): void
    {
        $this->projectId = $projectId;

        // Authorization: nur wer im Projekt einladen darf (Policy oder
        // Owner), sieht die Berechtigungssicht ueberhaupt. Reader und
        // Editor ohne invite-Recht landen hier nicht.
        $project = Project::findOrFail($projectId);
        Gate::authorize('invite', $project);

        // Ersten Non-Owner-User vorauswaehlen, sonst den Owner selbst.
        $firstUser = $this->buildUserList()->first();
        if ($firstUser !== null) {
            $this->selectUser($firstUser['id']);
        }
    }

    /**
     * Baut die Sidebar-Liste: Owner zuerst, dann alle User aus
     * project_user_permissions, dedupliziert.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function getUsersProperty(): \Illuminate\Support\Collection
    {
        return $this->buildUserList();
    }

    private function buildUserList(): \Illuminate\Support\Collection
    {
        /** @var Project $project */
        $project = Project::with('user')->findOrFail($this->projectId);

        $service = app(ProjectPermissionService::class);

        $ownerId = (int) $project->user_id;
        $result = collect();

        // Owner zuerst — im Detail wird ihm die Sonder-Behandlung
        // gegeben (alle Toggles implizit an, Preset-Buttons aus).
        if ($project->user !== null) {
            $result->push([
                'id' => $ownerId,
                'name' => trim(($project->user->name ?? '').' '.($project->user->last_name ?? '')),
                'first_name' => (string) ($project->user->name ?? ''),
                'last_name' => (string) ($project->user->last_name ?? ''),
                'avatar_path' => $project->user->avatar_path ?? null,
                'initials' => $project->user->initials ?? null,
                'initials_color' => $project->user->initials_color ?? null,
                'email' => $project->user->email,
                'role' => __('role_owner'),
                'is_owner' => true,
            ]);
        }

        // Alle User mit project-scoped Permissions (ohne den Owner).
        $granted = $service->getUsersForThisProject($this->projectId);
        foreach ($granted as $userId => $userData) {
            if ((int) $userId === $ownerId) {
                continue;
            }

            $user = User::find($userId);
            if ($user === null) {
                continue;
            }

            $result->push([
                'id' => (int) $userId,
                'name' => $userData['name'] ?? trim(($user->name ?? '').' '.($user->last_name ?? '')),
                'first_name' => (string) ($user->name ?? ''),
                'last_name' => (string) ($user->last_name ?? ''),
                'avatar_path' => $user->avatar_path,
                'initials' => $user->initials,
                'initials_color' => $user->initials_color,
                'email' => $user->email,
                'role' => $user->getRoleNames()->first() ?? RoleName::READER->value,
                'is_owner' => false,
            ]);
        }

        return $result;
    }

    /**
     * User im Detail-Panel wechseln. Setzt die Toggles neu auf den
     * project-scoped Ist-Zustand.
     */
    public function selectUser(int $userId): void
    {
        $this->selectedUserId = $userId;

        $service = app(ProjectPermissionService::class);
        $granted = $service->getSelectedPermissionUser($userId, $this->projectId);

        $state = [];
        foreach (self::VISIBLE_PERMISSIONS as $name) {
            $state[$name] = in_array($name, $granted, true);
        }

        $this->permissions = $state;
        $this->initialPermissions = $state;
    }

    /**
     * Rollen-Vorlage-Klick: setzt die vier Toggles auf die Default-
     * Permission-Menge der Rolle (siehe RoleTableSeeder).
     *
     * Owner-Preset ist informativ: alle vier an, aber der Save
     * schreibt keine Pivot-Eintraege — Owner-Rechte kommen ueber
     * project.user_id, nicht ueber die Pivot-Tabelle.
     */
    public function applyPreset(string $role): void
    {
        // Presets spiegeln RoleTableSeeder (siehe database/seeders):
        //   Editor:   view, add, edit, delete, publish, comment
        //   Reviewer: view, comment
        //   Reader:   view
        // view ist implizit — Reader kriegt keine der 6 sichtbaren
        // Toggles, kann aber die Seite trotzdem lesen.
        $presets = [
            RoleName::EDITOR->value => [
                PermissionName::EDIT->value    => true,
                PermissionName::ADD->value     => true,
                PermissionName::DELETE->value  => true,
                PermissionName::PUBLISH->value => true,
                PermissionName::COMMENT->value => true,
                PermissionName::INVITE->value  => false,
            ],
            RoleName::REVIEWER->value => [
                PermissionName::EDIT->value    => false,
                PermissionName::ADD->value     => false,
                PermissionName::DELETE->value  => false,
                PermissionName::PUBLISH->value => false,
                PermissionName::COMMENT->value => true,
                PermissionName::INVITE->value  => false,
            ],
            RoleName::READER->value => [
                PermissionName::EDIT->value    => false,
                PermissionName::ADD->value     => false,
                PermissionName::DELETE->value  => false,
                PermissionName::PUBLISH->value => false,
                PermissionName::COMMENT->value => false,
                PermissionName::INVITE->value  => false,
            ],
        ];

        if (! isset($presets[$role])) {
            return;
        }

        $this->permissions = $presets[$role];
    }

    /**
     * Q3-Politur G9 (2026-08-20) / P-01: welches Preset entspricht der
     * aktuellen Toggle-Kombination? Getter wird von den Preset-Buttons
     * gelesen und leuchtet live mit — der bisherige Vergleich gegen
     * den serverseitig gespeicherten `role`-Wert hinkte hinterher, sobald
     * der Nutzer einzelne Toggles anfasste, ohne zu speichern.
     */
    public function getMatchedPresetProperty(): ?string
    {
        $current = [
            PermissionName::EDIT->value    => (bool) ($this->permissions[PermissionName::EDIT->value] ?? false),
            PermissionName::ADD->value     => (bool) ($this->permissions[PermissionName::ADD->value] ?? false),
            PermissionName::DELETE->value  => (bool) ($this->permissions[PermissionName::DELETE->value] ?? false),
            PermissionName::PUBLISH->value => (bool) ($this->permissions[PermissionName::PUBLISH->value] ?? false),
            PermissionName::COMMENT->value => (bool) ($this->permissions[PermissionName::COMMENT->value] ?? false),
            PermissionName::INVITE->value  => (bool) ($this->permissions[PermissionName::INVITE->value] ?? false),
        ];

        $presets = [
            RoleName::EDITOR->value => [
                PermissionName::EDIT->value    => true,
                PermissionName::ADD->value     => true,
                PermissionName::DELETE->value  => true,
                PermissionName::PUBLISH->value => true,
                PermissionName::COMMENT->value => true,
                PermissionName::INVITE->value  => false,
            ],
            RoleName::REVIEWER->value => [
                PermissionName::EDIT->value    => false,
                PermissionName::ADD->value     => false,
                PermissionName::DELETE->value  => false,
                PermissionName::PUBLISH->value => false,
                PermissionName::COMMENT->value => true,
                PermissionName::INVITE->value  => false,
            ],
            RoleName::READER->value => [
                PermissionName::EDIT->value    => false,
                PermissionName::ADD->value     => false,
                PermissionName::DELETE->value  => false,
                PermissionName::PUBLISH->value => false,
                PermissionName::COMMENT->value => false,
                PermissionName::INVITE->value  => false,
            ],
        ];

        foreach ($presets as $role => $mask) {
            if ($mask === $current) {
                return $role;
            }
        }

        return null;
    }

    /**
     * Persistiert die vier Toggles fuer den aktiven User als
     * project_user_permissions-Zeilen (Set-Semantik). view und comment
     * werden IMMER mitgeschrieben — sonst wuerde der User seine
     * Sicht-/Kommentar-Rechte verlieren.
     */
    public function save(): void
    {
        if ($this->selectedUserId === 0) {
            return;
        }

        $project = Project::findOrFail($this->projectId);
        Gate::authorize('invite', $project);

        // view bleibt implizit fuer jeden Eingeladenen — comment und
        // invite haben mit der 6-Toggle-Erweiterung (Karl-Entscheidung
        // 2026-08-15) eigene Toggles und werden nicht mehr blind
        // dazugeschrieben.
        $activeNames = [PermissionName::VIEW->value];
        foreach ($this->permissions as $name => $active) {
            if ($active) {
                $activeNames[] = $name;
            }
        }

        $permissionIds = Permission::query()
            ->whereIn('name', $activeNames)
            ->pluck('id')
            ->all();

        app(ProjectPermissionService::class)->setForUserOnProject(
            $this->selectedUserId,
            $this->projectId,
            $permissionIds,
            (int) auth()->id(),
        );

        // Snapshot fuer Dirty-Check aktualisieren.
        $this->initialPermissions = $this->permissions;

        $this->dispatch('cc-toast-success', message: __('message_permissions_saved'));
    }

    public function discard(): void
    {
        $this->permissions = $this->initialPermissions;
    }

    /**
     * True, wenn irgendein Toggle vom Initialzustand abweicht.
     * Livewire 3 Computed — server-side, wird in der Blade ueber
     * $this->isDirty ausgewertet und rendert die Save-Bar per @if.
     */
    #[Computed]
    public function isDirty(): bool
    {
        return $this->permissions !== $this->initialPermissions;
    }

    /**
     * Oeffnet das Invite-Modal. Nur Nutzer:innen mit invite-Permission
     * auf dem Projekt sehen den Button (Sichtbarkeit wird server-side
     * aus @can('invite', $project) im Blade geregelt).
     */
    public function invite(): void
    {
        $this->inviteEmail = '';
        $this->inviteError = '';
        $this->showInviteModal = true;
    }

    public function closeInvite(): void
    {
        $this->showInviteModal = false;
        $this->inviteEmail = '';
        $this->inviteError = '';
    }

    /**
     * Persistiert die Einladung. Sucht den User per E-Mail; wenn er
     * existiert, wird er dem Projekt mit den Reader-Default-
     * Permissions (view + comment) hinzugefuegt und im Detail-Panel
     * ausgewaehlt. Neuanlage/Registrierung neuer Nutzer:innen
     * bleibt bis 5d.7 der separate Register-Flow.
     */
    public function submitInvite(): void
    {
        $this->inviteError = '';

        $this->validate([
            'inviteEmail' => 'required|email',
        ]);

        $project = Project::findOrFail($this->projectId);
        Gate::authorize('invite', $project);

        $user = User::where('email', $this->inviteEmail)->first();
        if ($user === null) {
            // Der Fehler-Text verweist auf /users/create (B12) —
            // dort legt ein Admin neue Nutzer:innen an. Ein direkter
            // Redirect aus dem Modal ist bewusst ausgelassen, weil
            // die Projekt-Kontext-Zuordnung ohnehin im Anschluss
            // ueber die Berechtigungs-Sicht laeuft.
            $this->inviteError = __('invite_user_not_found');

            return;
        }

        // Reader-Default: view + comment. Editor/Reviewer/Owner-
        // Zuweisung erfolgt danach ueber die Rollen-Vorlage-Buttons.
        $readerPermissionIds = Permission::query()
            ->whereIn('name', [
                PermissionName::VIEW->value,
                PermissionName::COMMENT->value,
            ])
            ->pluck('id')
            ->all();

        app(ProjectPermissionService::class)->setForUserOnProject(
            $user->id,
            $this->projectId,
            $readerPermissionIds,
            (int) auth()->id(),
        );

        $this->showInviteModal = false;
        $this->inviteEmail = '';
        $this->selectUser($user->id);
        $this->dispatch('cc-toast-success', message: __('message_invited_success'));
    }
};
?>
<div class="grid grid-cols-[280px_1fr] gap-6">

    {{-- LINKS: Mitarbeitende-Sidebar --}}
    <aside class="rounded-lg border border-line-200 bg-paper-0"
           aria-label="{{ __('collaborators') }}">
        <div class="flex items-center justify-between border-b border-line-200 px-4 py-3">
            <h2 class="text-heading font-semibold text-ink-900">
                {{ __('collaborators') }}
            </h2>
            <button
                type="button"
                wire:click="invite"
                class="inline-flex items-center gap-1.5 rounded-md bg-primary px-3 py-1.5
                       text-caption font-medium text-primary-on hover:opacity-90
                       focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
            >
                <x-icon name="plus" size="4"/>
                {{ __('invite') }}
            </button>
        </div>

        @php
            // Q3-Politur G4 (2026-08-20) / UX-06: Rollen-Beschreibungen
            // fuer den Chip in der User-Liste.
            $roleDescriptions = [
                RoleName::READER->value   => __('role_reader_desc'),
                RoleName::REVIEWER->value => __('role_reviewer_desc'),
                RoleName::EDITOR->value   => __('role_editor_desc'),
                RoleName::ADMIN->value    => __('role_admin_desc'),
            ];
        @endphp

        <ul class="p-2" role="list">
            @foreach ($this->users as $user)
                @php
                    $isActive = $user['id'] === $selectedUserId;
                    $userRoleDesc = $user['is_owner'] ?? false
                        ? __('role_owner_desc')
                        : ($roleDescriptions[$user['role']] ?? '');
                @endphp
                <li>
                    <button
                        type="button"
                        wire:click="selectUser({{ $user['id'] }})"
                        aria-current="{{ $isActive ? 'true' : 'false' }}"
                        class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-left transition-colors
                               {{ $isActive
                                   ? 'bg-danger-bg text-ink-900'
                                   : 'text-ink-900 hover:bg-line-100/40' }}
                               focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-bar"
                    >
                        <x-ui.user-avatar :user="$user" size="8" text="text-caption font-semibold"/>
                        <span class="min-w-0 flex-1 truncate">
                            <span class="block text-body font-medium">{{ $user['name'] }}</span>
                            <span class="block text-caption text-ink-500"
                                  @if ($userRoleDesc !== '') title="{{ $userRoleDesc }}" @endif>{{ $user['role'] }}</span>
                        </span>
                    </button>
                </li>
            @endforeach
        </ul>
    </aside>

    {{-- RECHTS: Detail-Sicht --}}
    <section class="rounded-lg border border-line-200 bg-paper-50 p-6"
             aria-label="{{ __('permission_detail') }}">
        @php
            $active = $this->users->firstWhere('id', $selectedUserId);
        @endphp

        @if ($active === null)
            <div class="py-12 text-center text-body text-ink-500">
                {{ __('no_user_selected') }}
            </div>
        @else
            @php
                $isOwner = (bool) ($active['is_owner'] ?? false);
            @endphp

            <div class="flex items-center gap-4">
                <x-ui.user-avatar :user="$active" size="14" text="text-title font-semibold"/>
                <div class="min-w-0">
                    <div class="text-title font-semibold text-ink-900">{{ $active['name'] }}</div>
                    <div class="truncate text-body text-ink-500">{{ $active['email'] }}</div>
                </div>
            </div>

            <div class="mt-8">
                <p class="mb-3 font-mono text-mono-caps uppercase tracking-widest text-ink-500">
                    {{ __('role_as_template') }}
                </p>

                <div class="flex flex-wrap gap-2" role="tablist">
                    @php
                        // Q3-Politur G4 (2026-08-20) / UX-06: Rollen-Preset-
                        // Buttons bekommen eine Erklaerung als Tooltip.
                        $rolePresets = [
                            RoleName::READER->value   => [__('role_reader'),   __('role_reader_desc')],
                            RoleName::REVIEWER->value => [__('role_reviewer'), __('role_reviewer_desc')],
                            RoleName::EDITOR->value   => [__('role_editor'),   __('role_editor_desc')],
                            'Owner'                   => [__('role_owner'),    __('role_owner_desc')],
                        ];
                    @endphp
                    @foreach ($rolePresets as $roleKey => [$label, $desc])
                        @php
                            // Q3-Politur G9 (2026-08-20) / P-01: der
                            // Highlight-Vergleich laeuft jetzt live gegen
                            // die aktuelle Toggle-Kombination
                            // ($this->matchedPreset), nicht mehr gegen
                            // den zuletzt gespeicherten $active['role'].
                            $isRolePresetActive = ($roleKey === 'Owner' && $isOwner)
                                || ($roleKey === $this->matchedPreset);
                            $isOwnerButton = ($roleKey === 'Owner');
                        @endphp
                        <button
                            type="button"
                            role="tab"
                            @if (! $isOwnerButton && ! $isOwner)
                                wire:click="applyPreset('{{ $roleKey }}')"
                            @endif
                            @disabledIf($isOwnerButton || $isOwner, __('role_owner_locked_hint'))
                            aria-selected="{{ $isRolePresetActive ? 'true' : 'false' }}"
                            @if (! $isOwnerButton && ! $isOwner) title="{{ $desc }}" @endif
                            aria-label="{{ $label }} — {{ $desc }}"
                            class="rounded-md border px-4 py-2 text-body transition-colors
                                   {{ $isRolePresetActive
                                       ? 'bg-ink-900 text-paper-0 border-ink-900'
                                       : 'bg-paper-0 text-ink-900 border-line-200 hover:border-ink-400' }}
                                   focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-bar"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="mt-6 overflow-hidden rounded-lg border border-line-200 bg-paper-0">
                @php
                    // Sechs Toggles (Karl-Entscheidung 2026-08-15).
                    // Handoff v4 zeigt vier — comment + invite sind
                    // die Erweiterung, dokumentiert im Briefing
                    // permission-matrix-6-toggles.md.
                    $toggleRows = [
                        PermissionName::EDIT->value    => [__('permission_edit_title'),    __('permission_edit_desc')],
                        PermissionName::ADD->value     => [__('permission_add_title'),     __('permission_add_desc')],
                        PermissionName::DELETE->value  => [__('permission_delete_title'),  __('permission_delete_desc')],
                        PermissionName::PUBLISH->value => [__('permission_publish_title'), __('permission_publish_desc')],
                        PermissionName::COMMENT->value => [__('permission_comment_title'), __('permission_comment_desc')],
                        PermissionName::INVITE->value  => [__('permission_invite_title'),  __('permission_invite_desc')],
                    ];
                @endphp
                @foreach ($toggleRows as $permName => [$title, $desc])
                    @php
                        $isOn = $isOwner ? true : (bool) ($permissions[$permName] ?? false);
                        $disabled = $isOwner;
                    @endphp
                    <div class="flex items-start justify-between gap-4 border-b border-line-100 px-5 py-4 last:border-b-0">
                        <div class="min-w-0 flex-1">
                            <div class="text-body font-semibold text-ink-900">{{ $title }}</div>
                            <div class="text-caption text-ink-500">{{ $desc }}</div>
                        </div>
                        <button
                            type="button"
                            role="switch"
                            aria-checked="{{ $isOn ? 'true' : 'false' }}"
                            aria-label="{{ $title }}"
                            @if (! $disabled) wire:click="$toggle('permissions.{{ $permName }}')" @endif
                            @disabledIf($disabled, __('role_owner_locked_hint'))
                            class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors
                                   {{ $isOn ? 'bg-primary' : 'bg-line-200' }}
                                   focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                        >
                            <span
                                aria-hidden="true"
                                class="inline-block size-5 transform rounded-full bg-white shadow transition-transform
                                       {{ $isOn ? 'translate-x-5' : 'translate-x-0.5' }}"
                            ></span>
                        </button>
                    </div>
                @endforeach
            </div>

            {{-- Save-Bar erscheint, sobald ein Toggle vom Initial-
                 zustand abweicht. Wir rendern die Bar per Blade-@if
                 auf der server-seitigen Computed-Property isDirty;
                 Livewire re-rendert bei jedem Toggle-Wechsel, damit
                 die Bar erscheint bzw. verschwindet. Alpine-x-show
                 innerhalb der Komponente steht auf true, damit die
                 Enter-Transition beim Einblenden greift. --}}
            @if ($this->isDirty)
                <x-ui.save-bar
                    dirty-expr="true"
                    save-expr="$wire.save()"
                    discard-expr="$wire.discard()"
                />
            @endif
        @endif
    </section>

    {{-- Invite-Modal (5d.4). Livewire schaltet die Sichtbarkeit ueber
         $showInviteModal, Alpine-x-show haengt daran fuer Enter/Leave-
         Transitions. --}}
    @if ($showInviteModal)
        <div
            class="fixed inset-0 z-40 flex items-center justify-center bg-ink-900/40 px-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="invite-title"
            wire:click.self="closeInvite"
            {{-- Q3-Politur G2 (2026-08-20) / A11Y-03 · A11Y-04:
                 Escape schliesst, Fokus faellt auf das E-Mail-Feld. --}}
            x-data
            x-init="$nextTick(() => document.getElementById('inviteEmail')?.focus())"
            @keydown.escape.window="$wire.closeInvite()"
        >
            <div class="w-full max-w-md rounded-lg border border-line-200 bg-paper-0 shadow-lg">
                <header class="flex items-center justify-between border-b border-line-200 px-5 py-3">
                    <h3 id="invite-title" class="text-heading font-semibold text-ink-900">
                        {{ __('invite') }}
                    </h3>
                    <button
                        type="button"
                        wire:click="closeInvite"
                        aria-label="{{ __('close') }}"
                        class="rounded-md p-1 text-ink-500 hover:bg-ink-900/5 hover:text-ink-900
                               focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink-900"
                    >
                        <x-icon name="x" size="4"/>
                    </button>
                </header>

                <form wire:submit.prevent="submitInvite" class="p-5">
                    <label for="inviteEmail" class="mb-1 block text-caption font-medium text-ink-700">
                        {{ __('email') }}
                    </label>
                    <input
                        type="email"
                        id="inviteEmail"
                        wire:model="inviteEmail"
                        required
                        autocomplete="email"
                        placeholder="name@example.org"
                        @if ($inviteError) aria-invalid="true" aria-describedby="inviteError" @endif
                        class="block w-full rounded-md border border-line-200 bg-paper-0 px-3 py-2 text-body text-ink-900
                               focus:border-primary focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary"
                    />
                    @if ($inviteError)
                        <p id="inviteError" role="alert"
                           class="mt-1 text-caption text-danger">
                            {{ $inviteError }}
                        </p>
                    @endif

                    <div class="mt-5 flex items-center justify-end gap-2">
                        <button
                            type="button"
                            wire:click="closeInvite"
                            class="inline-flex items-center rounded-md bg-transparent px-4 py-2 text-body font-medium text-ink-700
                                   hover:bg-ink-900/5
                                   focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink-900"
                        >
                            {{ __('cancel') }}
                        </button>
                        <button
                            type="submit"
                            class="inline-flex items-center gap-1.5 rounded-md bg-primary px-4 py-2 text-body font-medium text-primary-on
                                   hover:opacity-90
                                   focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                        >
                            <x-icon name="send" size="4"/>
                            {{ __('invite') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
