{{--
crowdCuratio - Curating together virtually
Copyright (C) 2022, 2026 - berlinHistory e.V.

Q4-Etappe 3 / C0d (2026-09-07): Projekt-weite Quellenverwaltung.
Wrapper fuer die Volt-Komponente `project-sources`.
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
    <x-projects.chrome :project="$project" active="sources">
        <x-slot:actions>
            <x-projects.edit-save-state/>
            <x-projects.editor-actions :project="$project"/>
        </x-slot:actions>
    </x-projects.chrome>
    {{-- Export-Modal auf Chrome-Geschwister-Ebene. --}}
    <x-projects.export-modal :project="$project"/>

    <livewire:project-sources :project-id="$project->id"/>
@endsection
