@props(['users', 'selectedUserId', 'roleDescriptions'])

{{--
    Q4-Etappe 1 / I4 (2026-08-27): Sidebar mit User-Liste + Invite-Button.
    Extrahiert aus resources/views/livewire/project-permissions.blade.php.
    Wire-Bindings (wire:click="invite" und wire:click="selectUser(...)")
    loesen weiter gegen den Volt-Root auf, weil diese Blade-Komponente
    im DOM des Root sitzt.

    Props:
    - $users            Collection<int, array>  aus $this->users
    - $selectedUserId   int                     aktuell markierte User-ID
    - $roleDescriptions array<string, string>   Slug → Beschreibung
--}}

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

    <ul class="p-2" role="list">
        @foreach ($users as $user)
            @php
                $isActive = $user['id'] === $selectedUserId;
                $userRoleDesc = ($user['is_owner'] ?? false)
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
