{{--
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

Q4-Etappe 5 / G-Fund-5 (2026-09-09): „Erarbeitet von" am Projekt.
Aggregation aller entry_credits, drei Spalten nach Rolle. Nur
sichtbar wenn mindestens ein Credit gepflegt ist.

Erwartet: $project (Project) im Kontext.
--}}
@php
    /** @var \App\Models\Project $project */
    $allCredits = collect();
    foreach ($project->chapters ?? [] as $ch) {
        foreach ($ch->entries ?? [] as $e) {
            foreach ($e->credits ?? [] as $c) {
                $allCredits->push($c);
            }
        }
    }

    $byRole = [
        'recherche' => $allCredits->where('role', 'recherche')->pluck('name')->unique()->sort()->values(),
        'redaktion' => $allCredits->where('role', 'redaktion')->pluck('name')->unique()->sort()->values(),
        'hinweis'   => $allCredits->where('role', 'hinweis')->pluck('name')->unique()->sort()->values(),
    ];
@endphp
@if($allCredits->isNotEmpty())
    <section class="cc-project-credits" aria-labelledby="project-credits-heading">
        <div class="cc-project-credits__inner">
            <h4 id="project-credits-heading">{{ __('reader_project_credits_heading') }}</h4>
            <div class="cc-project-credits__grid">
                @foreach($byRole as $role => $names)
                    @if($names->isNotEmpty())
                        <div>
                            <h5 class="cc-mono-caps">{{ __('reader_credit_'.$role) }}</h5>
                            <ul>
                                @foreach($names as $name)
                                    <li>{{ $name }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </section>
@endif
