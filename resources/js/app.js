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

// Alpine.js 3: explizites Start-up. In 2.x lief der Start
// automatisch beim require, in 3.x muss er gefeuert werden.
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

// Vanilla-Modal-Manager — ersetzt das Bootstrap-3-Modal-Plugin.
// Markup im Bestand bleibt unverändert (`<div class="modal fade">`),
// programmatische `$('#xxx').modal('show')`-Aufrufe gehen über ein
// jQuery-Shim in modal.js weiter.
import './modal';

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
