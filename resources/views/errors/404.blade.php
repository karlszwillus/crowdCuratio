{{--
crowdCuratio - Curating together virtually
Copyright (C)2026 - berlinHistory e.V.

Phase 5e.5: 404-Fehlerseite. Persona-freundlicher Text — kein
Stacktrace, keine App-Debug-Details. Debug-Bilder liefert Laravel
Ignition, wenn APP_DEBUG=true ist (die kommt bereits vor dem
Rendering dieser View).
--}}

@extends('projects.layout')

@section('content')
    @include('errors._error-shell', [
        'code'     => '404',
        'iconName' => 'compass',
        'title'    => __('error_404_title'),
        'body'     => __('error_404_body'),
    ])
@endsection
