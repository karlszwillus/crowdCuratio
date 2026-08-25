/**
 * crowdCuratio - Curating together virtually
 * Copyright (C)2026 - berlinHistory e.V.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * See LICENSE.
 */

/**
 * Alpine-Data-Factory für <livewire:rich-text-editor>. Instanziiert
 * pro Editor-Instanz einen Quill-Editor auf dem übergebenen DOM-Node
 * und pusht HTML-Änderungen debounced an die Volt-Component.
 *
 * Ansatz statt wire:model:
 * - Quill schreibt direkt in seinen eigenen DOM-Tree; ein
 *   wire:model-Roundtrip würde bei jedem Buchstaben das Editor-DOM
 *   invalidieren und die Caret-Position zerstören.
 * - Wir horchen auf Quills `text-change`, debouncen 1500 ms und
 *   rufen dann `$wire.save(html)`. Der Save läuft serverseitig
 *   durch Validation + Gate wie im inline-editor, dispatched
 *   `saved` / `save-failed` mit identischer Payload.
 *
 * Toolbar-Definition ist bewusst schmal — die alte Quill-Init im
 * chapters/index.blade.php-Script-Block hatte ein umfangreicheres
 * Setup (Fonts, Farben, RTL). Wir starten hier mit Basics; erweitern
 * lässt sich die Toolbar über die windows.ccRichTextToolbar-Config,
 * ohne die Volt-Component anzufassen.
 */

const DEFAULT_TOOLBAR = [
    [{ header: [1, 2, 3, false] }],
    ['bold', 'italic', 'underline', 'strike'],
    [{ list: 'ordered' }, { list: 'bullet' }],
    ['link'],
    ['clean'],
];

function debounce(fn, wait) {
    let timer = null;
    return (...args) => {
        if (timer) window.clearTimeout(timer);
        timer = window.setTimeout(() => fn(...args), wait);
    };
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('richTextEditor', (initialHtml) => ({
        quill: null,
        initialHtml: initialHtml ?? '',

        initQuill(container) {
            if (typeof window.Quill === 'undefined') {
                // Quill wird erst vom bestehenden CDN-Include auf
                // dem Chapter-Edit-Screen geladen. Auf Screens ohne
                // Quill-Bundle fallen wir stumm auf ein contenteditable
                // zurück, damit die Editor-Zeile nicht komplett
                // verschwindet.
                container.setAttribute('contenteditable', 'true');
                container.innerHTML = this.initialHtml;
                return;
            }

            const toolbar = window.ccRichTextToolbar ?? DEFAULT_TOOLBAR;

            this.quill = new window.Quill(container, {
                modules: { toolbar },
                theme: 'snow',
            });

            // Initial-HTML einspielen — Karls Bug-Report 2026-08-21:
            // `dangerouslyPasteHTML(html)` mit nur einem Argument pastet
            // an die aktuelle Selection, was bei frisch instanziiertem
            // Quill (kein Fokus) einen Leer-Newline-Vorspann im Delta
            // hinterlaesst. Der DOM zeigt nur den Content, aber der
            // interne Delta hat einen unsichtbaren Anfangs-Block; beim
            // Tippen an DOM-Position 0 landet der Insert an einer Stelle,
            // die im DOM nicht sichtbar ist, Backspace loescht dann das
            // falsche Zeichen. `setContents(clipboard.convert(...))`
            // ersetzt den Delta komplett und haelt DOM und Delta synchron.
            if (this.initialHtml !== '') {
                // Bug-Fix 2026-08-21: der frueher benutzte
                // `clipboard.dangerouslyPasteHTML(this.initialHtml)`
                // pastet an die aktuelle Selection (bei frisch
                // instanziiertem Quill = null) und hinterlaesst einen
                // unsichtbaren Leer-Vorspann im Delta. Symptome:
                // Backspace am Anfang loescht ins Leere, Backspace am
                // Ende wirkt erst nach Fokus-Wechsel, Zeichen bleiben
                // stehen. Direct-DOM-Init + explizite Quill-Sync loesen
                // das sauber — Quill parst den DOM neu und baut Delta
                // + Selection konsistent darueber.
                this.quill.root.innerHTML = this.initialHtml;
                this.quill.update('silent');
            }

            const pushSave = debounce(() => {
                const html = this.quill.root.innerHTML;
                if (html === this.initialHtml) return;
                this.initialHtml = html;

                if (this.$wire) {
                    // Alpine `this.$wire` ist die Volt-Component-Bridge.
                    // Fehler beim Save-Roundtrip landen im dispatched
                    // `save-failed` und im Toast (5c.3).
                    this.$wire.call('save', html);
                }
            }, 1500);

            this.quill.on('text-change', (_delta, _oldDelta, source) => {
                // Nur User-eigene Änderungen speichern — programmatische
                // Änderungen (z. B. das initial-Paste oben) sollen
                // kein Save auslösen.
                if (source === 'user') {
                    pushSave();
                }
            });
        },
    }));
});
