/**
 * crowdCuratio - Curating together virtually
 * Copyright (C)2026 - berlinHistory e.V.
 *
 * Phase 5ab.4 (Design v6 § 6): Diff-Modus fuer den Editor.
 *
 * Wenn im Verlauf-Panel eine Fassungs-Karte angeklickt wird, dispatched
 * die Livewire-Komponente `revision-selected` mit einem `fields`-Objekt.
 * Fuer jedes Feld liegt eine fertige Diff-HTML (Wort-Ebene) bereit —
 * wir haengen sie an die passenden Block-Elemente per data-Attribut.
 *
 * Zusaetzlich schaltet das Modul den Editor in den Ansicht-Modus:
 * Info-Banner oben, Block-Aktionen gedimmt (data-history-lock), damit
 * unter dem Vergleich nicht bearbeitet werden kann (§ 6, § 7).
 *
 * Reset lauft ueber `history:diff-close`.
 */

const BLOCK_SELECTOR = '[data-history-subject]';
const BANNER_ID = 'cc-history-diff-banner';

/**
 * Findet alle Block-Elemente, die zu einem gegebenen Subject gehoeren.
 * data-history-subject ist im Format "Chapter:5".
 */
function findBlockElements(subjectType, subjectId) {
    const marker = `${subjectType}:${subjectId}`;
    return document.querySelectorAll(`${BLOCK_SELECTOR}[data-history-subject="${marker}"]`);
}

function ensureBanner() {
    let banner = document.getElementById(BANNER_ID);
    if (banner) return banner;
    banner = document.createElement('div');
    banner.id = BANNER_ID;
    banner.className =
        'fixed left-1/2 top-4 z-30 flex -translate-x-1/2 items-center gap-3 rounded-md border border-warning bg-warning-bg px-4 py-2 text-caption text-warning shadow-medium';
    banner.setAttribute('role', 'status');
    banner.innerHTML = `
        <span class="font-medium">${window.ccI18n?.historyDiffBanner || 'Nur zum Ansehen — Vergleichsmodus aktiv'}</span>
        <button
            type="button"
            data-history-diff-close
            class="rounded-md border border-warning bg-paper-0 px-2 py-0.5 text-caption text-ink-900 hover:bg-canvas-bg"
        >${window.ccI18n?.historyDiffClose || 'Vergleich schließen'}</button>
    `;
    document.body.appendChild(banner);
    banner.querySelector('[data-history-diff-close]').addEventListener('click', () => {
        window.dispatchEvent(new CustomEvent('history:diff-close'));
    });
    return banner;
}

function enterDiffMode(subjectType, subjectId, fields) {
    document.documentElement.dataset.historyDiff = 'on';
    ensureBanner();

    const blocks = findBlockElements(subjectType, subjectId);
    blocks.forEach((block) => {
        block.setAttribute('data-history-lock', 'on');
        Object.entries(fields).forEach(([field, diff]) => {
            const target = block.querySelector(`[data-history-field="${field}"]`);
            if (!target) return;
            // Statt innerHTML zu ueberschreiben (das reisst Livewire-
            // Rich-Text-Editors auseinander), blenden wir eine Overlay-
            // <div> ein. CSS versteckt die Original-Kinder — der
            // Livewire-Editor bleibt inkl. Alpine-State im DOM.
            target.setAttribute('data-history-diff-active', 'on');
            let overlay = target.querySelector(':scope > .cc-history-diff-overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.className = 'cc-history-diff-overlay';
                target.appendChild(overlay);
            }
            overlay.innerHTML = diff.html;
        });
    });
}

function exitDiffMode() {
    delete document.documentElement.dataset.historyDiff;
    document.getElementById(BANNER_ID)?.remove();
    document
        .querySelectorAll('[data-history-field][data-history-diff-active="on"]')
        .forEach((target) => {
            target.removeAttribute('data-history-diff-active');
            const overlay = target.querySelector(':scope > .cc-history-diff-overlay');
            overlay?.remove();
        });
    document.querySelectorAll('[data-history-lock="on"]').forEach((block) => {
        block.removeAttribute('data-history-lock');
    });
}

// Livewire dispatched das Event ueber das window-Objekt.
window.addEventListener('revision-selected', (event) => {
    const detail = event.detail?.[0] ?? event.detail;
    if (!detail) return;
    const { subjectType, subjectId, fields } = detail;
    if (!subjectType || !subjectId || !fields) return;
    enterDiffMode(subjectType, Number(subjectId), fields);
});

window.addEventListener('history:diff-close', () => {
    exitDiffMode();
});

// ESC im Diff-Modus schliesst den Vergleich, aber NUR wenn kein Panel
// offen ist (das ESC-Handling der Panels hat Vorrang, sonst schliesst
// eine Taste zwei Sachen auf einmal).
document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    if (document.documentElement.dataset.historyDiff !== 'on') return;
    exitDiffMode();
});
