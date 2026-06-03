/**
 * crowdCuratio Frontend-Bootstrap.
 *
 * Vor dem npm-audit-Hotfix wurden hier `lodash` und `axios` als
 * globale Variablen registriert. Beide kamen aus dem Laravel-
 * Default-Stack, wurden aber im App-Code (Blade-Inline-Scripts)
 * nirgends verwendet — die AJAX-Aufrufe gehen alle über jQuery
 * (`$.ajax`/`$.get`). Damit waren beide nur Vehikel für ~25 CVEs,
 * ohne funktionalen Nutzen.
 *
 * Wenn künftig echte AJAX-Calls aus modernem JS hier eingeführt
 * werden sollen, ist nativ `fetch()` der saubere Pfad — kein
 * weiteres Frontend-Lib-Setup nötig.
 */
