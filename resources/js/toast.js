/*
 * crowdCuratio - Curating together virtually
 * Copyright (C)2026 - berlinHistory e.V.
 *
 * Toast-Notifications (Phase 5c.3).
 *
 * Alpine-Store `toast` mit einer Liste aktiver Toasts, plus einer
 * `push(text, type)`-API für die Bridge zu Livewire-Events und
 * direktem Aufruf aus anderem JS. Toasts werden nach 5000 ms
 * automatisch entfernt.
 *
 * Types:
 *   - 'error'   — rot, für Save-Failed und ähnliche Fehler.
 *   - 'success' — grün, für positive Bestätigungen (in dieser Welle
 *                 nicht genutzt, aber vorbereitet).
 *   - 'info'    — neutral.
 *
 * Die Toast-Region selbst lebt im Layout (Blade-Template rendert
 * die Liste reaktiv via `x-for`).
 *
 * Store wird via `alpine:init` an Livewires Alpine gehängt, damit
 * kein Race mit einer separaten Alpine-Instance entsteht
 * (siehe ADR-0025-Pattern).
 */

const AUTO_DISMISS_MS = 5000;

document.addEventListener('alpine:init', () => {
    window.Alpine.store('toast', {
        items: [],

        push(text, type = 'info') {
            const id = Date.now() + Math.random();
            this.items.push({ id, text, type });

            setTimeout(() => {
                this.dismiss(id);
            }, AUTO_DISMISS_MS);
        },

        dismiss(id) {
            this.items = this.items.filter((item) => item.id !== id);
        },
    });

    // Bridge: Livewire-Event `save-failed` löst automatisch einen
    // Fehler-Toast aus. Text kommt aus dem Event-Detail.
    window.addEventListener('save-failed', (event) => {
        const detail = event.detail?.[0] ?? event.detail ?? {};
        const message = detail.message ?? 'Speichern fehlgeschlagen.';
        window.Alpine.store('toast').push(message, 'error');
    });
});

// Öffentliche API auch außerhalb Alpine — z.B. für andere JS-Module,
// die Toasts triggern wollen (Netzwerk-Fehler, Async-Fails).
window.ccToast = function ccToast(text, type = 'info') {
    if (! window.Alpine || ! window.Alpine.store('toast')) {
        console.warn('ccToast: Alpine-Store noch nicht bereit.');
        return;
    }
    window.Alpine.store('toast').push(text, type);
};
