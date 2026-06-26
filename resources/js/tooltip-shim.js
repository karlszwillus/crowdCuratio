/*
 * crowdCuratio — Tooltip-Shim
 *
 * Bootstrap-3 brachte ein eigenes `.tooltip()`-Plugin auf jQuery mit;
 * Markup im Bestand: `<a data-toggle="tooltip" title="...">`. Mit dem
 * Bootstrap-3-JS-Abbau (5a.IV) fiel das Plugin, die `document.ready`-
 * Inits in chapters/index und roles/index warfen
 * `TypeError: $(...).tooltip is not a function`.
 *
 * Die `title`-Attribute auf den Triggern liefern bereits einen
 * nativen Browser-Tooltip. Wir installieren einen leeren Shim, damit
 * die Init-Aufrufe wieder durchlaufen, ohne den jQuery-deferred-Chain
 * zu brechen. Optisches Polishing (custom Tooltip-Komponente mit
 * Tailwind-Tokens) ist in 5b oder spaeter sinnvoll.
 */

function installJQueryShim() {
    if (!window.jQuery) return;
    if (window.jQuery.fn.tooltip && window.jQuery.fn.tooltip.__crowdCuratio) return;

    window.jQuery.fn.tooltip = function () {
        // no-op: native title-Tooltips uebernehmen
        return this;
    };
    window.jQuery.fn.tooltip.__crowdCuratio = true;
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', installJQueryShim);
} else {
    installJQueryShim();
}
