{{--
crowdCuratio - Curating together virtually
Copyright (C)2026 - berlinHistory e.V.

Phase 5e.5: 403-Fehlerseite. Persona-freundlicher Text nach
PLANS/PHASE-05.md Block 5e Schritt 5e.5.

Laravel liefert diese View automatisch bei
AuthorizationException / HttpException(403).
--}}

@extends('projects.layout')

@section('content')
    @include('errors._error-shell', [
        'code'     => '403',
        'iconName' => 'shield-off',
        'title'    => __('error_403_title'),
        'body'     => __('error_403_body'),
    ])
@endsection
