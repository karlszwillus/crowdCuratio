/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 *
 * Q4-Etappe 7 · E7-4 (2026-09-11): Autosave-Layer fuer die
 * Metadaten-Sicht.
 *
 * Konsolidiert das Speicherverhalten mit dem Bearbeiten-Screen:
 * jede Feld-Aenderung geht nach 1500 ms Debounce per AJAX an denselben
 * `projects.update`-Endpoint. Der Backend-Controller antwortet auf
 * `wantsJson()` mit `{ ok, savedAt, savedAtLabel }`, sonst wie bisher
 * mit Redirect+Flash — der klassische Submit-Pfad bleibt also
 * unveraendert.
 *
 * Datei-Uploads (Logo, Leitbild) sind vom Autosave ausgenommen: FormData
 * enthaelt Files, aber der Autosave-Debounce ist unhandlich fuer einen
 * mehrfach getriggerten Upload derselben Datei. Wir markieren den Store
 * als `fileDirty`, sobald ein File-Input geaendert wird — Autosave
 * pausiert dann, der Kopf-Button wechselt auf „Speichern (Bild inkl.)"
 * und fuehrt einen klassischen Form-Submit aus.
 *
 * State-Modell (Alpine-Store `metadataAutosave`):
 *   dirty         — offene Aenderungen im Text/Select/Checkbox-Bereich
 *   fileDirty     — offener File-Input (Autosave pausiert)
 *   saving        — Roundtrip laeuft gerade
 *   savedAtLabel  — HH:MM des letzten erfolgreichen Saves
 *   lastError     — Fehlermeldung oder null
 */

document.addEventListener('alpine:init', () => {
    window.Alpine.store('metadataAutosave', {
        // Runtime-State
        dirty: false,
        fileDirty: false,
        saving: false,
        savedAtLabel: '',
        lastError: null,

        // Interne Referenzen — via init() gesetzt
        _formEl: null,
        _actionUrl: null,
        _csrfToken: null,
        _timer: null,

        /**
         * Wird vom `<form x-init>` in projects/create.blade.php gerufen.
         * Uebergibt die Form-Referenz und den initialen Speicher-Timestamp
         * (Server-Renderzeit).
         */
        init(formEl, actionUrl, csrfToken, initialSavedAtLabel) {
            this._formEl = formEl;
            this._actionUrl = actionUrl;
            this._csrfToken = csrfToken;
            this.savedAtLabel = initialSavedAtLabel || '';
        },

        /**
         * Wird von @input.capture / @change.capture auf dem Form gerufen.
         * Setzt dirty und plant einen Save — bei File-Inputs pausiert
         * der Autosave (Datei geht nur ueber expliziten Submit-Button).
         */
        markDirty(event) {
            this.lastError = null;
            const target = event?.target;
            if (target && target.type === 'file' && target.files && target.files.length > 0) {
                // Karl 2026-09-11: Nach Wegfall des Speichern-Buttons
                // gibt es keinen expliziten Trigger fuer Datei-Uploads
                // mehr. Wir loesen den klassischen Form-Submit hier
                // direkt aus — das laedt die Seite mit dem
                // Server-Redirect neu, das ausgewaehlte Bild ist
                // gespeichert und der Kopf-State ist synchron.
                this.fileDirty = true;
                this.dirty = true;
                clearTimeout(this._timer);
                this._formEl?.submit();
                return;
            }
            this.dirty = true;
            if (!this.fileDirty) {
                this._scheduleSave();
            }
        },

        _scheduleSave() {
            clearTimeout(this._timer);
            this._timer = setTimeout(() => this.save(), 1500);
        },

        /**
         * Speichern. Bei fileDirty geht der klassische Form-Submit
         * durch (mit Redirect), sonst AJAX-POST ohne Files.
         */
        async save() {
            if (!this._formEl) return;

            if (this.fileDirty) {
                // Klassischer Submit — der Controller entscheidet per
                // wantsJson()==false wieder auf Redirect+Flash.
                this._formEl.submit();
                return;
            }

            if (this.saving) return;
            clearTimeout(this._timer);
            this.saving = true;

            const fd = new FormData(this._formEl);
            // File-Inputs aus dem Payload entfernen — der Autosave-Weg
            // ist bewusst dateifrei.
            for (const el of this._formEl.querySelectorAll('input[type=file]')) {
                if (el.name) fd.delete(el.name);
            }
            // Karl 2026-09-11: Quill-Editoren (imprint/terms/description)
            // schreiben ihren HTML-Wert nur beim nativen Form-Submit in
            // versteckte Textareas (jQuery-Handler in create.blade.php).
            // Beim Autosave via fetch feuert dieser Submit-Event nicht —
            // ohne Sync landen die Felder als leer im Request und
            // scheitern an der `required`-Regel (imprint). Hier
            // synchronisieren wir die drei bekannten Editor-Container.
            this._syncQuillFields(fd);

            try {
                const res = await fetch(this._actionUrl, {
                    method: 'POST',
                    body: fd,
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': this._csrfToken || '',
                    },
                    credentials: 'same-origin',
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();
                this.savedAtLabel = data.savedAtLabel || this.savedAtLabel;
                this.dirty = false;
                this.lastError = null;
                window.ccAnnounce?.(data.message || 'Gespeichert.');
            } catch (err) {
                this.lastError = err.message || 'save failed';
                window.ccToast?.('Speichern fehlgeschlagen — Werte bleiben stehen.', 'error');
            } finally {
                this.saving = false;
            }
        },

        /**
         * Quill-Editor-Werte (imprint/terms/description) in die FormData
         * schreiben. Die Editor-Container haben feste IDs; jeder hat
         * intern eine `.ql-editor` mit dem aktuellen HTML.
         */
        _syncQuillFields(fd) {
            const map = {
                imprintId: 'imprint',
                termsId: 'terms',
                descriptionId: 'description',
            };
            for (const [containerId, fieldName] of Object.entries(map)) {
                const container = document.getElementById(containerId);
                if (!container) continue;
                const editor = container.querySelector('.ql-editor');
                if (!editor) continue;
                fd.set(fieldName, editor.innerHTML);
            }
        },

        /**
         * „Aenderungen verwerfen": laedt die Seite neu und holt damit
         * den Server-State (offene File-Auswahlen inklusive). Deshalb
         * kein Confirm-Dialog — Alpine hat kein browser-natives „unsaved
         * changes"-Warning fuer AJAX-Saves, aber der Nutzer sieht am
         * Kopf-Button, dass er dirty ist.
         */
        discard() {
            window.location.reload();
        },
    });
});
