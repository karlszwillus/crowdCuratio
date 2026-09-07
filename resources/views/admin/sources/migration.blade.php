{{--
crowdCuratio - Curating together virtually
Copyright (C) 2022, 2026 - berlinHistory e.V.

Q4-Etappe 3 / C0-8b · Admin-Wrapper fuer den Migrations-Assistenten.
Zeigt die Volt-Komponente `admin-sources-migration`. Admin-only ueber
die Route-Middleware.
--}}
@extends('projects.layout')

@section('content')
    <livewire:admin-sources-migration/>
@endsection
