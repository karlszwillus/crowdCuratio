/*
 * crowdCuratio - Curating together virtually
 * Copyright (C)2026 - berlinHistory e.V.
 *
 * Live-Breadcrumb-Logik für die <x-ui.breadcrumb :tree="...">-Variante.
 * Wird via `alpine:init` registriert, damit sie an Livewires Alpine-
 * Instance hängt (siehe ADR-0025-Pattern: zweite Alpine-Instance hier
 * vermeiden).
 *
 * Die Komponente parsed window.location.hash der Form
 * `#anchor_Chapter_{id}` oder `#anchor_Entry_{id}`, navigiert durch
 * das Tree-Daten-Objekt und setzt `this.path` als reaktive
 * Item-Liste, die das Blade-Template via x-for rendert.
 */

document.addEventListener('alpine:init', () => {
    window.Alpine.data('ccBreadcrumb', (tree) => ({
        tree,
        path: [],

        syncFromHash() {
            const hash = window.location.hash;
            const rootItem = this.tree.root;

            // Kapitel-Pfad: Projekt > Kapitel
            const chapterMatch = hash.match(/^#anchor_Chapter_(\d+)$/);
            if (chapterMatch) {
                const chapter = this.tree.chapters?.[chapterMatch[1]];
                if (chapter) {
                    this.path = [
                        rootItem,
                        { label: chapter.label, href: chapter.href },
                    ];
                    return;
                }
            }

            // Abschnitt-Pfad: Projekt > Kapitel > Abschnitt. Wir kennen
            // die Kapitel-Zugehörigkeit nicht aus dem Hash, müssen also
            // den Tree durchsuchen, bis der Entry gefunden ist.
            const entryMatch = hash.match(/^#anchor_Entry_(\d+)$/);
            if (entryMatch) {
                for (const chapter of Object.values(this.tree.chapters ?? {})) {
                    const entry = chapter.entries?.[entryMatch[1]];
                    if (entry) {
                        this.path = [
                            rootItem,
                            { label: chapter.label, href: chapter.href },
                            { label: entry.label, href: entry.href },
                        ];
                        return;
                    }
                }
            }

            // Fallback: nur Projekt-Stamm.
            this.path = [rootItem];
        },
    }));
});
