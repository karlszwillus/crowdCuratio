{{--
crowdCuratio - Curating together virtually
Copyright (C)2026 - berlinHistory e.V.

Wiederverwendbarer Projekt-Tab-Balken (Phase 5d.4-Followup).

Zeigt vier Segmente an — Bearbeiten, Metadaten, Uebersetzen,
Berechtigungen — auf allen vier Projekt-Screens. Aktiver Tab wird
per :active gesteuert.

Nutzung:
    <x-projects.tabs :project="$project" active="edit" />
    <x-projects.tabs :project="$project" active="meta" />
    <x-projects.tabs :project="$project" active="translate" />
    <x-projects.tabs :project="$project" active="permissions" />

Der Berechtigungen-Tab erscheint nur fuer User mit `invite`-Permission
auf dem Projekt — analog zum ehemaligen Sichtbarkeits-Gate in
projects/create.blade.php. Admin und Owner haben `invite` implizit
(ProjectPolicy::before + userHasPermissionOnProject-Owner-Shortcut).
--}}

@props([
    'project',
    'active' => 'edit',
])

@php
    $canInvite = auth()->user()?->can('invite', $project) ?? false;

    $items = [
        [
            'label'  => __('edit'),
            // projects.edit rendert den vollen Editor mit Sidebar-Tree
            // (@include('chapters.index') im projects/edit.blade.php).
            // Konsistentere URL (/projects/{id}/edit) als
            // /chapters?id=X.
            'href'   => route('projects.edit', $project->id),
            'active' => $active === 'edit',
        ],
        [
            'label'  => __('metadata'),
            'href'   => route('project.metadata', $project->id),
            'active' => $active === 'meta',
        ],
        [
            'label'  => __('translate'),
            'href'   => route('translate', $project->id),
            'active' => $active === 'translate',
        ],
    ];

    if ($canInvite) {
        $items[] = [
            'label'  => __('permissions'),
            'href'   => route('projects.permissions', $project->id),
            'active' => $active === 'permissions',
        ];
    }
@endphp

<x-ui.segmented
    :items="$items"
    :aria-label="__('editor_mode')"
/>
