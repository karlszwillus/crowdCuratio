/*
 * crowdCuratio — Modal-Wire-Up
 *
 * Bindet Trigger-abhaengige Modal-Vorbereitungen an das `cc-modal-show`-
 * Event aus modal.js. Ziel: unabhaengig von @push('scripts')-Bloecken
 * in einzelnen Views. Ein JS-Fehler weiter oben in chapters/index
 * darf den Add-Entry-Flow nicht mehr aushebeln.
 *
 * Persona-Smoke 2026-08-15: Vorher setzte ein delegated
 * `$('.addEntry').click`-Handler in chapters/index das hidden
 * `input[name=chapterId]` im Entry-Modal. Wenn der @push-Block
 * abbrach oder ein anderer Reset-Handler das Feld ueberschrieb,
 * kam POST /entries ohne chapterId → StoreEntryRequest false → 403.
 * Der Wire-Up hier ist der zuverlaessige Weg.
 */

function wireEntryModal() {
    const modal = document.getElementById('entryModal');
    if (!modal) return;

    modal.addEventListener('cc-modal-show', (event) => {
        const trigger = event.detail && event.detail.relatedTarget;
        if (!trigger) return;

        const chapterId = trigger.getAttribute('data-id');
        const chapterName = trigger.getAttribute('data-chapter');

        const chapterIdInput = modal.querySelector('input[name="chapterId"]');
        if (chapterIdInput && chapterId) {
            chapterIdInput.value = chapterId;
        }

        const lbl = modal.querySelector('#lblChapter');
        if (lbl && chapterName) {
            lbl.textContent = chapterName;
        }

        // Titel-/Subtitel-Felder auf Add-Mode zuruecksetzen. Modify
        // ist seit Phase 5c.6.b weg (Inline-Editing), Add-Flow bleibt.
        const titleInput = modal.querySelector('#entryTitle');
        if (titleInput) titleInput.value = '';
        const subInput = modal.querySelector('#entrySubtitle');
        if (subInput) subInput.value = '';
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', wireEntryModal);
} else {
    wireEntryModal();
}
