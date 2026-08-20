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
    $indicator = $historyCount === null ? true : $historyCount > 0;
@endphp

<button
    type="button"
    onclick="window.dispatchEvent(new CustomEvent('history-panel:load-and-open', { detail: { subjectType: @js($subjectType), subjectId: {{ (int) $subjectId }} } }))"
    class="relative inline-flex items-center justify-center rounded-md p-2 text-ink-600 hover:bg-line-100 hover:text-ink-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
    aria-label="{{ $srLabel }}"
    aria-haspopup="dialog"
    aria-controls="history-panel"
    title="{{ $srLabel }}"
>
    <x-icon name="history" size="4" />
    @if ($indicator)
        {{-- Zahl-Badge oben rechts (analog Kommentar-Trigger). Bei
             unbekannter Zahl faellt es auf einen kleinen Punkt zurueck,
             damit der Vorher-Kontrakt „Punkt = Historie da" nicht bricht.
             sr-only-Zusatz nennt den Zustand fuer Screenreader
             (§ WCAG 1.4.1 „Nicht ausschliesslich Farbe"). --}}
        @if (is_int($historyCount) && $historyCount > 0)
            <span
                aria-hidden="true"
                class="absolute -right-1 -top-1 inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-primary px-1 text-caption font-medium text-primary-on ring-2 ring-paper-0"
            >{{ $historyCount }}</span>
            <span class="sr-only">
                — {{ __('history_has_changes_count', ['count' => $historyCount]) }}
            </span>
        @else
            <span
                aria-hidden="true"
                class="absolute right-1.5 top-1.5 size-2 rounded-full bg-primary ring-2 ring-paper-0"
            ></span>
            <span class="sr-only">— {{ __('history_has_changes') }}</span>
        @endif
    @endif
</button>
