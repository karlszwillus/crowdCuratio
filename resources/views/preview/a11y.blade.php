{{--
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

Q4-Etappe 7 · Etappe-6-Rest (2026-09-11): Barrierefreiheitserklaerung.
Loest den Fußzeile-Anker `#barrierefreiheit` ab, der ins Leere zeigte.
Statischer Text im Reader-Layout — projektuebergreifend gleich, aber
mit Projekt-Kontext im Kopf.
--}}

@extends('preview.layout')

@section('content')
    <div class="container cc-page">
        <header class="cc-page__header">
            <span class="cc-mono-caps cc-page__kicker">{{ $project->name }}</span>
            <h1 class="cc-page__title">{{ __('reader_a11y_title') }}</h1>
        </header>

        <div class="cc-page__prose">
            <p>{!! __('reader_a11y_intro') !!}</p>
            <h2>{{ __('reader_a11y_scope_heading') }}</h2>
            <p>{!! __('reader_a11y_scope_body') !!}</p>
            <h2>{{ __('reader_a11y_known_heading') }}</h2>
            <p>{!! __('reader_a11y_known_body') !!}</p>
            <h2>{{ __('reader_a11y_feedback_heading') }}</h2>
            <p>{!! __('reader_a11y_feedback_body') !!}</p>
        </div>
    </div>
@endsection
