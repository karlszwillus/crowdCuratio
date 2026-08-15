{{--
crowdCuratio - Curating together virtually
Copyright (C)2026 - berlinHistory e.V.

Wrapper fuer die Volt-Komponente project-permissions (Screen 3B,
Handoff v4). Loest die alte Modal-Kaskade in projects/create.blade.php
ab (Phase 5d.4).
--}}

@extends('projects.layout')

@section('main')
    <div class="sticky top-0 z-20 -mx-6 -mt-6 mb-6 flex flex-wrap items-center justify-between gap-4
                border-b border-line-200 bg-canvas-bg/95 px-6 py-3
                backdrop-blur supports-[backdrop-filter]:bg-canvas-bg/80">
        <div class="min-w-0 flex-1">
            <x-ui.breadcrumb :tree="app(App\Services\ProjectTreeService::class)->breadcrumbTree($project)"/>
        </div>

        <x-projects.tabs :project="$project" active="permissions"/>
    </div>

    <livewire:project-permissions :project-id="$project->id"/>
@endsection
