/*
 * crowdCuratio — Theme-Switch
 *
 * Wechselt zwischen Default-Theme (`crowdCuratio`, rot/dunkel-Chrome)
 * und `aktivesMuseum` (gelb/hell-Chrome). Setzt `data-theme` auf dem
 * `<html>`-Element, persistiert die Wahl in `localStorage`.
 *
 * Alpine-Store `theme` mit Properties:
 *   - current: 'crowdCuratio' | 'aktivesMuseum'
 *   - toggle(): wechselt zwischen den beiden
 *   - set(name): setzt explizit
 *
 * Beim Page-Load läuft die Wiederherstellung *früh* (vor Alpine-Init),
 * damit kein Flash-of-Default-Theme zwischen Render und Hydrate sichtbar
 * wird.
 */

const STORAGE_KEY = 'cc-theme';
const DEFAULT_THEME = 'crowdCuratio';
const ALT_THEME = 'aktivesMuseum';

function readStoredTheme() {
    try {
        const value = window.localStorage.getItem(STORAGE_KEY);
        return value === ALT_THEME || value === DEFAULT_THEME ? value : DEFAULT_THEME;
    } catch (e) {
        return DEFAULT_THEME;
    }
}

function applyTheme(name) {
    const html = document.documentElement;
    if (name === DEFAULT_THEME) {
        html.removeAttribute('data-theme');
    } else {
        html.setAttribute('data-theme', name);
    }
    try {
        window.localStorage.setItem(STORAGE_KEY, name);
    } catch (e) { /* noop */ }
}

// Frühe Anwendung — passiert vor Alpine.start().
applyTheme(readStoredTheme());

document.addEventListener('alpine:init', () => {
    if (!window.Alpine) return;
    window.Alpine.store('theme', {
        current: readStoredTheme(),
        toggle() {
            this.current = this.current === DEFAULT_THEME ? ALT_THEME : DEFAULT_THEME;
            applyTheme(this.current);
        },
        set(name) {
            this.current = name === ALT_THEME ? ALT_THEME : DEFAULT_THEME;
            applyTheme(this.current);
        },
    });
});

window.crowdCuratioTheme = {
    apply: applyTheme,
    current: readStoredTheme,
    DEFAULT: DEFAULT_THEME,
    ALT: ALT_THEME,
};
