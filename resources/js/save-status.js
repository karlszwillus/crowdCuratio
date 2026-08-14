/*
 * crowdCuratio - Curating together virtually
 * Copyright (C)2026 - berlinHistory e.V.
 *
 * Auto-Save-Indikator (Phase 5c.2).
 *
 * Alpine-Store `saveStatus` mit vier Zuständen — idle, saving,
 * saved, error — plus dem Zeitpunkt des letzten Save als
 * Unix-Millisekunden. Der Header-Indikator (siehe
 * layouts/navi-header.blade.php) liest den Store reaktiv aus und
 * zeigt den passenden Text.
 *
 * Die States werden von Livewire-Events getrieben:
 *   - `saved`         (aus <livewire:inline-editor>) → state='saved'
 *   - `save-failed`   → state='error'
 *   - `save-started`  → state='saving' (optional, für lange Saves)
 *
 * Der Listener wird via `alpine:init` an Livewires Alpine-Instance
 * gehängt, damit kein Race mit der eigenen Alpine-Instance entsteht
 * (siehe ADR-0025-Pattern).
 */

document.addEventListener('alpine:init', () => {
    window.Alpine.store('saveStatus', {
        state: 'idle',
        lastSavedAt: null,

        markSaving() {
            this.state = 'saving';
        },

        markSaved() {
            this.state = 'saved';
            this.lastSavedAt = Date.now();

            // Auto-Fade nach 5 s: der Indikator wandert zurück auf
            // idle und der Text verschwindet aus dem Header. Bei
            // einem erneuten Save flippt der State kurz auf saved
            // zurück, die Alpine-Enter-Transition (grüner Puls) läuft
            // dadurch neu — sichtbares Feedback bei wiederholtem
            // Speichern.
            clearTimeout(this._idleTimer);
            this._idleTimer = setTimeout(() => {
                if (this.state === 'saved') {
                    this.state = 'idle';
                }
            }, 5000);
        },

        markError() {
            this.state = 'error';
        },

        reset() {
            this.state = 'idle';
            this.lastSavedAt = null;
        },
    });

    // Livewire-Event-Bridge. Wir hören global auf die vom
    // Inline-Editor dispatched Events und mappen sie auf den Store.
    // Livewire 4 v3 hängt Events an das window-Event-Ziel, wenn
    // sie nicht mit `.to(Component)` bestimmt sind.
    window.addEventListener('saved', () => {
        window.Alpine.store('saveStatus').markSaved();
    });

    window.addEventListener('save-failed', () => {
        window.Alpine.store('saveStatus').markError();
    });

    window.addEventListener('save-started', () => {
        window.Alpine.store('saveStatus').markSaving();
    });
});
