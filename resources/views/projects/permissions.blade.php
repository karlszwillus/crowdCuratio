{{--
crowdCuratio - Curating together virtually
Copyright (C)2026 - berlinHistory e.V.

Wrapper fuer die Volt-Komponente project-permissions (Screen 3B,
Handoff v4). Loest die alte Modal-Kaskade in projects/create.blade.php
ab (Phase 5d.4).
--}}

@extends('projects.layout')

{{-- Q4-Etappe 7 · E7-4 (2026-09-11): Struktur-Baum als Orientierungs-
     panel — read-only. --}}
@section('log')
    <livewire:sidebar-tree
        :project="$project"
        :readonly="true"
        :key="'sidebar-tree-readonly-'.$project->id"/>
@endsection

@section('main')
    <x-projects.chrome :project="$project" active="permissions">
        <x-slot:actions>
            <x-projects.edit-save-state/>
            <x-projects.editor-actions :project="$project"/>
        </x-slot:actions>
    </x-projects.chrome>
    {{-- Export-Modal auf Chrome-Geschwister-Ebene. --}}
    <x-projects.export-modal :project="$project"/>

    <livewire:project-permissions :project-id="$project->id"/>
@endsection
