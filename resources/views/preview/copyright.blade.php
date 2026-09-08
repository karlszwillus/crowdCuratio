{{--
crowdCuratio - Curating together virtually
Copyright (C) 2022, 2026 - berlinHistory e.V.

Q4-Etappe 5 / G6 (2026-09-08): Copyright/Policy-Seite erbt jetzt
vom gemeinsamen preview/layout.blade.php. Vorher eine 129-LoC-
Duplikation des Reader-Head-Blocks inkl. Slick, FA und GSAP.
Diese Seite ist ohnehin nur eine Textausgabe (Impressum,
Datenschutz) — kein Reader-Chrome mit Anchor-Navi nötig.
--}}
@extends('preview.layout')

@section('title-suffix', __($type ?? ''))

@section('content')
    <div class="container">
        @if(isset($content))
            <h1>{{ __($type) }}</h1>
            <div class="mb-4">
                @rich($content )
            </div>
        @endif
        <div class="mt-4">
            <a href="{{ url()->previous() }}">&lt; {{ __('back') }}</a>
        </div>
    </div>
@endsection
