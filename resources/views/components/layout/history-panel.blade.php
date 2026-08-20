{{--
crowdCuratio - Curating together virtually
Copyright (C)2026 - berlinHistory e.V.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.
--}}

{{-- Phase 5ab.3: Verlauf-Slide-out-Panel rechts (Design v6 § 6).

Zweitverwendung des Kommentar-Panel-Musters aus 5x.1: gleicher Slide-out,
gleiche Anatomie, gleiche Animation. Unterschied ist der Inhalt und ein
Namens-Guard, damit Verlauf und Kommentare sich gegenseitig schliessen
(§ 6: „Nur eines von beiden ist gleichzeitig offen").

Wird per `panel:open` mit `{name: 'history'}` geoeffnet (analog dazu
`{name: 'comments'}` fuer das Kommentar-Panel). Empfaengt ausserdem
`history-panel:load` mit `{subjectType, subjectId}`, damit die Livewire-
Liste die richtige Fassungs-Historie zieht.

Props:
- `title` — Titel im Kopf (Default: „Verlauf").
--}}

@props([
    'title' => null,
])

@php
    $panelTitle = $title ?? __('history_panel_title');
@endphp

<div
    x-cloak
    x-data="{
        open: false,
        _previousFocus: null,
        openPanel() {
            // Q3-Politur G2 (2026-08-20) / A11Y-04: Fokus-Return.
            // Merkt sich das aktuell fokussierte Element, damit
            // wir beim Schliessen dorthin zuruecksetzen.
            this._previousFocus = document.activeElement;
            this.open = true;
            this.$nextTick(() => {
                this.$refs.closeBtn?.focus();
            });
        },
        closePanel() {
            this.open = false;
            const target = this._previousFocus;
            this._previousFocus = null;
            // Fokus zurueck auf den ausloesenden Trigger, wenn er
            // noch im DOM ist.
            if (target && typeof target.focus === 'function' && document.contains(target)) {
                this.$nextTick(() => target.focus());
            }
        },
    }"
    @keydown.escape.window="if (open) closePanel()"
    @panel:open.window="
        if ($event.detail && $event.detail.name === 'history') openPanel();
        else if (open && $event.detail && $event.detail.name && $event.detail.name !== 'history') closePanel();
    "
    @panel:close.window="closePanel()"
    @history-panel:load-and-open.window="
        Livewire.dispatch('history-panel:load', {
            subjectType: $event.detail.subjectType,
            subjectId: $event.detail.subjectId,
            scope: $event.detail.scope || 'block',
        });
        window.dispatchEvent(new CustomEvent('panel:open', { detail: { name: 'history' } }));
    "
>
    <div
        :class="open ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'"
        @click="closePanel()"
        class="fixed inset-0 z-40 bg-ink-900/40"
        style="transition: opacity 300ms ease-out;"
        aria-hidden="true"
    ></div>

    <aside
        id="history-panel"
        :class="open ? 'translate-x-0' : 'translate-x-full'"
        role="dialog"
        aria-modal="true"
        :aria-hidden="open ? 'false' : 'true'"
        aria-label="{{ $panelTitle }}"
        class="fixed inset-y-0 right-0 z-50 flex w-[26rem] max-w-full flex-col border-l border-line-200 bg-paper-0 shadow-floating"
        style="transition: transform 350ms cubic-bezier(0.16, 1, 0.3, 1);"
    >
        <header class="flex items-center justify-between border-b border-line-200 px-4 py-3">
            <h2 class="text-heading font-semibold text-ink-900">{{ $panelTitle }}</h2>
            <button
                type="button"
                x-ref="closeBtn"
                @click="closePanel()"
                class="rounded-md p-2 text-ink-600 hover:bg-line-100 hover:text-ink-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                aria-label="{{ __('close') }}"
            >
                <x-icon name="x" size="5" />
            </button>
        </header>

        <div class="flex-1 overflow-y-auto px-4 py-4">
            {{ $slot }}
        </div>
    </aside>
</div>
