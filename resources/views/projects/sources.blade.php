{{--
crowdCuratio - Curating together virtually
Copyright (C) 2022, 2026 - berlinHistory e.V.

Q4-Etappe 3 / C0d (2026-09-07): Projekt-weite Quellenverwaltung.
Wrapper fuer die Volt-Komponente `project-sources`.
--}}
@extends('projects.layout')

@section('main')
    <x-projects.chrome :project="$project" active="sources"/>

    <livewire:project-sources :project-id="$project->id"/>
@endsection
