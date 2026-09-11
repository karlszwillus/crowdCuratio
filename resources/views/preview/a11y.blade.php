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
    <div class="container">
        <header class="mb-6">
            <p class="mb-1 text-mono-caps font-mono uppercase tracking-widest text-ink-500">
                {{ $project->name }}
            </p>
            <h1 class="text-title font-semibold text-ink-900">
                {{ __('reader_a11y_title') }}
            </h1>
        </header>

        <div class="max-w-3xl space-y-4 text-body text-ink-700">
            <p>{!! __('reader_a11y_intro') !!}</p>
            <h2 class="mt-6 text-heading font-semibold text-ink-900">{{ __('reader_a11y_scope_heading') }}</h2>
            <p>{!! __('reader_a11y_scope_body') !!}</p>
            <h2 class="mt-6 text-heading font-semibold text-ink-900">{{ __('reader_a11y_known_heading') }}</h2>
            <p>{!! __('reader_a11y_known_body') !!}</p>
            <h2 class="mt-6 text-heading font-semibold text-ink-900">{{ __('reader_a11y_feedback_heading') }}</h2>
            <p>{!! __('reader_a11y_feedback_body') !!}</p>
        </div>
    </div>
@endsection
