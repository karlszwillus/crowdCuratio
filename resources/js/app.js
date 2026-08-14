import './bootstrap';

// Webfonts — IBM Plex Sans als Default-Schrift im Editor, IBM Plex
// Mono für die kleinen Caps-Labels. Source Serif 4 wird nur in den
// Preview-/PDF-Templates geladen (eigener Webfont-Bundle dort), um
// die Editor-Bundle-Grösse klein zu halten.
import '@fontsource/ibm-plex-sans/300.css';
import '@fontsource/ibm-plex-sans/400.css';
import '@fontsource/ibm-plex-sans/500.css';
import '@fontsource/ibm-plex-sans/600.css';
import '@fontsource/ibm-plex-sans/700.css';
import '@fontsource/ibm-plex-mono/400.css';
import '@fontsource/ibm-plex-mono/500.css';

// Theme-Switch — setzt `data-theme` auf `<html>` vor Alpine-Init,
// damit kein Flash zwischen Default- und persistiertem Theme entsteht.
import './theme';

// Alpine-Focus-Plugin — liefert `x-trap` für Fokus-Trap in Modalen
// (WCAG 2.4.11 / 2.1.2, Focus Order und No Keyboard Trap). Wird via
// `alpine:init` an Livewires Alpine-Instance registriert, damit die
// Konvention aus ADR-0025 (keine zweite Alpine-Instance) greift.
import focus from '@alpinejs/focus';
document.addEventListener('alpine:init', () => {
    window.Alpine.plugin(focus);
});

// Alpine wird von Livewire 4 selbst gebündelt und gestartet. Eine
// zweite Alpine-Instance hier wurde mit „Detected multiple instances
// of Alpine running" im Browser sichtbar — `<template x-if>` rendert
// dann leer, weil sich die zwei Instanzen die DOM-Walks streitig
// machen. Wir reichen das Plugin-Registrieren über `alpine:init` an
// Livewires Alpine; `window.Alpine` wird von Livewire selbst gesetzt.

// Vanilla-Modal-Manager — ersetzt das Bootstrap-3-Modal-Plugin.
// Markup im Bestand bleibt unverändert (`<div class="modal fade">`),
// programmatische `$('#xxx').modal('show')`-Aufrufe gehen über ein
// jQuery-Shim in modal.js weiter.
import './modal';

// Live-Breadcrumb — registriert die Alpine-Komponente `ccBreadcrumb`,
// die in <x-ui.breadcrumb :tree="..."> verwendet wird. Reagiert auf
// hashchange und leitet aus dem Tree-Daten-Objekt den aktuellen Pfad
// (Projekt > Kapitel > Abschnitt) ab.
import './breadcrumb';

// Auto-Save-Indikator (Phase 5c.2): Alpine-Store saveStatus,
// gefüttert von den Livewire-Events des Inline-Editors, gerendert
// im Header rechts vom Theme-Toggle.
import './save-status';

// Toast-Notifications (Phase 5c.3): Alpine-Store toast plus
// window.ccToast(). Save-Failed-Bridge hängt automatisch.
import './toast';

// Rich-Text-Inline-Editor (Phase 5c.6.c.4): Alpine-Data-Factory
// `richTextEditor(initialHtml)` mit Quill-Bridge, verwendet von
// <livewire:rich-text-editor>. Quill selbst kommt über den
// bestehenden CDN-Include; das JS hier verkabelt nur die Bridge.
import './rich-text-editor';

// Vanilla-Typeahead-Manager — ersetzt bootstrap-3-typeahead.js. Auch
// hier ein jQuery-Shim, damit die fünf `$('#xxx').typeahead({...})`-
// Aufrufe in den Editor-Views unverändert weiterlaufen.
import './typeahead';

// Vanilla-DataTable-Manager — ersetzt jquery.dataTables. jQuery-Shim
// `$('#xxxList').DataTable({...})` für die drei bestehenden Aufrufe
// in projects/index, users/index, contents/comment. Bietet Suche,
// Sortierung per Header-Klick und Pagination.
import './datatable';

// Sortable-Shim — `.sortable(opts)` läuft jetzt über SortableJS (über
// das `Sortable.min.js`-CDN-Bundle schon im Stack), nicht mehr über
// jQuery-UI. Damit fällt jQuery-UI als Abhängigkeit.
import './sortable-shim';

// ARIA-Live-Region-Announcer (WCAG 4.1.3): globale Funktion
// window.ccAnnounce(message) füllt die zentrale Live-Region im
// Layout. MUSS vor Modulen geladen werden, die die Funktion rufen.
import './announcer';

// Tastatur-Alternative für SortableJS-Drag (WCAG 2.5.7): Strg+Pfeil-
// hoch/runter auf einem fokussierten Listen-Item bewegt es eine
// Position. Persistiert über die gleiche `chapter.drag`-Route wie
// der Maus-Drag.
import './keyboard-reorder';

// Tooltip-Shim — Bootstrap-3-`.tooltip()`-Plugin ist mit BS3-JS gefallen,
// die `document.ready`-Inits in chapters/index und roles/index brauchen
// noch einen Aufruf, der nicht knallt. Native title-Tooltips greifen.
import './tooltip-shim';
