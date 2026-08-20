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
    // Optional: hat der Block schon Revisions? Wenn null, prueft die
    // Komponente selbst per COUNT-Query. Aufrufer, die die Zahl schon
    // eager geladen haben (withCount('revisions')) koennen sie hier
    // reinreichen und die Extra-Query sparen.
    'hasHistory' => null,
])

@php
    $srLabel = $label ?? __('history_open_button');

    // Phase 5ab.3-Followup: Indikator „gab schon eine Aenderung".
    // Analog zum Bestandsverhalten des alten rotate-ccw-Links, das dem
    // Kurator sofort zeigte, wo Historie vorliegt. Fallback auf
    // Selbst-Query, wenn kein hasHistory reingereicht wurde.
    $indicator = $hasHistory;
    if ($indicator === null) {
        $fqcn = \App\Support\RevisionSubject::TYPES[$subjectType] ?? null;
        if ($fqcn !== null) {
            $indicator = \App\Models\Revision::query()
                ->where('subject_type', $fqcn)
                ->where('subject_id', (int) $subjectId)
                ->exists();
        } else {
            $indicator = false;
        }
    }
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
        {{-- 2 px kleiner Punkt oben rechts, primary-Farbe. `sr-only`-
             Zusatz nennt den Zustand fuer Screenreader (§ WCAG 1.4.1
             „Nicht ausschliesslich Farbe"). --}}
        <span
            aria-hidden="true"
            class="absolute right-1.5 top-1.5 size-2 rounded-full bg-primary ring-2 ring-paper-0"
        ></span>
        <span class="sr-only">— {{ __('history_has_changes') }}</span>
    @endif
</button>
