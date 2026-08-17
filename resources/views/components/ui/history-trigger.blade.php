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
])

@php
    $srLabel = $label ?? __('history_open_button');
@endphp

<button
    type="button"
    onclick="window.dispatchEvent(new CustomEvent('history-panel:load-and-open', { detail: { subjectType: @js($subjectType), subjectId: {{ (int) $subjectId }} } }))"
    class="inline-flex items-center justify-center rounded-md p-2 text-ink-600 hover:bg-line-100 hover:text-ink-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
    aria-label="{{ $srLabel }}"
    title="{{ $srLabel }}"
>
    <x-icon name="history" size="4" />
</button>
