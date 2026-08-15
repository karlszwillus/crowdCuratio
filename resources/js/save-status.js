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

        // Pro-Block-Status (P2.12 aus Designer-Review 5-D.6b).
        // Key ist ein Slot-Bezeichner in der Form
        // '{Model}-{id}' (z. B. 'Text-42'). Wert:
        //   { state: 'idle'|'saving'|'saved'|'error', at: ms }
        blocks: {},

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

        /**
         * Pro-Block-Marker. Wird bei jedem `saved`-Event aus einem
         * Inline-Editor gesetzt, sofern das Event `model` und `id`
         * als Payload trägt.
         */
        markBlockSaved(slot) {
            if (! slot) return;
            this.blocks = {
                ...this.blocks,
                [slot]: { state: 'saved', at: Date.now() },
            };
            const currentSlot = slot;
            setTimeout(() => {
                const entry = this.blocks[currentSlot];
                if (entry && entry.state === 'saved') {
                    this.blocks = {
                        ...this.blocks,
                        [currentSlot]: { state: 'idle', at: entry.at },
                    };
                }
            }, 10000);
        },

        markBlockError(slot) {
            if (! slot) return;
            this.blocks = {
                ...this.blocks,
                [slot]: { state: 'error', at: Date.now() },
            };
        },

        reset() {
            this.state = 'idle';
            this.lastSavedAt = null;
            this.blocks = {};
        },
    });

    // Livewire-Event-Bridge. Wir hören global auf die vom
    // Inline-Editor dispatched Events und mappen sie auf den Store.
    // Livewire 4 v3 hängt Events an das window-Event-Ziel, wenn
    // sie nicht mit `.to(Component)` bestimmt sind.
    window.addEventListener('saved', (event) => {
        const store = window.Alpine.store('saveStatus');
        store.markSaved();

        // Blocks: Payload {field, model, id} — wir bilden den Slot
        // aus 'Model-id'. Bei fehlender Payload fällt der Block-
        // Track aus, die globale State-Anzeige bleibt.
        const detail = event.detail?.[0] ?? event.detail ?? {};
        const modelName = detail.model;
        const modelId = detail.id;
        if (modelName && modelId != null) {
            store.markBlockSaved(`${modelName}-${modelId}`);
        }
    });

    window.addEventListener('save-failed', (event) => {
        const store = window.Alpine.store('saveStatus');
        store.markError();

        const detail = event.detail?.[0] ?? event.detail ?? {};
        const modelName = detail.model;
        const modelId = detail.id;
        if (modelName && modelId != null) {
            store.markBlockError(`${modelName}-${modelId}`);
        }
    });

    window.addEventListener('save-started', () => {
        window.Alpine.store('saveStatus').markSaving();
    });
});
