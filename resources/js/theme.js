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
    } catch (e) {
        // localStorage kann werfen: Quota überschritten, Private-Mode
        // im Safari, deaktivierte Cookies. Das Theme greift trotzdem
        // (data-theme ist gesetzt), nur die Persistenz über Page-Reload
        // hinaus fehlt. console.warn statt silent swallow, damit der
        // Drift in DevTools sichtbar wird (Phase-5a-Code-Review H-3).
        // eslint-disable-next-line no-console
        console.warn('crowdCuratio theme persistence failed:', e);
    }
}

// Frühe Anwendung — passiert vor Alpine.start().
applyTheme(readStoredTheme());

function registerThemeStore() {
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
}

/*
 * Livewire 4 bringt sein eigenes Alpine mit und startet es früh —
 * möglicherweise BEVOR dieses Vite-Module geladen ist. In dem Fall
 * ist das `alpine:init`-Event bereits gefeuert, und ein nachträglich
 * angemeldeter Listener bekommt es nicht mehr mit. Folge: der
 * `theme`-Store wird nie registriert, $store.theme bleibt leer, die
 * x-show-Bedingungen an den Sun-/Moon-Spans evaluieren beide zu
 * undefined und beide Icons bleiben unsichtbar (kombiniert mit
 * x-cloak).
 *
 * Robust: wenn Alpine bereits da ist, sofort registrieren; sonst
 * auf alpine:init warten.
 */
if (window.Alpine) {
    registerThemeStore();
} else {
    document.addEventListener('alpine:init', registerThemeStore);
}

window.crowdCuratioTheme = {
    apply: applyTheme,
    current: readStoredTheme,
    DEFAULT: DEFAULT_THEME,
    ALT: ALT_THEME,
};
