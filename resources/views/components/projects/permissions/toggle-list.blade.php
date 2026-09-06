@props(['permissions', 'isOwner'])

{{--
    Q4-Etappe 1 / I4 (2026-08-27): Sechs Toggle-Reihen fuer die Editorial-
    Permissions. Extrahiert aus project-permissions.blade.php.

    Wire-Binding `wire:click="$toggle('permissions.<name>')"` loest gegen
    den Volt-Root auf.

    Props:
    - $permissions  array<string, bool>  Ist-Zustand aus $permissions
    - $isOwner      bool                 Owner-Sonderfall: alle On, disabled
--}}

<div class="mt-6 overflow-hidden rounded-lg border border-line-200 bg-paper-0">
    @php
        // Sechs Toggles (Karl-Entscheidung 2026-08-15). Handoff v4 zeigt
        // vier — comment + invite sind die Erweiterung, dokumentiert im
        // Briefing permission-matrix-6-toggles.md.
        $toggleRows = [
            \App\Support\PermissionName::EDIT->value    => [__('permission_edit_title'),    __('permission_edit_desc')],
            \App\Support\PermissionName::ADD->value     => [__('permission_add_title'),     __('permission_add_desc')],
            \App\Support\PermissionName::DELETE->value  => [__('permission_delete_title'),  __('permission_delete_desc')],
            \App\Support\PermissionName::PUBLISH->value => [__('permission_publish_title'), __('permission_publish_desc')],
            \App\Support\PermissionName::COMMENT->value => [__('permission_comment_title'), __('permission_comment_desc')],
            \App\Support\PermissionName::INVITE->value  => [__('permission_invite_title'),  __('permission_invite_desc')],
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
