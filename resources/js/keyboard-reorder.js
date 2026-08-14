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

    const order = collectOrder(container, element);

    // Payload muss form-encoded als Array kommen, nicht als JSON-String
    // — `ChapterController::saveDragAndDrop` prüft `isset($payload['data'])`
    // auf einem PHP-Array (kein String-Offset-Access, PHP 8.4). Der
    // SortableJS-Handler in chapters/index.blade.php sendet genau in
    // diesem Format via jQuery-`$.ajax({data: {data: {...}}})`; wir
    // replizieren die exakte Serialisierung mit URLSearchParams-`append`.
    const params = new URLSearchParams();
    for (const id of order) {
        params.append('data[data][]', id);
    }
    params.append('data[element]', element);
    if (element === 'chapter') {
        params.append('data[project]', container.dataset.reorderProject ?? '');
    } else if (element === 'entry') {
        params.append('data[chapter]', container.id);
    } else if (element === 'content') {
        params.append('data[entry]', container.id);
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
            body: params.toString(),
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
        // Shortcut: Alt+ArrowUp / Alt+ArrowDown. Alt (Option auf macOS)
        // ist auf allen drei Desktop-OS system-weit frei, im Gegensatz
        // zu Ctrl (macOS reserviert Ctrl+Pfeil fest für Mission Control
        // und Space-Switching). Konvention wie VS Code für Zeilen-
        // Verschieben.
        if (! event.altKey) return;
        if (event.ctrlKey || event.metaKey || event.shiftKey) return;
        if (event.key !== 'ArrowUp' && event.key !== 'ArrowDown') return;

        const item = event.target.closest('li');
        if (! item || item.parentElement !== container) return;

        event.preventDefault();

        const sibling = event.key === 'ArrowUp'
            ? item.previousElementSibling
            : item.nextElementSibling;

        if (! sibling || sibling.tagName !== 'LI') return;

        // DOM-Swap zunächst optimistisch — der Server-Roundtrip
        // dauert 100–300 ms, den User visuell warten zu lassen wäre
        // träge. Rollback bei Server-Fail weiter unten.
        if (event.key === 'ArrowUp') {
            container.insertBefore(item, sibling);
        } else {
            container.insertBefore(sibling, item);
        }

        // Fokus bleibt am bewegten Element — der Browser verliert
        // ihn beim DOM-Reorder gelegentlich nicht, aber wir erzwingen
        // den Refokus für eine konsistente Tastatur-Erfahrung.
        item.focus();

        // Vokabular aus dem Glossar: Projekt > Kapitel > Abschnitt > Inhalt.
        const label = {
            chapter: 'Kapitel',
            entry: 'Abschnitt',
            content: 'Inhalt',
        }[container.dataset.reorderElement] ?? 'Eintrag';

        const persisted = await persistReorder(container);
        if (persisted) {
            const position = [...container.children].indexOf(item) + 1;
            const total = container.children.length;
            window.ccAnnounce(`${label} ist jetzt an Position ${position} von ${total}.`);
        } else {
            // Server hat abgelehnt (403, 419, 500) oder Netz ist tot.
            // Der DOM-Zustand ist bereits geswapt und würde ohne
            // Rollback bis zum nächsten Reload eine falsche Reihenfolge
            // zeigen. Reset und Screen-Reader-Hinweis.
            if (event.key === 'ArrowUp') {
                container.insertBefore(sibling, item);
            } else {
                container.insertBefore(item, sibling);
            }
            item.focus();
            window.ccAnnounce(`Verschieben von ${label} fehlgeschlagen, Reihenfolge zurückgesetzt.`);
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-reorder-element]').forEach(attachContainer);
});
