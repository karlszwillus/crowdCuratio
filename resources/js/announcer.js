/*
 * crowdCuratio - Curating together virtually
 * Copyright (C)2026 - berlinHistory e.V.
 *
 * Globale Funktion `window.ccAnnounce(message)`, die in die zentrale
 * ARIA-Live-Region `#cc-live-announcer` in der Layout-Komponente
 * schreibt. Screen-Reader lesen den Text höflich vor — geeignet für
 * Move-Bestätigungen, Status-Updates aus Async-Aktionen etc.
 *
 * Die Live-Region selbst sitzt in resources/views/components/layout.blade.php.
 * Wenn das Layout nicht greift (z.B. Auth-Views), legt diese Funktion
 * still ein No-Op vor — kein DOM-Element, kein Announce.
 */

window.ccAnnounce = function ccAnnounce(message) {
    const region = document.getElementById('cc-live-announcer');
    if (! region) {
        return;
    }

    // Kurzer Reset, damit der Screen-Reader auch identische Folge-
    // Messages erneut vorliest. Ohne den Reset würde z.B. zwei Mal
    // dieselbe Position keine zweite Ansage auslösen.
    region.textContent = '';

    // Auf den nächsten Frame warten, damit der Reset durchschlägt.
    requestAnimationFrame(() => {
        region.textContent = message;
    });
};
