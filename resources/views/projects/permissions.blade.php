{{--
crowdCuratio - Curating together virtually
Copyright (C)2026 - berlinHistory e.V.

Wrapper fuer die Volt-Komponente project-permissions (Screen 3B,
Handoff v4). Loest die alte Modal-Kaskade in projects/create.blade.php
ab (Phase 5d.4).
--}}

@extends('projects.layout')

@section('main')
    <x-projects.chrome :project="$project" active="permissions"/>

    <livewire:project-permissions :project-id="$project->id"/>
@endsection
