{{--
crowdCuratio - Curating together virtually
Copyright (C)2026 - berlinHistory e.V.

Phase 5e.5: 500-Fehlerseite. Persona-freundlicher Text — kein
Stacktrace, keine App-Debug-Details.

Vor 5e.5 zeigte diese View im Non-Debug-Modus die
Exception-Message inkl. Datei und Zeile ($exception->getMessage()
und getLine()) an — ein Info-Leak an Endnutzer, den wir mit dem
Rewrite schliessen. In APP_DEBUG=true landet der Nutzer ohnehin
in Laravel Ignition und sieht diese View gar nicht.
--}}

@extends(Auth::check() ? 'projects.layout' : 'layouts.error-guest')

@section('title', __('error_500_title'))

@section('content')
    @include('errors._error-shell', [
        'code'     => '500',
        'iconName' => 'alert-triangle',
        'title'    => __('error_500_title'),
        'body'     => __('error_500_body'),
    ])
@endsection
