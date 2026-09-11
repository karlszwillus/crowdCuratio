{{--
crowdCuratio - Curating together virtually
Copyright (C)2026 - berlinHistory e.V.

Phase 5ab.3: Verlauf-Icon-Button fuer einen Content-Block.

Feuert `history-panel:load-and-open` mit `{subjectType, subjectId}`, das
Panel oeffnet und die Livewire-Liste zieht die Fassungen. Das Panel-
Wrapper schliesst dabei ein evtl. offenes Kommentar-Panel.

Props:
- `subjectType` — Kurzname aus RevisionSubject::TYPES (Chapter/Entry/…)
- `subjectId` — die konkrete ID
- `label` — optionaler Screenreader-Text (Default: „Verlauf ansehen")
--}}

@props([
    'subjectType',
    'subjectId',
    'label' => null,
    // Optional: Anzahl Revisions. Akzeptiert:
    //   - null → Komponente prueft selbst per COUNT-Query
    //   - bool → true == "≥1 vorhanden" (Legacy-Punkt), false == keine
    //   - int  → exakte Zahl (empfohlen, wenn Aufrufer withCount hat)
    'hasHistory' => null,
])

@php
    $srLabel = $label ?? __('history_open_button');

    // Q3-Politur G9 (2026-08-20) / UX-11: aus dem Legacy-Punkt wird ein
    // Zahl-Badge analog Kommentar-Trigger. `$hasHistory` darf jetzt eine
    // Zahl sein — bleibt fuer Alt-Aufrufer als bool rueckwaertskompatibel.
    if (is_int($hasHistory)) {
        $historyCount = $hasHistory;
    } elseif ($hasHistory === true) {
        $historyCount = null; // bekannt: „gibt welche", aber Zahl unklar
    } elseif ($hasHistory === false) {
        $historyCount = 0;
    } else {
        $fqcn = \App\Support\RevisionSubject::TYPES[$subjectType] ?? null;
        $historyCount = $fqcn !== null
            ? \App\Models\Revision::query()
                ->where('subject_type', $fqcn)
                ->where('subject_id', (int) $subjectId)
                ->count()
            : 0;
    }
    // A7-Followup (Karl 2026-08-21): v1 (Anlage) zaehlt nicht als
    // „Verlauf" — der Marker erscheint erst ab dem ersten Edit.
    // Anzeige-Zahl ist die Zahl der Edits (Revisions - 1).
    $editCount = $historyCount === null ? null : max(0, $historyCount - 1);
    $indicator = $historyCount === null ? true : ($historyCount > 1);
@endphp

<button
    type="button"
    onclick="window.dispatchEvent(new CustomEvent('history-panel:load-and-open', { detail: { subjectType: @js($subjectType), subjectId: {{ (int) $subjectId }} } }))"
    @class([
        'relative inline-flex items-center justify-center rounded-md p-2',
        'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary',
        // Karl 2026-09-11: „gefuellt vs. leer" statt rotem Zahl-Badge.
        // Mit Verlauf → voll dunkles Icon, dickerer Stroke, sanfter
        // Hintergrund-Chip; wirkt „prallend voll".
        // Ohne Verlauf → sehr blass, duennerer Stroke; wirkt „leer".
        // WCAG 1.4.1: die sr-only-Ansage nennt den Zustand zusaetzlich.
        'text-ink-900 bg-line-100 hover:bg-line-200 [&_svg]:stroke-[2.5]' => $indicator,
        'text-ink-300 hover:bg-line-100 hover:text-ink-900 [&_svg]:stroke-[1.25]' => ! $indicator,
    ])
    aria-label="{{ $srLabel }}"
    aria-haspopup="dialog"
    aria-controls="history-panel"
    title="{{ $srLabel }}"
>
    <x-icon name="history" size="4"/>
    @if ($indicator)
        <span class="sr-only">
            — @if (is_int($editCount) && $editCount > 0)
                {{ __('history_has_changes_count', ['count' => $editCount]) }}
            @else
                {{ __('history_has_changes') }}
            @endif
        </span>
    @endif
</button>
