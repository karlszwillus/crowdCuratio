/*
 * crowdCuratio - Curating together virtually
 * Copyright (C)2026 - berlinHistory e.V.
 *
 * Tastatur-Alternative zum SortableJS-Drag (WCAG 2.5.7). Strg+Pfeil-
 * hoch/runter auf einem fokussierten Listen-Item (`<li class="chapter">`,
 * `<li class="entry">`, `<li class="item content">`) bewegt es eine
 * Position in seinem Container hoch oder runter. Die neue Reihenfolge
 * wird über die bereits bestehende `chapter.drag`-Route persistiert
 * (gleiche Route, die SortableJS für Maus-Drag nutzt — siehe das
 * jQuery-Setup in chapters/index.blade.php).
 *
 * Move-Announcement an die Live-Region kommt in 5b.6.c — diese Datei
 * liefert noch ein No-Op-Fallback für `window.ccAnnounce`.
 */

if (typeof window.ccAnnounce !== 'function') {
    window.ccAnnounce = () => {};
}

/**
 * Liest die Reihenfolge der Kind-`<li>`-Items aus dem Container
 * anhand des `data-{attribute}`-Attributs (z.B. `data-chapter`).
 */
function collectOrder(container, attribute) {
    return [...container.children]
        .filter((el) => el.tagName === 'LI' && el.hasAttribute(`data-${attribute}`))
        .map((el) => el.getAttribute(`data-${attribute}`));
}

/**
 * Persistiert die neue Reihenfolge via POST an die `chapter.drag`-
 * Route. Payload ist identisch mit dem, was der SortableJS-Handler
 * heute sendet (siehe chapters/index.blade.php Z. 1513+).
 */
async function persistReorder(container) {
    const url = container.dataset.reorderUrl;
    if (! url) {
        console.warn('keyboard-reorder: kein data-reorder-url am Container.');
        return false;
    }

    const element = container.dataset.reorderElement;
    if (! element) {
        console.warn('keyboard-reorder: kein data-reorder-element am Container.');
        return false;
    }

    const data = {
        data: collectOrder(container, element),
        element,
    };

    // Parent-Referenz: für Chapter ist es das Project, für Entry das
    // Chapter, für Content das Entry.
    if (element === 'chapter') {
        data.project = container.dataset.reorderProject;
    } else if (element === 'entry') {
        data.chapter = container.id;
    } else if (element === 'content') {
        data.entry = container.id;
    }

    try {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const response = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-TOKEN': csrf ?? '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: new URLSearchParams({ data: JSON.stringify(data) }).toString(),
        });
        if (! response.ok) {
            // 403 für Reader, 419 bei abgelaufener CSRF, … wir
            // schlucken still und reverten die Anzeige nicht; der
            // Server-Reload bringt den korrekten Zustand zurück.
            console.warn(`keyboard-reorder: Server lehnte ${element}-Reorder ab (${response.status}).`);
            return false;
        }
        return true;
    } catch (err) {
        console.warn('keyboard-reorder: Netzwerk-Fehler beim Persist.', err);
        return false;
    }
}

/**
 * Hängt den Strg+Pfeil-Handler an den Container.
 */
function attachContainer(container) {
    if (container.__crowdCuratioKeyboardReorder) return;
    container.__crowdCuratioKeyboardReorder = true;

    container.addEventListener('keydown', async (event) => {
        if (! event.ctrlKey) return;
        if (event.key !== 'ArrowUp' && event.key !== 'ArrowDown') return;

        const item = event.target.closest('li');
        if (! item || item.parentElement !== container) return;

        event.preventDefault();

        const sibling = event.key === 'ArrowUp'
            ? item.previousElementSibling
            : item.nextElementSibling;

        if (! sibling || sibling.tagName !== 'LI') return;

        if (event.key === 'ArrowUp') {
            container.insertBefore(item, sibling);
        } else {
            container.insertBefore(sibling, item);
        }

        // Fokus bleibt am bewegten Element — der Browser verliert
        // ihn beim DOM-Reorder gelegentlich nicht, aber wir erzwingen
        // den Refokus für eine konsistente Tastatur-Erfahrung.
        item.focus();

        const persisted = await persistReorder(container);
        if (persisted) {
            const position = [...container.children].indexOf(item) + 1;
            const total = container.children.length;
            // Vokabular aus dem Glossar: Projekt > Kapitel > Abschnitt > Inhalt.
            const label = {
                chapter: 'Kapitel',
                entry: 'Abschnitt',
                content: 'Inhalt',
            }[container.dataset.reorderElement] ?? 'Eintrag';
            window.ccAnnounce(`${label} ist jetzt an Position ${position} von ${total}.`);
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-reorder-element]').forEach(attachContainer);
});
