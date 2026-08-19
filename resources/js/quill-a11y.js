/**
 * crowdCuratio - Curating together virtually
 * Copyright (C)2026 - berlinHistory e.V.
 *
 * See LICENSE.
 */

/**
 * Q3-Härtung F4 (2026-08-19) / A11y-Live-Fund LIVE-02.
 *
 * Quill-Toolbar rendert ihre Formatier-Buttons als reine SVG-Icons
 * ohne `aria-label` und ohne `title` — für Screenreader unbenannt
 * (WCAG 4.1.2 „Name, Role, Value"). Bestand: ~85 Buttons ohne Label
 * quer über den Editor.
 *
 * Wir horchen per MutationObserver auf neue `.ql-toolbar`-Nodes im
 * Document und labeln ihre Buttons nach der Quill-CSS-Klasse
 * (`.ql-bold` → „Fett", `.ql-italic` → „Kursiv", …). Damit greifen
 * wir für beide Quill-Init-Stellen (Volt-Editor in
 * `rich-text-editor.js` und Inline-Script in `chapters/index.blade.php`)
 * ohne dass die einzelnen Init-Stellen angefasst werden müssen.
 *
 * Werte-Buttons (Header-Level 1/2/3, ausgerichtet, ordered/bullet)
 * bekommen ein Präfix, damit „Header 2" vs. „Header 3" auseinander
 * gehen. Select-Elemente (Font/Size-Picker) bekommen einen
 * `aria-label` am Root — ihre Optionen bleiben unbeschriftet, weil
 * Quill sie erst beim Öffnen aufklappt.
 */

const LABEL_BY_CLASS = {
    'ql-bold': 'Fett',
    'ql-italic': 'Kursiv',
    'ql-underline': 'Unterstrichen',
    'ql-strike': 'Durchgestrichen',
    'ql-blockquote': 'Zitat',
    'ql-code-block': 'Codeblock',
    'ql-link': 'Link einfügen',
    'ql-image': 'Bild einfügen',
    'ql-video': 'Video einfügen',
    'ql-clean': 'Formatierung entfernen',
    'ql-indent': 'Einrücken',
    'ql-direction': 'Textrichtung wechseln',
    'ql-formula': 'Formel einfügen',
    'ql-script': 'Hoch-/Tiefstellen',
};

const LABEL_BY_VALUE = {
    list: { ordered: 'Nummerierte Liste', bullet: 'Aufzählungsliste' },
    header: { 1: 'Überschrift 1', 2: 'Überschrift 2', 3: 'Überschrift 3', 4: 'Überschrift 4' },
    align: { '': 'Links ausrichten', center: 'Zentrieren', right: 'Rechts ausrichten', justify: 'Blocksatz' },
    indent: { '-1': 'Ausrücken', '+1': 'Einrücken' },
    script: { sub: 'Tiefstellen', super: 'Hochstellen' },
};

function labelToolbar(toolbar) {
    if (!toolbar || toolbar.dataset.ccA11yLabeled === '1') return;
    toolbar.dataset.ccA11yLabeled = '1';

    toolbar.querySelectorAll('button').forEach(btn => {
        if (btn.getAttribute('aria-label')) return;

        // Klasse-basiertes Label (`ql-bold`, `ql-italic`, …)
        const classKey = [...btn.classList].find(c => LABEL_BY_CLASS[c]);
        if (classKey) {
            const label = LABEL_BY_CLASS[classKey];
            btn.setAttribute('aria-label', label);
            if (!btn.title) btn.title = label;
            return;
        }

        // Value-basiertes Label (`.ql-list[value="ordered"]`)
        for (const [classFragment, valueMap] of Object.entries(LABEL_BY_VALUE)) {
            if (btn.classList.contains(`ql-${classFragment}`)) {
                const val = btn.getAttribute('value') ?? '';
                const label = valueMap[val];
                if (label) {
                    btn.setAttribute('aria-label', label);
                    if (!btn.title) btn.title = label;
                }
                return;
            }
        }
    });

    // Select-Elemente (Font/Size/Header-Picker)
    toolbar.querySelectorAll('select').forEach(select => {
        if (select.getAttribute('aria-label')) return;
        for (const classFragment of Object.keys(LABEL_BY_VALUE)) {
            if (select.classList.contains(`ql-${classFragment}`)) {
                const map = { header: 'Überschriften-Auswahl', align: 'Ausrichtung', list: 'Listen-Format' };
                select.setAttribute('aria-label', map[classFragment] ?? classFragment);
                return;
            }
        }
        // Fallback für alle nicht-gemappten Selects (Font, Size, Color, Background)
        const cls = [...select.classList].find(c => c.startsWith('ql-') && c !== 'ql-picker');
        if (cls) {
            const nice = cls.replace(/^ql-/, '').replace(/-/g, ' ');
            select.setAttribute('aria-label', `${nice}-Auswahl`);
        }
    });
}

function labelAll() {
    document.querySelectorAll('.ql-toolbar').forEach(labelToolbar);
}

// Sofort greifen, wenn DOM schon steht — sonst nach DOMContentLoaded.
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', labelAll);
} else {
    labelAll();
}

// Neue Toolbars (Livewire-Refresh, Alpine-Init, Late-Load) mitnehmen.
const observer = new MutationObserver(mutations => {
    let needsScan = false;
    for (const m of mutations) {
        for (const node of m.addedNodes) {
            if (node.nodeType !== 1) continue;
            if (node.classList?.contains('ql-toolbar') ||
                node.querySelector?.('.ql-toolbar')) {
                needsScan = true;
                break;
            }
        }
        if (needsScan) break;
    }
    if (needsScan) labelAll();
});

observer.observe(document.body, { childList: true, subtree: true });
